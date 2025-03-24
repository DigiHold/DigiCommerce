<?php
/**
 * Success page template
 */

defined( 'ABSPATH' ) || exit;

$product   = DigiCommerce_Product::instance();
$countries = DigiCommerce()->get_countries();

// Fetch business details from plugin settings
$business_name         = DigiCommerce()->get_option( 'business_name' );
$business_address      = DigiCommerce()->get_option( 'business_address' );
$business_address2     = DigiCommerce()->get_option( 'business_address2' );
$business_city         = DigiCommerce()->get_option( 'business_city' );
$business_postal       = DigiCommerce()->get_option( 'business_postal' );
$business_vat          = DigiCommerce()->get_option( 'business_vat_number' );
$business_country      = DigiCommerce()->get_option( 'business_country' );
$business_country_name = isset( $countries[ $business_country ] ) ? $countries[ $business_country ]['name'] : $business_country;

// Billing details
$billing_details      = $order_data['billing_details'] ?? array();
$company              = ! empty( $billing_details ) ? DigiCommerce()->get_billing_value( $billing_details['company'], $billing_info['company'] ) : '';
$first_name           = ! empty( $billing_details ) ? DigiCommerce()->get_billing_value( $billing_details['first_name'], $billing_info['first_name'] ) : '';
$last_name            = ! empty( $billing_details ) ? DigiCommerce()->get_billing_value( $billing_details['last_name'], $billing_info['last_name'] ) : '';
$billing_address      = ! empty( $billing_details ) ? DigiCommerce()->get_billing_value( $billing_details['address'], $billing_info['address'] ) : '';
$billing_city         = ! empty( $billing_details ) ? DigiCommerce()->get_billing_value( $billing_details['city'], $billing_info['city'] ) : '';
$billing_postcode     = ! empty( $billing_details ) ? DigiCommerce()->get_billing_value( $billing_details['postcode'], $billing_info['postcode'] ) : '';
$vat_number           = ! empty( $billing_details ) ? DigiCommerce()->get_billing_value( $billing_details['vat_number'], $billing_info['vat_number'] ) : '';
$billing_country      = ! empty( $billing_details ) ? DigiCommerce()->get_billing_value( $billing_details['country'], $billing_info['country'] ) : '';
$billing_country_name = isset( $countries[ $billing_country ] ) ? $countries[ $billing_country ]['name'] : $billing_country;

// Business logo
$logo_id = DigiCommerce()->get_option( 'business_logo' );

// Get the URL of the uploaded logo
$logo_url = $logo_id ? wp_get_attachment_url( $logo_id ) : null;

// Prices
$subtotal = 0;
$items    = $order_data['items'] ?? array();

// Calculate subtotal from items
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

// Get billing country and VAT info
$billing_details = $order_data['billing_details'] ?? array();
$billing_country = ! empty( $billing_details ) ? $billing_details['country'] : '';
$vat_number      = ! empty( $billing_details ) ? $billing_details['vat_number'] : '';

// Get business country and buyer country
$business_country = DigiCommerce()->get_option( 'business_country' );
$buyer_country    = $billing_country;
$vat_rate         = isset( $order_data['vat_rate'] ) ? floatval( $order_data['vat_rate'] ) : 0.00;

// Initialize VAT amount
$vat       = 0;
$apply_vat = false;

// Only calculate VAT if taxes are not disabled
if ( ! DigiCommerce()->get_option( 'remove_taxes' ) ) {
	if ( $buyer_country === $business_country ) {
		// Domestic sale: Always charge seller's country VAT
		$apply_vat = true;
		$vat       = round( $subtotal * $vat_rate, 2 );
	} elseif ( ! empty( $countries[ $buyer_country ]['eu'] ) && ! empty( $countries[ $business_country ]['eu'] ) ) {
		// EU cross-border sale
		if ( empty( $vat_number ) || ! DigiCommerce_Orders::instance()->validate_vat_number( $vat_number, $buyer_country ) ) {
			// No valid VAT number - charge buyer's country rate
			$apply_vat = true;
			$vat       = round( $subtotal * $vat_rate, 2 );
		}
		// With valid VAT number - no VAT (vat remains 0)
	}
	// Non-EU sale - no VAT (vat remains 0)
}

