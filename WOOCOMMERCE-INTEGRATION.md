# WooCommerce Integration for Bil24 Connector

Полная интеграция между WooCommerce и билетинговой системой Bil24, включающая синхронизацию продуктов, заказов, клиентов и расширенную поддержку ACF полей.

## 🚀 Возможности

### Основные функции
- **Синхронизация продуктов**: Автоматический импорт событий Bil24 как продуктов WooCommerce
- **Управление корзиной**: Резервирование билетов, валидация доступности, таймер истечения
- **Синхронизация заказов**: Двусторонняя синхронизация между WooCommerce и Bil24
- **Управление клиентами**: Синхронизация профилей, предпочтений и истории покупок
- **Генерация билетов**: Автоматическое создание PDF билетов после оплаты
- **ACF интеграция**: Расширенные поля для событий, клиентов и заказов

### Дополнительные возможности
- **Резервирование билетов**: Временное резервирование в корзине (15 минут по умолчанию)
- **Real-time обновления**: Проверка доступности билетов в реальном времени
- **Email уведомления**: Автоматическая отправка билетов на email
- **Webhook поддержка**: Получение обновлений от Bil24
- **GDPR соответствие**: Экспорт и удаление персональных данных
- **Многоязычность**: Поддержка переводов через WordPress i18n

## 📦 Установка и настройка

### Требования
- WordPress 6.2+
- WooCommerce 7.0+
- PHP 8.0+
- Advanced Custom Fields (рекомендуется)
- Активный аккаунт Bil24 с API доступом

### Активация интеграции

1. **Убедитесь что WooCommerce активен**
2. **Настройте API ключи Bil24** в `Настройки → Bil24 Connector`
3. **Перейдите в** `WooCommerce → Настройки → Bil24`
4. **Включите интеграцию** и настройте параметры

### Основные настройки

```php
// Включить автосинхронизацию
'bil24_auto_sync_products' => 'yes'
'bil24_auto_sync_orders' => 'yes'
'bil24_auto_sync_customers' => 'yes'

// Настройки резервирования
'bil24_reservation_timeout' => '15' // минуты

// Настройки билетов
'bil24_auto_generate_tickets' => 'yes'
'bil24_email_tickets' => 'yes'
```

## 🛠️ Использование

### Импорт событий как продуктов

#### Автоматический импорт
События Bil24 автоматически импортируются как продукты WooCommerce по расписанию:

```php
// Настройка автоимпорта
add_action('bil24_sync_products', function() {
    $integration = new \Bil24\Integrations\WooCommerce\Integration();
    $product_sync = $integration->get_product_sync();
    $product_sync->import_events_as_products();
});
```

#### Ручной импорт
```php
$product_sync = new \Bil24\Integrations\WooCommerce\ProductSync();

// Импорт всех событий
$result = $product_sync->import_events_as_products();

// Импорт с фильтрами
$result = $product_sync->import_events_as_products([
    'date_from' => '2024-01-01',
    'event_type' => 'concert',
    'city' => 'Moscow'
]);
```

### Работа с корзиной

#### Резервирование билетов
При добавлении билета в корзину автоматически создается резервирование в Bil24:

```php
// Кастомизация времени резервирования
add_filter('bil24_reservation_timeout', function($timeout) {
    return 20; // 20 минут вместо 15
});

// Обработка истечения резервирования
add_action('bil24_reservation_expired', function($cart_item_key, $reservation_id) {
    // Кастомная логика при истечении резервирования
});
```

#### Валидация корзины
```javascript
// Frontend JavaScript для проверки доступности
jQuery('#add-to-cart').on('click', function(e) {
    e.preventDefault();
    
    var productId = jQuery(this).data('product-id');
    var quantity = jQuery('input[name="quantity"]').val();
    
    jQuery.post(bil24_frontend.ajax_url, {
        action: 'bil24_check_availability',
        product_id: productId,
        quantity: quantity,
        nonce: bil24_frontend.nonce
    }, function(response) {
        if (response.success && response.data.available) {
            // Добавить в корзину
        } else {
            alert('Недостаточно билетов');
        }
    });
});
```

### Синхронизация заказов

#### Автоматическая синхронизация
```php
// Отключить автосинхронизацию для определенных заказов
add_filter('bil24_should_sync_order', function($should_sync, $order_id) {
    $order = wc_get_order($order_id);
    
    // Не синхронизировать тестовые заказы
    if ($order->get_meta('_test_order')) {
        return false;
    }
    
    return $should_sync;
}, 10, 2);
```

