<?php

namespace App\Http\Controllers;

use App\Models\SchoolFormUpload as GradeSheetUpload;
use App\Support\AcademicPeriod;
use App\Support\XlsxWorkbookReader;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
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

        $schoolYears = collect(config('academics.school_years', []))
            ->merge(GradeSheetUpload::distinct()->pluck('school_year'))
            ->filter()
            ->unique()
            ->sortDesc()
            ->values();
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

    public function status(Request $request)
    {
        $this->authorizeRecordsStaff();
        $validated = $request->validate([
            'school_year' => ['required', Rule::in(config('academics.school_years', []))],
            'level' => ['required', Rule::in(config('academics.grade_levels', []))],
            'section' => ['required', Rule::in(['Bambi'])],
        ]);

        $uploads = GradeSheetUpload::query()
            ->where('school_year', $validated['school_year'])
            ->where('level', $validated['level'])
            ->where('section', $validated['section'])
            ->whereIn('grading_period', AcademicPeriod::numbers($validated['school_year']))
            ->get();
        $slots = [];
        foreach ($uploads as $upload) {
            $slots["{$upload->grading_period}:{$upload->file_type}"] = [
                'name' => $upload->original_name,
                'updated_at' => optional($upload->updated_at)->toIso8601String(),
            ];
        }

        return response()->json([
            'slots' => $slots,
            'completed' => count($slots),
            'total' => AcademicPeriod::count($validated['school_year']) * 2,
        ]);
    }

    public function template(Request $request, string $type)
    {
        $this->authorizeRecordsStaff();
        abort_unless(in_array($type, ['summary', 'attendance'], true), 404);
        $validated = $request->validate([
            'school_year' => ['required', Rule::in(config('academics.school_years', []))],
            'level' => ['required', Rule::in(config('academics.grade_levels', []))],
            'section' => ['required', Rule::in(['Bambi'])],
            'period' => ['required', 'integer', Rule::in(AcademicPeriod::numbers((string) $request->input('school_year')))],
        ]);

        $spreadsheet = $this->makeTemplate($type, $validated);
        $periodName = ['First', 'Second', 'Third', 'Fourth'][(int) $validated['period'] - 1];
        $filename = str_replace(' ', '-', strtolower("{$validated['level']}-{$validated['section']}-{$periodName}-{$type}.xlsx"));

        return response()->streamDownload(function () use ($spreadsheet) {
            (new Xlsx($spreadsheet))->save('php://output');
            $spreadsheet->disconnectWorksheets();
        }, $filename, ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']);
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

    private function makeTemplate(string $type, array $details): Spreadsheet
    {
        $period = (int) $details['period'];
        $periodName = strtoupper(['First', 'Second', 'Third', 'Fourth'][$period - 1]);
        $periodType = AcademicPeriod::usesTerms($details['school_year']) ? 'term' : 'grading';
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle($type === 'summary' ? 'Summary Sheet' : 'Attendance Sheet');
        $sheet->setCellValue('A1', strtoupper("{$details['level']} {$details['section']} {$type} sheet - {$periodName} {$periodType} - Academic Year {$details['school_year']}"));
        $sheet->setCellValue('A2', 'Adviser / Teacher:');

        if ($type === 'summary') {
            $headers = ['No.', 'Student No.', 'Learner name', 'Language', 'Reading and Literacy', 'Mathematics', 'Makabansa', 'Good Manners and Right Conduct', 'MAPE', 'Music', 'P.E.', 'Art', 'Mother Tongue I', 'General Average'];
            $sheet->fromArray($headers, null, 'A3');
            $sheet->freezePane('D4');
            $sheet->getColumnDimension('B')->setWidth(18);
            $sheet->getColumnDimension('C')->setWidth(32);
            foreach (range('D', 'N') as $column) {
                $sheet->getColumnDimension($column)->setWidth(18);
            }
        } else {
            $months = AcademicPeriod::usesTerms($details['school_year'])
                ? [
                    1 => ['June', 'July', 'August', 'September'],
                    2 => ['October', 'November', 'December', 'January'],
                    3 => ['February', 'March', 'April'],
                ][$period]
                : [
                    1 => ['June', 'July', 'August'],
                    2 => ['September', 'October', 'November'],
                    3 => ['December', 'January', 'February'],
                    4 => ['March', 'April', 'May'],
                ][$period];
            $headers = array_merge(['No.', 'LRN', 'Learner name'], $months);
            $sheet->fromArray($headers, null, 'A3');
            $sheet->setCellValue('C4', 'Days of School');
            $sheet->freezePane('D5');
            $sheet->getColumnDimension('B')->setWidth(20);
            $sheet->getColumnDimension('C')->setWidth(32);
            foreach (range('D', chr(67 + count($months))) as $column) {
                $sheet->getColumnDimension($column)->setWidth(16);
            }
        }

        $lastColumn = $type === 'summary' ? 'N' : chr(67 + count($months));
        $sheet->mergeCells("A1:{$lastColumn}1");
        $sheet->getRowDimension(1)->setRowHeight(28);
        $sheet->getStyle("A1:{$lastColumn}1")->getFont()->setBold(true)->setSize(12)->getColor()->setRGB('FFFFFF');
        $sheet->getStyle("A1:{$lastColumn}1")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('000638');
        $sheet->getStyle("A1:{$lastColumn}1")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("A3:{$lastColumn}3")->getFont()->setBold(true);
        $sheet->getStyle("A3:{$lastColumn}3")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('FFD22D');
        $sheet->getStyle("A3:{$lastColumn}100")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB('CBD5E1');
        $sheet->getColumnDimension('A')->setWidth(8);

        $instructions = $spreadsheet->createSheet();
        $instructions->setTitle('Instructions');
        $instructions->fromArray([
            ['How to use this template'],
            ["1. Keep the title row; it is used to validate school year, grade, and {$periodType} period."],
            ['2. Enter one learner per row. Do not merge learner rows.'],
            [$type === 'summary' ? '3. Subject columns are dynamic: rename, add, or remove subject columns as needed.' : '3. Enter school days on row 4 and each learner’s days present below it.'],
            ['4. Save as .xlsx, then upload it to the matching slot.'],
        ], null, 'A1');
        $instructions->getColumnDimension('A')->setWidth(110);
        $instructions->getStyle('A1')->getFont()->setBold(true)->setSize(14);

        return $spreadsheet;
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
