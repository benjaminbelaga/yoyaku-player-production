<?php
/**
 * YOYAKU Compatibility Checker - Enterprise Grade
 * 
 * Ensures compatibility with WordPress versions, plugins, and themes
 * Provides fallback mechanisms and graceful degradation
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
 * Comprehensive compatibility checker
 * 
 * Singleton pattern for enterprise reliability
 */
final class YOYAKU_Compatibility {
    
    /**
     * Singleton instance
     * 
     * @var YOYAKU_Compatibility|null
     */
    private static $instance = null;
    
    /**
     * Compatibility requirements
     * 
     * @var array
     */
    private $requirements = array();
    
    /**
     * Compatibility test results
     * 
     * @var array
     */
    private $test_results = array();
    
    /**
     * Active fallbacks
     * 
     * @var array
     */
    private $active_fallbacks = array();
    
    /**
     * Critical compatibility issues
     * 
     * @var array
     */
    private $critical_issues = array();
    
    /**
     * Get singleton instance
     * 
     * @return YOYAKU_Compatibility
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
        $this->define_requirements();
        $this->init_compatibility_checks();
        $this->setup_fallback_mechanisms();
        $this->register_admin_interfaces();
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
     * Define compatibility requirements
     */
    private function define_requirements() {
        $this->requirements = array(
            'wordpress' => array(
                'min_version' => '5.0',
                'recommended_version' => '6.0',
                'max_tested_version' => '6.4',
                'required' => true
            ),
            'php' => array(
                'min_version' => '7.4',
                'recommended_version' => '8.0',
                'max_tested_version' => '8.3',
                'required' => true,
                'extensions' => array('curl', 'gd', 'json', 'mbstring')
            ),
            'plugins' => array(
                'woocommerce' => array(
                    'min_version' => '5.0',
                    'recommended_version' => '8.0',
                    'required' => true,
                    'file' => 'woocommerce/woocommerce.php',
                    'class' => 'WooCommerce'
                ),
                'fwa-player' => array(
                    'min_version' => '1.0',
                    'required' => false,
                    'file' => 'fwa-player/fwa-player.php',
                    'fallback' => 'basic_audio_player'
                ),
                'ti-woocommerce-wishlist' => array(
                    'min_version' => '2.0',
                    'required' => false,
                    'file' => 'ti-woocommerce-wishlist-premium/ti-woocommerce-wishlist-premium.php',
                    'class' => 'TInvWL_Plugin_Wishlist',
                    'fallback' => 'session_wishlist'
                )
            ),
            'server' => array(
                'memory_limit' => '256M',
                'max_execution_time' => 300,
                'upload_max_filesize' => '32M'
            )
        );
    }
    
    /**
     * Initialize compatibility checking system
     */
    private function init_compatibility_checks() {
        add_action('admin_init', array($this, 'run_compatibility_checks'), 5);
        add_action('wp_loaded', array($this, 'run_runtime_checks'), 10);
        add_action('admin_notices', array($this, 'display_compatibility_notices'));
        
        // AJAX compatibility checker
        add_action('wp_ajax_yoyaku_run_compatibility_check', array($this, 'ajax_compatibility_check'));
        
        // Scheduled compatibility monitoring
        if (!wp_next_scheduled('yoyaku_compatibility_monitor')) {
            wp_schedule_event(time(), 'daily', 'yoyaku_compatibility_monitor');
        }
        add_action('yoyaku_compatibility_monitor', array($this, 'scheduled_compatibility_check'));
    }
    
    /**
     * Setup fallback mechanisms
     */
    private function setup_fallback_mechanisms() {
        // WooCommerce fallbacks
        if (!$this->is_plugin_compatible('woocommerce')) {
            add_action('init', array($this, 'activate_woocommerce_fallbacks'));
        }
        
        // Audio player fallbacks
        if (!$this->is_plugin_compatible('fwa-player')) {
            add_action('init', array($this, 'activate_audio_fallbacks'));
        }
        
        // Wishlist fallbacks
        if (!$this->is_plugin_compatible('ti-woocommerce-wishlist')) {
            add_action('init', array($this, 'activate_wishlist_fallbacks'));
        }
        
        // PHP version fallbacks
        if (!$this->is_php_compatible()) {
            add_action('init', array($this, 'activate_php_fallbacks'));
        }
    }
    
