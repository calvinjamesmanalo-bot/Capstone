<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_account_activations', function (Blueprint $table) {
            $table->id();
            $table->string('student_number')->index();
            $table->string('email')->index();
            // Only a one-way digest is stored. The email contains the raw token.
            $table->char('token_hash', 64)->unique();
            $table->timestamp('expires_at');
            $table->timestamp('used_at')->nullable();
            $table->timestamps();

            $table->foreign('student_number')
                ->references('student_number')
                ->on('students')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_account_activations');
    }
};
