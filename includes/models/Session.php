<?php
namespace Bil24\Models;

defined( 'ABSPATH' ) || exit;

/**
 * Session model class for Bil24 Connector
 * 
 * @package Bil24Connector
 * @since 0.1.0
 */
class Session {
    
    /**
     * Session ID
     */
    private ?int $id = null;
    
    /**
     * Event ID this session belongs to
     */
    private ?int $event_id = null;
    
    /**
     * Session title
     */
    private string $title = '';
    
    /**
     * Session description
     */
    private string $description = '';
    
    /**
     * Session start date and time
     */
    private ?\DateTime $start_datetime = null;
    
    /**
     * Session end date and time
     */
    private ?\DateTime $end_datetime = null;
    
    /**
     * Venue ID
     */
    private ?int $venue_id = null;
    
    /**
     * Total capacity
     */
    private int $capacity = 0;
    
    /**
     * Available seats
     */
    private int $available_seats = 0;
    
    /**
     * Reserved seats
     */
    private int $reserved_seats = 0;
    
    /**
     * Sold seats
     */
    private int $sold_seats = 0;
    
    /**
     * Session status
     */
    private string $status = 'scheduled';
    
    /**
     * Base price
     */
    private float $base_price = 0.0;
    
    /**
     * Currency
     */
    private string $currency = 'USD';
    
    /**
     * Bil24 external ID
     */
    private ?string $bil24_id = null;
    
    /**
     * Last sync timestamp
     */
    private ?\DateTime $last_sync = null;
    
    /**
     * Constructor
     */
    public function __construct( array $data = [] ) {
        $this->fill( $data );
    }
    
    /**
     * Fill session data from array
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
     * Get session ID
     */
    public function get_id(): ?int {
        return $this->id;
    }
    
