<?php
/**
 * Debug Plugin Activation Errors
 * This script helps identify what's causing the fatal error during activation
 */

// Load WordPress environment
$wp_load = dirname(__FILE__) . '/../../../wp-load.php';
if (file_exists($wp_load)) {
    require_once($wp_load);
} else {
    die('WordPress environment not found');
}

echo "<h1>🔍 Debug Plugin Activation Errors</h1>";

// Test 1: Check if main plugin file exists
echo "<h2>1. Plugin File Check</h2>";
$plugin_file = __DIR__ . '/bil24-connector.php';
if (file_exists($plugin_file)) {
    echo "<p>✅ Main plugin file exists</p>";
    
    // Check file permissions
    $perms = fileperms($plugin_file);
    echo "<p><strong>File permissions:</strong> " . substr(sprintf('%o', $perms), -4) . "</p>";
    
    // Check if file is readable
    if (is_readable($plugin_file)) {
        echo "<p>✅ File is readable</p>";
    } else {
        echo "<p>❌ File is not readable</p>";
    }
} else {
    echo "<p>❌ Main plugin file not found</p>";
    exit;
}

// Test 2: Check if autoloader exists
echo "<h2>2. Autoloader Check</h2>";
$autoloader = __DIR__ . '/vendor/autoload.php';
if (file_exists($autoloader)) {
    echo "<p>✅ Composer autoloader exists</p>";
    
    // Try to include autoloader
    try {
        require_once($autoloader);
        echo "<p>✅ Autoloader loaded successfully</p>";
    } catch (Exception $e) {
        echo "<p>❌ Autoloader error: " . $e->getMessage() . "</p>";
    }
} else {
    echo "<p>❌ Composer autoloader not found</p>";
}

// Test 3: Check if Constants class exists
echo "<h2>3. Constants Class Check</h2>";
if (class_exists('\\Bil24\\Constants')) {
    echo "<p>✅ Constants class exists</p>";
    
    // Check if constants are defined
    $constants_to_check = ['OPTION_SETTINGS', 'TEXT_DOMAIN', 'PLUGIN_BASENAME'];
    foreach ($constants_to_check as $const) {
        if (defined('\\Bil24\\Constants::' . $const)) {
            echo "<p>✅ Constant $const is defined</p>";
        } else {
            echo "<p>❌ Constant $const is not defined</p>";
        }
    }
} else {
    echo "<p>❌ Constants class not found</p>";
    
    // Try to load it manually
    $constants_file = __DIR__ . '/includes/Constants.php';
    if (file_exists($constants_file)) {
        echo "<p>✅ Constants.php file exists, trying to load manually</p>";
        try {
            require_once($constants_file);
            if (class_exists('\\Bil24\\Constants')) {
                echo "<p>✅ Constants class loaded manually</p>";
            } else {
                echo "<p>❌ Constants class still not found after manual load</p>";
            }
        } catch (Exception $e) {
            echo "<p>❌ Error loading Constants.php: " . $e->getMessage() . "</p>";
        }
    } else {
        echo "<p>❌ Constants.php file not found</p>";
    }
}

// Test 4: Check if Plugin class exists
echo "<h2>4. Plugin Class Check</h2>";
if (class_exists('\\Bil24\\Plugin')) {
    echo "<p>✅ Plugin class exists</p>";
    
    // Try to instantiate it
    try {
        $plugin = \Bil24\Plugin::get_instance();
        echo "<p>✅ Plugin instance created successfully</p>";
    } catch (Exception $e) {
        echo "<p>❌ Error creating plugin instance: " . $e->getMessage() . "</p>";
    }
} else {
    echo "<p>❌ Plugin class not found</p>";
    
    // Try to load it manually
    $plugin_file = __DIR__ . '/includes/Plugin.php';
    if (file_exists($plugin_file)) {
        echo "<p>✅ Plugin.php file exists, trying to load manually</p>";
        try {
            require_once($plugin_file);
            if (class_exists('\\Bil24\\Plugin')) {
                echo "<p>✅ Plugin class loaded manually</p>";
            } else {
                echo "<p>❌ Plugin class still not found after manual load</p>";
            }
        } catch (Exception $e) {
            echo "<p>❌ Error loading Plugin.php: " . $e->getMessage() . "</p>";
        }
    } else {
        echo "<p>❌ Plugin.php file not found</p>";
    }
}

