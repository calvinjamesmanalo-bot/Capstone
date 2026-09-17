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
        $this->assertSame('pending_clearance', $documentRequest->clearance_status);
        $this->assertFalse($documentRequest->payment_confirmed);
        $this->assertSame('0.00', $documentRequest->financial_balance);
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

    public function test_payment_confirmation_is_audited_and_does_not_auto_clear_accounting(): void
    {
        $owner = $this->student('2026-1009');
        $documentRequest = $this->documentRequest($owner->student_number);
        $documentRequest->update(['clearance_status' => 'has_balance', 'financial_balance' => 500]);

        $this->actingAs(User::factory()->create(['role' => 'records_officer']))
            ->post(route('requests.confirm-payment', $documentRequest->id))
            ->assertForbidden();

        $registrar = User::factory()->create(['role' => 'registrar']);
        $this->actingAs($registrar)
            ->post(route('requests.confirm-payment', $documentRequest->id))
            ->assertRedirect();

        $documentRequest->refresh();
        $this->assertTrue($documentRequest->payment_confirmed);
        $this->assertSame($registrar->id, $documentRequest->payment_confirmed_by);
        $this->assertNotNull($documentRequest->payment_confirmed_at);
        $this->assertSame('has_balance', $documentRequest->clearance_status);
        $this->assertSame('500.00', $documentRequest->financial_balance);
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
