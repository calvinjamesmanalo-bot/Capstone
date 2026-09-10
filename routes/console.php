<?php

use App\Models\LoginAttemptLog;
use App\Models\StudentAccountActivation;
use App\Models\User;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('admin:create', function () {
    $name = trim((string) $this->ask('Administrator name'));
    $email = strtolower(trim((string) $this->ask('Administrator email')));
    $password = (string) $this->secret('Administrator password (minimum 12 characters)');
    $confirmation = (string) $this->secret('Confirm the administrator password');

    $validator = Validator::make(compact('name', 'email', 'password', 'confirmation'), [
        'name' => ['required', 'string', 'max:255'],
        'email' => ['required', 'email', 'max:255'],
        'password' => ['required', 'same:confirmation', Password::min(12)->max(64)->mixedCase()->numbers(), 'regex:/[^\pL\pN\s]/u'],
    ], [
        'password.min' => 'The password is too short. Use at least 12 characters.',
        'password.mixed' => 'The password is too weak. Include uppercase and lowercase letters.',
        'password.numbers' => 'The password is too weak. Include at least one number.',
        'password.regex' => 'The password is too weak. Include at least one symbol.',
        'password.same' => 'The password confirmation does not match.',
    ]);

    if ($validator->fails()) {
        foreach ($validator->errors()->all() as $error) {
            $this->error($error);
        }

        return self::FAILURE;
    }

    $existing = User::whereRaw('LOWER(email) = ?', [$email])->first();

    if ($existing !== null && $existing->role !== 'admin') {
        $this->error('That email is already assigned to a different account role.');

        return self::FAILURE;
    }

    DB::transaction(function () use ($existing, $name, $email, $password): void {
        $admin = $existing ?? new User;
        $admin->forceFill([
            'name' => $name,
            'email' => $email,
            'email_verified_at' => now(),
            'password' => Hash::make($password),
            'role' => 'admin',
            'student_number' => null,
            'remember_token' => null,
        ])->save();

        // Password resets revoke existing sessions and outstanding reset links.
        DB::table(config('session.table', 'sessions'))->where('user_id', $admin->getKey())->delete();
        DB::table(config('auth.passwords.users.table', 'password_reset_tokens'))->where('email', $email)->delete();
    });

    $this->info('Administrator account created or updated securely.');
    $this->info('No plain-text password was stored or displayed.');

    return self::SUCCESS;
})->purpose('Securely create or reset an administrator account using hidden password prompts');

Artisan::command('staff:create', function () {
    $role = (string) $this->choice('Staff role', ['registrar', 'records_officer'], 0);
    $name = trim((string) $this->ask('Staff name'));
    $email = strtolower(trim((string) $this->ask('Staff email')));
    $password = (string) $this->secret('Staff password (minimum 12 characters)');
    $confirmation = (string) $this->secret('Confirm the staff password');

    $validator = Validator::make(compact('role', 'name', 'email', 'password', 'confirmation'), [
        'role' => ['required', 'in:registrar,records_officer'],
        'name' => ['required', 'string', 'max:255'],
        'email' => ['required', 'email', 'max:255'],
        'password' => ['required', 'same:confirmation', Password::min(12)->max(64)->mixedCase()->numbers(), 'regex:/[^\pL\pN\s]/u'],
    ], [
        'password.min' => 'The password is too short. Use at least 12 characters.',
        'password.mixed' => 'The password is too weak. Include uppercase and lowercase letters.',
        'password.numbers' => 'The password is too weak. Include at least one number.',
        'password.regex' => 'The password is too weak. Include at least one symbol.',
        'password.same' => 'The password confirmation does not match.',
    ]);

    if ($validator->fails()) {
        foreach ($validator->errors()->all() as $error) {
            $this->error($error);
        }

        return self::FAILURE;
    }

    $existing = User::whereRaw('LOWER(email) = ?', [$email])->first();

    if ($existing !== null && $existing->role !== $role) {
        $this->error('That email is already assigned to a different account role.');

        return self::FAILURE;
    }

    $user = DB::transaction(function () use ($existing, $name, $email, $password, $role): User {
        $user = $existing ?? new User;
        $user->forceFill([
            'name' => $name,
            'email' => $email,
            'email_verified_at' => now(),
            'password' => Hash::make($password),
            'role' => $role,
            'student_number' => null,
            'remember_token' => null,
        ])->save();

        // Resetting a staff password invalidates every existing login and reset link.
        DB::table(config('session.table', 'sessions'))->where('user_id', $user->getKey())->delete();
        DB::table(config('auth.passwords.users.table', 'password_reset_tokens'))->where('email', $email)->delete();

        return $user;
    });

    $this->info("{$role} account created or updated securely.");
    $this->info('No plain-text password was stored or displayed.');

    return self::SUCCESS;
})->purpose('Securely create or reset a registrar or records officer account');

