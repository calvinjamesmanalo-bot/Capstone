<?php

namespace App\Support;

use App\Models\DocumentArtifact;
use App\Models\DocumentAuthenticity;
use Endroid\QrCode\Bacon\MatrixFactory;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\RoundBlockSizeMode;
use Endroid\QrCode\Writer\SvgWriter;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class DocumentQrCode
{
    /**
     * Issue (or reuse) a verifiable identity and render its signed verification URL.
     *
     * Supported context keys: request_id, holder_identifier, purpose, issued_at,
     * expires_at and fields (the important document fields protected by the hash).
     */
    public function make(string $documentType, string $subject = '', array $context = []): array
    {
        $document = $this->issue($documentType, $subject, $context);
        $verificationUrl = $this->verificationUrl($document);

        $qrCode = new QrCode(
            data: $verificationUrl,
            encoding: new Encoding('UTF-8'),
            errorCorrectionLevel: ErrorCorrectionLevel::High,
            size: 220,
            margin: 10,
            roundBlockSizeMode: RoundBlockSizeMode::Margin,
        );
        $png = $this->png($qrCode);

        return [
            // SVG avoids a hard dependency on PHP-GD in Dompdf; PNG remains available for XLSX embedding.
            'data_uri' => (new SvgWriter)->write($qrCode)->getDataUri(),
            'payload' => $document->signed_payload,
            'reference' => $document->control_number,
            'signature' => $document->signature,
            'verification_url' => $verificationUrl,
            'document' => $document,
            'png' => $png,
        ];
    }

    public function issue(string $documentType, string $subject = '', array $context = []): DocumentAuthenticity
    {
        $documentType = trim($documentType);
        $subject = trim($subject);

        if ($documentType === '' || $subject === '') {
            throw new RuntimeException('A document type and holder name are required to issue a verification QR.');
        }

        $issuedAt = Carbon::parse($context['issued_at'] ?? now())->startOfSecond();
        $expiresAt = filled($context['expires_at'] ?? null)
            ? Carbon::parse($context['expires_at'])->startOfSecond()
            : null;

        $sourceData = $this->normalize([
            'document_type' => $documentType,
            'holder_name' => $subject,
            'holder_identifier' => trim((string) ($context['holder_identifier'] ?? '')),
            'purpose' => trim((string) ($context['purpose'] ?? '')),
            'issued_on' => $issuedAt->toDateString(),
            'expires_on' => $expiresAt?->toDateString(),
            'fields' => $context['fields'] ?? [],
        ]);
        $contentHash = hash('sha256', $this->canonicalJson($sourceData));
        $requestId = filled($context['request_id'] ?? null) ? (int) $context['request_id'] : null;

        return DB::transaction(function () use ($documentType, $subject, $issuedAt, $expiresAt, $sourceData, $contentHash, $requestId) {
            $query = DocumentAuthenticity::query()
                ->where('content_hash', $contentHash)
                ->where('status', 'valid');

            if ($requestId) {
                $query->where('request_document_id', $requestId);
            } else {
                $query->whereNull('request_document_id')
                    ->where('document_type', $documentType)
                    ->where('holder_name', $subject)
                    ->whereDate('issued_at', $issuedAt->toDateString());
            }

            if ($existing = $query->latest('id')->first()) {
                return $existing;
            }

            if ($requestId) {
                DocumentAuthenticity::where('request_document_id', $requestId)
                    ->where('status', 'valid')
                    ->update(['status' => 'superseded']);
            }

            $token = (string) Str::uuid();
            $controlNumber = $this->newControlNumber($documentType, $issuedAt);
            $issuer = (string) config('document_verification.issuer', config('app.name'));

            $claims = $this->normalize([
                'version' => 1,
                'issuer' => $issuer,
                'token' => $token,
                'control_number' => $controlNumber,
                'document_type' => $documentType,
                'holder_name' => $subject,
                'holder_identifier' => $sourceData['holder_identifier'],
                'purpose' => $sourceData['purpose'],
                'issued_at' => $issuedAt->toIso8601String(),
                'expires_at' => $expiresAt?->toIso8601String(),
                'content_hash' => $contentHash,
            ]);
            $payload = $this->canonicalJson($claims);

            return DocumentAuthenticity::create([
                'request_document_id' => $requestId,
                'verification_token' => $token,
                'control_number' => $controlNumber,
                'document_type' => $documentType,
                'holder_name' => $subject,
                'holder_identifier' => $sourceData['holder_identifier'] ?: null,
                'purpose' => $sourceData['purpose'] ?: null,
                'issued_at' => $issuedAt,
                'expires_at' => $expiresAt,
                'status' => 'valid',
                'content_hash' => $contentHash,
                'source_data' => $sourceData,
                'signed_payload' => $payload,
                'signature' => $this->sign($payload),
                'signature_algorithm' => 'HMAC-SHA256',
                'issuer_name' => $issuer,
                'issuer_key_fingerprint' => $this->signingKeyFingerprint(),
            ]);
        });
    }

    public function verificationUrl(DocumentAuthenticity $document): string
    {
        return route('documents.verify', [
            'identifier' => $document->control_number,
            'signature' => $document->signature,
        ]);
    }

    public function verify(DocumentAuthenticity $document, ?string $providedSignature): array
    {
        $signatureMatchesLink = is_string($providedSignature)
            && hash_equals($document->signature, $providedSignature);
        $payloadSignatureValid = $document->signature_algorithm === 'HMAC-SHA256'
            && hash_equals($document->issuer_key_fingerprint, $this->signingKeyFingerprint())
            && hash_equals($document->signature, $this->sign($document->signed_payload));
        $contentHashValid = hash_equals(
            $document->content_hash,
            hash('sha256', $this->canonicalJson($this->normalize($document->source_data)))
        );

        $expectedClaims = $this->normalize([
            'version' => 1,
            'issuer' => $document->issuer_name,
            'token' => $document->verification_token,
            'control_number' => $document->control_number,
            'document_type' => $document->document_type,
            'holder_name' => $document->holder_name,
            'holder_identifier' => (string) ($document->holder_identifier ?? ''),
            'purpose' => (string) ($document->purpose ?? ''),
            'issued_at' => $document->issued_at->toIso8601String(),
            'expires_at' => $document->expires_at?->toIso8601String(),
            'content_hash' => $document->content_hash,
        ]);
        $claimsValid = hash_equals(
            $document->signed_payload,
            $this->canonicalJson($expectedClaims)
        );

        $expired = $document->expires_at?->isPast() ?? false;
        $requestRejected = $document->requestDocument?->status === 'rejected';
        $status = $requestRejected ? 'revoked' : $document->status;

        $result = match (true) {
            ! $signatureMatchesLink => 'invalid_link',
            ! $payloadSignatureValid || ! $contentHashValid || ! $claimsValid => 'tampered',
            $status === 'revoked' => 'revoked',
            $status === 'superseded' => 'superseded',
            $expired => 'expired',
            $status !== 'valid' => 'invalid',
            default => 'authentic',
        };

        return [
            'authentic' => $result === 'authentic',
            'result' => $result,
            'signature_valid' => $signatureMatchesLink && $payloadSignatureValid,
            'content_hash_valid' => $contentHashValid && $claimsValid,
            'expired' => $expired,
            'status' => $status,
        ];
    }

    public function registerArtifact(
        DocumentAuthenticity $document,
        string $bytes,
        string $filename,
        string $mimeType
    ): DocumentArtifact {
        $hash = hash('sha256', $bytes);
        $size = strlen($bytes);

        return $document->artifacts()->firstOrCreate(
            ['sha256_hash' => $hash],
            [
                'hash_signature' => $this->artifactSignature($document, $hash, $size, $mimeType),
                'file_size' => $size,
                'mime_type' => $mimeType,
                'original_filename' => $filename,
            ]
        );
    }

    public function registerArtifactFile(
        DocumentAuthenticity $document,
        string $path,
        string $filename,
        string $mimeType
    ): DocumentArtifact {
        $bytes = file_get_contents($path);

        if ($bytes === false) {
            throw new RuntimeException('Unable to read the finalized document for file verification.');
        }

        return $this->registerArtifact($document, $bytes, $filename, $mimeType);
    }

    public function verifyArtifact(DocumentAuthenticity $document, string $path): array
    {
        $hash = hash_file('sha256', $path);
        $size = filesize($path);

        if (! is_string($hash) || $size === false) {
            throw new RuntimeException('Unable to read the uploaded document.');
        }

        $artifact = $document->artifacts()->where('sha256_hash', $hash)->first();
        if (! $artifact) {
            return [
                'matches' => false,
                'result' => 'file_tampered',
                'sha256_hash' => $hash,
                'artifact' => null,
            ];
        }

        $signatureValid = hash_equals(
            $artifact->hash_signature,
            $this->artifactSignature($document, $hash, (int) $artifact->file_size, $artifact->mime_type)
        );

        return [
            'matches' => $signatureValid,
            'result' => $signatureValid ? 'file_match' : 'file_registry_tampered',
            'sha256_hash' => $hash,
            'artifact' => $artifact,
        ];
    }

    private function sign(string $payload): string
    {
        return $this->base64UrlEncode(hash_hmac('sha256', $payload, $this->signingKey(), true));
    }

    private function artifactSignature(DocumentAuthenticity $document, string $hash, int $size, string $mimeType): string
    {
        return $this->sign(implode('|', [
            'REGHUB-ARTIFACT-V1',
            $document->verification_token,
            $hash,
            $size,
            $mimeType,
        ]));
    }

    private function signingKey(): string
    {
        $key = (string) config('document_verification.signing_key');

        if ($key === '') {
            throw new RuntimeException('Set DOCUMENT_SIGNING_KEY or APP_KEY before issuing documents.');
        }

        if (str_starts_with($key, 'base64:')) {
            return base64_decode(substr($key, 7), true) ?: $key;
        }

        return $key;
    }

    private function signingKeyFingerprint(): string
    {
        return strtoupper(substr(hash('sha256', $this->signingKey()), 0, 32));
    }

    private function newControlNumber(string $documentType, Carbon $issuedAt): string
    {
        $type = strtolower(trim($documentType));
        $prefix = match (true) {
            str_contains($type, '138') => 'F138',
            str_contains($type, '137') => 'F137',
            str_contains($type, 'certificate') => 'CERT',
            str_contains($type, 'diploma') => 'DPL',
            default => 'FLA',
        };

        do {
            $controlNumber = sprintf('%s-%s-%s', $prefix, $issuedAt->format('Y'), strtoupper(bin2hex(random_bytes(4))));
        } while (DocumentAuthenticity::where('control_number', $controlNumber)->exists());

        return $controlNumber;
    }

    private function canonicalJson(array $data): string
    {
        return json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    }

    private function normalize(array $data): array
    {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $data[$key] = $this->normalize($value);
            }
        }

        ksort($data);

        return $data;
    }

    private function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }

    private function png(QrCode $qrCode): string
    {
        $matrix = (new MatrixFactory)->create($qrCode);
        $size = $matrix->getOuterSize();
        $blockSize = $matrix->getBlockSize();
        $margin = $matrix->getMarginLeft();
        $raw = '';

        for ($y = 0; $y < $size; $y++) {
            $raw .= "\x00";
            for ($x = 0; $x < $size; $x++) {
                $column = intdiv($x - $margin, $blockSize);
                $row = intdiv($y - $margin, $blockSize);
                $black = $x >= $margin && $y >= $margin
                    && $column >= 0 && $row >= 0
                    && $column < $matrix->getBlockCount() && $row < $matrix->getBlockCount()
                    && $matrix->getBlockValue($row, $column) === 1;
                $raw .= $black ? "\x00\x00\x00" : "\xFF\xFF\xFF";
            }
        }

        $chunk = static fn (string $type, string $data): string => pack('N', strlen($data)).$type.$data.pack('N', crc32($type.$data));

        return "\x89PNG\r\n\x1A\n"
            .$chunk('IHDR', pack('NNCCCCC', $size, $size, 8, 2, 0, 0, 0))
            .$chunk('IDAT', gzcompress($raw, 9))
            .$chunk('IEND', '');
    }
}
