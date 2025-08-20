=== YOYAKU Player V3 - Professional Edition ===
Contributors: yoyaku
Tags: audio, player, woocommerce, music, wavesurfer, responsive, ecommerce
Requires at least: 5.8
Tested up to: 6.5
Requires PHP: 7.4
Stable tag: 6.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Professional audio player for YOYAKU music e-commerce with responsive design and complete tracklist functionality.

== Description ==

YOYAKU Player V3 Professional Edition is a premium audio player plugin designed specifically for music e-commerce sites. It provides a sleek, ultra-responsive interface with advanced audio visualization and seamless WooCommerce integration.

= Key Features =

* **Real WaveSurfer.js Integration** - Professional waveform visualization with real audio analysis
* **Tracklist Autoplay** - Both page tracklist and dropdown playlist work identically with autoplay
* **Ultra-Responsive Design** - 48px desktop height, 120px mobile with CSS Grid layout
* **WooCommerce Native** - Deep integration with product pages and YOYAKU music taxonomies
* **Production Ready** - Enterprise-grade error handling and security measures

= Perfect For =

* Music labels and distributors
* Record stores and vinyl shops  
* Digital music platforms
* Artists selling direct
* Audio sample libraries
* Music streaming sites

= Technical Excellence =

* Clean, modular architecture following WordPress coding standards
* Translation ready with full internationalization support
* Security-first development with nonce verification and input sanitization
* Performance optimized with conditional loading and asset caching
* Mobile-first responsive design with CSS Grid

= Professional Features =

* **Audio Controls** - Play/pause, previous/next, volume, pitch control (-8 to +8)
* **Visual Feedback** - Real-time waveform progress and status indicators  
* **Cart Integration** - One-click WooCommerce add to cart with AJAX
* **Playlist Management** - Dropdown track selection with BPM display
* **Mobile Optimization** - Touch-optimized controls and 2-row mobile layout

== Installation ==

= Automatic Installation =

1. Upload plugin zip file through WordPress admin
2. Activate "YOYAKU Player V3 - Professional Edition"
3. Plugin automatically detects WooCommerce products with audio files
4. No additional configuration required

= Manual Installation =

1. Upload the plugin folder to `/wp-content/plugins/`
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Ensure WooCommerce is installed and activated
4. Add audio files to your products using the `_yoyaku_playlist_files` meta field

= Requirements =

* WordPress 5.8 or higher
* PHP 7.4 or higher  
* WooCommerce 5.0 or higher
* Modern web browser with HTML5 audio support

== Usage ==

= Automatic Integration =

The player automatically appears on WooCommerce product pages that contain audio files. No configuration required.

= Shortcode Usage =

`[yoyaku_player_v3 product_id="123"]`

With custom styling:
`[yoyaku_player_v3 product_id="123" class="custom-player" style="margin: 20px 0;"]`

With autoplay:
`[yoyaku_player_v3 product_id="123" autoplay="true"]`

= Audio File Setup =

Add audio files to products using the `_yoyaku_playlist_files` meta field:

```php
$playlist_files = array(
    array(
        'name' => 'A1: Track Name',
        'url' => 'https://your-domain.com/audio/track1.mp3',
        'bpm' => '128',
        'duration' => '4:32'
    )
);
update_post_meta($product_id, '_yoyaku_playlist_files', $playlist_files);
```

== Screenshots ==

1. Desktop player interface showing waveform visualization and controls
2. Mobile responsive 2-row layout optimized for touch interaction
3. Dropdown playlist with BPM information and track selection
4. WooCommerce product page integration
5. Admin settings page with plugin status and configuration options

== Frequently Asked Questions ==

= Does this require WooCommerce? =

Yes, this plugin is designed specifically for WooCommerce music stores and requires WooCommerce 5.0 or higher.

= Can I customize the player appearance? =

Yes! You can override templates in your theme, use CSS variables for styling, and add custom classes via shortcode parameters.

