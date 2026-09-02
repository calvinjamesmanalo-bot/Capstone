<?php

namespace App\Support;

use RuntimeException;
use setasign\Fpdi\Tcpdf\Fpdi;

class PdfDigitalSignatureService
{
    public function sign(string $unsignedPdf, string $documentId): array
    {
        $this->configureTcpdfCache();
        [$certificatePath, $privateKeyPath, $password] = $this->credentials();
        $certificate = file_get_contents($certificatePath);
        $privateKey = openssl_pkey_get_private(file_get_contents($privateKeyPath), $password);

        if ($certificate === false || $privateKey === false) {
            throw new RuntimeException('The configured PDF signing certificate or private key could not be loaded.');
        }

        $x509 = openssl_x509_read($certificate);
        if ($x509 === false || ! openssl_x509_check_private_key($x509, $privateKey)) {
            throw new RuntimeException('The PDF signing private key does not match the configured X.509 certificate.');
        }

        $certificateDetails = openssl_x509_parse($x509);
        $now = time();
        if (($certificateDetails['validFrom_time_t'] ?? 0) > $now || ($certificateDetails['validTo_time_t'] ?? 0) < $now) {
            throw new RuntimeException('The configured PDF signing certificate is not currently valid.');
        }

        $sourcePath = tempnam(sys_get_temp_dir(), 'reghub-unsigned-');
        if ($sourcePath === false || file_put_contents($sourcePath, $unsignedPdf) === false) {
            throw new RuntimeException('Unable to prepare the unsigned PDF for signing.');
        }

        try {
            $pdf = new Fpdi;
            $pdf->setPrintHeader(false);
            $pdf->setPrintFooter(false);
            $pdf->SetAutoPageBreak(false, 0);
            $pageCount = $pdf->setSourceFile($sourcePath);

            for ($page = 1; $page <= $pageCount; $page++) {
                $template = $pdf->importPage($page);
                $size = $pdf->getTemplateSize($template);
                $pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
                $pdf->useTemplate($template);
            }

            $pdf->setSignature(
                'file://'.realpath($certificatePath),
                'file://'.realpath($privateKeyPath),
                $password,
                '',
                2,
                [
                    'Name' => $this->signerName(),
                    'Location' => (string) config('pdf_signing.location'),
                    'Reason' => (string) config('pdf_signing.reason'),
                    'ContactInfo' => (string) config('app.url'),
                ]
            );
            $pdf->setTitle($documentId);
            $pdf->setCreator(config('app.name'));
            $signedPdf = $pdf->Output($documentId.'.pdf', 'S');
        } finally {
            @unlink($sourcePath);
        }

        if (! is_string($signedPdf) || ! str_contains($signedPdf, '/ByteRange') || ! str_contains($signedPdf, '/Contents')) {
            throw new RuntimeException('PDF signing did not produce an embedded cryptographic signature.');
        }

        return [
            'bytes' => $signedPdf,
            'signed_at' => now(),
            'signer_name' => $this->signerName(),
            'certificate_fingerprint' => strtoupper(openssl_x509_fingerprint($x509, 'sha256')),
        ];
    }

    public function assertConfigured(): void
    {
        $this->credentials();
    }

    private function credentials(): array
    {
        $certificatePath = (string) config('pdf_signing.certificate_path');
        $privateKeyPath = (string) config('pdf_signing.private_key_path');
        $password = (string) config('pdf_signing.private_key_password');

        if (! is_file($certificatePath) || ! is_readable($certificatePath)) {
            throw new RuntimeException('PDF signing certificate is not configured or readable. Set PDF_SIGN_CERT_PATH.');
        }
        if (! is_file($privateKeyPath) || ! is_readable($privateKeyPath)) {
            throw new RuntimeException('PDF signing private key is not configured or readable. Set PDF_SIGN_KEY_PATH.');
        }

        return [$certificatePath, $privateKeyPath, $password];
    }

    private function signerName(): string
    {
        return (string) config('pdf_signing.signer_name');
    }

    private function configureTcpdfCache(): void
    {
        $cachePath = storage_path('framework/cache/tcpdf');
        if (! is_dir($cachePath) && ! mkdir($cachePath, 0700, true) && ! is_dir($cachePath)) {
            throw new RuntimeException('Unable to create the private TCPDF signing cache directory.');
        }

        if (! defined('K_PATH_CACHE')) {
            define('K_PATH_CACHE', rtrim($cachePath, '/\\').DIRECTORY_SEPARATOR);
        }
    }
}
