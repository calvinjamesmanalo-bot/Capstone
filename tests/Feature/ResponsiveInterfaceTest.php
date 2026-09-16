<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ResponsiveInterfaceTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_layout_has_responsive_navigation_controls(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('data-mobile-sidebar', false)
            ->assertSee('data-mobile-sidebar-toggle', false)
            ->assertSee('data-mobile-sidebar-close', false)
            ->assertSee('data-mobile-sidebar-backdrop', false)
            ->assertSee('lg:ml-64', false)
            ->assertSee('page-content p-4 sm:p-6 lg:p-8', false);
    }

    public function test_public_account_creation_page_uses_the_mobile_foundation(): void
    {
        $this->get(route('student.registration.request'))
            ->assertOk()
            ->assertSee('min-height: 100dvh', false)
            ->assertSee('p-6 shadow-lg sm:p-8', false);
    }
}
