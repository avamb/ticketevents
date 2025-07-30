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
        $this->settings = get_option( Constants::OPTION_SETTINGS, [
            'fid' => '',
            'token' => '',
            'env' => 'test'
        ] );
        
        // Получаем timeout из настроек или используем дефолтный
        $timeout = $this->settings['api_timeout'] ?? Constants::API_TIMEOUT;
        
        $this->http_args = [
            'timeout' => $timeout,
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
            $error_message = $response->get_error_message();
            $error_code = $response->get_error_code();
            
            Utils::log( "WP Error - Code: {$error_code}, Message: {$error_message}", Constants::LOG_LEVEL_ERROR );
            
            // Более информативные сообщения об ошибках
            if ($error_code === 'http_request_failed') {
                throw new \Exception( __('Network connection failed. Please check your internet connection or API server status.', 'bil24') );
            } elseif ($error_code === 'connect_timeout') {
                throw new \Exception( __('Connection timeout. The API server may be unavailable.', 'bil24') );
            } else {
                throw new \Exception( "Network error: {$error_message}" );
            }
        }

        $status_code = wp_remote_retrieve_response_code( $response );
        $body = wp_remote_retrieve_body( $response );
        
        // Логируем raw response для отладки
        Utils::log( "API Response - Status: {$status_code}, Body length: " . strlen($body), Constants::LOG_LEVEL_DEBUG );
        
        if (empty($body)) {
            Utils::log( 'Empty response body received', Constants::LOG_LEVEL_WARNING );
            throw new \Exception( 'Empty response from API server' );
        }
        
        $data = json_decode( $body, true );

        if ( json_last_error() !== JSON_ERROR_NONE ) {
            $json_error = json_last_error_msg();
            Utils::log( "JSON decode error: {$json_error}. Raw body: " . substr($body, 0, 500), Constants::LOG_LEVEL_ERROR );
            throw new \Exception( "Invalid JSON response from API: {$json_error}" );
        }

        if ( $status_code >= 400 ) {
            // Более детальная обработка ошибок
            $error_message = $data['message'] ?? $data['error'] ?? 'Unknown API error';
            $error_details = $data['details'] ?? '';
            
            $full_error = "API error ({$status_code}): {$error_message}";
            if ($error_details) {
                $full_error .= " - Details: {$error_details}";
            }
            
            Utils::log( $full_error, Constants::LOG_LEVEL_ERROR );
            
            // Специфические коды ошибок
            if ($status_code === 401) {
                throw new \Exception( __('Authentication failed. Please check your FID and Token credentials.', 'bil24') );
            } elseif ($status_code === 403) {
                throw new \Exception( __('Access denied. Your credentials may not have sufficient permissions.', 'bil24') );
            } elseif ($status_code === 404) {
                throw new \Exception( __('API endpoint not found. Please check the API documentation.', 'bil24') );
            } elseif ($status_code >= 500) {
                throw new \Exception( __('API server error. Please try again later or contact support.', 'bil24') );
            } else {
                throw new \Exception( $full_error );
            }
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
            // Проверяем настройки перед тестированием
            if (!$this->is_configured()) {
                Utils::log('Connection test failed: API credentials not configured', Constants::LOG_LEVEL_ERROR);
                throw new \Exception(__('API credentials (FID and Token) are required', 'bil24'));
            }
            
            // Пробуем несколько endpoints для тестирования подключения
            $test_endpoints = [
                '/status',      // Основной status endpoint
                '/version',     // Version endpoint как альтернатива
                '/events',      // Events endpoint с ограничением
            ];
            
            $last_error = null;
            
            foreach ($test_endpoints as $endpoint) {
                try {
                    Utils::log("Testing connection with endpoint: {$endpoint}", Constants::LOG_LEVEL_DEBUG);
                    
                    if ($endpoint === '/events') {
                        // Для events добавляем лимит чтобы минимизировать нагрузку
                        $response = $this->get($endpoint, ['limit' => 1]);
                    } else {
                        $response = $this->get($endpoint);
                    }
                    
                    // Если дошли до сюда без исключения - подключение работает
                    Utils::log("Connection test successful with endpoint: {$endpoint}", Constants::LOG_LEVEL_INFO);
                    return true;
                    
                } catch (\Exception $e) {
                    $last_error = $e;
                    Utils::log("Endpoint {$endpoint} failed: " . $e->getMessage(), Constants::LOG_LEVEL_DEBUG);
                    continue;
                }
            }
            
            // Если все endpoints failed
            if ($last_error) {
                throw $last_error;
            }
            
            return false;
            
        } catch ( \Exception $e ) {
            $error_msg = 'Connection test failed: ' . $e->getMessage();
            Utils::log($error_msg, Constants::LOG_LEVEL_ERROR);
            
            // Добавляем подробную диагностику
            $config = $this->get_config_status();
            Utils::log('API Configuration: ' . wp_json_encode($config), Constants::LOG_LEVEL_DEBUG);
            
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

    /**
     * Check if API credentials are configured
     */
    public function is_configured(): bool {
        $fid = $this->settings['fid'] ?? '';
        $token = $this->settings['token'] ?? '';
        
        return !empty($fid) && !empty($token);
    }
    
    /**
     * Get configuration status
     */
    public function get_config_status(): array {
        $fid = $this->settings['fid'] ?? '';
        $token = $this->settings['token'] ?? '';
        $env = $this->settings['env'] ?? 'test';
        
        return [
            'configured' => $this->is_configured(),
            'has_fid' => !empty($fid),
            'has_token' => !empty($token),
            'environment' => $env,
            'api_url' => $this->get_api_base_url()
        ];
    }
} 