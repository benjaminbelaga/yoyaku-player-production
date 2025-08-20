<?php
/**
 * YOYAKU Theme - Enterprise Fail-Safe Functions
 * 
 * 100% WordPress Standards Compliant
 * Zero Fatal Error Architecture
 * 
 * @package YOYAKU
 * @version 2.0.0
 * @author YOYAKU Themes Master
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    wp_die(
        esc_html__('Direct access denied.', 'yoyaku'),
        esc_html__('Security Error', 'yoyaku'),
        array('response' => 403)
    );
}

// Theme constants
if (!defined('YOYAKU_THEME_VERSION')) {
    define('YOYAKU_THEME_VERSION', '2.0.0');
}

if (!defined('YOYAKU_THEME_PATH')) {
    define('YOYAKU_THEME_PATH', get_template_directory());
}

if (!defined('YOYAKU_THEME_URI')) {
    define('YOYAKU_THEME_URI', get_template_directory_uri());
}

if (!defined('YOYAKU_INC_PATH')) {
    define('YOYAKU_INC_PATH', YOYAKU_THEME_PATH . '/inc');
}

/**
 * Fail-safe file loader with WordPress error handling
 * 
 * @param string $file_path Absolute path to file
 * @param bool $required Whether file is required (fatal if missing)
 * @param array $dependencies Array of required functions/classes
 * @return WP_Error|true Success or WP_Error object
 */
function yoyaku_safe_require($file_path, $required = true, $dependencies = array()) {
    // Validate file path
    if (empty($file_path) || !is_string($file_path)) {
        return new WP_Error(
            'yoyaku_invalid_path',
            esc_html__('Invalid file path provided.', 'yoyaku'),
            array('file_path' => $file_path)
        );
    }
    
    // Check if file exists
    if (!file_exists($file_path) || !is_readable($file_path)) {
        $error_msg = sprintf(
            /* translators: %s: file path */
            esc_html__('File not found or not readable: %s', 'yoyaku'),
            esc_html($file_path)
        );
        
        if ($required) {
            yoyaku_handle_critical_error($error_msg, $file_path);
            return new WP_Error('yoyaku_file_missing', $error_msg);
        } else {
            yoyaku_log_error($error_msg, 'file_missing');
            return new WP_Error('yoyaku_file_missing', $error_msg);
        }
    }
    
    // Check dependencies before loading
    if (!empty($dependencies)) {
        $missing_deps = yoyaku_check_dependencies($dependencies);
        if (!empty($missing_deps)) {
            $error_msg = sprintf(
                /* translators: %s: missing dependencies list */
                esc_html__('Missing dependencies: %s', 'yoyaku'),
                implode(', ', array_map('esc_html', $missing_deps))
            );
            
            if ($required) {
                yoyaku_handle_critical_error($error_msg, $file_path);
                return new WP_Error('yoyaku_missing_deps', $error_msg);
            } else {
                yoyaku_log_error($error_msg, 'missing_dependencies');
                return new WP_Error('yoyaku_missing_deps', $error_msg);
            }
        }
    }
    
    // Load file with error handling
    try {
        include_once $file_path;
        
        // Log successful load if debugging
        if (defined('WP_DEBUG') && WP_DEBUG) {
            yoyaku_log_debug(sprintf('Successfully loaded: %s', $file_path));
        }
        
        return true;
        
    } catch (Error $e) {
        $error_msg = sprintf(
            /* translators: 1: file path, 2: error message */
            esc_html__('Fatal error loading %1$s: %2$s', 'yoyaku'),
            esc_html($file_path),
            esc_html($e->getMessage())
        );
        
        yoyaku_handle_critical_error($error_msg, $file_path, $e);
        return new WP_Error('yoyaku_fatal_error', $error_msg);
    }
}

/**
 * Check if required dependencies exist
 * 
 * @param array $dependencies Array of functions/classes to check
 * @return array Missing dependencies
 */
