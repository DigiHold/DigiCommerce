<?php
/**
 * DigiCommerce Order Details Block
 *
 * @package DigiCommerce
 */

defined( 'ABSPATH' ) || exit;

/**
 * Order Details Block Class
 */
class DigiCommerce_Order_Details_Block {

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
			DIGICOMMERCE_PLUGIN_DIR . 'assets/blocks/order-details',
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
		// Check if we're on the success page with valid order
		if ( ! isset( $_GET['order_id'] ) || ! isset( $_GET['token'] ) ) {
			return '';
		}

		$order_id = intval( $_GET['order_id'] );
		$token    = sanitize_text_field( $_GET['token'] );

		// Verify order access
		if ( ! DigiCommerce_Orders::instance()->verify_order_access( $order_id, $token ) ) {
			return '';
		}

		// Get order data
		$order_data = DigiCommerce_Orders::instance()->get_order( $order_id );
		if ( ! $order_data ) {
			return '';
		}

		$product = DigiCommerce_Product::instance();
		$countries = DigiCommerce()->get_countries();

		// Calculate totals
		$subtotal = 0;
		$items    = $order_data['items'] ?? array();

		// Calculate subtotal from items - EXACT SAME LOGIC AS payment-success.php
		foreach ( $items as $item ) {
			$subscription_enabled = ! empty( $item['subscription_enabled'] );
			$signup_fee           = ! empty( $item['subscription_signup_fee'] ) ? floatval( $item['subscription_signup_fee'] ) : 0;
			$has_free_trial       = ! empty( $item['subscription_free_trial'] ) &&
							! empty( $item['subscription_free_trial']['duration'] ) &&
							intval( $item['subscription_free_trial']['duration'] ) > 0;

			// Determine the base price for this item
			$item_base_price = 0;
			if ( $subscription_enabled ) {
				if ( $signup_fee > 0 ) {
					// For subscription with signup fee, use signup fee as initial payment
					$item_base_price = $signup_fee;
				} elseif ( ! $has_free_trial ) {
					// For subscription without signup fee and no trial, use first payment
					$item_base_price = floatval( $item['price'] );
				}
				// If subscription has free trial and no signup fee, price is 0
			} else {
				// For regular products, use regular price
				$item_base_price = floatval( $item['price'] );
			}

			// Subtotal
			$subtotal += $item_base_price;
		}

		// Format subtotal
		$subtotal = number_format( $subtotal, 2, '.', '' );

		// Get VAT and discount info
		$vat      = floatval( $order_data['vat'] ?? 0 );
		$vat_rate = floatval( $order_data['vat_rate'] ?? 0 );
		$discount_amount = floatval( $order_data['discount_amount'] ?? 0 );
		
		// Calculate total
		$total = floatval( $order_data['total'] ?? 0 );

		// Get wrapper attributes with all the styling supports
		$wrapper_attributes = get_block_wrapper_attributes();

		ob_start();
		?>
		<div <?php echo $wrapper_attributes; // phpcs:ignore ?>>
			<h2 class="digicommerce-order-details__title"><?php esc_html_e( 'Order Details', 'digicommerce' ); ?></h2>
			