// Calculate total with VAT
$total_with_vat = $subtotal + $vat;

// Calculate discount if present
$discount_amount = 0;
if ( ! empty( $order_data['discount_code'] ) ) {
	global $wpdb;
	$coupon = $wpdb->get_row(
		$wpdb->prepare(
			"SELECT * FROM {$wpdb->prefix}digicommerce_coupons 
        WHERE code = %s AND status = 'active'",
			$order_data['discount_code']
		)
	);

	if ( $coupon ) {
		if ( 'percentage' === $coupon->discount_type ) {
			// Apply percentage discount to total with VAT
			$discount_amount = round( ( $total_with_vat * floatval( $coupon->amount ) ) / 100, 2 );
		} else {
			// Fixed amount discount
			$discount_amount = min( floatval( $coupon->amount ), $total_with_vat );
		}
	}
}

// Calculate final total (VAT-inclusive amount minus discount)
$total = $total_with_vat - $discount_amount;

// Format all amounts
$vat   = number_format( $vat, 2, '.', '' );
$total = number_format( $total, 2, '.', '' );
?>
<div class="digicommerce digicommerce-success">
	<?php
	if ( ! empty( $order_data ) ) {
		?>
		<div class="flex flex-col items-center justify-center gap-1 pb-6">
			<p class="text-2xl sm:text-4xl font-bold text-dark-blue m-0 no-margin">
				<?php
				printf(
					// translators: %s: customer first name
					esc_html__( 'Thank you for your purchase %s!', 'digicommerce' ),
					esc_html( $billing_details['first_name'] )
				);
				?>
			</p>
			<p class="text-medium m-0 no-margin"><?php esc_html_e( 'View the details of your order below.', 'digicommerce' ); ?></p>
		</div>
		
		<div id="digicommerce-receipt" class="border border-solid border-[#ddd] rounded-md overflow-hidden">
			<div class="flex flex-col gap-12 p-8">
				<div class="flex flex-col gap-6">
					<div class="grid grid-cols-1 sm:grid-cols-2 items-start justify-between gap-4">
						<?php if ( $logo_url ) { ?>
							<div>
								<img class="max-w-40" decoding="async" id="digicommerce-receipt-logo" src="<?php echo esc_url( $logo_url ); ?>" alt="<?php esc_attr_e( 'Business Logo', 'digicommerce' ); ?>">
							</div>
							<?php
						}

						if ( $logo_url ) {
							$class = 'items-end';
						} else {
							$class = 'items-start';
						}
						?>

						<div class="invoice-header flex flex-col gap-1 <?php echo esc_attr( $class ); ?>">
							<div class="order-id flex gap-2 text-[2rem] leading-normal font-bold text-dark-blue">
								<?php esc_html_e( 'Invoice ID:', 'digicommerce' ); ?>
								<?php echo esc_html( $order_data['order_number'] ?? '—' ); ?>
							</div>

							<div class="order-date flex gap-2 text-medium">
								<strong><?php esc_html_e( 'Date:', 'digicommerce' ); ?></strong>
								<?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $order_data['date_created'] ) ) ); ?>
							</div>
						</div>
					</div>

					<div class="grid grid-cols-1 sm:grid-cols-2 justify-between gap-4">
						<div class="business-info flex flex-col gap-2 text-medium leading-tight w-64">
							<?php
							if ( ! empty( $business_name ) ) {
								?>
								<span class="business-name text-xl font-bold text-dark-blue"><?php echo esc_html( $business_name ); ?></span>
								<?php
							}
							?>
							<div class="business-address flex flex-col gap-1">
								<?php
								if ( ! empty( $business_address ) ) {
									?>
									<span><?php echo esc_html( $business_address ); ?></span>
									<?php
								}

								if ( ! empty( $business_address2 ) ) {
									?>
									<span><?php echo esc_html( $business_address2 ); ?></span>
									<?php
								}

								if ( ! empty( $business_city ) && ! empty( $business_postal ) ) {
									?>
									<span><?php echo esc_html( DigiCommerce_Orders::instance()->format_city_postal( $business_city, $business_postal, $business_country, $countries ) ); ?></span>
									<?php
								}

								if ( ! empty( $business_country_name ) ) {
									?>
									<span><?php echo esc_html( $business_country_name ); ?></span>
									<?php
								}

								if ( ! empty( $business_vat ) ) {
									?>
									<span><?php echo esc_html( $business_vat ); ?></span>
									<?php
								}
								?>
							</div>
						</div>

						<div class="flex flex-col items-end gap-4 text-medium ltr:text-right rtl:text-left">
							<div class="billing-info flex flex-col gap-2 text-medium leading-tight">
								<?php
								if ( ! empty( $company ) ) {
									?>
									<span class="billing-company text-xl font-bold text-dark-blue"><?php echo esc_html( $company ); ?></span>
									<?php
								}
								?>
								<div class="billing-address flex flex-col gap-1">
									<?php
									if ( ! empty( $first_name ) && ! empty( $last_name ) ) {
										?>
										<span><?php echo esc_html( $first_name . ' ' . $last_name ); ?></span>
										<?php
									}

									if ( ! empty( $billing_address ) ) {
										?>
										<span><?php echo esc_html( $billing_address ); ?></span>
										<?php
									}

									if ( ! empty( $billing_city ) && ! empty( $billing_postcode ) ) {
										?>
										<span><?php echo esc_html( DigiCommerce_Orders::instance()->format_city_postal( $billing_city, $billing_postcode, $billing_country, $countries ) ); ?></span>
										<?php
									}

									if ( ! empty( $billing_country_name ) ) {
										?>
										<span><?php echo esc_html( $billing_country_name ); ?></span>
										<?php
									}

									if ( ! empty( $vat_number ) ) {
										?>
										<span><?php esc_html_e( 'VAT: ', 'digicommerce' ); ?><?php echo esc_html( $vat_number ); ?></span>
										<?php
									}
									?>
								</div>
							</div>

							<div class="flex gap-2 text-medium">
								<strong><?php esc_html_e( 'Status:', 'digicommerce' ); ?></strong>
								<?php echo esc_html( ucfirst( $order_data['status'] ?? '' ) ); ?>
							</div>
						</div>
					</div>
				</div>
			</div>

			<h2 class="p-4 m-0"><?php esc_html_e( 'Order Details', 'digicommerce' ); ?></h2>
			<?php
			// Check if DigiCommerce Pro is active and if we have license information
			if ( class_exists( 'DigiCommerce_Pro' ) ) :
				$licenses = DigiCommerce_Pro_License::instance()->get_user_licenses(
					$order_data['user_id'],
					array(
						'status'  => array( 'active', 'expired' ),
						'orderby' => 'date_created',
						'order'   => 'DESC',
					)
				);

				// Filter licenses for this specific order
				$order_licenses = array_filter(
					$licenses,
					function ( $license ) use ( $order_id ) {
						return $license['order_id'] == $order_id;
					}
				);

				if ( ! empty( $order_licenses ) ) :
					?>
					<div class="p-4 mb-4 bg-dark-blue-5 rounded-md">
						<div class="flex flex-col gap-2">
							<h3 class="text-lg font-bold m-0"><?php esc_html_e( 'License Keys', 'digicommerce' ); ?></h3>
							<?php foreach ( $order_licenses as $license ) : ?>
								<div class="flex items-center gap-2">
									<strong><?php echo esc_html( $license['product_name'] ); ?>:</strong>
									<code class="bg-light-blue px-2 py-1 rounded"><?php echo esc_html( $license['license_key'] ); ?></code>
								</div>
							<?php endforeach; ?>
							<?php
							$account_page_id = DigiCommerce()->get_option( 'account_page_id' );
							if ( $account_page_id ) :
								$license_url = add_query_arg(
									array(
										'section' => 'licenses',
									),
									get_permalink( $account_page_id )
								);
								?>
								<a href="<?php echo esc_url( $license_url ); ?>" class="text-dark-blue hover:underline">
									<?php esc_html_e( 'Manage Your Licenses', 'digicommerce' ); ?> →
								</a>
							<?php endif; ?>
						</div>
					</div>
					<?php
				endif;
			endif;
			?>
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
									<div class="inline-flex flex-col gap-2">
										<div class="text-medium font-bold text-dark-blue">
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
											echo '<div class="flex flex-col gap-1 text-sm font-normal text-gray-600">';

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
												'<div class="flex items-center">%s</div>',
												sprintf(
													// translators: %s: subscription billing period
													esc_html__( 'Billed %s until cancellation', 'digicommerce' ),
													esc_html( $period_display )
												)
											);

											// Signup fee
											if ( ! empty( $signup_fee ) && $signup_fee > 0 ) {
												printf(
													'<div class="flex items-center gap-1">%s</div>',
													sprintf(
														// translators: %1$s: signup fee, %2$s: total price
														esc_html__( 'First payment of %1$s then %2$s', 'digicommerce' ),
														DigiCommerce_Product::instance()->format_price( $signup_fee, '' ), // phpcs:ignore
														DigiCommerce_Product::instance()->format_price( $item['total'], '' ) // phpcs:ignore
													)
												);
											}

											// Free trial
											if ( ! empty( $free_trial ) && ! empty( $free_trial['duration'] ) ) {
												printf(
													'<div class="flex items-center">%s</div>',
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
												<div class="flex flex-col items-start gap-2">
													<?php
													// First check if this is a subscription product
													$subscription_enabled = ! empty( $item['subscription_enabled'] );
													$downloadable_files = array();

													foreach ( $files_to_show as $file ) {
														$can_download = false;

														if ( $subscription_enabled ) {
															// Get subscription status for this order/product
															global $wpdb;
															$subscription = $wpdb->get_row(
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
														<div class="flex items-scretch gap-2">
															<select class="py-2 px-3 text-sm rounded border border-solid border-dark-blue-20 bg-white text-dark-blue" 
																	name="file_select" 
																	id="file_select_<?php echo esc_attr( $product_id ); ?>">
																<?php foreach ( $downloadable_files as $file ) : ?>
																	<option value="<?php echo esc_attr( $file['id'] ); ?>">
																		<?php echo esc_html( $file['itemName'] ?? $file['name'] ?? esc_html__( 'Download', 'digicommerce' ) ); ?>
																	</option>
																<?php endforeach; ?>
															</select>
															
															<button type="button" 
																	class="download-item flex items-center gap-2 text-sm rounded py-2 px-3 bg-dark-blue-10 hover:bg-dark-blue text-dark-blue hover:text-white border border-solid border-dark-blue-20 hover:border-dark-blue default-transition" 
																	data-order="<?php echo esc_attr( $order_id ); ?>"
																	data-token="<?php echo esc_attr( $token ); ?>">
																<div class="icon flex-shrink-0">
																	<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 32" width="20" height="20" fill="currentColor">
																		<path d="m28 24v-4a1 1 0 0 0 -2 0v4a1 1 0 0 1 -1 1h-18a1 1 0 0 1 -1-1v-4a1 1 0 0 0 -2 0v4a3 3 0 0 0 3 3h18a3 3 0 0 0 3-3zm-6.38-5.22-5 4a1 1 0 0 1 -1.24 0l-5-4a1 1 0 0 1 1.24-1.56l3.38 2.7v-13.92a1 1 0 0 1 2 0v13.92l3.38-2.7a1 1 0 1 1 1.24 1.56z"/>
																	</svg>
																</div>
																<span class="text flex-grow"><?php echo esc_html__( 'Download', 'digicommerce' ); ?></span>
															</button>
														</div>
														<?php
													else :
														// Single file - show just the download button
														$file = reset( $downloadable_files );
														?>
														<button type="button" 
																class="download-item flex items-center gap-2 text-sm rounded py-2 px-3 bg-dark-blue-10 hover:bg-dark-blue text-dark-blue hover:text-white border border-solid border-dark-blue-20 hover:border-dark-blue default-transition" 
																data-file="<?php echo esc_attr( $file['id'] ); ?>" 
																data-order="<?php echo esc_attr( $order_id ); ?>"
																data-token="<?php echo esc_attr( $token ); ?>">
															<div class="icon flex-shrink-0">
																<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 32" width="20" height="20" fill="currentColor">
																	<path d="m28 24v-4a1 1 0 0 0 -2 0v4a1 1 0 0 1 -1 1h-18a1 1 0 0 1 -1-1v-4a1 1 0 0 0 -2 0v4a3 3 0 0 0 3 3h18a3 3 0 0 0 3-3zm-6.38-5.22-5 4a1 1 0 0 1 -1.24 0l-5-4a1 1 0 0 1 1.24-1.56l3.38 2.7v-13.92a1 1 0 0 1 2 0v13.92l3.38-2.7a1 1 0 1 1 1.24 1.56z"/>
																</svg>
															</div>
															<span class="text flex-grow"><?php echo esc_html__( 'Download', 'digicommerce' ); ?></span>
														</button>
														<?php
													endif;
													?>
												</div>
												<?php
											endif;
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
											echo DigiCommerce_Product::instance()->format_price( $signup_fee, 'total-price' ); // phpcs:ignore
										} elseif ( ! $has_free_trial ) {
											// Show first payment amount
											echo DigiCommerce_Product::instance()->format_price( $item['price'], 'total-price' ); // phpcs:ignore
										} else {
											// Show 0 for free trial with no signup fee
											echo DigiCommerce_Product::instance()->format_price( 0, 'total-price' ); // phpcs:ignore
										}
									} else {
										// Show regular price for non-subscription products
										echo DigiCommerce_Product::instance()->format_price( $item['price'], 'total-price' ); // phpcs:ignore
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
							<td data-label="<?php esc_html_e( 'Subtotal', 'digicommerce' ); ?>" class="text-dark-blue font-bold end">
								<?php echo DigiCommerce_Product::instance()->format_price( $subtotal, 'subtotal-price' ); // phpcs:ignore ?>
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
							<td data-label="<?php esc_html_e( 'VAT', 'digicommerce' ); ?>" class="text-dark-blue font-bold end">
								<?php echo DigiCommerce_Product::instance()->format_price( $vat, 'vat-price' ); // phpcs:ignore ?>
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
							<td data-label="<?php esc_html_e( 'Discount', 'digicommerce' ); ?>" class="text-dark-blue font-bold end">
								<div class="flex justify-end">-<?php echo DigiCommerce_Product::instance()->format_price( $discount_amount, 'discount-amount' ); // phpcs:ignore ?></div>
							</td>
						</tr>
					<?php endif; ?>
					
					<!-- Total -->
					<tr class="order-total">
						<th scope="row"><?php esc_html_e( 'Total:', 'digicommerce' ); ?></th>
						<td data-label="<?php esc_html_e( 'Total', 'digicommerce' ); ?>" class="end">
							<span class="amount">
								<?php echo DigiCommerce_Product::instance()->format_price( $total, 'total-price' ); // phpcs:ignore ?>
							</span>
						</td>
					</tr>
				</tfoot>
			</table>
		</div>
		<?php
	} else {
		?>
		<div class="text-center py-12">
			<div class="mb-6">
				<svg class="mx-auto text-gray-400" fill="none" viewBox="0 0 24 24" width="96" height="96" stroke="currentColor" aria-hidden="true">
					<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
				</svg>
			</div>
			<h2 class="mt-2 text-2xl font-bold text-dark-blue">
				<?php esc_html_e( 'Your session has expired', 'digicommerce' ); ?>
			</h2>
			<p class="mt-2">
				<?php esc_html_e( 'Your session has expired. Please log in to view your orders.', 'digicommerce' ); ?>
			</p>
			<div class="mt-6">
				<a href="<?php echo esc_url( get_permalink( DigiCommerce()->get_option( 'account_page_id' ) ) ); ?>" class="inline-flex items-center px-6 py-3 border border-solid border-transparent text-base font-medium rounded-md shadow-sm no-underline text-dark-blue bg-gold hover:bg-dark-blue hover:text-gold default-transition">
					<?php esc_html_e( 'Go to your account', 'digicommerce' ); ?>
				</a>
			</div>
		</div>
		<?php
	}
	?>
</div>