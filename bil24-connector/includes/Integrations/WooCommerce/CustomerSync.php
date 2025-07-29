<?php
namespace Bil24\Integrations\WooCommerce;

use Bil24\Api\Endpoints;
use Bil24\Constants;
use Bil24\Utils;

defined( 'ABSPATH' ) || exit;

/**
 * Customer Synchronization between WooCommerce and Bil24
 * 
 * Handles customer profile creation, updates, and preference synchronization
 * Manages customer data mapping and privacy compliance
 * 
 * @package Bil24Connector
 * @since 0.1.0
 */
class CustomerSync {

    /**
     * API Endpoints instance
     */
    private Endpoints $api;

    /**
     * ACF field mappings for customer data
     */
    private const CUSTOMER_ACF_MAPPINGS = [
        'bil24_customer_id' => 'bil24_customer_id',
        'preferred_venues' => 'preferred_venues',
        'event_preferences' => 'event_preferences',
        'communication_preferences' => 'communication_preferences',
        'loyalty_points' => 'bil24_loyalty_points',
        'vip_status' => 'bil24_vip_status',
        'last_purchase_date' => 'bil24_last_purchase',
        'total_purchases' => 'bil24_total_purchases',
        'preferred_seats' => 'bil24_preferred_seats'
    ];

    /**
     * Constructor
     */
    public function __construct( ?Endpoints $api = null ) {
        $this->api = $api ?? new Endpoints();
        $this->register_hooks();
    }

    /**
     * Register WordPress/WooCommerce hooks
     */
    private function register_hooks(): void {
        // Customer registration/update hooks
        add_action( 'user_register', [ $this, 'on_user_register' ], 10, 1 );
        add_action( 'profile_update', [ $this, 'on_profile_update' ], 10, 2 );
        add_action( 'delete_user', [ $this, 'on_user_delete' ], 10, 1 );
        
        // WooCommerce customer hooks
        add_action( 'woocommerce_created_customer', [ $this, 'on_wc_customer_created' ], 10, 3 );
        add_action( 'woocommerce_customer_save_address', [ $this, 'on_customer_address_update' ], 10, 2 );
        add_action( 'woocommerce_save_account_details', [ $this, 'on_account_details_save' ], 10, 1 );
        
        // Order completion hooks for customer history
        add_action( 'woocommerce_order_status_completed', [ $this, 'update_customer_purchase_history' ], 10, 1 );
        
        // Admin customer profile fields
        add_action( 'show_user_profile', [ $this, 'add_customer_profile_fields' ] );
        add_action( 'edit_user_profile', [ $this, 'add_customer_profile_fields' ] );
        add_action( 'personal_options_update', [ $this, 'save_customer_profile_fields' ] );
        add_action( 'edit_user_profile_update', [ $this, 'save_customer_profile_fields' ] );
        
        // My Account customizations
        add_action( 'woocommerce_edit_account_form', [ $this, 'add_account_form_fields' ] );
        add_action( 'woocommerce_save_account_details_errors', [ $this, 'validate_account_form_fields' ], 10, 1 );
        
        // AJAX handlers
        add_action( 'wp_ajax_bil24_sync_customer', [ $this, 'ajax_sync_customer' ] );
        add_action( 'wp_ajax_bil24_get_customer_preferences', [ $this, 'ajax_get_customer_preferences' ] );
        add_action( 'wp_ajax_bil24_update_preferences', [ $this, 'ajax_update_preferences' ] );
        
        // Privacy and GDPR compliance
        add_filter( 'wp_privacy_personal_data_exporters', [ $this, 'register_data_exporter' ] );
        add_filter( 'wp_privacy_personal_data_erasers', [ $this, 'register_data_eraser' ] );
        
        // Scheduled sync
        add_action( Constants::HOOK_SYNC_CUSTOMERS, [ $this, 'scheduled_customer_sync' ] );
    }

