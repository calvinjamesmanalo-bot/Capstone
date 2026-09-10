<?php

namespace App\Support;

use App\Models\SchoolFormAttendance;
use App\Models\SchoolFormEnrollment;
use App\Models\SchoolFormGrade;
use App\Models\SchoolFormStudent;

class GradeSheetRecordImporter
{
    public function __construct(private readonly GradeSheetImporter $reader) {}

    public function import(string $path, string $type, int $period, string $schoolYear, string $level, string $section): int
    {
        $teacherName = $this->reader->teacherName($path);
        $records = $type === 'summary' ? $this->reader->summaries($path) : $this->reader->attendance($path);

        foreach ($records as $record) {
            $identifierColumn = $type === 'summary' ? 'student_number' : 'lrn';
            $identifier = $record[$identifierColumn];
            $student = SchoolFormStudent::findByIdentifier($identifier);
            if (! $student && $record['name'] !== '') {
                $student = SchoolFormStudent::findByName($record['name']);
            }
            if (! $student) {
                $student = SchoolFormStudent::create([
                    $identifierColumn => $identifier,
                    'name' => $record['name'] ?: $identifier,
                ]);
            } elseif (! $student->{$identifierColumn}) {
                $student->update([$identifierColumn => $identifier]);
            }
            if ($record['name'] !== '') {
                $student->update(['name' => $record['name']]);
            }

            $enrollment = SchoolFormEnrollment::resolveForImport($student, $schoolYear, $level, $section, $teacherName);
            if ($type === 'summary') {
                $this->replaceGrades($enrollment, $period, $record['grades']);
            } else {
                $this->replaceAttendance($enrollment, $period, $record['months']);
            }
        }

        return count($records);
    }

    private function replaceGrades(SchoolFormEnrollment $enrollment, int $period, array $grades): void
    {
        SchoolFormGrade::where('student_enrollment_id', $enrollment->id)
            ->where('grading_period', $period)
            ->delete();

        foreach ($grades as $area => $grade) {
            $learningArea = LearningAreaNormalizer::label($area);
            if ($grade === null || $learningArea === null) {
                continue;
            }

            SchoolFormGrade::create([
                'student_enrollment_id' => $enrollment->id,
                'grading_period' => $period,
                'learning_area' => $learningArea,
                'grade' => $grade,
            ]);
        }
    }

    private function replaceAttendance(SchoolFormEnrollment $enrollment, int $period, array $months): void
    {
        SchoolFormAttendance::where('student_enrollment_id', $enrollment->id)
            ->where('grading_period', $period)
            ->delete();

        foreach ($months as $month => $values) {
            if (($values['school_days'] ?? null) === null && ($values['days_present'] ?? null) === null) {
                continue;
            }

            SchoolFormAttendance::create([
                'student_enrollment_id' => $enrollment->id,
                'grading_period' => $period,
                'month' => $month,
                'school_days' => $values['school_days'] ?? null,
                'days_present' => $values['days_present'] ?? null,
            ]);
        }
    }
}
