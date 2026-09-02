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
$resolvedOutput = [System.IO.Path]::GetFullPath($OutputDirectory)
New-Item -ItemType Directory -Force -Path $resolvedOutput | Out-Null

$periods = @(
    @{ Word = 'First';  Label = 'FIRST GRADING' },
    @{ Word = 'Second'; Label = 'SECOND GRADING' },
    @{ Word = 'Third';  Label = 'THIRD GRADING' },
    @{ Word = 'Fourth'; Label = 'FOURTH GRADING' }
)

$excel = $null
try {
    $excel = New-Object -ComObject Excel.Application
    $excel.Visible = $false
    $excel.DisplayAlerts = $false
    $excel.AskToUpdateLinks = $false
    foreach ($period in $periods) {
        $summaryName = "Student_Summary_2021-2022_$($period.Word)_Grading.xlsx"
        $summaryPath = Join-Path $resolvedOutput $summaryName
        Copy-Item -LiteralPath $summarySource -Destination $summaryPath -Force

        $summaryBook = $excel.Workbooks.Open($summaryPath, 0, $false)
        try {
            $sheet = $summaryBook.Worksheets.Item('Summary Sheet')
            $sheet.Range('A3').Value2 = 'Academic Year 2021-2022'
            $sheet.Range('A4').Value2 = $period.Label
            $sheet.Range('H3').Value2 = 'Louisse Chua'
            $sheet.Range('H4').Value2 = 'Grade 2 - Amity'
            $summaryBook.Save()
        }
        finally {
            $summaryBook.Close($true)
            [void][System.Runtime.InteropServices.Marshal]::ReleaseComObject($summaryBook)
        }

        $attendanceName = "Student_Attendance_2021-2022_$($period.Word)_Grading.xlsx"
        $attendancePath = Join-Path $resolvedOutput $attendanceName
        Copy-Item -LiteralPath $attendanceSource -Destination $attendancePath -Force

        $attendanceBook = $excel.Workbooks.Open($attendancePath, 0, $false)
        try {
            $sheet = $attendanceBook.Worksheets.Item('Attendance Sheet')
            $sheet.Range('A3').Value2 = 'Academic Year 2021-2022'
            $sheet.Range('C4').Value2 = 'Grade 2 - Amity'
            $sheet.Range('J4').Value2 = 'Louisse Chua'
            $sheet.Range('D5').Value2 = $period.Label
            $attendanceBook.Save()
        }
        finally {
            $attendanceBook.Close($true)
            [void][System.Runtime.InteropServices.Marshal]::ReleaseComObject($attendanceBook)
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

Get-ChildItem -LiteralPath $resolvedOutput -Filter '*.xlsx' |
    Sort-Object Name |
    Select-Object Name, Length, LastWriteTime
