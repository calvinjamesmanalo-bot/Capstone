<?php

namespace App\Support;

final class LearningAreaNormalizer
{
    private const LABELS = [
        'language' => 'Language',
        'reading and literacy' => 'Reading and Literacy',
        'mathematics' => 'Mathematics',
        'makabansa' => 'Makabansa',
        'good manners and right conduct' => 'Good Manners and Right Conduct',
        'mother tongue i' => 'Mother Tongue I',
        'mape' => 'MAPE',
        'music' => 'Music',
        'art' => 'Art',
        'physical education' => 'P.E.',
        'edukasyon sa pagpapakatao' => 'Edukasyon sa Pagpapakatao',
    ];

    private const ALIASES = [
        'gmrc' => 'good manners and right conduct',
        'good manners right conduct' => 'good manners and right conduct',
        'mother tongue 1' => 'mother tongue i',
        'pe' => 'physical education',
        'p e' => 'physical education',
        'arts' => 'art',
    ];

    private const SUMMARY_FIELDS = [
        'average',
        'general average',
        'gen ave',
        'final average',
    ];

    public static function key(string $learningArea): ?string
    {
        $key = mb_strtolower(trim(preg_replace('/\s+/', ' ', $learningArea)));
        $key = str_replace('&', ' and ', $key);
        $key = trim(preg_replace('/[^\pL\pN]+/u', ' ', $key));
        $key = trim(preg_replace('/\s+/', ' ', $key));
        $key = self::ALIASES[$key] ?? $key;

        return $key === '' || in_array($key, self::SUMMARY_FIELDS, true) ? null : $key;
    }

    public static function label(string $learningArea): ?string
    {
        $key = self::key($learningArea);
        if ($key === null) {
            return null;
        }

        return self::LABELS[$key] ?? trim(preg_replace('/\s+/', ' ', $learningArea));
    }

    public static function matrix(iterable $grades): array
    {
        $matrix = [];
        $labels = [];

        foreach ($grades as $grade) {
            if ($grade->grade === null) {
                continue;
            }

            $key = self::key((string) $grade->learning_area);
            if ($key === null) {
                continue;
            }

            $labels[$key] ??= self::label((string) $grade->learning_area);
            $matrix[$labels[$key]][(int) $grade->grading_period] = (float) $grade->grade;
        }

        ksort($matrix, SORT_NATURAL | SORT_FLAG_CASE);

        return $matrix;
    }
}
