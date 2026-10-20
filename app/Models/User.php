<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'email_verified_at',
        'pending_email',
        'password',
        'must_change_password',
        'role',
        'student_number',
        'can_bypass_request_limit',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class, 'student_number', 'student_number');
    }

    public function isArchived(): bool
    {
        return str_starts_with((string) $this->role, 'archived_');
    }

    public function archivedOriginalRole(): string
    {
        return $this->isArchived() ? substr((string) $this->role, 9) : (string) $this->role;
    }

    public function requestStatusChanges()
    {
        return $this->hasMany(RequestStatusHistory::class, 'changed_by');
    }

    public function messageThreads()
    {
        return $this->hasMany(MessageThread::class, 'student_id');
    }

    public function sentMessages()
    {
        return $this->hasMany(MessageEntry::class, 'sender_id');
    }

    public function hasMatchingOfficialStudentRecord(): bool
    {
        if ($this->role !== 'student') {
            return true;
        }

        $officialEmail = $this->student?->official_email;

        return $officialEmail !== null
            && hash_equals(strtolower(trim($officialEmail)), strtolower(trim($this->email)));
    }

    /**
     * Get the display name format: First Middle Last Suffix
     */
    public function getDisplayNameAttribute()
    {
        // Name is stored as: Last, Middle, First, Suffix
        // Using a more robust explode that handles potential missing spaces after commas
        $parts = array_map('trim', explode(',', $this->name));

        $lastName = $parts[0] ?? '';
        $middleName = $parts[1] ?? '';
        $firstName = $parts[2] ?? '';
        $suffix = $parts[3] ?? '';

        $displayParts = [];
        if (! empty($firstName)) {
            $displayParts[] = $firstName;
        }
        if (! empty($middleName)) {
            $displayParts[] = $middleName;
        }
        if (! empty($lastName)) {
            $displayParts[] = $lastName;
        }
        if (! empty($suffix)) {
            $displayParts[] = $suffix;
        }

        return implode(' ', $displayParts);
    }

    public function getFirstNameOnlyAttribute()
    {
        $parts = array_map('trim', explode(',', $this->name));

        return $parts[2] ?? ($parts[0] ?? 'User');
    }

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'must_change_password' => 'boolean',
            'can_bypass_request_limit' => 'boolean',
        ];
    }
}
