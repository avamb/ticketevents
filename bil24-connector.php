<?php
/**
 * Plugin Name: Bil24 Connector
 * Description: Bil24 ⇄ WooCommerce integration (skeleton).
 * Version:     0.1.0
 * Requires at least: 6.2
 * Requires PHP: 8.0
 * Author:      Your Team
 * License:     GPL-2.0-or-later
 * Text Domain: bil24
 */

defined( 'ABSPATH' ) || exit;

// PSR‑4 autoloading via Composer (vendor/autoload.php)
if ( file_exists( __DIR__ . '/vendor/autoload.php' ) ) {
    require_once __DIR__ . '/vendor/autoload.php';
}

// Fire up the plugin core.
if ( class_exists( '\\Bil24\\Plugin' ) ) {
    \Bil24\Plugin::instance();
}
