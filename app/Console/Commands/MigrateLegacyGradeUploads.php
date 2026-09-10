<?php

namespace App\Console\Commands;

use App\Models\Form138Upload;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

class MigrateLegacyGradeUploads extends Command
{
    protected $signature = 'grades:migrate-private {--dry-run : Report legacy uploads without copying or updating them}';

    protected $description = 'Copy legacy public Grade Portal uploads to private storage and verify them without deleting the originals';

    public function handle(): int
    {
        $counts = ['migrated' => 0, 'missing' => 0, 'failed' => 0, 'skipped' => 0];
        $dryRun = (bool) $this->option('dry-run');

        Form138Upload::query()->orderBy('id')->chunkById(100, function ($uploads) use (&$counts, $dryRun): void {
            foreach ($uploads as $upload) {
                if ($upload->storage_disk === 'local') {
                    $counts['skipped']++;
                    continue;
                }

                if ($upload->storage_disk !== null && $upload->storage_disk !== 'public') {
                    $this->warn("Upload #{$upload->id}: unsupported disk '{$upload->storage_disk}'; skipped.");
                    $counts['failed']++;
                    continue;
                }

                $sourcePath = (string) $upload->file_path;
                if ($sourcePath === '' || ! Storage::disk('public')->exists($sourcePath)) {
                    $this->warn("Upload #{$upload->id}: public source is missing.");
                    $counts['missing']++;
                    continue;
                }

                if ($dryRun) {
                    $this->line("Upload #{$upload->id}: ready to migrate {$sourcePath}");
                    $counts['migrated']++;
                    continue;
                }

                $extension = strtolower(pathinfo($sourcePath, PATHINFO_EXTENSION));
                $targetPath = sprintf(
                    'form138_uploads/%s/%s/%s%s',
                    $this->safeSegment((string) $upload->student_number),
                    $this->safeSegment((string) $upload->school_year),
                    Str::uuid(),
                    $extension !== '' ? '.'.$extension : ''
                );

                try {
                    $sourceHash = hash_file('sha256', Storage::disk('public')->path($sourcePath));
                    $sourceSize = Storage::disk('public')->size($sourcePath);
                    $stream = Storage::disk('public')->readStream($sourcePath);

                    if ($stream === null || ! Storage::disk('local')->writeStream($targetPath, $stream)) {
                        throw new \RuntimeException('The private copy could not be written.');
                    }
                    if (is_resource($stream)) {
                        fclose($stream);
                    }

                    $targetHash = hash_file('sha256', Storage::disk('local')->path($targetPath));
                    $targetSize = Storage::disk('local')->size($targetPath);
                    if (! hash_equals($sourceHash, $targetHash) || $sourceSize !== $targetSize) {
                        Storage::disk('local')->delete($targetPath);
                        throw new \RuntimeException('Hash or file-size verification failed.');
                    }

                    $upload->forceFill([
                        'file_path' => $targetPath,
                        'pdf_path' => $upload->pdf_path === $sourcePath ? $targetPath : $upload->pdf_path,
                        'storage_disk' => 'local',
                        'mime_type' => Storage::disk('local')->mimeType($targetPath) ?: null,
                        'file_size' => $targetSize,
                        'sha256' => $targetHash,
                    ])->save();

                    $counts['migrated']++;
                } catch (Throwable $exception) {
                    if (Storage::disk('local')->exists($targetPath)) {
                        Storage::disk('local')->delete($targetPath);
                    }
                    $this->error("Upload #{$upload->id}: {$exception->getMessage()}");
                    $counts['failed']++;
                }
            }
        });

        $this->newLine();
        $this->table(['Status', 'Count'], collect($counts)->map(fn ($count, $status) => [$status, $count])->values());
        $this->info($dryRun
            ? 'Dry run complete. No files or database rows were changed.'
            : 'Copy-and-verify complete. Original public files were not deleted.');

        return self::SUCCESS;
    }

    private function safeSegment(string $value): string
    {
        $clean = preg_replace('/[^A-Za-z0-9_-]/', '_', $value);

        return trim((string) $clean, '_') ?: 'unknown';
    }
}
