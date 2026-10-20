<?php

namespace App\Support;

use App\Models\SchoolFormAttendance;
use App\Models\SchoolFormEnrollment;
use App\Models\SchoolFormGrade;
use App\Models\SchoolFormStudent;
use App\Models\Student;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DemoStudentData
{
    public const STUDENT_NUMBER = 'TEST-CJM-2026';
    public const LRN = '999999999999';
    public const EMAIL = 'calvin.james.manalo.demo@fiatlux.test';
    public const PASSWORD = 'TestAccount2026!';

    public function create(): User
    {
        $storedName = 'Manalo,,Calvin James,';

        DB::transaction(function () use ($storedName): void {
            Student::updateOrCreate(['student_number' => self::STUDENT_NUMBER], [
                'lrn' => self::LRN,
                'name' => $storedName,
                'official_email' => self::EMAIL,
                'masterlist_school_year' => '2025-2026',
                'masterlist_campus' => 'Fiat Lux Academe - Cavite',
                'masterlist_grade_level' => 'Grade 12',
                'masterlist_section' => 'St. Thomas Aquinas',
                'gender' => 'Male',
                'masterlist_active' => true,
                'account_verification_status' => 'completed',
                'account_verification_email' => self::EMAIL,
                'account_verification_reviewed_at' => now(),
                'account_verification_notes' => 'System-generated full-history demo account.',
            ]);

            User::updateOrCreate(['student_number' => self::STUDENT_NUMBER], [
                'name' => $storedName,
                'email' => self::EMAIL,
                'email_verified_at' => now(),
                'password' => Hash::make(self::PASSWORD),
                'role' => 'student',
                'can_bypass_request_limit' => true,
            ]);
        });

        $schoolStudent = SchoolFormStudent::updateOrCreate(
            ['student_number' => self::STUDENT_NUMBER],
            ['lrn' => self::LRN, 'name' => 'Manalo, Calvin James']
        );

        foreach ($this->academicHistory() as $index => $record) {
            $enrollment = SchoolFormEnrollment::updateOrCreate(
                ['student_id' => $schoolStudent->id, 'school_year' => $record['school_year']],
                ['level' => $record['level'], 'section' => $record['section'], 'adviser_name' => $record['adviser']]
            );
            $enrollment->grades()->delete();
            $enrollment->attendance()->delete();

            foreach ($this->subjectsFor($record['level']) as $subjectIndex => $subject) {
                foreach (range(1, 4) as $period) {
                    SchoolFormGrade::create([
                        'student_enrollment_id' => $enrollment->id,
                        'grading_period' => $period,
                        'learning_area' => $subject,
                        'grade' => 84 + (($index + $subjectIndex + $period * 2) % 14),
                    ]);
                }
            }

            foreach ($this->attendanceMonths() as $monthIndex => $month) {
                $schoolDays = [20, 21, 20, 18, 20, 16, 20, 20, 22, 17][$monthIndex];
                SchoolFormAttendance::create([
                    'student_enrollment_id' => $enrollment->id,
                    'grading_period' => min(4, intdiv($monthIndex, 3) + 1),
                    'month' => $month,
                    'school_days' => $schoolDays,
                    'days_present' => max(0, $schoolDays - (($index + $monthIndex) % 3)),
                ]);
            }
        }

        return User::where('student_number', self::STUDENT_NUMBER)->firstOrFail();
    }

    private function academicHistory(): array
    {
        $levels = ['Kinder', 'Grade 1', 'Grade 2', 'Grade 3', 'Grade 4', 'Grade 5', 'Grade 6', 'Grade 7', 'Grade 8', 'Grade 9', 'Grade 10', 'Grade 11', 'Grade 12'];
        $sections = ['Hope', 'Faith', 'Charity', 'Wisdom', 'Generosity', 'Humility', 'Perseverance', 'Erbium', 'Radium', 'Osmium', 'Dubnium', 'St. Augustine', 'St. Thomas Aquinas'];

        return array_map(fn ($level, $index) => [
            'school_year' => (2013 + $index).'-'.(2014 + $index),
            'level' => $level,
            'section' => $sections[$index],
            'adviser' => 'Demo Adviser '.($index + 1),
        ], $levels, array_keys($levels));
    }

    private function subjectsFor(string $level): array
    {
        if ($level === 'Kinder') {
            return ['Language and Literacy', 'Numeracy', 'Physical Development', 'Socio-Emotional Development', 'Understanding the Physical and Natural Environment'];
        }
        $grade = (int) filter_var($level, FILTER_SANITIZE_NUMBER_INT);
        if ($grade <= 3) {
            return ['FILIPINO', 'ENGLISH', 'MATHEMATICS', 'SCIENCE', 'ARALING PANLIPUNAN', 'Edukasyon sa Pagpapakatao', 'MAPEH'];
        }
        if ($grade <= 6) {
            return ['FILIPINO', 'ENGLISH', 'MATHEMATICS', 'SCIENCE', 'ARALING PANLIPUNAN', 'Edukasyon sa Pagpapakatao', 'EPP / TLE', 'MAPEH', 'Music', 'Art', 'P.E.', 'Health'];
        }
        if ($grade <= 10) {
            return ['FILIPINO', 'ENGLISH', 'MATHEMATICS', 'SCIENCE', 'ARALING PANLIPUNAN', 'Edukasyon sa Pagpapakatao', 'TLE', 'MAPEH', 'Music', 'Art', 'P.E.', 'Health'];
        }

        return ['Oral Communication', 'Reading and Writing', 'Komunikasyon at Pananaliksik', 'General Mathematics', 'Statistics and Probability', 'Earth and Life Science', 'Physical Science', 'Personal Development', 'Understanding Culture Society and Politics', 'Physical Education and Health', 'Empowerment Technologies', 'Practical Research'];
    }

    private function attendanceMonths(): array
    {
        return ['June', 'July', 'August', 'September', 'October', 'November', 'December', 'January', 'February', 'March'];
    }
}
