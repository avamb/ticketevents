@echo off
cd /d "%~dp0"
echo Adding files to git...
git add includes/Api/Client.php
git add includes/Admin/SettingsPage.php
git add test-api-connection.php
git add test-bil24-format.php
git add BIL24-API-FORMAT-FIX.md
git add git-commit-api-fix.bat
git add run-git.cmd

echo Committing changes...
git commit -m "КРИТИЧЕСКОЕ ИСПРАВЛЕНИЕ: Правильный формат Bil24 API - fid/token в JSON теле запроса"

echo Pushing to GitHub...
git push origin main

echo Done!
pause