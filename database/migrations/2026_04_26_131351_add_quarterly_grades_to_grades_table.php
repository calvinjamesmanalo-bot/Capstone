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
        Schema::table('grades', function (Blueprint $table) {
            $table->integer('q1')->nullable()->after('subject');
            $table->integer('q2')->nullable()->after('q1');
            $table->integer('q3')->nullable()->after('q2');
            $table->integer('q4')->nullable()->after('q3');
            $table->integer('final_grade')->nullable()->after('q4');
        });
    }

    public function down(): void
    {
        Schema::table('grades', function (Blueprint $table) {
            $table->dropColumn(['q1', 'q2', 'q3', 'q4', 'final_grade']);
        });
    }
};
