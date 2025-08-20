<?php
/**
 * YOYAKU Error Handler - Enterprise Grade
 * 
 * Handles all errors with WordPress WP_Error integration
 * Provides graceful degradation and recovery mechanisms
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
 * Enhanced error handler with WordPress integration
 * 
 * Singleton pattern for enterprise reliability
 */
final class YOYAKU_Error_Handler {
    
    /**
     * Singleton instance
     * 
     * @var YOYAKU_Error_Handler|null
     */
    private static $instance = null;
    
    /**
     * Error log storage
     * 
     * @var array
     */
    private $error_log = array();
    
    /**
     * Recovery strategies
     * 
     * @var array
     */
    private $recovery_strategies = array();
    
    /**
     * Fatal error handler active
     * 
     * @var bool
     */
    private $fatal_handler_registered = false;
    
    /**
     * Get singleton instance
     * 
     * @return YOYAKU_Error_Handler
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
        $this->init_error_handling();
        $this->register_recovery_strategies();
        $this->setup_wordpress_integration();
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
     * Initialize comprehensive error handling
     */
    private function init_error_handling() {
        // Set custom error handler
        set_error_handler(array($this, 'handle_php_error'), E_ALL & ~E_NOTICE);
        
        // Set custom exception handler
        set_exception_handler(array($this, 'handle_php_exception'));
        
        // Register shutdown function for fatal errors
        register_shutdown_function(array($this, 'handle_fatal_error'));
        
        $this->fatal_handler_registered = true;
        
        // WordPress hooks
        add_action('wp_loaded', array($this, 'setup_wordpress_error_handling'), 5);
        add_action('admin_init', array($this, 'setup_admin_error_display'), 5);
    }
    
    /**
     * Register recovery strategies for different error types
     */
    private function register_recovery_strategies() {
        // WooCommerce recovery strategies
        $this->recovery_strategies['woocommerce'] = array(
            'fallback_template' => 'woocommerce-fallback.php',
            'disable_features' => array('product_gallery', 'zoom', 'lightbox'),
            'alternative_hooks' => array(
                'woocommerce_before_single_product_summary' => 'yoyaku_product_fallback_display'
            )
        );
        
        // Audio engine recovery strategies
        $this->recovery_strategies['audio'] = array(
            'fallback_player' => 'basic-html-audio',
            'disable_features' => array('visualization', 'effects'),
            'alternative_hooks' => array(
                'yoyaku_audio_player' => 'yoyaku_basic_audio_player'
            )
        );
        
        // Wishlist recovery strategies
        $this->recovery_strategies['wishlist'] = array(
            'fallback_storage' => 'session',
            'disable_features' => array('email_notifications', 'sharing'),
            'alternative_hooks' => array(
                'ti_wishlist_item_added' => 'yoyaku_wishlist_fallback_added'
            )
        );
        
        // Database recovery strategies
        $this->recovery_strategies['database'] = array(
            'fallback_queries' => true,
            'cache_results' => true,
            'timeout_protection' => 30
        );
    }
    
    /**
     * Setup WordPress-specific error integration
     */
    private function setup_wordpress_integration() {
        // WordPress fatal error recovery
        if (class_exists('WP_Fatal_Error_Handler')) {
            add_filter('wp_fatal_error_handler_enabled', '__return_true');
        }
        
        // Custom error handling for AJAX requests
        if (wp_doing_ajax()) {
            add_action('wp_ajax_yoyaku_error_report', array($this, 'handle_ajax_error_report'));
            add_action('wp_ajax_nopriv_yoyaku_error_report', array($this, 'handle_ajax_error_report'));
        }
    }
    
