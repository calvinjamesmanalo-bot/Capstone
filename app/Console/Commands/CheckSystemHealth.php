<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class CheckSystemHealth extends Command
{
    protected $signature = 'system:health';

    protected $description = 'Run non-destructive health checks for deployment dependencies and services';

    public function handle(): int
    {
        $checks = [];
        $add = function (string $name, string $status, string $detail) use (&$checks): void {
            $checks[] = [$status, $name, $detail];
        };

        try {
            DB::select('SELECT 1');
            $add('Database connection', 'PASS', config('database.default').' connection responded');
        } catch (\Throwable $exception) {
            $add('Database connection', 'FAIL', 'Connection failed; check credentials and server availability');
        }

        $requiredTables = ['users', 'students', 'request_documents', 'activity_logs', 'migrations'];
        $missingTables = array_values(array_filter($requiredTables, fn (string $table) => ! Schema::hasTable($table)));
        $add('Required database tables', $missingTables === [] ? 'PASS' : 'FAIL', $missingTables === [] ? 'All required tables exist' : 'Missing: '.implode(', ', $missingTables));

        try {
            $migrationFiles = app('migrator')->getMigrationFiles(database_path('migrations'));
            $ran = app('migrator')->getRepository()->getRan();
            $pending = array_values(array_diff(array_keys($migrationFiles), $ran));
            $add('Database migrations', $pending === [] ? 'PASS' : 'FAIL', $pending === [] ? 'No pending migrations' : count($pending).' migration(s) pending');
        } catch (\Throwable) {
            $add('Database migrations', 'FAIL', 'Migration status could not be read');
        }

        $probe = '.health-check-'.bin2hex(random_bytes(4));
        $storageOk = Storage::disk('local')->put($probe, 'health')
            && Storage::disk('local')->get($probe) === 'health';
        Storage::disk('local')->delete($probe);
        $add('Private storage read/write', $storageOk ? 'PASS' : 'FAIL', $storageOk ? 'Private storage is operational' : 'Private storage is not writable/readable');

        $backupDirectory = storage_path('app/backups');
        $backupCount = is_dir($backupDirectory) ? count(File::glob($backupDirectory.'/*.zip')) : 0;
        $add('Backup system', class_exists(CreateSystemBackup::class) && class_exists(VerifySystemBackup::class) ? 'PASS' : 'FAIL', "Backup commands available; {$backupCount} archive(s) found");

        $requiredExtensions = ['openssl', 'pdo_sqlite', 'fileinfo', 'mbstring', 'zip'];
        $missingExtensions = array_values(array_filter($requiredExtensions, fn (string $extension) => ! extension_loaded($extension)));
        $add('Required PHP extensions', $missingExtensions === [] ? 'PASS' : 'FAIL', $missingExtensions === [] ? 'All required extensions loaded' : 'Missing: '.implode(', ', $missingExtensions));

        $queue = (string) config('queue.default');
        $queueStatus = in_array($queue, ['sync', 'null'], true) ? 'WARNING' : 'PASS';
        $add('Queue configuration', $queueStatus, "Driver: {$queue}".($queueStatus === 'WARNING' ? '; use database or Redis in production' : ''));
        $failedJobs = Schema::hasTable('failed_jobs') ? DB::table('failed_jobs')->count() : null;
        $add('Failed queue jobs', $failedJobs === 0 ? 'PASS' : 'WARNING', $failedJobs === null ? 'failed_jobs table missing' : "{$failedJobs} failed job(s)");

        $certificate = (string) config('pdf_signing.certificate_path');
        $key = (string) config('pdf_signing.private_key_path');
        $signingReady = is_readable($certificate) && is_readable($key) && filled(config('pdf_signing.private_key_password'));
        $add('PDF signing', $signingReady ? 'PASS' : 'WARNING', $signingReady ? 'Certificate and protected key are readable' : 'Certificate, private key, or key password is not production-ready');

        $mailer = (string) config('mail.default');
        $mailReady = ! in_array($mailer, ['log', 'array'], true)
            && filled(config("mail.mailers.{$mailer}.host"));
        $add('Mail configuration', $mailReady ? 'PASS' : 'WARNING', $mailReady ? "Mailer {$mailer} is configured" : "Mailer {$mailer} does not deliver real email");

        $freeBytes = @disk_free_space(storage_path());
        $diskHealthy = is_float($freeBytes) || is_int($freeBytes) ? $freeBytes >= 1024 ** 3 : false;
        $freeText = $freeBytes === false ? 'Could not determine free space' : number_format($freeBytes / 1024 ** 3, 2).' GB free';
        $add('Disk space', $diskHealthy ? 'PASS' : 'WARNING', $freeText);

        $this->table(['Result', 'Check', 'Details'], $checks);
        $failures = count(array_filter($checks, fn (array $check) => $check[0] === 'FAIL'));
        $warnings = count(array_filter($checks, fn (array $check) => $check[0] === 'WARNING'));
        $this->line("Summary: {$failures} failed, {$warnings} warnings, ".(count($checks) - $failures - $warnings).' passed.');

        return $failures === 0 ? self::SUCCESS : self::FAILURE;
    }
}
