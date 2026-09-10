<?php

namespace Tests\Feature;

use App\Models\RequestDocument;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_from_role_protected_areas(): void
    {
        foreach (['student.my-requests', 'requests.index', 'grade-portal.index', 'logs.index'] as $route) {
            $this->get(route($route))->assertRedirect(route('login'));
        }
    }

    public function test_student_pages_are_limited_to_students(): void
    {
        $student = $this->verifiedStudent();

        $this->actingAs($student)->get(route('student.my-requests'))->assertOk();

        foreach (['admin', 'registrar', 'records_officer'] as $role) {
            $this->actingAs($this->user($role))
                ->get(route('student.my-requests'))
                ->assertForbidden();
        }
    }

    public function test_students_cannot_access_or_mutate_staff_requests(): void
    {
        $student = $this->verifiedStudent();
        $documentRequest = $this->documentRequest();

        $this->actingAs($student)->get(route('requests.index'))->assertForbidden();
        $this->actingAs($student)->get(route('requests.history'))->assertForbidden();
        $this->actingAs($student)->post(route('requests.update-status', $documentRequest->id), [
            'status' => 'rejected',
            'remarks' => 'Unauthorized change',
        ])->assertForbidden();

        $this->assertSame('pending', $documentRequest->fresh()->status);
    }

    public function test_request_management_is_available_to_staff_roles(): void
    {
        foreach (['admin', 'registrar', 'records_officer'] as $role) {
            $this->actingAs($this->user($role))
                ->get(route('requests.index'))
                ->assertOk();
        }
    }

    public function test_only_admin_can_access_system_pages_and_clear_logs(): void
    {
        foreach (['student', 'registrar', 'records_officer'] as $role) {
            $user = $role === 'student' ? $this->verifiedStudent() : $this->user($role);

            $this->actingAs($user)->get(route('analytics.index'))->assertForbidden();
            $this->actingAs($user)->get(route('logs.index'))->assertForbidden();
            $this->actingAs($user)->delete(route('logs.clear'))->assertForbidden();
            $this->actingAs($user)->get(route('settings.index'))->assertForbidden();
        }

        $admin = $this->user('admin');
        $this->actingAs($admin)->get(route('analytics.index'))->assertOk();
        $this->actingAs($admin)->get(route('logs.index'))->assertOk();
        $this->actingAs($admin)->get(route('settings.index'))->assertOk();
    }

    public function test_legacy_grade_portal_is_limited_to_admin_and_registrar(): void
    {
        foreach (['admin', 'registrar'] as $role) {
            $this->actingAs($this->user($role))
                ->get(route('grade-portal.index'))
                ->assertOk();
        }

        $this->actingAs($this->user('records_officer'))
            ->get(route('grade-portal.index'))
            ->assertForbidden();
        $this->actingAs($this->verifiedStudent())
            ->get(route('grade-portal.index'))
            ->assertForbidden();
    }

    public function test_clearance_is_limited_to_admin_and_registrar(): void
    {
        $documentRequest = $this->documentRequest();

        $this->actingAs($this->user('records_officer'))
            ->post(route('requests.update-clearance', $documentRequest->id), [
                'clearance_status' => 'has_balance',
                'financial_balance' => 500,
            ])
            ->assertForbidden();

        $this->assertSame('cleared', $documentRequest->fresh()->clearance_status);

        $this->actingAs($this->user('registrar'))
            ->post(route('requests.update-clearance', $documentRequest->id), [
                'clearance_status' => 'has_balance',
                'financial_balance' => 500,
            ])
            ->assertRedirect();

        $this->assertSame('has_balance', $documentRequest->fresh()->clearance_status);
    }

    public function test_only_admin_can_clear_request_history(): void
    {
        $documentRequest = $this->documentRequest(['status' => 'completed']);

        $this->actingAs($this->user('records_officer'))
            ->delete(route('requests.history.clear'), ['action' => 'all'])
            ->assertForbidden();
        $this->assertDatabaseHas('request_documents', ['id' => $documentRequest->id]);

        $this->actingAs($this->user('admin'))
            ->delete(route('requests.history.clear'), ['action' => 'all'])
            ->assertRedirect();
        $this->assertDatabaseMissing('request_documents', ['id' => $documentRequest->id]);
    }

    private function user(string $role): User
    {
        return User::factory()->create(['role' => $role]);
    }

    private function verifiedStudent(): User
    {
        $studentNumber = '2026-'.fake()->unique()->numerify('####');
        $email = fake()->unique()->safeEmail();

        Student::create([
            'student_number' => $studentNumber,
            'name' => 'Authorization Test Student',
            'official_email' => $email,
        ]);

        return User::factory()->create([
            'name' => 'Authorization Test Student',
            'email' => $email,
            'email_verified_at' => now(),
            'role' => 'student',
            'student_number' => $studentNumber,
        ]);
    }

    private function documentRequest(array $attributes = []): RequestDocument
    {
        Student::firstOrCreate(
            ['student_number' => '2026-0001'],
            ['name' => 'Request Test Student']
        );

        return RequestDocument::create(array_merge([
            'student_number' => '2026-0001',
            'document_type' => 'Certificate of Enrollment',
            'status' => 'pending',
            'clearance_status' => 'cleared',
        ], $attributes));
    }
}
