<?php
namespace Bil24\Models;

defined( 'ABSPATH' ) || exit;

/**
 * Venue model class for Bil24 Connector
 * 
 * @package Bil24Connector
 * @since 0.1.0
 */
class Venue {
    
    /**
     * Venue ID
     */
    private ?int $id = null;
    
    /**
     * Venue name
     */
    private string $name = '';
    
    /**
     * Venue description
     */
    private string $description = '';
    
    /**
     * Address line 1
     */
    private string $address_line_1 = '';
    
    /**
     * Address line 2
     */
    private string $address_line_2 = '';
    
    /**
     * City
     */
    private string $city = '';
    
    /**
     * State/Province
     */
    private string $state = '';
    
    /**
     * Postal code
     */
    private string $postal_code = '';
    
    /**
     * Country
     */
    private string $country = '';
    
    /**
     * Latitude
     */
    private ?float $latitude = null;
    
    /**
     * Longitude
     */
    private ?float $longitude = null;
    
    /**
     * Phone number
     */
    private string $phone = '';
    
    /**
     * Email
     */
    private string $email = '';
    
    /**
     * Website URL
     */
    private string $website = '';
    
    /**
     * Total capacity
     */
    private int $capacity = 0;
    
    /**
     * Venue type
     */
    private string $type = 'theater';
    
    /**
     * Venue status
     */
    private string $status = 'active';
    
    /**
     * Timezone
     */
    private string $timezone = 'UTC';
    
    /**
     * Amenities
     */
    private array $amenities = [];
    
    /**
     * Accessibility features
     */
    private array $accessibility = [];
    
    /**
     * Seating plan data
     */
    private ?array $seating_plan = null;
    
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
     * Fill venue data from array
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
     * Get venue ID
     */
    public function get_id(): ?int {
        return $this->id;
    }
    
    /**
     * Set venue ID
     */
    public function set_id( ?int $id ): self {
        $this->id = $id;
        return $this;
    }
    
    /**
     * Get venue name
     */
    public function get_name(): string {
        return $this->name;
    }
    
    /**
     * Set venue name
     */
    public function set_name( string $name ): self {
        $this->name = function_exists('sanitize_text_field') ? sanitize_text_field( $name ) : strip_tags( $name );
        return $this;
    }
    
    /**
     * Get venue description
     */
    public function get_description(): string {
        return $this->description;
    }
    
    /**
     * Set venue description
     */
    public function set_description( string $description ): self {
        $this->description = function_exists('wp_kses_post') ? wp_kses_post( $description ) : strip_tags( $description );
        return $this;
    }
    
    /**
     * Get address line 1
     */
    public function get_address_line_1(): string {
        return $this->address_line_1;
    }
    
    /**
     * Set address line 1
     */
    public function set_address_line_1( string $address ): self {
        $this->address_line_1 = function_exists('sanitize_text_field') ? sanitize_text_field( $address ) : strip_tags( $address );
        return $this;
    }
    
    /**
     * Get address line 2
     */
    public function get_address_line_2(): string {
        return $this->address_line_2;
    }
    
    /**
     * Set address line 2
     */
    public function set_address_line_2( string $address ): self {
        $this->address_line_2 = function_exists('sanitize_text_field') ? sanitize_text_field( $address ) : strip_tags( $address );
        return $this;
    }
    
    /**
     * Get city
     */
    public function get_city(): string {
        return $this->city;
    }
    
    /**
     * Set city
     */
    public function set_city( string $city ): self {
        $this->city = function_exists('sanitize_text_field') ? sanitize_text_field( $city ) : strip_tags( $city );
        return $this;
    }
    
    /**
     * Get state
     */
    public function get_state(): string {
        return $this->state;
    }
    
    /**
     * Set state
     */
    public function set_state( string $state ): self {
        $this->state = function_exists('sanitize_text_field') ? sanitize_text_field( $state ) : strip_tags( $state );
        return $this;
    }
    
    /**
     * Get postal code
     */
    public function get_postal_code(): string {
        return $this->postal_code;
    }
    
    /**
     * Set postal code
     */
    public function set_postal_code( string $postal_code ): self {
        $this->postal_code = function_exists('sanitize_text_field') ? sanitize_text_field( $postal_code ) : strip_tags( $postal_code );
        return $this;
    }
    
    /**
     * Get country
     */
    public function get_country(): string {
        return $this->country;
    }
    
    /**
     * Set country
     */
    public function set_country( string $country ): self {
        $this->country = function_exists('sanitize_text_field') ? sanitize_text_field( $country ) : strip_tags( $country );
        return $this;
    }
    
    /**
     * Get latitude
     */
    public function get_latitude(): ?float {
        return $this->latitude;
    }
    
    /**
     * Set latitude
     */
    public function set_latitude( ?float $latitude ): self {
        if ( $latitude !== null && ( $latitude < -90 || $latitude > 90 ) ) {
            throw new \InvalidArgumentException( 'Latitude must be between -90 and 90 degrees' );
        }
        $this->latitude = $latitude;
        return $this;
    }
    
