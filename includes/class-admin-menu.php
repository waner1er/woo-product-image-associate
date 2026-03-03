<?php
/**
 * Admin Menu class
 *
 * @package WooProductImageAssociate
 */

namespace WooProductImageAssociate;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers the plugin's submenu under WooCommerce in the WordPress admin.
 */
class Admin_Menu {

	/**
	 * Page slug used for the submenu.
	 *
	 * @var string
	 */
	const PAGE_SLUG = 'import-sku-images';

	/**
	 * Admin page instance used as render callback.
	 *
	 * @var Admin_Page
	 */
	private $admin_page;

	/**
	 * Constructor.
	 *
	 * @param Admin_Page $admin_page Admin page instance.
	 */
	public function __construct( Admin_Page $admin_page ) {
		$this->admin_page = $admin_page;
	}

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function register_hooks() {
		add_action( 'admin_menu', array( $this, 'add_submenu' ) );
	}

	/**
	 * Add the plugin submenu page under the WooCommerce menu.
	 *
	 * @return void
	 */
	public function add_submenu() {
		add_submenu_page(
			'woocommerce',
			__( 'Import SKU Images', 'woo-product-image-associate' ),
			__( 'Import SKU Images', 'woo-product-image-associate' ),
			'manage_options',
			self::PAGE_SLUG,
			array( $this->admin_page, 'render' )
		);
	}
}
