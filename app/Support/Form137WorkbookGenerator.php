<?php

namespace App\Support;

use DOMDocument;
use DOMElement;
use DOMXPath;
use RuntimeException;
use ZipArchive;

class Form137WorkbookGenerator
{
    private array $emphasizedStyles = [];

    private const ELEMENTARY_RECORD_SLOTS = [
        ['sheet' => 'front', 'meta_row' => 23, 'heading_row' => 28, 'first_row' => 30, 'last_row' => 44, 'general_row' => 45, 'side' => 'left', 'lower' => false],
        ['sheet' => 'front', 'meta_row' => 23, 'heading_row' => 28, 'first_row' => 30, 'last_row' => 44, 'general_row' => 45, 'side' => 'right', 'lower' => false],
        ['sheet' => 'front', 'meta_row' => 52, 'heading_row' => 58, 'first_row' => 60, 'last_row' => 74, 'general_row' => 75, 'side' => 'left', 'lower' => true],
        ['sheet' => 'front', 'meta_row' => 52, 'heading_row' => 58, 'first_row' => 60, 'last_row' => 74, 'general_row' => 75, 'side' => 'right', 'lower' => true],
        ['sheet' => 'back', 'meta_row' => 3, 'heading_row' => 8, 'first_row' => 10, 'last_row' => 24, 'general_row' => 25, 'side' => 'left', 'lower' => false],
        ['sheet' => 'back', 'meta_row' => 3, 'heading_row' => 8, 'first_row' => 10, 'last_row' => 24, 'general_row' => 25, 'side' => 'right', 'lower' => false],
        ['sheet' => 'back', 'meta_row' => 32, 'heading_row' => 37, 'first_row' => 39, 'last_row' => 53, 'general_row' => 54, 'side' => 'left', 'lower' => true],
        ['sheet' => 'back', 'meta_row' => 32, 'heading_row' => 37, 'first_row' => 39, 'last_row' => 53, 'general_row' => 54, 'side' => 'right', 'lower' => true],
    ];

    private const JHS_RECORD_SLOTS = [
        ['sheet' => 'front', 'meta_row' => 21, 'heading_row' => 24, 'first_row' => 26, 'last_row' => 39, 'general_row' => 40],
        ['sheet' => 'front', 'meta_row' => 49, 'heading_row' => 52, 'first_row' => 54, 'last_row' => 67, 'general_row' => 68],
        ['sheet' => 'back', 'meta_row' => 3, 'heading_row' => 6, 'first_row' => 8, 'last_row' => 21, 'general_row' => 22],
        ['sheet' => 'back', 'meta_row' => 31, 'heading_row' => 34, 'first_row' => 36, 'last_row' => 49, 'general_row' => 50],
        ['sheet' => 'back', 'meta_row' => 59, 'heading_row' => 62, 'first_row' => 64, 'last_row' => 77, 'general_row' => 78],
    ];

    public function generate(array $student, array $records, array $schoolProfile = [], string $schoolLevel = 'elementary'): string
    {
        return $schoolLevel === 'jhs'
            ? $this->generateJuniorHighSchool($student, $records, $schoolProfile)
            : $this->generateElementary($student, $records, $schoolProfile);
    }

