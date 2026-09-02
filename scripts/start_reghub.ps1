$ErrorActionPreference = 'Stop'

$projectRoot = (Resolve-Path (Join-Path $PSScriptRoot '..')).Path

function Resolve-PhpExecutable {
    $xamppPhp = 'C:\xampp\php\php.exe'
    if (Test-Path -LiteralPath $xamppPhp) {
        return $xamppPhp
    }

    $phpCommand = Get-Command php -ErrorAction SilentlyContinue
    if ($phpCommand) {
        return $phpCommand.Source
    }

    throw 'PHP was not found. Install PHP or make php.exe available in PATH.'
}

function Test-ListeningPort([int] $port) {
    $client = [System.Net.Sockets.TcpClient]::new()
    try {
        $connection = $client.BeginConnect('127.0.0.1', $port, $null, $null)
        if (-not $connection.AsyncWaitHandle.WaitOne(500)) {
            return $false
        }
        $client.EndConnect($connection)
        return $true
    } catch {
        return $false
    } finally {
        $client.Dispose()
    }
}

function Start-RegHubService(
    [string] $name,
    [string] $workingDirectory,
    [int] $port,
    [string] $phpExecutable
) {
    if (Test-ListeningPort $port) {
        Write-Host "[ready] $name is already running on port $port." -ForegroundColor Green
        return
    }

    Write-Host "[start] Starting $name on port $port..." -ForegroundColor Cyan
    Start-Process `
        -FilePath $phpExecutable `
        -ArgumentList @('artisan', 'serve', '--host=127.0.0.1', "--port=$port") `
        -WorkingDirectory $workingDirectory `
        -WindowStyle Hidden

    for ($attempt = 0; $attempt -lt 20; $attempt++) {
        Start-Sleep -Milliseconds 250
        if (Test-ListeningPort $port) {
            Write-Host "[ready] $name is running on port $port." -ForegroundColor Green
            return
        }
    }

    throw "$name did not start on port $port. Run its artisan serve command in a terminal to see the startup error."
}

$phpExecutable = Resolve-PhpExecutable
Start-RegHubService 'RegHub' $projectRoot 8000 $phpExecutable

Write-Host ''
Write-Host 'RegHub is ready. F137 and F138 are integrated on the same server.' -ForegroundColor Green
Write-Host 'Open: http://127.0.0.1:8000'
