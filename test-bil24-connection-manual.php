<?php
/**
 * Manual Bil24 API Connection Tester
 * Запустите этот скрипт напрямую для тестирования подключения
 */

// Настройки для тестирования - ЗАМЕНИТЕ НА РЕАЛЬНЫЕ
$test_config = [
    'fid' => 2558, // Ваш реальный FID
    'token' => 'bf7404918fd2785b2178', // Ваш реальный токен (замените!)
    
    // ВОЗМОЖНЫЕ URL - один из них должен быть правильным
    'possible_urls' => [
        'https://api.bil24.com',
        'https://bil24.com/api',
        'https://api.bil24.ru',
        'https://bil24.ru/api',
        'https://app.bil24.com/api',
        'https://portal.bil24.com/api',
        // Добавьте другие варианты, если знаете
    ]
];

echo "<h1>🧪 Manual Bil24 Connection Test</h1>\n";
echo "<p><strong>⚠️ Важно:</strong> Замените токен на реальный перед запуском!</p>\n";

foreach ($test_config['possible_urls'] as $base_url) {
    echo "<div style='border: 2px solid #333; margin: 20px 0; padding: 15px;'>\n";
    echo "<h2>🌐 Testing: {$base_url}</h2>\n";
    
    // Тест разных путей
    $test_paths = ['', '/api', '/v1', '/v2', '/api/v1', '/api/v2'];
    
    foreach ($test_paths as $path) {
        $full_url = rtrim($base_url, '/') . $path;
        echo "<h3>🔗 URL: {$full_url}</h3>\n";
        
        // Данные для отправки
        $request_data = [
            'locale' => 'en',
            'command' => 'GET_ALL_ACTIONS',
            'fid' => (int)$test_config['fid'],
            'token' => $test_config['token']
        ];
        
        echo "<strong>📋 Request Data:</strong><br>\n";
        echo "<pre>" . json_encode($request_data, JSON_PRETTY_PRINT) . "</pre>\n";
        
        // cURL команда для ручного тестирования
        echo "<strong>💻 cURL Command:</strong><br>\n";
        echo "<textarea style='width: 100%; height: 100px;' readonly>";
        echo "curl -X POST \"{$full_url}\" \\\n";
        echo "  -H \"Content-Type: application/json\" \\\n";
        echo "  -H \"Accept: application/json\" \\\n";
        echo "  -d '" . json_encode($request_data) . "'";
        echo "</textarea><br><br>\n";
        
        // PHP код для тестирования
        echo "<strong>🐘 PHP Test Code:</strong><br>\n";
        echo "<textarea style='width: 100%; height: 150px;' readonly>";
        echo "\$ch = curl_init();\n";
        echo "curl_setopt(\$ch, CURLOPT_URL, '{$full_url}');\n";
        echo "curl_setopt(\$ch, CURLOPT_POST, true);\n";
        echo "curl_setopt(\$ch, CURLOPT_POSTFIELDS, json_encode(" . var_export($request_data, true) . "));\n";
        echo "curl_setopt(\$ch, CURLOPT_HTTPHEADER, [\n";
        echo "    'Content-Type: application/json',\n";
        echo "    'Accept: application/json'\n";
        echo "]);\n";
        echo "curl_setopt(\$ch, CURLOPT_RETURNTRANSFER, true);\n";
        echo "\$response = curl_exec(\$ch);\n";
        echo "\$http_code = curl_getinfo(\$ch, CURLINFO_HTTP_CODE);\n";
        echo "curl_close(\$ch);\n";
        echo "echo \"HTTP Code: {\$http_code}\\n\";\n";
        echo "echo \"Response: {\$response}\\n\";";
        echo "</textarea><br><br>\n";
        
        // Автоматический тест (если раскомментировать)
        echo "<strong>🤖 Automatic Test Result:</strong><br>\n";
        echo "<div style='background: #f0f0f0; padding: 10px;'>\n";
        
        // РАСКОММЕНТИРУЙТЕ ДЛЯ РЕАЛЬНОГО ТЕСТИРОВАНИЯ:
        /*
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $full_url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($request_data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Accept: application/json'
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Только для тестов
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            echo "❌ cURL Error: {$error}<br>\n";
        } else {
            echo "📊 HTTP Code: <strong>{$http_code}</strong><br>\n";
            
            if ($http_code == 200) {
                echo "✅ <span style='color: green;'>SUCCESS!</span><br>\n";
                echo "📄 Response: <pre>" . htmlspecialchars($response) . "</pre>\n";
            } elseif ($http_code == 404) {
                echo "🔍 <span style='color: orange;'>Not Found</span> - Try different path<br>\n";
            } elseif ($http_code >= 400 && $http_code < 500) {
                echo "⚠️ <span style='color: red;'>Client Error ({$http_code})</span><br>\n";
                echo "📄 Response: <pre>" . htmlspecialchars($response) . "</pre>\n";
            } else {
                echo "💥 <span style='color: red;'>Server Error ({$http_code})</span><br>\n";
                echo "📄 Response: <pre>" . htmlspecialchars($response) . "</pre>\n";
            }
        }
        */
        
        echo "⚠️ <em>Automatic test disabled - uncomment code above to enable</em>\n";
        echo "</div>\n";
        
        echo "<hr>\n";
    }
    echo "</div>\n";
}

echo "<div style='background: #ffffcc; padding: 20px; margin: 20px 0; border: 1px solid #ffcc00;'>\n";
echo "<h2>📝 Что делать дальше:</h2>\n";
echo "<ol>\n";
echo "<li><strong>Скопируйте cURL команды</strong> и протестируйте в терминале</li>\n";
echo "<li><strong>Найдите рабочий URL</strong> (статус 200 или что-то кроме 404)</li>\n";
echo "<li><strong>Обновите настройки</strong> в WordPress плагине</li>\n";
echo "<li><strong>Свяжитесь с Bil24</strong> для получения официальной документации</li>\n";
echo "</ol>\n";
echo "</div>\n";

echo "<div style='background: #ffeeee; padding: 20px; margin: 20px 0; border: 1px solid #ff0000;'>\n";
echo "<h2>🚨 ВАЖНО:</h2>\n";
echo "<ul>\n";
echo "<li>Замените <code>token</code> на реальный</li>\n";
echo "<li>Проверьте <code>fid</code> в настройках Bil24</li>\n";
echo "<li>Для продакшена включите SSL проверку</li>\n";
echo "<li>Не публикуйте реальные токены в коде!</li>\n";
echo "</ul>\n";
echo "</div>\n";
?>