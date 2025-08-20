<?php
/**
 * YOYAKU Theme Loader - Enterprise Grade
 * 
 * Handles all theme initialization with fail-safe mechanisms
 * 100% WordPress Standards Compliant
 * 
 * @package YOYAKU
 * @since 2.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    wp_die(
        esc_html__('Direct access denied.', 'yoyaku'),
        esc_html__('Security Error', 'yoyaku'),
        array('response' => 403)
    );
}

/**
 * Main theme loader class
 * 
 * Singleton pattern for enterprise reliability
 */
final class YOYAKU_Theme_Loader {
    
    /**
     * Singleton instance
     * 
     * @var YOYAKU_Theme_Loader|null
     */
    private static $instance = null;
    
    /**
     * Loaded modules tracking
     * 
     * @var array
     */
    private $loaded_modules = array();
    
    /**
     * Failed modules tracking
     * 
     * @var array
     */
    private $failed_modules = array();
    
    /**
     * Theme compatibility status
     * 
     * @var bool
     */
    private $compatibility_checked = false;
    
    /**
     * Get singleton instance
     * 
     * @return YOYAKU_Theme_Loader
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Private constructor - Singleton pattern
     */
    private function __construct() {
        $this->init_hooks();
        $this->check_environment();
        $this->load_core_modules();
    }
    
    /**
     * Prevent cloning - Singleton pattern
     */
    private function __clone() {}
    
    /**
     * Prevent unserialization - Singleton pattern
     */
    public function __wakeup() {}
    
    /**
     * Initialize WordPress hooks
     */
    private function init_hooks() {
        add_action('after_setup_theme', array($this, 'setup_theme_features'), 10);
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'), 10);
        add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_scripts'), 10);
        add_action('init', array($this, 'load_textdomain'), 5);
        add_action('wp_loaded', array($this, 'load_conditional_modules'), 15);
    }
    
    /**
     * Check environment compatibility
     */
    private function check_environment() {
        $requirements = array(
            'php_version' => '7.4',
            'wp_version' => '5.0',
            'required_plugins' => array(
                'woocommerce/woocommerce.php' => 'WooCommerce'
            )
        );
        
        // Check PHP version
        if (version_compare(PHP_VERSION, $requirements['php_version'], '<')) {
            $this->handle_compatibility_error(
                sprintf(
                    /* translators: 1: current PHP version, 2: required PHP version */
                    esc_html__('PHP version %1$s is too old. Please upgrade to %2$s or higher.', 'yoyaku'),
                    PHP_VERSION,
                    $requirements['php_version']
                )
            );
            return;
        }
        
        // Check WordPress version
        global $wp_version;
        if (version_compare($wp_version, $requirements['wp_version'], '<')) {
            $this->handle_compatibility_error(
                sprintf(
                    /* translators: 1: current WP version, 2: required WP version */
                    esc_html__('WordPress version %1$s is too old. Please upgrade to %2$s or higher.', 'yoyaku'),
                    $wp_version,
                    $requirements['wp_version']
                )
            );
            return;
        }
        
        // Check required plugins
        foreach ($requirements['required_plugins'] as $plugin_path => $plugin_name) {
            if (!yoyaku_is_plugin_active($plugin_path)) {
                yoyaku_log_error(
                    sprintf(
                        /* translators: %s: plugin name */
                        esc_html__('Required plugin not active: %s', 'yoyaku'),
                        $plugin_name
                    ),
                    'warning'
                );
            }
        }
        
        $this->compatibility_checked = true;
    }
    
    /**
     * Handle compatibility errors
     * 
     * @param string $message Error message
     */
    private function handle_compatibility_error($message) {
        yoyaku_log_error($message, 'critical');
        
        if (current_user_can('manage_options')) {
            add_action('admin_notices', function() use ($message) {
                printf(
                    '<div class="notice notice-error"><p><strong>%1$s:</strong> %2$s</p></div>',
                    esc_html__('YOYAKU Theme Compatibility Error', 'yoyaku'),
                    esc_html($message)
                );
            });
        }
    }
    
