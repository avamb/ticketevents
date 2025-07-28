<?php

/**
 * Plugin Name: Bil24 Connector
 * Plugin URI: https://github.com/yourname/bil24-connector
 * Description: Bil24 ⇄ WordPress/WooCommerce integration plugin. Synchronizes events, sessions, and orders between Bil24 platform and WordPress using robust API client with authentication, caching, and error handling.
 * Version: 0.1.0
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
define('BIL24_CONNECTOR_VERSION', '0.1.0');
define('BIL24_CONNECTOR_PLUGIN_FILE', __FILE__);
define('BIL24_CONNECTOR_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('BIL24_CONNECTOR_PLUGIN_URL', plugin_dir_url(__FILE__));
define('BIL24_CONNECTOR_PLUGIN_BASENAME', plugin_basename(__FILE__));

// PSR‑4 autoloading via Composer (vendor/autoload.php)
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
}

// Load plugin text domain for internationalization
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

// Fire up the plugin core.
if (class_exists('\\Bil24\\Plugin')) {
    add_action('plugins_loaded', [ '\\Bil24\\Plugin', 'instance' ]);
}

// Register activation and deactivation hooks
register_activation_hook(__FILE__, [ '\\Bil24\\Plugin', 'activate' ]);
register_deactivation_hook(__FILE__, [ '\\Bil24\\Plugin', 'deactivate' ]);

// Register uninstall hook (if needed)
// register_uninstall_hook( __FILE__, [ '\\Bil24\\Plugin', 'uninstall' ] );
