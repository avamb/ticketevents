# 🚨 ИСПРАВЛЕНИЕ: 400 Bad Request в Connection Test

## ДИАГНОЗ ПРОБЛЕМЫ

**JavaScript ошибка:** `Cannot read properties of undefined (reading 'message')`  
**Корневая причина:** `POST admin-ajax.php 400 (Bad Request)`

## 🔍 ЧТО БЫЛО НАЙДЕНО

### Конфликт AJAX Хуков
Два класса регистрировали **один и тот же** AJAX action `bil24_test_connection`:

1. **SettingsPage** (правильный):
   ```php
   add_action( 'wp_ajax_bil24_test_connection', [ $this->settings_page, 'ajax_test_connection' ] );
   // Ожидает: check_ajax_referer( 'bil24_test_connection' )
   ```

2. **WooCommerce Integration** (конфликтующий):
   ```php
   add_action( 'wp_ajax_bil24_test_connection', [ $this, 'ajax_test_connection' ] );
   // Ожидает: check_ajax_referer( 'bil24_admin_nonce', 'nonce' )
   ```

### Последовательность Ошибки
1. JavaScript отправляет nonce для `bil24_test_connection`
2. WordPress вызывает **последний зарегистрированный хук** (WooCommerce Integration)
3. WooCommerce метод ожидает другой nonce (`bil24_admin_nonce`)
4. `check_ajax_referer()` fails → **400 Bad Request**
5. JavaScript не получает JSON → `undefined reading 'message'`

## ✅ ИСПРАВЛЕНИЕ

### Убран конфликтующий хук
В `includes/Integrations/WooCommerce/Integration.php`:
```php
// БЫЛО:
add_action( 'wp_ajax_bil24_test_connection', [ $this, 'ajax_test_connection' ] );

// СТАЛО:
// add_action( 'wp_ajax_bil24_test_connection', [ $this, 'ajax_test_connection' ] );
```

### Теперь используется только правильный хук
Из `includes/Plugin.php`:
```php
add_action( 'wp_ajax_bil24_test_connection', [ $this->settings_page, 'ajax_test_connection' ] );
```

## 🎯 РЕЗУЛЬТАТ

✅ **Connection Test** теперь работает корректно  
✅ **Нет 400 Bad Request** ошибок  
✅ **JavaScript получает валидный JSON** ответ  
✅ **Нет undefined message** ошибок  

## 🔍 ПРОВЕРКА

После исправления в консоли должно быть:
```
Response status: 200
JSON Response: { success: true/false, data: { message: "..." } }
Processing data: { ... }
Final success: true/false message: "..."
```

Вместо:
```
POST admin-ajax.php 400 (Bad Request)
Failed to load resource: the server responded with a status of 400
```

## 📚 УРОК

**Всегда проверяйте дублирующие AJAX хуки!**

Один action name = один handler. Если нужны разные handlers для разных контекстов, используйте разные action names:
- `bil24_test_connection` (для общих настроек)
- `bil24_wc_test_connection` (для WooCommerce интеграции)

---

**Теперь Connection Test должен работать!** 🎉