<?php

namespace App\Models;

use Database\Factories\RequestDocumentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RequestDocument extends Model
{
    /** @use HasFactory<RequestDocumentFactory> */
    use HasFactory;

    protected $fillable = [
        'ticket_number',
        'student_number',
        'document_type',
        'school_year',
        'school_level',
        'document_price',
        'status',
        'remarks',
        'delivery_method',
        'payment_method',
        'payment_confirmed',
        'clearance_status',
        'financial_balance',
        'payment_proof_path',
        'payment_proof_disk',
        'payment_proof_original_name',
        'payment_proof_mime_type',
        'payment_proof_size',
        'payment_proof_sha256',
        'release_location',
    ];

    protected $casts = [
        'payment_confirmed' => 'boolean',
        'financial_balance' => 'decimal:2',
        'document_price' => 'decimal:2',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class, 'student_number', 'student_number');
    }

    public function authenticities()
    {
        return $this->hasMany(DocumentAuthenticity::class);
    }
}
