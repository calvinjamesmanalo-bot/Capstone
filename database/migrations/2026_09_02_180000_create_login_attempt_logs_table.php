<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('login_attempt_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('attempted_identifier');
            $table->string('account_type', 30)->nullable();
            $table->string('ip_address', 45);
            $table->string('user_agent', 1000)->nullable();
            $table->string('failure_reason', 60);
            $table->boolean('anomaly_detected')->default(false);
            $table->timestamps();

            $table->index(['attempted_identifier', 'created_at']);
            $table->index(['ip_address', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('login_attempt_logs');
    }
};
