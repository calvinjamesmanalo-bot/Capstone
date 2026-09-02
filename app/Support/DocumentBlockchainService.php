<?php

namespace App\Support;

use App\Models\DocumentAuthenticity;
use RuntimeException;

class DocumentBlockchainService
{
    public function record(DocumentAuthenticity $document, string $sha256): array
    {
        $driver = trim((string) config('document_verification.blockchain_driver'));

        if ($driver === '') {
            return [
                'status' => 'unavailable',
                'transaction_hash' => null,
                'recorded_at' => null,
            ];
        }

        throw new RuntimeException("Blockchain driver [{$driver}] is configured but no matching project adapter is installed.");
    }
}
