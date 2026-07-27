<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StudentGrade extends Model
{
    protected $fillable = ['student_enrollment_id', 'grading_period', 'learning_area', 'grade'];
}
