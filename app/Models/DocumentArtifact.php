<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DocumentArtifact extends Model
{
    protected $fillable = [
        'document_authenticity_id',
        'sha256_hash',
        'hash_signature',
        'file_size',
        'mime_type',
        'original_filename',
        'storage_disk',
        'storage_path',
        'is_official',
        'is_pdf_signed',
    ];

    protected $casts = [
        'file_size' => 'integer',
        'is_official' => 'boolean',
        'is_pdf_signed' => 'boolean',
    ];

    public function documentAuthenticity()
    {
        return $this->belongsTo(DocumentAuthenticity::class);
    }
}
