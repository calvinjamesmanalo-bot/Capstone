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
                if ($value !== '') {
                    $values[] = $value;
                }
            }
        }

        $contents = implode("\n", $values);
        $detectedSchoolYear = preg_match('/\b(20\d{2})\s*[-–]\s*(20\d{2})\b/u', $contents, $yearMatch)
            ? $yearMatch[1].'-'.$yearMatch[2]
            : null;
        $detectedGradeLevel = $this->gradeLevel($contents);
        $periodWords = [
            'first' => 1, '1st' => 1, '1' => 1,
            'second' => 2, '2nd' => 2, '2' => 2,
            'third' => 3, '3rd' => 3, '3' => 3,
            'fourth' => 4, '4th' => 4, '4' => 4,
        ];
        $periodToken = 'first|second|third|fourth|[1-4](?:st|nd|rd|th)?';
        $periodPattern = '/\b(?:(?<leading_period>'.$periodToken.')\s+(?<trailing_type>grading|term)|(?<leading_type>grading|term)\s+(?<trailing_period>'.$periodToken.'))\b/i';
        $hasPeriod = preg_match($periodPattern, $contents, $periodMatch, PREG_UNMATCHED_AS_NULL) === 1;
        $detectedPeriodToken = $periodMatch['leading_period'] ?? $periodMatch['trailing_period'] ?? null;
        $detectedPeriodType = $periodMatch['trailing_type'] ?? $periodMatch['leading_type'] ?? null;
        $detectedPeriod = $hasPeriod && $detectedPeriodToken !== null
            ? $periodWords[strtolower($detectedPeriodToken)]
            : null;
        $detectedPeriodType = $detectedPeriodType !== null ? strtolower($detectedPeriodType) : null;
        $selectedGradeLevel = $this->gradeLevel($level);

        if ($detectedSchoolYear === null || $detectedGradeLevel === null || $detectedPeriod === null) {
            throw new RuntimeException('The workbook is missing a readable school year, grade level, or grading period header. Use the official grade sheet template.');
        }

        if ($selectedGradeLevel === null) {
            throw new RuntimeException("The selected grade level '{$level}' is not supported.");
        }

        $expectedPeriodType = AcademicPeriod::usesTerms($schoolYear) ? 'term' : 'grading';

        if ($detectedSchoolYear !== $schoolYear || $detectedGradeLevel !== $selectedGradeLevel || $detectedPeriod !== $gradingPeriod || $detectedPeriodType !== $expectedPeriodType) {
            $detectedPeriodLabel = AcademicPeriod::label(
                $detectedSchoolYear ?? $schoolYear,
                $detectedPeriod ?? $gradingPeriod,
            );
            if ($detectedPeriodType !== null) {
                $periodName = ['First', 'Second', 'Third', 'Fourth'][($detectedPeriod ?? 1) - 1] ?? 'Unknown';
                $detectedPeriodLabel = "{$periodName} {$detectedPeriodType}";
            }

            throw new RuntimeException(
                "Workbook metadata mismatch. The file is {$detectedSchoolYear}, {$detectedGradeLevel}, {$detectedPeriodLabel}, "
                ."but the selected upload destination is {$schoolYear}, {$level}, ".AcademicPeriod::label($schoolYear, $gradingPeriod).'.'
            );
        }
    }

    private function gradeLevel(string $text): ?string
    {
        if (preg_match('/\b(?:kinder|kindergarten)\b/i', $text)) {
            return 'Kinder';
        }

        $gradeWords = [
            'one' => 1,
            'two' => 2,
            'three' => 3,
            'four' => 4,
            'five' => 5,
            'six' => 6,
            'seven' => 7,
            'eight' => 8,
            'nine' => 9,
            'ten' => 10,
        ];

        if (! preg_match('/\bgrade\s+(one|two|three|four|five|six|seven|eight|nine|ten|10|[1-9])\b/i', $text, $match)) {
            return null;
        }

        $token = strtolower($match[1]);
        $grade = $gradeWords[$token] ?? (int) $token;

        return "Grade {$grade}";
    }

    public function summaries(string $path): array
    {
        $rows = $this->firstSheetRows($path);
        $subjectColumns = $this->subjectColumns($rows);
        $records = [];

        foreach ($rows as $row) {
            $cells = $this->cellsByColumn($row);
            $studentNumber = $this->normalizeStudentNumber($cells['B'] ?? '');

            if (! $this->isStudentNumber($studentNumber)) {
                continue;
            }

            $records[] = [
                'student_number' => $studentNumber,
                'name' => trim($cells['C'] ?? ''),
                'grades' => collect($subjectColumns)
                    ->mapWithKeys(fn (string $subject, string $column) => [
                        $subject => $this->number($cells[$column] ?? null),
                    ])
                    ->filter(fn (?float $grade) => $grade !== null)
                    ->all(),
            ];
        }

        if ($records === []) {
            throw new RuntimeException('No student grade records with a student number were found in the summary sheet.');
        }

        return $records;
    }

    private function subjectColumns(array $rows): array
    {
        $firstStudentRow = collect($rows)->first(function (array $row): bool {
            $cells = $this->cellsByColumn($row);

            return $this->isStudentNumber($this->normalizeStudentNumber($cells['B'] ?? ''));
        });
        $firstStudentIndex = (int) ($firstStudentRow['index'] ?? PHP_INT_MAX);
        $excluded = [
            'average', 'general average', 'final average', 'rank', 'remarks',
            'student number', 'student no.', 'student no', 'lrn', 'name',
        ];
        $candidates = [];

        foreach ($rows as $row) {
            if ((int) ($row['index'] ?? 0) >= $firstStudentIndex) {
                continue;
            }

            $subjects = [];
            foreach ($this->cellsByColumn($row) as $column => $value) {
                $subject = trim(preg_replace('/\s+/', ' ', (string) $value));
                if ($this->columnNumber($column) < 4 || $subject === '' || is_numeric($subject)) {
                    continue;
                }

                if (in_array(mb_strtolower($subject), $excluded, true)) {
                    continue;
                }

                $subjects[$column] = $subject;
            }

            if (count($subjects) > count($candidates)) {
                $candidates = $subjects;
            }
        }

        return $candidates ?: [
            'D' => 'Language',
            'E' => 'Reading and Literacy',
            'F' => 'Mathematics',
            'G' => 'Makabansa',
            'H' => 'Good Manners and Right Conduct',
        ];
    }

    private function columnNumber(string $column): int
    {
        $number = 0;
        foreach (str_split(strtoupper($column)) as $character) {
            $number = ($number * 26) + ord($character) - 64;
        }

        return $number;
    }

    public function attendance(string $path): array
    {
        $rows = $this->firstSheetRows($path);
        [$monthColumns, $monthHeaderIndex] = $this->attendanceMonthColumns($rows);
        $firstStudentIndex = collect($rows)->first(function (array $row): bool {
            $cells = $this->cellsByColumn($row);
            $lrn = $this->normalizeLrn($cells['B'] ?? '');

            return strlen($lrn) >= 10;
        })['index'] ?? PHP_INT_MAX;
        $schoolDays = [];

        foreach ($rows as $row) {
            $rowIndex = (int) ($row['index'] ?? 0);
            if ($rowIndex <= $monthHeaderIndex || $rowIndex >= $firstStudentIndex) {
                continue;
            }

            $cells = $this->cellsByColumn($row);
            foreach ($monthColumns as $column => $month) {
                $value = $this->integer($cells[$column] ?? null);
                if ($value !== null) {
                    $schoolDays[$month] = $value;
                }
            }
        }

        $records = [];
        foreach ($rows as $row) {
            $cells = $this->cellsByColumn($row);
            $lrn = $this->normalizeLrn($cells['B'] ?? '');
            if ($lrn === '' || strlen($lrn) < 10) {
                continue;
            }

            $months = [];
            foreach ($monthColumns as $column => $month) {
                $months[$month] = ['school_days' => $schoolDays[$month] ?? null, 'days_present' => $this->integer($cells[$column] ?? null)];
            }
            $records[] = ['lrn' => $lrn, 'name' => trim($cells['C'] ?? ''), 'months' => $months];
        }

        if ($records === []) {
            throw new RuntimeException('No student attendance records with a student number were found in the attendance sheet.');
        }

        return $records;
    }

    private function attendanceMonthColumns(array $rows): array
    {
        $monthNames = [
            'january' => 'January', 'jan' => 'January',
            'february' => 'February', 'feb' => 'February',
            'march' => 'March', 'mar' => 'March',
            'april' => 'April', 'apr' => 'April',
            'may' => 'May',
            'june' => 'June', 'jun' => 'June',
            'july' => 'July', 'jul' => 'July',
            'august' => 'August', 'aug' => 'August',
            'september' => 'September', 'sept' => 'September', 'sep' => 'September',
            'october' => 'October', 'oct' => 'October',
            'november' => 'November', 'nov' => 'November',
            'december' => 'December', 'dec' => 'December',
        ];
        $bestColumns = [];
        $bestRowIndex = 0;

        foreach ($rows as $row) {
            $columns = [];
            foreach ($this->cellsByColumn($row) as $column => $value) {
                if ($this->columnNumber($column) < 4) {
                    continue;
                }

                $key = mb_strtolower(trim(preg_replace('/[^\pL]+/u', ' ', (string) $value)));
                if (isset($monthNames[$key])) {
                    $columns[$column] = $monthNames[$key];
                }
            }

            if (count($columns) > count($bestColumns)) {
                $bestColumns = $columns;
                $bestRowIndex = (int) ($row['index'] ?? 0);
            }
        }

        if ($bestColumns === []) {
            throw new RuntimeException('No attendance month headings were found. Use the official attendance sheet template.');
        }

        return [$bestColumns, $bestRowIndex];
    }

    public function teacherName(string $path): ?string
    {
        foreach ($this->firstSheetRows($path) as $row) {
            $values = array_values(array_filter(
                array_map(fn (array $cell) => trim((string) ($cell['value'] ?? '')), $row['cells']),
                fn (string $value) => $value !== ''
            ));

            foreach ($values as $index => $value) {
                if (! preg_match('/\b(teacher(?:-in-charge)?|adviser|advisor)\b/i', $value)) {
                    continue;
                }

                foreach (array_slice($values, $index + 1) as $candidate) {
                    if (! preg_match('/teacher|adviser|advisor|signature/i', $candidate)) {
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
        if ($sheets === []) {
            throw new RuntimeException('The uploaded workbook does not contain a readable worksheet.');
        }

        return $sheets[0]['rows'];
    }

    private function cellsByColumn(array $row): array
    {
        $cells = [];
        foreach ($row['cells'] as $cell) {
            $cells[$cell['column']] = $cell['value'];
        }

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
