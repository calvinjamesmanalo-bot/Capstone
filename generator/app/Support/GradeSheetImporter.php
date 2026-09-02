<?php

namespace App\Support;

use RuntimeException;

class GradeSheetImporter
{
    public function __construct(private readonly XlsxWorkbookReader $reader) {}

    public function assertMatchesSelection(string $path, string $schoolYear, string $level, int $gradingPeriod): void
    {
        $values = [];
        foreach ($this->firstSheetRows($path) as $row) {
            foreach ($row['cells'] as $cell) {
                $value = trim((string) ($cell['value'] ?? ''));
                if ($value !== '') $values[] = $value;
            }
        }

        $contents = implode("\n", $values);
        $detectedSchoolYear = preg_match('/\b(20\d{2})\s*[-–]\s*(20\d{2})\b/u', $contents, $yearMatch)
            ? $yearMatch[1].'-'.$yearMatch[2]
            : null;
        $gradeWords = ['one' => 1, 'two' => 2, 'three' => 3];
        $detectedGrade = null;
        if (preg_match('/\bgrade\s+(one|two|three|[1-3])\b/i', $contents, $gradeMatch)) {
            $gradeToken = strtolower($gradeMatch[1]);
            $detectedGrade = $gradeWords[$gradeToken] ?? (int) $gradeToken;
        }
        $periodWords = ['first'=>1, '1st'=>1, 'second'=>2, '2nd'=>2, 'third'=>3, '3rd'=>3, 'fourth'=>4, '4th'=>4];
        $detectedPeriod = preg_match('/\b(first|second|third|fourth|[1-4](?:st|nd|rd|th))\s+grading\b/i', $contents, $periodMatch)
            ? $periodWords[strtolower($periodMatch[1])]
            : null;
        preg_match('/\bgrade\s+([1-3])\b/i', $level, $selectedGradeMatch);
        $selectedGrade = isset($selectedGradeMatch[1]) ? (int) $selectedGradeMatch[1] : null;

        if ($detectedSchoolYear === null || $detectedGrade === null || $detectedPeriod === null) {
            throw new RuntimeException('The workbook is missing a readable school year, grade level, or grading period header. Use the official grade sheet template.');
        }
        if ($detectedSchoolYear !== $schoolYear || $detectedGrade !== $selectedGrade || $detectedPeriod !== $gradingPeriod) {
            $periodNames = [1=>'First', 2=>'Second', 3=>'Third', 4=>'Fourth'];
            throw new RuntimeException(
                "Workbook metadata mismatch. The file is {$detectedSchoolYear}, Grade {$detectedGrade}, {$periodNames[$detectedPeriod]} Grading, "
                ."but the selected upload destination is {$schoolYear}, {$level}, {$periodNames[$gradingPeriod]} Grading."
            );
        }
    }

    public function summaries(string $path): array
    {
        $rows = $this->firstSheetRows($path);
        $records = [];

        foreach ($rows as $row) {
            $cells = $this->cellsByColumn($row);
            $studentNumber = $this->normalizeStudentNumber($cells['B'] ?? '');

            if (!$this->isStudentNumber($studentNumber)) continue;

            $records[] = [
                'student_number' => $studentNumber,
                'name' => trim($cells['C'] ?? ''),
                'grades' => [
                    'Language' => $this->number($cells['D'] ?? null),
                    'Reading and Literacy' => $this->number($cells['E'] ?? null),
                    'Mathematics' => $this->number($cells['F'] ?? null),
                    'Makabansa' => $this->number($cells['G'] ?? null),
                    'Good Manners and Right Conduct' => $this->number($cells['H'] ?? null),
                ],
            ];
        }

        if ($records === []) throw new RuntimeException('No student grade records with a student number were found in the summary sheet.');

        return $records;
    }

    public function attendance(string $path): array
    {
        $rows = $this->firstSheetRows($path);
        $schoolDays = [];
        $monthColumns = ['D'=>'September','E'=>'October','F'=>'November','G'=>'December','H'=>'January','I'=>'February','J'=>'March','K'=>'April','L'=>'May','M'=>'June'];

        foreach ($rows as $row) {
            if (($row['index'] ?? 0) === 7) {
                $cells = $this->cellsByColumn($row);
                foreach ($monthColumns as $column => $month) $schoolDays[$month] = $this->integer($cells[$column] ?? null);
            }
        }

        $records = [];
        foreach ($rows as $row) {
            $cells = $this->cellsByColumn($row);
            $lrn = $this->normalizeLrn($cells['B'] ?? '');
            if ($lrn === '' || strlen($lrn) < 10) continue;

            $months = [];
            foreach ($monthColumns as $column => $month) {
                $months[$month] = ['school_days' => $schoolDays[$month] ?? null, 'days_present' => $this->integer($cells[$column] ?? null)];
            }
            $records[] = ['lrn'=>$lrn, 'name'=>trim($cells['C'] ?? ''), 'months'=>$months];
        }

        if ($records === []) throw new RuntimeException('No student attendance records with a student number were found in the attendance sheet.');

        return $records;
    }

    public function teacherName(string $path): ?string
    {
        foreach ($this->firstSheetRows($path) as $row) {
            $values = array_values(array_filter(
                array_map(fn (array $cell) => trim((string) ($cell['value'] ?? '')), $row['cells']),
                fn (string $value) => $value !== ''
            ));

            foreach ($values as $index => $value) {
                if (!preg_match('/\b(teacher(?:-in-charge)?|adviser|advisor)\b/i', $value)) {
                    continue;
                }

                foreach (array_slice($values, $index + 1) as $candidate) {
                    if (!preg_match('/teacher|adviser|advisor|signature/i', $candidate)) {
                        return $candidate;
                    }
                }
            }
        }

        return null;
    }

    private function firstSheetRows(string $path): array
    {
        $sheets = $this->reader->read($path);
        if ($sheets === []) throw new RuntimeException('The uploaded workbook does not contain a readable worksheet.');
        return $sheets[0]['rows'];
    }

    private function cellsByColumn(array $row): array
    {
        $cells = [];
        foreach ($row['cells'] as $cell) $cells[$cell['column']] = $cell['value'];
        return $cells;
    }

    private function normalizeStudentNumber(mixed $value): string
    {
        return strtoupper(trim((string) $value));
    }

    private function isStudentNumber(string $value): bool
    {
        // Ignore headings such as "STUDENT NO." while allowing numeric and
        // school-specific identifiers such as 2020-0001.
        return $value !== '' && preg_match('/\d/', $value) === 1;
    }

    private function normalizeLrn(mixed $value): string
    {
        return preg_replace('/\D/', '', (string) $value) ?: '';
    }

    private function number(mixed $value): ?float
    {
        return is_numeric($value) ? (float) $value : null;
    }

    private function integer(mixed $value): ?int
    {
        return is_numeric($value) ? (int) round((float) $value) : null;
    }
}
