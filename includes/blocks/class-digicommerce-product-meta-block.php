<?php
/**
 * DigiCommerce Product Meta Block
 *
 * @package DigiCommerce
 */

defined( 'ABSPATH' ) || exit;

/**
 * Product Meta Block Class
 */
class DigiCommerce_Product_Meta_Block {

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
			DIGICOMMERCE_PLUGIN_DIR . 'assets/blocks/product-meta',
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

		// Prepare attributes
		$show_categories = isset( $attributes['showCategories'] ) ? $attributes['showCategories'] : true;
		$show_tags = isset( $attributes['showTags'] ) ? $attributes['showTags'] : true;
		$layout = isset( $attributes['layout'] ) ? $attributes['layout'] : 'stacked';
		$separator = isset( $attributes['separator'] ) ? $attributes['separator'] : ', ';

		// Get categories and tags
		$categories = $show_categories ? get_the_terms( $product_id, 'digi_product_cat' ) : false;
		$tags = $show_tags ? get_the_terms( $product_id, 'digi_product_tag' ) : false;

		// Check if we have any meta to display
		$has_categories = $categories && ! is_wp_error( $categories );
		$has_tags = $tags && ! is_wp_error( $tags );

		if ( ! $has_categories && ! $has_tags ) {
			return '';
		}

		// Build CSS classes
		$css_classes = array();
		$css_classes[] = 'digicommerce-meta-layout-' . $layout;

		// Get wrapper attributes with all the styling supports
		$wrapper_attributes = get_block_wrapper_attributes(
			array( 'class' => implode( ' ', $css_classes ) )
		);

		// Generate meta HTML
		$meta_html = self::generate_meta_html( $categories, $tags, $separator );

		if ( empty( $meta_html ) ) {
			return '';
		}

		return sprintf(
			'<div %s>%s</div>',
			$wrapper_attributes,
			$meta_html
		);
	}

	/**
	 * Generate meta HTML
	 *
	 * @param array|false $categories Product categories.
	 * @param array|false $tags Product tags.
	 * @param string      $separator Term separator.
	 * @return string Meta HTML.
	 */
	private static function generate_meta_html( $categories, $tags, $separator ) {
		$html = '';

		$has_categories = $categories && ! is_wp_error( $categories );
		$has_tags = $tags && ! is_wp_error( $tags );

		if ( $has_categories ) {
			$html .= '<div class="digicommerce-meta-item digicommerce-meta-categories">';
			$html .= '<span class="digicommerce-meta-label">' . esc_html__( 'Category:', 'digicommerce' ) . '</span>';
			$html .= '<span class="digicommerce-meta-value">';

			$category_links = array();
			foreach ( $categories as $category ) {
				$category_links[] = sprintf(
					'<a href="%s" class="digicommerce-meta-link">%s</a>',
					esc_url( get_term_link( $category ) ),
					esc_html( $category->name )
				);
			}
			$html .= implode( $separator, $category_links );

			$html .= '</span>';
			$html .= '</div>';
		}

		if ( $has_tags ) {
			$html .= '<div class="digicommerce-meta-item digicommerce-meta-tags">';
			$html .= '<span class="digicommerce-meta-label">' . esc_html__( 'Tags:', 'digicommerce' ) . '</span>';
			$html .= '<span class="digicommerce-meta-value">';

			$tag_links = array();
			foreach ( $tags as $tag ) {
				$tag_links[] = sprintf(
					'<a href="%s" class="digicommerce-meta-link">%s</a>',
					esc_url( get_term_link( $tag ) ),
					esc_html( $tag->name )
				);
			}
			$html .= implode( $separator, $tag_links );

			$html .= '</span>';
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
DigiCommerce_Product_Meta_Block::init();