param(
    [Parameter(Mandatory = $true)]
    [string[]]$Paths,
    [Parameter(Mandatory = $true)]
    [string]$OutputDirectory
)

$ErrorActionPreference = 'Stop'
Add-Type -AssemblyName System.Windows.Forms
Add-Type -AssemblyName System.Drawing
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
            foreach ($worksheet in $workbook.Worksheets) {
                $worksheet.Activate()
                $used = $worksheet.UsedRange
                $used.CopyPicture(1, 2)
                Start-Sleep -Milliseconds 750
                $image = [System.Windows.Forms.Clipboard]::GetImage()
                if ($null -eq $image) {
                    throw "Excel did not place an image of $($worksheet.Name) on the clipboard."
                }
                try {
                    $baseName = [System.IO.Path]::GetFileNameWithoutExtension($resolvedPath)
                    $safeSheet = ($worksheet.Name -replace '[^A-Za-z0-9_-]', '_')
                    $pngPath = Join-Path $resolvedOutput "$baseName-$safeSheet.png"
                    $image.Save($pngPath, [System.Drawing.Imaging.ImageFormat]::Png)
                    Write-Output $pngPath
                }
                finally {
                    $image.Dispose()
                    [System.Windows.Forms.Clipboard]::Clear()
                }
            }
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
