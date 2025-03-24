<?php
/**
 * DigiCommerce blocks
 */
class DigiCommerce_Blocks {
	/**
	 * Instance of this class.
	 *
	 * @var null
	 */
	private static $instance = null;

	/**
	 * Constructor
	 */
	private function __construct() {
		add_filter( 'block_categories_all', array( $this, 'register_block_category' ), 10, 2 );
		add_action( 'enqueue_block_assets', array( $this, 'enqueue_block_assets' ) );
		add_action( 'enqueue_block_editor_assets', array( $this, 'enqueue_editor_assets' ) );
		add_action( 'init', array( $this, 'register_blocks' ) );
	}

	/**
	 * Get the instance of this class.
	 */
	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Register block category
	 *
	 * @param array   $categories List of block categories.
	 * @param WP_Post $post Current post object.
	 */
	public function register_block_category( $categories, $post ) {
		return array_merge(
			array(
				array(
					'slug'  => 'digicommerce',
					'title' => esc_html__( 'DigiCommerce', 'digicommerce' ),
				),
			),
			$categories
		);
	}

	/**
	 * Enqueue block assets
	 */
	public function enqueue_block_assets() {
		$blocks = array(
			'button',
			'archives',
		);

		foreach ( $blocks as $block ) {
			if ( has_block( 'digicommerce/' . $block ) ) {
				wp_enqueue_style(
					'digicommerce-' . $block,
					DIGICOMMERCE_PLUGIN_URL . 'blocks/' . $block . '/style.css',
					array(),
					DIGICOMMERCE_VERSION
				);
			}
		}
	}

	/**
	 * Enqueue editor assets
	 */
	public function enqueue_editor_assets() {
		// Style for Archives editor.
		wp_enqueue_style(
			'digicommerce-archives-editor',
			DIGICOMMERCE_PLUGIN_URL . 'blocks/archives/editor.css',
			array( 'wp-edit-blocks' ),
			DIGICOMMERCE_VERSION
		);

		// Enqueue editor scripts.
		wp_enqueue_script(
			'digicommerce-blocks-editor',
			DIGICOMMERCE_PLUGIN_URL . 'assets/js/blocks/index.js',
			array(
				'wp-blocks',
				'wp-i18n',
				'wp-element',
				'wp-editor',
				'wp-components',
				'wp-data',
				'wp-block-editor',
				'wp-hooks',
				'wp-server-side-render',
				'wp-core-data',
			),
			DIGICOMMERCE_VERSION,
			true
		);

		// Register translations.
		wp_set_script_translations(
			'digicommerce-blocks-editor',
			'digicommerce'
		);

		// Pass data to editor script.
		wp_localize_script(
			'digicommerce-blocks-editor',
			'digicommerceBlocksData',
			array(
				'products'         => $this->get_products_for_selector(),
				'currencies'       => DigiCommerce()->get_currencies(),
				'selectedCurrency' => DigiCommerce()->get_option( 'currency', 'USD' ),
				'currencyPosition' => DigiCommerce()->get_option( 'currency_position', 'left' ),
				'checkoutUrl'      => get_permalink( DigiCommerce()->get_option( 'checkout_page_id', '' ) ),
			)
		);
	}

	/**
	 * Get products for selector
	 */
	private function get_products_for_selector() {
		$products = get_posts(
			array(
				'post_type'      => 'digi_product',
				'posts_per_page' => -1,
				'post_status'    => 'publish',
			)
		);

		return array_map(
			function ( $product ) {
				return array(
					'value'      => $product->ID,
					'label'      => $product->post_title,
					'price'      => get_post_meta( $product->ID, 'digi_price', true ),
					'sale_price' => get_post_meta( $product->ID, 'digi_sale_price', true ),
					'variations' => get_post_meta( $product->ID, 'digi_price_variations', true ),
				);
			},
			$products
		);
	}

	/**
	 * Register blocks
	 */
	public function register_blocks() {
		// Register Archives block with PHP rendering.
		register_block_type(
			'digicommerce/archives',
			array(
				'apiVersion'       => 2,
				'title'            => __( 'Archives', 'digicommerce' ),
				'category'         => 'digicommerce',
				'editor_script'    => 'digicommerce-blocks-editor',
				'editor_style'     => 'wp-edit-blocks',
				'render_in_editor' => true,
				'description'      => __( 'Display a grid of products with customizable settings', 'digicommerce' ),
				'attributes'       => array(
					'postsPerPage'       => array(
						'type'    => 'number',
						'default' => 9,
					),
					'columns'            => array(
						'type'    => 'number',
						'default' => 3,
					),
					'showTitle'          => array(
						'type'    => 'boolean',
						'default' => true,
					),
					'showPrice'          => array(
						'type'    => 'boolean',
						'default' => true,
					),
					'showButton'         => array(
						'type'    => 'boolean',
						'default' => true,
					),
					'showPagination'     => array(
						'type'    => 'boolean',
						'default' => true,
					),
					'selectedCategories' => array(
						'type'    => 'array',
						'default' => array(),
					),
					'selectedTags'       => array(
						'type'    => 'array',
						'default' => array(),
					),
				),
				'render_callback'       => array(
					$this,
					'render_archives_block',
				),
			)
		);
	}

