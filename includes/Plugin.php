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
     * Settings page instance
     */
    private ?\Bil24\Admin\SettingsPage $settings_page = null;

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
        
        // Fix jQuery Migrate warnings
        add_action( 'wp_default_scripts', [ $this, 'disable_jquery_migrate' ] );
        
        // Admin hooks - УБИРАЕМ is_admin() проверку и добавляем хуки всегда
        // WordPress сам определит когда их выполнять
        add_action( 'admin_menu', [ $this, 'register_admin_menu' ] );
        add_action( 'admin_notices', [ $this, 'admin_notices' ] );
        
        // Plugin action links - только если есть константа basename
        if ( defined( 'BIL24_CONNECTOR_PLUGIN_BASENAME' ) ) {
            add_filter( 'plugin_action_links_' . BIL24_CONNECTOR_PLUGIN_BASENAME, [ $this, 'plugin_action_links' ] );
        }

        $this->initialized = true;
        
        // Добавляем отладочную информацию
        if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
            error_log( '[Bil24] Plugin hooks initialized successfully' );
        }
    }

    /**
     * Register admin menu and settings page
     */
    public function register_admin_menu(): void {
        // Отладочная информация
        if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
            error_log( '[Bil24] register_admin_menu called' );
            error_log( '[Bil24] Current user can manage_options: ' . ( current_user_can( 'manage_options' ) ? 'YES' : 'NO' ) );
        }
        
        // Load required dependencies first
        $this->load_admin_classes();
        
        // Проверяем что классы загружены
        if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
            error_log( '[Bil24] SettingsPage class exists: ' . ( class_exists( '\\Bil24\\Admin\\SettingsPage' ) ? 'YES' : 'NO' ) );
        }
        
        // Проверяем параметр URL для включения тестовой версии
        $use_test_version = isset( $_GET['bil24_test'] ) && $_GET['bil24_test'] === '1';
        
        if ( $use_test_version ) {
            // Загружаем тестовую версию
            $test_settings_file = __DIR__ . '/Admin/SettingsPage-NO-CAPS-CHECK.php';
            // Try lower-case variant if canonical path not found (case-sensitive filesystems)
            if ( ! file_exists( $test_settings_file ) ) {
                $test_settings_file = __DIR__ . '/admin/SettingsPage-NO-CAPS-CHECK.php';
            }

            if ( file_exists( $test_settings_file ) ) {
                require_once $test_settings_file;
                
                if ( class_exists( '\\Bil24\\Admin\\SettingsPageNoCapsCheck' ) ) {
                    $test_settings_page = new \Bil24\Admin\SettingsPageNoCapsCheck();
                    $test_settings_page->register();
                    
                    if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                        error_log( '[Bil24] TEST SettingsPage registered (no caps check)' );
                    }
                    
                    // Показываем уведомление о тестовом режиме
                    add_action( 'admin_notices', function() {
                        echo '<div class="notice notice-warning">';
                        echo '<p><strong>Bil24 Connector:</strong> Активирован тестовый режим БЕЗ проверки прав. ';
                        echo 'Перейдите в Настройки → Bil24 Connector (TEST). ';
                        echo '<a href="' . admin_url( 'admin.php' ) . '">Отключить тестовый режим</a></p>';
                        echo '</div>';
                    });
                    
                    return; // Не загружаем обычную версию
                }
            }
        }
        
        // Initialize settings page only once (обычная версия)
        if ( ! $this->settings_page && class_exists( '\\Bil24\\Admin\\SettingsPage' ) ) {
            try {
                $this->settings_page = new \Bil24\Admin\SettingsPage();
                $this->settings_page->register();
                
                // Add AJAX handler for connection testing
                add_action( 'wp_ajax_bil24_test_connection', [ $this->settings_page, 'ajax_test_connection' ] );
                
                if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                    error_log( '[Bil24] SettingsPage registered successfully' );
                }
            } catch ( \Exception $e ) {
                if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                    error_log( '[Bil24] SettingsPage registration failed: ' . $e->getMessage() );
                }
                
                // Показываем ошибку в админке с ссылкой на тестовый режим
                add_action( 'admin_notices', function() use ( $e ) {
                    echo '<div class="notice notice-error"><p>';
                    echo '<strong>Bil24 Connector Error:</strong> ' . esc_html( $e->getMessage() );
                    echo '<br><br><a href="' . admin_url( 'admin.php?bil24_test=1' ) . '" class="button">';
                    echo 'Запустить тестовый режим (без проверки прав)</a>';
                    echo '</p></div>';
                });
            }
        } elseif ( ! class_exists( '\\Bil24\\Admin\\SettingsPage' ) ) {
            if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                error_log( '[Bil24] SettingsPage class not found - attempting manual load' );
            }
            
            // Показываем ошибку в админке с ссылкой на тестовый режим
            add_action( 'admin_notices', function() {
                echo '<div class="notice notice-error"><p>';
                echo '<strong>Bil24 Connector Error:</strong> Settings page class could not be loaded. Please check plugin installation.';
                echo '<br><br><a href="' . admin_url( 'admin.php?bil24_test=1' ) . '" class="button">';
                echo 'Запустить тестовый режим (диагностика)</a>';
                echo '</p></div>';
            });
        }
        
        // Добавляем ссылку на тестовый режим в любом случае (для диагностики)
        if ( defined( 'WP_DEBUG' ) && WP_DEBUG && current_user_can( 'activate_plugins' ) ) {
            add_action( 'admin_notices', function() {
                echo '<div class="notice notice-info" style="border-left-color: #007cba;"><p>';
                echo '<strong>Bil24 Connector Debug:</strong> ';
                echo '<a href="' . admin_url( 'admin.php?bil24_test=1' ) . '">Запустить диагностику прав доступа</a>';
                echo '</p></div>';
            });
        }
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
     * Load admin classes manually if needed
     */
    private function load_admin_classes(): void {
        // Load required dependencies first
        $includes_dir = __DIR__;
        
        // Отладочная информация
        if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
            error_log( '[Bil24] === ДИАГНОСТИКА ПУТЕЙ ===' );
            error_log( '[Bil24] Plugin file: ' . BIL24_CONNECTOR_PLUGIN_FILE );
            error_log( '[Bil24] Plugin dir: ' . BIL24_CONNECTOR_PLUGIN_DIR );
            error_log( '[Bil24] Plugin basename: ' . BIL24_CONNECTOR_PLUGIN_BASENAME );
            error_log( '[Bil24] Current folder name: ' . basename( BIL24_CONNECTOR_PLUGIN_DIR ) );
            error_log( '[Bil24] Includes dir: ' . $includes_dir );
            error_log( '[Bil24] Loading admin classes from: ' . $includes_dir );
        }
        
        // Список файлов для загрузки в правильном порядке
        $files_to_load = [
            'Utils.php' => '\\Bil24\\Utils',
            'Constants.php' => '\\Bil24\\Constants', 
            'Admin/SettingsPage.php' => '\\Bil24\\Admin\\SettingsPage',
            // fallback lowercase directory for case-sensitive servers
            'admin/SettingsPage.php' => '\\Bil24\\Admin\\SettingsPage',
            'Api/Client.php' => '\\Bil24\\Api\\Client',
            'Api/Endpoints.php' => '\\Bil24\\Api\\Endpoints'
        ];
        
        // Если активирован тестовый режим, добавляем тестовый файл
        $use_test_version = isset( $_GET['bil24_test'] ) && $_GET['bil24_test'] === '1';
        if ( $use_test_version ) {
            $files_to_load['Admin/SettingsPage-NO-CAPS-CHECK.php']  = '\\Bil24\\Admin\\SettingsPageNoCapsCheck';
            $files_to_load['admin/SettingsPage-NO-CAPS-CHECK.php']  = '\\Bil24\\Admin\\SettingsPageNoCapsCheck';
            
            if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                error_log( '[Bil24] Test mode activated - will load SettingsPageNoCapsCheck' );
            }
        }
        
        foreach ( $files_to_load as $file => $class ) {
            if ( ! class_exists( $class ) ) {
                $full_path = $includes_dir . '/' . $file;
                
                if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                    error_log( "[Bil24] Checking file: {$full_path}" );
                    error_log( "[Bil24] File exists: " . ( file_exists( $full_path ) ? 'YES' : 'NO' ) );
                }
                
                if ( file_exists( $full_path ) ) {
                    try {
                        require_once $full_path;
                        
                        if ( class_exists( $class ) ) {
                            if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                                error_log( "[Bil24] ✅ {$class} loaded from {$file}" );
                            }
                        } else {
                            if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                                error_log( "[Bil24] ❌ {$class} - file loaded but class not found" );
                            }
                        }
                    } catch ( \Exception $e ) {
                        if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                            error_log( "[Bil24] ❌ Error loading {$class}: " . $e->getMessage() );
                        }
                    }
                } else {
                    if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                        error_log( "[Bil24] ❌ File not found: {$full_path}" );
                    }
                }
            } else {
                if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                    error_log( "[Bil24] ✅ {$class} already loaded" );
                }
            }
        }
        
        // Финальная отчетность
        if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
            error_log( '[Bil24] Class loading summary:' );
            foreach ( $files_to_load as $file => $class ) {
                $status = class_exists( $class ) ? 'OK' : 'FAILED';
                error_log( "  - {$class}: {$status}" );
            }
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
            'show_in_menu' => false, // Will be added to Bil24 submenu
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
            'show_in_menu' => false, // Will be added to Bil24 submenu
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
     * Show admin notices
     */
    public function admin_notices(): void {
        // Проверяем настройки только на страницах админки
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }
        
        // Не показываем уведомление на самой странице настроек
        $screen = get_current_screen();
        if ( $screen && ( $screen->id === 'settings_page_bil24-connector' ) ) {
            return;
        }
        
        $this->load_admin_classes();
        
        try {
            // Проверяем, что API классы загружены
            if (!class_exists('\\Bil24\\Api\\Client')) {
                error_log('Bil24 Connector: API Client class not found. Autoloader may not be working properly.');
                return;
            }
            
            $api = new \Bil24\Api\Client();
            $status = $api->get_config_status();
            
            if ( ! $status['configured'] ) {
                echo '<div class="notice notice-warning is-dismissible">';
                echo '<p><strong>' . esc_html__( 'Bil24 Connector', 'bil24' ) . ':</strong> ';
                echo esc_html__( 'Plugin requires configuration to work properly.', 'bil24' );
                echo ' <a href="' . esc_url( admin_url( 'options-general.php?page=bil24-connector' ) ) . '">';
                echo esc_html__( 'Configure now', 'bil24' );
                echo '</a></p>';
                echo '</div>';
            }
        } catch ( \Exception $e ) {
            // Ошибки игнорируем, чтобы не спамить админку
            error_log('Bil24 Connector API error: ' . $e->getMessage());
        }
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
        // Основные настройки API (то что ожидает SettingsPage)
        $api_settings = [
            'fid'   => '',
            'token' => '',
            'env'   => 'test',
        ];
        
        // Технические настройки (для внутреннего использования)
        $technical_settings = [
            'api_timeout' => Constants::API_TIMEOUT,
            'cache_expiration' => Constants::CACHE_EXPIRATION,
            'log_level' => Constants::LOG_LEVEL_INFO,
            'sync_interval' => Constants::CRON_SYNC_INTERVAL,
        ];
        
        // Объединяем настройки
        $default_settings = array_merge($api_settings, $technical_settings);
        
        // Устанавливаем только если настройки еще не существуют
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
     * Disable jQuery Migrate to prevent console warnings
     * 
     * @param \WP_Scripts $scripts Scripts object
     */
    public function disable_jquery_migrate( $scripts ): void {
        if ( ! empty( $scripts->registered['jquery'] ) ) {
            $scripts->registered['jquery']->deps = array_diff( 
                $scripts->registered['jquery']->deps, 
                [ 'jquery-migrate' ] 
            );
            
            if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                Utils::log( 'jQuery Migrate disabled to prevent console warnings', Constants::LOG_LEVEL_DEBUG );
            }
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
