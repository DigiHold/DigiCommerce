<?php
/**
 * DigiCommerce Product Button Block
 *
 * @package DigiCommerce
 */

defined( 'ABSPATH' ) || exit;

/**
 * Product Button Block Class
 */
class DigiCommerce_Product_Button_Block {

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
		register_block_type(
			DIGICOMMERCE_PLUGIN_DIR . 'assets/blocks/product-button',
			array(
				'render_callback' => array( __CLASS__, 'render_block' ),
			)
		);
	}

	/**
	 * Convert CSS custom properties from block format to CSS format
	 *
	 * @param string $value The value to convert.
	 * @return string The converted value.
	 */
	private static function convert_custom_properties( $value ) {
		if ( ! is_string( $value ) ) {
			return $value;
		}
		
		// Convert var:preset|spacing|40 to var(--wp--preset--spacing--40)
		// Pattern: var:category|subcategory|value
		$value = preg_replace( 
			'/var:preset\|([^|]+)\|(.+)/', 
			'var(--wp--preset--$1--$2)', 
			$value 
		);
		
		// Also handle custom values like var:custom|value
		$value = preg_replace( 
			'/var:([^|]+)\|(.+)/', 
			'var(--wp--$1--$2)', 
			$value 
		);
		
		return $value;
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
		// Get attributes with defaults
		$product_id   = isset( $attributes['productId'] ) ? absint( $attributes['productId'] ) : 0;
		$button_text  = isset( $attributes['buttonText'] ) ? $attributes['buttonText'] : __( 'View Product', 'digicommerce' );
		$open_in_new  = isset( $attributes['openInNewTab'] ) ? $attributes['openInNewTab'] : false;
		$show_price   = isset( $attributes['showPrice'] ) ? $attributes['showPrice'] : false;
		$action_type  = isset( $attributes['actionType'] ) ? $attributes['actionType'] : 'link';
		$variation_id = isset( $attributes['variationId'] ) ? intval( $attributes['variationId'] ) : -1;

		// Return empty if no product selected
		if ( ! $product_id ) {
			return '';
		}

		// Check if product exists and is published
		$product = get_post( $product_id );
		if ( ! $product || 'digi_product' !== $product->post_type || 'publish' !== $product->post_status ) {
			return '';
		}

		// Build button text
		$display_text = esc_html( $button_text );
		
		// Add price if enabled
		if ( $show_price ) {
			$price      = get_post_meta( $product_id, 'digi_price', true );
			$sale_price = get_post_meta( $product_id, 'digi_sale_price', true );
			$price_mode = get_post_meta( $product_id, 'digi_price_mode', true );
			
			// Get variation price if applicable
			if ( 'variations' === $price_mode && $variation_id >= 0 ) {
				$variations = get_post_meta( $product_id, 'digi_price_variations', true );
				if ( isset( $variations[ $variation_id ] ) ) {
					$variation  = $variations[ $variation_id ];
					$price      = floatval( $variation['price'] );
					$sale_price = isset( $variation['salePrice'] ) ? floatval( $variation['salePrice'] ) : 0;
				}
			}
			
			$currency        = DigiCommerce()->get_option( 'currency', 'USD' );
			$currencies      = DigiCommerce()->get_currencies();
			$currency_symbol = isset( $currencies[ $currency ]['symbol'] ) ? $currencies[ $currency ]['symbol'] : $currency;
			$display_price   = ! empty( $sale_price ) ? $sale_price : $price;
			
			if ( $display_price ) {
				$formatted_price = $currency_symbol . number_format( floatval( $display_price ), 2 );
				$display_text   .= ' - ' . $formatted_price;
			}
		}

		// Build URL
		if ( 'checkout' === $action_type ) {
			// Direct to checkout with parameters
			$checkout_page_id = DigiCommerce()->get_option( 'checkout_page_id' );
			$checkout_url     = get_permalink( $checkout_page_id );
			$url_params       = array( 'id' => $product_id );
			
			// Add variation if product has variations and one is selected
			$price_mode = get_post_meta( $product_id, 'digi_price_mode', true );
			if ( 'variations' === $price_mode && $variation_id >= 0 ) {
				$url_params['variation'] = $variation_id + 1; // 1-based for URL
			}
			
			$url = add_query_arg( $url_params, $checkout_url );
		} else {
			// Link to product page
			$url = get_permalink( $product_id );
		}

		// Get wrapper attributes (for alignment and margin only)
		// We need to ensure padding is not included in the wrapper
		$wrapper_classes = array( 'wp-block-digicommerce-product-button' );
		
		// Add alignment classes if present
		if ( isset( $attributes['align'] ) ) {
			$wrapper_classes[] = 'align' . $attributes['align'];
		}
		
		// Add custom className if present
		if ( isset( $attributes['className'] ) ) {
			$wrapper_classes[] = $attributes['className'];
		}
		
		// Build wrapper attributes with only margin styles
		$wrapper_styles = array();
		if ( isset( $attributes['style']['spacing']['margin'] ) ) {
			$margin = $attributes['style']['spacing']['margin'];
			if ( is_string( $margin ) ) {
				$wrapper_styles[] = 'margin: ' . self::convert_custom_properties( $margin );
			} elseif ( is_array( $margin ) ) {
				if ( isset( $margin['top'] ) ) {
					$wrapper_styles[] = 'margin-top: ' . self::convert_custom_properties( $margin['top'] );
				}
				if ( isset( $margin['right'] ) ) {
					$wrapper_styles[] = 'margin-right: ' . self::convert_custom_properties( $margin['right'] );
				}
				if ( isset( $margin['bottom'] ) ) {
					$wrapper_styles[] = 'margin-bottom: ' . self::convert_custom_properties( $margin['bottom'] );
				}
				if ( isset( $margin['left'] ) ) {
					$wrapper_styles[] = 'margin-left: ' . self::convert_custom_properties( $margin['left'] );
				}
			}
		}
		
		// Add anchor/id if present
		$anchor_attr = '';
		if ( isset( $attributes['anchor'] ) && ! empty( $attributes['anchor'] ) ) {
			$anchor_attr = ' id="' . esc_attr( $attributes['anchor'] ) . '"';
		}
		
		$wrapper_attributes = sprintf(
			'class="%s"%s%s',
			esc_attr( implode( ' ', $wrapper_classes ) ),
			! empty( $wrapper_styles ) ? ' style="' . esc_attr( implode( '; ', $wrapper_styles ) ) . '"' : '',
			$anchor_attr
		);

		// Extract button-specific styles
		$button_classes = array( 'wp-element-button' );
		$button_styles  = array();

		// Handle color supports
		if ( isset( $attributes['textColor'] ) ) {
			$button_classes[] = 'has-text-color';
			$button_classes[] = 'has-' . $attributes['textColor'] . '-color';
		}
		if ( isset( $attributes['backgroundColor'] ) ) {
			$button_classes[] = 'has-background';
			$button_classes[] = 'has-' . $attributes['backgroundColor'] . '-background-color';
		}
		if ( isset( $attributes['borderColor'] ) ) {
			$button_classes[] = 'has-border-color';
			$button_classes[] = 'has-' . $attributes['borderColor'] . '-border-color';
		}
		
		// Handle custom colors from style attribute
		if ( isset( $attributes['style']['color'] ) ) {
			$color_styles = $attributes['style']['color'];
			if ( isset( $color_styles['text'] ) ) {
				$button_styles[] = 'color: ' . $color_styles['text'];
			}
			if ( isset( $color_styles['background'] ) ) {
				$button_styles[] = 'background-color: ' . $color_styles['background'];
			}
		}
		
		// Handle typography
		if ( isset( $attributes['fontSize'] ) ) {
			$button_classes[] = 'has-' . $attributes['fontSize'] . '-font-size';
		}
		if ( isset( $attributes['style']['typography'] ) ) {
			$typography = $attributes['style']['typography'];
			if ( isset( $typography['fontSize'] ) ) {
				$button_styles[] = 'font-size: ' . self::convert_custom_properties( $typography['fontSize'] );
			}
			if ( isset( $typography['lineHeight'] ) ) {
				$button_styles[] = 'line-height: ' . self::convert_custom_properties( $typography['lineHeight'] );
			}
			if ( isset( $typography['fontWeight'] ) ) {
				$button_styles[] = 'font-weight: ' . $typography['fontWeight'];
			}
			if ( isset( $typography['fontStyle'] ) ) {
				$button_styles[] = 'font-style: ' . $typography['fontStyle'];
			}
			if ( isset( $typography['textTransform'] ) ) {
				$button_styles[] = 'text-transform: ' . $typography['textTransform'];
			}
			if ( isset( $typography['textDecoration'] ) ) {
				$button_styles[] = 'text-decoration: ' . $typography['textDecoration'];
			}
			if ( isset( $typography['letterSpacing'] ) ) {
				$button_styles[] = 'letter-spacing: ' . self::convert_custom_properties( $typography['letterSpacing'] );
			}
		}
		
		// Handle padding
		if ( isset( $attributes['style']['spacing']['padding'] ) ) {
			$padding = $attributes['style']['spacing']['padding'];
			if ( is_string( $padding ) ) {
				// Convert var:preset|spacing|40 to var(--wp--preset--spacing--40)
				$padding = self::convert_custom_properties( $padding );
				$button_styles[] = 'padding: ' . $padding;
			} elseif ( is_array( $padding ) ) {
				// Handle individual padding values
				if ( isset( $padding['top'] ) ) {
					$button_styles[] = 'padding-top: ' . self::convert_custom_properties( $padding['top'] );
				}
				if ( isset( $padding['right'] ) ) {
					$button_styles[] = 'padding-right: ' . self::convert_custom_properties( $padding['right'] );
				}
				if ( isset( $padding['bottom'] ) ) {
					$button_styles[] = 'padding-bottom: ' . self::convert_custom_properties( $padding['bottom'] );
				}
				if ( isset( $padding['left'] ) ) {
					$button_styles[] = 'padding-left: ' . self::convert_custom_properties( $padding['left'] );
				}
			}
		}
		
		// Handle border
		if ( isset( $attributes['style']['border'] ) ) {
			$border = $attributes['style']['border'];
			
			// Track if we have any border width set
			$has_border = false;
			
			// Border width
			if ( isset( $border['width'] ) ) {
				if ( is_string( $border['width'] ) && ! empty( $border['width'] ) ) {
					$button_styles[] = 'border-width: ' . $border['width'];
					$has_border = true;
				} elseif ( is_array( $border['width'] ) ) {
					// Handle individual border widths
					foreach ( array( 'top', 'right', 'bottom', 'left' ) as $side ) {
						if ( isset( $border['width'][ $side ] ) && ! empty( $border['width'][ $side ] ) ) {
							$button_styles[] = 'border-' . $side . '-width: ' . $border['width'][ $side ];
							$has_border = true;
						}
					}
				}
			}
			
			// Border style - apply solid as default if we have border width but no style
			if ( isset( $border['style'] ) && ! empty( $border['style'] ) ) {
				$button_styles[] = 'border-style: ' . $border['style'];
			} elseif ( $has_border ) {
				// Default to solid if border width is set but style is not
				$button_styles[] = 'border-style: solid';
			}
			
			// Border color
			if ( isset( $border['color'] ) && ! empty( $border['color'] ) ) {
				$button_styles[] = 'border-color: ' . $border['color'];
			}
			
			// Border radius
			if ( isset( $border['radius'] ) ) {
				if ( is_string( $border['radius'] ) && ! empty( $border['radius'] ) ) {
					$button_styles[] = 'border-radius: ' . self::convert_custom_properties( $border['radius'] );
				} elseif ( is_array( $border['radius'] ) ) {
					// Handle individual corners
					$radius_values = array();
					$corner_keys = array( 'topLeft', 'topRight', 'bottomRight', 'bottomLeft' );
					foreach ( $corner_keys as $corner ) {
						if ( isset( $border['radius'][ $corner ] ) ) {
							$radius_values[] = self::convert_custom_properties( $border['radius'][ $corner ] );
						} else {
							$radius_values[] = '0';
						}
					}
					if ( count( array_filter( $radius_values, function( $v ) { return $v !== '0'; } ) ) > 0 ) {
						$button_styles[] = 'border-radius: ' . implode( ' ', $radius_values );
					}
				}
			}
		}
		
		// Handle gradients
		if ( isset( $attributes['gradient'] ) ) {
			$button_classes[] = 'has-' . $attributes['gradient'] . '-gradient-background';
		}
		if ( isset( $attributes['style']['color']['gradient'] ) ) {
			$button_styles[] = 'background: ' . $attributes['style']['color']['gradient'];
		}

		// Build class and style strings
		$button_class_string = esc_attr( implode( ' ', array_filter( $button_classes ) ) );
		$button_style_string = ! empty( $button_styles ) ? ' style="' . esc_attr( implode( '; ', $button_styles ) ) . '"' : '';
		
		// Build target attribute
		$target = $open_in_new ? ' target="_blank" rel="noopener noreferrer"' : '';

		return sprintf(
			'<div %1$s><a href="%2$s" class="%3$s"%4$s%5$s>%6$s</a></div>',
			$wrapper_attributes,
			esc_url( $url ),
			$button_class_string,
			$target,
			$button_style_string,
			$display_text
		);
	}
}

// Initialize the block
DigiCommerce_Product_Button_Block::init();