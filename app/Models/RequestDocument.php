<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RequestDocument extends Model
{
    /** @use HasFactory<\Database\Factories\RequestDocumentFactory> */
    use HasFactory;

    protected $fillable = [
        'student_number',
        'document_type',
        'status',
        'remarks',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class, 'student_number', 'student_number');
    }
}
