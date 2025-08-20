<?php
/**
 * Main plugin class for YOYAKU Player V3
 * 
 * @package YoyakuPlayerV3
 * @since 6.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Main Yoyaku Player class
 * 
 * Singleton pattern for reliability
 */
class Yoyaku_Player {
    
    /**
     * Singleton instance
     * 
     * @var Yoyaku_Player
     */
    private static $instance = null;
    
    /**
     * Ajax handler instance
     * 
     * @var Yoyaku_Player_Ajax
     */
    private $ajax_handler;
    
    /**
     * Assets loader instance
     * 
     * @var Yoyaku_Player_Assets
     */
    private $assets_loader;
    
    /**
     * Get singleton instance
     * 
     * @return Yoyaku_Player
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Private constructor
     */
    private function __construct() {
        // Constructor is private for singleton
    }
    
    /**
     * Initialize the plugin
     */
    public function init() {
        $this->load_dependencies();
        $this->define_hooks();
    }
    
    /**
     * Load required dependencies
     */
    private function load_dependencies() {
        // Load required classes
        require_once YPV3_PLUGIN_DIR . 'includes/class-ajax-handler.php';
        require_once YPV3_PLUGIN_DIR . 'includes/class-assets-loader.php';
        
        // Initialize handlers
        $this->ajax_handler = new Yoyaku_Player_Ajax();
        $this->assets_loader = new Yoyaku_Player_Assets();
    }
    
    /**
     * Define all hooks and filters
     */
    private function define_hooks() {
        // Register shortcode
        add_shortcode('yoyaku_player_v3', array($this, 'render_shortcode'));
        
        // Initialize components
        $this->ajax_handler->init();
        $this->assets_loader->init();
        
        // Add product ID to JS on product pages
        add_action('wp_footer', array($this, 'add_product_id_to_js'));
    }
    
    /**
     * Render the player shortcode
     * 
     * @param array $atts Shortcode attributes
     * @return string HTML output
     */
    public function render_shortcode($atts) {
        // Parse attributes
        $atts = shortcode_atts(array(
            'product_id' => 0,
            'autoplay' => 'false',
            'class' => '',
            'style' => ''
        ), $atts, 'yoyaku_player_v3');
        
        // Validate product ID
        if (!$atts['product_id']) {
            return $this->render_error(__('Please specify a product_id', 'yoyaku-player-v3'));
        }
        
        // Get product
        $product = wc_get_product($atts['product_id']);
        if (!$product) {
            return $this->render_error(__('Product not found', 'yoyaku-player-v3'));
        }
        
        // Allow filtering of attributes
        $atts = apply_filters('yoyaku_player_v3_shortcode_atts', $atts, $product);
        
        // Load template
        return $this->load_template('player-template', $atts, $product);
    }
    
    /**
     * Load a template file
     * 
     * @param string $template Template name without .php
     * @param array $atts Shortcode attributes
     * @param WC_Product $product Product object
     * @return string HTML output
     */
    private function load_template($template, $atts, $product) {
        // Check for template override in theme
        $theme_template = get_stylesheet_directory() . '/yoyaku-player-v3/' . $template . '.php';
        $plugin_template = YPV3_PLUGIN_DIR . 'templates/' . $template . '.php';
        
        // Use theme template if exists, otherwise use plugin template
        $template_path = file_exists($theme_template) ? $theme_template : $plugin_template;
        
        // Allow filtering of template path
        $template_path = apply_filters('yoyaku_player_v3_template_path', $template_path, $template);
        
        // Start output buffering
        ob_start();
        
        // Make variables available to template
        $player_atts = $atts;
        $player_product = $product;
        
        // Include template
        if (file_exists($template_path)) {
            include $template_path;
        } else {
            echo $this->render_error(__('Template not found', 'yoyaku-player-v3'));
        }
        
        // Get buffered content
        $html = ob_get_clean();
        
        // Allow filtering of output
        return apply_filters('yoyaku_player_v3_template_output', $html, $template, $atts, $product);
    }
    
    /**
     * Render an error message
     * 
     * @param string $message Error message
     * @return string HTML error output
     */
    private function render_error($message) {
        return sprintf(
            '<div class="yoyaku-player-error">%s</div>',
            esc_html($message)
        );
    }
    
    /**
     * Add product ID to JavaScript on product pages
     */
    public function add_product_id_to_js() {
        // Only on single product pages
        if (!is_product()) {
            return;
        }
        
        global $product;
        if (!$product || !is_object($product)) {
            return;
        }
        
        $product_id = $product->get_id();
        if (!$product_id) {
            return;
        }
        
        // Output inline script
        ?>
        <script type="text/javascript">
        /* YOYAKU Player V3 - Product Context */
        if (window.yoyaku_player_v3) {
            window.yoyaku_player_v3.current_page_product_id = <?php echo intval($product_id); ?>;
        }
        </script>
        <?php
    }
}