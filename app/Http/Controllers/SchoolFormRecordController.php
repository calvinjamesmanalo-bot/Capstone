<?php

namespace App\Http\Controllers;

use App\Models\SchoolFormUpload as GradeSheetUpload;
use App\Support\XlsxWorkbookReader;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class SchoolFormRecordController extends Controller
{
    public function index(Request $request)
    {
        $this->authorizeRecordsStaff();
        $schoolYear = trim((string) $request->query('school_year'));
        $level = trim((string) $request->query('level'));
        $section = trim((string) $request->query('section'));
        $hasSearch = $schoolYear !== '' && $level !== '' && $section !== '';

        $schoolYears = GradeSheetUpload::distinct()->orderByDesc('school_year')->pluck('school_year');
        $levels = GradeSheetUpload::distinct()->orderBy('level')->pluck('level');
        $sections = GradeSheetUpload::distinct()->orderBy('section')->pluck('section');
        $uploads = GradeSheetUpload::query()
            ->when(! $hasSearch, fn ($query) => $query->whereRaw('1 = 0'))
            ->when($hasSearch, fn ($query) => $query->where('school_year', $schoolYear)
                ->where('level', $level)->where('section', $section))
            ->orderBy('grading_period')->orderBy('file_type')->get();
        $attendanceUploads = $uploads->where('file_type', 'attendance')->values();
        $summaryUploads = $uploads->where('file_type', 'summary')->values();

        return view('school-forms.records', compact(
            'schoolYear', 'level', 'section', 'hasSearch', 'schoolYears', 'levels', 'sections',
            'uploads', 'attendanceUploads', 'summaryUploads'
        ));
    }

    public function destroy(GradeSheetUpload $upload)
    {
        $this->authorizeGradeStaff();
        DB::connection('school_forms')->transaction(function () use ($upload) {
            $database = DB::connection('school_forms');
            $enrollmentIds = $database->table('student_enrollments')->where([
                'school_year' => $upload->school_year, 'level' => $upload->level, 'section' => $upload->section,
            ])->pluck('id');
            $table = $upload->file_type === 'summary' ? 'student_grades' : 'student_attendance';
            $database->table($table)->whereIn('student_enrollment_id', $enrollmentIds)
                ->where('grading_period', $upload->grading_period)->delete();
            Storage::disk('school_forms_local')->delete($upload->stored_path);
            $upload->delete();
        });

        return back()->with('status', 'Uploaded sheet and its imported records were deleted.');
    }

    public function download(GradeSheetUpload $upload)
    {
        $this->authorizeRecordsStaff();
        abort_unless(Storage::disk('school_forms_local')->exists($upload->stored_path), 404);

        return Storage::disk('school_forms_local')->download($upload->stored_path, $upload->original_name);
    }

    public function preview(GradeSheetUpload $upload, XlsxWorkbookReader $reader)
    {
        $this->authorizeRecordsStaff();
        abort_unless(Storage::disk('school_forms_local')->exists($upload->stored_path), 404);

        try {
            $workbook = $reader->read(Storage::disk('school_forms_local')->path($upload->stored_path));
        } catch (RuntimeException $exception) {
            report($exception);
            abort(422, 'This workbook could not be opened for preview. Download it and open it in Excel instead.');
        }

        $sheets = array_map(fn (array $sheet) => $this->previewSheet($sheet), $workbook);
        abort_if($sheets === [], 422, 'This workbook does not contain a readable worksheet.');

        return response()
            ->view('school-forms.partials.workbook-preview', compact('upload', 'sheets'))
            ->header('Cache-Control', 'private, no-store, max-age=0');
    }

    private function previewSheet(array $sheet): array
    {
        $allRows = $sheet['rows'] ?? [];
        $rows = array_slice($allRows, 0, 200);
        $largestColumn = 1;

        foreach ($rows as $row) {
            foreach ($row['cells'] ?? [] as $cell) {
                $largestColumn = max($largestColumn, $this->columnNumber((string) ($cell['column'] ?? 'A')));
            }
        }

        $largestColumn = min($largestColumn, 50);
        $columns = [];
        for ($column = 1; $column <= $largestColumn; $column++) {
            $columns[] = $this->columnName($column);
        }

        $previewRows = array_map(function (array $row) use ($largestColumn): array {
            $cells = [];
            foreach ($row['cells'] ?? [] as $cell) {
                $column = (string) ($cell['column'] ?? '');
                if ($column !== '' && $this->columnNumber($column) <= $largestColumn) {
                    $cells[$column] = (string) ($cell['value'] ?? '');
                }
            }

            return ['index' => (int) ($row['index'] ?? 0), 'cells' => $cells];
        }, $rows);

        return [
            'name' => (string) ($sheet['name'] ?? 'Sheet'),
            'columns' => $columns,
            'rows' => $previewRows,
            'truncated' => count($allRows) > count($rows),
        ];
    }

    private function columnNumber(string $column): int
    {
        $number = 0;
        foreach (str_split(strtoupper($column)) as $character) {
            if ($character < 'A' || $character > 'Z') {
                continue;
            }
            $number = ($number * 26) + (ord($character) - 64);
        }

        return max(1, $number);
    }

    private function columnName(int $number): string
    {
        $name = '';
        while ($number > 0) {
            $number--;
            $name = chr(65 + ($number % 26)).$name;
            $number = intdiv($number, 26);
        }

        return $name;
    }

    private function authorizeGradeStaff(): void
    {
        abort_unless(auth()->check() && in_array(auth()->user()->role, ['admin', 'registrar'], true), 403);
    }

    private function authorizeRecordsStaff(): void
    {
        abort_unless(auth()->check() && in_array(auth()->user()->role, ['admin', 'registrar', 'records_officer'], true), 403);
    }
}
