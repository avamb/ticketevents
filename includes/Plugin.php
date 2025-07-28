<?php
namespace Bil24;

defined( 'ABSPATH' ) || exit;

/**
 * Main Plugin class for Bil24 Connector
 * 
 * @package Bil24Connector
 * @since 0.1.0
 */
final class Plugin {

    /**
     * Plugin instance
     */
    private static ?Plugin $instance = null;

    /**
     * Plugin initialization flag
     */
    private bool $initialized = false;

    /**
     * Get plugin instance (Singleton pattern)
     */
    public static function instance(): Plugin {
        return self::$instance ??= new self();
    }

    /**
     * Constructor - Private to enforce singleton
     */
    private function __construct() {
        $this->init_hooks();
    }

    /**
     * Initialize plugin hooks
     */
    private function init_hooks(): void {
        // Prevent double initialization
        if ( $this->initialized ) {
            return;
        }

        // WordPress init hook
        add_action( 'init', [ $this, 'init' ] );
        
        // Admin hooks
        if ( is_admin() ) {
            add_action( 'admin_init', [ $this, 'admin_init' ] );
        }

        $this->initialized = true;
    }

    /**
     * Plugin activation hook
     */
    public static function activate(): void {
        // Check requirements
        if ( ! self::check_requirements() ) {
            return;
        }

        // Create database tables if needed
        self::create_database_tables();
        
        // Set default options
        self::set_default_options();
        
        // Schedule cron events
        self::schedule_cron_events();
        
        // Flush rewrite rules
        flush_rewrite_rules();
        
        // Update activation option
        update_option( 'bil24_activated', time() );
        
        Utils::log( 'Plugin activated successfully', Constants::LOG_LEVEL_INFO );
    }

    /**
     * Plugin deactivation hook
     */
    public static function deactivate(): void {
        // Clear scheduled cron events
        self::clear_cron_events();
        
        // Flush rewrite rules
        flush_rewrite_rules();
        
        // Update deactivation option
        update_option( 'bil24_deactivated', time() );
        
        Utils::log( 'Plugin deactivated', Constants::LOG_LEVEL_INFO );
    }

    /**
     * Plugin uninstall hook (static method for register_uninstall_hook)
     */
    public static function uninstall(): void {
        // Remove plugin options
        delete_option( Constants::OPTION_SETTINGS );
        delete_option( Constants::OPTION_API_CREDENTIALS );
        delete_option( Constants::OPTION_SYNC_STATUS );
        delete_option( Constants::OPTION_DB_VERSION );
        delete_option( 'bil24_activated' );
        delete_option( 'bil24_deactivated' );
        
        // Drop database tables if needed
        // self::drop_database_tables();
        
        Utils::log( 'Plugin uninstalled', Constants::LOG_LEVEL_INFO );
    }

    /**
     * Initialize plugin
     */
    public function init(): void {
        // Load text domain
        load_plugin_textdomain( 
            Constants::TEXT_DOMAIN, 
            false, 
            dirname( Constants::PLUGIN_BASENAME ) . '/languages' 
        );
        
        // Register custom post types
        $this->register_post_types();
        
        // Initialize REST API
        $this->init_rest_api();
        
        // Initialize integrations
        $this->init_integrations();
    }

    /**
     * Initialize admin area
     */
    public function admin_init(): void {
        // Initialize admin settings page
        if ( class_exists( '\\Bil24\\Admin\\SettingsPage' ) ) {
            ( new \Bil24\Admin\SettingsPage() )->register();
        }
    }

    /**
     * Register custom post types
     */
    private function register_post_types(): void {
        // Event CPT
        register_post_type( Constants::CPT_EVENT, [
            'labels' => [
                'name' => __( 'Events', Constants::TEXT_DOMAIN ),
                'singular_name' => __( 'Event', Constants::TEXT_DOMAIN ),
            ],
            'public' => false,
            'show_ui' => true,
            'show_in_menu' => 'bil24-settings',
            'supports' => [ 'title', 'editor', 'custom-fields' ],
        ]);

        // Session CPT
        register_post_type( Constants::CPT_SESSION, [
            'labels' => [
                'name' => __( 'Sessions', Constants::TEXT_DOMAIN ),
                'singular_name' => __( 'Session', Constants::TEXT_DOMAIN ),
            ],
            'public' => false,
            'show_ui' => true,
            'show_in_menu' => 'bil24-settings',
            'supports' => [ 'title', 'editor', 'custom-fields' ],
        ]);
    }

