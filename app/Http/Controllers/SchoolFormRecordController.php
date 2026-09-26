<?php

namespace App\Http\Controllers;

use App\Models\SchoolFormUpload as GradeSheetUpload;
use App\Models\SchoolFormStudent;
use App\Support\AcademicPeriod;
use App\Support\CurriculumSubjects;
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
        $request->validate(['search_student' => ['nullable', 'string', 'max:100'], 'student_id' => ['nullable', 'integer', 'min:1']]);
        $schoolYear = trim((string) $request->query('school_year'));
        $level = trim((string) $request->query('level'));
        $section = trim((string) $request->query('section'));
        $hasSearch = $schoolYear !== '' && $level !== '' && $section !== '';
        $studentSearch = trim((string) $request->query('search_student', ''));
        $matchingStudents = collect();
        $selectedStudent = null;
        if ($studentSearch !== '') {
            $term = '%'.addcslashes($studentSearch, '%_\\').'%';
            $matchingStudents = SchoolFormStudent::query()
                ->where(function ($query) use ($term) {
                    $query->where('student_number', 'like', $term)
                        ->orWhere('lrn', 'like', $term)
                        ->orWhere('name', 'like', $term);
                })
                ->orderBy('name')->limit(25)->get();
            $selectedStudent = $matchingStudents->firstWhere('id', (int) $request->query('student_id'));
            if (! $selectedStudent && $matchingStudents->count() === 1) {
                $selectedStudent = $matchingStudents->first();
            }
            $selectedStudent?->load(['enrollments' => fn ($query) => $query->orderByDesc('school_year'), 'enrollments.grades']);
        }

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
            'uploads', 'attendanceUploads', 'summaryUploads', 'studentSearch', 'matchingStudents', 'selectedStudent'
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

    public function destroySchoolYear(Request $request)
    {
        $this->authorizeGradeStaff();
        $validated = $request->validate([
            'delete_school_year' => ['required', Rule::in(config('academics.school_years', []))],
            'school_year_confirmation' => ['required', 'string'],
        ], [], [
            'delete_school_year' => 'school year',
            'school_year_confirmation' => 'confirmation',
        ]);
        $schoolYear = $validated['delete_school_year'];
        if (! hash_equals($schoolYear, trim($validated['school_year_confirmation']))) {
            return back()->withErrors([
                'school_year_confirmation' => "Type {$schoolYear} exactly to confirm deletion.",
            ])->withInput();
        }

        $uploads = GradeSheetUpload::query()->where('school_year', $schoolYear)->get();
        $paths = $uploads->pluck('stored_path')->filter()->unique()->values()->all();
        $deleted = DB::connection('school_forms')->transaction(function () use ($schoolYear): array {
            $database = DB::connection('school_forms');
            $enrollmentIds = $database->table('student_enrollments')
                ->where('school_year', $schoolYear)->pluck('id');
            $gradeCount = $database->table('student_grades')->whereIn('student_enrollment_id', $enrollmentIds)->delete();
            $attendanceCount = $database->table('student_attendance')->whereIn('student_enrollment_id', $enrollmentIds)->delete();
            $enrollmentCount = $database->table('student_enrollments')->whereIn('id', $enrollmentIds)->delete();
            $uploadCount = $database->table('grade_sheet_uploads')->where('school_year', $schoolYear)->delete();

            return compact('gradeCount', 'attendanceCount', 'enrollmentCount', 'uploadCount');
        });

        Storage::disk('school_forms_local')->delete($paths);
        record_log(
            'Deleted School Year Data',
            'Grade Portal',
            "Deleted {$schoolYear}: {$deleted['uploadCount']} uploads, {$deleted['enrollmentCount']} enrollments, {$deleted['gradeCount']} grades, {$deleted['attendanceCount']} attendance records."
        );

        return redirect()->route('school-forms.records')->with(
            'status',
            "{$schoolYear} data removed: {$deleted['uploadCount']} workbook(s) and {$deleted['enrollmentCount']} enrollment(s). Student identities and other school years were kept."
        );
    }

    public function status(Request $request)
    {
        $this->authorizeRecordsStaff();
        $validated = $request->validate([
            'school_year' => ['required', Rule::in(config('academics.school_years', []))],
            'level' => ['required', Rule::in(config('academics.grade_levels', []))],
            'section' => ['required', Rule::in(CurriculumSubjects::sections())],
        ]);

        $uploads = GradeSheetUpload::query()
            ->where('school_year', $validated['school_year'])
            ->where('level', $validated['level'])
            ->where('section', $validated['section'])
            ->whereIn('grading_period', AcademicPeriod::numbers($validated['school_year'], $validated['level']))
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
            'total' => AcademicPeriod::count($validated['school_year'], $validated['level']) * 2,
        ]);
    }

    public function template(Request $request, string $type)
    {
        $this->authorizeRecordsStaff();
        abort_unless(in_array($type, ['summary', 'attendance'], true), 404);
        $validated = $request->validate([
            'school_year' => ['required', Rule::in(config('academics.school_years', []))],
            'level' => ['required', Rule::in(config('academics.grade_levels', []))],
            'section' => ['required', Rule::in(CurriculumSubjects::sections())],
            'period' => ['required', 'integer', Rule::in(AcademicPeriod::numbers((string) $request->input('school_year'), (string) $request->input('level')))],
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
        return $this->makePortalTemplate($type, $details);

        $period = (int) $details['period'];
        $periodName = strtoupper(['First', 'Second', 'Third', 'Fourth'][$period - 1]);
        $periodType = AcademicPeriod::usesTerms($details['school_year'], $details['level']) ? 'term' : 'grading';
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle($type === 'summary' ? 'Summary Sheet' : 'Attendance Sheet');
        $sheet->setCellValue('A1', strtoupper("{$details['level']} {$details['section']} {$type} sheet - {$periodName} {$periodType} - Academic Year {$details['school_year']}"));
        $sheet->setCellValue('A2', 'Adviser / Teacher:');

        if ($type === 'summary') {
            $subjects = CurriculumSubjects::for($details['level'], $details['section']);
            $headers = array_merge(['No.', 'Student No.', 'Learner name'], $subjects, ['General Average']);
            $sheet->fromArray($headers, null, 'A3');
            $sheet->freezePane('D4');
            $sheet->getColumnDimension('B')->setWidth(18);
            $sheet->getColumnDimension('C')->setWidth(32);
            $lastColumn = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(count($headers));
            foreach (range('D', $lastColumn) as $column) {
                $sheet->getColumnDimension($column)->setWidth(18);
            }
        } else {
            $months = AcademicPeriod::usesTerms($details['school_year'], $details['level'])
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

        $lastColumn = $type === 'summary'
            ? \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(count($headers))
            : chr(67 + count($months));
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

    private function makePortalTemplate(string $type, array $details): Spreadsheet
    {
        $period = (int) $details['period'];
        $periodName = strtoupper(['First', 'Second', 'Third', 'Fourth'][$period - 1]);
        $periodType = AcademicPeriod::usesTerms($details['school_year'], $details['level']) ? 'term' : 'grading';
        $periodHeading = $periodName.' '.strtoupper($periodType);
        $levelHeading = $this->levelHeading($details['level']).' - '.$details['section'];
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle($type === 'summary' ? 'Summary Sheet' : 'Attendance Sheet');

        if ($type === 'summary') {
            $subjects = CurriculumSubjects::for($details['level'], $details['section']);
            $headers = array_merge(['No.', 'LRN', "Student's Name"], array_map('strtoupper', $subjects), ['GEN. AVE.']);
            $lastColumn = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(count($headers));
            $sheet->setCellValue('A2', 'SUMMARY SHEET')->mergeCells("A2:{$lastColumn}2");
            $sheet->setCellValue('A3', "Academic Year {$details['school_year']}")->mergeCells('A3:E3');
            $sheet->setCellValue('F3', 'TEACHER-IN-CHARGE')->mergeCells('F3:G3');
            $sheet->setCellValue('H3', '')->mergeCells("H3:{$lastColumn}3");
            $sheet->setCellValue('A4', $periodHeading)->mergeCells('A4:E4');
            $sheet->setCellValue('F4', 'LEVEL AND SECTION')->mergeCells('F4:G4');
            $sheet->setCellValue('H4', $levelHeading)->mergeCells("H4:{$lastColumn}4");
            $sheet->fromArray($headers, null, 'A6');
            $sheet->getColumnDimension('B')->setWidth(16);
            $sheet->getColumnDimension('C')->setWidth(27);
            foreach (range('D', $lastColumn) as $column) {
                $sheet->getColumnDimension($column)->setWidth($column === $lastColumn ? 11 : 16);
            }
            $headerRow = 6;
            $firstStudentRow = 7;
        } else {
            $months = AcademicPeriod::usesTerms($details['school_year'], $details['level'])
                ? [1 => ['June', 'July', 'August', 'September'], 2 => ['October', 'November', 'December', 'January'], 3 => ['February', 'March', 'April']][$period]
                : [1 => ['June', 'July', 'August'], 2 => ['September', 'October', 'November'], 3 => ['December', 'January', 'February'], 4 => ['March', 'April', 'May']][$period];
            $lastMonthColumn = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(3 + count($months));
            $lastColumn = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(4 + count($months));
            $sheet->setCellValue('A2', 'ATTENDANCE SHEET')->mergeCells("A2:{$lastColumn}2");
            $sheet->setCellValue('A3', "Academic Year {$details['school_year']}")->mergeCells("A3:{$lastColumn}3");
            $sheet->setCellValue('A4', 'LEVEL AND SECTION:')->mergeCells('A4:B4');
            $sheet->setCellValue('C4', $levelHeading)->mergeCells('C4:D4');
            $sheet->setCellValue('E4', 'TEACHER-IN-CHARGE')->mergeCells('E4:F4');
            $sheet->setCellValue('G4', '');
            $sheet->setCellValue('A5', 'No.')->setCellValue('B5', 'LRN')->setCellValue('C5', "Student's Name");
            $sheet->setCellValue('D5', $periodHeading)->mergeCells("D5:{$lastMonthColumn}5");
            $sheet->setCellValue("{$lastColumn}5", 'Total');
            foreach ($months as $index => $month) {
                $column = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(4 + $index);
                $sheet->setCellValue("{$column}6", strtoupper($month));
            }
            $sheet->getColumnDimension('B')->setWidth(16);
            $sheet->getColumnDimension('C')->setWidth(27);
            foreach (range('D', $lastMonthColumn) as $column) {
                $sheet->getColumnDimension($column)->setWidth(13);
            }
            $sheet->getColumnDimension($lastColumn)->setWidth(10);
            $headerRow = 5;
            $firstStudentRow = 8;
        }

        $sheet->getColumnDimension('A')->setWidth(6);
        $sheet->getStyle("A1:{$lastColumn}102")->getFont()->setName('Carlito')->setSize(11);
        $sheet->getStyle("A2:{$lastColumn}4")->getFont()->setBold(true);
        $sheet->getStyle("A2:{$lastColumn}2")->getFont()->setSize(14);
        $sheet->getStyle("A2:{$lastColumn}2")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT)->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getStyle("A{$headerRow}:{$lastColumn}".($firstStudentRow + 94))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getStyle("A{$headerRow}:{$lastColumn}{$headerRow}")->getFont()->setBold(true);
        $sheet->getStyle("A{$headerRow}:{$lastColumn}".($firstStudentRow + 94))->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB('000000');
        $sheet->getStyle("A{$headerRow}:{$lastColumn}{$headerRow}")->getAlignment()->setWrapText(true);
        $sheet->getStyle("C{$firstStudentRow}:C".($firstStudentRow + 94))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);

        return $spreadsheet;
    }

    private function levelHeading(string $level): string
    {
        $words = [1 => 'One', 2 => 'Two', 3 => 'Three', 4 => 'Four', 5 => 'Five', 6 => 'Six', 7 => 'Seven', 8 => 'Eight', 9 => 'Nine', 10 => 'Ten', 11 => 'Eleven', 12 => 'Twelve'];
        if (strcasecmp($level, 'Kinder') === 0) {
            return 'Kindergarten';
        }
        $grade = (int) filter_var($level, FILTER_SANITIZE_NUMBER_INT);

        return 'Grade '.($words[$grade] ?? $grade);
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
        abort_unless(auth()->check() && in_array(auth()->user()->role, ['admin', 'registrar'], true), 403);
    }
}
