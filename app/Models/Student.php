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
        'name',
        'official_email',
    ];

    public function user()
    {
        return $this->hasOne(User::class, 'student_number', 'student_number');
    }

    public function grades()
    {
        return $this->hasMany(Grade::class, 'student_number', 'student_number');
    }
}
