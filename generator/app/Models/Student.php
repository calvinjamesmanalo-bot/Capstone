<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Student extends Model
{
    protected $fillable = ['student_number', 'lrn', 'name'];

    public static function findByIdentifier(string $identifier): ?self
    {
        $identifier = trim($identifier);
        $lrn = preg_replace('/\D/', '', $identifier) ?: '';
        $studentNumber = strtoupper($identifier);

        return static::query()
            ->whereRaw('UPPER(student_number) = ?', [$studentNumber])
            ->when(
                $lrn !== '',
                fn ($query) => $query->orWhere('lrn', $lrn)
            )
            ->first();
    }

    public static function findByName(string $name): ?self
    {
        $key = static::nameKey($name);
        if ($key === '') {
            return null;
        }

        return static::query()->get()->first(
            fn (self $student) => static::nameKey($student->name) === $key
        );
    }

    private static function nameKey(string $name): string
    {
        $name = mb_strtolower(trim($name));

        if (str_contains($name, ',')) {
            [$last, $given] = array_map('trim', explode(',', $name, 2));
            $name = "{$given} {$last}";
        }

        return preg_replace('/[^\pL\pN]+/u', '', $name) ?: '';
    }

    public function enrollments()
    {
        return $this->hasMany(StudentEnrollment::class);
    }
}
