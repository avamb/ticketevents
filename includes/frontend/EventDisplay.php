<?php
namespace Bil24\Frontend;

use Bil24\Models\Event;
use Bil24\Models\Session;

defined( 'ABSPATH' ) || exit;

/**
 * Frontend Event Display Class
 * 
 * Handles event listing, detail pages, and booking interface
 * 
 * @package Bil24Connector
 * @since 0.1.0
 */
class EventDisplay {
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->init();
    }
    
    /**
     * Initialize frontend functionality
     */
    public function init(): void {
        add_action( 'init', [ $this, 'register_shortcodes' ] );
        add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_scripts' ] );
        add_filter( 'single_template', [ $this, 'event_single_template' ] );
        add_action( 'wp_ajax_bil24_get_event_sessions', [ $this, 'ajax_get_event_sessions' ] );
        add_action( 'wp_ajax_nopriv_bil24_get_event_sessions', [ $this, 'ajax_get_event_sessions' ] );
        add_action( 'wp_ajax_bil24_check_availability', [ $this, 'ajax_check_availability' ] );
        add_action( 'wp_ajax_nopriv_bil24_check_availability', [ $this, 'ajax_check_availability' ] );
    }
    
    /**
     * Register shortcodes
     */
    public function register_shortcodes(): void {
        add_shortcode( 'bil24_events', [ $this, 'events_shortcode' ] );
        add_shortcode( 'bil24_event', [ $this, 'single_event_shortcode' ] );
    }
    
    /**
     * Enqueue frontend scripts and styles
     */
    public function enqueue_scripts(): void {
        if ( $this->should_load_assets() ) {
            wp_enqueue_style( 
                'bil24-events', 
                plugin_dir_url( __FILE__ ) . '../../assets/css/events.css',
                [],
                '1.0.0'
            );
            
            wp_enqueue_script( 
                'bil24-events', 
                plugin_dir_url( __FILE__ ) . '../../assets/js/events.js',
                [ 'jquery' ],
                '1.0.0',
                true
            );
            
            wp_localize_script( 'bil24-events', 'bil24_ajax', [
                'url' => admin_url( 'admin-ajax.php' ),
                'nonce' => wp_create_nonce( 'bil24_frontend_nonce' ),
                'currency_symbol' => function_exists( 'get_woocommerce_currency_symbol' ) ? get_woocommerce_currency_symbol() : '₽',
                'messages' => [
                    'loading' => __( 'Loading...', 'bil24' ),
                    'no_sessions' => __( 'No sessions available', 'bil24' ),
                    'booking_error' => __( 'Booking error occurred', 'bil24' ),
                    'tickets_added' => __( 'Tickets added to cart', 'bil24' )
                ]
            ]);
        }
    }
    
    /**
     * Check if we should load assets
     */
    private function should_load_assets(): bool {
        global $post;
        
        // Load on event pages
        if ( is_singular( 'bil24_event' ) ) {
            return true;
        }
        
        // Load if shortcode is present
        if ( $post && has_shortcode( $post->post_content, 'bil24_events' ) ) {
            return true;
        }
        
        if ( $post && has_shortcode( $post->post_content, 'bil24_event' ) ) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Events listing shortcode
     * 
     * @param array $atts Shortcode attributes
     * @return string
     */
    public function events_shortcode( $atts ): string {
        $atts = shortcode_atts( [
            'limit' => 10,
            'category' => '',
            'venue' => '',
            'date_from' => '',
            'date_to' => '',
            'status' => 'published',
            'view' => 'grid', // grid, list, card
            'show_featured' => true,
            'show_filters' => true
        ], $atts );
        
        ob_start();
        $this->render_events_list( $atts );
        return ob_get_clean();
    }
    
    /**
     * Single event shortcode
     * 
     * @param array $atts Shortcode attributes
     * @return string
     */
    public function single_event_shortcode( $atts ): string {
        $atts = shortcode_atts( [
            'id' => 0,
            'show_sessions' => true,
            'show_booking' => true,
            'show_description' => true
        ], $atts );
        
        if ( empty( $atts['id'] ) ) {
            return __( 'Event ID is required', 'bil24' );
        }
        
        $event_post = get_post( $atts['id'] );
        if ( ! $event_post || $event_post->post_type !== 'bil24_event' ) {
            return __( 'Event not found', 'bil24' );
        }
        
        ob_start();
        $this->render_single_event( $event_post, $atts );
        return ob_get_clean();
    }
    
    /**
     * Custom single template for events
     */
    public function event_single_template( $template ) {
        if ( is_singular( 'bil24_event' ) ) {
            $custom_template = locate_template( 'single-bil24_event.php' );
            if ( ! $custom_template ) {
                $custom_template = $this->get_default_event_template();
            }
            return $custom_template;
        }
        
        return $template;
    }
    
    /**
     * Render events list
     * 
     * @param array $args Display arguments
     */
    private function render_events_list( array $args ): void {
        $events = $this->get_events( $args );
        
        echo '<div class="bil24-events-container">';
        
        if ( $args['show_filters'] ) {
            $this->render_event_filters( $args );
        }
        
        if ( ! empty( $events ) ) {
            echo '<div class="bil24-events-list bil24-view-' . esc_attr( $args['view'] ) . '">';
            
            foreach ( $events as $event_post ) {
                $this->render_event_item( $event_post, $args );
            }
            
            echo '</div>';
        } else {
            echo '<div class="bil24-no-events">';
            echo '<p>' . __( 'No events found.', 'bil24' ) . '</p>';
            echo '</div>';
        }
        
        echo '</div>';
    }
    
    /**
     * Render single event
     * 
     * @param \WP_Post $event_post Event post object
     * @param array $args Display arguments
     */
    private function render_single_event( \WP_Post $event_post, array $args ): void {
        $event = $this->get_event_from_post( $event_post );
        
        echo '<div class="bil24-single-event" data-event-id="' . esc_attr( $event_post->ID ) . '">';
        
        // Event header
        $this->render_event_header( $event, $event_post );
        
        // Event description
        if ( $args['show_description'] ) {
            $this->render_event_description( $event_post );
        }
        
        // Event sessions
        if ( $args['show_sessions'] ) {
            $this->render_event_sessions( $event_post->ID );
        }
        
        // Booking interface
        if ( $args['show_booking'] ) {
            $this->render_booking_interface( $event_post->ID );
        }
        
        echo '</div>';
    }
    
    /**
     * Render event filters
     * 
     * @param array $args Current arguments
     */
    private function render_event_filters( array $args ): void {
        echo '<div class="bil24-event-filters">';
        echo '<form class="bil24-filters-form" method="get">';
        
        // Date filter
        echo '<div class="bil24-filter-group">';
        echo '<label for="bil24-date-from">' . __( 'From Date:', 'bil24' ) . '</label>';
        echo '<input type="date" id="bil24-date-from" name="date_from" value="' . esc_attr( $args['date_from'] ) . '">';
        echo '</div>';
        
        echo '<div class="bil24-filter-group">';
        echo '<label for="bil24-date-to">' . __( 'To Date:', 'bil24' ) . '</label>';
        echo '<input type="date" id="bil24-date-to" name="date_to" value="' . esc_attr( $args['date_to'] ) . '">';
        echo '</div>';
        
        // Venue filter
        $venues = $this->get_available_venues();
        if ( ! empty( $venues ) ) {
            echo '<div class="bil24-filter-group">';
            echo '<label for="bil24-venue">' . __( 'Venue:', 'bil24' ) . '</label>';
            echo '<select id="bil24-venue" name="venue">';
            echo '<option value="">' . __( 'All Venues', 'bil24' ) . '</option>';
            foreach ( $venues as $venue ) {
                $selected = selected( $args['venue'], $venue, false );
                echo '<option value="' . esc_attr( $venue ) . '"' . $selected . '>' . esc_html( $venue ) . '</option>';
            }
            echo '</select>';
            echo '</div>';
        }
        
        // View toggle
        echo '<div class="bil24-filter-group bil24-view-toggle">';
        echo '<button type="button" class="bil24-view-btn" data-view="grid" title="' . __( 'Grid View', 'bil24' ) . '">⊞</button>';
        echo '<button type="button" class="bil24-view-btn" data-view="list" title="' . __( 'List View', 'bil24' ) . '">☰</button>';
        echo '</div>';
        
        echo '<div class="bil24-filter-group">';
        echo '<button type="submit" class="bil24-filter-submit">' . __( 'Filter', 'bil24' ) . '</button>';
        echo '</div>';
        
        echo '</form>';
        echo '</div>';
    }
    
    /**
     * Render single event item
     * 
     * @param \WP_Post $event_post Event post object
     * @param array $args Display arguments
     */
    private function render_event_item( \WP_Post $event_post, array $args ): void {
        $event = $this->get_event_from_post( $event_post );
        
        echo '<div class="bil24-event-item">';
        
        // Event image
        if ( has_post_thumbnail( $event_post->ID ) ) {
            echo '<div class="bil24-event-image">';
            echo '<a href="' . get_permalink( $event_post->ID ) . '">';
            echo get_the_post_thumbnail( $event_post->ID, 'medium' );
            echo '</a>';
            echo '</div>';
        }
        
        echo '<div class="bil24-event-content">';
        
        // Event title
        echo '<h3 class="bil24-event-title">';
        echo '<a href="' . get_permalink( $event_post->ID ) . '">' . esc_html( $event_post->post_title ) . '</a>';
        echo '</h3>';
        
        // Event meta
        echo '<div class="bil24-event-meta">';
        
        if ( $event->get_start_date() ) {
            echo '<div class="bil24-event-date">';
            echo '<span class="bil24-icon">📅</span>';
            echo '<span>' . $event->get_start_date()->format( 'F j, Y' ) . '</span>';
            echo '</div>';
        }
        
        if ( $event->get_venue() ) {
            echo '<div class="bil24-event-venue">';
            echo '<span class="bil24-icon">📍</span>';
            echo '<span>' . esc_html( $event->get_venue() ) . '</span>';
            echo '</div>';
        }
        
        if ( $event->get_price() > 0 ) {
            echo '<div class="bil24-event-price">';
            echo '<span class="bil24-icon">💰</span>';
            echo '<span>' . $this->format_price( $event->get_price(), $event->get_currency() ) . '</span>';
            echo '</div>';
        }
        
        echo '</div>';
        
        // Event excerpt
        if ( $event_post->post_excerpt ) {
            echo '<div class="bil24-event-excerpt">';
            echo '<p>' . esc_html( $event_post->post_excerpt ) . '</p>';
            echo '</div>';
        }
        
        // Action buttons
        echo '<div class="bil24-event-actions">';
        echo '<a href="' . get_permalink( $event_post->ID ) . '" class="bil24-btn bil24-btn-primary">' . __( 'View Details', 'bil24' ) . '</a>';
        echo '<button class="bil24-btn bil24-btn-secondary bil24-quick-book" data-event-id="' . esc_attr( $event_post->ID ) . '">' . __( 'Quick Book', 'bil24' ) . '</button>';
        echo '</div>';
        
        echo '</div>'; // .bil24-event-content
        echo '</div>'; // .bil24-event-item
    }
    
    /**
     * Render event header
     * 
     * @param Event $event Event object
     * @param \WP_Post $event_post Event post object
     */
    private function render_event_header( Event $event, \WP_Post $event_post ): void {
        echo '<div class="bil24-event-header">';
        
        if ( has_post_thumbnail( $event_post->ID ) ) {
            echo '<div class="bil24-event-banner">';
            echo get_the_post_thumbnail( $event_post->ID, 'large' );
            echo '</div>';
        }
        
        echo '<div class="bil24-event-title-section">';
        echo '<h1 class="bil24-event-title">' . esc_html( $event_post->post_title ) . '</h1>';
        
        echo '<div class="bil24-event-meta">';
        
        if ( $event->get_start_date() ) {
            echo '<div class="bil24-meta-item bil24-event-date">';
            echo '<span class="bil24-icon">📅</span>';
            echo '<span class="bil24-meta-label">' . __( 'Date:', 'bil24' ) . '</span>';
            echo '<span class="bil24-meta-value">' . $event->get_start_date()->format( 'F j, Y \a\t g:i A' ) . '</span>';
            echo '</div>';
        }
        
        if ( $event->get_venue() ) {
            echo '<div class="bil24-meta-item bil24-event-venue">';
            echo '<span class="bil24-icon">📍</span>';
            echo '<span class="bil24-meta-label">' . __( 'Venue:', 'bil24' ) . '</span>';
            echo '<span class="bil24-meta-value">' . esc_html( $event->get_venue() ) . '</span>';
            echo '</div>';
        }
        
        if ( $event->get_price() > 0 ) {
            echo '<div class="bil24-meta-item bil24-event-price">';
            echo '<span class="bil24-icon">💰</span>';
            echo '<span class="bil24-meta-label">' . __( 'Starting from:', 'bil24' ) . '</span>';
            echo '<span class="bil24-meta-value">' . $this->format_price( $event->get_price(), $event->get_currency() ) . '</span>';
            echo '</div>';
        }
        
        echo '</div>'; // .bil24-event-meta
        echo '</div>'; // .bil24-event-title-section
        echo '</div>'; // .bil24-event-header
    }
    
    /**
     * Render event description
     * 
     * @param \WP_Post $event_post Event post object
     */
    private function render_event_description( \WP_Post $event_post ): void {
        if ( ! empty( $event_post->post_content ) ) {
            echo '<div class="bil24-event-description">';
            echo '<h3>' . __( 'About This Event', 'bil24' ) . '</h3>';
            echo '<div class="bil24-event-content">';
            echo apply_filters( 'the_content', $event_post->post_content );
            echo '</div>';
            echo '</div>';
        }
    }
    
    /**
     * Render event sessions
     * 
     * @param int $event_id Event post ID
     */
    private function render_event_sessions( int $event_id ): void {
        echo '<div class="bil24-event-sessions">';
        echo '<h3>' . __( 'Available Sessions', 'bil24' ) . '</h3>';
        echo '<div class="bil24-sessions-container" data-event-id="' . esc_attr( $event_id ) . '">';
        echo '<div class="bil24-loading">' . __( 'Loading sessions...', 'bil24' ) . '</div>';
        echo '</div>';
        echo '</div>';
    }
    
    /**
     * Render booking interface
     * 
     * @param int $event_id Event post ID
     */
    private function render_booking_interface( int $event_id ): void {
        echo '<div class="bil24-booking-interface">';
        echo '<h3>' . __( 'Book Tickets', 'bil24' ) . '</h3>';
        
        echo '<form class="bil24-booking-form" data-event-id="' . esc_attr( $event_id ) . '">';
        
        echo '<div class="bil24-booking-step bil24-step-sessions">';
        echo '<h4>' . __( 'Select Session', 'bil24' ) . '</h4>';
        echo '<div class="bil24-session-selector"></div>';
        echo '</div>';
        
        echo '<div class="bil24-booking-step bil24-step-tickets" style="display: none;">';
        echo '<h4>' . __( 'Select Tickets', 'bil24' ) . '</h4>';
        echo '<div class="bil24-ticket-selector"></div>';
        echo '</div>';
        
        echo '<div class="bil24-booking-step bil24-step-summary" style="display: none;">';
        echo '<h4>' . __( 'Booking Summary', 'bil24' ) . '</h4>';
        echo '<div class="bil24-booking-summary"></div>';
        echo '<button type="submit" class="bil24-btn bil24-btn-primary bil24-add-to-cart">' . __( 'Add to Cart', 'bil24' ) . '</button>';
        echo '</div>';
        
        echo '</form>';
        echo '</div>';
    }
    
    /**
     * Get events for listing
     * 
     * @param array $args Query arguments
     * @return array
     */
    private function get_events( array $args ): array {
        $query_args = [
            'post_type' => 'bil24_event',
            'post_status' => 'publish',
            'posts_per_page' => intval( $args['limit'] ),
            'meta_query' => []
        ];
        
        // Date filters
        if ( ! empty( $args['date_from'] ) || ! empty( $args['date_to'] ) ) {
            $date_query = [];
            
            if ( ! empty( $args['date_from'] ) ) {
                $date_query['after'] = $args['date_from'];
            }
            
            if ( ! empty( $args['date_to'] ) ) {
                $date_query['before'] = $args['date_to'];
            }
            
            $query_args['meta_query'][] = [
                'key' => '_bil24_start_date',
                'value' => $date_query,
                'type' => 'DATE',
                'compare' => 'BETWEEN'
            ];
        }
        
        // Venue filter
        if ( ! empty( $args['venue'] ) ) {
            $query_args['meta_query'][] = [
                'key' => '_bil24_venue',
                'value' => $args['venue'],
                'compare' => '='
            ];
        }
        
        // Status filter
        if ( ! empty( $args['status'] ) && $args['status'] !== 'published' ) {
            $query_args['meta_query'][] = [
                'key' => '_bil24_status',
                'value' => $args['status'],
                'compare' => '='
            ];
        }
        
        $query = new \WP_Query( $query_args );
        return $query->posts;
    }
    
    /**
     * Get event object from post
     * 
     * @param \WP_Post $post Event post object
     * @return Event
     */
    private function get_event_from_post( \WP_Post $post ): Event {
        $meta = get_post_meta( $post->ID );
        
        $event_data = [
            'id' => $post->ID,
            'title' => $post->post_title,
            'description' => $post->post_content,
            'status' => get_post_meta( $post->ID, '_bil24_status', true ),
            'venue' => get_post_meta( $post->ID, '_bil24_venue', true ),
            'price' => floatval( get_post_meta( $post->ID, '_bil24_price', true ) ),
            'currency' => get_post_meta( $post->ID, '_bil24_currency', true ) ?: 'USD',
            'bil24_id' => get_post_meta( $post->ID, '_bil24_id', true )
        ];
        
        // Parse dates
        $start_date = get_post_meta( $post->ID, '_bil24_start_date', true );
        if ( $start_date ) {
            $event_data['start_date'] = new \DateTime( $start_date );
        }
        
        $end_date = get_post_meta( $post->ID, '_bil24_end_date', true );
        if ( $end_date ) {
            $event_data['end_date'] = new \DateTime( $end_date );
        }
        
        return new Event( $event_data );
    }
    
    /**
     * Get available venues for filtering
     * 
     * @return array
     */
    private function get_available_venues(): array {
        global $wpdb;
        
        $venues = $wpdb->get_col( "
            SELECT DISTINCT meta_value 
            FROM {$wpdb->postmeta} 
            WHERE meta_key = '_bil24_venue' 
            AND meta_value != '' 
            ORDER BY meta_value ASC
        " );
        
        return $venues ?: [];
    }
    
    /**
     * Get default event template
     * 
     * @return string
     */
    private function get_default_event_template(): string {
        // Return path to built-in template
        return plugin_dir_path( __FILE__ ) . '../templates/single-bil24_event.php';
    }
    
    /**
     * AJAX: Get event sessions
     */
    public function ajax_get_event_sessions(): void {
        check_ajax_referer( 'bil24_frontend_nonce', 'nonce' );
        
        $event_id = intval( $_POST['event_id'] ?? 0 );
        
        if ( ! $event_id ) {
            wp_send_json_error( 'Invalid event ID' );
        }
        
        $sessions = $this->get_event_sessions( $event_id );
        
        wp_send_json_success( [
            'sessions' => $sessions,
            'html' => $this->render_sessions_html( $sessions )
        ]);
    }
    
    /**
     * AJAX: Check ticket availability
     */
    public function ajax_check_availability(): void {
        check_ajax_referer( 'bil24_frontend_nonce', 'nonce' );
        
        $session_id = intval( $_POST['session_id'] ?? 0 );
        $quantity = intval( $_POST['quantity'] ?? 1 );
        
        if ( ! $session_id ) {
            wp_send_json_error( 'Invalid session ID' );
        }
        
        $availability = $this->check_session_availability( $session_id, $quantity );
        
        wp_send_json_success( $availability );
    }
    
    /**
     * Get sessions for an event
     * 
     * @param int $event_id Event post ID
     * @return array
     */
    private function get_event_sessions( int $event_id ): array {
        $query = new \WP_Query([
            'post_type' => 'bil24_session',
            'post_status' => 'publish',
            'meta_query' => [
                [
                    'key' => '_bil24_event_id',
                    'value' => $event_id,
                    'compare' => '='
                ]
            ],
            'orderby' => 'meta_value',
            'meta_key' => '_bil24_session_date',
            'order' => 'ASC'
        ]);
        
        return $query->posts;
    }
    
    /**
     * Render sessions HTML
     * 
     * @param array $sessions Session posts
     * @return string
     */
    private function render_sessions_html( array $sessions ): string {
        if ( empty( $sessions ) ) {
            return '<p class="bil24-no-sessions">' . __( 'No sessions available for this event.', 'bil24' ) . '</p>';
        }
        
        ob_start();
        
        echo '<div class="bil24-sessions-list">';
        
        foreach ( $sessions as $session_post ) {
            $session_date = get_post_meta( $session_post->ID, '_bil24_session_date', true );
            $session_time = get_post_meta( $session_post->ID, '_bil24_session_time', true );
            $venue = get_post_meta( $session_post->ID, '_bil24_venue', true );
            $capacity = intval( get_post_meta( $session_post->ID, '_bil24_capacity', true ) );
            $available = intval( get_post_meta( $session_post->ID, '_bil24_available', true ) );
            $price = floatval( get_post_meta( $session_post->ID, '_bil24_price', true ) );
            
            echo '<div class="bil24-session-item" data-session-id="' . esc_attr( $session_post->ID ) . '">';
            
            echo '<div class="bil24-session-info">';
            echo '<div class="bil24-session-datetime">';
            echo '<span class="bil24-session-date">' . date( 'M j, Y', strtotime( $session_date ) ) . '</span>';
            echo '<span class="bil24-session-time">' . date( 'g:i A', strtotime( $session_time ) ) . '</span>';
            echo '</div>';
            
            if ( $venue ) {
                echo '<div class="bil24-session-venue">' . esc_html( $venue ) . '</div>';
            }
            
            echo '<div class="bil24-session-availability">';
            if ( $available > 0 ) {
                echo '<span class="bil24-available">' . sprintf( __( '%d tickets available', 'bil24' ), $available ) . '</span>';
            } else {
                echo '<span class="bil24-sold-out">' . __( 'Sold Out', 'bil24' ) . '</span>';
            }
            echo '</div>';
            echo '</div>';
            
            echo '<div class="bil24-session-booking">';
            echo '<div class="bil24-session-price">' . $this->format_price( $price ) . '</div>';
            
            if ( $available > 0 ) {
                echo '<button class="bil24-btn bil24-btn-primary bil24-select-session" data-session-id="' . esc_attr( $session_post->ID ) . '">';
                echo __( 'Select', 'bil24' );
                echo '</button>';
            } else {
                echo '<button class="bil24-btn bil24-btn-disabled" disabled>';
                echo __( 'Sold Out', 'bil24' );
                echo '</button>';
            }
            echo '</div>';
            
            echo '</div>'; // .bil24-session-item
        }
        
        echo '</div>'; // .bil24-sessions-list
        
        return ob_get_clean();
    }
    
    /**
     * Check session availability
     * 
     * @param int $session_id Session post ID
     * @param int $quantity Requested quantity
     * @return array
     */
    private function check_session_availability( int $session_id, int $quantity ): array {
        $available = intval( get_post_meta( $session_id, '_bil24_available', true ) );
        $capacity = intval( get_post_meta( $session_id, '_bil24_capacity', true ) );
        
        return [
            'available' => $available,
            'capacity' => $capacity,
            'can_book' => $available >= $quantity,
            'requested' => $quantity
        ];
    }
    
    /**
     * Format price with currency
     * 
     * @param float $price Price amount
     * @param string $currency Currency code
     * @return string Formatted price
     */
    private function format_price( float $price, string $currency = 'USD' ): string {
        // Use WooCommerce price formatting if available
        if ( function_exists( 'wc_price' ) ) {
            return wc_price( $price );
        }
        
        // Fallback price formatting
        $currency_symbols = [
            'USD' => '$',
            'EUR' => '€',
            'GBP' => '£',
            'RUB' => '₽',
            'UAH' => '₴'
        ];
        
        $symbol = $currency_symbols[$currency] ?? $currency;
        
        return $symbol . number_format( $price, 2 );
    }
} 