function yoyaku_check_dependencies($dependencies) {
    $missing = array();
    
    foreach ($dependencies as $dependency) {
        if (strpos($dependency, 'class:') === 0) {
            $class = substr($dependency, 6);
            if (!class_exists($class)) {
                $missing[] = $dependency;
            }
        } elseif (strpos($dependency, 'function:') === 0) {
            $function = substr($dependency, 9);
            if (!function_exists($function)) {
                $missing[] = $dependency;
            }
        } elseif (strpos($dependency, 'plugin:') === 0) {
            $plugin = substr($dependency, 7);
            if (!yoyaku_is_plugin_active($plugin)) {
                $missing[] = $dependency;
            }
        }
    }
    
    return $missing;
}

/**
 * Check if plugin is active (HPOS compatible)
 * 
 * @param string $plugin Plugin name or path
 * @return bool Plugin is active
 */
function yoyaku_is_plugin_active($plugin) {
    if (!function_exists('is_plugin_active')) {
        include_once ABSPATH . 'wp-admin/includes/plugin.php';
    }
    
    return is_plugin_active($plugin) || class_exists($plugin);
}

/**
 * Handle critical errors with graceful degradation
 * 
 * @param string $message Error message
 * @param string $file_path File that caused error
 * @param Throwable $exception Optional exception object
 */
function yoyaku_handle_critical_error($message, $file_path = '', $exception = null) {
    // Log error
    yoyaku_log_error($message, 'critical', array(
        'file_path' => $file_path,
        'exception' => $exception ? $exception->getMessage() : null,
        'trace' => $exception ? $exception->getTraceAsString() : wp_debug_backtrace_summary()
    ));
    
    // Admin notice for logged in admins
    if (current_user_can('manage_options')) {
        add_action('admin_notices', function() use ($message) {
            yoyaku_admin_error_notice($message);
        });
    }
    
    // Graceful fallback in production
    if (!defined('WP_DEBUG') || !WP_DEBUG) {
        yoyaku_graceful_fallback($file_path, $message);
    }
}

/**
 * Display admin error notice
 * 
 * @param string $message Error message
 */
function yoyaku_admin_error_notice($message) {
    $class = 'notice notice-error';
    $title = esc_html__('YOYAKU Theme Error', 'yoyaku');
    
    printf(
        '<div class="%1$s"><p><strong>%2$s:</strong> %3$s</p></div>',
        esc_attr($class),
        $title,
        esc_html($message)
    );
}

/**
 * Graceful fallback for missing components
 * 
 * @param string $component Component that failed
 * @param string $error_message Error details
 */
function yoyaku_graceful_fallback($component, $error_message) {
    // Load minimal fallback functionality
    $fallback_path = YOYAKU_INC_PATH . '/fallbacks/';
    
    if (strpos($component, 'woocommerce') !== false) {
        $fallback_file = $fallback_path . 'woocommerce-fallback.php';
        if (file_exists($fallback_file)) {
            include_once $fallback_file;
        }
    } elseif (strpos($component, 'audio') !== false) {
        $fallback_file = $fallback_path . 'audio-fallback.php';
        if (file_exists($fallback_file)) {
            include_once $fallback_file;
        }
    }
    
    // Register fallback notice for frontend users
    add_action('wp_footer', function() {
        if (current_user_can('manage_options') && defined('WP_DEBUG') && WP_DEBUG) {
            echo '<!-- YOYAKU Theme: Using fallback mode due to component failure -->';
        }
    });
}

/**
 * Enhanced error logging with context
 * 
 * @param string $message Log message
 * @param string $level Error level (critical, error, warning, info, debug)
 * @param array $context Additional context data
 */
