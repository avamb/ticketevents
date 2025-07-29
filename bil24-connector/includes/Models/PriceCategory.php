<?php
namespace Bil24\Models;

defined( 'ABSPATH' ) || exit;

/**
 * Price Category model class for Bil24 Connector
 * 
 * @package Bil24Connector
 * @since 0.1.0
 */
class PriceCategory {
    
    /**
     * Price category ID
     */
    private ?int $id = null;
    
    /**
     * Event ID this category belongs to
     */
    private ?int $event_id = null;
    
    /**
     * Category name
     */
    private string $name = '';
    
    /**
     * Category description
     */
    private string $description = '';
    
    /**
     * Base price
     */
    private float $price = 0.0;
    
    /**
     * Currency
     */
    private string $currency = 'USD';
    
    /**
     * Category color (for UI display)
     */
    private string $color = '#000000';
    
    /**
     * Sort order
     */
    private int $sort_order = 0;
    
    /**
     * Category status
     */
    private string $status = 'active';
    
    /**
     * Minimum age restriction
     */
    private ?int $min_age = null;
    
    /**
     * Maximum age restriction
     */
    private ?int $max_age = null;
    
    /**
     * Requires special validation
     */
    private bool $requires_validation = false;
    
    /**
     * Special conditions or requirements
     */
    private string $conditions = '';
    
    /**
     * Available quantity
     */
    private ?int $available_quantity = null;
    
    /**
     * Maximum per order
     */
    private ?int $max_per_order = null;
    
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
     * Fill price category data from array
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
     * Get price category ID
     */
    public function get_id(): ?int {
        return $this->id;
    }
    
    /**
     * Set price category ID
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
     * Get category name
     */
    public function get_name(): string {
        return $this->name;
    }
    
    /**
     * Set category name
     */
    public function set_name( string $name ): self {
        $this->name = function_exists('sanitize_text_field') ? sanitize_text_field( $name ) : strip_tags( $name );
        return $this;
    }
    
    /**
     * Get category description
     */
    public function get_description(): string {
        return $this->description;
    }
    
