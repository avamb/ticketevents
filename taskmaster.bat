@echo off
REM Taskmaster AI - Windows Batch File
REM Usage: taskmaster.bat [command] [options]

if "%1"=="" (
    echo Taskmaster AI - Bil24 Connector Task Management
    echo ================================================
    echo.
    echo Usage: taskmaster.bat ^<command^> [options]
    echo.
    echo Commands:
    echo   list                    List all tasks
    echo   status                  Show project status
    echo   sprint ^<sprint_name^>    Show sprint details
    echo   task ^<task_id^>          Show task details
    echo   start ^<task_id^>         Start a task
    echo   complete ^<task_id^>      Complete a task
    echo   progress                Show progress report
    echo   help                    Show this help
    echo.
    echo Examples:
    echo   taskmaster.bat list
    echo   taskmaster.bat sprint sprint_1
    echo   taskmaster.bat task settings
    echo   taskmaster.bat start settings
    goto :eof
)

REM Try to find PHP in common locations
set PHP_CMD=

REM Check if PHP is in PATH
php --version >nul 2>&1
if %errorlevel% equ 0 (
    set PHP_CMD=php
    goto :run_taskmaster
)

REM Check common PHP installation paths
if exist "C:\php\php.exe" (
    set PHP_CMD=C:\php\php.exe
    goto :run_taskmaster
)

if exist "C:\xampp\php\php.exe" (
    set PHP_CMD=C:\xampp\php\php.exe
    goto :run_taskmaster
)

if exist "C:\wamp\bin\php\php8.0.0\php.exe" (
    set PHP_CMD=C:\wamp\bin\php\php8.0.0\php.exe
    goto :run_taskmaster
)

if exist "C:\wamp64\bin\php\php8.0.0\php.exe" (
    set PHP_CMD=C:\wamp64\bin\php\php8.0.0\php.exe
    goto :run_taskmaster
)

REM If PHP not found, show error
echo Error: PHP not found!
echo.
echo Please install PHP or add it to your PATH.
echo Common installation paths:
echo   - C:\php\php.exe
echo   - C:\xampp\php\php.exe
echo   - C:\wamp\bin\php\php8.0.0\php.exe
echo   - C:\wamp64\bin\php\php8.0.0\php.exe
echo.
echo Or download PHP from: https://windows.php.net/download/
goto :eof

:run_taskmaster
echo Using PHP: %PHP_CMD%
echo.
%PHP_CMD% taskmaster.php %* 