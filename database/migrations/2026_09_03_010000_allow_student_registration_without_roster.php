<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Pending registrations must not create or depend on official student
        // records. A student row is created only after Gmail code verification.
        Schema::table('student_account_activations', function (Blueprint $table) {
            $table->dropForeign(['student_number']);
            $table->string('submitted_name')->nullable()->after('student_number');
        });
    }

    public function down(): void
    {
        // A pending number may not exist in students, so remove pending rows
        // before restoring the original roster foreign-key requirement.
        DB::table('student_account_activations')->delete();

        Schema::table('student_account_activations', function (Blueprint $table) {
            $table->dropColumn('submitted_name');
            $table->foreign('student_number')
                ->references('student_number')
                ->on('students')
                ->cascadeOnDelete();
        });
    }
};
