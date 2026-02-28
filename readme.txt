=== WooCommerce Error Monitor ===
Contributors: cameronashbi
Tags: woocommerce, monitoring, error tracking, checkout, alerts
Requires at least: 5.0
Tested up to: 6.7
Requires PHP: 7.2
Stable tag: 1.2.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Track WooCommerce checkout errors, JavaScript crashes, and broken buttons, sending alerts to a central Node.js monitoring server.

== Description ==

**WooCommerce Error Monitor** is a powerful plugin that helps you catch and fix checkout issues before they cost you sales. It monitors your WooCommerce store for:

* **Checkout Errors**: Invalid card messages, shipping errors, and other WooCommerce UI error banners
* **JavaScript Crashes**: Broken scripts that may break checkout functionality
* **AJAX Failures**: Failed add-to-cart and checkout requests (jQuery AJAX)
* **Fetch API Failures**: Failed WooCommerce Blocks checkout requests (Fetch API)

All errors are sent to a central Node.js monitoring server (separate application) which can send email alerts, helping you fix issues quickly.

= Key Features =

* **Real-time Error Tracking**: Catches errors as customers experience them
* **Centralized Monitoring**: Send errors to your own monitoring server
* **Configurable Tracking**: Choose which types of errors to monitor
* **WooCommerce Integration**: Only loads on relevant pages (checkout, cart, product)
* **Admin Settings**: Easy configuration from WordPress admin
* **Lightweight**: Minimal performance impact
* **Timeout Handling**: Prevents hanging requests if server is down

= How It Works =

1. Install this plugin on your WooCommerce store
2. Deploy the Node.js monitoring server (available separately)
3. Configure the plugin with your monitoring server URL
4. The plugin tracks errors and sends them to your server
5. Your monitoring server can send email alerts when issues are detected

= Requirements =

* WooCommerce 3.0 or higher
* Node.js monitoring server (optional but recommended)
* PHP 7.2 or higher

== Installation ==

1. Upload the `woo-monitor` folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Configure the settings under Settings → WooCommerce Monitor
4. Set up your Node.js monitoring server (see below)

== Node.js Monitoring Server ==

This plugin works with a separate Node.js monitoring server that receives error reports and can send email alerts. The server code is available separately.

**Server Features:**
* Receives error reports from multiple WooCommerce stores
* Sends email alerts via SMTP
* Performs deep health checks on WooCommerce stores
* Monitors Stripe gateway status, failed orders, subscription renewals, and more

**Setup Instructions:**
1. Download the Node.js server code
2. Configure SMTP email settings
3. Add your WooCommerce store API credentials
4. Deploy to a server (Heroku, DigitalOcean, VPS, etc.)
5. Update the plugin settings with your server URL

== Frequently Asked Questions ==

= Do I need the Node.js server? =

Yes, for full functionality. The plugin sends errors to the Node.js server, which handles alerting and monitoring. Without it, errors will be tracked but no alerts will be sent.

= Can I use a different monitoring service? =

Yes, you can configure the plugin to send errors to any endpoint that accepts JSON POST requests.

= Does this affect site performance? =

No, the tracking script is lightweight and only loads on WooCommerce pages (checkout, cart, product).

= What data is sent to the monitoring server? =

Only error information: site domain, URL, error type, error message, timestamp, and browser user agent. No customer personal data is sent.

= Is this compatible with my theme/plugins? =

The plugin should work with any WordPress theme and most plugins. It uses standard WooCommerce classes and JavaScript events.

== Changelog ==

= 1.2.1 =
* **FEATURE**: Unhandled promise rejection tracking for async WooCommerce errors
* **BUG FIX**: sendErrorAlert now uses native fetch, bypassing monkey-patched override
* **BUG FIX**: Fetch override skips webhook URL to prevent self-interception
* **IMPROVEMENT**: Default webhook URL extracted into constant
* **IMPROVEMENT**: Added Requires at least and Requires PHP plugin headers

= 1.2.0 =
* **SECURITY**: Fixed double-escaping of webhook URL that mangled URLs with query parameters
* **FEATURE**: Added Fetch API interception for WooCommerce Blocks checkout support
* **FEATURE**: Added "Send Test Alert" button in admin settings
* **BUG FIX**: JavaScript error handler now catches errors before DOMContentLoaded
* **BUG FIX**: Empty error messages are now filtered out
* **IMPROVEMENT**: Added script deduplication guard
* **IMPROVEMENT**: Added Requires Plugins header for WooCommerce dependency
* **IMPROVEMENT**: Fixed checkbox description UX in admin settings
* **IMPROVEMENT**: Added version constant for programmatic use

= 1.1.1 =
* **BUG FIX**: Added check for enabled setting
* **BUG FIX**: Added checks for individual tracking options
* **IMPROVEMENT**: Added timeout handling for fetch requests
* **IMPROVEMENT**: Better error logging in browser console

= 1.1.0 =
* Added admin settings page
* Configurable tracking options
* Improved error detection
* Better documentation

= 1.0.0 =
* Initial release
* Basic error tracking
* WooCommerce UI error detection
* AJAX error tracking
* JavaScript error tracking

== Upgrade Notice ==

= 1.2.1 =
Adds async error tracking, fixes fetch override self-interception, adds WordPress compatibility headers.

= 1.2.0 =
Security fix, WooCommerce Blocks support, one-click test button. Settings preserved.

= 1.1.1 =
Bug fix release. Settings from previous versions are preserved.

== Screenshots ==

1. Admin settings page
2. Error tracking in browser console
3. Email alert example

== License ==

This plugin is licensed under the GPL v2 or later.