    /**
     * Set session ID
     */
    public function set_id( ?int $id ): self {
        $this->id = $id;
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
     * Get session title
     */
    public function get_title(): string {
        return $this->title;
    }
    
    /**
     * Set session title
     */
    public function set_title( string $title ): self {
        $this->title = function_exists('sanitize_text_field') ? sanitize_text_field( $title ) : strip_tags( $title );
        return $this;
    }
    
    /**
     * Get session description
     */
    public function get_description(): string {
        return $this->description;
    }
    
    /**
     * Set session description
     */
    public function set_description( string $description ): self {
        $this->description = function_exists('wp_kses_post') ? wp_kses_post( $description ) : strip_tags( $description );
        return $this;
    }
    
    /**
     * Get start datetime
     */
    public function get_start_datetime(): ?\DateTime {
        return $this->start_datetime;
    }
    
    /**
     * Set start datetime
     */
    public function set_start_datetime( $datetime ): self {
        if ( is_string( $datetime ) ) {
            $this->start_datetime = new \DateTime( $datetime );
        } elseif ( $datetime instanceof \DateTime ) {
            $this->start_datetime = $datetime;
        }
        
        return $this;
    }
    
    /**
     * Get end datetime
     */
    public function get_end_datetime(): ?\DateTime {
        return $this->end_datetime;
    }
    
    /**
     * Set end datetime
     */
    public function set_end_datetime( $datetime ): self {
        if ( is_string( $datetime ) ) {
            $this->end_datetime = new \DateTime( $datetime );
        } elseif ( $datetime instanceof \DateTime ) {
            $this->end_datetime = $datetime;
        }
        
        return $this;
    }
    
    /**
     * Get venue ID
     */
    public function get_venue_id(): ?int {
        return $this->venue_id;
    }
    
    /**
     * Set venue ID
     */
    public function set_venue_id( ?int $venue_id ): self {
        $this->venue_id = $venue_id;
        return $this;
    }
    
    /**
     * Get capacity
     */
    public function get_capacity(): int {
        return $this->capacity;
    }
    
    /**
     * Set capacity
     */
    public function set_capacity( int $capacity ): self {
        $this->capacity = max( 0, $capacity );
        return $this;
    }
    
    /**
     * Get available seats
     */
    public function get_available_seats(): int {
        return $this->available_seats;
    }
    
    /**
     * Set available seats
     */
    public function set_available_seats( int $available_seats ): self {
        $this->available_seats = max( 0, $available_seats );
        return $this;
    }
    
    /**
     * Get reserved seats
     */
    public function get_reserved_seats(): int {
        return $this->reserved_seats;
    }
    
    /**
     * Set reserved seats
     */
    public function set_reserved_seats( int $reserved_seats ): self {
        $this->reserved_seats = max( 0, $reserved_seats );
        return $this;
    }
    
    /**
     * Get sold seats
     */
    public function get_sold_seats(): int {
        return $this->sold_seats;
    }
    
    /**
     * Set sold seats
     */
    public function set_sold_seats( int $sold_seats ): self {
        $this->sold_seats = max( 0, $sold_seats );
        return $this;
    }
    
    /**
     * Get session status
     */
    public function get_status(): string {
        return $this->status;
    }
    
    /**
     * Set session status
     */
    public function set_status( string $status ): self {
        $allowed_statuses = [ 'scheduled', 'active', 'cancelled', 'completed', 'sold_out' ];
        
        if ( in_array( $status, $allowed_statuses, true ) ) {
            $this->status = $status;
        }
        
        return $this;
    }
    
    /**
     * Get base price
     */
    public function get_base_price(): float {
        return $this->base_price;
    }
    
    /**
     * Set base price
     */
    public function set_base_price( float $price ): self {
        $this->base_price = max( 0.0, $price );
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
     * Get last sync timestamp
     */
    public function get_last_sync(): ?\DateTime {
        return $this->last_sync;
    }
    
    /**
     * Set last sync timestamp
     */
    public function set_last_sync( $timestamp ): self {
        if ( is_string( $timestamp ) ) {
            $this->last_sync = new \DateTime( $timestamp );
        } elseif ( $timestamp instanceof \DateTime ) {
            $this->last_sync = $timestamp;
        } elseif ( is_int( $timestamp ) ) {
            $this->last_sync = new \DateTime( '@' . $timestamp );
        }
        
        return $this;
    }
    
    /**
     * Check if session is available for booking
     */
    public function is_available(): bool {
        return $this->status === 'active' && $this->available_seats > 0;
    }
    
    /**
     * Check if session is sold out
     */
    public function is_sold_out(): bool {
        return $this->available_seats <= 0 || $this->status === 'sold_out';
    }
    
    /**
     * Update seat counts
     */
    public function update_seat_counts( int $available, int $reserved, int $sold ): self {
        $this->available_seats = max( 0, $available );
        $this->reserved_seats = max( 0, $reserved );
        $this->sold_seats = max( 0, $sold );
        
        // Auto-update status if sold out
        if ( $this->available_seats <= 0 && $this->status === 'active' ) {
            $this->status = 'sold_out';
        }
        
        return $this;
    }
    
    /**
     * Convert to array
     */
    public function to_array(): array {
        return [
            'id' => $this->id,
            'event_id' => $this->event_id,
            'title' => $this->title,
            'description' => $this->description,
            'start_datetime' => $this->start_datetime?->format( 'Y-m-d H:i:s' ),
            'end_datetime' => $this->end_datetime?->format( 'Y-m-d H:i:s' ),
            'venue_id' => $this->venue_id,
            'capacity' => $this->capacity,
            'available_seats' => $this->available_seats,
            'reserved_seats' => $this->reserved_seats,
            'sold_seats' => $this->sold_seats,
            'status' => $this->status,
            'base_price' => $this->base_price,
            'currency' => $this->currency,
            'bil24_id' => $this->bil24_id,
            'last_sync' => $this->last_sync?->format( 'Y-m-d H:i:s' ),
        ];
    }
    
    /**
     * CamelCase aliases for getters (for test compatibility)
     */
    public function getId(): ?int { return $this->get_id(); }
    public function getEventId(): ?int { return $this->get_event_id(); }
    public function getTitle(): string { return $this->get_title(); }
    public function getDescription(): string { return $this->get_description(); }
    public function getStartDatetime(): ?string { return $this->start_datetime?->format( 'Y-m-d H:i:s' ); }
    public function getEndDatetime(): ?string { return $this->end_datetime?->format( 'Y-m-d H:i:s' ); }
    public function getVenueId(): ?int { return $this->get_venue_id(); }
    public function getCapacity(): int { return $this->get_capacity(); }
    public function getAvailableSeats(): int { return $this->get_available_seats(); }
    public function getReservedSeats(): int { return $this->get_reserved_seats(); }
    public function getSoldSeats(): int { return $this->get_sold_seats(); }
    public function getStatus(): string { return $this->get_status(); }
    public function getBasePrice(): float { return $this->get_base_price(); }
    public function getCurrency(): string { return $this->get_currency(); }
    public function getBil24Id(): ?string { return $this->get_bil24_id(); }
    
    /**
     * CamelCase aliases for setters (for test compatibility)
     */
    public function setId( ?int $id ): self { return $this->set_id( $id ); }
    public function setEventId( ?int $event_id ): self { return $this->set_event_id( $event_id ); }
    public function setTitle( string $title ): self { return $this->set_title( $title ); }
    public function setDescription( string $description ): self { return $this->set_description( $description ); }
    public function setStartDatetime( $datetime ): self { return $this->set_start_datetime( $datetime ); }
    public function setEndDatetime( $datetime ): self { return $this->set_end_datetime( $datetime ); }
    public function setVenueId( ?int $venue_id ): self { return $this->set_venue_id( $venue_id ); }
    public function setCapacity( int $capacity ): self { return $this->set_capacity( $capacity ); }
    public function setAvailableSeats( int $available_seats ): self { return $this->set_available_seats( $available_seats ); }
    public function setReservedSeats( int $reserved_seats ): self { return $this->set_reserved_seats( $reserved_seats ); }
    public function setSoldSeats( int $sold_seats ): self { return $this->set_sold_seats( $sold_seats ); }
    public function setStatus( string $status ): self { return $this->set_status( $status ); }
    public function setBasePrice( float $price ): self { return $this->set_base_price( $price ); }
    public function setCurrency( string $currency ): self { return $this->set_currency( $currency ); }
    public function setBil24Id( ?string $bil24_id ): self { return $this->set_bil24_id( $bil24_id ); }
    
    /**
     * toArray alias for test compatibility
     */
    public function toArray(): array {
        return $this->to_array();
    }
    
    /**
     * Check if session is empty
     */
    public function isEmpty(): bool {
        return empty( $this->title ) && $this->id === null && $this->event_id === null;
    }
    
    /**
     * String representation
     */
    public function __toString(): string {
        $datetime = $this->start_datetime ? $this->start_datetime->format( 'Y-m-d H:i' ) : '';
        return $this->title . ( $datetime ? " ({$datetime})" : '' );
    }
    
    /**
     * Create from WordPress post
     */
    public static function from_post( \WP_Post $post ): self {
        $session = new self();
        $session->set_id( $post->ID );
        $session->set_title( $post->post_title );
        $session->set_description( $post->post_content );
        $session->set_status( $post->post_status );
        
        // Get meta data (only when WordPress functions are available)
        if ( function_exists('get_post_meta') && class_exists('Bil24\\Constants') ) {
            $bil24_id = get_post_meta( $post->ID, \Bil24\Constants::META_BIL24_ID, true );
            if ( $bil24_id ) {
                $session->set_bil24_id( $bil24_id );
            }
            
            $event_id = get_post_meta( $post->ID, \Bil24\Constants::META_EVENT_ID, true );
            if ( $event_id ) {
                $session->set_event_id( intval( $event_id ) );
            }
        }
        
        return $session;
    }
} 