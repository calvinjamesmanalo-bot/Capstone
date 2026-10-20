<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('request_document_transmissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('request_document_id')->constrained()->cascadeOnDelete();
            $table->string('method');
            $table->string('recipient_name');
            $table->text('destination');
            $table->string('reference_number');
            $table->timestamp('transmitted_at');
            $table->foreignId('transmitted_by')->constrained('users')->restrictOnDelete();
            $table->text('proof_path');
            $table->string('proof_original_name');
            $table->string('proof_mime_type');
            $table->unsignedBigInteger('proof_size');
            $table->text('remarks')->nullable();
            $table->timestamps();
        });

        Schema::create('request_document_claims', function (Blueprint $table) {
            $table->id();
            $table->foreignId('request_document_id')->unique()->constrained()->cascadeOnDelete();
            $table->timestamp('claimed_at');
            $table->foreignId('released_by')->constrained('users')->restrictOnDelete();
            $table->string('claimant_name');
            $table->string('claimant_type');
            $table->string('valid_id_type');
            $table->string('valid_id_last_four', 4);
            $table->text('authorization_path')->nullable();
            $table->string('authorization_original_name')->nullable();
            $table->text('remarks')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('request_document_claims');
        Schema::dropIfExists('request_document_transmissions');
    }
};
