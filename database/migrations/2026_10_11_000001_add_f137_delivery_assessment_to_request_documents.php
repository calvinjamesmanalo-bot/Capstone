<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('request_documents', function (Blueprint $table) {
            $table->string('f137_delivery_method')->nullable()->after('delivery_method');
            $table->decimal('delivery_fee', 8, 2)->nullable()->after('f137_delivery_method');
            $table->timestamp('delivery_assessed_at')->nullable()->after('delivery_fee');
            $table->foreignId('delivery_assessed_by')->nullable()->after('delivery_assessed_at')->constrained('users')->nullOnDelete();
            $table->decimal('payment_amount', 8, 2)->nullable()->after('payment_confirmed_by');
            $table->string('official_receipt_number', 100)->nullable()->after('payment_amount');
        });
    }

    public function down(): void
    {
        Schema::table('request_documents', function (Blueprint $table) {
            $table->dropConstrainedForeignId('delivery_assessed_by');
            $table->dropColumn(['f137_delivery_method', 'delivery_fee', 'delivery_assessed_at', 'payment_amount', 'official_receipt_number']);
        });
    }
};
