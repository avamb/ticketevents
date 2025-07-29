<?php
namespace Bil24\Models;

defined( 'ABSPATH' ) || exit;

/**
 * Event model class for Bil24 Connector
 * 
 * @package Bil24Connector
 * @since 0.1.0
 */
class Event {
    
    /**
     * Event ID
     */
    private ?int $id = null;
    
    /**
     * Event title
     */
    private string $title = '';
    
    /**
     * Event description
     */
    private string $description = '';
    
    /**
     * Event start date
     */
    private ?\DateTime $start_date = null;
    
    /**
     * Event end date
     */
    private ?\DateTime $end_date = null;
    
    /**
     * Event status
     */
    private string $status = 'draft';
    
    /**
     * Event venue
     */
    private string $venue = '';
    
    /**
     * Event price
     */
    private float $price = 0.0;
    
    /**
     * Event currency
     */
    private string $currency = 'USD';
    
    /**
     * Bil24 external ID
     */
    private ?string $bil24_id = null;
    
    /**
     * Constructor
     */
    public function __construct( array $data = [] ) {
        $this->fill( $data );
    }
    
    /**
     * Fill event data from array
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
     * Get event ID
     */
    public function get_id(): ?int {
        return $this->id;
    }
    
    /**
     * Set event ID
     */
    public function set_id( ?int $id ): self {
        $this->id = $id;
        return $this;
    }
    
    /**
     * Get event title
     */
    public function get_title(): string {
        return $this->title;
    }
    
    /**
     * Set event title
     */
    public function set_title( string $title ): self {
        // Use simple strip_tags instead of sanitize_text_field when WordPress is not available
        $this->title = function_exists('sanitize_text_field') ? sanitize_text_field( $title ) : strip_tags( $title );
        return $this;
    }
    
    /**
     * Get event description
     */
    public function get_description(): string {
        return $this->description;
    }
    
    /**
     * Set event description
     */
    public function set_description( string $description ): self {
        // Use simple strip_tags instead of wp_kses_post when WordPress is not available
        $this->description = function_exists('wp_kses_post') ? wp_kses_post( $description ) : strip_tags( $description );
        return $this;
    }
    
    /**
     * Get start date
     */
    public function get_start_date(): ?\DateTime {
        return $this->start_date;
    }
    
    /**
     * Set start date
     */
    public function set_start_date( $date ): self {
        if ( is_string( $date ) ) {
            $this->start_date = new \DateTime( $date );
        } elseif ( $date instanceof \DateTime ) {
            $this->start_date = $date;
        }
        
        return $this;
    }
    
    /**
     * Get end date
     */
    public function get_end_date(): ?\DateTime {
        return $this->end_date;
    }
    
    /**
     * Set end date
     */
    public function set_end_date( $date ): self {
        if ( is_string( $date ) ) {
            $this->end_date = new \DateTime( $date );
        } elseif ( $date instanceof \DateTime ) {
            $this->end_date = $date;
        }
        
        return $this;
    }
    
    /**
     * Get event status
     */
    public function get_status(): string {
        return $this->status;
    }
    
    /**
     * Set event status
     */
    public function set_status( string $status ): self {
        $allowed_statuses = [ 'draft', 'published', 'cancelled', 'sold_out', 'active' ];
        
        if ( in_array( $status, $allowed_statuses, true ) ) {
            $this->status = $status;
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
     * Get event venue
     */
    public function get_venue(): string {
        return $this->venue;
    }
    
    /**
     * Set event venue
     */
    public function set_venue( string $venue ): self {
        $this->venue = function_exists('sanitize_text_field') ? sanitize_text_field( $venue ) : strip_tags( $venue );
        return $this;
    }
    
    /**
     * Get event price
     */
    public function get_price(): float {
        return $this->price;
    }
    
    /**
     * Set event price
     */
    public function set_price( float $price ): self {
        $this->price = max( 0.0, $price ); // Ensure price is not negative
        return $this;
    }
    
    /**
     * Get event currency
     */
    public function get_currency(): string {
        return $this->currency;
    }
    
    /**
     * Set event currency
     */
    public function set_currency( string $currency ): self {
        $this->currency = strtoupper( $currency );
        return $this;
    }
    
    /**
     * Convert to array
     */
    public function to_array(): array {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'start_date' => $this->start_date?->format( 'Y-m-d' ),
            'end_date' => $this->end_date?->format( 'Y-m-d' ),
            'venue' => $this->venue,
            'price' => $this->price,
            'currency' => $this->currency,
            'status' => $this->status,
            'bil24_id' => $this->bil24_id,
        ];
    }
    
    /**
     * CamelCase aliases for getters (for test compatibility)
     */
    public function getId(): ?int { return $this->get_id(); }
    public function getTitle(): string { return $this->get_title(); }
    public function getDescription(): string { return $this->get_description(); }
    public function getStartDate(): ?string { return $this->start_date?->format( 'Y-m-d' ); }
    public function getEndDate(): ?string { return $this->end_date?->format( 'Y-m-d' ); }
    public function getVenue(): string { return $this->get_venue(); }
    public function getPrice(): float { return $this->get_price(); }
    public function getCurrency(): string { return $this->get_currency(); }
    public function getStatus(): string { return $this->get_status(); }
    public function getBil24Id(): ?string { return $this->get_bil24_id(); }
    
    /**
     * CamelCase aliases for setters (for test compatibility)
     */
    public function setId( ?int $id ): self { return $this->set_id( $id ); }
    public function setTitle( string $title ): self { return $this->set_title( $title ); }
    public function setDescription( string $description ): self { return $this->set_description( $description ); }
    public function setStartDate( $date ): self { return $this->set_start_date( $date ); }
    public function setEndDate( $date ): self { return $this->set_end_date( $date ); }
    public function setVenue( string $venue ): self { return $this->set_venue( $venue ); }
    public function setPrice( float $price ): self { return $this->set_price( $price ); }
    public function setCurrency( string $currency ): self { return $this->set_currency( $currency ); }
    public function setStatus( string $status ): self { return $this->set_status( $status ); }
    public function setBil24Id( ?string $bil24_id ): self { return $this->set_bil24_id( $bil24_id ); }
    
    /**
     * toArray alias for test compatibility
     */
    public function toArray(): array {
        return $this->to_array();
    }
    
    /**
     * Check if event is empty
     */
    public function isEmpty(): bool {
        return empty( $this->title ) && empty( $this->description ) && $this->id === null;
    }
    
    /**
     * String representation
     */
    public function __toString(): string {
        $date = $this->start_date ? $this->start_date->format( 'Y-m-d' ) : '';
        return $this->title . ( $date ? " ({$date})" : '' );
    }
    
    /**
     * Create from WordPress post
     */
    public static function from_post( \WP_Post $post ): self {
        $event = new self();
        $event->set_id( $post->ID );
        $event->set_title( $post->post_title );
        $event->set_description( $post->post_content );
        $event->set_status( $post->post_status );
        
        // Get meta data (only when WordPress functions are available)
        if ( function_exists('get_post_meta') && class_exists('Bil24\\Constants') ) {
            $bil24_id = get_post_meta( $post->ID, Constants::META_BIL24_ID, true );
            if ( $bil24_id ) {
                $event->set_bil24_id( $bil24_id );
            }
        }
        
        return $event;
    }
} 