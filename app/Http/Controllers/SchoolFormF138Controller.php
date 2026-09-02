<?php

namespace App\Http\Controllers;

use App\Models\RequestDocument;
use App\Models\SchoolFormAttendance as StudentAttendance;
use App\Models\SchoolFormEnrollment as StudentEnrollment;
use App\Models\SchoolFormGrade as StudentGrade;
use App\Models\SchoolFormStudent as Student;
use App\Models\SchoolFormUpload as GradeSheetUpload;
use App\Support\DocumentIssuanceService;
use App\Support\GradeSheetImporter;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Throwable;

class SchoolFormF138Controller extends Controller
{
    public function storeGradeSheets(Request $request, GradeSheetImporter $importer)
    {
        $this->authorizeGradeStaff();
        $validated = $request->validate([
            'grade_school_year' => ['required', Rule::in(['2020-2021', '2021-2022', '2022-2023'])],
            'grade_level' => ['required', Rule::in(['Grade 1', 'Grade 2', 'Grade 3'])],
            'grade_section' => ['required', Rule::in(['Amity'])],
            'grading_period' => ['required', 'integer', Rule::in([1, 2, 3, 4])],
            'attendance_file' => ['required', 'file', 'mimes:xlsx', 'max:20480'],
            'summary_file' => ['required', 'file', 'mimes:xlsx', 'max:20480'],
        ], [], [
            'grade_school_year' => 'school year',
            'grade_level' => 'grade level',
            'grade_section' => 'section',
            'grading_period' => 'grading period',
            'attendance_file' => 'attendance sheet',
            'summary_file' => 'summary sheet',
        ]);

        $schoolYear = $validated['grade_school_year'];
        $level = $validated['grade_level'];
        $section = $validated['grade_section'];
        $period = (int) $validated['grading_period'];

        try {
            $importer->assertMatchesSelection($request->file('summary_file')->getRealPath(), $schoolYear, $level, $period);
            $importer->assertMatchesSelection($request->file('attendance_file')->getRealPath(), $schoolYear, $level, $period);

            DB::connection('school_forms')->transaction(function () use ($request, $importer, $schoolYear, $level, $section, $period) {
                $this->importFile($request->file('summary_file'), 'summary', $period, $schoolYear, $level, $section, $importer);
                $this->importFile($request->file('attendance_file'), 'attendance', $period, $schoolYear, $level, $section, $importer);
            });
        } catch (RuntimeException $exception) {
            throw ValidationException::withMessages(['grade_sheets' => $exception->getMessage()]);
        }

        $periodName = ['First', 'Second', 'Third', 'Fourth'][$period - 1];

        return redirect()->route('school-forms.records')
            ->with('status', "{$periodName} grading attendance and summary sheets were uploaded.");
    }

    public function preview(Request $request)
    {
        $this->authorizeRecordsStaff();
        [$student, $enrollment, $gradeMatrix] = $this->studentEnrollment($request);
        $record = $this->previewRecord($enrollment, $gradeMatrix);
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

        return view('school-forms.f138-preview', compact('student', 'enrollment', 'record', 'requestId', 'issuedDocument'));
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
            'school_year' => ['required', Rule::in(['2020-2021', '2021-2022', '2022-2023'])],
            'request_id' => ['nullable', 'integer', 'exists:request_documents,id'],
        ]);

        $student = Student::findByIdentifier($validated['student']);
        if (! $student) {
            throw ValidationException::withMessages(['student' => 'No student record was found for this student number or LRN.']);
        }

        $enrollmentQuery = $student->enrollments()->with(['grades', 'attendance']);
        $enrollmentQuery->where('school_year', $validated['school_year']);
        $enrollment = $enrollmentQuery->first();
        if (! $enrollment) {
            throw ValidationException::withMessages(['student' => 'No enrollment was found for this student and school year.']);
        }
        $this->ensureTeacherName($enrollment);

        $gradeMatrix = [];
        foreach ($enrollment->grades as $grade) {
            $gradeMatrix[$grade->learning_area][$grade->grading_period] = $grade->grade;
        }

        $attendance = [];
        foreach ($enrollment->attendance->sortBy('grading_period') as $record) {
            $attendance[$record->month] = ['school_days' => $record->school_days, 'days_present' => $record->days_present];
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

    private function importFile($file, string $type, int $period, string $schoolYear, string $level, string $section, GradeSheetImporter $importer): void
    {
        if (! $file) {
            throw new RuntimeException("Missing {$type} file for grading period {$period}.");
        }

        $folder = "grade-sheets/{$schoolYear}/".str_replace(' ', '-', strtolower("{$level}-{$section}"));
        $name = "{$period}-{$type}-".now()->format('YmdHis').'.xlsx';
        $path = $file->storeAs($folder, $name, 'school_forms_local');

        GradeSheetUpload::updateOrCreate([
            'school_year' => $schoolYear, 'level' => $level, 'section' => $section, 'grading_period' => $period, 'file_type' => $type,
        ], ['original_name' => $file->getClientOriginalName(), 'stored_path' => $path]);

        $storedFile = Storage::disk('school_forms_local')->path($path);
        $teacherName = $importer->teacherName($storedFile);
        $records = $type === 'summary' ? $importer->summaries($storedFile) : $importer->attendance($storedFile);
        foreach ($records as $record) {
            $identifierColumn = $type === 'summary' ? 'student_number' : 'lrn';
            $identifier = $record[$identifierColumn];
            $student = Student::findByIdentifier($identifier);
            if (! $student && $record['name'] !== '') {
                $student = Student::findByName($record['name']);
            }
            if (! $student) {
                $student = Student::create([
                    $identifierColumn => $identifier,
                    'name' => $record['name'] ?: $identifier,
                ]);
            } elseif (! $student->{$identifierColumn}) {
                $student->update([$identifierColumn => $identifier]);
            }
            if ($record['name'] !== '') {
                $student->update(['name' => $record['name']]);
            }
            $enrollment = StudentEnrollment::resolveForImport($student, $schoolYear, $level, $section, $teacherName);
            if ($type === 'summary') {
                foreach ($record['grades'] as $area => $grade) {
                    StudentGrade::updateOrCreate(
                        ['student_enrollment_id' => $enrollment->id, 'grading_period' => $period, 'learning_area' => $area], ['grade' => $grade]
                    );
                }
            } else {
                foreach ($record['months'] as $month => $values) {
                    StudentAttendance::updateOrCreate(
                        ['student_enrollment_id' => $enrollment->id, 'grading_period' => $period, 'month' => $month], $values
                    );
                }
            }
        }
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
        $pdf->loadHtml(view('school-forms.pdf.f138-template', compact(
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
