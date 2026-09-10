<?php

namespace App\Console\Commands;

use App\Models\Student;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CleanupUatAccounts extends Command
{
    protected $signature = 'uat:cleanup {run_id} {--confirm : Confirm permanent deletion of only this UAT run}';

    protected $description = 'Delete accounts and cascaded test records belonging to one identified UAT run';

    public function handle(): int
    {
        if (! in_array(config('app.env'), ['local', 'testing'], true)) {
            $this->error('UAT cleanup is disabled outside local/testing environments.');
            return self::FAILURE;
        }
        if (! $this->option('confirm')) {
            $this->error('No changes made. Re-run with --confirm after checking the run ID.');
            return self::FAILURE;
        }

        $runId = (string) $this->argument('run_id');
        if (! preg_match('/^\d{14}[A-Z0-9]{4}$/', $runId)) {
            $this->error('Invalid UAT run ID. No changes made.');
            return self::FAILURE;
        }

        $deleted = DB::transaction(function () use ($runId): array {
            $users = User::where('email', 'like', "uat+%.{$runId}@example.test")->delete();
            $students = Student::where('student_number', 'UAT-'.$runId)->delete();
            return [$users, $students];
        });

        $this->info("Removed {$deleted[0]} UAT user(s) and {$deleted[1]} UAT student(s) for run {$runId}.");
        return self::SUCCESS;
    }
}
