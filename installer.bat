@echo off
setlocal enabledelayedexpansion

echo [~] Checking for PHP...
php -v >nul 2>&1
if %errorlevel% neq 0 (
    echo [!] PHP is not installed or not in PATH.
    echo [i] Please install PHP from https://windows.php.net/download/
    pause
    exit /b
)

echo [~] Checking for MySQL...
mysql --version >nul 2>&1
if %errorlevel% neq 0 (
    echo [!] MySQL is not installed or not in PATH.
    echo [i] Please install MySQL/MariaDB (e.g. via XAMPP).
    pause
    exit /b
)

echo [~] Setting up Database...
:: These should be changed to match your local setup
set DB_USER=root
set DB_PASS=
set DB_NAME=ttt

mysql -u%DB_USER% -p%DB_PASS% -e "CREATE DATABASE IF NOT EXISTS %DB_NAME%;"
if %errorlevel% neq 0 (
    echo [!] Failed to create database. Check your MySQL credentials in this BAT file.
    pause
    exit /b
)

mysql -u%DB_USER% -p%DB_PASS% %DB_NAME% < c2/db.sql
if %errorlevel% neq 0 (
    echo [!] Failed to import db.sql.
    pause
    exit /b
)

echo [~] Starting PHP Built-in Server...
echo [i] Panel will be available at http://localhost:8000
echo [i] Press Ctrl+C to stop the server.

cd c2
php -S localhost:8000
