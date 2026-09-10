<?php

namespace App\Support;

use DOMDocument;
use DOMXPath;
use RuntimeException;
use SimpleXMLElement;
use ZipArchive;

class XlsxWorkbookReader
{
    public function read(string $filePath): array
    {
        $zip = new ZipArchive;

        if ($zip->open($filePath) !== true) {
            throw new RuntimeException('Unable to open the uploaded Excel file.');
        }

        $workbook = $this->readXml($zip, 'xl/workbook.xml');
        $relationships = $this->readXml($zip, 'xl/_rels/workbook.xml.rels');
        $sharedStrings = $this->readSharedStrings($zip);
        $sheetPaths = $this->resolveSheetPaths($workbook, $relationships);

        if ($sheetPaths === []) {
            for ($index = 0; $index < $zip->numFiles; $index++) {
                $path = $zip->getNameIndex($index);
                if ($path !== false && preg_match('#^xl/worksheets/sheet\d+\.xml$#', $path)) {
                    $sheetPaths['Sheet '.(count($sheetPaths) + 1)] = $path;
                }
            }
        }

        $sheets = [];

        foreach ($sheetPaths as $sheetName => $sheetPath) {
            $rows = $this->readSheetRows($zip, $sheetPath, $sharedStrings);

            if ($rows === []) {
                continue;
            }

            $sheets[] = [
                'name' => $sheetName,
                'rows' => $rows,
            ];
        }

        $zip->close();

        return $sheets;
    }

    private function readSharedStrings(ZipArchive $zip): array
    {
        $sharedStringsXml = $zip->getFromName('xl/sharedStrings.xml');

        if ($sharedStringsXml === false) {
            return [];
        }

        $sharedStrings = new SimpleXMLElement($sharedStringsXml);
        $entries = $sharedStrings->xpath('//*[local-name()="si"]') ?: [];

        return array_map(fn (SimpleXMLElement $entry) => $this->extractInlineString($entry), $entries);
    }

    private function resolveSheetPaths(SimpleXMLElement $workbook, SimpleXMLElement $relationships): array
    {
        $workbookDocument = new DOMDocument;
        $workbookDocument->loadXML($workbook->asXML());
        $workbookXPath = new DOMXPath($workbookDocument);
        $relationshipDocument = new DOMDocument;
        $relationshipDocument->loadXML($relationships->asXML());
        $relationshipXPath = new DOMXPath($relationshipDocument);
        $relationshipMap = [];

        foreach ($relationshipXPath->query('//*[local-name()="Relationship"]') as $relationship) {
            $relationshipMap[$relationship->getAttribute('Id')] = $this->normalizeSheetPath($relationship->getAttribute('Target'));
        }

        $sheetPaths = [];

        foreach ($workbookXPath->query('//*[local-name()="sheets"]/*[local-name()="sheet"]') as $sheetIndex => $sheet) {
            $name = $sheet->getAttribute('name');
            $relationshipId = $sheet->getAttributeNS('http://schemas.openxmlformats.org/officeDocument/2006/relationships', 'id');
            $target = $relationshipMap[$relationshipId] ?? 'xl/worksheets/sheet'.($sheetIndex + 1).'.xml';

            if ($name !== '' && $target !== null) {
                $sheetPaths[$name] = $target;
            }
        }

        return $sheetPaths;
    }

    private function readSheetRows(ZipArchive $zip, string $sheetPath, array $sharedStrings): array
    {
        $worksheet = $this->readXml($zip, $sheetPath);
        $rowNodes = $worksheet->xpath('//*[local-name()="sheetData"]/*[local-name()="row"]') ?: [];
        $rows = [];

        foreach ($rowNodes as $rowNode) {
            $cells = [];

            foreach ($rowNode->xpath('./*[local-name()="c"]') ?: [] as $cell) {
                $reference = (string) $cell['r'];
                $column = preg_replace('/\d+/', '', $reference) ?: 'A';
                $value = $this->resolveCellValue($cell, $sharedStrings);

                if ($value === '') {
                    continue;
                }

                $cells[] = [
                    'column' => $column,
                    'value' => $value,
                    'order' => $this->columnIndex($column),
                ];
            }

            if ($cells === []) {
                continue;
            }

            usort($cells, fn (array $left, array $right) => $left['order'] <=> $right['order']);

            $rows[] = [
                'index' => (int) ($rowNode['r'] ?: 0),
                'cells' => array_map(fn (array $cell) => [
                    'column' => $cell['column'],
                    'value' => $cell['value'],
                ], $cells),
            ];
        }

        return $rows;
    }

    private function resolveCellValue(SimpleXMLElement $cell, array $sharedStrings): string
    {
        $type = (string) $cell['t'];

        if ($type === 'inlineStr') {
            return trim($this->extractInlineString($cell));
        }

        $valueNodes = $cell->xpath('./*[local-name()="v"]') ?: [];
        $value = isset($valueNodes[0]) ? trim((string) $valueNodes[0]) : '';

        if ($type === 's') {
            return trim($sharedStrings[(int) $value] ?? '');
        }

        if ($type === 'b') {
            return $value === '1' ? 'TRUE' : 'FALSE';
        }

        if ($value === '') {
            $formulaNodes = $cell->xpath('./*[local-name()="f"]') ?: [];

            if (isset($formulaNodes[0])) {
                return trim((string) $formulaNodes[0]);
            }
        }

        return $value;
    }

    private function extractInlineString(SimpleXMLElement $node): string
    {
        $parts = [];

        foreach ($node->xpath('.//*[local-name()="t"]') ?: [] as $textNode) {
            $parts[] = (string) $textNode;
        }

        return trim(implode('', $parts));
    }

    private function normalizeSheetPath(string $target): string
    {
        $target = str_replace('\\', '/', $target);

        if (str_starts_with($target, '/')) {
            return ltrim($target, '/');
        }

        return 'xl/'.ltrim($target, './');
    }

    private function readXml(ZipArchive $zip, string $path): SimpleXMLElement
    {
        $contents = $zip->getFromName($path);

        if ($contents === false) {
            throw new RuntimeException("Missing expected workbook data: {$path}");
        }

        return new SimpleXMLElement($contents);
    }

    private function columnIndex(string $column): int
    {
        $index = 0;

        foreach (str_split(strtoupper($column)) as $character) {
            $index = ($index * 26) + (ord($character) - 64);
        }

        return $index;
    }
}
