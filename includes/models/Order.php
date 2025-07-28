<?php
namespace Bil24\Models;

defined( 'ABSPATH' ) || exit;

/**
 * Order model class for Bil24 Connector
 * 
 * @package Bil24Connector
 * @since 0.1.0
 */
class Order {
    
    /**
     * Order ID
     */
    private ?int $id = null;
    
    /**
     * Order number
     */
    private string $order_number = '';
    
    /**
     * Event ID
     */
    private ?int $event_id = null;
    
    /**
     * Session ID
     */
    private ?int $session_id = null;
    
    /**
     * Customer email
     */
    private string $customer_email = '';
    
    /**
     * Customer name
     */
    private string $customer_name = '';
    
    /**
     * Customer phone
     */
    private string $customer_phone = '';
    
    /**
     * Order status
     */
    private string $status = 'pending';
    
    /**
     * Total amount
     */
    private float $total_amount = 0.0;
    
    /**
     * Currency
     */
    private string $currency = 'USD';
    
    /**
     * Payment status
     */
    private string $payment_status = 'pending';
    
    /**
     * Payment method
     */
    private string $payment_method = '';
    
    /**
     * Transaction ID
     */
    private ?string $transaction_id = null;
    
    /**
     * Order items (tickets)
     */
    private array $items = [];
    
    /**
     * Order created date
     */
    private ?\DateTime $created_at = null;
    
    /**
     * Order updated date
     */
    private ?\DateTime $updated_at = null;
    
    /**
     * Bil24 external ID
     */
    private ?string $bil24_id = null;
    
    /**
     * WooCommerce order ID
     */
    private ?int $woocommerce_order_id = null;
    
    /**
     * Constructor
     */
    public function __construct( array $data = [] ) {
        $this->created_at = new \DateTime();
        $this->updated_at = new \DateTime();
        $this->fill( $data );
    }
    
    /**
     * Fill order data from array
     */
    public function fill( array $data ): self {
        foreach ( $data as $key => $value ) {
            $method = 'set_' . $key;
            if ( method_exists( $this, $method ) ) {
                $this->$method( $value );
            }
        }
        
        return $this;
    }
    
    /**
     * Get order ID
     */
    public function get_id(): ?int {
        return $this->id;
    }
    
    /**
     * Set order ID
     */
    public function set_id( ?int $id ): self {
        $this->id = $id;
        return $this;
    }
    
    /**
     * Get order number
     */
    public function get_order_number(): string {
        return $this->order_number;
    }
    
    /**
     * Set order number
     */
    public function set_order_number( string $order_number ): self {
        $this->order_number = sanitize_text_field( $order_number );
        return $this;
    }
    
    /**
     * Generate order number
     */
    public function generate_order_number( string $prefix = 'BIL' ): self {
        $this->order_number = $prefix . '-' . date( 'Y' ) . '-' . str_pad( rand( 1, 999999 ), 6, '0', STR_PAD_LEFT );
        return $this;
    }
    
    /**
     * Get event ID
     */
    public function get_event_id(): ?int {
        return $this->event_id;
    }
    
    /**
     * Set event ID
     */
    public function set_event_id( ?int $event_id ): self {
        $this->event_id = $event_id;
        return $this;
    }
    
    /**
     * Get session ID
     */
    public function get_session_id(): ?int {
        return $this->session_id;
    }
    
    /**
     * Set session ID
     */
    public function set_session_id( ?int $session_id ): self {
        $this->session_id = $session_id;
        return $this;
    }
    
    /**
     * Get customer email
     */
    public function get_customer_email(): string {
        return $this->customer_email;
    }
    
    /**
     * Set customer email
     */
    public function set_customer_email( string $email ): self {
        if ( function_exists( 'sanitize_email' ) ) {
            $this->customer_email = sanitize_email( $email );
        } else {
            $this->customer_email = filter_var( $email, FILTER_SANITIZE_EMAIL );
        }
        return $this;
    }
    
    /**
     * Get customer name
     */
    public function get_customer_name(): string {
        return $this->customer_name;
    }
    
    /**
     * Set customer name
     */
    public function set_customer_name( string $name ): self {
        $this->customer_name = function_exists('sanitize_text_field') ? sanitize_text_field( $name ) : strip_tags( $name );
        return $this;
    }
    
    /**
     * Get customer phone
     */
    public function get_customer_phone(): string {
        return $this->customer_phone;
    }
    
    /**
     * Set customer phone
     */
    public function set_customer_phone( string $phone ): self {
        $this->customer_phone = function_exists('sanitize_text_field') ? sanitize_text_field( $phone ) : strip_tags( $phone );
        return $this;
    }
    
    /**
     * Get order status
     */
    public function get_status(): string {
        return $this->status;
    }
    
