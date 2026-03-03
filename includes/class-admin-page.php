<?php
/**
 * Admin Page class
 *
 * @package WooProductImageAssociate
 */

namespace WooProductImageAssociate;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles display and form processing for the plugin's admin page.
 */
class Admin_Page {

	/**
	 * Nonce action name.
	 *
	 * @var string
	 */
	const NONCE_ACTION = 'import_images_nonce';

	/**
	 * Nonce field name.
	 *
	 * @var string
	 */
	const NONCE_FIELD = 'import_images_nonce';

	/**
	 * Register hooks for form processing.
	 *
	 * @return void
	 */
	public function register_hooks() {
		add_action( 'admin_post_import_images', array( $this, 'handle_form_submission' ) );
	}

	/**
	 * Render the admin page.
	 *
	 * @return void
	 */
	public function render() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'woo-product-image-associate' ) );
		}

		settings_errors( 'wpia_notices' );

		$nonce = wp_create_nonce( self::NONCE_ACTION );
		?>
		<div class="wrap wpia-wrap">
			<h1><?php esc_html_e( 'Import SKU Images', 'woo-product-image-associate' ); ?></h1>
			<p><?php esc_html_e( 'Click the button below to associate media library images to WooCommerce products based on their SKU.', 'woo-product-image-associate' ); ?></p>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="import_images">
				<input type="hidden" name="<?php echo esc_attr( self::NONCE_FIELD ); ?>" value="<?php echo esc_attr( $nonce ); ?>">
				<?php submit_button( __( 'Start Import', 'woo-product-image-associate' ), 'primary', 'import_images_button' ); ?>
			</form>
		</div>
		<?php
	}

	/**
	 * Handle the form submission triggered by admin-post.php.
	 *
	 * Verifies nonce and capability, runs the matcher, then redirects back
	 * with result stats stored as a transient.
	 *
	 * @return void
	 */
	public function handle_form_submission() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to perform this action.', 'woo-product-image-associate' ) );
		}

		// Verify nonce.
		$nonce = isset( $_POST[ self::NONCE_FIELD ] ) ? sanitize_text_field( wp_unslash( $_POST[ self::NONCE_FIELD ] ) ) : '';
		if ( ! wp_verify_nonce( $nonce, self::NONCE_ACTION ) ) {
			wp_die( esc_html__( 'Security check failed. Please try again.', 'woo-product-image-associate' ) );
		}

		if ( isset( $_POST['import_images_button'] ) ) {
			$matcher = new Product_Image_Matcher();
			$stats   = $matcher->process_all_products();

			// Build feedback message.
			if ( empty( $stats['errors'] ) ) {
				$message = sprintf(
					/* translators: 1: number of products processed, 2: number of images associated */
					__( 'Import complete. %1$d product(s) processed, %2$d image(s) associated.', 'woo-product-image-associate' ),
					$stats['total_processed'],
					$stats['total_images_added']
				);
				set_transient( 'wpia_admin_notice', array( 'type' => 'success', 'message' => $message ), 60 );
			} else {
				$error_list = implode( ' | ', $stats['errors'] );
				$message    = sprintf(
					/* translators: 1: number of products processed, 2: number of images associated, 3: list of errors */
					__( 'Import finished with errors. %1$d product(s) processed, %2$d image(s) associated. Errors: %3$s', 'woo-product-image-associate' ),
					$stats['total_processed'],
					$stats['total_images_added'],
					$error_list
				);
				set_transient( 'wpia_admin_notice', array( 'type' => 'error', 'message' => $message ), 60 );
			}
		}

		wp_safe_redirect( admin_url( 'admin.php?page=' . Admin_Menu::PAGE_SLUG ) );
		exit;
	}
}
