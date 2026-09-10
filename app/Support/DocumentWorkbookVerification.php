<?php

namespace App\Support;

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use RuntimeException;
use ZipArchive;

class DocumentWorkbookVerification
{
    public function attachToFile(string $path, array $qr, array $details = []): void
    {
        $spreadsheet = IOFactory::load($path);
        $temporaryQr = tempnam(sys_get_temp_dir(), 'reghub-qr-');

        if ($temporaryQr === false || file_put_contents($temporaryQr, $qr['png']) === false) {
            throw new RuntimeException('Unable to prepare the document verification QR image.');
        }

        try {
            $this->attachSheet($spreadsheet, $qr, $temporaryQr, $details);
            IOFactory::createWriter($spreadsheet, 'Xlsx')->save($path);
            $this->normalizeBooleanStyleTags($path);
        } finally {
            $spreadsheet->disconnectWorksheets();
            @unlink($temporaryQr);
        }
    }

    private function normalizeBooleanStyleTags(string $path): void
    {
        $zip = new ZipArchive;
        if ($zip->open($path) !== true) {
            return;
        }

        $styles = $zip->getFromName('xl/styles.xml');
        if (is_string($styles)) {
            $styles = str_replace(['<b val="1"/>', '<i val="1"/>'], ['<b/>', '<i/>'], $styles);
            $zip->addFromString('xl/styles.xml', $styles);
        }
        $zip->close();
    }

    private function attachSheet(Spreadsheet $spreadsheet, array $qr, string $temporaryQr, array $details): void
    {
        if (($existing = $spreadsheet->getSheetByName('Verification')) !== null) {
            $spreadsheet->removeSheetByIndex($spreadsheet->getIndex($existing));
        }

        $sheet = new Worksheet($spreadsheet, 'Verification');
        $spreadsheet->addSheet($sheet);
        $sheet->setShowGridlines(false);
        $sheet->getPageSetup()->setFitToPage(true)->setFitToWidth(1)->setFitToHeight(1);
        $sheet->getPageMargins()->setTop(.35)->setRight(.35)->setBottom(.35)->setLeft(.35);
        $sheet->getColumnDimension('A')->setWidth(4);
        foreach (range('B', 'F') as $column) {
            $sheet->getColumnDimension($column)->setWidth(18);
        }

        $sheet->mergeCells('B2:F2')->setCellValue('B2', config('document_verification.issuer'));
        $sheet->mergeCells('B3:F3')->setCellValue('B3', 'SECURE DOCUMENT VERIFICATION');
        $sheet->getStyle('B2:F2')->getFont()->setBold(true)->setSize(18)->getColor()->setRGB('000638');
        $sheet->getStyle('B3:F3')->getFont()->setBold(true)->getColor()->setRGB('667085');
        $sheet->getStyle('B2:F3')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $drawing = new Drawing;
        $drawing->setName('Signed verification QR');
        $drawing->setDescription('Scan to verify this document');
        $drawing->setPath($temporaryQr);
        $drawing->setHeight(190);
        $drawing->setCoordinates('C5');
        $drawing->setOffsetX(34);
        $drawing->setWorksheet($sheet);

        $sheet->mergeCells('B17:F17')->setCellValue('B17', 'SCAN TO VERIFY');
        $sheet->mergeCells('B18:F18')->setCellValue('B18', $qr['reference']);
        $sheet->mergeCells('B19:F19')->setCellValue('B19', 'SHA-256 RECORD: '.strtoupper(substr($qr['document']->content_hash, 0, 16)));
        $sheet->mergeCells('B20:F20')->setCellValue('B20', $qr['verification_url']);
        $sheet->getCell('B20')->getHyperlink()->setUrl($qr['verification_url']);
        $sheet->getStyle('B17:F18')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('B17:F17')->getFont()->setBold(true)->getColor()->setRGB('067647');
        $sheet->getStyle('B18:F18')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('B19:F19')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('B19:F19')->getFont()->setSize(9)->getColor()->setRGB('344054');
        $sheet->getStyle('B20:F20')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setWrapText(true);
        $sheet->getStyle('B20:F20')->getFont()->setSize(8)->getColor()->setRGB('175CD3');
        $sheet->getRowDimension(20)->setRowHeight(30);

        $row = 22;
        foreach ($details as $label => $value) {
            if (is_array($value)) {
                $value = json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            }
            $sheet->setCellValue("B{$row}", ucwords(str_replace('_', ' ', (string) $label)));
            $sheet->mergeCells("C{$row}:F{$row}")->setCellValue("C{$row}", (string) $value);
            $sheet->getStyle("B{$row}")->getFont()->setBold(true)->getColor()->setRGB('667085');
            $sheet->getStyle("B{$row}:F{$row}")->getBorders()->getBottom()->getColor()->setRGB('E4E7EC');
            $row++;
        }

        $row += 1;
        $sheet->mergeCells("B{$row}:F{$row}")->setCellValue("B{$row}", 'The holder, document details, dates, and control number on the verification page must match the issued record. Any alteration voids the document.');
        $sheet->getStyle("B{$row}:F{$row}")->getAlignment()->setWrapText(true)->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getStyle("B{$row}:F{$row}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('FFFAEB');
        $sheet->getRowDimension($row)->setRowHeight(42);
        $sheet->getPageSetup()->setPrintArea("A1:F{$row}");
    }
}