    /**
     * Handle PHP errors with WordPress integration
     * 
     * @param int $severity Error severity
     * @param string $message Error message
     * @param string $filename File where error occurred
     * @param int $lineno Line number
     * @return bool True to prevent default PHP error handler
     */
    public function handle_php_error($severity, $message, $filename = '', $lineno = 0) {
        // Don't handle if error reporting is turned off
        if (!(error_reporting() & $severity)) {
            return false;
        }
        
        // Create error context
        $error_context = array(
            'severity' => $severity,
            'message' => $message,
            'filename' => $filename,
            'lineno' => $lineno,
            'timestamp' => current_time('timestamp'),
            'backtrace' => wp_debug_backtrace_summary(),
            'user_id' => get_current_user_id(),
            'request_uri' => isset($_SERVER['REQUEST_URI']) ? esc_url_raw($_SERVER['REQUEST_URI']) : ''
        );
        
        // Determine error level
        $error_level = $this->get_error_level($severity);
        
        // Log error
        $this->log_error($error_context, $error_level);
        
        // Apply recovery strategy if available
        $this->apply_recovery_strategy($error_context);
        
        // Handle critical errors
        if ($error_level === 'critical') {
            $this->handle_critical_error($error_context);
        }
        
        // Return false to allow WordPress to handle the error as well
        return false;
    }
    
    /**
     * Handle PHP exceptions
     * 
     * @param Throwable $exception The uncaught exception
     */
    public function handle_php_exception($exception) {
        $error_context = array(
            'type' => 'exception',
            'class' => get_class($exception),
            'message' => $exception->getMessage(),
            'filename' => $exception->getFile(),
            'lineno' => $exception->getLine(),
            'timestamp' => current_time('timestamp'),
            'backtrace' => $exception->getTraceAsString(),
            'user_id' => get_current_user_id(),
            'request_uri' => isset($_SERVER['REQUEST_URI']) ? esc_url_raw($_SERVER['REQUEST_URI']) : ''
        );
        
        $this->log_error($error_context, 'critical');
        $this->apply_recovery_strategy($error_context);
        $this->handle_critical_error($error_context);
        
        // Display user-friendly error page
        $this->display_error_page($error_context);
    }
    
    /**
     * Handle fatal PHP errors
     */
    public function handle_fatal_error() {
        $last_error = error_get_last();
        
        if ($last_error && in_array($last_error['type'], array(E_ERROR, E_PARSE, E_CORE_ERROR, E_CORE_WARNING, E_COMPILE_ERROR, E_COMPILE_WARNING))) {
            $error_context = array(
                'type' => 'fatal',
                'severity' => $last_error['type'],
                'message' => $last_error['message'],
                'filename' => $last_error['file'],
                'lineno' => $last_error['line'],
                'timestamp' => current_time('timestamp'),
                'user_id' => get_current_user_id()
            );
            
            $this->log_error($error_context, 'critical');
            $this->apply_recovery_strategy($error_context);
            $this->display_fatal_error_page($error_context);
        }
    }
    
    /**
     * Setup WordPress-specific error handling
     */
    public function setup_wordpress_error_handling() {
        // Hook into WordPress error handling
        add_filter('wp_die_ajax_handler', array($this, 'custom_wp_die_ajax_handler'));
        add_filter('wp_die_xmlrpc_handler', array($this, 'custom_wp_die_xmlrpc_handler'));
        add_filter('wp_die_handler', array($this, 'custom_wp_die_handler'));
    }
    
    /**
     * Setup admin error display
     */
    public function setup_admin_error_display() {
        if (current_user_can('manage_options')) {
            add_action('admin_notices', array($this, 'display_admin_error_notices'));
            add_action('admin_menu', array($this, 'add_error_log_menu'));
        }
    }
    
    /**
     * Custom AJAX error handler
     * 
     * @param callable $function Default handler
     * @return callable Custom handler
     */
    public function custom_wp_die_ajax_handler($function) {
        return array($this, 'wp_die_ajax_handler');
    }
    
    /**
     * Custom XML-RPC error handler
     * 
     * @param callable $function Default handler
     * @return callable Custom handler
     */
    public function custom_wp_die_xmlrpc_handler($function) {
        return array($this, 'wp_die_xmlrpc_handler');
    }
    
    /**
     * Custom general error handler
     * 
     * @param callable $function Default handler
     * @return callable Custom handler
     */
    public function custom_wp_die_handler($function) {
        return array($this, 'wp_die_handler');
    }
    
