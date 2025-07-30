<?php
namespace Bil24\Admin;

use Bil24\Constants;

defined( 'ABSPATH' ) || exit;

/**
 * Settings page for Bil24 Connector plugin
 * 
 * @package Bil24Connector
 * @since 0.1.0
 */
class SettingsPage {

    /**
     * Register the settings page
     */
    public function register(): void {
        // Add menu item
        $this->add_menu();
        
        // Register settings
        $this->register_settings();
        
        // Debug logging
        if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
            error_log( '[Bil24] SettingsPage registered successfully' );
        }
    }

    /**
     * Add the settings page to the admin menu
     */
    public function add_menu(): void {
        // Register as options page under Settings menu
        add_options_page(
            __( 'Bil24 Connector Settings', 'bil24' ), // Page title
            __( 'Bil24 Connector', 'bil24' ), // Menu title
            'manage_options', // Capability required
            'bil24-connector', // Menu slug
            [ $this, 'render_page' ] // Callback function
        );
        
        // Debug logging
        if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
            error_log( '[Bil24] Settings page registered with slug: bil24-connector' );
        }
    }

    /**
     * Register plugin settings
     */
    public function register_settings(): void {
        // Get option name with fallback
        $option_name = 'bil24_settings';
        if ( class_exists( '\\Bil24\\Constants' ) ) {
            $option_name = \Bil24\Constants::OPTION_SETTINGS;
        }
        
        register_setting(
            'bil24_settings_group',
            $option_name,
            [ 'sanitize_callback' => [ $this, 'sanitize' ] ]
        );
        
        // Debug logging
        if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
            error_log( '[Bil24] Settings registered: ' . $option_name );
            error_log( '[Bil24] Constants class exists: ' . ( class_exists( '\\Bil24\\Constants' ) ? 'YES' : 'NO' ) );
        }
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
        // РАСШИРЕННАЯ диагностика прав пользователя
        $user = wp_get_current_user();
        $can_manage = current_user_can( 'manage_options' );
        
        // Всегда логируем подробную информацию
        if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
            error_log( '[Bil24] === ДИАГНОСТИКА ПРАВ ПОЛЬЗОВАТЕЛЯ ===' );
            error_log( '[Bil24] User ID: ' . $user->ID );
            error_log( '[Bil24] User login: ' . $user->user_login );
            error_log( '[Bil24] User roles: ' . implode( ', ', $user->roles ) );
            error_log( '[Bil24] Can manage_options: ' . ( $can_manage ? 'YES' : 'NO' ) );
            error_log( '[Bil24] Is user logged in: ' . ( is_user_logged_in() ? 'YES' : 'NO' ) );
            error_log( '[Bil24] Is admin: ' . ( is_admin() ? 'YES' : 'NO' ) );
            error_log( '[Bil24] Is super admin: ' . ( is_super_admin() ? 'YES' : 'NO' ) );
            
            // Проверяем другие важные права
            $other_caps = ['administrator', 'edit_plugins', 'activate_plugins', 'switch_themes'];
            foreach ( $other_caps as $cap ) {
                error_log( "[Bil24] Can {$cap}: " . ( current_user_can( $cap ) ? 'YES' : 'NO' ) );
            }
            
            // Показываем первые 10 прав пользователя
            $user_caps = array_keys( array_filter( $user->allcaps ) );
            error_log( '[Bil24] First 10 user capabilities: ' . implode( ', ', array_slice( $user_caps, 0, 10 ) ) );
        }
        
        // Если нет прав - показываем подробную ошибку
        if ( ! $can_manage ) {
            $error_message = sprintf(
                __( 'Sorry, you are not allowed to access this page. User ID: %d, Roles: %s, Can manage_options: %s', 'bil24' ),
                $user->ID,
                implode( ', ', $user->roles ),
                $can_manage ? 'YES' : 'NO'
            );
            
            $error_title = __( 'Access Denied - Bil24 Connector', 'bil24' );
            
            // Добавляем дополнительную информацию для диагностики
            if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                $error_message .= '<br><br><strong>Диагностическая информация:</strong><br>';
                $error_message .= 'Logged in: ' . ( is_user_logged_in() ? 'YES' : 'NO' ) . '<br>';
                $error_message .= 'Is admin: ' . ( is_admin() ? 'YES' : 'NO' ) . '<br>';
                $error_message .= 'Is super admin: ' . ( is_super_admin() ? 'YES' : 'NO' ) . '<br>';
                $error_message .= 'WordPress version: ' . get_bloginfo( 'version' ) . '<br>';
                $error_message .= 'Is multisite: ' . ( is_multisite() ? 'YES' : 'NO' ) . '<br>';
                
                if ( is_multisite() ) {
                    $error_message .= 'Current blog ID: ' . get_current_blog_id() . '<br>';
                    $error_message .= 'Network admin: ' . ( is_network_admin() ? 'YES' : 'NO' ) . '<br>';
                }
            }
            
            wp_die( 
                $error_message,
                $error_title,
                [ 'response' => 403 ]
            );
        }
        
        // Debug logging для успешного доступа
        if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
            error_log( '[Bil24] ✅ User has manage_options capability - rendering settings page' );
        }
        
        // Get option name with fallback
        $option_name = 'bil24_settings';
        if ( class_exists( '\\Bil24\\Constants' ) ) {
            $option_name = \Bil24\Constants::OPTION_SETTINGS;
        }
        
        $opts = get_option( $option_name, [ 'env' => 'test' ] ); ?>
        <div class="wrap">
            <h1><?php esc_html_e( 'Bil24 Connector — Settings', 'bil24' ); ?></h1>

            <form method="post" action="options.php">
                <?php settings_fields( 'bil24_settings_group' ); ?>

                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row"><label for="fid"><?php esc_html_e( 'FID (Interface ID)', 'bil24' ); ?></label></th>
                        <td>
                            <input name="<?php echo esc_attr( $option_name ); ?>[fid]"
                                   id="fid" class="regular-text" required
                                   value="<?php echo esc_attr( $opts['fid'] ?? '' ); ?>">
                            <p class="description"><?php esc_html_e( 'Your Bil24 FID (interface identifier)', 'bil24' ); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="token"><?php esc_html_e( 'API Token', 'bil24' ); ?></label></th>
                        <td>
                            <input name="<?php echo esc_attr( $option_name ); ?>[token]"
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
                                           name="<?php echo esc_attr( $option_name ); ?>[env]"
                                           value="test" <?php checked( $opts['env'], 'test' ); ?>>
                                    <?php esc_html_e( 'Test Environment', 'bil24' ); ?>
                                    <span class="description">(api.bil24.pro:1240)</span>
                                </label><br>
                                <label>
                                    <input type="radio"
                                           name="<?php echo esc_attr( $option_name ); ?>[env]"
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
                .then(async response => {
                    try {
                        return await response.json();
                    } catch (e) {
                        // Не JSON – читаем как текст
                        const text = await response.text();
                        return { success: false, message: text || 'Non-JSON response' };
                    }
                })
                .then(data => {
                    // Формируем сообщение с учётом разных структур ответа
                    const success = data && typeof data.success !== 'undefined' ? data.success : false;
                    const message = (data && data.data && typeof data.data.message !== 'undefined')
                        ? data.data.message
                        : (data && typeof data.message === 'string'
                            ? data.message
                            : <?php echo wp_json_encode( __( 'Unknown error. Check browser console for details.', 'bil24' ) ); ?>);

                    const noticeClass = success ? 'notice-success' : 'notice-error';
                    const noticeLabel = success
                        ? <?php echo wp_json_encode( __( 'Success!', 'bil24' ) ); ?>
                        : <?php echo wp_json_encode( __( 'Error:', 'bil24' ) ); ?>;

                    result.innerHTML = '<div class="notice ' + noticeClass + ' inline"><p><strong>' +
                        noticeLabel + '</strong> ' + message + '</p></div>';
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
        
        <!-- Debug Information (visible only when WP_DEBUG is enabled) -->
        <?php if ( defined( 'WP_DEBUG' ) && WP_DEBUG ): ?>
        <div style="margin-top: 30px; padding: 10px; background: #f0f0f0; border: 1px solid #ccc;">
            <h4>Debug Information</h4>
            <p><strong>Current User ID:</strong> <?php echo get_current_user_id(); ?></p>
            <p><strong>User Login:</strong> <?php echo wp_get_current_user()->user_login; ?></p>
            <p><strong>User Roles:</strong> <?php echo implode( ', ', wp_get_current_user()->roles ); ?></p>
            <p><strong>User Capabilities:</strong> <?php echo implode( ', ', array_keys( wp_get_current_user()->allcaps ) ); ?></p>
            <p><strong>Can manage_options:</strong> <?php echo current_user_can( 'manage_options' ) ? 'YES' : 'NO'; ?></p>
            <p><strong>Constants Class:</strong> <?php echo class_exists( '\\Bil24\\Constants' ) ? 'Loaded' : 'NOT LOADED'; ?></p>
            <p><strong>Settings Option:</strong> <?php echo $option_name; ?></p>
            <p><strong>Current Settings:</strong> <?php var_dump( $opts ); ?></p>
        </div>
        <?php endif; ?>
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
            // Проверяем настройки перед тестированием
            $settings = get_option( Constants::OPTION_SETTINGS, [] );
            $fid = $settings['fid'] ?? '';
            $token = $settings['token'] ?? '';
            $env = $settings['env'] ?? 'test';
            
            if ( empty( $fid ) || empty( $token ) ) {
                wp_send_json_error( [
                    'message' => __( 'Please configure FID and Token credentials first.', 'bil24' )
                ] );
                return;
            }
            
            // Load API Client if not already loaded
            if ( ! class_exists( '\\Bil24\\Api\\Client' ) ) {
                $api_client_file = __DIR__ . '/../Api/Client.php';
                if ( file_exists( $api_client_file ) ) {
                    require_once $api_client_file;
                } else {
                    throw new \Exception( __( 'API Client class file not found', 'bil24' ) );
                }
            }
            
            $api = new \Bil24\Api\Client();
            
            // Получаем информацию о конфигурации для диагностики
            $config = $api->get_config_status();
            
            if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                error_log( '[Bil24] Testing connection with config: ' . wp_json_encode( $config ) );
            }
            
            $connected = $api->test_connection();
            
            if ( $connected ) {
                wp_send_json_success( [
                    'message' => sprintf(
                        __( 'Connection to Bil24 API (%s environment) established successfully!', 'bil24' ),
                        ucfirst( $env )
                    )
                ] );
            } else {
                wp_send_json_error( [
                    'message' => sprintf(
                        __( 'Failed to connect to Bil24 API (%s environment). Please check your FID and Token credentials.', 'bil24' ),
                        ucfirst( $env )
                    )
                ] );
            }
        } catch ( \Exception $e ) {
            // Логируем подробную ошибку
            if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                error_log( '[Bil24] Connection test exception: ' . $e->getMessage() );
                error_log( '[Bil24] Exception trace: ' . $e->getTraceAsString() );
            }
            
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