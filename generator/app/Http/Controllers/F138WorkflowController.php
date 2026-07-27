<?php

namespace App\Http\Controllers;

use App\Models\GradeSheetUpload;
use App\Models\Student;
use App\Models\StudentAttendance;
use App\Models\StudentEnrollment;
use App\Models\StudentGrade;
use App\Support\GradeSheetImporter;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class F138WorkflowController extends Controller
{
    public function storeGradeSheets(Request $request, GradeSheetImporter $importer)
    {
        $validated = $request->validate([
            'grade_school_years' => ['required','array','size:1'],
            'grade_school_years.0' => ['required', Rule::in(['2020-2021','2021-2022','2022-2023'])],
            'grade_levels' => ['required','array','size:1'],
            'grade_levels.0' => ['required', Rule::in(['Grade 1','Grade 2','Grade 3'])],
            'grade_sections' => ['required','array','size:1'],
            'grade_sections.0' => ['required', Rule::in(['Amity'])],
            'attendance_files' => ['required','array'],
            'attendance_files.0' => ['required','array','size:4'],
            'attendance_files.0.*' => ['required','file','mimes:xlsx','max:20480'],
            'average_files' => ['required','array'],
            'average_files.0' => ['required','array','size:4'],
            'average_files.0.*' => ['required','file','mimes:xlsx','max:20480'],
        ], [], [
            'attendance_files' => 'attendance sheets',
            'attendance_files.0' => 'attendance sheets',
            'attendance_files.0.*' => 'attendance sheet',
            'average_files' => 'average sheets',
            'average_files.0' => 'average sheets',
            'average_files.0.*' => 'average sheet',
        ]);

        $schoolYear = $validated['grade_school_years'][0];
        $level = $validated['grade_levels'][0];
        $section = $validated['grade_sections'][0];

        try {
            DB::transaction(function () use ($request, $importer, $schoolYear, $level, $section) {
                foreach (range(1, 4) as $period) {
                    $this->importFile($request->file("average_files.0.{$period}"), 'summary', $period, $schoolYear, $level, $section, $importer);
                    $this->importFile($request->file("attendance_files.0.{$period}"), 'attendance', $period, $schoolYear, $level, $section, $importer);
                }
            });
        } catch (RuntimeException $exception) {
            throw ValidationException::withMessages(['grade_sheets' => $exception->getMessage()]);
        }

        return redirect()->route('students.index')->with('status', 'All grade sheets were uploaded and saved.');
    }

    public function preview(Request $request)
    {
        $validated = $request->validate([
            'student' => ['required','string','max:40'],
            'school_year' => ['nullable', Rule::in(['2020-2021','2021-2022','2022-2023'])],
        ]);

        $student = Student::findByIdentifier($validated['student']);
        if (!$student) throw ValidationException::withMessages(['student' => 'No student record was found for this student number or LRN.']);

        $enrollmentQuery = $student->enrollments()->with(['grades','attendance']);
        if (!empty($validated['school_year'])) $enrollmentQuery->where('school_year', $validated['school_year']);
        $enrollment = $enrollmentQuery->orderByDesc('school_year')->first();
        if (!$enrollment) throw ValidationException::withMessages(['student' => 'No enrollment was found for this student and school year.']);
        $this->ensureTeacherName($enrollment);

        $gradeMatrix = [];
        foreach ($enrollment->grades as $grade) $gradeMatrix[$grade->learning_area][$grade->grading_period] = $grade->grade;

        $attendance = [];
        foreach ($enrollment->attendance->sortBy('grading_period') as $record) $attendance[$record->month] = ['school_days'=>$record->school_days, 'days_present'=>$record->days_present];

        $fileIdentifier = $student->student_number ?: $student->lrn;

        return Pdf::loadView('pdf.f138-template', compact('student','enrollment','gradeMatrix','attendance'))
            ->setPaper('a4', 'portrait')
            ->stream("F138-{$fileIdentifier}.pdf");
    }

    private function importFile($file, string $type, int $period, string $schoolYear, string $level, string $section, GradeSheetImporter $importer): void
    {
        if (!$file) throw new RuntimeException("Missing {$type} file for grading period {$period}.");

        $folder = "grade-sheets/{$schoolYear}/".str_replace(' ', '-', strtolower("{$level}-{$section}"));
        $name = "{$period}-{$type}-".now()->format('YmdHis').'.xlsx';
        $path = $file->storeAs($folder, $name, 'local');

        GradeSheetUpload::updateOrCreate([
            'school_year'=>$schoolYear,'level'=>$level,'section'=>$section,'grading_period'=>$period,'file_type'=>$type,
        ], ['original_name'=>$file->getClientOriginalName(),'stored_path'=>$path]);

        $storedFile = Storage::disk('local')->path($path);
        $teacherName = $importer->teacherName($storedFile);
        $records = $type === 'summary' ? $importer->summaries($storedFile) : $importer->attendance($storedFile);
        foreach ($records as $record) {
            $identifierColumn = $type === 'summary' ? 'student_number' : 'lrn';
            $identifier = $record[$identifierColumn];
            $student = Student::findByIdentifier($identifier);
            if (!$student && $record['name'] !== '') $student = Student::findByName($record['name']);
            if (!$student) {
                $student = Student::create([
                    $identifierColumn => $identifier,
                    'name' => $record['name'] ?: $identifier,
                ]);
            } elseif (!$student->{$identifierColumn}) {
                $student->update([$identifierColumn => $identifier]);
            }
            if ($record['name'] !== '') $student->update(['name' => $record['name']]);
            $enrollment = StudentEnrollment::updateOrCreate(
                ['student_id'=>$student->id,'school_year'=>$schoolYear],
                array_filter(['level'=>$level,'section'=>$section,'adviser_name'=>$teacherName], fn ($value) => $value !== null)
            );
            if ($type === 'summary') {
                foreach ($record['grades'] as $area=>$grade) StudentGrade::updateOrCreate(
                    ['student_enrollment_id'=>$enrollment->id,'grading_period'=>$period,'learning_area'=>$area], ['grade'=>$grade]
                );
            } else {
                foreach ($record['months'] as $month=>$values) StudentAttendance::updateOrCreate(
                    ['student_enrollment_id'=>$enrollment->id,'grading_period'=>$period,'month'=>$month], $values
                );
            }
        }
    }

    private function ensureTeacherName(StudentEnrollment $enrollment): void
    {
        if ($enrollment->adviser_name) return;

        $upload = GradeSheetUpload::where([
            'school_year' => $enrollment->school_year,
            'level' => $enrollment->level,
            'section' => $enrollment->section,
        ])->orderByRaw("CASE WHEN file_type = 'attendance' THEN 0 ELSE 1 END")->latest()->first();

        if (!$upload || !Storage::disk('local')->exists($upload->stored_path)) return;

        $teacher = app(GradeSheetImporter::class)->teacherName(Storage::disk('local')->path($upload->stored_path));
        if ($teacher) $enrollment->update(['adviser_name' => $teacher]);
    }
}
