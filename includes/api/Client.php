<?php
namespace Bil24\Api;

use Bil24\Constants;
use Bil24\Utils;

defined( 'ABSPATH' ) || exit;

/**
 * Bil24 API Client
 * 
 * Handles authentication, caching, and error handling for Bil24 API communication
 * 
 * @package Bil24Connector
 * @since 0.1.0
 */
class Client {

    /**
     * API base URLs for different environments
     */
    private const API_BASE_URLS = [
        'test' => 'https://api.bil24.pro:1240',
        'prod' => 'https://api.bil24.pro',
    ];

    /**
     * API credentials and settings
     */
    private array $settings;

    /**
     * WordPress HTTP API args
     */
    private array $http_args;

    /**
     * Constructor
     */
    public function __construct() {
        $this->settings = get_option( 'bil24_settings', [] );
        $this->http_args = [
            'timeout' => Constants::API_TIMEOUT,
            'headers' => [
                'Content-Type' => 'application/json',
                'User-Agent' => 'Bil24-WordPress-Connector/' . Constants::get_version(),
            ],
        ];
    }

    /**
     * Get API base URL based on environment
     */
    private function get_api_base_url(): string {
        $env = $this->settings['env'] ?? 'test';
        return self::API_BASE_URLS[$env] ?? self::API_BASE_URLS['test'];
    }

    /**
     * Build full API URL
     */
    private function build_url( string $endpoint ): string {
        return rtrim( $this->get_api_base_url(), '/' ) . '/' . ltrim( $endpoint, '/' );
    }

    /**
     * Add authentication to request args
     */
    private function add_auth( array $args ): array {
        $fid = $this->settings['fid'] ?? '';
        $token = $this->settings['token'] ?? '';

        if ( empty( $fid ) || empty( $token ) ) {
            throw new \Exception( __( 'API credentials not configured', 'bil24' ) );
        }

        $args['headers']['Authorization'] = 'Bearer ' . $token;
        $args['headers']['X-FID'] = $fid;

        return $args;
    }

    /**
     * Execute API request with retry logic
     */
    private function execute_request( string $method, string $url, array $args ): array {
        $retry_count = 0;
        $max_retries = Constants::API_RETRY_ATTEMPTS;

        do {
            $response = wp_remote_request( $url, array_merge( $args, [ 'method' => $method ] ) );
            
            if ( ! is_wp_error( $response ) ) {
                $status_code = wp_remote_retrieve_response_code( $response );
                
                if ( $status_code >= 200 && $status_code < 300 ) {
                    // Success
                    break;
                } elseif ( $status_code >= 500 && $retry_count < $max_retries ) {
                    // Server error - retry
                    $retry_count++;
                    sleep( pow( 2, $retry_count ) ); // Exponential backoff
                    continue;
                } else {
                    // Client error or max retries reached
                    break;
                }
            } else {
                // Network error
                if ( $retry_count < $max_retries ) {
                    $retry_count++;
                    sleep( pow( 2, $retry_count ) );
                    continue;
                }
                break;
            }
        } while ( $retry_count <= $max_retries );

        return $this->handle_response( $response );
    }

    /**
     * Handle API response
     */
    private function handle_response( $response ): array {
        if ( is_wp_error( $response ) ) {
            Utils::log( 'API request failed: ' . $response->get_error_message(), Constants::LOG_LEVEL_ERROR );
            throw new \Exception( 'API request failed: ' . $response->get_error_message() );
        }

        $status_code = wp_remote_retrieve_response_code( $response );
        $body = wp_remote_retrieve_body( $response );
        $data = json_decode( $body, true );

        if ( json_last_error() !== JSON_ERROR_NONE ) {
            Utils::log( 'Invalid JSON response: ' . $body, Constants::LOG_LEVEL_ERROR );
            throw new \Exception( 'Invalid JSON response from API' );
        }

        if ( $status_code >= 400 ) {
            $error_message = $data['message'] ?? 'Unknown API error';
            Utils::log( "API error {$status_code}: {$error_message}", Constants::LOG_LEVEL_ERROR );
            throw new \Exception( "API error ({$status_code}): {$error_message}" );
        }

        Utils::log( "API request successful: {$status_code}", Constants::LOG_LEVEL_DEBUG );
        return $data;
    }

    /**
     * GET request
     */
    public function get( string $endpoint, array $params = [] ): array {
        $url = $this->build_url( $endpoint );
        
        if ( ! empty( $params ) ) {
            $url .= '?' . http_build_query( $params );
        }

        // Check cache first
        $cache_key = Constants::CACHE_PREFIX . 'get_' . md5( $url );
        $cached = wp_cache_get( $cache_key, Constants::CACHE_GROUP );
        
        if ( $cached !== false ) {
            Utils::log( "Cache hit for: {$endpoint}", Constants::LOG_LEVEL_DEBUG );
            return $cached;
        }

        $args = $this->add_auth( $this->http_args );
        $response = $this->execute_request( 'GET', $url, $args );

        // Cache successful responses
        wp_cache_set( $cache_key, $response, Constants::CACHE_GROUP, Constants::CACHE_EXPIRATION );

        return $response;
    }

    /**
     * POST request
     */
    public function post( string $endpoint, array $data = [] ): array {
        $url = $this->build_url( $endpoint );
        $args = $this->add_auth( $this->http_args );
        
        if ( ! empty( $data ) ) {
            $args['body'] = wp_json_encode( $data );
        }

        return $this->execute_request( 'POST', $url, $args );
    }

    /**
     * PUT request
     */
    public function put( string $endpoint, array $data = [] ): array {
        $url = $this->build_url( $endpoint );
        $args = $this->add_auth( $this->http_args );
        
        if ( ! empty( $data ) ) {
            $args['body'] = wp_json_encode( $data );
        }

        return $this->execute_request( 'PUT', $url, $args );
    }

    /**
     * DELETE request
     */
    public function delete( string $endpoint ): array {
        $url = $this->build_url( $endpoint );
        $args = $this->add_auth( $this->http_args );

        return $this->execute_request( 'DELETE', $url, $args );
    }

    /**
     * Test API connection
     */
    public function test_connection(): bool {
        try {
            $response = $this->get( '/status' );
            return isset( $response['status'] ) && $response['status'] === 'ok';
        } catch ( \Exception $e ) {
            Utils::log( 'Connection test failed: ' . $e->getMessage(), Constants::LOG_LEVEL_ERROR );
            return false;
        }
    }

    /**
     * Clear all API cache
     */
    public function clear_cache(): void {
        wp_cache_flush_group( Constants::CACHE_GROUP );
        Utils::log( 'API cache cleared', Constants::LOG_LEVEL_INFO );
    }
} 