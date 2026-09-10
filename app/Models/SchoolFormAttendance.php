<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SchoolFormAttendance extends Model
{
    protected $connection = 'school_forms';

    protected $table = 'student_attendance';

    protected $fillable = ['student_enrollment_id', 'grading_period', 'month', 'school_days', 'days_present'];
}