    /**
     * Set category description
     */
    public function set_description( string $description ): self {
        $this->description = function_exists('wp_kses_post') ? wp_kses_post( $description ) : strip_tags( $description );
        return $this;
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
     * Get color
     */
    public function get_color(): string {
        return $this->color;
    }
    
    /**
     * Set color
     */
    public function set_color( string $color ): self {
        // Validate hex color format
        if ( preg_match( '/^#[0-9A-Fa-f]{6}$/', $color ) ) {
            $this->color = $color;
        }
        
        return $this;
    }
    
    /**
     * Get sort order
     */
    public function get_sort_order(): int {
        return $this->sort_order;
    }
    
    /**
     * Set sort order
     */
    public function set_sort_order( int $sort_order ): self {
        $this->sort_order = max( 0, $sort_order );
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
        $allowed_statuses = [ 'active', 'inactive', 'sold_out', 'coming_soon' ];
        
        if ( in_array( $status, $allowed_statuses, true ) ) {
            $this->status = $status;
        }
        
        return $this;
    }
    
    /**
     * Get minimum age
     */
    public function get_min_age(): ?int {
        return $this->min_age;
    }
    
    /**
     * Set minimum age
     */
    public function set_min_age( ?int $min_age ): self {
        if ( $min_age !== null ) {
            $this->min_age = max( 0, $min_age );
        } else {
            $this->min_age = null;
        }
        
        return $this;
    }
    
    /**
     * Get maximum age
     */
    public function get_max_age(): ?int {
        return $this->max_age;
    }
    
    /**
     * Set maximum age
     */
    public function set_max_age( ?int $max_age ): self {
        if ( $max_age !== null ) {
            $this->max_age = max( 0, $max_age );
        } else {
            $this->max_age = null;
        }
        
        return $this;
    }
    
    /**
     * Check if requires validation
     */
    public function requires_validation(): bool {
        return $this->requires_validation;
    }
    
    /**
     * Set requires validation
     */
    public function set_requires_validation( bool $requires_validation ): self {
        $this->requires_validation = $requires_validation;
        return $this;
    }
    
    /**
     * Get conditions
     */
    public function get_conditions(): string {
        return $this->conditions;
    }
    
    /**
     * Set conditions
     */
    public function set_conditions( string $conditions ): self {
        $this->conditions = function_exists('wp_kses_post') ? wp_kses_post( $conditions ) : strip_tags( $conditions );
        return $this;
    }
    
    /**
     * Get available quantity
     */
    public function get_available_quantity(): ?int {
        return $this->available_quantity;
    }
    
    /**
     * Set available quantity
     */
    public function set_available_quantity( ?int $quantity ): self {
        if ( $quantity !== null ) {
            $this->available_quantity = max( 0, $quantity );
        } else {
            $this->available_quantity = null;
        }
        
        return $this;
    }
    
    /**
     * Get maximum per order
     */
    public function get_max_per_order(): ?int {
        return $this->max_per_order;
    }
    
    /**
     * Set maximum per order
     */
    public function set_max_per_order( ?int $max ): self {
        if ( $max !== null ) {
            $this->max_per_order = max( 1, $max );
        } else {
            $this->max_per_order = null;
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
     * Check if category is active
     */
    public function is_active(): bool {
        return $this->status === 'active';
    }
    
    /**
     * Check if category is sold out
     */
    public function is_sold_out(): bool {
        return $this->status === 'sold_out' || 
               ( $this->available_quantity !== null && $this->available_quantity <= 0 );
    }
    
    /**
     * Check if category is available for purchase
     */
    public function is_available(): bool {
        return $this->is_active() && ! $this->is_sold_out();
    }
    
    /**
     * Validate age requirement
     */
    public function validate_age( int $age ): bool {
        if ( $this->min_age !== null && $age < $this->min_age ) {
            return false;
        }
        
        if ( $this->max_age !== null && $age > $this->max_age ) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Get age requirement text
     */
    public function get_age_requirement_text(): string {
        if ( $this->min_age === null && $this->max_age === null ) {
            return '';
        }
        
        if ( $this->min_age !== null && $this->max_age !== null ) {
            return "Ages {$this->min_age}-{$this->max_age}";
        }
        
        if ( $this->min_age !== null ) {
            return "Ages {$this->min_age}+";
        }
        
        if ( $this->max_age !== null ) {
            return "Ages {$this->max_age} and under";
        }
        
        return '';
    }
    
    /**
     * Reduce available quantity
     */
    public function reduce_quantity( int $amount ): self {
        if ( $this->available_quantity !== null ) {
            $this->available_quantity = max( 0, $this->available_quantity - $amount );
            
            // Auto-update status if sold out
            if ( $this->available_quantity <= 0 && $this->status === 'active' ) {
                $this->status = 'sold_out';
            }
        }
        
        return $this;
    }
    
    /**
     * Increase available quantity
     */
    public function increase_quantity( int $amount ): self {
        if ( $this->available_quantity !== null ) {
            $this->available_quantity += $amount;
            
            // Auto-update status if no longer sold out
            if ( $this->available_quantity > 0 && $this->status === 'sold_out' ) {
                $this->status = 'active';
            }
        }
        
        return $this;
    }
    
    /**
     * Format price with currency
     */
    public function get_formatted_price(): string {
        $price = number_format( $this->price, 2 );
        
        // Common currency symbols
        $symbols = [
            'USD' => '$',
            'EUR' => '€',
            'GBP' => '£',
            'JPY' => '¥',
            'RUB' => '₽'
        ];
        
        $symbol = $symbols[ $this->currency ] ?? $this->currency;
        
        return $symbol . $price;
    }
    
    /**
     * Convert to array
     */
    public function to_array(): array {
        return [
            'id' => $this->id,
            'event_id' => $this->event_id,
            'name' => $this->name,
            'description' => $this->description,
            'price' => $this->price,
            'currency' => $this->currency,
            'color' => $this->color,
            'sort_order' => $this->sort_order,
            'status' => $this->status,
            'min_age' => $this->min_age,
            'max_age' => $this->max_age,
            'requires_validation' => $this->requires_validation,
            'conditions' => $this->conditions,
            'available_quantity' => $this->available_quantity,
            'max_per_order' => $this->max_per_order,
            'bil24_id' => $this->bil24_id,
        ];
    }
    
    /**
     * CamelCase aliases for getters (for test compatibility)
     */
    public function getId(): ?int { return $this->get_id(); }
    public function getEventId(): ?int { return $this->get_event_id(); }
    public function getName(): string { return $this->get_name(); }
    public function getDescription(): string { return $this->get_description(); }
    public function getPrice(): float { return $this->get_price(); }
    public function getCurrency(): string { return $this->get_currency(); }
    public function getColor(): string { return $this->get_color(); }
    public function getSortOrder(): int { return $this->get_sort_order(); }
    public function getStatus(): string { return $this->get_status(); }
    public function getMinAge(): ?int { return $this->get_min_age(); }
    public function getMaxAge(): ?int { return $this->get_max_age(); }
    public function requiresValidation(): bool { return $this->requires_validation(); }
    public function getConditions(): string { return $this->get_conditions(); }
    public function getAvailableQuantity(): ?int { return $this->get_available_quantity(); }
    public function getMaxPerOrder(): ?int { return $this->get_max_per_order(); }
    public function getBil24Id(): ?string { return $this->get_bil24_id(); }
    
    /**
     * CamelCase aliases for setters (for test compatibility)
     */
    public function setId( ?int $id ): self { return $this->set_id( $id ); }
    public function setEventId( ?int $event_id ): self { return $this->set_event_id( $event_id ); }
    public function setName( string $name ): self { return $this->set_name( $name ); }
    public function setDescription( string $description ): self { return $this->set_description( $description ); }
    public function setPrice( float $price ): self { return $this->set_price( $price ); }
    public function setCurrency( string $currency ): self { return $this->set_currency( $currency ); }
    public function setColor( string $color ): self { return $this->set_color( $color ); }
    public function setSortOrder( int $sort_order ): self { return $this->set_sort_order( $sort_order ); }
    public function setStatus( string $status ): self { return $this->set_status( $status ); }
    public function setMinAge( ?int $min_age ): self { return $this->set_min_age( $min_age ); }
    public function setMaxAge( ?int $max_age ): self { return $this->set_max_age( $max_age ); }
    public function setRequiresValidation( bool $requires_validation ): self { return $this->set_requires_validation( $requires_validation ); }
    public function setConditions( string $conditions ): self { return $this->set_conditions( $conditions ); }
    public function setAvailableQuantity( ?int $quantity ): self { return $this->set_available_quantity( $quantity ); }
    public function setMaxPerOrder( ?int $max ): self { return $this->set_max_per_order( $max ); }
    public function setBil24Id( ?string $bil24_id ): self { return $this->set_bil24_id( $bil24_id ); }
    
    /**
     * toArray alias for test compatibility
     */
    public function toArray(): array {
        return $this->to_array();
    }
    
    /**
     * Check if price category is empty
     */
    public function isEmpty(): bool {
        return empty( $this->name ) && $this->id === null;
    }
    
    /**
     * String representation
     */
    public function __toString(): string {
        return $this->name . " ({$this->get_formatted_price()})";
    }
} 