<?php
/**
 * YOYAKU Player v3 Plugin Tests
 *
 * @package YOYAKU_Player_v3
 */

class Test_YOYAKU_Player extends WP_UnitTestCase {

	/**
	 * Test plugin activation
	 */
	public function test_plugin_activation() {
		// Activate the plugin
		activate_plugin( 'yoyaku-player-v3-production/yoyaku-player-v3.php' );
		
		$this->assertTrue( is_plugin_active( 'yoyaku-player-v3-production/yoyaku-player-v3.php' ) );
	}

	/**
	 * Test main plugin class exists
	 */
	public function test_main_class_exists() {
		$this->assertTrue( class_exists( 'YOYAKU_Player_v3' ) );
	}

	/**
	 * Test compatibility class exists
	 */
	public function test_compatibility_class_exists() {
		$this->assertTrue( class_exists( 'YOYAKU_Compatibility' ) );
	}

	/**
	 * Test error handler class exists
	 */
	public function test_error_handler_class_exists() {
		$this->assertTrue( class_exists( 'YOYAKU_Error_Handler' ) );
	}

	/**
	 * Test theme loader class exists
	 */
	public function test_theme_loader_class_exists() {
		$this->assertTrue( class_exists( 'YOYAKU_Theme_Loader' ) );
	}

	/**
	 * Test plugin headers are valid
	 */
	public function test_plugin_headers() {
		$plugin_data = get_plugin_data( dirname( dirname( __FILE__ ) ) . '/yoyaku-player-v3.php' );
		
		$this->assertNotEmpty( $plugin_data['Name'] );
		$this->assertNotEmpty( $plugin_data['Version'] );
		$this->assertNotEmpty( $plugin_data['Description'] );
		$this->assertNotEmpty( $plugin_data['Author'] );
	}

	/**
	 * Test WordPress compatibility
	 */
	public function test_wordpress_compatibility() {
		global $wp_version;
		
		// Plugin should be compatible with WordPress 5.8+
		$this->assertTrue( version_compare( $wp_version, '5.8', '>=' ) );
	}

	/**
	 * Test WooCommerce integration if available
	 */
	public function test_woocommerce_integration() {
		if ( class_exists( 'WooCommerce' ) ) {
			// Test WooCommerce hooks are properly registered
			$this->assertTrue( has_action( 'woocommerce_init' ) );
		}
		
		$this->assertTrue( true ); // Always pass if WooCommerce not available
	}

	/**
	 * Test no fatal errors in main plugin file
	 */
	public function test_no_syntax_errors() {
		$plugin_file = dirname( dirname( __FILE__ ) ) . '/yoyaku-player-v3.php';
		
		// Use php -l to check syntax
		$output = shell_exec( "php -l $plugin_file" );
		$this->assertStringContainsString( 'No syntax errors detected', $output );
	}

	/**
	 * Test security - no dangerous functions
	 */
	public function test_security_no_dangerous_functions() {
		$plugin_files = glob( dirname( dirname( __FILE__ ) ) . '/*.php' );
		
		foreach ( $plugin_files as $file ) {
			$content = file_get_contents( $file );
			
			// Check for dangerous functions
			$this->assertStringNotContainsString( 'eval(', $content );
			$this->assertStringNotContainsString( 'system(', $content );
			$this->assertStringNotContainsString( 'exec(', $content );
			$this->assertStringNotContainsString( 'shell_exec(', $content );
		}
	}

	/**
	 * Test performance - no unnecessary database queries
	 */
	public function test_performance_database_queries() {
		global $wpdb;
		
		$queries_before = count( $wpdb->queries );
		
		// Simulate plugin initialization
		do_action( 'plugins_loaded' );
		
		$queries_after = count( $wpdb->queries );
		$query_diff = $queries_after - $queries_before;
		
		// Plugin should not run more than 5 queries during initialization
		$this->assertLessThanOrEqual( 5, $query_diff );
	}

	/**
	 * Test AJAX functionality if present
	 */
	public function test_ajax_functionality() {
		if ( function_exists( 'yoyaku_ajax_handler' ) ) {
			// Test AJAX endpoints are registered
			$this->assertTrue( has_action( 'wp_ajax_yoyaku_action' ) );
			$this->assertTrue( has_action( 'wp_ajax_nopriv_yoyaku_action' ) );
		}
		
		$this->assertTrue( true ); // Always pass if no AJAX functionality
	}
}