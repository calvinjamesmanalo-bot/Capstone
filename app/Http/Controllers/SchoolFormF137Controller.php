<?php

namespace App\Http\Controllers;

use App\Models\SchoolFormStudent as Student;
use App\Models\SchoolFormUpload as GradeSheetUpload;
use App\Support\GradeSheetImporter;
use App\Support\Form137WorkbookGenerator;
use App\Support\SchoolProfile;
use App\Support\XlsxWorkbookReader;
use App\Support\DocumentQrCode;
use App\Support\DocumentWorkbookVerification;
use App\Models\RequestDocument;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class SchoolFormF137Controller extends Controller
{
    public function preview(Request $request)
    {
        $this->authorizeRecordsStaff();
        [$student, $records] = $this->studentRecords($request);

        return view('school-forms.f137-preview', compact('student', 'records'));
    }

    public function download(
        Request $request,
        Form137WorkbookGenerator $generator,
        SchoolProfile $schoolProfile,
        DocumentQrCode $qrCodes,
        DocumentWorkbookVerification $workbookVerification
    )
    {
        $this->authorizeRecordsStaff();
        [$student, $records] = $this->studentRecords($request);

        $path = $generator->generate([
            'lrn' => $student->lrn,
            'name_parts' => $this->splitName($student->name),
        ], $records->all(), $schoolProfile->values());
        $fileIdentifier = $student->student_number ?: $student->lrn;
        $requestId = RequestDocument::query()
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

    private function studentRecords(Request $request): array
    {
        $validated = $request->validate([
            'student' => ['required', 'string', 'max:40'],
        ]);

        $student = Student::findByIdentifier($validated['student']);

        if (!$student) {
            throw ValidationException::withMessages([
                'student' => 'No student record was found for this student number or LRN.',
            ]);
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
        if (!$enrollment->adviser_name) {
            $upload = GradeSheetUpload::where([
                'school_year' => $enrollment->school_year,
                'level' => $enrollment->level,
                'section' => $enrollment->section,
            ])->orderByRaw("CASE WHEN file_type = 'attendance' THEN 0 ELSE 1 END")->latest()->first();
            if ($upload && Storage::disk('school_forms_local')->exists($upload->stored_path)) {
                $teacher = app(GradeSheetImporter::class)->teacherName(Storage::disk('school_forms_local')->path($upload->stored_path));
                if ($teacher) $enrollment->update(['adviser_name' => $teacher]);
            }
        }

        $gradeMatrix = [];

        foreach ($enrollment->grades as $grade) {
            $gradeMatrix[$grade->learning_area][(int) $grade->grading_period] = $grade->grade === null
                ? null
                : (float) $grade->grade;
        }

        ksort($gradeMatrix, SORT_NATURAL | SORT_FLAG_CASE);

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
            if (count($tokens) === 1 && $parts['last'] === $tokens[0]) $tokens = [];
        }

        foreach ($tokens as $index => $token) {
            if (preg_match('/^(Jr\.?|Sr\.?|I|II|III|IV)$/i', $token)) {
                $parts['extension'] = $token;
                unset($tokens[$index]);
            }
        }

        $tokens = array_values($tokens);
        if (count($tokens) > 1) $parts['middle'] = (string) array_pop($tokens);
        $parts['first'] = implode(' ', $tokens);

        return $parts;
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
                    'f138_files.' . $index => $exception->getMessage(),
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
