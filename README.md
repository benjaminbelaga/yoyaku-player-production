# YOYAKU Player V3 - Production Ready

## Version: 5.4.3
**Status**: PRODUCTION READY  
**Date**: 15 August 2025

## Description
Professional audio player for WooCommerce music stores with waveform visualization and automatic playlist management.

## Features
- Ultra-thin 48px player bar design
- WaveSurfer.js waveform visualization
- Circular play/pause buttons with icons
- Smart AudioContext management
- Automatic track advancement
- Product playlist navigation
- AJAX cart integration
- Responsive mobile design

## Requirements
- WordPress 5.0 or higher
- WooCommerce 5.0 or higher
- PHP 7.4 or higher
- Modern browsers (Chrome, Firefox, Safari, Edge)

## Installation
1. Upload plugin folder to `/wp-content/plugins/`
2. Activate through WordPress admin panel
3. Clear all caches after activation

## File Structure
```
yoyaku-player-v3-production/
├── yoyaku-player-v3.php         # Main plugin file
├── ajax-handler.php             # AJAX endpoints
├── class-yoyaku-compatibility.php
├── class-yoyaku-error-handler.php
├── class-yoyaku-theme-loader.php
├── yoyaku-fail-safe-functions.php
├── assets/
│   ├── css/
│   │   └── frontend.css        # Player styles
│   └── js/
│       └── frontend.js          # Player logic
├── README.md                    # This file
└── readme.txt                   # WordPress readme
```

## Changelog

### 5.4.3 (2025-08-15)
- Fixed autoplay on product selection
- Enhanced AudioContext suspension handling
- Optimized title display with flex layout
- Improved circular button design

### 5.4.2 (2025-08-15)
- Fixed title wrapping issues
- CSS improvements for responsive layout

### 5.4.1 (2025-08-15)
- Implemented circular play/pause buttons
- UI/UX enhancements

## Support
For technical support: ben@yoyaku.io

## License
Proprietary - YOYAKU SARL - All rights reserved
