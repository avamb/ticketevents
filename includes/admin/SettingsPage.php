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
        </div>
    <?php }
}
