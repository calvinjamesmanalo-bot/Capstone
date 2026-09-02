<?php

namespace Tests\Feature;

use App\Models\DocumentAuthenticity;
use App\Models\RequestDocument;
use App\Models\Student;
use App\Models\User;
use App\Support\DocumentIssuanceService;
use Dompdf\Dompdf;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Tests\TestCase;

class OfficialDocumentIssuanceTest extends TestCase
{
    use RefreshDatabase;

    private string $certificateDirectory;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        $this->certificateDirectory = sys_get_temp_dir().DIRECTORY_SEPARATOR.'reghub-signing-'.Str::uuid();
        mkdir($this->certificateDirectory, 0700, true);
        $this->configureSigningCertificate();
    }

    protected function tearDown(): void
    {
        foreach (['certificate.crt', 'private.key'] as $file) {
            $path = $this->certificateDirectory.DIRECTORY_SEPARATOR.$file;
            if (is_file($path)) {
                unlink($path);
            }
        }
        if (is_dir($this->certificateDirectory)) {
            rmdir($this->certificateDirectory);
        }

        parent::tearDown();
    }

    public function test_finalization_embeds_a_pdf_signature_then_hashes_and_stores_the_official_file(): void
    {
        [$requestDocument, $staff] = $this->requestAndStaff();
        $this->actingAs($staff);

        $document = $this->issue($requestDocument, 'Official grade: 85');
        $artifact = $document->officialArtifact;
        $signedPdf = Storage::disk('local')->get($artifact->storage_path);

        $this->assertStringStartsWith('F138-2026-', $document->control_number);
        $this->assertSame('signed', $document->pdf_signature_status);
        $this->assertSame('unavailable', $document->blockchain_status);
        $this->assertTrue($artifact->is_official);
        $this->assertTrue($artifact->is_pdf_signed);
        $this->assertStringContainsString('/ByteRange', $signedPdf);
        $this->assertStringContainsString('/Contents', $signedPdf);
        $this->assertCryptographicallyValidPdfSignature($signedPdf);
        $tamperedPdf = $signedPdf;
        $tamperedPdf[10] = $tamperedPdf[10] === 'X' ? 'Y' : 'X';
        $this->assertCryptographicallyRejectedPdfSignature($tamperedPdf);
        $this->assertSame(hash('sha256', $signedPdf), $artifact->sha256_hash);
        $this->assertSame('ready_to_release', $requestDocument->fresh()->status);

        $response = $this->get(route('documents.download', $document));
        $response->assertOk()->assertDownload($artifact->original_filename);
    }

    public function test_repeated_finalization_is_idempotent_and_does_not_overwrite_the_official_pdf(): void
    {
        [$requestDocument, $staff] = $this->requestAndStaff();
        $this->actingAs($staff);

        $first = $this->issue($requestDocument, 'Official grade: 85');
        $firstBytes = Storage::disk('local')->get($first->officialArtifact->storage_path);
        $second = $this->issue($requestDocument->fresh(), 'Changed grade: 98');

        $this->assertSame($first->id, $second->id);
        $this->assertSame(1, DocumentAuthenticity::count());
        $this->assertSame($firstBytes, Storage::disk('local')->get($second->officialArtifact->storage_path));
    }

    public function test_revocation_then_reissue_keeps_history_and_creates_a_new_signed_document(): void
    {
        [$requestDocument, $staff] = $this->requestAndStaff();
        $this->actingAs($staff);
        $first = $this->issue($requestDocument, 'Official grade: 85');

        $this->post(route('documents.revoke', $first), ['reason' => 'Incorrect Mathematics grade'])
            ->assertRedirect();
        $second = $this->issue($requestDocument->fresh(), 'Corrected grade: 88');

        $this->assertNotSame($first->control_number, $second->control_number);
        $this->assertSame('revoked', $first->fresh()->status);
        $this->assertSame($first->id, $second->reissued_from_id);
        $this->assertSame($second->id, $first->fresh()->replaced_by_id);
        $this->assertNotSame($first->officialArtifact->sha256_hash, $second->officialArtifact->sha256_hash);
        $this->assertSame(2, DocumentAuthenticity::count());
    }

    public function test_signing_failure_does_not_create_an_issued_record(): void
    {
        [$requestDocument, $staff] = $this->requestAndStaff();
        $this->actingAs($staff);
        config(['pdf_signing.private_key_path' => $this->certificateDirectory.DIRECTORY_SEPARATOR.'missing.key']);

        try {
            $this->issue($requestDocument, 'Official grade: 85');
            $this->fail('Issuance should fail when the private key is unavailable.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('private key', $exception->getMessage());
        }

        $this->assertSame(0, DocumentAuthenticity::count());
        $this->assertSame('processing', $requestDocument->fresh()->status);
    }

    public function test_certificate_preview_is_a_draft_and_finalization_uses_the_existing_request(): void
    {
        [$requestDocument, $staff, $form] = $this->certificateRequestAndForm();
        $this->actingAs($staff)
            ->post(route('certifications.preview'), $form)
            ->assertOk()
            ->assertSee('DRAFT')
            ->assertSee('Finalize &amp; Issue', false);
        $this->assertSame(0, DocumentAuthenticity::count());

        $response = $this->post(route('certifications.finalize'), $form);
        $document = DocumentAuthenticity::with('officialArtifact')->sole();

        $response->assertRedirect(route('documents.issued', $document));
        $this->assertSame('signed', $document->pdf_signature_status);
        $this->assertTrue($document->officialArtifact->is_pdf_signed);
        $this->assertSame($requestDocument->id, $document->request_document_id);
    }

    public function test_registrar_cannot_finalize_a_certificate_on_behalf_of_the_issuer(): void
    {
        [, , $form] = $this->certificateRequestAndForm();
        $registrar = User::factory()->create(['role' => 'registrar']);

        $this->actingAs($registrar)
            ->post(route('certifications.finalize'), $form)
            ->assertForbidden();

        $this->assertSame(0, DocumentAuthenticity::count());
    }

    private function issue(RequestDocument $requestDocument, string $content): DocumentAuthenticity
    {
        return app(DocumentIssuanceService::class)->issue(
            $requestDocument,
            'Form 138',
            'Juan Dela Cruz',
            [
                'holder_identifier' => '2026-0001',
                'issued_at' => '2026-08-30',
                'fields' => ['mathematics' => $content],
            ],
            'F138-Juan-Dela-Cruz.pdf',
            function () use ($content): string {
                $pdf = new Dompdf;
                $pdf->loadHtml('<html><body><h1>Official Form 138</h1><p>'.e($content).'</p></body></html>');
                $pdf->render();

                return $pdf->output();
            },
        );
    }

    private function requestAndStaff(): array
    {
        Student::create(['student_number' => '2026-0001', 'name' => 'Juan Dela Cruz']);
        $requestDocument = RequestDocument::create([
            'student_number' => '2026-0001',
            'document_type' => 'Form 138',
            'school_year' => '2026-2027',
            'status' => 'processing',
        ]);

        return [$requestDocument, User::factory()->create(['role' => 'records_officer'])];
    }

    private function certificateRequestAndForm(): array
    {
        Student::create(['student_number' => '2026-0002', 'name' => 'Maria Santos']);
        $requestDocument = RequestDocument::create([
            'student_number' => '2026-0002',
            'document_type' => 'Certificate of Enrollment',
            'status' => 'processing',
        ]);
        $staff = User::factory()->create(['role' => 'records_officer']);
        $form = [
            'request_id' => $requestDocument->id,
            'certificate_type' => 'enrollment',
            'student_number' => '2026-0002',
            'student_name' => 'Maria Santos',
            'grade_level' => 'Grade 10',
            'section' => 'Hope',
            'school_year' => '2026-2027',
            'issue_date' => '2026-08-30',
            'expires_at' => '',
            'purpose' => 'Scholarship',
            'recognition' => '',
        ];

        return [$requestDocument, $staff, $form];
    }

    private function configureSigningCertificate(): void
    {
        $opensslOptions = [
            'private_key_bits' => 2048,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
            'digest_alg' => 'sha256',
        ];
        $xamppConfig = 'C:\\xampp\\php\\extras\\ssl\\openssl.cnf';
        if (is_file($xamppConfig)) {
            $opensslOptions['config'] = $xamppConfig;
        }

        $key = openssl_pkey_new($opensslOptions);
        $csr = openssl_csr_new(['commonName' => 'RegHub Test Signer'], $key, $opensslOptions);
        $certificate = openssl_csr_sign($csr, null, $key, 7, $opensslOptions);
        $this->assertTrue(openssl_pkey_export($key, $privateKeyPem, 'test-password', $opensslOptions));
        $this->assertTrue(openssl_x509_export($certificate, $certificatePem));

        $certificatePath = $this->certificateDirectory.DIRECTORY_SEPARATOR.'certificate.crt';
        $privateKeyPath = $this->certificateDirectory.DIRECTORY_SEPARATOR.'private.key';
        file_put_contents($certificatePath, $certificatePem);
        file_put_contents($privateKeyPath, $privateKeyPem);

        config([
            'pdf_signing.certificate_path' => $certificatePath,
            'pdf_signing.private_key_path' => $privateKeyPath,
            'pdf_signing.private_key_password' => 'test-password',
            'pdf_signing.signer_name' => 'RegHub Test Signer',
        ]);
    }

    private function assertCryptographicallyValidPdfSignature(string $pdf): void
    {
        [$exitCode, $output] = $this->pdfSignatureVerificationResult($pdf);
        $this->assertSame(0, $exitCode, $output);
    }

    private function assertCryptographicallyRejectedPdfSignature(string $pdf): void
    {
        [$exitCode] = $this->pdfSignatureVerificationResult($pdf);
        $this->assertNotSame(0, $exitCode, 'A byte-level PDF modification must invalidate the PKCS#7 signature.');
    }

    private function pdfSignatureVerificationResult(string $pdf): array
    {
        $matched = preg_match('/\/ByteRange\s*\[\s*(\d+)\s+(\d+)\s+(\d+)\s+(\d+)\s*\]/', $pdf, $range);
        $this->assertSame(1, $matched, 'The PDF must contain a valid signature byte range.');
        [, $offset1, $length1, $offset2, $length2] = array_map('intval', $range);
        $gap = substr($pdf, $offset1 + $length1, $offset2 - ($offset1 + $length1));
        $this->assertSame(1, preg_match('/<([0-9A-Fa-f]+)>/s', $gap, $contents));

        $signature = rtrim((string) hex2bin($contents[1]), "\0");
        $signedContent = substr($pdf, $offset1, $length1).substr($pdf, $offset2, $length2);
        $signaturePath = $this->certificateDirectory.DIRECTORY_SEPARATOR.'signature.der';
        $contentPath = $this->certificateDirectory.DIRECTORY_SEPARATOR.'signed-content.bin';
        file_put_contents($signaturePath, $signature);
        file_put_contents($contentPath, $signedContent);

        $candidates = PHP_OS_FAMILY === 'Windows'
            ? ['C:\\xampp\\apache\\bin\\openssl.exe', 'C:\\xampp\\php\\extras\\openssl\\openssl.exe']
            : ['/usr/bin/openssl', '/usr/local/bin/openssl'];
        $openssl = collect($candidates)->first(fn (string $path) => is_file($path));
        if ($openssl === null) {
            $this->markTestSkipped('OpenSSL CLI is unavailable for detached PKCS#7 verification.');
        }

        $nullOutput = PHP_OS_FAMILY === 'Windows' ? 'NUL' : '/dev/null';
        $process = proc_open([
            $openssl,
            'cms',
            '-verify',
            '-inform', 'DER',
            '-binary',
            '-in', $signaturePath,
            '-content', $contentPath,
            '-noverify',
            '-out', $nullOutput,
        ], [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
        $stdout = stream_get_contents($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        $exitCode = proc_close($process);
        unlink($signaturePath);
        unlink($contentPath);

        return [$exitCode, trim($stdout."\n".$stderr)];
    }
}
