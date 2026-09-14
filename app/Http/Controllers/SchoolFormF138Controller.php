<?php

namespace App\Http\Controllers;

use App\Models\RequestDocument;
use App\Models\SchoolFormEnrollment as StudentEnrollment;
use App\Models\SchoolFormStudent as Student;
use App\Models\SchoolFormUpload as GradeSheetUpload;
use App\Models\Student as RequestStudent;
use App\Support\AcademicPeriod;
use App\Support\DocumentIssuanceService;
use App\Support\GradeSheetImporter;
use App\Support\GradeSheetRecordImporter;
use App\Support\LearningAreaNormalizer;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Throwable;

class SchoolFormF138Controller extends Controller
{
    public function storeGradeSheets(Request $request, GradeSheetImporter $importer, GradeSheetRecordImporter $recordImporter)
    {
        $this->authorizeGradeStaff();
        $validated = $request->validate([
            'grade_school_year' => ['required', Rule::in(config('academics.school_years', []))],
            'grade_level' => ['required', Rule::in(config('academics.grade_levels', []))],
            'grade_section' => ['required', Rule::in(['Bambi'])],
            'summary_files' => ['nullable', 'array'],
            'summary_files.*' => ['nullable', 'file', 'mimes:xlsx', 'max:20480'],
            'attendance_files' => ['nullable', 'array'],
            'attendance_files.*' => ['nullable', 'file', 'mimes:xlsx', 'max:20480'],
            // Legacy fields remain accepted for old links and integrations.
            'grading_period' => ['nullable', 'integer', Rule::in([1, 2, 3, 4])],
            'attendance_file' => ['nullable', 'file', 'mimes:xlsx', 'max:20480'],
            'summary_file' => ['nullable', 'file', 'mimes:xlsx', 'max:20480'],
            'replace_existing' => ['nullable', 'boolean'],
        ], [], [
            'grade_school_year' => 'school year',
            'grade_level' => 'grade level',
            'grade_section' => 'section',
            'grading_period' => 'grading period',
            'attendance_files.*' => 'attendance sheet',
            'summary_files.*' => 'summary sheet',
        ]);

        $schoolYear = $validated['grade_school_year'];
        $level = $validated['grade_level'];
        $section = $validated['grade_section'];
        $periodNumbers = AcademicPeriod::numbers($schoolYear);
        $files = [];

        foreach (['summary_files', 'attendance_files'] as $field) {
            foreach (array_keys($request->file($field, [])) as $period) {
                if (! in_array((int) $period, $periodNumbers, true)) {
                    throw ValidationException::withMessages([
                        'grade_sheets' => "{$schoolYear} uses three terms. Fourth-period uploads are not accepted.",
                    ]);
                }
            }
        }

        foreach ($periodNumbers as $period) {
            foreach (['summary', 'attendance'] as $type) {
                $file = $request->file("{$type}_files.{$period}");
                if ($file) {
                    $files[] = compact('file', 'type', 'period');
                }
            }
        }

        $legacyUpload = $request->hasFile('summary_file') || $request->hasFile('attendance_file');
        if ($legacyUpload) {
            $period = (int) ($validated['grading_period'] ?? 0);
            if (! in_array($period, $periodNumbers, true) || ! $request->hasFile('summary_file') || ! $request->hasFile('attendance_file')) {
                throw ValidationException::withMessages([
                    'grade_sheets' => 'Legacy uploads require a valid period plus both attendance and summary sheets.',
                ]);
            }
            foreach (['summary', 'attendance'] as $type) {
                $file = $request->file("{$type}_file");
                $files[] = compact('file', 'type', 'period');
            }
        }

        if ($files === []) {
            throw ValidationException::withMessages([
                'grade_sheets' => 'Choose at least one summary or attendance workbook to upload.',
            ]);
        }

        try {
            // Validate every workbook before writing anything. A bad file cannot
            // leave the class with a half-imported grading period.
            foreach ($files as $item) {
                try {
                    $importer->assertMatchesSelection(
                        $item['file']->getRealPath(),
                        $schoolYear,
                        $level,
                        $item['period'],
                    );
                } catch (RuntimeException $exception) {
                    throw new RuntimeException($this->uploadSlotLabel($schoolYear, $item['period'], $item['type']).': '.$exception->getMessage());
                }
            }
        } catch (RuntimeException $exception) {
            throw ValidationException::withMessages(['grade_sheets' => $exception->getMessage()]);
        }

        $existingUploads = GradeSheetUpload::query()
            ->where('school_year', $schoolYear)
            ->where('level', $level)
            ->where('section', $section)
            ->get()
            ->keyBy(fn (GradeSheetUpload $upload) => "{$upload->grading_period}:{$upload->file_type}");
        $replacements = collect($files)->filter(
            fn (array $item) => $existingUploads->has("{$item['period']}:{$item['type']}")
        );

        if ($replacements->isNotEmpty() && ! $legacyUpload && ! $request->boolean('replace_existing')) {
            $labels = $replacements->map(fn (array $item) => $this->uploadSlotLabel($schoolYear, $item['period'], $item['type']))->join(', ');
            throw ValidationException::withMessages([
                'replace_existing' => "These slots already contain files: {$labels}. Check the replacement confirmation before uploading.",
            ]);
        }

        $newPaths = [];
        $oldPaths = [];
        try {
            DB::connection('school_forms')->transaction(function () use ($files, $recordImporter, $schoolYear, $level, $section, &$newPaths, &$oldPaths) {
                foreach ($files as $item) {
                    [$newPath, $oldPath] = $this->importFile(
                        $item['file'],
                        $item['type'],
                        $item['period'],
                        $schoolYear,
                        $level,
                        $section,
                        $recordImporter,
                    );
                    $newPaths[] = $newPath;
                    if ($oldPath && $oldPath !== $newPath) {
                        $oldPaths[] = $oldPath;
                    }
                }
            });
        } catch (RuntimeException $exception) {
            Storage::disk('school_forms_local')->delete($newPaths);
            throw ValidationException::withMessages(['grade_sheets' => $exception->getMessage()]);
        } catch (Throwable $exception) {
            Storage::disk('school_forms_local')->delete($newPaths);
            throw $exception;
        }

        Storage::disk('school_forms_local')->delete(array_unique($oldPaths));
        $periods = collect($files)->pluck('period')->unique()->sort()->map(
            fn (int $period) => AcademicPeriod::label($schoolYear, $period)
        )->join(', ');
        $uploadedCount = count($files);
        $replacementCount = $replacements->count();
        $sampleEnrollment = StudentEnrollment::query()
            ->with('student')
            ->where('school_year', $schoolYear)
            ->where('level', $level)
            ->where('section', $section)
            ->first();
        $previewUrl = null;
        if ($sampleEnrollment?->student && in_array(auth()->user()->role, ['admin', 'records_officer'], true)) {
            $previewUrl = route('school-forms.f138.preview', [
                'student' => $sampleEnrollment->student->student_number ?: $sampleEnrollment->student->lrn,
                'school_year' => $schoolYear,
            ]);
        }

        $redirect = $legacyUpload
            ? redirect()->route('school-forms.records')
            : redirect()->route('school-forms.records', ['school_year' => $schoolYear, 'level' => $level, 'section' => $section]);

        return $redirect->with('status', "{$uploadedCount} workbook(s) imported for {$periods}. {$replacementCount} existing slot(s) replaced.")
            ->with('grade_sheet_import_result', [
                'uploaded' => $uploadedCount,
                'replaced' => $replacementCount,
                'periods' => $periods,
                'preview_url' => $previewUrl,
            ]);
    }

