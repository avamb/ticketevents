<?php
namespace Bil24\Admin;

use Bil24\Constants;

defined( 'ABSPATH' ) || exit;

/**
 * ВРЕМЕННАЯ ВЕРСИЯ Settings page БЕЗ проверки прав
 * Используется ТОЛЬКО для диагностики проблемы с правами доступа
 * 
 * @package Bil24Connector
 * @since 0.1.0
 */
class SettingsPageNoCapsCheck {

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
            error_log( '[Bil24] SettingsPageNoCapsCheck registered successfully' );
        }
    }

    /**
     * Add the settings page to the admin menu
     */
    public function add_menu(): void {
        // Register as options page under Settings menu
        add_options_page(
            __( 'Bil24 Connector Settings (NO CAPS CHECK)', 'bil24' ), // Page title
            __( 'Bil24 Connector (TEST)', 'bil24' ), // Menu title
            'read', // МИНИМАЛЬНЫЕ права вместо manage_options
            'bil24-connector-test', // Menu slug
            [ $this, 'render_page' ] // Callback function
        );
        
        // Debug logging
        if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
            error_log( '[Bil24] TEST Settings page registered with slug: bil24-connector-test' );
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
     */
    public function sanitize( array $input ): array {
        return [
            'fid'   => sanitize_text_field( $input['fid']   ?? '' ),
            'token' => sanitize_text_field( $input['token'] ?? '' ),
            'env'   => ($input['env'] ?? 'test') === 'prod' ? 'prod' : 'test',
        ];
    }

    /**
     * Render the settings page HTML - БЕЗ ПРОВЕРКИ ПРАВ
     */
    public function render_page(): void {
        // ПОДРОБНАЯ диагностика БЕЗ проверки прав
        $user = wp_get_current_user();
        $can_manage = current_user_can( 'manage_options' );
        
        echo '<div class="wrap">';
        echo '<h1>🧪 Bil24 Connector — TEST Settings (БЕЗ ПРОВЕРКИ ПРАВ)</h1>';
        
        echo '<div style="background: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; margin: 20px 0;">';
        echo '<h3>⚠️ ВНИМАНИЕ: Это тестовая страница</h3>';
        echo '<p>Эта страница создана для диагностики проблемы с правами доступа. ';
        echo 'Обычная проверка прав <code>manage_options</code> отключена.</p>';
        echo '</div>';
        
        // Показываем диагностическую информацию прямо на странице
        echo '<div style="background: #f8f9fa; border: 1px solid #dee2e6; padding: 15px; margin: 20px 0;">';
        echo '<h3>🔍 Диагностика пользователя</h3>';
        echo '<table class="form-table">';
        echo '<tr><th>User ID:</th><td>' . $user->ID . '</td></tr>';
        echo '<tr><th>Login:</th><td>' . $user->user_login . '</td></tr>';
        echo '<tr><th>Email:</th><td>' . $user->user_email . '</td></tr>';
        echo '<tr><th>Роли:</th><td>' . implode( ', ', $user->roles ) . '</td></tr>';
        echo '<tr><th>Может manage_options:</th><td>' . ( $can_manage ? '✅ ДА' : '❌ НЕТ' ) . '</td></tr>';
        echo '<tr><th>Авторизован:</th><td>' . ( is_user_logged_in() ? '✅ ДА' : '❌ НЕТ' ) . '</td></tr>';
        echo '<tr><th>В админке:</th><td>' . ( is_admin() ? '✅ ДА' : '❌ НЕТ' ) . '</td></tr>';
        echo '<tr><th>Супер админ:</th><td>' . ( is_super_admin() ? '✅ ДА' : '❌ НЕТ' ) . '</td></tr>';
        echo '<tr><th>Multisite:</th><td>' . ( is_multisite() ? '✅ ДА' : '❌ НЕТ' ) . '</td></tr>';
        echo '<tr><th>WordPress версия:</th><td>' . get_bloginfo( 'version' ) . '</td></tr>';
        echo '</table>';
        echo '</div>';
        
        // Логируем в файл
        if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
            error_log( '[Bil24] === TEST PAGE ДИАГНОСТИКА ===' );
            error_log( '[Bil24] User ID: ' . $user->ID );
            error_log( '[Bil24] User login: ' . $user->user_login );
            error_log( '[Bil24] User roles: ' . implode( ', ', $user->roles ) );
            error_log( '[Bil24] Can manage_options: ' . ( $can_manage ? 'YES' : 'NO' ) );
            error_log( '[Bil24] Is user logged in: ' . ( is_user_logged_in() ? 'YES' : 'NO' ) );
            error_log( '[Bil24] Is admin: ' . ( is_admin() ? 'YES' : 'NO' ) );
            error_log( '[Bil24] Is super admin: ' . ( is_super_admin() ? 'YES' : 'NO' ) );
        }

        // Get option name with fallback
        $option_name = 'bil24_settings';
        if ( class_exists( '\\Bil24\\Constants' ) ) {
            $option_name = \Bil24\Constants::OPTION_SETTINGS;
        }
        
        $opts = get_option( $option_name, [ 'env' => 'test' ] ); ?>
        
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

        <div style="background: #d1ecf1; border: 1px solid #bee5eb; padding: 15px; margin: 20px 0;">
            <h3>💡 Что делать дальше:</h3>
            <ol>
                <li>Если эта страница работает - проблема в проверке прав</li>
                <li>Скопируйте диагностическую информацию выше</li>
                <li>Проверьте логи WordPress в wp-content/debug.log</li>
                <li>Сообщите разработчику результаты</li>
            </ol>
        </div>
        
        </div>
    <?php }
} 