    private function generateElementary(array $student, array $records, array $schoolProfile): string
    {
        $template = base_path('generator/F137.xlsx');

        [$zip, $xlsxPath] = $this->openTemplate($template);
        $worksheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
        $backXml = $zip->getFromName('xl/worksheets/sheet2.xml');
        $stylesXml = $zip->getFromName('xl/styles.xml');
        if ($worksheetXml === false || $backXml === false || $stylesXml === false) {
            $zip->close();
            @unlink($xlsxPath);
            throw new RuntimeException('The F137 template worksheet is missing.');
        }

        [$document, $xpath] = $this->worksheet($worksheetXml);
        [$backDocument, $backXpath] = $this->worksheet($backXml);
        $worksheets = [
            'front' => [$document, $xpath],
            'back' => [$backDocument, $backXpath],
        ];

        $stylesDocument = new DOMDocument;
        $stylesDocument->preserveWhiteSpace = false;
        $stylesDocument->formatOutput = false;
        $stylesDocument->loadXML($stylesXml);
        $stylesXpath = new DOMXPath($stylesDocument);
        $stylesXpath->registerNamespace('x', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
        $this->emphasizedStyles = [];

        $this->configureReadableLayout($document, $xpath);
        $valueStyles = [
            'identity' => $this->emphasizedStyle($stylesDocument, $stylesXpath, 82),
            'region_left' => $this->emphasizedStyle($stylesDocument, $stylesXpath, 54),
            'region_right' => $this->emphasizedStyle($stylesDocument, $stylesXpath, 157),
        ];

        if (count($records) > count(self::ELEMENTARY_RECORD_SLOTS)) {
            $zip->close();
            @unlink($xlsxPath);
            throw new RuntimeException('The elementary F137 template can contain at most eight school-year records.');
        }

        $this->writeElementaryStudent($document, $xpath, $student, $valueStyles['identity']);
        foreach (self::ELEMENTARY_RECORD_SLOTS as $index => $slot) {
            [$slotDocument, $slotXpath] = $worksheets[$slot['sheet']];
            $record = $records[$index] ?? null;
            $this->clearElementarySlot($slotDocument, $slotXpath, $slot);
            if ($record && AcademicPeriod::usesTerms($record['school_year'])) {
                $this->configureElementaryTermSlot($slotDocument, $slotXpath, $slot);
            }
            $this->writeElementarySchoolProfile($slotDocument, $slotXpath, $slot, $schoolProfile, $valueStyles);
            if ($record) {
                $this->writeElementaryRecord($slotDocument, $slotXpath, $slot, $record);
            }
        }
        $this->writeElementaryCertification($backDocument, $backXpath, $student, $schoolProfile, $records);

        $zip->addFromString('xl/worksheets/sheet1.xml', $document->saveXML());
        $zip->addFromString('xl/worksheets/sheet2.xml', $backDocument->saveXML());
        $zip->addFromString('xl/styles.xml', $stylesDocument->saveXML());
        $zip->close();

        return $xlsxPath;
    }

    private function generateJuniorHighSchool(array $student, array $records, array $schoolProfile): string
    {
        $template = base_path('generator/F137-JHS-3TERM.xlsx');
        [$zip, $xlsxPath] = $this->openTemplate($template);
        $frontXml = $zip->getFromName('xl/worksheets/sheet1.xml');
        $backXml = $zip->getFromName('xl/worksheets/sheet3.xml');
        if ($frontXml === false || $backXml === false) {
            $zip->close();
            @unlink($xlsxPath);
            throw new RuntimeException('The JHS F137 template worksheets are missing.');
        }
        if (count($records) > count(self::JHS_RECORD_SLOTS)) {
            $zip->close();
            @unlink($xlsxPath);
            throw new RuntimeException('The JHS F137 template can contain at most five school-year records.');
        }

        [$frontDocument, $frontXpath] = $this->worksheet($frontXml);
        [$backDocument, $backXpath] = $this->worksheet($backXml);
        $worksheets = [
            'front' => [$frontDocument, $frontXpath],
            'back' => [$backDocument, $backXpath],
        ];

        $this->writeJhsStudent($frontDocument, $frontXpath, $student);
        foreach (self::JHS_RECORD_SLOTS as $index => $slot) {
            [$slotDocument, $slotXpath] = $worksheets[$slot['sheet']];
            $record = $records[$index] ?? null;
            $this->clearJhsSlot($slotDocument, $slotXpath, $slot);
            if ($record && ! AcademicPeriod::usesTerms($record['school_year'])) {
                $this->configureJhsQuarterSlot($slotDocument, $slotXpath, $slot);
            }
            $this->writeJhsSchoolProfile($slotDocument, $slotXpath, $slot, $schoolProfile);
            if ($record) {
                $this->writeJhsRecord($slotDocument, $slotXpath, $slot, $record);
            }
        }

        foreach ([[$frontDocument, $frontXpath, 79, 80], [$backDocument, $backXpath, 89, 90]] as [$certDocument, $certXpath, $identityRow, $schoolRow]) {
            $this->writeJhsCertification($certDocument, $certXpath, $student, $schoolProfile, $records, $identityRow, $schoolRow);
        }
        foreach ([[$frontDocument, $frontXpath, ['BD43', 'BD71']], [$backDocument, $backXpath, ['BD25', 'BD53', 'BD81']]] as [$sheetDocument, $sheetXpath, $references]) {
            foreach ($references as $reference) {
                $this->setText($sheetDocument, $sheetXpath, $reference, '');
            }
        }

        $zip->addFromString('xl/worksheets/sheet1.xml', $frontDocument->saveXML());
        $zip->addFromString('xl/worksheets/sheet3.xml', $backDocument->saveXML());
        $zip->close();

        return $xlsxPath;
    }

    private function openTemplate(string $template): array
    {
        if (! is_file($template)) {
            throw new RuntimeException('The F137 Excel template is missing.');
        }

        $output = tempnam(sys_get_temp_dir(), 'f137-');
        if ($output === false) {
            throw new RuntimeException('Unable to create the F137 workbook.');
        }

        $xlsxPath = $output.'.xlsx';
        @unlink($output);
        if (! copy($template, $xlsxPath)) {
            throw new RuntimeException('Unable to copy the F137 Excel template.');
        }

        $zip = new ZipArchive;
        if ($zip->open($xlsxPath) !== true) {
            @unlink($xlsxPath);
            throw new RuntimeException('Unable to open the F137 Excel template.');
        }

        return [$zip, $xlsxPath];
    }

    private function worksheet(string $xml): array
    {
        $document = new DOMDocument;
        $document->preserveWhiteSpace = false;
        $document->formatOutput = false;
        $document->loadXML($xml);
        $xpath = new DOMXPath($document);
        $xpath->registerNamespace('x', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');

        return [$document, $xpath];
    }

    private function writeElementaryStudent(DOMDocument $document, DOMXPath $xpath, array $student, int $valueStyle): void
    {
        $name = $student['name_parts'];
        $this->setText($document, $xpath, 'B9', 'LAST NAME:');
        $this->setText($document, $xpath, 'D9', $name['last']);
        $this->setStyle($document, $xpath, 'D9', $valueStyle);
        $this->setText($document, $xpath, 'I9', 'FIRST NAME:');
        $this->setText($document, $xpath, 'L9', $name['first']);
        $this->setStyle($document, $xpath, 'L9', $valueStyle);
        $this->setText($document, $xpath, 'Q9', 'NAME EXTN.:');
        $this->setText($document, $xpath, 'S9', $name['extension']);
        $this->setStyle($document, $xpath, 'S9', $valueStyle);
        $this->setText($document, $xpath, 'T9', 'MIDDLE NAME:');
        $this->setText($document, $xpath, 'AA9', $name['middle']);
        $this->setStyle($document, $xpath, 'AA9', $valueStyle);
        $this->setText($document, $xpath, 'B10', 'Learner Reference Number (LRN):');
        $this->setText($document, $xpath, 'G10', (string) ($student['lrn'] ?: ''));
        $this->setStyle($document, $xpath, 'G10', $valueStyle);
    }

    private function writeElementarySchoolProfile(DOMDocument $document, DOMXPath $xpath, array $slot, array $profile, array $valueStyles): void
    {
        $school = trim((string) ($profile['school'] ?? ''));
        $schoolId = trim((string) ($profile['school_id'] ?? ''));
        $district = trim((string) ($profile['district'] ?? ''));
        $division = trim((string) ($profile['division'] ?? ''));
        $region = trim((string) ($profile['region'] ?? ''));
        $row = $slot['meta_row'];

        if ($slot['side'] === 'right') {
            $this->setText($document, $xpath, 'Q'.$row, 'School: '.$school);
            $this->setText($document, $xpath, 'AC'.$row, 'School ID: '.$schoolId);
            $this->setText($document, $xpath, 'Q'.($row + 1), 'District: '.$district.'    Division: '.$division);
            $this->setText($document, $xpath, 'AB'.($row + 1), 'Region:');
            $this->setText($document, $xpath, 'AD'.($row + 1), $region);
            $this->setStyle($document, $xpath, 'AD'.($row + 1), $valueStyles['region_right']);

            return;
        }

        $this->setText($document, $xpath, 'B'.$row, 'School: '.$school.($slot['lower'] ? '    School ID: '.$schoolId : ''));
        if (! $slot['lower']) {
            $this->setText($document, $xpath, 'J'.$row, 'School ID:');
            $this->setText($document, $xpath, 'N'.$row, $schoolId);
        }
        $this->setText($document, $xpath, 'B'.($row + 1), 'District: '.$district.'    Division: '.$division);
        $this->setText($document, $xpath, ($slot['lower'] ? 'M' : 'L').($row + 1), 'Region:');
        $this->setText($document, $xpath, 'N'.($row + 1), $region);
        $this->setStyle($document, $xpath, 'N'.($row + 1), $valueStyles['region_left']);
    }

    private function configureReadableLayout(DOMDocument $document, DOMXPath $xpath): void
    {
        $this->replaceMerge($document, $xpath, 'I9:P9', ['I9:K9', 'L9:P9']);
        $this->replaceMerge($document, $xpath, 'L24:N24', ['L24:M24', 'N24:O24']);
        $this->replaceMerge($document, $xpath, 'M53:N53', ['N53:O53']);
    }

    private function replaceMerge(DOMDocument $document, DOMXPath $xpath, string $old, array $replacements): void
    {
        $mergeCells = $xpath->query('//x:mergeCells')->item(0);
        if (! $mergeCells instanceof DOMElement) {
            return;
        }
        foreach (iterator_to_array($mergeCells->childNodes) as $merge) {
            if ($merge instanceof DOMElement && $merge->getAttribute('ref') === $old) {
                $mergeCells->removeChild($merge);
            }
        }
        foreach ($replacements as $reference) {
            $merge = $document->createElementNS($document->documentElement->namespaceURI, 'mergeCell');
            $merge->setAttribute('ref', $reference);
            $mergeCells->appendChild($merge);
        }
        $mergeCells->setAttribute('count', (string) $mergeCells->getElementsByTagNameNS($document->documentElement->namespaceURI, 'mergeCell')->length);
    }

    private function emphasizedStyle(DOMDocument $document, DOMXPath $xpath, int $baseStyle): int
    {
        if (isset($this->emphasizedStyles[$baseStyle])) {
            return $this->emphasizedStyles[$baseStyle];
        }
        $fonts = $xpath->query('//x:fonts')->item(0);
        $cellXfs = $xpath->query('//x:cellXfs')->item(0);
        $baseXf = $xpath->query('//x:cellXfs/x:xf')->item($baseStyle);
        if (! $fonts instanceof DOMElement || ! $cellXfs instanceof DOMElement || ! $baseXf instanceof DOMElement) {
            throw new RuntimeException('The F137 template styles are incomplete.');
        }
        $fontId = (int) $baseXf->getAttribute('fontId');
        $baseFont = $xpath->query('//x:fonts/x:font')->item($fontId);
        if (! $baseFont instanceof DOMElement) {
            throw new RuntimeException('The F137 template font is missing.');
        }
        $font = $baseFont->cloneNode(true);
        foreach (['b', 'i'] as $tag) {
            if ($font->getElementsByTagNameNS($document->documentElement->namespaceURI, $tag)->length === 0) {
                $font->appendChild($document->createElementNS($document->documentElement->namespaceURI, $tag));
            }
        }
        $fonts->appendChild($font);
        $newFontId = $fonts->getElementsByTagNameNS($document->documentElement->namespaceURI, 'font')->length - 1;
        $fonts->setAttribute('count', (string) ($newFontId + 1));
        $xf = $baseXf->cloneNode(true);
        $xf->setAttribute('fontId', (string) $newFontId);
        $xf->setAttribute('applyFont', '1');
        $cellXfs->appendChild($xf);
        $newStyle = $cellXfs->getElementsByTagNameNS($document->documentElement->namespaceURI, 'xf')->length - 1;
        $cellXfs->setAttribute('count', (string) ($newStyle + 1));

        return $this->emphasizedStyles[$baseStyle] = $newStyle;
    }

    private function setStyle(DOMDocument $document, DOMXPath $xpath, string $reference, int $style): void
    {
        $this->cell($document, $xpath, $reference)->setAttribute('s', (string) $style);
    }

    private function writeElementaryRecord(DOMDocument $document, DOMXPath $xpath, array $slot, array $record): void
    {
        $columns = $this->elementaryColumns($slot, AcademicPeriod::usesTerms($record['school_year']));
        $this->setText($document, $xpath, $columns['grade'].($slot['meta_row'] + 2), $record['level'].'   Section: '.$record['section']);
        $this->setText($document, $xpath, $columns['year'].($slot['meta_row'] + 2), $record['school_year']);
        $this->setText($document, $xpath, $columns['adviser'].($slot['meta_row'] + 3), 'Name of Adviser/Teacher: '.($record['adviser_name'] ?? ''));

        $capacity = $slot['last_row'] - $slot['first_row'] + 1;
        if (count($record['areas']) > $capacity) {
            throw new RuntimeException(
                "The F137 template has {$capacity} learning-area rows, but {$record['school_year']} contains ".count($record['areas']).' subjects.'
            );
        }

        foreach ($record['areas'] as $areaIndex => $area) {
            $row = $slot['first_row'] + $areaIndex;
            $this->setText($document, $xpath, $columns['label'].$row, (string) $area['name']);
            foreach ($columns['periods'] as $periodIndex => $column) {
                $grade = $area['quarters'][$periodIndex + 1] ?? null;
                if ($grade !== null) {
                    $this->setNumber($document, $xpath, $column.$row, $grade);
                }
            }
            if ($area['final'] !== null) {
                $this->setNumber($document, $xpath, $columns['final'].$row, $area['final']);
            }
            $this->setText($document, $xpath, $columns['remarks'].$row, $area['remarks']);
        }

        if ($record['general_average'] !== null) {
            $this->setNumber($document, $xpath, $columns['final'].$slot['general_row'], $record['general_average']);
        }
        $this->setText($document, $xpath, $columns['remarks'].$slot['general_row'], $record['remarks']);
    }

    private function clearElementarySlot(DOMDocument $document, DOMXPath $xpath, array $slot): void
    {
        $columns = $this->elementaryColumns($slot, false);
        $this->setText($document, $xpath, $columns['grade'].($slot['meta_row'] + 2), '');
        $this->setText($document, $xpath, $columns['year'].($slot['meta_row'] + 2), '');
        $this->setText($document, $xpath, $columns['adviser'].($slot['meta_row'] + 3), 'Name of Adviser/Teacher:');

        for ($row = $slot['first_row']; $row <= $slot['general_row']; $row++) {
            if ($row <= $slot['last_row']) {
                $this->setText($document, $xpath, $columns['label'].$row, '');
            }
            foreach ($columns['periods'] as $column) {
                $this->clearLiteralValue($document, $xpath, $column.$row);
            }
            $this->clearCachedFormulaValue($document, $xpath, $columns['final'].$row);
            $this->clearCachedFormulaValue($document, $xpath, $columns['remarks'].$row);
        }
    }

    private function elementaryColumns(array $slot, bool $usesTerms): array
    {
        if ($slot['side'] === 'left') {
            return [
                'label' => 'B', 'grade' => 'E', 'year' => 'N', 'adviser' => 'B',
                'periods' => $usesTerms ? ['H', 'I', 'J'] : ['G', 'H', 'I', 'J'],
                'final' => 'K', 'remarks' => 'N',
            ];
        }

        return [
            'label' => 'Q', 'grade' => 'S', 'year' => 'AC', 'adviser' => 'Q',
            'periods' => $usesTerms ? ['Z', 'AA', 'AB'] : ['X', 'Z', 'AA', 'AB'],
            'final' => 'AC', 'remarks' => 'AD',
        ];
    }

    private function configureElementaryTermSlot(DOMDocument $document, DOMXPath $xpath, array $slot): void
    {
        $heading = $slot['heading_row'];
        $numberRow = $heading + 1;

        if ($slot['side'] === 'left') {
            $this->replaceMerge($document, $xpath, "B{$heading}:F{$numberRow}", ["B{$heading}:G{$numberRow}"]);
            $this->replaceMerge($document, $xpath, "G{$heading}:J{$heading}", ["H{$heading}:J{$heading}"]);
            $this->setText($document, $xpath, 'H'.$heading, 'Term Rating');
            $this->clearLiteralValue($document, $xpath, 'G'.$numberRow);
            foreach ([1 => 'H', 2 => 'I', 3 => 'J'] as $number => $column) {
                $this->setNumber($document, $xpath, $column.$numberRow, $number);
            }
            for ($row = $slot['first_row']; $row <= $slot['general_row']; $row++) {
                $this->replaceMerge($document, $xpath, "B{$row}:F{$row}", ["B{$row}:G{$row}"]);
                $this->clearLiteralValue($document, $xpath, 'G'.$row);
            }

            return;
        }

        $this->replaceMerge($document, $xpath, "Q{$heading}:W{$numberRow}", ["Q{$heading}:Y{$numberRow}"]);
        $this->replaceMerge($document, $xpath, "X{$heading}:AB{$heading}", ["Z{$heading}:AB{$heading}"]);
        $this->replaceMerge($document, $xpath, "X{$numberRow}:Y{$numberRow}", []);
        $this->setText($document, $xpath, 'Z'.$heading, 'Term Rating');
        foreach (['X', 'Y'] as $column) {
            $this->clearLiteralValue($document, $xpath, $column.$numberRow);
        }
        foreach ([1 => 'Z', 2 => 'AA', 3 => 'AB'] as $number => $column) {
            $this->setNumber($document, $xpath, $column.$numberRow, $number);
        }
        for ($row = $slot['first_row']; $row <= $slot['general_row']; $row++) {
            $this->replaceMerge($document, $xpath, "Q{$row}:W{$row}", ["Q{$row}:Y{$row}"]);
            $this->replaceMerge($document, $xpath, "X{$row}:Y{$row}", []);
            foreach (['X', 'Y'] as $column) {
                $this->clearLiteralValue($document, $xpath, $column.$row);
            }
        }
    }

    private function writeElementaryCertification(DOMDocument $document, DOMXPath $xpath, array $student, array $profile, array $records): void
    {
        $this->setText($document, $xpath, 'B62', 'I CERTIFY that this is a true record of '.$this->studentFullName($student).' with LRN '.($student['lrn'] ?: '').'.');
        $lastSchoolYear = $records === [] ? '' : (string) $records[array_key_last($records)]['school_year'];
        $this->setText(
            $document,
            $xpath,
            'C63',
            'School Name: '.($profile['school'] ?? '').'    School ID: '.($profile['school_id'] ?? '').'    Division: '.($profile['division'] ?? '').'    Last School Year Attended: '.$lastSchoolYear
        );
        $this->setText($document, $xpath, 'C64', 'COPY VALID FOR:');
        $this->setText($document, $xpath, 'F65', '');
        $this->setText($document, $xpath, 'P65', '');
    }

    private function writeJhsStudent(DOMDocument $document, DOMXPath $xpath, array $student): void
    {
        $name = $student['name_parts'];
        foreach (['G7' => $name['last'], 'W7' => $name['first'], 'AN7' => $name['extension'], 'AX7' => $name['middle'], 'M8' => (string) ($student['lrn'] ?: '')] as $reference => $value) {
            $this->setText($document, $xpath, $reference, $value);
        }
    }

    private function clearJhsSlot(DOMDocument $document, DOMXPath $xpath, array $slot): void
    {
        $meta = $slot['meta_row'];
        foreach (['E'.$meta, 'U'.$meta, 'AD'.$meta, 'AP'.$meta, 'BB'.$meta, 'I'.($meta + 1), 'N'.($meta + 1), 'V'.($meta + 1), 'AI'.($meta + 1)] as $reference) {
            $this->setText($document, $xpath, $reference, '');
        }
        for ($row = $slot['first_row']; $row <= $slot['last_row']; $row++) {
            $this->setText($document, $xpath, 'B'.$row, '');
            foreach (['Y', 'AC', 'AG', 'AJ', 'AP'] as $column) {
                $this->clearLiteralValue($document, $xpath, $column.$row);
            }
        }
        foreach (['AJ', 'AP'] as $column) {
            $this->clearLiteralValue($document, $xpath, $column.$slot['general_row']);
        }
    }

    private function writeJhsSchoolProfile(DOMDocument $document, DOMXPath $xpath, array $slot, array $profile): void
    {
        $row = $slot['meta_row'];
        foreach ([
            'E'.$row => $profile['school'] ?? '',
            'U'.$row => $profile['school_id'] ?? '',
            'AD'.$row => $profile['district'] ?? '',
            'AP'.$row => $profile['division'] ?? '',
            'BB'.$row => $profile['region'] ?? '',
        ] as $reference => $value) {
            $this->setText($document, $xpath, $reference, trim((string) $value));
        }
    }

    private function writeJhsRecord(DOMDocument $document, DOMXPath $xpath, array $slot, array $record): void
    {
        $meta = $slot['meta_row'];
        $this->setText($document, $xpath, 'I'.($meta + 1), $record['level']);
        $this->setText($document, $xpath, 'N'.($meta + 1), $record['section']);
        $this->setText($document, $xpath, 'V'.($meta + 1), $record['school_year']);
        $this->setText($document, $xpath, 'AI'.($meta + 1), (string) ($record['adviser_name'] ?? ''));

        $capacity = $slot['last_row'] - $slot['first_row'] + 1;
        if (count($record['areas']) > $capacity) {
            throw new RuntimeException(
                "The JHS F137 template has {$capacity} learning-area rows, but {$record['school_year']} contains ".count($record['areas']).' subjects.'
            );
        }

        $periodColumns = AcademicPeriod::usesTerms($record['school_year']) ? ['Y', 'AC', 'AG'] : ['Y', 'AB', 'AE', 'AH'];
        foreach ($record['areas'] as $areaIndex => $area) {
            $row = $slot['first_row'] + $areaIndex;
            $this->setText($document, $xpath, 'B'.$row, (string) $area['name']);
            foreach ($periodColumns as $periodIndex => $column) {
                $grade = $area['quarters'][$periodIndex + 1] ?? null;
                if ($grade !== null) {
                    $this->setNumber($document, $xpath, $column.$row, $grade);
                }
            }
            if ($area['final'] !== null) {
                $this->setNumber($document, $xpath, 'AJ'.$row, $area['final']);
            }
            $this->setText($document, $xpath, 'AP'.$row, $area['remarks']);
        }
        if ($record['general_average'] !== null) {
            $this->setNumber($document, $xpath, 'AJ'.$slot['general_row'], $record['general_average']);
        }
        $this->setText($document, $xpath, 'AP'.$slot['general_row'], $record['remarks']);
    }

    private function configureJhsQuarterSlot(DOMDocument $document, DOMXPath $xpath, array $slot): void
    {
        $heading = $slot['heading_row'];
        $numberRow = $heading + 1;
        $this->setText($document, $xpath, 'Y'.$heading, 'Quarterly Rating');
        $this->replaceJhsPeriodMerges($document, $xpath, $numberRow);
        foreach ([1 => 'Y', 2 => 'AB', 3 => 'AE', 4 => 'AH'] as $number => $column) {
            $this->setNumber($document, $xpath, $column.$numberRow, $number);
        }
        foreach (['AC', 'AG'] as $column) {
            $this->clearLiteralValue($document, $xpath, $column.$numberRow);
        }
        for ($row = $slot['first_row']; $row <= $slot['last_row']; $row++) {
            $this->replaceJhsPeriodMerges($document, $xpath, $row);
        }
    }

    private function replaceJhsPeriodMerges(DOMDocument $document, DOMXPath $xpath, int $row): void
    {
        $this->replaceMerge($document, $xpath, "Y{$row}:AB{$row}", []);
        $this->replaceMerge($document, $xpath, "AC{$row}:AF{$row}", []);
        $this->replaceMerge($document, $xpath, "AG{$row}:AI{$row}", ["Y{$row}:AA{$row}", "AB{$row}:AD{$row}", "AE{$row}:AG{$row}", "AH{$row}:AI{$row}"]);
    }

    private function writeJhsCertification(DOMDocument $document, DOMXPath $xpath, array $student, array $profile, array $records, int $identityRow, int $schoolRow): void
    {
        $lastSchoolYear = $records === [] ? '' : (string) $records[array_key_last($records)]['school_year'];
        $lastLevel = $records === [] ? '' : (string) $records[array_key_last($records)]['level'];
        $nextGrade = ((int) filter_var($lastLevel, FILTER_SANITIZE_NUMBER_INT)) + 1;
        $this->setText($document, $xpath, 'O'.$identityRow, $this->studentFullName($student));
        $this->setText($document, $xpath, 'AF'.$identityRow, (string) ($student['lrn'] ?: ''));
        $this->setText($document, $xpath, 'BB'.$identityRow, $nextGrade > 1 && $nextGrade <= 10 ? (string) $nextGrade : '');
        $this->setText($document, $xpath, 'H'.$schoolRow, (string) ($profile['school'] ?? ''));
        $this->setText($document, $xpath, 'AC'.$schoolRow, (string) ($profile['school_id'] ?? ''));
        $this->setText($document, $xpath, 'AS'.$schoolRow, $lastSchoolYear);
    }

    private function studentFullName(array $student): string
    {
        return trim(implode(' ', array_filter([
            $student['name_parts']['first'] ?? '',
            $student['name_parts']['middle'] ?? '',
            $student['name_parts']['last'] ?? '',
            $student['name_parts']['extension'] ?? '',
        ])));
    }

    private function clearLiteralValue(DOMDocument $document, DOMXPath $xpath, string $reference): void
    {
        $cell = $this->cell($document, $xpath, $reference);
        foreach ($cell->childNodes as $child) {
            if ($child->localName === 'f') {
                return;
            }
        }
        $this->clearCellValue($cell);
        $cell->removeAttribute('t');
    }

    private function clearCachedFormulaValue(DOMDocument $document, DOMXPath $xpath, string $reference): void
    {
        $cell = $this->cell($document, $xpath, $reference);
        $hasFormula = false;
        foreach ($cell->childNodes as $child) {
            if ($child->localName === 'f') {
                $hasFormula = true;
            }
        }
        if (! $hasFormula) {
            $this->clearCellValue($cell);
            $cell->removeAttribute('t');

            return;
        }
        foreach (iterator_to_array($cell->childNodes) as $child) {
            if ($child->localName === 'v') {
                $cell->removeChild($child);
            }
        }
    }

    private function setText(DOMDocument $document, DOMXPath $xpath, string $reference, string $value): void
    {
        $cell = $this->cell($document, $xpath, $reference);
        $this->clearCellValue($cell);
        $cell->setAttribute('t', 'inlineStr');
        $inline = $document->createElementNS($document->documentElement->namespaceURI, 'is');
        $text = $document->createElementNS($document->documentElement->namespaceURI, 't');
        $text->appendChild($document->createTextNode($value));
        $inline->appendChild($text);
        $cell->appendChild($inline);
    }

    private function setNumber(DOMDocument $document, DOMXPath $xpath, string $reference, float|int|string $value): void
    {
        $cell = $this->cell($document, $xpath, $reference);
        $this->clearCellValue($cell);
        $cell->removeAttribute('t');
        $number = $document->createElementNS($document->documentElement->namespaceURI, 'v');
        $number->appendChild($document->createTextNode((string) $value));
        $cell->appendChild($number);
    }

    private function cell(DOMDocument $document, DOMXPath $xpath, string $reference): DOMElement
    {
        $nodes = $xpath->query('//x:c[@r="'.$reference.'"]');
        if ($nodes !== false && $nodes->length > 0) {
            return $nodes->item(0);
        }

        preg_match('/(\d+)$/', $reference, $matches);
        $rowNumber = (int) ($matches[1] ?? 0);
        $rowNodes = $xpath->query('//x:row[@r="'.$rowNumber.'"]');
        if ($rowNodes === false || $rowNodes->length === 0) {
            throw new RuntimeException("The template is missing row {$rowNumber}.");
        }

        $cell = $document->createElementNS($document->documentElement->namespaceURI, 'c');
        $cell->setAttribute('r', $reference);
        $rowNodes->item(0)->appendChild($cell);

        return $cell;
    }

    private function clearCellValue(DOMElement $cell): void
    {
        foreach (iterator_to_array($cell->childNodes) as $child) {
            if (in_array($child->localName, ['v', 'f', 'is'], true)) {
                $cell->removeChild($child);
            }
        }
    }
}
