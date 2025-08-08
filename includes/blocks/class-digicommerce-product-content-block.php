<?php
/**
 * DigiCommerce Product Content Block
 *
 * @package DigiCommerce
 */

defined( 'ABSPATH' ) || exit;

/**
 * Product Content Block Class
 */
class DigiCommerce_Product_Content_Block {

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
			DIGICOMMERCE_PLUGIN_DIR . 'assets/blocks/product-content',
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

		// Get product post
		$product_post = get_post( $product_id );
		
		if ( ! $product_post || empty( $product_post->post_content ) ) {
			return '';
		}

		// Prepare attributes
		$show_title = isset( $attributes['showTitle'] ) ? $attributes['showTitle'] : false;

		// Process content
		$processed_content = apply_filters( 'the_content', $product_post->post_content );

		// Build output
		$output = '';
		
		if ( $show_title ) {
			$output .= '<h2 class="digicommerce-product-content__title">' . esc_html__( 'Description', 'digicommerce' ) . '</h2>';
		}
		
		$output .= '<div class="digicommerce-product-content__body">' . $processed_content . '</div>';

		// Get wrapper attributes with all the styling supports
		$wrapper_attributes = get_block_wrapper_attributes();

		return sprintf(
			'<div %s>%s</div>',
			$wrapper_attributes,
			$output
		);
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
DigiCommerce_Product_Content_Block::init();