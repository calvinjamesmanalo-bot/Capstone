<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Form138Upload extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_number',
        'school_year',
        'file_path',
        'pdf_path',
        'original_filename',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class, 'student_number', 'student_number');
    }
}
