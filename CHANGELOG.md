# Changelog

All notable changes to the WooCommerce Error Monitor plugin will be documented in this file.

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

1. Update version in `woo-monitor.php` (line 7)
2. Update version in `readme.txt` (Stable tag)
3. Update CHANGELOG.md
4. Create ZIP: `zip -r woo-monitor-plugin.zip . -x ".*" -x "__MACOSX" -x "*.git*"`
5. Commit changes
6. Create GitHub release with ZIP attachment