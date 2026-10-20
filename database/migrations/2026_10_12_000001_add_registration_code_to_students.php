<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->string('registration_code_hash', 64)->nullable()->after('masterlist_active');
            $table->timestamp('registration_code_expires_at')->nullable()->after('registration_code_hash');
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropColumn(['registration_code_hash', 'registration_code_expires_at']);
        });
    }
};