#### Кастомные поля заказа
```php
// Добавить дополнительные поля при синхронизации
add_filter('bil24_order_sync_data', function($data, $order) {
    $data['custom_field'] = $order->get_meta('_custom_field');
    $data['special_notes'] = $order->get_customer_note();
    
    return $data;
}, 10, 2);
```

### Генерация билетов

#### Автоматическая генерация
```php
// Кастомизация процесса генерации билетов
add_action('bil24_before_generate_tickets', function($order_id) {
    // Логика перед генерацией билетов
});

add_action('bil24_after_generate_tickets', function($order_id, $tickets) {
    // Логика после генерации билетов
    foreach ($tickets as $ticket) {
        // Отправить в CRM, создать уведомления и т.д.
    }
}, 10, 2);
```

#### Кастомизация email с билетами
```php
// Изменить шаблон email с билетами
add_filter('bil24_ticket_email_template', function($template, $order, $tickets) {
    // Кастомный шаблон
    return 'path/to/custom/template.php';
}, 10, 3);

// Добавить дополнительные вложения
add_filter('bil24_ticket_email_attachments', function($attachments, $order, $tickets) {
    $attachments[] = '/path/to/terms.pdf';
    return $attachments;
}, 10, 3);
```

## 🎛️ ACF поля

### Поля продуктов (событий)
```php
// Основные поля событий
$event_fields = [
    'bil24_event_id' => 'ID события в Bil24',
    'bil24_venue_id' => 'ID площадки',
    'event_start_date' => 'Дата начала события',
    'event_end_date' => 'Дата окончания события',
    'venue_name' => 'Название площадки',
    'venue_address' => 'Адрес площадки',
    'age_restrictions' => 'Возрастные ограничения',
    'max_tickets_per_customer' => 'Максимум билетов на клиента'
];

// Использование в шаблонах
$start_date = get_field('event_start_date', $product_id);
$venue = get_field('venue_name', $product_id);
```

### Поля клиентов
```php
// Предпочтения клиентов
$customer_fields = [
    'preferred_venues' => 'Предпочитаемые площадки',
    'event_preferences' => 'Типы событий',
    'communication_preferences' => 'Настройки уведомлений',
    'bil24_loyalty_points' => 'Бонусные баллы',
    'bil24_vip_status' => 'VIP статус'
];

// Получение предпочтений клиента
$user_id = get_current_user_id();
$preferred_venues = get_field('preferred_venues', 'user_' . $user_id);
$event_prefs = get_field('event_preferences', 'user_' . $user_id);
```

### Поля заказов
```php
// Дополнительная информация о заказе
$order_fields = [
    'bil24_order_id' => 'ID заказа в Bil24',
    'bil24_tickets_generated' => 'Билеты сгенерированы',
    'special_requests' => 'Особые пожелания',
    'seating_preference' => 'Предпочтения по местам'
];
```

## 🔧 Администрирование

### Панель управления
Доступна по адресу: `WooCommerce → Bil24 Integration`

#### Вкладки панели:
1. **Обзор** - статистика и статус соединения
2. **Синхронизация** - инструменты массовой синхронизации
3. **Логи** - просмотр логов интеграции
4. **Инструменты** - диагностика и сброс настроек

### Массовые операции

#### Импорт событий
```bash
# Через WP-CLI
wp bil24 import events --date-from=2024-01-01 --limit=50

# Программно
$product_sync = new \Bil24\Integrations\WooCommerce\ProductSync();
$result = $product_sync->import_events_as_products([
    'limit' => 50,
    'date_from' => '2024-01-01'
]);
```

#### Синхронизация заказов
```php
// Синхронизация всех несинхронизированных заказов
$orders = wc_get_orders([
    'status' => ['processing', 'completed'],
    'meta_query' => [
        [
            'key' => '_bil24_sync_status',
            'value' => 'pending',
            'compare' => '='
        ]
    ]
]);

$order_sync = new \Bil24\Integrations\WooCommerce\OrderSync();
foreach ($orders as $order) {
    try {
        $order_sync->sync_order_to_bil24($order->get_id());
    } catch (Exception $e) {
        error_log('Ошибка синхронизации заказа: ' . $e->getMessage());
    }
}
```

### Мониторинг и логирование

#### Просмотр статистики
```php
$integration = new \Bil24\Integrations\WooCommerce\Integration();
$stats = $integration->get_integration_stats();

echo "Синхронизированных продуктов: " . $stats['synced_products'];
echo "Синхронизированных заказов: " . $stats['synced_orders'];
echo "Синхронизированных клиентов: " . $stats['synced_customers'];
```

