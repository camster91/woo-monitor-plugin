<?php
/**
 * Integration tests for WooCommerce Error Monitor
 *
 * @package WooCommerce_Error_Monitor
 */

/**
 * Integration test class for WooCommerce functionality
 */
class Integration_Test extends WP_UnitTestCase {

	/**
	 * Set up test environment
	 */
	public function setUp() {
		parent::setUp();
		
		// Ensure WooCommerce is active
		if ( ! class_exists( 'WooCommerce' ) ) {
			$this->markTestSkipped( 'WooCommerce is not active' );
		}
		
		// Initialize WooCommerce if needed
		WC()->init();
	}

	/**
	 * Test plugin compatibility with WooCommerce
	 */
	public function test_woocommerce_compatibility() {
		// Check that required WooCommerce functions exist
		$this->assertTrue( function_exists( 'wc_add_notice' ) );
		$this->assertTrue( function_exists( 'wc_get_product' ) );
		
		// Test that plugin doesn't break WooCommerce
		$product = wc_get_product( $this->factory->post->create( array(
			'post_type' => 'product',
		) ) );
		
		$this->assertInstanceOf( 'WC_Product', $product );
	}

	/**
	 * Test plugin settings
	 */
	public function test_plugin_settings() {
		// Test that settings can be saved and retrieved
		$test_value = 'test_value_' . time();
		update_option( 'wc-error-monitor_test_option', $test_value );
		
		$retrieved_value = get_option( 'wc-error-monitor_test_option' );
		$this->assertEquals( $test_value, $retrieved_value );
	}

	/**
	 * Test admin functionality
	 */
	public function test_admin_functionality() {
		// Create admin user
		$user_id = $this->factory->user->create( array(
			'role' => 'administrator',
		) );
		
		wp_set_current_user( $user_id );
		
		// Test that admin functions work
		$this->assertTrue( current_user_can( 'manage_options' ) );
	}

	/**
	 * Clean up after tests
	 */
	public function tearDown() {
		// Clean up test data
		delete_option( 'wc-error-monitor_test_option' );
		
		parent::tearDown();
	}
}