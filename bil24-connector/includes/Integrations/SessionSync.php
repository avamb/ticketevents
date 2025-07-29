<?php
namespace Bil24\Integrations;

use Bil24\Api\Endpoints;
use Bil24\Constants;
use Bil24\Utils;

defined( 'ABSPATH' ) || exit;

/**
 * Session Synchronization between WordPress and Bil24
 * 
 * Handles session data sync, capacity management, pricing sync, and availability updates
 * 
 * @package Bil24Connector
 * @since 0.1.0
 */
class SessionSync {

    /**
     * API Endpoints instance
     */
    private Endpoints $api;

    /**
     * Constructor
     */
    public function __construct( ?Endpoints $api = null ) {
        $this->api = $api ?? new Endpoints();
        $this->register_hooks();
    }

    /**
     * Register WordPress hooks
     */
    private function register_hooks(): void {
        // Auto sync on session save
        add_action( 'save_post_' . Constants::CPT_SESSION, [ $this, 'on_session_save' ], 10, 3 );
        
        // Auto sync on session deletion
        add_action( 'before_delete_post', [ $this, 'on_session_delete' ], 10, 2 );
        
        // Scheduled sync hooks
        add_action( Constants::HOOK_SYNC_SESSIONS, [ $this, 'scheduled_sync' ] );
        
        // Admin actions
        add_action( 'wp_ajax_bil24_sync_sessions', [ $this, 'ajax_manual_sync' ] );
        add_action( 'wp_ajax_bil24_sync_single_session', [ $this, 'ajax_sync_single_session' ] );
        add_action( 'wp_ajax_bil24_check_session_availability', [ $this, 'ajax_check_availability' ] );
        
        // Meta box for session details
        add_action( 'add_meta_boxes', [ $this, 'add_session_meta_boxes' ] );
        add_action( 'save_post', [ $this, 'save_session_meta' ] );
    }

    /**
     * Add meta boxes for session management
     */
    public function add_session_meta_boxes(): void {
        add_meta_box(
            'bil24_session_details',
            'Детали сессии Bil24',
            [ $this, 'render_session_meta_box' ],
            Constants::CPT_SESSION,
            'normal',
            'high'
        );
    }

