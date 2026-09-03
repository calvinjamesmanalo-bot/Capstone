<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Revoke link-based activations from the retired workflow.
        DB::table('student_account_activations')->delete();

        Schema::table('student_account_activations', function (Blueprint $table) {
            $table->char('verification_code_hash', 64)->nullable()->after('token_hash');
            $table->string('password_hash')->nullable()->after('verification_code_hash');
            $table->unsignedTinyInteger('verification_attempts')->default(0)->after('password_hash');
        });
    }

    public function down(): void
    {
        Schema::table('student_account_activations', function (Blueprint $table) {
            $table->dropColumn([
                'verification_code_hash',
                'password_hash',
                'verification_attempts',
            ]);
        });
    }
};
