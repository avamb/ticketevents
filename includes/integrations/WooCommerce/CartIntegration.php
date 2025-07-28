<?php
namespace Bil24\Integrations\WooCommerce;

use Bil24\Api\Endpoints;
use Bil24\Constants;
use Bil24\Utils;

defined( 'ABSPATH' ) || exit;

/**
 * Cart Integration for Bil24 ticket products
 * 
 * Handles ticket reservations, stock validation, and cart-specific functionality
 * for Bil24 events integrated with WooCommerce
 * 
 * @package Bil24Connector
 * @since 0.1.0
 */
class CartIntegration {

    /**
     * API Endpoints instance
     */
    private Endpoints $api;

    /**
     * Reservation timeout in minutes
     */
    private const RESERVATION_TIMEOUT = 15;

    /**
     * Constructor
     */
    public function __construct( Endpoints $api = null ) {
        $this->api = $api ?? new Endpoints();
        $this->register_hooks();
    }

    /**
     * Register WooCommerce hooks
     */
    private function register_hooks(): void {
        // Cart item validation
        add_filter( 'woocommerce_add_to_cart_validation', [ $this, 'validate_add_to_cart' ], 10, 6 );
        add_action( 'woocommerce_add_to_cart', [ $this, 'reserve_tickets_on_add_to_cart' ], 10, 6 );
        
        // Cart item removal
        add_action( 'woocommerce_cart_item_removed', [ $this, 'release_ticket_reservation' ], 10, 2 );
        add_action( 'woocommerce_remove_cart_item', [ $this, 'release_ticket_reservation' ], 10, 2 );
        
        // Cart updates
        add_action( 'woocommerce_after_cart_item_quantity_update', [ $this, 'update_ticket_reservation' ], 10, 4 );
        
        // Cart display customization
        add_filter( 'woocommerce_cart_item_name', [ $this, 'customize_cart_item_name' ], 10, 3 );
        add_filter( 'woocommerce_get_item_data', [ $this, 'add_cart_item_data' ], 10, 2 );
        
        // Checkout validation
        add_action( 'woocommerce_checkout_process', [ $this, 'validate_checkout_tickets' ] );
        add_action( 'woocommerce_before_checkout_process', [ $this, 'validate_ticket_availability' ] );
        
        // Cart expiry handling
        add_action( 'wp_loaded', [ $this, 'check_reservation_expiry' ] );
        add_action( 'woocommerce_cart_loaded_from_session', [ $this, 'validate_cart_reservations' ] );
        
        // AJAX handlers
        add_action( 'wp_ajax_bil24_extend_reservation', [ $this, 'ajax_extend_reservation' ] );
        add_action( 'wp_ajax_nopriv_bil24_extend_reservation', [ $this, 'ajax_extend_reservation' ] );
        add_action( 'wp_ajax_bil24_check_availability', [ $this, 'ajax_check_availability' ] );
        add_action( 'wp_ajax_nopriv_bil24_check_availability', [ $this, 'ajax_check_availability' ] );
        
        // Frontend notices
        add_action( 'woocommerce_before_cart', [ $this, 'show_reservation_notices' ] );
        add_action( 'woocommerce_before_checkout_form', [ $this, 'show_reservation_notices' ] );
        
        // Cart fragments for AJAX updates
        add_filter( 'woocommerce_add_to_cart_fragments', [ $this, 'add_cart_fragments' ] );
    }