#### Настройка логирования
```php
// В wp-config.php
define('BIL24_LOG_LEVEL', 'debug');
define('BIL24_LOG_FILE', '/path/to/custom/log/file.log');

// Программная настройка
add_filter('bil24_log_level', function() {
    return 'info'; // error, warning, info, debug
});
```

## 🔌 Webhooks

### Настройка webhooks в Bil24
1. В админке Bil24 перейдите в раздел API → Webhooks
2. Добавьте URL: `https://yoursite.com/wp-json/bil24/v1/webhook`
3. Выберите события для подписки
4. Сохраните webhook secret в настройках плагина

### Обработка webhook событий
```php
// Кастомная обработка webhook событий
add_action('bil24_webhook_received', function($event_type, $data) {
    switch ($event_type) {
        case 'event.updated':
            // Обновить продукт
            break;
        case 'order.payment_completed':
            // Сгенерировать билеты
            break;
        case 'ticket.scanned':
            // Отметить использование билета
            break;
    }
}, 10, 2);
```

## 🎨 Кастомизация фронтенда

### Отображение информации о событии
```php
// В single-product.php
if (function_exists('bil24_display_event_info')) {
    bil24_display_event_info();
}

// Или через хук
add_action('woocommerce_single_product_summary', function() {
    global $product;
    
    $bil24_id = get_post_meta($product->get_id(), '_bil24_event_id', true);
    if ($bil24_id) {
        $start_date = get_field('event_start_date', $product->get_id());
        $venue = get_field('venue_name', $product->get_id());
        
        echo '<div class="event-info">';
        if ($start_date) {
            echo '<p><strong>Дата:</strong> ' . date('d.m.Y H:i', strtotime($start_date)) . '</p>';
        }
        if ($venue) {
            echo '<p><strong>Место:</strong> ' . esc_html($venue) . '</p>';
        }
        echo '</div>';
    }
}, 25);
```

### Кастомизация корзины
```php
// Добавить информацию о резервировании в корзину
add_filter('woocommerce_get_item_data', function($item_data, $cart_item) {
    $product_id = $cart_item['product_id'];
    $bil24_id = get_post_meta($product_id, '_bil24_event_id', true);
    
    if ($bil24_id) {
        $reservations = WC()->session->get('bil24_reservations', []);
        $cart_item_key = $cart_item['key'];
        
        if (isset($reservations[$cart_item_key])) {
            $expires_at = $reservations[$cart_item_key]['expires_at'];
            $time_left = $expires_at - time();
            
            if ($time_left > 0) {
                $minutes_left = ceil($time_left / 60);
                $item_data[] = [
                    'key' => 'Резервирование',
                    'value' => "Действует еще {$minutes_left} мин."
                ];
            }
        }
    }
    
    return $item_data;
}, 10, 2);
```

### Страница аккаунта
```php
// Добавить раздел "Мои билеты"
add_action('woocommerce_account_dashboard', function() {
    $customer_id = get_current_user_id();
    
    // Получить заказы с билетами
    $orders = wc_get_orders([
        'customer' => $customer_id,
        'status' => 'completed',
        'meta_query' => [
            [
                'key' => '_bil24_tickets_generated',
                'value' => 'yes',
                'compare' => '='
            ]
        ]
    ]);
    
    if (!empty($orders)) {
        echo '<h3>Мои билеты</h3>';
        foreach ($orders as $order) {
            $tickets = get_post_meta($order->get_id(), '_bil24_tickets', true);
            if (!empty($tickets)) {
                echo '<div class="my-tickets">';
                echo '<h4>Заказ #' . $order->get_order_number() . '</h4>';
                foreach ($tickets as $ticket) {
                    echo '<p>Билет #' . $ticket['id'];
                    if (!empty($ticket['qr_code'])) {
                        echo ' <a href="' . $ticket['qr_code'] . '" target="_blank">QR код</a>';
                    }
                    echo '</p>';
                }
                echo '</div>';
            }
        }
    }
});
```

## 🔒 Безопасность и GDPR

### Экспорт персональных данных
```php
// Автоматически включается при активации интеграции
// Данные экспортируются через Инструменты → Экспорт персональных данных

// Кастомизация экспорта
add_filter('bil24_export_customer_data', function($data, $user_id) {
    // Добавить дополнительные данные для экспорта
    $data['custom_field'] = get_user_meta($user_id, 'custom_field', true);
    return $data;
}, 10, 2);
```