    /**
     * Handle user registration
     */
    public function on_user_register( int $user_id ): void {
        $auto_sync = get_option( 'bil24_auto_sync_customers', true );
        
        if ( ! $auto_sync ) {
            return;
        }
        
        try {
            $this->sync_customer_to_bil24( $user_id );
        } catch ( \Exception $e ) {
            Utils::log( "Ошибка синхронизации нового клиента {$user_id}: " . $e->getMessage(), Constants::LOG_LEVEL_ERROR );
        }
    }

    /**
     * Handle WooCommerce customer creation
     */
    public function on_wc_customer_created( int $customer_id, array $new_customer_data, bool $password_generated ): void {
        // Same as user registration
        $this->on_user_register( $customer_id );
    }

    /**
     * Handle profile updates
     */
    public function on_profile_update( int $user_id, \WP_User $old_user_data ): void {
        $bil24_customer_id = get_user_meta( $user_id, 'bil24_customer_id', true );
        
        if ( ! $bil24_customer_id ) {
            return;
        }
        
        try {
            $this->sync_customer_to_bil24( $user_id );
        } catch ( \Exception $e ) {
            Utils::log( "Ошибка синхронизации обновленного клиента {$user_id}: " . $e->getMessage(), Constants::LOG_LEVEL_ERROR );
        }
    }

    /**
     * Sync customer to Bil24
     */
    public function sync_customer_to_bil24( int $user_id ): array {
        $user = get_user_by( 'id', $user_id );
        
        if ( ! $user ) {
            throw new \Exception( "Пользователь {$user_id} не найден" );
        }
        
        $customer_data = $this->convert_wp_user_to_bil24( $user );
        $bil24_customer_id = get_user_meta( $user_id, 'bil24_customer_id', true );
        
        try {
            if ( $bil24_customer_id ) {
                // Update existing customer
                $response = $this->api->update_customer( $bil24_customer_id, $customer_data );
            } else {
                // Create new customer
                $response = $this->api->create_customer( $customer_data );
                
                if ( ! empty( $response['customer_id'] ) ) {
                    update_user_meta( $user_id, 'bil24_customer_id', $response['customer_id'] );
                }
            }
            
            // Update sync metadata
            update_user_meta( $user_id, 'bil24_sync_status', 'synced' );
            update_user_meta( $user_id, 'bil24_last_sync', time() );
            update_user_meta( $user_id, 'bil24_customer_data', $response );
            
            $result = [
                'success' => true,
                'message' => 'Клиент успешно синхронизирован с Bil24',
                'bil24_customer_id' => $response['customer_id'] ?? $bil24_customer_id,
                'synced_at' => current_time( 'mysql' )
            ];
            
            Utils::log( "Клиент {$user_id} синхронизирован с Bil24", Constants::LOG_LEVEL_INFO );
            
            return $result;
            
        } catch ( \Exception $e ) {
            update_user_meta( $user_id, 'bil24_sync_status', 'error' );
            update_user_meta( $user_id, 'bil24_sync_error', $e->getMessage() );
            
            throw $e;
        }
    }

