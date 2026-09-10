<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('request_documents', function (Blueprint $table) {
            $table->string('payment_proof_disk')->nullable()->after('payment_proof_path');
            $table->string('payment_proof_original_name')->nullable()->after('payment_proof_disk');
            $table->string('payment_proof_mime_type')->nullable()->after('payment_proof_original_name');
            $table->unsignedBigInteger('payment_proof_size')->nullable()->after('payment_proof_mime_type');
            $table->string('payment_proof_sha256', 64)->nullable()->after('payment_proof_size');
        });
    }

    public function down(): void
    {
        Schema::table('request_documents', function (Blueprint $table) {
            $table->dropColumn([
                'payment_proof_disk',
                'payment_proof_original_name',
                'payment_proof_mime_type',
                'payment_proof_size',
                'payment_proof_sha256',
            ]);
        });
    }
};
