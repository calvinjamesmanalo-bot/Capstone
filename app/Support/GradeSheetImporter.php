<?php

namespace App\Support;

use RuntimeException;

class GradeSheetImporter
{
    public function __construct(private readonly XlsxWorkbookReader $reader) {}

    public function assertMatchesSelection(string $path, string $schoolYear, string $level, int $gradingPeriod): void
    {
        $values = [];

        foreach ($this->allRows($path) as $row) {
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
        $periodPattern = '/\b(?:(?<leading_period>'.$periodToken.')\s+(?<trailing_type>grading|term|quarter)|(?<leading_type>grading|term|quarter)\s+(?<trailing_period>'.$periodToken.'))\b/i';
        $hasPeriod = preg_match($periodPattern, $contents, $periodMatch, PREG_UNMATCHED_AS_NULL) === 1;
        $detectedPeriodToken = $periodMatch['leading_period'] ?? $periodMatch['trailing_period'] ?? null;
        $detectedPeriodType = $periodMatch['trailing_type'] ?? $periodMatch['leading_type'] ?? null;
        $detectedPeriod = $hasPeriod && $detectedPeriodToken !== null
            ? $periodWords[strtolower($detectedPeriodToken)]
            : null;
        $detectedPeriodType = $detectedPeriodType !== null ? strtolower($detectedPeriodType) : null;
        if ($detectedPeriodType === 'quarter') {
            $detectedPeriodType = 'grading';
        }
        if ($gradingPeriod === 4 && $this->isYearEndWorkbook($path)) {
            $detectedPeriod = 4;
            $detectedPeriodType = 'grading';
        }
        $selectedGradeLevel = $this->gradeLevel($level);

        if ($detectedSchoolYear === null || $detectedGradeLevel === null || ($gradingPeriod !== 0 && $detectedPeriod === null)) {
            throw new RuntimeException('The workbook is missing a readable school year, grade level, or grading period header. Use the official grade sheet template.');
        }

        if ($selectedGradeLevel === null) {
            throw new RuntimeException("The selected grade level '{$level}' is not supported.");
        }

        $expectedPeriodType = AcademicPeriod::usesTerms($schoolYear, $level) ? 'term' : 'grading';

        if ($detectedSchoolYear !== $schoolYear || $detectedGradeLevel !== $selectedGradeLevel
            || ($gradingPeriod !== 0 && ($detectedPeriod !== $gradingPeriod || $detectedPeriodType !== $expectedPeriodType))) {
            $detectedPeriodLabel = AcademicPeriod::label(
                $detectedSchoolYear ?? $schoolYear,
                $detectedPeriod ?? $gradingPeriod,
                $detectedGradeLevel,
            );
            if ($detectedPeriodType !== null) {
                $periodName = ['First', 'Second', 'Third', 'Fourth'][($detectedPeriod ?? 1) - 1] ?? 'Unknown';
                $detectedPeriodLabel = "{$periodName} {$detectedPeriodType}";
            }

            throw new RuntimeException(
                "Workbook metadata mismatch. The file is {$detectedSchoolYear}, {$detectedGradeLevel}, {$detectedPeriodLabel}, "
                ."but the selected upload destination is {$schoolYear}, {$level}, ".AcademicPeriod::label($schoolYear, $gradingPeriod, $level).'.'
            );
        }
    }

    public function destination(string $path, string $originalName = ''): array
    {
        $values = [];
        foreach ($this->allRows($path) as $row) {
            foreach ($row['cells'] as $cell) {
                $value = trim((string) ($cell['value'] ?? ''));
                if ($value !== '') {
                    $values[] = $value;
                }
            }
        }

        $contents = implode("\n", $values);
        $searchable = $originalName."\n".$contents;
        preg_match('/\b(20\d{2})\s*[-_â€“]\s*(20\d{2})\b/u', $searchable, $yearMatch);
        $schoolYear = isset($yearMatch[1], $yearMatch[2]) ? $yearMatch[1].'-'.$yearMatch[2] : null;
        $level = $this->gradeLevel($searchable);
        $period = $this->gradingPeriod($searchable);
        $isYearEnd = $this->isYearEndWorkbook($path);
        $type = $isYearEnd || preg_match('/\bSUMMARY(?:\s+SHEET)?\b/i', $searchable)
            ? 'summary'
            : (preg_match('/\bATTENDANCE(?:\s+SHEET)?\b/i', $searchable) ? 'attendance' : null);
        if ($isYearEnd) {
            $period = 4;
        }
        if ($type === 'attendance') {
            $period = 0;
        }

        $normalized = ' '.mb_strtolower(preg_replace('/[^\pL\pN]+/u', ' ', $searchable)).' ';
        $sections = collect(CurriculumSubjects::sections())
            ->sortByDesc(fn (string $section) => mb_strlen($section));
        $section = $sections->first(function (string $candidate) use ($normalized): bool {
            $needle = ' '.mb_strtolower(preg_replace('/[^\pL\pN]+/u', ' ', $candidate)).' ';

            return str_contains($normalized, $needle);
        });

        if (! $schoolYear || ! $level || ! $period || ! $type || ! $section) {
            $missing = collect([
                'school year' => $schoolYear,
                'grade level' => $level,
                'section' => $section,
                'grading period' => $type === 'attendance' ? true : $period,
                'file type' => $type,
            ])->filter(fn ($value) => ! $value)->keys()->join(', ');
            throw new RuntimeException("Could not automatically identify {$missing}. Use an official workbook with complete headers and a descriptive filename.");
        }

        if (! in_array($schoolYear, config('academics.school_years', []), true)
            || ! in_array($level, config('academics.grade_levels', []), true)
            || ($type !== 'attendance' && ! in_array($period, AcademicPeriod::numbers($schoolYear, $level), true))) {
            throw new RuntimeException("Detected destination {$schoolYear}, {$level} - {$section}, period {$period} is not configured in the system.");
        }

        // Historical sections can move between grade levels from one school year
        // to another. The section was already matched against the system-wide
        // section list, so the workbook header is authoritative for batch imports.

        $this->assertMatchesSelection($path, $schoolYear, $level, $period);

        return compact('schoolYear', 'level', 'section', 'period', 'type');
    }

    private function gradingPeriod(string $text): ?int
    {
        $tokens = ['first' => 1, '1st' => 1, 'second' => 2, '2nd' => 2, 'third' => 3, '3rd' => 3, 'fourth' => 4, '4th' => 4];
        if (preg_match('/\b(first|second|third|fourth|[1-4](?:st|nd|rd|th))\s+(?:grading|term|quarter)\b/i', $text, $match)
            || preg_match('/\b(?:grading|term|quarter)\s+(first|second|third|fourth|[1-4](?:st|nd|rd|th)?)\b/i', $text, $match)
            || preg_match('/\bq([1-4])\b/i', $text, $match)) {
            $token = strtolower($match[1]);

            return $tokens[$token] ?? (int) $token;
        }

        return null;
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
            'eleven' => 11,
            'twelve' => 12,
        ];

        if (! preg_match('/\bgrade\s+(one|two|three|four|five|six|seven|eight|nine|ten|eleven|twelve|1[0-2]|[1-9])\b/i', $text, $match)) {
            return null;
        }

        $token = strtolower($match[1]);
        $grade = $gradeWords[$token] ?? (int) $token;

        return "Grade {$grade}";
    }

