<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_authenticities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('request_document_id')->nullable()->constrained('request_documents')->nullOnDelete();
            $table->uuid('verification_token')->unique();
            $table->string('control_number', 32)->unique();
            $table->string('document_type');
            $table->string('holder_name');
            $table->string('holder_identifier')->nullable();
            $table->string('purpose')->nullable();
            $table->timestamp('issued_at');
            $table->timestamp('expires_at')->nullable();
            $table->string('status', 24)->default('valid');
            $table->char('content_hash', 64);
            $table->json('source_data');
            $table->text('signed_payload');
            $table->string('signature', 128);
            $table->string('signature_algorithm', 32)->default('Ed25519');
            $table->string('issuer_name');
            $table->string('issuer_key_fingerprint', 64);
            $table->timestamp('revoked_at')->nullable();
            $table->foreignId('revoked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('revocation_reason')->nullable();
            $table->timestamps();

            $table->index(['document_type', 'holder_name']);
            $table->index(['status', 'expires_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_authenticities');
    }
};
