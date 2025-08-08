<?php
/**
 * DigiCommerce Product Gallery Block
 *
 * @package DigiCommerce
 */

defined( 'ABSPATH' ) || exit;

/**
 * Product Gallery Block Class
 */
class DigiCommerce_Product_Gallery_Block {

	/**
	 * Initialize the block
	 */
	public static function init() {
		add_action( 'init', array( __CLASS__, 'register_block' ) );
	}

	/**
	 * Register the block
	 */
	public static function register_block() {
		// Register using block.json from assets/blocks folder
		register_block_type(
			DIGICOMMERCE_PLUGIN_DIR . 'assets/blocks/product-gallery',
			array(
				'render_callback' => array( __CLASS__, 'render_block' ),
			)
		);
	}

	/**
	 * Render the block
	 *
	 * @param array    $attributes Block attributes.
	 * @param string   $content Block content.
	 * @param WP_Block $block Block instance.
	 * @return string Rendered block.
	 */
	public static function render_block( $attributes, $content, $block ) {
		// Get current product ID
		$product_id = self::get_current_product_id( $block );
		
		if ( ! $product_id ) {
			return '';
		}

		// Get gallery data
		$gallery_data = self::get_gallery_data( $product_id );
		
		if ( empty( $gallery_data ) ) {
			return '';
		}

		// Prepare attributes
		$show_thumbnails = isset( $attributes['showThumbnails'] ) ? $attributes['showThumbnails'] : true;
		$thumbnails_position = isset( $attributes['thumbnailsPosition'] ) ? $attributes['thumbnailsPosition'] : 'bottom';
		$enable_lightbox = isset( $attributes['enableLightbox'] ) ? $attributes['enableLightbox'] : true;

		// Get wrapper attributes with all the styling supports
		$wrapper_attributes = get_block_wrapper_attributes(
			array( 'class' => 'product-gallery' )
		);

		// Generate gallery HTML
		$gallery_html = self::generate_gallery_html( 
			$gallery_data, 
			$show_thumbnails, 
			$thumbnails_position,
			$enable_lightbox
		);

		if ( empty( $gallery_html ) ) {
			return '';
		}

		return sprintf(
			'<div %s>%s</div>',
			$wrapper_attributes,
			$gallery_html
		);
	}

	/**
	 * Get gallery data for a product
	 *
	 * @param int $product_id Product ID.
	 * @return array Gallery images data.
	 */
	private static function get_gallery_data( $product_id ) {
		$gallery_images = array();

		// Add the featured image to the gallery
		if ( has_post_thumbnail( $product_id ) ) {
			$featured_image_id   = get_post_thumbnail_id( $product_id );
			$featured_image_url  = wp_get_attachment_image_url( $featured_image_id, 'large' );
			$featured_image_full = wp_get_attachment_image_url( $featured_image_id, 'full' );
			$featured_image_thumb = wp_get_attachment_image_url( $featured_image_id, 'medium' );
			$image_metadata      = wp_get_attachment_metadata( $featured_image_id );
			
			$gallery_images[] = array(
				'id'    => $featured_image_id,
				'src'   => $featured_image_full,
				'large' => $featured_image_url,
				'thumb' => $featured_image_thumb,
				'w'     => $image_metadata['width'] ?? 800,
				'h'     => $image_metadata['height'] ?? 600,
				'alt'   => get_post_meta( $featured_image_id, '_wp_attachment_image_alt', true ),
			);
		}

		// Add gallery images to the array
		$gallery = get_post_meta( $product_id, 'digi_gallery', true );
		if ( ! empty( $gallery ) && is_array( $gallery ) ) {
			foreach ( $gallery as $image ) {
				if ( empty( $image['id'] ) ) {
					continue;
				}

				$image_id = $image['id'];
				$image_full = wp_get_attachment_image_url( $image_id, 'full' );
				$image_large = wp_get_attachment_image_url( $image_id, 'large' );
				$image_thumb = wp_get_attachment_image_url( $image_id, 'medium' );
				$image_metadata = wp_get_attachment_metadata( $image_id );
				
				$gallery_images[] = array(
					'id'    => $image_id,
					'src'   => $image_full,
					'large' => $image_large,
					'thumb' => $image_thumb,
					'w'     => $image_metadata['width'] ?? 800,
					'h'     => $image_metadata['height'] ?? 600,
					'alt'   => get_post_meta( $image_id, '_wp_attachment_image_alt', true ),
				);
			}
		}

		return $gallery_images;
	}

