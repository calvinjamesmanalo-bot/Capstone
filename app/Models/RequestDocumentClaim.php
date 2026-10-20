<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RequestDocumentClaim extends Model
{
    protected $fillable = [
        'request_document_id', 'claimed_at', 'released_by', 'claimant_name',
        'claimant_type', 'valid_id_type', 'valid_id_last_four',
        'authorization_path', 'authorization_original_name', 'remarks',
    ];

    protected $casts = ['claimed_at' => 'datetime'];

    public function requestDocument()
    {
        return $this->belongsTo(RequestDocument::class);
    }

    public function releaser()
    {
        return $this->belongsTo(User::class, 'released_by');
    }
}
