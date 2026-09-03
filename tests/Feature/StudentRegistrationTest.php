<?php

namespace Tests\Feature;

use App\Models\Student;
use App\Models\StudentAccountActivation;
use App\Models\User;
use App\Notifications\VerifyStudentAccountCode;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class StudentRegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_login_page_links_to_student_registration(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee('Create Student Account')
            ->assertSee(route('student.registration.request'));

        $this->get(route('password.request'))
            ->assertOk()
            ->assertDontSee('Create account')
            ->assertDontSee(route('student.registration.request'));

        $this->get(route('student.registration.request'))
            ->assertOk()
            ->assertSee('Student number')
            ->assertSee('Full name')
            ->assertSee('Official Gmail address')
            ->assertSee('Password')
            ->assertDontSee('Forgot password?');
    }

    public function test_valid_registration_details_send_a_hashed_six_digit_code(): void
    {
        Notification::fake();

        $response = $this->post(route('student.registration.store'), $this->registrationData())
            ->assertRedirect()
            ->assertSessionHas('status', $this->genericStatus());

        $code = $this->verificationCodeFromNotification();
        $activation = StudentAccountActivation::firstOrFail();

        $this->assertMatchesRegularExpression('/^\d{6}$/', $code);
        $this->assertNotSame($code, $activation->verification_code_hash);
        $this->assertNotSame('Strong Student!2026', $activation->password_hash);
        $this->assertTrue(Hash::check('Strong Student!2026', $activation->password_hash));
        $this->assertSame('argon2id', password_get_info($activation->password_hash)['algoName']);
        $response->assertRedirectContains('/create-student-account/verify/');
        $this->assertDatabaseCount('students', 0);
        $this->assertDatabaseCount('users', 0);
    }

    public function test_name_order_and_punctuation_are_normalized_against_the_roster(): void
    {
        Notification::fake();
        config(['security.student_registration.require_roster' => true]);
        $this->officialStudent();

        $this->post(route('student.registration.store'), $this->registrationData([
            'name' => 'JUAN SANTOS DELA CRUZ',
        ]))->assertSessionHas('status');

        Notification::assertSentOnDemand(VerifyStudentAccountCode::class);
    }

    public function test_registration_does_not_require_an_existing_roster_when_disabled(): void
    {
        Notification::fake();

        $this->post(route('student.registration.store'), $this->registrationData())
            ->assertSessionHas('status', $this->genericStatus());

        $this->assertDatabaseCount('student_account_activations', 1);
        Notification::assertSentOnDemand(VerifyStudentAccountCode::class);
    }

    public function test_roster_mode_rejects_a_mismatched_name_or_gmail(): void
    {
        Notification::fake();
        config(['security.student_registration.require_roster' => true]);
        $this->officialStudent();

        $this->post(route('student.registration.store'), $this->registrationData([
            'name' => 'Different Person',
        ]))->assertSessionHas('status', $this->genericStatus());
        $this->post(route('student.registration.store'), $this->registrationData([
            'email' => 'attacker@gmail.com',
        ]))->assertSessionHas('status', $this->genericStatus());

        $this->assertDatabaseCount('student_account_activations', 0);
        Notification::assertNothingSent();
    }

    public function test_registration_requires_a_gmail_address(): void
    {
        $this->post(route('student.registration.store'), $this->registrationData([
            'email' => 'student@fiat.edu.ph',
        ]))->assertSessionHasErrors([
            'email' => 'Use the official Gmail address registered in the student roster.',
        ]);
    }

    public function test_correct_code_creates_a_verified_active_student_account(): void
    {
        Notification::fake();
        $response = $this->post(route('student.registration.store'), $this->registrationData());
        $token = basename((string) parse_url($response->headers->get('Location'), PHP_URL_PATH));
        $code = $this->verificationCodeFromNotification();

        $this->get(route('student.registration.code', ['token' => $token]))
            ->assertOk()
            ->assertSee('Check your Gmail');

        $this->post(route('student.registration.verify'), [
            'token' => $token,
            'code' => $code,
        ])->assertRedirect(route('login'))
            ->assertSessionHas('status', 'Your student account is verified and active. You can now sign in.');

        $student = Student::findOrFail('2026-0001');
        $user = User::where('student_number', $student->student_number)->firstOrFail();
        $this->assertSame($student->name, $user->name);
        $this->assertSame($student->official_email, $user->email);
        $this->assertSame('student', $user->role);
        $this->assertTrue($user->hasVerifiedEmail());
        $this->assertTrue(Hash::check('Strong Student!2026', $user->password));
        $this->assertSame('argon2id', password_get_info($user->password)['algoName']);
        $this->assertNotNull(StudentAccountActivation::firstOrFail()->used_at);
    }

    public function test_code_is_locked_after_five_incorrect_attempts(): void
    {
        Notification::fake();
        $response = $this->post(route('student.registration.store'), $this->registrationData());
        $token = basename((string) parse_url($response->headers->get('Location'), PHP_URL_PATH));
        $correctCode = $this->verificationCodeFromNotification();

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->post(route('student.registration.verify'), [
                'token' => $token,
                'code' => '000000',
            ])->assertSessionHasErrors('code');
        }

        $this->assertSame(5, StudentAccountActivation::firstOrFail()->verification_attempts);
        $this->post(route('student.registration.verify'), [
            'token' => $token,
            'code' => $correctCode,
        ])->assertSessionHasErrors('code');
        $this->assertDatabaseCount('users', 0);
    }

    public function test_expired_code_is_rejected(): void
    {
        Notification::fake();
        $response = $this->post(route('student.registration.store'), $this->registrationData());
        $token = basename((string) parse_url($response->headers->get('Location'), PHP_URL_PATH));
        $code = $this->verificationCodeFromNotification();
        StudentAccountActivation::query()->update(['expires_at' => now()->subMinute()]);

        $this->post(route('student.registration.verify'), [
            'token' => $token,
            'code' => $code,
        ])->assertSessionHasErrors('code');

        $this->assertDatabaseCount('users', 0);
    }

    public function test_registration_password_must_meet_the_security_policy(): void
    {
        $this->post(route('student.registration.store'), $this->registrationData([
            'password' => 'short',
            'password_confirmation' => 'short',
        ]))->assertSessionHasErrors([
            'password' => 'The password is too short. Use at least 12 characters.',
        ]);
    }

    public function test_existing_active_account_cannot_register_again(): void
    {
        Notification::fake();
        $student = $this->officialStudent();
        User::factory()->create([
            'student_number' => $student->student_number,
            'email' => $student->official_email,
            'role' => 'student',
        ]);

        $this->post(route('student.registration.store'), $this->registrationData())
            ->assertSessionHas('status', $this->genericStatus());

        $this->assertDatabaseCount('student_account_activations', 0);
        Notification::assertNothingSent();
    }

    public function test_forgot_password_works_after_code_verification(): void
    {
        Notification::fake();
        $response = $this->post(route('student.registration.store'), $this->registrationData());
        $token = basename((string) parse_url($response->headers->get('Location'), PHP_URL_PATH));
        $code = $this->verificationCodeFromNotification();

        $this->post(route('student.registration.verify'), [
            'token' => $token,
            'code' => $code,
        ]);

        Notification::fake();
        $this->post(route('password.email'), ['email' => 'student@gmail.com'])
            ->assertSessionHas('status');

        $user = User::where('email', 'student@gmail.com')->firstOrFail();
        Notification::assertSentTo($user, ResetPassword::class);
    }

    private function officialStudent(): Student
    {
        return Student::create([
            'student_number' => '2026-0001',
            'name' => 'Dela Cruz, Santos, Juan,',
            'official_email' => 'student@gmail.com',
        ]);
    }

    private function registrationData(array $overrides = []): array
    {
        return array_merge([
            'student_number' => '2026-0001',
            'name' => 'Juan Santos Dela Cruz',
            'email' => 'STUDENT@GMAIL.COM',
            'password' => 'Strong Student!2026',
            'password_confirmation' => 'Strong Student!2026',
        ], $overrides);
    }

    private function verificationCodeFromNotification(): string
    {
        $verificationCode = '';

        Notification::assertSentOnDemand(
            VerifyStudentAccountCode::class,
            function (VerifyStudentAccountCode $notification, array $channels, AnonymousNotifiable $notifiable) use (&$verificationCode): bool {
                $verificationCode = $notification->verificationCode;

                return $notifiable->routes['mail'] === 'student@gmail.com';
            }
        );

        return $verificationCode;
    }

    private function genericStatus(): string
    {
        return 'If the details match an eligible official student record, a verification code has been sent to the Gmail address.';
    }
}