    /**
     * Validate adding Bil24 product to cart
     */
    public function validate_add_to_cart( bool $passed, int $product_id, int $quantity, $variation_id = '', $variations = [], $cart_item_data = [] ): bool {
        $product = wc_get_product( $product_id );
        $bil24_id = get_post_meta( $product_id, Constants::META_BIL24_ID, true );
        
        if ( ! $bil24_id ) {
            return $passed; // Not a Bil24 product
        }
        
        try {
            // Check if event is still active
            $event_data = $this->api->get_event( $bil24_id );
            
            if ( $event_data['status'] !== 'active' ) {
                wc_add_notice( 'Это событие больше не доступно для бронирования.', 'error' );
                return false;
            }
            
            // Check start date
            $start_date = new \DateTime( $event_data['start_date'] );
            $now = new \DateTime();
            
            if ( $start_date <= $now ) {
                wc_add_notice( 'Событие уже началось или завершилось.', 'error' );
                return false;
            }
            
            // Check available tickets
            $available_tickets = $this->get_available_tickets( $bil24_id );
            $current_cart_quantity = $this->get_cart_quantity_for_product( $product_id );
            
            if ( ( $current_cart_quantity + $quantity ) > $available_tickets ) {
                wc_add_notice( 
                    sprintf( 
                        'Недостаточно билетов. Доступно: %d, в корзине: %d, запрашивается: %d', 
                        $available_tickets, 
                        $current_cart_quantity, 
                        $quantity 
                    ), 
                    'error' 
                );
                return false;
            }
            
            // Check maximum tickets per customer
            $max_tickets = get_post_meta( $product_id, '_bil24_max_tickets_per_customer', true );
            if ( $max_tickets && ( $current_cart_quantity + $quantity ) > $max_tickets ) {
                wc_add_notice( 
                    sprintf( 'Максимальное количество билетов на одного покупателя: %d', $max_tickets ), 
                    'error' 
                );
                return false;
            }
            
        } catch ( \Exception $e ) {
            Utils::log( 'Ошибка валидации добавления в корзину: ' . $e->getMessage(), Constants::LOG_LEVEL_ERROR );
            wc_add_notice( 'Произошла ошибка при проверке доступности билетов.', 'error' );
            return false;
        }
        
        return $passed;
    }

    /**
     * Reserve tickets when added to cart
     */
    public function reserve_tickets_on_add_to_cart( string $cart_item_key, int $product_id, int $quantity, $variation_id, $variation, $cart_item_data ): void {
        $bil24_id = get_post_meta( $product_id, Constants::META_BIL24_ID, true );
        
        if ( ! $bil24_id ) {
            return; // Not a Bil24 product
        }
        
        try {
            // Create reservation in Bil24
            $reservation_data = [
                'event_id' => $bil24_id,
                'quantity' => $quantity,
                'customer_session' => WC()->session->get_customer_id(),
                'timeout_minutes' => self::RESERVATION_TIMEOUT
            ];
            
            $reservation_response = $this->api->create_reservation( $reservation_data );
            
            if ( ! empty( $reservation_response['reservation_id'] ) ) {
                // Store reservation ID in session
                $reservations = WC()->session->get( 'bil24_reservations', [] );
                $reservations[ $cart_item_key ] = [
                    'reservation_id' => $reservation_response['reservation_id'],
                    'event_id' => $bil24_id,
                    'quantity' => $quantity,
                    'expires_at' => time() + ( self::RESERVATION_TIMEOUT * 60 ),
                    'product_id' => $product_id
                ];
                WC()->session->set( 'bil24_reservations', $reservations );
                
                Utils::log( "Создано резервирование {$reservation_response['reservation_id']} для продукта {$product_id}", Constants::LOG_LEVEL_DEBUG );
            }
            
        } catch ( \Exception $e ) {
            Utils::log( 'Ошибка создания резервирования: ' . $e->getMessage(), Constants::LOG_LEVEL_ERROR );
            // Don't fail the cart addition, but log the error
        }
    }

    /**
     * Release ticket reservation when item removed from cart
     */
    public function release_ticket_reservation( string $cart_item_key, WC $cart ): void {
        $reservations = WC()->session->get( 'bil24_reservations', [] );
        
        if ( isset( $reservations[ $cart_item_key ] ) ) {
            $reservation = $reservations[ $cart_item_key ];
            
            try {
                $this->api->cancel_reservation( $reservation['reservation_id'] );
                Utils::log( "Отменено резервирование {$reservation['reservation_id']}", Constants::LOG_LEVEL_DEBUG );
            } catch ( \Exception $e ) {
                Utils::log( 'Ошибка отмены резервирования: ' . $e->getMessage(), Constants::LOG_LEVEL_ERROR );
            }
            
            unset( $reservations[ $cart_item_key ] );
            WC()->session->set( 'bil24_reservations', $reservations );
        }
    }

