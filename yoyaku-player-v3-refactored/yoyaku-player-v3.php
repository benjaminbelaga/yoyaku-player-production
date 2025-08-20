<?php
/**
 * Plugin Name: YOYAKU Player V3 - Professional Edition
 * Plugin URI: https://yoyaku.io
 * Description: Professional audio player with WaveSurfer.js integration and pitch control
 * Version: 6.0.0
 * Author: YOYAKU SARL
 * Author URI: https://yoyaku.io
 * Text Domain: yoyaku-player-v3
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * 
 * @package YoyakuPlayerV3
 * @since 6.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('YPV3_VERSION', '6.0.0');
define('YPV3_PLUGIN_FILE', __FILE__);
define('YPV3_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('YPV3_PLUGIN_URL', plugin_dir_url(__FILE__));
define('YPV3_PLUGIN_BASENAME', plugin_basename(__FILE__));

// Minimum requirements
define('YPV3_MIN_PHP_VERSION', '7.4');
define('YPV3_MIN_WP_VERSION', '5.8');

/**
 * Check minimum requirements
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
 * PHP version notice
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
 * WordPress version notice
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
 * Initialize the plugin
 */
function ypv3_init() {
    // Check requirements
    if (!ypv3_check_requirements()) {
        return;
    }
    
    // Load main plugin class
    require_once YPV3_PLUGIN_DIR . 'includes/class-yoyaku-player.php';
    
    // Initialize plugin
    $player = Yoyaku_Player::get_instance();
    $player->init();
}

// Hook into WordPress
add_action('plugins_loaded', 'ypv3_init', 10);

/**
 * Activation hook
 */
function ypv3_activate() {
    // Check requirements on activation
    if (!ypv3_check_requirements()) {
        deactivate_plugins(YPV3_PLUGIN_BASENAME);
        wp_die(
            esc_html__('YOYAKU Player V3 requires minimum PHP 7.4 and WordPress 5.8', 'yoyaku-player-v3'),
            esc_html__('Plugin Activation Error', 'yoyaku-player-v3'),
            array('back_link' => true)
        );
    }
    
    // Flush rewrite rules
    flush_rewrite_rules();
}
register_activation_hook(__FILE__, 'ypv3_activate');

/**
 * Deactivation hook
 */
function ypv3_deactivate() {
    // Cleanup if needed
    flush_rewrite_rules();
}
register_deactivation_hook(__FILE__, 'ypv3_deactivate');