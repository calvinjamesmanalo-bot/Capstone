<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LoginAttemptLog extends Model
{
    protected $fillable = [
        'user_id',
        'attempted_identifier',
        'account_type',
        'ip_address',
        'user_agent',
        'failure_reason',
        'anomaly_detected',
    ];

    protected function casts(): array
    {
        return [
            'anomaly_detected' => 'boolean',
        ];
    }
}
