<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SchoolFormUpload extends Model
{
    protected $connection = 'school_forms';

    protected $table = 'grade_sheet_uploads';

    protected $fillable = ['school_year', 'level', 'section', 'grading_period', 'file_type', 'original_name', 'stored_path'];

    public function getPeriodLabelAttribute(): string
    {
        return $this->file_type === 'attendance' && (int) $this->grading_period === 0
            ? 'Whole school year'
            : (string) $this->grading_period;
    }
}
