<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StudentEnrollment extends Model
{
    protected $fillable = ['student_id', 'school_year', 'level', 'section', 'adviser_name'];

    public function student() { return $this->belongsTo(Student::class); }
    public function grades() { return $this->hasMany(StudentGrade::class); }
    public function attendance() { return $this->hasMany(StudentAttendance::class); }
}
