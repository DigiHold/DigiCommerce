<?php
/**
 * DigiCommerce Products Grid Block
 *
 * @package DigiCommerce
 */

defined( 'ABSPATH' ) || exit;

/**
 * Products Grid Block Class
 */
class DigiCommerce_Products_Grid_Block {

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
			DIGICOMMERCE_PLUGIN_DIR . 'assets/blocks/products-grid',
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
		// Get attributes with defaults
		$columns = isset( $attributes['columns'] ) ? absint( $attributes['columns'] ) : 3;
		$rows = isset( $attributes['rows'] ) ? absint( $attributes['rows'] ) : 4;
		$show_pagination = isset( $attributes['showPagination'] ) ? $attributes['showPagination'] : true;
		$show_image = isset( $attributes['showImage'] ) ? $attributes['showImage'] : true;
		$show_title = isset( $attributes['showTitle'] ) ? $attributes['showTitle'] : true;
		$show_price = isset( $attributes['showPrice'] ) ? $attributes['showPrice'] : true;
		$show_button = isset( $attributes['showButton'] ) ? $attributes['showButton'] : true;

		// Calculate posts per page
		$posts_per_page = $columns * $rows;

		// Get current page
		$paged = get_query_var( 'paged' ) ? absint( get_query_var( 'paged' ) ) : 1;

		// Build query args based on context
		$query_args = self::get_query_args( $posts_per_page, $paged );

		// Run the query
		$products_query = new WP_Query( $query_args );

		if ( ! $products_query->have_posts() ) {
			return '<div class="digicommerce-products-grid__empty">' . 
			       esc_html__( 'No products found.', 'digicommerce' ) . 
			       '</div>';
		}

		// Get product instance for price formatting
		$product_instance = class_exists( 'DigiCommerce_Product' ) ? DigiCommerce_Product::instance() : null;

		// Start output buffering
		ob_start();

		// Get wrapper attributes
		$wrapper_attributes = get_block_wrapper_attributes();

		?>
		<div <?php echo $wrapper_attributes; ?>>
			<div class="digicommerce-products-grid__products digicommerce-products-grid__products--cols-<?php echo esc_attr( $columns ); ?>">
				<?php
				while ( $products_query->have_posts() ) :
					$products_query->the_post();
					$product_id = get_the_ID();
					
					// Get product metadata
					$price_mode = get_post_meta( $product_id, 'digi_price_mode', true ) ?: 'single';
					$single_price = get_post_meta( $product_id, 'digi_price', true );
					$sale_price = get_post_meta( $product_id, 'digi_sale_price', true );
					$price_variations = get_post_meta( $product_id, 'digi_price_variations', true );
					?>
					<article class="digicommerce-products-grid__product">
						<div class="digicommerce-products-grid__product-inner">
							<?php if ( $show_image && has_post_thumbnail() ) : ?>
								<a href="<?php the_permalink(); ?>" class="digicommerce-products-grid__product-image">
									<?php the_post_thumbnail( 'medium_large' ); ?>
								</a>
							<?php endif; ?>

							<div class="digicommerce-products-grid__product-content">
								<?php if ( $show_title ) : ?>
									<h3 class="digicommerce-products-grid__product-title">
										<a href="<?php the_permalink(); ?>">
											<?php the_title(); ?>
										</a>
									</h3>
								<?php endif; ?>

								<?php if ( $show_price && $product_instance ) : ?>
									<div class="digicommerce-products-grid__product-price">
										<?php echo self::render_price( $price_mode, $single_price, $sale_price, $price_variations, $product_instance ); ?>
									</div>
								<?php endif; ?>

								<?php if ( $show_button ) : ?>
									<div class="digicommerce-products-grid__product-button">
										<a href="<?php the_permalink(); ?>" class="digicommerce-button">
											<?php esc_html_e( 'View Product', 'digicommerce' ); ?>
										</a>
									</div>
								<?php endif; ?>
							</div>
						</div>
					</article>
					<?php
				endwhile;
				wp_reset_postdata();
				?>
			</div>

			<?php if ( $show_pagination && $products_query->max_num_pages > 1 ) : ?>
				<nav class="digicommerce-products-grid__pagination">
					<?php
					echo paginate_links( array(
						'total'     => $products_query->max_num_pages,
						'current'   => $paged,
						'prev_text' => '← ' . __( 'Previous', 'digicommerce' ),
						'next_text' => __( 'Next', 'digicommerce' ) . ' →',
						'type'      => 'list',
					) );
					?>
				</nav>
			<?php endif; ?>
		</div>
		<?php

