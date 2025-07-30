@echo off
echo Committing 400 Bad Request fix...
git add includes/Integrations/WooCommerce/Integration.php
git add includes/Admin/SettingsPage.php
git add test-ajax-handler.php
git add CRITICAL-DEBUG-GUIDE.md
git add FIX-400-BAD-REQUEST.md
git add fix-400-error.bat
git add git-commands-400-fix.txt
git add commit.cmd

git commit -m "КРИТИЧЕСКОЕ ИСПРАВЛЕНИЕ: 400 Bad Request - убран конфликт AJAX хуков bil24_test_connection"

git push origin main

echo Done!
pause