= Is it mobile responsive? =

Absolutely! The player uses CSS Grid to automatically switch between desktop (single row, 48px) and mobile (double row, 120px) layouts.

= Can I use multiple players on one page? =

Yes, you can have multiple player instances on the same page without conflicts. Each player maintains its own state.

= What audio formats are supported? =

The player supports all HTML5 audio formats. MP3 is recommended for best browser compatibility.

= Does it work with my existing theme? =

Yes, the player is designed to work with any well-coded WordPress theme. It uses minimal, non-conflicting CSS.

= How do I add audio files to products? =

Audio files are managed through the `_yoyaku_playlist_files` product meta field. You can add this programmatically or through a custom meta box.

= Is the player accessible? =

Yes, the player includes proper ARIA labels, keyboard navigation support, and screen reader compatibility.

= Can I translate the plugin? =

Yes, the plugin is fully translation ready with .pot files included for all text strings.

== Changelog ==

= 6.1.0 - 2025-08-20 =
* **MAJOR UPDATE:** Fixed tracklist buttons autoplay functionality 
* **Enhancement:** Page tracklist now works identically to dropdown playlist
* **Architecture:** Professional code refactoring for better maintainability  
* **Security:** Enhanced security with complete nonce verification
* **Performance:** Reduced plugin size by 60% through optimization
* **Mobile:** Improved touch responsiveness and layout stability
* **Documentation:** Complete technical documentation and usage guides

= 6.0.0 - 2025-08-16 =
* **Major refactoring:** Clean, professional code architecture
* **Mobile responsive:** 2-row CSS Grid layout for mobile devices
* **Real WaveSurfer:** Proper audio analysis and waveform visualization
* **Security audit:** Production-ready security measures implemented
* **Template system:** Overridable templates for theme customization
* **Hooks and filters:** Extensive customization API
* **Error handling:** Graceful fallbacks and user-friendly messages
* **Performance:** Optimized asset loading and caching

= 5.4.3 =
* Initial public release
* Basic player functionality with WooCommerce integration
* WaveSurfer.js visualization support

== Upgrade Notice ==

= 6.1.0 =
Major update with enhanced functionality and bug fixes. The tracklist buttons now work perfectly with autoplay. 100% backward compatible. Backup recommended before upgrade.

= 6.0.0 =  
Significant architecture improvements and mobile responsiveness. All previous functionality preserved with enhanced performance and security.

== Additional Information ==

= System Requirements =

* **WordPress:** 5.8 or higher
* **PHP:** 7.4 or higher (8.0+ recommended)
* **WooCommerce:** 5.0 or higher
* **Browser:** Modern browsers with HTML5 and CSS Grid support
* **HTTPS:** Recommended for audio file serving

= Performance =

* **Asset Size:** ~80KB total (CSS + JS + dependencies)
* **AJAX Payload:** ~2KB average per track request  
* **Memory Usage:** <5MB typical, <10MB with large playlists
* **Mobile Optimized:** Designed for 3G+ networks

= Browser Compatibility =

* **Full Support:** Chrome 70+, Firefox 65+, Safari 12+, Edge 79+
* **Basic Support:** Internet Explorer 11, Android 5+, iOS 10+
* **Audio Formats:** MP3 (recommended), WAV, OGG, M4A

= Security Features =

* Nonce verification for all AJAX requests
* Input sanitization and validation  
* XSS and CSRF protection
* SQL injection prevention
* Secure file access controls

= Support and Documentation =

* **Plugin URI:** https://yoyaku.io
* **Documentation:** Complete technical docs included
* **Support:** professional support available
* **Updates:** Regular updates with new features and security fixes

= Credits =

Developed by YOYAKU SARL for professional music e-commerce.
Built with WaveSurfer.js, WordPress APIs, and WooCommerce integration.

---

**Professional Audio Player for Professional Music E-commerce**