    /**
     * Update ticket reservation when quantity changes
     */
    public function update_ticket_reservation( string $cart_item_key, int $quantity, int $old_quantity, WC $cart ): void {
        $reservations = WC()->session->get( 'bil24_reservations', [] );
        
        if ( ! isset( $reservations[ $cart_item_key ] ) ) {
            return;
        }
        
        $reservation = $reservations[ $cart_item_key ];
        $quantity_diff = $quantity - $old_quantity;
        
        try {
            if ( $quantity_diff > 0 ) {
                // Increase reservation
                $this->api->update_reservation( $reservation['reservation_id'], [
                    'quantity' => $quantity
                ] );
            } elseif ( $quantity_diff < 0 ) {
                // Decrease reservation
                $this->api->update_reservation( $reservation['reservation_id'], [
                    'quantity' => $quantity
                ] );
            }
            
            // Update local data
            $reservations[ $cart_item_key ]['quantity'] = $quantity;
            WC()->session->set( 'bil24_reservations', $reservations );
            
        } catch ( \Exception $e ) {
            Utils::log( 'Ошибка обновления резервирования: ' . $e->getMessage(), Constants::LOG_LEVEL_ERROR );
        }
    }

    /**
     * Customize cart item name for Bil24 products
     */
    public function customize_cart_item_name( string $product_name, array $cart_item, string $cart_item_key ): string {
        $product_id = $cart_item['product_id'];
        $bil24_id = get_post_meta( $product_id, Constants::META_BIL24_ID, true );
        
        if ( ! $bil24_id ) {
            return $product_name;
        }
        
        $start_date = get_post_meta( $product_id, '_bil24_start_date', true );
        $venue_name = get_post_meta( $product_id, '_bil24_venue_name', true );
        
        $additional_info = [];
        
        if ( $start_date ) {
            $date = new \DateTime( $start_date );
            $additional_info[] = $date->format( 'd.m.Y H:i' );
        }
        
        if ( $venue_name ) {
            $additional_info[] = $venue_name;
        }
        
        if ( ! empty( $additional_info ) ) {
            $product_name .= '<br><small style="color: #666;">' . implode( ' | ', $additional_info ) . '</small>';
        }
        
        return $product_name;
    }

    /**
     * Add custom data to cart items
     */
    public function add_cart_item_data( array $item_data, array $cart_item ): array {
        $product_id = $cart_item['product_id'];
        $bil24_id = get_post_meta( $product_id, Constants::META_BIL24_ID, true );
        
        if ( ! $bil24_id ) {
            return $item_data;
        }
        
        // Add reservation expiry time
        $reservations = WC()->session->get( 'bil24_reservations', [] );
        $cart_item_key = $cart_item['key'] ?? '';
        
        if ( isset( $reservations[ $cart_item_key ] ) ) {
            $expires_at = $reservations[ $cart_item_key ]['expires_at'];
            $time_left = $expires_at - time();
            
            if ( $time_left > 0 ) {
                $minutes_left = ceil( $time_left / 60 );
                $item_data[] = [
                    'key' => 'Резервирование',
                    'value' => "Действительно еще {$minutes_left} мин."
                ];
            } else {
                $item_data[] = [
                    'key' => 'Резервирование',
                    'value' => '<span style="color: red;">Истекло</span>'
                ];
            }
        }
        
        return $item_data;
    }

