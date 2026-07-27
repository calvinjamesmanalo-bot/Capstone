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
            $table->string('ticket_number')->nullable()->unique()->after('id');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->boolean('can_bypass_request_limit')->default(false)->after('student_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('request_documents', function (Blueprint $table) {
            $table->dropColumn('ticket_number');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('can_bypass_request_limit');
        });
    }
};
