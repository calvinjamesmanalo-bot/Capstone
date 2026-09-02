<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('document_authenticities', function (Blueprint $table) {
            $table->foreignId('issued_by')->nullable()->after('issuer_key_fingerprint')->constrained('users')->nullOnDelete();
            $table->string('pdf_signature_status', 24)->default('pending')->after('issued_by');
            $table->timestamp('pdf_signed_at')->nullable()->after('pdf_signature_status');
            $table->string('pdf_signer_name')->nullable()->after('pdf_signed_at');
            $table->char('pdf_certificate_fingerprint', 64)->nullable()->after('pdf_signer_name');
            $table->string('blockchain_status', 24)->default('unavailable')->after('pdf_certificate_fingerprint');
            $table->string('blockchain_transaction_hash')->nullable()->after('blockchain_status');
            $table->timestamp('blockchain_recorded_at')->nullable()->after('blockchain_transaction_hash');
            $table->unsignedBigInteger('reissued_from_id')->nullable()->after('blockchain_recorded_at');
            $table->unsignedBigInteger('replaced_by_id')->nullable()->after('reissued_from_id');

            $table->index('reissued_from_id');
            $table->index('replaced_by_id');
        });

        Schema::table('document_artifacts', function (Blueprint $table) {
            $table->string('storage_disk', 40)->nullable()->after('original_filename');
            $table->string('storage_path')->nullable()->after('storage_disk');
            $table->boolean('is_official')->default(false)->after('storage_path');
            $table->boolean('is_pdf_signed')->default(false)->after('is_official');
        });
    }

    public function down(): void
    {
        Schema::table('document_artifacts', function (Blueprint $table) {
            $table->dropColumn(['storage_disk', 'storage_path', 'is_official', 'is_pdf_signed']);
        });

        Schema::table('document_authenticities', function (Blueprint $table) {
            $table->dropForeign(['issued_by']);
            $table->dropIndex(['reissued_from_id']);
            $table->dropIndex(['replaced_by_id']);
            $table->dropColumn([
                'issued_by',
                'pdf_signature_status',
                'pdf_signed_at',
                'pdf_signer_name',
                'pdf_certificate_fingerprint',
                'blockchain_status',
                'blockchain_transaction_hash',
                'blockchain_recorded_at',
                'reissued_from_id',
                'replaced_by_id',
            ]);
        });
    }
};
