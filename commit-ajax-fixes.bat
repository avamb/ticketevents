@echo off
chcp 65001 >nul
echo ================================================================
echo КОММИТ ИСПРАВЛЕНИЙ: AJAX 400 ERROR И JQMIGRATE WARNING
echo ================================================================
echo.

echo Проверяем статус git:
git status
echo.

echo Добавляем исправленные файлы:
git add includes/Admin/SettingsPage.php
git add includes/Plugin.php
git add test-ajax-simple.php
git add commit-ajax-fixes.bat
echo.

echo Создаем коммит:
git commit -m "fix: Исправлена ошибка 400 Bad Request в AJAX и JQMIGRATE warning

🔧 КРИТИЧЕСКИЕ ИСПРАВЛЕНИЯ:
- Улучшена диагностика AJAX запросов test_connection
- Заменена check_ajax_referer на wp_verify_nonce с детальной отладкой
- Добавлено логирование POST данных и nonce для диагностики
- Отключен jQuery Migrate для устранения console warnings

📋 НОВЫЕ ВОЗМОЖНОСТИ:
- test-ajax-simple.php - простой тестер AJAX без сложных зависимостей
- Улучшенная обработка ошибок с детальным логированием
- Проверка пользователя и прав доступа перед выполнением

🚫 ИСПРАВЛЕННЫЕ ПРОБЛЕМЫ:
- 400 Bad Request при тестировании подключения к Bil24 API
- JQMIGRATE: Migrate is installed, version 3.4.1 warning
- Неинформативные сообщения об ошибках в консоли

🧪 ТЕСТИРОВАНИЕ:
- Используйте test-ajax-simple.php для изолированного тестирования AJAX
- Проверьте логи WordPress при включенном WP_DEBUG
- Обновите страницу настроек перед тестированием

Fixes: #ajax-400-error #jqmigrate-warning"
echo.

echo Отправляем на GitHub:
git push origin main
echo.

echo ================================================================
echo ГОТОВО! Проверьте результат:
echo 1. Обновите страницу настроек Bil24 Connector
echo 2. Попробуйте Test Connection
echo 3. Проверьте консоль браузера - предупреждений быть не должно
echo 4. При проблемах используйте test-ajax-simple.php
echo ================================================================
pause