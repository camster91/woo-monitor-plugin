<?php
/**
 * Plugin Name: WooCommerce Error Monitor
 * Plugin URI: https://ashbi.ca
 * Description: Tracks WooCommerce checkout errors, JS crashes, and broken buttons, sending alerts to a central Node.js monitoring server.
 * Version: 1.1.1
 * Author: Ashbi
 * Author URI: https://ashbi.ca
 * License: GPL2
 * Text Domain: woo-monitor
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Initialize plugin
add_action( 'plugins_loaded', 'woo_monitor_init' );

function woo_monitor_init() {
    // Only run on the frontend for non-admin users
    if ( ! is_admin() ) {
        add_action( 'wp_footer', 'woo_monitor_frontend_tracker' );
    }
    
    // Load text domain for translations
    load_plugin_textdomain( 'woo-monitor', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
    
    // Add admin menu if in admin area
    if ( is_admin() ) {
        add_action( 'admin_menu', 'woo_monitor_add_admin_menu' );
        add_action( 'admin_init', 'woo_monitor_settings_init' );
        add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'woo_monitor_add_action_links' );
    }
}

/**
 * Frontend error tracking script
 */
function woo_monitor_frontend_tracker() {
    // Check if WooCommerce is active to avoid fatal errors
    if ( ! class_exists( 'WooCommerce' ) ) {
        return;
    }

    // Check if monitoring is enabled
    if ( get_option( 'woo_monitor_enabled', 'yes' ) !== 'yes' ) {
        return;
    }

    // Only load the script on pages where transactions or add-to-cart happen
    if ( ! is_checkout() && ! is_cart() && ! is_product() ) {
        return;
    }
    
    // Get webhook URL from options
    $webhook_url = get_option( 'woo_monitor_webhook_url', 'https://woo.ashbi.ca/api/track-woo-error' );
    
    if ( empty( $webhook_url ) ) {
        // No webhook URL configured, don't load tracking script
        return;
    }
    
    $webhook_url = esc_url( $webhook_url );
    
    // Get tracking options
    $track_js_errors = get_option( 'woo_monitor_track_js_errors', 'yes' ) === 'yes';
    $track_ajax_errors = get_option( 'woo_monitor_track_ajax_errors', 'yes' ) === 'yes';
    $track_ui_errors = get_option( 'woo_monitor_track_ui_errors', 'yes' ) === 'yes';
    
    // If nothing to track, don't load script
    if ( ! $track_js_errors && ! $track_ajax_errors && ! $track_ui_errors ) {
        return;
    }
    ?>
    <script>
    document.addEventListener("DOMContentLoaded", function() {
        const webhookUrl = "<?php echo $webhook_url; ?>";
        const siteName = window.location.hostname;
        
        <?php if ( $track_ui_errors ) : ?>
        // 1. Catch WooCommerce UI Error Banners (e.g., "Invalid Card", "No shipping options")
        const observer = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                const errorNodes = document.querySelectorAll('.woocommerce-error, .woocommerce-NoticeGroup-checkout, .wc-block-components-notice-banner.is-error');
                errorNodes.forEach(node => {
                    // Prevent duplicate sending
                    if (!node.dataset.reported) {
                        node.dataset.reported = "true";
                        sendErrorAlert("WooCommerce UI Error", node.innerText.trim());
                    }
                });
            });
        });
        observer.observe(document.body, { childList: true, subtree: true });
        <?php endif; ?>

        <?php if ( $track_ajax_errors && function_exists( 'wp_script_is' ) && wp_script_is( 'jquery', 'enqueued' ) ) : ?>
        // 2. Catch AJAX "Add to Cart" or "Checkout" Failures
        if (typeof jQuery !== 'undefined') {
            jQuery(document).ajaxError(function(event, jqxhr, settings, thrownError) {
                if (settings.url && (settings.url.includes('wc-ajax=add_to_cart') || settings.url.includes('wc-ajax=checkout') || settings.url.includes('wc-ajax=update_order_review'))) {
                    sendErrorAlert("AJAX Checkout/Cart Failure", `Failed URL: ${settings.url} | Error: ${jqxhr.statusText} | Status: ${jqxhr.status}`);
                }
            });
        }
        <?php endif; ?>

        <?php if ( $track_js_errors ) : ?>
        // 3. Catch Global JavaScript Errors (This catches broken/unclickable buttons from cached JS/Themes)
        window.addEventListener('error', function(e) {
            // Ignore benign third-party errors, focus on main thread that might break checkout
            if (e.filename && e.filename.includes(siteName)) {
                sendErrorAlert("JavaScript Crash (Might break buttons)", `${e.message} at ${e.filename}:${e.lineno}:${e.colno}`);
            }
        });
        <?php endif; ?>

        function sendErrorAlert(type, message) {
            // Don't send if the webhook URL is a placeholder
            if (webhookUrl.includes('example.com') || webhookUrl.includes('your-server.com')) {
                console.warn('WooMonitor: Webhook URL not properly configured in plugin settings.');
                return;
            }

            // Add timeout to prevent hanging requests
            const controller = new AbortController();
            const timeoutId = setTimeout(() => controller.abort(), 5000);

            fetch(webhookUrl, {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({
                    site: siteName,
                    url: window.location.href,
                    type: type,
                    error_message: message.substring(0, 1000), // Limit length
                    time: new Date().toISOString(),
                    user_agent: navigator.userAgent
                }),
                signal: controller.signal
            })
            .then(response => {
                clearTimeout(timeoutId);
                if (!response.ok) {
                    console.error('WooMonitor: Server returned error:', response.status);
                }
            })
            .catch(function(error) {
                clearTimeout(timeoutId);
                if (error.name === 'AbortError') {
                    console.error('WooMonitor: Request timeout after 5 seconds');
                } else {
                    console.error('WooMonitor: Failed to send error alert:', error);
                }
            });
        }
    });
    </script>
    <?php
}

