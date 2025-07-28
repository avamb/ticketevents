<?php
namespace Bil24\Admin;

defined( 'ABSPATH' ) || exit;

/**
 * Settings page for Bil24 Connector plugin
 * 
 * @package Bil24Connector
 * @since 0.1.0
 */
class SettingsPage {

    private const OPTION_KEY = 'bil24_settings';

    /**
     * Register hooks for the settings page
     */
    public function register(): void {
        add_action( 'admin_menu',   [ $this, 'add_menu' ] );
        add_action( 'admin_init',   [ $this, 'register_settings' ] );
        add_action( 'wp_ajax_bil24_test_connection', [ $this, 'ajax_test_connection' ] );
    }

    /**
     * Add settings page to WordPress admin menu
     */
    public function add_menu(): void {
        add_options_page(
            __( 'Bil24 Connector Settings', 'bil24' ),
            __( 'Bil24 Connector', 'bil24' ),
            'manage_options',
            'bil24-connector',
            [ $this, 'render_page' ]
        );
    }

    /**
     * Register plugin settings
     */
    public function register_settings(): void {
        register_setting(
            'bil24_settings_group',
            self::OPTION_KEY,
            [ 'sanitize_callback' => [ $this, 'sanitize' ] ]
        );
    }

    /**
     * Sanitize input data
     * 
     * @param array $input Raw input data
     * @return array Sanitized data
     */
    public function sanitize( array $input ): array {
        return [
            'fid'   => sanitize_text_field( $input['fid']   ?? '' ),
            'token' => sanitize_text_field( $input['token'] ?? '' ),
            'env'   => ($input['env'] ?? 'test') === 'prod' ? 'prod' : 'test',
        ];
    }

    /**
     * Render the settings page HTML
     */
    public function render_page(): void {
        $opts = get_option( self::OPTION_KEY, [ 'env' => 'test' ] ); ?>
        <div class="wrap">
            <h1><?php esc_html_e( 'Bil24 Connector — Settings', 'bil24' ); ?></h1>

            <form method="post" action="options.php">
                <?php settings_fields( 'bil24_settings_group' ); ?>

                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row"><label for="fid"><?php esc_html_e( 'FID (Interface ID)', 'bil24' ); ?></label></th>
                        <td>
                            <input name="<?php echo esc_attr( self::OPTION_KEY ); ?>[fid]"
                                   id="fid" class="regular-text" required
                                   value="<?php echo esc_attr( $opts['fid'] ?? '' ); ?>">
                            <p class="description"><?php esc_html_e( 'Your Bil24 FID (interface identifier)', 'bil24' ); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="token"><?php esc_html_e( 'API Token', 'bil24' ); ?></label></th>
                        <td>
                            <input name="<?php echo esc_attr( self::OPTION_KEY ); ?>[token]"
                                   id="token" class="regular-text" required type="password"
                                   value="<?php echo esc_attr( $opts['token'] ?? '' ); ?>">
                            <p class="description"><?php esc_html_e( 'Your Bil24 API token for authentication', 'bil24' ); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e( 'Environment', 'bil24' ); ?></th>
                        <td>
                            <fieldset>
                                <label>
                                    <input type="radio"
                                           name="<?php echo esc_attr( self::OPTION_KEY ); ?>[env]"
                                           value="test" <?php checked( $opts['env'], 'test' ); ?>>
                                    <?php esc_html_e( 'Test Environment', 'bil24' ); ?>
                                    <span class="description">(api.bil24.pro:1240)</span>
                                </label><br>
                                <label>
                                    <input type="radio"
                                           name="<?php echo esc_attr( self::OPTION_KEY ); ?>[env]"
                                           value="prod" <?php checked( $opts['env'], 'prod' ); ?>>
                                    <?php esc_html_e( 'Production Environment', 'bil24' ); ?>
                                    <span class="description">(api.bil24.pro)</span>
                                </label>
                            </fieldset>
                            <p class="description"><?php esc_html_e( 'Select the Bil24 API environment to connect to', 'bil24' ); ?></p>
                        </td>
                    </tr>
                </table>

                <?php submit_button(); ?>
            </form>

            <div class="bil24-test-connection" style="margin-top: 20px;">
                <h3><?php esc_html_e( 'Connection Test', 'bil24' ); ?></h3>
                <p><?php esc_html_e( 'Test the connection to Bil24 API with current settings:', 'bil24' ); ?></p>
                <button type="button" id="bil24-test-btn" class="button button-secondary">
                    <?php esc_html_e( 'Test Connection', 'bil24' ); ?>
                </button>
                <div id="bil24-test-result" style="margin-top: 10px;"></div>
            </div>

            <script>
            document.getElementById('bil24-test-btn').addEventListener('click', function() {
                const button = this;
                const result = document.getElementById('bil24-test-result');
                
                button.disabled = true;
                button.textContent = <?php echo wp_json_encode( __( 'Testing...', 'bil24' ) ); ?>;
                result.innerHTML = '';
                
                fetch(ajaxurl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'action=bil24_test_connection&_ajax_nonce=' + <?php echo wp_json_encode( wp_create_nonce( 'bil24_test_connection' ) ); ?>
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        result.innerHTML = '<div class="notice notice-success inline"><p><strong>' + 
                            <?php echo wp_json_encode( __( 'Success!', 'bil24' ) ); ?> + 
                            '</strong> ' + data.data.message + '</p></div>';
                    } else {
                        result.innerHTML = '<div class="notice notice-error inline"><p><strong>' + 
                            <?php echo wp_json_encode( __( 'Error:', 'bil24' ) ); ?> + 
                            '</strong> ' + data.data.message + '</p></div>';
                    }
                })
                .catch(error => {
                    result.innerHTML = '<div class="notice notice-error inline"><p><strong>' + 
                        <?php echo wp_json_encode( __( 'Error:', 'bil24' ) ); ?> + 
                        '</strong> ' + error.message + '</p></div>';
                })
                .finally(() => {
                    button.disabled = false;
                    button.textContent = <?php echo wp_json_encode( __( 'Test Connection', 'bil24' ) ); ?>;
                });
            });
            </script>
        </div>
    <?php }

    /**
     * AJAX handler for connection testing
     */
    public function ajax_test_connection(): void {
        check_ajax_referer( 'bil24_test_connection' );
        
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( __( 'Insufficient permissions', 'bil24' ) );
        }

        try {
            $api = new \Bil24\Api\Client();
            $connected = $api->test_connection();
            
            if ( $connected ) {
                wp_send_json_success( [
                    'message' => __( 'Connection to Bil24 API established successfully!', 'bil24' )
                ] );
            } else {
                wp_send_json_error( [
                    'message' => __( 'Failed to connect to Bil24 API. Please check your settings.', 'bil24' )
                ] );
            }
        } catch ( \Exception $e ) {
            wp_send_json_error( [
                'message' => sprintf( 
                    /* translators: %s: error message */
                    __( 'Connection error: %s', 'bil24' ), 
                    $e->getMessage() 
                )
            ] );
        }
    }
}