    /**
     * Register admin interfaces
     */
    private function register_admin_interfaces() {
        if (current_user_can('manage_options')) {
            add_action('admin_menu', array($this, 'add_compatibility_menu'));
            add_action('wp_dashboard_setup', array($this, 'add_dashboard_widget'));
        }
    }
    
    /**
     * Run comprehensive compatibility checks
     */
    public function run_compatibility_checks() {
        $this->test_results = array();
        
        // Check WordPress compatibility
        $this->check_wordpress_compatibility();
        
        // Check PHP compatibility
        $this->check_php_compatibility();
        
        // Check plugin compatibility
        $this->check_plugin_compatibility();
        
        // Check server environment
        $this->check_server_compatibility();
        
        // Check theme compatibility
        $this->check_theme_compatibility();
        
        // Store results
        update_option('yoyaku_compatibility_results', $this->test_results);
        update_option('yoyaku_compatibility_last_check', current_time('timestamp'));
        
        yoyaku_log_debug('Compatibility checks completed', $this->test_results);
    }
    
    /**
     * Check WordPress core compatibility
     */
    private function check_wordpress_compatibility() {
        global $wp_version;
        
        $wp_req = $this->requirements['wordpress'];
        
        $result = array(
            'component' => 'WordPress Core',
            'current_version' => $wp_version,
            'min_version' => $wp_req['min_version'],
            'recommended_version' => $wp_req['recommended_version'],
            'status' => 'unknown',
            'issues' => array()
        );
        
        if (version_compare($wp_version, $wp_req['min_version'], '<')) {
            $result['status'] = 'critical';
            $result['issues'][] = sprintf(
                /* translators: 1: current version, 2: minimum version */
                esc_html__('WordPress %1$s is below minimum required version %2$s', 'yoyaku'),
                $wp_version,
                $wp_req['min_version']
            );
            $this->critical_issues[] = $result;
        } elseif (version_compare($wp_version, $wp_req['recommended_version'], '<')) {
            $result['status'] = 'warning';
            $result['issues'][] = sprintf(
                /* translators: 1: current version, 2: recommended version */
                esc_html__('WordPress %1$s is below recommended version %2$s', 'yoyaku'),
                $wp_version,
                $wp_req['recommended_version']
            );
        } else {
            $result['status'] = 'pass';
        }
        
        // Check for WordPress features
        $required_features = array('post_thumbnails', 'title_tag', 'html5');
        foreach ($required_features as $feature) {
            if (!current_theme_supports($feature)) {
                $result['issues'][] = sprintf(
                    /* translators: %s: feature name */
                    esc_html__('Missing WordPress feature support: %s', 'yoyaku'),
                    $feature
                );
                if ($result['status'] === 'pass') {
                    $result['status'] = 'warning';
                }
            }
        }
        
        $this->test_results['wordpress'] = $result;
    }
    
    /**
     * Check PHP compatibility
     */
    private function check_php_compatibility() {
        $php_req = $this->requirements['php'];
        
        $result = array(
            'component' => 'PHP',
            'current_version' => PHP_VERSION,
            'min_version' => $php_req['min_version'],
            'recommended_version' => $php_req['recommended_version'],
            'status' => 'unknown',
            'issues' => array()
        );
        
        if (version_compare(PHP_VERSION, $php_req['min_version'], '<')) {
            $result['status'] = 'critical';
            $result['issues'][] = sprintf(
                /* translators: 1: current version, 2: minimum version */
                esc_html__('PHP %1$s is below minimum required version %2$s', 'yoyaku'),
                PHP_VERSION,
                $php_req['min_version']
            );
            $this->critical_issues[] = $result;
        } elseif (version_compare(PHP_VERSION, $php_req['recommended_version'], '<')) {
            $result['status'] = 'warning';
            $result['issues'][] = sprintf(
                /* translators: 1: current version, 2: recommended version */
                esc_html__('PHP %1$s is below recommended version %2$s', 'yoyaku'),
                PHP_VERSION,
                $php_req['recommended_version']
            );
        } else {
            $result['status'] = 'pass';
        }
        
        // Check PHP extensions
        foreach ($php_req['extensions'] as $extension) {
            if (!extension_loaded($extension)) {
                $result['issues'][] = sprintf(
                    /* translators: %s: extension name */
                    esc_html__('Missing PHP extension: %s', 'yoyaku'),
                    $extension
                );
                if ($result['status'] === 'pass') {
                    $result['status'] = 'warning';
                }
            }
        }
        
        $this->test_results['php'] = $result;
    }
    
