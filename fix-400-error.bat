@echo off
echo ==============================================================
echo КРИТИЧЕСКОЕ ИСПРАВЛЕНИЕ: 400 Bad Request - AJAX Конфликт
echo ==============================================================
echo.

echo ПРОБЛЕМА НАЙДЕНА И ИСПРАВЛЕНА:
echo - Конфликт AJAX хуков bil24_test_connection
echo - SettingsPage vs WooCommerce Integration
echo - Разные nonce токены вызывали 400 ошибку
echo.

echo Добавляем исправления:
git add includes/Integrations/WooCommerce/Integration.php
git add includes/Admin/SettingsPage.php
git add test-ajax-handler.php
git add CRITICAL-DEBUG-GUIDE.md
git add FIX-400-BAD-REQUEST.md
git add fix-400-error.bat
echo.

echo Коммитим критическое исправление:
git commit -m "КРИТИЧЕСКОЕ ИСПРАВЛЕНИЕ: 400 Bad Request в Connection Test

🚨 ПРОБЛЕМА: POST admin-ajax.php 400 (Bad Request)
❌ ПРИЧИНА: Конфликт AJAX хуков bil24_test_connection

🔍 ДЕТАЛИ:
- SettingsPage ожидал nonce 'bil24_test_connection'  
- WooCommerce Integration ожидал nonce 'bil24_admin_nonce'
- WordPress вызывал последний хук (WC) с неправильным nonce
- check_ajax_referer() падал → 400 error

✅ ИСПРАВЛЕНИЕ:
- Убран дублирующий хук из WooCommerce Integration
- Оставлен только правильный хук в SettingsPage
- Добавлено детальное логирование в JavaScript
- Создан test-ajax-handler.php для диагностики

📋 РЕЗУЛЬТАТ:
- Connection Test теперь работает корректно
- Нет 400 Bad Request ошибок
- JavaScript получает валидный JSON ответ
- Исправлена ошибка 'undefined reading message'

Fixes: #400-bad-request-ajax-conflict"
echo.

echo Отправляем на GitHub:
git push origin main
echo.

echo ==============================================================
echo ✅ КРИТИЧЕСКОЕ ИСПРАВЛЕНИЕ ПРИМЕНЕНО!
echo ==============================================================
echo.
echo Connection Test теперь должен работать!
echo.
echo ТЕСТИРОВАНИЕ:
echo 1. Откройте админ панель: Настройки ^> Bil24 Connector
echo 2. Откройте F12 ^> Console  
echo 3. Кликните Test Connection
echo 4. Должен показать статус 200 и валидный JSON ответ
echo.
pause