<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->string('student_number', 40)->nullable()->unique()->after('id');
        });

        // Keep the documented test learner usable after upgrading an existing database.
        DB::table('students')->where('lrn', '424413240015')->update(['student_number' => '2020-0001']);
    }

    public function down(): void
    {
        Schema::table('students', fn (Blueprint $table) => $table->dropColumn('student_number'));
    }
};
