<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use RuntimeException;
use ZipArchive;

class CreateSystemBackup extends Command
{
    protected $signature = 'backup:create {--name= : Optional safe label for the backup}';

    protected $description = 'Create a private ZIP backup with database snapshots, uploaded files, and an integrity manifest';

    public function handle(): int
    {
        if (! class_exists(ZipArchive::class)) {
            $this->error('PHP ZipArchive is required to create backups.');
            return self::FAILURE;
        }

        $backupDirectory = storage_path('app/backups');
        File::ensureDirectoryExists($backupDirectory, 0700, true);
        $label = preg_replace('/[^A-Za-z0-9_-]/', '-', (string) ($this->option('name') ?: 'system'));
        $filename = now()->format('Ymd_His').'_'.trim($label, '-').'_'.bin2hex(random_bytes(3)).'.zip';
        $finalPath = $backupDirectory.DIRECTORY_SEPARATOR.$filename;
        $temporaryPath = $finalPath.'.tmp';
        $snapshotDirectory = storage_path('app/backup-staging-'.bin2hex(random_bytes(5)));
        File::ensureDirectoryExists($snapshotDirectory, 0700, true);

        try {
            $entries = [];
            $this->snapshotSqliteDatabase(database_path('database.sqlite'), $snapshotDirectory.DIRECTORY_SEPARATOR.'application.sqlite');
            $entries[] = ['source' => $snapshotDirectory.DIRECTORY_SEPARATOR.'application.sqlite', 'archive' => 'databases/application.sqlite'];

            $schoolDatabase = base_path('generator/database/database.sqlite');
            if (is_file($schoolDatabase)) {
                $this->snapshotSqliteDatabase($schoolDatabase, $snapshotDirectory.DIRECTORY_SEPARATOR.'school_forms.sqlite');
                $entries[] = ['source' => $snapshotDirectory.DIRECTORY_SEPARATOR.'school_forms.sqlite', 'archive' => 'databases/school_forms.sqlite'];
            }

            $this->collectFiles(storage_path('app/private'), 'files/application', $entries);
            $this->collectFiles(base_path('generator/storage/app/private'), 'files/school_forms', $entries);

            $zip = new ZipArchive;
            if ($zip->open($temporaryPath, ZipArchive::CREATE | ZipArchive::EXCL) !== true) {
                throw new RuntimeException('Could not create the backup archive.');
            }

            $manifestFiles = [];
            foreach ($entries as $entry) {
                if (! $zip->addFile($entry['source'], $entry['archive'])) {
                    $zip->close();
                    throw new RuntimeException("Could not add {$entry['archive']} to the archive.");
                }
                $manifestFiles[] = [
                    'path' => $entry['archive'],
                    'size' => filesize($entry['source']),
                    'sha256' => hash_file('sha256', $entry['source']),
                ];
            }

            $manifest = json_encode([
                'format_version' => 1,
                'created_at' => now()->toIso8601String(),
                'application' => config('app.name'),
                'file_count' => count($manifestFiles),
                'files' => $manifestFiles,
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
            $zip->addFromString('manifest.json', $manifest);
            if (! $zip->close()) {
                throw new RuntimeException('Could not finalize the backup archive.');
            }

            File::move($temporaryPath, $finalPath);
            @chmod($finalPath, 0600);

            $this->info("Backup created: {$filename}");
            $this->line('Files: '.count($manifestFiles));
            $this->line('Archive size: '.filesize($finalPath).' bytes');
            $this->line('Archive SHA-256: '.hash_file('sha256', $finalPath));
            $this->warn('Stored privately under storage/app/backups; do not place it under public/.');

            return self::SUCCESS;
        } catch (\Throwable $exception) {
            File::delete([$temporaryPath, $finalPath]);
            $this->error('Backup failed: '.$exception->getMessage());
            return self::FAILURE;
        } finally {
            File::deleteDirectory($snapshotDirectory);
        }
    }

    private function snapshotSqliteDatabase(string $source, string $target): void
    {
        if (! is_file($source)) {
            throw new RuntimeException("SQLite database not found: {$source}");
        }

        $pdo = new \PDO('sqlite:'.$source);
        $quotedTarget = str_replace("'", "''", str_replace('\\', '/', $target));
        $pdo->exec("VACUUM INTO '{$quotedTarget}'");
    }

    private function collectFiles(string $root, string $archiveRoot, array &$entries): void
    {
        if (! is_dir($root)) {
            return;
        }

        foreach (File::allFiles($root) as $file) {
            $relative = str_replace('\\', '/', $file->getRelativePathname());
            $entries[] = ['source' => $file->getPathname(), 'archive' => $archiveRoot.'/'.$relative];
        }
    }
}