    /**
     * Check plugin compatibility
     */
    private function check_plugin_compatibility() {
        foreach ($this->requirements['plugins'] as $plugin_id => $plugin_req) {
            $result = array(
                'component' => $plugin_req['file'],
                'plugin_id' => $plugin_id,
                'required' => $plugin_req['required'],
                'status' => 'unknown',
                'issues' => array()
            );
            
            if (!yoyaku_is_plugin_active($plugin_req['file'])) {
                if ($plugin_req['required']) {
                    $result['status'] = 'critical';
                    $result['issues'][] = sprintf(
                        /* translators: %s: plugin name */
                        esc_html__('Required plugin not active: %s', 'yoyaku'),
                        $plugin_id
                    );
                    $this->critical_issues[] = $result;
                } else {
                    $result['status'] = 'info';
                    $result['issues'][] = sprintf(
                        /* translators: %s: plugin name */
                        esc_html__('Optional plugin not active: %s (fallback will be used)', 'yoyaku'),
                        $plugin_id
                    );
                    
                    // Activate fallback if available
                    if (isset($plugin_req['fallback'])) {
                        $this->activate_plugin_fallback($plugin_id, $plugin_req['fallback']);
                    }
                }
            } else {
                // Check plugin version if specified
                if (isset($plugin_req['min_version'])) {
                    $plugin_version = $this->get_plugin_version($plugin_req['file']);
                    if ($plugin_version && version_compare($plugin_version, $plugin_req['min_version'], '<')) {
                        $result['status'] = 'warning';
                        $result['issues'][] = sprintf(
                            /* translators: 1: plugin name, 2: current version, 3: minimum version */
                            esc_html__('Plugin %1$s version %2$s is below minimum %3$s', 'yoyaku'),
                            $plugin_id,
                            $plugin_version,
                            $plugin_req['min_version']
                        );
                    } else {
                        $result['status'] = 'pass';
                        $result['current_version'] = $plugin_version;
                    }
                } else {
                    $result['status'] = 'pass';
                }
                
                // Check plugin class if specified
                if (isset($plugin_req['class']) && !class_exists($plugin_req['class'])) {
                    $result['issues'][] = sprintf(
                        /* translators: 1: class name, 2: plugin name */
                        esc_html__('Plugin class %1$s not found for %2$s', 'yoyaku'),
                        $plugin_req['class'],
                        $plugin_id
                    );
                    if ($result['status'] === 'pass') {
                        $result['status'] = 'warning';
                    }
                }
            }
            
            $this->test_results['plugins'][$plugin_id] = $result;
        }
    }
    
    /**
     * Check server environment compatibility
     */
    private function check_server_compatibility() {
        $server_req = $this->requirements['server'];
        
        $result = array(
            'component' => 'Server Environment',
            'status' => 'pass',
            'issues' => array()
        );
        
        // Check memory limit
        $memory_limit = $this->parse_size(ini_get('memory_limit'));
        $required_memory = $this->parse_size($server_req['memory_limit']);
        
        if ($memory_limit < $required_memory) {
            $result['status'] = 'warning';
            $result['issues'][] = sprintf(
                /* translators: 1: current limit, 2: required limit */
                esc_html__('Memory limit %1$s is below recommended %2$s', 'yoyaku'),
                ini_get('memory_limit'),
                $server_req['memory_limit']
            );
        }
        
        // Check execution time
        $max_execution_time = (int) ini_get('max_execution_time');
        if ($max_execution_time > 0 && $max_execution_time < $server_req['max_execution_time']) {
            $result['status'] = 'warning';
            $result['issues'][] = sprintf(
                /* translators: 1: current time, 2: required time */
                esc_html__('Max execution time %1$ds is below recommended %2$ds', 'yoyaku'),
                $max_execution_time,
                $server_req['max_execution_time']
            );
        }
        
        // Check upload file size
        $upload_max_filesize = $this->parse_size(ini_get('upload_max_filesize'));
        $required_upload = $this->parse_size($server_req['upload_max_filesize']);
        
        if ($upload_max_filesize < $required_upload) {
            $result['status'] = 'warning';
            $result['issues'][] = sprintf(
                /* translators: 1: current size, 2: required size */
                esc_html__('Upload max filesize %1$s is below recommended %2$s', 'yoyaku'),
                ini_get('upload_max_filesize'),
                $server_req['upload_max_filesize']
            );
        }
        
        $this->test_results['server'] = $result;
    }
    
