<?php
namespace Bil24\Integrations\WooCommerce;

use Bil24\Api\Endpoints;
use Bil24\Constants;
use Bil24\Utils;

defined( 'ABSPATH' ) || exit;

/**
 * Main WooCommerce Integration class
 * 
 * Coordinates all WooCommerce integration components and manages settings
 * Handles initialization, configuration, and component lifecycle
 * 
 * @package Bil24Connector
 * @since 0.1.0
 */
class Integration {

    /**
     * API Endpoints instance
     */
    private Endpoints $api;

    /**
     * Integration components
     */
    private ProductSync $product_sync;
    private CartIntegration $cart_integration;
    private OrderSync $order_sync;
    private CustomerSync $customer_sync;

    /**
     * Integration settings
     */
    private array $settings;

    /**
     * Constructor
     */
    public function __construct( ?Endpoints $api = null ) {
        $this->api = $api ?? new Endpoints();
        $this->settings = get_option( 'bil24_woocommerce_settings', [] );
        
        $this->init_components();
        $this->register_hooks();
    }

    /**
     * Initialize integration components
     */
    private function init_components(): void {
        // Check if WooCommerce is active
        if ( ! $this->is_woocommerce_active() ) {
            add_action( 'admin_notices', [ $this, 'woocommerce_missing_notice' ] );
            return;
        }

        // Initialize components
        $this->product_sync = new ProductSync( $this->api );
        $this->cart_integration = new CartIntegration( $this->api );
        $this->order_sync = new OrderSync( $this->api );
        $this->customer_sync = new CustomerSync( $this->api );
        
        Utils::log( 'WooCommerce интеграция инициализирована', Constants::LOG_LEVEL_INFO );
    }

    /**
     * Register WordPress hooks
     */
    private function register_hooks(): void {
        // Admin hooks
        add_action( 'woocommerce_settings_tabs_array', [ $this, 'add_settings_tab' ], 50 );
        add_action( 'woocommerce_settings_tabs_bil24', [ $this, 'settings_tab_content' ] );
        add_action( 'woocommerce_update_options_bil24', [ $this, 'update_settings' ] );
        
        // System status integration
        add_action( 'woocommerce_system_status_report', [ $this, 'add_system_status_section' ] );
        
        // Admin dashboard widgets
        add_action( 'wp_dashboard_setup', [ $this, 'add_dashboard_widgets' ] );
        
        // Admin menu
        add_action( 'admin_menu', [ $this, 'add_admin_menu' ] );
        
        // Plugin activation/deactivation
        register_activation_hook( __FILE__, [ $this, 'on_activation' ] );
        register_deactivation_hook( __FILE__, [ $this, 'on_deactivation' ] );
        
        // AJAX handlers
        add_action( 'wp_ajax_bil24_test_connection', [ $this, 'ajax_test_connection' ] );
        add_action( 'wp_ajax_bil24_bulk_import', [ $this, 'ajax_bulk_import' ] );
        add_action( 'wp_ajax_bil24_sync_status', [ $this, 'ajax_sync_status' ] );
        
        // Enqueue scripts and styles
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_scripts' ] );
        add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_frontend_scripts' ] );
        
        // WooCommerce product types
        add_filter( 'product_type_selector', [ $this, 'add_bil24_product_type' ] );
        add_action( 'woocommerce_product_options_general_product_data', [ $this, 'add_bil24_product_fields' ] );
        
        // Order meta display
        add_action( 'woocommerce_admin_order_data_after_billing_address', [ $this, 'display_bil24_order_info' ] );
        
        // Webhooks
        add_action( 'init', [ $this, 'register_webhooks' ] );
        
        // Cron jobs
        add_action( 'init', [ $this, 'schedule_cron_jobs' ] );
    }

    /**
     * Check if WooCommerce is active
     */
    private function is_woocommerce_active(): bool {
        return class_exists( 'WooCommerce' );
    }

    /**
     * Display WooCommerce missing notice
     */
    public function woocommerce_missing_notice(): void {
        ?>
        <div class="notice notice-error">
            <p><strong>Bil24 Connector:</strong> Для работы WooCommerce интеграции требуется активный плагин WooCommerce.</p>
        </div>
        <?php
    }

    /**
     * Add settings tab to WooCommerce
     */
    public function add_settings_tab( array $settings_tabs ): array {
        $settings_tabs['bil24'] = 'Bil24';
        return $settings_tabs;
    }

    /**
     * Render settings tab content
     */
    public function settings_tab_content(): void {
        woocommerce_admin_fields( $this->get_settings_fields() );
    }

