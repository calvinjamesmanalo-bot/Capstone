<?php

namespace Tests\Feature;

use App\Models\Form138Upload;
use App\Models\Student;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class LegacyGradeUploadMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_copies_verifies_and_updates_legacy_upload_without_deleting_public_source(): void
    {
        Storage::fake('public');
        Storage::fake('local');
        Storage::disk('public')->put('form138_uploads/legacy-card.pdf', 'legacy grade file');
        $upload = $this->legacyUpload('form138_uploads/legacy-card.pdf');

        $this->artisan('grades:migrate-private')->assertSuccessful();

        $upload->refresh();
        $this->assertSame('local', $upload->storage_disk);
        $this->assertSame(hash('sha256', 'legacy grade file'), $upload->sha256);
        $this->assertSame(strlen('legacy grade file'), $upload->file_size);
        $this->assertStringNotContainsString('legacy-card', $upload->file_path);
        Storage::disk('local')->assertExists($upload->file_path);
        Storage::disk('public')->assertExists('form138_uploads/legacy-card.pdf');
    }

    public function test_dry_run_and_missing_files_do_not_change_database(): void
    {
        Storage::fake('public');
        Storage::fake('local');
        Storage::disk('public')->put('form138_uploads/dry-run.pdf', 'dry run');
        $ready = $this->legacyUpload('form138_uploads/dry-run.pdf');
        $missing = $this->legacyUpload('form138_uploads/missing.pdf');

        $this->artisan('grades:migrate-private', ['--dry-run' => true])->assertSuccessful();

        $this->assertNull($ready->fresh()->storage_disk);
        $this->assertSame('form138_uploads/dry-run.pdf', $ready->fresh()->file_path);
        $this->artisan('grades:migrate-private')->assertSuccessful();
        $this->assertNull($missing->fresh()->storage_disk);
        $this->assertSame('form138_uploads/missing.pdf', $missing->fresh()->file_path);
    }

    private function legacyUpload(string $path): Form138Upload
    {
        Student::firstOrCreate(
            ['student_number' => '2026-3001'],
            ['name' => 'Legacy Upload Student']
        );

        return Form138Upload::create([
            'student_number' => '2026-3001',
            'school_year' => '2024-2025',
            'file_path' => $path,
            'pdf_path' => $path,
            'original_filename' => basename($path),
        ]);
    }
}