/**
 * Add admin menu
 */
function woo_monitor_add_admin_menu() {
    add_options_page(
        __( 'WooCommerce Monitor Settings', 'woo-monitor' ),
        __( 'WooCommerce Monitor', 'woo-monitor' ),
        'manage_options',
        'woo-monitor-settings',
        'woo_monitor_settings_page'
    );
}

/**
 * Register settings
 */
function woo_monitor_settings_init() {
    register_setting( 'woo_monitor_settings_group', 'woo_monitor_webhook_url', array(
        'sanitize_callback' => 'esc_url_raw',
        'default' => 'https://woo.ashbi.ca/api/track-woo-error'
    ) );
    
    register_setting( 'woo_monitor_settings_group', 'woo_monitor_enabled', array(
        'sanitize_callback' => 'sanitize_text_field',
        'default' => 'yes'
    ) );
    
    register_setting( 'woo_monitor_settings_group', 'woo_monitor_track_js_errors', array(
        'sanitize_callback' => 'sanitize_text_field',
        'default' => 'yes'
    ) );
    
    register_setting( 'woo_monitor_settings_group', 'woo_monitor_track_ajax_errors', array(
        'sanitize_callback' => 'sanitize_text_field',
        'default' => 'yes'
    ) );
    
    register_setting( 'woo_monitor_settings_group', 'woo_monitor_track_ui_errors', array(
        'sanitize_callback' => 'sanitize_text_field',
        'default' => 'yes'
    ) );
}

/**
 * Settings page
 */
