<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class CheckProductionReadiness extends Command
{
    protected $signature = 'production:check';

    protected $description = 'Audit production configuration without displaying secret values';

    public function handle(): int
    {
        $checks = [];
        $add = function (string $check, bool $passed, string $failure, string $level = 'FAIL') use (&$checks): void {
            $checks[] = [$passed ? 'PASS' : $level, $check, $passed ? 'Configured safely' : $failure];
        };

        $add('Production environment', app()->environment('production'), 'Set APP_ENV=production.');
        $add('Debug disabled', ! config('app.debug'), 'Set APP_DEBUG=false.');
        $appUrl = (string) config('app.url');
        $add('HTTPS application URL', str_starts_with(strtolower($appUrl), 'https://'), 'Set APP_URL to the final https:// hostname.');
        $add('HTTPS enforcement', (bool) config('security.https.force'), 'Set FORCE_HTTPS=true.');
        $add('HSTS enabled', (bool) config('security.https.hsts.enabled'), 'Set HTTPS_HSTS_ENABLED=true.');

        $key = (string) config('app.key');
        $decodedKey = str_starts_with($key, 'base64:') ? base64_decode(substr($key, 7), true) : $key;
        $add('Application encryption key', is_string($decodedKey) && strlen($decodedKey) === 32, 'Generate a unique APP_KEY with php artisan key:generate.');
        $documentKey = (string) config('document_verification.signing_key');
        $add('Separate document signing key', $documentKey !== '' && ! hash_equals($key, $documentKey), 'Set a separate random DOCUMENT_SIGNING_KEY.');

        $add('Encrypted sessions', (bool) config('session.encrypt'), 'Set SESSION_ENCRYPT=true.');
        $add(
            'Strict session mode',
            (bool) config('session.strict_mode') && ini_get('session.use_strict_mode') === '1',
            'Set SESSION_STRICT_MODE=true and ensure PHP session.use_strict_mode is enabled.'
        );
        $add('Secure session cookie', (bool) config('session.secure'), 'Set SESSION_SECURE_COOKIE=true.');
        $add('HTTP-only session cookie', (bool) config('session.http_only'), 'Set SESSION_HTTP_ONLY=true.');
        $add('SameSite session cookie', in_array(config('session.same_site'), ['lax', 'strict'], true), 'Use SESSION_SAME_SITE=lax or strict.');

        $turnstile = config('services.turnstile');
        $host = parse_url($appUrl, PHP_URL_HOST);
        $allowedHosts = (array) ($turnstile['allowed_hostnames'] ?? []);
        $add('Turnstile enabled', (bool) ($turnstile['enabled'] ?? false), 'Set TURNSTILE_ENABLED=true.');
        $add('Turnstile production keys', ! ($turnstile['using_test_keys'] ?? true) && filled($turnstile['site_key']) && filled($turnstile['secret_key']), 'Disable test keys and configure production site/secret keys.');
        $add('Turnstile hostname allowlist', is_string($host) && in_array($host, $allowedHosts, true), 'Add the APP_URL hostname to TURNSTILE_ALLOWED_HOSTNAMES.');

        $mailUser = (string) config('mail.mailers.smtp.username');
        $mailPassword = (string) config('mail.mailers.smtp.password');
        $add('Production mail credentials', filled($mailUser) && filled($mailPassword) && ! str_contains($mailUser, 'your-') && ! str_contains($mailPassword, 'your-'), 'Configure real SMTP credentials.');

        $certificate = (string) config('pdf_signing.certificate_path');
        $privateKey = (string) config('pdf_signing.private_key_path');
        $publicRoot = realpath(public_path()) ?: public_path();
        $keyRealPath = realpath($privateKey);
        $add('PDF signing certificate', is_file($certificate) && is_readable($certificate), 'Configure a readable PDF signing certificate.');
        $add('Private signing key', is_file($privateKey) && is_readable($privateKey) && ($keyRealPath === false || ! str_starts_with($keyRealPath, $publicRoot)), 'Keep a readable signing key outside public/.');
        $add('Signing key password', filled(config('pdf_signing.private_key_password')), 'Set a strong PDF_SIGN_KEY_PASSWORD.');

        try {
            DB::connection()->getPdo();
            $databaseOk = true;
        } catch (\Throwable) {
            $databaseOk = false;
        }
        $add('Database reachable', $databaseOk, 'Check production database connection and credentials.');
        $add('Backup-compatible database', config('database.default') === 'sqlite', 'The built-in backup command supports SQLite only; configure and test a backup tool for your production database.');
        $add('Asynchronous queue', ! in_array(config('queue.default'), ['sync', 'null'], true), 'Set QUEUE_CONNECTION=database or redis and run a queue worker.');
        $add('SMTP mail transport', config('mail.default') === 'smtp', 'Set MAIL_MAILER=smtp for notification delivery.');
        $add('Private storage writable', Storage::disk('local')->put('.production-check', 'ok'), 'Grant write access to private storage.');
        Storage::disk('local')->delete('.production-check');
        $backupRoot = realpath(storage_path('app/backups')) ?: storage_path('app/backups');
        $add('Backups outside public', ! str_starts_with($backupRoot, $publicRoot), 'Move backups outside public/.');
        $logLevel = config('logging.channels.'.config('logging.default').'.level')
            ?? config('logging.channels.single.level');
        $add('Production log level', $logLevel !== 'debug', 'Set LOG_LEVEL=warning or error.', 'WARNING');
        $add('Shared auth rate-limit cache', config('security.authentication.cache_store') === 'redis', 'Use Redis when running more than one app instance.', 'WARNING');

        $this->table(['Result', 'Check', 'Action'], $checks);
        $failures = count(array_filter($checks, fn (array $check) => $check[0] === 'FAIL'));
        $warnings = count(array_filter($checks, fn (array $check) => $check[0] === 'WARNING'));
        $this->line("Summary: {$failures} failed, {$warnings} warnings, ".(count($checks) - $failures - $warnings).' passed.');
        $this->line('No secret values were displayed.');

        return $failures === 0 ? self::SUCCESS : self::FAILURE;
    }
}
