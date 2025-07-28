<?php
namespace Bil24\Models;

defined( 'ABSPATH' ) || exit;

/**
 * Ticket model class for Bil24 Connector
 * 
 * @package Bil24Connector
 * @since 0.1.0
 */
class Ticket {
    
    /**
     * Ticket ID
     */
    private ?int $id = null;
    
    /**
     * Ticket number/code
     */
    private string $ticket_number = '';
    
    /**
     * Order ID this ticket belongs to
     */
    private ?int $order_id = null;
    
    /**
     * Event ID
     */
    private ?int $event_id = null;
    
    /**
     * Session ID
     */
    private ?int $session_id = null;
    
    /**
     * Price category
     */
    private string $price_category = '';
    
    /**
     * Ticket type
     */
    private string $type = 'standard';
    
    /**
     * Seat section
     */
    private string $section = '';
    
    /**
     * Seat row
     */
    private string $row = '';
    
    /**
     * Seat number
     */
    private string $seat_number = '';
    
    /**
     * Ticket price
     */
    private float $price = 0.0;
    
    /**
     * Original price (before discounts)
     */
    private float $original_price = 0.0;
    
    /**
     * Currency
     */
    private string $currency = 'USD';
    
    /**
     * Ticket status
     */
    private string $status = 'valid';
    
    /**
     * Customer name on ticket
     */
    private string $customer_name = '';
    
    /**
     * Customer email
     */
    private string $customer_email = '';
    
    /**
     * QR code content
     */
    private ?string $qr_code = null;
    
    /**
     * Barcode content
     */
    private ?string $barcode = null;
    
    /**
     * Check-in status
     */
    private bool $checked_in = false;
    
    /**
     * Check-in timestamp
     */
    private ?\DateTime $checked_in_at = null;
    
    /**
     * Special notes or instructions
     */
    private string $notes = '';
    
    /**
     * Created timestamp
     */
    private ?\DateTime $created_at = null;
    
    /**
     * Updated timestamp
     */
    private ?\DateTime $updated_at = null;
    
    /**
     * Bil24 external ID
     */
    private ?string $bil24_id = null;
    
    /**
     * Constructor
     */
    public function __construct( array $data = [] ) {
        $this->created_at = new \DateTime();
        $this->updated_at = new \DateTime();
        $this->fill( $data );
    }
    
    /**
     * Fill ticket data from array
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
     * Get ticket ID
     */
    public function get_id(): ?int {
        return $this->id;
    }
    
    /**
     * Set ticket ID
     */
    public function set_id( ?int $id ): self {
        $this->id = $id;
        return $this;
    }
    
    /**
     * Get ticket number
     */
    public function get_ticket_number(): string {
        return $this->ticket_number;
    }
    
    /**
     * Set ticket number
     */
    public function set_ticket_number( string $ticket_number ): self {
        $this->ticket_number = function_exists('sanitize_text_field') ? sanitize_text_field( $ticket_number ) : strip_tags( $ticket_number );
        return $this;
    }
    
    /**
     * Generate ticket number
     */
    public function generate_ticket_number( string $prefix = 'TKT' ): self {
        $this->ticket_number = $prefix . '-' . date( 'Y' ) . '-' . str_pad( rand( 1, 999999 ), 6, '0', STR_PAD_LEFT );
        return $this;
    }
    
    /**
     * Get order ID
     */
    public function get_order_id(): ?int {
        return $this->order_id;
    }
    
