<?php
/**
 * Simple AJAX Test for Bil24 Connection
 * 
 * Direct test without all the complexity - helps isolate the 400 error
 */

// WordPress Bootstrap
if ( ! defined( 'ABSPATH' ) ) {
    $wp_root = dirname( __FILE__ );
    while ( ! file_exists( $wp_root . '/wp-config.php' ) && $wp_root !== '/' ) {
        $wp_root = dirname( $wp_root );
    }
    require_once $wp_root . '/wp-load.php';
}

// Only accessible to admin users
if ( ! current_user_can( 'manage_options' ) ) {
    wp_die( 'Access denied. You need administrator privileges.' );
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Simple Bil24 AJAX Test</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .test-result { margin: 10px 0; padding: 10px; border: 1px solid #ccc; }
        .success { background: #d4edda; border-color: #c3e6cb; }
        .error { background: #f8d7da; border-color: #f5c6cb; }
        .debug { background: #f7f7f7; font-family: monospace; font-size: 12px; }
    </style>
</head>
<body>
    <h1>🧪 Simple Bil24 AJAX Test</h1>
    
    <p>This test bypasses all the complex plugin logic to test AJAX directly.</p>
    
    <button id="test-basic-ajax">Test Basic AJAX</button>
    <button id="test-bil24-ajax">Test Bil24 AJAX</button>
    
    <div id="results"></div>
    
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const results = document.getElementById('results');
        
        // Test 1: Basic WordPress AJAX
        document.getElementById('test-basic-ajax').addEventListener('click', function() {
            results.innerHTML += '<div class="test-result"><strong>Testing Basic AJAX...</strong></div>';
            
            fetch('<?php echo admin_url( "admin-ajax.php" ); ?>', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=heartbeat&_wpnonce=<?php echo wp_create_nonce( "heartbeat-nonce" ); ?>'
            })
            .then(response => {
                results.innerHTML += '<div class="test-result debug">Basic AJAX Response Status: ' + response.status + '</div>';
                return response.text();
            })
            .then(data => {
                results.innerHTML += '<div class="test-result success">Basic AJAX Response: ' + data.substring(0, 200) + '...</div>';
            })
            .catch(error => {
                results.innerHTML += '<div class="test-result error">Basic AJAX Error: ' + error.message + '</div>';
            });
        });
        
        // Test 2: Bil24 AJAX
        document.getElementById('test-bil24-ajax').addEventListener('click', function() {
            results.innerHTML += '<div class="test-result"><strong>Testing Bil24 AJAX...</strong></div>';
            
            fetch('<?php echo admin_url( "admin-ajax.php" ); ?>', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=bil24_test_connection&_ajax_nonce=<?php echo wp_create_nonce( "bil24_test_connection" ); ?>'
            })
            .then(response => {
                results.innerHTML += '<div class="test-result debug">Bil24 AJAX Response Status: ' + response.status + '</div>';
                results.innerHTML += '<div class="test-result debug">Bil24 AJAX Response Headers: ' + JSON.stringify([...response.headers]) + '</div>';
                return response.text();
            })
            .then(data => {
                try {
                    const json = JSON.parse(data);
                    results.innerHTML += '<div class="test-result ' + (json.success ? 'success' : 'error') + '">Bil24 AJAX JSON: ' + JSON.stringify(json, null, 2) + '</div>';
                } catch (e) {
                    results.innerHTML += '<div class="test-result error">Bil24 AJAX Raw Response: ' + data + '</div>';
                }
            })
            .catch(error => {
                results.innerHTML += '<div class="test-result error">Bil24 AJAX Error: ' + error.message + '</div>';
            });
        });
    });
    </script>
    
    <hr>
    <h2>Debug Information</h2>
    <div class="test-result debug">
        <strong>WordPress Version:</strong> <?php echo get_bloginfo( 'version' ); ?><br>
        <strong>PHP Version:</strong> <?php echo PHP_VERSION; ?><br>
        <strong>Current User:</strong> <?php echo wp_get_current_user()->user_login; ?><br>
        <strong>User Capabilities:</strong> <?php echo current_user_can( 'manage_options' ) ? 'Has manage_options' : 'No manage_options'; ?><br>
        <strong>AJAX URL:</strong> <?php echo admin_url( 'admin-ajax.php' ); ?><br>
        <strong>Plugin Loaded:</strong> <?php echo class_exists( '\\Bil24\\Plugin' ) ? 'YES' : 'NO'; ?><br>
        <strong>AJAX Handler Registered:</strong> <?php echo has_action( 'wp_ajax_bil24_test_connection' ) ? 'YES' : 'NO'; ?><br>
        <strong>Debug Mode:</strong> <?php echo defined( 'WP_DEBUG' ) && WP_DEBUG ? 'ENABLED' : 'DISABLED'; ?>
    </div>
</body>
</html>