    /**
     * Set order status
     */
    public function set_status( string $status ): self {
        $allowed_statuses = [ 'pending', 'confirmed', 'paid', 'cancelled', 'refunded', 'completed' ];
        
        if ( in_array( $status, $allowed_statuses, true ) ) {
            $this->status = $status;
            $this->updated_at = new \DateTime();
        }
        
        return $this;
    }
    
    /**
     * Get total amount
     */
    public function get_total_amount(): float {
        return $this->total_amount;
    }
    
    /**
     * Set total amount
     */
    public function set_total_amount( float $amount ): self {
        $this->total_amount = max( 0.0, $amount );
        return $this;
    }
    
    /**
     * Get currency
     */
    public function get_currency(): string {
        return $this->currency;
    }
    
    /**
     * Set currency
     */
    public function set_currency( string $currency ): self {
        $this->currency = strtoupper( $currency );
        return $this;
    }
    
    /**
     * Get payment status
     */
    public function get_payment_status(): string {
        return $this->payment_status;
    }
    
    /**
     * Set payment status
     */
    public function set_payment_status( string $status ): self {
        $allowed_statuses = [ 'pending', 'processing', 'paid', 'failed', 'refunded', 'partially_refunded' ];
        
        if ( in_array( $status, $allowed_statuses, true ) ) {
            $this->payment_status = $status;
            $this->updated_at = new \DateTime();
        }
        
        return $this;
    }
    
    /**
     * Get payment method
     */
    public function get_payment_method(): string {
        return $this->payment_method;
    }
    
    /**
     * Set payment method
     */
    public function set_payment_method( string $method ): self {
        $this->payment_method = function_exists('sanitize_text_field') ? sanitize_text_field( $method ) : strip_tags( $method );
        return $this;
    }
    
    /**
     * Get transaction ID
     */
    public function get_transaction_id(): ?string {
        return $this->transaction_id;
    }
    
    /**
     * Set transaction ID
     */
    public function set_transaction_id( ?string $transaction_id ): self {
        $this->transaction_id = $transaction_id;
        return $this;
    }
    
    /**
     * Get order items
     */
    public function get_items(): array {
        return $this->items;
    }
    
    /**
     * Set order items
     */
    public function set_items( array $items ): self {
        $this->items = $items;
        $this->calculate_total();
        return $this;
    }
    
    /**
     * Add order item
     */
    public function add_item( array $item ): self {
        $this->items[] = $item;
        $this->calculate_total();
        return $this;
    }
    
    /**
     * Remove order item
     */
    public function remove_item( int $index ): self {
        if ( isset( $this->items[ $index ] ) ) {
            unset( $this->items[ $index ] );
            $this->items = array_values( $this->items ); // Re-index array
            $this->calculate_total();
        }
        return $this;
    }
    
    /**
     * Calculate total amount from items
     */
    private function calculate_total(): void {
        $total = 0.0;
        foreach ( $this->items as $item ) {
            $price = floatval( $item['price'] ?? 0 );
            $quantity = intval( $item['quantity'] ?? 1 );
            $total += $price * $quantity;
        }
        $this->total_amount = $total;
    }
    
    /**
     * Get created date
     */
    public function get_created_at(): ?\DateTime {
        return $this->created_at;
    }
    
    /**
     * Set created date
     */
    public function set_created_at( $date ): self {
        if ( is_string( $date ) ) {
            $this->created_at = new \DateTime( $date );
        } elseif ( $date instanceof \DateTime ) {
            $this->created_at = $date;
        }
        
        return $this;
    }
    
    /**
     * Get updated date
     */
    public function get_updated_at(): ?\DateTime {
        return $this->updated_at;
    }
    
    /**
     * Set updated date
     */
    public function set_updated_at( $date ): self {
        if ( is_string( $date ) ) {
            $this->updated_at = new \DateTime( $date );
        } elseif ( $date instanceof \DateTime ) {
            $this->updated_at = $date;
        }
        
        return $this;
    }
    
    /**
     * Get Bil24 ID
     */
    public function get_bil24_id(): ?string {
        return $this->bil24_id;
    }
    
    /**
     * Set Bil24 ID
     */
    public function set_bil24_id( ?string $bil24_id ): self {
        $this->bil24_id = $bil24_id;
        return $this;
    }
    
    /**
     * Get WooCommerce order ID
     */
    public function get_woocommerce_order_id(): ?int {
        return $this->woocommerce_order_id;
    }
    
    /**
     * Set WooCommerce order ID
     */
    public function set_woocommerce_order_id( ?int $order_id ): self {
        $this->woocommerce_order_id = $order_id;
        return $this;
    }
    
    /**
     * Check if order is paid
     */
    public function is_paid(): bool {
        return in_array( $this->payment_status, [ 'paid', 'completed' ], true );
    }
    
    /**
     * Check if order is cancelled
     */
    public function is_cancelled(): bool {
        return $this->status === 'cancelled';
    }
    
