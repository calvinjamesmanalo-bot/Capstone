<?php

$elementaryGradeLevels = [
    'Kinder',
    ...array_map(static fn (int $grade): string => "Grade {$grade}", range(1, 6)),
];

$juniorHighSchoolGradeLevels = array_map(
    static fn (int $grade): string => "Grade {$grade}",
    range(7, 10),
);
$latestSchoolYearStart = max(2026, (int) date('Y') + 1);

return [
    'school_years' => array_map(
        static fn (int $startYear): string => $startYear.'-'.($startYear + 1),
        range(2010, $latestSchoolYearStart),
    ),
    'grade_level_groups' => [
        'Elementary (Kinder to Grade 6)' => $elementaryGradeLevels,
        'Junior High School (Grade 7 to Grade 10)' => $juniorHighSchoolGradeLevels,
    ],
    'grade_levels' => [
        ...$elementaryGradeLevels,
        ...$juniorHighSchoolGradeLevels,
    ],
];
