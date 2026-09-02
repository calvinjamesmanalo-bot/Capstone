<?php

namespace App\Support;

use App\Models\DocumentAuthenticity;
use App\Models\RequestDocument;
use Closure;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

class DocumentIssuanceService
{
    public function __construct(
        private readonly DocumentQrCode $qrCodes,
        private readonly PdfDigitalSignatureService $pdfSignatures,
        private readonly DocumentBlockchainService $blockchain,
    ) {}

    /**
     * @param  Closure(array): string  $renderOfficialPdf
     */
    public function issue(
        RequestDocument $requestDocument,
        string $documentType,
        string $holderName,
        array $context,
        string $filename,
        Closure $renderOfficialPdf,
    ): DocumentAuthenticity {
        $this->pdfSignatures->assertConfigured();
        $storedPath = null;

        try {
            return DB::transaction(function () use (
                $requestDocument,
                $documentType,
                $holderName,
                $context,
                $filename,
                $renderOfficialPdf,
                &$storedPath,
            ) {
                $lockedRequest = RequestDocument::query()->lockForUpdate()->findOrFail($requestDocument->id);
                if (! in_array($lockedRequest->status, ['processing', 'processed', 'ready_to_release'], true)) {
                    throw new RuntimeException('This request must be processed before an official document can be issued.');
                }
                $existing = $lockedRequest->authenticities()
                    ->where('status', 'valid')
                    ->where('pdf_signature_status', 'signed')
                    ->whereHas('artifacts', fn ($query) => $query->where('is_official', true)->where('is_pdf_signed', true))
                    ->latest('id')
                    ->first();

                if ($existing) {
                    return $existing->load('officialArtifact');
                }

                // Older QR/hash-only records are retained but cannot represent a real signed issuance.
                $lockedRequest->authenticities()
                    ->where('status', 'valid')
                    ->update(['status' => 'superseded']);

                $previous = $lockedRequest->authenticities()
                    ->where('status', 'revoked')
                    ->latest('id')
                    ->first();

                $qr = $this->qrCodes->make($documentType, $holderName, array_merge($context, [
                    'request_id' => $lockedRequest->id,
                ]));
                $document = $qr['document'];
                $unsignedPdf = $renderOfficialPdf($qr);
                $signed = $this->pdfSignatures->sign($unsignedPdf, $document->control_number);
                $signedBytes = $signed['bytes'];
                $sha256 = hash('sha256', $signedBytes);
                $storedPath = 'issued-documents/'.$document->issued_at->format('Y').'/'.$document->control_number.'.pdf';

                if (! Storage::disk('local')->put($storedPath, $signedBytes)) {
                    throw new RuntimeException('The signed official PDF could not be stored.');
                }

                $artifact = $this->qrCodes->registerArtifact($document, $signedBytes, $filename, 'application/pdf');
                $artifact->update([
                    'storage_disk' => 'local',
                    'storage_path' => $storedPath,
                    'is_official' => true,
                    'is_pdf_signed' => true,
                ]);

                $blockchain = $this->blockchain->record($document, $sha256);
                $document->update([
                    'issued_by' => auth()->id(),
                    'pdf_signature_status' => 'signed',
                    'pdf_signed_at' => $signed['signed_at'],
                    'pdf_signer_name' => $signed['signer_name'],
                    'pdf_certificate_fingerprint' => $signed['certificate_fingerprint'],
                    'blockchain_status' => $blockchain['status'],
                    'blockchain_transaction_hash' => $blockchain['transaction_hash'],
                    'blockchain_recorded_at' => $blockchain['recorded_at'],
                    'reissued_from_id' => $previous?->id,
                ]);

                if ($previous) {
                    $previous->update(['replaced_by_id' => $document->id]);
                }

                $lockedRequest->update(['status' => 'ready_to_release']);
                record_log('Document Finalized', 'Document Issuance', "Finalized {$document->control_number} for request #{$lockedRequest->id}");
                record_log('Document Signed', 'Document Issuance', "Embedded X.509 PDF signature for {$document->control_number}");
                if ($blockchain['status'] === 'recorded') {
                    record_log('Blockchain Recorded', 'Document Issuance', "Recorded blockchain proof for {$document->control_number}");
                }
                record_log('Document Issued', 'Document Issuance', "Issued {$document->control_number}; SHA-256 {$sha256}");
                if ($previous) {
                    record_log('Document Reissued', 'Document Issuance', "Reissued {$previous->control_number} as {$document->control_number}");
                }

                return $document->fresh(['officialArtifact', 'reissuedFrom']);
            }, 3);
        } catch (Throwable $exception) {
            if ($storedPath !== null) {
                Storage::disk('local')->delete($storedPath);
            }

            throw $exception;
        }
    }
}
