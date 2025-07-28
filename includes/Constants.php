<?php
namespace Bil24;

defined( 'ABSPATH' ) || exit;

/**
 * Constants class for Bil24 Connector plugin
 * 
 * @package Bil24Connector
 * @since 0.1.0
 */
final class Constants {
    
    // Plugin info (fallback values when global constants are not available)
    public const PLUGIN_NAME = 'Bil24 Connector';
    public const PLUGIN_VERSION = '0.1.0';
    public const PLUGIN_SLUG = 'bil24-connector';
    public const TEXT_DOMAIN = 'bil24';
    
    // File paths (fallback values)
    public const PLUGIN_FILE = __DIR__ . '/../bil24-connector.php';
    public const PLUGIN_DIR = __DIR__ . '/../';
    public const PLUGIN_URL = '/wp-content/plugins/bil24-connector/';
    public const PLUGIN_BASENAME = 'bil24-connector/bil24-connector.php';
    
    // Asset paths
    public const ASSETS_DIR = __DIR__ . '/../assets/';
    public const ASSETS_URL = '/wp-content/plugins/bil24-connector/assets/';
    
    // Database
    public const DB_PREFIX = 'bil24_';
    public const DB_VERSION = '1.0.0';
    
    // API
    public const API_VERSION = 'v1';
    public const API_NAMESPACE = 'bil24/v1';
    public const API_TIMEOUT = 30;
    public const API_RETRY_ATTEMPTS = 3;
    
    // Hooks and Actions
    public const HOOK_SYNC_CATALOG = 'bil24_sync_catalog';
    public const HOOK_SYNC_ORDERS = 'bil24_sync_orders';
    public const HOOK_SYNC_SESSIONS = 'bil24_sync_sessions';
    public const HOOK_CLEANUP_LOGS = 'bil24_cleanup_logs';
    
    // Cron intervals
    public const CRON_SYNC_INTERVAL = 'hourly';
    public const CRON_CLEANUP_INTERVAL = 'daily';
    
    // Capabilities
    public const CAP_MANAGE_SETTINGS = 'manage_bil24_settings';
    public const CAP_VIEW_REPORTS = 'view_bil24_reports';
    public const CAP_SYNC_DATA = 'sync_bil24_data';
    
    // Options
    public const OPTION_SETTINGS = 'bil24_settings';
    public const OPTION_API_CREDENTIALS = 'bil24_api_credentials';
    public const OPTION_SYNC_STATUS = 'bil24_sync_status';
    public const OPTION_DB_VERSION = 'bil24_db_version';
    
    // Cache keys
    public const CACHE_PREFIX = 'bil24_';
    public const CACHE_GROUP = 'bil24';
    public const CACHE_EXPIRATION = 3600; // 1 hour
    
    // Log levels
    public const LOG_LEVEL_ERROR = 'error';
    public const LOG_LEVEL_WARNING = 'warning';
    public const LOG_LEVEL_INFO = 'info';
    public const LOG_LEVEL_DEBUG = 'debug';
    
    // Custom Post Types
    public const CPT_EVENT = 'bil24_event';
    public const CPT_SESSION = 'bil24_session';
    public const CPT_ORDER = 'bil24_order';
    
    // Meta keys
    public const META_BIL24_ID = '_bil24_id';
    public const META_BIL24_DATA = '_bil24_data';
    public const META_SYNC_STATUS = '_bil24_sync_status';
    public const META_LAST_SYNC = '_bil24_last_sync';
    
    /**
     * Get plugin file path (with fallback)
     */
    public static function get_plugin_file(): string {
        return defined('BIL24_CONNECTOR_PLUGIN_FILE') ? BIL24_CONNECTOR_PLUGIN_FILE : self::PLUGIN_FILE;
    }
    
    /**
     * Get plugin directory path (with fallback)
     */
    public static function get_plugin_dir(): string {
        return defined('BIL24_CONNECTOR_PLUGIN_DIR') ? BIL24_CONNECTOR_PLUGIN_DIR : self::PLUGIN_DIR;
    }
    
    /**
     * Get plugin URL (with fallback)
     */
    public static function get_plugin_url(): string {
        return defined('BIL24_CONNECTOR_PLUGIN_URL') ? BIL24_CONNECTOR_PLUGIN_URL : self::PLUGIN_URL;
    }
    
    /**
     * Get plugin version (with fallback)
     */
    public static function get_version(): string {
        return defined('BIL24_CONNECTOR_VERSION') ? BIL24_CONNECTOR_VERSION : self::PLUGIN_VERSION;
    }
    
    /**
     * Prevent instantiation
     */
    private function __construct() {}
} 