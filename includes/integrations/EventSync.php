<?php
namespace Bil24\Integrations;

use Bil24\Api\Endpoints;
use Bil24\Constants;
use Bil24\Models\Event;
use Bil24\Utils;

defined( 'ABSPATH' ) || exit;

/**
 * Event Synchronization between WordPress and Bil24
 * 
 * Handles bidirectional sync, conflict resolution, scheduled sync jobs, and manual triggers
 * 
 * @package Bil24Connector
 * @since 0.1.0
 */
class EventSync {

    /**
     * API Endpoints instance
     */
    private Endpoints $api;

    /**
     * Sync direction constants
     */
    private const SYNC_TO_BIL24 = 'to_bil24';
    private const SYNC_FROM_BIL24 = 'from_bil24';
    private const SYNC_BIDIRECTIONAL = 'bidirectional';

    /**
     * Constructor
     */
    public function __construct( Endpoints $api = null ) {
        $this->api = $api ?? new Endpoints();
        $this->register_hooks();
    }

    /**
     * Register WordPress hooks
     */
    private function register_hooks(): void {
        // Auto sync on event save
        add_action( 'save_post_' . Constants::CPT_EVENT, [ $this, 'on_event_save' ], 10, 3 );
        
        // Auto sync on event deletion
        add_action( 'before_delete_post', [ $this, 'on_event_delete' ], 10, 2 );
        
        // Scheduled sync hooks
        add_action( Constants::HOOK_SYNC_CATALOG, [ $this, 'scheduled_sync' ] );
        
        // Admin actions
        add_action( 'wp_ajax_bil24_sync_events', [ $this, 'ajax_manual_sync' ] );
        add_action( 'wp_ajax_bil24_sync_single_event', [ $this, 'ajax_sync_single_event' ] );
        
        // Add admin notices for sync status
        add_action( 'admin_notices', [ $this, 'show_sync_notices' ] );
    }

    /**
     * Manual sync trigger (AJAX)
     */
    public function ajax_manual_sync(): void {
        check_ajax_referer( 'bil24_sync_events' );
        
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( 'Insufficient permissions' );
        }

        $direction = sanitize_text_field( $_POST['direction'] ?? self::SYNC_BIDIRECTIONAL );
        
