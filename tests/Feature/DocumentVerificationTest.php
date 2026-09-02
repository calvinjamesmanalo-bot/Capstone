<?php

namespace Tests\Feature;

use App\Models\DocumentAuthenticity;
use App\Models\DocumentVerificationAudit;
use App\Models\User;
use App\Support\DocumentQrCode;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class DocumentVerificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_signed_qr_opens_an_authentic_public_verification_page_and_is_audited(): void
    {
        $qr = app(DocumentQrCode::class)->make('Certificate of Enrollment', 'Juan Dela Cruz', [
            'holder_identifier' => '2026-0001',
            'purpose' => 'Scholarship',
            'issued_at' => '2026-08-16',
            'fields' => ['school_year' => '2026-2027', 'grade_level' => 'Grade 10'],
        ]);

        $response = $this->get($qr['verification_url']);

        $response->assertOk()
            ->assertSee('Issuance record is authentic')
            ->assertSee('Juan Dela Cruz')
            ->assertSee($qr['reference'])
            ->assertSee(strtoupper($qr['document']->content_hash))
            ->assertSee('Valid · HMAC-SHA256');

        $this->assertDatabaseHas('document_verification_audits', [
            'document_authenticity_id' => $qr['document']->id,
            'result' => 'authentic',
        ]);
    }

    public function test_the_same_document_content_reuses_its_control_number(): void
    {
        $context = [
            'issued_at' => '2026-08-16',
            'fields' => ['school_year' => '2026-2027'],
        ];

        $first = app(DocumentQrCode::class)->make('Form 138', 'Ana Santos', $context);
        $second = app(DocumentQrCode::class)->make('Form 138', 'Ana Santos', $context);

        $this->assertSame($first['reference'], $second['reference']);
        $this->assertSame(1, DocumentAuthenticity::count());
    }

    public function test_an_invalid_qr_signature_is_rejected_and_audited(): void
    {
        $document = app(DocumentQrCode::class)->issue('Diploma', 'Maria Reyes', [
            'issued_at' => '2026-08-16',
            'fields' => ['course' => 'Secondary Education'],
        ]);

        $response = $this->get(route('documents.verify', [
            'identifier' => $document->control_number,
            'signature' => 'changed-signature',
        ]));

        $response->assertOk()->assertSee('Invalid verification link');
        $this->assertSame('invalid_link', DocumentVerificationAudit::latest('id')->value('result'));
    }

    public function test_changed_protected_fields_are_reported_as_tampered(): void
    {
        $document = app(DocumentQrCode::class)->issue('Form 138', 'Pedro Ramos', [
            'issued_at' => '2026-08-16',
            'fields' => ['general_average' => 91],
        ]);
        $source = $document->source_data;
        $source['fields']['general_average'] = 99;
        $document->update(['source_data' => $source]);

        $response = $this->get(app(DocumentQrCode::class)->verificationUrl($document->fresh()));

        $response->assertOk()->assertSee('Verification data mismatch');
    }

    public function test_changed_display_metadata_is_reported_as_tampered(): void
    {
        $document = app(DocumentQrCode::class)->issue('Form 138', 'Original Holder', [
            'issued_at' => '2026-08-16',
        ]);
        $document->update(['holder_name' => 'Changed Holder']);

        $this->get(app(DocumentQrCode::class)->verificationUrl($document->fresh()))
            ->assertOk()
            ->assertSee('Verification data mismatch');
    }

    public function test_reissuing_revoked_content_creates_a_new_valid_record(): void
    {
        $first = app(DocumentQrCode::class)->issue('Diploma', 'Reissue Holder', [
            'issued_at' => '2026-08-16',
        ]);
        $first->update(['status' => 'revoked', 'revoked_at' => now()]);

        $second = app(DocumentQrCode::class)->issue('Diploma', 'Reissue Holder', [
            'issued_at' => '2026-08-16',
        ]);

        $this->assertNotSame($first->control_number, $second->control_number);
        $this->assertSame('valid', $second->status);
        $this->assertSame(2, DocumentAuthenticity::count());
    }

    public function test_expired_and_revoked_documents_show_a_warning(): void
    {
        $expired = app(DocumentQrCode::class)->issue('Certificate of Enrollment', 'Expired Holder', [
            'issued_at' => now()->subYear(),
            'expires_at' => now()->subDay(),
        ]);

        $this->get(app(DocumentQrCode::class)->verificationUrl($expired))
            ->assertOk()
            ->assertSee('Document has expired');

        $admin = User::factory()->create(['role' => 'admin']);
        $active = app(DocumentQrCode::class)->issue('Diploma', 'Revoked Holder');

        $this->actingAs($admin)->post(route('documents.revoke', $active), [
            'reason' => 'Replacement copy issued',
        ])->assertRedirect();

        $this->get(app(DocumentQrCode::class)->verificationUrl($active->fresh()))
            ->assertOk()
            ->assertSee('Document has been revoked')
            ->assertSee('Replacement copy issued');
    }

    public function test_a_printed_control_number_can_be_looked_up(): void
    {
        $document = app(DocumentQrCode::class)->issue('Good Moral Certificate', 'Lookup Holder');

        $this->get(route('documents.lookup', ['reference' => strtolower($document->control_number)]))
            ->assertRedirect(app(DocumentQrCode::class)->verificationUrl($document));
    }

    public function test_an_uploaded_official_file_matches_while_an_edited_copy_is_rejected(): void
    {
        $qr = app(DocumentQrCode::class);
        $document = $qr->issue('Form 138', 'File Check Holder', [
            'issued_at' => '2026-08-16',
            'fields' => ['mathematics' => 91],
        ]);

        $officialFile = UploadedFile::fake()->createWithContent(
            'Form138_File_Check_Holder.pdf',
            "%PDF-1.4\nOfficial grade: 91\n%%EOF"
        );
        $officialBytes = file_get_contents($officialFile->getRealPath());
        $qr->registerArtifact($document, $officialBytes, $officialFile->getClientOriginalName(), 'application/pdf');

        $this->post(route('documents.verify-file', ['identifier' => $document->control_number]), [
            'signature' => $document->signature,
            'document_file' => $officialFile,
        ])->assertOk()
            ->assertSee('Exact official-file match');

        $editedFile = UploadedFile::fake()->createWithContent(
            'Form138_File_Check_Holder-edited.pdf',
            "%PDF-1.4\nEdited grade: 99\n%%EOF"
        );

        $this->post(route('documents.verify-file', ['identifier' => $document->control_number]), [
            'signature' => $document->signature,
            'document_file' => $editedFile,
        ])->assertOk()
            ->assertSee('Altered or unrecognized file');

        $this->assertDatabaseHas('document_verification_audits', [
            'document_authenticity_id' => $document->id,
            'result' => 'file_match',
        ]);
        $this->assertDatabaseHas('document_verification_audits', [
            'document_authenticity_id' => $document->id,
            'result' => 'file_tampered',
        ]);
    }
}
