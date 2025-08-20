<?php
/**
 * Assets loader for YOYAKU Player V3
 * 
 * @package YoyakuPlayerV3
 * @since 6.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Assets loader class
 */
class Yoyaku_Player_Assets {
    
    /**
     * Use minified assets in production
     * 
     * @var bool
     */
    private $use_minified;
    
    /**
     * Constructor
     */
    public function __construct() {
        // Use minified assets if not in debug mode
        $this->use_minified = !defined('SCRIPT_DEBUG') || !SCRIPT_DEBUG;
    }
    
    /**
     * Initialize assets loading
     */
    public function init() {
        // Enqueue scripts and styles
        add_action('wp_enqueue_scripts', array($this, 'enqueue_assets'));
    }
    
    /**
     * Enqueue scripts and styles
     */
    public function enqueue_assets() {
        // Check if we should load assets
        if (!$this->should_load_assets()) {
            return;
        }
        
        // Enqueue styles
        $this->enqueue_styles();
        
        // Enqueue scripts
        $this->enqueue_scripts();
        
        // Localize script
        $this->localize_script();
    }
    
    /**
     * Check if assets should be loaded
     * 
     * @return bool
     */
    private function should_load_assets() {
        // Always load on product pages
        if (is_product()) {
            return true;
        }
        
        // Check if shortcode is present in content
        global $post;
        if ($post && has_shortcode($post->post_content, 'yoyaku_player_v3')) {
            return true;
        }
        
        // Allow filtering
        return apply_filters('yoyaku_player_v3_should_load_assets', false);
    }
    
    /**
     * Enqueue styles
     */
    private function enqueue_styles() {
        // Determine file to use
        $css_file = $this->use_minified ? 'player.min.css' : 'player.css';
        $css_path = 'assets/css/' . $css_file;
        
        // Check if file exists, fallback to non-minified
        if (!file_exists(YPV3_PLUGIN_DIR . $css_path)) {
            $css_path = 'assets/css/player.css';
        }
        
        // Build version string
        $version = YPV3_VERSION;
        if (defined('WP_DEBUG') && WP_DEBUG) {
            $version .= '.' . time(); // Cache busting in debug mode
        }
        
        // Get URL
        $css_url = YPV3_PLUGIN_URL . $css_path;
        
        // Allow filtering
        $css_url = apply_filters('yoyaku_player_v3_style_url', $css_url);
        
        // Enqueue style
        wp_enqueue_style(
            'yoyaku-player-v3',
            $css_url,
            array(), // No dependencies
            $version,
            'all'
        );
        
        // Add inline styles for customization
        $this->add_inline_styles();
    }
    
    /**
     * Enqueue scripts
     */
    private function enqueue_scripts() {
        // Determine file to use
        $js_file = $this->use_minified ? 'player.min.js' : 'player.js';
        $js_path = 'assets/js/' . $js_file;
        
        // Check if file exists, fallback to non-minified
        if (!file_exists(YPV3_PLUGIN_DIR . $js_path)) {
            $js_path = 'assets/js/player.js';
        }
        
        // Build version string
        $version = YPV3_VERSION;
        if (defined('WP_DEBUG') && WP_DEBUG) {
            $version .= '.' . time(); // Cache busting in debug mode
        }
        
        // Get URL
        $js_url = YPV3_PLUGIN_URL . $js_path;
        
        // Allow filtering
        $js_url = apply_filters('yoyaku_player_v3_script_url', $js_url);
        
        // Dependencies
        $deps = array('jquery');
        
        // Add WooCommerce dependency if available
        if (function_exists('WC')) {
            $deps[] = 'wc-add-to-cart';
        }
        
        // Enqueue script
        wp_enqueue_script(
            'yoyaku-player-v3',
            $js_url,
            $deps,
            $version,
            true // Load in footer
        );
    }
    
    /**
     * Localize script with data
     */
    private function localize_script() {
        // Get current product ID if on product page
        $current_product_id = 0;
        if (is_product()) {
            global $product;
            if ($product && is_object($product) && method_exists($product, 'get_id')) {
                $current_product_id = $product->get_id();
            }
        }
        
        // Build localization data
        $localize_data = array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('yoyaku_player_v3_nonce'),
            'plugin_url' => YPV3_PLUGIN_URL,
            'current_product_id' => $current_product_id,
            'is_mobile' => wp_is_mobile(),
            'debug_mode' => defined('WP_DEBUG') && WP_DEBUG,
            'strings' => array(
                'loading' => __('Loading...', 'yoyaku-player-v3'),
                'error' => __('An error occurred', 'yoyaku-player-v3'),
                'play' => __('Play', 'yoyaku-player-v3'),
                'pause' => __('Pause', 'yoyaku-player-v3'),
                'previous' => __('Previous', 'yoyaku-player-v3'),
                'next' => __('Next', 'yoyaku-player-v3'),
                'mute' => __('Mute', 'yoyaku-player-v3'),
                'unmute' => __('Unmute', 'yoyaku-player-v3'),
                'add_to_cart' => __('Add to Cart', 'yoyaku-player-v3')
            )
        );
        
        // Add WooCommerce AJAX URL if available
        if (class_exists('WC_AJAX')) {
            $localize_data['wc_ajax_url'] = WC_AJAX::get_endpoint('add_to_cart');
        }
        
        // Allow filtering
        $localize_data = apply_filters('yoyaku_player_v3_localize_data', $localize_data);
        
        // Localize script
        wp_localize_script('yoyaku-player-v3', 'yoyaku_player_v3', $localize_data);
    }
    
    /**
     * Add inline styles for customization
     */
    private function add_inline_styles() {
        // Get customization options (future feature)
        $primary_color = apply_filters('yoyaku_player_v3_primary_color', '#007cba');
        $player_height = apply_filters('yoyaku_player_v3_height', '48px');
        
        // Build inline CSS
        $inline_css = "
            :root {
                --ypv3-primary-color: {$primary_color};
                --ypv3-player-height: {$player_height};
            }
        ";
        
        // Allow filtering
        $inline_css = apply_filters('yoyaku_player_v3_inline_styles', $inline_css);
        
        // Add inline styles
        if (!empty($inline_css)) {
            wp_add_inline_style('yoyaku-player-v3', $inline_css);
        }
    }
}