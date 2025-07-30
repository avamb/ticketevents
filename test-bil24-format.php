<?php
/**
 * Test Bil24 API Format - проверяем правильный формат запросов
 * 
 * Этот скрипт тестирует исправленный формат запросов к Bil24 API
 */

// Load WordPress environment
$wp_load_paths = [
    dirname(__FILE__) . '/../../../wp-load.php',
    dirname(__FILE__) . '/../../wp-load.php',
    dirname(__FILE__) . '/../wp-load.php',
    dirname(__FILE__) . '/wp-load.php'
];

$wp_loaded = false;
foreach ($wp_load_paths as $wp_load) {
    if (file_exists($wp_load)) {
        require_once($wp_load);
        $wp_loaded = true;
        break;
    }
}

if (!$wp_loaded) {
    die('❌ WordPress environment not found. Please ensure this script is in the correct plugin directory.');
}

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>🔧 Bil24 API Format Test</h1>";
echo "<p><em>Тестируем исправленный формат запросов к Bil24 API...</em></p>";

// Load plugin classes
$plugin_dir = dirname(__FILE__);
$autoloader = $plugin_dir . '/vendor/autoload.php';

if (file_exists($autoloader)) {
    require_once($autoloader);
    echo "✅ Composer autoloader loaded<br>";
} else {
    echo "⚠️ No Composer autoloader found, trying manual class loading<br>";
    
    $required_files = [
        '/includes/Constants.php',
        '/includes/Utils.php',
        '/includes/Api/Client.php'
    ];
    
    foreach ($required_files as $file) {
        $full_path = $plugin_dir . $file;
        if (file_exists($full_path)) {
            require_once($full_path);
            echo "✅ Loaded: " . basename($file) . "<br>";
        } else {
            echo "❌ Missing: " . $file . "<br>";
        }
    }
}

echo "<hr>";

// Get current settings
echo "<h2>⚙️ Current Plugin Settings</h2>";
$settings = get_option('bil24_settings', []);
$fid = $settings['fid'] ?? '';
$token = $settings['token'] ?? '';
$env = $settings['env'] ?? 'test';

echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
echo "<tr><td><strong>FID</strong></td><td>" . (!empty($fid) ? "✅ Configured: " . esc_html($fid) : "❌ Not set") . "</td></tr>";
echo "<tr><td><strong>Token</strong></td><td>" . (!empty($token) ? "✅ Configured (" . substr($token, 0, 8) . "...)" : "❌ Not set") . "</td></tr>";
echo "<tr><td><strong>Environment</strong></td><td>" . ucfirst($env) . "</td></tr>";
echo "</table>";

if (empty($fid) || empty($token)) {
    echo "<p style='color: red;'><strong>❌ Cannot test - FID and Token are required</strong></p>";
    exit;
}

echo "<hr>";

// Show what the request should look like
echo "<h2>📤 Правильный формат запроса Bil24</h2>";
echo "<h3>URL:</h3>";
$api_urls = [
    'test' => 'https://api.bil24.pro:1240',
    'prod' => 'https://api.bil24.pro'
];
$api_url = $api_urls[$env];
echo "<code>{$api_url}</code>";

echo "<h3>Method:</h3>";
echo "<code>POST</code>";

echo "<h3>Headers:</h3>";
echo "<pre>Content-Type: application/json</pre>";

