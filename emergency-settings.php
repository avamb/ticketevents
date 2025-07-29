<?php
/**
 * Emergency Settings Page for Bil24 Connector
 * Access this directly to configure the plugin when the normal settings page fails
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

// Load plugin constants
if (file_exists(__DIR__ . '/includes/Constants.php')) {
    require_once(__DIR__ . '/includes/Constants.php');
}

// Process form submission
if ($_POST && wp_verify_nonce($_POST['bil24_nonce'] ?? '', 'bil24_save_settings')) {
    $settings = [
        'fid' => sanitize_text_field($_POST['bil24_fid'] ?? ''),
        'token' => sanitize_text_field($_POST['bil24_token'] ?? ''),
        'env' => ($_POST['bil24_env'] ?? 'test') === 'prod' ? 'prod' : 'test'
    ];
    
    $option_name = class_exists('\\Bil24\\Constants') ? \Bil24\Constants::OPTION_SETTINGS : 'bil24_settings';
    update_option($option_name, $settings);
    
    echo '<div style="background: #d4edda; border: 1px solid #c3e6cb; color: #155724; padding: 10px; margin: 10px 0; border-radius: 4px;">';
    echo '✅ Settings saved successfully!';
    echo '</div>';
}

// Get current settings
$option_name = class_exists('\\Bil24\\Constants') ? \Bil24\Constants::OPTION_SETTINGS : 'bil24_settings';
$settings = get_option($option_name, ['fid' => '', 'token' => '', 'env' => 'test']);

?>
<!DOCTYPE html>
<html>
<head>
    <title>Bil24 Connector - Emergency Settings</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; margin: 20px; }
        .wrap { max-width: 800px; }
        h1 { color: #23282d; border-bottom: 1px solid #ccd0d4; padding-bottom: 10px; }
        .form-table { width: 100%; }
        .form-table th { text-align: left; padding: 20px 10px 20px 0; width: 200px; }
        .form-table td { padding: 15px 10px; }
        .regular-text { width: 300px; padding: 8px; border: 1px solid #ddd; border-radius: 4px; }
        .button-primary { background: #0073aa; color: white; border: none; padding: 8px 16px; border-radius: 4px; cursor: pointer; }
        .button-primary:hover { background: #005177; }
        .description { color: #646970; font-style: italic; }
        .notice { padding: 12px; margin: 16px 0; border-left: 4px solid #0073aa; background: #f0f6fc; }
        .alert { background: #fff3cd; border-left-color: #ffc107; color: #856404; }
        .error { background: #f8d7da; border-left-color: #dc3545; color: #721c24; }
    </style>
</head>
<body>
    <div class="wrap">
        <h1>🚨 Bil24 Connector - Emergency Settings</h1>
        
        <div class="notice alert">
            <p><strong>Notice:</strong> This is an emergency configuration page. Use this when the normal WordPress settings page is not accessible.</p>
        </div>
        
        <!-- Plugin Status Check -->
        <h2>Plugin Status</h2>
        <table class="form-table">
            <tr>
                <th>Main Plugin File:</th>
                <td><?php echo file_exists(__DIR__ . '/bil24-connector.php') ? '✅ Found' : '❌ Not Found'; ?></td>
            </tr>
            <tr>
                <th>Autoloader:</th>
                <td><?php echo file_exists(__DIR__ . '/vendor/autoload.php') ? '✅ Found' : '❌ Not Found'; ?></td>
            </tr>
            <tr>
                <th>Constants Class:</th>
                <td><?php echo class_exists('\\Bil24\\Constants') ? '✅ Loaded' : '❌ Not Loaded'; ?></td>
            </tr>
            <tr>
                <th>Current User:</th>
                <td><?php echo wp_get_current_user()->user_login; ?> (ID: <?php echo get_current_user_id(); ?>)</td>
            </tr>
            <tr>
                <th>Can Manage Options:</th>
                <td><?php echo current_user_can('manage_options') ? '✅ Yes' : '❌ No'; ?></td>
            </tr>
        </table>
        
        <!-- Settings Form -->
        <h2>Configuration</h2>
        <form method="post" action="">
            <?php wp_nonce_field('bil24_save_settings', 'bil24_nonce'); ?>
            
            <table class="form-table">
                <tr>
                    <th><label for="bil24_fid">FID (Interface ID)</label></th>
                    <td>
                        <input type="text" id="bil24_fid" name="bil24_fid" class="regular-text" 
                               value="<?php echo esc_attr($settings['fid'] ?? ''); ?>" required>
                        <p class="description">Your Bil24 FID (interface identifier)</p>
                    </td>
                </tr>
                <tr>
                    <th><label for="bil24_token">API Token</label></th>
                    <td>
                        <input type="password" id="bil24_token" name="bil24_token" class="regular-text" 
                               value="<?php echo esc_attr($settings['token'] ?? ''); ?>" required>
                        <p class="description">Your Bil24 API token for authentication</p>
                    </td>
                </tr>
                <tr>
                    <th>Environment</th>
                    <td>
                        <label>
                            <input type="radio" name="bil24_env" value="test" 
                                   <?php checked($settings['env'] ?? 'test', 'test'); ?>>
                            Test Environment <span class="description">(api.bil24.pro:1240)</span>
                        </label><br>
                        <label>
                            <input type="radio" name="bil24_env" value="prod" 
                                   <?php checked($settings['env'] ?? 'test', 'prod'); ?>>
                            Production Environment <span class="description">(api.bil24.pro)</span>
                        </label>
                        <p class="description">Select the Bil24 API environment to connect to</p>
                    </td>
                </tr>
            </table>
            
            <p>
                <button type="submit" class="button-primary">Save Settings</button>
            </p>
        </form>
        
        <!-- Test Connection -->
        <h2>Connection Test</h2>
        <p>
            <button type="button" id="test-connection" class="button-primary">Test Connection</button>
            <span id="test-result" style="margin-left: 10px;"></span>
        </p>
        
        <!-- Actions -->
        <h2>Actions</h2>
        <p>
            <a href="<?php echo admin_url('options-general.php?page=bil24-connector'); ?>" class="button-primary">
                Try Normal Settings Page
            </a>
            <a href="<?php echo admin_url('plugins.php'); ?>" class="button-primary" style="margin-left: 10px;">
                Go to Plugins Page
            </a>
        </p>
        
        <!-- Debug Information -->
        <h2>Debug Information</h2>
        <p><strong>Settings Option Name:</strong> <?php echo esc_html($option_name); ?></p>
        <p><strong>Current Settings:</strong></p>
        <pre style="background: #f1f1f1; padding: 10px; border-radius: 4px; overflow: auto;"><?php 
        print_r($settings); 
        ?></pre>
    </div>
    
    <script>
    document.getElementById('test-connection').addEventListener('click', function() {
        const button = this;
        const result = document.getElementById('test-result');
        
        button.disabled = true;
        button.textContent = 'Testing...';
        result.innerHTML = '';
        
        // Simple test - just check if settings are configured
        const fid = document.getElementById('bil24_fid').value;
        const token = document.getElementById('bil24_token').value;
        
        setTimeout(() => {
            if (fid && token) {
                result.innerHTML = '<span style="color: green;">✅ Settings appear to be configured</span>';
            } else {
                result.innerHTML = '<span style="color: red;">❌ FID and Token are required</span>';
            }
            
            button.disabled = false;
            button.textContent = 'Test Connection';
        }, 1000);
    });
    </script>
</body>
</html> 