    /**
     * Get longitude
     */
    public function get_longitude(): ?float {
        return $this->longitude;
    }
    
    /**
     * Set longitude
     */
    public function set_longitude( ?float $longitude ): self {
        if ( $longitude !== null && ( $longitude < -180 || $longitude > 180 ) ) {
            throw new \InvalidArgumentException( 'Longitude must be between -180 and 180 degrees' );
        }
        $this->longitude = $longitude;
        return $this;
    }
    
    /**
     * Get phone
     */
    public function get_phone(): string {
        return $this->phone;
    }
    
    /**
     * Set phone
     */
    public function set_phone( string $phone ): self {
        $this->phone = function_exists('sanitize_text_field') ? sanitize_text_field( $phone ) : strip_tags( $phone );
        return $this;
    }
    
    /**
     * Get email
     */
    public function get_email(): string {
        return $this->email;
    }
    
    /**
     * Set email
     */
    public function set_email( string $email ): self {
        if ( function_exists( 'sanitize_email' ) ) {
            $this->email = sanitize_email( $email );
        } else {
            $this->email = filter_var( $email, FILTER_SANITIZE_EMAIL );
        }
        return $this;
    }
    
    /**
     * Get website
     */
    public function get_website(): string {
        return $this->website;
    }
    
    /**
     * Set website
     */
    public function set_website( string $website ): self {
        if ( function_exists( 'esc_url_raw' ) ) {
            $this->website = esc_url_raw( $website );
        } else {
            $this->website = filter_var( $website, FILTER_SANITIZE_URL );
        }
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
     * Get venue type
     */
    public function get_type(): string {
        return $this->type;
    }
    
    /**
     * Set venue type
     */
    public function set_type( string $type ): self {
        $allowed_types = [ 'theater', 'concert_hall', 'stadium', 'arena', 'club', 'auditorium', 'conference_center', 'outdoor', 'other' ];
        
        if ( in_array( $type, $allowed_types, true ) ) {
            $this->type = $type;
        }
        
        return $this;
    }
    
    /**
     * Get venue status
     */
    public function get_status(): string {
        return $this->status;
    }
    
    /**
     * Set venue status
     */
    public function set_status( string $status ): self {
        $allowed_statuses = [ 'active', 'inactive', 'maintenance', 'closed' ];
        
        if ( in_array( $status, $allowed_statuses, true ) ) {
            $this->status = $status;
        }
        
        return $this;
    }
    
    /**
     * Get timezone
     */
    public function get_timezone(): string {
        return $this->timezone;
    }
    
    /**
     * Set timezone
     */
    public function set_timezone( string $timezone ): self {
        // Validate timezone
        if ( in_array( $timezone, timezone_identifiers_list() ) ) {
            $this->timezone = $timezone;
        }
        
        return $this;
    }
    
    /**
     * Get amenities
     */
    public function get_amenities(): array {
        return $this->amenities;
    }
    
    /**
     * Set amenities
     */
    public function set_amenities( array $amenities ): self {
        $this->amenities = array_map( 'sanitize_text_field', $amenities );
        return $this;
    }
    
    /**
     * Add amenity
     */
    public function add_amenity( string $amenity ): self {
        $sanitized_amenity = function_exists('sanitize_text_field') ? sanitize_text_field( $amenity ) : strip_tags( $amenity );
        if ( ! in_array( $sanitized_amenity, $this->amenities, true ) ) {
            $this->amenities[] = $sanitized_amenity;
        }
        return $this;
    }
    
    /**
     * Remove amenity
     */
    public function remove_amenity( string $amenity ): self {
        $key = array_search( $amenity, $this->amenities, true );
        if ( $key !== false ) {
            unset( $this->amenities[ $key ] );
            $this->amenities = array_values( $this->amenities ); // Re-index
        }
        return $this;
    }
    
    /**
     * Get accessibility features
     */
    public function get_accessibility(): array {
        return $this->accessibility;
    }
    
    /**
     * Set accessibility features
     */
    public function set_accessibility( array $accessibility ): self {
        $this->accessibility = array_map( 'sanitize_text_field', $accessibility );
        return $this;
    }
    
    /**
     * Add accessibility feature
     */
    public function add_accessibility_feature( string $feature ): self {
        $sanitized_feature = function_exists('sanitize_text_field') ? sanitize_text_field( $feature ) : strip_tags( $feature );
        if ( ! in_array( $sanitized_feature, $this->accessibility, true ) ) {
            $this->accessibility[] = $sanitized_feature;
        }
        return $this;
    }
    
    /**
     * Remove accessibility feature
     */
    public function remove_accessibility_feature( string $feature ): self {
        $key = array_search( $feature, $this->accessibility, true );
        if ( $key !== false ) {
            unset( $this->accessibility[ $key ] );
            $this->accessibility = array_values( $this->accessibility ); // Re-index
        }
        return $this;
    }
    
    /**
     * Get seating plan
     */
    public function get_seating_plan(): ?array {
        return $this->seating_plan;
    }
    
    /**
     * Set seating plan
     */
    public function set_seating_plan( ?array $seating_plan ): self {
        $this->seating_plan = $seating_plan;
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
     * Get full address
     */
    public function get_full_address(): string {
        $parts = array_filter( [
            $this->address_line_1,
            $this->address_line_2,
            $this->city,
            $this->state,
            $this->postal_code,
            $this->country
        ] );
        
        return implode( ', ', $parts );
    }
    
    /**
     * Check if venue has coordinates
     */
    public function has_coordinates(): bool {
        return $this->latitude !== null && $this->longitude !== null;
    }
    
    /**
     * Check if venue is active
     */
    public function is_active(): bool {
        return $this->status === 'active';
    }
    
    /**
     * Convert to array
     */
    public function to_array(): array {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'address_line_1' => $this->address_line_1,
            'address_line_2' => $this->address_line_2,
            'city' => $this->city,
            'state' => $this->state,
            'postal_code' => $this->postal_code,
            'country' => $this->country,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'phone' => $this->phone,
            'email' => $this->email,
            'website' => $this->website,
            'capacity' => $this->capacity,
            'type' => $this->type,
            'status' => $this->status,
            'timezone' => $this->timezone,
            'amenities' => $this->amenities,
            'accessibility' => $this->accessibility,
            'seating_plan' => $this->seating_plan,
            'bil24_id' => $this->bil24_id,
        ];
    }
    
    /**
     * CamelCase aliases for getters (for test compatibility)
     */
    public function getId(): ?int { return $this->get_id(); }
    public function getName(): string { return $this->get_name(); }
    public function getDescription(): string { return $this->get_description(); }
    public function getAddressLine1(): string { return $this->get_address_line_1(); }
    public function getAddressLine2(): string { return $this->get_address_line_2(); }
    public function getCity(): string { return $this->get_city(); }
    public function getState(): string { return $this->get_state(); }
    public function getPostalCode(): string { return $this->get_postal_code(); }
    public function getCountry(): string { return $this->get_country(); }
    public function getLatitude(): ?float { return $this->get_latitude(); }
    public function getLongitude(): ?float { return $this->get_longitude(); }
    public function getPhone(): string { return $this->get_phone(); }
    public function getEmail(): string { return $this->get_email(); }
    public function getWebsite(): string { return $this->get_website(); }
    public function getCapacity(): int { return $this->get_capacity(); }
    public function getType(): string { return $this->get_type(); }
    public function getStatus(): string { return $this->get_status(); }
    public function getTimezone(): string { return $this->get_timezone(); }
    public function getAmenities(): array { return $this->get_amenities(); }
    public function getAccessibility(): array { return $this->get_accessibility(); }
    public function getSeatingPlan(): ?array { return $this->get_seating_plan(); }
    public function getBil24Id(): ?string { return $this->get_bil24_id(); }
    
    /**
     * CamelCase aliases for setters (for test compatibility)
     */
    public function setId( ?int $id ): self { return $this->set_id( $id ); }
    public function setName( string $name ): self { return $this->set_name( $name ); }
    public function setDescription( string $description ): self { return $this->set_description( $description ); }
    public function setAddressLine1( string $address ): self { return $this->set_address_line_1( $address ); }
    public function setAddressLine2( string $address ): self { return $this->set_address_line_2( $address ); }
    public function setCity( string $city ): self { return $this->set_city( $city ); }
    public function setState( string $state ): self { return $this->set_state( $state ); }
    public function setPostalCode( string $postal_code ): self { return $this->set_postal_code( $postal_code ); }
    public function setCountry( string $country ): self { return $this->set_country( $country ); }
    public function setLatitude( ?float $latitude ): self { return $this->set_latitude( $latitude ); }
    public function setLongitude( ?float $longitude ): self { return $this->set_longitude( $longitude ); }
    public function setPhone( string $phone ): self { return $this->set_phone( $phone ); }
    public function setEmail( string $email ): self { return $this->set_email( $email ); }
    public function setWebsite( string $website ): self { return $this->set_website( $website ); }
    public function setCapacity( int $capacity ): self { return $this->set_capacity( $capacity ); }
    public function setType( string $type ): self { return $this->set_type( $type ); }
    public function setStatus( string $status ): self { return $this->set_status( $status ); }
    public function setTimezone( string $timezone ): self { return $this->set_timezone( $timezone ); }
    public function setAmenities( array $amenities ): self { return $this->set_amenities( $amenities ); }
    public function setAccessibility( array $accessibility ): self { return $this->set_accessibility( $accessibility ); }
    public function setSeatingPlan( ?array $seating_plan ): self { return $this->set_seating_plan( $seating_plan ); }
    public function setBil24Id( ?string $bil24_id ): self { return $this->set_bil24_id( $bil24_id ); }
    
    /**
     * toArray alias for test compatibility
     */
    public function toArray(): array {
        return $this->to_array();
    }
    
    /**
     * Check if venue is empty
     */
    public function isEmpty(): bool {
        return empty( $this->name ) && $this->id === null;
    }
    
    /**
     * String representation
     */
    public function __toString(): string {
        return $this->name . ( $this->city ? " ({$this->city})" : '' );
    }
} 