    public function preview(Request $request)
    {
        $this->authorizeRecordsStaff();
        [$student, $enrollment, $gradeMatrix] = $this->studentEnrollment($request);
        $record = $this->previewRecord($enrollment, $gradeMatrix);
        $periodNumbers = AcademicPeriod::numbers($enrollment->school_year);
        $usesTerms = AcademicPeriod::usesTerms($enrollment->school_year);
        $requestId = $request->integer('request_id') ?: null;
        $documentRequest = $requestId ? $this->documentRequest($requestId, $student, $enrollment) : null;
        $issuedDocument = $documentRequest?->authenticities()
            ->with('officialArtifact')
            ->where('pdf_signature_status', 'signed')
            ->latest('id')
            ->first();
        if ($documentRequest) {
            record_log('Draft Generated', 'Document Issuance', "Generated Form 138 draft for request #{$documentRequest->id}");
        }

        return view('school-forms.f138-preview', compact('student', 'enrollment', 'record', 'periodNumbers', 'usesTerms', 'requestId', 'issuedDocument'));
    }

    public function pdf(Request $request)
    {
        $this->authorizeRecordsStaff();
        [$student, $enrollment, $gradeMatrix, $attendance] = $this->studentEnrollment($request);
        $fileIdentifier = $student->student_number ?: $student->lrn;

        return $this->pdfResponse($student, $enrollment, $gradeMatrix, $attendance, "F138-{$fileIdentifier}.pdf", false);
    }

