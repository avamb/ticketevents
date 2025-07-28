<?php
namespace Bil24\Integrations\WooCommerce;

use Bil24\Api\Endpoints;
use Bil24\Constants;
use Bil24\Models\Event;
use Bil24\Models\Session;
use Bil24\Models\PriceCategory;
use Bil24\Utils;

defined( 'ABSPATH' ) || exit;

/**
 * Product Synchronization between WooCommerce and Bil24
 * 
 * Converts Bil24 events/sessions into WooCommerce products with proper field mapping
 * Handles ACF custom fields for extended product data
 * 
 * @package Bil24Connector
 * @since 0.1.0
 */
class ProductSync {

    /**
     * API Endpoints instance
     */
    private Endpoints $api;

    /**
     * ACF field mappings for extended product data
     */
    private const ACF_FIELD_MAPPINGS = [
        'bil24_event_id' => 'bil24_event_id',
        'bil24_session_id' => 'bil24_session_id',
        'bil24_venue_id' => 'bil24_venue_id',
        'bil24_price_category' => 'bil24_price_category',
        'event_start_date' => 'event_start_date',
        'event_end_date' => 'event_end_date',
        'venue_name' => 'venue_name',
        'venue_address' => 'venue_address',
        'seat_number' => 'seat_number',
        'seat_section' => 'seat_section',
        'age_restrictions' => 'age_restrictions',
        'is_reserved_seating' => 'is_reserved_seating',
        'max_tickets_per_customer' => 'max_tickets_per_customer',
        'early_bird_discount' => 'early_bird_discount',
        'bil24_last_sync' => 'bil24_last_sync',
        'bil24_sync_status' => 'bil24_sync_status'
    ];

    /**
     * Product category mappings
     */
    private const CATEGORY_MAPPINGS = [
        'concert' => 'concerts',
        'theater' => 'theater',
        'sport' => 'sports',
        'exhibition' => 'exhibitions',
        'conference' => 'conferences',
        'workshop' => 'workshops',
        'festival' => 'festivals',
        'cinema' => 'cinema'
    ];

    /**
     * Constructor
     */
    public function __construct( ?Endpoints $api = null ) {
        $this->api = $api ?? new Endpoints();
        $this->register_hooks();
    }

    /**
     * Register WordPress/WooCommerce hooks
     */
    private function register_hooks(): void {
        // Product creation/update hooks
        add_action( 'woocommerce_new_product', [ $this, 'on_product_created' ], 10, 1 );
        add_action( 'woocommerce_update_product', [ $this, 'on_product_updated' ], 10, 1 );
        add_action( 'before_delete_post', [ $this, 'on_product_deleted' ], 10, 2 );
        
        // Scheduled sync hooks
        add_action( Constants::HOOK_SYNC_PRODUCTS, [ $this, 'scheduled_sync' ] );
        
        // Admin actions
        add_action( 'wp_ajax_bil24_sync_products', [ $this, 'ajax_manual_sync' ] );
        add_action( 'wp_ajax_bil24_import_events', [ $this, 'ajax_import_events' ] );
        add_action( 'wp_ajax_bil24_sync_single_product', [ $this, 'ajax_sync_single_product' ] );
        
        // Product meta boxes
        add_action( 'add_meta_boxes', [ $this, 'add_product_meta_boxes' ] );
        add_action( 'save_post', [ $this, 'save_product_meta' ] );
        
        // Product data tabs
        add_filter( 'woocommerce_product_data_tabs', [ $this, 'add_product_data_tab' ] );
        add_action( 'woocommerce_product_data_panels', [ $this, 'add_product_data_panel' ] );
        add_action( 'woocommerce_process_product_meta', [ $this, 'save_product_data_fields' ] );
        
        // Frontend product display
        add_action( 'woocommerce_single_product_summary', [ $this, 'display_event_info' ], 25 );
        add_filter( 'woocommerce_product_tabs', [ $this, 'add_event_details_tab' ] );
        
        // Stock management
        add_filter( 'woocommerce_product_get_stock_quantity', [ $this, 'get_stock_from_bil24' ], 10, 2 );
        add_filter( 'woocommerce_product_variation_get_stock_quantity', [ $this, 'get_stock_from_bil24' ], 10, 2 );
    }

