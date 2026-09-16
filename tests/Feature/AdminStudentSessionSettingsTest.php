<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminStudentSessionSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_configure_the_student_idle_timeout(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->post(route('settings.update'), $this->settingsPayload(25))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('settings', [
            'key' => 'student_idle_timeout_minutes',
            'value' => '25',
            'group' => 'security',
        ]);
    }

    public function test_student_pages_use_the_timeout_set_by_the_admin(): void
    {
        Setting::create([
            'key' => 'student_idle_timeout_minutes',
            'value' => '25',
            'group' => 'security',
        ]);
        $student = $this->verifiedStudent();

        $this->actingAs($student)
            ->get(route('student.request'))
            ->assertOk()
            ->assertSee('const idleLimit = 1500000;', false)
            ->assertSee('Are you still there?');
    }

    public function test_admin_sidebar_keeps_core_tools_and_hides_document_maker_shortcuts(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('User Management')
            ->assertSee('Active Requests')
            ->assertSee('Data Analytics')
            ->assertSee('Audit &amp; Security Logs', false)
            ->assertSee('System Settings')
            ->assertDontSee('Academic Records')
            ->assertDontSee('Grade Sheet Upload')
            ->assertDontSee('Form 137 Maker')
            ->assertDontSee('Form 138 Maker')
            ->assertDontSee('Certification Maker')
            ->assertDontSee('Good Moral Maker');
    }

    public function test_non_admin_cannot_change_the_student_idle_timeout(): void
    {
        $registrar = User::factory()->create(['role' => 'registrar']);

        $this->actingAs($registrar)
            ->post(route('settings.update'), $this->settingsPayload(5))
            ->assertForbidden();

        $this->assertDatabaseMissing('settings', [
            'key' => 'student_idle_timeout_minutes',
        ]);
    }

    private function verifiedStudent(): User
    {
        Student::create([
            'student_number' => '2026-9901',
            'name' => 'Session Test Student',
            'official_email' => 'session.student@example.com',
        ]);

        return User::factory()->create([
            'name' => 'Session Test Student',
            'email' => 'session.student@example.com',
            'email_verified_at' => now(),
            'role' => 'student',
            'student_number' => '2026-9901',
        ]);
    }

    private function settingsPayload(int $timeout): array
    {
        return [
            'student_idle_timeout_minutes' => $timeout,
            'price_certificate_enrollment' => 100,
            'price_certificate_completion' => 120,
            'price_good_moral' => 100,
            'price_certificate_recognition' => 120,
            'price_diploma' => 150,
        ];
    }
}
