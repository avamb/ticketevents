<?php
/**
 * Authentication Debug Script for Bil24 Connector
 * This script will help identify why the settings page is not accessible
 */

// Load WordPress environment
$wp_load = dirname(__FILE__) . '/../../../wp-load.php';
if (file_exists($wp_load)) {
    require_once($wp_load);
} else {
    die('WordPress environment not found');
}

echo "<h1>🔐 Authentication & Permissions Debug</h1>";

// Test 1: Check if WordPress is loaded
echo "<h2>1. WordPress Environment</h2>";
echo "<strong>WordPress loaded:</strong> " . (function_exists('get_current_user_id') ? "✅ YES" : "❌ NO") . "<br>";
echo "<strong>ABSPATH defined:</strong> " . (defined('ABSPATH') ? "✅ YES" : "❌ NO") . "<br>";
echo "<strong>WP_DEBUG:</strong> " . (defined('WP_DEBUG') && WP_DEBUG ? "✅ ENABLED" : "❌ DISABLED") . "<br>";

// Test 2: Check current user
echo "<h2>2. Current User Status</h2>";
if (function_exists('get_current_user_id')) {
    $user_id = get_current_user_id();
    echo "<strong>User ID:</strong> " . ($user_id > 0 ? $user_id : "❌ NOT LOGGED IN") . "<br>";
    
    if ($user_id > 0) {
        $user = wp_get_current_user();
        echo "<strong>User Login:</strong> " . $user->user_login . "<br>";
        echo "<strong>User Email:</strong> " . $user->user_email . "<br>";
        echo "<strong>User Roles:</strong> " . implode(', ', $user->roles) . "<br>";
        echo "<strong>User Capabilities:</strong> " . implode(', ', array_keys($user->allcaps)) . "<br>";
    } else {
        echo "❌ No user is currently logged in<br>";
        echo "<p><strong>Solution:</strong> You need to log in as an administrator first.</p>";
    }
} else {
    echo "❌ WordPress user functions not available<br>";
}

// Test 3: Check specific capabilities
echo "<h2>3. Capability Tests</h2>";
$capabilities_to_test = [
    'manage_options' => 'Manage Options (Admin)',
    'administrator' => 'Administrator Role',
    'edit_posts' => 'Edit Posts',
    'read' => 'Read Posts'
];

foreach ($capabilities_to_test as $cap => $description) {
    $has_cap = current_user_can($cap);
    echo "<strong>$description ($cap):</strong> " . ($has_cap ? "✅ YES" : "❌ NO") . "<br>";
}

// Test 4: Check if user is admin
echo "<h2>4. Administrator Check</h2>";
if (function_exists('current_user_can')) {
    $is_admin = current_user_can('manage_options');
    echo "<strong>Can manage_options:</strong> " . ($is_admin ? "✅ YES" : "❌ NO") . "<br>";
    
    if (!$is_admin) {
        echo "<p style='color: red;'><strong>❌ PROBLEM IDENTIFIED:</strong> Current user does not have administrator privileges.</p>";
        echo "<p><strong>Solutions:</strong></p>";
        echo "<ul>";
        echo "<li>Log in as an administrator user</li>";
        echo "<li>Check if your user has the 'administrator' role</li>";
        echo "<li>Verify that the 'manage_options' capability is assigned to your role</li>";
        echo "</ul>";
    }
} else {
    echo "❌ WordPress capability functions not available<br>";
}

// Test 5: Check plugin activation
echo "<h2>5. Plugin Status</h2>";
if (function_exists('is_plugin_active')) {
    $plugin_file = 'bil24-connector/bil24-connector.php';
    $is_active = is_plugin_active($plugin_file);
    echo "<strong>Plugin active:</strong> " . ($is_active ? "✅ YES" : "❌ NO") . "<br>";
    
    if (!$is_active) {
        echo "<p style='color: red;'><strong>❌ PROBLEM IDENTIFIED:</strong> Plugin is not activated.</p>";
        echo "<p><strong>Solution:</strong> Activate the plugin in WordPress admin → Plugins</p>";
    }
} else {
    echo "❌ Plugin functions not available<br>";
}

