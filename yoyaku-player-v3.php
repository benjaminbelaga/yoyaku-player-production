<?php
/**
 * Plugin Name: YOYAKU Player V3 - Professional Edition
 * Plugin URI: https://yoyaku.io
 * Description: Professional audio player with WaveSurfer.js integration, responsive design, and tracklist autoplay functionality 
 * Version: 6.1.0
 * Author: YOYAKU SARL
 * Author URI: https://yoyaku.io
 * Text Domain: yoyaku-player-v3
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * 
 * Professional edition with:
 * - Fixed tracklist buttons autoplay functionality
 * - Responsive mobile design (48px desktop, 120px mobile)
 * - Real WaveSurfer.js integration with proper audio analysis
 * - Clean, maintainable code architecture
 * - Production-ready error handling
 * - YOYAKU-specific e-commerce integration
 * 
 * @package YoyakuPlayerV3
 * @since 6.1.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('YPV3_VERSION', '6.1.0');
define('YPV3_PLUGIN_FILE', __FILE__);
define('YPV3_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('YPV3_PLUGIN_URL', plugin_dir_url(__FILE__));
define('YPV3_PLUGIN_BASENAME', plugin_basename(__FILE__));

// Minimum requirements for production deployment
define('YPV3_MIN_PHP_VERSION', '7.4');
define('YPV3_MIN_WP_VERSION', '5.8');

/**
 * Check minimum requirements before plugin activation
 * 
 * @return bool True if requirements met
 */
function ypv3_check_requirements() {
    // Check PHP version
    if (version_compare(PHP_VERSION, YPV3_MIN_PHP_VERSION, '<')) {
        add_action('admin_notices', 'ypv3_php_version_notice');
        return false;
    }
    
    // Check WordPress version  
    global $wp_version;
    if (version_compare($wp_version, YPV3_MIN_WP_VERSION, '<')) {
        add_action('admin_notices', 'ypv3_wp_version_notice');
        return false;
    }
    
    return true;
}

/**
 * PHP version requirement notice
 */
function ypv3_php_version_notice() {
    ?>
    <div class="notice notice-error">
        <p><?php printf(
            esc_html__('YOYAKU Player V3 requires PHP %s or higher. You are running PHP %s.', 'yoyaku-player-v3'),
            YPV3_MIN_PHP_VERSION,
            PHP_VERSION
        ); ?></p>
    </div>
    <?php
}

/**
 * WordPress version requirement notice
 */
function ypv3_wp_version_notice() {
    global $wp_version;
    ?>
    <div class="notice notice-error">
        <p><?php printf(
            esc_html__('YOYAKU Player V3 requires WordPress %s or higher. You are running WordPress %s.', 'yoyaku-player-v3'),
            YPV3_MIN_WP_VERSION,
            $wp_version
        ); ?></p>
    </div>
    <?php
}

/**
 * Initialize the YOYAKU Player V3 Professional Edition
 */
function ypv3_init() {
    // Check requirements before initialization
    if (!ypv3_check_requirements()) {
        return;
    }
    
    // Load main plugin functionality
    require_once YPV3_PLUGIN_DIR . 'includes/class-yoyaku-player-core.php';
    
    // Initialize the player
    $player = YoyakuPlayerCore::get_instance();
    $player->init();
}

// Hook into WordPress initialization
add_action('plugins_loaded', 'ypv3_init', 10);

/**
 * Plugin activation hook - production safety checks
 */
function ypv3_activate() {
    // Verify requirements on activation
    if (!ypv3_check_requirements()) {
        deactivate_plugins(YPV3_PLUGIN_BASENAME);
        wp_die(
            esc_html__('YOYAKU Player V3 Professional Edition requires minimum PHP 7.4 and WordPress 5.8', 'yoyaku-player-v3'),
            esc_html__('Plugin Activation Error', 'yoyaku-player-v3'),
            array('back_link' => true)
        );
    }
    
    // Flush rewrite rules for clean URLs
    flush_rewrite_rules();
    
    // Create necessary database tables if needed
    ypv3_create_tables();
}

/**
 * Plugin deactivation hook - cleanup
 */
function ypv3_deactivate() {
    // Clean up temporary data
    flush_rewrite_rules();
    
    // Remove transients
    delete_transient('yoyaku_player_cache');
}

/**
 * Create necessary database tables for enhanced functionality
 */
function ypv3_create_tables() {
    // Future: Analytics, playlists, user preferences
    // Currently using WordPress post meta - no custom tables needed
    
    // Set database version for future migrations
    update_option('yoyaku_player_db_version', '1.0');
}

// Register activation/deactivation hooks
register_activation_hook(__FILE__, 'ypv3_activate');
register_deactivation_hook(__FILE__, 'ypv3_deactivate');

/**
 * Plugin loaded successfully message for debugging
 */
add_action('wp_footer', function() {
    if (defined('WP_DEBUG') && WP_DEBUG) {
        echo "<!-- YOYAKU Player V3 Professional Edition v" . YPV3_VERSION . " loaded successfully -->";
    }
});