    /**
     * Check theme compatibility
     */
    private function check_theme_compatibility() {
        $result = array(
            'component' => 'Theme',
            'status' => 'pass',
            'issues' => array()
        );
        
        // Check if this is a child theme
        if (!is_child_theme()) {
            $result['status'] = 'warning';
            $result['issues'][] = esc_html__('Not using a child theme - updates will override customizations', 'yoyaku');
        }
        
        // Check for required theme files
        $required_files = array(
            'style.css',
            'functions.php',
            'index.php'
        );
        
        foreach ($required_files as $file) {
            $file_path = get_template_directory() . '/' . $file;
            if (!file_exists($file_path)) {
                $result['status'] = 'critical';
                $result['issues'][] = sprintf(
                    /* translators: %s: file name */
                    esc_html__('Missing required theme file: %s', 'yoyaku'),
                    $file
                );
                $this->critical_issues[] = $result;
            }
        }
        
        // Check theme supports
        $required_supports = array('post-thumbnails', 'title-tag');
        foreach ($required_supports as $support) {
            if (!current_theme_supports($support)) {
                $result['issues'][] = sprintf(
                    /* translators: %s: support name */
                    esc_html__('Missing theme support: %s', 'yoyaku'),
                    $support
                );
                if ($result['status'] === 'pass') {
                    $result['status'] = 'warning';
                }
            }
        }
        
        $this->test_results['theme'] = $result;
    }
    
    /**
     * Run runtime compatibility checks
     */
    public function run_runtime_checks() {
        // Check for plugin conflicts
        $this->check_plugin_conflicts();
        
        // Check for JavaScript errors
        $this->setup_js_error_monitoring();
        
        // Check for CSS conflicts
        $this->check_css_conflicts();
    }
    
    /**
     * Check for plugin conflicts
     */
    private function check_plugin_conflicts() {
        $known_conflicts = array(
            'cache' => array(
                'wp-rocket/wp-rocket.php',
                'w3-total-cache/w3-total-cache.php',
                'wp-super-cache/wp-cache.php'
            ),
            'security' => array(
                'wordfence/wordfence.php',
                'all-in-one-wp-security-and-firewall/wp-security.php'
            )
        );
        
        foreach ($known_conflicts as $category => $plugins) {
            $active_conflicting = array();
            foreach ($plugins as $plugin) {
                if (yoyaku_is_plugin_active($plugin)) {
                    $active_conflicting[] = $plugin;
                }
            }
            
            if (count($active_conflicting) > 1) {
                yoyaku_log_error(
                    sprintf(
                        /* translators: 1: category, 2: plugin list */
                        esc_html__('Potential %1$s plugin conflict detected: %2$s', 'yoyaku'),
                        $category,
                        implode(', ', $active_conflicting)
                    ),
                    'warning'
                );
            }
        }
    }
    
    /**
     * Setup JavaScript error monitoring
     */
    private function setup_js_error_monitoring() {
        if (!is_admin()) {
            add_action('wp_footer', function() {
                ?>
                <script>
                window.addEventListener('error', function(e) {
                    if (typeof jQuery !== 'undefined') {
                        jQuery.post('<?php echo admin_url('admin-ajax.php'); ?>', {
                            action: 'yoyaku_js_error_report',
                            message: e.message,
                            filename: e.filename,
                            lineno: e.lineno,
                            colno: e.colno,
                            nonce: '<?php echo wp_create_nonce('yoyaku_js_error'); ?>'
                        });
                    }
                });
                </script>
                <?php
            });
        }
    }
    
    /**
     * Check for CSS conflicts
     */
    private function check_css_conflicts() {
        // This would be implemented with additional JavaScript monitoring
        // for CSS-related issues like missing styles, layout breaks, etc.
    }
    
    /**
     * Get plugin version
     * 
     * @param string $plugin_file Plugin file path
     * @return string|null Plugin version
     */
    private function get_plugin_version($plugin_file) {
        if (!function_exists('get_plugin_data')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }
        
        $plugin_path = WP_PLUGIN_DIR . '/' . $plugin_file;
        if (file_exists($plugin_path)) {
            $plugin_data = get_plugin_data($plugin_path);
            return $plugin_data['Version'] ?? null;
        }
        
        return null;
    }
    