echo "<h3>Request Body (JSON):</h3>";
$request_body = [
    'locale' => 'en',
    'command' => 'GET_ALL_ACTIONS', 
    'fid' => (int)$fid,
    'token' => $token
];
echo "<pre>" . wp_json_encode($request_body, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "</pre>";

echo "<hr>";

// Test the API
echo "<h2>🌐 Test Bil24 API Connection</h2>";

try {
    if (!class_exists('\\Bil24\\Api\\Client')) {
        throw new Exception('API Client class not found');
    }
    
    $api = new \Bil24\Api\Client();
    
    echo "<h3>1. Проверяем конфигурацию:</h3>";
    $config = $api->get_config_status();
    echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
    foreach ($config as $key => $value) {
        echo "<tr><td><strong>" . ucfirst(str_replace('_', ' ', $key)) . "</strong></td><td>" . 
             (is_bool($value) ? ($value ? '✅ Yes' : '❌ No') : esc_html($value)) . "</td></tr>";
    }
    echo "</table>";
    
    echo "<h3>2. Тестируем подключение с командой GET_ALL_ACTIONS:</h3>";
    
    $start_time = microtime(true);
    $connected = $api->test_connection();
    $end_time = microtime(true);
    $duration = round(($end_time - $start_time) * 1000, 2);
    
    if ($connected) {
        echo "<div style='background: #d4edda; border: 1px solid #c3e6cb; padding: 15px; border-radius: 5px;'>";
        echo "<h3 style='color: #155724; margin-top: 0;'>✅ Connection Successful!</h3>";
        echo "<p><strong>Response time:</strong> {$duration} ms</p>";
        echo "<p>Bil24 API connection working with proper format!</p>";
        echo "</div>";
    } else {
        echo "<div style='background: #f8d7da; border: 1px solid #f5c6cb; padding: 15px; border-radius: 5px;'>";
        echo "<h3 style='color: #721c24; margin-top: 0;'>❌ Connection Failed</h3>";
        echo "<p>Could not establish connection to Bil24 API.</p>";
        echo "<p><strong>Response time:</strong> {$duration} ms</p>";
        echo "</div>";
    }
    
    echo "<h3>3. Тестируем прямой запрос:</h3>";
    echo "<p>Отправляем команду GET_ALL_ACTIONS напрямую...</p>";
    
    try {
        $response = $api->execute_command('GET_ALL_ACTIONS');
        echo "<div style='background: #d1ecf1; border: 1px solid #bee5eb; padding: 15px; border-radius: 5px;'>";
        echo "<h4>📥 Ответ от API:</h4>";
        echo "<pre>" . wp_json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "</pre>";
        echo "</div>";
    } catch (Exception $e) {
        echo "<div style='background: #f8d7da; border: 1px solid #f5c6cb; padding: 15px; border-radius: 5px;'>";
        echo "<h4 style='color: #721c24;'>❌ Ошибка при выполнении команды:</h4>";
        echo "<p>" . esc_html($e->getMessage()) . "</p>";
        echo "</div>";
    }
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; border: 1px solid #f5c6cb; padding: 15px; border-radius: 5px;'>";
    echo "<h3 style='color: #721c24; margin-top: 0;'>❌ Test Error</h3>";
    echo "<p><strong>Error:</strong> " . esc_html($e->getMessage()) . "</p>";
    echo "</div>";
}

echo "<hr>";

// Show debug info
echo "<h2>🔍 Debug Information</h2>";

if (defined('WP_DEBUG') && WP_DEBUG) {
    echo "<h3>Recent logs (last 10 Bil24 entries):</h3>";
    if (class_exists('\\Bil24\\Utils')) {
        $logs = \Bil24\Utils::get_recent_logs(10);
        if (!empty($logs)) {
            echo "<table border='1' cellpadding='5' style='border-collapse: collapse; width: 100%;'>";
            echo "<tr><th>Time</th><th>Level</th><th>Message</th></tr>";
            foreach ($logs as $log) {
                $level_color = $log['level'] === 'error' ? '#721c24' : 
                              ($log['level'] === 'warning' ? '#856404' : '#155724');
                echo "<tr>";
                echo "<td>" . esc_html($log['timestamp']) . "</td>";
                echo "<td style='color: {$level_color};'>" . strtoupper($log['level']) . "</td>";
                echo "<td>" . esc_html($log['message']) . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "<p>No recent Bil24 logs found. Make sure WP_DEBUG_LOG is enabled.</p>";
        }
    }
} else {
    echo "<p>⚠️ WP_DEBUG is disabled. Enable it in wp-config.php to see detailed logs:</p>";
    echo "<pre>define('WP_DEBUG', true);\ndefine('WP_DEBUG_LOG', true);</pre>";
}

echo "<hr>";
echo "<h2>✅ Test Complete</h2>";
echo "<p>Теперь запросы формируются в правильном формате Bil24 API!</p>";
echo "<p><a href='" . admin_url('options-general.php?page=bil24-connector') . "'>← Return to Plugin Settings</a></p>";