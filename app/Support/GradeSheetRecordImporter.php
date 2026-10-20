<?php

namespace App\Support;

use App\Models\SchoolFormAttendance;
use App\Models\SchoolFormEnrollment;
use App\Models\SchoolFormGrade;
use App\Models\SchoolFormStudent;

class GradeSheetRecordImporter
{
    public function __construct(private readonly GradeSheetImporter $reader) {}

    public function import(string $path, string $type, int $period, string $schoolYear, string $level, string $section, bool $skipEnrollmentConflicts = false): array
    {
        $teacherName = $this->reader->teacherName($path);
        $records = $type === 'summary' ? $this->reader->summaries($path, $period) : $this->reader->attendance($path);
        $imported = 0;
        $conflicts = [];

        foreach ($records as $record) {
            $identifierColumn = 'student_number';
            $identifier = trim((string) ($record[$identifierColumn] ?? ''));
            $lrn = preg_replace('/\D/', '', (string) ($record['lrn'] ?? '')) ?: '';
            $studentByNumber = $identifier !== ''
                ? SchoolFormStudent::query()->whereRaw('UPPER(student_number) = ?', [strtoupper($identifier)])->first()
                : null;
            $studentByLrn = $lrn !== ''
                ? SchoolFormStudent::query()->where('lrn', $lrn)->first()
                : null;
            if ($studentByNumber && $studentByLrn && ! $studentByNumber->is($studentByLrn)) {
                try {
                    $studentByNumber = $this->mergeMatchingStudentRecords($studentByNumber, $studentByLrn);
                    $studentByLrn = $studentByNumber;
                } catch (\RuntimeException $exception) {
                    if ($skipEnrollmentConflicts) {
                        $conflicts[] = ['student_number' => $identifier, 'message' => $exception->getMessage()];

                        continue;
                    }
                    throw $exception;
                }
            }
            $student = $studentByNumber ?: $studentByLrn;
            if ($student && (($identifier !== '' && $student->student_number && strcasecmp($student->student_number, $identifier) !== 0)
                || ($lrn !== '' && $student->lrn && $student->lrn !== $lrn))) {
                $message = "Identifier conflict: Student Number {$identifier} and LRN {$lrn} do not match the same existing student record.";
                if ($skipEnrollmentConflicts) {
                    $conflicts[] = ['student_number' => $identifier, 'message' => $message];

                    continue;
                }
                throw new \RuntimeException($message);
            }
            // Student number is authoritative. Different learners can have
            // identical names, especially in large batch test datasets. A name
            // match is safe only for legacy rows that contain no identifier.
            if (! $student && $identifier === '' && $lrn === '' && $record['name'] !== '') {
                $student = SchoolFormStudent::findByName($record['name']);
            }
            if (! $student) {
                $attributes = ['name' => $record['name'] ?: $identifier];
                if ($identifier !== '') {
                    $attributes[$identifierColumn] = $identifier;
                }
                if ($lrn !== '') {
                    $attributes['lrn'] = $lrn;
                }
                $student = SchoolFormStudent::create($attributes);
            } elseif ($identifier !== '' && ! $student->{$identifierColumn}) {
                $student->update([$identifierColumn => $identifier]);
            }
            if ($lrn !== '' && ! $student->lrn) {
                $student->update(['lrn' => $lrn]);
            }
            if ($record['name'] !== '') {
                $student->update(['name' => $record['name']]);
            }

            try {
                $enrollment = SchoolFormEnrollment::resolveForImport($student, $schoolYear, $level, $section, $teacherName);
            } catch (\RuntimeException $exception) {
                if (! $skipEnrollmentConflicts || ! str_starts_with($exception->getMessage(), 'Enrollment conflict')) {
                    throw $exception;
                }
                $conflicts[] = [
                    'student_number' => $identifier,
                    'message' => $exception->getMessage(),
                ];

                continue;
            }
            if ($type === 'summary') {
                $this->replaceGrades($enrollment, $period, $record['grades']);
            } else {
                $this->replaceAttendance($enrollment, $period, $record['months']);
            }
            $imported++;
        }

        return compact('imported', 'conflicts');
    }

    private function mergeMatchingStudentRecords(SchoolFormStudent $primary, SchoolFormStudent $duplicate): SchoolFormStudent
    {
        if ($this->nameKey($primary->name) === '' || $this->nameKey($primary->name) !== $this->nameKey($duplicate->name)) {
            throw new \RuntimeException(
                "Identifier conflict: Student Number {$primary->student_number} and LRN {$duplicate->lrn} belong to students with different names."
            );
        }

        $duplicate->load(['enrollments.grades', 'enrollments.attendance']);
        $primary->load(['enrollments.grades', 'enrollments.attendance']);
        foreach ($duplicate->enrollments as $duplicateEnrollment) {
            $primaryEnrollment = $primary->enrollments->firstWhere('school_year', $duplicateEnrollment->school_year);
            if ($primaryEnrollment && (strcasecmp($primaryEnrollment->level, $duplicateEnrollment->level) !== 0
                || strcasecmp($primaryEnrollment->section, $duplicateEnrollment->section) !== 0)) {
                throw new \RuntimeException(
                    "Identifier conflict: matching student records have different classes for {$duplicateEnrollment->school_year}."
                );
            }
        }

        foreach ($duplicate->enrollments as $duplicateEnrollment) {
            $primaryEnrollment = $primary->enrollments->firstWhere('school_year', $duplicateEnrollment->school_year);
            if (! $primaryEnrollment) {
                $duplicateEnrollment->update(['student_id' => $primary->id]);

                continue;
            }

            foreach ($duplicateEnrollment->grades as $grade) {
                $existing = $primaryEnrollment->grades->first(fn ($candidate) => (int) $candidate->grading_period === (int) $grade->grading_period
                    && LearningAreaNormalizer::key($candidate->learning_area) === LearningAreaNormalizer::key($grade->learning_area)
                );
                $existing ? $grade->delete() : $grade->update(['student_enrollment_id' => $primaryEnrollment->id]);
            }
            foreach ($duplicateEnrollment->attendance as $attendance) {
                $existing = $primaryEnrollment->attendance->first(fn ($candidate) => (int) $candidate->grading_period === (int) $attendance->grading_period
                    && strcasecmp($candidate->month, $attendance->month) === 0
                );
                if ($existing) {
                    $existing->update([
                        'school_days' => $existing->school_days ?? $attendance->school_days,
                        'days_present' => $existing->days_present ?? $attendance->days_present,
                    ]);
                    $attendance->delete();
                } else {
                    $attendance->update(['student_enrollment_id' => $primaryEnrollment->id]);
                }
            }
            $duplicateEnrollment->delete();
        }

        $duplicateLrn = $duplicate->lrn;
        $duplicate->delete();
        if (! $primary->lrn && $duplicateLrn) {
            $primary->update(['lrn' => $duplicateLrn]);
        }

        return $primary->fresh();
    }

    private function nameKey(string $name): string
    {
        $name = mb_strtolower(trim($name));
        if (str_contains($name, ',')) {
            [$last, $given] = array_map('trim', explode(',', $name, 2));
            $name = "{$given} {$last}";
        }

        return preg_replace('/[^\pL\pN]+/u', '', $name) ?: '';
    }

    private function replaceGrades(SchoolFormEnrollment $enrollment, int $period, array $grades): void
    {
        SchoolFormGrade::where('student_enrollment_id', $enrollment->id)
            ->when($period !== 0, fn ($query) => $query->where('grading_period', $period))
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
