<?php
/**
 * DigiCommerce Product Title Block
 *
 * @package DigiCommerce
 */

defined( 'ABSPATH' ) || exit;

/**
 * Product Title Block Class
 */
class DigiCommerce_Product_Title_Block {

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
			DIGICOMMERCE_PLUGIN_DIR . 'assets/blocks/product-title',
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

		// Get product title
		$product_title = get_the_title( $product_id );
		
		if ( empty( $product_title ) ) {
			return '';
		}

		// Get attributes with defaults
		$tag_name = isset( $attributes['tagName'] ) ? $attributes['tagName'] : 'h1';

		// Validate tag name
		$allowed_tags = array( 'h1', 'h2', 'h3', 'h4', 'h5', 'h6' );
		if ( ! in_array( $tag_name, $allowed_tags, true ) ) {
			$tag_name = 'h1';
		}

		// Get wrapper attributes with all the styling supports
		$wrapper_attributes = get_block_wrapper_attributes();

		return sprintf(
			'<%1$s %2$s>%3$s</%1$s>',
			$tag_name,
			$wrapper_attributes,
			esc_html( $product_title )
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
DigiCommerce_Product_Title_Block::init();