<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class SchoolFormUpload extends Model
{
    protected $connection = 'school_forms';
    protected $table = 'grade_sheet_uploads';
    protected $fillable = ['school_year', 'level', 'section', 'grading_period', 'file_type', 'original_name', 'stored_path'];
}