    /**
     * Import events from Bil24 as WooCommerce products
     */
    public function import_events_as_products( array $filters = [] ): array {
        $results = [
            'imported' => 0,
            'updated' => 0,
            'errors' => 0,
            'messages' => []
        ];

        try {
            // Get events from Bil24 API
            $events_data = $this->api->get_events( $filters );
            
            if ( empty( $events_data['events'] ) ) {
                $results['messages'][] = 'Нет событий для импорта';
                return $results;
            }

            foreach ( $events_data['events'] as $event_data ) {
                try {
                    $event = new Event( $event_data );
                    $product_id = $this->create_or_update_product_from_event( $event );
                    
                    if ( $product_id ) {
                        $existing_product = get_post_meta( $product_id, Constants::META_BIL24_ID, true );
                        if ( $existing_product ) {
                            $results['updated']++;
                            $results['messages'][] = "Обновлен продукт ID {$product_id} для события {$event->getBil24Id()}";
                        } else {
                            $results['imported']++;
                            $results['messages'][] = "Создан продукт ID {$product_id} для события {$event->getBil24Id()}";
                        }
                    }
                    
                } catch ( \Exception $e ) {
                    $results['errors']++;
                    $results['messages'][] = "Ошибка при импорте события {$event_data['id']}: " . $e->getMessage();
                    Utils::log( "Ошибка импорта события: " . $e->getMessage(), Constants::LOG_LEVEL_ERROR );
                }
            }

        } catch ( \Exception $e ) {
            $results['errors']++;
            $results['messages'][] = "Ошибка API: " . $e->getMessage();
            Utils::log( "Ошибка API при импорте событий: " . $e->getMessage(), Constants::LOG_LEVEL_ERROR );
        }

        return $results;
    }

    /**
     * Create or update WooCommerce product from Bil24 event
     */
    public function create_or_update_product_from_event( Event $event ): ?int {
        // Check if product already exists
        $existing_product_id = $this->find_product_by_bil24_id( $event->getBil24Id() );
        
        if ( $existing_product_id ) {
            return $this->update_product_from_event( $existing_product_id, $event );
        } else {
            return $this->create_product_from_event( $event );
        }
    }

    /**
     * Create new WooCommerce product from Bil24 event
     */
    private function create_product_from_event( Event $event ): ?int {
        // Create new product
        $product = new \WC_Product_Simple();
        
        // Basic product data
        $product->set_name( $event->getTitle() );
        $product->set_description( $event->getDescription() );
        $product->set_short_description( $this->generate_short_description( $event ) );
        $product->set_status( 'publish' );
        $product->set_catalog_visibility( 'visible' );
        
        // Price data
        $price = $event->getPrice();
        if ( $price > 0 ) {
            $product->set_regular_price( $price );
            $product->set_price( $price );
        }
        
        // Stock management
        $product->set_manage_stock( true );
        $product->set_stock_quantity( $event->getAvailableTickets() );
        $product->set_stock_status( $event->getAvailableTickets() > 0 ? 'instock' : 'outofstock' );
        
        // Virtual product (tickets are digital)
        $product->set_virtual( true );
        $product->set_downloadable( false );
        
        // Save product
        $product_id = $product->save();
        
        if ( $product_id ) {
            // Save Bil24 metadata
            $this->save_bil24_metadata( $product_id, $event );
            
            // Set product categories
            $this->set_product_categories( $product_id, $event );
            
            // Save ACF fields if available
            if ( function_exists( 'update_field' ) ) {
                $this->save_acf_fields( $product_id, $event );
            }
            
            // Add product tags
            $this->set_product_tags( $product_id, $event );
            
            Utils::log( "Создан продукт ID {$product_id} для события {$event->getBil24Id()}", Constants::LOG_LEVEL_INFO );
        }
        
        return $product_id;
    }

