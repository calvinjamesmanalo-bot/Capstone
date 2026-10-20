<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('student_masterlist_imports', function (Blueprint $table) {
            $table->unsignedInteger('new_rows')->default(0)->after('imported_rows');
            $table->unsignedInteger('reused_rows')->default(0)->after('new_rows');
        });
    }

    public function down(): void
    {
        Schema::table('student_masterlist_imports', function (Blueprint $table) {
            $table->dropColumn(['new_rows', 'reused_rows']);
        });
    }
};
