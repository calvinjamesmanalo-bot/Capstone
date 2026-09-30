<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('document_authenticities', function (Blueprint $table): void {
            $table->dateTime('issued_at')->change();
        });

        // Repair records whose issue time was changed by MariaDB's implicit
        // ON UPDATE behavior. The HMAC-protected payload is the authoritative
        // original value and cannot be altered without invalidating its signature.
        DB::table('document_authenticities')
            ->select(['id', 'signed_payload'])
            ->orderBy('id')
            ->chunkById(200, function ($documents): void {
                foreach ($documents as $document) {
                    $claims = json_decode((string) $document->signed_payload, true);
                    if (! is_array($claims) || ! is_string($claims['issued_at'] ?? null)) {
                        continue;
                    }

                    try {
                        $issuedAt = Carbon::parse($claims['issued_at'])->utc()->format('Y-m-d H:i:s');
                    } catch (Throwable) {
                        continue;
                    }

                    DB::table('document_authenticities')->where('id', $document->id)
                        ->update(['issued_at' => $issuedAt]);
                }
            });
    }

    public function down(): void
    {
        Schema::table('document_authenticities', function (Blueprint $table): void {
            $table->timestamp('issued_at')->change();
        });
    }
};