    public function summaries(string $path, ?int $period = null): array
    {
        if ($period === 4 && $this->isYearEndWorkbook($path)) {
            return $this->yearEndRecords($path, false);
        }

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
                'name' => is_numeric($cells['C'] ?? null) ? '' : trim($cells['C'] ?? ''),
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
            'average', 'gen. ave', 'gen. ave.', 'general average', 'final average', 'rank', 'remarks',
            'student number', 'student no.', 'student no', "student's name", 'student’s name', 'lrn', 'name',
        ];
        $candidates = [];

        foreach ($rows as $row) {
            if ((int) ($row['index'] ?? 0) >= $firstStudentIndex) {
                continue;
            }

            $subjects = [];
            foreach ($this->cellsByColumn($row) as $column => $value) {
                $subject = trim(preg_replace('/\s+/', ' ', (string) $value));
                if ($this->columnNumber($column) < 3 || $subject === '' || is_numeric($subject)) {
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
        if ($this->isYearEndWorkbook($path)) {
            return $this->yearEndRecords($path, true);
        }

        $rows = $this->firstSheetRows($path);
        [$monthColumns, $monthHeaderIndex] = $this->attendanceMonthColumns($rows);
        $firstStudentIndex = collect($rows)->first(function (array $row): bool {
            $cells = $this->cellsByColumn($row);
            $studentNumber = $this->normalizeStudentNumber($cells['B'] ?? '');

            return $this->isStudentNumber($studentNumber);
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
            $studentNumber = $this->normalizeStudentNumber($cells['B'] ?? '');
            if (! $this->isStudentNumber($studentNumber)) {
                continue;
            }

            $months = [];
            foreach ($monthColumns as $column => $month) {
                $months[$month] = ['school_days' => $schoolDays[$month] ?? null, 'days_present' => $this->integer($cells[$column] ?? null)];
            }
            $records[] = ['student_number' => $studentNumber, 'name' => trim($cells['C'] ?? ''), 'months' => $months];
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
                    if ($candidate !== ':' && ! preg_match('/teacher|adviser|advisor|signature/i', $candidate)) {
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

    private function allRows(string $path): array
    {
        return collect($this->reader->read($path))
            ->flatMap(fn (array $sheet) => $sheet['rows'] ?? [])
            ->values()
            ->all();
    }

    public function includesAnnualAttendance(string $path): bool
    {
        return $this->isYearEndWorkbook($path);
    }

    /**
     * Read the complete JHS report-card block from the Q4 workbook. Unlike the
     * regular summary import, this preserves the subject order and all four
     * grading-period values shown on the CARD JHS worksheet.
     */
    public function jhsReportCard(string $path, string $identifier): ?array
    {
        $needle = strtoupper(trim($identifier));
        $numericNeedle = $this->normalizeLrn($identifier);

        foreach ($this->reader->read($path) as $sheet) {
            if (! preg_match('/^CARD\s*JHS(?:\s*\(\d+\))?$/i', trim((string) ($sheet['name'] ?? '')))) {
                continue;
            }

            $rows = $sheet['rows'] ?? [];
            foreach ($rows as $rowPosition => $row) {
                $cells = $this->cellsByColumn($row);
                foreach ($cells as $labelColumn => $label) {
                    if (! preg_match('/^student\s*no\.?$/i', trim((string) $label))) {
                        continue;
                    }

                    $studentNumber = $this->normalizeStudentNumber($this->valueToRight($cells, $labelColumn, 4));
                    $lrn = '';
                    for ($offset = 1; $offset <= 4; $offset++) {
                        $nearby = $rows[$rowPosition + $offset] ?? null;
                        if (! $nearby) {
                            continue;
                        }
                        $nearbyCells = $this->cellsByColumn($nearby);
                        foreach ($nearbyCells as $column => $value) {
                            if (preg_match('/^lrn$/i', trim((string) $value))) {
                                $lrn = $this->normalizeLrn($this->valueToRight($nearbyCells, $column, 4));
                                break 2;
                            }
                        }
                    }

                    if (strtoupper($studentNumber) !== $needle && ($numericNeedle === '' || $lrn !== $numericNeedle)) {
                        continue;
                    }

                    return [
                        'student_number' => $studentNumber,
                        'lrn' => $lrn,
                        'grades' => $this->jhsCardGrades($rows, $rowPosition, $labelColumn),
                        'attendance' => $this->yearEndAttendance($rows, $rowPosition, $labelColumn),
                    ];
                }
            }
        }

        return null;
    }

    private function jhsCardGrades(array $rows, int $studentRowPosition, string $labelColumn): array
    {
        $periodColumns = [];
        $learningAreasPosition = null;
        $labelColumnNumber = $this->columnNumber($labelColumn);
        foreach (array_slice($rows, $studentRowPosition + 1, 12, true) as $position => $row) {
            $cells = $this->cellsByColumn($row);
            if (preg_match('/^LEARNING AREAS$/i', trim((string) ($cells[$labelColumn] ?? '')))) {
                $learningAreasPosition = $position;
            }
            foreach ($cells as $column => $value) {
                $columnNumber = $this->columnNumber($column);
                if ($columnNumber <= $labelColumnNumber || $columnNumber > $labelColumnNumber + 15) {
                    continue;
                }
                if (preg_match('/^([1-4])(?:st|nd|rd|th)$/i', trim((string) $value), $match)) {
                    $periodColumns[(int) $match[1]] = $column;
                }
            }
        }

        if ($learningAreasPosition === null || count($periodColumns) < 4) {
            return [];
        }

        ksort($periodColumns);
        $grades = [];
        foreach (array_slice($rows, $learningAreasPosition + 1, 24) as $row) {
            $cells = $this->cellsByColumn($row);
            $label = trim((string) ($cells[$labelColumn] ?? ''));
            if (preg_match('/^(GENERAL AVERAGE|LEARNING MODALITY|ATTENDANCE REPORT)$/i', $label)) {
                break;
            }

            $subject = $label ?: trim((string) ($cells[$this->columnName($labelColumnNumber + 1)] ?? ''));
            $normalizedSubject = LearningAreaNormalizer::label($subject);
            if ($normalizedSubject === null) {
                continue;
            }
            foreach ($periodColumns as $period => $column) {
                $grade = $this->number($cells[$column] ?? null);
                if ($grade !== null && $grade > 0) {
                    $grades[$normalizedSubject][$period] = $grade;
                }
            }
        }

        return $grades;
    }

    private function isYearEndWorkbook(string $path): bool
    {
        foreach ($this->reader->read($path) as $sheet) {
            $values = [];
            foreach ($sheet['rows'] ?? [] as $row) {
                foreach ($row['cells'] ?? [] as $cell) {
                    $value = trim((string) ($cell['value'] ?? ''));
                    if ($value !== '') {
                        $values[] = $value;
                    }
                }
            }
            $contents = implode("\n", $values);
            if (preg_match('/\bPROGRESS REPORT CARD\b/i', $contents)
                && preg_match('/\bATTENDANCE REPORT\b/i', $contents)
                && preg_match('/\bGRADING PERIODS\b/i', $contents)) {
                return true;
            }
        }

        return false;
    }

    private function yearEndRecords(string $path, bool $attendanceOnly): array
    {
        $records = [];
        foreach ($this->reader->read($path) as $sheet) {
            $rows = $sheet['rows'] ?? [];
            foreach ($rows as $rowPosition => $row) {
                $cells = $this->cellsByColumn($row);
                foreach ($cells as $labelColumn => $label) {
                    if (! preg_match('/^student\s*no\.?$/i', trim((string) $label))) {
                        continue;
                    }

                    $studentNumber = $this->normalizeStudentNumber($this->valueToRight($cells, $labelColumn, 4));
                    if (! $this->isStudentNumber($studentNumber) || (float) $studentNumber === 0.0) {
                        continue;
                    }

                    $name = '';
                    $level = '';
                    $lrn = '';
                    $labelColumnNumber = $this->columnNumber($labelColumn);
                    for ($offset = 1; $offset <= 4; $offset++) {
                        $nearby = $rows[$rowPosition + $offset] ?? null;
                        if (! $nearby) {
                            continue;
                        }
                        $nearbyCells = $this->cellsByColumn($nearby);
                        $nearbyLabel = mb_strtolower(trim((string) ($nearbyCells[$labelColumn] ?? '')));
                        if ($nearbyLabel === 'name') {
                            $name = $this->valueToRight($nearbyCells, $labelColumn, 4);
                        } elseif (str_contains($nearbyLabel, 'level')) {
                            $level = $this->valueToRight($nearbyCells, $labelColumn, 4);
                        }
                        foreach ($nearbyCells as $nearbyColumn => $nearbyValue) {
                            $nearbyColumnNumber = $this->columnNumber($nearbyColumn);
                            if ($nearbyColumnNumber < $labelColumnNumber || $nearbyColumnNumber > $labelColumnNumber + 15) {
                                continue;
                            }
                            if (preg_match('/^lrn$/i', trim((string) $nearbyValue))) {
                                $lrn = $this->normalizeLrn($this->valueToRight($nearbyCells, $nearbyColumn, 4));
                                break;
                            }
                        }
                    }

                    if ($this->gradeLevel($level) === null) {
                        continue;
                    }

                    $record = ['student_number' => $studentNumber, 'lrn' => $lrn, 'name' => $name];
                    if ($attendanceOnly) {
                        $record['months'] = $this->yearEndAttendance($rows, $rowPosition, $labelColumn);
                        if ($record['months'] !== []) {
                            $records[$studentNumber] = $record;
                        }
                    } else {
                        $record['grades'] = $this->yearEndGrades($rows, $rowPosition, $labelColumn);
                        if ($record['grades'] !== []) {
                            $records[$studentNumber] = $record;
                        }
                    }
                }
            }
        }

        if ($records === []) {
            throw new RuntimeException($attendanceOnly
                ? 'No student attendance records with a student number were found in the year-end workbook.'
                : 'No fourth-quarter grade records with a student number were found in the year-end workbook.');
        }

        return array_values($records);
    }

    private function yearEndGrades(array $rows, int $studentRowPosition, string $labelColumn): array
    {
        $grades = [];
        $labelNumber = $this->columnNumber($labelColumn);
        $gradeColumn = $this->columnName($labelNumber + 11);
        $insideSubjects = false;

        foreach (array_slice($rows, $studentRowPosition + 1, 24) as $row) {
            $cells = $this->cellsByColumn($row);
            $label = trim((string) ($cells[$labelColumn] ?? ''));
            if (preg_match('/^LEARNING AREAS$/i', $label)) {
                $insideSubjects = true;

                continue;
            }
            if (! $insideSubjects) {
                continue;
            }
            if (preg_match('/^(GENERAL AVERAGE|LEARNING MODALITY|ATTENDANCE REPORT)$/i', $label)) {
                break;
            }

            $subject = $label ?: trim((string) ($cells[$this->columnName($labelNumber + 1)] ?? ''));
            $grade = $this->number($cells[$gradeColumn] ?? null);
            if ($subject !== '' && $grade !== null && $grade > 0) {
                $grades[$subject] = $grade;
            }
        }

        return $grades;
    }

    private function yearEndAttendance(array $rows, int $studentRowPosition, string $labelColumn): array
    {
        $labelNumber = $this->columnNumber($labelColumn);
        $attendancePosition = null;
        foreach (array_slice($rows, $studentRowPosition + 1, 35, true) as $position => $row) {
            $cells = $this->cellsByColumn($row);
            if (preg_match('/^ATTENDANCE REPORT$/i', trim((string) ($cells[$labelColumn] ?? '')))) {
                $attendancePosition = $position;
                break;
            }
        }
        if ($attendancePosition === null) {
            return [];
        }

        $header = [];
        $schoolDays = [];
        $daysPresent = [];
        foreach (array_slice($rows, $attendancePosition + 1, 8) as $row) {
            $candidate = $this->cellsByColumn($row);
            $rowLabel = mb_strtolower(trim((string) ($candidate[$labelColumn] ?? '')));
            if ($rowLabel === 'days of school') {
                $schoolDays = $candidate;

                continue;
            }
            if ($rowLabel === 'days present') {
                $daysPresent = $candidate;

                continue;
            }
            $monthCount = collect($candidate)->filter(fn ($value) => $this->monthName((string) $value) !== null)->count();
            if ($monthCount >= 2) {
                $header = $candidate;
            }
        }
        $months = [];
        foreach ($header as $column => $month) {
            $columnNumber = $this->columnNumber($column);
            if ($columnNumber <= $labelNumber || $columnNumber > $labelNumber + 14) {
                continue;
            }
            $normalizedMonth = $this->monthName((string) $month);
            if ($normalizedMonth === null) {
                continue;
            }
            $schoolDayValue = $this->integer($schoolDays[$column] ?? null);
            $presentValue = $this->integer($daysPresent[$column] ?? null);
            if (($schoolDayValue ?? 0) <= 0 && ($presentValue ?? 0) <= 0) {
                continue;
            }
            $months[$normalizedMonth] = ['school_days' => $schoolDayValue, 'days_present' => $presentValue];
        }

        return $months;
    }

    private function valueToRight(array $cells, string $labelColumn, int $distance): string
    {
        $start = $this->columnNumber($labelColumn);
        for ($offset = 1; $offset <= $distance; $offset++) {
            $value = trim((string) ($cells[$this->columnName($start + $offset)] ?? ''));
            if ($value !== '' && $value !== ':') {
                return $value;
            }
        }

        return '';
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

    private function monthName(string $value): ?string
    {
        $months = [
            'jan' => 'January', 'feb' => 'February', 'mar' => 'March', 'apr' => 'April',
            'may' => 'May', 'jun' => 'June', 'jul' => 'July', 'aug' => 'August',
            'sep' => 'September', 'sept' => 'September', 'oct' => 'October',
            'nov' => 'November', 'dec' => 'December',
        ];

        return $months[mb_strtolower(trim($value))] ?? null;
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
