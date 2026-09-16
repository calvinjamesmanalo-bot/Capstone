<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SecurityHeadersTest extends TestCase
{
    use RefreshDatabase;

    public function test_html_responses_include_baseline_browser_security_headers(): void
    {
        $response = $this->get('/login');

        $response->assertOk()
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('X-Frame-Options', 'DENY')
            ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
            ->assertHeader('Permissions-Policy', 'camera=(), microphone=(), geolocation=(), payment=(), usb=()');

        $policy = (string) $response->headers->get('Content-Security-Policy');

        $this->assertStringContainsString("default-src 'self'", $policy);
        $this->assertStringContainsString("object-src 'none'", $policy);
        $this->assertStringContainsString("frame-ancestors 'none'", $policy);
        $this->assertStringContainsString("form-action 'self'", $policy);
        $this->assertStringContainsString('https://challenges.cloudflare.com', $policy);
        $this->assertStringContainsString('https://cdn.tailwindcss.com', $policy);
        $this->assertStringNotContainsString('upgrade-insecure-requests', $policy);
    }

    public function test_secure_production_html_upgrades_insecure_subresources(): void
    {
        config([
            'app.url' => 'https://hub.fiatlux.edu.ph',
            'security.https.force' => true,
        ]);

        $response = $this->get('https://hub.fiatlux.edu.ph/login');

        $response->assertOk();
        $this->assertStringContainsString(
            'upgrade-insecure-requests',
            (string) $response->headers->get('Content-Security-Policy')
        );
    }

    public function test_security_headers_can_be_disabled_for_diagnostics(): void
    {
        config(['security.headers.enabled' => false]);

        $this->get('/login')
            ->assertOk()
            ->assertHeaderMissing('Content-Security-Policy')
            ->assertHeaderMissing('Permissions-Policy');
    }
}
