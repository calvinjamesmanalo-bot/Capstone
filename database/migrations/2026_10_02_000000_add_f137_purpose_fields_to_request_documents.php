<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('request_documents', function (Blueprint $table) {
            $table->string('request_purpose')->nullable()->after('school_level');
            $table->text('purpose_details')->nullable()->after('request_purpose');
            $table->string('receiving_school_name')->nullable()->after('purpose_details');
            $table->text('receiving_school_address')->nullable()->after('receiving_school_name');
            $table->text('request_proof_path')->nullable()->after('receiving_school_address');
            $table->string('request_proof_original_name')->nullable()->after('request_proof_path');
            $table->string('request_proof_mime_type')->nullable()->after('request_proof_original_name');
            $table->unsignedBigInteger('request_proof_size')->nullable()->after('request_proof_mime_type');
            $table->string('employer_name')->nullable()->after('request_purpose');
            $table->text('employment_proof_path')->nullable()->after('employer_name');
            $table->string('employment_proof_original_name')->nullable()->after('employment_proof_path');
            $table->string('employment_proof_mime_type')->nullable()->after('employment_proof_original_name');
            $table->unsignedBigInteger('employment_proof_size')->nullable()->after('employment_proof_mime_type');
            $table->boolean('is_f137_employment_exception')->default(false)->after('employment_proof_size');
            $table->timestamp('exception_approved_at')->nullable()->after('is_f137_employment_exception');
            $table->foreignId('exception_approved_by')->nullable()->after('exception_approved_at')->constrained('users')->nullOnDelete();
            $table->string('review_status')->nullable()->after('exception_approved_by');
            $table->timestamp('reviewed_at')->nullable()->after('review_status');
            $table->foreignId('reviewed_by')->nullable()->after('reviewed_at')->constrained('users')->nullOnDelete();
            $table->text('review_notes')->nullable()->after('reviewed_by');
            $table->text('pickup_location')->nullable()->after('review_notes');
            $table->timestamp('ready_for_pickup_at')->nullable()->after('pickup_location');
        });
    }

    public function down(): void
    {
        Schema::table('request_documents', function (Blueprint $table) {
            $table->dropConstrainedForeignId('exception_approved_by');
            $table->dropConstrainedForeignId('reviewed_by');
            $table->dropColumn([
                'request_purpose', 'purpose_details', 'receiving_school_name',
                'receiving_school_address', 'request_proof_path',
                'request_proof_original_name', 'request_proof_mime_type', 'request_proof_size',
                'employer_name', 'employment_proof_path',
                'employment_proof_original_name', 'employment_proof_mime_type',
                'employment_proof_size', 'is_f137_employment_exception',
                'exception_approved_at', 'review_status', 'reviewed_at', 'review_notes',
                'pickup_location', 'ready_for_pickup_at',
            ]);
        });
    }
};
