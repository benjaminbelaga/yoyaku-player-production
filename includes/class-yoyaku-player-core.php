<?php
/**
 * YOYAKU Player Core - Professional Edition
 * 
 * Main plugin class handling initialization, AJAX, and WordPress integration
 *
 * @package YoyakuPlayerV3
 * @since 6.1.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Main YOYAKU Player Core Class
 */
class YoyakuPlayerCore {
    
    /**
     * Single instance of the class
     *
     * @var YoyakuPlayerCore
     */
    private static $instance = null;
    
    /**
     * Plugin version
     *
     * @var string
     */
    public $version = '6.1.0';
    
    /**
     * Get instance of the class
     *
     * @return YoyakuPlayerCore
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        // Plugin initialization will be handled by init() method
    }
    
    /**
     * Initialize the plugin
     */
    public function init() {
        // Load text domain for translations
        add_action('init', array($this, 'load_textdomain'));
        
        // Enqueue scripts and styles
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        
        // AJAX handlers
        add_action('wp_ajax_yoyaku_player_v3_get_track', array($this, 'ajax_get_track'));
        add_action('wp_ajax_nopriv_yoyaku_player_v3_get_track', array($this, 'ajax_get_track'));
        
        // Shortcode support
        add_shortcode('yoyaku_player_v3', array($this, 'shortcode'));
        
        // Admin hooks
        if (is_admin()) {
            add_action('admin_menu', array($this, 'add_admin_menu'));
        }
        
        // Add WooCommerce product integration
        if (class_exists('WooCommerce')) {
            add_action('woocommerce_single_product_summary', array($this, 'add_to_product_page'), 25);
        }
    }
    
    /**
     * Load plugin text domain
     */
    public function load_textdomain() {
        load_plugin_textdomain(
            'yoyaku-player-v3',
            false,
            dirname(plugin_basename(YPV3_PLUGIN_FILE)) . '/languages'
        );
    }
    
