<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     */
    public function test_guests_are_redirected_to_login(): void
    {
        $response = $this->get('/');

        $response->assertRedirect(route('login'));
    }

    public function test_global_loading_animation_renders_the_fiat_lux_logo_without_text(): void
    {
        $this->view('partials.loading-overlay')
            ->assertSee('id="fla-page-loader"', false)
            ->assertSee('class="fla-loader-logo"', false)
            ->assertSee(asset('images/fiat.png'), false)
            ->assertDontSee('fla-loader-title', false)
            ->assertDontSee('fla-loader-message', false)
            ->assertDontSee('fla-loader-dots', false);
    }
}
