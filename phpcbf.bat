@echo off
echo Bil24 Connector PHPCBF Code Beautifier and Fixer
echo =================================================

REM Check if PHP is available
php --version >nul 2>&1
if %errorlevel% neq 0 (
    echo Error: PHP is not available in PATH
    echo Please install PHP or add it to your PATH environment variable
    pause
    exit /b 1
)

REM Check if PHPCBF files exist
if not exist "tools\phpcbf.phar" (
    echo Error: PHPCBF not found at tools\phpcbf.phar
    echo Please ensure PHPCBF is properly installed
    pause
    exit /b 1
)

if not exist "phpcs.xml.dist" (
    echo Error: PHPCS configuration file not found
    echo Please ensure phpcs.xml.dist exists in the project root
    pause
    exit /b 1
)

echo Running PHPCBF to fix code automatically...
echo.
php tools\phpcbf.phar --standard=phpcs.xml.dist %*

echo.
echo PHPCBF fix completed.
echo Run phpcs.bat to check if all issues are resolved.
pause 