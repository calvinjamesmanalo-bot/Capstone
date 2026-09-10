<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StudentAccountActivation extends Model
{
    protected $fillable = [
        'student_number',
        'lrn',
        'submitted_name',
        'email',
        'token_hash',
        'verification_code_hash',
        'password_hash',
        'verification_attempts',
        'expires_at',
        'used_at',
    ];

    protected $hidden = [
        'token_hash',
        'verification_code_hash',
        'password_hash',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'used_at' => 'datetime',
            'verification_attempts' => 'integer',
        ];
    }
}
