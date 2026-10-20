<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->string('account_verification_status', 20)->nullable()->index();
            $table->string('account_verification_email')->nullable();
            $table->string('school_id_path')->nullable();
            $table->string('school_id_selfie_path')->nullable();
            $table->string('account_verification_token_hash', 64)->nullable()->index();
            $table->timestamp('account_verification_expires_at')->nullable();
            $table->foreignId('account_verification_reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('account_verification_reviewed_at')->nullable();
            $table->text('account_verification_notes')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropForeign(['account_verification_reviewed_by']);
            $table->dropIndex(['account_verification_status']);
            $table->dropIndex(['account_verification_token_hash']);
            $table->dropColumn([
                'account_verification_status', 'account_verification_email', 'school_id_path',
                'school_id_selfie_path', 'account_verification_token_hash',
                'account_verification_expires_at', 'account_verification_reviewed_by',
                'account_verification_reviewed_at', 'account_verification_notes',
            ]);
        });
    }
};
