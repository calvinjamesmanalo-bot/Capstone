<?php

namespace App\Support;

final class CurriculumSubjects
{
    public static function for(string $level, string $section): array
    {
        foreach (config("academics.shs_subject_groups.{$level}", []) as $group) {
            if (in_array($section, $group['sections'] ?? [], true)) {
                return $group['subjects'] ?? [];
            }
        }

        $subjects = config("academics.subjects.{$level}", []);
        if ($subjects !== []) {
            return $subjects;
        }

        $groups = config("academics.shs_subject_groups.{$level}", []);
        if ($groups === []) {
            return [];
        }

        // Sections present in the enrollment report but absent from the
        // subject reference receive only subjects common to every known
        // group. Uploaded subject headings remain authoritative.
        return array_values(array_reduce(
            array_slice($groups, 1),
            fn (array $common, array $group): array => array_values(array_intersect($common, $group['subjects'] ?? [])),
            array_values($groups)[0]['subjects'] ?? [],
        ));
    }

    public static function sections(): array
    {
        return array_values(array_unique(array_merge(...array_values(config('academics.sections_by_grade', [])))));
    }
}
