<?php
/**
 * Plugin Name: Import Products with Images
 * Description: A plugin to import products with images associated by product SKU.
 * Version:     2.0
 * Author:      Erwan RIVET
 * Text Domain: woo-product-image-associate
 * Domain Path: /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Plugin constants.
define( 'WPIA_VERSION', '2.0' );
define( 'WPIA_PLUGIN_FILE', __FILE__ );
define( 'WPIA_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
define( 'WPIA_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'WPIA_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

/**
 * Check that WooCommerce is active before loading the plugin.
 */
function wpia_is_woocommerce_active() {
	return in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins', array() ) ), true );
}

/**
 * Load all plugin classes (simple autoloader).
 *
 * @return void
 */
function wpia_autoload() {
	$classes = array(
		'WooProductImageAssociate\\Product_Image_Matcher' => 'includes/class-product-image-matcher.php',
		'WooProductImageAssociate\\Admin_Menu'            => 'includes/class-admin-menu.php',
		'WooProductImageAssociate\\Admin_Page'            => 'includes/class-admin-page.php',
		'WooProductImageAssociate\\Plugin'                => 'includes/class-plugin.php',
	);

	foreach ( $classes as $class => $file ) {
		if ( ! class_exists( $class ) ) {
			require_once WPIA_PLUGIN_DIR . $file;
		}
	}
}

/**
 * Bootstrap the plugin after all plugins are loaded so we can check for WooCommerce.
 *
 * @return void
 */
function wpia_init() {
	if ( ! wpia_is_woocommerce_active() ) {
		add_action(
			'admin_notices',
			function () {
				echo '<div class="notice notice-error"><p>'
					. esc_html__( 'Import Products with Images requires WooCommerce to be installed and active.', 'woo-product-image-associate' )
					. '</p></div>';
			}
		);
		return;
	}

	wpia_autoload();
	\WooProductImageAssociate\Plugin::get_instance()->init();
}

add_action( 'plugins_loaded', 'wpia_init' );