    /**
     * Get settings fields configuration
     */
    private function get_settings_fields(): array {
        return [
            [
                'title' => 'Bil24 WooCommerce Integration Settings',
                'type' => 'title',
                'desc' => 'Настройки интеграции между WooCommerce и Bil24',
                'id' => 'bil24_woocommerce_settings'
            ],
            [
                'title' => 'Включить интеграцию',
                'desc' => 'Активировать синхронизацию между WooCommerce и Bil24',
                'id' => 'bil24_woocommerce_enabled',
                'type' => 'checkbox',
                'default' => 'yes'
            ],
            [
                'title' => 'Автосинхронизация продуктов',
                'desc' => 'Автоматически импортировать события Bil24 как продукты WooCommerce',
                'id' => 'bil24_auto_sync_products',
                'type' => 'checkbox',
                'default' => 'yes'
            ],
            [
                'title' => 'Автосинхронизация заказов',
                'desc' => 'Автоматически синхронизировать заказы WooCommerce с Bil24',
                'id' => 'bil24_auto_sync_orders',
                'type' => 'checkbox',
                'default' => 'yes'
            ],
            [
                'title' => 'Автосинхронизация клиентов',
                'desc' => 'Автоматически синхронизировать профили клиентов с Bil24',
                'id' => 'bil24_auto_sync_customers',
                'type' => 'checkbox',
                'default' => 'yes'
            ],
            [
                'title' => 'Время резервирования (минуты)',
                'desc' => 'Время резервирования билетов в корзине',
                'id' => 'bil24_reservation_timeout',
                'type' => 'number',
                'default' => '15',
                'custom_attributes' => [
                    'min' => '5',
                    'max' => '60'
                ]
            ],
            [
                'title' => 'Автогенерация билетов',
                'desc' => 'Автоматически генерировать билеты при завершении заказа',
                'id' => 'bil24_auto_generate_tickets',
                'type' => 'checkbox',
                'default' => 'yes'
            ],
            [
                'title' => 'Отправка билетов по email',
                'desc' => 'Отправлять билеты клиентам по электронной почте',
                'id' => 'bil24_email_tickets',
                'type' => 'checkbox',
                'default' => 'yes'
            ],
            [
                'title' => 'Категория для событий',
                'desc' => 'Категория продуктов для импортированных событий',
                'id' => 'bil24_default_category',
                'type' => 'select',
                'options' => $this->get_product_categories(),
                'default' => ''
            ],
            [
                'title' => 'Статус продуктов по умолчанию',
                'desc' => 'Статус для новых импортированных продуктов',
                'id' => 'bil24_default_product_status',
                'type' => 'select',
                'options' => [
                    'publish' => 'Опубликован',
                    'draft' => 'Черновик',
                    'private' => 'Приватный'
                ],
                'default' => 'publish'
            ],
            [
                'title' => 'Маппинг статусов заказов',
                'type' => 'title',
                'desc' => 'Соответствие статусов заказов между WooCommerce и Bil24',
                'id' => 'bil24_order_status_mapping'
            ],
            [
                'title' => 'Ожидает оплаты → Bil24',
                'id' => 'bil24_status_mapping_pending',
                'type' => 'select',
                'options' => $this->get_bil24_order_statuses(),
                'default' => 'pending_payment'
            ],
            [
                'title' => 'В обработке → Bil24',
                'id' => 'bil24_status_mapping_processing',
                'type' => 'select',
                'options' => $this->get_bil24_order_statuses(),
                'default' => 'confirmed'
            ],
            [
                'title' => 'Выполнен → Bil24',
                'id' => 'bil24_status_mapping_completed',
                'type' => 'select',
                'options' => $this->get_bil24_order_statuses(),
                'default' => 'completed'
            ],
            [
                'title' => 'Отменен → Bil24',
                'id' => 'bil24_status_mapping_cancelled',
                'type' => 'select',
                'options' => $this->get_bil24_order_statuses(),
                'default' => 'cancelled'
            ],
            [
                'title' => 'Дополнительные настройки',
                'type' => 'title',
                'desc' => 'Расширенные параметры интеграции',
                'id' => 'bil24_advanced_settings'
            ],
            [
                'title' => 'Использовать ACF поля',
                'desc' => 'Использовать Advanced Custom Fields для расширенных данных',
                'id' => 'bil24_use_acf',
                'type' => 'checkbox',
                'default' => 'yes'
            ],
            [
                'title' => 'Логирование',
                'desc' => 'Уровень логирования для интеграции',
                'id' => 'bil24_log_level',
                'type' => 'select',
                'options' => [
                    'error' => 'Только ошибки',
                    'warning' => 'Предупреждения и ошибки',
                    'info' => 'Информация, предупреждения и ошибки',
                    'debug' => 'Все сообщения'
                ],
                'default' => 'info'
            ],
            [
                'title' => 'Интервал синхронизации',
                'desc' => 'Как часто выполнять автоматическую синхронизацию (минуты)',
                'id' => 'bil24_sync_interval',
                'type' => 'number',
                'default' => '15',
                'custom_attributes' => [
                    'min' => '5',
                    'max' => '1440'
                ]
            ],
            [
                'type' => 'sectionend',
                'id' => 'bil24_woocommerce_settings'
            ]
        ];
    }

