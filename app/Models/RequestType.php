<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RequestType extends Model
{
    use SoftDeletes;

    protected $fillable = ['name', 'description', 'instructions', 'fee', 'is_active', 'fields', 'version'];

    protected $casts = ['fields' => 'array', 'fee' => 'decimal:2', 'is_active' => 'boolean'];

    public function requests()
    {
        return $this->hasMany(RequestDocument::class);
    }
}
