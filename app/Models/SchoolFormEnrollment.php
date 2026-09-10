<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use RuntimeException;

class SchoolFormEnrollment extends Model
{
    protected $connection = 'school_forms';

    protected $table = 'student_enrollments';

    protected $fillable = ['student_id', 'school_year', 'level', 'section', 'adviser_name'];

    public function student()
    {
        return $this->belongsTo(SchoolFormStudent::class, 'student_id');
    }

    public function grades()
    {
        return $this->hasMany(SchoolFormGrade::class, 'student_enrollment_id');
    }

    public function attendance()
    {
        return $this->hasMany(SchoolFormAttendance::class, 'student_enrollment_id');
    }

    public static function resolveForImport(
        SchoolFormStudent $student,
        string $schoolYear,
        string $level,
        string $section,
        ?string $adviserName = null,
    ): self {
        $enrollment = static::query()
            ->where('student_id', $student->id)
            ->where('school_year', $schoolYear)
            ->lockForUpdate()
            ->first();

        if (! $enrollment) {
            return static::create(array_filter([
                'student_id' => $student->id,
                'school_year' => $schoolYear,
                'level' => $level,
                'section' => $section,
                'adviser_name' => $adviserName,
            ], fn ($value) => $value !== null));
        }

        if (strcasecmp(trim($enrollment->level), trim($level)) !== 0
            || strcasecmp(trim($enrollment->section), trim($section)) !== 0) {
            $identifier = $student->student_number ?: $student->lrn;

            throw new RuntimeException(
                "Enrollment conflict for {$identifier}: {$schoolYear} is already {$enrollment->level} - {$enrollment->section}; "
                ."the uploaded sheet was tagged {$level} - {$section}. Check the selected school year and grade level."
            );
        }

        if ($adviserName && $enrollment->adviser_name !== $adviserName) {
            $enrollment->update(['adviser_name' => $adviserName]);
        }

        return $enrollment;
    }
}
