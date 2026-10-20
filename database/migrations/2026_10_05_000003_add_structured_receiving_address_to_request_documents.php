<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('request_documents', function (Blueprint $table) {
            $table->string('receiving_address_scope', 20)->nullable()->after('receiving_school_address');
            $table->string('receiving_school_street')->nullable()->after('receiving_address_scope');
            $table->string('receiving_barangay')->nullable()->after('receiving_school_street');
            $table->string('receiving_city')->nullable()->after('receiving_barangay');
            $table->string('receiving_province')->nullable()->after('receiving_city');
            $table->string('receiving_region_code', 20)->nullable()->after('receiving_province');
            $table->text('receiving_overseas_address')->nullable()->after('receiving_region_code');
            $table->decimal('lbc_sakto_rate', 8, 2)->nullable()->after('receiving_overseas_address');
        });
    }

    public function down(): void
    {
        Schema::table('request_documents', function (Blueprint $table) {
            $table->dropColumn([
                'receiving_address_scope', 'receiving_school_street', 'receiving_barangay',
                'receiving_city', 'receiving_province', 'receiving_region_code',
                'receiving_overseas_address', 'lbc_sakto_rate',
            ]);
        });
    }
};
