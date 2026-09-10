<?php

namespace App\Support;

final class AcademicPeriod
{
    public const THREE_TERM_START_YEAR = 2026;

    public static function usesTerms(string $schoolYear): bool
    {
        return preg_match('/^(\d{4})-\d{4}$/', trim($schoolYear), $match) === 1
            && (int) $match[1] >= self::THREE_TERM_START_YEAR;
    }

    public static function count(string $schoolYear): int
    {
        return self::usesTerms($schoolYear) ? 3 : 4;
    }

    public static function numbers(string $schoolYear): array
    {
        return range(1, self::count($schoolYear));
    }

    public static function label(string $schoolYear, int $period): string
    {
        $name = ['First', 'Second', 'Third', 'Fourth'][$period - 1] ?? "Period {$period}";

        return $name.' '.(self::usesTerms($schoolYear) ? 'term' : 'grading');
    }
}