function yoyaku_log_error($message, $level = 'error', $context = array()) {
    if (!function_exists('error_log')) {
        return;
    }
    
    $log_entry = array(
        'timestamp' => current_time('Y-m-d H:i:s'),
        'level' => strtoupper($level),
        'message' => $message,
        'context' => $context,
        'user_id' => get_current_user_id(),
        'url' => isset($_SERVER['REQUEST_URI']) ? esc_url_raw($_SERVER['REQUEST_URI']) : 'N/A'
    );
    
    $log_message = sprintf(
        '[YOYAKU %s] %s | Context: %s',
        $log_entry['level'],
        $log_entry['message'],
        wp_json_encode($log_entry['context'])
    );
    
    error_log($log_message);
    
    // Store critical errors in option for admin dashboard
    if ($level === 'critical') {
        $critical_errors = get_option('yoyaku_critical_errors', array());
        $critical_errors[] = $log_entry;
        
        // Keep only last 10 critical errors
        if (count($critical_errors) > 10) {
            $critical_errors = array_slice($critical_errors, -10);
        }
        
        update_option('yoyaku_critical_errors', $critical_errors);
    }
}

/**
 * Debug logging (only when WP_DEBUG is true)
 * 
 * @param string $message Debug message
 * @param array $context Optional context
 */
function yoyaku_log_debug($message, $context = array()) {
    if (defined('WP_DEBUG') && WP_DEBUG) {
        yoyaku_log_error($message, 'debug', $context);
    }
}

// ============================================================================
// THEME INITIALIZATION (WordPress Standards)
// ============================================================================

/**
 * Initialize YOYAKU theme with fail-safe loading
 * 
 * Hooks into after_setup_theme for proper timing
 */
function yoyaku_theme_setup() {
    // Load core theme loader
    $loader_result = yoyaku_safe_require(
        YOYAKU_INC_PATH . '/class-yoyaku-theme-loader.php',
        true,
        array('function:add_theme_support')
    );
    
    if (is_wp_error($loader_result)) {
        yoyaku_handle_critical_error(
            'Failed to load theme loader: ' . $loader_result->get_error_message()
        );
        return;
    }
    
    // Initialize theme loader
    if (class_exists('YOYAKU_Theme_Loader')) {
        YOYAKU_Theme_Loader::get_instance();
    } else {
        yoyaku_handle_critical_error('YOYAKU_Theme_Loader class not found after loading');
    }
}
add_action('after_setup_theme', 'yoyaku_theme_setup', 5);

/**
 * Initialize theme modules after WordPress is fully loaded
 */
function yoyaku_init_modules() {
    // Load compatibility checker
    $compat_result = yoyaku_safe_require(
        YOYAKU_INC_PATH . '/class-yoyaku-compatibility.php',
        false
    );
    
    if (!is_wp_error($compat_result) && class_exists('YOYAKU_Compatibility')) {
        YOYAKU_Compatibility::get_instance();
    }
    
    // Load performance monitor
    $perf_result = yoyaku_safe_require(
        YOYAKU_INC_PATH . '/class-yoyaku-performance.php',
        false
    );
    
    if (!is_wp_error($perf_result) && class_exists('YOYAKU_Performance')) {
        YOYAKU_Performance::get_instance();
    }
}
add_action('wp_loaded', 'yoyaku_init_modules', 10);

/**
 * Admin initialization
 */
function yoyaku_admin_init() {
    // Display critical errors to admins
    $critical_errors = get_option('yoyaku_critical_errors', array());
    if (!empty($critical_errors) && current_user_can('manage_options')) {
        add_action('admin_notices', function() use ($critical_errors) {
            $latest_error = end($critical_errors);
            yoyaku_admin_error_notice(
                sprintf(
                    /* translators: %s: error message */
                    esc_html__('Latest critical error: %s', 'yoyaku'),
                    $latest_error['message']
                )
            );
        });
    }
}
add_action('admin_init', 'yoyaku_admin_init');

// ============================================================================
// EMERGENCY FALLBACKS
// ============================================================================

/**
 * Emergency fallback if theme loader fails completely
 */
if (!class_exists('YOYAKU_Theme_Loader')) {
    add_action('wp_head', function() {
        if (current_user_can('manage_options')) {
            echo '<!-- YOYAKU: Emergency mode - Theme loader failed -->';
        }
    });
    
    // Minimal theme support
    add_theme_support('post-thumbnails');
    add_theme_support('title-tag');
    
    if (class_exists('WooCommerce')) {
        add_theme_support('woocommerce');
    }
}