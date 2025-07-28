<?php
/**
 * Bootstrap file for PHPUnit tests
 *
 * @package Bil24_Connector
 */

// Ensure we're running in the test environment
define('WP_ENV', 'testing');

// Plugin root directory
define('BIL24_PLUGIN_ROOT', dirname(__DIR__));
define('BIL24_TESTS_ROOT', __DIR__);

// Load Composer autoloader
require_once BIL24_PLUGIN_ROOT . '/vendor/autoload.php';

// Load WordPress test functions if available
$_tests_dir = getenv('WP_TESTS_DIR');

if (!$_tests_dir) {
    $_tests_dir = rtrim(sys_get_temp_dir(), '/\\') . '/wordpress-tests-lib';
}

// WordPress test environment (optional - can be used for integration tests)
if (file_exists($_tests_dir . '/includes/functions.php')) {
    require_once $_tests_dir . '/includes/functions.php';
    
    /**
     * Manually load the plugin being tested.
     */
    function _manually_load_plugin() {
        require BIL24_PLUGIN_ROOT . '/bil24-connector.php';
    }
    tests_add_filter('muplugins_loaded', '_manually_load_plugin');
    
    require $_tests_dir . '/includes/bootstrap.php';
} else {
    // Fallback for unit tests without WordPress
    echo "WordPress test environment not found. Running unit tests only.\n";
    
    // Define WordPress constants for compatibility
    if (!defined('WPINC')) {
        define('WPINC', 'wp-includes');
    }
    
    if (!defined('WP_CONTENT_DIR')) {
        define('WP_CONTENT_DIR', '/tmp/wp-content');
    }
    
    if (!defined('WP_PLUGIN_DIR')) {
        define('WP_PLUGIN_DIR', WP_CONTENT_DIR . '/plugins');
    }
    
    // Mock WordPress functions for unit testing
    if (!function_exists('__')) {
        function __($text, $domain = 'default') {
            return $text;
        }
    }
    
    if (!function_exists('esc_html')) {
        function esc_html($text) {
            return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
        }
    }
    
    if (!function_exists('esc_attr')) {
        function esc_attr($text) {
            return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
        }
    }
    
    if (!function_exists('wp_kses_post')) {
        function wp_kses_post($text) {
            return $text;
        }
    }
    
    if (!function_exists('add_action')) {
        function add_action($hook, $callback, $priority = 10, $accepted_args = 1) {
            // Mock implementation
        }
    }
    
    if (!function_exists('add_filter')) {
        function add_filter($hook, $callback, $priority = 10, $accepted_args = 1) {
            // Mock implementation
        }
    }
    
    if (!function_exists('is_admin')) {
        function is_admin() {
            return false;
        }
    }
    
    if (!function_exists('plugin_dir_url')) {
        function plugin_dir_url($file) {
            return 'http://example.org/wp-content/plugins/bil24-connector/';
        }
    }
    
    if (!function_exists('plugin_dir_path')) {
        function plugin_dir_path($file) {
            return BIL24_PLUGIN_ROOT . '/';
        }
    }
}

// Load plugin constants and base classes
require_once BIL24_PLUGIN_ROOT . '/includes/Constants.php';

// Initialize constants with test values if not already defined
if (!defined('BIL24_PLUGIN_DIR')) {
    define('BIL24_PLUGIN_DIR', BIL24_PLUGIN_ROOT . '/');
}

if (!defined('BIL24_PLUGIN_URL')) {
    define('BIL24_PLUGIN_URL', 'http://example.org/wp-content/plugins/bil24-connector/');
}

if (!defined('BIL24_VERSION')) {
    define('BIL24_VERSION', '1.0.0');
}

echo "Bootstrap completed. Running tests...\n"; 