    /**
     * Convert WordPress user to Bil24 customer format
     */
    private function convert_wp_user_to_bil24( \WP_User $user ): array {
        $customer_data = [
            'wp_user_id' => $user->ID,
            'email' => $user->user_email,
            'first_name' => get_user_meta( $user->ID, 'first_name', true ),
            'last_name' => get_user_meta( $user->ID, 'last_name', true ),
            'display_name' => $user->display_name,
            'username' => $user->user_login,
            'registration_date' => $user->user_registered,
            'status' => 'active'
        ];
        
        // Add WooCommerce customer data if available
        if ( function_exists( 'wc_get_customer' ) ) {
            $wc_customer = new \WC_Customer( $user->ID );
            
            $customer_data = array_merge( $customer_data, [
                'phone' => $wc_customer->get_billing_phone(),
                'billing_address' => [
                    'first_name' => $wc_customer->get_billing_first_name(),
                    'last_name' => $wc_customer->get_billing_last_name(),
                    'company' => $wc_customer->get_billing_company(),
                    'address_1' => $wc_customer->get_billing_address_1(),
                    'address_2' => $wc_customer->get_billing_address_2(),
                    'city' => $wc_customer->get_billing_city(),
                    'state' => $wc_customer->get_billing_state(),
                    'postcode' => $wc_customer->get_billing_postcode(),
                    'country' => $wc_customer->get_billing_country()
                ],
                'shipping_address' => [
                    'first_name' => $wc_customer->get_shipping_first_name(),
                    'last_name' => $wc_customer->get_shipping_last_name(),
                    'company' => $wc_customer->get_shipping_company(),
                    'address_1' => $wc_customer->get_shipping_address_1(),
                    'address_2' => $wc_customer->get_shipping_address_2(),
                    'city' => $wc_customer->get_shipping_city(),
                    'state' => $wc_customer->get_shipping_state(),
                    'postcode' => $wc_customer->get_shipping_postcode(),
                    'country' => $wc_customer->get_shipping_country()
                ]
            ] );
        }
        
        // Add ACF custom fields if available
        if ( function_exists( 'get_field' ) ) {
            $customer_data['preferences'] = $this->get_customer_preferences( $user->ID );
        }
        
        // Add purchase history
        $customer_data['purchase_history'] = $this->get_customer_purchase_history( $user->ID );
        
        return $customer_data;
    }

    /**
     * Get customer preferences from ACF fields
     */
    private function get_customer_preferences( int $user_id ): array {
        $preferences = [];
        
        foreach ( self::CUSTOMER_ACF_MAPPINGS as $key => $field_name ) {
            if ( function_exists( 'get_field' ) ) {
                $value = get_field( $field_name, 'user_' . $user_id );
                if ( $value !== false && $value !== '' ) {
                    $preferences[ $key ] = $value;
                }
            }
        }
        
        // Add standard user meta
        $preferences['preferred_venues'] = get_user_meta( $user_id, 'bil24_preferred_venues', true );
        $preferences['event_preferences'] = get_user_meta( $user_id, 'bil24_event_preferences', true );
        $preferences['communication_preferences'] = get_user_meta( $user_id, 'bil24_communication_prefs', true );
        
        return array_filter( $preferences );
    }

    /**
     * Get customer purchase history
     */
    private function get_customer_purchase_history( int $user_id ): array {
        if ( ! function_exists( 'wc_get_orders' ) ) {
            return [];
        }
        
        $orders = wc_get_orders( [
            'customer' => $user_id,
            'status' => [ 'completed' ],
            'limit' => 50,
            'orderby' => 'date',
            'order' => 'DESC'
        ] );
        
        $history = [
            'total_orders' => 0,
            'total_spent' => 0,
            'last_order_date' => null,
            'favorite_venues' => [],
            'favorite_event_types' => [],
            'orders' => []
        ];
        
        foreach ( $orders as $order ) {
            $order_data = [
                'order_id' => $order->get_id(),
                'order_number' => $order->get_order_number(),
                'date' => $order->get_date_created()->format( 'Y-m-d H:i:s' ),
                'total' => $order->get_total(),
                'currency' => $order->get_currency(),
                'items' => []
            ];
            
            // Get Bil24 items only
            foreach ( $order->get_items() as $item ) {
                $product_id = $item->get_product_id();
                $bil24_id = get_post_meta( $product_id, Constants::META_BIL24_ID, true );
                
                if ( $bil24_id ) {
                    $order_data['items'][] = [
                        'event_id' => $bil24_id,
                        'product_name' => $item->get_name(),
                        'quantity' => $item->get_quantity(),
                        'total' => $item->get_total()
                    ];
                    
                    // Track venue and event type preferences
                    $venue_name = get_post_meta( $product_id, '_bil24_venue_name', true );
                    $event_type = get_post_meta( $product_id, '_bil24_event_type', true );
                    
                    if ( $venue_name ) {
                        $history['favorite_venues'][ $venue_name ] = ( $history['favorite_venues'][ $venue_name ] ?? 0 ) + $item->get_quantity();
                    }
                    
                    if ( $event_type ) {
                        $history['favorite_event_types'][ $event_type ] = ( $history['favorite_event_types'][ $event_type ] ?? 0 ) + $item->get_quantity();
                    }
                }
            }
            
            if ( ! empty( $order_data['items'] ) ) {
                $history['orders'][] = $order_data;
                $history['total_orders']++;
                $history['total_spent'] += $order->get_total();
                
                if ( ! $history['last_order_date'] ) {
                    $history['last_order_date'] = $order_data['date'];
                }
            }
        }
        
        // Sort preferences by frequency
        arsort( $history['favorite_venues'] );
        arsort( $history['favorite_event_types'] );
        
        return $history;
    }