    /**
     * Enqueue scripts and styles
     */
    public function enqueue_scripts() {
        // Only load on product pages and where needed
        if (!$this->should_load_assets()) {
            return;
        }
        
        // Enqueue CSS
        wp_enqueue_style(
            'yoyaku-player-v3',
            YPV3_PLUGIN_URL . 'assets/css/frontend.css',
            array(),
            $this->version,
            'all'
        );
        
        // Enqueue JavaScript
        wp_enqueue_script(
            'yoyaku-player-v3',
            YPV3_PLUGIN_URL . 'assets/js/frontend.js',
            array('jquery'),
            $this->version,
            true
        );
        
        // Localize script with AJAX data
        wp_localize_script('yoyaku-player-v3', 'yoyaku_player_v3', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('yoyaku_player_v3_nonce'),
            'plugin_url' => YPV3_PLUGIN_URL,
            'wc_ajax_url' => WC_AJAX::get_endpoint('add_to_cart'),
            'current_product_id' => $this->get_current_product_id(),
            'is_mobile' => wp_is_mobile(),
            'version' => $this->version
        ));
    }
    
    /**
     * Check if we should load plugin assets
     *
     * @return bool
     */
    private function should_load_assets() {
        // Load on product pages
        if (is_product()) {
            return true;
        }
        
        // Load on shop pages
        if (function_exists('is_shop') && is_shop()) {
            return true;
        }
        
        // Load on pages with shortcode
        if (is_page() || is_single()) {
            global $post;
            if ($post && has_shortcode($post->post_content, 'yoyaku_player_v3')) {
                return true;
            }
        }
        
        // Load on archive pages with products
        if (is_archive() && function_exists('wc_get_products')) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Get current product ID for JavaScript localization
     *
     * @return int|string
     */
    private function get_current_product_id() {
        global $product, $post;
        
        // Try from global product object
        if ($product && is_object($product)) {
            return $product->get_id();
        }
        
        // Try from post object on product pages
        if ($post && is_product()) {
            return $post->ID;
        }
        
        // Return 0 for non-product pages (will be detected by JavaScript)
        return 0;
    }
    
    /**
     * AJAX handler for track data
     */
    public function ajax_get_track() {
        // Clean output buffer to prevent JSON pollution
        if (ob_get_level()) {
            ob_clean();
        }
        
        // Verify nonce for security
        if (!wp_verify_nonce($_POST['nonce'], 'yoyaku_player_v3_nonce')) {
            wp_send_json_error('Invalid nonce');
            return;
        }
        
        $product_id = intval($_POST['product_id']);
        
        if (!$product_id) {
            wp_send_json_error('Invalid product ID');
            return;
        }
        
        // Get product
        $product = wc_get_product($product_id);
        
        if (!$product) {
            wp_send_json_error('Product not found');
            return;
        }
        
        // Get playlist files from product meta
        $playlist_files = get_post_meta($product_id, '_yoyaku_playlist_files', true);
        
        if (empty($playlist_files) || !is_array($playlist_files)) {
            wp_send_json_error('No tracks found for this product');
            return;
        }
        
        // Prepare response data
        $tracks = array();
        foreach ($playlist_files as $file) {
            if (isset($file['name']) && isset($file['url'])) {
                $tracks[] = array(
                    'name' => sanitize_text_field($file['name']),
                    'url' => esc_url($file['url']),
                    'bpm' => isset($file['bpm']) ? sanitize_text_field($file['bpm']) : null,
                    'duration' => isset($file['duration']) ? sanitize_text_field($file['duration']) : null
                );
            }
        }
        
        if (empty($tracks)) {
            wp_send_json_error('No valid tracks found');
            return;
        }
        
        // Get product image
        $image_id = $product->get_image_id();
        $image_url = '';
        if ($image_id) {
            $image_array = wp_get_attachment_image_src($image_id, 'medium');
            $image_url = $image_array ? $image_array[0] : '';
        }
        
        // Get taxonomies for YOYAKU music metadata
        $artist = '';
        $label = '';
        
        // Try musicartist taxonomy
        $artist_terms = wp_get_post_terms($product_id, 'musicartist');
        if (!is_wp_error($artist_terms) && !empty($artist_terms)) {
            $artist = $artist_terms[0]->name;
        }
        
        // Try musiclabel taxonomy  
        $label_terms = wp_get_post_terms($product_id, 'musiclabel');
        if (!is_wp_error($label_terms) && !empty($label_terms)) {
            $label = $label_terms[0]->name;
        }
        
        $response_data = array(
            'product_id' => $product_id,
            'title' => $product->get_name(),
            'artist' => $artist ?: 'Unknown Artist',
            'cover' => $image_url,
            'tracks' => $tracks,
            'sku' => $product->get_sku(),
            'label' => $label ?: 'Unknown Label'
        );
        
        wp_send_json_success($response_data);
    }
    
    /**
     * Shortcode handler
     *
     * @param array $atts Shortcode attributes
     * @return string
     */
    public function shortcode($atts) {
        $atts = shortcode_atts(array(
            'product_id' => 0,
            'autoplay' => 'false',
            'class' => '',
            'style' => ''
        ), $atts);
        
        if (!$atts['product_id']) {
            return '<p>YOYAKU Player: Product ID required</p>';
        }
        
        // Get product to verify it exists
        $product = wc_get_product($atts['product_id']);
        if (!$product) {
            return '<p>YOYAKU Player: Product not found</p>';
        }
        
        // Build CSS classes
        $classes = array('yoyaku-player-shortcode');
        if ($atts['class']) {
            $classes[] = sanitize_html_class($atts['class']);
        }
        
        // Build inline styles
        $style = '';
        if ($atts['style']) {
            $style = 'style="' . esc_attr($atts['style']) . '"';
        }
        
        ob_start();
        ?>
        <div class="<?php echo esc_attr(implode(' ', $classes)); ?>" <?php echo $style; ?>>
            <button class="yoyaku-play-button" 
                    data-product-id="<?php echo esc_attr($atts['product_id']); ?>"
                    data-autoplay="<?php echo esc_attr($atts['autoplay']); ?>">
                ▶ Play <?php echo esc_html($product->get_name()); ?>
            </button>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Add player to WooCommerce product pages
     */
    public function add_to_product_page() {
        global $product;
        
        if (!$product) {
            return;
        }
        
        // Check if product has playlist files
        $playlist_files = get_post_meta($product->get_id(), '_yoyaku_playlist_files', true);
        
        if (empty($playlist_files)) {
            return;
        }
        
        // Auto-add player to product pages
        echo do_shortcode('[yoyaku_player_v3 product_id="' . $product->get_id() . '"]');
    }
    
    /**
     * Add admin menu (future: settings page)
     */
    public function add_admin_menu() {
        add_options_page(
            'YOYAKU Player Settings',
            'YOYAKU Player',
            'manage_options',
            'yoyaku-player-settings',
            array($this, 'admin_page')
        );
    }
    
    /**
     * Admin page content (basic version)
     */
    public function admin_page() {
        ?>
        <div class="wrap">
            <h1>YOYAKU Player V3 Professional Edition</h1>
            <div class="card">
                <h2>Plugin Status</h2>
                <p><strong>Version:</strong> <?php echo esc_html($this->version); ?></p>
                <p><strong>Status:</strong> <span style="color: green;">Active & Working</span></p>
                <p><strong>Features:</strong></p>
                <ul>
                    <li>✅ Tracklist buttons autoplay functionality</li>
                    <li>✅ Responsive mobile design</li>
                    <li>✅ WaveSurfer.js integration</li>
                    <li>✅ WooCommerce integration</li>
                    <li>✅ Production-ready error handling</li>
                </ul>
            </div>
            
            <div class="card">
                <h2>Usage</h2>
                <p>The player automatically appears on product pages with audio files.</p>
                <p>You can also use the shortcode:</p>
                <code>[yoyaku_player_v3 product_id="123"]</code>
            </div>
        </div>
        <?php
    }
}