    /**
     * Update existing WooCommerce product from Bil24 event
     */
    private function update_product_from_event( int $product_id, Event $event ): int {
        $product = wc_get_product( $product_id );
        
        if ( ! $product ) {
            throw new \Exception( "Продукт с ID {$product_id} не найден" );
        }
        
        // Update basic data
        $product->set_name( $event->getTitle() );
        $product->set_description( $event->getDescription() );
        $product->set_short_description( $this->generate_short_description( $event ) );
        
        // Update price
        $price = $event->getPrice();
        if ( $price > 0 ) {
            $product->set_regular_price( $price );
            $product->set_price( $price );
        }
        
        // Update stock
        $product->set_stock_quantity( $event->getAvailableTickets() );
        $product->set_stock_status( $event->getAvailableTickets() > 0 ? 'instock' : 'outofstock' );
        
        // Save product
        $product->save();
        
        // Update metadata
        $this->save_bil24_metadata( $product_id, $event );
        
        // Update categories
        $this->set_product_categories( $product_id, $event );
        
        // Update ACF fields
        if ( function_exists( 'update_field' ) ) {
            $this->save_acf_fields( $product_id, $event );
        }
        
        // Update tags
        $this->set_product_tags( $product_id, $event );
        
        Utils::log( "Обновлен продукт ID {$product_id} для события {$event->getBil24Id()}", Constants::LOG_LEVEL_INFO );
        
        return $product_id;
    }

    /**
     * Save Bil24 metadata to product
     */
    private function save_bil24_metadata( int $product_id, Event $event ): void {
        update_post_meta( $product_id, Constants::META_BIL24_ID, $event->getBil24Id() );
        update_post_meta( $product_id, Constants::META_SYNC_STATUS, 'synced' );
        update_post_meta( $product_id, Constants::META_LAST_SYNC, time() );
        update_post_meta( $product_id, Constants::META_BIL24_DATA, $event->toArray() );
        
        // Additional event-specific metadata
        update_post_meta( $product_id, '_bil24_event_type', $event->getEventType() );
        update_post_meta( $product_id, '_bil24_venue_id', $event->getVenueId() );
        update_post_meta( $product_id, '_bil24_start_date', $event->getStartDate() ? $event->getStartDate()->format( 'Y-m-d H:i:s' ) : '' );
        update_post_meta( $product_id, '_bil24_end_date', $event->getEndDate() ? $event->getEndDate()->format( 'Y-m-d H:i:s' ) : '' );
    }

    /**
     * Save ACF custom fields for extended data
     */
    private function save_acf_fields( int $product_id, Event $event ): void {
        $fields_data = [
            'bil24_event_id' => $event->getBil24Id(),
            'venue_name' => $event->getVenueName(),
            'venue_address' => $event->getVenueAddress(),
            'event_start_date' => $event->getStartDate() ? $event->getStartDate()->format( 'Y-m-d H:i:s' ) : '',
            'event_end_date' => $event->getEndDate() ? $event->getEndDate()->format( 'Y-m-d H:i:s' ) : '',
            'age_restrictions' => $event->getAgeRestrictions(),
            'max_tickets_per_customer' => $event->getMaxTicketsPerCustomer(),
            'bil24_last_sync' => date( 'Y-m-d H:i:s' ),
            'bil24_sync_status' => 'synced'
        ];

        foreach ( $fields_data as $field_key => $value ) {
            if ( isset( self::ACF_FIELD_MAPPINGS[ $field_key ] ) && ! empty( $value ) ) {
                update_field( self::ACF_FIELD_MAPPINGS[ $field_key ], $value, $product_id );
            }
        }
    }

    /**
     * Set product categories based on event type
     */
    private function set_product_categories( int $product_id, Event $event ): void {
        $event_type = $event->getEventType();
        $category_slug = self::CATEGORY_MAPPINGS[ $event_type ] ?? 'events';
        
        // Get or create category
        $category = get_term_by( 'slug', $category_slug, 'product_cat' );
        
        if ( ! $category ) {
            $category_name = ucfirst( str_replace( '-', ' ', $category_slug ) );
            $category_result = wp_insert_term( $category_name, 'product_cat', [
                'slug' => $category_slug,
                'description' => "События типа {$category_name}"
            ] );
            
            if ( ! is_wp_error( $category_result ) ) {
                $category_id = $category_result['term_id'];
            }
        } else {
            $category_id = $category->term_id;
        }
        
        if ( isset( $category_id ) ) {
            wp_set_object_terms( $product_id, [ $category_id ], 'product_cat' );
        }
    }

