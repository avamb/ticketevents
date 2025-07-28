<?php
namespace Bil24\Api;

use Bil24\Constants;
use Bil24\Utils;

defined( 'ABSPATH' ) || exit;

/**
 * Bil24 API Endpoints
 * 
 * High-level API methods for specific Bil24 operations
 * 
 * @package Bil24Connector
 * @since 0.1.0
 */
class Endpoints {

    /**
     * API Client instance
     */
    private Client $client;

    /**
     * Constructor
     */
    public function __construct( Client $client = null ) {
        $this->client = $client ?? new Client();
    }

    /**
     * EVENT METHODS
     */

    /**
     * Get all events
     */
    public function get_events( array $params = [] ): array {
        return $this->client->get( '/events', $params );
    }

    /**
     * Get single event by ID
     */
    public function get_event( int $event_id ): array {
        return $this->client->get( "/events/{$event_id}" );
    }

    /**
     * Create new event
     */
    public function create_event( array $event_data ): array {
        return $this->client->post( '/events', $event_data );
    }

    /**
     * Update existing event
     */
    public function update_event( int $event_id, array $event_data ): array {
        return $this->client->put( "/events/{$event_id}", $event_data );
    }

    /**
     * Delete event
     */
    public function delete_event( int $event_id ): array {
        return $this->client->delete( "/events/{$event_id}" );
    }

    /**
     * SESSION METHODS
     */

    /**
     * Get all sessions for an event
     */
    public function get_sessions( int $event_id, array $params = [] ): array {
        return $this->client->get( "/events/{$event_id}/sessions", $params );
    }

    /**
     * Get single session by ID
     */
    public function get_session( int $event_id, int $session_id ): array {
        return $this->client->get( "/events/{$event_id}/sessions/{$session_id}" );
    }

    /**
     * Create new session
     */
    public function create_session( int $event_id, array $session_data ): array {
        return $this->client->post( "/events/{$event_id}/sessions", $session_data );
    }

    /**
     * Update existing session
     */
    public function update_session( int $event_id, int $session_id, array $session_data ): array {
        return $this->client->put( "/events/{$event_id}/sessions/{$session_id}", $session_data );
    }

    /**
     * Delete session
     */
    public function delete_session( int $event_id, int $session_id ): array {
        return $this->client->delete( "/events/{$event_id}/sessions/{$session_id}" );
    }

    /**
     * Get session availability
     */
    public function get_session_availability( int $event_id, int $session_id ): array {
        return $this->client->get( "/events/{$event_id}/sessions/{$session_id}/availability" );
    }

    /**
     * ORDER METHODS
     */

    /**
     * Get all orders
     */
    public function get_orders( array $params = [] ): array {
        return $this->client->get( '/orders', $params );
    }

    /**
     * Get single order by ID
     */
    public function get_order( int $order_id ): array {
        return $this->client->get( "/orders/{$order_id}" );
    }

    /**
     * Create new order
     */
    public function create_order( array $order_data ): array {
        return $this->client->post( '/orders', $order_data );
    }

    /**
     * Update order status
     */
    public function update_order_status( int $order_id, string $status ): array {
        return $this->client->put( "/orders/{$order_id}/status", [ 'status' => $status ] );
    }

    /**
     * Cancel order
     */
    public function cancel_order( int $order_id, string $reason = '' ): array {
        return $this->client->put( "/orders/{$order_id}/cancel", [ 'reason' => $reason ] );
    }

    /**
     * Get order tickets
     */
    public function get_order_tickets( int $order_id ): array {
        return $this->client->get( "/orders/{$order_id}/tickets" );
    }

    /**
     * BOOKING METHODS
     */

    /**
     * Check seat availability
     */
    public function check_seat_availability( int $session_id, array $seat_ids ): array {
        return $this->client->post( "/sessions/{$session_id}/check-seats", [ 'seats' => $seat_ids ] );
    }

    /**
     * Reserve seats
     */
    public function reserve_seats( int $session_id, array $seat_ids, int $hold_time = 900 ): array {
        return $this->client->post( "/sessions/{$session_id}/reserve", [
            'seats' => $seat_ids,
            'hold_time' => $hold_time
        ] );
    }

    /**
     * Release seat reservation
     */
    public function release_reservation( string $reservation_id ): array {
        return $this->client->delete( "/reservations/{$reservation_id}" );
    }

    /**
     * Create reservation for tickets
     */
    public function create_reservation( array $data ): array {
        return $this->client->post( '/reservations', $data );
    }

    /**
     * Update reservation
     */
    public function update_reservation( string $reservation_id, array $data ): array {
        return $this->client->put( "/reservations/{$reservation_id}", $data );
    }

