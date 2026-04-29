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
        Schema::create('form138_uploads', function (Blueprint $table) {
            $table->id();
            $table->string('student_number');
            $table->string('school_year');
            $table->string('file_path');
            $table->string('original_filename');
            $table->timestamps();

            $table->foreign('student_number')->references('student_number')->on('students')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('form138_uploads');
    }
};
