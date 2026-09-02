param(
    [Parameter(Mandatory = $true)]
    [string]$SummaryTemplate,
    [Parameter(Mandatory = $true)]
    [string]$AttendanceTemplate,
    [Parameter(Mandatory = $true)]
    [string]$OutputDirectory
)

$ErrorActionPreference = 'Stop'
$summarySource = (Resolve-Path -LiteralPath $SummaryTemplate).Path
$attendanceSource = (Resolve-Path -LiteralPath $AttendanceTemplate).Path
$resolvedOutput = (Resolve-Path -LiteralPath $OutputDirectory).Path
$periodLabels = @{
    'First' = 'FIRST GRADING'
    'Second' = 'SECOND GRADING'
    'Third' = 'THIRD GRADING'
    'Fourth' = 'FOURTH GRADING'
}

function Read-Students {
    param($Worksheet, [int]$StartRow)
    $rows = @()
    for ($row = $StartRow; $row -lt ($StartRow + 5); $row++) {
        $rows += [pscustomobject]@{
            Id = [string]$Worksheet.Cells.Item($row, 2).Text
            Name = [string]$Worksheet.Cells.Item($row, 3).Text
        }
    }
    return $rows
}

function Compare-Students {
    param([object[]]$Expected, [object[]]$Actual, [string]$FileName)
    for ($index = 0; $index -lt $Expected.Count; $index++) {
        if ($Expected[$index].Id -ne $Actual[$index].Id -or $Expected[$index].Name -ne $Actual[$index].Name) {
            throw "Student mismatch in $FileName at list position $($index + 1)."
        }
    }
}

$excel = $null
try {
    $excel = New-Object -ComObject Excel.Application
    $excel.Visible = $false
    $excel.DisplayAlerts = $false
    $excel.AskToUpdateLinks = $false

    $summaryTemplateBook = $excel.Workbooks.Open($summarySource, 0, $true)
    try {
        $expectedSummaryStudents = Read-Students $summaryTemplateBook.Worksheets.Item('Summary Sheet') 7
    }
    finally {
        $summaryTemplateBook.Close($false)
        [void][System.Runtime.InteropServices.Marshal]::ReleaseComObject($summaryTemplateBook)
    }

    $attendanceTemplateBook = $excel.Workbooks.Open($attendanceSource, 0, $true)
    try {
        $expectedAttendanceStudents = Read-Students $attendanceTemplateBook.Worksheets.Item('Attendance Sheet') 8
    }
    finally {
        $attendanceTemplateBook.Close($false)
        [void][System.Runtime.InteropServices.Marshal]::ReleaseComObject($attendanceTemplateBook)
    }

    $files = @(Get-ChildItem -LiteralPath $resolvedOutput -Filter '*.xlsx' | Sort-Object Name)
    if ($files.Count -ne 8) {
        throw "Expected exactly 8 .xlsx files, found $($files.Count)."
    }

    foreach ($file in $files) {
        if ($file.Name -notmatch '^Student_(Attendance|Summary)_2021-2022_(First|Second|Third|Fourth)_Grading\.xlsx$') {
            throw "Unexpected output filename: $($file.Name)"
        }

        $fileType = $Matches[1]
        $periodWord = $Matches[2]
        $expectedPeriod = $periodLabels[$periodWord]
        $workbook = $excel.Workbooks.Open($file.FullName, 0, $true)
        try {
            if ($fileType -eq 'Summary') {
                $sheet = $workbook.Worksheets.Item('Summary Sheet')
                if ([string]$sheet.Range('A3').Text -ne 'Academic Year 2021-2022') { throw "Bad school year in $($file.Name)." }
                if ([string]$sheet.Range('A4').Text -ne $expectedPeriod) { throw "Bad grading period in $($file.Name)." }
                if ([string]$sheet.Range('H3').Text -ne 'Louisse Chua') { throw "Bad adviser in $($file.Name)." }
                if ([string]$sheet.Range('H4').Text -ne 'Grade 2 - Amity') { throw "Bad class in $($file.Name)." }
                Compare-Students $expectedSummaryStudents (Read-Students $sheet 7) $file.Name
                $formulaCount = 0
                for ($row = 7; $row -le 11; $row++) {
                    if ($sheet.Cells.Item($row, 9).HasFormula) { $formulaCount++ }
                }
                if ($formulaCount -ne 5) { throw "Expected 5 general-average formulas in $($file.Name), found $formulaCount." }
            }
            else {
                $sheet = $workbook.Worksheets.Item('Attendance Sheet')
                if ([string]$sheet.Range('A3').Text -ne 'Academic Year 2021-2022') { throw "Bad school year in $($file.Name)." }
                if ([string]$sheet.Range('C4').Text -ne 'Grade 2 - Amity') { throw "Bad class in $($file.Name)." }
                if ([string]$sheet.Range('J4').Text -ne 'Louisse Chua') { throw "Bad adviser in $($file.Name)." }
                if ([string]$sheet.Range('D5').Text -ne $expectedPeriod) { throw "Bad grading period in $($file.Name)." }
                Compare-Students $expectedAttendanceStudents (Read-Students $sheet 8) $file.Name
                $formulaCount = 0
                for ($row = 8; $row -le 12; $row++) {
                    if ($sheet.Cells.Item($row, 14).HasFormula) { $formulaCount++ }
                }
                if ($formulaCount -ne 5) { throw "Expected 5 attendance-total formulas in $($file.Name), found $formulaCount." }
            }

            $used = $sheet.UsedRange
            for ($row = 1; $row -le $used.Rows.Count; $row++) {
                for ($col = 1; $col -le $used.Columns.Count; $col++) {
                    $text = [string]$used.Cells.Item($row, $col).Text
                    if ($text -match '^#(REF!|DIV/0!|VALUE!|NAME\?|N/A|NUM!|NULL!)$') {
                        throw "Formula error $text in $($file.Name)."
                    }
                }
            }

            Write-Output ("PASS: {0}; students=5; formulas={1}; period={2}" -f $file.Name, $formulaCount, $expectedPeriod)
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
