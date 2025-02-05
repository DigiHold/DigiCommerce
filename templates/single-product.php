<?php
/**
 * Single product template
 *
 * This template displays a single digital product with support for both single and variation pricing.
 *
 * @package DigiCommerce
 */

defined( 'ABSPATH' ) || exit;

get_header();

$product = DigiCommerce_Product::instance();

// Prepare the gallery array for PhotoSwipe
$gallery_images = array();

// Add the featured image to the gallery
if ( has_post_thumbnail() ) {
	$featured_image_id   = get_post_thumbnail_id();
	$featured_image_url  = wp_get_attachment_image_url( $featured_image_id, 'large' );
	$featured_image_full = wp_get_attachment_image_url( $featured_image_id, 'full' );
	$image_metadata      = wp_get_attachment_metadata( $featured_image_id );
	$gallery_images[]    = array(
		'src'   => $featured_image_full,
		'thumb' => $featured_image_url,
		'w'     => $image_metadata['width'] ?? 800, // Fallback width
		'h'     => $image_metadata['height'] ?? 600, // Fallback height
	);
}

// Add gallery images to the array
$gallery = get_post_meta( get_the_ID(), 'digi_gallery', true );
if ( ! empty( $gallery ) && is_array( $gallery ) ) {
	foreach ( $gallery as $image ) {
		$image_full       = wp_get_attachment_image_url( $image['id'], 'full' );
		$image_thumb      = wp_get_attachment_image_url( $image['id'], 'medium' );
		$image_metadata   = wp_get_attachment_metadata( $image['id'] );
		$gallery_images[] = array(
			'src'   => $image_full,
			'thumb' => $image_thumb,
			'w'     => $image_metadata['width'] ?? 800, // Fallback width
			'h'     => $image_metadata['height'] ?? 600, // Fallback height
		);
	}
}

$sale_price          = get_post_meta( get_the_ID(), 'digi_sale_price', true );
$product_description = get_post_meta( get_the_ID(), 'digi_product_description', true );
$features            = get_post_meta( get_the_ID(), 'digi_features', true );

$allowed_html = array(
	'span' => array(
		'class' => array(),
	),
);
?>

