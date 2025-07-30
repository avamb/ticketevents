# 🔧 КРИТИЧЕСКОЕ ИСПРАВЛЕНИЕ: Bil24 API Format

## ⚠️ ПРОБЛЕМА ОБНАРУЖЕНА

**Плагин неправильно формировал запросы к Bil24 API!**

### ❌ Неправильный подход (ранее):
```http
GET /status HTTP/1.1
Host: api.bil24.pro:1240
Authorization: Bearer bf7404918fd2785b2178
X-FID: 2558
```

### ✅ Правильный формат Bil24:
```http
POST / HTTP/1.1 
Host: api.bil24.pro:1240
Content-Type: application/json

{
  "locale": "en",
  "command": "GET_ALL_ACTIONS", 
  "fid": 2558,
  "token": "bf7404918fd2785b2178"
}
```

## 🔥 ЧТО БЫЛО ИСПРАВЛЕНО

### 1. Формат запросов (`includes/Api/Client.php`)
- ✅ **Новый метод:** `execute_command()` - отправляет команды в правильном JSON формате
- ✅ **Аутентификация:** FID и Token теперь в теле запроса, не в заголовках
- ✅ **Команды:** Используем Bil24 команды вместо REST endpoints
- ✅ **URL:** Все запросы идут на базовый URL без дополнительных путей

### 2. Команды Bil24 вместо REST endpoints
```php
// Старый подход
$api->get('/status');
$api->get('/events'); 

// Новый подход  
$api->execute_command('GET_ALL_ACTIONS');
$api->execute_command('GET_EVENTS');
```

### 3. Маппинг команд
- `/status` → `GET_ALL_ACTIONS`
- `/events` → `GET_EVENTS` 
- `/orders` → `GET_ORDERS`
- `/venues` → `GET_VENUES`

### 4. Тестирование подключения
- ✅ Использует команду `GET_ALL_ACTIONS` для тестирования
- ✅ Правильная проверка ответов Bil24
- ✅ Обработка ошибок в формате Bil24

## 📋 НОВЫЕ ФАЙЛЫ

### `test-bil24-format.php` 
Специальный тестер для проверки нового формата:
- Показывает правильную структуру запроса
- Тестирует команду `GET_ALL_ACTIONS`
- Отображает реальный ответ от API

### Обновленный `test-api-connection.php`
- Предупреждение о новом формате
- Показ ожидаемой структуры запроса
- Улучшенная диагностика

## 🚀 КАК ТЕСТИРОВАТЬ

### 1. Через админ-панель
1. Настройки → Bil24 Connector
2. Введите FID и Token  
3. Нажмите "Test Connection"
4. Должен показать успешное подключение

### 2. Через новый тестер
Откройте: `yoursite.com/wp-content/plugins/bil24-connector/test-bil24-format.php`

### 3. Проверка запросов в Network
В браузере откройте DevTools → Network и посмотрите:
- Должен быть POST запрос на базовый API URL
- Content-Type: application/json
- Body содержит: locale, command, fid, token

## 📊 СТРУКТУРА ЗАПРОСА

### Базовые URL
- **Test:** `https://api.bil24.pro:1240`
- **Production:** `https://api.bil24.pro`

### Обязательные поля
```json
{
  "locale": "en",        // Язык ответа (en/ru)
  "command": "...",      // Команда Bil24
  "fid": 2558,          // Facility ID (число)
  "token": "..."        // Токен доступа
}
```

### Основные команды
- `GET_ALL_ACTIONS` - получить доступные действия (для тестирования)
- `GET_EVENTS` - получить события
- `GET_ORDERS` - получить заказы  
- `GET_VENUES` - получить площадки
- `CREATE_EVENT` - создать событие
- `UPDATE_EVENT` - обновить событие

## 🔍 ДИАГНОСТИКА ПРОБЛЕМ

### Если тест не проходит:

1. **"API credentials not configured"**
   - Убедитесь что FID и Token заполнены
   
2. **"Network connection failed"**
   - Проверьте доступность api.bil24.pro
   - Убедитесь что порт 1240 открыт (для test)
   
3. **"Authentication failed"**
   - Проверьте правильность FID и Token
   - Убедитесь что FID - это число
   
4. **"Bil24 API error: ..."**
   - Смотрите конкретную ошибку от API
   - Проверьте права доступа для токена

### Логирование
При включенном `WP_DEBUG` в логах будет:
```
[Bil24] [DEBUG] Executing Bil24 command: GET_ALL_ACTIONS with data: {"command":"GET_ALL_ACTIONS","fid":2558,"token":"bf7404918fd2785b2178","locale":"en"}
[Bil24] [INFO] Bil24 API connection test successful
```

## ⚡ BACKWARDS COMPATIBILITY

Старые методы `get()`, `post()`, `put()`, `delete()` по-прежнему работают - они автоматически конвертируются в правильные команды Bil24.

## 🎯 СЛЕДУЮЩИЕ ШАГИ

1. **Протестируйте подключение** с новым форматом
2. **Проверьте логи** на предмет ошибок
3. **Обновите другие части кода** если используют прямые API вызовы
4. **Документируйте** специфичные команды вашего Bil24 аккаунта

---

**Теперь плагин должен корректно работать с реальным Bil24 API! 🎉**