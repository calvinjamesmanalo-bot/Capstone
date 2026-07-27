<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->string('lrn', 20)->unique();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('student_enrollments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->string('school_year', 9);
            $table->string('level', 30);
            $table->string('section', 60);
            $table->timestamps();
            $table->unique(['student_id', 'school_year']);
        });

        Schema::create('student_grades', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_enrollment_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('grading_period');
            $table->string('learning_area', 80);
            $table->decimal('grade', 5, 2)->nullable();
            $table->timestamps();
            $table->unique(['student_enrollment_id', 'grading_period', 'learning_area'], 'student_grade_unique');
        });

        Schema::create('student_attendance', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_enrollment_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('grading_period');
            $table->string('month', 12);
            $table->unsignedSmallInteger('school_days')->nullable();
            $table->unsignedSmallInteger('days_present')->nullable();
            $table->timestamps();
            $table->unique(['student_enrollment_id', 'grading_period', 'month'], 'student_attendance_unique');
        });

        Schema::create('grade_sheet_uploads', function (Blueprint $table) {
            $table->id();
            $table->string('school_year', 9);
            $table->string('level', 30);
            $table->string('section', 60);
            $table->unsignedTinyInteger('grading_period');
            $table->string('file_type', 20);
            $table->string('original_name');
            $table->string('stored_path');
            $table->timestamps();
            $table->unique(['school_year', 'level', 'section', 'grading_period', 'file_type'], 'grade_sheet_upload_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('grade_sheet_uploads');
        Schema::dropIfExists('student_attendance');
        Schema::dropIfExists('student_grades');
        Schema::dropIfExists('student_enrollments');
        Schema::dropIfExists('students');
    }
};
