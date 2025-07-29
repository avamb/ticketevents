<?php
/**
 * Fix Permissions Script for Bil24 Connector
 * This script will help fix user permission issues
 */

// Load WordPress environment
$wp_load = dirname(__FILE__) . '/../../../wp-load.php';
if (file_exists($wp_load)) {
    require_once($wp_load);
} else {
    die('WordPress environment not found');
}

// Check if we have basic admin access
if (!function_exists('current_user_can') || !current_user_can('manage_options')) {
    echo "<h1>❌ Access Denied</h1>";
    echo "<p>You need administrator privileges to run this script.</p>";
    echo "<p>Please log in as an administrator first.</p>";
    exit;
}

echo "<h1>🔧 Fix Permissions & Plugin Access</h1>";

// Step 1: Check current user
echo "<h2>Step 1: Current User Check</h2>";
$user = wp_get_current_user();
echo "<strong>User ID:</strong> " . $user->ID . "<br>";
echo "<strong>User Login:</strong> " . $user->user_login . "<br>";
echo "<strong>User Roles:</strong> " . implode(', ', $user->roles) . "<br>";

// Step 2: Ensure user has administrator role
echo "<h2>Step 2: Administrator Role Check</h2>";
if (!in_array('administrator', $user->roles)) {
    echo "<p style='color: orange;'>⚠️ User does not have administrator role. Adding...</p>";
    
    // Add administrator role
    $user->add_role('administrator');
    echo "✅ Administrator role added<br>";
    
    // Refresh user data
    $user = wp_get_current_user();
    echo "<strong>Updated Roles:</strong> " . implode(', ', $user->roles) . "<br>";
} else {
    echo "✅ User already has administrator role<br>";
}

// Step 3: Check plugin activation
echo "<h2>Step 3: Plugin Activation Check</h2>";
if (function_exists('is_plugin_active')) {
    $plugin_file = 'bil24-connector/bil24-connector.php';
    $is_active = is_plugin_active($plugin_file);
    
    if (!$is_active) {
        echo "<p style='color: orange;'>⚠️ Plugin not active. Activating...</p>";
        
        // Try to activate plugin
        $result = activate_plugin($plugin_file);
        if (is_wp_error($result)) {
            echo "❌ Failed to activate plugin: " . $result->get_error_message() . "<br>";
        } else {
            echo "✅ Plugin activated successfully<br>";
        }
    } else {
        echo "✅ Plugin is already active<br>";
    }
} else {
    echo "❌ Plugin functions not available<br>";
}

// Step 4: Force plugin initialization
echo "<h2>Step 4: Force Plugin Initialization</h2>";
$includes_dir = __DIR__ . '/includes/';

// Load required classes manually
$required_files = [
    'Constants.php' => '\\Bil24\\Constants',
    'Utils.php' => '\\Bil24\\Utils',
    'Api/Client.php' => '\\Bil24\\Api\\Client',
    'Admin/SettingsPage.php' => '\\Bil24\\Admin\\SettingsPage',
    'Plugin.php' => '\\Bil24\\Plugin'
];

foreach ($required_files as $file => $class) {
    $full_path = $includes_dir . $file;
    if (file_exists($full_path) && !class_exists($class)) {
        require_once($full_path);
        echo "✅ Loaded $class<br>";
    }
}

// Step 5: Force settings page registration
echo "<h2>Step 5: Force Settings Page Registration</h2>";
if (class_exists('\\Bil24\\Admin\\SettingsPage')) {
    try {
        $settings_page = new \Bil24\Admin\SettingsPage();
        $settings_page->register();
        echo "✅ Settings page hooks registered<br>";
        
        // Force menu addition
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
        echo "❌ Error registering settings page: " . $e->getMessage() . "<br>";
    }
} else {
    echo "❌ SettingsPage class not available<br>";
}

// Step 6: Create default settings if they don't exist
echo "<h2>Step 6: Create Default Settings</h2>";
$option_name = 'bil24_settings';
if (class_exists('\\Bil24\\Constants')) {
    $option_name = \Bil24\Constants::OPTION_SETTINGS;
}

$settings = get_option($option_name);
if ($settings === false) {
    $default_settings = [
        'fid' => '',
        'token' => '',
        'env' => 'test'
    ];
    update_option($option_name, $default_settings);
    echo "✅ Default settings created<br>";
} else {
    echo "✅ Settings already exist<br>";
}

// Step 7: Test settings page access
echo "<h2>Step 7: Test Settings Page Access</h2>";
if (current_user_can('manage_options')) {
    echo "✅ User has manage_options capability<br>";
    
    // Test if we can render the page
    if (class_exists('\\Bil24\\Admin\\SettingsPage')) {
        try {
            $settings_page = new \Bil24\Admin\SettingsPage();
            ob_start();
            $settings_page->render_page();
            $output = ob_get_clean();
            
            if (!empty($output)) {
                echo "✅ Settings page renders successfully<br>";
            } else {
                echo "❌ Settings page renders empty output<br>";
            }
        } catch (Exception $e) {
            echo "❌ Error rendering settings page: " . $e->getMessage() . "<br>";
        }
    }
} else {
    echo "❌ User still doesn't have manage_options capability<br>";
}

// Step 8: Clear any caches
echo "<h2>Step 8: Clear Caches</h2>";
if (function_exists('wp_cache_flush')) {
    wp_cache_flush();
    echo "✅ WordPress cache cleared<br>";
}

if (function_exists('w3tc_flush_all')) {
    w3tc_flush_all();
    echo "✅ W3 Total Cache cleared<br>";
}

if (function_exists('wp_rocket_clean_domain')) {
    wp_rocket_clean_domain();
    echo "✅ WP Rocket cache cleared<br>";
}

// Step 9: Final status check
echo "<h2>Step 9: Final Status Check</h2>";
$user = wp_get_current_user();
$has_admin_role = in_array('administrator', $user->roles);
$can_manage_options = current_user_can('manage_options');
$plugin_active = function_exists('is_plugin_active') ? is_plugin_active('bil24-connector/bil24-connector.php') : false;

echo "<strong>Administrator Role:</strong> " . ($has_admin_role ? "✅ YES" : "❌ NO") . "<br>";
echo "<strong>Can Manage Options:</strong> " . ($can_manage_options ? "✅ YES" : "❌ NO") . "<br>";
echo "<strong>Plugin Active:</strong> " . ($plugin_active ? "✅ YES" : "❌ NO") . "<br>";

if ($has_admin_role && $can_manage_options && $plugin_active) {
    echo "<p style='color: green;'><strong>✅ ALL CHECKS PASSED!</strong></p>";
    echo "<p>The settings page should now be accessible.</p>";
} else {
    echo "<p style='color: red;'><strong>❌ SOME CHECKS FAILED</strong></p>";
    echo "<p>Please check the issues above and try again.</p>";
}

// Step 10: Provide access links
echo "<h2>Step 10: Access Links</h2>";
$settings_url = admin_url('options-general.php?page=bil24-connector');
echo "<p><strong>Settings Page:</strong> <a href='$settings_url' target='_blank'>$settings_url</a></p>";
echo "<p><strong>Emergency Settings:</strong> <a href='emergency-settings.php' target='_blank'>emergency-settings.php</a></p>";
echo "<p><strong>Debug Script:</strong> <a href='debug-auth.php' target='_blank'>debug-auth.php</a></p>";

echo "<h2>✅ Fix Complete</h2>";
echo "<p>Try accessing the settings page now. If it still doesn't work, check the debug script for more details.</p>"; 