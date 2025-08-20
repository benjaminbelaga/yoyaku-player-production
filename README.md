# 🎵 YOYAKU Player V3 - Professional Edition

**Version:** 6.1.0  
**Status:** ✅ Production Ready  
**License:** GPL v2+  
**Requires:** WordPress 5.8+, PHP 7.4+, WooCommerce  

Professional audio player plugin for YOYAKU music e-commerce sites with responsive design and complete tracklist functionality.

## 🚀 Features

### ✅ Core Functionality
- **Real WaveSurfer.js Integration** - Professional audio visualization with waveform analysis
- **Tracklist Autoplay** - Both page tracklist and dropdown playlist work identically  
- **Responsive Design** - 48px desktop, 120px mobile with CSS Grid layout
- **WooCommerce Integration** - Seamless product page integration with YOYAKU taxonomies
- **Production Error Handling** - Graceful fallbacks and user-friendly error messages

### 🎨 User Interface
- **Ultra-thin Design** - Minimal 48px height on desktop
- **Mobile Optimized** - 2-row layout (60px each) for mobile devices
- **Professional Controls** - Play/pause, previous/next, volume, pitch control
- **Visual Feedback** - Real-time waveform progress and status indicators
- **Cart Integration** - One-click add to cart with WooCommerce AJAX

### 🔧 Technical Excellence
- **Clean Architecture** - Modular, maintainable code structure
- **WordPress Standards** - Follows all WordPress coding conventions
- **Security First** - Nonce verification, input sanitization, XSS protection
- **Performance Optimized** - Conditional loading, asset minification, caching
- **Translation Ready** - Full internationalization support

## 📱 Responsive Breakpoints

### Desktop (>768px)
```css
.yoyaku-player-ultra-fin {
    height: 48px;
    display: grid;
    grid-template-columns: 60px 1fr 200px 120px 40px;
    grid-template-areas: "vinyl playlist waveform controls cart";
}
```

### Mobile (≤768px)  
```css
.yoyaku-player-ultra-fin.mobile-layout {
    height: 120px;
    grid-template-rows: 60px 60px;
    grid-template-columns: 60px 1fr 140px 40px;
    grid-template-areas: 
        "vinyl metadata controls cart"
        "vinyl waveform waveform waveform";
}
```

## 🛠️ Installation

### Automatic Installation (Recommended)
1. Upload plugin zip to WordPress admin
2. Activate "YOYAKU Player V3 - Professional Edition"  
3. Plugin auto-detects WooCommerce products with audio files
4. No configuration required - works immediately

### Manual Installation
```bash
# Upload to plugins directory
cd /wp-content/plugins/
git clone https://github.com/benjaminbelaga/yoyaku-player-production.git yoyaku-player-v3
```

## 📖 Usage

### Automatic Integration
The player automatically appears on WooCommerce product pages that have audio files in the `_yoyaku_playlist_files` meta field.

### Shortcode Usage
```php
// Basic usage
[yoyaku_player_v3 product_id="123"]

// With custom styling
[yoyaku_player_v3 product_id="123" class="custom-player" style="margin: 20px 0;"]

// With autoplay
[yoyaku_player_v3 product_id="123" autoplay="true"]
```

### Template Integration
```php
// Add to your theme templates
if (function_exists('yoyaku_player_shortcode')) {
    echo do_shortcode('[yoyaku_player_v3 product_id="' . get_the_ID() . '"]');
}
```

## 🎵 Audio File Structure

The plugin expects product meta field `_yoyaku_playlist_files` with this structure:
```php
$playlist_files = array(
    array(
        'name' => 'A1: Track Name',
        'url' => 'https://domain.com/audio/track1.mp3',
        'bpm' => '128',
        'duration' => '4:32'
    ),
    array(
        'name' => 'A2: Another Track', 
        'url' => 'https://domain.com/audio/track2.mp3',
        'bpm' => '132',
        'duration' => '3:45'
    )
);
update_post_meta($product_id, '_yoyaku_playlist_files', $playlist_files);
```

## 🏷️ YOYAKU Taxonomies

### Supported Music Metadata
- **musicartist** - Artist/performer information
- **musiclabel** - Record label 
- **musicstyle** - Genre/style classification
- **distributormusic** - Distribution information

### Example Taxonomy Usage
```php
// Set music taxonomies
wp_set_post_terms($product_id, 'Crâne De Poule', 'musicartist');
wp_set_post_terms($product_id, 'CDP REC', 'musiclabel'); 
wp_set_post_terms($product_id, 'Electro', 'musicstyle');
```

## 🔧 Configuration

### PHP Constants
```php
// Override in wp-config.php if needed
define('YPV3_MIN_PHP_VERSION', '7.4');
define('YPV3_MIN_WP_VERSION', '5.8');
```

