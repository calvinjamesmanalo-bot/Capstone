<?php

namespace Tests\Feature;

use Tests\TestCase;

class ProductionReadinessCheckTest extends TestCase
{
    public function test_local_configuration_is_reported_as_not_production_ready_without_printing_secrets(): void
    {
        config([
            'app.env' => 'local',
            'app.debug' => true,
            'app.url' => 'http://127.0.0.1:8000',
            'services.turnstile.secret_key' => 'never-print-this-secret',
        ]);

        $this->artisan('production:check')
            ->expectsOutputToContain('Production environment')
            ->expectsOutputToContain('Debug disabled')
            ->expectsOutputToContain('No secret values were displayed.')
            ->doesntExpectOutputToContain('never-print-this-secret')
            ->assertFailed();
    }
}