// Test 5: Check if SettingsPage class exists
echo "<h2>5. SettingsPage Class Check</h2>";
if (class_exists('\\Bil24\\Admin\\SettingsPage')) {
    echo "<p>✅ SettingsPage class exists</p>";
    
    // Try to instantiate it
    try {
        $settings_page = new \Bil24\Admin\SettingsPage();
        echo "<p>✅ SettingsPage instance created successfully</p>";
    } catch (Exception $e) {
        echo "<p>❌ Error creating SettingsPage instance: " . $e->getMessage() . "</p>";
    }
} else {
    echo "<p>❌ SettingsPage class not found</p>";
    
    // Try to load it manually
    $settings_file = __DIR__ . '/includes/Admin/SettingsPage.php';
    if (file_exists($settings_file)) {
        echo "<p>✅ SettingsPage.php file exists, trying to load manually</p>";
        try {
            require_once($settings_file);
            if (class_exists('\\Bil24\\Admin\\SettingsPage')) {
                echo "<p>✅ SettingsPage class loaded manually</p>";
            } else {
                echo "<p>❌ SettingsPage class still not found after manual load</p>";
            }
        } catch (Exception $e) {
            echo "<p>❌ Error loading SettingsPage.php: " . $e->getMessage() . "</p>";
        }
    } else {
        echo "<p>❌ SettingsPage.php file not found</p>";
    }
}

// Test 6: Check PHP syntax errors
echo "<h2>6. PHP Syntax Check</h2>";
$files_to_check = [
    'bil24-connector.php' => 'Main plugin file',
    'includes/Constants.php' => 'Constants class',
    'includes/Plugin.php' => 'Plugin class',
    'includes/Admin/SettingsPage.php' => 'SettingsPage class',
    'includes/Utils.php' => 'Utils class'
];

foreach ($files_to_check as $file => $description) {
    $full_path = __DIR__ . '/' . $file;
    if (file_exists($full_path)) {
        $output = [];
        $return_var = 0;
        exec("php -l " . escapeshellarg($full_path) . " 2>&1", $output, $return_var);
        
        if ($return_var === 0) {
            echo "<p>✅ $description: No syntax errors</p>";
        } else {
            echo "<p>❌ $description: Syntax errors found</p>";
            echo "<pre>" . implode("\n", $output) . "</pre>";
        }
    } else {
        echo "<p>⚠️ $description: File not found</p>";
    }
}

// Test 7: Check WordPress compatibility
echo "<h2>7. WordPress Compatibility</h2>";
if (function_exists('add_action')) {
    echo "<p>✅ WordPress functions available</p>";
} else {
    echo "<p>❌ WordPress functions not available</p>";
}

if (defined('ABSPATH')) {
    echo "<p>✅ WordPress constants available</p>";
} else {
    echo "<p>❌ WordPress constants not available</p>";
}

// Test 8: Check for missing dependencies
echo "<h2>8. Dependencies Check</h2>";
$required_functions = [
    'add_action' => 'WordPress add_action',
    'add_filter' => 'WordPress add_filter',
    'register_activation_hook' => 'WordPress register_activation_hook',
    'register_deactivation_hook' => 'WordPress register_deactivation_hook',
    'add_options_page' => 'WordPress add_options_page',
    'register_setting' => 'WordPress register_setting',
    'add_settings_section' => 'WordPress add_settings_section',
    'add_settings_field' => 'WordPress add_settings_field'
];

foreach ($required_functions as $func => $description) {
    if (function_exists($func)) {
        echo "<p>✅ $description function available</p>";
    } else {
        echo "<p>❌ $description function not available</p>";
    }
}

echo "<h2>✅ Debug Complete</h2>";
echo "<p>Check the results above to identify the cause of the fatal error.</p>";
echo "<p>Most common causes:</p>";
echo "<ul>";
echo "<li>PHP syntax errors in plugin files</li>";
echo "<li>Missing required classes or functions</li>";
echo "<li>Incorrect namespace usage</li>";
echo "<li>Missing WordPress dependencies</li>";
echo "<li>File permission issues</li>";
echo "</ul>"; 