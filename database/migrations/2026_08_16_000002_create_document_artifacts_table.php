<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_artifacts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_authenticity_id')->constrained('document_authenticities')->cascadeOnDelete();
            $table->char('sha256_hash', 64);
            $table->string('hash_signature', 128);
            $table->unsignedBigInteger('file_size');
            $table->string('mime_type', 120);
            $table->string('original_filename')->nullable();
            $table->timestamps();

            $table->unique(['document_authenticity_id', 'sha256_hash'], 'document_artifact_hash_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_artifacts');
    }
};
