# Исправление ошибки подключения к Bil24 API

## Что было исправлено

### 1. Улучшен метод тестирования подключения (`includes/Api/Client.php`)
- ✅ Добавлена проверка нескольких endpoints (`/status`, `/version`, `/events`)
- ✅ Улучшена диагностика ошибок с конкретными сообщениями
- ✅ Добавлена проверка настроек перед тестированием
- ✅ Расширено логирование для отладки

### 2. Улучшена обработка ошибок API (`includes/Api/Client.php`)
- ✅ Специфические сообщения для разных HTTP кодов (401, 403, 404, 500+)
- ✅ Улучшена обработка network timeouts и connection errors
- ✅ Добавлено логирование raw responses для диагностики
- ✅ Лучшие сообщения об ошибках для пользователей

### 3. Улучшен AJAX handler (`includes/Admin/SettingsPage.php`)
- ✅ Проверка credentials перед тестированием
- ✅ Информативные сообщения об ошибках
- ✅ Показ текущей среды (test/prod) в результатах
- ✅ Расширенное логирование для отладки

### 4. Создан независимый тестер подключения
- ✅ Новый файл `test-api-connection.php` для диагностики
- ✅ Подробная диагностика настроек и окружения
- ✅ Тест сетевой связности
- ✅ Проверка всех необходимых PHP расширений

## Как тестировать исправление

### 1. Через админ-панель WordPress
1. Зайдите в **Настройки → Bil24 Connector**
2. Введите ваши **FID** и **Token** credentials
3. Выберите среду (**Test** или **Production**)
4. Нажмите кнопку **"Test Connection"**
5. Вы должны получить информативное сообщение о результате

### 2. Через независимый тестер
1. Откройте в браузере: `http://yoursite.com/wp-content/plugins/bil24-connector/test-api-connection.php`
2. Скрипт покажет подробную диагностику:
   - Статус загрузки классов
   - Текущие настройки
   - Результат подключения к API
   - Диагностику PHP/WordPress окружения

### 3. Проверка логов
Если включен `WP_DEBUG`, в файле `wp-content/debug.log` появятся подробные записи:
```
[Bil24] [DEBUG] Testing connection with endpoint: /status
[Bil24] [INFO] Connection test successful with endpoint: /status
```

## Возможные ошибки и их решения

### ❌ "API credentials (FID and Token) are required"
**Решение:** Настройте FID и Token в админ-панели

### ❌ "Authentication failed. Please check your FID and Token credentials"
**Решение:** Проверьте правильность введенных credentials

### ❌ "Network connection failed. Please check your internet connection or API server status"
**Решение:** Проверьте интернет-соединение и доступность api.bil24.pro

### ❌ "Connection timeout. The API server may be unavailable"
**Решение:** 
- Проверьте firewall настройки
- Убедитесь что порт 1240 (для test) доступен
- Попробуйте переключиться на prod среду

### ❌ "API endpoint not found"
**Решение:** Обновите плагин или обратитесь в поддержку Bil24

## API Endpoints для тестирования

### Test Environment
- **URL:** `https://api.bil24.pro:1240`
- **Endpoints:** `/status`, `/version`, `/events`

### Production Environment  
- **URL:** `https://api.bil24.pro`
- **Endpoints:** `/status`, `/version`, `/events`

## Дополнительная диагностика

Если проблемы продолжаются:

1. **Включите debug режим** в `wp-config.php`:
   ```php
   define('WP_DEBUG', true);
   define('WP_DEBUG_LOG', true);
   ```

2. **Запустите тестер подключения** и скопируйте результаты

3. **Проверьте debug.log** на предмет дополнительных ошибок

4. **Проверьте настройки хостинга:**
   - cURL включен
   - OpenSSL доступен
   - Исходящие HTTPS соединения разрешены
   - Нет блокировки внешних API

## Контакты для поддержки

При необходимости обратитесь в техподдержку со следующей информацией:
- Результаты `test-api-connection.php`
- Фрагмент из debug.log с ошибками Bil24
- Версия WordPress и PHP
- Информация о хостинг-провайдере