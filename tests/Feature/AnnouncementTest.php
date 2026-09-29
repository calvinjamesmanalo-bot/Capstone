<?php

namespace Tests\Feature;

use App\Models\Announcement;
use App\Models\Setting;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AnnouncementTest extends TestCase
{
    use RefreshDatabase;

    public function test_registrar_can_create_edit_and_archive_without_overwriting_other_announcements(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'registrar']));
        foreach (['First notice', 'Second notice'] as $body) {
            $this->post(route('announcements.store'), ['body' => $body])->assertRedirect(route('settings.index'))
                ->assertSessionHas('success', 'Announcement created successfully.');
            $page = $this->get(route('settings.index'))->assertOk()->assertSee($body);
            $this->assertSame(1, substr_count($page->getContent(), 'Announcement created successfully.'));
            $this->assertMatchesRegularExpression('/id="new-announcement"[^>]*><\/textarea>/', $page->getContent());
        }
        $first = Announcement::oldest('id')->first();
        $this->patch(route('announcements.update', $first), ['body' => 'Edited notice'])->assertSessionHas('success', 'Announcement updated successfully.');
        $this->assertSame(1, substr_count($this->get(route('settings.index'))->getContent(), 'Announcement updated successfully.'));
        $this->assertDatabaseHas('announcements', ['body' => 'Second notice', 'deleted_at' => null]);
        $this->patch(route('announcements.archive', $first))->assertSessionHas('success', 'Announcement archived successfully.');
        $page = $this->get(route('settings.index'))->assertDontSee('Edited notice');
        $this->assertSame(1, substr_count($page->getContent(), 'Announcement archived successfully.'));
        $this->assertSoftDeleted($first);
        $this->patch(route('announcements.update', $first), ['body' => 'Revived'])->assertNotFound();
        $this->post(route('settings.update'), ['office_hours' => '9 to 5'])->assertForbidden();
    }

    public function test_student_sees_all_active_announcements_first_and_escaped(): void
    {
        $first = Announcement::create(['body' => 'First notice']);
        Announcement::create(['body' => '<script>Second notice</script>']);
        $archived = Announcement::create(['body' => 'Archived notice']);
        $archived->delete();
        Student::create(['student_number' => '12345', 'name' => 'Student', 'official_email' => 'notice@example.test']);
        $student = User::factory()->create(['role' => 'student', 'student_number' => '12345', 'email' => 'notice@example.test']);
        $this->actingAs($student)->get(route('dashboard'))->assertOk()
            ->assertSeeInOrder(['Announcement', '&lt;script&gt;Second notice&lt;/script&gt;', 'First notice', 'Quick actions'], false)
            ->assertDontSee('<script>Second notice</script>', false)->assertDontSee('Archived notice');
        $this->get(route('student.request'))->assertOk()->assertDontSee('First notice')->assertDontSee('Second notice');
        $first->update(['body' => 'Updated notice']);
        $this->get(route('dashboard'))->assertSee('Updated notice')->assertDontSee('First notice');
        Announcement::query()->delete();
        $this->get(route('dashboard'))->assertSee('No announcements at this time.');
    }

    public function test_announcement_actions_are_registrar_only(): void
    {
        $announcement = Announcement::create(['body' => 'Protected notice']);
        foreach (['admin', 'student', 'records_officer'] as $role) {
            $this->actingAs(User::factory()->create(['role' => $role]));
            $this->post(route('announcements.store'), ['body' => 'Forbidden'])->assertForbidden();
            $this->patch(route('announcements.update', $announcement), ['body' => 'Forbidden'])->assertForbidden();
            $this->patch(route('announcements.archive', $announcement))->assertForbidden();
        }
        $this->assertSame('Protected notice', $announcement->fresh()->body);
        $this->assertNull($announcement->fresh()->deleted_at);
    }

    public function test_validation_preserves_the_correct_form_and_reopens_edit_dialog(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'registrar']));
        foreach (['', '   ', str_repeat('x', 2001)] as $body) {
            $this->from(route('settings.index'))->post(route('announcements.store'), ['body' => $body])
                ->assertSessionHasErrors('body', null, 'createAnnouncement');
            $this->get(route('settings.index'))->assertOk();
        }
        $announcement = Announcement::create(['body' => 'Original']);
        $this->from(route('settings.index'))->patch(route('announcements.update', $announcement), ['body' => str_repeat('z', 2001)])
            ->assertSessionHasErrors('body', null, 'announcement'.$announcement->id);
        $page = $this->get(route('settings.index'))->assertOk()->assertSee('data-reopen', false);
        $this->assertMatchesRegularExpression('/id="new-announcement"[^>]*><\/textarea>/', $page->getContent());
        $this->assertSame('Original', $announcement->fresh()->body);
    }

    public function test_migration_preserves_legacy_announcement(): void
    {
        $migration = require database_path('migrations/2026_09_27_000000_create_announcements_table.php');
        $migration->down();
        Setting::where('key', 'general_message')->update(['value' => 'Existing school notice']);
        $migration->up();
        $this->assertDatabaseHas('announcements', ['body' => 'Existing school notice', 'deleted_at' => null]);
        $this->assertDatabaseMissing('settings', ['key' => 'general_message']);
    }
}
