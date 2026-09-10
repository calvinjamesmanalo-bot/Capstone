<?php

namespace App\Console\Commands;

use App\Models\Student;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class PrepareUatAccounts extends Command
{
    protected $signature = 'uat:prepare';

    protected $description = 'Create isolated local UAT accounts and print one-time test credentials';

    public function handle(): int
    {
        if (! in_array(config('app.env'), ['local', 'testing'], true)) {
            $this->error('UAT dummy accounts can only be generated in local or testing environments.');
            return self::FAILURE;
        }

        $runId = now()->format('YmdHis').Str::upper(Str::random(4));
        $rows = [];
        foreach (['student', 'records_officer', 'registrar', 'admin'] as $role) {
            $password = Str::password(16, true, true, false, false);
            $email = "uat+{$role}.{$runId}@example.test";
            $studentNumber = $role === 'student' ? 'UAT-'.$runId : null;

            if ($studentNumber !== null) {
                Student::create([
                    'student_number' => $studentNumber,
                    'name' => 'UAT Test Student',
                    'official_email' => $email,
                ]);
            }

            User::create([
                'name' => 'UAT '.str_replace('_', ' ', Str::title($role)),
                'email' => $email,
                'email_verified_at' => now(),
                'password' => Hash::make($password),
                'role' => $role,
                'student_number' => $studentNumber,
            ]);

            $rows[] = [$role, $role === 'student' ? $studentNumber : $email, $password];
        }

        $this->info("UAT run created: {$runId}");
        $this->table(['Role', 'Login identifier', 'One-time test password'], $rows);
        $this->warn('Copy these credentials into your private testing handoff. They are not stored in plain text and cannot be shown again.');
        $this->line("Cleanup later: php artisan uat:cleanup {$runId} --confirm");

        return self::SUCCESS;
    }
}
