<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SystemHealthAndErrorPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_system_health_reports_core_dependencies_without_mutating_records(): void
    {
        $before = \App\Models\User::count();

        $this->artisan('system:health')
            ->expectsOutputToContain('Database connection')
            ->expectsOutputToContain('Private storage read/write')
            ->expectsOutputToContain('Required PHP extensions')
            ->assertSuccessful();

        $this->assertSame($before, \App\Models\User::count());
    }

    public function test_unknown_pages_use_the_branded_safe_404_page(): void
    {
        $this->get('/this-page-does-not-exist')
            ->assertNotFound()
            ->assertSee('Page not found')
            ->assertDontSee('Stack trace');
    }

    public function test_safe_error_templates_exist_for_expected_http_failures(): void
    {
        $expectations = [
            403 => 'Access not allowed',
            419 => 'Session expired',
            429 => 'Please slow down',
            500 => 'Something went wrong',
            503 => 'Temporarily unavailable',
        ];

        foreach ($expectations as $code => $message) {
            $html = view("errors.{$code}")->render();
            $this->assertStringContainsString((string) $code, $html);
            $this->assertStringContainsString($message, $html);
            $this->assertStringNotContainsString('Stack trace', $html);
        }
    }
}
