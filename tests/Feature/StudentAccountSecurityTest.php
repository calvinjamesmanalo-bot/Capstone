<?php

namespace Tests\Feature;

use App\Models\Student;
use App\Models\User;
use App\Notifications\StudentEmailChanged;
use App\Notifications\VerifyStudentEmailChange;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class StudentAccountSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'cache.default' => 'array',
            'security.authentication.cache_store' => null,
            'services.turnstile.enabled' => true,
            'services.turnstile.secret_key' => 'test-secret',
            'services.turnstile.allowed_hostnames' => [],
            'services.turnstile.expected_action' => 'login',
        ]);
        Http::fake([
            'challenges.cloudflare.com/turnstile/v0/siteverify' => Http::response([
                'success' => true,
                'action' => 'login',
            ]),
        ]);
    }

    public function test_student_account_requires_an_existing_official_roster_record(): void
    {
        Notification::fake();
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->post(route('users.store'), $this->newStudentData([
            'student_number' => 'missing-student',
        ]))->assertSessionHasErrors('student_number');

        $this->assertDatabaseMissing('users', ['email' => 'student@gmail.com']);
    }

    public function test_only_administrators_can_manage_student_accounts(): void
    {
        $registrar = User::factory()->create(['role' => 'registrar']);

        $this->actingAs($registrar)->get(route('users.index'))->assertForbidden();
        $this->actingAs($registrar)->post(route('users.store'), $this->newStudentData())->assertForbidden();
    }

    public function test_administrator_can_securely_find_an_existing_account(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $student = $this->studentUser();

        $this->actingAs($admin)
            ->get(route('users.index', ['search' => strtoupper($student->email)]))
            ->assertOk()
            ->assertSee('Account exists.')
            ->assertSee($student->email)
            ->assertSee('Email verified')
            ->assertSee('Official roster matched');
    }

    public function test_administrator_account_lookup_reports_no_match_without_showing_other_accounts(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        User::factory()->create(['email' => 'unrelated@example.com', 'role' => 'registrar']);

        $this->actingAs($admin)
            ->get(route('users.index', ['search' => 'missing@example.com']))
            ->assertOk()
            ->assertSee('No account matches')
            ->assertDontSee('unrelated@example.com');
    }

    public function test_student_account_uses_the_roster_email_and_sends_verification(): void
    {
        Notification::fake();
        $admin = User::factory()->create(['role' => 'admin']);
        $student = Student::create([
            'student_number' => '2026-0001',
            'name' => 'Juan Dela Cruz',
        ]);

        $this->actingAs($admin)->post(route('users.store'), $this->newStudentData())
            ->assertRedirect(route('users.index'));

        $studentUser = User::where('student_number', $student->student_number)->firstOrFail();
        $this->assertSame('student@gmail.com', $student->fresh()->official_email);
        $this->assertNull($studentUser->email_verified_at);
        Notification::assertSentTo($studentUser, VerifyEmail::class);
    }

    public function test_an_existing_roster_email_must_match_the_new_account(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        Student::create([
            'student_number' => '2026-0001',
            'name' => 'Juan Dela Cruz',
            'official_email' => 'official@gmail.com',
        ]);

        $this->actingAs($admin)->post(route('users.store'), $this->newStudentData())
            ->assertSessionHasErrors('email');

        $this->assertDatabaseMissing('users', ['email' => 'student@gmail.com']);
    }

    public function test_unverified_student_is_sent_to_the_verification_notice(): void
    {
        Notification::fake();
        $student = $this->studentUser(verified: false);

        $this->post(route('login'), [
            'identifier' => $student->student_number,
            'password' => 'password',
            'account_type' => 'student',
            'cf-turnstile-response' => 'valid-turnstile-token',
            'website' => '',
            'form_started_at' => Crypt::encryptString((string) now()->subSeconds(3)->timestamp),
        ])->assertRedirect(route('verification.notice'));

        $this->actingAs($student)->get(route('dashboard'))
            ->assertRedirect(route('verification.notice'));
    }

    public function test_signed_verification_link_verifies_the_official_email(): void
    {
        $student = $this->studentUser(verified: false);
        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $student->id, 'hash' => sha1($student->email)]
        );

        $this->actingAs($student)->get($verificationUrl)
            ->assertRedirect(route('dashboard'));

        $this->assertTrue($student->fresh()->hasVerifiedEmail());
    }

    public function test_email_change_requires_the_current_password(): void
    {
        Notification::fake();
        $student = $this->studentUser();

        $this->actingAs($student)->post(route('student.email.request'), [
            'current_password' => 'wrong-password',
            'email' => 'new.student@gmail.com',
        ])->assertSessionHasErrors('current_password');

        $this->assertNull($student->fresh()->pending_email);
        Notification::assertNothingSent();
    }

    public function test_verified_email_change_updates_the_roster_notifies_old_email_and_logs_out_other_sessions(): void
    {
        Notification::fake();
        $student = $this->studentUser();
        DB::table('sessions')->insert([
            'id' => 'other-student-session',
            'user_id' => $student->id,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'Other browser',
            'payload' => 'test',
            'last_activity' => now()->timestamp,
        ]);

        $this->actingAs($student)->post(route('student.email.request'), [
            'current_password' => 'password',
            'email' => 'new.student@gmail.com',
        ])->assertSessionHas('status');

        $verificationUrl = null;
        Notification::assertSentOnDemand(
            VerifyStudentEmailChange::class,
            function (VerifyStudentEmailChange $notification, array $channels, AnonymousNotifiable $notifiable) use (&$verificationUrl): bool {
                $verificationUrl = $notification->verificationUrl;

                return $notifiable->routes['mail'] === 'new.student@gmail.com';
            }
        );

        $this->actingAs($student)->get($verificationUrl)
            ->assertRedirect(route('student.email.edit'))
            ->assertSessionHas('status', 'Your email address has been changed and verified.');

        $this->assertDatabaseHas('users', [
            'id' => $student->id,
            'email' => 'new.student@gmail.com',
            'pending_email' => null,
        ]);
        $this->assertDatabaseHas('students', [
            'student_number' => $student->student_number,
            'official_email' => 'new.student@gmail.com',
        ]);
        $this->assertDatabaseMissing('sessions', ['id' => 'other-student-session']);
        Notification::assertSentOnDemand(
            StudentEmailChanged::class,
            fn (StudentEmailChanged $notification, array $channels, AnonymousNotifiable $notifiable): bool => $notifiable->routes['mail'] === 'student@gmail.com'
        );
    }

    private function studentUser(bool $verified = true): User
    {
        $student = Student::create([
            'student_number' => '2026-0001',
            'name' => 'Juan Dela Cruz',
            'official_email' => 'student@gmail.com',
        ]);

        $factory = User::factory();
        if (! $verified) {
            $factory = $factory->unverified();
        }

        return $factory->create([
            'role' => 'student',
            'student_number' => $student->student_number,
            'email' => $student->official_email,
        ]);
    }

    private function newStudentData(array $overrides = []): array
    {
        return array_merge([
            'last_name' => 'Dela Cruz',
            'first_name' => 'Juan',
            'middle_name' => '',
            'suffix' => '',
            'student_number' => '2026-0001',
            'email' => 'student@gmail.com',
            'password' => 'Strong Student!2026',
            'password_confirmation' => 'Strong Student!2026',
            'role' => 'student',
        ], $overrides);
    }
}
