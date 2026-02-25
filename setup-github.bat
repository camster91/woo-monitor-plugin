@echo off
echo ========================================
echo   WOOCOMMERCE MONITOR PLUGIN - GITHUB SETUP
echo ========================================
echo.
echo This script helps you push the PATCHED plugin to GitHub.
echo Version: 1.1.1 (with all critical bug fixes)
echo.
echo PREREQUISITE:
echo 1. Create repository at: https://github.com/camster91/woo-monitor-plugin
echo    - No README, no .gitignore, no license
echo    - Public repository
echo.
echo ========================================
echo.

echo Step 1: Initialize Git repository
git init
if errorlevel 1 (
    echo ERROR: Git not installed or failed to initialize
    pause
    exit /b 1
)

echo Step 2: Add all files
git add .

echo Step 3: Create initial commit
git commit -m "Initial commit: WooCommerce Error Monitor plugin v1.1.1

- Patched version with all critical bug fixes
- Added check for enabled setting
- Added checks for individual tracking options  
- Added 5-second timeout with AbortController
- Better error logging and timeout handling
- Single-file plugin (no activation issues)
- Ready for production deployment"

echo.
echo Step 4: Add remote repository
echo Run these commands AFTER creating the repository on GitHub:
echo.
echo git remote add origin https://github.com/camster91/woo-monitor-plugin.git
echo git branch -M main
echo git push -u origin main
echo.
echo Step 5: Create release on GitHub
echo 1. Go to repository -> Releases
echo 2. 'Create a new release'
echo 3. Tag: v1.1.1
echo 4. Title: 'Version 1.1.1 - Patched with bug fixes'
echo 5. Description: Include changelog from README.md
echo 6. Attach: woo-monitor-plugin.zip
echo 7. Publish release
echo.
echo ========================================
echo     GITHUB SETUP COMPLETE!
echo ========================================
echo.
echo Next steps:
echo 1. Install plugin on WordPress sites using woo-monitor-plugin.zip
echo 2. Configure Settings -> WooCommerce Monitor
echo 3. Set webhook URL: https://woo.ashbi.ca/api/track-woo-error
echo 4. Test bug fixes are working
echo.
pause