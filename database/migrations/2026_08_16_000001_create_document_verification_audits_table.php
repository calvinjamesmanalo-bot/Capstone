<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_verification_audits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_authenticity_id')->nullable()->constrained('document_authenticities')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('control_number', 32)->nullable();
            $table->string('result', 32);
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('verified_at');
            $table->timestamps();

            $table->index(['document_authenticity_id', 'verified_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_verification_audits');
    }
};