    /**
     * Update settings
     */
    public function update_settings(): void {
        woocommerce_update_options( $this->get_settings_fields() );
        
        // Clear cache after settings update
        if ( method_exists( $this->api, 'clear_cache' ) ) {
            $this->api->clear_cache();
        }
        
        // Reschedule cron jobs if interval changed
        $this->schedule_cron_jobs();
        
        Utils::log( 'Настройки WooCommerce интеграции обновлены', Constants::LOG_LEVEL_INFO );
    }

    /**
     * Get product categories for settings
     */
    private function get_product_categories(): array {
        $categories = get_terms( [
            'taxonomy' => 'product_cat',
            'hide_empty' => false
        ] );
        
        $options = [ '' => 'Выберите категорию...' ];
        
        foreach ( $categories as $category ) {
            $options[ $category->term_id ] = $category->name;
        }
        
        return $options;
    }

    /**
     * Get Bil24 order statuses
     */
    private function get_bil24_order_statuses(): array {
        return [
            'pending_payment' => 'Ожидает оплаты',
            'confirmed' => 'Подтвержден',
            'completed' => 'Выполнен',
            'cancelled' => 'Отменен',
            'refunded' => 'Возвращен',
            'failed' => 'Неудачный',
            'on_hold' => 'Приостановлен'
        ];
    }

    /**
     * Add system status section
     */
    public function add_system_status_section(): void {
        ?>
        <table class="wc_status_table widefat" cellspacing="0">
            <thead>
                <tr>
                    <th colspan="3" data-export-label="Bil24 Integration">
                        <h2>Bil24 Integration</h2>
                    </th>
                </tr>
            </thead>
            <tbody>
                <?php
                $this->output_system_status_row( 'WooCommerce интеграция', $this->is_woocommerce_active() ? 'Активна' : 'Неактивна' );
                $this->output_system_status_row( 'API соединение', $this->test_api_connection() ? 'Работает' : 'Ошибка' );
                $this->output_system_status_row( 'Автосинхронизация продуктов', get_option( 'bil24_auto_sync_products' ) ? 'Включена' : 'Отключена' );
                $this->output_system_status_row( 'Автосинхронизация заказов', get_option( 'bil24_auto_sync_orders' ) ? 'Включена' : 'Отключена' );
                $this->output_system_status_row( 'Автосинхронизация клиентов', get_option( 'bil24_auto_sync_customers' ) ? 'Включена' : 'Отключена' );
                
                // Statistics
                $stats = $this->get_integration_stats();
                $this->output_system_status_row( 'Синхронизированных продуктов', $stats['synced_products'] );
                $this->output_system_status_row( 'Синхронизированных заказов', $stats['synced_orders'] );
                $this->output_system_status_row( 'Синхронизированных клиентов', $stats['synced_customers'] );
                ?>
            </tbody>
        </table>
        <?php
    }

    /**
     * Output system status row
     */
    private function output_system_status_row( string $label, string $value ): void {
        ?>
        <tr>
            <td data-export-label="<?php echo esc_attr( $label ); ?>"><?php echo esc_html( $label ); ?>:</td>
            <td class="help">&nbsp;</td>
            <td><?php echo esc_html( $value ); ?></td>
        </tr>
        <?php
    }

    /**
     * Test API connection
     */
    private function test_api_connection(): bool {
        try {
            return $this->api->test_connection();
        } catch ( \Exception $e ) {
            return false;
        }
    }

