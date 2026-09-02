<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DocumentAuthenticity extends Model
{
    protected $fillable = [
        'request_document_id',
        'verification_token',
        'control_number',
        'document_type',
        'holder_name',
        'holder_identifier',
        'purpose',
        'issued_at',
        'expires_at',
        'status',
        'content_hash',
        'source_data',
        'signed_payload',
        'signature',
        'signature_algorithm',
        'issuer_name',
        'issuer_key_fingerprint',
        'issued_by',
        'pdf_signature_status',
        'pdf_signed_at',
        'pdf_signer_name',
        'pdf_certificate_fingerprint',
        'blockchain_status',
        'blockchain_transaction_hash',
        'blockchain_recorded_at',
        'reissued_from_id',
        'replaced_by_id',
        'revoked_at',
        'revoked_by',
        'revocation_reason',
    ];

    protected $casts = [
        'source_data' => 'array',
        'issued_at' => 'datetime',
        'expires_at' => 'datetime',
        'revoked_at' => 'datetime',
        'pdf_signed_at' => 'datetime',
        'blockchain_recorded_at' => 'datetime',
    ];

    public function requestDocument()
    {
        return $this->belongsTo(RequestDocument::class);
    }

    public function audits()
    {
        return $this->hasMany(DocumentVerificationAudit::class);
    }

    public function artifacts()
    {
        return $this->hasMany(DocumentArtifact::class);
    }

    public function revokedBy()
    {
        return $this->belongsTo(User::class, 'revoked_by');
    }

    public function issuedBy()
    {
        return $this->belongsTo(User::class, 'issued_by');
    }

    public function reissuedFrom()
    {
        return $this->belongsTo(self::class, 'reissued_from_id');
    }

    public function replacement()
    {
        return $this->belongsTo(self::class, 'replaced_by_id');
    }

    public function officialArtifact()
    {
        return $this->hasOne(DocumentArtifact::class)->where('is_official', true)->latestOfMany();
    }
}
