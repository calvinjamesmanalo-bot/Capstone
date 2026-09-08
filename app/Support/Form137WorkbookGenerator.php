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

    private const RECORD_SLOTS = [
        ['start_row' => 23, 'label_col' => 'B', 'grade_col' => 'E', 'year_col' => 'N', 'adviser_col' => 'B', 'quarter_cols' => ['G', 'H', 'I', 'J'], 'final_col' => 'K', 'remarks_col' => 'N'],
        ['start_row' => 23, 'label_col' => 'Q', 'grade_col' => 'S', 'year_col' => 'AC', 'adviser_col' => 'Q', 'quarter_cols' => ['X', 'Z', 'AA', 'AB'], 'final_col' => 'AC', 'remarks_col' => 'AD'],
        ['start_row' => 52, 'label_col' => 'B', 'grade_col' => 'E', 'year_col' => 'N', 'adviser_col' => 'B', 'quarter_cols' => ['G', 'H', 'I', 'J'], 'final_col' => 'K', 'remarks_col' => 'N'],
        ['start_row' => 52, 'label_col' => 'Q', 'grade_col' => 'S', 'year_col' => 'AC', 'adviser_col' => 'Q', 'quarter_cols' => ['X', 'Z', 'AA', 'AB'], 'final_col' => 'AC', 'remarks_col' => 'AD'],
    ];

    private const SUBJECT_ROWS = [
        'mother tongue' => 30,
        'filipino' => 31,
        'english' => 32,
        'mathematics' => 33,
        'science' => 34,
        'araling panlipunan' => 35,
        'epp / tle' => 36,
        'epp/tle' => 36,
        'mapeh' => 37,
        'music' => 38,
        'arts' => 39,
        'physical education' => 40,
        'health' => 41,
        'eduk. sa pagpapakatao' => 42,
        'edukasyon sa pagpapakatao' => 42,
        'arabic language' => 43,
        'islamic values education' => 44,
    ];

    public function generate(array $student, array $records, array $schoolProfile = []): string
    {
        $template = base_path('generator/F137.xlsx');

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

        $worksheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
        $stylesXml = $zip->getFromName('xl/styles.xml');
        if ($worksheetXml === false || $stylesXml === false) {
            $zip->close();
            @unlink($xlsxPath);
            throw new RuntimeException('The F137 template worksheet is missing.');
        }

        $document = new DOMDocument;
        $document->preserveWhiteSpace = false;
        $document->formatOutput = false;
        $document->loadXML($worksheetXml);
        $xpath = new DOMXPath($document);
        $xpath->registerNamespace('x', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');

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

        $this->clearTemplateRecordValues($document, $xpath);
        $this->writeStudent($document, $xpath, $student, $valueStyles['identity']);

        foreach (self::RECORD_SLOTS as $slot) {
            $this->writeSchoolProfile($document, $xpath, $slot, $schoolProfile, $valueStyles);
        }

        foreach (array_slice($records, 0, count(self::RECORD_SLOTS)) as $index => $record) {
            $this->writeRecord($document, $xpath, self::RECORD_SLOTS[$index], $record);
        }

        $zip->addFromString('xl/worksheets/sheet1.xml', $document->saveXML());
        $zip->addFromString('xl/styles.xml', $stylesDocument->saveXML());

        $backXml = $zip->getFromName('xl/worksheets/sheet2.xml');
        if ($backXml !== false) {
            $backDocument = new DOMDocument;
            $backDocument->preserveWhiteSpace = false;
            $backDocument->formatOutput = false;
            $backDocument->loadXML($backXml);
            $backXpath = new DOMXPath($backDocument);
            $backXpath->registerNamespace('x', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
            $this->clearBackTemplateValues($backDocument, $backXpath, $student);
            $zip->addFromString('xl/worksheets/sheet2.xml', $backDocument->saveXML());
        }
        $zip->close();

        return $xlsxPath;
    }

    private function writeStudent(DOMDocument $document, DOMXPath $xpath, array $student, int $valueStyle): void
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

    private function writeSchoolProfile(DOMDocument $document, DOMXPath $xpath, array $slot, array $profile, array $valueStyles): void
    {
        $school = trim((string) ($profile['school'] ?? ''));
        $schoolId = trim((string) ($profile['school_id'] ?? ''));
        $district = trim((string) ($profile['district'] ?? ''));
        $division = trim((string) ($profile['division'] ?? ''));
        $region = trim((string) ($profile['region'] ?? ''));
        $position = $slot['label_col'].$slot['start_row'];

        if ($position === 'B23') {
            $this->setText($document, $xpath, 'B23', 'School: '.$school);
            $this->setText($document, $xpath, 'J23', 'School ID:');
            $this->setText($document, $xpath, 'N23', $schoolId);
            $this->setText($document, $xpath, 'B24', 'District: '.$district.'    Division: '.$division);
            $this->setText($document, $xpath, 'L24', 'Region:');
            $this->setText($document, $xpath, 'N24', $region);
            $this->setStyle($document, $xpath, 'N24', $valueStyles['region_left']);

            return;
        }

        if ($position === 'Q23') {
            $this->setText($document, $xpath, 'Q23', 'School: '.$school);
            $this->setText($document, $xpath, 'AC23', 'School ID: '.$schoolId);
            $this->setText($document, $xpath, 'Q24', 'District: '.$district.'    Division: '.$division);
            $this->setText($document, $xpath, 'AB24', 'Region:');
            $this->setText($document, $xpath, 'AD24', $region);
            $this->setStyle($document, $xpath, 'AD24', $valueStyles['region_right']);

            return;
        }

        $schoolCell = $position === 'B52' ? 'B52' : 'Q52';
        $districtCell = $position === 'B52' ? 'B53' : 'Q53';
        $regionCell = $position === 'B52' ? 'M53' : 'AB53';
        $regionValueCell = $position === 'B52' ? 'N53' : 'AD53';
        $this->setText($document, $xpath, $schoolCell, 'School: '.$school.'    School ID: '.$schoolId);
        $this->setText($document, $xpath, $districtCell, 'District: '.$district.'    Division: '.$division);
        $this->setText($document, $xpath, $regionCell, 'Region:');
        $this->setText($document, $xpath, $regionValueCell, $region);
        $this->setStyle($document, $xpath, $regionValueCell, $position === 'B52' ? $valueStyles['region_left'] : $valueStyles['region_right']);
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

    private function writeRecord(DOMDocument $document, DOMXPath $xpath, array $slot, array $record): void
    {
        $start = $slot['start_row'];
        $offset = $start === 52 ? 30 : 0;
        $label = $slot['label_col'];

        $this->setText($document, $xpath, $slot['grade_col'].($start + 2), $record['level'].'   Section: '.$record['section']);
        $this->setText($document, $xpath, $slot['year_col'].($start + 2), $record['school_year']);
        $this->setText($document, $xpath, $slot['adviser_col'].($start + 3), 'Name of Adviser/Teacher: '.($record['adviser_name'] ?? ''));

        $usedRows = [];
        foreach ($record['areas'] as $areaIndex => $area) {
            $subjectRow = $this->subjectRow((string) $area['name']);
            if ($subjectRow === null || in_array($subjectRow, $usedRows, true)) {
                $subjectRow = 30 + $areaIndex;
            }
            if ($subjectRow > 44) {
                continue;
            }
            $usedRows[] = $subjectRow;

            $row = $subjectRow + $offset;
            $this->setText($document, $xpath, $label.$row, (string) $area['name']);
            foreach ($slot['quarter_cols'] as $periodIndex => $column) {
                $grade = $area['quarters'][$periodIndex + 1] ?? null;
                if ($grade !== null) {
                    $this->setNumber($document, $xpath, $column.$row, $grade);
                }
            }

            if ($area['final'] !== null) {
                $this->setNumber($document, $xpath, $slot['final_col'].$row, $area['final']);
            }
            $this->setText($document, $xpath, $slot['remarks_col'].$row, $area['remarks']);
        }

        $generalAverageRow = 45 + $offset;
        if ($record['general_average'] !== null) {
            $this->setNumber($document, $xpath, $slot['final_col'].$generalAverageRow, $record['general_average']);
        }
        $this->setText($document, $xpath, $slot['remarks_col'].$generalAverageRow, $record['remarks']);
    }

    private function subjectRow(string $subject): ?int
    {
        $normalized = mb_strtolower(trim(preg_replace('/\s+/', ' ', ltrim($subject, '*'))));

        return self::SUBJECT_ROWS[$normalized] ?? null;
    }

    private function clearTemplateRecordValues(DOMDocument $document, DOMXPath $xpath): void
    {
        foreach (self::RECORD_SLOTS as $slot) {
            $offset = $slot['start_row'] === 52 ? 30 : 0;
            $this->setText($document, $xpath, $slot['grade_col'].($slot['start_row'] + 2), '');
            $this->setText($document, $xpath, $slot['year_col'].($slot['start_row'] + 2), '');
            $this->setText($document, $xpath, $slot['adviser_col'].($slot['start_row'] + 3), 'Name of Adviser/Teacher:');

            for ($baseRow = 30; $baseRow <= 45; $baseRow++) {
                $row = $baseRow + $offset;
                foreach ($slot['quarter_cols'] as $column) {
                    $this->clearLiteralValue($document, $xpath, $column.$row);
                }
                $this->clearCachedFormulaValue($document, $xpath, $slot['final_col'].$row);
                $this->clearCachedFormulaValue($document, $xpath, $slot['remarks_col'].$row);
            }
        }

        $this->setText($document, $xpath, 'B52', 'School:                                      School ID:');
        $this->setText($document, $xpath, 'Q52', 'School:                                      School ID:');
    }

    private function clearBackTemplateValues(DOMDocument $document, DOMXPath $xpath, array $student): void
    {
        $quarterSets = [
            ['columns' => ['G', 'H', 'I', 'J'], 'final' => 'K', 'remarks' => 'N'],
            ['columns' => ['X', 'Z', 'AA', 'AB'], 'final' => 'AC', 'remarks' => 'AD'],
        ];

        foreach ([[10, 25], [39, 54]] as [$firstRow, $lastRow]) {
            foreach ($quarterSets as $set) {
                for ($row = $firstRow; $row <= $lastRow; $row++) {
                    foreach ($set['columns'] as $column) {
                        $this->clearLiteralValue($document, $xpath, $column.$row);
                    }
                    $this->clearCachedFormulaValue($document, $xpath, $set['final'].$row);
                    $this->clearCachedFormulaValue($document, $xpath, $set['remarks'].$row);
                }
            }
        }

        foreach (['E5', 'N5', 'S5', 'AC5', 'E34', 'N34', 'S34', 'AC34'] as $reference) {
            $this->setText($document, $xpath, $reference, '');
        }

        $name = trim(implode(' ', array_filter([
            $student['name_parts']['first'] ?? '',
            $student['name_parts']['middle'] ?? '',
            $student['name_parts']['last'] ?? '',
            $student['name_parts']['extension'] ?? '',
        ])));
        $this->setText(
            $document,
            $xpath,
            'B62',
            'I CERTIFY that this is a true record of '.$name.' with LRN '.($student['lrn'] ?: '').'.'
        );
        $this->setText($document, $xpath, 'C63', 'School Name:                         School ID:               Division:               Last School Year Attended:');
        $this->setText($document, $xpath, 'C64', 'COPY VALID FOR:');
        $this->setText($document, $xpath, 'F65', '');
        $this->setText($document, $xpath, 'P65', '');
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
