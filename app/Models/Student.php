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
    ];

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
        return $this->hasOne(User::class, 'student_number', 'student_number');
    }

    public function grades()
    {
        return $this->hasMany(Grade::class, 'student_number', 'student_number');
    }
}
