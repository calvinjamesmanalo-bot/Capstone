<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        $groups = DB::table('students')->get()->groupBy(fn ($student) => mb_strtolower(trim($student->name)));

        foreach ($groups as $students) {
            $target = $students->first(fn ($student) => preg_match('/^\d{4}-\d+$/', (string) $student->student_number));
            if (!$target) continue;

            $lrn = $students->pluck('lrn')->filter()->first()
                ?? $students->pluck('student_number')->first(fn ($value) => preg_match('/^\d{10,20}$/', (string) $value));

            foreach ($students as $source) {
                if ($source->id === $target->id) continue;
                DB::table('students')->where('id', $source->id)->update(['lrn' => null]);
            }
            if ($lrn) DB::table('students')->where('id', $target->id)->update(['lrn' => $lrn]);

            foreach ($students as $source) {
                if ($source->id === $target->id) continue;
                foreach (DB::table('student_enrollments')->where('student_id', $source->id)->get() as $sourceEnrollment) {
                    $targetEnrollmentId = DB::table('student_enrollments')->where([
                        'student_id' => $target->id, 'school_year' => $sourceEnrollment->school_year,
                    ])->value('id');
                    if (!$targetEnrollmentId) {
                        $targetEnrollmentId = DB::table('student_enrollments')->insertGetId([
                            'student_id' => $target->id, 'school_year' => $sourceEnrollment->school_year,
                            'level' => $sourceEnrollment->level, 'section' => $sourceEnrollment->section,
                            'created_at' => now(), 'updated_at' => now(),
                        ]);
                    }
                    foreach (DB::table('student_grades')->where('student_enrollment_id', $sourceEnrollment->id)->get() as $grade) {
                        DB::table('student_grades')->updateOrInsert([
                            'student_enrollment_id' => $targetEnrollmentId, 'grading_period' => $grade->grading_period,
                            'learning_area' => $grade->learning_area,
                        ], ['grade' => $grade->grade, 'created_at' => $grade->created_at, 'updated_at' => now()]);
                    }
                    foreach (DB::table('student_attendance')->where('student_enrollment_id', $sourceEnrollment->id)->get() as $attendance) {
                        DB::table('student_attendance')->updateOrInsert([
                            'student_enrollment_id' => $targetEnrollmentId, 'grading_period' => $attendance->grading_period,
                            'month' => $attendance->month,
                        ], ['school_days' => $attendance->school_days, 'days_present' => $attendance->days_present,
                            'created_at' => $attendance->created_at, 'updated_at' => now()]);
                    }
                }
                DB::table('students')->where('id', $source->id)->delete();
            }
        }
    }

    public function down(): void {}
};
