<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('form138_uploads', function (Blueprint $table) {
            $table->string('storage_disk')->nullable()->after('original_filename');
            $table->string('mime_type')->nullable()->after('storage_disk');
            $table->unsignedBigInteger('file_size')->nullable()->after('mime_type');
            $table->string('sha256', 64)->nullable()->after('file_size');
        });
    }

    public function down(): void
    {
        Schema::table('form138_uploads', function (Blueprint $table) {
            $table->dropColumn(['storage_disk', 'mime_type', 'file_size', 'sha256']);
        });
    }
};
