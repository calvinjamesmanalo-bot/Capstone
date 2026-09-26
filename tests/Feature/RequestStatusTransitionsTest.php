<?php

namespace Tests\Feature;

use App\Models\RequestDocument;
use App\Models\Student;
use App\Models\User;
use App\Support\DocumentQrCode;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RequestStatusTransitionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_cannot_skip_required_status_steps(): void
    {
        $request = $this->documentRequest();

        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->post(route('requests.update-status', $request->id), ['status' => 'completed'])
            ->assertSessionHasErrors('status');

        $this->assertSame('pending', $request->fresh()->status);
        $this->assertSame(1, $request->statusHistories()->count());
    }

    public function test_start_processing_opens_the_auto_filled_school_form_preview_instead_of_the_maker(): void
    {
        $request = $this->documentRequest();
        $request->update([
            'document_type' => 'Form 137',
            'school_level' => 'elementary',
        ]);

        $response = $this->actingAs(User::factory()->create(['role' => 'records_officer']))
            ->from(route('requests.index'))
            ->post(route('requests.update-status', $request->id), ['status' => 'processing']);

        $response->assertRedirect(route('school-forms.f137.preview', [
            'student' => $request->student_number,
            'request_id' => $request->id,
        ]));
        $this->assertSame('processing', $request->fresh()->status);
    }

    public function test_records_officer_cannot_approve_release_even_with_a_forged_request(): void
    {
        $request = $this->documentRequest('processed', true);

        $this->actingAs(User::factory()->create(['role' => 'records_officer']))
            ->post(route('requests.update-status', $request->id), ['status' => 'ready_to_release'])
            ->assertSessionHasErrors('status');

        $this->assertSame('processed', $request->fresh()->status);
    }

    public function test_request_cannot_be_forwarded_before_a_protected_document_is_generated(): void
    {
        $request = $this->documentRequest('processing');

        $this->actingAs(User::factory()->create(['role' => 'records_officer']))
            ->post(route('requests.update-status', $request->id), ['status' => 'processed'])
            ->assertSessionHasErrors([
                'status' => 'Generate and download the protected document before forwarding it to the registrar.',
            ]);

        $this->assertSame('processing', $request->fresh()->status);
    }

    public function test_prepared_document_can_be_forwarded_to_the_registrar(): void
    {
        $request = $this->documentRequest('processing');
        $officer = User::factory()->create(['role' => 'records_officer']);
        $this->actingAs($officer);
        $qr = app(DocumentQrCode::class)->make('Certificate of Enrollment', 'Transition Student', [
            'request_id' => $request->id,
            'holder_identifier' => $request->student_number,
        ]);
        $qr['document']->update(['pdf_signature_status' => 'signed']);
        app(DocumentQrCode::class)
            ->registerArtifact($qr['document'], '%PDF-1.4 generated test document', 'prepared-document.pdf', 'application/pdf')
            ->update([
                'storage_disk' => 'local',
                'storage_path' => 'review-documents/test/prepared-document.pdf',
                'is_pdf_signed' => true,
            ]);

        $this->post(route('requests.update-status', $request->id), ['status' => 'processed'])
            ->assertRedirect();

        $this->assertSame('processed', $request->fresh()->status);
    }

    public function test_registrar_approval_does_not_require_accounting_controls(): void
    {
        $request = $this->documentRequest('processed');
        $this->actingAs(User::factory()->create(['role' => 'registrar']));

        $this->post(route('requests.update-status', $request->id), ['status' => 'ready_to_release'])
            ->assertRedirect();

        $this->assertSame('ready_to_release', $request->fresh()->status);
    }

    public function test_rejection_requires_reason_and_terminal_states_cannot_reopen(): void
    {
        $request = $this->documentRequest();
        $this->actingAs(User::factory()->create(['role' => 'admin']));

        $this->post(route('requests.update-status', $request->id), ['status' => 'rejected'])
            ->assertSessionHasErrors('remarks');

        $this->post(route('requests.update-status', $request->id), [
            'status' => 'rejected',
            'remarks' => 'Incomplete application',
        ])->assertRedirect();

        $this->post(route('requests.update-status', $request->id), ['status' => 'processing'])
            ->assertSessionHasErrors('status');

        $this->assertSame('rejected', $request->fresh()->status);
        $this->assertSame(2, $request->statusHistories()->count());
    }

    private function documentRequest(string $status = 'pending', bool $paid = false): RequestDocument
    {
        Student::create(['student_number' => '2026-2000', 'name' => 'Transition Student']);

        return RequestDocument::create([
            'ticket_number' => 'REQ-2026-TRANSITION',
            'student_number' => '2026-2000',
            'document_type' => 'Certificate of Enrollment',
            'status' => $status,
            'payment_confirmed' => $paid,
            'clearance_status' => 'cleared',
        ]);
    }
}