// Test 6: Check if we can access admin area
echo "<h2>6. Admin Area Access</h2>";
if (function_exists('is_admin')) {
    $in_admin = is_admin();
    echo "<strong>In admin area:</strong> " . ($in_admin ? "✅ YES" : "❌ NO") . "<br>";
} else {
    echo "❌ Admin functions not available<br>";
}

// Test 7: Check if settings page is registered
echo "<h2>7. Settings Page Registration</h2>";
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
        echo "<p><strong>Possible causes:</strong></p>";
        echo "<ul>";
        echo "<li>Plugin not activated</li>";
        echo "<li>SettingsPage class not loaded</li>";
        echo "<li>Hook registration failed</li>";
        echo "</ul>";
    }
} else {
    echo "❌ Options submenu not available<br>";
}

// Test 8: Manual settings page test
echo "<h2>8. Manual Settings Page Test</h2>";
if (current_user_can('manage_options')) {
    echo "<p><strong>✅ User has proper permissions</strong></p>";
    
    // Try to load and test SettingsPage manually
    $includes_dir = __DIR__ . '/includes/';
    $settings_file = $includes_dir . 'Admin/SettingsPage.php';
    
    if (file_exists($settings_file)) {
        if (!class_exists('\\Bil24\\Admin\\SettingsPage')) {
            require_once($settings_file);
        }
        
        if (class_exists('\\Bil24\\Admin\\SettingsPage')) {
            try {
                $settings_page = new \Bil24\Admin\SettingsPage();
                echo "✅ SettingsPage class loaded successfully<br>";
                
                // Test if render_page method exists
                if (method_exists($settings_page, 'render_page')) {
                    echo "✅ render_page method exists<br>";
                    
                    // Test if we can call it
                    ob_start();
                    $settings_page->render_page();
                    $output = ob_get_clean();
                    
                    if (!empty($output)) {
                        echo "✅ Settings page renders successfully<br>";
                    } else {
                        echo "❌ Settings page renders empty output<br>";
                    }
                } else {
                    echo "❌ render_page method not found<br>";
                }
                
            } catch (Exception $e) {
                echo "❌ Error testing SettingsPage: " . $e->getMessage() . "<br>";
            }
        } else {
            echo "❌ SettingsPage class could not be loaded<br>";
        }
    } else {
        echo "❌ SettingsPage.php file not found<br>";
    }
} else {
    echo "<p style='color: red;'><strong>❌ CRITICAL PROBLEM:</strong> User does not have 'manage_options' capability.</p>";
    echo "<p><strong>This is why you're getting 'Sorry, you are not allowed to access this page'</strong></p>";
}

// Test 9: Provide solutions
echo "<h2>9. Solutions</h2>";
echo "<div style='background: #f0f6fc; padding: 15px; border-left: 4px solid #0073aa;'>";
echo "<h3>🔧 Quick Fixes:</h3>";
echo "<ol>";
echo "<li><strong>Log in as administrator:</strong> Make sure you're logged in with an administrator account</li>";
echo "<li><strong>Check user role:</strong> Go to Users → Your Profile and verify you have 'Administrator' role</li>";
echo "<li><strong>Activate plugin:</strong> Go to Plugins and activate Bil24 Connector if not active</li>";
echo "<li><strong>Clear cache:</strong> Clear any caching plugins or browser cache</li>";
echo "<li><strong>Try emergency settings:</strong> <a href='emergency-settings.php' target='_blank'>emergency-settings.php</a></li>";
echo "</ol>";
echo "</div>";

// Test 10: Direct access test
echo "<h2>10. Direct Access Test</h2>";
$settings_url = admin_url('options-general.php?page=bil24-connector');
echo "<p><strong>Settings URL:</strong> <a href='$settings_url' target='_blank'>$settings_url</a></p>";

if (current_user_can('manage_options')) {
    echo "<p>✅ You should be able to access the settings page</p>";
} else {
    echo "<p style='color: red;'>❌ You cannot access the settings page - insufficient permissions</p>";
}

echo "<h2>✅ Debug Complete</h2>";
echo "<p><a href='" . admin_url('plugins.php') . "' target='_blank'>Go to Plugins</a> | ";
echo "<a href='" . admin_url('users.php') . "' target='_blank'>Go to Users</a> | ";
echo "<a href='emergency-settings.php' target='_blank'>Emergency Settings</a></p>"; 