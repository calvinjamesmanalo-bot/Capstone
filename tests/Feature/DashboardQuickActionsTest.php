<?php

namespace Tests\Feature;

use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class DashboardQuickActionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_sees_only_admin_and_shared_staff_quick_actions(): void
    {
        $this->assertQuickActions('admin', [
            'requests.index',
            'users.index',
            'school-forms.records',
            'grade-portal.index',
            'analytics.index',
            'logs.index',
            'settings.index',
        ], [
            'student.request',
            'student.my-requests',
            'school-forms.home',
        ]);
    }

    public function test_registrar_sees_only_registrar_and_shared_staff_quick_actions(): void
    {
        $this->assertQuickActions('registrar', [
            'requests.index',
            'requests.history',
            'school-forms.records',
            'grade-portal.index',
            'certifications.index',
        ], [
            'users.index',
            'analytics.index',
            'logs.index',
            'settings.index',
            'school-forms.home',
            'student.request',
            'student.my-requests',
        ]);
    }

    public function test_records_officer_sees_only_records_and_shared_staff_quick_actions(): void
    {
        $this->assertQuickActions('records_officer', [
            'requests.index',
            'requests.history',
            'school-forms.home',
            'school-forms.records',
            'certifications.index',
        ], [
            'users.index',
            'grade-portal.index',
            'analytics.index',
            'logs.index',
            'settings.index',
            'student.request',
            'student.my-requests',
        ]);
    }

    public function test_student_sees_only_student_quick_actions(): void
    {
        $this->assertQuickActions('student', [
            'student.request',
            'student.my-requests',
        ], [
            'requests.index',
            'requests.history',
            'users.index',
            'school-forms.home',
            'school-forms.records',
            'grade-portal.index',
            'certifications.index',
            'analytics.index',
            'logs.index',
            'settings.index',
        ]);
    }

    public function test_quick_action_cards_are_keyboard_focusable_links(): void
    {
        $response = $this->actingAs($this->user('student'))->get(route('dashboard'));

        $response->assertOk()
            ->assertSee('data-quick-action="student.request"', false)
            ->assertSee('focus-visible:outline-none', false)
            ->assertSee('focus-visible:ring-4', false)
            ->assertSee('href="'.route('student.request').'"', false);
    }

    public function test_quick_action_counts_describe_what_they_count(): void
    {
        $this->actingAs($this->user('admin'))->get(route('dashboard'))
            ->assertOk()
            ->assertSee('aria-label="0 pending requests"', false)
            ->assertSee('aria-label="1 users"', false);

        $this->actingAs($this->user('student'))->get(route('dashboard'))
            ->assertOk()
            ->assertSee('aria-label="0 my requests"', false);
    }

    private function assertQuickActions(string $role, array $visible, array $hidden): void
    {
        $response = $this->actingAs($this->user($role))->get(route('dashboard'));

        $response->assertOk()->assertSee('Quick actions');

        foreach ($visible as $routeName) {
            $response->assertSee('data-quick-action="'.$routeName.'"', false)
                ->assertSee('href="'.route($routeName).'"', false);

            $route = Route::getRoutes()->getByName($routeName);
            $this->assertNotNull($route, $routeName);
            $this->assertContains('GET', $route->methods(), $routeName);
            $roleMiddleware = collect($route->gatherMiddleware())
                ->first(fn (string $middleware) => str_starts_with($middleware, 'role:'));
            $this->assertNotNull($roleMiddleware, $routeName);
            $this->assertContains($role, explode(',', substr($roleMiddleware, 5)), $routeName);
        }

        foreach ($hidden as $routeName) {
            $response->assertDontSee('data-quick-action="'.$routeName.'"', false);
        }
    }

    private function user(string $role): User
    {
        $email = $role === 'student' ? 'dashboard.student@example.test' : null;

        if ($role === 'student') {
            Student::create([
                'student_number' => 'DASHBOARD-STUDENT',
                'name' => 'Dashboard Student',
                'official_email' => $email,
            ]);
        }

        return User::factory()->create(array_filter([
            'role' => $role,
            'student_number' => $role === 'student' ? 'DASHBOARD-STUDENT' : null,
            'email' => $email,
            'email_verified_at' => now(),
        ], fn ($value) => $value !== null));
    }
}