    /**
     * Update customer purchase history after order completion
     */
    public function update_customer_purchase_history( int $order_id ): void {
        $order = wc_get_order( $order_id );
        
        if ( ! $order ) {
            return;
        }
        
        $customer_id = $order->get_customer_id();
        
        if ( ! $customer_id ) {
            return;
        }
        
        $bil24_customer_id = get_user_meta( $customer_id, 'bil24_customer_id', true );
        
        if ( ! $bil24_customer_id ) {
            return;
        }
        
        try {
            // Update customer stats in Bil24
            $purchase_data = [
                'last_purchase_date' => current_time( 'mysql' ),
                'total_orders' => $this->get_customer_total_orders( $customer_id ),
                'total_spent' => $this->get_customer_total_spent( $customer_id ),
                'purchase_history' => $this->get_customer_purchase_history( $customer_id )
            ];
            
            $this->api->update_customer_stats( $bil24_customer_id, $purchase_data );
            
            // Update local cache
            update_user_meta( $customer_id, 'bil24_last_purchase_date', current_time( 'mysql' ) );
            update_user_meta( $customer_id, 'bil24_total_orders', $purchase_data['total_orders'] );
            update_user_meta( $customer_id, 'bil24_total_spent', $purchase_data['total_spent'] );
            
        } catch ( \Exception $e ) {
            Utils::log( "Ошибка обновления истории покупок клиента {$customer_id}: " . $e->getMessage(), Constants::LOG_LEVEL_ERROR );
        }
    }

    /**
     * Get customer total orders count
     */
    private function get_customer_total_orders( int $customer_id ): int {
        if ( ! function_exists( 'wc_get_customer_order_count' ) ) {
            return 0;
        }
        
        return wc_get_customer_order_count( $customer_id );
    }

    /**
     * Get customer total spent amount
     */
    private function get_customer_total_spent( int $customer_id ): float {
        if ( ! function_exists( 'wc_get_customer_total_spent' ) ) {
            return 0.0;
        }
        
        return wc_get_customer_total_spent( $customer_id );
    }

