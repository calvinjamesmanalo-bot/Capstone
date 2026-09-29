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

    public function test_registrar_settings_only_contains_announcements(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'registrar']));
        $page = $this->get(route('settings.index'))->assertOk();
        $page->assertSee('Announcements')
            ->assertDontSee('School Logo')
            ->assertDontSee('General Configuration');
        $this->post(route('settings.update'), ['office_hours' => '9 to 5'])->assertForbidden();
    }

    public function test_admin_can_view_and_update_system_settings_without_announcements(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        $this->get(route('settings.index'))->assertOk()
            ->assertSee('School Logo')
            ->assertSee('General Configuration')
            ->assertDontSee('Create Announcement');

        $this->post(route('settings.update'), ['office_hours' => '9 to 5'])
            ->assertRedirect()->assertSessionHasNoErrors();
        $this->assertDatabaseHas('settings', ['key' => 'office_hours', 'value' => '9 to 5']);
    }
}
