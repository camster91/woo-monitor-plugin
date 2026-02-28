<?php
/**
 * Plugin Name: WooCommerce Error Monitor
 * Plugin URI: https://ashbi.ca
 * Description: Tracks WooCommerce checkout errors, JS crashes, and broken buttons, sending alerts to a central Node.js monitoring server.
 * Version: 1.2.2
 * Author: Ashbi
 * Author URI: https://ashbi.ca
 * License: GPL2
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: woo-monitor
 * Requires at least: 5.0
 * Requires PHP: 7.2
 * Requires Plugins: woocommerce
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'WOO_MONITOR_VERSION', '1.2.2' );
define( 'WOO_MONITOR_DEFAULT_WEBHOOK', 'https://woo.ashbi.ca/api/track-woo-error' );

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
    $webhook_url = get_option( 'woo_monitor_webhook_url', WOO_MONITOR_DEFAULT_WEBHOOK );
    
    if ( empty( $webhook_url ) ) {
        // No webhook URL configured, don't load tracking script
        return;
    }
    
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
    (function() {
        if (window._wooMonitorLoaded) return;
        window._wooMonitorLoaded = true;

        var webhookUrl = <?php echo wp_json_encode( $webhook_url ); ?>;
        var siteName = window.location.hostname;
        var errorCount = 0;
        var maxErrorsPerPage = 10;
        // Save native fetch reference so sendErrorAlert always bypasses any override
        var nativeFetch = window.fetch.bind(window);
        console.log('WooMonitor: Webhook URL configured');

        function sendErrorAlert(type, message) {
            if (!message || message.trim() === '') return;

            if (errorCount >= maxErrorsPerPage) {
                console.warn('WooMonitor: Rate limit reached (' + maxErrorsPerPage + ' errors). Suppressing further reports.');
                return;
            }
            errorCount++;

            if (webhookUrl.indexOf('example.com') !== -1 || webhookUrl.indexOf('your-server.com') !== -1) {
                console.warn('WooMonitor: Webhook URL not properly configured in plugin settings.');
                return;
            }

            var controller = new AbortController();
            var timeoutId = setTimeout(function() { controller.abort(); }, 5000);

            nativeFetch(webhookUrl, {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({
                    site: siteName,
                    url: window.location.href,
                    type: type,
                    error_message: message.substring(0, 1000),
                    time: new Date().toISOString(),
                    user_agent: navigator.userAgent
                }),
                signal: controller.signal
            })
            .then(function(response) {
                clearTimeout(timeoutId);
                if (response.ok) {
                    console.log('WooMonitor: Sent error alert -', type);
                } else {
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

        <?php if ( $track_js_errors ) : ?>
        // 1. Catch Global JavaScript Errors - registered early to catch pre-DOMReady crashes
        window.addEventListener('error', function(e) {
            if (e.filename && e.filename.indexOf(siteName) !== -1) {
                sendErrorAlert("JavaScript Crash", e.message + ' at ' + e.filename + ':' + e.lineno + ':' + e.colno);
            }
        });

        // 2. Catch unhandled promise rejections (async errors from WooCommerce Blocks, etc.)
        window.addEventListener('unhandledrejection', function(e) {
            var message = (e.reason && e.reason.message) ? e.reason.message : String(e.reason);
            sendErrorAlert("Unhandled Promise Rejection", message);
        });
        <?php endif; ?>

        document.addEventListener("DOMContentLoaded", function() {
            <?php if ( $track_ui_errors ) : ?>
            // 3. Catch WooCommerce UI Error Banners (e.g., "Invalid Card", "No shipping options")
            var errorSelector = '.woocommerce-error, .woocommerce-NoticeGroup-checkout, .wc-block-components-notice-banner.is-error';
            function checkErrorNode(node) {
                if (node.nodeType !== 1) return;
                if (node.matches && node.matches(errorSelector)) {
                    if (!node.dataset.reported) {
                        node.dataset.reported = "true";
                        sendErrorAlert("WooCommerce UI Error", node.innerText.trim());
                    }
                }
                if (node.querySelectorAll) {
                    var childErrors = node.querySelectorAll(errorSelector);
                    childErrors.forEach(function(child) {
                        if (!child.dataset.reported) {
                            child.dataset.reported = "true";
                            sendErrorAlert("WooCommerce UI Error", child.innerText.trim());
                        }
                    });
                }
            }
            var observer = new MutationObserver(function(mutations) {
                mutations.forEach(function(mutation) {
                    mutation.addedNodes.forEach(checkErrorNode);
                });
            });
            observer.observe(document.body, { childList: true, subtree: true });
            <?php endif; ?>

            <?php if ( $track_ajax_errors ) : ?>
            // 4. Catch AJAX "Add to Cart" or "Checkout" Failures (jQuery)
            <?php if ( wp_script_is( 'jquery', 'enqueued' ) ) : ?>
            if (typeof jQuery !== 'undefined') {
                jQuery(document).ajaxError(function(event, jqxhr, settings) {
                    if (settings.url && (settings.url.indexOf('wc-ajax=add_to_cart') !== -1 || settings.url.indexOf('wc-ajax=checkout') !== -1 || settings.url.indexOf('wc-ajax=update_order_review') !== -1)) {
                        sendErrorAlert("AJAX Checkout/Cart Failure", 'Failed URL: ' + settings.url + ' | Error: ' + jqxhr.statusText + ' | Status: ' + jqxhr.status);
                    }
                });
            }
            <?php endif; ?>

            // 5. Catch Fetch API failures (WooCommerce Blocks checkout)
            var originalFetch = window.fetch;
            window.fetch = function(url, options) {
                var urlStr = (typeof url === 'string') ? url : (url && url.url ? url.url : '');
                // Skip interception for our own webhook calls
                if (urlStr === webhookUrl) {
                    return originalFetch.apply(this, arguments);
                }
                return originalFetch.apply(this, arguments).then(function(response) {
                    if (!response.ok && urlStr.indexOf('/wc/store') !== -1) {
                        sendErrorAlert("Fetch Checkout/Cart Failure", 'Failed URL: ' + urlStr + ' | Status: ' + response.status);
                    }
                    return response;
                }).catch(function(error) {
                    if (urlStr.indexOf('/wc/store') !== -1) {
                        sendErrorAlert("Fetch Checkout/Cart Failure", 'Failed URL: ' + urlStr + ' | Error: ' + error.message);
                    }
                    throw error;
                });
            };
            <?php endif; ?>
        });
    })();
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
        'default' => WOO_MONITOR_DEFAULT_WEBHOOK
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
        
        <div class="card" style="margin: 20px 0; padding: 20px;">
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
            
            <p><strong><?php _e( 'Default monitoring server URL:', 'woo-monitor' ); ?></strong> <code><?php echo esc_html( WOO_MONITOR_DEFAULT_WEBHOOK ); ?></code></p>
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
                        $webhook_url = get_option( 'woo_monitor_webhook_url', WOO_MONITOR_DEFAULT_WEBHOOK );
                        ?>
                        <input type="url" name="woo_monitor_webhook_url" value="<?php echo esc_attr( $webhook_url ); ?>" class="regular-text" placeholder="<?php echo esc_attr( WOO_MONITOR_DEFAULT_WEBHOOK ); ?>">
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
                            </label>
                            <p class="description"><?php _e( 'Catches JavaScript crashes that might break buttons', 'woo-monitor' ); ?></p>
                            <br>

                            <label>
                                <input type="checkbox" name="woo_monitor_track_ajax_errors" value="yes" <?php checked( $track_ajax, 'yes' ); ?>>
                                <?php _e( 'Track AJAX and Fetch errors', 'woo-monitor' ); ?>
                            </label>
                            <p class="description"><?php _e( 'Catches failed add-to-cart and checkout requests (jQuery AJAX and Fetch API for WooCommerce Blocks)', 'woo-monitor' ); ?></p>
                            <br>

                            <label>
                                <input type="checkbox" name="woo_monitor_track_ui_errors" value="yes" <?php checked( $track_ui, 'yes' ); ?>>
                                <?php _e( 'Track UI error messages', 'woo-monitor' ); ?>
                            </label>
                            <p class="description"><?php _e( 'Catches WooCommerce error banners (e.g., "Invalid Card")', 'woo-monitor' ); ?></p>
                        </fieldset>
                    </td>
                </tr>
            </table>
            
            <?php submit_button( __( 'Save Settings', 'woo-monitor' ) ); ?>
        </form>
        
        <div class="card" style="margin-top: 20px; padding: 20px;">
            <h3><?php _e( 'Test Connection', 'woo-monitor' ); ?></h3>
            <p><?php _e( 'Send a test error alert to verify your monitoring server is reachable.', 'woo-monitor' ); ?></p>
            <button type="button" id="woo-monitor-test-btn" class="button button-secondary">
                <?php _e( 'Send Test Alert', 'woo-monitor' ); ?>
            </button>
            <span id="woo-monitor-test-result" style="margin-left: 10px;"></span>
            <script>
            document.getElementById('woo-monitor-test-btn').addEventListener('click', function() {
                var btn = this;
                var result = document.getElementById('woo-monitor-test-result');
                var url = <?php echo wp_json_encode( get_option( 'woo_monitor_webhook_url', '' ) ); ?>;
                if (!url) {
                    result.textContent = '<?php echo esc_js( __( 'No webhook URL configured.', 'woo-monitor' ) ); ?>';
                    result.style.color = '#d63638';
                    return;
                }
                btn.disabled = true;
                btn.textContent = '<?php echo esc_js( __( 'Sending...', 'woo-monitor' ) ); ?>';
                result.textContent = '';
                var controller = new AbortController();
                var timeoutId = setTimeout(function() { controller.abort(); }, 5000);
                fetch(url, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        site: window.location.hostname,
                        url: window.location.href,
                        type: 'Test Alert',
                        error_message: 'This is a test alert from WooCommerce Error Monitor settings page.',
                        time: new Date().toISOString(),
                        user_agent: navigator.userAgent
                    }),
                    signal: controller.signal
                }).then(function(response) {
                    clearTimeout(timeoutId);
                    if (response.ok) {
                        result.textContent = '<?php echo esc_js( __( 'Success! Server responded OK.', 'woo-monitor' ) ); ?>';
                        result.style.color = '#00a32a';
                    } else {
                        result.textContent = '<?php echo esc_js( __( 'Server returned error: ', 'woo-monitor' ) ); ?>' + response.status;
                        result.style.color = '#d63638';
                    }
                }).catch(function(error) {
                    clearTimeout(timeoutId);
                    if (error.name === 'AbortError') {
                        result.textContent = '<?php echo esc_js( __( 'Request timed out after 5 seconds.', 'woo-monitor' ) ); ?>';
                    } else {
                        result.textContent = '<?php echo esc_js( __( 'Connection failed: ', 'woo-monitor' ) ); ?>' + error.message;
                    }
                    result.style.color = '#d63638';
                }).finally(function() {
                    btn.disabled = false;
                    btn.textContent = '<?php echo esc_js( __( 'Send Test Alert', 'woo-monitor' ) ); ?>';
                });
            });
            </script>
        </div>

        <div class="card" style="margin-top: 20px; padding: 20px;">
            <h3><?php _e( 'Testing & Troubleshooting', 'woo-monitor' ); ?></h3>

            <h4><?php _e( 'Verify Frontend Tracking', 'woo-monitor' ); ?></h4>
            <ol>
                <li><?php _e( 'Go to a product, cart, or checkout page on your site', 'woo-monitor' ); ?></li>
                <li><?php _e( 'Open browser Developer Tools (F12) and go to the Console tab', 'woo-monitor' ); ?></li>
                <li><?php _e( 'You should see "WooMonitor: Webhook URL configured" message', 'woo-monitor' ); ?></li>
                <li><?php _e( 'Trigger an error (e.g., submit checkout with invalid data)', 'woo-monitor' ); ?></li>
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
        update_option( 'woo_monitor_webhook_url', WOO_MONITOR_DEFAULT_WEBHOOK );
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

