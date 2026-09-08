<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HttpsSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_local_http_requests_are_not_forced_to_https(): void
    {
        config(['security.https.force' => false]);

        $this->get('http://127.0.0.1/login')
            ->assertOk()
            ->assertHeaderMissing('Strict-Transport-Security');
    }

    public function test_insecure_production_request_is_redirected_to_the_canonical_https_host(): void
    {
        config([
            'app.url' => 'https://hub.fiatlux.edu.ph',
            'security.https.force' => true,
        ]);

        $this->get('http://untrusted.example/login?session_expired=1')
            ->assertStatus(308)
            ->assertRedirect('https://hub.fiatlux.edu.ph/login?session_expired=1');
    }

    public function test_secure_production_response_contains_hsts(): void
    {
        config([
            'app.url' => 'https://hub.fiatlux.edu.ph',
            'security.https.force' => true,
            'security.https.hsts.enabled' => true,
            'security.https.hsts.max_age' => 31536000,
            'security.https.hsts.include_subdomains' => false,
        ]);

        $this->get('https://hub.fiatlux.edu.ph/login')
            ->assertOk()
            ->assertHeader('Strict-Transport-Security', 'max-age=31536000');
    }

    public function test_hsts_can_include_subdomains_after_they_are_https_ready(): void
    {
        config([
            'app.url' => 'https://hub.fiatlux.edu.ph',
            'security.https.force' => true,
            'security.https.hsts.enabled' => true,
            'security.https.hsts.max_age' => 31536000,
            'security.https.hsts.include_subdomains' => true,
        ]);

        $this->get('https://hub.fiatlux.edu.ph/login')
            ->assertOk()
            ->assertHeader('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
    }
}
