# YOYAKU Player V3 - Production Ready with CI/CD Pipeline

## Version: 5.4.3
**Status**: PRODUCTION READY with Enterprise CI/CD  
**Date**: 18 August 2025

## Description
Professional audio player for WooCommerce music stores with waveform visualization, automatic playlist management, and enterprise-grade CI/CD pipeline for safe production deployments.

## 🚀 CI/CD Pipeline Features
- **Multi-Environment**: Staging → Production deployment flow
- **Zero-Downtime**: Atomic deployments with automatic rollback
- **E-commerce Safety**: Checkout/shop page validation
- **Real-time Monitoring**: GitHub Actions + Discord alerts
- **WordPress Security**: Automated vulnerability scanning
- **HPOS Compatible**: WooCommerce High-Performance Order Storage ready

## Audio Player Features
- Ultra-thin 48px player bar design
- WaveSurfer.js waveform visualization
- Circular play/pause buttons with icons
- Smart AudioContext management
- Automatic track advancement
- Product playlist navigation
- AJAX cart integration
- Responsive mobile design

## Requirements
- WordPress 5.8 or higher
- WooCommerce 5.0 or higher
- PHP 8.1 or higher
- Modern browsers (Chrome, Firefox, Safari, Edge)
- SSH access for deployments
- GitHub repository with Actions enabled

## Quick Start

### For Developers
```bash
# Clone and setup
git clone [repository-url]
cd yoyaku-player-v3-production
composer install

# Deploy to staging
./scripts/deploy.sh staging

# Run tests
composer test

# Deploy to production
./scripts/deploy.sh production
```

### For Emergencies
```bash
# Emergency rollback
./scripts/rollback.sh production --force

# Check site health
./scripts/monitor.sh production
```

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
