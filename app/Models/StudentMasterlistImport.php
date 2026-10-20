<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StudentMasterlistImport extends Model
{
    protected $fillable = ['school_year', 'original_name', 'file_sha256', 'storage_path', 'mime_type', 'imported_rows', 'new_rows', 'reused_rows', 'invalid_lrn_rows', 'imported_by'];

    public function importer()
    {
        return $this->belongsTo(User::class, 'imported_by');
    }
}