	/**
	 * Render Archives block
	 *
	 * @param array  $attributes Block attributes.
	 * @param string $content Block content.
	 */
	public function render_archives_block( $attributes, $content ) {
		$product = DigiCommerce_Product::instance();
		$args    = array(
			'post_type'      => 'digi_product',
			'posts_per_page' => $attributes['postsPerPage'],
			'paged'          => get_query_var( 'paged' ) ? get_query_var( 'paged' ) : 1,
		);

		// Add category filter
		if ( ! empty( $attributes['selectedCategories'] ) ) {
			$args['tax_query'][] = array(
				'taxonomy' => 'digi_product_cat',
				'field'    => 'id',
				'terms'    => $attributes['selectedCategories'],
			);
		}

		// Add tag filter
		if ( ! empty( $attributes['selectedTags'] ) ) {
			$args['tax_query'][] = array(
				'taxonomy' => 'digi_product_tag',
				'field'    => 'id',
				'terms'    => $attributes['selectedTags'],
			);
		}

		$query           = new WP_Query( $args );
		$wrapper_classes = 'digicommerce-archive digicommerce';
		$wrapper_style   = '';

		// Add background color if set
		if ( ! empty( $attributes['backgroundColor'] ) ) {
			$wrapper_style .= 'background-color: ' . $attributes['backgroundColor'] . ';';
		}

		ob_start();
		?>
		<div class="<?php echo esc_attr( $wrapper_classes ); ?>" style="<?php echo esc_attr( $wrapper_style ); ?>">
			<div class="dc-inner col-<?php echo esc_attr( $attributes['columns'] ); ?>">
				<?php
				if ( $query->have_posts() ) :
					while ( $query->have_posts() ) :
						$query->the_post();
						$product_id = get_the_ID();
						$price_mode = get_post_meta( $product_id, 'digi_price_mode', true );
						$price      = get_post_meta( $product_id, 'digi_price', true );
						$sale_price = get_post_meta( $product_id, 'digi_sale_price', true );
						$variations = get_post_meta( $product_id, 'digi_price_variations', true );
						?>
						<article class="product-card">
							<a href="<?php the_permalink(); ?>" class="product-link">
								<?php
								if ( has_post_thumbnail() ) :
									?>
									<div class="product-img">
										<?php the_post_thumbnail( 'large' ); ?>
									</div>
									<?php
								endif;
								?>

								<div class="product-content">
									<?php
									if ( $attributes['showTitle'] ) :
										?>
										<h2>
											<?php the_title(); ?>
										</h2>
										<?php
									endif;

									if ( $attributes['showPrice'] ) :
										if ( 'variations' === $price_mode ) :
											if ( ! empty( $variations ) ) :
												$prices      = array();
												$sale_prices = array();

												foreach ( $variations as $variation ) {
													$variation_price      = floatval( $variation['price'] );
													$variation_sale_price = ! empty( $variation['salePrice'] ) ? floatval( $variation['salePrice'] ) : 0;

													if ( $variation_price > 0 ) {
														$prices[] = $variation_price;
														if ( $variation_sale_price > 0 && $variation_sale_price < $variation_price ) {
															$sale_prices[] = $variation_sale_price;
														}
													}
												}

												if ( ! empty( $prices ) ) :
													$lowest_regular = min( $prices );
													$lowest_sale    = ! empty( $sale_prices ) ? min( $sale_prices ) : 0;
													?>
													<div class="product-prices">
														<span class="from"><?php esc_html_e( 'From:', 'digicommerce' ); ?></span>
														<?php
														if ( $lowest_sale && $lowest_sale < $lowest_regular ) {
															echo $product->format_price( $lowest_sale, 'normal-price' );// phpcs:ignore
															echo $product->format_price( $lowest_regular, 'regular-price' );// phpcs:ignore
														} else {
															echo $product->format_price( $lowest_regular, 'normal-price' );// phpcs:ignore
														}
														?>
													</div>
													<?php
												endif;
											endif;
										else :
											$price      = get_post_meta( $product_id, 'digi_price', true );
											$sale_price = get_post_meta( $product_id, 'digi_sale_price', true );

											if ( $sale_price && $sale_price < $price ) :
												?>
												<div class="product-prices">
													<?php
													echo $product->format_price( $sale_price, 'normal-price' ); // phpcs:ignore
													echo $product->format_price( $price, 'regular-price' ); // phpcs:ignore
													?>
												</div>
												<?php
											else :
												echo $product->format_price( $price, 'normal-price' ); // phpcs:ignore
											endif;
										endif;
									endif;
									?>
								</div>
							</a>

							<?php
							if ( $attributes['showButton'] ) :
								?>
								<div class="product-button">
									<a href="<?php the_permalink(); ?>">
										<?php esc_html_e( 'View Product', 'digicommerce' ); ?>
									</a>
								</div>
								<?php
							endif;
							?>
						</article>
						<?php
					endwhile;
				else :
					?>
					<p class="no-product">
						<?php esc_html_e( 'No products found.', 'digicommerce' ); ?>
					</p>
					<?php
				endif;
				?>
			</div>

			<?php if ( $attributes['showPagination'] && $query->max_num_pages > 1 ) : ?>
				<nav class="pagination">
					<?php
					echo paginate_links( // phpcs:ignore
						array(
							'base'      => str_replace( 999999999, '%#%', esc_url( get_pagenum_link( 999999999 ) ) ),
							'format'    => '?paged=%#%',
							'current'   => max( 1, get_query_var( 'paged' ) ),
							'total'     => $query->max_num_pages,
							'prev_text' => '&laquo;',
							'next_text' => '&raquo;',
							'type'      => 'list',
						)
					);
					?>
				</nav>
			<?php endif; ?>
		</div>
		<?php
		wp_reset_postdata();
		return ob_get_clean();
	}
}

// Initialize blocks.
DigiCommerce_Blocks::instance();