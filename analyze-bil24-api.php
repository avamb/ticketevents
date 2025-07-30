<?php
/**
 * Bil24 API Analyzer Tool
 * Этот инструмент поможет определить правильный формат запросов
 */

// Конфигурация для тестирования
$possible_urls = [
    'https://api.bil24.com',
    'https://bil24.com/api', 
    'https://api.bil24.ru',
    'https://bil24.ru/api',
    // Добавьте ваш реальный URL здесь
];

$test_data = [
    'locale' => 'en',
    'command' => 'GET_ALL_ACTIONS',
    'fid' => 2558, // Ваш реальный FID
    'token' => 'your_token_here' // Ваш реальный токен
];

echo "<h2>🔍 Bil24 API Analysis Tool</h2>\n";

foreach ($possible_urls as $base_url) {
    echo "<h3>Testing: {$base_url}</h3>\n";
    
    // Тест 1: POST с JSON в теле
    echo "<strong>Test 1: POST with JSON body</strong><br>\n";
    test_api_call($base_url, $test_data, 'POST', 'json');
    
    // Тест 2: POST с form data
    echo "<strong>Test 2: POST with form data</strong><br>\n";
    test_api_call($base_url, $test_data, 'POST', 'form');
    
    // Тест 3: GET с параметрами
    echo "<strong>Test 3: GET with parameters</strong><br>\n";
    test_api_call($base_url, $test_data, 'GET', 'query');
    
    echo "<hr>\n";
}

function test_api_call($base_url, $data, $method = 'POST', $format = 'json') {
    $url = $base_url;
    
    $args = [
        'method' => $method,
        'timeout' => 10,
        'headers' => []
    ];
    
    if ($method === 'POST') {
        if ($format === 'json') {
            $args['body'] = json_encode($data);
            $args['headers']['Content-Type'] = 'application/json';
        } else {
            $args['body'] = http_build_query($data);
            $args['headers']['Content-Type'] = 'application/x-www-form-urlencoded';
        }
    } else {
        $url .= '?' . http_build_query($data);
    }
    
    echo "URL: {$url}<br>\n";
    echo "Method: {$method}<br>\n";
    echo "Body: " . ($args['body'] ?? 'None') . "<br>\n";
    
    // Имитация запроса (закомментируйте для реального теста)
    echo "Status: <span style='color: orange;'>SIMULATED - Replace with real request</span><br>\n";
    
    // Раскомментируйте для реального тестирования:
    /*
    $response = wp_remote_request($url, $args);
    
    if (is_wp_error($response)) {
        echo "Error: " . $response->get_error_message() . "<br>\n";
    } else {
        $code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        
        echo "Status: {$code}<br>\n";
        echo "Response: " . substr($body, 0, 200) . "...<br>\n";
    }
    */
    
    echo "<br>\n";
}

echo "<h3>📋 Что проверить вручную:</h3>\n";
echo "<ol>\n";
echo "<li><strong>Базовый URL:</strong> Проверьте документацию Bil24 для правильного API endpoint</li>\n";
echo "<li><strong>Аутентификация:</strong> Возможно нужны дополнительные заголовки авторизации</li>\n";
echo "<li><strong>Версия API:</strong> Убедитесь что используете правильную версию</li>\n";
echo "<li><strong>Headers:</strong> Могут требоваться специальные заголовки</li>\n";
echo "</ol>\n";

echo "<h3>🔧 Рекомендуемый формат для тестирования:</h3>\n";
echo "<pre>\n";
echo "POST [REAL_BIL24_URL]\n";
echo "Content-Type: application/json\n\n";
echo json_encode($test_data, JSON_PRETTY_PRINT) . "\n";
echo "</pre>\n";
?>