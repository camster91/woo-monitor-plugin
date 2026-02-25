# WooCommerce Error Monitor - WordPress Plugin

**Version 1.1.1** - Patched with all critical bug fixes

A WordPress plugin that tracks WooCommerce checkout errors, JavaScript crashes, and broken buttons, sending alerts to a central Node.js monitoring server.

## 🚀 Quick Start

### 1. Download Plugin
- **Latest Release**: [`woo-monitor-plugin.zip`](https://github.com/camster91/woo-monitor-plugin/releases/latest) (v1.1.1)
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

## ✅ Bug Fixes (Version 1.1.1)

This version includes critical bug fixes:

### **Fixed Issues**:
1. **Missing "Enabled" Check**: Plugin now properly checks the `woo_monitor_enabled` setting
2. **Unused Tracking Options**: JS/AJAX/UI tracking options now work correctly
3. **No Request Timeout**: Added 5-second timeout with `AbortController` to prevent browser hanging
4. **Improved Error Handling**: Better logging and timeout handling

### **New Features**:
- **Timeout Handling**: 5-second timeout for failed requests
- **Optimized Loading**: Script only loads when tracking options are enabled
- **Better jQuery Check**: Verifies jQuery is enqueued before adding AJAX handler
- **Enhanced Settings UI**: Descriptions for each tracking option

## 📋 Features

- **Real-time Error Tracking**: Catches WooCommerce errors as customers experience them
- **Multiple Error Types**:
  - WooCommerce UI error banners (e.g., "Invalid Card", "No shipping options")
  - AJAX "Add to Cart" and checkout failures
  - JavaScript crashes that might break buttons
  - Potentially stuck/unresponsive buttons
- **Configurable Settings**: Admin interface for customizing tracking options
- **Centralized Monitoring**: Sends errors to your Node.js monitoring server
- **Lightweight**: Only loads on WooCommerce pages (checkout, cart, product)
- **Timeout Handling**: 5-second timeout prevents browser hanging

## 📊 How It Works

### 1. **Error Detection**
- **WooCommerce UI Errors**: Uses `MutationObserver` to detect `.woocommerce-error` elements
- **AJAX Failures**: Catches jQuery AJAX errors on checkout/cart endpoints
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
├── readme.txt              # WordPress.org readme
├── README.md               # GitHub readme (this file)
├── LICENSE                 # GPL v2 license
└── composer.json          # PHP dependencies
```

**Note**: This is a single-file plugin (no separate admin directory) to avoid activation issues.

## 🧪 Testing

### 1. **Test Plugin Activation**
- Activate plugin, verify no PHP errors
- Check **Settings → WooCommerce Monitor** page loads

### 2. **Test Error Tracking**
```bash
# Test server connection
curl -X POST https://woo.ashbi.ca/api/track-woo-error \
  -H "Content-Type: application/json" \
  -d '{"site":"test.com","type":"test","error_message":"test"}'
```

### 3. **Test Bug Fixes**
- **Enable/Disable monitoring** in settings
- **Toggle individual tracking options** (JS/AJAX/UI)
- **Test timeout**: Set invalid webhook URL, verify 5-second timeout
- **Verify** errors are only tracked when enabled

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

### **1.1.1** (Current - Patched)
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