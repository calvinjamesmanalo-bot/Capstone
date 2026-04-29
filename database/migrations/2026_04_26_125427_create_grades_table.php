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
        Schema::create('grades', function (Blueprint $table) {
            $table->id();
            $table->string('student_number');
            $table->string('school_year');
            $table->string('grade_level');
            $table->string('subject');
            $table->integer('grade');
            $table->string('semester')->nullable();
            $table->timestamps();

            $table->foreign('student_number')->references('student_number')->on('students')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('grades');
    }
};