    /**
     * Add customer profile fields to admin
     */
    public function add_customer_profile_fields( \WP_User $user ): void {
        if ( ! current_user_can( 'edit_users' ) ) {
            return;
        }
        
        $bil24_customer_id = get_user_meta( $user->ID, 'bil24_customer_id', true );
        $sync_status = get_user_meta( $user->ID, 'bil24_sync_status', true );
        $last_sync = get_user_meta( $user->ID, 'bil24_last_sync', true );
        $total_orders = get_user_meta( $user->ID, 'bil24_total_orders', true );
        $total_spent = get_user_meta( $user->ID, 'bil24_total_spent', true );
        
        ?>
        <h3>Bil24 Customer Information</h3>
        <table class="form-table">
            <tr>
                <th>Bil24 Customer ID</th>
                <td>
                    <input type="text" name="bil24_customer_id" value="<?php echo esc_attr( $bil24_customer_id ); ?>" class="regular-text" />
                    <p class="description">ID клиента в системе Bil24</p>
                </td>
            </tr>
            <tr>
                <th>Статус синхронизации</th>
                <td>
                    <span class="bil24-sync-status status-<?php echo esc_attr( $sync_status ?: 'pending' ); ?>">
                        <?php echo esc_html( $sync_status ?: 'pending' ); ?>
                    </span>
                    <?php if ( $last_sync ): ?>
                        <p class="description">Последняя синхронизация: <?php echo date( 'Y-m-d H:i:s', $last_sync ); ?></p>
                    <?php endif; ?>
                </td>
            </tr>
            <tr>
                <th>Статистика покупок</th>
                <td>
                    <p>Всего заказов: <strong><?php echo esc_html( $total_orders ?: '0' ); ?></strong></p>
                    <p>Общая сумма: <strong><?php echo wc_price( $total_spent ?: 0 ); ?></strong></p>
                </td>
            </tr>
        </table>
        
        <h4>Предпочтения клиента</h4>
        <table class="form-table">
            <tr>
                <th>Предпочитаемые площадки</th>
                <td>
                    <textarea name="bil24_preferred_venues" rows="3" cols="50"><?php echo esc_textarea( get_user_meta( $user->ID, 'bil24_preferred_venues', true ) ); ?></textarea>
                    <p class="description">Список предпочитаемых площадок (по одной на строку)</p>
                </td>
            </tr>
            <tr>
                <th>Типы событий</th>
                <td>
                    <textarea name="bil24_event_preferences" rows="3" cols="50"><?php echo esc_textarea( get_user_meta( $user->ID, 'bil24_event_preferences', true ) ); ?></textarea>
                    <p class="description">Предпочитаемые типы событий (по одному на строку)</p>
                </td>
            </tr>
            <tr>
                <th>Предпочтения уведомлений</th>
                <td>
                    <?php $comm_prefs = get_user_meta( $user->ID, 'bil24_communication_prefs', true ) ?: []; ?>
                    <label><input type="checkbox" name="bil24_communication_prefs[]" value="email" <?php checked( in_array( 'email', $comm_prefs ) ); ?> /> Email уведомления</label><br>
                    <label><input type="checkbox" name="bil24_communication_prefs[]" value="sms" <?php checked( in_array( 'sms', $comm_prefs ) ); ?> /> SMS уведомления</label><br>
                    <label><input type="checkbox" name="bil24_communication_prefs[]" value="push" <?php checked( in_array( 'push', $comm_prefs ) ); ?> /> Push уведомления</label>
                </td>
            </tr>
        </table>
        
        <p>
            <button type="button" class="button" onclick="bil24SyncCustomer(<?php echo $user->ID; ?>)">
                Синхронизировать с Bil24
            </button>
            <span id="bil24-customer-sync-result-<?php echo $user->ID; ?>"></span>
        </p>
        
        <script>
        function bil24SyncCustomer(userId) {
            var resultSpan = document.getElementById('bil24-customer-sync-result-' + userId);
            resultSpan.innerHTML = ' Синхронизация...';
            
            jQuery.post(ajaxurl, {
                action: 'bil24_sync_customer',
                user_id: userId,
                nonce: '<?php echo wp_create_nonce( 'bil24_admin_nonce' ); ?>'
            }, function(response) {
                if (response.success) {
                    resultSpan.innerHTML = ' <span style="color: green;">✓ ' + response.data.message + '</span>';
                } else {
                    resultSpan.innerHTML = ' <span style="color: red;">✗ ' + response.data.message + '</span>';
                }
            });
        }
        </script>
        
        <style>
        .bil24-sync-status {
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 11px;
            font-weight: bold;
            text-transform: uppercase;
        }
        .status-synced { background: #d4edda; color: #155724; }
        .status-pending { background: #fff3cd; color: #856404; }
        .status-error { background: #f8d7da; color: #721c24; }
        </style>
        <?php
    }

    /**
     * Save customer profile fields
     */
    public function save_customer_profile_fields( int $user_id ): void {
        if ( ! current_user_can( 'edit_users' ) ) {
            return;
        }
        
        if ( isset( $_POST['bil24_customer_id'] ) ) {
            update_user_meta( $user_id, 'bil24_customer_id', sanitize_text_field( $_POST['bil24_customer_id'] ) );
        }
        
        if ( isset( $_POST['bil24_preferred_venues'] ) ) {
            update_user_meta( $user_id, 'bil24_preferred_venues', sanitize_textarea_field( $_POST['bil24_preferred_venues'] ) );
        }
        
        if ( isset( $_POST['bil24_event_preferences'] ) ) {
            update_user_meta( $user_id, 'bil24_event_preferences', sanitize_textarea_field( $_POST['bil24_event_preferences'] ) );
        }
        
        if ( isset( $_POST['bil24_communication_prefs'] ) ) {
            $prefs = array_map( 'sanitize_text_field', $_POST['bil24_communication_prefs'] );
            update_user_meta( $user_id, 'bil24_communication_prefs', $prefs );
        } else {
            update_user_meta( $user_id, 'bil24_communication_prefs', [] );
        }
    }

    /**
     * Add account form fields to My Account page
     */
    public function add_account_form_fields(): void {
        $user_id = get_current_user_id();
        
        if ( ! $user_id ) {
            return;
        }
        
        $preferred_venues = get_user_meta( $user_id, 'bil24_preferred_venues', true );
        $event_preferences = get_user_meta( $user_id, 'bil24_event_preferences', true );
        $comm_prefs = get_user_meta( $user_id, 'bil24_communication_prefs', true ) ?: [];
        
        ?>
        <fieldset class="bil24-preferences">
            <legend>Предпочтения для билетов</legend>
            
            <p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
                <label for="bil24_preferred_venues">Предпочитаемые площадки</label>
                <textarea id="bil24_preferred_venues" name="bil24_preferred_venues" rows="3" placeholder="Укажите ваши любимые площадки..."><?php echo esc_textarea( $preferred_venues ); ?></textarea>
                <small>По одной площадке на строку</small>
            </p>
            
            <p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
                <label for="bil24_event_preferences">Типы событий</label>
                <textarea id="bil24_event_preferences" name="bil24_event_preferences" rows="3" placeholder="Концерты, театр, спорт..."><?php echo esc_textarea( $event_preferences ); ?></textarea>
                <small>По одному типу на строку</small>
            </p>
            
            <p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
                <label>Уведомления</label>
                <label class="checkbox">
                    <input type="checkbox" name="bil24_communication_prefs[]" value="email" <?php checked( in_array( 'email', $comm_prefs ) ); ?> />
                    Email уведомления о новых событиях
                </label>
                <label class="checkbox">
                    <input type="checkbox" name="bil24_communication_prefs[]" value="sms" <?php checked( in_array( 'sms', $comm_prefs ) ); ?> />
                    SMS уведомления
                </label>
            </p>
        </fieldset>
        
        <style>
        .bil24-preferences {
            margin: 20px 0;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        .bil24-preferences legend {
            padding: 0 10px;
            font-weight: bold;
        }
        .bil24-preferences .checkbox {
            display: block;
            margin-bottom: 5px;
        }
        .bil24-preferences .checkbox input {
            margin-right: 5px;
        }
        </style>
        <?php
    }

    /**
     * Validate account form fields
     */
    public function validate_account_form_fields( \WP_Error $errors ): void {
        // No specific validation needed for now
    }

    /**
     * Handle account details save
     */
    public function on_account_details_save( int $user_id ): void {
        if ( isset( $_POST['bil24_preferred_venues'] ) ) {
            update_user_meta( $user_id, 'bil24_preferred_venues', sanitize_textarea_field( $_POST['bil24_preferred_venues'] ) );
        }
        
        if ( isset( $_POST['bil24_event_preferences'] ) ) {
            update_user_meta( $user_id, 'bil24_event_preferences', sanitize_textarea_field( $_POST['bil24_event_preferences'] ) );
        }
        
        if ( isset( $_POST['bil24_communication_prefs'] ) ) {
            $prefs = array_map( 'sanitize_text_field', $_POST['bil24_communication_prefs'] );
            update_user_meta( $user_id, 'bil24_communication_prefs', $prefs );
        } else {
            update_user_meta( $user_id, 'bil24_communication_prefs', [] );
        }
        
        // Sync to Bil24 if connected
        $bil24_customer_id = get_user_meta( $user_id, 'bil24_customer_id', true );
        
        if ( $bil24_customer_id ) {
            try {
                $this->sync_customer_to_bil24( $user_id );
            } catch ( \Exception $e ) {
                Utils::log( "Ошибка синхронизации предпочтений клиента {$user_id}: " . $e->getMessage(), Constants::LOG_LEVEL_ERROR );
            }
        }
    }

    /**
     * AJAX handler for syncing customer
     */
    public function ajax_sync_customer(): void {
        check_ajax_referer( 'bil24_admin_nonce', 'nonce' );
        
        if ( ! current_user_can( 'edit_users' ) ) {
            wp_die( 'Недостаточно прав доступа' );
        }
        
        $user_id = intval( $_POST['user_id'] ?? 0 );
        
        try {
            $result = $this->sync_customer_to_bil24( $user_id );
            wp_send_json_success( $result );
        } catch ( \Exception $e ) {
            wp_send_json_error( [
                'message' => $e->getMessage()
            ] );
        }
    }

    /**
     * AJAX handler for getting customer preferences
     */
    public function ajax_get_customer_preferences(): void {
        $user_id = get_current_user_id();
        
        if ( ! $user_id ) {
            wp_send_json_error( [ 'message' => 'Пользователь не авторизован' ] );
        }
        
        $preferences = $this->get_customer_preferences( $user_id );
        
        wp_send_json_success( $preferences );
    }

    /**
     * Handle user deletion
     */
    public function on_user_delete( int $user_id ): void {
        $bil24_customer_id = get_user_meta( $user_id, 'bil24_customer_id', true );
        
        if ( ! $bil24_customer_id ) {
            return;
        }
        
        try {
            // Mark customer as deleted in Bil24 (don't actually delete for data integrity)
            $this->api->update_customer( $bil24_customer_id, [
                'status' => 'deleted',
                'deleted_at' => current_time( 'mysql' )
            ] );
            
            Utils::log( "Клиент {$bil24_customer_id} отмечен как удаленный в Bil24", Constants::LOG_LEVEL_INFO );
            
        } catch ( \Exception $e ) {
            Utils::log( "Ошибка отметки клиента как удаленного: " . $e->getMessage(), Constants::LOG_LEVEL_ERROR );
        }
    }

    /**
     * Scheduled customer synchronization
     */
    public function scheduled_customer_sync(): void {
        $auto_sync_enabled = get_option( 'bil24_auto_sync_customers', true );
        
        if ( ! $auto_sync_enabled ) {
            return;
        }
        
        // Get users that need sync (modified in last 24 hours)
        $users = get_users( [
            'meta_query' => [
                'relation' => 'OR',
                [
                    'key' => 'bil24_sync_status',
                    'value' => [ 'pending', 'error' ],
                    'compare' => 'IN'
                ],
                [
                    'key' => 'bil24_last_sync',
                    'value' => strtotime( '-24 hours' ),
                    'compare' => '<',
                    'type' => 'NUMERIC'
                ]
            ],
            'number' => 50
        ] );
        
        foreach ( $users as $user ) {
            try {
                $this->sync_customer_to_bil24( $user->ID );
                Utils::log( "Автосинхронизация клиента {$user->ID} выполнена", Constants::LOG_LEVEL_DEBUG );
            } catch ( \Exception $e ) {
                Utils::log( "Ошибка автосинхронизации клиента {$user->ID}: " . $e->getMessage(), Constants::LOG_LEVEL_ERROR );
            }
        }
    }

    /**
     * Register data exporter for GDPR compliance
     */
    public function register_data_exporter( array $exporters ): array {
        $exporters['bil24-customer-data'] = [
            'exporter_friendly_name' => 'Bil24 Customer Data',
            'callback' => [ $this, 'export_customer_data' ]
        ];
        
        return $exporters;
    }

    /**
     * Export customer data for GDPR
     */
    public function export_customer_data( string $email_address, int $page = 1 ): array {
        $user = get_user_by( 'email', $email_address );
        
        if ( ! $user ) {
            return [
                'data' => [],
                'done' => true
            ];
        }
        
        $bil24_customer_id = get_user_meta( $user->ID, 'bil24_customer_id', true );
        $export_items = [];
        
        if ( $bil24_customer_id ) {
            $preferences = $this->get_customer_preferences( $user->ID );
            $purchase_history = $this->get_customer_purchase_history( $user->ID );
            
            $export_items[] = [
                'group_id' => 'bil24-customer',
                'group_label' => 'Bil24 Customer Data',
                'item_id' => 'bil24-customer-' . $user->ID,
                'data' => [
                    [
                        'name' => 'Bil24 Customer ID',
                        'value' => $bil24_customer_id
                    ],
                    [
                        'name' => 'Preferred Venues',
                        'value' => $preferences['preferred_venues'] ?? ''
                    ],
                    [
                        'name' => 'Event Preferences',
                        'value' => $preferences['event_preferences'] ?? ''
                    ],
                    [
                        'name' => 'Communication Preferences',
                        'value' => implode( ', ', $preferences['communication_preferences'] ?? [] )
                    ],
                    [
                        'name' => 'Total Orders',
                        'value' => $purchase_history['total_orders'] ?? 0
                    ],
                    [
                        'name' => 'Total Spent',
                        'value' => $purchase_history['total_spent'] ?? 0
                    ]
                ]
            ];
        }
        
        return [
            'data' => $export_items,
            'done' => true
        ];
    }

    /**
     * Register data eraser for GDPR compliance
     */
    public function register_data_eraser( array $erasers ): array {
        $erasers['bil24-customer-data'] = [
            'eraser_friendly_name' => 'Bil24 Customer Data',
            'callback' => [ $this, 'erase_customer_data' ]
        ];
        
        return $erasers;
    }

    /**
     * Erase customer data for GDPR
     */
    public function erase_customer_data( string $email_address, int $page = 1 ): array {
        $user = get_user_by( 'email', $email_address );
        
        if ( ! $user ) {
            return [
                'items_removed' => false,
                'items_retained' => false,
                'messages' => [],
                'done' => true
            ];
        }
        
        $bil24_customer_id = get_user_meta( $user->ID, 'bil24_customer_id', true );
        $items_removed = false;
        $messages = [];
        
        if ( $bil24_customer_id ) {
            try {
                // Anonymize customer in Bil24
                $this->api->anonymize_customer( $bil24_customer_id );
                
                // Remove local data
                delete_user_meta( $user->ID, 'bil24_customer_id' );
                delete_user_meta( $user->ID, 'bil24_preferred_venues' );
                delete_user_meta( $user->ID, 'bil24_event_preferences' );
                delete_user_meta( $user->ID, 'bil24_communication_prefs' );
                delete_user_meta( $user->ID, 'bil24_sync_status' );
                delete_user_meta( $user->ID, 'bil24_last_sync' );
                delete_user_meta( $user->ID, 'bil24_customer_data' );
                
                $items_removed = true;
                $messages[] = 'Bil24 customer data removed.';
                
            } catch ( \Exception $e ) {
                $messages[] = 'Error removing Bil24 customer data: ' . $e->getMessage();
            }
        }
        
        return [
            'items_removed' => $items_removed,
            'items_retained' => false,
            'messages' => $messages,
            'done' => true
        ];
    }
} 