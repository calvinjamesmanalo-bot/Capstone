<?php

use App\Http\Controllers\SchoolFormRecordController;
use App\Support\AcademicPeriod;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

require dirname(__DIR__).'/vendor/autoload.php';
$app = require dirname(__DIR__).'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$output = dirname(__DIR__).'/test-data/grade-portal-complete-official-excel-format';
if (is_dir($output)) {
    fwrite(STDERR, "Output already exists: {$output}\n");
    exit(1);
}

$students = [
    ['number' => 'TEST-2021-001', 'lrn' => '990000000001', 'name' => 'Amara Reyes', 'start_grade' => 0],
    ['number' => 'TEST-2021-002', 'lrn' => '990000000002', 'name' => 'Bruno Santos', 'start_grade' => 2],
    ['number' => 'TEST-2021-003', 'lrn' => '990000000003', 'name' => 'Clara Mendoza', 'start_grade' => 5],
    ['number' => 'TEST-2021-004', 'lrn' => '990000000004', 'name' => 'Diego Navarro', 'start_grade' => 7],
    ['number' => 'TEST-2021-005', 'lrn' => '990000000005', 'name' => 'Elena Cruz', 'start_grade' => 1],
];
$schoolYears = ['2021-2022', '2022-2023', '2023-2024', '2024-2025', '2025-2026', '2026-2027'];
$controller = app(SchoolFormRecordController::class);
$makeTemplate = new ReflectionMethod($controller, 'makeTemplate');
$manifest = [];
$coverage = [];

foreach ($schoolYears as $yearIndex => $schoolYear) {
    foreach (config('academics.sections_by_grade', []) as $level => $sections) {
        $gradeNumber = $level === 'Kinder' ? 0 : (int) filter_var($level, FILTER_SANITIZE_NUMBER_INT);
        foreach ($sections as $sectionIndex => $section) {
            $classStudents = [];
            if ($sectionIndex === 0) {
                foreach ($students as $studentIndex => $student) {
                    if ($student['start_grade'] + $yearIndex === $gradeNumber) {
                        $classStudents[] = $student + ['grade' => $gradeNumber, 'student_index' => $studentIndex, 'is_main' => true];
                    }
                }
            }
            for ($slot = count($classStudents) + 1; $slot <= 5; $slot++) {
                $classStudents[] = [
                    'number' => sprintf('DUMMY-%d-%02d-%02d-%02d', (int) substr($schoolYear, 0, 4), $gradeNumber, $sectionIndex + 1, $slot),
                    'lrn' => sprintf('88%02d%02d%03d%03d', $yearIndex, $gradeNumber, $sectionIndex + 1, $slot),
                    'name' => fictionalName($yearIndex, $gradeNumber, $sectionIndex, $slot),
                    'grade' => $gradeNumber,
                    'student_index' => 5 + $slot,
                    'is_main' => false,
                ];
            }
            $classKey = sprintf('%s|%s|%s', $schoolYear, $level, $section);
            $coverage[$classKey] = ['school_year' => $schoolYear, 'level' => $level, 'section' => $section, 'students' => $classStudents];
        }
    }
}

