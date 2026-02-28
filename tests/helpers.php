<?php
/**
 * Test helpers for WooCommerce Error Monitor
 *
 * @package WooCommerce_Error_Monitor
 */

/**
 * Create a test WooCommerce product
 *
 * @param array $args Product arguments.
 * @return WC_Product
 */
function create_test_product( $args = array() ) {
	$defaults = array(
		'name'          => 'Test Product ' . time(),
		'regular_price' => '19.99',
		'price'         => '19.99',
		'sku'           => 'TEST-' . time(),
		'manage_stock'  => false,
		'tax_status'    => 'taxable',
		'downloadable'  => false,
		'virtual'       => false,
		'stock_status'  => 'instock',
		'weight'        => '1.5',
	);
	
	$args = wp_parse_args( $args, $defaults );
	
	$product = new WC_Product_Simple();
	$product->set_name( $args['name'] );
	$product->set_regular_price( $args['regular_price'] );
	$product->set_price( $args['price'] );
	$product->set_sku( $args['sku'] );
	$product->set_manage_stock( $args['manage_stock'] );
	$product->set_tax_status( $args['tax_status'] );
	$product->set_downloadable( $args['downloadable'] );
	$product->set_virtual( $args['virtual'] );
	$product->set_stock_status( $args['stock_status'] );
	$product->set_weight( $args['weight'] );
	
	if ( $args['manage_stock'] ) {
		$product->set_stock_quantity( $args['stock_quantity'] ?? 10 );
	}
	
	$product->save();
	
	return $product;
}

/**
 * Create a test WooCommerce order
 *
 * @param array $args Order arguments.
 * @return WC_Order
 */
function create_test_order( $args = array() ) {
	$defaults = array(
		'status'        => 'pending',
		'customer_id'   => 1,
		'customer_note' => '',
		'total'         => '19.99',
	);
	
	$args = wp_parse_args( $args, $defaults );
	
	$order = wc_create_order( $args );
	
	if ( ! $order ) {
		return false;
	}
	
	// Add a product to the order
	$product = create_test_product();
	$order->add_product( $product, 1 );
	
	// Set addresses
	$order->set_billing_first_name( 'John' );
	$order->set_billing_last_name( 'Doe' );
	$order->set_billing_company( 'Test Company' );
	$order->set_billing_address_1( '123 Test St' );
	$order->set_billing_city( 'Test City' );
	$order->set_billing_state( 'CA' );
	$order->set_billing_postcode( '12345' );
	$order->set_billing_country( 'US' );
	$order->set_billing_email( 'john.doe@example.com' );
	$order->set_billing_phone( '555-123-4567' );
	
	$order->save();
	
	return $order;
}

/**
 * Check if a plugin is active
 *
 * @param string $plugin Plugin basename.
 * @return bool
 */
function is_plugin_active_for_test( $plugin ) {
	require_once ABSPATH . 'wp-admin/includes/plugin.php';
	return is_plugin_active( $plugin );
}