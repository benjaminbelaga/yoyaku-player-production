# 🎵 YOYAKU Player V3 - Developer Documentation

**Version:** 6.1.0  
**Architecture:** Modular WordPress Plugin  
**Pattern:** Singleton + Factory + Observer  
**Compatibility:** WordPress 5.8+, PHP 7.4+, WooCommerce 5.0+

## 📋 Table of Contents

1. [Architecture Overview](#architecture-overview)
2. [Core Classes & Patterns](#core-classes--patterns)
3. [JavaScript Architecture](#javascript-architecture)
4. [Hook System & Filters](#hook-system--filters)
5. [AJAX API Reference](#ajax-api-reference)
6. [Database Schema](#database-schema)
7. [Asset Management](#asset-management)
8. [Security Implementation](#security-implementation)
9. [Performance Optimization](#performance-optimization)
10. [Extension Points](#extension-points)
11. [Testing Framework](#testing-framework)
12. [Deployment & CI/CD](#deployment--cicd)

---

## Architecture Overview

### Plugin Structure
```
yoyaku-player-v3/
├── yoyaku-player-v3.php                    # Bootstrap & Activation
├── includes/
│   ├── class-yoyaku-player-core.php        # Main Controller (Singleton)
│   ├── class-ajax-handler.php              # AJAX Request Handler
│   ├── class-assets-manager.php            # Asset Loading Strategy
│   ├── class-security-manager.php          # Security Layer
│   ├── class-template-loader.php           # Template System
│   └── interfaces/
│       ├── interface-player-handler.php    # Player Interface Contract
│       └── interface-audio-provider.php    # Audio Source Interface
├── assets/
│   ├── css/
│   │   ├── frontend.css                    # Production CSS (minified)
│   │   └── admin.css                       # Admin Interface CSS
│   ├── js/
│   │   ├── frontend.js                     # Main Player Class
│   │   ├── admin.js                        # Admin Interface
│   │   └── modules/
│   │       ├── waveform-handler.js         # WaveSurfer Integration
│   │       ├── audio-engine.js             # Audio Control Logic
│   │       └── responsive-manager.js       # Mobile/Desktop Logic
│   └── images/
│       └── icons/                          # SVG Icon Library
├── templates/
│   ├── player/
│   │   ├── desktop-layout.php              # Desktop Template
│   │   ├── mobile-layout.php               # Mobile Template
│   │   └── fallback.php                    # Graceful Degradation
│   └── admin/
│       └── settings-page.php               # Admin Configuration
├── languages/
│   ├── yoyaku-player-v3.pot                # Translation Template
│   └── en_US/                              # English Translations
├── tests/
│   ├── phpunit/                            # PHP Unit Tests
│   ├── jest/                               # JavaScript Tests
│   └── cypress/                            # E2E Integration Tests
└── docs/
    ├── hooks-reference.md                  # Complete Hooks List
    ├── api-documentation.md               # REST API Endpoints
    └── customization-guide.md             # Theme Developer Guide
```

### Design Patterns Used

#### 1. Singleton Pattern (Plugin Core)
```php
class YoyakuPlayerCore {
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        // Prevent direct instantiation
    }
}
```

#### 2. Factory Pattern (Audio Providers)
```php
class AudioProviderFactory {
    public static function create($type, $config = []) {
        switch ($type) {
            case 'wavesurfer':
                return new WaveSurferProvider($config);
            case 'html5':
                return new HTML5AudioProvider($config);
            case 'web_audio':
                return new WebAudioProvider($config);
            default:
                throw new InvalidArgumentException("Unknown provider: {$type}");
        }
    }
}
```

#### 3. Observer Pattern (Event System)
```php
class PlayerEventManager {
    private $listeners = [];
    
    public function add_listener($event, $callback, $priority = 10) {
        $this->listeners[$event][$priority][] = $callback;
    }
    
    public function trigger($event, $data = []) {
        if (isset($this->listeners[$event])) {
            foreach ($this->listeners[$event] as $priority => $callbacks) {
                foreach ($callbacks as $callback) {
                    call_user_func($callback, $data);
                }
            }
        }
    }
}
```

#### 4. Strategy Pattern (Responsive Layouts)
```php
interface LayoutStrategy {
    public function render($data);
    public function get_breakpoint();
}

class DesktopLayoutStrategy implements LayoutStrategy {
    public function render($data) {
        return $this->load_template('desktop-layout', $data);
    }
    
    public function get_breakpoint() {
        return 768; // px
    }
}

class MobileLayoutStrategy implements LayoutStrategy {
    public function render($data) {
        return $this->load_template('mobile-layout', $data);
    }
    
    public function get_breakpoint() {
        return 768; // px
    }
}
```

---

## Core Classes & Patterns

### Main Controller (`YoyakuPlayerCore`)

```php
/**
 * Main Plugin Controller
 * 
 * Responsibilities:
 * - Plugin initialization and lifecycle management
 * - Service container for dependency injection
 * - Event dispatcher for inter-component communication
 * - Configuration management and validation
 */
class YoyakuPlayerCore {
    
    /**
     * Plugin version for cache busting and compatibility
     */
    public $version = '6.1.0';
    
    /**
     * Service container for dependency injection
     */
    private $container = [];
    
    /**
     * Configuration array
     */
    private $config = [];
    
    /**
     * Event manager instance
     */
    private $events;
    
    /**
     * Initialize plugin with dependency injection
     */
    public function init() {
        // Load configuration
        $this->load_config();
        
        // Initialize event system
        $this->events = new PlayerEventManager();
        
        // Register services
        $this->register_services();
        
        // Hook into WordPress
        $this->init_hooks();
        
        // Trigger initialization event
        $this->events->trigger('player_initialized', ['version' => $this->version]);
    }
    
    /**
     * Service container implementation
     */
    public function register_service($name, $callback) {
        $this->container[$name] = $callback;
    }
    
    public function get_service($name) {
        if (!isset($this->container[$name])) {
            throw new Exception("Service '{$name}' not found");
        }
        
        if (is_callable($this->container[$name])) {
            $this->container[$name] = call_user_func($this->container[$name]);
        }
        
        return $this->container[$name];
    }
    
    /**
     * Configuration management with validation
     */
    private function load_config() {
        $default_config = [
            'audio' => [
                'provider' => 'wavesurfer',
                'fallback_provider' => 'html5',
                'preload' => 'metadata',
                'crossorigin' => 'anonymous'
            ],
            'ui' => [
                'desktop_height' => 48,
                'mobile_height' => 120,
                'breakpoint' => 768,
                'theme' => 'default'
            ],
            'performance' => [
                'lazy_load' => true,
                'cache_duration' => 3600,
                'cdn_fallback' => true
            ],
            'security' => [
                'nonce_lifetime' => DAY_IN_SECONDS,
                'rate_limit' => 100 // requests per minute
            ]
        ];
        
        // Allow configuration override via WordPress options
        $user_config = get_option('yoyaku_player_config', []);
        $this->config = wp_parse_args($user_config, $default_config);
        
        // Validate configuration
        $this->validate_config();
    }
    
    private function validate_config() {
        // Validate audio provider
        $valid_providers = ['wavesurfer', 'html5', 'web_audio'];
        if (!in_array($this->config['audio']['provider'], $valid_providers)) {
            $this->config['audio']['provider'] = 'html5';
        }
        
        // Validate breakpoint is numeric
        if (!is_numeric($this->config['ui']['breakpoint'])) {
            $this->config['ui']['breakpoint'] = 768;
        }
        
        // Apply filters for extensibility
        $this->config = apply_filters('yoyaku_player_config', $this->config);
    }
    
    /**
     * WordPress hooks initialization
     */
    private function init_hooks() {
        // Core WordPress hooks
        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);
        add_action('wp_ajax_yoyaku_player_get_track', [$this, 'handle_ajax_get_track']);
        add_action('wp_ajax_nopriv_yoyaku_player_get_track', [$this, 'handle_ajax_get_track']);
        
        // WooCommerce integration hooks
        if (class_exists('WooCommerce')) {
            add_action('woocommerce_single_product_summary', [$this, 'render_product_player'], 25);
            add_filter('woocommerce_product_data_tabs', [$this, 'add_product_audio_tab']);
        }
        
        // Shortcode registration
        add_shortcode('yoyaku_player_v3', [$this, 'render_shortcode']);
        
        // Admin hooks
        if (is_admin()) {
            add_action('admin_menu', [$this, 'add_admin_menu']);
            add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
        }
        
        // REST API endpoints
        add_action('rest_api_init', [$this, 'register_rest_routes']);
        
        // Template hooks for theme integration
        add_filter('template_include', [$this, 'template_redirect']);
    }
}
```

### AJAX Handler (`AjaxHandler`)

```php
/**
 * Centralized AJAX Request Handler
 * 
 * Handles all AJAX requests with:
 * - Security validation (nonces, capabilities)
 * - Rate limiting to prevent abuse
 * - Error handling and logging
 * - Response standardization
 */
class AjaxHandler {
    
    /**
     * Rate limiting storage
     */
    private $rate_limits = [];
    
    /**
     * Handle get track AJAX request
     */
    public function handle_get_track() {
        try {
            // Security checks
            $this->validate_request();
            
            // Rate limiting
            $this->check_rate_limit();
            
            // Input validation
            $product_id = $this->validate_product_id();
            
            // Business logic
            $track_data = $this->get_track_data($product_id);
            
            // Response formatting
            $this->send_success_response($track_data);
            
        } catch (ValidationException $e) {
            $this->send_error_response($e->getMessage(), 400);
        } catch (SecurityException $e) {
            $this->log_security_event($e);
            $this->send_error_response('Access denied', 403);
        } catch (Exception $e) {
            $this->log_error($e);
            $this->send_error_response('Internal server error', 500);
        }
    }
    
    /**
     * Security validation with multiple layers
     */
    private function validate_request() {
        // Nonce verification
        if (!wp_verify_nonce($_POST['nonce'], 'yoyaku_player_nonce')) {
            throw new SecurityException('Invalid nonce');
        }
        
        // Check if user can access this functionality
        if (!$this->user_can_access()) {
            throw new SecurityException('Insufficient permissions');
        }
        
        // Validate referer for additional security
        if (!wp_validate_auth_cookie()) {
            // Log suspicious activity
            $this->log_security_event('Invalid auth cookie in AJAX request');
        }
    }
    
    /**
     * Rate limiting implementation
     */
    private function check_rate_limit() {
        $user_id = get_current_user_id();
        $ip_address = $this->get_client_ip();
        $identifier = $user_id ? "user_{$user_id}" : "ip_{$ip_address}";
        
        $current_time = time();
        $window = 60; // 1 minute window
        $max_requests = 100;
        
        // Clean old entries
        $this->rate_limits = array_filter($this->rate_limits, function($timestamp) use ($current_time, $window) {
            return ($current_time - $timestamp) < $window;
        });
        
        // Count requests in current window
        $requests_in_window = count(array_filter($this->rate_limits, function($data) use ($identifier) {
            return $data['identifier'] === $identifier;
        }));
        
        if ($requests_in_window >= $max_requests) {
            throw new SecurityException('Rate limit exceeded');
        }
        
        // Record this request
        $this->rate_limits[] = [
            'identifier' => $identifier,
            'timestamp' => $current_time
        ];
    }
    
    /**
     * Comprehensive input validation
     */
    private function validate_product_id() {
        // Check if product_id is present
        if (!isset($_POST['product_id'])) {
            throw new ValidationException('Product ID is required');
        }
        
        // Sanitize and validate
        $product_id = intval($_POST['product_id']);
        
        if ($product_id <= 0) {
            throw new ValidationException('Invalid product ID format');
        }
        
        // Verify product exists
        $product = wc_get_product($product_id);
        if (!$product) {
            throw new ValidationException('Product not found');
        }
        
        // Check if product is published
        if ($product->get_status() !== 'publish') {
            throw new ValidationException('Product not available');
        }
        
        return $product_id;
    }
    
    /**
     * Business logic for track data retrieval
     */
    private function get_track_data($product_id) {
        // Get playlist files with caching
        $cache_key = "yoyaku_tracks_{$product_id}";
        $playlist_files = wp_cache_get($cache_key);
        
        if (false === $playlist_files) {
            $playlist_files = get_post_meta($product_id, '_yoyaku_playlist_files', true);
            wp_cache_set($cache_key, $playlist_files, 'yoyaku_player', 3600);
        }
        
        if (empty($playlist_files) || !is_array($playlist_files)) {
            throw new ValidationException('No audio tracks found for this product');
        }
        
        // Process and validate track data
        $tracks = [];
        foreach ($playlist_files as $index => $file) {
            if (!$this->is_valid_track($file)) {
                continue;
            }
            
            $tracks[] = [
                'id' => $index,
                'name' => sanitize_text_field($file['name']),
                'url' => esc_url_raw($file['url']),
                'bpm' => isset($file['bpm']) ? intval($file['bpm']) : null,
                'duration' => isset($file['duration']) ? sanitize_text_field($file['duration']) : null,
                'waveform_data' => $this->get_waveform_data($file['url'])
            ];
        }
        
        if (empty($tracks)) {
            throw new ValidationException('No valid tracks found');
        }
        
        // Get additional product data
        $product = wc_get_product($product_id);
        $product_data = $this->get_product_metadata($product);
        
        return [
            'product' => $product_data,
            'tracks' => $tracks,
            'player_config' => $this->get_player_config(),
            'timestamp' => current_time('timestamp')
        ];
    }
    
    /**
     * Standardized response methods
     */
    private function send_success_response($data) {
        wp_send_json_success([
            'data' => $data,
            'timestamp' => current_time('timestamp'),
            'version' => YPV3_VERSION
        ]);
    }
    
    private function send_error_response($message, $code = 400) {
        wp_send_json_error([
            'message' => $message,
            'code' => $code,
            'timestamp' => current_time('timestamp')
        ], $code);
    }
}
```

---

## JavaScript Architecture

### Main Player Class (`YoyakuPlayerUltraFin`)

```javascript
/**
 * YOYAKU Player Ultra-Fin JavaScript Class
 * 
 * Architecture:
 * - ES6 Class with private methods and properties
 * - Event-driven communication with WordPress
 * - Modular audio engine with provider abstraction
 * - Responsive layout management
 * - Error handling with graceful degradation
 */
class YoyakuPlayerUltraFin {
    
    /**
     * Constructor with dependency injection
     */
    constructor(config = {}) {
        // Configuration with defaults
        this.config = {
            audioProvider: 'wavesurfer',
            fallbackProvider: 'html5',
            mobileBreakpoint: 768,
            autoplayPolicy: 'user-gesture',
            debug: false,
            ...config
        };
        
        // State management
        this.state = {
            isInitialized: false,
            isPlaying: false,
            currentTrack: null,
            currentProduct: null,
            volume: 0.8,
            pitch: 0,
            playbackRate: 1,
            position: 0,
            duration: 0
        };
        
        // Event system
        this.events = new Map();
        this.eventQueue = [];
        
        // Audio engine
        this.audioEngine = null;
        this.waveformEngine = null;
        
        // UI components
        this.ui = {
            container: null,
            controls: {},
            playlist: null,
            waveform: null
        };
        
        // Performance monitoring
        this.performance = {
            initTime: 0,
            loadTimes: {},
            errorCount: 0
        };
        
        // Initialize player
        this.init();
    }
    
    /**
     * Initialization with error handling
     */
    async init() {
        try {
            const startTime = performance.now();
            
            this.log('Initializing YOYAKU Player Ultra-Fin...');
            
            // Load dependencies
            await this.loadDependencies();
            
            // Initialize audio engine
            await this.initAudioEngine();
            
            // Create UI components
            await this.createUI();
            
            // Bind event listeners
            this.bindEvents();
            
            // Setup responsive behavior
            this.initResponsiveManager();
            
            // Auto-load content if specified
            await this.checkAutoload();
            
            // Mark as initialized
            this.state.isInitialized = true;
            this.performance.initTime = performance.now() - startTime;
            
            this.log(`Player initialized successfully in ${this.performance.initTime.toFixed(2)}ms`);
            this.emit('player:initialized', { performance: this.performance });
            
        } catch (error) {
            this.handleError('Initialization failed', error);
            this.initFallbackMode();
        }
    }
    
    /**
     * Dependency loading with CDN fallback
     */
    async loadDependencies() {
        const dependencies = [
            {
                name: 'wavesurfer',
                primary: 'https://unpkg.com/wavesurfer.js@7.8.0/dist/wavesurfer.umd.min.js',
                fallback: 'https://cdn.jsdelivr.net/npm/wavesurfer.js@7/dist/wavesurfer.min.js',
                check: () => window.WaveSurfer
            }
        ];
        
        for (const dep of dependencies) {
            if (dep.check()) {
                this.log(`${dep.name} already loaded`);
                continue;
            }
            
            try {
                await this.loadScript(dep.primary);
                this.log(`${dep.name} loaded from primary CDN`);
            } catch (error) {
                this.log(`Primary CDN failed for ${dep.name}, trying fallback...`);
                try {
                    await this.loadScript(dep.fallback);
                    this.log(`${dep.name} loaded from fallback CDN`);
                } catch (fallbackError) {
                    throw new Error(`Failed to load ${dep.name} from both CDNs`);
                }
            }
        }
    }
    
    /**
     * Audio engine initialization with provider pattern
     */
    async initAudioEngine() {
        try {
            // Try primary audio provider
            this.audioEngine = await this.createAudioProvider(this.config.audioProvider);
            this.log(`Audio engine initialized with ${this.config.audioProvider} provider`);
        } catch (error) {
            this.log(`Primary audio provider failed, trying fallback...`);
            
            try {
                // Fallback to HTML5 audio
                this.audioEngine = await this.createAudioProvider(this.config.fallbackProvider);
                this.log(`Audio engine initialized with ${this.config.fallbackProvider} fallback`);
            } catch (fallbackError) {
                throw new Error('All audio providers failed to initialize');
            }
        }
        
        // Bind audio engine events
        this.audioEngine.on('ready', () => this.emit('audio:ready'));
        this.audioEngine.on('play', () => this.emit('audio:play'));
        this.audioEngine.on('pause', () => this.emit('audio:pause'));
        this.audioEngine.on('ended', () => this.emit('audio:ended'));
        this.audioEngine.on('error', (error) => this.emit('audio:error', error));
        this.audioEngine.on('progress', (progress) => this.emit('audio:progress', progress));
    }
    
    /**
     * Audio provider factory
     */
    async createAudioProvider(type) {
        switch (type) {
            case 'wavesurfer':
                return new WaveSurferProvider({
                    container: '#waveform',
                    waveColor: 'rgba(255, 255, 255, 0.3)',
                    progressColor: '#ffd700',
                    cursorColor: '#ffffff',
                    barWidth: 2,
                    barGap: 1,
                    responsive: true,
                    height: this.isMobile() ? 40 : 24,
                    normalize: true,
                    backend: 'WebAudio',
                    cors: 'anonymous'
                });
                
            case 'html5':
                return new HTML5AudioProvider({
                    preload: 'metadata',
                    crossorigin: 'anonymous'
                });
                
            case 'web_audio':
                return new WebAudioProvider({
                    bufferSize: 4096,
                    sampleRate: 44100
                });
                
            default:
                throw new Error(`Unknown audio provider: ${type}`);
        }
    }
    
    /**
     * Event system implementation
     */
    on(event, callback, options = {}) {
        if (!this.events.has(event)) {
            this.events.set(event, []);
        }
        
        this.events.get(event).push({
            callback,
            once: options.once || false,
            priority: options.priority || 10
        });
        
        // Sort by priority
        this.events.get(event).sort((a, b) => b.priority - a.priority);
    }
    
    emit(event, data = {}) {
        this.log(`Event emitted: ${event}`, data);
        
        // Add to event queue if not initialized
        if (!this.state.isInitialized) {
            this.eventQueue.push({ event, data, timestamp: Date.now() });
            return;
        }
        
        const listeners = this.events.get(event);
        if (!listeners) return;
        
        // Execute listeners
        listeners.forEach((listener, index) => {
            try {
                listener.callback(data);
                
                // Remove 'once' listeners
                if (listener.once) {
                    listeners.splice(index, 1);
                }
            } catch (error) {
                this.handleError(`Event listener error for ${event}`, error);
            }
        });
    }
    
    /**
     * Product loading with caching and validation
     */
    async loadProduct(productId) {
        const startTime = performance.now();
        
        try {
            this.log(`Loading product ${productId}...`);
            
            // Check cache first
            const cacheKey = `product_${productId}`;
            const cached = this.getFromCache(cacheKey);
            
            if (cached && !this.config.debug) {
                this.log('Product data loaded from cache');
                return this.processProductData(cached);
            }
            
            // Fetch from server
            const response = await this.makeAjaxRequest('yoyaku_player_get_track', {
                product_id: productId
            });
            
            if (!response.success) {
                throw new Error(response.data.message || 'Failed to load product data');
            }
            
            // Cache the response
            this.setCache(cacheKey, response.data, 300); // 5 minutes
            
            // Process and validate data
            const productData = this.processProductData(response.data);
            
            this.performance.loadTimes[productId] = performance.now() - startTime;
            this.log(`Product ${productId} loaded in ${this.performance.loadTimes[productId].toFixed(2)}ms`);
            
            return productData;
            
        } catch (error) {
            this.performance.errorCount++;
            throw new Error(`Failed to load product ${productId}: ${error.message}`);
        }
    }
    
    /**
     * AJAX request with retry logic and timeout
     */
    async makeAjaxRequest(action, data = {}, options = {}) {
        const config = {
            timeout: 10000,
            retries: 2,
            retryDelay: 1000,
            ...options
        };
        
        const requestData = new FormData();
        requestData.append('action', action);
        requestData.append('nonce', yoyaku_player_v3.nonce);
        
        // Add data fields
        Object.keys(data).forEach(key => {
            requestData.append(key, data[key]);
        });
        
        for (let attempt = 0; attempt <= config.retries; attempt++) {
            try {
                const controller = new AbortController();
                const timeoutId = setTimeout(() => controller.abort(), config.timeout);
                
                const response = await fetch(yoyaku_player_v3.ajax_url, {
                    method: 'POST',
                    body: requestData,
                    signal: controller.signal,
                    credentials: 'same-origin'
                });
                
                clearTimeout(timeoutId);
                
                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                }
                
                const result = await response.json();
                
                // Validate response structure
                if (typeof result !== 'object' || result === null) {
                    throw new Error('Invalid response format');
                }
                
                return result;
                
            } catch (error) {
                if (attempt === config.retries) {
                    throw error;
                }
                
                this.log(`Request attempt ${attempt + 1} failed, retrying in ${config.retryDelay}ms...`);
                await this.delay(config.retryDelay);
            }
        }
    }
    
    /**
     * Error handling with categorization and recovery
     */
    handleError(message, error = null) {
        const errorData = {
            message,
            error: error ? error.message : null,
            stack: error ? error.stack : null,
            timestamp: new Date().toISOString(),
            userAgent: navigator.userAgent,
            url: window.location.href,
            playerState: { ...this.state }
        };
        
        // Log to console in debug mode
        if (this.config.debug) {
            console.error('YOYAKU Player Error:', errorData);
        }
        
        // Send to WordPress error logging
        if (window.yoyaku_player_v3 && yoyaku_player_v3.debug) {
            this.makeAjaxRequest('yoyaku_player_log_error', {
                error_data: JSON.stringify(errorData)
            }).catch(() => {
                // Ignore logging errors to prevent infinite loops
            });
        }
        
        // Emit error event for custom handling
        this.emit('player:error', errorData);
        
        // Attempt recovery based on error type
        this.attemptErrorRecovery(error);
    }
    
    /**
     * Responsive layout management
     */
    initResponsiveManager() {
        this.responsiveManager = {
            breakpoint: this.config.mobileBreakpoint,
            isMobile: false,
            
            check: () => {
                const wasMobile = this.responsiveManager.isMobile;
                this.responsiveManager.isMobile = window.innerWidth <= this.responsiveManager.breakpoint;
                
                if (wasMobile !== this.responsiveManager.isMobile) {
                    this.emit('layout:change', {
                        isMobile: this.responsiveManager.isMobile,
                        width: window.innerWidth
                    });
                }
            },
            
            init: () => {
                this.responsiveManager.check();
                window.addEventListener('resize', this.debounce(this.responsiveManager.check, 250));
            }
        };
        
        // Handle layout changes
        this.on('layout:change', (data) => {
            this.updateLayoutForViewport(data.isMobile);
        });
        
        this.responsiveManager.init();
    }
    
    /**
     * Performance monitoring and optimization
     */
    getPerformanceMetrics() {
        return {
            ...this.performance,
            memoryUsage: this.getMemoryUsage(),
            renderTime: this.getRenderTime(),
            audioLatency: this.getAudioLatency()
        };
    }
    
    /**
     * Utility methods
     */
    log(message, data = null) {
        if (this.config.debug) {
            console.log(`[YOYAKU Player] ${message}`, data || '');
        }
    }
    
    debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }
    
    delay(ms) {
        return new Promise(resolve => setTimeout(resolve, ms));
    }
    
    isMobile() {
        return window.innerWidth <= this.config.mobileBreakpoint;
    }
}

/**
 * Initialize player when DOM is ready
 */
document.addEventListener('DOMContentLoaded', () => {
    if (typeof yoyaku_player_v3 !== 'undefined') {
        window.yoyakuPlayer = new YoyakuPlayerUltraFin(yoyaku_player_v3);
    }
});
```

---

## Hook System & Filters

### Complete Hook Reference

```php
/**
 * YOYAKU Player Hook System Documentation
 * 
 * All hooks follow WordPress naming conventions:
 * - Actions: yoyaku_player_{context}_{action}
 * - Filters: yoyaku_player_{context}_{property}
 */

// === INITIALIZATION HOOKS ===

/**
 * Fired when plugin is initializing
 * 
 * @param YoyakuPlayerCore $core Plugin core instance
 */
do_action('yoyaku_player_init', $core);

/**
 * Fired after plugin is fully loaded
 * 
 * @param YoyakuPlayerCore $core Plugin core instance
 * @param array $config Current configuration
 */
do_action('yoyaku_player_loaded', $core, $config);

// === CONFIGURATION FILTERS ===

/**
 * Filter plugin configuration
 * 
 * @param array $config Configuration array
 * @return array Modified configuration
 */
$config = apply_filters('yoyaku_player_config', $config);

/**
 * Filter audio provider settings
 * 
 * @param array $settings Provider settings
 * @param string $provider Provider name (wavesurfer, html5, etc.)
 * @return array Modified settings
 */
$settings = apply_filters('yoyaku_player_audio_settings', $settings, $provider);

/**
 * Filter responsive breakpoints
 * 
 * @param array $breakpoints Breakpoint configuration
 * @return array Modified breakpoints
 */
$breakpoints = apply_filters('yoyaku_player_breakpoints', $breakpoints);

// === ASSET MANAGEMENT HOOKS ===

/**
 * Filter whether to load player assets on current page
 * 
 * @param bool $should_load Whether to load assets
 * @return bool Modified decision
 */
$should_load = apply_filters('yoyaku_player_should_load_assets', $should_load);

/**
 * Filter CSS dependencies
 * 
 * @param array $dependencies Array of CSS dependencies
 * @return array Modified dependencies
 */
$dependencies = apply_filters('yoyaku_player_css_dependencies', $dependencies);

/**
 * Filter JavaScript dependencies
 * 
 * @param array $dependencies Array of JS dependencies
 * @return array Modified dependencies
 */
$dependencies = apply_filters('yoyaku_player_js_dependencies', $dependencies);

/**
 * Filter localized script data
 * 
 * @param array $data Localized data
 * @return array Modified data
 */
$data = apply_filters('yoyaku_player_localized_data', $data);

// === TRACK DATA HOOKS ===

/**
 * Fired before track data is processed
 * 
 * @param int $product_id Product ID
 * @param array $raw_data Raw track data from database
 */
do_action('yoyaku_player_before_track_processing', $product_id, $raw_data);

/**
 * Filter track data after processing
 * 
 * @param array $tracks Processed track data
 * @param int $product_id Product ID
 * @return array Modified track data
 */
$tracks = apply_filters('yoyaku_player_track_data', $tracks, $product_id);

/**
 * Filter individual track before processing
 * 
 * @param array $track Single track data
 * @param int $index Track index
 * @param int $product_id Product ID
 * @return array Modified track data
 */
$track = apply_filters('yoyaku_player_process_track', $track, $index, $product_id);

/**
 * Filter track URL before output
 * 
 * @param string $url Track URL
 * @param array $track Complete track data
 * @param int $product_id Product ID
 * @return string Modified URL
 */
$url = apply_filters('yoyaku_player_track_url', $url, $track, $product_id);

// === PRODUCT INTEGRATION HOOKS ===

/**
 * Filter product metadata for player
 * 
 * @param array $metadata Product metadata
 * @param WC_Product $product WooCommerce product object
 * @return array Modified metadata
 */
$metadata = apply_filters('yoyaku_player_product_metadata', $metadata, $product);

/**
 * Filter whether product should have player
 * 
 * @param bool $has_player Whether product has player
 * @param WC_Product $product WooCommerce product object
 * @return bool Modified decision
 */
$has_player = apply_filters('yoyaku_player_product_has_player', $has_player, $product);

/**
 * Filter product cover image URL
 * 
 * @param string $image_url Cover image URL
 * @param WC_Product $product WooCommerce product object
 * @return string Modified URL
 */
$image_url = apply_filters('yoyaku_player_product_cover', $image_url, $product);

// === TEMPLATE SYSTEM HOOKS ===

/**
 * Filter template path
 * 
 * @param string $template Template file path
 * @param string $template_name Template name
 * @param array $args Template arguments
 * @return string Modified path
 */
$template = apply_filters('yoyaku_player_template_path', $template, $template_name, $args);

/**
 * Filter template arguments
 * 
 * @param array $args Template arguments
 * @param string $template_name Template name
 * @return array Modified arguments
 */
$args = apply_filters('yoyaku_player_template_args', $args, $template_name);

/**
 * Fired before template is loaded
 * 
 * @param string $template_name Template name
 * @param array $args Template arguments
 */
do_action('yoyaku_player_before_template', $template_name, $args);

/**
 * Fired after template is loaded
 * 
 * @param string $template_name Template name
 * @param array $args Template arguments
 */
do_action('yoyaku_player_after_template', $template_name, $args);

// === SHORTCODE HOOKS ===

/**
 * Filter shortcode attributes
 * 
 * @param array $atts Shortcode attributes
 * @param array $original_atts Original attributes before processing
 * @return array Modified attributes
 */
$atts = apply_filters('yoyaku_player_shortcode_atts', $atts, $original_atts);

/**
 * Filter shortcode output
 * 
 * @param string $output Shortcode HTML output
 * @param array $atts Shortcode attributes
 * @return string Modified output
 */
$output = apply_filters('yoyaku_player_shortcode_output', $output, $atts);

// === AJAX HOOKS ===

/**
 * Fired before AJAX request processing
 * 
 * @param string $action AJAX action name
 * @param array $data Request data
 */
do_action('yoyaku_player_before_ajax', $action, $data);

/**
 * Filter AJAX response data
 * 
 * @param array $response Response data
 * @param string $action AJAX action name
 * @return array Modified response
 */
$response = apply_filters('yoyaku_player_ajax_response', $response, $action);

/**
 * Fired after successful AJAX request
 * 
 * @param array $response Response data
 * @param string $action AJAX action name
 */
do_action('yoyaku_player_ajax_success', $response, $action);

/**
 * Fired on AJAX error
 * 
 * @param string $error_message Error message
 * @param string $action AJAX action name
 * @param Exception $exception Original exception (if any)
 */
do_action('yoyaku_player_ajax_error', $error_message, $action, $exception);

// === SECURITY HOOKS ===

/**
 * Filter security validation rules
 * 
 * @param array $rules Validation rules
 * @param string $context Security context
 * @return array Modified rules
 */
$rules = apply_filters('yoyaku_player_security_rules', $rules, $context);

/**
 * Filter rate limiting configuration
 * 
 * @param array $config Rate limiting config
 * @param string $identifier User/IP identifier
 * @return array Modified config
 */
$config = apply_filters('yoyaku_player_rate_limit_config', $config, $identifier);

/**
 * Fired when security violation is detected
 * 
 * @param string $violation_type Type of violation
 * @param array $context Violation context
 */
do_action('yoyaku_player_security_violation', $violation_type, $context);

// === PERFORMANCE HOOKS ===

/**
 * Filter cache duration for different data types
 * 
 * @param int $duration Cache duration in seconds
 * @param string $data_type Type of data being cached
 * @return int Modified duration
 */
$duration = apply_filters('yoyaku_player_cache_duration', $duration, $data_type);

/**
 * Filter memory usage optimization settings
 * 
 * @param array $settings Optimization settings
 * @return array Modified settings
 */
$settings = apply_filters('yoyaku_player_memory_optimization', $settings);

// === ERROR HANDLING HOOKS ===

/**
 * Fired when error occurs
 * 
 * @param string $error_message Error message
 * @param string $context Error context
 * @param Exception $exception Original exception
 */
do_action('yoyaku_player_error', $error_message, $context, $exception);

/**
 * Filter error recovery strategies
 * 
 * @param array $strategies Recovery strategies
 * @param string $error_type Type of error
 * @return array Modified strategies
 */
$strategies = apply_filters('yoyaku_player_error_recovery', $strategies, $error_type);

// === INTEGRATION HOOKS ===

/**
 * Filter WooCommerce integration settings
 * 
 * @param array $settings Integration settings
 * @return array Modified settings
 */
$settings = apply_filters('yoyaku_player_woocommerce_integration', $settings);

/**
 * Filter taxonomy integration
 * 
 * @param array $taxonomies Supported taxonomies
 * @return array Modified taxonomies
 */
$taxonomies = apply_filters('yoyaku_player_supported_taxonomies', $taxonomies);

// === FRONTEND DISPLAY HOOKS ===

/**
 * Fired before player is rendered
 * 
 * @param array $player_data Player configuration data
 */
do_action('yoyaku_player_before_render', $player_data);

/**
 * Filter player HTML classes
 * 
 * @param array $classes CSS classes
 * @param array $context Rendering context
 * @return array Modified classes
 */
$classes = apply_filters('yoyaku_player_css_classes', $classes, $context);

/**
 * Filter player inline styles
 * 
 * @param array $styles Inline styles
 * @param array $context Rendering context
 * @return array Modified styles
 */
$styles = apply_filters('yoyaku_player_inline_styles', $styles, $context);

/**
 * Fired after player is rendered
 * 
 * @param string $output Rendered HTML
 * @param array $player_data Player configuration data
 */
do_action('yoyaku_player_after_render', $output, $player_data);
```

### Hook Usage Examples

```php
/**
 * Example: Custom audio provider integration
 */
add_filter('yoyaku_player_audio_settings', function($settings, $provider) {
    if ($provider === 'custom_provider') {
        $settings['custom_option'] = 'custom_value';
    }
    return $settings;
}, 10, 2);

/**
 * Example: Modify track URLs for CDN delivery
 */
add_filter('yoyaku_player_track_url', function($url, $track, $product_id) {
    // Replace domain with CDN URL
    return str_replace('example.com', 'cdn.example.com', $url);
}, 10, 3);

/**
 * Example: Add custom product metadata
 */
add_filter('yoyaku_player_product_metadata', function($metadata, $product) {
    $metadata['custom_field'] = get_post_meta($product->get_id(), '_custom_field', true);
    return $metadata;
}, 10, 2);

/**
 * Example: Log player errors to custom system
 */
add_action('yoyaku_player_error', function($message, $context, $exception) {
    // Send to external logging service
    error_log("YOYAKU Player Error [{$context}]: {$message}");
}, 10, 3);

/**
 * Example: Customize player for specific products
 */
add_filter('yoyaku_player_template_args', function($args, $template_name) {
    if ($template_name === 'player-desktop' && $args['product_id'] === 123) {
        $args['custom_theme'] = 'special';
    }
    return $args;
}, 10, 2);
```

---

## AJAX API Reference

### Endpoint: `yoyaku_player_get_track`

**Description:** Retrieves track data for a specific product

**Method:** POST  
**Action:** `yoyaku_player_get_track`  
**Authentication:** WordPress nonce required

#### Request Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `product_id` | integer | Yes | WooCommerce product ID |
| `nonce` | string | Yes | WordPress security nonce |
| `format` | string | No | Response format (json, xml) |
| `include_waveform` | boolean | No | Include waveform data |
| `cache_bust` | boolean | No | Force cache refresh |

#### Request Example

```javascript
const requestData = new FormData();
requestData.append('action', 'yoyaku_player_get_track');
requestData.append('product_id', '12345');
requestData.append('nonce', yoyaku_player_v3.nonce);
requestData.append('include_waveform', 'true');

fetch(yoyaku_player_v3.ajax_url, {
    method: 'POST',
    body: requestData,
    credentials: 'same-origin'
})
.then(response => response.json())
.then(data => {
    if (data.success) {
        console.log('Track data:', data.data);
    } else {
        console.error('Error:', data.data.message);
    }
});
```

#### Response Structure

**Success Response:**
```json
{
    "success": true,
    "data": {
        "product": {
            "id": 12345,
            "title": "Album Title",
            "artist": "Artist Name",
            "label": "Record Label",
            "sku": "ALBUM001",
            "cover": "https://example.com/cover.jpg",
            "metadata": {
                "release_date": "2025-01-01",
                "genre": "Electronic",
                "total_duration": "45:30"
            }
        },
        "tracks": [
            {
                "id": 0,
                "name": "A1: Track Name",
                "url": "https://example.com/track1.mp3",
                "bpm": 128,
                "duration": "4:32",
                "waveform_data": [0.1, 0.5, 0.8, ...],
                "metadata": {
                    "key": "A minor",
                    "genre": "Techno"
                }
            }
        ],
        "player_config": {
            "autoplay": false,
            "preload": "metadata",
            "volume": 0.8
        },
        "permissions": {
            "can_download": false,
            "can_share": true
        }
    },
    "timestamp": 1642608000,
    "version": "6.1.0"
}
```

**Error Response:**
```json
{
    "success": false,
    "data": {
        "message": "Product not found",
        "code": "product_not_found",
        "details": {
            "product_id": 12345,
            "user_id": 1
        }
    },
    "timestamp": 1642608000
}
```

#### Error Codes

| Code | Description | HTTP Status |
|------|-------------|-------------|
| `invalid_nonce` | Security nonce verification failed | 403 |
| `missing_product_id` | Product ID parameter not provided | 400 |
| `product_not_found` | Product does not exist or is not published | 404 |
| `no_tracks` | Product has no audio tracks | 404 |
| `permission_denied` | User lacks permission to access tracks | 403 |
| `rate_limit_exceeded` | Too many requests from user/IP | 429 |
| `server_error` | Internal server error | 500 |

#### Rate Limiting

- **Limit:** 100 requests per minute per user/IP
- **Headers:** Rate limit info included in response headers
- **Reset:** Rate limit window resets every minute

#### Caching

- **Duration:** 5 minutes for track data
- **Cache Key:** `yoyaku_tracks_{product_id}_{user_id}`
- **Invalidation:** Automatic on product update

### Endpoint: `yoyaku_player_log_error`

**Description:** Logs client-side errors for debugging

**Method:** POST  
**Action:** `yoyaku_player_log_error`  
**Authentication:** WordPress nonce required

#### Request Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `error_data` | string | Yes | JSON encoded error data |
| `nonce` | string | Yes | WordPress security nonce |

#### Request Example

```javascript
const errorData = {
    message: 'Audio failed to load',
    error: 'NetworkError: Failed to fetch',
    timestamp: new Date().toISOString(),
    userAgent: navigator.userAgent,
    url: window.location.href
};

const requestData = new FormData();
requestData.append('action', 'yoyaku_player_log_error');
requestData.append('error_data', JSON.stringify(errorData));
requestData.append('nonce', yoyaku_player_v3.nonce);

fetch(yoyaku_player_v3.ajax_url, {
    method: 'POST',
    body: requestData
});
```

---

## Database Schema

### Custom Tables

The plugin primarily uses WordPress post meta for data storage, but may create custom tables for enhanced functionality.

#### Table: `wp_yoyaku_player_analytics` (Optional)

```sql
CREATE TABLE `wp_yoyaku_player_analytics` (
    `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
    `product_id` bigint(20) unsigned NOT NULL,
    `track_index` int(11) NOT NULL DEFAULT 0,
    `user_id` bigint(20) unsigned DEFAULT NULL,
    `session_id` varchar(32) NOT NULL,
    `event_type` varchar(50) NOT NULL,
    `event_data` longtext,
    `ip_address` varchar(45) DEFAULT NULL,
    `user_agent` text,
    `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `product_id` (`product_id`),
    KEY `user_id` (`user_id`),
    KEY `session_id` (`session_id`),
    KEY `event_type` (`event_type`),
    KEY `created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

#### Table: `wp_yoyaku_player_cache` (Optional)

```sql
CREATE TABLE `wp_yoyaku_player_cache` (
    `cache_key` varchar(255) NOT NULL,
    `cache_value` longtext NOT NULL,
    `expiry` datetime NOT NULL,
    `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`cache_key`),
    KEY `expiry` (`expiry`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### WordPress Post Meta Fields

#### Product Meta Fields

| Meta Key | Type | Description | Example |
|----------|------|-------------|---------|
| `_yoyaku_playlist_files` | array | Audio track data | `[{"name": "Track 1", "url": "...", "bpm": 128}]` |
| `_yoyaku_player_config` | array | Player configuration | `{"autoplay": false, "theme": "dark"}` |
| `_yoyaku_waveform_data` | array | Pre-generated waveform data | `[0.1, 0.5, 0.8, ...]` |
| `_yoyaku_track_analytics` | array | Track analytics data | `{"total_plays": 100, "avg_listen_time": 120}` |

#### User Meta Fields

| Meta Key | Type | Description | Example |
|----------|------|-------------|---------|
| `yoyaku_player_preferences` | array | User player preferences | `{"volume": 0.8, "autoplay": true}` |
| `yoyaku_player_history` | array | Listening history | `[{"product_id": 123, "timestamp": 1642608000}]` |

### Data Structure Examples

#### Playlist Files Structure

```php
$playlist_files = [
    [
        'name' => 'A1: Opening Track',
        'url' => 'https://cdn.example.com/audio/track1.mp3',
        'bpm' => 128,
        'duration' => '4:32',
        'key' => 'A minor',
        'genre' => 'Techno',
        'file_size' => 8847360, // bytes
        'bitrate' => 320, // kbps
        'sample_rate' => 44100, // Hz
        'channels' => 2,
        'metadata' => [
            'isrc' => 'USRC17607839',
            'composer' => 'Artist Name',
            'publisher' => 'Publishing Company'
        ]
    ],
    [
        'name' => 'A2: Second Track',
        'url' => 'https://cdn.example.com/audio/track2.mp3',
        'bpm' => 132,
        'duration' => '5:15',
        'key' => 'C major',
        'genre' => 'House'
    ]
];

update_post_meta($product_id, '_yoyaku_playlist_files', $playlist_files);
```

#### Player Configuration Structure

```php
$player_config = [
    'theme' => 'default', // default, dark, light, custom
    'layout' => 'auto', // auto, desktop, mobile
    'autoplay' => false,
    'preload' => 'metadata', // none, metadata, auto
    'volume' => 0.8,
    'loop' => false,
    'shuffle' => false,
    'crossfade' => 0, // seconds
    'pitch_control' => true,
    'waveform' => [
        'color' => '#ffffff',
        'progress_color' => '#ffd700',
        'height' => 24
    ],
    'controls' => [
        'show_playlist' => true,
        'show_volume' => true,
        'show_pitch' => true,
        'show_cart' => true
    ]
];

update_post_meta($product_id, '_yoyaku_player_config', $player_config);
```

---

## Asset Management

### CSS Architecture

#### File Structure
```
assets/css/
├── frontend.css                 # Main production CSS (minified)
├── frontend.scss                # Source SCSS file
├── admin.css                    # Admin interface styles
├── components/
│   ├── _player.scss             # Main player component
│   ├── _controls.scss           # Control buttons
│   ├── _waveform.scss           # Waveform visualization
│   ├── _playlist.scss           # Playlist dropdown
│   └── _responsive.scss         # Responsive layouts
├── themes/
│   ├── _default.scss            # Default theme
│   ├── _dark.scss               # Dark theme
│   └── _light.scss              # Light theme
└── vendor/
    └── _normalize.scss          # CSS reset/normalize
```

#### SCSS Build Process

```scss
// frontend.scss - Main entry point
@import 'vendor/normalize';
@import 'themes/default';
@import 'components/player';
@import 'components/controls';
@import 'components/waveform';
@import 'components/playlist';
@import 'components/responsive';

// CSS Variables for theming
:root {
    --yoyaku-primary-color: #ffd700;
    --yoyaku-secondary-color: #ffffff;
    --yoyaku-background-color: rgba(0, 0, 0, 0.8);
    --yoyaku-text-color: #ffffff;
    --yoyaku-border-radius: 4px;
    --yoyaku-transition-speed: 0.3s;
    --yoyaku-font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    
    // Responsive breakpoints
    --yoyaku-mobile-breakpoint: 768px;
    --yoyaku-tablet-breakpoint: 1024px;
    
    // Player dimensions
    --yoyaku-player-height-desktop: 48px;
    --yoyaku-player-height-mobile: 120px;
    --yoyaku-control-size: 32px;
    --yoyaku-vinyl-size: 45px;
}

// Dark theme override
[data-theme="dark"] {
    --yoyaku-background-color: rgba(0, 0, 0, 0.9);
    --yoyaku-text-color: #ffffff;
    --yoyaku-secondary-color: rgba(255, 255, 255, 0.8);
}

// Light theme override
[data-theme="light"] {
    --yoyaku-background-color: rgba(255, 255, 255, 0.9);
    --yoyaku-text-color: #333333;
    --yoyaku-secondary-color: rgba(0, 0, 0, 0.6);
}
```

#### Responsive Design System

```scss
// _responsive.scss
.yoyaku-player-ultra-fin {
    // Desktop layout (default)
    display: grid;
    grid-template-columns: 60px 1fr 200px 120px 40px;
    grid-template-areas: "vinyl playlist waveform controls cart";
    height: var(--yoyaku-player-height-desktop);
    
    // Mobile layout
    @media (max-width: #{map-get($breakpoints, mobile)}) {
        grid-template-rows: 60px 60px;
        grid-template-columns: 60px 1fr 140px 40px;
        grid-template-areas: 
            "vinyl metadata controls cart"
            "vinyl waveform waveform waveform";
        height: var(--yoyaku-player-height-mobile);
        
        // Hide desktop-only elements
        .desktop-only {
            display: none;
        }
        
        // Adjust control sizes for touch
        .control-btn {
            min-height: 44px;
            min-width: 44px;
        }
    }
    
    // Tablet adjustments
    @media (min-width: #{map-get($breakpoints, mobile) + 1px}) and (max-width: #{map-get($breakpoints, tablet)}) {
        grid-template-columns: 60px 1fr 180px 100px 40px;
        
        .pitch-control {
            display: none; // Hide pitch on tablet
        }
    }
}
```

### JavaScript Architecture

#### Module System

```javascript
// assets/js/modules/audio-engine.js
export class AudioEngine {
    constructor(provider, config = {}) {
        this.provider = provider;
        this.config = config;
        this.state = {
            isPlaying: false,
            position: 0,
            duration: 0,
            volume: 0.8
        };
    }
    
    async load(url) {
        return this.provider.load(url);
    }
    
    play() {
        return this.provider.play();
    }
    
    pause() {
        return this.provider.pause();
    }
    
    seek(position) {
        return this.provider.seek(position);
    }
    
    setVolume(volume) {
        this.state.volume = Math.max(0, Math.min(1, volume));
        return this.provider.setVolume(this.state.volume);
    }
}

// assets/js/modules/waveform-handler.js
export class WaveformHandler {
    constructor(container, options = {}) {
        this.container = container;
        this.options = {
            waveColor: 'rgba(255, 255, 255, 0.3)',
            progressColor: '#ffd700',
            height: 24,
            responsive: true,
            ...options
        };
        this.wavesurfer = null;
    }
    
    async init() {
        if (!window.WaveSurfer) {
            throw new Error('WaveSurfer.js not loaded');
        }
        
        this.wavesurfer = WaveSurfer.create({
            container: this.container,
            ...this.options
        });
        
        return new Promise((resolve, reject) => {
            this.wavesurfer.on('ready', resolve);
            this.wavesurfer.on('error', reject);
        });
    }
    
    load(url) {
        return this.wavesurfer.load(url);
    }
    
    on(event, callback) {
        return this.wavesurfer.on(event, callback);
    }
}

// assets/js/modules/responsive-manager.js
export class ResponsiveManager {
    constructor(breakpoints = {}) {
        this.breakpoints = {
            mobile: 768,
            tablet: 1024,
            ...breakpoints
        };
        this.currentBreakpoint = this.getCurrentBreakpoint();
        this.listeners = new Map();
        this.init();
    }
    
    init() {
        window.addEventListener('resize', this.debounce(() => {
            const newBreakpoint = this.getCurrentBreakpoint();
            if (newBreakpoint !== this.currentBreakpoint) {
                this.currentBreakpoint = newBreakpoint;
                this.emit('breakpoint:change', newBreakpoint);
            }
        }, 250));
    }
    
    getCurrentBreakpoint() {
        const width = window.innerWidth;
        if (width <= this.breakpoints.mobile) return 'mobile';
        if (width <= this.breakpoints.tablet) return 'tablet';
        return 'desktop';
    }
    
    isMobile() {
        return this.currentBreakpoint === 'mobile';
    }
    
    on(event, callback) {
        if (!this.listeners.has(event)) {
            this.listeners.set(event, []);
        }
        this.listeners.get(event).push(callback);
    }
    
    emit(event, data) {
        const callbacks = this.listeners.get(event);
        if (callbacks) {
            callbacks.forEach(callback => callback(data));
        }
    }
    
    debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }
}
```

#### Asset Loading Strategy

```php
class AssetsManager {
    
    private $version;
    private $debug;
    
    public function __construct($version, $debug = false) {
        this->version = $version;
        this->debug = $debug;
    }
    
    public function enqueue_frontend_assets() {
        // Conditional loading based on page type
        if (!$this->should_load_assets()) {
            return;
        }
        
        // CSS with RTL support
        $css_file = $this->debug ? 'frontend.css' : 'frontend.min.css';
        wp_enqueue_style(
            'yoyaku-player-frontend',
            YPV3_PLUGIN_URL . "assets/css/{$css_file}",
            [],
            $this->version,
            'all'
        );
        
        // Add RTL support
        wp_style_add_data('yoyaku-player-frontend', 'rtl', 'replace');
        
        // JavaScript with module support
        $js_file = $this->debug ? 'frontend.js' : 'frontend.min.js';
        wp_enqueue_script(
            'yoyaku-player-frontend',
            YPV3_PLUGIN_URL . "assets/js/{$js_file}",
            ['jquery'],
            $this->version,
            true
        );
        
        // Add module attribute for modern browsers
        wp_script_add_data('yoyaku-player-frontend', 'type', 'module');
        
        // Localized configuration
        wp_localize_script('yoyaku-player-frontend', 'yoyaku_player_config', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('yoyaku_player_nonce'),
            'plugin_url' => YPV3_PLUGIN_URL,
            'version' => $this->version,
            'debug' => $this->debug,
            'i18n' => $this->get_i18n_strings(),
            'breakpoints' => $this->get_responsive_breakpoints(),
            'audio_config' => $this->get_audio_config(),
            'user' => [
                'id' => get_current_user_id(),
                'can_manage' => current_user_can('manage_options')
            ]
        ]);
        
        // Preload critical resources
        $this->add_resource_hints();
    }
    
    private function add_resource_hints() {
        // Preload WaveSurfer.js
        wp_add_inline_script('yoyaku-player-frontend', '
            const link = document.createElement("link");
            link.rel = "preload";
            link.href = "https://unpkg.com/wavesurfer.js@7.8.0/dist/wavesurfer.umd.min.js";
            link.as = "script";
            link.crossOrigin = "anonymous";
            document.head.appendChild(link);
        ', 'before');
        
        // DNS prefetch for audio CDN
        add_action('wp_head', function() {
            echo '<link rel="dns-prefetch" href="//cdn.yoyaku.io">';
            echo '<link rel="preconnect" href="https://unpkg.com" crossorigin>';
        });
    }
    
    private function get_i18n_strings() {
        return [
            'play' => __('Play', 'yoyaku-player-v3'),
            'pause' => __('Pause', 'yoyaku-player-v3'),
            'previous' => __('Previous Track', 'yoyaku-player-v3'),
            'next' => __('Next Track', 'yoyaku-player-v3'),
            'volume' => __('Volume', 'yoyaku-player-v3'),
            'pitch' => __('Pitch Control', 'yoyaku-player-v3'),
            'add_to_cart' => __('Add to Cart', 'yoyaku-player-v3'),
            'loading' => __('Loading...', 'yoyaku-player-v3'),
            'error' => __('Error loading audio', 'yoyaku-player-v3'),
            'no_tracks' => __('No tracks available', 'yoyaku-player-v3')
        ];
    }
}
```

---

## Security Implementation

### Security Layers

#### 1. Input Validation & Sanitization

```php
class SecurityManager {
    
    /**
     * Comprehensive input validation
     */
    public function validate_ajax_request($data) {
        $errors = [];
        
        // Nonce verification
        if (!wp_verify_nonce($data['nonce'], 'yoyaku_player_nonce')) {
            $errors[] = 'Invalid security token';
        }
        
        // Product ID validation
        if (!isset($data['product_id'])) {
            $errors[] = 'Product ID is required';
        } elseif (!is_numeric($data['product_id']) || intval($data['product_id']) <= 0) {
            $errors[] = 'Invalid product ID format';
        }
        
        // Rate limiting check
        if ($this->is_rate_limited()) {
            $errors[] = 'Too many requests';
        }
        
        // User capability check
        if (!$this->user_can_access_tracks($data['product_id'])) {
            $errors[] = 'Insufficient permissions';
        }
        
        return $errors;
    }
    
    /**
     * Advanced rate limiting with multiple strategies
     */
    private function is_rate_limited() {
        $user_id = get_current_user_id();
        $ip_address = $this->get_client_ip();
        
        // Different limits for different user types
        $limits = [
            'guest' => ['requests' => 50, 'window' => 300], // 5 minutes
            'user' => ['requests' => 100, 'window' => 300],
            'premium' => ['requests' => 200, 'window' => 300]
        ];
        
        $user_type = $this->get_user_type($user_id);
        $limit_config = $limits[$user_type];
        
        // Check both user-based and IP-based limits
        $user_key = "rate_limit_user_{$user_id}";
        $ip_key = "rate_limit_ip_" . md5($ip_address);
        
        return $this->check_rate_limit($user_key, $limit_config) || 
               $this->check_rate_limit($ip_key, $limit_config);
    }
    
    /**
     * Content Security Policy headers
     */
    public function add_security_headers() {
        // Content Security Policy
        $csp = [
            "default-src 'self'",
            "script-src 'self' 'unsafe-inline' https://unpkg.com https://cdn.jsdelivr.net",
            "style-src 'self' 'unsafe-inline'",
            "img-src 'self' data: https:",
            "media-src 'self' https:",
            "connect-src 'self' https://unpkg.com",
            "font-src 'self' data:",
            "object-src 'none'",
            "base-uri 'self'",
            "form-action 'self'"
        ];
        
        header('Content-Security-Policy: ' . implode('; ', $csp));
        
        // Additional security headers
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: SAMEORIGIN');
        header('X-XSS-Protection: 1; mode=block');
        header('Referrer-Policy: strict-origin-when-cross-origin');
    }
    
    /**
     * Audio file access control
     */
    public function secure_audio_access($url, $product_id) {
        // Check if user has purchased the product
        if (!$this->user_owns_product($product_id)) {
            // Return preview URL instead
            return $this->get_preview_url($product_id);
        }
        
        // Add temporary access token for full track
        return $this->add_access_token($url, $product_id);
    }
    
    /**
     * SQL injection prevention
     */
    public function safe_query($query, $params = []) {
        global $wpdb;
        
        // Use prepared statements
        if (!empty($params)) {
            return $wpdb->prepare($query, $params);
        }
        
        return $query;
    }
}
```

#### 2. Data Encryption & Privacy

```php
class DataProtection {
    
    private $encryption_key;
    
    public function __construct() {
        $this->encryption_key = $this->get_encryption_key();
    }
    
    /**
     * Encrypt sensitive data
     */
    public function encrypt($data) {
        $method = 'AES-256-CBC';
        $iv = random_bytes(16);
        
        $encrypted = openssl_encrypt(
            json_encode($data),
            $method,
            $this->encryption_key,
            OPENSSL_RAW_DATA,
            $iv
        );
        
        return base64_encode($iv . $encrypted);
    }
    
    /**
     * Decrypt sensitive data
     */
    public function decrypt($encrypted_data) {
        $method = 'AES-256-CBC';
        $data = base64_decode($encrypted_data);
        $iv = substr($data, 0, 16);
        $encrypted = substr($data, 16);
        
        $decrypted = openssl_decrypt(
            $encrypted,
            $method,
            $this->encryption_key,
            OPENSSL_RAW_DATA,
            $iv
        );
        
        return json_decode($decrypted, true);
    }
    
    /**
     * GDPR compliance - data anonymization
     */
    public function anonymize_user_data($user_id) {
        // Remove personal data while keeping analytics
        $anonymous_id = 'anon_' . wp_hash($user_id);
        
        // Update analytics records
        global $wpdb;
        $wpdb->update(
            $wpdb->prefix . 'yoyaku_player_analytics',
            ['user_id' => 0, 'session_id' => $anonymous_id],
            ['user_id' => $user_id],
            ['%d', '%s'],
            ['%d']
        );
        
        // Remove user preferences
        delete_user_meta($user_id, 'yoyaku_player_preferences');
        delete_user_meta($user_id, 'yoyaku_player_history');
    }
    
    /**
     * Secure session management
     */
    public function create_secure_session($user_id, $product_id) {
        $session_data = [
            'user_id' => $user_id,
            'product_id' => $product_id,
            'created_at' => time(),
            'expires_at' => time() + (30 * 60), // 30 minutes
            'ip_address' => $this->get_client_ip(),
            'user_agent' => substr($_SERVER['HTTP_USER_AGENT'], 0, 255)
        ];
        
        $session_id = wp_generate_uuid4();
        $encrypted_data = $this->encrypt($session_data);
        
        // Store in secure cookie
        setcookie(
            'yoyaku_session',
            $session_id,
            time() + (30 * 60),
            '/',
            '',
            is_ssl(),
            true // httponly
        );
        
        // Store encrypted data in database
        set_transient('yoyaku_session_' . $session_id, $encrypted_data, 30 * 60);
        
        return $session_id;
    }
}
```

---

## Performance Optimization

### Caching Strategy

```php
class CacheManager {
    
    private $cache_groups = [
        'tracks' => 3600,      // 1 hour
        'products' => 1800,    // 30 minutes  
        'waveforms' => 86400,  // 24 hours
        'analytics' => 300     // 5 minutes
    ];
    
    /**
     * Multi-level caching implementation
     */
    public function get($key, $group = 'default') {
        // Level 1: Object cache (Redis/Memcached)
        $cached = wp_cache_get($key, $group);
        if ($cached !== false) {
            return $cached;
        }
        
        // Level 2: Transients (Database)
        $transient_key = "yoyaku_{$group}_{$key}";
        $cached = get_transient($transient_key);
        if ($cached !== false) {
            // Restore to object cache
            wp_cache_set($key, $cached, $group, $this->cache_groups[$group] ?? 300);
            return $cached;
        }
        
        return false;
    }
    
    public function set($key, $data, $group = 'default', $expiration = null) {
        $expiration = $expiration ?? ($this->cache_groups[$group] ?? 300);
        
        // Store in both levels
        wp_cache_set($key, $data, $group, $expiration);
        
        $transient_key = "yoyaku_{$group}_{$key}";
        set_transient($transient_key, $data, $expiration);
        
        return true;
    }
    
    /**
     * Cache invalidation with dependency tracking
     */
    public function invalidate($keys, $group = 'default') {
        if (!is_array($keys)) {
            $keys = [$keys];
        }
        
        foreach ($keys as $key) {
            wp_cache_delete($key, $group);
            delete_transient("yoyaku_{$group}_{$key}");
        }
        
        // Trigger dependent cache invalidation
        $this->invalidate_dependencies($keys, $group);
    }
    
    /**
     * Smart cache warming
     */
    public function warm_cache($product_ids) {
        // Use background processing for large datasets
        if (count($product_ids) > 10) {
            wp_schedule_single_event(time() + 30, 'yoyaku_warm_cache', [$product_ids]);
            return;
        }
        
        foreach ($product_ids as $product_id) {
            $this->warm_product_cache($product_id);
        }
    }
    
    private function warm_product_cache($product_id) {
        // Pre-load frequently accessed data
        $this->get_track_data($product_id);
        $this->get_product_metadata($product_id);
        $this->generate_waveform_data($product_id);
    }
}
```

### Database Optimization

```php
class DatabaseOptimizer {
    
    /**
     * Optimized query for track data
     */
    public function get_tracks_optimized($product_ids) {
        global $wpdb;
        
        // Single query instead of multiple meta queries
        $placeholders = implode(',', array_fill(0, count($product_ids), '%d'));
        
        $query = $wpdb->prepare("
            SELECT 
                pm.post_id as product_id,
                pm.meta_value as playlist_data,
                p.post_title,
                p.post_status
            FROM {$wpdb->postmeta} pm
            INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID
            WHERE pm.meta_key = '_yoyaku_playlist_files'
            AND pm.post_id IN ({$placeholders})
            AND p.post_status = 'publish'
        ", $product_ids);
        
        $results = $wpdb->get_results($query);
        
        // Process and cache results
        $processed = [];
        foreach ($results as $row) {
            $playlist_data = maybe_unserialize($row->playlist_data);
            if (is_array($playlist_data)) {
                $processed[$row->product_id] = [
                    'title' => $row->post_title,
                    'tracks' => $playlist_data
                ];
            }
        }
        
        return $processed;
    }
    
    /**
     * Index optimization suggestions
     */
    public function optimize_database() {
        global $wpdb;
        
        // Create custom indexes for better performance
        $indexes = [
            "CREATE INDEX idx_yoyaku_meta ON {$wpdb->postmeta} (meta_key, post_id) 
             WHERE meta_key IN ('_yoyaku_playlist_files', '_yoyaku_player_config')",
            
            "CREATE INDEX idx_yoyaku_analytics_product ON {$wpdb->prefix}yoyaku_player_analytics 
             (product_id, event_type, created_at)",
             
            "CREATE INDEX idx_yoyaku_cache_expiry ON {$wpdb->prefix}yoyaku_player_cache 
             (expiry, cache_key)"
        ];
        
        foreach ($indexes as $index) {
            $wpdb->query($index);
        }
    }
    
    /**
     * Query monitoring and optimization
     */
    public function monitor_slow_queries() {
        // Log queries that take longer than 100ms
        add_filter('query', function($query) {
            $start_time = microtime(true);
            
            register_shutdown_function(function() use ($query, $start_time) {
                $execution_time = microtime(true) - $start_time;
                
                if ($execution_time > 0.1) { // 100ms threshold
                    error_log("Slow YOYAKU query ({$execution_time}s): {$query}");
                }
            });
            
            return $query;
        });
    }
}
```

### Frontend Performance

```javascript
/**
 * Performance monitoring and optimization
 */
class PerformanceOptimizer {
    
    constructor() {
        this.metrics = {
            loadTime: 0,
            renderTime: 0,
            audioLatency: 0,
            memoryUsage: 0
        };
        
        this.observer = null;
        this.init();
    }
    
    init() {
        // Performance Observer for monitoring
        if ('PerformanceObserver' in window) {
            this.observer = new PerformanceObserver((list) => {
                this.processPerformanceEntries(list.getEntries());
            });
            
            this.observer.observe({
                entryTypes: ['measure', 'navigation', 'resource']
            });
        }
        
        // Memory usage monitoring
        this.monitorMemory();
        
        // Network monitoring
        this.monitorNetwork();
    }
    
    /**
     * Lazy loading implementation
     */
    lazyLoadAudio(trackUrl) {
        return new Promise((resolve, reject) => {
            // Use Intersection Observer for viewport-based loading
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        this.loadAudio(trackUrl).then(resolve).catch(reject);
                        observer.disconnect();
                    }
                });
            });
            
            // Observe player container
            const playerElement = document.getElementById('yoyaku-player');
            if (playerElement) {
                observer.observe(playerElement);
            }
        });
    }
    
    /**
     * Resource preloading strategy
     */
    preloadCriticalResources() {
        // Preload WaveSurfer.js
        this.preloadScript('https://unpkg.com/wavesurfer.js@7.8.0/dist/wavesurfer.umd.min.js');
        
        // Preload first track of current product
        const firstTrackUrl = this.getFirstTrackUrl();
        if (firstTrackUrl) {
            this.preloadAudio(firstTrackUrl);
        }
    }
    
    preloadScript(url) {
        const link = document.createElement('link');
        link.rel = 'preload';
        link.as = 'script';
        link.href = url;
        link.crossOrigin = 'anonymous';
        document.head.appendChild(link);
    }
    
    preloadAudio(url) {
        const audio = new Audio();
        audio.preload = 'metadata';
        audio.src = url;
    }
    
    /**
     * Bundle splitting and dynamic imports
     */
    async loadModule(moduleName) {
        const moduleMap = {
            'waveform': () => import('./modules/waveform-handler.js'),
            'analytics': () => import('./modules/analytics.js'),
            'social': () => import('./modules/social-sharing.js')
        };
        
        if (moduleMap[moduleName]) {
            try {
                performance.mark(`module-${moduleName}-start`);
                const module = await moduleMap[moduleName]();
                performance.mark(`module-${moduleName}-end`);
                performance.measure(`module-${moduleName}-load`, 
                    `module-${moduleName}-start`, `module-${moduleName}-end`);
                
                return module;
            } catch (error) {
                console.error(`Failed to load module ${moduleName}:`, error);
                throw error;
            }
        }
        
        throw new Error(`Unknown module: ${moduleName}`);
    }
    
    /**
     * Memory leak prevention
     */
    cleanup() {
        // Clean up event listeners
        if (this.observer) {
            this.observer.disconnect();
        }
        
        // Clear timers and intervals
        this.clearAllTimers();
        
        // Cleanup audio resources
        if (this.audioEngine) {
            this.audioEngine.cleanup();
        }
        
        // Clear references
        this.metrics = null;
        this.observer = null;
    }
    
    /**
     * Performance reporting
     */
    getPerformanceReport() {
        return {
            metrics: this.metrics,
            navigation: this.getNavigationTiming(),
            resources: this.getResourceTiming(),
            memory: this.getMemoryInfo()
        };
    }
    
    sendPerformanceData() {
        const report = this.getPerformanceReport();
        
        // Send to analytics endpoint
        navigator.sendBeacon('/wp-admin/admin-ajax.php', new URLSearchParams({
            action: 'yoyaku_player_performance',
            data: JSON.stringify(report),
            nonce: yoyaku_player_config.nonce
        }));
    }
}
```

---

This documentation provides comprehensive technical details for expert developers who need to understand, maintain, or extend the YOYAKU Player V3 plugin. Each section includes practical examples, best practices, and implementation details following WordPress coding standards.

The architecture is designed for maintainability, security, and performance while providing extensive customization options through hooks, filters, and modular design patterns.