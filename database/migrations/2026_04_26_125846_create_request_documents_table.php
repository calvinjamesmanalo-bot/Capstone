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
        Schema::create('request_documents', function (Blueprint $table) {
            $table->id();
            $table->string('student_number');
            $table->string('document_type');
            $table->string('status')->default('pending'); // pending, processing, for_approval, approved
            $table->text('remarks')->nullable();
            $table->timestamps();

            $table->foreign('student_number')->references('student_number')->on('students')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('request_documents');
    }
};
