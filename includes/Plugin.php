<?php
namespace Bil24;

defined( 'ABSPATH' ) || exit;

final class Plugin {

    private static ?Plugin $instance = null;

    public static function instance(): Plugin {
        return self::$instance ??= new self();
    }

    private function __construct() {
        // Register activation hook.
        register_activation_hook( __DIR__ . '/../bil24-connector.php', [ self::class, 'activate' ] );
        /*** Admin UI ***/              // NEW
        if ( is_admin() ) {             // NEW
            ( new \Bil24\Admin\SettingsPage() )->register();  // NEW
        }                               // NEW
        // Init hooks.
        add_action( 'init', [ $this, 'register_cpt' ] );
    }

    public static function activate(): void {
        if ( ! wp_next_scheduled( 'bil24_sync_catalog' ) ) {
            wp_schedule_event( time() + 60, 'hourly', 'bil24_sync_catalog' );
        }
    }

    public function register_cpt(): void {
        // Placeholder — will add CPT registration later.
    }
}
