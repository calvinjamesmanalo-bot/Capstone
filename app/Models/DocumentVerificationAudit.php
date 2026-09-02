<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DocumentVerificationAudit extends Model
{
    protected $fillable = [
        'document_authenticity_id',
        'user_id',
        'control_number',
        'result',
        'ip_address',
        'user_agent',
        'verified_at',
    ];

    protected $casts = [
        'verified_at' => 'datetime',
    ];

    public function documentAuthenticity()
    {
        return $this->belongsTo(DocumentAuthenticity::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
