param(
    [Parameter(Mandatory = $true)]
    [string[]]$Paths,
    [Parameter(Mandatory = $true)]
    [string]$OutputDirectory
)

$ErrorActionPreference = 'Stop'
$resolvedOutput = [System.IO.Path]::GetFullPath($OutputDirectory)
New-Item -ItemType Directory -Force -Path $resolvedOutput | Out-Null

$excel = $null
try {
    $excel = New-Object -ComObject Excel.Application
    $excel.Visible = $false
    $excel.DisplayAlerts = $false
    $excel.AskToUpdateLinks = $false

    foreach ($path in $Paths) {
        $resolvedPath = (Resolve-Path -LiteralPath $path).Path
        $workbook = $excel.Workbooks.Open($resolvedPath, 0, $true)
        try {
            Write-Output "WORKBOOK: $resolvedPath"
            foreach ($worksheet in $workbook.Worksheets) {
                $used = $worksheet.UsedRange
                Write-Output ("SHEET: {0}; USED: {1}" -f $worksheet.Name, $used.Address())
                for ($row = 1; $row -le $used.Rows.Count; $row++) {
                    $items = @()
                    for ($col = 1; $col -le $used.Columns.Count; $col++) {
                        $cell = $used.Cells.Item($row, $col)
                        $text = [string]$cell.Text
                        if ($text.Trim() -ne '') {
                            $address = $cell.Address($false, $false)
                            $items += "$address=$text"
                        }
                    }
                    if ($items.Count -gt 0) {
                        Write-Output ($items -join ' | ')
                    }
                }
            }

            $pdfName = [System.IO.Path]::GetFileNameWithoutExtension($resolvedPath) + '.pdf'
            $pdfPath = Join-Path $resolvedOutput $pdfName
            $workbook.ExportAsFixedFormat(0, $pdfPath)
            Write-Output "PDF: $pdfPath"
        }
        finally {
            $workbook.Close($false)
            [void][System.Runtime.InteropServices.Marshal]::ReleaseComObject($workbook)
        }
    }
}
finally {
    if ($excel -ne $null) {
        $excel.Quit()
        [void][System.Runtime.InteropServices.Marshal]::ReleaseComObject($excel)
    }
    [GC]::Collect()
    [GC]::WaitForPendingFinalizers()
}
