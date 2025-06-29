<?php
/**
 * Template for displaying product archives, categories, and tags
 *
 * This template can be overridden by copying it to yourtheme/digicommerce/product-archive.php
 *
 * @version 1.0.0
 */

defined( 'ABSPATH' ) || exit;

get_header();

$product = DigiCommerce_Product::instance();

$allowed_html = array(
	'span' => array(
		'class' => array(),
	),
);
?>

<div class="digicommerce-archive digicommerce py-10 md:py-16 w-full">
	<?php if ( have_posts() ) : ?>
		<!-- Products Grid -->
		<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
			<?php
			while ( have_posts() ) :
				the_post();
				// Get product metadata
				$price_mode          = get_post_meta( get_the_ID(), 'digi_price_mode', true ) ? get_post_meta( get_the_ID(), 'digi_price_mode', true ) : 'single';
				$single_price        = get_post_meta( get_the_ID(), 'digi_price', true );
				$sale_price          = get_post_meta( get_the_ID(), 'digi_sale_price', true );
				$price_variations    = get_post_meta( get_the_ID(), 'digi_price_variations', true );
				$product_description = get_post_meta( get_the_ID(), 'digi_product_description', true );
				?>
				<article class="product-card group flex flex-col bg-white rounded-lg shadow-sm hover:shadow-lg transition-shadow duration-300 overflow-hidden border border-gray-200">
					<a href="<?php the_permalink(); ?>" class="flex-1">
						<!-- Product Image -->
						<?php if ( has_post_thumbnail() ) : ?>
							<div class="aspect-w-16 aspect-h-9 w-full overflow-hidden bg-gray-100">
								<?php
								the_post_thumbnail(
									'medium_large',
									array(
										'class' => 'w-full h-full object-cover object-center group-hover:scale-105 transition-transform duration-300',
									)
								);
								?>
							</div>
						<?php endif; ?>

						<!-- Product Content -->
						<div class="p-6">
							<h2 class="text-xl font-bold text-dark-blue mb-2 group-hover:text-gold transition-colors">
								<?php the_title(); ?>
							</h2>

							<!-- Price Display -->
							<div class="mb-4">
								<?php if ( 'single' === $price_mode && $single_price ) : ?>
									<?php if ( $sale_price && $sale_price < $single_price ) : ?>
										<div class="flex items-center gap-2">
											<?php
											// Sale price
											echo wp_kses(
												$product->format_price(
													$sale_price,
													'text-xl font-bold text-green-600'
												),
												$allowed_html
											);
											// Regular price struck through
											echo wp_kses(
												$product->format_price(
													$single_price,
													'text-sm text-gray-500 line-through'
												),
												$allowed_html
											);
											?>
										</div>
									<?php else : ?>
										<?php
										echo wp_kses(
											$product->format_price(
												$single_price,
												'text-xl font-bold text-dark-blue'
											),
											$allowed_html
										);
										?>
									<?php endif; ?>
								<?php elseif ( 'variations' === $price_mode && ! empty( $price_variations ) ) : ?>
									<?php
									// Find the lowest price among variations
									$lowest_price      = null;
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
									?>
									<div class="flex items-center gap-2">
										<span class="text-sm text-dark-blue"><?php esc_html_e( 'From:', 'digicommerce' ); ?></span>
										<?php
										if ( null !== $lowest_sale_price ) :
											echo wp_kses(
												$product->format_price(
													$lowest_sale_price,
													'text-xl font-bold text-green-600'
												),
												$allowed_html
											);
										else :
											echo wp_kses(
												$product->format_price(
													$lowest_price,
													'text-xl font-bold text-dark-blue'
												),
												$allowed_html
											);
										endif;
										?>
									</div>
								<?php endif; ?>
							</div>
						</div>
					</a>

					<!-- Card Footer -->
					<div class="p-6 pt-0 mt-auto">
						<a href="<?php the_permalink(); ?>" class="inline-flex w-full items-center justify-center px-4 py-2 border border-transparent text-base font-medium rounded-md text-white bg-dark-blue hover:bg-gold hover:text-dark-blue transition-colors">
							<?php esc_html_e( 'View Product', 'digicommerce' ); ?>
						</a>
					</div>
				</article>
				<?php
			endwhile;
			?>
		</div>

		<!-- Pagination -->
		<nav class="mt-12">
			<?php
			echo wp_kses_post(
				paginate_links(
					array(
						'prev_text' => '← ' . __( 'Previous', 'digicommerce' ),
						'next_text' => __( 'Next', 'digicommerce' ) . ' →',
						'type'      => 'list',
						'class'     => 'pagination',
					)
				)
			);
			?>
		</nav>

	<?php else : ?>
		<div class="text-center py-12">
			<p class="text-xl text-gray-600">
				<?php esc_html_e( 'No products found.', 'digicommerce' ); ?>
			</p>
		</div>
	<?php endif; ?>
</div>

<?php
get_footer();