    /**
     * Set product tags
     */
    private function set_product_tags( int $product_id, Event $event ): void {
        $tags = [];
        
        // Add venue as tag
        if ( $event->getVenueName() ) {
            $tags[] = $event->getVenueName();
        }
        
        // Add event type
        if ( $event->getEventType() ) {
            $tags[] = $event->getEventType();
        }
        
        // Add city if available
        if ( $event->getCity() ) {
            $tags[] = $event->getCity();
        }
        
        if ( ! empty( $tags ) ) {
            wp_set_object_terms( $product_id, $tags, 'product_tag' );
        }
    }

    /**
     * Generate short description for product
     */
    private function generate_short_description( Event $event ): string {
        $parts = [];
        
        if ( $event->getStartDate() ) {
            $parts[] = 'Дата: ' . $event->getStartDate()->format( 'd.m.Y H:i' );
        }
        
        if ( $event->getVenueName() ) {
            $parts[] = 'Место: ' . $event->getVenueName();
        }
        
        if ( $event->getCity() ) {
            $parts[] = 'Город: ' . $event->getCity();
        }
        
        return implode( ' | ', $parts );
    }

    /**
     * Find product by Bil24 ID
     */
    private function find_product_by_bil24_id( string $bil24_id ): ?int {
        $products = get_posts( [
            'post_type' => 'product',
            'meta_query' => [
                [
                    'key' => Constants::META_BIL24_ID,
                    'value' => $bil24_id,
                    'compare' => '='
                ]
            ],
            'posts_per_page' => 1,
            'fields' => 'ids'
        ] );
        
        return ! empty( $products ) ? $products[0] : null;
    }

    /**
     * Add product data tab for Bil24 settings
     */
    public function add_product_data_tab( array $tabs ): array {
        $tabs['bil24'] = [
            'label' => 'Bil24',
            'target' => 'bil24_product_data',
            'class' => [ 'show_if_simple', 'show_if_variable' ]
        ];
        
        return $tabs;
    }

    /**
     * Add product data panel content
     */
    public function add_product_data_panel(): void {
        global $woocommerce, $post;
        
        $bil24_id = get_post_meta( $post->ID, Constants::META_BIL24_ID, true );
        $sync_status = get_post_meta( $post->ID, Constants::META_SYNC_STATUS, true );
        
        ?>
        <div id="bil24_product_data" class="panel woocommerce_options_panel">
            <div class="options_group">
                <?php
                woocommerce_wp_text_input( [
                    'id' => '_bil24_event_id',
                    'label' => 'Bil24 Event ID',
                    'description' => 'ID события в системе Bil24',
                    'type' => 'text',
                    'value' => $bil24_id
                ] );
                
                woocommerce_wp_select( [
                    'id' => '_bil24_sync_status',
                    'label' => 'Статус синхронизации',
                    'options' => [
                        'pending' => 'Ожидает',
                        'synced' => 'Синхронизирован',
                        'error' => 'Ошибка',
                        'manual' => 'Вручную'
                    ],
                    'value' => $sync_status ?: 'pending'
                ] );
                
                woocommerce_wp_checkbox( [
                    'id' => '_bil24_auto_sync',
                    'label' => 'Автосинхронизация',
                    'description' => 'Автоматически синхронизировать с Bil24'
                ] );
                ?>
            </div>
            
            <div class="options_group">
                <p class="form-field">
                    <label>Действия:</label>
                    <button type="button" class="button" onclick="syncProductToBil24(<?php echo $post->ID; ?>)">
                        Синхронизировать в Bil24
                    </button>
                    <button type="button" class="button" onclick="syncProductFromBil24(<?php echo $post->ID; ?>)">
                        Загрузить из Bil24
                    </button>
                </p>
                <div id="product-sync-result-<?php echo $post->ID; ?>"></div>
            </div>
        </div>
        <?php
    }

