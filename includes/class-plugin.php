<?php
/**
 * Main Plugin class
 *
 * @package WooProductImageAssociate
 */

namespace WooProductImageAssociate;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Orchestrates initialisation of all plugin components.
 */
class Plugin {

	/**
	 * Singleton instance.
	 *
	 * @var Plugin|null
	 */
	private static $instance = null;

	/**
	 * Private constructor to enforce singleton pattern.
	 */
	private function __construct() {}

	/**
	 * Return the single instance of the plugin.
	 *
	 * @return Plugin
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Initialise components and register all hooks.
	 *
	 * @return void
	 */
	public function init() {
		$this->load_textdomain();
		$this->register_hooks();
	}

	/**
	 * Load the plugin text domain for i18n.
	 *
	 * @return void
	 */
	private function load_textdomain() {
		load_plugin_textdomain(
			'woo-product-image-associate',
			false,
			dirname( WPIA_PLUGIN_BASENAME ) . '/languages'
		);
	}

	/**
	 * Register all hooks for the plugin components.
	 *
	 * @return void
	 */
	private function register_hooks() {
		$admin_page = new Admin_Page();
		$admin_page->register_hooks();

		$admin_menu = new Admin_Menu( $admin_page );
		$admin_menu->register_hooks();

		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );

		// Display transient notices on the plugin page.
		add_action( 'admin_notices', array( $this, 'show_admin_notices' ) );
	}

	/**
	 * Enqueue CSS and JS assets only on the plugin's own admin page.
	 *
	 * @param string $hook The current admin page hook suffix.
	 * @return void
	 */
	public function enqueue_assets( $hook ) {
		if ( 'woocommerce_page_' . Admin_Menu::PAGE_SLUG !== $hook ) {
			return;
		}

		wp_enqueue_style(
			'wpia-admin',
			WPIA_PLUGIN_URL . 'assets/css/admin.css',
			array(),
			WPIA_VERSION
		);

		wp_enqueue_script(
			'wpia-admin',
			WPIA_PLUGIN_URL . 'assets/js/admin.js',
			array( 'jquery' ),
			WPIA_VERSION,
			true
		);
	}

	/**
	 * Display transient-based admin notices set during form processing.
	 *
	 * @return void
	 */
	public function show_admin_notices() {
		$notice = get_transient( 'wpia_admin_notice' );
		if ( ! $notice ) {
			return;
		}

		delete_transient( 'wpia_admin_notice' );

		$type    = ( 'error' === $notice['type'] ) ? 'notice-error' : 'notice-success';
		$message = $notice['message'];
		?>
		<div class="notice <?php echo esc_attr( $type ); ?> is-dismissible">
			<p><?php echo esc_html( $message ); ?></p>
		</div>
		<?php
	}
}
