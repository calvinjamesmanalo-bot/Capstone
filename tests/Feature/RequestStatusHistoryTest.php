<?php

namespace Tests\Feature;

use App\Models\RequestDocument;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RequestStatusHistoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_request_has_initial_status_and_a_staff_change_records_actor_and_remarks(): void
    {
        $request = $this->documentRequest();
        $admin = User::factory()->create(['role' => 'admin']);

        $this->assertDatabaseHas('request_status_histories', [
            'request_document_id' => $request->id,
            'from_status' => null,
            'to_status' => 'pending',
        ]);

        $this->actingAs($admin)
            ->post(route('requests.update-status', $request->id), [
                'status' => 'processing',
                'remarks' => 'Internal records review',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('request_status_histories', [
            'request_document_id' => $request->id,
            'from_status' => 'pending',
            'to_status' => 'processing',
            'remarks' => 'Internal records review',
            'changed_by' => $admin->id,
            'is_internal' => true,
        ]);
        $this->assertSame(2, $request->statusHistories()->count());
    }

    public function test_repeating_the_same_status_does_not_create_duplicate_history(): void
    {
        $request = $this->documentRequest();

        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->post(route('requests.update-status', $request->id), [
                'status' => 'pending',
                'remarks' => 'Updated note only',
            ])
            ->assertRedirect();

        $this->assertSame(1, $request->statusHistories()->count());
        $this->assertSame('Updated note only', $request->fresh()->remarks);
    }

    public function test_student_sees_only_own_timeline_without_internal_remarks(): void
    {
        $student = $this->studentUser('2026-1001', 'first@example.com');
        $other = $this->studentUser('2026-1002', 'second@example.com');
        $request = $this->documentRequest($student->student_number);
        $otherRequest = $this->documentRequest($other->student_number);

        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->post(route('requests.update-status', $request->id), [
                'status' => 'processing',
                'remarks' => 'Internal staff-only review note',
            ])
            ->assertRedirect();

        $this->actingAs($student)
            ->get(route('student.my-requests'))
            ->assertOk()
            ->assertSee('Status timeline')
            ->assertSee('Processing')
            ->assertSee($request->ticket_number)
            ->assertDontSee('Internal staff-only review note')
            ->assertDontSee($otherRequest->ticket_number);

        $this->actingAs($student)->get(route('requests.index'))->assertForbidden();
    }

    private function studentUser(string $number, string $email): User
    {
        Student::create([
            'student_number' => $number,
            'name' => 'History Test Student',
            'official_email' => $email,
        ]);

        return User::factory()->create([
            'role' => 'student',
            'student_number' => $number,
            'email' => $email,
        ]);
    }

    private function documentRequest(string $number = '2026-1000'): RequestDocument
    {
        Student::firstOrCreate(
            ['student_number' => $number],
            ['name' => 'History Test Student']
        );

        return RequestDocument::create([
            'ticket_number' => 'REQ-2026-'.strtoupper(bin2hex(random_bytes(3))),
            'student_number' => $number,
            'document_type' => 'Certificate of Enrollment',
            'status' => 'pending',
        ]);
    }
}