    /**
     * Cancel reservation
     */
    public function cancel_reservation( string $reservation_id ): array {
        return $this->client->delete( "/reservations/{$reservation_id}" );
    }

    /**
     * Extend reservation
     */
    public function extend_reservation( string $reservation_id, array $data ): array {
        return $this->client->post( "/reservations/{$reservation_id}/extend", $data );
    }

    /**
     * Confirm reservation (convert to confirmed tickets)
     */
    public function confirm_reservation( string $reservation_id ): array {
        return $this->client->post( "/reservations/{$reservation_id}/confirm", [] );
    }

    /**
     * Generate tickets for order
     */
    public function generate_tickets( string $order_id ): array {
        return $this->client->post( "/orders/{$order_id}/tickets", [] );
    }



    /**
     * Update order payment information
     */
    public function update_order_payment( string $order_id, array $payment_data ): array {
        return $this->client->put( "/orders/{$order_id}/payment", $payment_data );
    }

    /**
     * Refund order
     */
    public function refund_order( string $order_id, array $refund_data ): array {
        return $this->client->post( "/orders/{$order_id}/refunds", $refund_data );
    }

    /**
     * Create customer in Bil24
     */
    public function create_customer( array $customer_data ): array {
        return $this->client->post( '/customers', $customer_data );
    }

    /**
     * Update customer in Bil24
     */
    public function update_customer( string $customer_id, array $customer_data ): array {
        return $this->client->put( "/customers/{$customer_id}", $customer_data );
    }

    /**
     * Get customer from Bil24
     */
    public function get_customer( string $customer_id ): array {
        return $this->client->get( "/customers/{$customer_id}" );
    }

    /**
     * Update customer statistics
     */
    public function update_customer_stats( string $customer_id, array $stats_data ): array {
        return $this->client->put( "/customers/{$customer_id}/stats", $stats_data );
    }

    /**
     * Anonymize customer (GDPR compliance)
     */
    public function anonymize_customer( string $customer_id ): array {
        return $this->client->post( "/customers/{$customer_id}/anonymize", [] );
    }

    /**
     * Get event availability
     */
    public function get_event_availability( string $event_id ): array {
        return $this->client->get( "/events/{$event_id}/availability" );
    }

    /**
     * UTILITY METHODS
     */

    /**
     * Get venue information
     */
    public function get_venues( array $params = [] ): array {
        return $this->client->get( '/venues', $params );
    }

    /**
     * Get venue by ID
     */
    public function get_venue( int $venue_id ): array {
        return $this->client->get( "/venues/{$venue_id}" );
    }

    /**
     * Get venue seating plan
     */
    public function get_venue_seating( int $venue_id ): array {
        return $this->client->get( "/venues/{$venue_id}/seating" );
    }

    /**
     * Get pricing categories
     */
    public function get_price_categories( int $event_id ): array {
        return $this->client->get( "/events/{$event_id}/price-categories" );
    }

    /**
     * Get event statistics
     */
    public function get_event_stats( int $event_id ): array {
        return $this->client->get( "/events/{$event_id}/stats" );
    }

    /**
     * Get event sessions
     */
    public function get_event_sessions( string $event_id ): array {
        return $this->client->get( "/events/{$event_id}/sessions" );
    }

    /**
     * Get event price categories
     */
    public function get_event_price_categories( string $event_id ): array {
        return $this->client->get( "/events/{$event_id}/price-categories" );
    }

    /**
     * SYNC HELPERS
     */

    /**
     * Get events modified since timestamp
     */
    public function get_events_since( int $timestamp ): array {
        return $this->get_events( [ 'modified_since' => $timestamp ] );
    }

    /**
     * Get orders modified since timestamp
     */
    public function get_orders_since( int $timestamp ): array {
        return $this->get_orders( [ 'modified_since' => $timestamp ] );
    }

    /**
     * Get sessions modified since timestamp
     */
    public function get_sessions_since( int $event_id, int $timestamp ): array {
        return $this->get_sessions( $event_id, [ 'modified_since' => $timestamp ] );
    }

    /**
     * Test API connectivity
     */
    public function test_connection(): bool {
        return $this->client->test_connection();
    }

    /**
     * Clear API cache
     */
    public function clear_cache(): void {
        $this->client->clear_cache();
    }

    /**
     * Get API status and health check
     */
    public function get_status(): array {
        return $this->client->get( '/status' );
    }

    /**
     * Get API version information
     */
    public function get_version(): array {
        return $this->client->get( '/version' );
    }

    /**
     * Send webhook test
     */
    public function test_webhook( string $webhook_url ): array {
        return $this->client->post( '/webhooks/test', [
            'url' => $webhook_url
        ] );
    }

    /**
     * Get statistics and analytics
     */
    public function get_analytics( array $params = [] ): array {
        return $this->client->get( '/analytics', $params );
    }
} 