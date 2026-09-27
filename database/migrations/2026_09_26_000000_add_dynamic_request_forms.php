<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('request_types', function (Blueprint $table) {
            $table->id();
            $table->string('name', 160)->unique();
            $table->text('description')->nullable();
            $table->text('instructions')->nullable();
            $table->decimal('fee', 10, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->json('fields');
            $table->unsignedInteger('version')->default(1);
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::table('request_documents', function (Blueprint $table) {
            $table->foreignId('request_type_id')->nullable()->constrained('request_types')->restrictOnDelete();
            $table->json('form_snapshot')->nullable();
            $table->json('dynamic_values')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('request_documents', function (Blueprint $table) {
            $table->dropForeign(['request_type_id']);
            $table->dropColumn(['request_type_id', 'form_snapshot', 'dynamic_values']);
        });
        Schema::dropIfExists('request_types');
    }
};
