<?php
/**
 * Product Image Matcher class
 *
 * @package WooProductImageAssociate
 */

namespace WooProductImageAssociate;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles the business logic for matching and associating images to products.
 */
class Product_Image_Matcher {

	/**
	 * Cache of all media library images.
	 *
	 * @var array|null
	 */
	private $images_cache = null;

	/**
	 * Number of products to process per batch.
	 *
	 * @var int
	 */
	private $batch_size = 50;

	/**
	 * Process all products with pagination for better performance.
	 *
	 * @return array Stats array with total_processed, total_images_added, errors.
	 */
	public function process_all_products() {
		set_time_limit( 300 );

		$offset = 0;
		$stats  = array(
			'total_processed'    => 0,
			'total_images_added' => 0,
			'errors'             => array(),
		);

		do_action( 'wpia_before_associate_images' );

		do {
			$products = $this->get_products_batch( $offset );

			foreach ( $products as $product ) {
				try {
					$images_added                 = $this->process_single_product( $product->ID );
					$stats['total_processed']++;
					$stats['total_images_added'] += $images_added;
				} catch ( \Exception $e ) {
					$stats['errors'][] = sprintf(
						/* translators: 1: product ID, 2: error message */
						__( 'Error processing product %1$d: %2$s', 'woo-product-image-associate' ),
						$product->ID,
						$e->getMessage()
					);
				}
			}

			$offset += $this->batch_size;

		} while ( count( $products ) === $this->batch_size );

		do_action( 'wpia_after_associate_images', $stats );

		return $stats;
	}

	/**
	 * Get a batch of products.
	 *
	 * @param int $offset Offset for pagination.
	 * @return \WP_Post[]
	 */
	private function get_products_batch( $offset ) {
		return get_posts(
			array(
				'post_type'      => 'product',
				'posts_per_page' => $this->batch_size,
				'offset'         => $offset,
				'post_status'    => 'publish',
			)
		);
	}

	/**
	 * Process a single product: find matching images and update its gallery.
	 *
	 * @param int $product_id The product post ID.
	 * @return int Number of images added.
	 */
	public function process_single_product( $product_id ) {
		$sku = get_post_meta( $product_id, '_sku', true );

		if ( empty( $sku ) ) {
			return 0;
		}

		do_action( 'wpia_before_process_product', $product_id, $sku );

		$matching_images = $this->find_matching_images( $sku );

		if ( ! empty( $matching_images ) ) {
			$this->update_product_gallery( $product_id, $matching_images );
		}

		do_action( 'wpia_after_process_product', $product_id, $sku, count( $matching_images ) );

		return count( $matching_images );
	}

	/**
	 * Find all media library images whose title matches the SKU pattern.
	 *
	 * @param string $sku The product SKU.
	 * @return int[] Array of matching attachment IDs.
	 */
	private function find_matching_images( $sku ) {
		$images   = $this->get_all_images();
		$matching = array();

		$pattern = apply_filters( 'wpia_sku_pattern', '/^' . preg_quote( $sku, '/' ) . '_/' );

		foreach ( $images as $image ) {
			if ( preg_match( $pattern, $image->post_title ) ) {
				$matching[] = $image->ID;
				do_action( 'wpia_image_associated', $image->ID, $sku );
			}
		}

		return $matching;
	}

	/**
	 * Return all images from the media library, using an in-memory cache.
	 *
	 * @return \WP_Post[]
	 */
	private function get_all_images() {
		if ( null !== $this->images_cache ) {
			return $this->images_cache;
		}

		$args = apply_filters(
			'wpia_image_query_args',
			array(
				'post_type'      => 'attachment',
				'posts_per_page' => -1,
				'post_status'    => 'inherit',
				'post_mime_type' => 'image',
			)
		);

		$this->images_cache = get_posts( $args );

		return $this->images_cache;
	}

	/**
	 * Merge new image IDs into the product's gallery meta field.
	 *
	 * @param int   $product_id    The product post ID.
	 * @param int[] $new_image_ids Array of image attachment IDs to add.
	 * @return void
	 */
	private function update_product_gallery( $product_id, $new_image_ids ) {
		$gallery_ids = get_post_meta( $product_id, '_product_image_gallery', true );

		if ( ! empty( $gallery_ids ) ) {
			$gallery_ids = explode( ',', $gallery_ids );
		} else {
			$gallery_ids = array();
		}

		$gallery_ids = array_merge( $gallery_ids, $new_image_ids );
		$gallery_ids = array_unique( $gallery_ids );
		$gallery_ids = array_filter( $gallery_ids );

		update_post_meta( $product_id, '_product_image_gallery', implode( ',', $gallery_ids ) );
	}
}