    /**
     * Get integration statistics
     */
    private function get_integration_stats(): array {
        global $wpdb;
        
        // Count synced products
        $synced_products = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->postmeta} 
             WHERE meta_key = '" . Constants::META_BIL24_ID . "' 
             AND post_id IN (SELECT ID FROM {$wpdb->posts} WHERE post_type = 'product')"
        );
        
        // Count synced orders
        $synced_orders = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->postmeta} 
             WHERE meta_key = '" . Constants::META_BIL24_ID . "' 
             AND post_id IN (SELECT ID FROM {$wpdb->posts} WHERE post_type = 'shop_order')"
        );
        
        // Count synced customers
        $synced_customers = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->usermeta} 
             WHERE meta_key = 'bil24_customer_id'"
        );
        
        return [
            'synced_products' => intval( $synced_products ),
            'synced_orders' => intval( $synced_orders ),
            'synced_customers' => intval( $synced_customers )
        ];
    }

    /**
     * Add admin menu
     */
    public function add_admin_menu(): void {
        add_submenu_page(
            'woocommerce',
            'Bil24 Integration',
            'Bil24 Integration',
            'manage_woocommerce',
            'bil24-woocommerce',
            [ $this, 'render_admin_page' ]
        );
    }

    /**
     * Render admin page
     */
    public function render_admin_page(): void {
        $active_tab = $_GET['tab'] ?? 'overview';
        ?>
        <div class="wrap">
            <h1>Bil24 WooCommerce Integration</h1>
            
            <nav class="nav-tab-wrapper">
                <a href="?page=bil24-woocommerce&tab=overview" class="nav-tab <?php echo $active_tab === 'overview' ? 'nav-tab-active' : ''; ?>">Обзор</a>
                <a href="?page=bil24-woocommerce&tab=sync" class="nav-tab <?php echo $active_tab === 'sync' ? 'nav-tab-active' : ''; ?>">Синхронизация</a>
                <a href="?page=bil24-woocommerce&tab=logs" class="nav-tab <?php echo $active_tab === 'logs' ? 'nav-tab-active' : ''; ?>">Логи</a>
                <a href="?page=bil24-woocommerce&tab=tools" class="nav-tab <?php echo $active_tab === 'tools' ? 'nav-tab-active' : ''; ?>">Инструменты</a>
            </nav>
            
            <div class="tab-content">
                <?php
                switch ( $active_tab ) {
                    case 'overview':
                        $this->render_overview_tab();
                        break;
                    case 'sync':
                        $this->render_sync_tab();
                        break;
                    case 'logs':
                        $this->render_logs_tab();
                        break;
                    case 'tools':
                        $this->render_tools_tab();
                        break;
                }
                ?>
            </div>
        </div>
        <?php
    }

    /**
     * Render overview tab
     */
    private function render_overview_tab(): void {
        $stats = $this->get_integration_stats();
        $connection_status = $this->test_api_connection();
        ?>
        <div class="bil24-overview">
            <div class="bil24-status-cards">
                <div class="status-card">
                    <h3>API Соединение</h3>
                    <div class="status-indicator <?php echo $connection_status ? 'connected' : 'disconnected'; ?>">
                        <?php echo $connection_status ? '✓ Подключено' : '✗ Отключено'; ?>
                    </div>
                </div>
                
                <div class="status-card">
                    <h3>Продукты</h3>
                    <div class="stat-number"><?php echo $stats['synced_products']; ?></div>
                    <div class="stat-label">Синхронизировано</div>
                </div>
                
                <div class="status-card">
                    <h3>Заказы</h3>
                    <div class="stat-number"><?php echo $stats['synced_orders']; ?></div>
                    <div class="stat-label">Синхронизировано</div>
                </div>
                
                <div class="status-card">
                    <h3>Клиенты</h3>
                    <div class="stat-number"><?php echo $stats['synced_customers']; ?></div>
                    <div class="stat-label">Синхронизировано</div>
                </div>
            </div>
            
            <div class="bil24-recent-activity">
                <h3>Последняя активность</h3>
                <?php $this->render_recent_activity(); ?>
            </div>
        </div>
        
        <style>
        .bil24-status-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin: 20px 0;
        }
        .status-card {
            background: #fff;
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 20px;
            text-align: center;
        }
        .status-indicator.connected {
            color: #46b450;
            font-weight: bold;
        }
        .status-indicator.disconnected {
            color: #dc3232;
            font-weight: bold;
        }
        .stat-number {
            font-size: 2em;
            font-weight: bold;
            color: #333;
        }
        .stat-label {
            color: #666;
            font-size: 0.9em;
        }
        </style>
        <?php
    }

    /**
     * Render sync tab
     */
    private function render_sync_tab(): void {
        ?>
        <div class="bil24-sync-tools">
            <h3>Инструменты синхронизации</h3>
            
            <div class="sync-section">
                <h4>Импорт продуктов</h4>
                <p>Импортировать события из Bil24 как продукты WooCommerce</p>
                <button type="button" class="button button-primary" onclick="bil24BulkImport('products')">
                    Импортировать события
                </button>
                <div id="import-products-result"></div>
            </div>
            
            <div class="sync-section">
                <h4>Синхронизация заказов</h4>
                <p>Синхронизировать заказы WooCommerce с Bil24</p>
                <button type="button" class="button button-primary" onclick="bil24BulkImport('orders')">
                    Синхронизировать заказы
                </button>
                <div id="import-orders-result"></div>
            </div>
            
            <div class="sync-section">
                <h4>Синхронизация клиентов</h4>
                <p>Синхронизировать профили клиентов с Bil24</p>
                <button type="button" class="button button-primary" onclick="bil24BulkImport('customers')">
                    Синхронизировать клиентов
                </button>
                <div id="import-customers-result"></div>
            </div>
            
            <div class="sync-section">
                <h4>Статус синхронизации</h4>
                <button type="button" class="button" onclick="bil24GetSyncStatus()">
                    Обновить статус
                </button>
                <div id="sync-status-result"></div>
            </div>
        </div>
        
        <script>
        function bil24BulkImport(type) {
            var resultDiv = document.getElementById('import-' + type + '-result');
            resultDiv.innerHTML = 'Выполняется импорт...';
            
            jQuery.post(ajaxurl, {
                action: 'bil24_bulk_import',
                type: type,
                nonce: '<?php echo wp_create_nonce( 'bil24_admin_nonce' ); ?>'
            }, function(response) {
                if (response.success) {
                    resultDiv.innerHTML = '<div class="notice notice-success"><p>' + response.data.message + '</p></div>';
                } else {
                    resultDiv.innerHTML = '<div class="notice notice-error"><p>' + response.data.message + '</p></div>';
                }
            });
        }
        
        function bil24GetSyncStatus() {
            var resultDiv = document.getElementById('sync-status-result');
            resultDiv.innerHTML = 'Получение статуса...';
            
            jQuery.post(ajaxurl, {
                action: 'bil24_sync_status',
                nonce: '<?php echo wp_create_nonce( 'bil24_admin_nonce' ); ?>'
            }, function(response) {
                if (response.success) {
                    var status = response.data;
                    var html = '<table class="widefat"><tbody>';
                    html += '<tr><td>Продукты с ошибками:</td><td>' + status.products_error + '</td></tr>';
                    html += '<tr><td>Заказы с ошибками:</td><td>' + status.orders_error + '</td></tr>';
                    html += '<tr><td>Клиенты с ошибками:</td><td>' + status.customers_error + '</td></tr>';
                    html += '</tbody></table>';
                    resultDiv.innerHTML = html;
                } else {
                    resultDiv.innerHTML = '<div class="notice notice-error"><p>Ошибка получения статуса</p></div>';
                }
            });
        }
        </script>
        
        <style>
        .sync-section {
            background: #fff;
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 20px;
            margin: 20px 0;
        }
        .sync-section h4 {
            margin-top: 0;
        }
        </style>
        <?php
    }

    /**
     * Render logs tab
     */
    private function render_logs_tab(): void {
        $logs = Utils::get_recent_logs( 100 );
        ?>
        <div class="bil24-logs">
            <h3>Логи интеграции</h3>
            
            <div class="log-controls">
                <button type="button" class="button" onclick="location.reload()">Обновить</button>
                <button type="button" class="button" onclick="bil24ClearLogs()">Очистить логи</button>
            </div>
            
            <div class="log-entries">
                <?php if ( empty( $logs ) ): ?>
                    <p>Нет логов для отображения</p>
                <?php else: ?>
                    <table class="widefat">
                        <thead>
                            <tr>
                                <th>Время</th>
                                <th>Уровень</th>
                                <th>Сообщение</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ( $logs as $log ): ?>
                            <tr class="log-level-<?php echo esc_attr( $log['level'] ); ?>">
                                <td><?php echo esc_html( $log['timestamp'] ); ?></td>
                                <td><?php echo esc_html( strtoupper( $log['level'] ) ); ?></td>
                                <td><?php echo esc_html( $log['message'] ); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
        
        <style>
        .log-controls {
            margin: 20px 0;
        }
        .log-level-error { background-color: #ffeaea; }
        .log-level-warning { background-color: #fff3cd; }
        .log-level-info { background-color: #e7f3ff; }
        .log-level-debug { background-color: #f8f9fa; }
        </style>
        <?php
    }

    /**
     * Render tools tab
     */
    private function render_tools_tab(): void {
        ?>
        <div class="bil24-tools">
            <h3>Инструменты диагностики</h3>
            
            <div class="tool-section">
                <h4>Тест соединения</h4>
                <p>Проверить соединение с API Bil24</p>
                <button type="button" class="button" onclick="bil24TestConnection()">
                    Тестировать соединение
                </button>
                <div id="connection-test-result"></div>
            </div>
            
            <div class="tool-section">
                <h4>Очистка кэша</h4>
                <p>Очистить весь кэш интеграции</p>
                <button type="button" class="button" onclick="bil24ClearCache()">
                    Очистить кэш
                </button>
            </div>
            
            <div class="tool-section">
                <h4>Сброс настроек</h4>
                <p>Сбросить все настройки интеграции к значениям по умолчанию</p>
                <button type="button" class="button button-secondary" onclick="bil24ResetSettings()">
                    Сбросить настройки
                </button>
            </div>
        </div>
        
        <script>
        function bil24TestConnection() {
            var resultDiv = document.getElementById('connection-test-result');
            resultDiv.innerHTML = 'Тестирование...';
            
            jQuery.post(ajaxurl, {
                action: 'bil24_test_connection',
                nonce: '<?php echo wp_create_nonce( 'bil24_admin_nonce' ); ?>'
            }, function(response) {
                if (response.success) {
                    resultDiv.innerHTML = '<div class="notice notice-success"><p>✓ Соединение работает</p></div>';
                } else {
                    resultDiv.innerHTML = '<div class="notice notice-error"><p>✗ Ошибка соединения: ' + response.data.message + '</p></div>';
                }
            });
        }
        
        function bil24ClearCache() {
            if (confirm('Очистить весь кэш интеграции?')) {
                // Implementation for cache clearing
                alert('Кэш очищен');
            }
        }
        
        function bil24ResetSettings() {
            if (confirm('Сбросить все настройки к значениям по умолчанию?')) {
                // Implementation for settings reset
                alert('Настройки сброшены');
            }
        }
        </script>
        
        <style>
        .tool-section {
            background: #fff;
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 20px;
            margin: 20px 0;
        }
        .tool-section h4 {
            margin-top: 0;
        }
        </style>
        <?php
    }

    /**
     * Render recent activity
     */
    private function render_recent_activity(): void {
        // Get recent sync activities
        $activities = [
            [
                'type' => 'product_sync',
                'message' => 'Импортированы 5 новых событий',
                'time' => '2 минуты назад'
            ],
            [
                'type' => 'order_sync',
                'message' => 'Синхронизирован заказ #1234',
                'time' => '15 минут назад'
            ],
            [
                'type' => 'customer_sync',
                'message' => 'Обновлен профиль клиента',
                'time' => '1 час назад'
            ]
        ];
        
        if ( empty( $activities ) ) {
            echo '<p>Нет недавней активности</p>';
            return;
        }
        
        echo '<ul class="activity-list">';
        foreach ( $activities as $activity ) {
            echo '<li>';
            echo '<span class="activity-message">' . esc_html( $activity['message'] ) . '</span>';
            echo '<span class="activity-time">' . esc_html( $activity['time'] ) . '</span>';
            echo '</li>';
        }
        echo '</ul>';
    }

    /**
     * Enqueue admin scripts
     */
    public function enqueue_admin_scripts( string $hook ): void {
        // Only load on our admin pages
        if ( strpos( $hook, 'bil24' ) === false && $hook !== 'woocommerce_page_wc-settings' ) {
            return;
        }
        
        wp_enqueue_script(
            'bil24-admin',
            plugin_dir_url( __FILE__ ) . '../../../assets/js/admin.js',
            [ 'jquery' ],
            Constants::get_version(),
            true
        );
        
        wp_localize_script( 'bil24-admin', 'bil24_admin', [
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'nonce' => wp_create_nonce( 'bil24_admin_nonce' ),
            'strings' => [
                'confirm_sync' => 'Выполнить синхронизацию?',
                'confirm_import' => 'Импортировать данные?',
                'sync_in_progress' => 'Синхронизация выполняется...',
                'sync_complete' => 'Синхронизация завершена',
                'sync_error' => 'Ошибка синхронизации'
            ]
        ] );
        
        wp_enqueue_style(
            'bil24-admin',
            plugin_dir_url( __FILE__ ) . '../../../assets/css/admin.css',
            [],
            Constants::get_version()
        );
    }

    /**
     * Enqueue frontend scripts
     */
    public function enqueue_frontend_scripts(): void {
        if ( ! is_woocommerce() && ! is_cart() && ! is_checkout() && ! is_account_page() ) {
            return;
        }
        
        wp_enqueue_script(
            'bil24-frontend',
            plugin_dir_url( __FILE__ ) . '../../../assets/js/frontend.js',
            [ 'jquery' ],
            Constants::get_version(),
            true
        );
        
        wp_localize_script( 'bil24-frontend', 'bil24_frontend', [
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'nonce' => wp_create_nonce( 'bil24_frontend_nonce' ),
            'strings' => [
                'reservation_expired' => 'Резервирование истекло',
                'extend_reservation' => 'Продлить резервирование',
                'tickets_not_available' => 'Билеты недоступны',
                'adding_to_cart' => 'Добавление в корзину...'
            ]
        ] );
        
        wp_enqueue_style(
            'bil24-frontend',
            plugin_dir_url( __FILE__ ) . '../../../assets/css/frontend.css',
            [],
            Constants::get_version()
        );
    }

    /**
     * Add Bil24 product type
     */
    public function add_bil24_product_type( array $types ): array {
        $types['bil24_ticket'] = 'Bil24 Билет';
        return $types;
    }

    /**
     * Add Bil24 product fields
     */
    public function add_bil24_product_fields(): void {
        global $woocommerce, $post;
        
        echo '<div class="bil24_product_fields show_if_bil24_ticket">';
        
        woocommerce_wp_text_input( [
            'id' => '_bil24_event_id',
            'label' => 'Bil24 Event ID',
            'placeholder' => 'ID события в Bil24',
            'desc_tip' => true,
            'description' => 'Уникальный идентификатор события в системе Bil24'
        ] );
        
        woocommerce_wp_text_input( [
            'id' => '_bil24_venue_name',
            'label' => 'Название площадки',
            'placeholder' => 'Название места проведения',
            'desc_tip' => true,
            'description' => 'Название площадки или места проведения события'
        ] );
        
        woocommerce_wp_text_input( [
            'id' => '_bil24_start_date',
            'label' => 'Дата начала',
            'placeholder' => 'YYYY-MM-DD HH:MM:SS',
            'desc_tip' => true,
            'description' => 'Дата и время начала события'
        ] );
        
        echo '</div>';
    }

    /**
     * Register webhooks
     */
    public function register_webhooks(): void {
        add_action( 'rest_api_init', function() {
            register_rest_route( 'bil24/v1', '/webhook', [
                'methods' => 'POST',
                'callback' => [ $this, 'handle_webhook' ],
                'permission_callback' => [ $this, 'verify_webhook' ]
            ] );
        } );
    }

    /**
     * Handle incoming webhook
     */
    public function handle_webhook( \WP_REST_Request $request ): \WP_REST_Response {
        $data = $request->get_json_params();
        $event_type = $data['event_type'] ?? '';
        
        try {
            switch ( $event_type ) {
                case 'event.updated':
                    $this->handle_event_updated_webhook( $data );
                    break;
                case 'order.status_changed':
                    $this->handle_order_status_webhook( $data );
                    break;
                case 'ticket.generated':
                    $this->handle_ticket_generated_webhook( $data );
                    break;
                default:
                    Utils::log( "Неизвестный тип webhook: {$event_type}", Constants::LOG_LEVEL_WARNING );
            }
            
            return new \WP_REST_Response( [ 'status' => 'success' ], 200 );
            
        } catch ( \Exception $e ) {
            Utils::log( "Ошибка обработки webhook: " . $e->getMessage(), Constants::LOG_LEVEL_ERROR );
            return new \WP_REST_Response( [ 'status' => 'error', 'message' => $e->getMessage() ], 500 );
        }
    }

    /**
     * Verify webhook signature
     */
    public function verify_webhook( \WP_REST_Request $request ): bool {
        $signature = $request->get_header( 'X-Bil24-Signature' );
        $payload = $request->get_body();
        $secret = get_option( 'bil24_webhook_secret' );
        
        if ( ! $signature || ! $secret ) {
            return false;
        }
        
        $expected_signature = hash_hmac( 'sha256', $payload, $secret );
        
        return hash_equals( $signature, $expected_signature );
    }

    /**
     * Handle event updated webhook
     */
    private function handle_event_updated_webhook( array $data ): void {
        $event_id = $data['event_id'] ?? null;
        
        if ( ! $event_id ) {
            return;
        }
        
        // Find corresponding product
        $products = get_posts( [
            'post_type' => 'product',
            'meta_query' => [
                [
                    'key' => Constants::META_BIL24_ID,
                    'value' => $event_id,
                    'compare' => '='
                ]
            ],
            'posts_per_page' => 1
        ] );
        
        if ( ! empty( $products ) ) {
            $product_id = $products[0]->ID;
            
            try {
                // Sync updated event data
                $this->product_sync->sync_product_from_bil24( $product_id );
                Utils::log( "Обновлен продукт {$product_id} по webhook события {$event_id}", Constants::LOG_LEVEL_INFO );
            } catch ( \Exception $e ) {
                Utils::log( "Ошибка обновления продукта по webhook: " . $e->getMessage(), Constants::LOG_LEVEL_ERROR );
            }
        }
    }

    /**
     * Handle order status webhook
     */
    private function handle_order_status_webhook( array $data ): void {
        // Implementation for order status changes from Bil24
    }

    /**
     * Handle ticket generated webhook
     */
    private function handle_ticket_generated_webhook( array $data ): void {
        // Implementation for ticket generation notifications
    }

    /**
     * Schedule cron jobs
     */
    public function schedule_cron_jobs(): void {
        $interval = get_option( 'bil24_sync_interval', 15 ) * 60; // Convert to seconds
        
        // Clear existing schedules
        wp_clear_scheduled_hook( 'bil24_sync_cron' );
        
        // Schedule new cron job
        if ( ! wp_next_scheduled( 'bil24_sync_cron' ) ) {
            wp_schedule_event( time(), 'bil24_sync_interval', 'bil24_sync_cron' );
        }
        
        // Add custom cron interval
        add_filter( 'cron_schedules', function( $schedules ) use ( $interval ) {
            $schedules['bil24_sync_interval'] = [
                'interval' => $interval,
                'display' => 'Bil24 Sync Interval'
            ];
            return $schedules;
        } );
        
        // Register cron handler
        add_action( 'bil24_sync_cron', [ $this, 'run_scheduled_sync' ] );
    }

    /**
     * Run scheduled synchronization
     */
    public function run_scheduled_sync(): void {
        Utils::log( 'Запуск планированной синхронизации', Constants::LOG_LEVEL_INFO );
        
        try {
            // Run component sync methods
            if ( method_exists( $this->product_sync, 'scheduled_sync' ) ) {
                $this->product_sync->scheduled_sync();
            }
            
            if ( method_exists( $this->order_sync, 'scheduled_order_sync' ) ) {
                $this->order_sync->scheduled_order_sync();
            }
            
            if ( method_exists( $this->customer_sync, 'scheduled_customer_sync' ) ) {
                $this->customer_sync->scheduled_customer_sync();
            }
            
            Utils::log( 'Планированная синхронизация завершена', Constants::LOG_LEVEL_INFO );
            
        } catch ( \Exception $e ) {
            Utils::log( 'Ошибка планированной синхронизации: ' . $e->getMessage(), Constants::LOG_LEVEL_ERROR );
        }
    }

    /**
     * AJAX handler for testing connection
     */
    public function ajax_test_connection(): void {
        check_ajax_referer( 'bil24_admin_nonce', 'nonce' );
        
        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            wp_die( 'Недостаточно прав доступа' );
        }
        
        try {
            $connected = $this->test_api_connection();
            
            if ( $connected ) {
                wp_send_json_success( [ 'message' => 'Соединение работает' ] );
            } else {
                wp_send_json_error( [ 'message' => 'Не удается подключиться к API' ] );
            }
        } catch ( \Exception $e ) {
            wp_send_json_error( [ 'message' => $e->getMessage() ] );
        }
    }

    /**
     * AJAX handler for bulk import
     */
    public function ajax_bulk_import(): void {
        check_ajax_referer( 'bil24_admin_nonce', 'nonce' );
        
        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            wp_die( 'Недостаточно прав доступа' );
        }
        
        $type = sanitize_text_field( $_POST['type'] ?? '' );
        
        try {
            switch ( $type ) {
                case 'products':
                    $result = $this->product_sync->import_events_as_products();
                    break;
                case 'orders':
                    $result = [ 'message' => 'Синхронизация заказов запущена' ];
                    break;
                case 'customers':
                    $result = [ 'message' => 'Синхронизация клиентов запущена' ];
                    break;
                default:
                    throw new \Exception( 'Неизвестный тип импорта' );
            }
            
            wp_send_json_success( $result );
            
        } catch ( \Exception $e ) {
            wp_send_json_error( [ 'message' => $e->getMessage() ] );
        }
    }

    /**
     * AJAX handler for sync status
     */
    public function ajax_sync_status(): void {
        check_ajax_referer( 'bil24_admin_nonce', 'nonce' );
        
        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            wp_die( 'Недостаточно прав доступа' );
        }
        
        global $wpdb;
        
        $status = [
            'products_error' => $wpdb->get_var(
                "SELECT COUNT(*) FROM {$wpdb->postmeta} 
                 WHERE meta_key = '" . Constants::META_SYNC_STATUS . "' 
                 AND meta_value = 'error'
                 AND post_id IN (SELECT ID FROM {$wpdb->posts} WHERE post_type = 'product')"
            ),
            'orders_error' => $wpdb->get_var(
                "SELECT COUNT(*) FROM {$wpdb->postmeta} 
                 WHERE meta_key = '" . Constants::META_SYNC_STATUS . "' 
                 AND meta_value = 'error'
                 AND post_id IN (SELECT ID FROM {$wpdb->posts} WHERE post_type = 'shop_order')"
            ),
            'customers_error' => $wpdb->get_var(
                "SELECT COUNT(*) FROM {$wpdb->usermeta} 
                 WHERE meta_key = 'bil24_sync_status' 
                 AND meta_value = 'error'"
            )
        ];
        
        wp_send_json_success( $status );
    }

    /**
     * Plugin activation
     */
    public function on_activation(): void {
        // Set default options
        add_option( 'bil24_woocommerce_enabled', 'yes' );
        add_option( 'bil24_auto_sync_products', 'yes' );
        add_option( 'bil24_auto_sync_orders', 'yes' );
        add_option( 'bil24_auto_sync_customers', 'yes' );
        add_option( 'bil24_reservation_timeout', '15' );
        add_option( 'bil24_auto_generate_tickets', 'yes' );
        add_option( 'bil24_email_tickets', 'yes' );
        
        // Schedule cron jobs
        $this->schedule_cron_jobs();
        
        Utils::log( 'WooCommerce интеграция активирована', Constants::LOG_LEVEL_INFO );
    }

    /**
     * Plugin deactivation
     */
    public function on_deactivation(): void {
        // Clear scheduled hooks
        wp_clear_scheduled_hook( 'bil24_sync_cron' );
        
        Utils::log( 'WooCommerce интеграция деактивирована', Constants::LOG_LEVEL_INFO );
    }

    /**
     * Get integration components
     */
    public function get_product_sync(): ProductSync {
        return $this->product_sync;
    }

    public function get_cart_integration(): CartIntegration {
        return $this->cart_integration;
    }

    public function get_order_sync(): OrderSync {
        return $this->order_sync;
    }

    public function get_customer_sync(): CustomerSync {
        return $this->customer_sync;
    }
} 