function woo_monitor_settings_page() {
    // Check user capabilities
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }
    
    // Show success message if settings were saved
    if ( isset( $_GET['settings-updated'] ) ) {
        add_settings_error( 'woo_monitor_messages', 'woo_monitor_message', __( 'Settings Saved', 'woo-monitor' ), 'updated' );
    }
    
    // Show error/info messages
    settings_errors( 'woo_monitor_messages' );
    ?>
    <div class="wrap">
        <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
        
        <div style="background: #fff; border: 1px solid #ccd0d4; padding: 20px; margin: 20px 0; border-radius: 4px;">
            <h2><?php _e( 'About WooCommerce Error Monitor', 'woo-monitor' ); ?></h2>
            <p><?php _e( 'This plugin tracks checkout errors, JavaScript crashes, and broken buttons on your WooCommerce store, sending alerts to a central monitoring server.', 'woo-monitor' ); ?></p>
            
            <h3><?php _e( 'How It Works', 'woo-monitor' ); ?></h3>
            <ol>
                <li><?php _e( 'The plugin monitors for WooCommerce error messages on checkout, cart, and product pages.', 'woo-monitor' ); ?></li>
                <li><?php _e( 'It catches JavaScript errors that might break checkout functionality.', 'woo-monitor' ); ?></li>
                <li><?php _e( 'It detects AJAX failures when adding to cart or during checkout.', 'woo-monitor' ); ?></li>
                <li><?php _e( 'Errors are sent to your monitoring server (Node.js application) which can send email alerts.', 'woo-monitor' ); ?></li>
            </ol>
            
            <h3><?php _e( 'Setup Instructions', 'woo-monitor' ); ?></h3>
            <ol>
                <li><?php _e( 'Deploy the Node.js monitoring server (available in the woo-monitor directory).', 'woo-monitor' ); ?></li>
                <li><?php _e( 'Configure the monitoring server URL below.', 'woo-monitor' ); ?></li>
                <li><?php _e( 'Test by creating an error on your checkout page.', 'woo-monitor' ); ?></li>
            </ol>
            
            <p><strong><?php _e( 'Default monitoring server URL:', 'woo-monitor' ); ?></strong> <code>https://woo.ashbi.ca/api/track-woo-error</code></p>
        </div>
        
        <form action="options.php" method="post">
            <?php
            settings_fields( 'woo_monitor_settings_group' );
            ?>
            
            <table class="form-table">
                <tr>
                    <th scope="row"><?php _e( 'Monitoring Server URL', 'woo-monitor' ); ?></th>
                    <td>
                        <?php
                        $webhook_url = get_option( 'woo_monitor_webhook_url', 'https://woo.ashbi.ca/api/track-woo-error' );
                        ?>
                        <input type="url" name="woo_monitor_webhook_url" value="<?php echo esc_attr( $webhook_url ); ?>" class="regular-text" placeholder="https://woo.ashbi.ca/api/track-woo-error">
                        <p class="description"><?php _e( 'URL of your Node.js monitoring server. This is where error reports will be sent.', 'woo-monitor' ); ?></p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row"><?php _e( 'Enable Monitoring', 'woo-monitor' ); ?></th>
                    <td>
                        <?php
                        $enabled = get_option( 'woo_monitor_enabled', 'yes' );
                        ?>
                        <label>
                            <input type="checkbox" name="woo_monitor_enabled" value="yes" <?php checked( $enabled, 'yes' ); ?>>
                            <?php _e( 'Enable error monitoring on this site', 'woo-monitor' ); ?>
                        </label>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row"><?php _e( 'Tracking Options', 'woo-monitor' ); ?></th>
                    <td>
                        <?php
                        $track_js = get_option( 'woo_monitor_track_js_errors', 'yes' );
                        $track_ajax = get_option( 'woo_monitor_track_ajax_errors', 'yes' );
                        $track_ui = get_option( 'woo_monitor_track_ui_errors', 'yes' );
                        ?>
                        <fieldset>
                            <label>
                                <input type="checkbox" name="woo_monitor_track_js_errors" value="yes" <?php checked( $track_js, 'yes' ); ?>>
                                <?php _e( 'Track JavaScript errors', 'woo-monitor' ); ?>
                                <p class="description"><?php _e( 'Catches JavaScript crashes that might break buttons', 'woo-monitor' ); ?></p>
                            </label><br>
                            
                            <label>
                                <input type="checkbox" name="woo_monitor_track_ajax_errors" value="yes" <?php checked( $track_ajax, 'yes' ); ?>>
                                <?php _e( 'Track AJAX errors', 'woo-monitor' ); ?>
                                <p class="description"><?php _e( 'Catches failed add-to-cart and checkout requests', 'woo-monitor' ); ?></p>
                            </label><br>
                            
                            <label>
                                <input type="checkbox" name="woo_monitor_track_ui_errors" value="yes" <?php checked( $track_ui, 'yes' ); ?>>
                                <?php _e( 'Track UI error messages', 'woo-monitor' ); ?>
                                <p class="description"><?php _e( 'Catches WooCommerce error banners (e.g., "Invalid Card")', 'woo-monitor' ); ?></p>
                            </label>
                        </fieldset>
                    </td>
                </tr>
            </table>
            
            <?php submit_button( __( 'Save Settings', 'woo-monitor' ) ); ?>
        </form>
        
        <div style="margin-top: 30px; padding: 20px; background: #f0f6fc; border: 1px solid #c3c4c7;">
            <h3><?php _e( 'Testing & Troubleshooting', 'woo-monitor' ); ?></h3>
            
            <h4><?php _e( 'Test the Connection', 'woo-monitor' ); ?></h4>
            <p><?php _e( 'To test if the plugin is working:', 'woo-monitor' ); ?></p>
            <ol>
                <li><?php _e( 'Go to a product page on your site', 'woo-monitor' ); ?></li>
                <li><?php _e( 'Open browser Developer Tools (F12)', 'woo-monitor' ); ?></li>
                <li><?php _e( 'Go to the Console tab', 'woo-monitor' ); ?></li>
                <li><?php _e( 'You should see "WooMonitor: Webhook URL configured" message', 'woo-monitor' ); ?></li>
                <li><?php _e( 'Try to trigger an error (e.g., add to cart without selecting required options)', 'woo-monitor' ); ?></li>
                <li><?php _e( 'Check console for "WooMonitor: Sent error alert" messages', 'woo-monitor' ); ?></li>
            </ol>
            
            <h4><?php _e( 'Common Issues', 'woo-monitor' ); ?></h4>
            <ul>
                <li><strong><?php _e( 'No errors being sent:', 'woo-monitor' ); ?></strong> <?php _e( 'Check that WooCommerce is active and the webhook URL is correct.', 'woo-monitor' ); ?></li>
                <li><strong><?php _e( 'CORS errors in console:', 'woo-monitor' ); ?></strong> <?php _e( 'Ensure your monitoring server has CORS enabled and accepts requests from your domain.', 'woo-monitor' ); ?></li>
                <li><strong><?php _e( 'Plugin not loading:', 'woo-monitor' ); ?></strong> <?php _e( 'Check that you\'re on a WooCommerce page (checkout, cart, or product).', 'woo-monitor' ); ?></li>
                <li><strong><?php _e( 'Request timeout errors:', 'woo-monitor' ); ?></strong> <?php _e( 'Check that your monitoring server is running and accessible.', 'woo-monitor' ); ?></li>
            </ul>
        </div>
    </div>
    <?php
}

