<?php

namespace App\Http\Controllers;

use App\Models\RequestDocument;
use App\Models\SchoolFormStudent as Student;
use App\Models\SchoolFormUpload as GradeSheetUpload;
use App\Models\Student as RequestStudent;
use App\Support\AcademicPeriod;
use App\Support\DocumentQrCode;
use App\Support\DocumentWorkbookVerification;
use App\Support\Form137WorkbookGenerator;
use App\Support\GradeSheetImporter;
use App\Support\LearningAreaNormalizer;
use App\Support\SchoolProfile;
use App\Support\XlsxWorkbookReader;
use Barryvdh\DomPDF\Facade\Pdf;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class SchoolFormF137Controller extends Controller
{
    public function preview(Request $request, SchoolProfile $schoolProfile)
    {
        $this->authorizeRecordsStaff();
        [$student, $records, $schoolLevel, $requestId] = $this->requestedStudentRecords($request);
        $profile = $schoolProfile->values();
        $nameParts = $this->splitName($student->name);

        return view('school-forms.f137-preview', compact('student', 'records', 'profile', 'nameParts', 'requestId', 'schoolLevel'));
    }

    public function pdf(Request $request, SchoolProfile $schoolProfile)
    {
        $this->authorizeRecordsStaff();
        [$student, $records, $schoolLevel, $requestId] = $this->requestedStudentRecords($request);
        $profile = $schoolProfile->values();
        $nameParts = $this->splitName($student->name);
        $fileIdentifier = $student->student_number ?: $student->lrn;

        $html = view('school-forms.pdf.f137-template', compact(
            'student',
            'records',
            'profile',
            'nameParts',
            'requestId',
            'schoolLevel',
        ))->render();
        $options = new Options;
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true);
        $pdf = new Dompdf($options);
        $pdf->loadHtml($html);
        $pdf->setPaper('a4', 'portrait');
        $pdf->render();

        return response($pdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="F137-'.$fileIdentifier.'.pdf"',
        ]);
    }

    public function download(
        Request $request,
        Form137WorkbookGenerator $generator,
        SchoolProfile $schoolProfile,
        DocumentQrCode $qrCodes,
        DocumentWorkbookVerification $workbookVerification
    ) {
        $this->authorizeRecordsStaff();
        [$student, $records, $schoolLevel, $requestedId] = $this->requestedStudentRecords($request);

        $path = $generator->generate([
            'lrn' => $student->lrn,
            'name_parts' => $this->splitName($student->name),
        ], $records->all(), $schoolProfile->values(), $schoolLevel);
        $fileIdentifier = $student->student_number ?: $student->lrn;
        $requestId = $requestedId ?: RequestDocument::query()
            ->where('student_number', $student->student_number)
            ->whereIn('document_type', ['Form 137', 'F137'])
            ->latest('id')
            ->value('id');
        $qr = $qrCodes->make('Form 137', $student->name, [
            'request_id' => $requestId,
            'holder_identifier' => $fileIdentifier,
            'fields' => ['school_records' => $records->all()],
        ]);
        $workbookVerification->attachToFile($path, $qr, [
            'document_type' => 'Form 137',
            'holder_name' => $student->name,
            'holder_identifier' => $fileIdentifier,
            'issue_date' => now()->toDateString(),
        ]);
        $qrCodes->registerArtifactFile(
            $qr['document'],
            $path,
            "F137-{$fileIdentifier}.xlsx",
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
        );

        return response()->download(
            $path,
            "F137-{$fileIdentifier}.xlsx",
            [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
                'Pragma' => 'no-cache',
                'Expires' => '0',
            ]
        )->deleteFileAfterSend(true);
    }

    private function requestedStudentRecords(Request $request): array
    {
        [$student, $records] = $this->studentRecords($request);
        $requestId = $request->integer('request_id') ?: null;
        $documentRequest = $requestId ? $this->documentRequest($requestId, $student) : null;
        $schoolLevel = match ($documentRequest?->school_level) {
            'kinder', 'elementary' => 'elementary',
            'jhs' => 'jhs',
            'shs' => 'shs',
            default => 'elementary',
        };

        if ($documentRequest && in_array($schoolLevel, ['elementary', 'jhs'], true)) {
            $records = $records->filter(
                fn (array $record): bool => $this->recordMatchesSchoolLevel($record, $schoolLevel)
            )->values();

            if ($records->isEmpty()) {
                throw ValidationException::withMessages([
                    'student' => "This student does not have any uploaded {$schoolLevel} records.",
                ]);
            }
        }

        return [$student, $records, $schoolLevel, $requestId];
    }

    private function studentRecords(Request $request): array
    {
        $validated = $request->validate([
            'student' => ['required', 'string', 'max:40'],
        ]);

        $identifier = trim($validated['student']);
        $numericIdentifier = preg_replace('/\D/', '', $identifier) ?: '';
        $rosterStudent = RequestStudent::query()
            ->where(function ($query) use ($identifier, $numericIdentifier) {
                $query->where('student_number', $identifier);
                if (strlen($numericIdentifier) >= 10) {
                    $query->orWhere('lrn', $numericIdentifier);
                }
            })
            ->first();

        $student = Student::findByIdentifier($identifier);
        if (! $student && $rosterStudent?->lrn) {
            $student = Student::findByIdentifier($rosterStudent->lrn);
        }

        if (! $student) {
            throw ValidationException::withMessages([
                'student' => 'No student record was found for this student number or LRN.',
            ]);
        }

        // Uploaded sheets can identify a learner by LRN while the request uses
        // the official student number. Resolve through the main roster and use
        // its identifiers for the preview, download, and request association.
        if ($rosterStudent) {
            $student->setAttribute('student_number', $rosterStudent->student_number);
            $student->setAttribute('lrn', $rosterStudent->resolvedLrn() ?: $student->lrn);
        }

        $enrollments = $student->enrollments()
            ->with(['grades', 'attendance'])
            ->orderBy('school_year')
            ->get()
            ->filter(fn ($enrollment) => $enrollment->grades->isNotEmpty() || $enrollment->attendance->isNotEmpty())
            ->values();

        if ($enrollments->isEmpty()) {
            throw ValidationException::withMessages([
                'student' => 'This student does not have any uploaded school-year records yet.',
            ]);
        }

        $records = $enrollments->map(fn ($enrollment) => $this->formatEnrollment($enrollment));

        return [$student, $records];
    }

    public function template()
    {
        $this->authorizeRecordsStaff();

        return response()->download(
            base_path('generator/F137.xlsx'),
            'F137-Blank-Template.xlsx',
            ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']
        );
    }

    private function formatEnrollment($enrollment): array
    {
        if (! $enrollment->adviser_name) {
            $upload = GradeSheetUpload::where([
                'school_year' => $enrollment->school_year,
                'level' => $enrollment->level,
                'section' => $enrollment->section,
            ])->orderByRaw("CASE WHEN file_type = 'attendance' THEN 0 ELSE 1 END")->latest()->first();
            if ($upload && Storage::disk('school_forms_local')->exists($upload->stored_path)) {
                $teacher = app(GradeSheetImporter::class)->teacherName(Storage::disk('school_forms_local')->path($upload->stored_path));
                if ($teacher) {
                    $enrollment->update(['adviser_name' => $teacher]);
                }
            }
        }

        $gradeMatrix = LearningAreaNormalizer::matrix($enrollment->grades);
        $validPeriods = array_flip(AcademicPeriod::numbers($enrollment->school_year));
        $gradeMatrix = array_map(
            fn (array $grades): array => array_intersect_key($grades, $validPeriods),
            $gradeMatrix,
        );

        $areas = [];
        foreach ($gradeMatrix as $learningArea => $quarters) {
            $available = array_values(array_filter($quarters, fn ($grade) => $grade !== null));
            $final = $available === [] ? null : round(array_sum($available) / count($available));
            $areas[] = [
                'name' => $learningArea,
                'quarters' => $quarters,
                'final' => $final,
                'remarks' => $final === null ? '' : ($final >= 75 ? 'PASSED' : 'FAILED'),
            ];
        }

        $finals = array_values(array_filter(array_column($areas, 'final'), fn ($grade) => $grade !== null));
        $generalAverage = $finals === [] ? null : round(array_sum($finals) / count($finals));

        return [
            'school_year' => $enrollment->school_year,
            'level' => $enrollment->level,
            'section' => $enrollment->section,
            'adviser_name' => $enrollment->adviser_name,
            'areas' => $areas,
            'areas_by_name' => collect($areas)->keyBy(fn ($area) => strtolower($area['name']))->all(),
            'general_average' => $generalAverage,
            'remarks' => $generalAverage === null ? '' : ($generalAverage >= 75 ? 'PROMOTED' : 'RETAINED'),
        ];
    }

    private function splitName(string $name): array
    {
        $parts = ['last' => '', 'first' => '', 'extension' => '', 'middle' => ''];
        $name = trim(preg_replace('/\s+/', ' ', $name));

        if (str_contains($name, ',')) {
            [$parts['last'], $given] = array_map('trim', explode(',', $name, 2));
            $tokens = preg_split('/\s+/', $given) ?: [];
        } else {
            $tokens = preg_split('/\s+/', $name) ?: [];
            $parts['last'] = count($tokens) > 1 ? (string) array_pop($tokens) : ($tokens[0] ?? '');
            if (count($tokens) === 1 && $parts['last'] === $tokens[0]) {
                $tokens = [];
            }
        }

        foreach ($tokens as $index => $token) {
            if (preg_match('/^(Jr\.?|Sr\.?|I|II|III|IV)$/i', $token)) {
                $parts['extension'] = $token;
                unset($tokens[$index]);
            }
        }

        $tokens = array_values($tokens);
        if (count($tokens) > 1) {
            $parts['middle'] = (string) array_pop($tokens);
        }
        $parts['first'] = implode(' ', $tokens);

        return $parts;
    }

    private function documentRequest(int $requestId, $student): RequestDocument
    {
        $documentRequest = RequestDocument::findOrFail($requestId);
        abort_unless(in_array(strtolower($documentRequest->document_type), ['form 137', 'f137'], true), 422, 'The selected request is not for Form 137.');
        abort_unless($documentRequest->student_number === $student->student_number, 422, 'The request student does not match this Form 137.');

        return $documentRequest;
    }

    private function recordMatchesSchoolLevel(array $record, string $schoolLevel): bool
    {
        if (strcasecmp($record['level'], 'Kinder') === 0) {
            return $schoolLevel === 'elementary';
        }

        $grade = (int) filter_var($record['level'], FILTER_SANITIZE_NUMBER_INT);

        return $schoolLevel === 'jhs'
            ? $grade >= 7 && $grade <= 10
            : $grade >= 1 && $grade <= 6;
    }

    private function authorizeRecordsStaff(): void
    {
        abort_unless(auth()->check() && in_array(auth()->user()->role, ['admin', 'records_officer'], true), 403);
    }

    public function generateF138(Request $request, XlsxWorkbookReader $reader)
    {
        $validated = $request->validate([
            'school_years' => ['required', 'array', 'min:1'],
            'school_years.0' => ['required', 'string', 'max:20'],
            'f138_files' => ['required', 'array', 'min:1'],
            'f138_files.0' => ['required', 'file', 'mimes:xlsx', 'max:20480'],
        ]);

        $uploadedFile = $request->file('f138_files')[0];

        try {
            $sheets = $reader->read($uploadedFile->getRealPath());
        } catch (RuntimeException $exception) {
            throw ValidationException::withMessages([
                'f138_files.0' => $exception->getMessage(),
            ]);
        }

        $records = [[
            'school_year' => $validated['school_years'][0],
            'file_name' => $uploadedFile->getClientOriginalName(),
            'sheet_count' => count($sheets),
            'row_count' => array_sum(array_map(fn (array $sheet) => count($sheet['rows']), $sheets)),
            'sheets' => $sheets,
        ]];

        return Pdf::loadView('pdf.form137', [
            'records' => $records,
            'generatedAt' => now(),
            'formName' => 'Form 138',
            'description' => 'Generated PDF output from the submitted Excel workbook.',
        ])->setPaper('a4')->download('F138.pdf');
    }

    public function generate(Request $request, XlsxWorkbookReader $reader): RedirectResponse
    {
        $validated = $request->validate([
            'school_years' => ['required', 'array', 'min:1'],
            'school_years.*' => ['required', 'string', 'max:20'],
            'f138_files' => ['required', 'array', 'min:1'],
            'f138_files.*' => ['required', 'file', 'mimes:xlsx', 'max:20480'],
        ]);

        $uploadedFiles = $request->file('f138_files', []);
        $schoolYears = $validated['school_years'];

        if (count($schoolYears) !== count($uploadedFiles)) {
            throw ValidationException::withMessages([
                'f138_files' => 'Each school year entry must include one F138 .xlsx file.',
            ]);
        }

        $records = [];

        foreach ($schoolYears as $index => $schoolYear) {
            $uploadedFile = $uploadedFiles[$index] ?? null;

            if ($uploadedFile === null) {
                throw ValidationException::withMessages([
                    'f138_files' => 'Each school year entry must include one F138 .xlsx file.',
                ]);
            }

            try {
                $sheets = $reader->read($uploadedFile->getRealPath());
            } catch (RuntimeException $exception) {
                throw ValidationException::withMessages([
                    'f138_files.'.$index => $exception->getMessage(),
                ]);
            }

            $records[] = [
                'school_year' => $schoolYear,
                'file_name' => $uploadedFile->getClientOriginalName(),
                'sheet_count' => count($sheets),
                'row_count' => array_sum(array_map(fn (array $sheet) => count($sheet['rows']), $sheets)),
                'sheets' => $sheets,
            ];
        }

        $pdf = Pdf::loadView('pdf.form137', [
            'records' => $records,
            'generatedAt' => now(),
            'formName' => 'Form 137',
            'description' => 'Generated PDF output from submitted F138 workbooks.',
        ])->setPaper('a4');

        File::put(base_path('F137.pdf'), $pdf->output());

        return redirect()
            ->route('home')
            ->with('status', 'F137.pdf generated successfully.');
    }
}
