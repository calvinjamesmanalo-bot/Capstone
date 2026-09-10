<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->string('lrn', 12)->nullable()->unique()->after('student_number');
        });

        Schema::table('student_account_activations', function (Blueprint $table) {
            $table->string('lrn', 12)->nullable()->after('student_number');
        });
    }

    public function down(): void
    {
        Schema::table('student_account_activations', function (Blueprint $table) {
            $table->dropColumn('lrn');
        });

        Schema::table('students', function (Blueprint $table) {
            $table->dropUnique(['lrn']);
            $table->dropColumn('lrn');
        });
    }
};
