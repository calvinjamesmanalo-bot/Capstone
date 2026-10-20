<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RequestDocumentTransmission extends Model
{
    protected $fillable = [
        'request_document_id', 'method', 'recipient_name', 'destination',
        'reference_number', 'transmitted_at', 'transmitted_by', 'proof_path',
        'proof_original_name', 'proof_mime_type', 'proof_size', 'remarks',
    ];

    protected $casts = ['transmitted_at' => 'datetime'];

    public function requestDocument()
    {
        return $this->belongsTo(RequestDocument::class);
    }

    public function transmitter()
    {
        return $this->belongsTo(User::class, 'transmitted_by');
    }
}