Artisan::command('security:prune-login-attempts {--days=90}', function () {
    $days = max(1, (int) $this->option('days'));
    $deleted = LoginAttemptLog::query()
        ->where('created_at', '<', now()->subDays($days))
        ->delete();

    $this->info("Deleted {$deleted} expired login audit records.");

    return self::SUCCESS;
})->purpose('Delete login audit records older than the retention period');

Schedule::command('security:prune-login-attempts --days=90')
    ->daily()
    ->withoutOverlapping();

Artisan::command('security:prune-student-activations', function () {
    $deleted = StudentAccountActivation::query()
        ->where('expires_at', '<', now())
        ->orWhere(function ($query): void {
            $query->whereNotNull('used_at')
                ->where('used_at', '<', now()->subDay());
        })
        ->delete();

    $this->info("Deleted {$deleted} expired or used student activation records.");

    return self::SUCCESS;
})->purpose('Delete expired and previously used student account activation records');

Schedule::command('security:prune-student-activations')
    ->daily()
    ->withoutOverlapping();

Artisan::command('documents:generate-signing-certificate {--force}', function () {
    $certificatePath = (string) config('pdf_signing.certificate_path');
    $privateKeyPath = (string) config('pdf_signing.private_key_path');
    $password = (string) config('pdf_signing.private_key_password');

    if (! $this->option('force') && (is_file($certificatePath) || is_file($privateKeyPath))) {
        $this->error('Signing files already exist. Use --force only when intentionally rotating the certificate.');

        return self::FAILURE;
    }

    File::ensureDirectoryExists(dirname($certificatePath), 0700, true);
    File::ensureDirectoryExists(dirname($privateKeyPath), 0700, true);

    $opensslOptions = [
        'private_key_bits' => 3072,
        'private_key_type' => OPENSSL_KEYTYPE_RSA,
        'digest_alg' => 'sha256',
    ];
    if (filled(config('pdf_signing.openssl_config_path'))) {
        $opensslOptions['config'] = (string) config('pdf_signing.openssl_config_path');
    }

    $key = openssl_pkey_new($opensslOptions);
    if ($key === false) {
        throw new RuntimeException('OpenSSL could not generate the private key.');
    }

    $issuer = (string) config('document_verification.issuer');
    $csr = openssl_csr_new([
        'commonName' => (string) config('pdf_signing.signer_name'),
        'organizationName' => $issuer,
        'organizationalUnitName' => 'Document Issuing System',
    ], $key, $opensslOptions);
    $certificate = $csr === false ? false : openssl_csr_sign($csr, null, $key, 3650, $opensslOptions);

    if ($certificate === false
        || ! openssl_pkey_export($key, $privateKeyPem, $password, $opensslOptions)
        || ! openssl_x509_export($certificate, $certificatePem)) {
        throw new RuntimeException('OpenSSL could not export the signing certificate.');
    }

    File::put($privateKeyPath, $privateKeyPem);
    File::put($certificatePath, $certificatePem);
    @chmod($privateKeyPath, 0600);
    @chmod($certificatePath, 0644);

    $this->info('Development X.509 certificate created in protected private storage.');
    $this->line('Certificate SHA-256: '.strtoupper(openssl_x509_fingerprint($certificate, 'sha256')));
    $this->warn('Back up the key securely. Do not commit it, expose it through public/, or rotate it without an issuance-key migration plan.');

    return self::SUCCESS;
})->purpose('Generate a self-signed development certificate for official PDF signing');
