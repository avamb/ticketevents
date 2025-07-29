<?php
namespace Bil24\Integrations;

use Bil24\Api\Endpoints;
use Bil24\Constants;
use Bil24\Utils;

defined( 'ABSPATH' ) || exit;

/**
 * Order Synchronization between WordPress and Bil24
 * 
 * Handles order creation, status updates, payment integration, and ticket generation
 * 
 * @package Bil24Connector
 * @since 0.1.0
 */
class OrderSync {

    /**
     * API Endpoints instance
     */
    private Endpoints $api;

    /**
     * Order status mapping
     */
    private const STATUS_MAPPING = [
        'pending' => 'pending',
        'processing' => 'confirmed',
        'completed' => 'completed',
        'cancelled' => 'cancelled',
        'refunded' => 'refunded',
        'failed' => 'failed'
    ];

    /**
     * Constructor
     */
    public function __construct( ?Endpoints $api = null ) {
        $this->api = $api ?? new Endpoints();
        $this->register_hooks();
    }

    /**
     * Register WordPress hooks
     */
    private function register_hooks(): void {
        // WooCommerce order hooks
        add_action( 'woocommerce_new_order', [ $this, 'on_new_order' ], 10, 1 );
        add_action( 'woocommerce_order_status_changed', [ $this, 'on_order_status_changed' ], 10, 4 );
        add_action( 'woocommerce_payment_complete', [ $this, 'on_payment_complete' ], 10, 1 );
        
        // Generic WordPress order hooks (for non-WooCommerce orders)
        add_action( 'save_post_' . Constants::CPT_ORDER, [ $this, 'on_order_save' ], 10, 3 );
        add_action( 'before_delete_post', [ $this, 'on_order_delete' ], 10, 2 );
        
        // Scheduled sync hooks
        add_action( Constants::HOOK_SYNC_ORDERS, [ $this, 'scheduled_sync' ] );
        
        // Admin actions
        add_action( 'wp_ajax_bil24_sync_orders', [ $this, 'ajax_manual_sync' ] );
        add_action( 'wp_ajax_bil24_sync_single_order', [ $this, 'ajax_sync_single_order' ] );
        add_action( 'wp_ajax_bil24_generate_tickets', [ $this, 'ajax_generate_tickets' ] );
        
        // Meta boxes for order management
        add_action( 'add_meta_boxes', [ $this, 'add_order_meta_boxes' ] );
        add_action( 'save_post', [ $this, 'save_order_meta' ] );
        
        // Custom order actions
        add_filter( 'woocommerce_order_actions', [ $this, 'add_order_actions' ] );
        add_action( 'woocommerce_order_action_bil24_sync_order', [ $this, 'process_sync_order_action' ] );
        add_action( 'woocommerce_order_action_bil24_generate_tickets', [ $this, 'process_generate_tickets_action' ] );
    }

    /**
     * Add meta boxes for order management
     */
    public function add_order_meta_boxes(): void {
        // Add meta box for WooCommerce orders
        add_meta_box(
            'bil24_order_details',
            'Детали заказа Bil24',
            [ $this, 'render_order_meta_box' ],
            'shop_order',
            'side',
            'high'
        );
        
        // Add meta box for custom order post type
        add_meta_box(
            'bil24_order_details',
            'Детали заказа Bil24',
            [ $this, 'render_order_meta_box' ],
            Constants::CPT_ORDER,
            'normal',
            'high'
        );
    }

