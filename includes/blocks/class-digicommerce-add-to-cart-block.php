<?php
/**
 * DigiCommerce Add to Cart Block
 *
 * @package DigiCommerce
 */

defined( 'ABSPATH' ) || exit;

/**
 * Add to Cart Block Class
 */
class DigiCommerce_Add_To_Cart_Block {

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
			DIGICOMMERCE_PLUGIN_DIR . 'assets/blocks/add-to-cart',
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

		// Get product data
		$price_mode = get_post_meta( $product_id, 'digi_price_mode', true );
		$single_price = get_post_meta( $product_id, 'digi_price', true );
		$sale_price = get_post_meta( $product_id, 'digi_sale_price', true );
		$price_variations = get_post_meta( $product_id, 'digi_price_variations', true );

		// Check if product has valid pricing
		if ( empty( $single_price ) && empty( $price_variations ) ) {
			return '';
		}

		// Prepare attributes
		$button_text = isset( $attributes['buttonText'] ) ? $attributes['buttonText'] : '';
		$show_variation_labels = isset( $attributes['showVariationLabels'] ) ? $attributes['showVariationLabels'] : true;

		// Get product instance for price formatting
		$product = null;
		if ( class_exists( 'DigiCommerce_Product' ) ) {
			$product = DigiCommerce_Product::instance();
		}

		// Get default button text if not set
		if ( empty( $button_text ) ) {
			if ( 'single' === $price_mode ) {
				$display_price = ( $sale_price && $sale_price < $single_price ) ? $sale_price : $single_price;
				if ( $product ) {
					$allowed_html = array(
						'span' => array(
							'class' => array(),
						),
					);
					$button_text = wp_kses(
						sprintf(
							/* translators: %s: Price */
							esc_html__( 'Purchase for %s', 'digicommerce' ),
							$product->format_price( $display_price, 'button-price' )
						),
						$allowed_html
					);
				} else {
					$button_text = esc_html__( 'Add to Cart', 'digicommerce' );
				}
			} else {
				$button_text = esc_html__( 'Select an option', 'digicommerce' );
			}
		}

		// Get wrapper attributes with all the styling supports
		$wrapper_attributes = get_block_wrapper_attributes();

		// Generate the HTML
		$html = '<div ' . $wrapper_attributes . '>';
		$html .= self::generate_add_to_cart_form( 
			$product_id, 
			$price_mode, 
			$single_price, 
			$sale_price, 
			$price_variations, 
			$product, 
			$button_text, 
			$show_variation_labels
		);
		$html .= '</div>';

		return $html;
	}

	/**
	 * Generate add to cart form HTML
	 *
	 * @param int    $product_id Product ID.
	 * @param string $price_mode Price mode.
	 * @param string $single_price Single price.
	 * @param string $sale_price Sale price.
	 * @param array  $price_variations Price variations.
	 * @param object $product Product instance.
	 * @param string $button_text Button text.
	 * @param bool   $show_variation_labels Show variation labels.
	 * @return string Form HTML.
	 */
	private static function generate_add_to_cart_form( $product_id, $price_mode, $single_price, $sale_price, $price_variations, $product, $button_text, $show_variation_labels ) {
		$html = '<form class="digicommerce-add-to-cart" method="POST" action="">';
		$html .= wp_nonce_field( 'digicommerce_add_to_cart', 'cart_nonce', true, false );
		$html .= '<input type="hidden" name="action" value="add_to_cart">';
		$html .= '<input type="hidden" name="product_id" value="' . esc_attr( $product_id ) . '">';

		if ( 'variations' === $price_mode ) {
			$html .= '<input type="hidden" name="variation_name" id="variation-name" value="">';
			$html .= '<input type="hidden" name="variation_price" id="variation-price" value="">';
		} else {
			$display_price = ( $sale_price && $sale_price < $single_price ) ? $sale_price : $single_price;
			$html .= '<input type="hidden" name="product_price" value="' . esc_attr( $display_price ) . '">';
		}

		// Add variations if needed
		if ( 'variations' === $price_mode && ! empty( $price_variations ) ) {
			$html .= self::generate_variations_html( $price_variations, $product, $show_variation_labels );
		}

		// Add button
		$html .= '<button type="submit" class="add-to-cart-button wp-element-button" id="add-to-cart-button">';
		$html .= wp_kses_post( $button_text );
		$html .= '</button>';

		$html .= '</form>';

		return $html;
	}

	/**
	 * Generate variations HTML
	 *
	 * @param array  $price_variations Price variations.
	 * @param object $product Product instance.
	 * @param bool   $show_variation_labels Show variation labels.
	 * @return string Variations HTML.
	 */
	private static function generate_variations_html( $price_variations, $product, $show_variation_labels ) {
		$html = '<div class="variation-prices">';
		
		if ( $show_variation_labels ) {
			$html .= '<p class="variation-label">' . esc_html__( 'Select an option', 'digicommerce' ) . '</p>';
		}
		
		$html .= '<div class="variations-container">';

		foreach ( $price_variations as $index => $variation ) {
			$variation_price = isset( $variation['salePrice'] ) && $variation['salePrice'] < $variation['price'] ? $variation['salePrice'] : $variation['price'];
			$has_sale = isset( $variation['salePrice'] ) && $variation['salePrice'] < $variation['price'];
			$formatted_price = $product ? $product->format_price( $variation_price, 'variation-price', true ) : '$' . number_format( $variation_price, 2 );
			$is_default = isset( $variation['isDefault'] ) && $variation['isDefault'];

			$allowed_html = array(
				'span' => array(
					'class' => array(),
				),
			);

			$html .= '<div class="variation-option">';
			$html .= '<input type="radio" ';
			$html .= 'id="variation-' . esc_attr( $index ) . '" ';
			$html .= 'name="price_variation" ';
			$html .= 'value="' . esc_attr( $variation_price ) . '" ';
			$html .= 'data-name="' . esc_attr( $variation['name'] ) . '" ';
			$html .= 'data-formatted-price="' . esc_attr( $formatted_price ) . '"';
			if ( $is_default ) {
				$html .= ' checked="checked"';
			}
			$html .= '>';
			
			$html .= '<label for="variation-' . esc_attr( $index ) . '" class="cursor-pointer default-transition">';
			$html .= '<span class="variation-name">' . esc_html( $variation['name'] ) . '</span>';
			
			if ( $has_sale && $product ) {
				$html .= '<span class="variation-pricing">';
				$html .= wp_kses( $product->format_price( $variation['salePrice'], 'variation-sale-price' ), $allowed_html );
				$html .= wp_kses( $product->format_price( $variation['price'], 'variation-regular-price text-sm line-through' ), $allowed_html );
				$html .= '</span>';
			} else {
				$html .= '<span class="variation-pricing">';
				$html .= wp_kses( $product->format_price( $variation['price'], 'variation-price' ), $allowed_html );
				$html .= '</span>';
			}
			
			$html .= '</label>';
			$html .= '</div>';
		}

		$html .= '</div></div>';

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
DigiCommerce_Add_To_Cart_Block::init();