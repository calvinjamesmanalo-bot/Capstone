<?php

namespace App\Support;

use DOMDocument;
use DOMElement;
use DOMXPath;
use RuntimeException;
use ZipArchive;

class Form137WorkbookGenerator
{
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
        $template = base_path('F137.xlsx');

        if (!is_file($template)) {
            throw new RuntimeException('The F137 Excel template is missing.');
        }

        $output = tempnam(sys_get_temp_dir(), 'f137-');
        if ($output === false) {
            throw new RuntimeException('Unable to create the F137 workbook.');
        }

        $xlsxPath = $output.'.xlsx';
        @unlink($output);

        if (!copy($template, $xlsxPath)) {
            throw new RuntimeException('Unable to copy the F137 Excel template.');
        }

        $zip = new ZipArchive();
        if ($zip->open($xlsxPath) !== true) {
            @unlink($xlsxPath);
            throw new RuntimeException('Unable to open the F137 Excel template.');
        }

        $worksheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
        if ($worksheetXml === false) {
            $zip->close();
            @unlink($xlsxPath);
            throw new RuntimeException('The F137 template worksheet is missing.');
        }

        $document = new DOMDocument();
        $document->preserveWhiteSpace = false;
        $document->formatOutput = false;
        $document->loadXML($worksheetXml);
        $xpath = new DOMXPath($document);
        $xpath->registerNamespace('x', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');

        $this->clearTemplateRecordValues($document, $xpath);
        $this->writeStudent($document, $xpath, $student);

        foreach (self::RECORD_SLOTS as $slot) {
            $this->writeSchoolProfile($document, $xpath, $slot, $schoolProfile);
        }

        foreach (array_slice($records, 0, count(self::RECORD_SLOTS)) as $index => $record) {
            $this->writeRecord($document, $xpath, self::RECORD_SLOTS[$index], $record);
        }

        $zip->addFromString('xl/worksheets/sheet1.xml', $document->saveXML());

        $backXml = $zip->getFromName('xl/worksheets/sheet2.xml');
        if ($backXml !== false) {
            $backDocument = new DOMDocument();
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

    private function writeStudent(DOMDocument $document, DOMXPath $xpath, array $student): void
    {
        $name = $student['name_parts'];
        $this->setText($document, $xpath, 'B9', 'LAST NAME:');
        $this->setText($document, $xpath, 'D9', $name['last']);
        $this->setText($document, $xpath, 'I9', 'FIRST NAME: '.$name['first']);
        $this->setText($document, $xpath, 'Q9', 'NAME EXTN.:');
        $this->setText($document, $xpath, 'S9', $name['extension']);
        $this->setText($document, $xpath, 'T9', 'MIDDLE NAME:');
        $this->setText($document, $xpath, 'AA9', $name['middle']);
        $this->setText($document, $xpath, 'B10', 'Learner Reference Number (LRN):');
        $this->setText($document, $xpath, 'G10', (string) ($student['lrn'] ?: ''));
    }

    private function writeSchoolProfile(DOMDocument $document, DOMXPath $xpath, array $slot, array $profile): void
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
            $this->setText($document, $xpath, 'L24', 'Region: '.$region);
            return;
        }

        if ($position === 'Q23') {
            $this->setText($document, $xpath, 'Q23', 'School: '.$school);
            $this->setText($document, $xpath, 'AC23', 'School ID: '.$schoolId);
            $this->setText($document, $xpath, 'Q24', 'District: '.$district.'    Division: '.$division);
            $this->setText($document, $xpath, 'AB24', 'Region: '.$region);
            return;
        }

        $schoolCell = $position === 'B52' ? 'B52' : 'Q52';
        $districtCell = $position === 'B52' ? 'B53' : 'Q53';
        $regionCell = $position === 'B52' ? 'M53' : 'AB53';
        $this->setText($document, $xpath, $schoolCell, 'School: '.$school.'    School ID: '.$schoolId);
        $this->setText($document, $xpath, $districtCell, 'District: '.$district.'    Division: '.$division);
        $this->setText($document, $xpath, $regionCell, 'Region: '.$region);
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
            if ($subjectRow > 44) continue;
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
            if ($child->localName === 'f') return;
        }
        $this->clearCellValue($cell);
        $cell->removeAttribute('t');
    }

    private function clearCachedFormulaValue(DOMDocument $document, DOMXPath $xpath, string $reference): void
    {
        $cell = $this->cell($document, $xpath, $reference);
        $hasFormula = false;
        foreach ($cell->childNodes as $child) {
            if ($child->localName === 'f') $hasFormula = true;
        }
        if (!$hasFormula) {
            $this->clearCellValue($cell);
            $cell->removeAttribute('t');
            return;
        }
        foreach (iterator_to_array($cell->childNodes) as $child) {
            if ($child->localName === 'v') $cell->removeChild($child);
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
