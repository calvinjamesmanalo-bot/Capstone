<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GradeSheetUpload extends Model
{
    protected $fillable = ['school_year', 'level', 'section', 'grading_period', 'file_type', 'original_name', 'stored_path'];
}
