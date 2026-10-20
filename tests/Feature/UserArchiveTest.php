<?php

namespace Tests\Feature;

use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserArchiveTest extends TestCase
{
    use RefreshDatabase;

    public function test_deleting_an_account_moves_it_to_archive_and_restore_reactivates_it(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $student = Student::create(['student_number' => 'ARC-100', 'name' => 'Archive, Student', 'official_email' => 'archive@student.test']);
        $user = User::factory()->create(['role' => 'student', 'student_number' => $student->student_number, 'email' => $student->official_email]);

        $this->actingAs($admin)->delete(route('users.destroy', $user))
            ->assertRedirect(route('users.index'))->assertSessionHas('success');

        $user->refresh();
        $this->assertSame('archived_student', $user->role);
        $this->assertSame('archive@student.test', $student->fresh()->official_email);
        $this->actingAs($admin)->get(route('users.index'))->assertOk()->assertSee('Archived Accounts')->assertSee('ARC-100');

        $this->actingAs($admin)->post(route('users.restore', $user->id))
            ->assertRedirect(route('users.index'))->assertSessionHas('success');
        $this->assertSame('student', $user->fresh()->role);
    }

    public function test_archive_is_permanently_deleted_after_six_months_without_deleting_student_record(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $student = Student::create(['student_number' => 'ARC-200', 'name' => 'Expired, Student', 'official_email' => 'expired@student.test']);
        $user = User::factory()->create([
            'role' => 'archived_student', 'student_number' => $student->student_number, 'email' => $student->official_email,
            'updated_at' => now()->subMonthsNoOverflow(6)->subDay(),
        ]);

        $this->actingAs($admin)->get(route('users.index'))->assertOk();

        $this->assertDatabaseMissing('users', ['id' => $user->id]);
        $this->assertDatabaseHas('students', ['student_number' => $student->student_number]);
        $this->assertNull($student->fresh()->official_email);
    }

    public function test_admin_cannot_archive_the_account_currently_in_use(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->delete(route('users.destroy', $admin))->assertStatus(422);
        $this->assertSame('admin', $admin->fresh()->role);
    }
}
