<?php

namespace Tests\Feature;

use Tests\TestCase;

class SessionStrictModeTest extends TestCase
{
    public function test_strict_session_mode_is_enabled_during_application_bootstrap(): void
    {
        $this->assertTrue((bool) config('session.strict_mode'));
        $this->assertSame('1', ini_get('session.use_strict_mode'));
    }

    public function test_production_check_reports_disabled_strict_session_mode(): void
    {
        config([
            'app.env' => 'production',
            'session.strict_mode' => false,
        ]);
        ini_set('session.use_strict_mode', '0');

        try {
            $this->artisan('production:check')
                ->expectsOutputToContain('Strict session mode')
                ->assertFailed();
        } finally {
            ini_set('session.use_strict_mode', '1');
        }
    }
}
