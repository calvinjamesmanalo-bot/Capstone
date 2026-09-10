<?php

namespace App\Models;

use Database\Factories\GradeFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Grade extends Model
{
    /** @use HasFactory<GradeFactory> */
    use HasFactory;

    protected $fillable = [
        'student_number',
        'school_year',
        'grade_level',
        'subject',
        'q1',
        'q2',
        'q3',
        'q4',
        'final_grade',
        'grade', // Keep for compatibility if needed
        'semester',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class, 'student_number', 'student_number');
    }
}