    /**
     * Check if order is refunded
     */
    public function is_refunded(): bool {
        return in_array( $this->payment_status, [ 'refunded', 'partially_refunded' ], true );
    }
    
    /**
     * Get ticket count
     */
    public function get_ticket_count(): int {
        $count = 0;
        foreach ( $this->items as $item ) {
            $count += intval( $item['quantity'] ?? 1 );
        }
        return $count;
    }
    
    /**
     * Convert to array
     */
    public function to_array(): array {
        return [
            'id' => $this->id,
            'order_number' => $this->order_number,
            'event_id' => $this->event_id,
            'session_id' => $this->session_id,
            'customer_email' => $this->customer_email,
            'customer_name' => $this->customer_name,
            'customer_phone' => $this->customer_phone,
            'status' => $this->status,
            'total_amount' => $this->total_amount,
            'currency' => $this->currency,
            'payment_status' => $this->payment_status,
            'payment_method' => $this->payment_method,
            'transaction_id' => $this->transaction_id,
            'items' => $this->items,
            'created_at' => $this->created_at?->format( 'Y-m-d H:i:s' ),
            'updated_at' => $this->updated_at?->format( 'Y-m-d H:i:s' ),
            'bil24_id' => $this->bil24_id,
            'woocommerce_order_id' => $this->woocommerce_order_id,
        ];
    }
    
    /**
     * CamelCase aliases for getters (for test compatibility)
     */
    public function getId(): ?int { return $this->get_id(); }
    public function getOrderNumber(): string { return $this->get_order_number(); }
    public function getEventId(): ?int { return $this->get_event_id(); }
    public function getSessionId(): ?int { return $this->get_session_id(); }
    public function getCustomerEmail(): string { return $this->get_customer_email(); }
    public function getCustomerName(): string { return $this->get_customer_name(); }
    public function getCustomerPhone(): string { return $this->get_customer_phone(); }
    public function getStatus(): string { return $this->get_status(); }
    public function getTotalAmount(): float { return $this->get_total_amount(); }
    public function getCurrency(): string { return $this->get_currency(); }
    public function getPaymentStatus(): string { return $this->get_payment_status(); }
    public function getPaymentMethod(): string { return $this->get_payment_method(); }
    public function getTransactionId(): ?string { return $this->get_transaction_id(); }
    public function getItems(): array { return $this->get_items(); }
    public function getCreatedAt(): ?string { return $this->created_at?->format( 'Y-m-d H:i:s' ); }
    public function getUpdatedAt(): ?string { return $this->updated_at?->format( 'Y-m-d H:i:s' ); }
    public function getBil24Id(): ?string { return $this->get_bil24_id(); }
    public function getWoocommerceOrderId(): ?int { return $this->get_woocommerce_order_id(); }
    
    /**
     * CamelCase aliases for setters (for test compatibility)
     */
    public function setId( ?int $id ): self { return $this->set_id( $id ); }
    public function setOrderNumber( string $order_number ): self { return $this->set_order_number( $order_number ); }
    public function setEventId( ?int $event_id ): self { return $this->set_event_id( $event_id ); }
    public function setSessionId( ?int $session_id ): self { return $this->set_session_id( $session_id ); }
    public function setCustomerEmail( string $email ): self { return $this->set_customer_email( $email ); }
    public function setCustomerName( string $name ): self { return $this->set_customer_name( $name ); }
    public function setCustomerPhone( string $phone ): self { return $this->set_customer_phone( $phone ); }
    public function setStatus( string $status ): self { return $this->set_status( $status ); }
    public function setTotalAmount( float $amount ): self { return $this->set_total_amount( $amount ); }
    public function setCurrency( string $currency ): self { return $this->set_currency( $currency ); }
    public function setPaymentStatus( string $status ): self { return $this->set_payment_status( $status ); }
    public function setPaymentMethod( string $method ): self { return $this->set_payment_method( $method ); }
    public function setTransactionId( ?string $transaction_id ): self { return $this->set_transaction_id( $transaction_id ); }
    public function setItems( array $items ): self { return $this->set_items( $items ); }
    public function setCreatedAt( $date ): self { return $this->set_created_at( $date ); }
    public function setUpdatedAt( $date ): self { return $this->set_updated_at( $date ); }
    public function setBil24Id( ?string $bil24_id ): self { return $this->set_bil24_id( $bil24_id ); }
    public function setWoocommerceOrderId( ?int $order_id ): self { return $this->set_woocommerce_order_id( $order_id ); }
    
    /**
     * toArray alias for test compatibility
     */
    public function toArray(): array {
        return $this->to_array();
    }
    
    /**
     * Check if order is empty
     */
    public function isEmpty(): bool {
        return empty( $this->order_number ) && $this->id === null && empty( $this->items );
    }
    
    /**
     * String representation
     */
    public function __toString(): string {
        return $this->order_number ?: 'Order #' . ( $this->id ?: 'New' );
    }
} 