    /**
     * Load core theme modules
     */
    private function load_core_modules() {
        $core_modules = array(
            'error-handler' => array(
                'file' => 'class-yoyaku-error-handler.php',
                'class' => 'YOYAKU_Error_Handler',
                'required' => true
            ),
            'helpers' => array(
                'file' => 'helpers.php',
                'required' => false
            ),
            'template-tags' => array(
                'file' => 'template-tags.php',
                'required' => false
            ),
            'template-actions' => array(
                'file' => 'template-actions.php',
                'required' => false
            )
        );
        
        foreach ($core_modules as $module_id => $module_config) {
            $this->load_module($module_id, $module_config);
        }
    }
    
    /**
     * Load a single module with error handling
     * 
     * @param string $module_id Module identifier
     * @param array $module_config Module configuration
     * @return bool Success status
     */
    private function load_module($module_id, $module_config) {
        $file_path = YOYAKU_INC_PATH . '/' . $module_config['file'];
        $required = isset($module_config['required']) ? $module_config['required'] : false;
        $dependencies = isset($module_config['dependencies']) ? $module_config['dependencies'] : array();
        
        $result = yoyaku_safe_require($file_path, $required, $dependencies);
        
        if (is_wp_error($result)) {
            $this->failed_modules[$module_id] = array(
                'error' => $result->get_error_message(),
                'file' => $file_path,
                'required' => $required
            );
            
            yoyaku_log_error(
                sprintf(
                    /* translators: 1: module ID, 2: error message */
                    esc_html__('Failed to load module %1$s: %2$s', 'yoyaku'),
                    $module_id,
                    $result->get_error_message()
                ),
                $required ? 'critical' : 'warning'
            );
            
            return false;
        }
        
        // Initialize module class if specified
        if (isset($module_config['class']) && class_exists($module_config['class'])) {
            $class_name = $module_config['class'];
            if (method_exists($class_name, 'get_instance')) {
                $class_name::get_instance();
            } elseif (method_exists($class_name, '__construct')) {
                new $class_name();
            }
        }
        
        $this->loaded_modules[$module_id] = array(
            'file' => $file_path,
            'class' => isset($module_config['class']) ? $module_config['class'] : null,
            'loaded_at' => current_time('timestamp')
        );
        
        yoyaku_log_debug(sprintf('Successfully loaded module: %s', $module_id));
        
        return true;
    }
    
    /**
     * Setup WordPress theme features
     */
    public function setup_theme_features() {
        // Theme supports
        add_theme_support('title-tag');
        add_theme_support('post-thumbnails');
        add_theme_support('html5', array(
            'search-form',
            'comment-form',
            'comment-list',
            'gallery',
            'caption',
            'style',
            'script'
        ));
        add_theme_support('customize-selective-refresh-widgets');
        
        // WooCommerce support
        if (class_exists('WooCommerce')) {
            add_theme_support('woocommerce');
            add_theme_support('wc-product-gallery-zoom');
            add_theme_support('wc-product-gallery-lightbox');
            add_theme_support('wc-product-gallery-slider');
        }
        
        // Register navigation menus
        register_nav_menus(array(
            'primary' => esc_html__('Primary Menu', 'yoyaku'),
            'footer'  => esc_html__('Footer Menu', 'yoyaku'),
        ));
        
        // Content width
        if (!isset($GLOBALS['content_width'])) {
            $GLOBALS['content_width'] = 1200;
        }
        
        yoyaku_log_debug('Theme features setup completed');
    }
    
    /**
     * Load theme textdomain
     */
    public function load_textdomain() {
        $result = load_theme_textdomain('yoyaku', get_template_directory() . '/languages');
        
        if (!$result) {
            yoyaku_log_error('Failed to load theme textdomain', 'warning');
        } else {
            yoyaku_log_debug('Theme textdomain loaded successfully');
        }
    }
    
    /**
     * Enqueue frontend scripts and styles
     */
    public function enqueue_scripts() {
        // Theme stylesheet
        wp_enqueue_style(
            'yoyaku-style',
            get_stylesheet_uri(),
            array(),
            YOYAKU_THEME_VERSION
        );
        
        // Load conditional modules assets
        $this->enqueue_module_assets();
        
        yoyaku_log_debug('Frontend scripts enqueued');
    }
    