foreach ($coverage as $class) {
    $folder = $output.'/'.str_replace('-', '_', $class['school_year']).'/'.slug($class['level'].'_'.$class['section']);
    mkdir($folder, 0777, true);
    $periods = AcademicPeriod::numbers($class['school_year'], $class['level']);

    foreach ($periods as $period) {
        foreach (['summary', 'attendance'] as $type) {
            /** @var Spreadsheet $workbook */
            $workbook = $makeTemplate->invoke($controller, $type, [
                'school_year' => $class['school_year'],
                'level' => $class['level'],
                'section' => $class['section'],
                'period' => $period,
            ]);
            $sheet = $workbook->getSheet(0);
            $sheet->setCellValue($type === 'summary' ? 'H3' : 'G4', adviserFor($class['level']));

            if ($type === 'summary') {
                $lastColumn = Coordinate::columnIndexFromString($sheet->getHighestDataColumn());
                foreach ($class['students'] as $offset => $student) {
                    $row = 7 + $offset;
                    $sheet->setCellValue("A{$row}", $offset + 1);
                    $sheet->setCellValueExplicit("B{$row}", $student['lrn'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
                    $sheet->setCellValue("C{$row}", $student['name']);
                    $grades = [];
                    for ($column = 4; $column < $lastColumn; $column++) {
                        $grade = 82 + (($student['student_index'] * 3 + $period * 2 + $column) % 16);
                        $sheet->setCellValue([$column, $row], $grade);
                        $grades[] = $grade;
                    }
                    $sheet->setCellValue([$lastColumn, $row], round(array_sum($grades) / count($grades), 2));
                }
            } else {
                $lastColumn = Coordinate::columnIndexFromString($sheet->getHighestDataColumn());
                $schoolDays = [];
                for ($column = 4; $column < $lastColumn; $column++) {
                    $days = 18 + (($period + $column) % 5);
                    $sheet->setCellValue([$column, 7], $days);
                    $schoolDays[$column] = $days;
                }
                $sheet->setCellValue([$lastColumn, 7], array_sum($schoolDays));
                foreach ($class['students'] as $offset => $student) {
                    $row = 8 + $offset;
                    $sheet->setCellValue("A{$row}", $offset + 1);
                    $sheet->setCellValueExplicit("B{$row}", $student['lrn'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
                    $sheet->setCellValue("C{$row}", $student['name']);
                    foreach ($schoolDays as $column => $days) {
                        $sheet->setCellValue([$column, $row], max(0, $days - (($student['student_index'] + $period + $column) % 3)));
                    }
                    $sheet->setCellValue([$lastColumn, $row], array_sum(array_map(fn ($column) => (int) $sheet->getCell([$column, $row])->getValue(), array_keys($schoolDays))));
                }
            }

            $periodSlug = strtoupper(str_replace(' ', '_', AcademicPeriod::label($class['school_year'], $period, $class['level'])));
            $levelSlug = str_replace(' ', '_', $class['level']);
            $sectionSlug = preg_replace('/[^A-Za-z0-9]+/', '_', $class['section']);
            $filename = "{$levelSlug}_{$class['school_year']}_{$periodSlug}_Student_".ucfirst($type)."_{$sectionSlug}.xlsx";
            (new Xlsx($workbook))->save($folder.'/'.$filename);
            $workbook->disconnectWorksheets();
            $manifest[] = [
                'school_year' => $class['school_year'],
                'level' => $class['level'],
                'section' => $class['section'],
                'period' => $period,
                'period_label' => AcademicPeriod::label($class['school_year'], $period, $class['level']),
                'type' => $type,
                'student_count' => count($class['students']),
                'main_student_count' => count(array_filter($class['students'], fn (array $student) => $student['is_main'])),
                'relative_path' => relativePath($output, $folder.'/'.$filename),
            ];
        }
    }
}

writeRoster($output.'/00_STUDENT_ROSTER.xlsx', $students, $schoolYears);
writeManifest($output.'/00_UPLOAD_MANIFEST.xlsx', $manifest);
writeSectionCatalog($output.'/00_SECTION_CATALOG.xlsx');
file_put_contents($output.'/README.txt', readme($students, $schoolYears, $manifest));

echo "Created {$output}\n";
echo count($manifest)." uploader workbooks plus roster, manifest, and README.\n";

function adviserFor(string $level): string
{
    $grade = (int) filter_var($level, FILTER_SANITIZE_NUMBER_INT);
    return match (true) {
        $grade <= 3 => 'Maria L. Flores, LPT',
        $grade <= 6 => 'Joshua P. Ramos, LPT',
        $grade <= 10 => 'Andrea C. Villanueva, LPT',
        default => 'Jimmy E. Esporas, LPT',
    };
}

function sectionFor(string $level): string
{
    return config("academics.sections_by_grade.{$level}.0");
}

function fictionalName(int $yearIndex, int $grade, int $sectionIndex, int $slot): string
{
    $firstNames = ['Adrian', 'Bianca', 'Carlo', 'Danica', 'Ethan', 'Faith', 'Gabriel', 'Hannah', 'Ivan', 'Julia', 'Kevin', 'Lara', 'Marco', 'Nina', 'Oscar', 'Paula', 'Rafael', 'Sofia', 'Tristan', 'Vanessa'];
    $lastNames = ['Aquino', 'Bautista', 'Castillo', 'Domingo', 'Evangelista', 'Flores', 'Garcia', 'Herrera', 'Ignacio', 'Jimenez', 'Katigbak', 'Lim', 'Morales', 'Navarro', 'Ocampo', 'Pascual', 'Reyes', 'Santos', 'Tolentino', 'Valdez'];
    $seed = ($yearIndex * 117) + ($grade * 31) + ($sectionIndex * 7) + $slot;
    $first = $firstNames[$seed % count($firstNames)];
    $last = $lastNames[intdiv($seed, count($firstNames)) % count($lastNames)];
    $middle = chr(65 + ($seed % 26));

    return "{$last}, {$first} {$middle}.";
}

function slug(string $value): string
{
    return strtolower(preg_replace('/[^A-Za-z0-9]+/', '_', trim($value)) ?: 'class');
}

function relativePath(string $root, string $path): string
{
    return str_replace('\\', '/', ltrim(substr($path, strlen($root)), '/\\'));
}

function writeRoster(string $path, array $students, array $schoolYears): void
{
    $book = new Spreadsheet;
    $sheet = $book->getActiveSheet();
    $sheet->setTitle('Student Progression');
    $headers = array_merge(['Student Number', 'LRN', 'Student Name'], $schoolYears);
    $sheet->fromArray($headers, null, 'A1');
    foreach ($students as $index => $student) {
        $row = [$student['number'], $student['lrn'], $student['name']];
        foreach ($schoolYears as $yearIndex => $schoolYear) {
            $grade = $student['start_grade'] + $yearIndex;
            $level = $grade === 0 ? 'Kinder' : ($grade <= 12 ? "Grade {$grade}" : 'Graduated');
            $section = $level === 'Graduated' ? '' : sectionFor($level);
            $row[] = trim($level.($section ? " - {$section}" : ''));
        }
        $sheet->fromArray($row, null, 'A'.($index + 2));
    }
    styleIndexSheet($sheet, count($headers), count($students) + 1);
    (new Xlsx($book))->save($path);
}

function writeSectionCatalog(string $path): void
{
    $book = new Spreadsheet;
    $sheet = $book->getActiveSheet();
    $sheet->setTitle('Official Sections');
    $sheet->fromArray(['Grade Level', 'Section'], null, 'A1');
    $row = 2;
    foreach (config('academics.sections_by_grade', []) as $level => $sections) {
        foreach ($sections as $section) {
            $sheet->fromArray([$level, $section], null, "A{$row}");
            $row++;
        }
    }
    styleIndexSheet($sheet, 2, $row - 1);
    $sheet->freezePane('A2');
    $sheet->setAutoFilter("A1:B".($row - 1));
    (new Xlsx($book))->save($path);
}

function writeManifest(string $path, array $manifest): void
{
    $book = new Spreadsheet;
    $sheet = $book->getActiveSheet();
    $sheet->setTitle('Upload Manifest');
    $headers = ['School Year', 'Grade Level', 'Section', 'Period', 'Period Label', 'File Type', 'Students', 'Main Students', 'Relative Path'];
    $sheet->fromArray($headers, null, 'A1');
    foreach ($manifest as $index => $item) {
        $sheet->fromArray(array_values($item), null, 'A'.($index + 2));
    }
    styleIndexSheet($sheet, count($headers), count($manifest) + 1);
    $sheet->freezePane('A2');
    $sheet->setAutoFilter('A1:I'.(count($manifest) + 1));
    (new Xlsx($book))->save($path);
}

function styleIndexSheet($sheet, int $columns, int $rows): void
{
    $last = Coordinate::stringFromColumnIndex($columns);
    $sheet->getStyle("A1:{$last}1")->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
    $sheet->getStyle("A1:{$last}1")->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('000638');
    $sheet->getStyle("A1:{$last}{$rows}")->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN)->getColor()->setRGB('D9D9D9');
    foreach (range(1, $columns) as $column) {
        $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($column))->setAutoSize(true);
    }
}

function readme(array $students, array $years, array $manifest): string
{
    return "GRADE PORTAL FULL-COVERAGE TEST DATA\r\n"
        ."====================================\r\n\r\n"
        ."Scope: 5 fictional students progressing annually from SY {$years[0]} through {$years[array_key_last($years)]}.\r\n"
        ."Coverage: Every official section from Kinder through Grade 12 in every school year.\r\n"
        ."Each class contains five learners. The five named main students appear only in their correct progressing class; remaining rows are dummy learners.\r\n"
        ."Files: ".count($manifest)." system-compatible uploader workbooks.\r\n\r\n"
        ."HOW TO UPLOAD\r\n"
        ."1. Open 00_UPLOAD_MANIFEST.xlsx.\r\n"
        ."   Use 00_SECTION_CATALOG.xlsx to review every official section from the enrollment report.\r\n"
        ."2. In Grade Portal > Class Grade Sheets, select the matching school year, grade level, and section.\r\n"
        ."3. Upload each summary and attendance workbook into the period shown in the manifest.\r\n"
        ."4. For SY 2026-2027, Grade 11 and lower use three terms; transition-year Grade 12 uses four grading periods.\r\n"
        ."5. Use TEST-2021-001 through TEST-2021-005 or the LRNs in 00_STUDENT_ROSTER.xlsx when testing previews.\r\n\r\n"
        ."All names, identifiers, grades, and attendance values are fictional and intended only for testing.\r\n";
}
