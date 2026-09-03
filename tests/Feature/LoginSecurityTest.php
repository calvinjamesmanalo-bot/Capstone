<?php

namespace Tests\Feature;

use App\Models\LoginAttemptLog;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class LoginSecurityTest extends TestCase
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
            'services.turnstile.site_key' => 'test-site-key',
            'services.turnstile.allowed_hostnames' => [],
            'services.turnstile.expected_action' => 'login',
        ]);
        Http::fake([
            'challenges.cloudflare.com/turnstile/v0/siteverify' => Http::response([
                'success' => true,
                'action' => 'login',
                'hostname' => 'localhost',
            ]),
        ]);
    }

    public function test_five_failed_attempts_lock_the_account_for_fifteen_minutes(): void
    {
        $user = User::factory()->create(['email' => 'staff@example.com', 'role' => 'records_officer']);

        $this->failLoginFiveTimes($user->email);

        $this->login($user->email, 'password', 'staff')
            ->assertSessionHasErrors(['identifier' => 'Too many login attempts. Please try again after 15 minutes.']);
        $this->assertGuest();
    }

    public function test_account_lockout_progresses_from_fifteen_to_thirty_minutes(): void
    {
        $user = User::factory()->create(['email' => 'staff@example.com', 'role' => 'records_officer']);

        $this->failLoginFiveTimes($user->email);
        $this->travel(15)->minutes();
        $this->travel(1)->second();
        $this->failLoginFiveTimes($user->email);

        $this->travel(15)->minutes();
        $this->travel(1)->second();
        $this->login($user->email, 'password', 'staff')->assertSessionHasErrors('identifier');
        $this->assertGuest();

        $this->travel(15)->minutes();
        $this->login($user->email, 'password', 'staff')->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_successful_login_clears_the_account_counter(): void
    {
        $user = User::factory()->create(['email' => 'staff@example.com', 'role' => 'records_officer']);

        for ($attempt = 0; $attempt < 4; $attempt++) {
            $this->login($user->email, 'wrong-password', 'staff', "10.0.0.{$attempt}");
        }

        $this->login(strtoupper($user->email), 'password', 'staff', '10.0.1.1')
            ->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user);

        $this->post('/logout');
        $this->login($user->email, 'wrong-password', 'staff', '10.0.2.1');
        $this->login($user->email, 'wrong-password', 'staff', '10.0.2.2');
        $this->login($user->email, 'password', 'staff', '10.0.2.3')
            ->assertRedirect(route('dashboard'));
    }

    public function test_account_limit_combines_failures_from_different_ip_addresses(): void
    {
        $user = User::factory()->create(['email' => 'staff@example.com', 'role' => 'records_officer']);

        for ($attempt = 1; $attempt <= 5; $attempt++) {
            $this->login($user->email, 'wrong-password', 'staff', "10.1.0.{$attempt}");
        }

        $this->login($user->email, 'password', 'staff', '10.1.0.99')->assertSessionHasErrors('identifier');
        $this->assertGuest();
    }

    public function test_ip_limit_combines_failures_against_different_accounts(): void
    {
        $user = User::factory()->create(['email' => 'staff@example.com', 'role' => 'records_officer']);

        for ($attempt = 1; $attempt <= 5; $attempt++) {
            $this->login("UNKNOWN-{$attempt}", 'wrong-password', 'student', '10.2.0.1');
        }

        $this->login($user->email, 'password', 'staff', '10.2.0.1')->assertSessionHasErrors('identifier');
        $this->assertGuest();
    }

    public function test_failed_login_is_audited_with_required_context(): void
    {
        $this->withHeader('User-Agent', 'Security Test Browser')
            ->withServerVariables(['REMOTE_ADDR' => '10.3.0.1'])
            ->post('/login', $this->credentials('2026-9998', 'wrong-password'));

        $this->assertDatabaseHas('login_attempt_logs', [
            'attempted_identifier' => '2026-9998',
            'account_type' => 'student',
            'ip_address' => '10.3.0.1',
            'user_agent' => 'Security Test Browser',
            'failure_reason' => 'invalid_credentials',
        ]);
        $this->assertNotNull(LoginAttemptLog::firstOrFail()->created_at);
    }

    public function test_multiple_source_ips_are_marked_as_anomalous(): void
    {
        for ($attempt = 1; $attempt <= 3; $attempt++) {
            $this->login('2026-9999', 'wrong-password', 'student', "10.4.0.{$attempt}");
        }

        $this->assertDatabaseHas('login_attempt_logs', [
            'attempted_identifier' => '2026-9999',
            'ip_address' => '10.4.0.3',
            'anomaly_detected' => true,
        ]);
    }

    public function test_turnstile_is_verified_on_the_server_and_failure_is_generic(): void
    {
        config(['services.turnstile.verify_url' => 'https://turnstile.test/siteverify']);
        Http::fake([
            'turnstile.test/siteverify' => Http::response(['success' => false]),
        ]);

        $response = $this->from('/login')->post('/login', $this->credentials('2026-9997', 'wrong-password'));

        $response->assertRedirect('/login')->assertSessionHasErrors([
            'identifier' => 'Unable to sign in with the provided credentials. Please try again later.',
        ]);
        $this->assertDatabaseHas('login_attempt_logs', ['failure_reason' => 'captcha_failed']);
        Http::assertSent(fn ($request) => $request['secret'] === 'test-secret'
            && $request['response'] === 'valid-turnstile-token');
    }

    public function test_missing_turnstile_token_is_rejected_without_calling_cloudflare(): void
    {
        $payload = $this->credentials('2026-9996', 'wrong-password');
        unset($payload['cf-turnstile-response']);

        $this->post('/login', $payload)->assertSessionHasErrors('identifier');
        $this->assertDatabaseHas('login_attempt_logs', ['failure_reason' => 'captcha_failed']);
        Http::assertNothingSent();
    }

    public function test_official_local_test_token_can_omit_action(): void
    {
        config([
            'services.turnstile.using_test_keys' => true,
            'services.turnstile.verify_url' => 'https://turnstile-local.test/siteverify',
        ]);
        Http::fake([
            'turnstile-local.test/siteverify' => Http::response([
                'success' => true,
                'hostname' => 'example.com',
            ]),
        ]);

        $this->post('/login', $this->credentials('2026-9994', 'wrong-password'))
            ->assertSessionHasErrors('identifier');

        $this->assertDatabaseHas('login_attempt_logs', [
            'attempted_identifier' => '2026-9994',
            'failure_reason' => 'invalid_credentials',
        ]);
        $this->assertDatabaseMissing('login_attempt_logs', [
            'attempted_identifier' => '2026-9994',
            'failure_reason' => 'captcha_failed',
        ]);
    }

    public function test_production_turnstile_token_must_include_the_login_action(): void
    {
        config([
            'services.turnstile.using_test_keys' => false,
            'services.turnstile.verify_url' => 'https://turnstile-production.test/siteverify',
        ]);
        Http::fake([
            'turnstile-production.test/siteverify' => Http::response([
                'success' => true,
                'hostname' => 'app.example.com',
            ]),
        ]);

        $this->post('/login', $this->credentials('2026-9993', 'wrong-password'))
            ->assertSessionHasErrors('identifier');

        $this->assertDatabaseHas('login_attempt_logs', [
            'attempted_identifier' => '2026-9993',
            'failure_reason' => 'captcha_failed',
        ]);
    }

    public function test_unknown_and_known_accounts_receive_the_same_error(): void
    {
        $user = User::factory()->create([
            'email' => 'student@example.com',
            'student_number' => '2026-0100',
        ]);

        $known = $this->login($user->student_number, 'wrong-password', 'student', '10.5.0.1');
        $unknown = $this->login('2026-9995', 'wrong-password', 'student', '10.5.0.2');

        $this->assertSame(
            $known->getSession()->get('errors')->first('identifier'),
            $unknown->getSession()->get('errors')->first('identifier')
        );
    }

    public function test_student_signs_in_with_student_number_instead_of_email(): void
    {
        $student = Student::create([
            'student_number' => '2026-0123',
            'name' => 'Student User',
            'official_email' => 'student@gmail.com',
        ]);
        $user = User::factory()->create([
            'role' => 'student',
            'student_number' => $student->student_number,
            'email' => $student->official_email,
        ]);

        $this->login('2026-0123', 'password', 'student')
            ->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_admin_uses_password_and_turnstile_without_an_access_code(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'remember_token' => null]);

        $this->login($admin->email, 'password', 'admin')->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($admin);
        $this->assertNull($admin->fresh()->remember_token);
    }

    public function test_login_page_has_account_choices_and_turnstile_but_no_admin_code(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee('value="student"', false)
            ->assertSee('value="staff"', false)
            ->assertSee('value="admin"', false)
            ->assertSee('Student Number')
            ->assertSee('name="identifier"', false)
            ->assertSee('class="cf-turnstile', false)
            ->assertDontSee('test-secret', false)
            ->assertDontSee('admin_access_code', false)
            ->assertDontSee('Admin Access Code');
    }

    public function test_frontend_role_tampering_cannot_turn_a_student_into_an_admin(): void
    {
        $student = Student::create([
            'student_number' => '2026-0001',
            'name' => 'Student User',
            'official_email' => 'student@gmail.com',
        ]);
        $user = User::factory()->create([
            'role' => 'student',
            'student_number' => $student->student_number,
            'email' => $student->official_email,
        ]);

        $this->post('/login', [
            ...$this->credentials($user->email, 'password', 'admin'),
            'role' => 'admin',
        ])->assertSessionHasErrors('identifier');

        $this->assertGuest();
        $this->assertSame('student', $user->fresh()->role);
    }

    public function test_argon2id_is_used_for_new_passwords(): void
    {
        $hash = Hash::make('a secure password');

        $this->assertSame('argon2id', password_get_info($hash)['algoName']);
        $this->assertTrue(Hash::check('a secure password', $hash));
    }

    public function test_legacy_non_argon_hash_is_rejected_without_an_error_leak(): void
    {
        $user = User::factory()->create([
            'email' => 'legacy@example.com',
            'role' => 'records_officer',
        ]);
        DB::table('users')->where('id', $user->id)->update([
            'password' => password_hash('password', PASSWORD_BCRYPT, ['cost' => 12]),
        ]);

        $this->login($user->email, 'password', 'staff')->assertSessionHasErrors([
            'identifier' => 'Unable to sign in with the provided credentials. Please try again later.',
        ]);
        $this->assertGuest();
    }

    public function test_quick_access_supports_admin_only_in_local_or_testing_environments(): void
    {
        $this->get(route('login.as', 'registrar'))->assertRedirect(route('dashboard'));
        $this->assertAuthenticated();

        $this->post('/logout');
        $this->get(route('login.as', 'admin'))->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs(User::where('email', 'admin@example.com')->firstOrFail());

        $this->post('/logout');
        $this->get(route('login.as', 'super-admin'))->assertNotFound();
    }

    public function test_quick_access_and_its_admin_button_are_unavailable_in_production(): void
    {
        $this->app->detectEnvironment(fn () => 'production');

        $this->get(route('login'))->assertOk()->assertDontSee('Quick access for testing');
        $this->get(route('login.as', 'admin'))->assertNotFound();
        $this->assertGuest();
    }

    public function test_admin_create_command_stores_an_argon2id_password(): void
    {
        $this->artisan('admin:create')
            ->expectsQuestion('Administrator name', 'System Administrator')
            ->expectsQuestion('Administrator email', 'secure.admin@example.com')
            ->expectsQuestion('Administrator password (minimum 12 characters)', 'Strong Admin!2026')
            ->expectsQuestion('Confirm the administrator password', 'Strong Admin!2026')
            ->assertSuccessful();

        $admin = User::where('email', 'secure.admin@example.com')->firstOrFail();

        $this->assertSame('admin', $admin->role);
        $this->assertSame('argon2id', password_get_info($admin->password)['algoName']);
        $this->assertTrue(Hash::check('Strong Admin!2026', $admin->password));
    }

    private function login(
        string $identifier,
        string $password,
        string $accountType = 'student',
        string $ipAddress = '127.0.0.1'
    ) {
        return $this->withServerVariables(['REMOTE_ADDR' => $ipAddress])
            ->post('/login', $this->credentials($identifier, $password, $accountType));
    }

    private function credentials(
        string $identifier,
        string $password,
        string $accountType = 'student'
    ): array {
        return [
            'identifier' => $identifier,
            'password' => $password,
            'account_type' => $accountType,
            'cf-turnstile-response' => 'valid-turnstile-token',
        ];
    }

    private function failLoginFiveTimes(string $identifier): void
    {
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->login($identifier, 'wrong-password', 'staff');
        }
    }
}