	/**
	 * Generate gallery HTML
	 *
	 * @param array  $gallery_data Gallery images data.
	 * @param bool   $show_thumbnails Show thumbnails.
	 * @param string $thumbnails_position Thumbnails position.
	 * @param bool   $enable_lightbox Enable lightbox.
	 * @return string Gallery HTML.
	 */
	private static function generate_gallery_html( $gallery_data, $show_thumbnails, $thumbnails_position, $enable_lightbox ) {
		if ( empty( $gallery_data ) ) {
			return '';
		}

		$html = '';

		// Main image container
		$main_image = $gallery_data[0];
		$html .= '<div class="gallery-main-container">';
		
		if ( $enable_lightbox ) {
			$html .= sprintf(
				'<a href="%s" data-pswp-index="0" data-pswp-width="%d" data-pswp-height="%d">',
				esc_url( $main_image['src'] ),
				esc_attr( $main_image['w'] ),
				esc_attr( $main_image['h'] )
			);
		}

		$html .= sprintf(
			'<img src="%s" class="gallery-main-image" alt="%s">',
			esc_url( $main_image['large'] ),
			esc_attr( $main_image['alt'] )
		);

		if ( $enable_lightbox ) {
			$html .= '</a>';
		}

		$html .= '</div>';

		// Thumbnails
		if ( $show_thumbnails && count( $gallery_data ) > 1 ) {
			$remaining_images = array_slice( $gallery_data, 1 );
			$thumbnails_html = '<div class="gallery-thumbnails">';
			
			foreach ( $remaining_images as $index => $image ) {
				$actual_index = $index + 1; // Since we're skipping the first image
				
				$thumbnails_html .= '<div class="gallery-thumbnail-container">';
				
				if ( $enable_lightbox ) {
					$thumbnails_html .= sprintf(
						'<a href="%s" class="flex" data-pswp-index="%d" data-pswp-width="%d" data-pswp-height="%d">',
						esc_url( $image['src'] ),
						esc_attr( $actual_index ),
						esc_attr( $image['w'] ),
						esc_attr( $image['h'] )
					);
				}

				$thumbnails_html .= sprintf(
					'<img src="%s" class="gallery-thumbnail-image" alt="%s">',
					esc_url( $image['thumb'] ),
					esc_attr( $image['alt'] )
				);

				if ( $enable_lightbox ) {
					$thumbnails_html .= '</a>';
				}
				
				$thumbnails_html .= '</div>';
			}
			
			$thumbnails_html .= '</div>';

			// Add thumbnails based on position
			if ( 'top' === $thumbnails_position ) {
				$html = $thumbnails_html . $html;
			} else {
				$html .= $thumbnails_html;
			}
		}

		return $html;
	}

	/**
	 * Get current product ID from context
	 *
	 * @param WP_Block $block Block instance.
	 * @return int Product ID or 0 if not found.
	 */
	private static function get_current_product_id( $block = null ) {
		// Try block context first
		if ( $block && isset( $block->context['postId'] ) ) {
			$context_post_id = absint( $block->context['postId'] );
			if ( get_post_type( $context_post_id ) === 'digi_product' ) {
				return $context_post_id;
			}
		}

		// Try global post
		global $post;
		if ( is_singular( 'digi_product' ) && $post ) {
			return $post->ID;
		}

		return 0;
	}
}

// Initialize the block
DigiCommerce_Product_Gallery_Block::init();