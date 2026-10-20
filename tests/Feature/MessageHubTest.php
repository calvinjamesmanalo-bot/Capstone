<?php

namespace Tests\Feature;

use App\Http\Middleware\EnsureStudentAccountIsVerified;
use App\Models\MessageThread;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MessageHubTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(EnsureStudentAccountIsVerified::class);
    }

    public function test_student_can_start_a_conversation_with_subject_and_message(): void
    {
        $student = User::factory()->create(['role' => 'student', 'student_number' => '2026-0001']);

        $response = $this->actingAs($student)->post(route('messages.store'), [
            'subject' => 'Question about Form 137',
            'message' => 'When will my document be transmitted?',
        ]);

        $thread = MessageThread::first();
        $response->assertRedirect(route('messages.index', ['thread' => $thread]));
        $this->assertDatabaseHas('message_threads', ['student_id' => $student->id, 'subject' => 'Question about Form 137']);
        $this->assertDatabaseHas('message_entries', ['thread_id' => $thread->id, 'sender_id' => $student->id]);
    }

    public function test_registrar_can_view_and_reply_to_student_thread(): void
    {
        $student = User::factory()->create(['role' => 'student']);
        $registrar = User::factory()->create(['role' => 'registrar']);
        $thread = MessageThread::create(['student_id' => $student->id, 'subject' => 'Diploma inquiry', 'last_message_at' => now()]);
        $thread->messages()->create(['sender_id' => $student->id, 'body' => 'Is it ready?']);

        $this->actingAs($registrar)->get(route('messages.index', ['thread' => $thread]))
            ->assertOk()->assertSee('Diploma inquiry')->assertSee('Is it ready?');

        $this->actingAs($registrar)->post(route('messages.reply', $thread), ['message' => 'We are checking it now.'])
            ->assertRedirect(route('messages.index', ['thread' => $thread]));
        $this->assertDatabaseHas('message_entries', ['thread_id' => $thread->id, 'sender_id' => $registrar->id, 'body' => 'We are checking it now.']);
    }

    public function test_student_cannot_open_or_reply_to_another_students_thread(): void
    {
        $owner = User::factory()->create(['role' => 'student']);
        $intruder = User::factory()->create(['role' => 'student']);
        $thread = MessageThread::create(['student_id' => $owner->id, 'subject' => 'Private', 'last_message_at' => now()]);
        $thread->messages()->create(['sender_id' => $owner->id, 'body' => 'Confidential question']);

        $this->actingAs($intruder)->get(route('messages.index', ['thread' => $thread]))->assertForbidden();
        $this->actingAs($intruder)->post(route('messages.reply', $thread), ['message' => 'Unauthorized'])->assertForbidden();
        $this->assertDatabaseMissing('message_entries', ['body' => 'Unauthorized']);
    }

    public function test_only_students_can_start_threads_and_only_student_or_registrar_can_use_hub(): void
    {
        $registrar = User::factory()->create(['role' => 'registrar']);
        $recordsOfficer = User::factory()->create(['role' => 'records_officer']);

        $this->actingAs($registrar)->post(route('messages.store'), ['subject' => 'No', 'message' => 'No'])->assertForbidden();
        $this->actingAs($recordsOfficer)->get(route('messages.index'))->assertForbidden();
    }

    public function test_opening_thread_marks_only_incoming_messages_as_read(): void
    {
        $student = User::factory()->create(['role' => 'student']);
        $registrar = User::factory()->create(['role' => 'registrar']);
        $thread = MessageThread::create(['student_id' => $student->id, 'subject' => 'Status', 'last_message_at' => now()]);
        $studentMessage = $thread->messages()->create(['sender_id' => $student->id, 'body' => 'Hello']);
        $registrarMessage = $thread->messages()->create(['sender_id' => $registrar->id, 'body' => 'Hi']);

        $this->actingAs($student)->get(route('messages.index', ['thread' => $thread]))->assertOk();

        $this->assertNull($studentMessage->fresh()->read_at);
        $this->assertNotNull($registrarMessage->fresh()->read_at);
    }
}
