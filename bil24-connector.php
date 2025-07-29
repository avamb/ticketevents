<?php

/**
 * Plugin Name: Bil24 Connector
 * Plugin URI: https://github.com/yourname/bil24-connector
 * Description: Bil24 ⇄ WordPress/WooCommerce integration plugin. Synchronizes events, sessions, and orders between Bil24 platform and WordPress using robust API client with authentication, caching, and error handling.
 * Version: 0.1.3
 * Requires at least: 6.2
 * Tested up to: 6.4
 * Requires PHP: 8.0
 * Author: Your Team
 * Author URI: https://yourwebsite.com
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: bil24
 * Domain Path: /languages
 * Network: false
 * Update URI: false
 *
 * @package Bil24Connector
 * @since 0.1.0
 */

// Exit if accessed directly
defined('ABSPATH') || exit;

// Define plugin constants
define('BIL24_CONNECTOR_VERSION', '0.1.3');
define('BIL24_CONNECTOR_PLUGIN_FILE', __FILE__);
define('BIL24_CONNECTOR_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('BIL24_CONNECTOR_PLUGIN_URL', plugin_dir_url(__FILE__));
define('BIL24_CONNECTOR_PLUGIN_BASENAME', plugin_basename(__FILE__));

// Добавим отладочную информацию о путях
if (defined('WP_DEBUG') && WP_DEBUG) {
    error_log('[Bil24] Plugin constants:');
    error_log('  - PLUGIN_DIR: ' . BIL24_CONNECTOR_PLUGIN_DIR);
    error_log('  - PLUGIN_BASENAME: ' . BIL24_CONNECTOR_PLUGIN_BASENAME);
    error_log('  - Plugin folder name: ' . basename(BIL24_CONNECTOR_PLUGIN_DIR));
}

// Load plugin text domain
add_action('plugins_loaded', 'bil24_connector_load_textdomain');

/**
 * Load plugin text domain for internationalization
 */
function bil24_connector_load_textdomain()
{
    load_plugin_textdomain('bil24', false, dirname(plugin_basename(__FILE__)) . '/languages');
}

// Check minimum requirements
if (! bil24_connector_check_requirements()) {
    return;
}

/**
 * Check if the server meets minimum requirements
 */
function bil24_connector_check_requirements(): bool
{
    global $wp_version;
    
    $requirements = [
        'php_version' => '8.0',
        'wp_version' => '6.2',
    ];
    
    $errors = [];
    
    // Check PHP version
    if (version_compare(PHP_VERSION, $requirements['php_version'], '<')) {
        $errors[] = sprintf(
            __('Bil24 Connector requires PHP %s or higher. You are running %s.', 'bil24'),
            $requirements['php_version'],
            PHP_VERSION
        );
    }
    
    // Check WordPress version
    if (version_compare($wp_version, $requirements['wp_version'], '<')) {
        $errors[] = sprintf(
            __('Bil24 Connector requires WordPress %s or higher. You are running %s.', 'bil24'),
            $requirements['wp_version'],
            $wp_version
        );
    }
    
    if (! empty($errors)) {
        add_action('admin_notices', function () use ($errors) {
            foreach ($errors as $error) {
                echo '<div class="notice notice-error"><p>' . esc_html($error) . '</p></div>';
            }
        });
        return false;
    }
    
    return true;
}

// Load Composer autoloader
$autoloader = BIL24_CONNECTOR_PLUGIN_DIR . 'vendor/autoload.php';
if (file_exists($autoloader)) {
    require_once $autoloader;
} else {
    add_action('admin_notices', function () {
        echo '<div class="notice notice-error"><p>';
        echo esc_html__('Bil24 Connector: Composer autoloader not found. Please run "composer install" in the plugin directory.', 'bil24');
        echo '</p></div>';
    });
    return;
}

// Fallback class loading if autoloader doesn't work
if (!class_exists('\\Bil24\\Plugin')) {
    // Try to load classes manually
    $includes_dir = BIL24_CONNECTOR_PLUGIN_DIR . 'includes/';
    
    // Use a case-insensitive file lookup because some hosting providers
    // (especially those running on Linux) treat path case strictly, while
    // Windows development environments do not. This often leads to
    // situations where the folder is called "admin" on the server but
    // "Admin" locally (or vice-versa). We therefore try both the original
    // path and a lower-case variant.

    $required_files = [
        'Constants.php'           => '\\Bil24\\Constants',
        'Utils.php'               => '\\Bil24\\Utils',
        'Api/Client.php'          => '\\Bil24\\Api\\Client',
        'Admin/SettingsPage.php'  => '\\Bil24\\Admin\\SettingsPage',
        // fallback lower-case path – will be skipped if the canonical one exists
        'admin/SettingsPage.php'  => '\\Bil24\\Admin\\SettingsPage',
        'Plugin.php'              => '\\Bil24\\Plugin',
    ];
    
    foreach ($required_files as $file => $class) {
        $full_path = $includes_dir . $file;

        // If the exact path doesn't exist, try a case-insensitive glob
        if (! file_exists($full_path)) {
            $glob = glob($includes_dir . str_ireplace('Admin', '*', $file));
            if ($glob) {
                $full_path = reset($glob);
            }
        }

        if (file_exists($full_path) && ! class_exists($class)) {
            require_once $full_path;
        }
    }
    
    // If still not loaded, show error
    if (!class_exists('\\Bil24\\Plugin')) {
        add_action('admin_notices', function () {
            echo '<div class="notice notice-error"><p>';
            echo esc_html__('Bil24 Connector: Plugin classes could not be loaded. Please check plugin installation.', 'bil24');
            echo '</p></div>';
        });
        return;
    }
}

// Fire up the plugin core
if (class_exists('\\Bil24\\Plugin')) {
    add_action('plugins_loaded', function() {
        // Отладочная информация
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('[Bil24] Initializing plugin on plugins_loaded hook');
        }
        
        try {
            $plugin_instance = \Bil24\Plugin::instance();
            
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('[Bil24] Plugin instance created successfully');
            }
        } catch (Exception $e) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('[Bil24] Plugin initialization failed: ' . $e->getMessage());
            }
            
            add_action('admin_notices', function() use ($e) {
                echo '<div class="notice notice-error"><p>';
                echo '<strong>Bil24 Connector:</strong> Failed to initialize - ' . esc_html($e->getMessage());
                echo '</p></div>';
            });
        }
    });
} else {
    // Более подробное сообщение об ошибке
    add_action('admin_notices', function () {
        echo '<div class="notice notice-error"><p>';
        echo '<strong>Bil24 Connector:</strong> Plugin class not found. ';
        
        // Дополнительная диагностика
        if (!file_exists(BIL24_CONNECTOR_PLUGIN_DIR . 'vendor/autoload.php')) {
            echo 'Composer autoloader missing - run "composer install". ';
        }
        
        if (!file_exists(BIL24_CONNECTOR_PLUGIN_DIR . 'includes/Plugin.php')) {
            echo 'Plugin.php file missing. ';
        }
        
        echo 'Please check plugin installation.';
        echo '</p></div>';
    });
    
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log('[Bil24] Plugin class not found - checking files:');
        error_log('  - Plugin.php exists: ' . (file_exists(BIL24_CONNECTOR_PLUGIN_DIR . 'includes/Plugin.php') ? 'YES' : 'NO'));
        error_log('  - Autoloader exists: ' . (file_exists(BIL24_CONNECTOR_PLUGIN_DIR . 'vendor/autoload.php') ? 'YES' : 'NO'));
        error_log('  - Class exists: ' . (class_exists('\\Bil24\\Plugin') ? 'YES' : 'NO'));
    }
    
    return;
}

// Register activation and deactivation hooks
register_activation_hook(__FILE__, [ '\\Bil24\\Plugin', 'activate' ]);
register_deactivation_hook(__FILE__, [ '\\Bil24\\Plugin', 'deactivate' ]);

// Register uninstall hook (if needed)
// register_uninstall_hook( __FILE__, [ '\\Bil24\\Plugin', 'uninstall' ] );
