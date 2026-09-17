<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('request_status_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('request_document_id')->constrained()->cascadeOnDelete();
            $table->string('from_status')->nullable();
            $table->string('to_status');
            $table->text('remarks')->nullable();
            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('is_internal')->default(false);
            $table->timestamps();

            $table->index(['request_document_id', 'created_at'], 'request_status_history_timeline_index');
        });

        DB::table('request_documents')
            ->orderBy('id')
            ->chunkById(200, function ($requests): void {
                $rows = $requests->map(fn ($request): array => [
                    'request_document_id' => $request->id,
                    'from_status' => null,
                    'to_status' => $request->status,
                    'remarks' => null,
                    'changed_by' => null,
                    'is_internal' => false,
                    // Previous transitions cannot be reconstructed. Record only
                    // the status known when this migration introduced tracking.
                    'created_at' => now(),
                    'updated_at' => now(),
                ])->all();

                if ($rows !== []) {
                    DB::table('request_status_histories')->insert($rows);
                }
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('request_status_histories');
    }
};
