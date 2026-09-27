<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\Student;
use App\Models\User;
use App\Support\DocumentQrCode;
use App\Support\SystemContent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SystemContentSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_settings_are_allowlisted_and_displayed_in_the_existing_ui(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'registrar']));
        $this->post(route('settings.update'), [...$this->payload(), 'APP_KEY' => 'forged', 'DB_PASSWORD' => 'forged', 'school_logo_path' => '../secret', '_method_extra' => 'forged'])
            ->assertSessionHasNoErrors();
        foreach (['APP_KEY', 'DB_PASSWORD', 'school_logo_path', '_method_extra'] as $key) {
            $this->assertDatabaseMissing('settings', ['key' => $key]);
        }
        foreach ($this->payload() as $key => $value) {
            $this->assertDatabaseHas('settings', ['key' => $key, 'value' => $value]);
        }
        Setting::create(['key' => 'request_instructions', 'value' => 'Legacy instructions']);
        $this->get(route('settings.index'))->assertOk()->assertSee('Example School');
        Student::create(['student_number' => '12345', 'name' => 'Student', 'official_email' => 'content@example.test']);
        $this->actingAs(User::factory()->create(['role' => 'student', 'student_number' => '12345', 'email' => 'content@example.test']))
            ->get(route('student.request'))->assertOk()->assertSee('School Contact Information')->assertSee('Example address')->assertSee('Example School')
            ->assertSee('046-123-4567')->assertSee('0917-123-4567')->assertSee('school@example.test')->assertSee('Weekdays 8 AM to 5 PM')
            ->assertDontSee('Legacy instructions')->assertDontSee('Test announcement');

    }

    public function test_only_registrars_can_save_settings(): void
    {
        foreach (['student', 'admin', 'records_officer'] as $role) {
            $this->actingAs(User::factory()->create(['role' => $role]))->post(route('settings.update'), $this->payload())->assertForbidden();
        }
        $this->assertDatabaseMissing('settings', ['key' => 'school_address']);
    }

    public function test_logo_upload_validation_and_reset(): void
    {
        Storage::fake('local');
        $this->actingAs(User::factory()->create(['role' => 'registrar']));
        $this->post(route('settings.update'), [...$this->payload(), 'school_logo' => UploadedFile::fake()->create('evil.svg', 2, 'image/svg+xml')])->assertSessionHasErrors('school_logo');
        $this->post(route('settings.update'), [...$this->payload(), 'school_logo' => UploadedFile::fake()->image('seal.png')])->assertSessionHasNoErrors();
        $path = Setting::where('key', 'school_logo_path')->value('value');
        Storage::disk('local')->assertExists($path);
        $this->get(route('school.logo'))->assertOk()->assertHeader('X-Content-Type-Options', 'nosniff');
        $this->post(route('settings.update'), [...$this->payload(), 'remove_school_logo' => 1])->assertSessionHasNoErrors();
        Storage::disk('local')->assertMissing($path);
        $this->assertSame(public_path('images/fiat.png'), SystemContent::logoPath());
    }

    public function test_content_length_and_contact_email_are_validated(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'registrar']))
            ->post(route('settings.update'), [...$this->payload(), 'school_address' => str_repeat('a', 501), 'system_email' => 'invalid', 'mobile_number' => str_repeat('1', 61), 'contact_number' => str_repeat('2', 61)])
            ->assertSessionHasErrors(['school_address', 'system_email', 'mobile_number', 'contact_number']);
    }

    public function test_changing_issuer_preserves_previously_signed_document_identity(): void
    {
        Setting::create(['key' => 'document_issuer', 'value' => 'Original issuer']);
        $qr = app(DocumentQrCode::class);
        $original = $qr->issue('Custom document', 'Original student');
        Setting::where('key', 'document_issuer')->update(['value' => 'New issuer']);
        $new = $qr->issue('Custom document', 'Another student');
        $this->assertSame('Original issuer', $original->fresh()->issuer_name);
        $this->assertSame('New issuer', $new->issuer_name);
        $this->assertTrue($qr->verify($original->fresh(), $original->signature)['authentic']);
    }

    private function payload(): array
    {
        return ['institution_name' => 'Example School', 'school_address' => 'Example address',
            'system_email' => 'school@example.test', 'contact_number' => '046-123-4567', 'mobile_number' => '0917-123-4567',
            'office_hours' => 'Weekdays 8 AM to 5 PM'];
    }
}
