<?php

use App\Support\GradeSheetImporter;
use PhpOffice\PhpSpreadsheet\IOFactory;

require dirname(__DIR__).'/vendor/autoload.php';
$app = require dirname(__DIR__).'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$root = dirname(__DIR__).'/test-data/grade-portal-complete-official-excel-format';
$manifestPath = $root.'/00_UPLOAD_MANIFEST.xlsx';
if (! is_file($manifestPath)) {
    fwrite(STDERR, "Manifest not found: {$manifestPath}\n");
    exit(1);
}

$sheet = IOFactory::load($manifestPath)->getSheetByName('Upload Manifest');
$rows = $sheet->toArray(null, true, true, false);
$headers = array_shift($rows);
$importer = app(GradeSheetImporter::class);
$levels = [];
$verified = 0;

foreach ($rows as $row) {
    if (! array_filter($row, fn ($value) => $value !== null && $value !== '')) {
        continue;
    }
    $item = array_combine($headers, array_slice(array_pad($row, count($headers), null), 0, count($headers)));
    $path = $root.'/'.str_replace('/', DIRECTORY_SEPARATOR, $item['Relative Path']);
    if (! is_file($path)) {
        throw new RuntimeException("Missing workbook: {$item['Relative Path']}");
    }
    $importer->assertMatchesSelection($path, $item['School Year'], $item['Grade Level'], (int) $item['Period']);
    $records = $item['File Type'] === 'summary' ? $importer->summaries($path) : $importer->attendance($path);
    if (count($records) !== (int) $item['Students']) {
        throw new RuntimeException("Student count mismatch: {$item['Relative Path']}");
    }
    $levels[$item['Grade Level']] = true;
    $verified++;
}

$expectedLevels = array_merge(['Kinder'], array_map(fn ($grade) => "Grade {$grade}", range(1, 12)));
$missing = array_values(array_diff($expectedLevels, array_keys($levels)));
if ($missing !== []) {
    throw new RuntimeException('Missing grade-level coverage: '.implode(', ', $missing));
}

$catalog = IOFactory::load($root.'/00_SECTION_CATALOG.xlsx')->getSheetByName('Official Sections')->toArray(null, true, true, false);
array_shift($catalog);
$catalogSections = array_map(fn (array $row) => [$row[0], $row[1]], array_values(array_filter($catalog, fn (array $row) => ($row[0] ?? null) && ($row[1] ?? null))));
$configuredSections = [];
foreach (config('academics.sections_by_grade', []) as $level => $sections) {
    foreach ($sections as $section) {
        $configuredSections[] = [$level, $section];
    }
}
if ($catalogSections !== $configuredSections) {
    throw new RuntimeException('The section catalog does not match the system configuration.');
}

$roster = IOFactory::load($root.'/00_STUDENT_ROSTER.xlsx')->getSheetByName('Student Progression')->toArray(null, true, true, false);
if (count(array_filter(array_slice($roster, 1), fn (array $row) => ($row[0] ?? null) !== null)) !== 5) {
    throw new RuntimeException('The progression roster must contain exactly five main students.');
}

$expectedClasses = count($schoolYears = config('academics.school_years', []));
$manifestClasses = [];
$mainClasses = [];
foreach ($rows as $row) {
    if (! array_filter($row, fn ($value) => $value !== null && $value !== '')) {
        continue;
    }
    $item = array_combine($headers, array_slice(array_pad($row, count($headers), null), 0, count($headers)));
    $classKey = $item['School Year'].'|'.$item['Grade Level'].'|'.$item['Section'];
    $manifestClasses[$classKey] = true;
    if ((int) $item['Main Students'] > 0) {
        $mainClasses[$classKey] = true;
    }
}
$requiredClassCount = count(config('academics.sections_by_grade', [])) > 0
    ? count(array_merge(...array_values(config('academics.sections_by_grade')))) * 6
    : 0;
if (count($manifestClasses) !== $requiredClassCount) {
    throw new RuntimeException("Expected {$requiredClassCount} populated classes, found ".count($manifestClasses).'.');
}
if (count($mainClasses) !== 30) {
    throw new RuntimeException('Expected five main students across six yearly classes each.');
}

echo "Verified {$verified} uploader workbooks.\n";
echo 'Grade coverage: '.implode(', ', $expectedLevels).".\n";
echo count($configuredSections)." official sections and 5 main student progressions verified.\n";
echo count($manifestClasses)." grade-and-section classes populated across all 6 school years.\n";
echo "30 main-student yearly class placements verified.\n";
