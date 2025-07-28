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
     * Get recent log entries
     * 
     * @param int $limit Number of log entries to return
     * @return array Array of log entries
     */
    public static function get_recent_logs( int $limit = 50 ): array {
        // Check if WP_DEBUG_LOG is enabled and log file exists
        if ( ! defined( 'WP_DEBUG_LOG' ) || ! WP_DEBUG_LOG ) {
            return [];
        }
        
        $log_file = WP_CONTENT_DIR . '/debug.log';
        
        if ( ! file_exists( $log_file ) || ! is_readable( $log_file ) ) {
            return [];
        }
        
        try {
            $logs = [];
            $file = new \SplFileObject( $log_file );
            $file->seek( PHP_INT_MAX );
            $total_lines = $file->key();
            
            // Start from end of file and work backwards
            $start_line = max( 0, $total_lines - ( $limit * 3 ) ); // Read more lines to filter for Bil24
            $file->seek( $start_line );
            
            $bil24_logs = [];
            while ( ! $file->eof() && count( $bil24_logs ) < $limit ) {
                $line = trim( $file->fgets() );
                
                // Filter for Bil24 log entries
                if ( strpos( $line, '[Bil24]' ) !== false ) {
                    // Parse log entry
                    $parsed = self::parse_log_entry( $line );
                    if ( $parsed ) {
                        $bil24_logs[] = $parsed;
                    }
                }
            }
            
            // Return most recent entries first
            return array_reverse( array_slice( $bil24_logs, -$limit ) );
            
        } catch ( \Exception $e ) {
            return [];
        }
    }
    
    /**
     * Parse a log entry line
     * 
     * @param string $line Log line to parse
     * @return array|null Parsed log entry or null if invalid
     */
    private static function parse_log_entry( string $line ): ?array {
        // Expected format: [DD-Mon-YYYY HH:MM:SS UTC] [Bil24] [LEVEL] Message
        $pattern = '/^\[([^\]]+)\].*\[Bil24\]\s*\[([^\]]+)\]\s*(.+)$/';
        
        if ( preg_match( $pattern, $line, $matches ) ) {
            return [
                'timestamp' => $matches[1],
                'level' => strtolower( trim( $matches[2] ) ),
                'message' => trim( $matches[3] )
            ];
        }
        
        return null;
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