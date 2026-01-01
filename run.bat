@echo off
setlocal enabledelayedexpansion

echo [1/3] Checking dependencies...
where php >nul 2>nul
if %errorlevel% neq 0 (
    echo Error: PHP is not installed or not in PATH.
    pause
    exit /b
)

echo [2/3] Setting up environment...
if exist "c2\panel\index.php" (
    set PANEL_DIR=c2\panel
) else if exist "c2\index.php" (
    set PANEL_DIR=c2
) else (
    echo Error: Panel files not found.
    pause
    exit /b
)

echo [3/3] Starting Pin FREE server...
set PORT=5000
:checkport
netstat -ano | findstr :%PORT% >nul
if %errorlevel% equ 0 (
    echo Port %PORT% is in use, trying !PORT! + 1...
    set /a PORT+=1
    goto checkport
)

echo Panel will be available at http://localhost:%PORT%
echo Press Ctrl+C to stop the server.

cd %PANEL_DIR%
php -S localhost:%PORT%
pause