    /**
     * Handle AJAX wp_die
     * 
     * @param string $message Error message
     * @param string $title Error title
     * @param array $args Additional arguments
     */
    public function wp_die_ajax_handler($message = '', $title = '', $args = array()) {
        $error_data = array(
            'type' => 'ajax_wp_die',
            'message' => $message,
            'title' => $title,
            'args' => $args,
            'timestamp' => current_time('timestamp')
        );
        
        $this->log_error($error_data, 'error');
        
        // Send JSON response with error details
        wp_send_json_error(array(
            'message' => $message,
            'title' => $title,
            'error_code' => 'yoyaku_ajax_error'
        ));
    }
    
    /**
     * Handle XML-RPC wp_die
     * 
     * @param string $message Error message
     * @param string $title Error title
     * @param array $args Additional arguments
     */
    public function wp_die_xmlrpc_handler($message = '', $title = '', $args = array()) {
        $error_data = array(
            'type' => 'xmlrpc_wp_die',
            'message' => $message,
            'title' => $title,
            'args' => $args,
            'timestamp' => current_time('timestamp')
        );
        
        $this->log_error($error_data, 'error');
        
        // Send XML-RPC fault
        $wp_xmlrpc_server = new wp_xmlrpc_server();
        $wp_xmlrpc_server->error(new IXR_Error(500, $message));
    }
    
    /**
     * Handle general wp_die
     * 
     * @param string $message Error message
     * @param string $title Error title
     * @param array $args Additional arguments
     */
    public function wp_die_handler($message = '', $title = '', $args = array()) {
        $error_data = array(
            'type' => 'wp_die',
            'message' => $message,
            'title' => $title,
            'args' => $args,
            'timestamp' => current_time('timestamp')
        );
        
        $this->log_error($error_data, 'error');
        
        // Display error page with recovery options
        $this->display_wp_die_page($message, $title, $args);
    }
    
    /**
     * Get error level from PHP severity
     * 
     * @param int $severity PHP error severity
     * @return string Error level
     */
    private function get_error_level($severity) {
        switch ($severity) {
            case E_ERROR:
            case E_PARSE:
            case E_CORE_ERROR:
            case E_COMPILE_ERROR:
                return 'critical';
            case E_WARNING:
            case E_CORE_WARNING:
            case E_COMPILE_WARNING:
            case E_USER_ERROR:
                return 'error';
            case E_NOTICE:
            case E_USER_WARNING:
            case E_USER_NOTICE:
                return 'warning';
            case E_STRICT:
            case E_DEPRECATED:
            case E_USER_DEPRECATED:
                return 'info';
            default:
                return 'debug';
        }
    }
    
    /**
     * Log error with context
     * 
     * @param array $error_context Error context data
     * @param string $level Error level
     */
    private function log_error($error_context, $level = 'error') {
        // Add to internal log
        $this->error_log[] = array_merge($error_context, array('level' => $level));
        
        // Keep only last 100 errors in memory
        if (count($this->error_log) > 100) {
            array_shift($this->error_log);
        }
        
        // Log to WordPress error log
        if (function_exists('error_log')) {
            $log_message = sprintf(
                '[YOYAKU %s] %s in %s:%d',
                strtoupper($level),
                $error_context['message'],
                $error_context['filename'] ?? 'unknown',
                $error_context['lineno'] ?? 0
            );
            error_log($log_message);
        }
        
        // Store critical errors in database
        if (in_array($level, array('critical', 'error'))) {
            $stored_errors = get_option('yoyaku_stored_errors', array());
            $stored_errors[] = $error_context;
            
            // Keep only last 50 stored errors
            if (count($stored_errors) > 50) {
                $stored_errors = array_slice($stored_errors, -50);
            }
            
            update_option('yoyaku_stored_errors', $stored_errors);
        }
    }
    
    /**
     * Apply recovery strategy based on error context
     * 
     * @param array $error_context Error context
     */
    private function apply_recovery_strategy($error_context) {
        $filename = $error_context['filename'] ?? '';
        $message = $error_context['message'] ?? '';
        
        // Determine affected system
        $affected_system = $this->detect_affected_system($filename, $message);
        
        if ($affected_system && isset($this->recovery_strategies[$affected_system])) {
            $strategy = $this->recovery_strategies[$affected_system];
            $this->execute_recovery_strategy($affected_system, $strategy, $error_context);
        }
    }
    