    /**
     * Render session meta box
     */
    public function render_session_meta_box( \WP_Post $post ): void {
        wp_nonce_field( 'bil24_session_meta', 'bil24_session_meta_nonce' );
        
        $session_data = get_post_meta( $post->ID, Constants::META_BIL24_DATA, true ) ?: [];
        $event_id = get_post_meta( $post->ID, '_bil24_event_id', true );
        $start_time = get_post_meta( $post->ID, '_session_start_time', true );
        $end_time = get_post_meta( $post->ID, '_session_end_time', true );
        $capacity = get_post_meta( $post->ID, '_session_capacity', true );
        $available = get_post_meta( $post->ID, '_session_available', true );
        $price = get_post_meta( $post->ID, '_session_price', true );
        
        ?>
        <table class="form-table">
            <tr>
                <th scope="row"><label for="bil24_event_id">Event ID (Bil24)</label></th>
                <td><input type="number" id="bil24_event_id" name="bil24_event_id" value="<?php echo esc_attr( $event_id ); ?>" class="regular-text" /></td>
            </tr>
            <tr>
                <th scope="row"><label for="session_start_time">Время начала</label></th>
                <td><input type="datetime-local" id="session_start_time" name="session_start_time" value="<?php echo esc_attr( $start_time ); ?>" class="regular-text" /></td>
            </tr>
            <tr>
                <th scope="row"><label for="session_end_time">Время окончания</label></th>
                <td><input type="datetime-local" id="session_end_time" name="session_end_time" value="<?php echo esc_attr( $end_time ); ?>" class="regular-text" /></td>
            </tr>
            <tr>
                <th scope="row"><label for="session_capacity">Общая вместимость</label></th>
                <td><input type="number" id="session_capacity" name="session_capacity" value="<?php echo esc_attr( $capacity ); ?>" min="0" /></td>
            </tr>
            <tr>
                <th scope="row"><label for="session_available">Доступно мест</label></th>
                <td><input type="number" id="session_available" name="session_available" value="<?php echo esc_attr( $available ); ?>" min="0" /></td>
            </tr>
            <tr>
                <th scope="row"><label for="session_price">Цена</label></th>
                <td><input type="number" id="session_price" name="session_price" value="<?php echo esc_attr( $price ); ?>" step="0.01" min="0" /></td>
            </tr>
        </table>

        <div style="margin-top: 20px;">
            <h4>Действия синхронизации</h4>
            <button type="button" class="button" onclick="syncSessionToBil24(<?php echo $post->ID; ?>)">Синхронизировать в Bil24</button>
            <button type="button" class="button" onclick="syncSessionFromBil24(<?php echo $post->ID; ?>)">Загрузить из Bil24</button>
            <button type="button" class="button" onclick="checkSessionAvailability(<?php echo $post->ID; ?>)">Проверить доступность</button>
            <div id="session-sync-result-<?php echo $post->ID; ?>"></div>
        </div>

        <script>
        function syncSessionToBil24(postId) {
            performSessionAction(postId, 'bil24_sync_single_session', {direction: 'to_bil24'});
        }

        function syncSessionFromBil24(postId) {
            performSessionAction(postId, 'bil24_sync_single_session', {direction: 'from_bil24'});
        }

        function checkSessionAvailability(postId) {
            performSessionAction(postId, 'bil24_check_session_availability', {});
        }

        function performSessionAction(postId, action, extraData) {
            const resultDiv = document.getElementById('session-sync-result-' + postId);
            resultDiv.innerHTML = 'Выполняется...';
            
            const data = new FormData();
            data.append('action', action);
            data.append('post_id', postId);
            data.append('_ajax_nonce', '<?php echo wp_create_nonce( 'bil24_session_action' ); ?>');
            
            for (const [key, value] of Object.entries(extraData)) {
                data.append(key, value);
            }
            
            fetch(ajaxurl, {
                method: 'POST',
                body: data
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    resultDiv.innerHTML = '<div style="color: green;">✓ ' + data.data.message + '</div>';
                } else {
                    resultDiv.innerHTML = '<div style="color: red;">✗ ' + data.data.message + '</div>';
                }
            })
            .catch(error => {
                resultDiv.innerHTML = '<div style="color: red;">✗ Ошибка: ' + error.message + '</div>';
            });
        }
        </script>
        <?php
    }

    /**
     * Save session meta data
     */
    public function save_session_meta( int $post_id ): void {
        if ( ! isset( $_POST['bil24_session_meta_nonce'] ) || ! wp_verify_nonce( $_POST['bil24_session_meta_nonce'], 'bil24_session_meta' ) ) {
            return;
        }

        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }

        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            return;
        }

        $fields = [
            'bil24_event_id' => '_bil24_event_id',
            'session_start_time' => '_session_start_time',
            'session_end_time' => '_session_end_time',
            'session_capacity' => '_session_capacity',
            'session_available' => '_session_available',
            'session_price' => '_session_price'
        ];

        foreach ( $fields as $input_name => $meta_key ) {
            if ( isset( $_POST[ $input_name ] ) ) {
                $value = sanitize_text_field( $_POST[ $input_name ] );
                update_post_meta( $post_id, $meta_key, $value );
            }
        }
    }

    /**
     * Manual sync trigger (AJAX)
     */
    public function ajax_manual_sync(): void {
        check_ajax_referer( 'bil24_sync_sessions' );
        
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( 'Insufficient permissions' );
        }

        $direction = sanitize_text_field( $_POST['direction'] ?? 'bidirectional' );
        
        try {
            $result = $this->sync_sessions( $direction );
            
            wp_send_json_success( [
                'message' => 'Синхронизация сессий завершена успешно',
                'stats' => $result
            ] );
        } catch ( \Exception $e ) {
            Utils::log( 'Manual session sync failed: ' . $e->getMessage(), Constants::LOG_LEVEL_ERROR );
            wp_send_json_error( [
                'message' => 'Ошибка синхронизации: ' . $e->getMessage()
            ] );
        }
    }

    /**
     * Manual sync for single session (AJAX)
     */
    public function ajax_sync_single_session(): void {
        check_ajax_referer( 'bil24_session_action' );
        
        if ( ! current_user_can( 'edit_posts' ) ) {
            wp_die( 'Insufficient permissions' );
        }

        $post_id = intval( $_POST['post_id'] ?? 0 );
        $direction = sanitize_text_field( $_POST['direction'] ?? 'to_bil24' );
        
        if ( ! $post_id ) {
            wp_send_json_error( [ 'message' => 'Invalid post ID' ] );
        }

        try {
            if ( $direction === 'to_bil24' ) {
                $result = $this->sync_session_to_bil24( $post_id );
            } else {
                $result = $this->sync_session_from_bil24( $post_id );
            }
            
            wp_send_json_success( [
                'message' => 'Сессия синхронизирована успешно',
                'result' => $result
            ] );
        } catch ( \Exception $e ) {
            Utils::log( "Single session sync failed for post {$post_id}: " . $e->getMessage(), Constants::LOG_LEVEL_ERROR );
            wp_send_json_error( [
                'message' => 'Ошибка синхронизации: ' . $e->getMessage()
            ] );
        }
    }

    /**
     * Check session availability (AJAX)
     */
    public function ajax_check_availability(): void {
        check_ajax_referer( 'bil24_session_action' );
        
        if ( ! current_user_can( 'edit_posts' ) ) {
            wp_die( 'Insufficient permissions' );
        }

        $post_id = intval( $_POST['post_id'] ?? 0 );
        
        if ( ! $post_id ) {
            wp_send_json_error( [ 'message' => 'Invalid post ID' ] );
        }

        try {
            $availability = $this->check_session_availability( $post_id );
            
            wp_send_json_success( [
                'message' => "Доступность проверена. Доступно: {$availability['available']} из {$availability['capacity']}",
                'availability' => $availability
            ] );
        } catch ( \Exception $e ) {
            Utils::log( "Availability check failed for session {$post_id}: " . $e->getMessage(), Constants::LOG_LEVEL_ERROR );
            wp_send_json_error( [
                'message' => 'Ошибка проверки доступности: ' . $e->getMessage()
            ] );
        }
    }

    /**
     * Auto sync on session save
     */
    public function on_session_save( int $post_id, \WP_Post $post, bool $update ): void {
        // Skip auto-drafts and revisions
        if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
            return;
        }

        // Skip if we're currently syncing to avoid loops
        if ( get_transient( "bil24_syncing_session_{$post_id}" ) ) {
            return;
        }

        // Only sync published sessions
        if ( $post->post_status !== 'publish' ) {
            return;
        }

        try {
            $this->sync_session_to_bil24( $post_id );
        } catch ( \Exception $e ) {
            Utils::log( "Auto sync failed for session {$post_id}: " . $e->getMessage(), Constants::LOG_LEVEL_ERROR );
        }
    }

    /**
     * Auto sync on session deletion
     */
    public function on_session_delete( int $post_id, \WP_Post $post ): void {
        if ( $post->post_type !== Constants::CPT_SESSION ) {
            return;
        }

        $event_id = get_post_meta( $post_id, '_bil24_event_id', true );
        $session_id = get_post_meta( $post_id, Constants::META_BIL24_ID, true );
        
        if ( $event_id && $session_id ) {
            try {
                $this->api->delete_session( intval( $event_id ), intval( $session_id ) );
                Utils::log( "Session {$session_id} deleted from Bil24", Constants::LOG_LEVEL_INFO );
            } catch ( \Exception $e ) {
                Utils::log( "Failed to delete session {$session_id} from Bil24: " . $e->getMessage(), Constants::LOG_LEVEL_ERROR );
            }
        }
    }

    /**
     * Scheduled sync
     */
    public function scheduled_sync(): void {
        try {
            $result = $this->sync_sessions( 'bidirectional' );
            Utils::log( 'Scheduled session sync completed: ' . wp_json_encode( $result ), Constants::LOG_LEVEL_INFO );
        } catch ( \Exception $e ) {
            Utils::log( 'Scheduled session sync failed: ' . $e->getMessage(), Constants::LOG_LEVEL_ERROR );
        }
    }

    /**
     * Main sync sessions method
     */
    public function sync_sessions( string $direction = 'bidirectional' ): array {
        $stats = [
            'to_bil24' => [ 'created' => 0, 'updated' => 0, 'errors' => 0 ],
            'from_bil24' => [ 'created' => 0, 'updated' => 0, 'errors' => 0 ]
        ];

        if ( $direction === 'to_bil24' || $direction === 'bidirectional' ) {
            $stats['to_bil24'] = $this->sync_all_sessions_to_bil24();
        }

        if ( $direction === 'from_bil24' || $direction === 'bidirectional' ) {
            $stats['from_bil24'] = $this->sync_all_sessions_from_bil24();
        }

        return $stats;
    }

    /**
     * Sync all WordPress sessions to Bil24
     */
    private function sync_all_sessions_to_bil24(): array {
        $stats = [ 'created' => 0, 'updated' => 0, 'errors' => 0 ];

        $sessions = get_posts( [
            'post_type' => Constants::CPT_SESSION,
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

        foreach ( $sessions as $session_post ) {
            try {
                $result = $this->sync_session_to_bil24( $session_post->ID );
                
                if ( $result['action'] === 'created' ) {
                    $stats['created']++;
                } else {
                    $stats['updated']++;
                }
            } catch ( \Exception $e ) {
                $stats['errors']++;
                Utils::log( "Failed to sync session {$session_post->ID} to Bil24: " . $e->getMessage(), Constants::LOG_LEVEL_ERROR );
            }
        }

        return $stats;
    }

    /**
     * Sync all Bil24 sessions to WordPress
     */
    private function sync_all_sessions_from_bil24(): array {
        $stats = [ 'created' => 0, 'updated' => 0, 'errors' => 0 ];

        try {
            // Get all events that have sessions
            $events_with_sessions = get_posts( [
                'post_type' => Constants::CPT_EVENT,
                'posts_per_page' => -1,
                'meta_query' => [
                    [
                        'key' => Constants::META_BIL24_ID,
                        'compare' => 'EXISTS'
                    ]
                ]
            ] );

            foreach ( $events_with_sessions as $event_post ) {
                $event_bil24_id = get_post_meta( $event_post->ID, Constants::META_BIL24_ID, true );
                
                if ( ! $event_bil24_id ) {
                    continue;
                }

                try {
                    $bil24_sessions = $this->api->get_sessions( intval( $event_bil24_id ) );
                    
                    foreach ( $bil24_sessions as $bil24_session ) {
                        try {
                            $result = $this->sync_session_from_bil24_data( $bil24_session, intval( $event_bil24_id ) );
                            
                            if ( $result['action'] === 'created' ) {
                                $stats['created']++;
                            } else {
                                $stats['updated']++;
                            }
                        } catch ( \Exception $e ) {
                            $stats['errors']++;
                            Utils::log( "Failed to sync session {$bil24_session['id']} from Bil24: " . $e->getMessage(), Constants::LOG_LEVEL_ERROR );
                        }
                    }
                } catch ( \Exception $e ) {
                    Utils::log( "Failed to fetch sessions for event {$event_bil24_id}: " . $e->getMessage(), Constants::LOG_LEVEL_ERROR );
                }
            }
        } catch ( \Exception $e ) {
            Utils::log( 'Failed to sync sessions from Bil24: ' . $e->getMessage(), Constants::LOG_LEVEL_ERROR );
            throw $e;
        }

        return $stats;
    }

    /**
     * Sync single WordPress session to Bil24
     */
    public function sync_session_to_bil24( int $post_id ): array {
        // Prevent sync loops
        set_transient( "bil24_syncing_session_{$post_id}", true, 300 );

        try {
            $post = get_post( $post_id );
            if ( ! $post || $post->post_type !== Constants::CPT_SESSION ) {
                throw new \Exception( 'Invalid session post' );
            }

            $event_id = get_post_meta( $post_id, '_bil24_event_id', true );
            if ( ! $event_id ) {
                throw new \Exception( 'Session must be associated with an event' );
            }

            $session_data = $this->prepare_session_data_for_bil24( $post_id );
            $bil24_session_id = get_post_meta( $post_id, Constants::META_BIL24_ID, true );

            if ( $bil24_session_id ) {
                // Update existing session
                $response = $this->api->update_session( intval( $event_id ), intval( $bil24_session_id ), $session_data );
                $action = 'updated';
            } else {
                // Create new session
                $response = $this->api->create_session( intval( $event_id ), $session_data );
                $bil24_session_id = $response['id'] ?? null;
                
                if ( $bil24_session_id ) {
                    update_post_meta( $post_id, Constants::META_BIL24_ID, $bil24_session_id );
                }
                $action = 'created';
            }

            // Update sync status
            update_post_meta( $post_id, Constants::META_SYNC_STATUS, 'synced' );
            update_post_meta( $post_id, Constants::META_LAST_SYNC, time() );

            Utils::log( "Session {$post_id} {$action} in Bil24 (ID: {$bil24_session_id})", Constants::LOG_LEVEL_INFO );

            return [
                'action' => $action,
                'post_id' => $post_id,
                'bil24_session_id' => $bil24_session_id,
                'response' => $response
            ];
        } finally {
            delete_transient( "bil24_syncing_session_{$post_id}" );
        }
    }

    /**
     * Sync single session from Bil24 to WordPress
     */
    public function sync_session_from_bil24( int $post_id ): array {
        $event_id = get_post_meta( $post_id, '_bil24_event_id', true );
        $session_id = get_post_meta( $post_id, Constants::META_BIL24_ID, true );
        
        if ( ! $event_id || ! $session_id ) {
            throw new \Exception( 'Session does not have valid Bil24 IDs' );
        }

        $bil24_session = $this->api->get_session( intval( $event_id ), intval( $session_id ) );
        return $this->sync_session_from_bil24_data( $bil24_session, intval( $event_id ), $post_id );
    }

    /**
     * Sync session from Bil24 data
     */
    private function sync_session_from_bil24_data( array $bil24_session, int $event_bil24_id, ?int $existing_post_id = null ): array {
        $bil24_session_id = $bil24_session['id'] ?? null;
        
        if ( ! $bil24_session_id ) {
            throw new \Exception( 'Invalid Bil24 session data' );
        }

        // Find existing WordPress post
        if ( ! $existing_post_id ) {
            $existing_posts = get_posts( [
                'post_type' => Constants::CPT_SESSION,
                'meta_query' => [
                    'relation' => 'AND',
                    [
                        'key' => Constants::META_BIL24_ID,
                        'value' => $bil24_session_id,
                        'compare' => '='
                    ],
                    [
                        'key' => '_bil24_event_id',
                        'value' => $event_bil24_id,
                        'compare' => '='
                    ]
                ],
                'posts_per_page' => 1
            ] );
            
            $existing_post_id = $existing_posts ? $existing_posts[0]->ID : null;
        }

        // Prevent sync loops
        if ( $existing_post_id ) {
            set_transient( "bil24_syncing_session_{$existing_post_id}", true, 300 );
        }

        try {
            $post_data = $this->prepare_post_data_from_bil24_session( $bil24_session );

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
            update_post_meta( $post_id, Constants::META_BIL24_ID, $bil24_session_id );
            update_post_meta( $post_id, '_bil24_event_id', $event_bil24_id );
            update_post_meta( $post_id, Constants::META_BIL24_DATA, $bil24_session );
            update_post_meta( $post_id, Constants::META_SYNC_STATUS, 'synced' );
            update_post_meta( $post_id, Constants::META_LAST_SYNC, time() );
            
            // Update session-specific meta
            $this->update_session_meta_from_bil24( $post_id, $bil24_session );

            Utils::log( "Session {$bil24_session_id} {$action} in WordPress (Post ID: {$post_id})", Constants::LOG_LEVEL_INFO );

            return [
                'action' => $action,
                'post_id' => $post_id,
                'bil24_session_id' => $bil24_session_id,
                'data' => $bil24_session
            ];
        } finally {
            if ( $existing_post_id ) {
                delete_transient( "bil24_syncing_session_{$existing_post_id}" );
            }
        }
    }

    /**
     * Check session availability
     */
    public function check_session_availability( int $post_id ): array {
        $event_id = get_post_meta( $post_id, '_bil24_event_id', true );
        $session_id = get_post_meta( $post_id, Constants::META_BIL24_ID, true );
        
        if ( ! $event_id || ! $session_id ) {
            throw new \Exception( 'Session does not have valid Bil24 IDs' );
        }

        $availability = $this->api->get_session_availability( intval( $event_id ), intval( $session_id ) );
        
        // Update local availability data
        update_post_meta( $post_id, '_session_available', $availability['available'] ?? 0 );
        update_post_meta( $post_id, '_session_capacity', $availability['capacity'] ?? 0 );
        update_post_meta( $post_id, '_availability_last_check', time() );

        return $availability;
    }

    /**
     * Prepare WordPress session data for Bil24 API
     */
    private function prepare_session_data_for_bil24( int $post_id ): array {
        $post = get_post( $post_id );
        
        return [
            'title' => $post->post_title,
            'description' => $post->post_content,
            'start_time' => get_post_meta( $post_id, '_session_start_time', true ),
            'end_time' => get_post_meta( $post_id, '_session_end_time', true ),
            'capacity' => intval( get_post_meta( $post_id, '_session_capacity', true ) ),
            'price' => floatval( get_post_meta( $post_id, '_session_price', true ) )
        ];
    }

    /**
     * Prepare WordPress post data from Bil24 session
     */
    private function prepare_post_data_from_bil24_session( array $bil24_session ): array {
        return [
            'post_type' => Constants::CPT_SESSION,
            'post_title' => sanitize_text_field( $bil24_session['title'] ?? 'Session' ),
            'post_content' => wp_kses_post( $bil24_session['description'] ?? '' ),
            'post_status' => 'publish'
        ];
    }

    /**
     * Update session meta from Bil24 data
     */
    private function update_session_meta_from_bil24( int $post_id, array $bil24_session ): void {
        $meta_mappings = [
            'start_time' => '_session_start_time',
            'end_time' => '_session_end_time',
            'capacity' => '_session_capacity',
            'available' => '_session_available',
            'price' => '_session_price'
        ];

        foreach ( $meta_mappings as $bil24_key => $meta_key ) {
            if ( isset( $bil24_session[ $bil24_key ] ) ) {
                update_post_meta( $post_id, $meta_key, sanitize_text_field( $bil24_session[ $bil24_key ] ) );
            }
        }
    }
} 