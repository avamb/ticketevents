<?php
namespace Bil24;

defined( 'ABSPATH' ) || exit;

final class Utils {
    
    /**
     * Get plugin asset URL
     */
    public static function get_asset_url( string $path ): string {
        return plugins_url( 'assets/' . ltrim( $path, '/' ), Constants::PLUGIN_FILE );
    }
    
    /**
     * Get plugin asset path
     */
    public static function get_asset_path( string $path ): string {
        return Constants::PLUGIN_DIR . 'assets/' . ltrim( $path, '/' );
    }
    
    /**
     * Check if WooCommerce is active
     */
    public static function is_woocommerce_active(): bool {
        return class_exists( 'WooCommerce' );
    }
    
    /**
     * Log message to WordPress debug log
     */
    public static function log( string $message, string $level = 'info' ): void {
        if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
            error_log( sprintf( '[Bil24] [%s] %s', strtoupper( $level ), $message ) );
        }
    }
    
    /**
     * Sanitize API response
     */
    public static function sanitize_api_response( $response ) {
        if ( is_array( $response ) ) {
            return array_map( [ self::class, 'sanitize_api_response' ], $response );
        }
        
        if ( is_string( $response ) ) {
            return sanitize_text_field( $response );
        }
        
        return $response;
    }
    
    private function __construct() {}
} 