    /**
     * Save product data fields
     */
    public function save_product_data_fields( int $product_id ): void {
        if ( isset( $_POST['_bil24_event_id'] ) ) {
            update_post_meta( $product_id, Constants::META_BIL24_ID, sanitize_text_field( $_POST['_bil24_event_id'] ) );
        }
        
        if ( isset( $_POST['_bil24_sync_status'] ) ) {
            update_post_meta( $product_id, Constants::META_SYNC_STATUS, sanitize_text_field( $_POST['_bil24_sync_status'] ) );
        }
        
        if ( isset( $_POST['_bil24_auto_sync'] ) ) {
            update_post_meta( $product_id, '_bil24_auto_sync', 'yes' );
        } else {
            update_post_meta( $product_id, '_bil24_auto_sync', 'no' );
        }
    }

    /**
     * Display event information on product page
     */
    public function display_event_info(): void {
        global $product;
        
        $bil24_id = get_post_meta( $product->get_id(), Constants::META_BIL24_ID, true );
        
        if ( ! $bil24_id ) {
            return;
        }
        
        $start_date = get_post_meta( $product->get_id(), '_bil24_start_date', true );
        $venue_name = get_post_meta( $product->get_id(), '_bil24_venue_name', true );
        
        if ( $start_date || $venue_name ) {
            echo '<div class="bil24-event-info">';
            echo '<h3>Информация о событии</h3>';
            
            if ( $start_date ) {
                $date = new \DateTime( $start_date );
                echo '<p><strong>Дата проведения:</strong> ' . $date->format( 'd.m.Y H:i' ) . '</p>';
            }
            
            if ( $venue_name ) {
                echo '<p><strong>Место проведения:</strong> ' . esc_html( $venue_name ) . '</p>';
            }
            
            echo '</div>';
        }
    }

    /**
     * Add event details tab to product tabs
     */
    public function add_event_details_tab( array $tabs ): array {
        global $product;
        
        $bil24_id = get_post_meta( $product->get_id(), Constants::META_BIL24_ID, true );
        
        if ( $bil24_id ) {
            $tabs['event_details'] = [
                'title' => 'Детали события',
                'priority' => 20,
                'callback' => [ $this, 'render_event_details_tab' ]
            ];
        }
        
        return $tabs;
    }

    /**
     * Render event details tab content
     */
    public function render_event_details_tab(): void {
        global $product;
        
        $product_id = $product->get_id();
        $bil24_data = get_post_meta( $product_id, Constants::META_BIL24_DATA, true );
        
        if ( $bil24_data ) {
            echo '<div class="bil24-event-details">';
            echo '<h2>Детали события</h2>';
            
            // Venue information
            if ( ! empty( $bil24_data['venue_name'] ) ) {
                echo '<h3>Место проведения</h3>';
                echo '<p><strong>Название:</strong> ' . esc_html( $bil24_data['venue_name'] ) . '</p>';
                
                if ( ! empty( $bil24_data['venue_address'] ) ) {
                    echo '<p><strong>Адрес:</strong> ' . esc_html( $bil24_data['venue_address'] ) . '</p>';
                }
            }
            
            // Event timing
            if ( ! empty( $bil24_data['start_date'] ) ) {
                echo '<h3>Время проведения</h3>';
                $start_date = new \DateTime( $bil24_data['start_date'] );
                echo '<p><strong>Начало:</strong> ' . $start_date->format( 'd.m.Y H:i' ) . '</p>';
                
                if ( ! empty( $bil24_data['end_date'] ) ) {
                    $end_date = new \DateTime( $bil24_data['end_date'] );
                    echo '<p><strong>Окончание:</strong> ' . $end_date->format( 'd.m.Y H:i' ) . '</p>';
                }
            }
            
            // Additional info
            if ( ! empty( $bil24_data['age_restrictions'] ) ) {
                echo '<h3>Дополнительная информация</h3>';
                echo '<p><strong>Возрастные ограничения:</strong> ' . esc_html( $bil24_data['age_restrictions'] ) . '</p>';
            }
            
            echo '</div>';
        }
    }

