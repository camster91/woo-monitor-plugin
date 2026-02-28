<?php
/**
 * Uninstall handler for WooCommerce Error Monitor.
 * Removes all plugin options from the database when the plugin is deleted.
 */

// Exit if not called by WordPress
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

delete_option( 'woo_monitor_webhook_url' );
delete_option( 'woo_monitor_enabled' );
delete_option( 'woo_monitor_track_js_errors' );
delete_option( 'woo_monitor_track_ajax_errors' );
delete_option( 'woo_monitor_track_ui_errors' );
