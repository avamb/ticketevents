@echo off
chcp 65001 >nul
echo ================================================================
echo КОММИТ ТЕКУЩИХ ИЗМЕНЕНИЙ - BIL24 API ДОКУМЕНТАЦИЯ И ИСПРАВЛЕНИЯ
echo ================================================================
echo.

echo Проверяем статус git:
git status
echo.

echo Добавляем все изменения:
echo Основные файлы:
git add includes/Admin/SettingsPage.php
git add includes/Api/Client.php

echo Документация и анализ:
git add BIL24-API-DOCUMENTATION-NEEDED.md
git add NEXT-STEPS.md
git add BIL24-API-FORMAT-FIX.md

echo Тестовые и вспомогательные файлы:
git add analyze-bil24-api.php
git add test-bil24-connection-manual.php
git add test-bil24-formats.php

echo Скрипты и утилиты:
git add commit.cmd
git add run-git.cmd
git add fix-400-error.bat
git add git-commit.bat
git add git-commit-api-fix.bat
git add commit-v0.1.4.bat

echo Прочие файлы:
git add git-commands-400-fix.txt
git add git-version-0.1.4.txt
git add fix-js-error.bat
echo.

echo Создаем коммит:
git commit -m "feat: Добавлена документация Bil24 API и исправления

✅ ИЗМЕНЕНИЯ:
- Найдена и документирована правильная структура Bil24 API
- Обновлен Client.php с новым форматом запросов (JSON команды)
- Добавлены тестовые утилиты для проверки API форматов
- Создана документация по интеграции и решению проблем

📋 НОВЫЕ ФАЙЛЫ:
- BIL24-API-DOCUMENTATION-NEEDED.md - требования к документации
- BIL24-API-FORMAT-FIX.md - правильный формат API
- NEXT-STEPS.md - план дальнейшей разработки
- analyze-bil24-api.php - анализатор API
- test-bil24-formats.php - тестер форматов запросов

🔧 ИСПРАВЛЕНИЯ:
- Формат запросов: POST с JSON body вместо GET с headers
- Аутентификация: fid/token в JSON теле
- Команды: GET_ALL_ACTIONS, GET_EVENTS вместо REST endpoints
- URL: api.bil24.pro:1240 для тестов

Refs: #bil24-api-integration"
echo.

echo Отправляем на GitHub:
git push origin main
echo.

echo ================================================================
echo ГОТОВО! Изменения отправлены на GitHub
echo ================================================================
pause