			<table class="digicommerce-table">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Product', 'digicommerce' ); ?></th>
						<th class="end"><?php esc_html_e( 'Total', 'digicommerce' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php
					if ( ! empty( $order_data['items'] ) ) :
						foreach ( $order_data['items'] as $item ) :
							?>
							<tr>
								<td data-label="<?php esc_html_e( 'Product', 'digicommerce' ); ?>">
									<div class="digicommerce-order-details__product">
										<div class="digicommerce-order-details__product-name">
											<?php
											echo esc_html( $item['name'] );
											if ( ! empty( $item['variation_name'] ) ) {
												echo ' - ' . esc_html( $item['variation_name'] );
											}
											?>
										</div>

										<?php
										// Check if product is a subscription
										$subscription_enabled = ! empty( $item['subscription_enabled'] );
										if ( $subscription_enabled ) {
											$subscription_period = ! empty( $item['subscription_period'] ) ? $item['subscription_period'] : 'month';
											$free_trial          = ! empty( $item['subscription_free_trial'] ) ? $item['subscription_free_trial'] : array(
												'duration' => 0,
												'period'   => 'days',
											);
											$signup_fee          = ! empty( $item['subscription_signup_fee'] ) ? floatval( $item['subscription_signup_fee'] ) : 0;

											// Display subscription details
											echo '<div class="digicommerce-order-details__subscription-info">';

											// Billing period
											$period_display = '';
											switch ( $subscription_period ) {
												case 'day':
													$period_display = esc_html__( 'daily', 'digicommerce' );
													break;
												case 'week':
													$period_display = esc_html__( 'weekly', 'digicommerce' );
													break;
												case 'month':
													$period_display = esc_html__( 'monthly', 'digicommerce' );
													break;
												case 'year':
													$period_display = esc_html__( 'annually', 'digicommerce' );
													break;
												default:
													$period_display = $subscription_period . 'ly';
											}

											printf(
												'<div class="digicommerce-order-details__subscription-period">%s</div>',
												sprintf(
													// translators: %s: subscription billing period
													esc_html__( 'Billed %s until cancellation', 'digicommerce' ),
													esc_html( $period_display )
												)
											);

											// Signup fee
											if ( ! empty( $signup_fee ) && $signup_fee > 0 ) {
												printf(
													'<div class="digicommerce-order-details__subscription-signup">%s</div>',
													sprintf(
														// translators: %1$s: signup fee, %2$s: total price
														esc_html__( 'First payment of %1$s then %2$s', 'digicommerce' ),
														wp_kses_post( DigiCommerce_Product::instance()->format_price( $signup_fee, '' ) ),
														wp_kses_post( DigiCommerce_Product::instance()->format_price( $item['total'], '' ) )
													)
												);
											}

											// Free trial
											if ( ! empty( $free_trial ) && ! empty( $free_trial['duration'] ) ) {
												printf(
													'<div class="digicommerce-order-details__subscription-trial">%s</div>',
													sprintf(
														// translators: %1$d: free trial duration, %2$s: free trial period
														esc_html__( '%1$d %2$s free trial', 'digicommerce' ),
														esc_html( $free_trial['duration'] ),
														esc_html( $free_trial['period'] )
													)
												);
											}

											echo '</div>';
										}

										$product_id = isset( $item['product_id'] ) ? absint( $item['product_id'] ) : 0;

										if ( $product_id && $order_id ) {
											// Check if this is a bundle product
											$is_bundle_item = !empty($item['is_bundle']) && !empty($item['bundle_products']);

											// Fallback: check product meta if order item doesn't have bundle flag
											if (!$is_bundle_item) {
												$bundle_products_meta = get_post_meta( $product_id, 'digi_bundle_products', true );
												$is_bundle_from_meta = !empty($bundle_products_meta) && is_array($bundle_products_meta) && count(array_filter($bundle_products_meta)) > 0;
												
												// If it's a bundle from meta but doesn't have bundle_products in item, reconstruct the data
												if ($is_bundle_from_meta) {
													$item['is_bundle'] = true;
													$item['bundle_products'] = array();
													
													foreach ($bundle_products_meta as $bundle_product_id) {
														if (empty($bundle_product_id)) continue;
														
														$bundle_product_id = intval($bundle_product_id);
														$bundle_product = get_post($bundle_product_id);
														if ($bundle_product) {
															$bundle_files = get_post_meta($bundle_product_id, 'digi_files', true);
															$item['bundle_products'][] = array(
																'product_id' => $bundle_product_id,
																'name' => $bundle_product->post_title,
																'files' => $bundle_files ?: array(),
															);
														}
													}
													$is_bundle_item = true;
												}
											}

											if ( $is_bundle_item ) {
												// Display bundle products
												?>
												<div class="digicommerce-order-details__bundle">
													<div class="digicommerce-order-details__bundle-title">
														<?php esc_html_e( 'Bundle includes:', 'digicommerce' ); ?>
													</div>
													<?php 
													// Ensure bundle_products exists and is an array
													$bundle_products = isset($item['bundle_products']) && is_array($item['bundle_products']) ? $item['bundle_products'] : array();
													
													foreach ( $bundle_products as $bundle_product ) : 
														$bundle_product_id = isset($bundle_product['product_id']) ? intval($bundle_product['product_id']) : 0;
														$bundle_product_name = isset($bundle_product['name']) ? $bundle_product['name'] : '';
														
														if (!$bundle_product_id || !$bundle_product_name) continue;
													?>
														<div class="digicommerce-order-details__bundle-product">
															<div class="digicommerce-order-details__bundle-product-name">
																<?php echo esc_html( $bundle_product_name ); ?>
															</div>
															<?php
															$bundle_files = isset($bundle_product['files']) && is_array($bundle_product['files']) ? $bundle_product['files'] : array();
															
															if ( !empty( $bundle_files ) ) :
																$downloadable_files = array();
																
																foreach ( $bundle_files as $file ) {
																	$can_download = DigiCommerce_Orders::instance()->verify_order_access( $order_id, $token );
																	if ( $can_download && ! empty( $file['id'] ) ) {
																		$downloadable_files[] = $file;
																	}
																}

																if ( count( $downloadable_files ) > 1 ) :
																	// Multiple files - show dropdown
																	$downloadable_files = array_reverse( $downloadable_files );
																	?>
																	<div class="digicommerce-order-details__download-group">
																		<select class="digicommerce-order-details__file-select" 
																				name="file_select" 
																				id="file_select_bundle_<?php echo esc_attr( $bundle_product_id ); ?>">
																			<?php foreach ( $downloadable_files as $file ) : ?>
																				<option value="<?php echo esc_attr( $file['id'] ); ?>">
																					<?php echo esc_html( $file['itemName'] ?? $file['name'] ?? esc_html__( 'Download', 'digicommerce' ) ); ?>
																				</option>
																			<?php endforeach; ?>
																		</select>
																		
																		<button type="button" 
																				class="digicommerce-order-details__download-btn download-item" 
																				data-order="<?php echo esc_attr( $order_id ); ?>"
																				data-token="<?php echo esc_attr( $token ); ?>">
																			<div class="icon">
																				<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 32" width="20" height="20" fill="currentColor">
																					<path d="m28 24v-4a1 1 0 0 0 -2 0v4a1 1 0 0 1 -1 1h-18a1 1 0 0 1 -1-1v-4a1 1 0 0 0 -2 0v4a3 3 0 0 0 3 3h18a3 3 0 0 0 3-3zm-6.38-5.22-5 4a1 1 0 0 1 -1.24 0l-5-4a1 1 0 0 1 1.24-1.56l3.38 2.7v-13.92a1 1 0 0 1 2 0v13.92l3.38-2.7a1 1 0 1 1 1.24 1.56z"/>
																				</svg>
																			</div>
																			<span class="text"><?php esc_html_e( 'Download', 'digicommerce' ); ?></span>
																		</button>
																	</div>
																	<?php
																elseif ( count( $downloadable_files ) === 1 ) :
																	// Single file - show download button
																	$file = reset( $downloadable_files );
																	$file_name = $file['itemName'] ?? $file['name'] ?? esc_html__( 'Download', 'digicommerce' );
																	?>
																	<button type="button" 
																			class="digicommerce-order-details__download-btn download-item" 
																			data-file="<?php echo esc_attr( $file['id'] ); ?>" 
																			data-order="<?php echo esc_attr( $order_id ); ?>"
																			data-token="<?php echo esc_attr( $token ); ?>">
																		<div class="icon">
																			<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 32" width="20" height="20" fill="currentColor">
																				<path d="m28 24v-4a1 1 0 0 0 -2 0v4a1 1 0 0 1 -1 1h-18a1 1 0 0 1 -1-1v-4a1 1 0 0 0 -2 0v4a3 3 0 0 0 3 3h18a3 3 0 0 0 3-3zm-6.38-5.22-5 4a1 1 0 0 1 -1.24 0l-5-4a1 1 0 0 1 1.24-1.56l3.38 2.7v-13.92a1 1 0 0 1 2 0v13.92l3.38-2.7a1 1 0 1 1 1.24 1.56z"/>
																			</svg>
																		</div>
																		<span class="text"><?php printf( esc_html__( 'Download %s', 'digicommerce' ), esc_html( $file_name ) ); ?></span>
																	</button>
																	<?php
																endif;
															else :
																// No files available for this bundle product
																?>
																<div class="digicommerce-order-details__no-files">
																	<?php esc_html_e( 'No downloadable files', 'digicommerce' ); ?>
																</div>
																<?php
															endif;
															?>
														</div>
													<?php endforeach; ?>
												</div>
												<?php
											} else {
												// REGULAR PRODUCT FILES
												$price_mode           = get_post_meta( $product_id, 'digi_price_mode', true );
												$variation_name       = isset( $item['variation_name'] ) ? $item['variation_name'] : '';
												$show_variation_files = false;
												$variation_files      = array();
												$regular_files        = array();

												// First check for variation files if it's a variable product
												if ( 'variations' === $price_mode && ! empty( $variation_name ) ) {
													$variations = get_post_meta( $product_id, 'digi_price_variations', true );

													if ( ! empty( $variations ) && is_array( $variations ) ) {
														foreach ( $variations as $variation ) {
															if ( isset( $variation['name'] ) && $variation['name'] === $variation_name ) {
																if ( ! empty( $variation['files'] ) && is_array( $variation['files'] ) ) {
																	$variation_files = $variation['files'];
																	$show_variation_files = true;
																} else {
																	$show_variation_files = false;
																}
																break;
															}
														}
													}
												}

												// Only get regular files if no variation files were found
												if ( ! $show_variation_files ) {
													$cache_key     = 'product_files_' . $product_id;
													$regular_files = wp_cache_get( $cache_key, 'digicommerce_files' );

													if ( false === $regular_files ) {
														$regular_files = get_post_meta( $product_id, 'digi_files', true );

														if ( ! empty( $regular_files ) && is_array( $regular_files ) ) {
															wp_cache_set( $cache_key, $regular_files, 'digicommerce_files', HOUR_IN_SECONDS );
														}
													}
												}

												// Use variation files if available, otherwise fall back to regular files
												$files_to_show = $show_variation_files ? $variation_files : $regular_files;

												if ( ! empty( $files_to_show ) && is_array( $files_to_show ) ) :
													?>
													<div class="digicommerce-order-details__download">
														<?php
														// First check if this is a subscription product
														$subscription_enabled = ! empty( $item['subscription_enabled'] );
														$downloadable_files = array();

														foreach ( $files_to_show as $file ) {
															$can_download = false;

															if ( $subscription_enabled ) {
																// Get subscription status for this order/product
																global $wpdb;
																$subscription = $wpdb->get_row( // phpcs:ignore
																	$wpdb->prepare(
																		"SELECT s.* 
																		FROM {$wpdb->prefix}digicommerce_subscription_items si
																		JOIN {$wpdb->prefix}digicommerce_subscriptions s ON si.subscription_id = s.id
																		WHERE si.order_id = %d AND si.product_id = %d
																		LIMIT 1",
																		$order_id,
																		$product_id
																	),
																	ARRAY_A
																);

																if ( $subscription ) {
																	if ( 'active' === $subscription['status'] ) {
																		$can_download = true;
																	} elseif ( 'cancelled' === $subscription['status'] ) {
																		$next_payment = strtotime( $subscription['next_payment'] );
																		$now         = time();
																		$can_download = ( $now < $next_payment );
																	}
																}
															} else {
																// Regular product - use normal order access check
																$can_download = DigiCommerce_Orders::instance()->verify_order_access( $order_id, $token );
															}

															if ( $can_download && ! empty( $file['id'] ) ) {
																$downloadable_files[] = $file;
															}
														}

														if ( count( $downloadable_files ) > 1 ) :
															// Reverse the array so newest files appear first
															$downloadable_files = array_reverse( $downloadable_files );
															?>
															<div class="digicommerce-order-details__download-group">
																<select class="digicommerce-order-details__file-select" 
																		name="file_select" 
																		id="file_select_<?php echo esc_attr( $product_id ); ?>">
																	<?php foreach ( $downloadable_files as $file ) : ?>
																		<option value="<?php echo esc_attr( $file['id'] ); ?>">
																			<?php echo esc_html( $file['itemName'] ?? $file['name'] ?? esc_html__( 'Download', 'digicommerce' ) ); ?>
																		</option>
																	<?php endforeach; ?>
																</select>
																
																<button type="button" 
																		class="digicommerce-order-details__download-btn download-item" 
																		data-order="<?php echo esc_attr( $order_id ); ?>"
																		data-token="<?php echo esc_attr( $token ); ?>">
																	<div class="icon">
																		<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 32" width="20" height="20" fill="currentColor">
																			<path d="m28 24v-4a1 1 0 0 0 -2 0v4a1 1 0 0 1 -1 1h-18a1 1 0 0 1 -1-1v-4a1 1 0 0 0 -2 0v4a3 3 0 0 0 3 3h18a3 3 0 0 0 3-3zm-6.38-5.22-5 4a1 1 0 0 1 -1.24 0l-5-4a1 1 0 0 1 1.24-1.56l3.38 2.7v-13.92a1 1 0 0 1 2 0v13.92l3.38-2.7a1 1 0 1 1 1.24 1.56z"/>
																		</svg>
																	</div>
																	<span class="text"><?php echo esc_html__( 'Download', 'digicommerce' ); ?></span>
																</button>
															</div>
															<?php
														elseif ( count( $downloadable_files ) === 1 ) :
															// Single file - show just the download button
															$file = reset( $downloadable_files );
															?>
															<button type="button" 
																	class="digicommerce-order-details__download-btn download-item" 
																	data-file="<?php echo esc_attr( $file['id'] ); ?>" 
																	data-order="<?php echo esc_attr( $order_id ); ?>"
																	data-token="<?php echo esc_attr( $token ); ?>">
																<div class="icon">
																	<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 32" width="20" height="20" fill="currentColor">
																		<path d="m28 24v-4a1 1 0 0 0 -2 0v4a1 1 0 0 1 -1 1h-18a1 1 0 0 1 -1-1v-4a1 1 0 0 0 -2 0v4a3 3 0 0 0 3 3h18a3 3 0 0 0 3-3zm-6.38-5.22-5 4a1 1 0 0 1 -1.24 0l-5-4a1 1 0 0 1 1.24-1.56l3.38 2.7v-13.92a1 1 0 0 1 2 0v13.92l3.38-2.7a1 1 0 1 1 1.24 1.56z"/>
																	</svg>
																</div>
																<span class="text"><?php echo esc_html__( 'Download', 'digicommerce' ); ?></span>
															</button>
															<?php
														endif;
														?>
													</div>
													<?php
												endif;
											}
										}
										?>
									</div>
								</td>
								<td data-label="<?php esc_html_e( 'Total', 'digicommerce' ); ?>" class="end">
									<?php
									$subscription_enabled = ! empty( $item['subscription_enabled'] );
									$signup_fee           = ! empty( $item['subscription_signup_fee'] ) ? floatval( $item['subscription_signup_fee'] ) : 0;
									$has_free_trial       = ! empty( $item['subscription_free_trial'] ) &&
													! empty( $item['subscription_free_trial']['duration'] ) &&
													intval( $item['subscription_free_trial']['duration'] ) > 0;

									if ( $subscription_enabled ) {
										if ( $signup_fee > 0 ) {
											// Show signup fee
											echo wp_kses_post( DigiCommerce_Product::instance()->format_price( $signup_fee, 'total-price' ) );
										} elseif ( ! $has_free_trial ) {
											// Show first payment amount
											echo wp_kses_post( DigiCommerce_Product::instance()->format_price( $item['price'], 'total-price' ) );
										} else {
											// Show 0 for free trial with no signup fee
											echo wp_kses_post( DigiCommerce_Product::instance()->format_price( 0, 'total-price' ) );
										}
									} else {
										// Show regular price for non-subscription products
										echo wp_kses_post( DigiCommerce_Product::instance()->format_price( $item['price'], 'total-price' ) );
									}
									?>
								</td>
							</tr>
							<?php
						endforeach;
					endif;
					?>
				</tbody>
				<tfoot>
					<?php
					if ( ! DigiCommerce()->get_option( 'remove_taxes' ) ) {
						?>
						<!-- Subtotal -->
						<tr>
							<th scope="row"><?php esc_html_e( 'Subtotal:', 'digicommerce' ); ?></th>
							<td data-label="<?php esc_html_e( 'Subtotal', 'digicommerce' ); ?>" class="digicommerce-order-details__subtotal-value end">
								<?php echo wp_kses_post( DigiCommerce_Product::instance()->format_price( $subtotal, 'subtotal-price' ) ); ?>
							</td>
						</tr>

						<!-- VAT -->
						<tr>
							<th scope="row">
								<?php
								printf(
									'%s (%s%%):',
									esc_html__( 'VAT', 'digicommerce' ),
									esc_html( rtrim( rtrim( number_format( $vat_rate * 100, 3 ), '0' ), '.' ) )
								);
								?>
							</th>
							<td data-label="<?php esc_html_e( 'VAT', 'digicommerce' ); ?>" class="digicommerce-order-details__vat-value end">
								<?php echo wp_kses_post( DigiCommerce_Product::instance()->format_price( $vat, 'vat-price' ) ); ?>
							</td>
						</tr>
						<?php
					}
					?>

					<!-- Discount row (new) -->
					<?php if ( ! empty( $order_data['discount_amount'] ) && $order_data['discount_amount'] > 0 ) : ?>
						<tr>
							<th scope="row">
								<?php esc_html_e( 'Coupon:', 'digicommerce' ); ?>
							</th>
							<td data-label="<?php esc_html_e( 'Discount', 'digicommerce' ); ?>" class="digicommerce-order-details__discount-value end">
								<div class="digicommerce-order-details__discount">
									-<?php echo wp_kses_post( DigiCommerce_Product::instance()->format_price( $discount_amount, 'discount-amount' ) ); ?>
								</div>
							</td>
						</tr>
					<?php endif; ?>
					
					<!-- Total -->
					<tr class="order-total">
						<th scope="row"><?php esc_html_e( 'Total:', 'digicommerce' ); ?></th>
						<td data-label="<?php esc_html_e( 'Total', 'digicommerce' ); ?>" class="end">
							<span class="amount">
								<?php echo wp_kses_post( DigiCommerce_Product::instance()->format_price( $total, 'total-price' ) ); ?>
							</span>
						</td>
					</tr>
				</tfoot>
			</table>
		</div>
		<?php
		return ob_get_clean();
	}
}

// Initialize the block
DigiCommerce_Order_Details_Block::init();