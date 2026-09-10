<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class RunUatPreflight extends Command
{
    protected $signature = 'uat:preflight {--with-tests : Include the complete automated test suite}';

    protected $description = 'Run health, production configuration, backup integrity, and optional automated-test checks before UAT';

    public function handle(): int
    {
        $results = [];
        $results[] = $this->runCheck('System health', 'system:health');
        $results[] = $this->runCheck('Production readiness', 'production:check');

        $backups = glob(storage_path('app/backups/*.zip')) ?: [];
        usort($backups, fn (string $a, string $b) => filemtime($b) <=> filemtime($a));
        $results[] = $backups === []
            ? ['FAIL', 'Latest backup integrity', 'No backup archive found']
            : $this->runCheck('Latest backup integrity', 'backup:restore-test', ['file' => $backups[0]]);

        if ($this->option('with-tests')) {
            $results[] = $this->runCheck('Automated test suite', 'test');
        } else {
            $results[] = ['WARNING', 'Automated test suite', 'Skipped; use --with-tests before tester handoff'];
        }

        $this->table(['Result', 'Check', 'Details'], $results);
        $failed = count(array_filter($results, fn (array $row) => $row[0] === 'FAIL'));
        $this->line($failed === 0 ? 'UAT preflight completed without blocking failures.' : "UAT preflight has {$failed} blocking failure(s).");

        return $failed === 0 ? self::SUCCESS : self::FAILURE;
    }

    private function runCheck(string $label, string $command, array $arguments = []): array
    {
        $exit = Artisan::call($command, $arguments);
        $output = trim(Artisan::output());
        return [$exit === self::SUCCESS ? 'PASS' : 'FAIL', $label, $exit === self::SUCCESS ? 'Completed successfully' : $this->lastLine($output)];
    }

    private function lastLine(string $output): string
    {
        $lines = array_values(array_filter(array_map('trim', preg_split('/\R/', $output) ?: [])));
        return end($lines) ?: 'Check failed';
    }
}
