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
            add_action( 'admin_menu', [ $this, 'admin_menu' ] );
            add_filter( 'plugin_action_links_' . BIL24_CONNECTOR_PLUGIN_BASENAME, [ $this, 'plugin_action_links' ] );
            add_action( 'wp_ajax_bil24_test_connection', [ $this, 'ajax_test_connection' ] );
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
        // Register settings only (menu is handled in admin_menu hook)
        if ( class_exists( '\\Bil24\\Admin\\SettingsPage' ) ) {
            $settings_page = new \Bil24\Admin\SettingsPage();
            $settings_page->register_settings();
        }
    }
    
    /**
     * Initialize admin menu
     */
    public function admin_menu(): void {
        // Добавляем страницу настроек в меню Настройки
        add_options_page(
            __( 'Bil24 Connector Settings', 'bil24' ),
            __( 'Bil24 Connector', 'bil24' ),
            'manage_options',
            'bil24-connector',
            [ $this, 'render_settings_page' ]
        );
        
        // Альтернативно добавляем в главное меню для лучшей видимости
        add_menu_page(
            __( 'Bil24 Connector', 'bil24' ),
            __( 'Bil24', 'bil24' ),
            'manage_options',
            'bil24-main',
            [ $this, 'render_settings_page' ],
            'dashicons-tickets-alt',
            30
        );
    }
    
    /**
     * Add plugin action links (Settings link)
     */
    public function plugin_action_links( array $links ): array {
        $settings_link = sprintf(
            '<a href="%s">%s</a>',
            admin_url( 'options-general.php?page=bil24-connector' ),
            __( 'Settings', 'bil24' )
        );
        
        array_unshift( $links, $settings_link );
        
        return $links;
    }
    
    /**
     * Render settings page
     */
    public function render_settings_page(): void {
        if ( class_exists( '\\Bil24\\Admin\\SettingsPage' ) ) {
            $settings_page = new \Bil24\Admin\SettingsPage();
            $settings_page->render_page();
        } else {
            echo '<div class="wrap"><h1>Bil24 Connector</h1><p>Ошибка: Класс SettingsPage не найден.</p></div>';
        }
    }
    
    /**
     * AJAX handler for connection testing
     */
    public function ajax_test_connection(): void {
        check_ajax_referer( 'bil24_test_connection' );
        
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( __( 'Insufficient permissions', 'bil24' ) );
        }

        try {
            if ( class_exists( '\\Bil24\\Api\\Client' ) ) {
                $api = new \Bil24\Api\Client();
                $connected = $api->test_connection();
                
                if ( $connected ) {
                    wp_send_json_success( [
                        'message' => __( 'Connection to Bil24 API established successfully!', 'bil24' )
                    ] );
                } else {
                    wp_send_json_error( [
                        'message' => __( 'Failed to connect to Bil24 API. Please check your settings.', 'bil24' )
                    ] );
                }
            } else {
                wp_send_json_error( [
                    'message' => __( 'API Client class not found.', 'bil24' )
                ] );
            }
        } catch ( \Exception $e ) {
            wp_send_json_error( [
                'message' => sprintf( 
                    /* translators: %s: error message */
                    __( 'Connection error: %s', 'bil24' ), 
                    $e->getMessage() 
                )
            ] );
        }
    }

    /**
     * Register custom post types
     */
    private function register_post_types(): void {
        // Event CPT
        register_post_type( Constants::CPT_EVENT, [
            'labels' => [
                'name' => __( 'Bil24 Events', 'bil24' ),
                'singular_name' => __( 'Bil24 Event', 'bil24' ),
                'menu_name' => __( 'Events', 'bil24' ),
                'name_admin_bar' => __( 'Event', 'bil24' ),
                'add_new' => __( 'Add New Event', 'bil24' ),
                'add_new_item' => __( 'Add New Event', 'bil24' ),
                'new_item' => __( 'New Event', 'bil24' ),
                'edit_item' => __( 'Edit Event', 'bil24' ),
                'view_item' => __( 'View Event', 'bil24' ),
                'view_items' => __( 'View Events', 'bil24' ),
                'all_items' => __( 'All Events', 'bil24' ),
                'search_items' => __( 'Search Events', 'bil24' ),
                'parent_item_colon' => __( 'Parent Event:', 'bil24' ),
                'not_found' => __( 'No events found', 'bil24' ),
                'not_found_in_trash' => __( 'No events found in trash', 'bil24' ),
                'archives' => __( 'Event Archives', 'bil24' ),
                'attributes' => __( 'Event Attributes', 'bil24' ),
                'insert_into_item' => __( 'Insert into event', 'bil24' ),
                'uploaded_to_this_item' => __( 'Uploaded to this event', 'bil24' ),
                'featured_image' => __( 'Event Image', 'bil24' ),
                'set_featured_image' => __( 'Set event image', 'bil24' ),
                'remove_featured_image' => __( 'Remove event image', 'bil24' ),
                'use_featured_image' => __( 'Use as event image', 'bil24' ),
            ],
            'description' => __( 'Events imported from Bil24 platform', 'bil24' ),
            'public' => true,
            'publicly_queryable' => true,
            'show_ui' => true,
            'show_in_menu' => 'bil24-settings',
            'show_in_admin_bar' => false,
            'show_in_nav_menus' => true,
            'show_in_rest' => true,
            'rest_base' => 'bil24-events',
            'capability_type' => 'post',
            'map_meta_cap' => true,
            'supports' => [ 'title', 'editor', 'custom-fields', 'thumbnail', 'excerpt' ],
            'taxonomies' => [],
            'has_archive' => true,
            'rewrite' => [
                'slug' => 'events',
                'with_front' => false,
            ],
            'query_var' => true,
        ]);

        // Session CPT
        register_post_type( Constants::CPT_SESSION, [
            'labels' => [
                'name' => __( 'Bil24 Sessions', 'bil24' ),
                'singular_name' => __( 'Bil24 Session', 'bil24' ),
                'menu_name' => __( 'Sessions', 'bil24' ),
                'name_admin_bar' => __( 'Session', 'bil24' ),
                'add_new' => __( 'Add New Session', 'bil24' ),
                'add_new_item' => __( 'Add New Session', 'bil24' ),
                'new_item' => __( 'New Session', 'bil24' ),
                'edit_item' => __( 'Edit Session', 'bil24' ),
                'view_item' => __( 'View Session', 'bil24' ),
                'view_items' => __( 'View Sessions', 'bil24' ),
                'all_items' => __( 'All Sessions', 'bil24' ),
                'search_items' => __( 'Search Sessions', 'bil24' ),
                'parent_item_colon' => __( 'Parent Session:', 'bil24' ),
                'not_found' => __( 'No sessions found', 'bil24' ),
                'not_found_in_trash' => __( 'No sessions found in trash', 'bil24' ),
                'archives' => __( 'Session Archives', 'bil24' ),
                'attributes' => __( 'Session Attributes', 'bil24' ),
                'insert_into_item' => __( 'Insert into session', 'bil24' ),
                'uploaded_to_this_item' => __( 'Uploaded to this session', 'bil24' ),
                'featured_image' => __( 'Session Image', 'bil24' ),
                'set_featured_image' => __( 'Set session image', 'bil24' ),
                'remove_featured_image' => __( 'Remove session image', 'bil24' ),
                'use_featured_image' => __( 'Use as session image', 'bil24' ),
            ],
            'description' => __( 'Sessions imported from Bil24 platform', 'bil24' ),
            'public' => false,
            'show_ui' => true,
            'show_in_menu' => 'bil24-settings',
            'show_in_admin_bar' => false,
            'show_in_nav_menus' => false,
            'show_in_rest' => true,
            'rest_base' => 'bil24-sessions',
            'capability_type' => 'post',
            'capabilities' => [
                'create_posts' => 'manage_bil24_sessions',
                'edit_posts' => 'manage_bil24_sessions',
                'edit_others_posts' => 'manage_bil24_sessions',
                'publish_posts' => 'manage_bil24_sessions',
                'read_private_posts' => 'manage_bil24_sessions',
                'delete_posts' => 'manage_bil24_sessions',
                'delete_private_posts' => 'manage_bil24_sessions',
                'delete_published_posts' => 'manage_bil24_sessions',
                'delete_others_posts' => 'manage_bil24_sessions',
                'edit_private_posts' => 'manage_bil24_sessions',
                'edit_published_posts' => 'manage_bil24_sessions',
            ],
            'supports' => [ 'title', 'editor', 'custom-fields', 'thumbnail', 'excerpt' ],
            'taxonomies' => [],
            'has_archive' => false,
            'rewrite' => false,
            'query_var' => false,
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
        
        // Frontend event display
        if ( ! is_admin() && class_exists( '\\Bil24\\Frontend\\EventDisplay' ) ) {
            new \Bil24\Frontend\EventDisplay();
        }
        
        // WooCommerce integration
        if ( Utils::is_woocommerce_active() && class_exists( '\\Bil24\\Integrations\\WooCommerce\\Integration' ) ) {
            // Initialize WooCommerce integration
            try {
                $wc_integration = new \Bil24\Integrations\WooCommerce\Integration();
                Utils::log( 'WooCommerce интеграция успешно инициализирована', Constants::LOG_LEVEL_INFO );
            } catch ( \Exception $e ) {
                Utils::log( 'Ошибка инициализации WooCommerce интеграции: ' . $e->getMessage(), Constants::LOG_LEVEL_ERROR );
            }
        } else if ( ! Utils::is_woocommerce_active() ) {
            Utils::log( 'WooCommerce не активен, интеграция пропущена', Constants::LOG_LEVEL_WARNING );
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
