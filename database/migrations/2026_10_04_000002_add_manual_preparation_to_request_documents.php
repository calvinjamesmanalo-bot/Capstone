<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('request_documents', function (Blueprint $table) {
            $table->boolean('is_manually_prepared')->default(false)->after('ready_for_pickup_at');
            $table->text('manual_preparation_reason')->nullable()->after('is_manually_prepared');
            $table->timestamp('manually_prepared_at')->nullable()->after('manual_preparation_reason');
            $table->foreignId('manually_prepared_by')->nullable()->after('manually_prepared_at')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('request_documents', function (Blueprint $table) {
            $table->dropConstrainedForeignId('manually_prepared_by');
            $table->dropColumn(['is_manually_prepared', 'manual_preparation_reason', 'manually_prepared_at']);
        });
    }
};
