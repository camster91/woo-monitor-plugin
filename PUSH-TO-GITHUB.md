# Push to GitHub Instructions

## Prerequisites
1. GitHub account (camster91)
2. Create repository: https://github.com/camster91/woo-monitor-plugin
   - Public repository
   - No README, .gitignore, or license (we have these already)
   - Create empty repository

## Push Commands

Run these commands in `C:\Users\camst\woo-monitor-plugin-github`:

```bash
# Add remote origin (AFTER creating repository on GitHub)
git remote add origin https://github.com/camster91/woo-monitor-plugin.git

# Push to GitHub
git push -u origin main
```

## Create Release on GitHub

1. Go to: https://github.com/camster91/woo-monitor-plugin/releases/new
2. **Tag**: `v1.1.1`
3. **Title**: `Version 1.1.1 - Patched with bug fixes`
4. **Description**:
   ```
   ## WooCommerce Error Monitor v1.1.1
   
   Patched version with all critical bug fixes.
   
   ### Bug Fixes:
   - Added check for enabled setting (was missing)
   - Added checks for individual tracking options (JS/AJAX/UI)
   - Added 5-second timeout with AbortController
   - Better error logging and timeout handling
   - Optimized script loading
   
   ### Installation:
   1. Download woo-monitor-plugin.zip
   2. WordPress → Plugins → Add New → Upload
   3. Activate → Settings → WooCommerce Monitor
   4. Set webhook URL: https://woo.ashbi.ca/api/track-woo-error
   ```
5. **Attach binary**: Drag and drop `woo-monitor-plugin.zip`
6. **Publish release**

## Verify Release
- URL: https://github.com/camster91/woo-monitor-plugin/releases/tag/v1.1.1
- Download: https://github.com/camster91/woo-monitor-plugin/releases/download/v1.1.1/woo-monitor-plugin.zip

## Update Deployment Documentation
After pushing to GitHub, update:
1. `deploy-woomonitor.bat` - Reference GitHub repository
2. `START_HERE.md` - Add GitHub link
3. `README.md` in Downloads folder