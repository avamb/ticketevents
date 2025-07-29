<?php
/**
 * Debug script for Bil24 Connector settings page
 * Access this directly to test settings page functionality
 */

// Load WordPress environment
$wp_load = dirname(__FILE__) . '/../../../wp-load.php';
if (file_exists($wp_load)) {
    require_once($wp_load);
} else {
    die('WordPress environment not found');
}

// Check permissions
if (!current_user_can('manage_options')) {
    wp_die('Sorry, you are not allowed to access this page.');
}

echo "<h1>🔧 Bil24 Connector Settings Debug</h1>";

// Test 1: Check if plugin files exist
echo "<h2>1. File Existence Check</h2>";
$files_to_check = [
    'bil24-connector.php' => 'Main plugin file',
    'vendor/autoload.php' => 'Composer autoloader',
    'includes/Constants.php' => 'Constants class',
    'includes/Utils.php' => 'Utils class',
    'includes/Admin/SettingsPage.php' => 'SettingsPage class',
    'includes/Plugin.php' => 'Plugin class',
    'includes/Api/Client.php' => 'API Client class'
];

foreach ($files_to_check as $file => $description) {
    $full_path = __DIR__ . '/' . $file;
    $exists = file_exists($full_path);
    echo "<strong>$description ($file):</strong> " . ($exists ? "✅ EXISTS" : "❌ NOT FOUND") . "<br>";
}

// Test 2: Load classes manually
echo "<h2>2. Class Loading Test</h2>";
$includes_dir = __DIR__ . '/includes/';

// Load classes in dependency order
$classes_to_load = [
    'Constants.php' => '\\Bil24\\Constants',
    'Utils.php' => '\\Bil24\\Utils',
    'Api/Client.php' => '\\Bil24\\Api\\Client',
    'Admin/SettingsPage.php' => '\\Bil24\\Admin\\SettingsPage',
    'Plugin.php' => '\\Bil24\\Plugin'
];

foreach ($classes_to_load as $file => $class) {
    $full_path = $includes_dir . $file;
    if (file_exists($full_path)) {
        if (!class_exists($class)) {
            require_once($full_path);
        }
        $loaded = class_exists($class);
        echo "<strong>$class:</strong> " . ($loaded ? "✅ LOADED" : "❌ FAILED") . "<br>";
    } else {
        echo "<strong>$class:</strong> ❌ FILE NOT FOUND ($file)<br>";
    }
}

// Test 3: Test SettingsPage registration
echo "<h2>3. SettingsPage Registration Test</h2>";
if (class_exists('\\Bil24\\Admin\\SettingsPage')) {
    try {
        $settings_page = new \Bil24\Admin\SettingsPage();
        echo "✅ SettingsPage instance created<br>";
        
        // Test registration
        $settings_page->register();
        echo "✅ SettingsPage->register() completed<br>";
        
        // Test menu addition
        $page_hook = add_options_page(
            'Bil24 Connector Settings',
            'Bil24 Connector',
            'manage_options',
            'bil24-connector',
            [$settings_page, 'render_page']
        );
        
        if ($page_hook) {
            echo "✅ Settings page menu added: $page_hook<br>";
        } else {
            echo "❌ Failed to add settings page menu<br>";
        }
        
    } catch (Exception $e) {
        echo "❌ Error testing SettingsPage: " . $e->getMessage() . "<br>";
    }
} else {
    echo "❌ SettingsPage class not available<br>";
}

// Test 4: Check WordPress hooks
echo "<h2>4. WordPress Hooks Test</h2>";
global $wp_filter;
$hooks_to_check = ['admin_menu', 'admin_init', 'plugins_loaded'];

foreach ($hooks_to_check as $hook) {
    if (isset($wp_filter[$hook])) {
        $count = count($wp_filter[$hook]->callbacks);
        echo "<strong>$hook:</strong> ✅ HAS $count CALLBACKS<br>";
    } else {
        echo "<strong>$hook:</strong> ❌ NO CALLBACKS<br>";
    }
}

// Test 5: Check current user and capabilities
echo "<h2>5. User & Capabilities Test</h2>";
$user = wp_get_current_user();
echo "<strong>User ID:</strong> " . $user->ID . "<br>";
echo "<strong>User Login:</strong> " . $user->user_login . "<br>";
echo "<strong>User Roles:</strong> " . implode(', ', $user->roles) . "<br>";
echo "<strong>Can manage_options:</strong> " . (current_user_can('manage_options') ? "✅ YES" : "❌ NO") . "<br>";

// Test 6: Check plugin options
echo "<h2>6. Plugin Options Test</h2>";
$option_name = 'bil24_settings';
if (class_exists('\\Bil24\\Constants')) {
    $option_name = \Bil24\Constants::OPTION_SETTINGS;
}

echo "<strong>Option name:</strong> $option_name<br>";
$settings = get_option($option_name);
if ($settings !== false) {
    echo "<strong>Settings exist:</strong> ✅ YES<br>";
    echo "<strong>Settings content:</strong> " . print_r($settings, true) . "<br>";
} else {
    echo "<strong>Settings exist:</strong> ❌ NO<br>";
}

// Test 7: Direct page rendering test
echo "<h2>7. Direct Page Rendering Test</h2>";
if (class_exists('\\Bil24\\Admin\\SettingsPage')) {
    try {
        echo "<div style='border: 2px solid #007cba; padding: 20px; margin: 10px 0; background: white;'>";
        echo "<h3>Settings Page Output:</h3>";
        
        $settings_page = new \Bil24\Admin\SettingsPage();
        ob_start();
        $settings_page->render_page();
        $output = ob_get_clean();
        
        if (!empty($output)) {
            echo "✅ Page rendering successful<br>";
            echo "<div style='max-height: 400px; overflow: auto; border: 1px solid #ccc; padding: 10px;'>";
            echo $output;
            echo "</div>";
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

// Test 8: Check global submenu
echo "<h2>8. WordPress Menu Test</h2>";
global $submenu;
if (isset($submenu['options-general.php'])) {
    $bil24_found = false;
    foreach ($submenu['options-general.php'] as $item) {
        if (strpos($item[2], 'bil24-connector') !== false) {
            echo "✅ Settings page found in menu: " . $item[0] . " -> " . $item[2] . "<br>";
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

echo "<h2>✅ Debug Complete</h2>";
echo "<p><a href='" . admin_url('options-general.php?page=bil24-connector') . "' target='_blank'>Try accessing settings page</a></p>";
echo "<p><a href='" . admin_url('plugins.php') . "'>Go to plugins page</a></p>"; 