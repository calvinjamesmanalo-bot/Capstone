<?php

namespace App\Models;

use Database\Factories\StudentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Student extends Model
{
    /** @use HasFactory<StudentFactory> */
    use HasFactory;

    protected $primaryKey = 'student_number';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'student_number',
        'lrn',
        'name',
        'official_email',
        'masterlist_import_id',
        'masterlist_school_year',
        'masterlist_campus',
        'masterlist_grade_level',
        'masterlist_section',
        'gender',
        'masterlist_active',
        'registration_code_hash',
        'registration_code_expires_at',
        'account_verification_status',
        'account_verification_email',
        'school_id_path',
        'school_id_selfie_path',
        'account_verification_token_hash',
        'account_verification_expires_at',
        'account_verification_reviewed_by',
        'account_verification_reviewed_at',
        'account_verification_notes',
    ];

    protected function casts(): array
    {
        return [
            'masterlist_active' => 'boolean',
            'registration_code_expires_at' => 'datetime',
            'account_verification_expires_at' => 'datetime',
            'account_verification_reviewed_at' => 'datetime',
        ];
    }

    public function resolvedLrn(): ?string
    {
        if (filled($this->lrn)) {
            return (string) $this->lrn;
        }

        try {
            $lrn = SchoolFormStudent::query()
                ->where('student_number', $this->student_number)
                ->value('lrn');

            return filled($lrn) ? (string) $lrn : null;
        } catch (\Throwable) {
            // Student accounts remain available if the school-forms database
            // is temporarily unavailable.
            return null;
        }
    }

    public function user()
    {
        return $this->hasOne(User::class, 'student_number', 'student_number')
            ->where('role', 'not like', 'archived_%');
    }

    public function grades()
    {
        return $this->hasMany(Grade::class, 'student_number', 'student_number');
    }
}