    /**
     * Get stock quantity from Bil24 in real-time
     */
    public function get_stock_from_bil24( $stock_quantity, $product ) {
        $bil24_id = get_post_meta( $product->get_id(), Constants::META_BIL24_ID, true );
        
        if ( ! $bil24_id ) {
            return $stock_quantity;
        }
        
        // Check cache first
        $cache_key = Constants::CACHE_PREFIX . 'stock_' . $bil24_id;
        $cached_stock = wp_cache_get( $cache_key, Constants::CACHE_GROUP );
        
        if ( $cached_stock !== false ) {
            return $cached_stock;
        }
        
        try {
            // Get fresh stock from Bil24 API
            $event_data = $this->api->get_event( $bil24_id );
            $available_tickets = $event_data['available_tickets'] ?? $stock_quantity;
            
            // Cache for 5 minutes
            wp_cache_set( $cache_key, $available_tickets, Constants::CACHE_GROUP, 300 );
            
            return $available_tickets;
            
        } catch ( \Exception $e ) {
            Utils::log( "Ошибка получения стока из Bil24: " . $e->getMessage(), Constants::LOG_LEVEL_WARNING );
            return $stock_quantity;
        }
    }

    /**
     * AJAX handler for manual sync
     */
    public function ajax_manual_sync(): void {
        check_ajax_referer( 'bil24_admin_nonce', 'nonce' );
        
        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            wp_die( 'Недостаточно прав доступа' );
        }
        
        $filters = $_POST['filters'] ?? [];
        $results = $this->import_events_as_products( $filters );
        