        try {
            $result = $this->sync_events( $direction );
            
            wp_send_json_success( [
                'message' => 'Синхронизация завершена успешно',
                'stats' => $result
            ] );
        } catch ( \Exception $e ) {
            Utils::log( 'Manual sync failed: ' . $e->getMessage(), Constants::LOG_LEVEL_ERROR );
            wp_send_json_error( [
                'message' => 'Ошибка синхронизации: ' . $e->getMessage()
            ] );
        }
    }

    /**
     * Manual sync for single event (AJAX)
     */
    public function ajax_sync_single_event(): void {
        check_ajax_referer( 'bil24_sync_single_event' );
        
        if ( ! current_user_can( 'edit_posts' ) ) {
            wp_die( 'Insufficient permissions' );
        }

        $post_id = intval( $_POST['post_id'] ?? 0 );
        $direction = sanitize_text_field( $_POST['direction'] ?? self::SYNC_TO_BIL24 );
        
        if ( ! $post_id ) {
            wp_send_json_error( [ 'message' => 'Invalid post ID' ] );
        }

        try {
            if ( $direction === self::SYNC_TO_BIL24 ) {
                $result = $this->sync_event_to_bil24( $post_id );
            } else {
                $result = $this->sync_event_from_bil24( $post_id );
            }
            
            wp_send_json_success( [
                'message' => 'Событие синхронизировано успешно',
                'result' => $result
            ] );
        } catch ( \Exception $e ) {
            Utils::log( "Single event sync failed for post {$post_id}: " . $e->getMessage(), Constants::LOG_LEVEL_ERROR );
            wp_send_json_error( [
                'message' => 'Ошибка синхронизации: ' . $e->getMessage()
            ] );
        }
    }

    /**
     * Auto sync on event save
     */
    public function on_event_save( int $post_id, \WP_Post $post, bool $update ): void {
        // Skip auto-drafts and revisions
        if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
            return;
        }

        // Skip if we're currently syncing to avoid loops
        if ( get_transient( "bil24_syncing_event_{$post_id}" ) ) {
            return;
        }

        // Only sync published events
        if ( $post->post_status !== 'publish' ) {
            return;
        }

        try {
            $this->sync_event_to_bil24( $post_id );
        } catch ( \Exception $e ) {
            Utils::log( "Auto sync failed for event {$post_id}: " . $e->getMessage(), Constants::LOG_LEVEL_ERROR );
        }
    }

    /**
     * Auto sync on event deletion
     */
    public function on_event_delete( int $post_id, \WP_Post $post ): void {
        if ( $post->post_type !== Constants::CPT_EVENT ) {
            return;
        }

        $bil24_id = get_post_meta( $post_id, Constants::META_BIL24_ID, true );
        
        if ( $bil24_id ) {
            try {
                $this->api->delete_event( intval( $bil24_id ) );
                Utils::log( "Event {$bil24_id} deleted from Bil24", Constants::LOG_LEVEL_INFO );
            } catch ( \Exception $e ) {
                Utils::log( "Failed to delete event {$bil24_id} from Bil24: " . $e->getMessage(), Constants::LOG_LEVEL_ERROR );
            }
        }
    }

    /**
     * Scheduled sync
     */
    public function scheduled_sync(): void {
        try {
            $result = $this->sync_events( self::SYNC_BIDIRECTIONAL );
            Utils::log( 'Scheduled sync completed: ' . wp_json_encode( $result ), Constants::LOG_LEVEL_INFO );
            
            // Update sync status
            update_option( Constants::OPTION_SYNC_STATUS, [
                'last_sync' => time(),
                'status' => 'success',
                'result' => $result
            ] );
        } catch ( \Exception $e ) {
            Utils::log( 'Scheduled sync failed: ' . $e->getMessage(), Constants::LOG_LEVEL_ERROR );
            
            update_option( Constants::OPTION_SYNC_STATUS, [
                'last_sync' => time(),
                'status' => 'error',
                'error' => $e->getMessage()
            ] );
        }
    }

    /**
     * Main sync events method
     */
    public function sync_events( string $direction = self::SYNC_BIDIRECTIONAL ): array {
        $stats = [
            'to_bil24' => [ 'created' => 0, 'updated' => 0, 'errors' => 0 ],
            'from_bil24' => [ 'created' => 0, 'updated' => 0, 'errors' => 0 ]
        ];

        if ( $direction === self::SYNC_TO_BIL24 || $direction === self::SYNC_BIDIRECTIONAL ) {
            $stats['to_bil24'] = $this->sync_all_events_to_bil24();
        }

        if ( $direction === self::SYNC_FROM_BIL24 || $direction === self::SYNC_BIDIRECTIONAL ) {
            $stats['from_bil24'] = $this->sync_all_events_from_bil24();
        }

        return $stats;
    }

    /**
     * Sync all WordPress events to Bil24
     */
    private function sync_all_events_to_bil24(): array {
        $stats = [ 'created' => 0, 'updated' => 0, 'errors' => 0 ];

        $events = get_posts( [
            'post_type' => Constants::CPT_EVENT,
            'post_status' => 'publish',
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

        foreach ( $events as $event_post ) {
            try {
                $result = $this->sync_event_to_bil24( $event_post->ID );
                
                if ( $result['action'] === 'created' ) {
                    $stats['created']++;
                } else {
                    $stats['updated']++;
                }
            } catch ( \Exception $e ) {
                $stats['errors']++;
                Utils::log( "Failed to sync event {$event_post->ID} to Bil24: " . $e->getMessage(), Constants::LOG_LEVEL_ERROR );
            }
        }

        return $stats;
    }

    /**
     * Sync all Bil24 events to WordPress
     */
    private function sync_all_events_from_bil24(): array {
        $stats = [ 'created' => 0, 'updated' => 0, 'errors' => 0 ];

        try {
            // Get events modified since last sync
            $last_sync = get_option( 'bil24_last_sync_time', time() - DAY_IN_SECONDS );
            $bil24_events = $this->api->get_events_since( $last_sync );

            foreach ( $bil24_events as $bil24_event ) {
                try {
                    $result = $this->sync_event_from_bil24_data( $bil24_event );
                    
                    if ( $result['action'] === 'created' ) {
                        $stats['created']++;
                    } else {
                        $stats['updated']++;
                    }
                } catch ( \Exception $e ) {
                    $stats['errors']++;
                    Utils::log( "Failed to sync event {$bil24_event['id']} from Bil24: " . $e->getMessage(), Constants::LOG_LEVEL_ERROR );
                }
            }

            // Update last sync time
            update_option( 'bil24_last_sync_time', time() );
        } catch ( \Exception $e ) {
            Utils::log( 'Failed to fetch events from Bil24: ' . $e->getMessage(), Constants::LOG_LEVEL_ERROR );
            throw $e;
        }

        return $stats;
    }

    /**
     * Sync single WordPress event to Bil24
     */
    public function sync_event_to_bil24( int $post_id ): array {
        // Prevent sync loops
        set_transient( "bil24_syncing_event_{$post_id}", true, 300 );

        try {
            $post = get_post( $post_id );
            if ( ! $post || $post->post_type !== Constants::CPT_EVENT ) {
                throw new \Exception( 'Invalid event post' );
            }

            $event = Event::from_post( $post );
            $bil24_id = $event->get_bil24_id();

            $event_data = $this->prepare_event_data_for_bil24( $event );

            if ( $bil24_id ) {
                // Update existing event
                $response = $this->api->update_event( intval( $bil24_id ), $event_data );
                $action = 'updated';
            } else {
                // Create new event
                $response = $this->api->create_event( $event_data );
                $bil24_id = $response['id'] ?? null;
                
                if ( $bil24_id ) {
                    update_post_meta( $post_id, Constants::META_BIL24_ID, $bil24_id );
                }
                $action = 'created';
            }

            // Update sync status
            update_post_meta( $post_id, Constants::META_SYNC_STATUS, 'synced' );
            update_post_meta( $post_id, Constants::META_LAST_SYNC, time() );

            Utils::log( "Event {$post_id} {$action} in Bil24 (ID: {$bil24_id})", Constants::LOG_LEVEL_INFO );

            return [
                'action' => $action,
                'post_id' => $post_id,
                'bil24_id' => $bil24_id,
                'response' => $response
            ];
        } finally {
            delete_transient( "bil24_syncing_event_{$post_id}" );
        }
    }

    /**
     * Sync single event from Bil24 to WordPress
     */
    public function sync_event_from_bil24( int $post_id ): array {
        $bil24_id = get_post_meta( $post_id, Constants::META_BIL24_ID, true );
        
        if ( ! $bil24_id ) {
            throw new \Exception( 'Event does not have Bil24 ID' );
        }

        $bil24_event = $this->api->get_event( intval( $bil24_id ) );
        return $this->sync_event_from_bil24_data( $bil24_event, $post_id );
    }

    /**
     * Sync event from Bil24 data
     */
    private function sync_event_from_bil24_data( array $bil24_event, int $existing_post_id = null ): array {
        $bil24_id = $bil24_event['id'] ?? null;
        
        if ( ! $bil24_id ) {
            throw new \Exception( 'Invalid Bil24 event data' );
        }

        // Find existing WordPress post
        if ( ! $existing_post_id ) {
            $existing_posts = get_posts( [
                'post_type' => Constants::CPT_EVENT,
                'meta_key' => Constants::META_BIL24_ID,
                'meta_value' => $bil24_id,
                'posts_per_page' => 1
            ] );
            
            $existing_post_id = $existing_posts ? $existing_posts[0]->ID : null;
        }

        // Prevent sync loops
        if ( $existing_post_id ) {
            set_transient( "bil24_syncing_event_{$existing_post_id}", true, 300 );
        }

        try {
            $post_data = $this->prepare_post_data_from_bil24( $bil24_event );

            if ( $existing_post_id ) {
                // Update existing post
                $post_data['ID'] = $existing_post_id;
                $post_id = wp_update_post( $post_data );
                $action = 'updated';
            } else {
                // Create new post
                $post_id = wp_insert_post( $post_data );
                $action = 'created';
            }

            if ( is_wp_error( $post_id ) ) {
                throw new \Exception( 'Failed to save WordPress post: ' . $post_id->get_error_message() );
            }

            // Update meta data
            update_post_meta( $post_id, Constants::META_BIL24_ID, $bil24_id );
            update_post_meta( $post_id, Constants::META_BIL24_DATA, $bil24_event );
            update_post_meta( $post_id, Constants::META_SYNC_STATUS, 'synced' );
            update_post_meta( $post_id, Constants::META_LAST_SYNC, time() );

            Utils::log( "Event {$bil24_id} {$action} in WordPress (Post ID: {$post_id})", Constants::LOG_LEVEL_INFO );

            return [
                'action' => $action,
                'post_id' => $post_id,
                'bil24_id' => $bil24_id,
                'data' => $bil24_event
            ];
        } finally {
            if ( $existing_post_id ) {
                delete_transient( "bil24_syncing_event_{$existing_post_id}" );
            }
        }
    }

    /**
     * Prepare WordPress event data for Bil24 API
     */
    private function prepare_event_data_for_bil24( Event $event ): array {
        return [
            'title' => $event->get_title(),
            'description' => $event->get_description(),
            'start_date' => $event->get_start_date()?->format( 'Y-m-d H:i:s' ),
            'end_date' => $event->get_end_date()?->format( 'Y-m-d H:i:s' ),
            'status' => $this->map_wp_status_to_bil24( $event->get_status() )
        ];
    }

    /**
     * Prepare WordPress post data from Bil24 event
     */
    private function prepare_post_data_from_bil24( array $bil24_event ): array {
        return [
            'post_type' => Constants::CPT_EVENT,
            'post_title' => sanitize_text_field( $bil24_event['title'] ?? '' ),
            'post_content' => wp_kses_post( $bil24_event['description'] ?? '' ),
            'post_status' => $this->map_bil24_status_to_wp( $bil24_event['status'] ?? 'draft' ),
            'meta_input' => [
                'event_start_date' => sanitize_text_field( $bil24_event['start_date'] ?? '' ),
                'event_end_date' => sanitize_text_field( $bil24_event['end_date'] ?? '' )
            ]
        ];
    }

    /**
     * Map WordPress status to Bil24 status
     */
    private function map_wp_status_to_bil24( string $wp_status ): string {
        $mapping = [
            'publish' => 'published',
            'draft' => 'draft',
            'private' => 'draft',
            'trash' => 'cancelled'
        ];

        return $mapping[ $wp_status ] ?? 'draft';
    }

    /**
     * Map Bil24 status to WordPress status
     */
    private function map_bil24_status_to_wp( string $bil24_status ): string {
        $mapping = [
            'published' => 'publish',
            'draft' => 'draft',
            'cancelled' => 'trash',
            'sold_out' => 'publish'
        ];

        return $mapping[ $bil24_status ] ?? 'draft';
    }

    /**
     * Show admin notices for sync status
     */
    public function show_sync_notices(): void {
        $sync_status = get_option( Constants::OPTION_SYNC_STATUS );
        
        if ( ! $sync_status || ! isset( $sync_status['status'] ) ) {
            return;
        }

        if ( $sync_status['status'] === 'error' ) {
            echo '<div class="notice notice-error"><p>';
            echo '<strong>Bil24 Sync Error:</strong> ' . esc_html( $sync_status['error'] ?? 'Unknown error' );
            echo '</p></div>';
        }
    }
} 