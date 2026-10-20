<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('request_document_transmissions', function (Blueprint $table) {
            $table->string('reference_number')->nullable()->change();
            $table->text('proof_path')->nullable()->change();
            $table->string('proof_original_name')->nullable()->change();
            $table->string('proof_mime_type')->nullable()->change();
            $table->unsignedBigInteger('proof_size')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('request_document_transmissions', function (Blueprint $table) {
            $table->string('reference_number')->nullable(false)->change();
            $table->text('proof_path')->nullable(false)->change();
            $table->string('proof_original_name')->nullable(false)->change();
            $table->string('proof_mime_type')->nullable(false)->change();
            $table->unsignedBigInteger('proof_size')->nullable(false)->change();
        });
    }
};
