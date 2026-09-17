<?php

namespace Tests\Feature;

use App\Models\RequestDocument;
use App\Models\Student;
use App\Models\User;
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

    public function test_records_officer_cannot_approve_release_even_with_a_forged_request(): void
    {
        $request = $this->documentRequest('processed', true);

        $this->actingAs(User::factory()->create(['role' => 'records_officer']))
            ->post(route('requests.update-status', $request->id), ['status' => 'ready_to_release'])
            ->assertSessionHasErrors('status');

        $this->assertSame('processed', $request->fresh()->status);
    }

    public function test_release_requires_confirmed_payment_and_clearance(): void
    {
        $request = $this->documentRequest('processed');
        $this->actingAs(User::factory()->create(['role' => 'registrar']));

        $this->post(route('requests.update-status', $request->id), ['status' => 'ready_to_release'])
            ->assertSessionHasErrors('status');

        $request->update(['payment_confirmed' => true, 'clearance_status' => 'has_balance']);
        $this->post(route('requests.update-status', $request->id), ['status' => 'ready_to_release'])
            ->assertSessionHasErrors('status');

        $request->update(['clearance_status' => 'cleared']);
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
