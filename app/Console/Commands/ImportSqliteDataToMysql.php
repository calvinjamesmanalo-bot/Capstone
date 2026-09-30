<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Throwable;

class ImportSqliteDataToMysql extends Command
{
    protected $signature = 'database:import-sqlite-to-mysql {--confirm : Confirm the empty MySQL databases may receive imported data}';

    protected $description = 'Copy both legacy SQLite databases into empty MySQL/MariaDB databases without modifying the SQLite sources';

    public function handle(): int
    {
        if (! $this->option('confirm')) {
            $this->error('Import not started. Pass --confirm after backing up both SQLite files.');

            return self::FAILURE;
        }

        $imports = [
            [
                'label' => 'application',
                'source' => 'sqlite_import_source',
                'source_path' => database_path('database.sqlite'),
                'target' => config('database.default'),
            ],
            [
                'label' => 'school forms',
                'source' => 'school_forms_sqlite_import_source',
                'source_path' => base_path('generator/database/database.sqlite'),
                'target' => 'school_forms',
            ],
        ];

        try {
            foreach ($imports as $import) {
                $this->importDatabase(...$import);
            }
        } catch (Throwable $exception) {
            $this->newLine();
            $this->error($exception->getMessage());
            $this->warn('The SQLite source databases were not modified.');

            return self::FAILURE;
        } finally {
            DB::purge('sqlite_import_source');
            DB::purge('school_forms_sqlite_import_source');
        }

        $this->newLine();
        $this->info('Both SQLite databases were copied and all table row counts were verified.');
        $this->info('The SQLite source files remain unchanged.');

        return self::SUCCESS;
    }

    private function importDatabase(string $label, string $source, string $source_path, string $target): void
    {
        if (! is_file($source_path) || ! is_readable($source_path)) {
            throw new RuntimeException("The {$label} SQLite source file is missing or unreadable.");
        }

        config()->set("database.connections.{$source}", [
            'driver' => 'sqlite',
            'database' => $source_path,
            'prefix' => '',
            'foreign_key_constraints' => true,
        ]);

        DB::purge($source);
        $sourceConnection = DB::connection($source);
        $targetConnection = DB::connection($target);

        if ($sourceConnection->getDriverName() !== 'sqlite') {
            throw new RuntimeException("The {$label} import source is not SQLite.");
        }

        if (! in_array($targetConnection->getDriverName(), ['mysql', 'mariadb'], true)) {
            throw new RuntimeException("The {$label} target must use MySQL or MariaDB.");
        }

        $tables = collect($sourceConnection->select(
            "SELECT name FROM sqlite_master WHERE type = 'table' AND name NOT LIKE 'sqlite_%' ORDER BY name"
        ))
            ->pluck('name')
            ->reject(fn (string $table): bool => $table === 'migrations')
            ->values();

        foreach ($tables as $table) {
            if (! Schema::connection($target)->hasTable($table)) {
                throw new RuntimeException("Target table {$table} is missing from the {$label} database.");
            }

            if ($targetConnection->table($table)->count() !== 0) {
                throw new RuntimeException("Target table {$table} in the {$label} database is not empty; import refused.");
            }
        }

        $this->info('Importing '.$label.' database...');
        $targetConnection->statement('SET FOREIGN_KEY_CHECKS=0');

        try {
            $targetConnection->transaction(function () use ($sourceConnection, $targetConnection, $tables, $label): void {
                foreach ($tables as $table) {
                    $rows = $sourceConnection->table($table)->get();

                    foreach ($rows->chunk(250) as $chunk) {
                        $targetConnection->table($table)->insert(
                            $chunk->map(fn (object $row): array => (array) $row)->all()
                        );
                    }

                    $sourceCount = $rows->count();
                    $targetCount = $targetConnection->table($table)->count();

                    if ($sourceCount !== $targetCount) {
                        throw new RuntimeException("Row-count verification failed for {$label}.{$table}.");
                    }

                    $this->line("  {$table}: {$targetCount} row(s)");
                }
            });
        } finally {
            $targetConnection->statement('SET FOREIGN_KEY_CHECKS=1');
        }
    }
}
