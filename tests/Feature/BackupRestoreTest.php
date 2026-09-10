<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;
use ZipArchive;

class BackupRestoreTest extends TestCase
{
    use RefreshDatabase;

    private array $createdBackups = [];

    protected function tearDown(): void
    {
        File::delete($this->createdBackups);
        parent::tearDown();
    }

    public function test_a_created_backup_passes_an_isolated_restore_test_without_changing_live_database(): void
    {
        $before = hash_file('sha256', database_path('database.sqlite'));
        $this->artisan('backup:create', ['--name' => 'automated-restore-test'])->assertSuccessful();
        $backup = collect(File::glob(storage_path('app/backups/*_automated-restore-test_*.zip')))->sort()->last();
        $this->assertNotNull($backup);
        $this->createdBackups[] = $backup;

        $this->artisan('backup:restore-test', ['file' => $backup])
            ->expectsOutputToContain('Isolated restore verification passed.')
            ->expectsOutputToContain('Current system modified: NO')
            ->assertSuccessful();

        $this->assertSame($before, hash_file('sha256', database_path('database.sqlite')));
    }

    public function test_a_tampered_backup_fails_restore_verification(): void
    {
        $path = storage_path('app/backups/tampered-restore-test.zip');
        File::ensureDirectoryExists(dirname($path));
        $this->createdBackups[] = $path;
        $zip = new ZipArchive;
        $zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        $zip->addFromString('databases/application.sqlite', 'tampered');
        $zip->addFromString('manifest.json', json_encode([
            'format_version' => 1,
            'files' => [[
                'path' => 'databases/application.sqlite',
                'size' => 8,
                'sha256' => str_repeat('0', 64),
            ]],
        ], JSON_THROW_ON_ERROR));
        $zip->close();

        $this->artisan('backup:restore-test', ['file' => $path])
            ->expectsOutputToContain('Isolated restore verification failed:')
            ->expectsOutputToContain('Current system modified: NO')
            ->assertFailed();
    }
}
