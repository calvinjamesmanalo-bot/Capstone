<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('request_documents', function (Blueprint $table) {
            $table->string('delivery_method')->nullable()->after('remarks'); // pickup, delivery
            $table->string('payment_method')->nullable()->after('delivery_method'); // cash, gcash, bank_transfer
            $table->boolean('payment_confirmed')->default(false)->after('payment_method');
            $table->string('clearance_status')->nullable()->after('payment_confirmed'); // cleared, pending_clearance, has_balance
            $table->decimal('financial_balance', 10, 2)->nullable()->after('clearance_status');
            $table->text('payment_proof_path')->nullable()->after('financial_balance');
            $table->string('release_location')->nullable()->after('payment_proof_path');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('request_documents', function (Blueprint $table) {
            $table->dropColumn([
                'delivery_method',
                'payment_method',
                'payment_confirmed',
                'clearance_status',
                'financial_balance',
                'payment_proof_path',
                'release_location'
            ]);
        });
    }
};