    /**
     * Detect which system is affected by the error
     * 
     * @param string $filename Error filename
     * @param string $message Error message
     * @return string|null Affected system identifier
     */
    private function detect_affected_system($filename, $message) {
        if (strpos($filename, 'woocommerce') !== false || strpos($message, 'WC_') !== false) {
            return 'woocommerce';
        }
        
        if (strpos($filename, 'audio') !== false || strpos($message, 'audio') !== false) {
            return 'audio';
        }
        
        if (strpos($filename, 'wishlist') !== false || strpos($message, 'wishlist') !== false) {
            return 'wishlist';
        }
        
        if (strpos($message, 'database') !== false || strpos($message, 'SQL') !== false) {
            return 'database';
        }
        
        return null;
    }
    
    /**
     * Execute recovery strategy
     * 
     * @param string $system System identifier
     * @param array $strategy Recovery strategy
     * @param array $error_context Error context
     */
    private function execute_recovery_strategy($system, $strategy, $error_context) {
        yoyaku_log_debug(sprintf('Executing recovery strategy for system: %s', $system));
        
        // Load fallback template
        if (isset($strategy['fallback_template'])) {
            $fallback_path = YOYAKU_INC_PATH . '/fallbacks/' . $strategy['fallback_template'];
            if (file_exists($fallback_path)) {
                include_once $fallback_path;
            }
        }
        
        // Disable problematic features
        if (isset($strategy['disable_features'])) {
            foreach ($strategy['disable_features'] as $feature) {
                $this->disable_feature($system, $feature);
            }
        }
        
        // Hook alternative functions
        if (isset($strategy['alternative_hooks'])) {
            foreach ($strategy['alternative_hooks'] as $hook => $callback) {
                if (function_exists($callback)) {
                    add_action($hook, $callback, 999);
                }
            }
        }
    }
    
    /**
     * Disable specific feature
     * 
     * @param string $system System identifier
     * @param string $feature Feature to disable
     */
    private function disable_feature($system, $feature) {
        switch ($system) {
            case 'woocommerce':
                if ($feature === 'product_gallery') {
                    remove_theme_support('wc-product-gallery-zoom');
                    remove_theme_support('wc-product-gallery-lightbox');
                    remove_theme_support('wc-product-gallery-slider');
                }
                break;
                
            case 'audio':
                if ($feature === 'visualization') {
                    add_filter('yoyaku_audio_enable_visualization', '__return_false');
                }
                break;
        }
    }
    
    /**
     * Handle critical errors
     * 
     * @param array $error_context Error context
     */
    private function handle_critical_error($error_context) {
        // Notify administrators
        if (current_user_can('manage_options')) {
            add_action('admin_notices', function() use ($error_context) {
                printf(
                    '<div class="notice notice-error is-dismissible"><p><strong>%1$s:</strong> %2$s</p></div>',
                    esc_html__('Critical Error Detected', 'yoyaku'),
                    esc_html($error_context['message'])
                );
            });
        }
        
        // Enable maintenance mode for severe errors
        if ($this->should_enable_maintenance_mode($error_context)) {
            $this->enable_emergency_maintenance_mode();
        }
    }
    
