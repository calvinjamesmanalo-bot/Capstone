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

    protected static function booted(): void
    {
        static::created(function (RequestDocument $requestDocument): void {
            $requestDocument->statusHistories()->create([
                'from_status' => null,
                'to_status' => $requestDocument->status ?? 'pending',
                'remarks' => null,
                'changed_by' => auth()->id(),
                'is_internal' => false,
            ]);
        });

        static::updated(function (RequestDocument $requestDocument): void {
            if (! $requestDocument->wasChanged('status')) {
                return;
            }

            $requestDocument->statusHistories()->create([
                'from_status' => $requestDocument->getOriginal('status'),
                'to_status' => $requestDocument->status,
                // Existing remarks may contain internal processing details.
                'remarks' => $requestDocument->remarks,
                'changed_by' => auth()->id(),
                'is_internal' => filled($requestDocument->remarks),
            ]);
        });
    }

    public function student()
    {
        return $this->belongsTo(Student::class, 'student_number', 'student_number');
    }

    public function authenticities()
    {
        return $this->hasMany(DocumentAuthenticity::class);
    }

    public function statusHistories()
    {
        return $this->hasMany(RequestStatusHistory::class)->oldest('id');
    }

    public function publicStatusHistories()
    {
        return $this->hasMany(RequestStatusHistory::class)
            ->oldest('id');
    }
}
