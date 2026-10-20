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
        'request_type_id',
        'form_snapshot',
        'dynamic_values',
        'ticket_number',
        'student_number',
        'document_type',
        'school_year',
        'school_level',
        'request_purpose',
        'purpose_details',
        'receiving_school_name',
        'receiving_school_address',
        'receiving_address_scope',
        'receiving_school_street',
        'receiving_barangay',
        'receiving_city',
        'receiving_province',
        'receiving_region_code',
        'receiving_overseas_address',
        'lbc_sakto_rate',
        'request_proof_path',
        'request_proof_original_name',
        'request_proof_mime_type',
        'request_proof_size',
        'employer_name',
        'employment_proof_path',
        'employment_proof_original_name',
        'employment_proof_mime_type',
        'employment_proof_size',
        'is_f137_employment_exception',
        'exception_approved_at',
        'exception_approved_by',
        'review_status',
        'reviewed_at',
        'reviewed_by',
        'review_notes',
        'pickup_location',
        'ready_for_pickup_at',
        'is_manually_prepared',
        'manual_preparation_reason',
        'manually_prepared_at',
        'manually_prepared_by',
        'document_price',
        'status',
        'remarks',
        'delivery_method',
        'f137_delivery_method',
        'delivery_fee',
        'delivery_assessed_at',
        'delivery_assessed_by',
        'payment_method',
        'payment_confirmed',
        'payment_confirmed_at',
        'payment_confirmed_by',
        'payment_amount',
        'official_receipt_number',
        'clearance_status',
        'payment_proof_path',
        'payment_proof_disk',
        'payment_proof_original_name',
        'payment_proof_mime_type',
        'payment_proof_size',
        'payment_proof_sha256',
        'release_location',
    ];

    protected $casts = [
        'form_snapshot' => 'array',
        'dynamic_values' => 'array',
        'payment_confirmed' => 'boolean',
        'payment_confirmed_at' => 'datetime',
        'document_price' => 'decimal:2',
        'delivery_fee' => 'decimal:2',
        'delivery_assessed_at' => 'datetime',
        'payment_amount' => 'decimal:2',
        'is_f137_employment_exception' => 'boolean',
        'exception_approved_at' => 'datetime',
        'reviewed_at' => 'datetime',
        'ready_for_pickup_at' => 'datetime',
        'is_manually_prepared' => 'boolean',
        'manually_prepared_at' => 'datetime',
        'lbc_sakto_rate' => 'decimal:2',
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

    public function requestType()
    {
        return $this->belongsTo(RequestType::class)->withTrashed();
    }

    public function authenticities()
    {
        return $this->hasMany(DocumentAuthenticity::class);
    }

    public function statusHistories()
    {
        return $this->hasMany(RequestStatusHistory::class)->oldest('id');
    }

    public function transmissions()
    {
        return $this->hasMany(RequestDocumentTransmission::class);
    }

    public function claim()
    {
        return $this->hasOne(RequestDocumentClaim::class);
    }

    public function manualPreparer()
    {
        return $this->belongsTo(User::class, 'manually_prepared_by');
    }

    public function deliveryAssessor()
    {
        return $this->belongsTo(User::class, 'delivery_assessed_by');
    }

    public function publicStatusHistories()
    {
        return $this->hasMany(RequestStatusHistory::class)
            ->oldest('id');
    }

    public function hasPreparedDocument(): bool
    {
        if ($this->is_manually_prepared) {
            return true;
        }

        return $this->authenticities()
            ->where('status', 'valid')
            ->where('pdf_signature_status', 'signed')
            ->whereHas('artifacts', fn ($query) => $query
                ->where('is_pdf_signed', true)
                ->whereNotNull('storage_path'))
            ->exists();
    }
}
