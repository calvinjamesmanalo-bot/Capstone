<?php

namespace Tests\Feature;

use App\Models\RequestDocument;
use App\Models\Student;
use App\Models\User;
use App\Notifications\RequestStatusEmail;
use App\Support\RequestNotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class RequestNotificationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_status_change_creates_private_database_notification_without_remarks(): void
    {
        $student = $this->studentUser();
        $document = $this->document();
        $document->update(['status' => 'processing', 'remarks' => 'Internal office note']);

        app(RequestNotificationService::class)->statusChanged($document);

        $this->assertSame(1, $student->notifications()->count());
        $data = $student->notifications()->first()->data;
        $this->assertSame('processing', $data['status']);
        $this->assertArrayNotHasKey('remarks', $data);
        $this->assertStringNotContainsString('Internal office note', json_encode($data));
    }

    public function test_controller_notifies_only_when_status_actually_changes(): void
    {
        $student = $this->studentUser();
        $document = $this->document();
        $this->actingAs(User::factory()->create(['role' => 'admin']));

        $this->post(route('requests.update-status', $document->id), ['status' => 'processing'])
            ->assertRedirect();
        $this->post(route('requests.update-status', $document->id), ['status' => 'processing'])
            ->assertRedirect();

        $this->assertSame(1, $student->notifications()->count());
    }

    public function test_student_can_only_mark_their_own_notification_as_read(): void
    {
        $student = $this->studentUser();
        Student::create(['student_number' => '2026-3001', 'name' => 'Other Student', 'official_email' => 'other@example.test']);
        $other = User::factory()->create(['role' => 'student', 'student_number' => '2026-3001', 'email' => 'other@example.test']);
        $document = $this->document();
        app(RequestNotificationService::class)->statusChanged($document);
        $notification = $student->notifications()->first();

        $this->actingAs($other)
            ->post(route('student.notifications.read', $notification->id))
            ->assertNotFound();
        $this->assertNull($notification->fresh()->read_at);

        $this->actingAs($student)
            ->get(route('student.notifications.index'))
            ->assertOk()
            ->assertSee($document->ticket_number);
        $this->post(route('student.notifications.read', $notification->id))->assertRedirect();
        $this->assertNotNull($notification->fresh()->read_at);
    }

    public function test_important_status_queues_email_for_verified_student(): void
    {
        Notification::fake();
        $student = $this->studentUser();
        $document = $this->document();
        $document->status = 'ready_to_release';

        app(RequestNotificationService::class)->statusChanged($document);

        Notification::assertSentTo($student, RequestStatusEmail::class);
    }

    private function studentUser(): User
    {
        Student::create(['student_number' => '2026-3000', 'name' => 'Notification Student', 'official_email' => 'notify@example.test']);

        return User::factory()->create(['role' => 'student', 'student_number' => '2026-3000', 'email' => 'notify@example.test']);
    }

    private function document(): RequestDocument
    {
        return RequestDocument::create([
            'ticket_number' => 'REQ-2026-NOTIFICATION',
            'student_number' => '2026-3000',
            'document_type' => 'Certificate of Enrollment',
            'status' => 'pending',
        ]);
    }
}
