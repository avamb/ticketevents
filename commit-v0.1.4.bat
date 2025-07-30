@echo off
echo ================================================================
echo КОММИТ ВЕРСИИ 0.1.4 - КРИТИЧЕСКОЕ ИСПРАВЛЕНИЕ BIL24 API FORMAT
echo ================================================================
echo.

echo Проверяем статус git:
git status
echo.

echo Добавляем все файлы версии 0.1.4:
git add bil24-connector.php
git add includes/Constants.php
git add includes/Api/Client.php
git add includes/Admin/SettingsPage.php
git add test-api-connection.php
git add test-bil24-format.php
git add BIL24-API-FORMAT-FIX.md
git add CHANGELOG.md
git add commit-v0.1.4.bat
echo.

echo Создаем коммит версии 0.1.4:
git commit -m "Release v0.1.4: Критическое исправление Bil24 API формата

🔥 КРИТИЧЕСКИЕ ИЗМЕНЕНИЯ:
- ИСПРАВЛЕН формат API запросов Bil24 (JSON команды вместо REST)
- API клиент полностью переписан для соответствия документации
- Аутентификация fid/token в JSON теле вместо заголовков

✅ НОВЫЕ ВОЗМОЖНОСТИ:
- Команды Bil24: GET_ALL_ACTIONS, GET_EVENTS, GET_ORDERS, GET_VENUES
- Специальный тестер test-bil24-format.php
- Подробная документация в BIL24-API-FORMAT-FIX.md
- Обратная совместимость для старых методов API

📋 ФОРМАТ ЗАПРОСА:
ДО:  GET /status + Bearer token в заголовках
ПОСЛЕ: POST / + {command, fid, token, locale} в JSON

🎯 ВЕРСИЯ: 0.1.3 → 0.1.4"
echo.

echo Создаем тег версии:
git tag -a v0.1.4 -m "Version 0.1.4 - Critical Bil24 API format fix"
echo.

echo Отправляем на GitHub:
git push origin main
git push origin v0.1.4
echo.

echo ================================================================
echo ✅ ВЕРСИЯ 0.1.4 УСПЕШНО ОПУБЛИКОВАНА!
echo ================================================================
echo.
echo Изменения:
echo - Обновлена версия в bil24-connector.php
echo - Обновлена версия в includes/Constants.php  
echo - Создан CHANGELOG.md с описанием изменений
echo - Исправлен формат API для работы с реальным Bil24
echo.
echo Протестируйте новую версию:
echo 1. Админ панель: Настройки ^> Bil24 Connector
echo 2. Тестер: yoursite.com/.../test-bil24-format.php
echo.
pause