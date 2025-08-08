<?php
/**
 * DigiCommerce Product Features Block
 *
 * @package DigiCommerce
 */

defined( 'ABSPATH' ) || exit;

/**
 * Product Features Block Class
 */
class DigiCommerce_Product_Features_Block {

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
			DIGICOMMERCE_PLUGIN_DIR . 'assets/blocks/product-features',
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

		// Get product features
		$features = get_post_meta( $product_id, 'digi_features', true );
		
		if ( empty( $features ) || ! is_array( $features ) ) {
			return '';
		}

		// Prepare attributes
		$show_title = isset( $attributes['showTitle'] ) ? $attributes['showTitle'] : true;
		$title = isset( $attributes['title'] ) && ! empty( $attributes['title'] ) 
			? $attributes['title'] 
			: esc_html__( 'Features', 'digicommerce' );
		$show_borders = isset( $attributes['showBorders'] ) ? $attributes['showBorders'] : true;
		$alternate_rows = isset( $attributes['alternateRows'] ) ? $attributes['alternateRows'] : true;

		// Build CSS classes
		$css_classes = array();
		if ( $show_borders ) {
			$css_classes[] = 'digicommerce-features-bordered';
		}
		if ( $alternate_rows ) {
			$css_classes[] = 'digicommerce-features-striped';
		}

		// Get block wrapper attributes
		$wrapper_attributes = get_block_wrapper_attributes(
			array( 'class' => implode( ' ', $css_classes ) )
		);

		// Generate features HTML
		$features_html = self::generate_features_html( $features, $show_title, $title );

		if ( empty( $features_html ) ) {
			return '';
		}

		return sprintf(
			'<div %s>%s</div>',
			$wrapper_attributes,
			$features_html
		);
	}

	/**
	 * Generate features HTML
	 *
	 * @param array  $features Product features.
	 * @param bool   $show_title Show title.
	 * @param string $title Features title.
	 * @return string Features HTML.
	 */
	private static function generate_features_html( $features, $show_title, $title ) {
		$html = '';

		if ( $show_title ) {
			$html .= '<h3 class="digicommerce-product-features__title">' . esc_html( $title ) . '</h3>';
		}

		$html .= '<table class="digicommerce-product-features__table">';
		$html .= '<tbody>';

		foreach ( $features as $index => $feature ) {
			if ( empty( $feature['name'] ) ) {
				continue;
			}

			$row_class = 'digicommerce-product-features__row';
			if ( 0 === $index % 2 ) {
				$row_class .= ' digicommerce-product-features__row-even';
			} else {
				$row_class .= ' digicommerce-product-features__row-odd';
			}

			$html .= '<tr class="' . esc_attr( $row_class ) . '">';
			$html .= '<td class="digicommerce-product-features__name">' . esc_html( $feature['name'] ) . '</td>';
			$html .= '<td class="digicommerce-product-features__value">' . esc_html( $feature['text'] ?? '' ) . '</td>';
			$html .= '</tr>';
		}

		$html .= '</tbody>';
		$html .= '</table>';

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
DigiCommerce_Product_Features_Block::init();