<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class RateLimitingTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'cache.default' => 'array',
            'security.rate_limiting.web_requests_per_minute' => 2,
        ]);

        Route::middleware('web')->get('/rate-limit-test', fn () => response('OK'));
    }

    public function test_web_requests_are_rate_limited_per_ip_address(): void
    {
        $this->withServerVariables(['REMOTE_ADDR' => '192.0.2.10'])
            ->get('/rate-limit-test')
            ->assertOk()
            ->assertHeader('X-RateLimit-Limit', '2');

        $this->withServerVariables(['REMOTE_ADDR' => '192.0.2.10'])
            ->get('/rate-limit-test')
            ->assertOk();

        $this->withServerVariables(['REMOTE_ADDR' => '192.0.2.10'])
            ->get('/rate-limit-test')
            ->assertTooManyRequests()
            ->assertHeader('Retry-After');

        $this->withServerVariables(['REMOTE_ADDR' => '192.0.2.11'])
            ->get('/rate-limit-test')
            ->assertOk();
    }
}
