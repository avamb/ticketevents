@echo off
echo Bil24 Connector PHPCS Code Sniffer
echo ==================================

REM Check if PHP is available
php --version >nul 2>&1
if %errorlevel% neq 0 (
    echo Error: PHP is not available in PATH
    echo Please install PHP or add it to your PATH environment variable
    pause
    exit /b 1
)

REM Check if PHPCS files exist
if not exist "tools\phpcs.phar" (
    echo Error: PHPCS not found at tools\phpcs.phar
    echo Please ensure PHPCS is properly installed
    pause
    exit /b 1
)

if not exist "phpcs.xml.dist" (
    echo Error: PHPCS configuration file not found
    echo Please ensure phpcs.xml.dist exists in the project root
    pause
    exit /b 1
)

echo Running PHPCS...
echo.
php tools\phpcs.phar --standard=phpcs.xml.dist %*

echo.
echo PHPCS check completed.
echo To fix issues automatically, run: phpcbf.bat
pause 