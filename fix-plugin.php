<?php
/**
 * Fix script for Bil24 Connector plugin
 * This script will force reactivation and fix common issues
 */

// Load WordPress environment
$wp_load = dirname(__FILE__) . '/../../../wp-load.php';
if (file_exists($wp_load)) {
    require_once($wp_load);
} else {
    die('WordPress environment not found');
}

// Must be admin to run this script
if (!current_user_can('manage_options')) {
    die('Insufficient permissions');
}

echo "<h1>Bil24 Connector Plugin Fix</h1>";

// Step 1: Check and load autoloader
echo "<h2>Step 1: Loading Dependencies</h2>";
$autoloader = __DIR__ . '/vendor/autoload.php';
if (file_exists($autoloader)) {
    require_once($autoloader);
    echo "✅ Autoloader loaded<br>";
} else {
    echo "❌ Autoloader not found - run 'composer install'<br>";
    exit;
}

// Step 2: Manual class loading
echo "<h2>Step 2: Loading Classes</h2>";
$includes_dir = __DIR__ . '/includes';

// Define required files in dependency order
$required_files = [
    '/Constants.php' => '\\Bil24\\Constants',
    '/Utils.php' => '\\Bil24\\Utils',
    '/Admin/SettingsPage.php' => '\\Bil24\\Admin\\SettingsPage',
    '/Api/Client.php' => '\\Bil24\\Api\\Client',
    '/Plugin.php' => '\\Bil24\\Plugin'
];

foreach ($required_files as $file => $class) {
    $full_path = $includes_dir . $file;
    if (file_exists($full_path)) {
        if (!class_exists($class)) {
            require_once($full_path);
        }
        $status = class_exists($class) ? "✅" : "❌";
        echo "$status $class<br>";
    } else {
        echo "❌ File not found: $file<br>";
    }
}

// Step 3: Force plugin activation
echo "<h2>Step 3: Plugin Activation</h2>";
if (class_exists('\\Bil24\\Plugin')) {
    try {
        // Call activation hook manually
        \Bil24\Plugin::activate();
        echo "✅ Plugin activation completed<br>";
    } catch (Exception $e) {
        echo "❌ Activation error: " . $e->getMessage() . "<br>";
    }
} else {
    echo "❌ Plugin class not available<br>";
}

// Step 4: Register settings page manually
echo "<h2>Step 4: Settings Page Registration</h2>";
if (class_exists('\\Bil24\\Admin\\SettingsPage')) {
    try {
        $settings_page = new \Bil24\Admin\SettingsPage();
        
        // Force registration
        $settings_page->register();
        echo "✅ Settings page hooks registered<br>";
        
        // Try to add menu manually
        if (function_exists('add_options_page')) {
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
        }
        
    } catch (Exception $e) {
        echo "❌ Settings page error: " . $e->getMessage() . "<br>";
    }
} else {
    echo "❌ SettingsPage class not available<br>";
}

// Step 5: Initialize plugin instance
echo "<h2>Step 5: Plugin Instance</h2>";
if (class_exists('\\Bil24\\Plugin')) {
    try {
        $plugin = \Bil24\Plugin::instance();
        echo "✅ Plugin instance created<br>";
        
        // Manually trigger admin_init
        if (method_exists($plugin, 'admin_init')) {
            $plugin->admin_init();
            echo "✅ admin_init method called<br>";
        }
        
    } catch (Exception $e) {
        echo "❌ Plugin instance error: " . $e->getMessage() . "<br>";
    }
} else {
    echo "❌ Plugin class not available<br>";
}

// Step 6: Check final status
echo "<h2>Step 6: Final Status Check</h2>";

// Check if settings page is accessible
$settings_url = admin_url('options-general.php?page=bil24-connector');
echo "Settings page URL: <a href='$settings_url' target='_blank'>$settings_url</a><br>";

// Check options
if (class_exists('\\Bil24\\Constants')) {
    $option_name = \Bil24\Constants::OPTION_SETTINGS;
    $settings = get_option($option_name);
    if ($settings) {
        echo "✅ Plugin settings exist<br>";
    } else {
        echo "⚠️ Plugin settings not found, creating defaults...<br>";
        $default_settings = [
            'fid' => '',
            'token' => '',
            'env' => 'test'
        ];
        update_option($option_name, $default_settings);
        echo "✅ Default settings created<br>";
    }
}

// Check global submenu
global $submenu;
if (isset($submenu['options-general.php'])) {
    foreach ($submenu['options-general.php'] as $item) {
        if (strpos($item[2], 'bil24-connector') !== false) {
            echo "✅ Settings page found in WordPress menu<br>";
            break;
        }
    }
}

echo "<h2>✅ Fix Complete</h2>";
echo "Try accessing the <a href='$settings_url' target='_blank'>settings page</a> now.<br>";
echo "If you still have issues, check WordPress debug.log for detailed error messages."; 