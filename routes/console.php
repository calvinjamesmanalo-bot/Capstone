<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('documents:generate-signing-certificate {--force}', function () {
    $certificatePath = (string) config('pdf_signing.certificate_path');
    $privateKeyPath = (string) config('pdf_signing.private_key_path');
    $password = (string) config('pdf_signing.private_key_password');

    if (! $this->option('force') && (is_file($certificatePath) || is_file($privateKeyPath))) {
        $this->error('Signing files already exist. Use --force only when intentionally rotating the certificate.');

        return self::FAILURE;
    }

    File::ensureDirectoryExists(dirname($certificatePath), 0700, true);
    File::ensureDirectoryExists(dirname($privateKeyPath), 0700, true);

    $opensslOptions = [
        'private_key_bits' => 3072,
        'private_key_type' => OPENSSL_KEYTYPE_RSA,
        'digest_alg' => 'sha256',
    ];
    if (filled(config('pdf_signing.openssl_config_path'))) {
        $opensslOptions['config'] = (string) config('pdf_signing.openssl_config_path');
    }

    $key = openssl_pkey_new($opensslOptions);
    if ($key === false) {
        throw new RuntimeException('OpenSSL could not generate the private key.');
    }

    $issuer = (string) config('document_verification.issuer');
    $csr = openssl_csr_new([
        'commonName' => (string) config('pdf_signing.signer_name'),
        'organizationName' => $issuer,
        'organizationalUnitName' => 'Document Issuing System',
    ], $key, $opensslOptions);
    $certificate = $csr === false ? false : openssl_csr_sign($csr, null, $key, 3650, $opensslOptions);

    if ($certificate === false
        || ! openssl_pkey_export($key, $privateKeyPem, $password, $opensslOptions)
        || ! openssl_x509_export($certificate, $certificatePem)) {
        throw new RuntimeException('OpenSSL could not export the signing certificate.');
    }

    File::put($privateKeyPath, $privateKeyPem);
    File::put($certificatePath, $certificatePem);
    @chmod($privateKeyPath, 0600);
    @chmod($certificatePath, 0644);

    $this->info('Development X.509 certificate created in protected private storage.');
    $this->line('Certificate SHA-256: '.strtoupper(openssl_x509_fingerprint($certificate, 'sha256')));
    $this->warn('Back up the key securely. Do not commit it, expose it through public/, or rotate it without an issuance-key migration plan.');

    return self::SUCCESS;
})->purpose('Generate a self-signed development certificate for official PDF signing');
