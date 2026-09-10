<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Form138Upload;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class GradePortalUploadSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_grade_reference_upload_is_private_and_uses_a_random_filename(): void
    {
        Storage::fake('local');
        Storage::fake('public');
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->post(route('grade-portal.upload'), [
            'student_number' => '2026-2001',
            'name' => 'Grade Upload Student',
            'school_years' => ['2025-2026'],
            'files' => [UploadedFile::fake()->image('report-card.jpg')],
        ])->assertSessionHasNoErrors();

        $upload = Form138Upload::firstOrFail();

        $this->assertSame('local', $upload->storage_disk);
        $this->assertSame('report-card.jpg', $upload->original_filename);
        $this->assertStringNotContainsString('report-card', $upload->file_path);
        $this->assertMatchesRegularExpression('/^[0-9a-f-]{36}\.jpg$/', basename($upload->file_path));
        $this->assertNotNull($upload->sha256);
        Storage::disk('local')->assertExists($upload->file_path);
        Storage::disk('public')->assertMissing($upload->file_path);
        $this->assertDatabaseHas('activity_logs', [
            'action' => 'Uploaded Grade File',
            'module' => 'File Security',
            'status' => 'success',
        ]);
    }

    public function test_only_admin_and_registrar_can_view_grade_reference_uploads(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('form138_uploads/private.pdf', 'private grade record');
        $upload = $this->uploadRecord();

        $this->get(route('grade-portal.uploads.view', $upload))->assertRedirect(route('login'));

        foreach (['student', 'records_officer'] as $role) {
            $this->actingAs(User::factory()->create(['role' => $role]))
                ->get(route('grade-portal.uploads.view', $upload))
                ->assertForbidden();
        }

        foreach (['admin', 'registrar'] as $role) {
            $this->actingAs(User::factory()->create(['role' => $role]))
                ->get(route('grade-portal.uploads.view', $upload))
                ->assertOk()
                ->assertHeader('X-Content-Type-Options', 'nosniff');
        }

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'Grade File Access Denied',
            'module' => 'File Security',
            'status' => 'denied',
        ]);
        $this->assertSame(2, ActivityLog::where('action', 'Viewed Grade File')->count());
    }

    public function test_invalid_type_invalid_school_year_and_oversized_files_are_rejected(): void
    {
        Storage::fake('local');
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->post(route('grade-portal.upload'), [
            'student_number' => '2026-2003',
            'name' => 'Invalid Upload Student',
            'school_years' => ['not-a-school-year'],
            'files' => [UploadedFile::fake()->create('malware.exe', 1, 'application/x-msdownload')],
        ])->assertSessionHasErrors(['school_years.0', 'files.0']);

        $this->actingAs($admin)->post(route('grade-portal.upload'), [
            'student_number' => '2026-2003',
            'name' => 'Invalid Upload Student',
            'school_years' => ['2025-2026'],
            'files' => [UploadedFile::fake()->create('large.pdf', 20481, 'application/pdf')],
        ])->assertSessionHasErrors(['files.0']);

        $this->assertDatabaseCount('form138_uploads', 0);
    }

    public function test_missing_and_deleted_grade_files_are_audited(): void
    {
        Storage::fake('local');
        $admin = User::factory()->create(['role' => 'admin']);
        $missingUpload = $this->uploadRecord();

        $this->actingAs($admin)->get(route('grade-portal.uploads.view', $missingUpload))->assertNotFound();
        $this->assertDatabaseHas('activity_logs', [
            'action' => 'Grade File Missing',
            'status' => 'missing',
        ]);

        Storage::disk('local')->put($missingUpload->file_path, 'file to delete');
        $this->actingAs($admin)->delete(route('grade-portal.delete', $missingUpload->id))->assertRedirect();
        $this->assertDatabaseHas('activity_logs', [
            'action' => 'Deleted Grade File',
            'status' => 'warning',
        ]);
        Storage::disk('local')->assertMissing($missingUpload->file_path);
    }

    private function uploadRecord(): Form138Upload
    {
        Student::create([
            'student_number' => '2026-2002',
            'name' => 'Private Grade Student',
        ]);

        return Form138Upload::create([
            'student_number' => '2026-2002',
            'school_year' => '2025-2026',
            'file_path' => 'form138_uploads/private.pdf',
            'original_filename' => 'private.pdf',
            'storage_disk' => 'local',
        ]);
    }
}
