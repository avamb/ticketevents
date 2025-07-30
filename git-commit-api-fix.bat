@echo off
echo ================================================
echo КРИТИЧЕСКОЕ ИСПРАВЛЕНИЕ: Bil24 API Format
echo ================================================
echo.

echo Проверяем статус git:
git status
echo.

echo Добавляем все исправленные файлы:
git add includes/Api/Client.php
git add includes/Admin/SettingsPage.php
git add test-api-connection.php
git add test-bil24-format.php
git add BIL24-API-FORMAT-FIX.md
git add git-commit-api-fix.bat
echo.

echo Делаем коммит с исправлением:
git commit -m "КРИТИЧЕСКОЕ ИСПРАВЛЕНИЕ: Правильный формат Bil24 API

🔧 ПРОБЛЕМА: Плагин неправильно формировал запросы к Bil24 API
❌ Было: REST endpoints с Bearer токеном в заголовках
✅ Стало: JSON команды с fid/token в теле запроса

ИЗМЕНЕНИЯ:
- Новый метод execute_command() для правильного формата
- Аутентификация fid/token в теле запроса вместо заголовков
- Команды Bil24 (GET_ALL_ACTIONS, GET_EVENTS) вместо REST endpoints
- test_connection() использует GET_ALL_ACTIONS команду
- Создан test-bil24-format.php для диагностики
- Обратная совместимость для старых методов get/post/put/delete

ПРИМЕР ПРАВИЛЬНОГО ЗАПРОСА:
POST https://api.bil24.pro:1240
Content-Type: application/json
{\"locale\":\"en\",\"command\":\"GET_ALL_ACTIONS\",\"fid\":2558,\"token\":\"bf7404918fd2785b2178\"}

Fixes: #bil24-api-format-error"
echo.

echo Отправляем на GitHub:
git push origin main
echo.

echo ================================================
echo ✅ ИСПРАВЛЕНИЕ ЗАВЕРШЕНО!
echo ================================================
echo.
echo Теперь плагин должен корректно работать с Bil24 API!
echo Протестируйте подключение через:
echo - Админ панель: Настройки ^> Bil24 Connector
echo - Тестер: test-bil24-format.php
echo.
pause