    /**
     * Render order meta box
     */
    public function render_order_meta_box( \WP_Post $post ): void {
        wp_nonce_field( 'bil24_order_meta', 'bil24_order_meta_nonce' );
        
        $bil24_order_id = get_post_meta( $post->ID, Constants::META_BIL24_ID, true );
        $sync_status = get_post_meta( $post->ID, Constants::META_SYNC_STATUS, true );
        $last_sync = get_post_meta( $post->ID, Constants::META_LAST_SYNC, true );
        $bil24_data = get_post_meta( $post->ID, Constants::META_BIL24_DATA, true );
        $tickets_generated = get_post_meta( $post->ID, '_bil24_tickets_generated', true );
        
        ?>
        <div class="bil24-order-info">
            <table class="form-table">
                <tr>
                    <th>Bil24 Order ID:</th>
                    <td><?php echo $bil24_order_id ? esc_html( $bil24_order_id ) : 'Не синхронизирован'; ?></td>
                </tr>
                <tr>
                    <th>Статус синхронизации:</th>
                    <td><?php echo esc_html( $sync_status ?: 'pending' ); ?></td>
                </tr>
                <tr>
                    <th>Последняя синхронизация:</th>
                    <td><?php echo $last_sync ? esc_html( date( 'Y-m-d H:i:s', $last_sync ) ) : 'Никогда'; ?></td>
                </tr>
                <tr>
                    <th>Билеты:</th>
                    <td><?php echo $tickets_generated ? 'Сгенерированы' : 'Не сгенерированы'; ?></td>
                </tr>
            </table>
            
            <div class="bil24-order-actions" style="margin-top: 15px;">
                <button type="button" class="button" onclick="syncOrderToBil24(<?php echo $post->ID; ?>)">
                    Синхронизировать в Bil24
                </button>
                <button type="button" class="button" onclick="syncOrderFromBil24(<?php echo $post->ID; ?>)">
                    Загрузить из Bil24
                </button>
                <?php if ( $bil24_order_id ): ?>
                <button type="button" class="button button-primary" onclick="generateTickets(<?php echo $post->ID; ?>)">
                    Сгенерировать билеты
                </button>
                <?php endif; ?>
                <div id="order-sync-result-<?php echo $post->ID; ?>"></div>
            </div>
            
            <?php if ( $bil24_data ): ?>
            <div class="bil24-raw-data" style="margin-top: 15px;">
                <h4>Данные Bil24:</h4>
                <textarea readonly style="width: 100%; height: 150px;"><?php echo esc_textarea( wp_json_encode( $bil24_data, JSON_PRETTY_PRINT ) ); ?></textarea>
            </div>
            <?php endif; ?>
        </div>

        <script>
        function syncOrderToBil24(postId) {
            performOrderAction(postId, 'bil24_sync_single_order', {direction: 'to_bil24'});
        }

        function syncOrderFromBil24(postId) {
            performOrderAction(postId, 'bil24_sync_single_order', {direction: 'from_bil24'});
        }

        function generateTickets(postId) {
            performOrderAction(postId, 'bil24_generate_tickets', {});
        }

        function performOrderAction(postId, action, extraData) {
            const resultDiv = document.getElementById('order-sync-result-' + postId);
            resultDiv.innerHTML = 'Выполняется...';
            
            const data = new FormData();
            data.append('action', action);
            data.append('post_id', postId);
            data.append('_ajax_nonce', '<?php echo wp_create_nonce( 'bil24_order_action' ); ?>');
            
            for (const [key, value] of Object.entries(extraData)) {
                data.append(key, value);
            }
            
            fetch(ajaxurl, {
                method: 'POST',
                body: data
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    resultDiv.innerHTML = '<div style="color: green;">✓ ' + data.data.message + '</div>';
                    setTimeout(() => location.reload(), 2000);
                } else {
                    resultDiv.innerHTML = '<div style="color: red;">✗ ' + data.data.message + '</div>';
                }
            })
            .catch(error => {
                resultDiv.innerHTML = '<div style="color: red;">✗ Ошибка: ' + error.message + '</div>';
            });
        }
        </script>
        <?php
    }

    /**
     * Save order meta data
     */
    public function save_order_meta( int $post_id ): void {
        if ( ! isset( $_POST['bil24_order_meta_nonce'] ) || ! wp_verify_nonce( $_POST['bil24_order_meta_nonce'], 'bil24_order_meta' ) ) {
            return;
        }

        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }

        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            return;
        }

        // Additional meta handling can be added here if needed
    }

    /**
     * Add custom order actions to WooCommerce
     */
    public function add_order_actions( array $actions ): array {
        $actions['bil24_sync_order'] = 'Синхронизировать с Bil24';
        $actions['bil24_generate_tickets'] = 'Сгенерировать билеты в Bil24';
        return $actions;
    }

    /**
     * Process sync order action
     */
    public function process_sync_order_action( \WC_Order $order ): void {
        try {
            $this->sync_order_to_bil24( $order->get_id() );
            $order->add_order_note( 'Заказ синхронизирован с Bil24' );
        } catch ( \Exception $e ) {
            $order->add_order_note( 'Ошибка синхронизации с Bil24: ' . $e->getMessage() );
        }
    }

    /**
     * Process generate tickets action
     */
    public function process_generate_tickets_action( \WC_Order $order ): void {
        try {
            $this->generate_tickets( $order->get_id() );
            $order->add_order_note( 'Билеты сгенерированы в Bil24' );
        } catch ( \Exception $e ) {
            $order->add_order_note( 'Ошибка генерации билетов: ' . $e->getMessage() );
        }
    }

    /**
     * Handle new WooCommerce order
     */
    public function on_new_order( int $order_id ): void {
        // Auto-sync new orders if enabled
        $auto_sync = get_option( 'bil24_auto_sync_orders', true );
        
        if ( $auto_sync ) {
            try {
                $this->sync_order_to_bil24( $order_id );
            } catch ( \Exception $e ) {
                Utils::log( "Auto sync failed for new order {$order_id}: " . $e->getMessage(), Constants::LOG_LEVEL_ERROR );
            }
        }
    }

    /**
     * Handle WooCommerce order status change
     */
    public function on_order_status_changed( int $order_id, string $from, string $to, \WC_Order $order ): void {
        try {
            $this->sync_order_status_to_bil24( $order_id, $to );
        } catch ( \Exception $e ) {
            Utils::log( "Status sync failed for order {$order_id}: " . $e->getMessage(), Constants::LOG_LEVEL_ERROR );
        }
    }

    /**
     * Handle WooCommerce payment completion
     */
    public function on_payment_complete( int $order_id ): void {
        try {
            // Generate tickets when payment is complete
            $this->generate_tickets( $order_id );
        } catch ( \Exception $e ) {
            Utils::log( "Ticket generation failed for order {$order_id}: " . $e->getMessage(), Constants::LOG_LEVEL_ERROR );
        }
    }

    /**
     * Handle generic order save
     */
    public function on_order_save( int $post_id, \WP_Post $post, bool $update ): void {
        // Skip auto-drafts and revisions
        if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
            return;
        }

        // Skip if we're currently syncing to avoid loops
        if ( get_transient( "bil24_syncing_order_{$post_id}" ) ) {
            return;
        }

        try {
            $this->sync_order_to_bil24( $post_id );
        } catch ( \Exception $e ) {
            Utils::log( "Auto sync failed for order {$post_id}: " . $e->getMessage(), Constants::LOG_LEVEL_ERROR );
        }
    }

    /**
     * Handle order deletion
     */
    public function on_order_delete( int $post_id, \WP_Post $post ): void {
        if ( $post->post_type !== Constants::CPT_ORDER && $post->post_type !== 'shop_order' ) {
            return;
        }

        $bil24_order_id = get_post_meta( $post_id, Constants::META_BIL24_ID, true );
        
        if ( $bil24_order_id ) {
            try {
                $this->api->cancel_order( intval( $bil24_order_id ), 'Order deleted from WordPress' );
                Utils::log( "Order {$bil24_order_id} cancelled in Bil24", Constants::LOG_LEVEL_INFO );
            } catch ( \Exception $e ) {
                Utils::log( "Failed to cancel order {$bil24_order_id} in Bil24: " . $e->getMessage(), Constants::LOG_LEVEL_ERROR );
            }
        }
    }

    /**
     * Scheduled sync
     */
    public function scheduled_sync(): void {
        try {
            $result = $this->sync_orders( 'bidirectional' );
            Utils::log( 'Scheduled order sync completed: ' . wp_json_encode( $result ), Constants::LOG_LEVEL_INFO );
        } catch ( \Exception $e ) {
            Utils::log( 'Scheduled order sync failed: ' . $e->getMessage(), Constants::LOG_LEVEL_ERROR );
        }
    }

    /**
     * Manual sync trigger (AJAX)
     */
    public function ajax_manual_sync(): void {
        check_ajax_referer( 'bil24_sync_orders' );
        
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( 'Insufficient permissions' );
        }

        $direction = sanitize_text_field( $_POST['direction'] ?? 'bidirectional' );
        
        try {
            $result = $this->sync_orders( $direction );
            
            wp_send_json_success( [
                'message' => 'Синхронизация заказов завершена успешно',
                'stats' => $result
            ] );
        } catch ( \Exception $e ) {
            Utils::log( 'Manual order sync failed: ' . $e->getMessage(), Constants::LOG_LEVEL_ERROR );
            wp_send_json_error( [
                'message' => 'Ошибка синхронизации: ' . $e->getMessage()
            ] );
        }
    }

    /**
     * Manual sync for single order (AJAX)
     */
    public function ajax_sync_single_order(): void {
        check_ajax_referer( 'bil24_order_action' );
        
        if ( ! current_user_can( 'edit_posts' ) ) {
            wp_die( 'Insufficient permissions' );
        }

        $post_id = intval( $_POST['post_id'] ?? 0 );
        $direction = sanitize_text_field( $_POST['direction'] ?? 'to_bil24' );
        
        if ( ! $post_id ) {
            wp_send_json_error( [ 'message' => 'Invalid post ID' ] );
        }

        try {
            if ( $direction === 'to_bil24' ) {
                $result = $this->sync_order_to_bil24( $post_id );
            } else {
                $result = $this->sync_order_from_bil24( $post_id );
            }
            
            wp_send_json_success( [
                'message' => 'Заказ синхронизирован успешно',
                'result' => $result
            ] );
        } catch ( \Exception $e ) {
            Utils::log( "Single order sync failed for post {$post_id}: " . $e->getMessage(), Constants::LOG_LEVEL_ERROR );
            wp_send_json_error( [
                'message' => 'Ошибка синхронизации: ' . $e->getMessage()
            ] );
        }
    }

    /**
     * Generate tickets (AJAX)
     */
    public function ajax_generate_tickets(): void {
        check_ajax_referer( 'bil24_order_action' );
        
        if ( ! current_user_can( 'edit_posts' ) ) {
            wp_die( 'Insufficient permissions' );
        }

        $post_id = intval( $_POST['post_id'] ?? 0 );
        
        if ( ! $post_id ) {
            wp_send_json_error( [ 'message' => 'Invalid post ID' ] );
        }

        try {
            $tickets = $this->generate_tickets( $post_id );
            
            wp_send_json_success( [
                'message' => 'Билеты сгенерированы успешно',
                'tickets' => $tickets
            ] );
        } catch ( \Exception $e ) {
            Utils::log( "Ticket generation failed for order {$post_id}: " . $e->getMessage(), Constants::LOG_LEVEL_ERROR );
            wp_send_json_error( [
                'message' => 'Ошибка генерации билетов: ' . $e->getMessage()
            ] );
        }
    }

    /**
     * Main sync orders method
     */
    public function sync_orders( string $direction = 'bidirectional' ): array {
        $stats = [
            'to_bil24' => [ 'created' => 0, 'updated' => 0, 'errors' => 0 ],
            'from_bil24' => [ 'created' => 0, 'updated' => 0, 'errors' => 0 ]
        ];

        if ( $direction === 'to_bil24' || $direction === 'bidirectional' ) {
            $stats['to_bil24'] = $this->sync_all_orders_to_bil24();
        }

        if ( $direction === 'from_bil24' || $direction === 'bidirectional' ) {
            $stats['from_bil24'] = $this->sync_all_orders_from_bil24();
        }

        return $stats;
    }

    /**
     * Sync all orders to Bil24
     */
    private function sync_all_orders_to_bil24(): array {
        $stats = [ 'created' => 0, 'updated' => 0, 'errors' => 0 ];

        // Sync WooCommerce orders
        if ( Utils::is_woocommerce_active() ) {
            $wc_orders = wc_get_orders( [
                'limit' => -1,
                'meta_query' => [
                    'relation' => 'OR',
                    [
                        'key' => Constants::META_SYNC_STATUS,
                        'value' => 'pending',
                        'compare' => '='
                    ],
                    [
                        'key' => Constants::META_SYNC_STATUS,
                        'compare' => 'NOT EXISTS'
                    ]
                ]
            ] );

            foreach ( $wc_orders as $order ) {
                try {
                    $result = $this->sync_order_to_bil24( $order->get_id() );
                    
                    if ( $result['action'] === 'created' ) {
                        $stats['created']++;
                    } else {
                        $stats['updated']++;
                    }
                } catch ( \Exception $e ) {
                    $stats['errors']++;
                    Utils::log( "Failed to sync WC order {$order->get_id()} to Bil24: " . $e->getMessage(), Constants::LOG_LEVEL_ERROR );
                }
            }
        }

        // Sync custom order posts
        $custom_orders = get_posts( [
            'post_type' => Constants::CPT_ORDER,
            'posts_per_page' => -1,
            'meta_query' => [
                'relation' => 'OR',
                [
                    'key' => Constants::META_SYNC_STATUS,
                    'value' => 'pending',
                    'compare' => '='
                ],
                [
                    'key' => Constants::META_SYNC_STATUS,
                    'compare' => 'NOT EXISTS'
                ]
            ]
        ] );

        foreach ( $custom_orders as $order_post ) {
            try {
                $result = $this->sync_order_to_bil24( $order_post->ID );
                
                if ( $result['action'] === 'created' ) {
                    $stats['created']++;
                } else {
                    $stats['updated']++;
                }
            } catch ( \Exception $e ) {
                $stats['errors']++;
                Utils::log( "Failed to sync custom order {$order_post->ID} to Bil24: " . $e->getMessage(), Constants::LOG_LEVEL_ERROR );
            }
        }

        return $stats;
    }

    /**
     * Sync all orders from Bil24
     */
    private function sync_all_orders_from_bil24(): array {
        $stats = [ 'created' => 0, 'updated' => 0, 'errors' => 0 ];

        try {
            // Get orders modified since last sync
            $last_sync = get_option( 'bil24_last_order_sync_time', time() - DAY_IN_SECONDS );
            $bil24_orders = $this->api->get_orders_since( $last_sync );

            foreach ( $bil24_orders as $bil24_order ) {
                try {
                    $result = $this->sync_order_from_bil24_data( $bil24_order );
                    
                    if ( $result['action'] === 'created' ) {
                        $stats['created']++;
                    } else {
                        $stats['updated']++;
                    }
                } catch ( \Exception $e ) {
                    $stats['errors']++;
                    Utils::log( "Failed to sync order {$bil24_order['id']} from Bil24: " . $e->getMessage(), Constants::LOG_LEVEL_ERROR );
                }
            }

            // Update last sync time
            update_option( 'bil24_last_order_sync_time', time() );
        } catch ( \Exception $e ) {
            Utils::log( 'Failed to fetch orders from Bil24: ' . $e->getMessage(), Constants::LOG_LEVEL_ERROR );
            throw $e;
        }

        return $stats;
    }

    /**
     * Sync single order to Bil24
     */
    public function sync_order_to_bil24( int $order_id ): array {
        // Prevent sync loops
        set_transient( "bil24_syncing_order_{$order_id}", true, 300 );

        try {
            $order_data = $this->prepare_order_data_for_bil24( $order_id );
            $bil24_order_id = get_post_meta( $order_id, Constants::META_BIL24_ID, true );

            if ( $bil24_order_id ) {
                // Update existing order status
                $response = $this->api->update_order_status( intval( $bil24_order_id ), $order_data['status'] );
                $action = 'updated';
            } else {
                // Create new order
                $response = $this->api->create_order( $order_data );
                $bil24_order_id = $response['id'] ?? null;
                
                if ( $bil24_order_id ) {
                    update_post_meta( $order_id, Constants::META_BIL24_ID, $bil24_order_id );
                }
                $action = 'created';
            }

            // Update sync status
            update_post_meta( $order_id, Constants::META_SYNC_STATUS, 'synced' );
            update_post_meta( $order_id, Constants::META_LAST_SYNC, time() );

            Utils::log( "Order {$order_id} {$action} in Bil24 (ID: {$bil24_order_id})", Constants::LOG_LEVEL_INFO );

            return [
                'action' => $action,
                'order_id' => $order_id,
                'bil24_order_id' => $bil24_order_id,
                'response' => $response
            ];
        } finally {
            delete_transient( "bil24_syncing_order_{$order_id}" );
        }
    }

    /**
     * Sync order status to Bil24
     */
    public function sync_order_status_to_bil24( int $order_id, string $status ): array {
        $bil24_order_id = get_post_meta( $order_id, Constants::META_BIL24_ID, true );
        
        if ( ! $bil24_order_id ) {
            throw new \Exception( 'Order not synced to Bil24 yet' );
        }

        $bil24_status = $this->map_wp_status_to_bil24( $status );
        $response = $this->api->update_order_status( intval( $bil24_order_id ), $bil24_status );

        Utils::log( "Order {$order_id} status updated to {$bil24_status} in Bil24", Constants::LOG_LEVEL_INFO );

        return $response;
    }

    /**
     * Sync single order from Bil24
     */
    public function sync_order_from_bil24( int $order_id ): array {
        $bil24_order_id = get_post_meta( $order_id, Constants::META_BIL24_ID, true );
        
        if ( ! $bil24_order_id ) {
            throw new \Exception( 'Order does not have Bil24 ID' );
        }

        $bil24_order = $this->api->get_order( intval( $bil24_order_id ) );
        return $this->sync_order_from_bil24_data( $bil24_order, $order_id );
    }

    /**
     * Sync order from Bil24 data
     */
    private function sync_order_from_bil24_data( array $bil24_order, ?int $existing_order_id = null ): array {
        $bil24_order_id = $bil24_order['id'] ?? null;
        
        if ( ! $bil24_order_id ) {
            throw new \Exception( 'Invalid Bil24 order data' );
        }

        // Find existing order
        if ( ! $existing_order_id ) {
            $existing_orders = get_posts( [
                'post_type' => [ 'shop_order', Constants::CPT_ORDER ],
                'meta_key' => Constants::META_BIL24_ID,
                'meta_value' => $bil24_order_id,
                'posts_per_page' => 1
            ] );
            
            $existing_order_id = $existing_orders ? $existing_orders[0]->ID : null;
        }

        if ( $existing_order_id ) {
            // Update existing order
            $this->update_order_from_bil24_data( $existing_order_id, $bil24_order );
            $action = 'updated';
            $order_id = $existing_order_id;
        } else {
            // Create new order (logic depends on whether WooCommerce is active)
            if ( Utils::is_woocommerce_active() ) {
                $order_id = $this->create_wc_order_from_bil24_data( $bil24_order );
            } else {
                $order_id = $this->create_custom_order_from_bil24_data( $bil24_order );
            }
            $action = 'created';
        }

        // Update meta data
        update_post_meta( $order_id, Constants::META_BIL24_ID, $bil24_order_id );
        update_post_meta( $order_id, Constants::META_BIL24_DATA, $bil24_order );
        update_post_meta( $order_id, Constants::META_SYNC_STATUS, 'synced' );
        update_post_meta( $order_id, Constants::META_LAST_SYNC, time() );

        Utils::log( "Order {$bil24_order_id} {$action} in WordPress (ID: {$order_id})", Constants::LOG_LEVEL_INFO );

        return [
            'action' => $action,
            'order_id' => $order_id,
            'bil24_order_id' => $bil24_order_id,
            'data' => $bil24_order
        ];
    }

    /**
     * Generate tickets for order
     */
    public function generate_tickets( int $order_id ): array {
        $bil24_order_id = get_post_meta( $order_id, Constants::META_BIL24_ID, true );
        
        if ( ! $bil24_order_id ) {
            throw new \Exception( 'Order must be synced to Bil24 first' );
        }

        $tickets = $this->api->get_order_tickets( intval( $bil24_order_id ) );
        
        // Store ticket information
        update_post_meta( $order_id, '_bil24_tickets', $tickets );
        update_post_meta( $order_id, '_bil24_tickets_generated', time() );

        Utils::log( "Tickets generated for order {$order_id}", Constants::LOG_LEVEL_INFO );

        return $tickets;
    }

    /**
     * Prepare WordPress order data for Bil24
     */
    private function prepare_order_data_for_bil24( int $order_id ): array {
        // Check if it's a WooCommerce order
        if ( Utils::is_woocommerce_active() && function_exists( 'wc_get_order' ) ) {
            $order = wc_get_order( $order_id );
            if ( $order ) {
                return $this->prepare_wc_order_data_for_bil24( $order );
            }
        }

        // Handle custom order post
        return $this->prepare_custom_order_data_for_bil24( $order_id );
    }

    /**
     * Prepare WooCommerce order data for Bil24
     */
    private function prepare_wc_order_data_for_bil24( \WC_Order $order ): array {
        $items = [];
        
        foreach ( $order->get_items() as $item ) {
            $items[] = [
                'name' => $item->get_name(),
                'quantity' => $item->get_quantity(),
                'price' => $item->get_total() / $item->get_quantity()
            ];
        }

        return [
            'customer_email' => $order->get_billing_email(),
            'customer_name' => $order->get_billing_first_name() . ' ' . $order->get_billing_last_name(),
            'customer_phone' => $order->get_billing_phone(),
            'status' => $this->map_wp_status_to_bil24( $order->get_status() ),
            'total' => $order->get_total(),
            'currency' => $order->get_currency(),
            'items' => $items,
            'payment_method' => $order->get_payment_method(),
            'order_date' => $order->get_date_created()->format( 'Y-m-d H:i:s' )
        ];
    }

    /**
     * Prepare custom order data for Bil24
     */
    private function prepare_custom_order_data_for_bil24( int $order_id ): array {
        $post = get_post( $order_id );
        
        return [
            'customer_email' => get_post_meta( $order_id, '_customer_email', true ),
            'customer_name' => get_post_meta( $order_id, '_customer_name', true ),
            'customer_phone' => get_post_meta( $order_id, '_customer_phone', true ),
            'status' => $this->map_wp_status_to_bil24( $post->post_status ),
            'total' => floatval( get_post_meta( $order_id, '_order_total', true ) ),
            'currency' => get_post_meta( $order_id, '_order_currency', true ) ?: 'RUB',
            'items' => get_post_meta( $order_id, '_order_items', true ) ?: [],
            'payment_method' => get_post_meta( $order_id, '_payment_method', true ),
            'order_date' => $post->post_date
        ];
    }

    /**
     * Create WooCommerce order from Bil24 data
     */
    private function create_wc_order_from_bil24_data( array $bil24_order ): int {
        $order = wc_create_order();
        
        // Set billing information
        $order->set_billing_email( $bil24_order['customer_email'] ?? '' );
        $order->set_billing_first_name( explode( ' ', $bil24_order['customer_name'] ?? '' )[0] ?? '' );
        $order->set_billing_last_name( explode( ' ', $bil24_order['customer_name'] ?? '', 2 )[1] ?? '' );
        $order->set_billing_phone( $bil24_order['customer_phone'] ?? '' );
        
        // Add items
        foreach ( $bil24_order['items'] ?? [] as $item ) {
            $order->add_product( 
                wc_get_product( 0 ), // You might need to create/find products
                $item['quantity'] ?? 1,
                [ 'total' => $item['price'] ?? 0 ]
            );
        }
        
        $order->set_status( $this->map_bil24_status_to_wp( $bil24_order['status'] ?? 'pending' ) );
        $order->calculate_totals();
        $order->save();
        
        return $order->get_id();
    }

    /**
     * Create custom order from Bil24 data
     */
    private function create_custom_order_from_bil24_data( array $bil24_order ): int {
        $post_data = [
            'post_type' => Constants::CPT_ORDER,
            'post_title' => 'Order #' . ( $bil24_order['id'] ?? uniqid() ),
            'post_status' => $this->map_bil24_status_to_wp( $bil24_order['status'] ?? 'pending' ),
            'meta_input' => [
                '_customer_email' => $bil24_order['customer_email'] ?? '',
                '_customer_name' => $bil24_order['customer_name'] ?? '',
                '_customer_phone' => $bil24_order['customer_phone'] ?? '',
                '_order_total' => $bil24_order['total'] ?? 0,
                '_order_currency' => $bil24_order['currency'] ?? 'RUB',
                '_order_items' => $bil24_order['items'] ?? [],
                '_payment_method' => $bil24_order['payment_method'] ?? ''
            ]
        ];
        
        $post_id = wp_insert_post( $post_data );
        
        if ( is_wp_error( $post_id ) ) {
            throw new \Exception( 'Failed to create order: ' . $post_id->get_error_message() );
        }
        
        return $post_id;
    }

    /**
     * Update order from Bil24 data
     */
    private function update_order_from_bil24_data( int $order_id, array $bil24_order ): void {
        if ( Utils::is_woocommerce_active() && function_exists( 'wc_get_order' ) ) {
            $order = wc_get_order( $order_id );
            if ( $order ) {
                $order->set_status( $this->map_bil24_status_to_wp( $bil24_order['status'] ?? 'pending' ) );
                $order->save();
                return;
            }
        }

        // Update custom order
        wp_update_post( [
            'ID' => $order_id,
            'post_status' => $this->map_bil24_status_to_wp( $bil24_order['status'] ?? 'pending' )
        ] );
    }

    /**
     * Map WordPress status to Bil24 status
     */
    private function map_wp_status_to_bil24( string $wp_status ): string {
        return self::STATUS_MAPPING[ $wp_status ] ?? 'pending';
    }

    /**
     * Map Bil24 status to WordPress status
     */
    private function map_bil24_status_to_wp( string $bil24_status ): string {
        $reverse_mapping = array_flip( self::STATUS_MAPPING );
        return $reverse_mapping[ $bil24_status ] ?? 'pending';
    }
} 