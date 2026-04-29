<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Student extends Model
{
    /** @use HasFactory<\Database\Factories\StudentFactory> */
    use HasFactory;

    protected $primaryKey = 'student_number';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'student_number',
        'name',
    ];

    public function grades()
    {
        return $this->hasMany(Grade::class, 'student_number', 'student_number');
    }
}
