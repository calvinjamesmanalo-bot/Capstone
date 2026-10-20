<?php

namespace App\Support;

use RuntimeException;
use Smalot\PdfParser\Parser;

class StudentMasterlistPdfParser
{
    public function parse(string $path): array
    {
        $pdf = (new Parser)->parseFile($path);
        $rows = [];
        $schoolYear = null;
        $campus = null;
        $gradeLevel = null;
        $section = null;
        $pendingIdentity = null;

        foreach ($pdf->getPages() as $page) {
            $lines = preg_split('/\R/u', $page->getText()) ?: [];
            foreach ($lines as $index => $rawLine) {
                $line = trim((string) preg_replace('/\s+/u', ' ', $rawLine));
                if ($line === '') {
                    continue;
                }

                if ($pendingIdentity !== null) {
                    $pendingIdentity .= ' '.$line;
                    if (! preg_match('/(?:Male|Female)$/iu', $pendingIdentity)) {
                        continue;
                    }
                    $line = $pendingIdentity;
                    $pendingIdentity = null;
                } elseif (preg_match('/^\d{6,12}\s+\d+$/', $line)) {
                    $pendingIdentity = $line;

                    continue;
                }

                if (preg_match('/Academic Year\s+(\d{4})\s*-\s*(\d{4})/i', $line, $match)) {
                    $schoolYear = $match[1].'-'.$match[2];

                    continue;
                }

                if (in_array($line, ['Imus', 'Dasmariñas', 'Dasmarinas'], true)) {
                    $campus = $line;

                    continue;
                }

                if (preg_match('/^(Pre-Kinder|Kindergarten|Grade\s+(?:[1-9]|1[0-2]))$/i', $line)) {
                    $gradeLevel = $line;
                    $section = null;

                    continue;
                }

                $next = trim((string) preg_replace('/\s+/u', ' ', $lines[$index + 1] ?? ''));
                if (str_starts_with($next, 'Student No.') && ! str_starts_with($line, 'Student No.')) {
                    $section = $line;

                    continue;
                }

                if (! preg_match('/^(\d{6,12})\s+(\d+)\s+(.+?)(Male|Female)$/iu', $line, $match)) {
                    continue;
                }

                $studentNumber = $match[1];
                $rawLrn = $match[2];
                $name = trim($match[3]);
                if ($studentNumber === '' || $name === '' || $gradeLevel === null) {
                    continue;
                }

                $rows[$studentNumber] = [
                    'student_number' => $studentNumber,
                    'lrn' => preg_match('/^\d{12}$/', $rawLrn) ? $rawLrn : null,
                    'name' => $name,
                    'gender' => ucfirst(strtolower($match[4])),
                    'campus' => $campus,
                    'grade_level' => $gradeLevel,
                    'section' => $section,
                ];
            }
        }

        if ($schoolYear === null || count($rows) === 0) {
            throw new RuntimeException('The PDF does not match the Fiat Lux Student List format.');
        }

        return ['school_year' => $schoolYear, 'rows' => array_values($rows)];
    }
}
