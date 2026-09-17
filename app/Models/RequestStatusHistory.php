<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RequestStatusHistory extends Model
{
    protected $fillable = [
        'from_status',
        'to_status',
        'remarks',
        'changed_by',
        'is_internal',
    ];

    protected $casts = [
        'is_internal' => 'boolean',
    ];

    public function requestDocument()
    {
        return $this->belongsTo(RequestDocument::class);
    }

    public function changedBy()
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
