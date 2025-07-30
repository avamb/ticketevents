<?php
/**
 * API Connection Test Script for Bil24 Connector
 * 
 * This script tests the API connection independently of WordPress admin interface
 * Run this script directly in browser or via command line to diagnose API issues
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

echo "<h1>🔌 Bil24 API Connection Test</h1>";
echo "<p><em>Testing connection to Bil24 API with current plugin settings...</em></p>";

// Load plugin classes
$plugin_dir = dirname(__FILE__);
$autoloader = $plugin_dir . '/vendor/autoload.php';

if (file_exists($autoloader)) {
    require_once($autoloader);
    echo "✅ Composer autoloader loaded<br>";
} else {
    echo "⚠️ No Composer autoloader found, trying manual class loading<br>";
    
    // Manual class loading
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

// Check if required classes exist
$required_classes = [
    '\\Bil24\\Constants' => 'Constants Class',
    '\\Bil24\\Utils' => 'Utils Class', 
    '\\Bil24\\Api\\Client' => 'API Client Class'
];

echo "<h2>📋 Class Availability Check</h2>";
$classes_ok = true;
foreach ($required_classes as $class => $name) {
    if (class_exists($class)) {
        echo "✅ {$name}: Available<br>";
    } else {
        echo "❌ {$name}: Missing<br>";
        $classes_ok = false;
    }
}

if (!$classes_ok) {
    echo "<p style='color: red;'><strong>❌ Cannot proceed - required classes are missing</strong></p>";
    exit;
}

echo "<hr>";

// Get current settings
echo "<h2>⚙️ Current Plugin Settings</h2>";
$settings = get_option('bil24_settings', []);
$fid = $settings['fid'] ?? '';
$token = $settings['token'] ?? '';
$env = $settings['env'] ?? 'test';

echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
echo "<tr><td><strong>FID</strong></td><td>" . (!empty($fid) ? "✅ Configured (" . substr($fid, 0, 8) . "...)" : "❌ Not set") . "</td></tr>";
echo "<tr><td><strong>Token</strong></td><td>" . (!empty($token) ? "✅ Configured (" . substr($token, 0, 8) . "...)" : "❌ Not set") . "</td></tr>";
echo "<tr><td><strong>Environment</strong></td><td>" . ucfirst($env) . "</td></tr>";
echo "</table>";

if (empty($fid) || empty($token)) {
    echo "<p style='color: red;'><strong>❌ Cannot test connection - FID and Token are required</strong></p>";
    echo "<p>Please configure your credentials first in the plugin settings.</p>";
    exit;
}

echo "<hr>";

// Test API connection
echo "<h2>🌐 API Connection Test</h2>";

try {
    $api = new \Bil24\Api\Client();
    
    // Show configuration status
    $config = $api->get_config_status();
    echo "<h3>Configuration Status:</h3>";
    echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
    foreach ($config as $key => $value) {
        echo "<tr><td><strong>" . ucfirst(str_replace('_', ' ', $key)) . "</strong></td><td>" . 
             (is_bool($value) ? ($value ? '✅ Yes' : '❌ No') : esc_html($value)) . "</td></tr>";
    }
    echo "</table>";
    
    echo "<h3>Testing Connection...</h3>";
    
    // Test connection with detailed output
    $start_time = microtime(true);
    $connected = $api->test_connection();
    $end_time = microtime(true);
    $duration = round(($end_time - $start_time) * 1000, 2);
    
    if ($connected) {
        echo "<div style='background: #d4edda; border: 1px solid #c3e6cb; padding: 15px; border-radius: 5px;'>";
        echo "<h3 style='color: #155724; margin-top: 0;'>✅ Connection Successful!</h3>";
        echo "<p><strong>Response time:</strong> {$duration} ms</p>";
        echo "<p>The plugin can successfully communicate with the Bil24 API.</p>";
        echo "</div>";
    } else {
        echo "<div style='background: #f8d7da; border: 1px solid #f5c6cb; padding: 15px; border-radius: 5px;'>";
        echo "<h3 style='color: #721c24; margin-top: 0;'>❌ Connection Failed</h3>";
        echo "<p>Could not establish connection to the Bil24 API.</p>";
        echo "<p><strong>Response time:</strong> {$duration} ms</p>";
        echo "</div>";
    }
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; border: 1px solid #f5c6cb; padding: 15px; border-radius: 5px;'>";
    echo "<h3 style='color: #721c24; margin-top: 0;'>❌ Connection Error</h3>";
    echo "<p><strong>Error:</strong> " . esc_html($e->getMessage()) . "</p>";
    echo "<p><strong>File:</strong> " . $e->getFile() . " (line " . $e->getLine() . ")</p>";
    echo "</div>";
}

echo "<hr>";

// Additional diagnostic info
echo "<h2>🔍 Additional Diagnostic Information</h2>";
echo "<h3>PHP Environment:</h3>";
echo "<ul>";
echo "<li><strong>PHP Version:</strong> " . PHP_VERSION . "</li>";
echo "<li><strong>WordPress Version:</strong> " . get_bloginfo('version') . "</li>";
echo "<li><strong>cURL available:</strong> " . (function_exists('curl_init') ? '✅ Yes' : '❌ No') . "</li>";
echo "<li><strong>OpenSSL available:</strong> " . (extension_loaded('openssl') ? '✅ Yes' : '❌ No') . "</li>";
echo "<li><strong>JSON extension:</strong> " . (extension_loaded('json') ? '✅ Yes' : '❌ No') . "</li>";
echo "</ul>";

echo "<h3>WordPress HTTP API:</h3>";
echo "<ul>";
echo "<li><strong>wp_remote_get available:</strong> " . (function_exists('wp_remote_get') ? '✅ Yes' : '❌ No') . "</li>";
echo "<li><strong>WP_Http class:</strong> " . (class_exists('WP_Http') ? '✅ Yes' : '❌ No') . "</li>";
echo "</ul>";

echo "<h3>Network Test:</h3>";
$test_url = 'https://httpbin.org/get';
echo "<p>Testing basic HTTPS connectivity...</p>";

$response = wp_remote_get($test_url, ['timeout' => 10]);
if (is_wp_error($response)) {
    echo "<p>❌ Network test failed: " . $response->get_error_message() . "</p>";
} else {
    $status_code = wp_remote_retrieve_response_code($response);
    echo "<p>✅ Network test successful (HTTP {$status_code})</p>";
}

echo "<hr>";
echo "<h2>✅ Test Complete</h2>";
echo "<p><a href='" . admin_url('options-general.php?page=bil24-connector') . "'>← Return to Plugin Settings</a></p>";