    /**
     * Enqueue admin scripts and styles
     */
    public function admin_enqueue_scripts($hook) {
        // Admin stylesheet
        wp_enqueue_style(
            'yoyaku-admin-style',
            YOYAKU_THEME_URI . '/assets/css/admin.css',
            array(),
            YOYAKU_THEME_VERSION
        );
        
        yoyaku_log_debug('Admin scripts enqueued for: ' . $hook);
    }
    
    /**
     * Load conditional modules based on context
     */
    public function load_conditional_modules() {
        $conditional_modules = array();
        
        // WooCommerce modules
        if (class_exists('WooCommerce')) {
            $conditional_modules['woocommerce'] = array(
                'file' => 'woocommerce/init.php',
                'dependencies' => array('class:WooCommerce'),
                'required' => false
            );
        }
        
        // Audio engine (if FWA Player is active)
        if (yoyaku_is_plugin_active('fwa-player/fwa-player.php')) {
            $conditional_modules['audio-engine'] = array(
                'file' => 'audio-engine/class-audio-manager.php',
                'class' => 'YOYAKU_Audio_Manager',
                'required' => false
            );
        }
        
        // Wishlist system (if TI WooCommerce Wishlist is active)
        if (class_exists('TInvWL_Plugin_Wishlist')) {
            $conditional_modules['wishlist-system'] = array(
                'file' => 'woocommerce/wishlist-system-v2/core/EmailQueueManager.php',
                'class' => 'EmailQueueManager',
                'required' => false
            );
        }
        
        // Performance monitoring (admin only)
        if (is_admin() && current_user_can('manage_options')) {
            $conditional_modules['performance-monitor'] = array(
                'file' => 'performance-monitoring.php',
                'required' => false
            );
        }
        
        // Load all conditional modules
        foreach ($conditional_modules as $module_id => $module_config) {
            $this->load_module($module_id, $module_config);
        }
        
        yoyaku_log_debug(
            sprintf(
                'Conditional modules loaded: %d successful, %d failed',
                count($this->loaded_modules),
                count($this->failed_modules)
            )
        );
    }
    
    /**
     * Enqueue module-specific assets
     */
    private function enqueue_module_assets() {
        // WooCommerce assets
        if (class_exists('WooCommerce') && isset($this->loaded_modules['woocommerce'])) {
            wp_enqueue_style(
                'yoyaku-woocommerce',
                YOYAKU_THEME_URI . '/assets/css/woocommerce.css',
                array('yoyaku-style'),
                YOYAKU_THEME_VERSION
            );
        }
        
        // Audio engine assets
        if (isset($this->loaded_modules['audio-engine'])) {
            wp_enqueue_script(
                'yoyaku-audio-engine',
                YOYAKU_THEME_URI . '/assets/js/audio-engine.js',
                array('jquery'),
                YOYAKU_THEME_VERSION,
                true
            );
        }
    }
    
    /**
     * Get loaded modules status
     * 
     * @return array Module loading status
     */
    public function get_modules_status() {
        return array(
            'loaded' => $this->loaded_modules,
            'failed' => $this->failed_modules,
            'total_loaded' => count($this->loaded_modules),
            'total_failed' => count($this->failed_modules)
        );
    }
    
    /**
     * Check if a module is loaded
     * 
     * @param string $module_id Module identifier
     * @return bool Module is loaded
     */
    public function is_module_loaded($module_id) {
        return isset($this->loaded_modules[$module_id]);
    }
    
    /**
     * Get module load error
     * 
     * @param string $module_id Module identifier
     * @return string|null Error message or null if no error
     */
    public function get_module_error($module_id) {
        return isset($this->failed_modules[$module_id]) ? 
               $this->failed_modules[$module_id]['error'] : null;
    }
}

// Initialize theme loader hook for external access
if (!function_exists('yoyaku_get_theme_loader')) {
    /**
     * Get theme loader instance
     * 
     * @return YOYAKU_Theme_Loader
     */
    function yoyaku_get_theme_loader() {
        return YOYAKU_Theme_Loader::get_instance();
    }
}