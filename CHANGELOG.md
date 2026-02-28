## [1.2.2] - 2026-02-28

### Security
- Comprehensive security audit performed
- Added nonce verification for all form submissions
- Implemented proper input sanitization for user data
- Added output escaping for all user-facing strings
- Created SECURITY.md with best practices documentation

### Added
- Comprehensive PHPUnit test suite
- GitHub Actions CI/CD pipeline for automated testing

### Changed
- Enhanced code quality and WordPress standards compliance
- Updated directory structure for better maintainability
- Improved plugin headers and metadata

### Fixed
- Security vulnerabilities identified during audit
- Missing ABSPATH checks in included files
- Code quality issues reported by static analysis

## [1.2.1] - 2026-02-28

### Fixed
- **Bug**: `sendErrorAlert` now uses `nativeFetch` (saved at IIFE scope) instead of `window.fetch`, preventing its calls from going through the monkey-patched fetch override
- **Bug**: Fetch override now explicitly skips requests matching the webhook URL to prevent any self-interception

### Added
- **Feature**: Unhandled promise rejection tracking (`unhandledrejection` event) for catching async errors from WooCommerce Blocks and modern async/await code
- **Feature**: `WOO_MONITOR_DEFAULT_WEBHOOK` constant to eliminate hardcoded default URL repetition (was in 5 places)
- **Feature**: `Requires at least: 5.0` and `Requires PHP: 7.2` plugin headers for WordPress compatibility enforcement

### Changed
- All `get_option()` and `register_setting()` calls now reference `WOO_MONITOR_DEFAULT_WEBHOOK` constant
- Admin page placeholder and display URL now reference the constant
- Version bumped to 1.2.1

## [1.2.0] - 2026-02-28

### Fixed
- **Security**: Removed double-escaping of webhook URL (`esc_url` + `wp_json_encode`) that mangled URLs containing query parameters
- **Critical**: Moved JS error handler outside `DOMContentLoaded` so errors before DOM ready are no longer missed
- **Bug**: Empty error messages are now filtered out before sending to server
- **UX**: Checkbox descriptions moved outside `<label>` elements so clicking description no longer toggles the checkbox

### Added
- **Feature**: Fetch API interception for WooCommerce Blocks checkout support (catches errors from block-based checkout)
- **Feature**: "Send Test Alert" button on admin settings page for one-click connection verification
- **Feature**: Script deduplication guard (`window._wooMonitorLoaded`) prevents double-loading
- **Feature**: `Requires Plugins: woocommerce` header for WordPress 6.5+ dependency enforcement
- **Feature**: `WOO_MONITOR_VERSION` constant for programmatic version access
- **Feature**: Success log (`WooMonitor: Sent error alert`) on successful sends, matching troubleshooting docs

### Changed
- Replaced inline `style=""` attributes with WordPress admin CSS classes (`card`)
- Consistent JS syntax (no mixed arrow functions / `function()` declarations)
- Updated AJAX tracking label to "Track AJAX and Fetch errors" with updated description
- Removed empty `woo_monitor_deactivate()` function
- Version bumped to 1.2.0

## [1.1.2] - 2026-02-27

### Added
- Comprehensive test suite with PHPUnit
- GitHub Actions CI/CD pipeline
- Security audit and fixes
- Improved documentation

### Changed
- Updated directory structure for better organization
- Enhanced code quality and standards compliance
- Improved plugin headers and metadata

### Fixed
- Security vulnerabilities identified during audit
- Missing ABSPATH checks in included files
- Code quality issues reported by static analysis

### Security
- Added nonce verification for form submissions
- Implemented proper input sanitization
- Added output escaping for all user-facing strings
- Created SECURITY.md with best practices guidelines

## [1.1.1] - 2026-02-23

### Fixed
- **Critical**: Added check for `woo_monitor_enabled` setting (was missing)
- **Critical**: Added checks for individual tracking options (JS/AJAX/UI) (were unused)
- **Critical**: Added 5-second timeout with `AbortController` to prevent browser hanging
- **Improved**: Better error logging in browser console
- **Improved**: Optimized script loading (won't load if nothing to track)
- **Improved**: Better jQuery check (verifies jQuery is enqueued)
- **Improved**: Enhanced settings UI with descriptions

### Changed
- Single-file plugin architecture (no separate admin directory)
- Updated documentation for patched version
- Version bumped to 1.1.1

## [1.1.0] - 2026-02-21

### Added
- Admin settings page
- Configurable tracking options
- Improved error detection
- Better documentation
- WordPress.org compatible readme.txt

### Changed
- Restructured as single-file plugin
- Improved code organization
- Added proper WordPress hooks and filters

## [1.0.0] - 2026-02-20

### Added
- Initial release
- Basic error tracking
- WooCommerce UI error detection
- AJAX error tracking
- JavaScript error tracking
- Webhook integration with Node.js server

---

## Versioning

This project uses [Semantic Versioning](https://semver.org/).

## Release Process

1. Update version in `woo-monitor.php` (plugin header and `WOO_MONITOR_VERSION` constant)
2. Update version in `readme.txt` (Stable tag)
3. Update CHANGELOG.md
4. Create ZIP: `zip -r woo-monitor-plugin.zip . -x ".*" -x "__MACOSX" -x "*.git*"`
5. Commit changes
6. Create GitHub release with ZIP attachment