<div class="digicommerce-single-product digicommerce flex flex-col gap-8 py-12">
	<div class="lg:grid lg:grid-cols-2 lg:gap-x-8 lg:items-start">
		<!-- Product Image -->
		<div class="product-gallery flex flex-col">
			<!-- Render the featured image -->
			<?php if ( has_post_thumbnail() ) : ?>
				<div class="w-full aspect-w-1 aspect-h-1 bg-gray-100 rounded-lg overflow-hidden">
					<a href="<?php echo esc_url( $gallery_images[0]['src'] ); ?>" 
					data-pswp-index="0" 
					data-pswp-width="<?php echo esc_attr( $gallery_images[0]['w'] ); ?>" 
					data-pswp-height="<?php echo esc_attr( $gallery_images[0]['h'] ); ?>">
						<img src="<?php echo esc_url( $gallery_images[0]['thumb'] ); ?>" class="w-full h-full object-center object-cover" alt="">
					</a>
				</div>
			<?php endif; ?>

			<!-- Render the gallery -->
			<?php if ( count( $gallery_images ) > 1 ) : ?>
				<div class="grid grid-cols-4 gap-4 mt-4">
					<?php foreach ( $gallery_images as $index => $image ) : ?>
						<?php
						if ( $index === 0 ) {
							continue;} // Skip the featured image
						?>
						<div class="aspect-w-1 aspect-h-1 bg-gray-100 rounded-lg overflow-hidden">
							<a href="<?php echo esc_url( $image['src'] ); ?>" 
							data-pswp-index="<?php echo esc_attr( $index ); ?>" 
							data-pswp-width="<?php echo esc_attr( $image['w'] ); ?>" 
							data-pswp-height="<?php echo esc_attr( $image['h'] ); ?>">
								<img src="<?php echo esc_url( $image['thumb'] ); ?>" class="w-full h-full object-center object-cover" alt="">
							</a>
						</div>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
		</div>

		<!-- Product Info -->
		<div class="product-summary mt-10 px-4 sm:px-0 sm:mt-16 lg:mt-0">
			<?php do_action( 'digicommerce_before_single_product_summary' ); ?>

			<h2 class="text-3xl font-extrabold tracking-tight text-dark-blue">
				<?php the_title(); ?>
			</h2>

			<?php do_action( 'digicommerce_after_single_product_title' ); ?>

			<!-- Price Section -->
			<div class="price-section mt-6">
				<?php if ( $price_mode === 'single' && $single_price ) : ?>
					<div class="single-price-wrap">
						<?php
						if ( $sale_price && $sale_price < $single_price ) :
							?>
							<div class="flex items-center gap-2 font-bold">
								<?php
								// Sale price in green
								echo wp_kses(
									$product->format_price(
										$sale_price,
										'sale-price text-4xl text-green-600'
									),
									$allowed_html
								);
								// Regular price struck through
								echo wp_kses(
									$product->format_price(
										$single_price,
										'regular-price text-[1.2rem] text-gray-500 line-through'
									),
									$allowed_html
								);
								?>
							</div>
						<?php else : ?>
							<?php
							// Display only regular price
							echo wp_kses(
								$product->format_price(
									$single_price,
									'single-price text-4xl text-green-600'
								),
								$allowed_html
							);
							?>
						<?php endif; ?>
					</div>
				<?php elseif ( $price_mode === 'variations' && ! empty( $price_variations ) ) : ?>
					<div class="variation-prices space-y-4">
						<?php
						// Find the lowest price among variations
						$lowest_price      = null;
						$lowest_sale_price = null;
						foreach ( $price_variations as $variation ) {
							$current_price      = $variation['price'];
							$current_sale_price = isset( $variation['salePrice'] ) ? $variation['salePrice'] : null;

							// Initialize lowest price if not set
							if ( $lowest_price === null || $current_price < $lowest_price ) {
								$lowest_price = $current_price;
							}

							// Check if this variation has a valid sale price
							if ( $current_sale_price && $current_sale_price < $current_price ) {
								if ( $lowest_sale_price === null || $current_sale_price < $lowest_sale_price ) {
									$lowest_sale_price = $current_sale_price;
								}
							}
						}
						?>
						<!-- Display the "From" price -->
						<div class="flex gap-2 mb-8 font-bold">
							<span class="text-xl text-dark-blue"><?php esc_html_e( 'From:', 'digicommerce' ); ?></span>
							<div class="flex items-center gap-2">
								<?php if ( $lowest_sale_price !== null ) : ?>
									<?php
									// Show sale price
									echo wp_kses(
										$product->format_price(
											$lowest_sale_price,
											'sale-price text-4xl text-green-600'
										),
										$allowed_html
									);
									// Show original price struck through
									echo wp_kses(
										$product->format_price(
											$lowest_price,
											'regular-price text-[1.2rem] text-gray-500 line-through'
										),
										$allowed_html
									);
									?>
									<?php
								else :
									echo wp_kses(
										$product->format_price(
											$lowest_price,
											'regular-price text-2xl text-green-600'
										),
										$allowed_html
									);
								endif;
								?>
							</div>
						</div>

						<p class="text-medium font-bold text-dark-blue mt-0 mb-4"><?php esc_html_e( 'Select an option', 'digicommerce' ); ?></p>
						<div class="flex flex-wrap gap-3">
							<?php foreach ( $price_variations as $index => $variation ) : ?>
								<div class="flex items-center justify-center">
									<input type="radio" 
										id="variation-<?php echo esc_attr( $index ); ?>" 
										name="price_variation" 
										value="<?php echo esc_attr( isset( $variation['salePrice'] ) && $variation['salePrice'] < $variation['price'] ? $variation['salePrice'] : $variation['price'] ); ?>" 
										data-name="<?php echo esc_attr( $variation['name'] ); ?>" 
										data-formatted-price="
										<?php
										echo esc_attr(
											$product->format_price(
												isset( $variation['salePrice'] ) && $variation['salePrice'] < $variation['price'] ? $variation['salePrice'] : $variation['price'],
												'variation-price',
												true
											)
										);
										?>
										" 
										<?php checked( isset( $variation['isDefault'] ) && $variation['isDefault'] ); ?>>
									<label for="variation-<?php echo esc_attr( $index ); ?>" class="default-transition">
										<span class="leading-none font-bold"><?php echo esc_html( $variation['name'] ); ?></span>
										<?php if ( isset( $variation['salePrice'] ) && $variation['salePrice'] < $variation['price'] ) : ?>
											<span class="leading-none flex items-center gap-1">
												<?php
												// Sale price
												echo wp_kses(
													$product->format_price(
														$variation['salePrice'],
														'variation-sale-price'
													),
													$allowed_html
												);
												// Regular price struck through
												echo wp_kses(
													$product->format_price(
														$variation['price'],
														'variation-regular-price text-sm line-through'
													),
													$allowed_html
												);
												?>
											</span>
										<?php else : ?>
											<span class="leading-none">
												<?php echo $product->format_price( $variation['price'], 'variation-price' ); ?>
											</span>
										<?php endif; ?>
									</label>
								</div>
							<?php endforeach; ?>
						</div>
					</div>
				<?php endif; ?>
			</div>

			<?php if ( ! empty( $product_description ) ) : ?>
				<!-- Small description -->
				<div class="product-small-description mt-6">
					<div class="text-base">
						<?php echo wp_kses_post( wpautop( $product_description ) ); ?>
					</div>
				</div>
			<?php endif; ?>

			<?php if ( ! empty( $features ) ) : ?>
				<div class="product-features mt-6">
					<h3 class="text-medium font-bold text-dark-blue m-0 p-0 mb-4"><?php esc_html_e( 'Features', 'digicommerce' ); ?></h3>
					<table class="w-full text-left border-collapse">
						<tbody>
							<?php foreach ( $features as $index => $feature ) : ?>
								<tr class="<?php echo $index % 2 === 0 ? 'bg-gray-100' : ''; ?>">
									<td class="py-2 px-4 text-dark-blue"><?php echo esc_html( $feature['name'] ); ?></td>
									<td class="py-2 px-4"><?php echo esc_html( $feature['text'] ); ?></td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				</div>
			<?php endif; ?>

			<?php do_action( 'digicommerce_single_product_summary' ); ?>

			<!-- Add to Cart Form -->
			<div class="add-to-cart-section mt-8">
				<form class="digicommerce-add-to-cart" method="POST" action="">
					<?php wp_nonce_field( 'digicommerce_add_to_cart', 'cart_nonce' ); ?>
					<input type="hidden" name="action" value="add_to_cart">
					<input type="hidden" name="product_id" value="<?php echo esc_attr( $product_id ); ?>">
					<?php if ( $price_mode === 'variations' ) : ?>
						<input type="hidden" name="variation_name" id="variation-name" value="">
						<input type="hidden" name="variation_price" id="variation-price" value="">
					<?php else : ?>
						<input type="hidden" name="product_price" value="<?php echo esc_attr( ( $sale_price && $sale_price < $single_price ) ? $sale_price : $single_price ); ?>">
					<?php endif; ?>
					<button type="submit" class="w-full bg-dark-blue border border-solid border-transparent rounded-md py-4 px-8 flex items-center justify-center gap-2 text-base font-medium text-white hover:bg-hover-blue focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-dark-blue" id="add-to-cart-button">
						<?php
						if ( $price_mode === 'single' ) {
							// Use sale price if it exists and is lower than regular price
							$display_price = ( $sale_price && $sale_price < $single_price ) ? $sale_price : $single_price;
							echo wp_kses(
								sprintf(
									esc_html__( 'Purchase for %s', 'digicommerce' ),
									$product->format_price(
										$display_price,
										'button-price'
									)
								),
								$allowed_html
							);
						} else {
							esc_html_e( 'Select an option', 'digicommerce' );
						}
						?>
					</button>
				</form>
				
				<?php
				$categories = get_the_terms(get_the_ID(), 'digi_product_cat');
				if ($categories && !is_wp_error($categories)) : ?>
					<div class="product-categories flex items-center justify-between gap-2 mt-4">
						<p class="text-medium font-bold text-dark-blue m-0"><?php esc_html_e('Category:', 'digicommerce'); ?></p>
						<div class="flex flex-wrap">
							<?php 
							echo implode(', ', array_map(function($category) {
								return sprintf(
									'<a href="%s" class="hover:text-blue-600">%s</a>',
									esc_url(get_term_link($category)),
									esc_html($category->name)
								);
							}, $categories));
							?>
						</div>
					</div>
				<?php endif; ?>

				<?php
				$tags = get_the_terms(get_the_ID(), 'digi_product_tag');
				if ($tags && !is_wp_error($tags)) : ?>
					<div class="product-tags flex items-center justify-between gap-2 mt-4">
						<p class="text-medium font-bold text-dark-blue m-0"><?php esc_html_e('Tags:', 'digicommerce'); ?></p>
						<div class="flex flex-wrap">
							<?php 
							echo implode(', ', array_map(function($tag) {
								return sprintf(
									'<a href="%s" class="hover:text-blue-600">%s</a>',
									esc_url(get_term_link($tag)),
									esc_html($tag->name)
								);
							}, $tags));
							?>
						</div>
					</div>
				<?php endif; ?>
			</div>

			<div class="product-share flex flex-col sm:flex-row items-center gap-4 justify-between mt-8">
				<h3 class="text-medium font-bold text-dark-blue m-0"><?php esc_html_e( 'Share on:', 'digicommerce' ); ?></h3>
				<div class="flex gap-2">
					<!-- Facebook Share -->
					<a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo urlencode( get_permalink() ); ?>" target="_blank" rel="noopener noreferrer" class="share-link flex items-center justify-center w-10 h-10 rounded-full bg-dark-blue-10 hover:bg-dark-blue text-dark-blue hover:text-white border border-solid border-dark-blue-20 hover:border-dark-blue default-transition">
						<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" width="18" height="18" class="fill-dark-blue default-transition"><path d="M512 256C512 114.6 397.4 0 256 0S0 114.6 0 256C0 376 82.7 476.8 194.2 504.5V334.2H141.4V256h52.8V222.3c0-87.1 39.4-127.5 125-127.5c16.2 0 44.2 3.2 55.7 6.4V172c-6-.6-16.5-1-29.6-1c-42 0-58.2 15.9-58.2 57.2V256h83.6l-14.4 78.2H287V510.1C413.8 494.8 512 386.9 512 256h0z"/></svg>
						<span class="sr-only"><?php esc_html_e( 'Facebook', 'digicommerce' ); ?></span>
					</a>

					<!-- X (Twitter) Share -->
					<a href="https://twitter.com/intent/tweet?url=<?php echo urlencode( get_permalink() ); ?>&text=<?php echo urlencode( get_the_title() ); ?>" target="_blank" rel="noopener noreferrer" class="share-link flex items-center justify-center w-10 h-10 rounded-full bg-dark-blue-10 hover:bg-dark-blue text-dark-blue hover:text-white border border-solid border-dark-blue-20 hover:border-dark-blue default-transition">
						<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" width="18" height="18" class="fill-dark-blue default-transition"><path d="M389.2 48h70.6L305.6 224.2 487 464H345L233.7 318.6 106.5 464H35.8L200.7 275.5 26.8 48H172.4L272.9 180.9 389.2 48zM364.4 421.8h39.1L151.1 88h-42L364.4 421.8z"/></svg>
						<span class="sr-only"><?php esc_html_e( 'X (Twitter)', 'digicommerce' ); ?></span>
					</a>

					<!-- Pinterest Share -->
					<a href="https://pinterest.com/pin/create/button/?url=<?php echo urlencode( get_permalink() ); ?>&media=<?php echo urlencode( wp_get_attachment_url( get_post_thumbnail_id() ) ); ?>&description=<?php echo urlencode( get_the_title() ); ?>" target="_blank" rel="noopener noreferrer" class="share-link flex items-center justify-center w-10 h-10 rounded-full bg-dark-blue-10 hover:bg-dark-blue text-dark-blue hover:text-white border border-solid border-dark-blue-20 hover:border-dark-blue default-transition">
						<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 496 512" width="18" height="18" class="fill-dark-blue default-transition"><path d="M496 256c0 137-111 248-248 248-25.6 0-50.2-3.9-73.4-11.1 10.1-16.5 25.2-43.5 30.8-65 3-11.6 15.4-59 15.4-59 8.1 15.4 31.7 28.5 56.8 28.5 74.8 0 128.7-68.8 128.7-154.3 0-81.9-66.9-143.2-152.9-143.2-107 0-163.9 71.8-163.9 150.1 0 36.4 19.4 81.7 50.3 96.1 4.7 2.2 7.2 1.2 8.3-3.3 .8-3.4 5-20.3 6.9-28.1 .6-2.5 .3-4.7-1.7-7.1-10.1-12.5-18.3-35.3-18.3-56.6 0-54.7 41.4-107.6 112-107.6 60.9 0 103.6 41.5 103.6 100.9 0 67.1-33.9 113.6-78 113.6-24.3 0-42.6-20.1-36.7-44.8 7-29.5 20.5-61.3 20.5-82.6 0-19-10.2-34.9-31.4-34.9-24.9 0-44.9 25.7-44.9 60.2 0 22 7.4 36.8 7.4 36.8s-24.5 103.8-29 123.2c-5 21.4-3 51.6-.9 71.2C65.4 450.9 0 361.1 0 256 0 119 111 8 248 8s248 111 248 248z"/></svg>
						<span class="sr-only"><?php esc_html_e( 'Pinterest', 'digicommerce' ); ?></span>
					</a>

					<!-- Email Share -->
					<a href="mailto:?subject=<?php echo rawurlencode( get_the_title() ); ?>&body=<?php echo rawurlencode( get_the_permalink() ); ?>" class="share-link flex items-center justify-center w-10 h-10 rounded-full bg-dark-blue-10 hover:bg-dark-blue text-dark-blue hover:text-white border border-solid border-dark-blue-20 hover:border-dark-blue default-transition">
						<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" width="18" height="18" class="fill-dark-blue default-transition"><path d="M48 64C21.5 64 0 85.5 0 112c0 15.1 7.1 29.3 19.2 38.4L236.8 313.6c11.4 8.5 27 8.5 38.4 0L492.8 150.4c12.1-9.1 19.2-23.3 19.2-38.4c0-26.5-21.5-48-48-48L48 64zM0 176L0 384c0 35.3 28.7 64 64 64l384 0c35.3 0 64-28.7 64-64l0-208L294.4 339.2c-22.8 17.1-54 17.1-76.8 0L0 176z"/></svg>
						<span class="sr-only"><?php esc_html_e( 'Email', 'digicommerce' ); ?></span>
					</a>
				</div>
			</div>

			<?php do_action( 'digicommerce_after_single_product_summary' ); ?>
		</div>
	</div>

	<!-- Description -->
	<div class="product-description flex flex-col gap-4">
		<h2 class="text-2xl font-bold text-dark-blue"><?php esc_html_e( 'Description', 'digicommerce' ); ?></h2>
		<div class="text-base">
			<?php the_content(); ?>
		</div>
	</div>

	<?php do_action( 'digicommerce_after_single_product' ); ?>
</div>

<?php
get_footer();