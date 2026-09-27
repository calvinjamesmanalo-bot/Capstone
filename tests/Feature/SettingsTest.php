<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_uses_the_logo_only_loading_animation(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee('class="fla-loader-logo"', false)
            ->assertDontSee('data-loading-message', false)
            ->assertDontSee('fla-loader-message', false);
    }

    public function test_registrar_settings_excludes_generator_and_removed_fields(): void
    {
        Setting::create(['key' => 'school_district', 'value' => 'Existing district', 'group' => 'school_profile']);
        $this->actingAs(User::factory()->create(['role' => 'registrar']));
        $page = $this->get(route('settings.index'))->assertOk();
        $removed = ['document_issuer', 'request_instructions', 'school_name', 'school_district', 'school_id', 'school_division', 'school_region'];
        foreach ($removed as $key) {
            $page->assertDontSee('name="'.$key.'"', false);
        }
        $page->assertDontSee('Save School Profile')->assertSee('School Email')->assertSee('Landline Number')->assertSee('Mobile/Cellphone Number');
        $this->post(route('settings.update'), array_fill_keys($removed, 'Ignored'))
            ->assertRedirect()->assertSessionHasNoErrors();
        $this->assertDatabaseHas('settings', ['key' => 'school_district', 'value' => 'Existing district']);
        foreach (array_diff($removed, ['school_district']) as $key) {
            $this->assertDatabaseMissing('settings', ['key' => $key]);
        }
        $this->assertSame('Existing district', app(\App\Support\SchoolProfile::class)->values()['district']);
    }
}
