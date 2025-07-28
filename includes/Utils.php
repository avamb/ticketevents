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
    
    /**
     * Sanitize text field (WordPress compatibility)
     */
    public static function sanitize_text_field( string $input ): string {
        if ( function_exists( 'sanitize_text_field' ) ) {
            return sanitize_text_field( $input );
        }
        
        // Fallback for unit tests
        return trim( strip_tags( $input ) );
    }
    
    /**
     * Validate email address
     */
    public static function is_valid_email( string $email ): bool {
        return filter_var( $email, FILTER_VALIDATE_EMAIL ) !== false;
    }
    
    /**
     * Validate URL
     */
    public static function is_valid_url( string $url ): bool {
        return filter_var( $url, FILTER_VALIDATE_URL ) !== false;
    }
    
    /**
     * Get array value with default
     */
    public static function get_array_value( array $array, string $key, $default = null ) {
        return $array[ $key ] ?? $default;
    }
    
    /**
     * Format date for display
     */
    public static function format_date( string $date, string $format = 'Y-m-d' ): string {
        try {
            $dateTime = new \DateTime( $date );
            return $dateTime->format( $format );
        } catch ( \Exception $e ) {
            return $date;
        }
    }
    
    /**
     * Log error message
     */
    public static function log_error( string $message ): bool {
        self::log( $message, 'error' );
        return true;
    }
    
    /**
     * Get plugin version
     */
    public static function get_plugin_version(): string {
        return defined( 'BIL24_VERSION' ) ? BIL24_VERSION : '1.0.0';
    }
    
    private function __construct() {}
} 