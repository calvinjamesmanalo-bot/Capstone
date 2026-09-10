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

    public function test_administrator_can_create_a_new_student_number_and_account(): void
    {
        Notification::fake();
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->post(route('users.store'), $this->newStudentData([
            'student_number' => ' 2022-0001 ',
        ]))->assertRedirect(route('users.index'));

        $this->assertDatabaseHas('students', [
            'student_number' => '2022-0001',
            'lrn' => '123456789012',
            'official_email' => 'student@gmail.com',
        ]);
        $this->assertDatabaseHas('users', [
            'student_number' => '2022-0001',
            'email' => 'student@gmail.com',
            'role' => 'student',
        ]);
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

    public function test_administrator_can_find_and_view_a_student_lrn(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $student = $this->studentUser();

        $this->actingAs($admin)
            ->get(route('users.index', ['search' => $student->student->lrn]))
            ->assertOk()
            ->assertSee('Account exists.')
            ->assertSee('LRN: '.$student->student->lrn);
    }

    public function test_administrator_can_enable_one_re_request_from_the_student_edit_page(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $student = $this->studentUser();

        $this->actingAs($admin)
            ->get(route('users.edit', $student))
            ->assertOk()
            ->assertSee('form="toggleBypassForm"', false)
            ->assertSee('id="toggleBypassForm"', false)
            ->assertSee('Enable Re-request');

        $this->actingAs($admin)
            ->from(route('users.edit', $student))
            ->post(route('users.toggle-bypass', $student))
            ->assertRedirect(route('users.edit', $student))
            ->assertSessionHas('success');

        $this->assertTrue($student->fresh()->can_bypass_request_limit);
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
        $this->assertSame('123456789012', $student->fresh()->lrn);
        $this->assertSame('student@gmail.com', $student->fresh()->official_email);
        $this->assertNull($studentUser->email_verified_at);
        Notification::assertSentTo($studentUser, VerifyEmail::class);
    }

    public function test_administrator_can_replace_existing_roster_details_when_creating_an_account(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        Student::create([
            'student_number' => '2026-0001',
            'name' => 'Juan Dela Cruz',
            'official_email' => 'official@gmail.com',
        ]);

        $this->actingAs($admin)->post(route('users.store'), $this->newStudentData())
            ->assertRedirect(route('users.index'));

        $this->assertDatabaseHas('users', ['email' => 'student@gmail.com']);
        $this->assertDatabaseHas('students', [
            'student_number' => '2026-0001',
            'official_email' => 'student@gmail.com',
            'lrn' => '123456789012',
        ]);
    }

    public function test_administrator_can_assign_a_student_number_missing_from_the_roster_during_edit(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $studentUser = User::factory()->create([
            'role' => 'student',
            'student_number' => null,
            'name' => 'Alkuno,A,Seb Ezekiel,',
            'email' => 'old-seb@fiat.edu.ph',
        ]);

        $this->actingAs($admin)->put(route('users.update', $studentUser), [
            'last_name' => 'Alkuno',
            'first_name' => 'Seb Ezekiel',
            'middle_name' => 'A',
            'suffix' => '',
            'student_number' => '2022-0001',
            'lrn' => '424413240015',
            'email' => 'seb@fiat.edu.ph',
            'password' => '',
            'password_confirmation' => '',
            'role' => 'student',
        ])->assertRedirect(route('users.index'));

        $this->assertDatabaseHas('users', [
            'id' => $studentUser->id,
            'student_number' => '2022-0001',
            'email' => 'seb@fiat.edu.ph',
        ]);
        $this->assertDatabaseHas('students', [
            'student_number' => '2022-0001',
            'lrn' => '424413240015',
            'official_email' => 'seb@fiat.edu.ph',
        ]);
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

    public function test_unverified_student_can_skip_email_verification_for_the_testing_session(): void
    {
        $student = $this->studentUser(verified: false);

        $this->actingAs($student)->get(route('verification.notice'))
            ->assertOk()
            ->assertSee('Skip for testing');

        $this->post(route('verification.skip'))
            ->assertRedirect(route('dashboard'))
            ->assertSessionHas('student_email_verification_skipped', true);

        $this->get(route('dashboard'))->assertOk();
        $this->assertFalse($student->fresh()->hasVerifiedEmail());
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
            'lrn' => '123456789012',
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
            'lrn' => '123456789012',
            'email' => 'student@gmail.com',
            'password' => 'Strong Student!2026',
            'password_confirmation' => 'Strong Student!2026',
            'role' => 'student',
        ], $overrides);
    }
}