    public function download(Request $request)
    {
        $this->authorizeRecordsStaff();
        [$student, $enrollment, $gradeMatrix, $attendance] = $this->studentEnrollment($request);
        $fileIdentifier = $student->student_number ?: $student->lrn;

        return $this->pdfResponse($student, $enrollment, $gradeMatrix, $attendance, "F138-{$fileIdentifier}.pdf", true);
    }

    public function finalize(Request $request, DocumentIssuanceService $issuance)
    {
        $this->authorizeRecordsStaff();
        [$student, $enrollment, $gradeMatrix, $attendance] = $this->studentEnrollment($request);
        $requestId = $request->integer('request_id');
        abort_if(! $requestId, 422, 'An existing Form 138 request is required for official issuance.');
        $documentRequest = $this->documentRequest($requestId, $student, $enrollment);
        $fileIdentifier = $student->student_number ?: $student->lrn;
        $filename = "F138-{$fileIdentifier}.pdf";

        try {
            $document = $issuance->issue(
                $documentRequest,
                'Form 138',
                $student->name,
                [
                    'holder_identifier' => $student->student_number ?? $student->lrn,
                    'issued_at' => now(),
                    'fields' => [
                        'school_year' => $enrollment->school_year,
                        'level' => $enrollment->level,
                        'section' => $enrollment->section,
                        'grades' => $gradeMatrix,
                        'attendance' => $attendance,
                    ],
                ],
                $filename,
                fn (array $qr): string => $this->renderPdfBytes(
                    $student,
                    $enrollment,
                    $gradeMatrix,
                    $attendance,
                    $requestId,
                    'official',
                    $qr,
                ),
            );
        } catch (Throwable $exception) {
            report($exception);

            return redirect()->route('school-forms.f138.preview', [
                'student' => $student->student_number ?: $student->lrn,
                'school_year' => $enrollment->school_year,
                'request_id' => $requestId,
            ])->with('error', 'Official issuance failed safely; no document was issued. '.$exception->getMessage());
        }

        return redirect()->route('documents.issued', $document)
            ->with('success', "{$document->control_number} was digitally signed and officially issued.");
    }

    private function studentEnrollment(Request $request): array
    {
        $validated = $request->validate([
            'student' => ['required', 'string', 'max:40'],
            'school_year' => ['required', Rule::in(config('academics.school_years', []))],
            'request_id' => ['nullable', 'integer', 'exists:request_documents,id'],
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
            throw ValidationException::withMessages(['student' => 'No student record was found for this student number or LRN.']);
        }

        // Grade sheets may identify a learner by LRN while requests use the
        // official student number. Keep the imported record unchanged while
        // presenting and validating it with the roster's official identifiers.
        if ($rosterStudent) {
            $student->setAttribute('student_number', $rosterStudent->student_number);
            $student->setAttribute('lrn', $rosterStudent->lrn ?: $student->lrn);
        }

        $enrollmentQuery = $student->enrollments()->with(['grades', 'attendance']);
        $enrollmentQuery->where('school_year', $validated['school_year']);
        $enrollment = $enrollmentQuery->first();
        if (! $enrollment) {
            throw ValidationException::withMessages(['student' => 'No enrollment was found for this student and school year.']);
        }
        $this->ensureTeacherName($enrollment);

        $gradeMatrix = LearningAreaNormalizer::matrix($enrollment->grades);
        $validPeriods = array_flip(AcademicPeriod::numbers($enrollment->school_year));
        $gradeMatrix = array_map(
            fn (array $grades): array => array_intersect_key($grades, $validPeriods),
            $gradeMatrix,
        );

        $attendance = [];
        foreach ($enrollment->attendance->sortBy('grading_period') as $record) {
            if ($record->school_days === null && $record->days_present === null) {
                continue;
            }

            $attendance[$record->month] = [
                'school_days' => $record->school_days ?? ($attendance[$record->month]['school_days'] ?? null),
                'days_present' => $record->days_present ?? ($attendance[$record->month]['days_present'] ?? null),
            ];
        }

        return [$student, $enrollment, $gradeMatrix, $attendance];
    }

    private function previewRecord(StudentEnrollment $enrollment, array $gradeMatrix): array
    {
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
            'areas' => $areas,
            'general_average' => $generalAverage,
            'remarks' => $generalAverage === null ? '' : ($generalAverage >= 75 ? 'PROMOTED' : 'RETAINED'),
        ];
    }

