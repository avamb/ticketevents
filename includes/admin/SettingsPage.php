<?php
namespace Bil24\Admin;

defined( 'ABSPATH' ) || exit;

/**
 * Страница «Настройки → Bil24 Connector».
 */
class SettingsPage {

    private const OPTION_KEY = 'bil24_settings';

    /** Регистрируем хук-инициализацию из Plugin.php */
    public function register(): void {
        add_action( 'admin_menu',   [ $this, 'add_menu' ] );
        add_action( 'admin_init',   [ $this, 'register_settings' ] );
        add_action( 'wp_ajax_bil24_test_connection', [ $this, 'ajax_test_connection' ] );
    }

    /** Добавляем пункт меню */
    public function add_menu(): void {
        add_options_page(
            'Bil24 Connector',
            'Bil24 Connector',
            'manage_options',
            'bil24-connector',
            [ $this, 'render_page' ]
        );
    }

    /** Регистрируем опции */
    public function register_settings(): void {
        register_setting(
            'bil24_settings_group',
            self::OPTION_KEY,
            [ 'sanitize_callback' => [ $this, 'sanitize' ] ]
        );
    }

    /** Очистка данных */
    public function sanitize( array $input ): array {
        return [
            'fid'   => sanitize_text_field( $input['fid']   ?? '' ),
            'token' => sanitize_text_field( $input['token'] ?? '' ),
            'env'   => ($input['env'] ?? 'test') === 'prod' ? 'prod' : 'test',
        ];
    }

    /** Рендер HTML-формы */
    public function render_page(): void {
        $opts = get_option( self::OPTION_KEY, [ 'env' => 'test' ] ); ?>
        <div class="wrap">
            <h1>Bil24 Connector &mdash; Настройки</h1>

            <form method="post" action="options.php">
                <?php settings_fields( 'bil24_settings_group' ); ?>

                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row"><label for="fid">FID (interface ID)</label></th>
                        <td><input name="<?php echo self::OPTION_KEY; ?>[fid]"
                                   id="fid" class="regular-text" required
                                   value="<?php echo esc_attr( $opts['fid'] ?? '' ); ?>"></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="token">Token</label></th>
                        <td><input name="<?php echo self::OPTION_KEY; ?>[token]"
                                   id="token" class="regular-text" required
                                   value="<?php echo esc_attr( $opts['token'] ?? '' ); ?>"></td>
                    </tr>
                    <tr>
                        <th scope="row">Environment</th>
                        <td>
                            <label><input type="radio"
                                   name="<?php echo self::OPTION_KEY; ?>[env]"
                                   value="test" <?php checked( $opts['env'], 'test' ); ?>>
                                   Test&nbsp;(api.bil24.pro:1240)</label><br>
                            <label><input type="radio"
                                   name="<?php echo self::OPTION_KEY; ?>[env]"
                                   value="prod" <?php checked( $opts['env'], 'prod' ); ?>>
                                   Production&nbsp;(api.bil24.pro)</label>
                        </td>
                    </tr>
                </table>

                <?php submit_button(); ?>
            </form>

            <div class="bil24-test-connection" style="margin-top: 20px;">
                <h3>Тестирование соединения</h3>
                <p>Проверьте подключение к API Bil24 с текущими настройками:</p>
                <button type="button" id="bil24-test-btn" class="button button-secondary">
                    Тестировать соединение
                </button>
                <div id="bil24-test-result" style="margin-top: 10px;"></div>
            </div>

            <script>
            document.getElementById('bil24-test-btn').addEventListener('click', function() {
                const button = this;
                const result = document.getElementById('bil24-test-result');
                
                button.disabled = true;
                button.textContent = 'Тестирование...';
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
                        result.innerHTML = '<div class="notice notice-success inline"><p><strong>Успешно!</strong> ' + data.data.message + '</p></div>';
                    } else {
                        result.innerHTML = '<div class="notice notice-error inline"><p><strong>Ошибка:</strong> ' + data.data.message + '</p></div>';
                    }
                })
                .catch(error => {
                    result.innerHTML = '<div class="notice notice-error inline"><p><strong>Ошибка:</strong> ' + error.message + '</p></div>';
                })
                .finally(() => {
                    button.disabled = false;
                    button.textContent = 'Тестировать соединение';
                });
            });
            </script>
        </div>
    <?php }

    /** AJAX обработчик тестирования соединения */
    public function ajax_test_connection(): void {
        check_ajax_referer( 'bil24_test_connection' );
        
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( 'Insufficient permissions' );
        }

        try {
            $api = new \Bil24\Api\Client();
            $connected = $api->test_connection();
            
            if ( $connected ) {
                wp_send_json_success( [
                    'message' => 'Соединение с API Bil24 установлено успешно!'
                ] );
            } else {
                wp_send_json_error( [
                    'message' => 'Не удалось подключиться к API Bil24. Проверьте настройки.'
                ] );
            }
        } catch ( \Exception $e ) {
            wp_send_json_error( [
                'message' => $e->getMessage()
            ] );
        }
    }
}
