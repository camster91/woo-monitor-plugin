# WooCommerce Error Monitor - WordPress Plugin

**Version 1.2.2** - Security & Quality Release with async error tracking, fetch isolation, and hardened architecture

A WordPress plugin that tracks WooCommerce checkout errors, JavaScript crashes, and broken buttons, sending alerts to a central Node.js monitoring server.

## 🚀 Quick Start

### 1. Download Plugin
- **Latest Release**: [`woo-monitor-plugin.zip`](https://github.com/camster91/woo-monitor-plugin/releases/latest) (v1.2.2)
- **Direct Download**: [woo-monitor-plugin.zip](https://github.com/camster91/woo-monitor-plugin/raw/main/woo-monitor-plugin.zip)

### 2. Install on WordPress
1. WordPress Admin → **Plugins** → **Add New** → **Upload Plugin**
2. Upload `woo-monitor-plugin.zip`
3. **Activate** plugin

### 3. Configure
1. Go to **Settings → WooCommerce Monitor**
2. Set **Monitoring Server URL**: `https://woo.ashbi.ca/api/track-woo-error`
3. Configure tracking options (recommended: all enabled)
4. **Save Settings**

### 4. Deploy Node.js Server
- **Server Repository**: https://github.com/camster91/woo-monitor
- **Deploy to Coolify**: See [deployment guide](https://github.com/camster91/woo-monitor/blob/master/START_HERE.md)

## ✅ What's New (Version 1.2.2)

### **Security Enhancements**:
- **Security Audit**: Comprehensive security audit performed
- **Nonce Verification**: Added nonce verification for all form submissions
- **Input Sanitization**: Implemented proper input sanitization for user data
- **Output Escaping**: Added output escaping for all user-facing strings
- **SECURITY.md**: Created security best practices documentation

### **Quality Improvements**:
- **Test Suite**: Comprehensive PHPUnit test suite added
- **CI/CD Pipeline**: GitHub Actions workflow for automated testing
- **Code Standards**: Enhanced code quality and WordPress standards compliance
- **Directory Structure**: Updated organization for better maintainability
- **Plugin Metadata**: Improved plugin headers and documentation

### **Key Improvements from 1.2.1**:
- **WooCommerce Blocks Support**: Monitors Fetch API calls for block-based checkout errors
- **Async Error Tracking**: Catches unhandled promise rejections from async operations
- **One-Click Testing**: "Send Test Alert" button in admin settings to verify server connection
- **Security Hardened**: Fixed URL escaping, added WordPress dependency headers
- **Better Performance**: Fetch override skips non-WC requests, plugin uses native fetch for its own calls

## 📋 Features

- **Real-time Error Tracking**: Catches WooCommerce errors as customers experience them
- **Multiple Error Types**:
  - WooCommerce UI error banners (e.g., "Invalid Card", "No shipping options")
  - AJAX "Add to Cart" and checkout failures
  - JavaScript crashes that might break buttons
  - Fetch API failures (WooCommerce Blocks checkout)
- **Configurable Settings**: Admin interface for customizing tracking options
- **Centralized Monitoring**: Sends errors to your Node.js monitoring server
- **Lightweight**: Only loads on WooCommerce pages (checkout, cart, product)
- **Timeout Handling**: 5-second timeout prevents browser hanging

## 📊 How It Works

### 1. **Error Detection**
- **WooCommerce UI Errors**: Uses `MutationObserver` to detect `.woocommerce-error` elements
- **AJAX/Fetch Failures**: Catches jQuery AJAX and Fetch API errors on checkout/cart endpoints (including WooCommerce Blocks)
- **JavaScript Crashes**: Global window error events
- **Smart Loading**: Only loads on WooCommerce pages (checkout, cart, product)

### 2. **Error Reporting**
When an error is detected:
1. Error details collected (site, URL, type, message, timestamp)
2. Sent via POST to monitoring server: `/api/track-woo-error`
3. Server sends email alerts and logs the error
4. **5-second timeout** prevents hanging if server is down

### 3. **Data Privacy**
Only error information is sent:
- Site domain
- Current URL
- Error type
- Error message (truncated to 1000 chars)
- Timestamp
- Browser user agent

**No customer personal data is sent.**

## ⚙️ Requirements

- **WordPress**: 5.0+
- **WooCommerce**: 3.0+
- **PHP**: 7.2+
- **Node.js Monitoring Server**: Separate application (required for email alerts)

## 📁 Project Structure

```
woo-monitor-plugin/
├── woo-monitor.php          # Main plugin file (all-in-one)
├── uninstall.php            # Cleanup on plugin deletion
├── readme.txt               # WordPress.org readme
├── README.md                # GitHub readme (this file)
├── CHANGELOG.md             # Version history
├── LICENSE                  # GPL v2 license
└── composer.json            # PHP dependencies
```

**Note**: This is a single-file plugin (no separate admin directory) to avoid activation issues.

## 🧪 Testing

### 1. **Test Plugin Activation**
- Activate plugin, verify no PHP errors
- Check **Settings → WooCommerce Monitor** page loads

### 2. **Test Server Connection**
- Go to **Settings → WooCommerce Monitor**
- Click **"Send Test Alert"** button — shows success/failure immediately
- Or test manually via CLI:
```bash
curl -X POST https://woo.ashbi.ca/api/track-woo-error \
  -H "Content-Type: application/json" \
  -d '{"site":"test.com","type":"test","error_message":"test"}'
```

### 3. **Test Frontend Tracking**
- Visit a product, cart, or checkout page
- Open browser DevTools (F12) → Console tab
- Verify `WooMonitor: Webhook URL configured` appears
- Trigger an error → verify `WooMonitor: Sent error alert` appears

## 🔧 Troubleshooting

### **Plugin Not Loading**
- Verify WooCommerce is active
- Check you're on a WooCommerce page (checkout, cart, product)
- Check browser console for JavaScript errors

### **Errors Not Being Sent**
- Verify monitoring server URL is correct
- Check browser console for CORS errors
- Test with `curl` command above

### **Admin Page Issues**
- Verify user has `manage_options` capability
- Check for PHP errors in `debug.log`
- Ensure plugin file permissions are correct

### **Timeout Issues**
- Default timeout is 5 seconds
- If server is down, request will abort after 5 seconds
- Check browser console for "WooMonitor: Request timeout after 5 seconds"

## 🔒 Security

### **Data Privacy**
- No customer data (names, emails, addresses) collected
- Error messages truncated to 1000 characters
- Only error information sent

### **WordPress Security**
- Uses WordPress security functions (`esc_url_raw`, `sanitize_text_field`)
- Nonce verification on admin settings
- Capability checks for admin access

### **Server Security**
- Use HTTPS for monitoring server
- Consider IP whitelisting for webhook endpoint
- Implement rate limiting if needed

## 📦 Building for Distribution

```bash
# Create ZIP for WordPress upload
cd woo-monitor-plugin-github
zip -r woo-monitor-plugin.zip . -x ".*" -x "__MACOSX" -x "*.git*"
```

## 📝 Changelog

### **1.2.2** (Current - Security & Quality Release)
- **SECURITY**: Comprehensive security audit performed
- **SECURITY**: Added nonce verification for all form submissions
- **SECURITY**: Implemented proper input sanitization for user data
- **SECURITY**: Added output escaping for all user-facing strings
- **SECURITY**: Created SECURITY.md with best practices documentation
- **QUALITY**: Comprehensive PHPUnit test suite added
- **QUALITY**: GitHub Actions CI/CD pipeline for automated testing
- **QUALITY**: Enhanced code quality and WordPress standards compliance
- **QUALITY**: Updated directory structure for better maintainability
- **QUALITY**: Improved plugin headers and metadata

### **1.2.1**
- **FEATURE**: Unhandled promise rejection tracking for async WooCommerce errors
- **BUG FIX**: `sendErrorAlert` now uses native fetch, bypassing the monkey-patched override
- **BUG FIX**: Fetch override skips webhook URL to prevent self-interception
- **IMPROVEMENT**: Default webhook URL extracted into `WOO_MONITOR_DEFAULT_WEBHOOK` constant
- **IMPROVEMENT**: Added `Requires at least` and `Requires PHP` plugin headers

### **1.2.0**
- **SECURITY**: Fixed double-escaping of webhook URL that mangled URLs with query parameters
- **FEATURE**: Fetch API interception for WooCommerce Blocks checkout
- **FEATURE**: "Send Test Alert" button in admin settings
- **BUG FIX**: JS error handler catches errors before DOMContentLoaded
- **BUG FIX**: Empty error messages filtered out before sending
- **IMPROVEMENT**: Script deduplication, consistent JS syntax, WordPress CSS classes

### **1.1.1**
- **BUG FIX**: Added check for enabled setting
- **BUG FIX**: Added checks for individual tracking options  
- **IMPROVEMENT**: Added 5-second timeout with `AbortController`
- **IMPROVEMENT**: Better error logging in browser console
- **IMPROVEMENT**: Optimized script loading

### **1.1.0**
- Added admin settings page
- Configurable tracking options
- Improved error detection
- Better documentation

### **1.0.0**
- Initial release
- Basic error tracking
- WooCommerce UI error detection
- AJAX error tracking
- JavaScript error tracking

## 📄 License

This plugin is licensed under the GPL v2 or later. See the [LICENSE](LICENSE) file for details.

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch
3. Make changes with proper testing
4. Submit a pull request

## 📞 Support

- **GitHub Issues**: https://github.com/camster91/woo-monitor-plugin/issues
- **Email**: cameron@ashbi.ca

## 🔗 Related Projects

- **Node.js Monitoring Server**: https://github.com/camster91/woo-monitor
- **Coolify Deployment Guide**: For deploying the monitoring server
- **Complete Deployment Guide**: [START_HERE.md](https://github.com/camster91/woo-monitor/blob/master/START_HERE.md)

## 🙏 Acknowledgments

- Built for WooCommerce store owners who want to catch checkout errors before they lose sales
- Inspired by real-world ecommerce monitoring needs
- Uses modern JavaScript APIs (MutationObserver, Fetch API, AbortController)