<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\RequestDocument;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PaymentReceiptSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_receipts_are_stored_privately_with_random_names(): void
    {
        Storage::fake('local');
        Storage::fake('public');
        $student = $this->student('2026-1001');

        $this->actingAs($student)->post(route('student.request.store'), [
            'document_type' => 'Certificate of Enrollment',
            'delivery_method' => 'pickup',
            'payment_method' => 'cash',
            'transcript_receipt' => UploadedFile::fake()->image('my receipt.jpg'),
        ])->assertSessionHasNoErrors();

        $documentRequest = RequestDocument::firstOrFail();

        $this->assertSame('local', $documentRequest->payment_proof_disk);
        $this->assertSame('my receipt.jpg', $documentRequest->payment_proof_original_name);
        $this->assertStringNotContainsString('my receipt', $documentRequest->payment_proof_path);
        Storage::disk('local')->assertExists($documentRequest->payment_proof_path);
        Storage::disk('public')->assertMissing($documentRequest->payment_proof_path);
        $this->assertDatabaseHas('activity_logs', [
            'action' => 'Uploaded Payment Receipt',
            'module' => 'File Security',
            'status' => 'success',
        ]);
    }

    public function test_form_137_requires_and_stores_the_requested_school_level(): void
    {
        Storage::fake('local');
        $student = $this->student('2026-1370');

        $this->actingAs($student)
            ->get(route('student.request'))
            ->assertOk()
            ->assertSee('Kinder and Elementary')
            ->assertSee('Junior High School (JHS)')
            ->assertSee('Senior High School (SHS)');

        $this->actingAs($student)->post(route('student.request.store'), [
            'document_type' => 'Form 137',
            'delivery_method' => 'pickup',
            'payment_method' => 'cash',
            'transcript_receipt' => UploadedFile::fake()->image('clearance.jpg'),
        ])->assertSessionHasErrors('school_level');

        $this->actingAs($student)->post(route('student.request.store'), [
            'document_type' => 'Form 137',
            'school_level' => 'shs',
            'delivery_method' => 'pickup',
            'payment_method' => 'cash',
            'transcript_receipt' => UploadedFile::fake()->image('clearance.jpg'),
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('request_documents', [
            'student_number' => '2026-1370',
            'document_type' => 'Form 137',
            'school_level' => 'shs',
        ]);
    }

    public function test_only_owner_and_authorized_staff_can_view_a_receipt(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('transcript_receipts/private.pdf', 'receipt');
        $owner = $this->student('2026-1002');
        $otherStudent = $this->student('2026-1003');
        $documentRequest = $this->documentRequest($owner->student_number);

        $this->get(route('requests.receipt', $documentRequest))->assertRedirect(route('login'));
        $this->actingAs($otherStudent)->get(route('requests.receipt', $documentRequest))->assertForbidden();
        $this->actingAs($owner)->get(route('requests.receipt', $documentRequest))
            ->assertOk()
            ->assertHeader('Cache-Control', 'max-age=0, no-store, private')
            ->assertHeader('X-Content-Type-Options', 'nosniff');

        foreach (['admin', 'registrar', 'records_officer'] as $role) {
            $this->actingAs(User::factory()->create(['role' => $role]))
                ->get(route('requests.receipt', $documentRequest))
                ->assertOk();
        }

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'Receipt Access Denied',
            'module' => 'File Security',
            'status' => 'denied',
        ]);
        $this->assertSame(4, ActivityLog::where('action', 'Viewed Payment Receipt')->count());
    }

    public function test_missing_receipt_file_returns_not_found(): void
    {
        Storage::fake('local');
        $owner = $this->student('2026-1004');
        $documentRequest = $this->documentRequest($owner->student_number);

        $this->actingAs($owner)->get(route('requests.receipt', $documentRequest))->assertNotFound();
        $this->assertDatabaseHas('activity_logs', [
            'action' => 'Receipt File Missing',
            'module' => 'File Security',
            'status' => 'missing',
        ]);
    }

    public function test_receipt_renders_request_details_and_print_controls_without_private_metadata(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('transcript_receipts/private-form-138.pdf', 'receipt');
        $owner = $this->student('2026-1010');
        $documentRequest = RequestDocument::create([
            'ticket_number' => 'REQ-PRINT-1010',
            'student_number' => $owner->student_number,
            'document_type' => 'Form 138',
            'school_year' => '2025-2026',
            'document_price' => 100,
            'status' => 'ready_to_release',
            'delivery_method' => 'pickup',
            'payment_method' => 'gcash',
            'payment_confirmed' => true,
            'clearance_status' => 'cleared',
            'payment_proof_path' => 'transcript_receipts/private-form-138.pdf',
            'payment_proof_disk' => 'local',
            'payment_proof_original_name' => 'accounting-receipt.pdf',
            'payment_proof_mime_type' => 'application/pdf',
            'payment_proof_sha256' => str_repeat('a', 64),
        ]);

        $this->actingAs($owner)->get(route('requests.receipt', $documentRequest))
            ->assertOk()
            ->assertSee('Fiat Lux Academe')
            ->assertSee('REQ-PRINT-1010')
            ->assertSee('Receipt Test Student')
            ->assertSee('2026-1010')
            ->assertSee('Form 138')
            ->assertSee('2025-2026')
            ->assertSee('₱100.00')
            ->assertSee('GCash')
            ->assertSee('Confirmed')
            ->assertSee('Pickup at Registrar’s Office')
            ->assertSee('Ready for Release')
            ->assertSee('Print Receipt')
            ->assertSee('onclick="window.print()"', false)
            ->assertSee('@page { size: A4 portrait;', false)
            ->assertSee('bg-indigo-50', false)
            ->assertSee('text-indigo-800', false)
            ->assertDontSee('transcript_receipts/private-form-138.pdf')
            ->assertDontSee('accounting-receipt.pdf')
            ->assertDontSee(str_repeat('a', 64));
    }

    public function test_enabled_student_can_submit_one_duplicate_active_request_and_bypass_is_consumed(): void
    {
        Storage::fake('local');
        $student = $this->student('2026-1005');
        $this->documentRequest($student->student_number);
        $student->update(['can_bypass_request_limit' => true]);

        $this->actingAs($student)->post(route('student.request.store'), [
            'document_type' => 'Certificate of Enrollment',
            'delivery_method' => 'pickup',
            'payment_method' => 'cash',
            'transcript_receipt' => UploadedFile::fake()->image('clearance.jpg'),
        ])->assertSessionHasNoErrors()
            ->assertSessionHas('success');

        $this->assertSame(2, RequestDocument::where('student_number', $student->student_number)->count());
        $this->assertFalse($student->fresh()->can_bypass_request_limit);
    }

    private function student(string $studentNumber): User
    {
        Student::create([
            'student_number' => $studentNumber,
            'name' => 'Receipt Test Student',
            'official_email' => $studentNumber.'@example.test',
        ]);

        return User::factory()->create([
            'name' => 'Receipt Test Student',
            'email' => $studentNumber.'@example.test',
            'email_verified_at' => now(),
            'role' => 'student',
            'student_number' => $studentNumber,
        ]);
    }

    private function documentRequest(string $studentNumber): RequestDocument
    {
        return RequestDocument::create([
            'student_number' => $studentNumber,
            'document_type' => 'Certificate of Enrollment',
            'status' => 'pending',
            'clearance_status' => 'cleared',
            'payment_proof_path' => 'transcript_receipts/private.pdf',
            'payment_proof_disk' => 'local',
            'payment_proof_original_name' => 'receipt.pdf',
        ]);
    }
}