    /**
     * Validate tickets at checkout
     */
    public function validate_checkout_tickets(): void {
        foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
            $product_id = $cart_item['product_id'];
            $bil24_id = get_post_meta( $product_id, Constants::META_BIL24_ID, true );
            
            if ( ! $bil24_id ) {
                continue;
            }
            
            // Check if reservation is still valid
            $reservations = WC()->session->get( 'bil24_reservations', [] );
            
            if ( ! isset( $reservations[ $cart_item_key ] ) ) {
                wc_add_notice( 'Резервирование билетов истекло. Пожалуйста, обновите корзину.', 'error' );
                return;
            }
            
            $reservation = $reservations[ $cart_item_key ];
            
            if ( $reservation['expires_at'] <= time() ) {
                wc_add_notice( 'Резервирование билетов истекло. Пожалуйста, обновите корзину.', 'error' );
                return;
            }
            
            // Final availability check
            try {
                $available_tickets = $this->get_available_tickets( $bil24_id );
                
                if ( $cart_item['quantity'] > $available_tickets ) {
                    wc_add_notice( 
                        sprintf( 
                            'Билеты для "%s" больше не доступны в запрашиваемом количестве.', 
                            $cart_item['data']->get_name() 
                        ), 
                        'error' 
                    );
                    return;
                }
                
            } catch ( \Exception $e ) {
                Utils::log( 'Ошибка проверки доступности на чекауте: ' . $e->getMessage(), Constants::LOG_LEVEL_ERROR );
                wc_add_notice( 'Произошла ошибка при проверке доступности билетов.', 'error' );
                return;
            }
        }
    }

    /**
     * Validate ticket availability before checkout
     */
    public function validate_ticket_availability(): void {
        $reservations = WC()->session->get( 'bil24_reservations', [] );
        
        foreach ( $reservations as $cart_item_key => $reservation ) {
            try {
                // Extend reservation for checkout process
                $this->api->extend_reservation( $reservation['reservation_id'], [
                    'additional_minutes' => 10
                ] );
                
                // Update expiry time
                $reservations[ $cart_item_key ]['expires_at'] = time() + 600; // 10 more minutes
                
            } catch ( \Exception $e ) {
                Utils::log( 'Ошибка продления резервирования на чекауте: ' . $e->getMessage(), Constants::LOG_LEVEL_ERROR );
            }
        }
        
        WC()->session->set( 'bil24_reservations', $reservations );
    }

    /**
     * Check for expired reservations
     */
    public function check_reservation_expiry(): void {
        if ( ! WC()->session ) {
            return;
        }
        
        $reservations = WC()->session->get( 'bil24_reservations', [] );
        $expired_items = [];
        
        foreach ( $reservations as $cart_item_key => $reservation ) {
            if ( $reservation['expires_at'] <= time() ) {
                $expired_items[] = $cart_item_key;
                
                try {
                    $this->api->cancel_reservation( $reservation['reservation_id'] );
                } catch ( \Exception $e ) {
                    Utils::log( 'Ошибка отмены истекшего резервирования: ' . $e->getMessage(), Constants::LOG_LEVEL_ERROR );
                }
                
                unset( $reservations[ $cart_item_key ] );
            }
        }
        
        if ( ! empty( $expired_items ) ) {
            WC()->session->set( 'bil24_reservations', $reservations );
            
            // Remove expired items from cart
            foreach ( $expired_items as $cart_item_key ) {
                WC()->cart->remove_cart_item( $cart_item_key );
            }
            
            if ( ! empty( $expired_items ) ) {
                wc_add_notice( 'Некоторые билеты были удалены из корзины из-за истечения времени резервирования.', 'notice' );
            }
        }
    }

    /**
     * Validate cart reservations when cart is loaded
     */
    public function validate_cart_reservations( $cart ): void {
        $this->check_reservation_expiry();
    }

    /**
     * Show reservation notices
     */
    public function show_reservation_notices(): void {
        $reservations = WC()->session->get( 'bil24_reservations', [] );
        
        if ( empty( $reservations ) ) {
            return;
        }
        
        $earliest_expiry = null;
        
        foreach ( $reservations as $reservation ) {
            if ( ! $earliest_expiry || $reservation['expires_at'] < $earliest_expiry ) {
                $earliest_expiry = $reservation['expires_at'];
            }
        }
        
        if ( $earliest_expiry ) {
            $time_left = $earliest_expiry - time();
            
            if ( $time_left > 0 ) {
                $minutes_left = ceil( $time_left / 60 );
                ?>
                <div class="woocommerce-info bil24-reservation-notice">
                    <strong>Внимание!</strong> Ваши билеты зарезервированы еще на <span id="bil24-countdown"><?php echo $minutes_left; ?></span> мин. 
                    <a href="#" id="bil24-extend-reservation" class="button">Продлить резервирование</a>
                </div>
                
                <script>
                jQuery(document).ready(function($) {
                    // Countdown timer
                    var timeLeft = <?php echo $time_left; ?>;
                    var countdown = setInterval(function() {
                        timeLeft--;
                        var minutes = Math.ceil(timeLeft / 60);
                        $('#bil24-countdown').text(minutes);
                        
                        if (timeLeft <= 0) {
                            clearInterval(countdown);
                            location.reload();
                        }
                    }, 1000);
                    
                    // Extend reservation
                    $('#bil24-extend-reservation').on('click', function(e) {
                        e.preventDefault();
                        
                        $.post('<?php echo admin_url( 'admin-ajax.php' ); ?>', {
                            action: 'bil24_extend_reservation',
                            nonce: '<?php echo wp_create_nonce( 'bil24_extend_reservation' ); ?>'
                        }, function(response) {
                            if (response.success) {
                                location.reload();
                            } else {
                                alert('Не удалось продлить резервирование');
                            }
                        });
                    });
                });
                </script>
                <?php
            }
        }
    }

    /**
     * Add cart fragments for AJAX updates
     */
    public function add_cart_fragments( array $fragments ): array {
        $reservations = WC()->session->get( 'bil24_reservations', [] );
        
        if ( ! empty( $reservations ) ) {
            $earliest_expiry = null;
            
            foreach ( $reservations as $reservation ) {
                if ( ! $earliest_expiry || $reservation['expires_at'] < $earliest_expiry ) {
                    $earliest_expiry = $reservation['expires_at'];
                }
            }
            
            if ( $earliest_expiry ) {
                $time_left = max( 0, $earliest_expiry - time() );
                $fragments['bil24_reservation_time'] = $time_left;
            }
        }
        
        return $fragments;
    }

    /**
     * AJAX handler for extending reservation
     */
    public function ajax_extend_reservation(): void {
        check_ajax_referer( 'bil24_extend_reservation', 'nonce' );
        
        $reservations = WC()->session->get( 'bil24_reservations', [] );
        $extended = 0;
        
        foreach ( $reservations as $cart_item_key => $reservation ) {
            try {
                $this->api->extend_reservation( $reservation['reservation_id'], [
                    'additional_minutes' => self::RESERVATION_TIMEOUT
                ] );
                
                $reservations[ $cart_item_key ]['expires_at'] = time() + ( self::RESERVATION_TIMEOUT * 60 );
                $extended++;
                
            } catch ( \Exception $e ) {
                Utils::log( 'Ошибка продления резервирования: ' . $e->getMessage(), Constants::LOG_LEVEL_ERROR );
            }
        }
        
        if ( $extended > 0 ) {
            WC()->session->set( 'bil24_reservations', $reservations );
            wp_send_json_success( [
                'message' => "Продлено резервирований: {$extended}",
                'extended_count' => $extended
            ] );
        } else {
            wp_send_json_error( [
                'message' => 'Не удалось продлить резервирование'
            ] );
        }
    }

    /**
     * AJAX handler for checking availability
     */
    public function ajax_check_availability(): void {
        check_ajax_referer( 'bil24_check_availability', 'nonce' );
        
        $product_id = intval( $_POST['product_id'] ?? 0 );
        $quantity = intval( $_POST['quantity'] ?? 1 );
        
        if ( ! $product_id ) {
            wp_send_json_error( [ 'message' => 'Неверный ID продукта' ] );
        }
        
        $bil24_id = get_post_meta( $product_id, Constants::META_BIL24_ID, true );
        
        if ( ! $bil24_id ) {
            wp_send_json_success( [ 'available' => true ] );
        }
        
        try {
            $available_tickets = $this->get_available_tickets( $bil24_id );
            $current_cart_quantity = $this->get_cart_quantity_for_product( $product_id );
            
            $available = ( $current_cart_quantity + $quantity ) <= $available_tickets;
            
            wp_send_json_success( [
                'available' => $available,
                'available_tickets' => $available_tickets,
                'cart_quantity' => $current_cart_quantity,
                'max_available' => max( 0, $available_tickets - $current_cart_quantity )
            ] );
            
        } catch ( \Exception $e ) {
            wp_send_json_error( [
                'message' => 'Ошибка проверки доступности: ' . $e->getMessage()
            ] );
        }
    }

    /**
     * Get available tickets for event
     */
    private function get_available_tickets( string $bil24_id ): int {
        $cache_key = Constants::CACHE_PREFIX . 'available_tickets_' . $bil24_id;
        $cached = wp_cache_get( $cache_key, Constants::CACHE_GROUP );
        
        if ( $cached !== false ) {
            return $cached;
        }
        
        try {
            $event_data = $this->api->get_event( $bil24_id );
            $available = $event_data['available_tickets'] ?? 0;
            
            // Cache for 1 minute
            wp_cache_set( $cache_key, $available, Constants::CACHE_GROUP, 60 );
            
            return $available;
            
        } catch ( \Exception $e ) {
            Utils::log( 'Ошибка получения доступных билетов: ' . $e->getMessage(), Constants::LOG_LEVEL_ERROR );
            return 0;
        }
    }

    /**
     * Get cart quantity for specific product
     */
    private function get_cart_quantity_for_product( int $product_id ): int {
        $total_quantity = 0;
        
        foreach ( WC()->cart->get_cart() as $cart_item ) {
            if ( $cart_item['product_id'] == $product_id ) {
                $total_quantity += $cart_item['quantity'];
            }
        }
        
        return $total_quantity;
    }
} 