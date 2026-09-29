<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class AdminOperationsSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_save_operational_security_branding_and_feature_settings(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)->post(route('settings.update'), [
            'maintenance_message' => 'Scheduled maintenance',
            'login_max_attempts' => 7,
            'login_lockout_minutes' => 20,
            'portal_primary_color' => '#112233',
            'portal_accent_color' => '#AABBCC',
            'portal_footer_text' => 'Custom footer',
            'document_requests_enabled' => 1,
            'grade_uploads_enabled' => 0,
        ])->assertRedirect()->assertSessionHasNoErrors();

        $this->assertDatabaseHas('settings', ['key' => 'login_max_attempts', 'value' => '7']);
        $this->assertDatabaseHas('settings', ['key' => 'portal_footer_text', 'value' => 'Custom footer']);
        $this->assertDatabaseHas('settings', ['key' => 'document_requests_enabled', 'value' => '1']);
        $this->assertDatabaseHas('settings', ['key' => 'grade_uploads_enabled', 'value' => '0']);
    }

    public function test_disabled_document_requests_are_enforced_and_admin_settings_remain_available(): void
    {
        Setting::create(['key' => 'document_requests_enabled', 'value' => '0', 'group' => 'features']);
        Student::create(['student_number' => 'FEATURE-001', 'name' => 'Feature Student', 'official_email' => 'feature@example.test']);
        $student = User::factory()->create(['role' => 'student', 'student_number' => 'FEATURE-001', 'email' => 'feature@example.test', 'email_verified_at' => now()]);
        $this->actingAs($student)->get(route('student.request'))->assertStatus(503);
        $this->actingAs(User::factory()->create(['role' => 'admin']))->get(route('settings.index'))->assertOk();
    }

    public function test_admin_can_send_configured_test_email_but_registrar_cannot(): void
    {
        Mail::fake();
        Config::set('mail.mailers.smtp.username', 'configured@example.test');
        Config::set('mail.mailers.smtp.password', 'app-password');
        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->post(route('settings.test-email'), ['test_email' => 'admin@example.test'])
            ->assertRedirect()->assertSessionHas('success');
        $this->actingAs(User::factory()->create(['role' => 'registrar']))
            ->post(route('settings.test-email'), ['test_email' => 'admin@example.test'])->assertForbidden();
    }

    public function test_placeholder_smtp_credentials_show_a_validation_error_instead_of_crashing(): void
    {
        Config::set('mail.default', 'smtp');
        Config::set('mail.mailers.smtp.username', 'your-sender@gmail.com');
        Config::set('mail.mailers.smtp.password', '');

        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->from(route('settings.index'))
            ->post(route('settings.test-email'), ['test_email' => 'admin@example.test'])
            ->assertRedirect(route('settings.index'))
            ->assertSessionHasErrors('test_email');
    }
}
