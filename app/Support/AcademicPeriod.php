<?php

namespace App\Support;

final class AcademicPeriod
{
    public const THREE_TERM_START_YEAR = 2026;

    public static function usesTerms(string $schoolYear, ?string $level = null): bool
    {
        if (preg_match('/^(\d{4})-\d{4}$/', trim($schoolYear), $match) !== 1) {
            return false;
        }

        $startYear = (int) $match[1];
        $grade = (int) filter_var((string) $level, FILTER_SANITIZE_NUMBER_INT);

        // The SHS curriculum transition starts with Grade 11 in 2026 and
        // reaches Grade 12 the following school year.
        $termStartYear = $grade === 12 ? self::THREE_TERM_START_YEAR + 1 : self::THREE_TERM_START_YEAR;

        return $startYear >= $termStartYear;
    }

    public static function count(string $schoolYear, ?string $level = null): int
    {
        return self::usesTerms($schoolYear, $level) ? 3 : 4;
    }

    public static function numbers(string $schoolYear, ?string $level = null): array
    {
        return range(1, self::count($schoolYear, $level));
    }

    public static function label(string $schoolYear, int $period, ?string $level = null): string
    {
        $name = ['First', 'Second', 'Third', 'Fourth'][$period - 1] ?? "Period {$period}";

        return $name.' '.(self::usesTerms($schoolYear, $level) ? 'term' : 'grading');
    }
}
