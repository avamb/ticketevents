<?php
/**
 * Test Different Bil24 API Request Formats
 * Запустите этот скрипт для тестирования различных форматов запросов
 */

echo "<h2>🧪 Bil24 API Format Tester</h2>\n";

// Ваши реальные данные (замените на правильные)
$bil24_config = [
    'base_url' => 'REPLACE_WITH_REAL_BIL24_URL', // Нужен реальный URL
    'fid' => 2558, // Ваш FID
    'token' => 'REPLACE_WITH_REAL_TOKEN' // Ваш токен
];

// Различные форматы команд для тестирования
$test_commands = [
    'GET_ALL_ACTIONS',
    'GET_EVENTS',
    'GET_STATUS',
    'PING',
    'TEST_CONNECTION'
];

echo "<h3>📝 Тестируемые форматы:</h3>\n";

foreach ($test_commands as $command) {
    echo "<div style='border: 1px solid #ccc; margin: 10px; padding: 10px;'>\n";
    echo "<h4>Command: {$command}</h4>\n";
    
    // Формат 1: Как указал пользователь
    $format1 = [
        'locale' => 'en',
        'command' => $command,
        'fid' => (int)$bil24_config['fid'],
        'token' => $bil24_config['token']
    ];
    
    echo "<strong>Format 1 (User Example):</strong><br>\n";
    echo "<pre>" . json_encode($format1, JSON_PRETTY_PRINT) . "</pre>\n";
    
    // Формат 2: С дополнительными полями
    $format2 = [
        'data' => [
            'locale' => 'en',
            'command' => $command,
            'fid' => (int)$bil24_config['fid'],
            'token' => $bil24_config['token']
        ]
    ];
    
    echo "<strong>Format 2 (Wrapped in data):</strong><br>\n";
    echo "<pre>" . json_encode($format2, JSON_PRETTY_PRINT) . "</pre>\n";
    
    // Формат 3: С заголовками авторизации
    echo "<strong>Format 3 (With Auth Headers):</strong><br>\n";
    echo "<pre>Headers:\n";
    echo "Authorization: Bearer {$bil24_config['token']}\n";
    echo "X-FID: {$bil24_config['fid']}\n";
    echo "Content-Type: application/json\n\n";
    echo "Body:\n";
    echo json_encode([
        'locale' => 'en',
        'command' => $command
    ], JSON_PRETTY_PRINT) . "</pre>\n";
    
    echo "</div>\n";
}

echo "<h3>🔍 Как найти правильный формат:</h3>\n";
echo "<ol>\n";
echo "<li><strong>Проверьте браузер:</strong> Откройте DevTools в админке Bil24, найдите реальные запросы</li>\n";
echo "<li><strong>Документация:</strong> Свяжитесь с поддержкой Bil24 для получения API docs</li>\n";
echo "<li><strong>Тестирование:</strong> Попробуйте разные endpoints (/api, /api/v1, /api/v2)</li>\n";
echo "</ol>\n";

echo "<h3>🚀 Следующие шаги:</h3>\n";
echo "<ul>\n";
echo "<li>1. Получите <strong>реальный базовый URL</strong> API от Bil24</li>\n";
echo "<li>2. Уточните нужны ли <strong>дополнительные заголовки</strong></li>\n";
echo "<li>3. Протестируйте с помощью <strong>Postman</strong> или <strong>curl</strong></li>\n";
echo "<li>4. Обновите код в <code>includes/Api/Client.php</code></li>\n";
echo "</ul>\n";

// Функция для реального тестирования (раскомментируйте когда получите URL)
function test_real_bil24_api($url, $data) {
    /*
    $response = wp_remote_post($url, [
        'body' => wp_json_encode($data),
        'headers' => [
            'Content-Type' => 'application/json'
        ],
        'timeout' => 30
    ]);
    
    if (is_wp_error($response)) {
        echo "Error: " . $response->get_error_message() . "\n";
        return false;
    }
    
    $code = wp_remote_retrieve_response_code($response);
    $body = wp_remote_retrieve_body($response);
    
    echo "Status: {$code}\n";
    echo "Response: {$body}\n";
    
    return json_decode($body, true);
    */
    
    echo "⚠️ Функция тестирования отключена - нужен реальный URL\n";
    return null;
}

echo "<div style='background: #f0f0f0; padding: 15px; margin: 20px 0;'>\n";
echo "<h3>💡 Рекомендация:</h3>\n";
echo "<p><strong>Свяжитесь с командой Bil24</strong> и запросите:</p>\n";
echo "<ul>\n";
echo "<li>📖 Официальную документацию API</li>\n";
echo "<li>🌐 Базовый URL для API</li>\n";
echo "<li>🔑 Примеры аутентификации</li>\n";
echo "<li>📋 Список доступных команд</li>\n";
echo "<li>🧪 Тестовый аккаунт для разработки</li>\n";
echo "</ul>\n";
echo "</div>\n";
?>