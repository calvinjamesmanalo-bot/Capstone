<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UatPreparationTest extends TestCase
{
    use RefreshDatabase;

    public function test_uat_prepare_creates_all_roles_and_cleanup_requires_confirmation(): void
    {
        $this->artisan('uat:prepare')->assertSuccessful();
        $users = User::where('email', 'like', 'uat+%@example.test')->get();

        $this->assertEqualsCanonicalizing(
            ['admin', 'records_officer', 'registrar', 'student'],
            $users->pluck('role')->all()
        );

        $studentNumber = $users->firstWhere('role', 'student')->student_number;
        $runId = str_replace('UAT-', '', $studentNumber);
        $this->assertSame(strtoupper($studentNumber), $studentNumber);
        $this->artisan('uat:cleanup', ['run_id' => $runId, '--confirm' => true])->assertSuccessful();
        $this->assertDatabaseMissing('users', ['student_number' => $studentNumber]);
    }

    public function test_uat_cleanup_refuses_to_run_without_confirmation(): void
    {
        $this->artisan('uat:cleanup', ['run_id' => '20260908120000ABCD'])->assertFailed();
        $this->assertDatabaseCount('users', 0);
    }

    public function test_uat_prepare_is_disabled_in_production(): void
    {
        config(['app.env' => 'production']);
        $this->artisan('uat:prepare')->assertFailed();
        $this->assertDatabaseCount('users', 0);
    }
}
