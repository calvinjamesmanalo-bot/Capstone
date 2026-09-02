@echo off
setlocal
powershell.exe -NoProfile -ExecutionPolicy Bypass -File "%~dp0scripts\start_reghub.ps1"
if errorlevel 1 (
    echo.
    echo Startup failed. Review the message above.
)
echo.
pause