### Удаление персональных данных
```php
// Автоматически включается при активации интеграции
// Данные удаляются через Инструменты → Удаление персональных данных

// Кастомизация удаления
add_action('bil24_erase_customer_data', function($user_id) {
    // Дополнительная очистка данных
    delete_user_meta($user_id, 'custom_field');
});
```

## 🚨 Устранение неполадок

### Частые проблемы

#### Резервирование не работает
```php
// Проверить настройки сессий
if (!WC()->session) {
    // Проблема с сессиями WooCommerce
}

// Проверить API соединение
$client = new \Bil24\Api\Client();
if (!$client->test_connection()) {
    // Проблема с API
}
```

#### Билеты не генерируются
```php
// Проверить статус заказа
$order = wc_get_order($order_id);
if ($order->get_status() !== 'completed') {
    // Заказ должен быть в статусе "completed"
}

// Проверить синхронизацию с Bil24
$bil24_order_id = get_post_meta($order_id, '_bil24_order_id', true);
if (!$bil24_order_id) {
    // Заказ не синхронизирован с Bil24
}
```

#### Синхронизация не работает
```php
// Проверить настройки автосинхронизации
$auto_sync = get_option('bil24_auto_sync_products');
if ($auto_sync !== 'yes') {
    // Автосинхронизация отключена
}

// Проверить cron задачи
$next_run = wp_next_scheduled('bil24_sync_cron');
if (!$next_run) {
    // Cron задачи не запланированы
    wp_schedule_event(time(), 'hourly', 'bil24_sync_cron');
}
```

### Отладка

#### Включение детального логирования
```php
// В wp-config.php
define('BIL24_DEBUG', true);
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);

// Просмотр логов
tail -f wp-content/debug.log | grep bil24
```

#### Проверка API запросов
```php
// Включить логирование HTTP запросов
add_action('http_api_debug', function($response, $context, $class, $parsed_args, $url) {
    if (strpos($url, 'bil24') !== false) {
        error_log('Bil24 API Request: ' . print_r([
            'url' => $url,
            'args' => $parsed_args,
            'response' => $response
        ], true));
    }
}, 10, 5);
```

## 📈 Производительность

### Оптимизация

#### Кэширование
```php
// Настройка времени кэширования
add_filter('bil24_cache_timeout', function($timeout, $cache_key) {
    if (strpos($cache_key, 'availability') !== false) {
        return 60; // 1 минута для доступности билетов
    }
    return $timeout;
}, 10, 2);

// Принудительная очистка кэша
do_action('bil24_clear_cache');
```

#### Оптимизация запросов
```php
// Пакетная обработка синхронизации
add_filter('bil24_sync_batch_size', function($size) {
    return 10; // Обрабатывать по 10 элементов за раз
});

// Отложенная обработка
add_action('bil24_queue_sync_job', function($job_type, $item_id) {
    // Использовать Action Scheduler или аналогичную систему очередей
    as_schedule_single_action(time() + 60, 'bil24_process_sync_job', [
        'type' => $job_type,
        'id' => $item_id
    ]);
});
```

## 🔄 Миграция и обновления

### Миграция данных
```php
// Миграция существующих продуктов
function bil24_migrate_existing_products() {
    $products = get_posts([
        'post_type' => 'product',
        'posts_per_page' => -1,
        'meta_query' => [
            [
                'key' => '_bil24_event_id',
                'compare' => 'NOT EXISTS'
            ]
        ]
    ]);
    
    foreach ($products as $product) {
        // Логика определения связи с Bil24
        // Например, по названию или другим критериям
    }
}
```

### Обновление схемы данных
```php
// Хук для обновления при активации плагина
register_activation_hook(__FILE__, function() {
    $current_version = get_option('bil24_woocommerce_version');
    
    if (version_compare($current_version, '1.1.0', '<')) {
        // Миграция на версию 1.1.0
        bil24_migrate_to_v1_1_0();
    }
    
    update_option('bil24_woocommerce_version', BIL24_VERSION);
});
```

---

## 📞 Поддержка

Если у вас возникли вопросы или проблемы с интеграцией:

1. Проверьте раздел "Устранение неполадок" выше
2. Включите отладочное логирование и проанализируйте логи
3. Обратитесь в техническую поддержку Bil24
4. Создайте issue в репозитории плагина

Документация актуальна для версии плагина 1.0.0 и выше. 