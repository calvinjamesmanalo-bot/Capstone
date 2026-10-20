<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_masterlist_imports', function (Blueprint $table) {
            $table->id();
            $table->string('school_year', 20);
            $table->string('original_name');
            $table->char('file_sha256', 64)->unique();
            $table->string('storage_path');
            $table->string('mime_type', 100)->nullable();
            $table->unsignedInteger('imported_rows')->default(0);
            $table->unsignedInteger('invalid_lrn_rows')->default(0);
            $table->foreignId('imported_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::table('students', function (Blueprint $table) {
            $table->foreignId('masterlist_import_id')->nullable()->after('official_email')->constrained('student_masterlist_imports')->nullOnDelete();
            $table->string('masterlist_school_year', 20)->nullable()->after('masterlist_import_id')->index();
            $table->string('masterlist_campus', 80)->nullable()->after('masterlist_school_year')->index();
            $table->string('masterlist_grade_level', 80)->nullable()->after('masterlist_campus')->index();
            $table->string('masterlist_section', 120)->nullable()->after('masterlist_grade_level')->index();
            $table->string('gender', 20)->nullable()->after('masterlist_section');
            $table->boolean('masterlist_active')->default(false)->after('gender')->index();
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropForeign(['masterlist_import_id']);
            $table->dropColumn(['masterlist_import_id', 'masterlist_school_year', 'masterlist_campus', 'masterlist_grade_level', 'masterlist_section', 'gender', 'masterlist_active']);
        });
        Schema::dropIfExists('student_masterlist_imports');
    }
};
