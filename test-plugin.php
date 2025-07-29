<?php
/**
 * Test script for Bil24 Connector plugin
 * Place this file in the plugin directory and access via browser
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    // Load WordPress environment if not already loaded
    $wp_load = dirname(__FILE__) . '/../../../wp-load.php';
    if (file_exists($wp_load)) {
        require_once($wp_load);
    } else {
        die('WordPress environment not found');
    }
}

// Enable debug mode for this script
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Bil24 Connector Plugin Test</h1>";

// Test 1: Check if plugin files exist
echo "<h2>File Existence Check</h2>";
$main_file = __DIR__ . '/bil24-connector.php';
echo "Main plugin file: " . ($main_file && file_exists($main_file) ? "✅ EXISTS" : "❌ NOT FOUND") . "<br>";

$autoloader = __DIR__ . '/vendor/autoload.php';
echo "Composer autoloader: " . ($autoloader && file_exists($autoloader) ? "✅ EXISTS" : "❌ NOT FOUND") . "<br>";

// Test 2: Load autoloader manually
if (file_exists($autoloader)) {
    require_once($autoloader);
    echo "Autoloader loaded successfully<br>";
} else {
    echo "❌ Cannot load autoloader<br>";
}

// Test 3: Check if classes exist
echo "<h2>Class Existence Check</h2>";
$classes_to_check = [
    '\\Bil24\\Plugin',
    '\\Bil24\\Constants',
    '\\Bil24\\Utils',
    '\\Bil24\\Admin\\SettingsPage',
    '\\Bil24\\Api\\Client'
];

foreach ($classes_to_check as $class) {
    echo "$class: " . (class_exists($class) ? "✅ EXISTS" : "❌ NOT FOUND") . "<br>";
}

// Test 4: Check current user and capabilities
echo "<h2>User & Capabilities Check</h2>";
if (function_exists('get_current_user_id')) {
    $user_id = get_current_user_id();
    echo "Current user ID: $user_id<br>";
    
    if ($user_id > 0) {
        $user = get_userdata($user_id);
        echo "User login: " . $user->user_login . "<br>";
        echo "User roles: " . implode(', ', $user->roles) . "<br>";
        echo "Can manage options: " . (current_user_can('manage_options') ? "✅ YES" : "❌ NO") . "<br>";
    } else {
        echo "❌ No user logged in<br>";
    }
} else {
    echo "❌ WordPress functions not available<br>";
}

// Test 5: Check if settings page is registered
echo "<h2>Settings Page Registration Check</h2>";
if (function_exists('get_option') && class_exists('\\Bil24\\Admin\\SettingsPage')) {
    try {
        $settings_page = new \Bil24\Admin\SettingsPage();
        
        // Test menu registration
        global $submenu;
        if (isset($submenu['options-general.php'])) {
            $bil24_found = false;
            foreach ($submenu['options-general.php'] as $item) {
                if (strpos($item[2], 'bil24-connector') !== false) {
                    echo "✅ Settings page found in menu: " . $item[0] . "<br>";
                    $bil24_found = true;
                    break;
                }
            }
            if (!$bil24_found) {
                echo "❌ Settings page not found in submenu<br>";
            }
        } else {
            echo "❌ Options submenu not available<br>";
        }
        
        // Test manual registration
        echo "Attempting manual registration...<br>";
        $settings_page->register();
        echo "✅ SettingsPage->register() completed<br>";
        
    } catch (Exception $e) {
        echo "❌ Error testing SettingsPage: " . $e->getMessage() . "<br>";
    }
} else {
    echo "❌ Cannot test SettingsPage (missing dependencies)<br>";
}

// Test 6: Check plugin options
echo "<h2>Plugin Options Check</h2>";
if (class_exists('\\Bil24\\Constants')) {
    $option_name = \Bil24\Constants::OPTION_SETTINGS;
    echo "Option name: $option_name<br>";
    
    $settings = get_option($option_name, null);
    if ($settings !== null) {
        echo "✅ Settings option exists<br>";
        echo "Settings content: " . print_r($settings, true) . "<br>";
    } else {
        echo "❌ Settings option not found<br>";
    }
} else {
    echo "❌ Constants class not available<br>";
}

// Test 7: Test direct page access
echo "<h2>Direct Page Access Test</h2>";
if (current_user_can('manage_options') && class_exists('\\Bil24\\Admin\\SettingsPage')) {
    echo '<a href="' . admin_url('options-general.php?page=bil24-connector') . '" target="_blank">➡️ Try accessing settings page</a><br>';
} else {
    echo "❌ Cannot test direct access (insufficient permissions or missing class)<br>";
}

// Test 8: Manual page rendering test
echo "<h2>Manual Page Rendering Test</h2>";
if (current_user_can('manage_options') && class_exists('\\Bil24\\Admin\\SettingsPage')) {
    try {
        echo "Attempting to render settings page manually...<br>";
        echo "<div style='border: 2px solid #007cba; padding: 20px; margin: 10px 0;'>";
        
        $settings_page = new \Bil24\Admin\SettingsPage();
        ob_start();
        $settings_page->render_page();
        $output = ob_get_clean();
        
        if (!empty($output)) {
            echo "✅ Page rendering successful<br>";
            echo $output;
        } else {
            echo "❌ Page rendering returned empty output<br>";
        }
        
        echo "</div>";
    } catch (Exception $e) {
        echo "❌ Error rendering page: " . $e->getMessage() . "<br>";
    }
} else {
    echo "❌ Cannot test page rendering<br>";
}

echo "<h2>Test Complete</h2>";
echo "Check the output above for any issues."; 