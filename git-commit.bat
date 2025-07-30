@echo off
echo Начинаем коммит изменений Bil24 API...
echo.

echo Проверяем статус git:
git status
echo.

echo Добавляем измененные файлы:
git add includes/Api/Client.php
git add includes/Admin/SettingsPage.php
git add test-api-connection.php
git add FIX-CONNECTION-ERROR.md
git add git-commit.bat
echo.

echo Делаем коммит:
git commit -m "Исправление ошибки подключения к Bil24 API

- Улучшен метод test_connection() с несколькими endpoints
- Добавлена детальная обработка HTTP ошибок (401, 403, 404, 500+)
- Улучшен AJAX handler с проверкой credentials
- Создан независимый тестер подключения (test-api-connection.php)
- Добавлено подробное логирование для диагностики
- Информативные сообщения об ошибках на русском языке

Fixes: #api-connection-error"
echo.

echo Отправляем на GitHub:
git push origin main
echo.

echo Готово! Проверьте результат выше.
pause