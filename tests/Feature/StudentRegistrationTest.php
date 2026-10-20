<?php

namespace Tests\Feature;

use App\Models\Student;
use App\Models\StudentAccountActivation;
use App\Models\User;
use App\Notifications\StudentIdentityApproved;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class StudentRegistrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        Notification::fake();
        config(['services.turnstile.enabled' => false]);
    }

    public function test_registration_collects_identity_photos_without_requesting_a_password_yet(): void
    {
        $this->get(route('student.registration.request'))->assertOk()
            ->assertSee('Photo of School ID')->assertSee('Selfie holding your School ID')
            ->assertSee('Middle name')->assertSee('(optional)')
            ->assertDontSee('name="password"', false);
    }

    public function test_optional_middle_name_does_not_change_first_and_last_name_matching(): void
    {
        $this->officialStudent();
        $data = $this->proofData();
        $data['middle_name'] = 'A different optional middle name';

        $this->post(route('student.registration.lookup'), $data)
            ->assertRedirectContains('/create-student-account/status/');
    }

    public function test_matching_student_waits_for_admin_review_before_any_email(): void
    {
        $student = $this->officialStudent();
        $response = $this->post(route('student.registration.lookup'), $this->proofData());
        $response->assertRedirectContains('/create-student-account/status/');
        $student->refresh();
        $this->assertSame('pending', $student->account_verification_status);
        $this->assertNotNull($student->account_verification_token_hash);
        Storage::disk('local')->assertExists($student->school_id_path);
        Storage::disk('local')->assertExists($student->school_id_selfie_path);
        Notification::assertNothingSent();
        $this->get($response->headers->get('Location'))->assertOk()
            ->assertSee('Wait for the Admin or Registrar to verify your account')
            ->assertSee('No verification code is required')
            ->assertDontSee('Copy status link');
    }

    public function test_mismatched_identity_is_rejected_before_files_are_stored(): void
    {
        $this->officialStudent();
        $data = $this->proofData();
        $data['first_name'] = 'Different';
        $this->post(route('student.registration.lookup'), $data)->assertSessionHasErrors('student_number');
        $this->assertSame([], Storage::disk('local')->allFiles('student-identity-proofs'));
    }

    public function test_admin_approval_emails_private_continue_link(): void
    {
        [$student, $token] = $this->pendingVerification();
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)->post(route('account-verifications.approve', $student))->assertSessionHas('success');
        $student->refresh();
        $this->assertSame('approved', $student->account_verification_status);
        $this->assertNotSame(hash('sha256', $token), $student->account_verification_token_hash);
        Notification::assertSentOnDemand(StudentIdentityApproved::class);
    }

    public function test_approved_email_link_allows_student_to_set_password_and_create_account(): void
    {
        [$student, $token] = $this->pendingVerification();
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)->post(route('account-verifications.approve', $student))->assertSessionHas('success');
        auth()->logout();
        $continueUrl = '';
        Notification::assertSentOnDemand(
            StudentIdentityApproved::class,
            function (StudentIdentityApproved $notification) use (&$continueUrl): bool {
                $continueUrl = $notification->continueUrl;
                return true;
            }
        );
        $approvalToken = basename((string) parse_url($continueUrl, PHP_URL_PATH));
        $this->get(route('student.registration.continue', ['token' => $approvalToken]))->assertRedirect(route('student.registration.request'));
        $this->post(route('student.registration.store'), [
            'password' => 'Strong Student!2026',
            'password_confirmation' => 'Strong Student!2026',
        ])->assertRedirect(route('login'));
        $user = User::where('student_number', $student->student_number)->firstOrFail();
        $this->assertSame('student@gmail.com', $user->email);
        $this->assertTrue(Hash::check('Strong Student!2026', $user->password));
        $student->refresh();
        $this->assertSame('completed', $student->account_verification_status);
        $this->assertNull($student->account_verification_token_hash);
        $this->assertNull($student->school_id_path);
        $this->assertDatabaseMissing('student_account_activations', ['student_number' => $student->student_number]);
    }

    public function test_registration_page_hides_capacity_and_blocks_processing_at_limit(): void
    {
        config(['security.student_registration.daily_account_limit' => 1]);
        User::factory()->create(['role' => 'student', 'created_at' => now()]);

        $this->get(route('student.registration.request'))->assertOk()
            ->assertDontSee('Daily account capacity')->assertDontSee('1 / 1')
            ->assertSee('Account creation is unavailable for today')
            ->assertDontSee('Submit for account verification');

        $this->post(route('student.registration.lookup'), [])
            ->assertRedirect(route('student.registration.request'))
            ->assertSessionHasErrors('student_number');
    }

    public function test_rejected_student_sees_reason_and_can_submit_again(): void
    {
        [$student, $token] = $this->pendingVerification();
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)->post(route('account-verifications.reject', $student), ['reason' => 'The School ID photo is blurry.'])->assertSessionHas('success');
        auth()->logout();
        $this->get(route('student.registration.status', ['token' => $token]))->assertOk()
            ->assertSee('Verification rejected')->assertSee('The School ID photo is blurry.')->assertSee('Submit new photos');
        $student->refresh();
        $this->assertNull($student->school_id_path);
        $this->assertNull($student->school_id_selfie_path);
    }

    private function officialStudent(): Student
    {
        return Student::create(['student_number' => '2026-0001', 'name' => 'Dela Cruz, Santos, Juan,', 'masterlist_active' => true]);
    }

    private function proofData(): array
    {
        return [
            'student_number' => '2026-0001', 'first_name' => 'Juan', 'last_name' => 'Dela Cruz',
            'email' => 'student@gmail.com', 'school_id' => UploadedFile::fake()->image('school-id.jpg'),
            'school_id_selfie' => UploadedFile::fake()->image('selfie.jpg'),
        ];
    }

    private function pendingVerification(): array
    {
        $student = $this->officialStudent();
        $response = $this->post(route('student.registration.lookup'), $this->proofData());
        $token = basename((string) parse_url($response->headers->get('Location'), PHP_URL_PATH));

        return [$student->fresh(), $token];
    }
}
