<?php

namespace App\Http\Controllers;

use App\Models\Form138Upload;
use App\Models\Grade;
use App\Models\RequestDocument;
use App\Models\Student;
use App\Support\DocumentQrCode;
use App\Support\DocumentWorkbookVerification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Html;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class Form137Controller extends Controller
{
    public function index(Request $request)
    {
        $students = Student::orderBy('name')->get();
        $student = null;
        $uploads = [];
        $existingGrades = [];

        if ($request->has('student_number')) {
            $student = Student::where('student_number', $request->student_number)->first();
            if ($student) {
                $uploads = Form138Upload::where('student_number', $student->student_number)
                    ->orderBy('school_year', 'desc')
                    ->get();

                $existingGrades = Grade::where('student_number', $student->student_number)
                    ->orderBy('school_year')
                    ->get()
                    ->groupBy('school_year');
            }
        }

        return view('form-137.index', compact('student', 'uploads', 'existingGrades', 'students'));
    }

    public function previewManual(Request $request)
    {
        $request->validate([
            'student_number' => 'required|string',
            'data' => 'required|array',
            'data.*.school_year' => 'required|string',
            'data.*.grade_level' => 'required|string',
            'data.*.subjects' => 'required|array',
            'data.*.subjects.*' => 'required|string',
        ]);

        // Store encoded data in session temporarily for preview/download
        session(['temp_encoded_grades' => [
            'student_number' => $request->student_number,
            'data' => $request->data,
            'selected_uploads' => $request->input('selected_uploads', []),
        ]]);

        return redirect()->back()->with('show_preview', true)->withInput();
    }

    public function generateManual(Request $request)
    {
        $request->validate([
            'student_number' => 'required|string',
            'data' => 'required|array',
            'data.*.school_year' => 'required|string',
            'data.*.grade_level' => 'required|string',
            'data.*.subjects' => 'required|array',
            'data.*.subjects.*' => 'required|string',
        ]);

        // Store in session then trigger download
        session(['temp_encoded_grades' => [
            'student_number' => $request->student_number,
            'data' => $request->data,
            'selected_uploads' => $request->input('selected_uploads', []),
        ]]);

        record_log('Generated Form 137', 'Records', "Generated Form 137 for Student #{$request->student_number}");

        return $this->downloadExcel($request, $request->student_number);
    }

    private function parseExcelGrades($filePath)
    {
        $upload = $filePath instanceof Form138Upload
            ? $filePath
            : Form138Upload::where('file_path', $filePath)->first();
        if (! $upload) {
            return [];
        }

        $disk = $upload->storage_disk ?: 'public';
        $fullPath = Storage::disk($disk)->path($upload->file_path);
        if (! file_exists($fullPath)) {
            return [];
        }

        try {
            $reader = IOFactory::createReaderForFile($fullPath);
            $reader->setReadDataOnly(true);
            $spreadsheet = $reader->load($fullPath);

            $sheet = null;
            $targetSheetName = 'CARD back';

            foreach ($spreadsheet->getSheetNames() as $name) {
                if (strtolower($name) === strtolower($targetSheetName)) {
                    $sheet = $spreadsheet->getSheetByName($name);
                    break;
                }
            }

            if (! $sheet) {
                foreach ($spreadsheet->getSheetNames() as $name) {
                    if (str_contains(strtolower($name), 'back')) {
                        $sheet = $spreadsheet->getSheetByName($name);
                        break;
                    }
                }
            }

            if (! $sheet) {
                $sheet = $spreadsheet->getActiveSheet();
            }

            $data = $sheet->toArray(null, true, true, true);

            // Dynamic column detection
            $colQ1 = 'B';
            $colQ2 = 'C';
            $colQ3 = 'D';
            $colQ4 = 'E';
            $colFinal = 'F';
            $startRow = 10;

            // Scan rows 1-15 to find the header row and correct columns
            for ($r = 1; $r <= 15; $r++) {
                if (! isset($data[$r])) {
                    continue;
                }
                $rowValues = $data[$r];

                $foundQ1 = false;
                foreach ($rowValues as $col => $val) {
                    $cleanVal = strtolower(trim($val));
                    if ($cleanVal == '1' || $cleanVal == '1st' || $cleanVal == 'q1') {
                        $colQ1 = $col;
                        $foundQ1 = true;
                        $startRow = $r + 1; // Data usually starts after the header
                    } elseif ($cleanVal == '2' || $cleanVal == '2nd' || $cleanVal == 'q2') {
                        $colQ2 = $col;
                    } elseif ($cleanVal == '3' || $cleanVal == '3rd' || $cleanVal == 'q3') {
                        $colQ3 = $col;
                    } elseif ($cleanVal == '4' || $cleanVal == '4th' || $cleanVal == 'q4') {
                        $colQ4 = $col;
                    } elseif (str_contains($cleanVal, 'final') || $cleanVal == 'rating' || $cleanVal == 'avg') {
                        $colFinal = $col;
                    }
                }
                if ($foundQ1) {
                    break;
                } // Found the header row
            }

            $parsedGrades = [];
            for ($i = $startRow; $i <= count($data); $i++) {
                $row = $data[$i];
                // Subject is usually in Column A
                if (! empty($row['A']) && ! is_numeric($row['A']) && strlen($row['A']) > 2) {
                    $parsedGrades[] = [
                        'subject' => $row['A'],
                        'q1' => $row[$colQ1] ?? null,
                        'q2' => $row[$colQ2] ?? null,
                        'q3' => $row[$colQ3] ?? null,
                        'q4' => $row[$colQ4] ?? null,
                        'final' => $row[$colFinal] ?? null,
                    ];
                }
            }

            return $parsedGrades;
        } catch (\Exception $e) {
            return [];
        }
    }

    public function downloadExcel(Request $request, $student_number)
    {
        $student = Student::where('student_number', $student_number)->firstOrFail();

        // Get selected uploads from request OR session
        $selectedUploadIds = $request->input('selected_uploads', []);

        $tempGrades = session('temp_encoded_grades');
        if (empty($selectedUploadIds) && $tempGrades && $tempGrades['student_number'] == $student_number) {
            $selectedUploadIds = $tempGrades['selected_uploads'] ?? [];
        }

        // If no selected uploads but we have encoded grades, we can still generate
        $spreadsheet = $this->generateSpreadsheet($student, $selectedUploadIds);

        if ($spreadsheet instanceof RedirectResponse) {
            return $spreadsheet;
        }

        // If it's just for preview
        if ($request->has('preview')) {
            $writer = new Html($spreadsheet);
            $html = $writer->generateHtmlAll();
            $styledHtml = str_replace(
                '</style>',
                'body { font-family: sans-serif; padding: 20px; background: #f3f4f6; } .container { background: white; padding: 40px; box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1); margin: 0 auto; max-width: 1000px; } table { border-collapse: collapse; width: 100%; margin-top: 20px; } th, td { border: 1px solid #000; padding: 4px; font-size: 12px; } </style>',
                $html
            );

            return '<div class="container">'.$styledHtml.'</div>';
        }

        $fileName = 'F137_'.$student->student_number.'.xlsx';
        $temporary = tempnam(sys_get_temp_dir(), 'f137-legacy-');
        if ($temporary === false) {
            abort(500, 'Unable to prepare the Form 137 workbook.');
        }
        $xlsxPath = $temporary.'.xlsx';
        @unlink($temporary);
        (new Xlsx($spreadsheet))->save($xlsxPath);

        $requestId = $request->integer('request_id') ?: RequestDocument::query()
            ->where('student_number', $student->student_number)
            ->whereIn('document_type', ['Form 137', 'F137'])
            ->latest('id')
            ->value('id');
        $qr = app(DocumentQrCode::class)->make('Form 137', $student->name, [
            'request_id' => $requestId,
            'holder_identifier' => $student->student_number,
            'fields' => [
                'selected_uploads' => array_values($selectedUploadIds),
                'manual_records' => $tempGrades['data'] ?? [],
            ],
        ]);
        app(DocumentWorkbookVerification::class)->attachToFile($xlsxPath, $qr, [
            'document_type' => 'Form 137',
            'holder_name' => $student->name,
            'holder_identifier' => $student->student_number,
            'issue_date' => now()->toDateString(),
        ]);
        app(DocumentQrCode::class)->registerArtifactFile(
            $qr['document'],
            $xlsxPath,
            $fileName,
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
        );

        return response()->download($xlsxPath, $fileName, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
        ])->deleteFileAfterSend(true);
    }

    public function viewHtml($id)
    {
        $upload = Form138Upload::findOrFail($id);
        $disk = $upload->storage_disk ?: 'public';
        $fullPath = Storage::disk($disk)->path($upload->file_path);

        if (! file_exists($fullPath)) {
            return 'File not found.';
        }

        try {
            $reader = IOFactory::createReaderForFile($fullPath);
            $reader->setReadDataOnly(true);
            $spreadsheet = $reader->load($fullPath);

            $writer = new Html($spreadsheet);

            // Set styles for a cleaner preview
            $html = $writer->generateHtmlAll();

            // Add some basic styling to the generated HTML
            $styledHtml = str_replace(
                '</style>',
                'body { font-family: sans-serif; padding: 20px; } table { border-collapse: collapse; width: 100%; } th, td { border: 1px solid #ccc; padding: 8px; } </style>',
                $html
            );

            return $styledHtml;
        } catch (\Exception $e) {
            return 'Error loading preview: '.$e->getMessage();
        }
    }

    private function generateSpreadsheet($student, $selectedUploadIds = [])
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Form 137');

        // Set Default Font
        $spreadsheet->getDefaultStyle()->getFont()->setName('Arial');
        $spreadsheet->getDefaultStyle()->getFont()->setSize(10);

        // Header
        $sheet->mergeCells('A1:F1');
        $sheet->setCellValue('A1', 'OFFICIAL TRANSCRIPT OF RECORDS (FORM 137)');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Student Info
        $sheet->setCellValue('A3', 'Name:');
        $sheet->setCellValue('B3', $student->name);
        $sheet->setCellValue('A4', 'Student No:');
        $sheet->setCellValue('B4', $student->student_number);
        $sheet->getStyle('A3:A4')->getFont()->setBold(true);

        $currentRow = 6;

        // Parsed Grades from Uploads
        if (! empty($selectedUploadIds)) {
            $uploads = Form138Upload::whereIn('id', $selectedUploadIds)->get();
            foreach ($uploads as $upload) {
                $parsedGrades = $this->parseExcelGrades($upload);
                if (! empty($parsedGrades)) {
                    // SY Header
                    $sheet->mergeCells("A{$currentRow}:F{$currentRow}");
                    $sheet->setCellValue("A{$currentRow}", 'School Year: '.$upload->school_year.' | Level: '.$upload->grade_level);
                    $sheet->getStyle("A{$currentRow}")->getFont()->setBold(true);
                    $sheet->getStyle("A{$currentRow}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('E0E0E0');
                    $currentRow++;

                    // Table Header
                    $sheet->setCellValue("A{$currentRow}", 'Subject');
                    $sheet->setCellValue("B{$currentRow}", 'Q1');
                    $sheet->setCellValue("C{$currentRow}", 'Q2');
                    $sheet->setCellValue("D{$currentRow}", 'Q3');
                    $sheet->setCellValue("E{$currentRow}", 'Q4');
                    $sheet->setCellValue("F{$currentRow}", 'Final');
                    $sheet->getStyle("A{$currentRow}:F{$currentRow}")->getFont()->setBold(true);
                    $sheet->getStyle("A{$currentRow}:F{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    $currentRow++;

                    foreach ($parsedGrades as $grade) {
                        $sheet->setCellValue("A{$currentRow}", $grade['subject']);
                        $sheet->setCellValue("B{$currentRow}", $grade['q1'] ?? '');
                        $sheet->setCellValue("C{$currentRow}", $grade['q2'] ?? '');
                        $sheet->setCellValue("D{$currentRow}", $grade['q3'] ?? '');
                        $sheet->setCellValue("E{$currentRow}", $grade['q4'] ?? '');
                        $sheet->setCellValue("F{$currentRow}", $grade['final'] ?? '');
                        $sheet->getStyle("B{$currentRow}:F{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                        $currentRow++;
                    }
                    $currentRow++;
                }
            }
        }

        // Manual Grades from Session
        $tempGrades = session('temp_encoded_grades');
        if ($tempGrades && $tempGrades['student_number'] == $student->student_number) {
            foreach ($tempGrades['data'] as $syData) {
                // SY Header
                $sheet->mergeCells("A{$currentRow}:F{$currentRow}");
                $sheet->setCellValue("A{$currentRow}", 'School Year: '.$syData['school_year'].' | Level: '.$syData['grade_level']);
                $sheet->getStyle("A{$currentRow}")->getFont()->setBold(true);
                $sheet->getStyle("A{$currentRow}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('E0E0E0');
                $currentRow++;

                // Table Header
                $sheet->setCellValue("A{$currentRow}", 'Subject');
                $sheet->setCellValue("B{$currentRow}", 'Q1');
                $sheet->setCellValue("C{$currentRow}", 'Q2');
                $sheet->setCellValue("D{$currentRow}", 'Q3');
                $sheet->setCellValue("E{$currentRow}", 'Q4');
                $sheet->setCellValue("F{$currentRow}", 'Final');
                $sheet->getStyle("A{$currentRow}:F{$currentRow}")->getFont()->setBold(true);
                $sheet->getStyle("A{$currentRow}:F{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $currentRow++;

                // Grades
                foreach ($syData['subjects'] as $idx => $subject) {
                    $sheet->setCellValue("A{$currentRow}", $subject);
                    $sheet->setCellValue("B{$currentRow}", $syData['q1'][$idx] ?? '');
                    $sheet->setCellValue("C{$currentRow}", $syData['q2'][$idx] ?? '');
                    $sheet->setCellValue("D{$currentRow}", $syData['q3'][$idx] ?? '');
                    $sheet->setCellValue("E{$currentRow}", $syData['q4'][$idx] ?? '');
                    $sheet->setCellValue("F{$currentRow}", $syData['final'][$idx] ?? '');

                    // Center numbers
                    $sheet->getStyle("B{$currentRow}:F{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    $currentRow++;
                }
                $currentRow++; // Gap between SYs
            }
        }

        // Set column widths
        $sheet->getColumnDimension('A')->setWidth(30);
        $sheet->getColumnDimension('B')->setWidth(8);
        $sheet->getColumnDimension('C')->setWidth(8);
        $sheet->getColumnDimension('D')->setWidth(8);
        $sheet->getColumnDimension('E')->setWidth(8);
        $sheet->getColumnDimension('F')->setWidth(8);

        return $spreadsheet;
    }
}
