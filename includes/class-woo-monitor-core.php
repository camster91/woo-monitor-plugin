<?php
/**
 * WooCommerce Error Monitor - Core Functions
 *
 * @package WooMonitor
 */

defined( 'ABSPATH' ) || exit;

class Woo_Monitor_Core {
    
    /**
     * Initialize the plugin.
     */
    public static function init() {
        // Add initialization code here
    }
    
    /**
     * Check if monitoring is enabled.
     *
     * @return bool
     */
    public static function is_enabled() {
        return get_option( 'woo_monitor_enabled', 'yes' ) === 'yes';
    }
    
    /**
     * Get the webhook URL.
     *
     * @return string
     */
    public static function get_webhook_url() {
        return get_option( 'woo_monitor_webhook_url', 'https://woo.ashbi.ca/api/track-woo-error' );
    }
}