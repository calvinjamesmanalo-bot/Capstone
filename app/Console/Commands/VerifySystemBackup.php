<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use RuntimeException;
use ZipArchive;

class VerifySystemBackup extends Command
{
    protected $signature = 'backup:verify {file : Backup filename or absolute path}';

    protected $description = 'Verify every file in a system backup against its SHA-256 manifest';

    public function handle(): int
    {
        $input = (string) $this->argument('file');
        $path = is_file($input) ? $input : storage_path('app/backups'.DIRECTORY_SEPARATOR.basename($input));
        if (! is_file($path)) {
            $this->error('Backup file not found.');
            return self::FAILURE;
        }

        $zip = new ZipArchive;
        if ($zip->open($path) !== true) {
            $this->error('Backup archive cannot be opened.');
            return self::FAILURE;
        }

        try {
            $manifestJson = $zip->getFromName('manifest.json');
            $manifest = is_string($manifestJson) ? json_decode($manifestJson, true, flags: JSON_THROW_ON_ERROR) : null;
            if (! is_array($manifest) || ! isset($manifest['files']) || ! is_array($manifest['files'])) {
                throw new RuntimeException('Manifest is missing or invalid.');
            }

            foreach ($manifest['files'] as $entry) {
                $contents = $zip->getFromName($entry['path']);
                if (! is_string($contents)
                    || strlen($contents) !== (int) $entry['size']
                    || ! hash_equals((string) $entry['sha256'], hash('sha256', $contents))) {
                    throw new RuntimeException("Integrity check failed for {$entry['path']}.");
                }
            }

            $this->info('Backup verified successfully.');
            $this->line('Verified files: '.count($manifest['files']));
            $this->line('Archive SHA-256: '.hash_file('sha256', $path));
            return self::SUCCESS;
        } catch (\Throwable $exception) {
            $this->error('Backup verification failed: '.$exception->getMessage());
            return self::FAILURE;
        } finally {
            $zip->close();
        }
    }
}