    /**
     * Set order ID
     */
    public function set_order_id( ?int $order_id ): self {
        $this->order_id = $order_id;
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
     * Get price category
     */
    public function get_price_category(): string {
        return $this->price_category;
    }
    
    /**
     * Set price category
     */
    public function set_price_category( string $category ): self {
        $this->price_category = function_exists('sanitize_text_field') ? sanitize_text_field( $category ) : strip_tags( $category );
        return $this;
    }
    
    /**
     * Get ticket type
     */
    public function get_type(): string {
        return $this->type;
    }
    
    /**
     * Set ticket type
     */
    public function set_type( string $type ): self {
        $allowed_types = [ 'standard', 'vip', 'premium', 'student', 'senior', 'child', 'group', 'complimentary' ];
        
        if ( in_array( $type, $allowed_types, true ) ) {
            $this->type = $type;
        }
        
        return $this;
    }
    
    /**
     * Get section
     */
    public function get_section(): string {
        return $this->section;
    }
    
    /**
     * Set section
     */
    public function set_section( string $section ): self {
        $this->section = function_exists('sanitize_text_field') ? sanitize_text_field( $section ) : strip_tags( $section );
        return $this;
    }
    
    /**
     * Get row
     */
    public function get_row(): string {
        return $this->row;
    }
    
    /**
     * Set row
     */
    public function set_row( string $row ): self {
        $this->row = function_exists('sanitize_text_field') ? sanitize_text_field( $row ) : strip_tags( $row );
        return $this;
    }
    
    /**
     * Get seat number
     */
    public function get_seat_number(): string {
        return $this->seat_number;
    }
    
    /**
     * Set seat number
     */
    public function set_seat_number( string $seat_number ): self {
        $this->seat_number = function_exists('sanitize_text_field') ? sanitize_text_field( $seat_number ) : strip_tags( $seat_number );
        return $this;
    }
    
    /**
     * Get full seat information
     */
    public function get_full_seat(): string {
        $parts = array_filter( [ $this->section, $this->row, $this->seat_number ] );
        return implode( '-', $parts );
    }
    
    /**
     * Get price
     */
    public function get_price(): float {
        return $this->price;
    }
    
    /**
     * Set price
     */
    public function set_price( float $price ): self {
        $this->price = max( 0.0, $price );
        return $this;
    }
    
    /**
     * Get original price
     */
    public function get_original_price(): float {
        return $this->original_price;
    }
    
    /**
     * Set original price
     */
    public function set_original_price( float $price ): self {
        $this->original_price = max( 0.0, $price );
        return $this;
    }
    
    /**
     * Get discount amount
     */
    public function get_discount_amount(): float {
        return max( 0.0, $this->original_price - $this->price );
    }
    
    /**
     * Get discount percentage
     */
    public function get_discount_percentage(): float {
        if ( $this->original_price <= 0 ) {
            return 0.0;
        }
        
        return ( ( $this->original_price - $this->price ) / $this->original_price ) * 100;
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
     * Get status
     */
    public function get_status(): string {
        return $this->status;
    }
    
    /**
     * Set status
     */
    public function set_status( string $status ): self {
        $allowed_statuses = [ 'valid', 'used', 'expired', 'cancelled', 'refunded', 'transferred' ];
        
        if ( in_array( $status, $allowed_statuses, true ) ) {
            $this->status = $status;
            $this->updated_at = new \DateTime();
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
     * Get QR code
     */
    public function get_qr_code(): ?string {
        return $this->qr_code;
    }
    
    /**
     * Set QR code
     */
    public function set_qr_code( ?string $qr_code ): self {
        $this->qr_code = $qr_code;
        return $this;
    }
    
    /**
     * Generate QR code content
     */
    public function generate_qr_code(): self {
        $data = [
            'ticket_id' => $this->id,
            'ticket_number' => $this->ticket_number,
            'event_id' => $this->event_id,
            'session_id' => $this->session_id,
            'customer_email' => $this->customer_email,
            'timestamp' => time()
        ];
        
        $this->qr_code = base64_encode( json_encode( $data ) );
        return $this;
    }
    
    /**
     * Get barcode
     */
    public function get_barcode(): ?string {
        return $this->barcode;
    }
    
    /**
     * Set barcode
     */
    public function set_barcode( ?string $barcode ): self {
        $this->barcode = $barcode;
        return $this;
    }
    
    /**
     * Generate barcode content
     */
    public function generate_barcode(): self {
        $this->barcode = str_pad( $this->id ?: rand( 1000000, 9999999 ), 10, '0', STR_PAD_LEFT );
        return $this;
    }
    
    /**
     * Check if ticket is checked in
     */
    public function is_checked_in(): bool {
        return $this->checked_in;
    }
    
    /**
     * Set checked in status
     */
    public function set_checked_in( bool $checked_in ): self {
        $this->checked_in = $checked_in;
        
        if ( $checked_in && ! $this->checked_in_at ) {
            $this->checked_in_at = new \DateTime();
        } elseif ( ! $checked_in ) {
            $this->checked_in_at = null;
        }
        
        $this->updated_at = new \DateTime();
        return $this;
    }
    
    /**
     * Get checked in timestamp
     */
    public function get_checked_in_at(): ?\DateTime {
        return $this->checked_in_at;
    }
    
    /**
     * Set checked in timestamp
     */
    public function set_checked_in_at( ?\DateTime $timestamp ): self {
        $this->checked_in_at = $timestamp;
        $this->checked_in = $timestamp !== null;
        return $this;
    }
    
    /**
     * Check in ticket
     */
    public function check_in(): self {
        return $this->set_checked_in( true );
    }
    
    /**
     * Get notes
     */
    public function get_notes(): string {
        return $this->notes;
    }
    
    /**
     * Set notes
     */
    public function set_notes( string $notes ): self {
        $this->notes = function_exists('wp_kses_post') ? wp_kses_post( $notes ) : strip_tags( $notes );
        return $this;
    }
    
    /**
     * Get created timestamp
     */
    public function get_created_at(): ?\DateTime {
        return $this->created_at;
    }
    
    /**
     * Set created timestamp
     */
    public function set_created_at( $timestamp ): self {
        if ( is_string( $timestamp ) ) {
            $this->created_at = new \DateTime( $timestamp );
        } elseif ( $timestamp instanceof \DateTime ) {
            $this->created_at = $timestamp;
        }
        
        return $this;
    }
    
    /**
     * Get updated timestamp
     */
    public function get_updated_at(): ?\DateTime {
        return $this->updated_at;
    }
    
    /**
     * Set updated timestamp
     */
    public function set_updated_at( $timestamp ): self {
        if ( is_string( $timestamp ) ) {
            $this->updated_at = new \DateTime( $timestamp );
        } elseif ( $timestamp instanceof \DateTime ) {
            $this->updated_at = $timestamp;
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
     * Check if ticket is valid
     */
    public function is_valid(): bool {
        return $this->status === 'valid';
    }
    
    /**
     * Check if ticket is used
     */
    public function is_used(): bool {
        return $this->status === 'used' || $this->checked_in;
    }
    
    /**
     * Check if ticket is cancelled
     */
    public function is_cancelled(): bool {
        return in_array( $this->status, [ 'cancelled', 'refunded' ], true );
    }
    
    /**
     * Use ticket (mark as used)
     */
    public function use_ticket(): self {
        $this->set_status( 'used' );
        $this->check_in();
        return $this;
    }
    
    /**
     * Cancel ticket
     */
    public function cancel_ticket(): self {
        return $this->set_status( 'cancelled' );
    }
    
    /**
     * Convert to array
     */
    public function to_array(): array {
        return [
            'id' => $this->id,
            'ticket_number' => $this->ticket_number,
            'order_id' => $this->order_id,
            'event_id' => $this->event_id,
            'session_id' => $this->session_id,
            'price_category' => $this->price_category,
            'type' => $this->type,
            'section' => $this->section,
            'row' => $this->row,
            'seat_number' => $this->seat_number,
            'price' => $this->price,
            'original_price' => $this->original_price,
            'currency' => $this->currency,
            'status' => $this->status,
            'customer_name' => $this->customer_name,
            'customer_email' => $this->customer_email,
            'qr_code' => $this->qr_code,
            'barcode' => $this->barcode,
            'checked_in' => $this->checked_in,
            'checked_in_at' => $this->checked_in_at?->format( 'Y-m-d H:i:s' ),
            'notes' => $this->notes,
            'created_at' => $this->created_at?->format( 'Y-m-d H:i:s' ),
            'updated_at' => $this->updated_at?->format( 'Y-m-d H:i:s' ),
            'bil24_id' => $this->bil24_id,
        ];
    }
    
    /**
     * CamelCase aliases for getters (for test compatibility)
     */
    public function getId(): ?int { return $this->get_id(); }
    public function getTicketNumber(): string { return $this->get_ticket_number(); }
    public function getOrderId(): ?int { return $this->get_order_id(); }
    public function getEventId(): ?int { return $this->get_event_id(); }
    public function getSessionId(): ?int { return $this->get_session_id(); }
    public function getPriceCategory(): string { return $this->get_price_category(); }
    public function getType(): string { return $this->get_type(); }
    public function getSection(): string { return $this->get_section(); }
    public function getRow(): string { return $this->get_row(); }
    public function getSeatNumber(): string { return $this->get_seat_number(); }
    public function getPrice(): float { return $this->get_price(); }
    public function getOriginalPrice(): float { return $this->get_original_price(); }
    public function getCurrency(): string { return $this->get_currency(); }
    public function getStatus(): string { return $this->get_status(); }
    public function getCustomerName(): string { return $this->get_customer_name(); }
    public function getCustomerEmail(): string { return $this->get_customer_email(); }
    public function getQrCode(): ?string { return $this->get_qr_code(); }
    public function getBarcode(): ?string { return $this->get_barcode(); }
    public function isCheckedIn(): bool { return $this->is_checked_in(); }
    public function getCheckedInAt(): ?string { return $this->checked_in_at?->format( 'Y-m-d H:i:s' ); }
    public function getNotes(): string { return $this->get_notes(); }
    public function getCreatedAt(): ?string { return $this->created_at?->format( 'Y-m-d H:i:s' ); }
    public function getUpdatedAt(): ?string { return $this->updated_at?->format( 'Y-m-d H:i:s' ); }
    public function getBil24Id(): ?string { return $this->get_bil24_id(); }
    
    /**
     * CamelCase aliases for setters (for test compatibility)
     */
    public function setId( ?int $id ): self { return $this->set_id( $id ); }
    public function setTicketNumber( string $ticket_number ): self { return $this->set_ticket_number( $ticket_number ); }
    public function setOrderId( ?int $order_id ): self { return $this->set_order_id( $order_id ); }
    public function setEventId( ?int $event_id ): self { return $this->set_event_id( $event_id ); }
    public function setSessionId( ?int $session_id ): self { return $this->set_session_id( $session_id ); }
    public function setPriceCategory( string $category ): self { return $this->set_price_category( $category ); }
    public function setType( string $type ): self { return $this->set_type( $type ); }
    public function setSection( string $section ): self { return $this->set_section( $section ); }
    public function setRow( string $row ): self { return $this->set_row( $row ); }
    public function setSeatNumber( string $seat_number ): self { return $this->set_seat_number( $seat_number ); }
    public function setPrice( float $price ): self { return $this->set_price( $price ); }
    public function setOriginalPrice( float $price ): self { return $this->set_original_price( $price ); }
    public function setCurrency( string $currency ): self { return $this->set_currency( $currency ); }
    public function setStatus( string $status ): self { return $this->set_status( $status ); }
    public function setCustomerName( string $name ): self { return $this->set_customer_name( $name ); }
    public function setCustomerEmail( string $email ): self { return $this->set_customer_email( $email ); }
    public function setQrCode( ?string $qr_code ): self { return $this->set_qr_code( $qr_code ); }
    public function setBarcode( ?string $barcode ): self { return $this->set_barcode( $barcode ); }
    public function setCheckedIn( bool $checked_in ): self { return $this->set_checked_in( $checked_in ); }
    public function setNotes( string $notes ): self { return $this->set_notes( $notes ); }
    public function setCreatedAt( $timestamp ): self { return $this->set_created_at( $timestamp ); }
    public function setUpdatedAt( $timestamp ): self { return $this->set_updated_at( $timestamp ); }
    public function setBil24Id( ?string $bil24_id ): self { return $this->set_bil24_id( $bil24_id ); }
    
    /**
     * toArray alias for test compatibility
     */
    public function toArray(): array {
        return $this->to_array();
    }
    
    /**
     * Check if ticket is empty
     */
    public function isEmpty(): bool {
        return empty( $this->ticket_number ) && $this->id === null;
    }
    
    /**
     * String representation
     */
    public function __toString(): string {
        $seat = $this->get_full_seat();
        return $this->ticket_number . ( $seat ? " ({$seat})" : '' );
    }
} 