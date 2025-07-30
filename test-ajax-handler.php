<?php
/**
 * Test AJAX Handler Independently
 * 
 * Этот скрипт тестирует AJAX handler напрямую, минуя JavaScript
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

// Enable error reporting and logging
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

echo "<h1>🧪 Direct AJAX Handler Test</h1>";
echo "<p><em>Testing the ajax_test_connection method directly...</em></p>";

// Test if user is logged in
if (!is_user_logged_in()) {
    echo "<div style='background: #f8d7da; border: 1px solid #f5c6cb; padding: 15px; border-radius: 5px;'>";
    echo "<h3 style='color: #721c24;'>❌ User Not Logged In</h3>";
    echo "<p>Please log in as administrator first: <a href='" . wp_login_url($_SERVER['REQUEST_URI']) . "'>Login</a></p>";
    echo "</div>";
    exit;
}

// Test user permissions
if (!current_user_can('manage_options')) {
    echo "<div style='background: #f8d7da; border: 1px solid #f5c6cb; padding: 15px; border-radius: 5px;'>";
    echo "<h3 style='color: #721c24;'>❌ Insufficient Permissions</h3>";
    echo "<p>Current user does not have 'manage_options' capability.</p>";
    echo "</div>";
    exit;
}

echo "<h2>✅ User Authentication Passed</h2>";
echo "<p><strong>User:</strong> " . wp_get_current_user()->user_login . "</p>";

// Check current settings
echo "<h2>📋 Current Settings</h2>";
$settings = get_option('bil24_settings', []);
$fid = $settings['fid'] ?? '';
$token = $settings['token'] ?? '';
$env = $settings['env'] ?? 'test';

echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
echo "<tr><td><strong>FID</strong></td><td>" . (!empty($fid) ? "✅ Set: " . esc_html($fid) : "❌ Not set") . "</td></tr>";
echo "<tr><td><strong>Token</strong></td><td>" . (!empty($token) ? "✅ Set (" . substr($token, 0, 8) . "...)" : "❌ Not set") . "</td></tr>";
echo "<tr><td><strong>Environment</strong></td><td>" . ucfirst($env) . "</td></tr>";
echo "</table>";

if (empty($fid) || empty($token)) {
    echo "<div style='background: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; border-radius: 5px;'>";
    echo "<h3>⚠️ Credentials Missing</h3>";
    echo "<p>FID and Token must be configured to test the connection.</p>";
    echo "<p>The test will run anyway to check for loading errors...</p>";
    echo "</div>";
}

echo "<hr>";

// Test direct method call
echo "<h2>🎯 Direct Method Test</h2>";

try {
    // Load plugin classes manually
    $plugin_dir = dirname(__FILE__);
    $autoloader = $plugin_dir . '/vendor/autoload.php';

    if (file_exists($autoloader)) {
        require_once($autoloader);
        echo "✅ Autoloader loaded<br>";
    } else {
        echo "⚠️ No autoloader, loading manually<br>";
        
        $required_files = [
            '/includes/Constants.php',
            '/includes/Utils.php', 
            '/includes/Api/Client.php',
            '/includes/Admin/SettingsPage.php'
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
    
    // Check if settings page class exists
    if (!class_exists('\\Bil24\\Admin\\SettingsPage')) {
        throw new Exception('SettingsPage class not found');
    }
    
    echo "<h3>Testing AJAX Handler Directly:</h3>";
    
    // Create settings page instance
    $settings_page = new \Bil24\Admin\SettingsPage();
    
    // Mock the AJAX environment
    $_POST['_ajax_nonce'] = wp_create_nonce('bil24_test_connection');
    $_REQUEST['_wpnonce'] = $_POST['_ajax_nonce']; // Some WordPress versions check this
    
    echo "<p>🔧 Mocking AJAX environment...</p>";
    echo "<p><strong>Nonce:</strong> " . $_POST['_ajax_nonce'] . "</p>";
    
    // Capture output
    ob_start();
    
    try {
        $settings_page->ajax_test_connection();
        $output = ob_get_clean();
        
        // This should not happen if wp_send_json_* functions work properly
        if (!empty($output)) {
            echo "<div style='background: #f8d7da; border: 1px solid #f5c6cb; padding: 15px; border-radius: 5px;'>";
            echo "<h4>❌ Unexpected Output (should be JSON only):</h4>";
            echo "<pre>" . esc_html($output) . "</pre>";
            echo "</div>";
        } else {
            echo "<div style='background: #d4edda; border: 1px solid #c3e6cb; padding: 15px; border-radius: 5px;'>";
            echo "<h4>✅ Method Executed Successfully</h4>";
            echo "<p>The ajax_test_connection method ran without fatal errors.</p>";
            echo "<p>Check browser network tab or debug logs for the actual JSON response.</p>";
            echo "</div>";
        }
        
    } catch (Exception $e) {
        $output = ob_get_clean();
        echo "<div style='background: #f8d7da; border: 1px solid #f5c6cb; padding: 15px; border-radius: 5px;'>";
        echo "<h4>❌ Method Exception:</h4>";
        echo "<p><strong>Error:</strong> " . esc_html($e->getMessage()) . "</p>";
        echo "<p><strong>File:</strong> " . $e->getFile() . ":" . $e->getLine() . "</p>";
        if (!empty($output)) {
            echo "<p><strong>Output:</strong></p><pre>" . esc_html($output) . "</pre>";
        }
        echo "</div>";
    }
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; border: 1px solid #f5c6cb; padding: 15px; border-radius: 5px;'>";
    echo "<h3 style='color: #721c24;'>❌ Test Setup Error</h3>";
    echo "<p><strong>Error:</strong> " . esc_html($e->getMessage()) . "</p>";
    echo "</div>";
}

echo "<hr>";

// Show recent logs if available
echo "<h2>📝 Recent Debug Logs</h2>";
if (defined('WP_DEBUG') && WP_DEBUG && defined('WP_DEBUG_LOG') && WP_DEBUG_LOG) {
    $log_file = WP_CONTENT_DIR . '/debug.log';
    if (file_exists($log_file) && is_readable($log_file)) {
        $log_lines = file($log_file);
        $bil24_logs = array_filter($log_lines, function($line) {
            return strpos($line, '[Bil24]') !== false;
        });
        
        if (!empty($bil24_logs)) {
            $recent_logs = array_slice($bil24_logs, -10); // Last 10 entries
            echo "<div style='background: #f8f9fa; border: 1px solid #dee2e6; padding: 15px; border-radius: 5px;'>";
            echo "<h4>Last 10 Bil24 log entries:</h4>";
            echo "<pre style='font-size: 12px; max-height: 300px; overflow-y: scroll;'>";
            foreach ($recent_logs as $log) {
                echo esc_html($log);
            }
            echo "</pre>";
            echo "</div>";
        } else {
            echo "<p>No Bil24 logs found in debug.log</p>";
        }
    } else {
        echo "<p>Debug log file not found or not readable.</p>";
    }
} else {
    echo "<p>WP_DEBUG or WP_DEBUG_LOG is not enabled. Enable them in wp-config.php:</p>";
    echo "<pre>define('WP_DEBUG', true);\ndefine('WP_DEBUG_LOG', true);</pre>";
}

echo "<hr>";
echo "<h2>✅ Test Complete</h2>";
echo "<p>Now try the actual button in admin panel to see if the JavaScript error is fixed.</p>";
echo "<p><a href='" . admin_url('options-general.php?page=bil24-connector') . "'>← Go to Plugin Settings</a></p>";