		return ob_get_clean();
	}

	/**
	 * Get query args based on current context
	 *
	 * @param int $posts_per_page Number of posts per page.
	 * @param int $paged Current page number.
	 * @return array Query arguments.
	 */
	private static function get_query_args( $posts_per_page, $paged ) {
		$args = array(
			'post_type'      => 'digi_product',
			'post_status'    => 'publish',
			'posts_per_page' => $posts_per_page,
			'paged'          => $paged,
			'orderby'        => 'date',
			'order'          => 'DESC',
		);

		// Handle sorting from products-sorting block
		if ( isset( $_GET['orderby'] ) ) {
			$orderby = sanitize_text_field( $_GET['orderby'] );
			
			switch ( $orderby ) {
				case 'date':
					$args['orderby'] = 'date';
					$args['order'] = 'DESC';
					break;
				case 'date-asc':
					$args['orderby'] = 'date';
					$args['order'] = 'ASC';
					break;
				case 'title':
					$args['orderby'] = 'title';
					$args['order'] = 'ASC';
					break;
				case 'title-desc':
					$args['orderby'] = 'title';
					$args['order'] = 'DESC';
					break;
				case 'price':
					$args['meta_key'] = 'digi_price';
					$args['orderby'] = 'meta_value_num';
					$args['order'] = 'ASC';
					break;
				case 'price-desc':
					$args['meta_key'] = 'digi_price';
					$args['orderby'] = 'meta_value_num';
					$args['order'] = 'DESC';
					break;
			}
		}

		// Handle category filter from products-filters block
		$selected_categories = array();
		if ( isset( $_GET['product_cat'] ) && $_GET['product_cat'] !== '' ) {
			$selected_categories = array_map( 'intval', (array) $_GET['product_cat'] );
			$selected_categories = array_filter( $selected_categories ); // Remove any zeros from empty values
		}

		$selected_tags = array();
		if ( isset( $_GET['product_tag'] ) && $_GET['product_tag'] !== '' ) {
			$selected_tags = array_map( 'intval', (array) $_GET['product_tag'] );
			$selected_tags = array_filter( $selected_tags ); // Remove any zeros from empty values
		}

		$tax_query = array();

		// Add category filter
		if ( ! empty( $selected_categories ) ) {
			$tax_query[] = array(
				'taxonomy' => 'digi_product_cat',
				'field'    => 'term_id',
				'terms'    => $selected_categories,
				'operator' => 'IN',
			);
		}

		// Add tag filter
		if ( ! empty( $selected_tags ) ) {
			$tax_query[] = array(
				'taxonomy' => 'digi_product_tag',
				'field'    => 'term_id',
				'terms'    => $selected_tags,
				'operator' => 'IN',
			);
		}

		// Check if we're on a category page (existing functionality)
		if ( is_tax( 'digi_product_cat' ) ) {
			$current_term = get_queried_object();
			if ( $current_term && isset( $current_term->term_id ) ) {
				$tax_query[] = array(
					'taxonomy' => 'digi_product_cat',
					'field'    => 'term_id',
					'terms'    => $current_term->term_id,
				);
			}
		}

		// Check if we're on a tag page (existing functionality)
		elseif ( is_tax( 'digi_product_tag' ) ) {
			$current_term = get_queried_object();
			if ( $current_term && isset( $current_term->term_id ) ) {
				$tax_query[] = array(
					'taxonomy' => 'digi_product_tag',
					'field'    => 'term_id',
					'terms'    => $current_term->term_id,
				);
			}
		}

		// Check if we're on any product taxonomy (existing functionality)
		elseif ( is_tax() ) {
			$current_term = get_queried_object();
			if ( $current_term && isset( $current_term->taxonomy ) && isset( $current_term->term_id ) ) {
				$product_taxonomies = get_object_taxonomies( 'digi_product' );
				if ( in_array( $current_term->taxonomy, $product_taxonomies, true ) ) {
					$tax_query[] = array(
						'taxonomy' => $current_term->taxonomy,
						'field'    => 'term_id',
						'terms'    => $current_term->term_id,
					);
				}
			}
		}

		// Add tax query to args if not empty
		if ( ! empty( $tax_query ) ) {
			// If more than one tax query, set relation to AND
			if ( count( $tax_query ) > 1 ) {
				$tax_query['relation'] = 'AND';
			}
			$args['tax_query'] = $tax_query;
		}

		// Allow filtering of query args
		return apply_filters( 'digicommerce_products_grid_query_args', $args );
	}

	/**
	 * Render price HTML
	 *
	 * @param string $price_mode Price mode.
	 * @param string $single_price Single price.
	 * @param string $sale_price Sale price.
	 * @param array  $price_variations Price variations.
	 * @param object $product_instance Product instance.
	 * @return string Price HTML.
	 */
	private static function render_price( $price_mode, $single_price, $sale_price, $price_variations, $product_instance ) {
		$html = '';

		if ( 'single' === $price_mode && $single_price ) {
			if ( $sale_price && $sale_price < $single_price ) {
				$html .= '<span class="digicommerce-price-sale">' . 
				         wp_kses_post( $product_instance->format_price( $sale_price ) ) . 
				         '</span>';
				$html .= '<span class="digicommerce-price-regular">' . 
				         wp_kses_post( $product_instance->format_price( $single_price ) ) . 
				         '</span>';
			} else {
				$html .= '<span class="digicommerce-price">' . 
				         wp_kses_post( $product_instance->format_price( $single_price ) ) . 
				         '</span>';
			}
		} elseif ( 'variations' === $price_mode && ! empty( $price_variations ) ) {
			// Find lowest price
			$lowest_price = null;
			$lowest_sale_price = null;

			foreach ( $price_variations as $variation ) {
				$current_price = $variation['price'];
				$current_sale = isset( $variation['salePrice'] ) ? $variation['salePrice'] : null;

				if ( null === $lowest_price || $current_price < $lowest_price ) {
					$lowest_price = $current_price;
				}

				if ( $current_sale && $current_sale < $current_price ) {
					if ( null === $lowest_sale_price || $current_sale < $lowest_sale_price ) {
						$lowest_sale_price = $current_sale;
					}
				}
			}

			$html .= '<span class="digicommerce-price-from">' . 
			         esc_html__( 'From:', 'digicommerce' ) . 
			         '</span> ';

			if ( null !== $lowest_sale_price ) {
				$html .= '<span class="digicommerce-price-sale">' . 
				         wp_kses_post( $product_instance->format_price( $lowest_sale_price ) ) . 
				         '</span>';
			} else {
				$html .= '<span class="digicommerce-price">' . 
				         wp_kses_post( $product_instance->format_price( $lowest_price ) ) . 
				         '</span>';
			}
		}

		return $html;
	}
}

// Initialize the block
DigiCommerce_Products_Grid_Block::init();