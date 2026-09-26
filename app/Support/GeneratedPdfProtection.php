<?php

namespace App\Support;

use App\Models\DocumentAuthenticity;
use Illuminate\Support\Facades\Storage;

class GeneratedPdfProtection
{
    public function __construct(
        private readonly PdfDigitalSignatureService $signatures,
        private readonly DocumentQrCode $qrCodes,
    ) {}

    public function protect(DocumentAuthenticity $document, string $unsignedPdf, string $filename): string
    {
        $signed = $this->signatures->sign($unsignedPdf, $document->control_number);
        $storagePath = 'review-documents/'.$document->issued_at->format('Y').'/'.$document->control_number.'.pdf';
        Storage::disk('local')->put($storagePath, $signed['bytes']);
        $artifact = $this->qrCodes->registerArtifact($document, $signed['bytes'], $filename, 'application/pdf');
        $artifact->update([
            'storage_disk' => 'local',
            'storage_path' => $storagePath,
            'is_pdf_signed' => true,
        ]);

        $document->update([
            'issued_by' => auth()->id(),
            'pdf_signature_status' => 'signed',
            'pdf_signed_at' => $signed['signed_at'],
            'pdf_signer_name' => $signed['signer_name'],
            'pdf_certificate_fingerprint' => $signed['certificate_fingerprint'],
        ]);

        record_log('Document Signed', 'Document Generation', "Embedded X.509 signature in {$document->control_number} for registrar review");

        return $signed['bytes'];
    }
}