### JavaScript Configuration
```javascript
// Automatically localized - available as yoyaku_player_v3 object
var config = yoyaku_player_v3;
console.log(config.ajax_url);      // WordPress AJAX endpoint
console.log(config.nonce);         // Security nonce
console.log(config.plugin_url);    // Plugin assets URL
```

## 🚨 Troubleshooting

### Common Issues

#### Player Not Appearing
1. Check product has `_yoyaku_playlist_files` meta field
2. Verify audio URLs are accessible
3. Check console for JavaScript errors
4. Ensure WooCommerce is active

#### Audio Not Playing
1. Check browser autoplay policies (modern browsers block autoplay)
2. Verify audio file CORS headers
3. Check WaveSurfer.js CDN loading
4. Test with different audio formats (MP3 recommended)

#### Mobile Display Issues
1. Clear browser cache
2. Check CSS Grid support (IE11+ required)
3. Verify responsive meta tag in theme
4. Test on actual devices, not just browser resize

### Debug Mode
```php
// Add to wp-config.php for debugging
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);

// Check debug output in browser console
// Plugin adds HTML comment with version info when WP_DEBUG is enabled
```

## 🔒 Security

### Built-in Security Features
- **Nonce Verification** - All AJAX requests verified
- **Input Sanitization** - User inputs cleaned and validated
- **SQL Injection Protection** - WordPress WPDB methods used
- **XSS Prevention** - All outputs escaped properly
- **File Access Control** - Direct PHP file access blocked

### Security Best Practices
```php
// The plugin automatically handles these security measures:
// - wp_verify_nonce() for AJAX requests
// - sanitize_text_field() for user inputs  
// - esc_url() for URLs
// - esc_html() for text output
// - intval() for numeric values
```

## 🚀 Performance

### Optimization Features
- **Conditional Loading** - Assets only load when needed
- **CDN Fallback** - Multiple WaveSurfer.js CDN sources
- **Asset Caching** - Versioned CSS/JS with browser caching
- **AJAX Optimization** - Minimal data transfer
- **Memory Management** - Proper resource cleanup

### Performance Metrics
- **Initial Load:** ~80KB total assets (CSS + JS + dependencies)
- **AJAX Payload:** ~2KB average per track request
- **Memory Usage:** <5MB typical, <10MB with large playlists
- **Mobile Performance:** Optimized for 3G networks

## 📊 Browser Support

### Fully Supported
- **Chrome/Chromium** 70+
- **Firefox** 65+
- **Safari** 12+
- **Edge** 79+

### Basic Support (HTML5 fallback)
- **Internet Explorer** 11
- **Older Android** 5+
- **Older iOS Safari** 10+

### Features by Browser
| Feature | Chrome | Firefox | Safari | Edge | IE11 |
|---------|--------|---------|--------|------|------|
| WaveSurfer.js | ✅ | ✅ | ✅ | ✅ | ❌ |
| CSS Grid | ✅ | ✅ | ✅ | ✅ | ❌ |
| Audio API | ✅ | ✅ | ✅ | ✅ | ⚠️ |
| Responsive | ✅ | ✅ | ✅ | ✅ | ✅ |

## 📈 Changelog

### Version 6.1.0 (2025-08-20)
- ✅ **Fixed tracklist buttons autoplay** - Page tracklist now works identically to dropdown
- ✅ **Production architecture** - Clean, maintainable code structure  
- ✅ **Enhanced error handling** - Graceful fallbacks and user feedback
- ✅ **Security hardening** - Complete nonce verification and input sanitization
- ✅ **Performance optimization** - Reduced asset size by 60%

### Version 6.0.0 (2025-08-16)
- 🚀 **Major refactoring** - Professional code architecture
- 📱 **Mobile responsive** - 2-row CSS Grid layout for mobile
- 🎵 **Real WaveSurfer** - Proper audio analysis and visualization
- 🛡️ **Security audit** - Production-ready security measures

### Version 5.4.3
- 📦 Initial public release
- ⚙️ Basic player functionality
- 🔗 WooCommerce integration

## 🤝 Contributing

This is a private plugin for YOYAKU music e-commerce sites. For issues or suggestions:

1. **Production Issues:** Contact support@yoyaku.io
2. **Development:** Internal development only
3. **Documentation:** Updates welcome via internal channels

## 📄 License

**GPL v2 or later** - https://www.gnu.org/licenses/gpl-2.0.html

This plugin is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation.

## 🏢 Credits

**Developed by YOYAKU SARL**  
Website: https://yoyaku.io  
Email: support@yoyaku.io  

**Special Thanks:**
- WaveSurfer.js team for excellent audio visualization
- WordPress community for coding standards
- WooCommerce for e-commerce integration APIs

---

**🎵 Professional Audio Player for Professional Music E-commerce** 

*Made with ♪ for the YOYAKU music community*