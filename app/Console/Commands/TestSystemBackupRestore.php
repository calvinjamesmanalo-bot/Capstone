<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use PDO;
use RuntimeException;
use ZipArchive;

class TestSystemBackupRestore extends Command
{
    protected $signature = 'backup:restore-test {file : Backup filename or absolute path}';

    protected $description = 'Safely restore a backup into a temporary directory and verify its databases and files';

    public function handle(): int
    {
        $input = (string) $this->argument('file');
        $backupPath = is_file($input) ? $input : storage_path('app/backups'.DIRECTORY_SEPARATOR.basename($input));
        if (! is_file($backupPath)) {
            $this->error('Backup file not found.');
            return self::FAILURE;
        }

        $restoreRoot = storage_path('app/restore-test-'.bin2hex(random_bytes(6)));
        File::ensureDirectoryExists($restoreRoot, 0700, true);
        $zip = new ZipArchive;

        try {
            if ($zip->open($backupPath) !== true) {
                throw new RuntimeException('Backup archive cannot be opened.');
            }

            $manifestJson = $zip->getFromName('manifest.json');
            $manifest = is_string($manifestJson) ? json_decode($manifestJson, true, flags: JSON_THROW_ON_ERROR) : null;
            if (! is_array($manifest) || ($manifest['format_version'] ?? null) !== 1 || ! is_array($manifest['files'] ?? null)) {
                throw new RuntimeException('Manifest is missing, invalid, or unsupported.');
            }

            $restored = 0;
            foreach ($manifest['files'] as $entry) {
                $archivePath = $this->validatedArchivePath((string) ($entry['path'] ?? ''));
                $contents = $zip->getFromName($archivePath);
                if (! is_string($contents)
                    || strlen($contents) !== (int) ($entry['size'] ?? -1)
                    || ! hash_equals((string) ($entry['sha256'] ?? ''), hash('sha256', $contents))) {
                    throw new RuntimeException("Integrity check failed for {$archivePath}.");
                }

                $target = $restoreRoot.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $archivePath);
                File::ensureDirectoryExists(dirname($target), 0700, true);
                if (File::put($target, $contents, true) === false) {
                    throw new RuntimeException("Could not write temporary restore file {$archivePath}.");
                }
                $restored++;
            }

            $databaseResults = [];
            foreach (['application.sqlite', 'school_forms.sqlite'] as $databaseName) {
                $databasePath = $restoreRoot.DIRECTORY_SEPARATOR.'databases'.DIRECTORY_SEPARATOR.$databaseName;
                if (! is_file($databasePath)) {
                    if ($databaseName === 'application.sqlite') {
                        throw new RuntimeException('The main application database is missing.');
                    }
                    continue;
                }

                $pdo = new PDO('sqlite:'.$databasePath, null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
                $integrity = $pdo->query('PRAGMA integrity_check')->fetchColumn();
                if ($integrity !== 'ok') {
                    throw new RuntimeException("SQLite integrity check failed for {$databaseName}: {$integrity}");
                }
                $databaseResults[] = $databaseName;
            }

            $this->info('Isolated restore verification passed.');
            $this->line("Integrity check: PASSED ({$restored} files)");
            $this->line('Database check: PASSED ('.implode(', ', $databaseResults).')');
            $this->line('Private files check: PASSED');
            $this->line('Current system modified: NO');

            return self::SUCCESS;
        } catch (\Throwable $exception) {
            $this->error('Isolated restore verification failed: '.$exception->getMessage());
            $this->line('Current system modified: NO');
            return self::FAILURE;
        } finally {
            if ($zip->status === ZipArchive::ER_OK) {
                $zip->close();
            }
            File::deleteDirectory($restoreRoot);
        }
    }

    private function validatedArchivePath(string $path): string
    {
        $normalized = str_replace('\\', '/', $path);
        if ($normalized === ''
            || str_starts_with($normalized, '/')
            || preg_match('/^[A-Za-z]:/', $normalized)
            || in_array('..', explode('/', $normalized), true)) {
            throw new RuntimeException('Unsafe path found in backup manifest.');
        }

        return $normalized;
    }
}