    /**
     * Check if maintenance mode should be enabled
     * 
     * @param array $error_context Error context
     * @return bool Should enable maintenance mode
     */
    private function should_enable_maintenance_mode($error_context) {
        // Enable for database errors in production
        if (!defined('WP_DEBUG') || !WP_DEBUG) {
            if (strpos($error_context['message'], 'database') !== false) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Enable emergency maintenance mode
     */
    private function enable_emergency_maintenance_mode() {
        if (!file_exists(ABSPATH . '.maintenance')) {
            $maintenance_content = sprintf(
                "<?php\n\$upgrading = %d;\n// YOYAKU Emergency maintenance mode",
                time()
            );
            file_put_contents(ABSPATH . '.maintenance', $maintenance_content);
            
            yoyaku_log_error('Emergency maintenance mode enabled due to critical error', 'critical');
        }
    }
    
    /**
     * Display admin error notices
     */
    public function display_admin_error_notices() {
        $stored_errors = get_option('yoyaku_stored_errors', array());
        $recent_errors = array_filter($stored_errors, function($error) {
            return ($error['timestamp'] > (current_time('timestamp') - HOUR_IN_SECONDS));
        });
        
        if (!empty($recent_errors)) {
            $error_count = count($recent_errors);
            printf(
                '<div class="notice notice-warning is-dismissible"><p><strong>%1$s:</strong> %2$s <a href="%3$s">%4$s</a></p></div>',
                esc_html__('YOYAKU Error Handler', 'yoyaku'),
                sprintf(
                    /* translators: %d: number of errors */
                    esc_html(_n('%d recent error detected', '%d recent errors detected', $error_count, 'yoyaku')),
                    $error_count
                ),
                admin_url('tools.php?page=yoyaku-error-log'),
                esc_html__('View Details', 'yoyaku')
            );
        }
    }
    
    /**
     * Add error log admin menu
     */
    public function add_error_log_menu() {
        add_management_page(
            esc_html__('YOYAKU Error Log', 'yoyaku'),
            esc_html__('YOYAKU Errors', 'yoyaku'),
            'manage_options',
            'yoyaku-error-log',
            array($this, 'display_error_log_page')
        );
    }
    
    /**
     * Display error log page
     */
    public function display_error_log_page() {
        $stored_errors = get_option('yoyaku_stored_errors', array());
        $stored_errors = array_reverse($stored_errors); // Show newest first
        
        include_once YOYAKU_INC_PATH . '/admin/error-log-page.php';
    }
    
    /**
     * Display user-friendly error page
     * 
     * @param array $error_context Error context
     */
    private function display_error_page($error_context) {
        // Clean any output buffer
        while (ob_get_level()) {
            ob_end_clean();
        }
        
        http_response_code(500);
        include_once YOYAKU_INC_PATH . '/templates/error-page.php';
        exit;
    }
    
    /**
     * Display fatal error page
     * 
     * @param array $error_context Error context
     */
    private function display_fatal_error_page($error_context) {
        // Clean any output buffer
        while (ob_get_level()) {
            ob_end_clean();
        }
        
        http_response_code(500);
        include_once YOYAKU_INC_PATH . '/templates/fatal-error-page.php';
        exit;
    }
    
    /**
     * Display custom wp_die page
     * 
     * @param string $message Error message
     * @param string $title Error title
     * @param array $args Additional arguments
     */
    private function display_wp_die_page($message, $title, $args) {
        include_once YOYAKU_INC_PATH . '/templates/wp-die-page.php';
        exit;
    }
    
    /**
     * Handle AJAX error reporting
     */
    public function handle_ajax_error_report() {
        // Verify nonce for security
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'yoyaku_error_report')) {
            wp_send_json_error(array('message' => 'Invalid nonce'));
            return;
        }
        
        $error_data = array(
            'type' => 'ajax_reported',
            'message' => sanitize_textarea_field($_POST['message'] ?? ''),
            'url' => esc_url_raw($_POST['url'] ?? ''),
            'user_agent' => sanitize_text_field($_POST['user_agent'] ?? ''),
            'timestamp' => current_time('timestamp'),
            'user_id' => get_current_user_id()
        );
        
        $this->log_error($error_data, 'info');
        
        wp_send_json_success(array('message' => 'Error report received'));
    }
    
    /**
     * Get error statistics
     * 
     * @return array Error statistics
     */
    public function get_error_statistics() {
        $stored_errors = get_option('yoyaku_stored_errors', array());
        
        $stats = array(
            'total_errors' => count($stored_errors),
            'recent_errors' => 0,
            'critical_errors' => 0,
            'by_system' => array(),
            'by_level' => array()
        );
        
        $recent_threshold = current_time('timestamp') - DAY_IN_SECONDS;
        
        foreach ($stored_errors as $error) {
            if ($error['timestamp'] > $recent_threshold) {
                $stats['recent_errors']++;
            }
            
            if (($error['level'] ?? 'error') === 'critical') {
                $stats['critical_errors']++;
            }
            
            // Count by system
            $system = $this->detect_affected_system($error['filename'] ?? '', $error['message'] ?? '');
            if ($system) {
                $stats['by_system'][$system] = ($stats['by_system'][$system] ?? 0) + 1;
            }
            
            // Count by level
            $level = $error['level'] ?? 'error';
            $stats['by_level'][$level] = ($stats['by_level'][$level] ?? 0) + 1;
        }
        
        return $stats;
    }
}