        wp_send_json_success( $results );
    }

    /**
     * AJAX handler for importing events
     */
    public function ajax_import_events(): void {
        check_ajax_referer( 'bil24_admin_nonce', 'nonce' );
        
        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            wp_die( 'Недостаточно прав доступа' );
        }
        
        $event_ids = $_POST['event_ids'] ?? [];
        $results = [
            'imported' => 0,
            'errors' => 0,
            'messages' => []
        ];
        
        foreach ( $event_ids as $event_id ) {
            try {
                $event_data = $this->api->get_event( $event_id );
                $event = new Event( $event_data );
                $product_id = $this->create_or_update_product_from_event( $event );
                
                if ( $product_id ) {
                    $results['imported']++;
                    $results['messages'][] = "Импортировано событие {$event_id} как продукт {$product_id}";
                }
                
            } catch ( \Exception $e ) {
                $results['errors']++;
                $results['messages'][] = "Ошибка импорта события {$event_id}: " . $e->getMessage();
            }
        }
        
        wp_send_json_success( $results );
    }

    /**
     * AJAX handler for syncing single product
     */
    public function ajax_sync_single_product(): void {
        check_ajax_referer( 'bil24_admin_nonce', 'nonce' );
        
        if ( ! current_user_can( 'edit_products' ) ) {
            wp_die( 'Недостаточно прав доступа' );
        }
        
        $product_id = intval( $_POST['product_id'] ?? 0 );
        $direction = sanitize_text_field( $_POST['direction'] ?? 'from_bil24' );
        
        try {
            if ( $direction === 'from_bil24' ) {
                $result = $this->sync_product_from_bil24( $product_id );
            } else {
                $result = $this->sync_product_to_bil24( $product_id );
            }
            
            wp_send_json_success( $result );
            
        } catch ( \Exception $e ) {
            wp_send_json_error( [
                'message' => $e->getMessage()
            ] );
        }
    }

    /**
     * Sync product from Bil24
     */
    public function sync_product_from_bil24( int $product_id ): array {
        $bil24_id = get_post_meta( $product_id, Constants::META_BIL24_ID, true );
        
        if ( ! $bil24_id ) {
            throw new \Exception( 'Продукт не связан с событием Bil24' );
        }
        
        $event_data = $this->api->get_event( $bil24_id );
        $event = new Event( $event_data );
        
        $this->update_product_from_event( $product_id, $event );
        
        return [
            'message' => 'Продукт успешно синхронизирован из Bil24',
            'updated_at' => current_time( 'mysql' )
        ];
    }

    /**
     * Sync product to Bil24
     */
    public function sync_product_to_bil24( int $product_id ): array {
        $product = wc_get_product( $product_id );
        
        if ( ! $product ) {
            throw new \Exception( 'Продукт не найден' );
        }
        
        $event_data = $this->convert_product_to_event_data( $product );
        $bil24_id = get_post_meta( $product_id, Constants::META_BIL24_ID, true );
        
        if ( $bil24_id ) {
            // Update existing event
            $response = $this->api->update_event( $bil24_id, $event_data );
        } else {
            // Create new event
            $response = $this->api->create_event( $event_data );
            if ( ! empty( $response['id'] ) ) {
                update_post_meta( $product_id, Constants::META_BIL24_ID, $response['id'] );
            }
        }
        
        update_post_meta( $product_id, Constants::META_SYNC_STATUS, 'synced' );
        update_post_meta( $product_id, Constants::META_LAST_SYNC, time() );
        
        return [
            'message' => 'Продукт успешно синхронизирован в Bil24',
            'bil24_id' => $response['id'] ?? $bil24_id,
            'updated_at' => current_time( 'mysql' )
        ];
    }

    /**
     * Convert WooCommerce product to Bil24 event data
     */
    private function convert_product_to_event_data( \WC_Product $product ): array {
        return [
            'title' => $product->get_name(),
            'description' => $product->get_description(),
            'price' => floatval( $product->get_regular_price() ),
            'currency' => get_woocommerce_currency(),
            'available_tickets' => $product->get_stock_quantity(),
            'status' => $product->get_status() === 'publish' ? 'active' : 'draft',
            'start_date' => get_post_meta( $product->get_id(), '_bil24_start_date', true ),
            'end_date' => get_post_meta( $product->get_id(), '_bil24_end_date', true ),
            'venue_id' => get_post_meta( $product->get_id(), '_bil24_venue_id', true ),
            'event_type' => get_post_meta( $product->get_id(), '_bil24_event_type', true )
        ];
    }

    /**
     * Scheduled synchronization
     */
    public function scheduled_sync(): void {
        $auto_sync_enabled = get_option( 'bil24_auto_sync_products', false );
        
        if ( ! $auto_sync_enabled ) {
            return;
        }
        
        try {
            $this->import_events_as_products( [
                'updated_since' => date( 'Y-m-d H:i:s', strtotime( '-1 hour' ) )
            ] );
            
            Utils::log( 'Автоматическая синхронизация продуктов выполнена', Constants::LOG_LEVEL_INFO );
            
        } catch ( \Exception $e ) {
            Utils::log( 'Ошибка автоматической синхронизации: ' . $e->getMessage(), Constants::LOG_LEVEL_ERROR );
        }
    }

    /**
     * Handle product creation
     */
    public function on_product_created( int $product_id ): void {
        $auto_sync = get_post_meta( $product_id, '_bil24_auto_sync', true );
        
        if ( $auto_sync === 'yes' ) {
            try {
                $this->sync_product_to_bil24( $product_id );
            } catch ( \Exception $e ) {
                Utils::log( "Ошибка синхронизации нового продукта {$product_id}: " . $e->getMessage(), Constants::LOG_LEVEL_ERROR );
            }
        }
    }

    /**
     * Handle product update
     */
    public function on_product_updated( int $product_id ): void {
        $auto_sync = get_post_meta( $product_id, '_bil24_auto_sync', true );
        
        if ( $auto_sync === 'yes' ) {
            try {
                $this->sync_product_to_bil24( $product_id );
            } catch ( \Exception $e ) {
                Utils::log( "Ошибка синхронизации обновленного продукта {$product_id}: " . $e->getMessage(), Constants::LOG_LEVEL_ERROR );
            }
        }
    }

    /**
     * Handle product deletion
     */
    public function on_product_deleted( int $post_id, \WP_Post $post ): void {
        if ( $post->post_type !== 'product' ) {
            return;
        }
        
        $bil24_id = get_post_meta( $post_id, Constants::META_BIL24_ID, true );
        
        if ( $bil24_id ) {
            try {
                // Don't delete from Bil24, just mark as cancelled
                $this->api->update_event( $bil24_id, [ 'status' => 'cancelled' ] );
                Utils::log( "Событие {$bil24_id} отмечено как отмененное при удалении продукта {$post_id}", Constants::LOG_LEVEL_INFO );
            } catch ( \Exception $e ) {
                Utils::log( "Ошибка отмены события при удалении продукта: " . $e->getMessage(), Constants::LOG_LEVEL_ERROR );
            }
        }
    }
} 