    /**
     * Parse size string to bytes
     * 
     * @param string $size Size string (e.g., "256M")
     * @return int Size in bytes
     */
    private function parse_size($size) {
        $size = trim($size);
        $last = strtolower($size[strlen($size) - 1]);
        $size = (int) $size;
        
        switch ($last) {
            case 'g':
                $size *= 1024;
            case 'm':
                $size *= 1024;
            case 'k':
                $size *= 1024;
        }
        
        return $size;
    }
    
    /**
     * Check if plugin is compatible
     * 
     * @param string $plugin_id Plugin identifier
     * @return bool Plugin is compatible
     */
    public function is_plugin_compatible($plugin_id) {
        if (!isset($this->requirements['plugins'][$plugin_id])) {
            return true; // Unknown plugins are assumed compatible
        }
        
        $plugin_req = $this->requirements['plugins'][$plugin_id];
        
        if (!yoyaku_is_plugin_active($plugin_req['file'])) {
            return !$plugin_req['required']; // OK if not required
        }
        
        if (isset($plugin_req['min_version'])) {
            $version = $this->get_plugin_version($plugin_req['file']);
            if ($version && version_compare($version, $plugin_req['min_version'], '<')) {
                return false;
            }
        }
        
        if (isset($plugin_req['class']) && !class_exists($plugin_req['class'])) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Check if PHP is compatible
     * 
     * @return bool PHP is compatible
     */
    public function is_php_compatible() {
        $php_req = $this->requirements['php'];
        return version_compare(PHP_VERSION, $php_req['min_version'], '>=');
    }
    
    /**
     * Activate plugin fallback
     * 
     * @param string $plugin_id Plugin identifier
     * @param string $fallback_type Fallback type
     */
    private function activate_plugin_fallback($plugin_id, $fallback_type) {
        $fallback_path = YOYAKU_INC_PATH . '/fallbacks/' . $fallback_type . '.php';
        
        if (file_exists($fallback_path)) {
            include_once $fallback_path;
            $this->active_fallbacks[$plugin_id] = $fallback_type;
            
            yoyaku_log_debug(
                sprintf('Activated fallback %s for plugin %s', $fallback_type, $plugin_id)
            );
        }
    }
    
    /**
     * Activate WooCommerce fallbacks
     */
    public function activate_woocommerce_fallbacks() {
        $this->activate_plugin_fallback('woocommerce', 'woocommerce_basic');
    }
    
    /**
     * Activate audio fallbacks
     */
    public function activate_audio_fallbacks() {
        $this->activate_plugin_fallback('fwa-player', 'basic_audio_player');
    }
    
    /**
     * Activate wishlist fallbacks
     */
    public function activate_wishlist_fallbacks() {
        $this->activate_plugin_fallback('ti-woocommerce-wishlist', 'session_wishlist');
    }
    
    /**
     * Activate PHP version fallbacks
     */
    public function activate_php_fallbacks() {
        // Load PHP compatibility functions
        $compat_file = YOYAKU_INC_PATH . '/compatibility/php-compat.php';
        if (file_exists($compat_file)) {
            include_once $compat_file;
        }
    }
    
    /**
     * Display compatibility notices
     */
    public function display_compatibility_notices() {
        if (!current_user_can('manage_options')) {
            return;
        }
        
        if (!empty($this->critical_issues)) {
            foreach ($this->critical_issues as $issue) {
                printf(
                    '<div class="notice notice-error"><p><strong>%1$s:</strong> %2$s</p></div>',
                    esc_html__('YOYAKU Compatibility Error', 'yoyaku'),
                    esc_html(implode(' | ', $issue['issues']))
                );
            }
        }
        
        // Show fallback notifications
        if (!empty($this->active_fallbacks)) {
            $fallback_list = implode(', ', array_keys($this->active_fallbacks));
            printf(
                '<div class="notice notice-info is-dismissible"><p><strong>%1$s:</strong> %2$s: %3$s</p></div>',
                esc_html__('YOYAKU Compatibility', 'yoyaku'),
                esc_html__('Using fallbacks for', 'yoyaku'),
                esc_html($fallback_list)
            );
        }
    }
    
    /**
     * AJAX compatibility check
     */
    public function ajax_compatibility_check() {
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'yoyaku_compatibility_check')) {
            wp_send_json_error(array('message' => 'Invalid nonce'));
            return;
        }
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Insufficient permissions'));
            return;
        }
        
        $this->run_compatibility_checks();
        
        wp_send_json_success(array(
            'results' => $this->test_results,
            'critical_issues' => count($this->critical_issues),
            'active_fallbacks' => $this->active_fallbacks
        ));
    }
    
    /**
     * Scheduled compatibility check
     */
    public function scheduled_compatibility_check() {
        $this->run_compatibility_checks();
        
        // Send alert if new critical issues found
        if (!empty($this->critical_issues)) {
            $admin_email = get_option('admin_email');
            if ($admin_email) {
                wp_mail(
                    $admin_email,
                    esc_html__('YOYAKU Compatibility Issues Detected', 'yoyaku'),
                    sprintf(
                        /* translators: %d: number of issues */
                        esc_html__('%d critical compatibility issues detected. Please check your WordPress admin.', 'yoyaku'),
                        count($this->critical_issues)
                    )
                );
            }
        }
    }
    
    /**
     * Add compatibility menu to admin
     */
    public function add_compatibility_menu() {
        add_management_page(
            esc_html__('YOYAKU Compatibility', 'yoyaku'),
            esc_html__('YOYAKU Compatibility', 'yoyaku'),
            'manage_options',
            'yoyaku-compatibility',
            array($this, 'display_compatibility_page')
        );
    }
    
    /**
     * Add dashboard widget
     */
    public function add_dashboard_widget() {
        wp_add_dashboard_widget(
            'yoyaku_compatibility_widget',
            esc_html__('YOYAKU Compatibility Status', 'yoyaku'),
            array($this, 'display_dashboard_widget')
        );
    }
    
    /**
     * Display compatibility admin page
     */
    public function display_compatibility_page() {
        $results = get_option('yoyaku_compatibility_results', array());
        $last_check = get_option('yoyaku_compatibility_last_check', 0);
        
        include_once YOYAKU_INC_PATH . '/admin/compatibility-page.php';
    }
    
    /**
     * Display dashboard widget
     */
    public function display_dashboard_widget() {
        $results = get_option('yoyaku_compatibility_results', array());
        $critical_count = count($this->critical_issues);
        $fallback_count = count($this->active_fallbacks);
        
        printf(
            '<p>%1$s: <strong>%2$s</strong></p>',
            esc_html__('Critical Issues', 'yoyaku'),
            $critical_count > 0 ? 
                sprintf(esc_html(_n('%d issue', '%d issues', $critical_count, 'yoyaku')), $critical_count) :
                esc_html__('None', 'yoyaku')
        );
        
        printf(
            '<p>%1$s: <strong>%2$s</strong></p>',
            esc_html__('Active Fallbacks', 'yoyaku'),
            $fallback_count > 0 ? 
                sprintf(esc_html(_n('%d fallback', '%d fallbacks', $fallback_count, 'yoyaku')), $fallback_count) :
                esc_html__('None', 'yoyaku')
        );
        
        printf(
            '<p><a href="%1$s" class="button button-secondary">%2$s</a></p>',
            admin_url('tools.php?page=yoyaku-compatibility'),
            esc_html__('View Details', 'yoyaku')
        );
    }
    
    /**
     * Get compatibility status summary
     * 
     * @return array Status summary
     */
    public function get_status_summary() {
        return array(
            'overall_status' => empty($this->critical_issues) ? 'compatible' : 'incompatible',
            'critical_issues' => count($this->critical_issues),
            'warnings' => $this->count_warnings(),
            'active_fallbacks' => count($this->active_fallbacks),
            'last_check' => get_option('yoyaku_compatibility_last_check', 0)
        );
    }
    
    /**
     * Count warning-level issues
     * 
     * @return int Number of warnings
     */
    private function count_warnings() {
        $warning_count = 0;
        foreach ($this->test_results as $category => $results) {
            if (is_array($results)) {
                foreach ($results as $result) {
                    if (isset($result['status']) && $result['status'] === 'warning') {
                        $warning_count++;
                    }
                }
            } elseif (isset($results['status']) && $results['status'] === 'warning') {
                $warning_count++;
            }
        }
        return $warning_count;
    }
}