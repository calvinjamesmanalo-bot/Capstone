<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class SchoolFormGrade extends Model
{
    protected $connection = 'school_forms';
    protected $table = 'student_grades';
    protected $fillable = ['student_enrollment_id', 'grading_period', 'learning_area', 'grade'];
}
