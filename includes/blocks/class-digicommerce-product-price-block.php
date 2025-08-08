<?php
/**
 * DigiCommerce Product Price Block
 *
 * @package DigiCommerce
 */

defined( 'ABSPATH' ) || exit;

/**
 * Product Price Block Class
 */
class DigiCommerce_Product_Price_Block {

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
			DIGICOMMERCE_PLUGIN_DIR . 'assets/blocks/product-price',
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

		// Get product instance
		if ( ! class_exists( 'DigiCommerce_Product' ) ) {
			return '';
		}

		$product_instance = DigiCommerce_Product::instance();
		
		// Get pricing data
		$price_mode = get_post_meta( $product_id, 'digi_price_mode', true );
		$single_price = get_post_meta( $product_id, 'digi_price', true );
		$sale_price = get_post_meta( $product_id, 'digi_sale_price', true );
		$price_variations = get_post_meta( $product_id, 'digi_price_variations', true );

		if ( empty( $single_price ) && empty( $price_variations ) ) {
			return '';
		}

		// Prepare attributes
		$show_variations = isset( $attributes['showVariations'] ) ? $attributes['showVariations'] : true;

		// Get wrapper attributes with all the styling supports
		$wrapper_attributes = get_block_wrapper_attributes();

		// Generate price HTML
		$price_html = self::generate_price_html( 
			$price_mode, 
			$single_price, 
			$sale_price, 
			$price_variations, 
			$product_instance,
			$show_variations
		);

		if ( empty( $price_html ) ) {
			return '';
		}

		return sprintf(
			'<div %s>%s</div>',
			$wrapper_attributes,
			$price_html
		);
	}

	/**
	 * Generate price HTML
	 *
	 * @param string $price_mode Price mode (single/variations).
	 * @param string $single_price Single price.
	 * @param string $sale_price Sale price.
	 * @param array  $price_variations Price variations.
	 * @param object $product_instance Product instance.
	 * @param bool   $show_variations Show variations.
	 * @return string Price HTML.
	 */
	private static function generate_price_html( $price_mode, $single_price, $sale_price, $price_variations, $product_instance, $show_variations ) {
		$html = '';

		if ( 'single' === $price_mode && $single_price ) {
			// Single price mode - show_variations doesn't affect this
			if ( $sale_price && $sale_price < $single_price ) {
				// Has sale price
				$html .= '<div class="digicommerce-product-price__container">';
				$html .= '<span class="digicommerce-product-price__sale">';
				$html .= $product_instance->format_price( $sale_price );
				$html .= '</span>';
				$html .= '<span class="digicommerce-product-price__regular">';
				$html .= $product_instance->format_price( $single_price );
				$html .= '</span>';
				$html .= '</div>';
			} else {
				// Regular price only
				$html .= '<div class="digicommerce-product-price__container">';
				$html .= '<span class="digicommerce-product-price__price">';
				$html .= $product_instance->format_price( $single_price );
				$html .= '</span>';
				$html .= '</div>';
			}
		} elseif ( 'variations' === $price_mode && ! empty( $price_variations ) ) {
			// Variations mode - calculate lowest prices
			$lowest_price = null;
			$lowest_sale_price = null;

			foreach ( $price_variations as $variation ) {
				$current_price = $variation['price'];
				$current_sale_price = isset( $variation['salePrice'] ) ? $variation['salePrice'] : null;

				if ( null === $lowest_price || $current_price < $lowest_price ) {
					$lowest_price = $current_price;
				}

				if ( $current_sale_price && $current_sale_price < $current_price ) {
					if ( null === $lowest_sale_price || $current_sale_price < $lowest_sale_price ) {
						$lowest_sale_price = $current_sale_price;
					}
				}
			}

			// Show price with or without "From:" based on show_variations setting
			if ( $show_variations ) {
				$html .= '<div class="digicommerce-product-price__container digicommerce-product-price__container--variations">';
				$html .= '<span class="digicommerce-product-price__from">' . esc_html__( 'From:', 'digicommerce' ) . '</span>';
			} else {
				$html .= '<div class="digicommerce-product-price__container">';
			}
			
			if ( null !== $lowest_sale_price ) {
				$html .= '<span class="digicommerce-product-price__sale">';
				$html .= $product_instance->format_price( $lowest_sale_price );
				$html .= '</span>';
				$html .= '<span class="digicommerce-product-price__regular">';
				$html .= $product_instance->format_price( $lowest_price );
				$html .= '</span>';
			} else {
				$html .= '<span class="digicommerce-product-price__price">';
				$html .= $product_instance->format_price( $lowest_price );
				$html .= '</span>';
			}
			$html .= '</div>';
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
DigiCommerce_Product_Price_Block::init();