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

    public function test_admin_can_save_the_f137_school_profile(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->get(route('settings.index'))
            ->assertOk()
            ->assertSee('F137 School Profile')
            ->assertSee('name="school_name"', false)
            ->assertSee('name="school_district"', false)
            ->assertSee('name="school_id"', false)
            ->assertSee('name="school_division"', false)
            ->assertSee('name="school_region"', false);

        $response = $this->actingAs($admin)->post(route('settings.update'), [
            'institution_name' => 'Fiat Lux Academe',
            'school_name' => 'Fiat Lux Academe',
            'school_district' => 'Imus District',
            'school_id' => '401234',
            'school_division' => 'City Schools Division of Imus',
            'school_region' => 'Region IV-A',
            'student_idle_timeout_minutes' => 15,
            'price_certificate_enrollment' => 100,
            'price_certificate_completion' => 120,
            'price_good_moral' => 100,
            'price_certificate_recognition' => 120,
            'price_diploma' => 150,
        ]);

        $response->assertRedirect()->assertSessionHas('success');

        foreach ([
            'school_name' => 'Fiat Lux Academe',
            'school_district' => 'Imus District',
            'school_id' => '401234',
            'school_division' => 'City Schools Division of Imus',
            'school_region' => 'Region IV-A',
        ] as $key => $value) {
            $this->assertDatabaseHas('settings', [
                'key' => $key,
                'value' => $value,
                'group' => 'school_profile',
            ]);
        }
    }
}
