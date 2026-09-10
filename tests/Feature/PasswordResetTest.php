<?php

namespace Tests\Feature;

use App\Models\Student;
use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_page_links_to_the_password_reset_request_form(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee('Forgot password?')
            ->assertSee(route('password.request'));

        $this->get(route('password.request'))
            ->assertOk()
            ->assertSee('Send reset link');
    }

    public function test_reset_request_sends_a_link_for_an_existing_account(): void
    {
        Notification::fake();
        $user = $this->createVerifiedStudent();

        $this->post(route('password.email'), ['email' => $user->email])
            ->assertSessionHas('status', 'If an account exists for that email address, a password reset link has been sent.');

        Notification::assertSentTo($user, ResetPassword::class);
    }

    public function test_unknown_and_known_accounts_receive_the_same_reset_response(): void
    {
        Notification::fake();
        $user = $this->createVerifiedStudent();

        $known = $this->post(route('password.email'), ['email' => $user->email]);
        $unknown = $this->post(route('password.email'), ['email' => 'unknown@example.com']);

        $this->assertSame(
            $known->getSession()->get('status'),
            $unknown->getSession()->get('status'),
        );
    }

    public function test_reset_confirmation_explains_why_an_email_might_not_arrive_without_revealing_the_account(): void
    {
        $this->post(route('password.email'), ['email' => 'unknown@example.com'])
            ->assertSessionHas('status');

        $this->get(route('password.request'))
            ->assertOk()
            ->assertSee('Didn’t receive a reset email?')
            ->assertSee('may not be registered')
            ->assertSee('this page cannot confirm which condition applies')
            ->assertDontSee('Account does not exist');
    }

    public function test_a_valid_token_resets_the_password_and_invalidates_sessions(): void
    {
        Notification::fake();
        $user = $this->createVerifiedStudent();
        DB::table('sessions')->insert([
            'id' => 'existing-session',
            'user_id' => $user->id,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'PHPUnit',
            'payload' => 'test',
            'last_activity' => now()->timestamp,
        ]);

        $this->post(route('password.email'), ['email' => $user->email]);

        $token = null;
        Notification::assertSentTo(
            $user,
            ResetPassword::class,
            function (ResetPassword $notification) use (&$token): bool {
                $token = $notification->token;

                return true;
            }
        );

        $this->post(route('password.update'), [
            'token' => $token,
            'email' => $user->email,
            'password' => 'Strong Reset!2026',
            'password_confirmation' => 'Strong Reset!2026',
        ])->assertRedirect(route('login'))
            ->assertSessionHas('status', 'Your password has been reset. You can now sign in.');

        $this->assertTrue(Hash::check('Strong Reset!2026', $user->fresh()->password));
        $this->assertDatabaseMissing('sessions', ['id' => 'existing-session']);

        $this->post(route('password.update'), [
            'token' => $token,
            'email' => $user->email,
            'password' => 'Another Strong!2026',
            'password_confirmation' => 'Another Strong!2026',
        ])->assertSessionHasErrors('email');
    }

    public function test_new_password_must_be_at_least_twelve_characters(): void
    {
        $this->post(route('password.update'), [
            'token' => 'invalid-token',
            'email' => 'student@example.com',
            'password' => 'short',
            'password_confirmation' => 'short',
        ])->assertSessionHasErrors([
            'password' => 'The password is too short. Use at least 12 characters.',
        ]);
    }

    public function test_weak_password_receives_a_specific_notice(): void
    {
        $this->post(route('password.update'), [
            'token' => 'invalid-token',
            'email' => 'student@example.com',
            'password' => 'alllowercasepassword1!',
            'password_confirmation' => 'alllowercasepassword1!',
        ])->assertSessionHasErrors([
            'password' => 'The password is too weak. Include uppercase and lowercase letters.',
        ]);
    }

    public function test_reset_form_shows_live_password_requirements(): void
    {
        $this->get(route('password.reset', [
            'token' => 'example-token',
            'email' => 'student@example.com',
        ]))->assertOk()
            ->assertSee('Password must meet all requirements below.')
            ->assertSee('At least 12 characters')
            ->assertSee('Uppercase and lowercase letters')
            ->assertSee('At least one number')
            ->assertSee('At least one symbol');
    }

    public function test_unverified_or_roster_mismatched_students_do_not_receive_a_reset_link(): void
    {
        Notification::fake();
        $student = Student::create([
            'student_number' => '2026-0002',
            'name' => 'Unverified Student',
            'official_email' => 'official@gmail.com',
        ]);
        $unverified = User::factory()->unverified()->create([
            'role' => 'student',
            'student_number' => $student->student_number,
            'email' => $student->official_email,
        ]);
        $mismatched = User::factory()->create([
            'role' => 'student',
            'student_number' => $student->student_number,
            'email' => 'different@gmail.com',
        ]);

        $this->post(route('password.email'), ['email' => $unverified->email])
            ->assertSessionHas('status');
        $this->post(route('password.email'), ['email' => $mismatched->email])
            ->assertSessionHas('status');

        Notification::assertNothingSent();
    }

    private function createVerifiedStudent(): User
    {
        $student = Student::create([
            'student_number' => '2026-0001',
            'name' => 'Verified Student',
            'official_email' => 'verified.student@gmail.com',
        ]);

        return User::factory()->create([
            'role' => 'student',
            'student_number' => $student->student_number,
            'email' => $student->official_email,
        ]);
    }
}