    private function importFile($file, string $type, int $period, string $schoolYear, string $level, string $section, GradeSheetRecordImporter $importer): array
    {
        if (! $file) {
            throw new RuntimeException("Missing {$type} file for grading period {$period}.");
        }

        $folder = "grade-sheets/{$schoolYear}/".str_replace(' ', '-', strtolower("{$level}-{$section}"));
        $name = "{$period}-{$type}-".Str::uuid().'.xlsx';
        $path = $file->storeAs($folder, $name, 'school_forms_local');
        if (! $path) {
            throw new RuntimeException("The {$type} workbook for grading period {$period} could not be stored.");
        }

        $upload = GradeSheetUpload::firstOrNew([
            'school_year' => $schoolYear, 'level' => $level, 'section' => $section, 'grading_period' => $period, 'file_type' => $type,
        ]);
        $oldPath = $upload->exists ? $upload->stored_path : null;
        $upload->fill(['original_name' => $file->getClientOriginalName(), 'stored_path' => $path])->save();

        try {
            $importer->import(
                Storage::disk('school_forms_local')->path($path),
                $type,
                $period,
                $schoolYear,
                $level,
                $section,
            );
        } catch (Throwable $exception) {
            Storage::disk('school_forms_local')->delete($path);
            throw $exception;
        }

        return [$path, $oldPath];
    }

    private function uploadSlotLabel(string $schoolYear, int $period, string $type): string
    {
        return AcademicPeriod::label($schoolYear, $period).' '.ucfirst($type);
    }

    private function ensureTeacherName(StudentEnrollment $enrollment): void
    {
        if ($enrollment->adviser_name) {
            return;
        }

        $upload = GradeSheetUpload::where([
            'school_year' => $enrollment->school_year,
            'level' => $enrollment->level,
            'section' => $enrollment->section,
        ])->orderByRaw("CASE WHEN file_type = 'attendance' THEN 0 ELSE 1 END")->latest()->first();

        if (! $upload || ! Storage::disk('school_forms_local')->exists($upload->stored_path)) {
            return;
        }

        $teacher = app(GradeSheetImporter::class)->teacherName(Storage::disk('school_forms_local')->path($upload->stored_path));
        if ($teacher) {
            $enrollment->update(['adviser_name' => $teacher]);
        }
    }

    private function pdfResponse($student, $enrollment, array $gradeMatrix, array $attendance, string $filename, bool $download)
    {
        $requestId = request()->integer('request_id') ?: RequestDocument::query()
            ->where('student_number', $student->student_number)
            ->whereIn('document_type', ['Form 138', 'F138'])
            ->where('school_year', $enrollment->school_year)
            ->latest('id')
            ->value('id');
        $disposition = $download ? 'attachment' : 'inline';
        $bytes = $this->renderPdfBytes($student, $enrollment, $gradeMatrix, $attendance, $requestId, 'draft');
        $filename = preg_replace('/\.pdf$/i', '-DRAFT.pdf', $filename);

        return response($bytes, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => $disposition.'; filename="'.$filename.'"',
        ]);
    }

    private function renderPdfBytes(
        $student,
        $enrollment,
        array $gradeMatrix,
        array $attendance,
        ?int $requestId,
        string $documentMode,
        ?array $documentQr = null,
    ): string {
        $options = new Options;
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true);

        $pdf = new Dompdf($options);
        $pdf->loadHtml(view($this->pdfView($enrollment->school_year), compact(
            'student',
            'enrollment',
            'gradeMatrix',
            'attendance',
            'requestId',
            'documentMode',
            'documentQr',
        ))->render());
        $pdf->setPaper('a4', 'portrait');
        $pdf->render();

        return $pdf->output();
    }

    private function pdfView(string $schoolYear): string
    {
        return AcademicPeriod::usesTerms($schoolYear)
            ? 'school-forms.pdf.f138-three-term-template'
            : 'school-forms.pdf.f138-template';
    }

    private function documentRequest(int $requestId, $student, $enrollment): RequestDocument
    {
        $documentRequest = RequestDocument::findOrFail($requestId);
        abort_unless(in_array(strtolower($documentRequest->document_type), ['form 138', 'f138'], true), 422, 'The selected request is not for Form 138.');
        abort_unless($documentRequest->student_number === $student->student_number, 422, 'The request student does not match this Form 138.');
        abort_unless(! $documentRequest->school_year || $documentRequest->school_year === $enrollment->school_year, 422, 'The request school year does not match this Form 138.');

        return $documentRequest;
    }

    private function authorizeRecordsStaff(): void
    {
        abort_unless(auth()->check() && in_array(auth()->user()->role, ['admin', 'records_officer'], true), 403);
    }

    private function authorizeGradeStaff(): void
    {
        abort_unless(auth()->check() && in_array(auth()->user()->role, ['admin', 'registrar', 'records_officer'], true), 403);
    }
}