    /**
     * Initialize REST API endpoints
     */
    private function init_rest_api(): void {
        add_action( 'rest_api_init', function() {
            register_rest_route( Constants::API_NAMESPACE, '/webhook', [
                'methods' => 'POST',
                'callback' => [ $this, 'handle_webhook' ],
                'permission_callback' => [ $this, 'webhook_permissions_check' ],
            ]);
        });
    }

    /**
     * Initialize integrations
     */
    private function init_integrations(): void {
        // Event synchronization
        if ( class_exists( '\\Bil24\\Integrations\\EventSync' ) ) {
            new \Bil24\Integrations\EventSync();
        }
        
        // Session synchronization
        if ( class_exists( '\\Bil24\\Integrations\\SessionSync' ) ) {
            new \Bil24\Integrations\SessionSync();
        }
        
        // Order synchronization
        if ( class_exists( '\\Bil24\\Integrations\\OrderSync' ) ) {
            new \Bil24\Integrations\OrderSync();
        }
        
        // WooCommerce integration
        if ( Utils::is_woocommerce_active() ) {
            // Initialize WooCommerce integration
        }
    }

    /**
     * Handle incoming webhooks
     */
    public function handle_webhook( \WP_REST_Request $request ) {
        // Webhook handling logic will be implemented later
        return new \WP_REST_Response( [ 'status' => 'received' ], 200 );
    }

    /**
     * Check webhook permissions
     */
    public function webhook_permissions_check( \WP_REST_Request $request ): bool {
        // Webhook authentication logic will be implemented later
        return true;
    }

    /**
     * Check plugin requirements
     */
    private static function check_requirements(): bool {
        global $wp_version;
        
        // Check WordPress version
        if ( version_compare( $wp_version, '6.2', '<' ) ) {
            return false;
        }
        
        // Check PHP version
        if ( version_compare( PHP_VERSION, '8.0', '<' ) ) {
            return false;
        }
        
        return true;
    }

    /**
     * Create database tables
     */
    private static function create_database_tables(): void {
        // Database table creation logic will be implemented later
        update_option( Constants::OPTION_DB_VERSION, Constants::DB_VERSION );
    }

    /**
     * Set default plugin options
     */
    private static function set_default_options(): void {
        $default_settings = [
            'api_timeout' => Constants::API_TIMEOUT,
            'cache_expiration' => Constants::CACHE_EXPIRATION,
            'log_level' => Constants::LOG_LEVEL_INFO,
            'sync_interval' => Constants::CRON_SYNC_INTERVAL,
        ];
        
        if ( ! get_option( Constants::OPTION_SETTINGS ) ) {
            update_option( Constants::OPTION_SETTINGS, $default_settings );
        }
    }

    /**
     * Schedule cron events
     */
    private static function schedule_cron_events(): void {
        // Schedule catalog sync
        if ( ! wp_next_scheduled( Constants::HOOK_SYNC_CATALOG ) ) {
            wp_schedule_event( time() + 60, Constants::CRON_SYNC_INTERVAL, Constants::HOOK_SYNC_CATALOG );
        }
        
        // Schedule cleanup
        if ( ! wp_next_scheduled( Constants::HOOK_CLEANUP_LOGS ) ) {
            wp_schedule_event( time() + 3600, Constants::CRON_CLEANUP_INTERVAL, Constants::HOOK_CLEANUP_LOGS );
        }
    }

    /**
     * Clear cron events
     */
    private static function clear_cron_events(): void {
        wp_clear_scheduled_hook( Constants::HOOK_SYNC_CATALOG );
        wp_clear_scheduled_hook( Constants::HOOK_SYNC_ORDERS );
        wp_clear_scheduled_hook( Constants::HOOK_SYNC_SESSIONS );
        wp_clear_scheduled_hook( Constants::HOOK_CLEANUP_LOGS );
    }
}