/**
 * Add plugin action links
 */
function woo_monitor_add_action_links( $links ) {
    $settings_link = '<a href="' . admin_url( 'options-general.php?page=woo-monitor-settings' ) . '">' . __( 'Settings', 'woo-monitor' ) . '</a>';
    array_unshift( $links, $settings_link );
    return $links;
}

/**
 * Activation hook
 */
register_activation_hook( __FILE__, 'woo_monitor_activate' );
function woo_monitor_activate() {
    // Set default options if not exists
    if ( ! get_option( 'woo_monitor_webhook_url' ) ) {
        update_option( 'woo_monitor_webhook_url', 'https://woo.ashbi.ca/api/track-woo-error' );
    }
    
    if ( ! get_option( 'woo_monitor_enabled' ) ) {
        update_option( 'woo_monitor_enabled', 'yes' );
    }
    
    if ( ! get_option( 'woo_monitor_track_js_errors' ) ) {
        update_option( 'woo_monitor_track_js_errors', 'yes' );
    }
    
    if ( ! get_option( 'woo_monitor_track_ajax_errors' ) ) {
        update_option( 'woo_monitor_track_ajax_errors', 'yes' );
    }
    
    if ( ! get_option( 'woo_monitor_track_ui_errors' ) ) {
        update_option( 'woo_monitor_track_ui_errors', 'yes' );
    }
}

/**
 * Deactivation hook
 */
register_deactivation_hook( __FILE__, 'woo_monitor_deactivate' );
function woo_monitor_deactivate() {
    // Clean up if needed
}