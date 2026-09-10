<?php

namespace App\Console\Commands;

use App\Models\SchoolFormUpload;
use App\Support\GradeSheetRecordImporter;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Throwable;

class ReimportSchoolFormUploads extends Command
{
    protected $signature = 'school-forms:reimport {--school-year= : Only rebuild uploads for this school year}';

    protected $description = 'Rebuild grade and attendance records from stored school-form workbooks';

    public function handle(GradeSheetRecordImporter $importer): int
    {
        $query = SchoolFormUpload::query()->orderBy('grading_period')->orderBy('file_type');
        if ($schoolYear = $this->option('school-year')) {
            $query->where('school_year', $schoolYear);
        }

        $processed = 0;
        $failed = 0;
        foreach ($query->get() as $upload) {
            if (! Storage::disk('school_forms_local')->exists($upload->stored_path)) {
                $this->error("Upload #{$upload->id} is missing: {$upload->stored_path}");
                $failed++;

                continue;
            }

            try {
                DB::connection('school_forms')->transaction(fn () => $importer->import(
                    Storage::disk('school_forms_local')->path($upload->stored_path),
                    $upload->file_type,
                    (int) $upload->grading_period,
                    $upload->school_year,
                    $upload->level,
                    $upload->section,
                ));
                $processed++;
                $this->line("Rebuilt upload #{$upload->id}: {$upload->original_name}");
            } catch (Throwable $exception) {
                $this->error("Upload #{$upload->id} failed: {$exception->getMessage()}");
                $failed++;
            }
        }

        $this->info("Reimport complete: {$processed} rebuilt, {$failed} failed.");

        return $failed === 0 ? self::SUCCESS : self::FAILURE;
    }
}
