<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->string('official_email')->nullable()->unique()->after('name');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('pending_email')->nullable()->unique()->after('email');
        });

        // Preserve existing student-account links when upgrading an installation.
        DB::table('users')
            ->where('role', 'student')
            ->whereNotNull('student_number')
            ->orderBy('id')
            ->each(function (object $user): void {
                DB::table('students')
                    ->where('student_number', $user->student_number)
                    ->whereNull('official_email')
                    ->update(['official_email' => strtolower(trim($user->email))]);
            });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('pending_email');
        });

        Schema::table('students', function (Blueprint $table) {
            $table->dropColumn('official_email');
        });
    }
};
