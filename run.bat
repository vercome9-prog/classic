@echo off
setlocal

echo [1/3] Checking dependencies...
where php >nul 2>nul
if %errorlevel% neq 0 (
    echo Error: PHP is not installed or not in PATH.
    echo Please install PHP from https://windows.php.net/download/
    pause
    exit /b
)

echo [2/3] Setting up environment...
:: Replit specific: The panel is usually in c2/ or c2/panel/
if exist "c2\panel\index.php" (
    set PANEL_DIR=c2\panel
) else if exist "c2\index.php" (
    set PANEL_DIR=c2
) else (
    echo Error: Panel files not found in c2/ or c2/panel/
    pause
    exit /b
)

echo [3/3] Starting PHP server...
echo Panel will be available at http://localhost:5000
echo Press Ctrl+C to stop the server.

cd %PANEL_DIR%
php -S localhost:5000
pause
