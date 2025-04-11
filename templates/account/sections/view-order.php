<?php
/**
 * Orders section template for My Account page
 */

defined( 'ABSPATH' ) || exit;

// Get order ID from the order data
$order_id = isset( $order_data['id'] ) ? $order_data['id'] : null;

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

if ( $order_data ) : ?>
	<!-- Orders Header -->
	<div class="flex items-start sm:items-center flex-col sm:flex-row justify-between pb-6">
		<div class="flex flex-col">
			<h2 class="text-[2rem] leading-normal font-bold text-dark-blue m-0 no margin"><?php esc_html_e( 'Order Details', 'digicommerce' ); ?></h2>
			<p class="text-medium m-0 no-margin"><?php esc_html_e( 'View the details of your order below.', 'digicommerce' ); ?></p>
		</div>

		<div>
			<button class="download-pdf flex items-center justify-center gap-2 text-medium rounded py-2 px-3 mt-4 bg-dark-blue-10 hover:bg-dark-blue text-dark-blue hover:text-white border border-solid border-dark-blue-20 hover:border-dark-blue default-transition" data-order="<?php echo esc_attr( $order_id ); ?>">
				<div class="icon"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 32" width="20" height="20" fill="currentColor" ><path d="m28 24v-4a1 1 0 0 0 -2 0v4a1 1 0 0 1 -1 1h-18a1 1 0 0 1 -1-1v-4a1 1 0 0 0 -2 0v4a3 3 0 0 0 3 3h18a3 3 0 0 0 3-3zm-6.38-5.22-5 4a1 1 0 0 1 -1.24 0l-5-4a1 1 0 0 1 1.24-1.56l3.38 2.7v-13.92a1 1 0 0 1 2 0v13.92l3.38-2.7a1 1 0 1 1 1.24 1.56z"/></svg></div>
				<span class="text"><?php esc_html_e( 'Download PDF', 'digicommerce' ); ?><span>
			</button>
		</div>
	</div>

	<div id="digicommerce-receipt" class="border border-solid border-[#ddd] rounded-md overflow-hidden">
		<div class="flex flex-col gap-12 p-8">
			<div class="invoice-header flex flex-col gap-1">
				<div class="pdf-id flex gap-2 text-[2rem] leading-normal font-bold text-dark-blue">
					<?php esc_html_e( 'Invoice ID:', 'digicommerce' ); ?>
					<?php echo esc_html( $order_data['order_number'] ?? '—' ); ?>
				</div>

				<div class="pdf-date flex gap-2 text-medium">
					<strong><?php esc_html_e( 'Date:', 'digicommerce' ); ?></strong>
					<?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $order_data['date_created'] ) ) ); ?>
				</div>
			</div>

			<div class="flex flex-col gap-6">
				<div class="grid grid-cols-1 sm:grid-cols-2 items-start justify-between gap-4">
					<?php if ( $logo_url ) : ?>
						<div>
							<img class="max-w-40" decoding="async" id="digicommerce-receipt-logo" src="<?php echo esc_url( $logo_url ); // phpcs:ignore ?>" alt="<?php esc_attr_e( 'Business Logo', 'digicommerce' ); ?>">
						</div>
					<?php endif; ?>

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
				</div>

				<div class="flex flex-col gap-4 text-medium">
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

		<h2 class="p-4 m-0"><?php esc_html_e( 'Order Details', 'digicommerce' ); ?></h2>
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
												// translators: %s is the billing period
												esc_html__( 'Billed %s until cancellation', 'digicommerce' ),
												esc_html( $period_display )
											)
										);

										// Signup fee
										if ( ! empty( $signup_fee ) && $signup_fee > 0 ) {
											printf(
												'<div class="flex items-center gap-1">%s</div>',
												sprintf(
													// translators: %1$s is the signup fee, %2$s is the total price
													esc_html__( 'First payment of %1$s then %2$s', 'digicommerce' ),
													wp_kses_post( DigiCommerce_Product::instance()->format_price( $signup_fee, '' ) ),
													wp_kses_post( DigiCommerce_Product::instance()->format_price( $item['total'], '' ) )
												)
											);
										}

										// Free trial
										if ( ! empty( $free_trial ) && ! empty( $free_trial['duration'] ) ) {
											printf(
												'<div class="flex items-center">%s</div>',
												sprintf(
													// translators: %1$d is the duration, %2$s is the period
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
															$variation_files      = $variation['files'];
															$show_variation_files = true;
															break;
														}
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
											<div class="no-invoice flex flex-col items-start gap-2">
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
														$can_download = DigiCommerce_Orders::instance()->verify_order_access( $order_id );
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
																data-order="<?php echo esc_attr( $order_id ); ?>">
															<div class="icon flex-shrink-0">
																<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 32" width="20" height="20" fill="currentColor">
																	<path d="m28 24v-4a1 1 0 0 0 -2 0v4a1 1 0 0 1 -1 1h-18a1 1 0 0 1 -1-1v-4a1 1 0 0 0 -2 0v4a3 3 0 0 0 3 3h18a3 3 0 0 0 3-3zm-6.38-5.22-5 4a1 1 0 0 1 -1.24 0l-5-4a1 1 0 0 1 1.24-1.56l3.38 2.7v-13.92a1 1 0 0 1 2 0v13.92l3.38-2.7a1 1 0 1 1 1.24 1.56z"/>
																</svg>
															</div>
															<span class="text flex-grow"><?php esc_html_e( 'Download', 'digicommerce' ); ?></span>
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
															data-order="<?php echo esc_attr( $order_id ); ?>">
														<div class="icon flex-shrink-0">
															<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 32" width="20" height="20" fill="currentColor">
																<path d="m28 24v-4a1 1 0 0 0 -2 0v4a1 1 0 0 1 -1 1h-18a1 1 0 0 1 -1-1v-4a1 1 0 0 0 -2 0v4a3 3 0 0 0 3 3h18a3 3 0 0 0 3-3zm-6.38-5.22-5 4a1 1 0 0 1 -1.24 0l-5-4a1 1 0 0 1 1.24-1.56l3.38 2.7v-13.92a1 1 0 0 1 2 0v13.92l3.38-2.7a1 1 0 1 1 1.24 1.56z"/>
															</svg>
														</div>
														<span class="text flex-grow"><?php esc_html_e( 'Download', 'digicommerce' ); ?></span>
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
								<?php echo wp_kses_post( DigiCommerce_Product::instance()->format_price( $item['price'], 'total-price' ) ); ?>
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
							<?php echo wp_kses_post( DigiCommerce_Product::instance()->format_price( $order_data['subtotal'], 'subtotal-price' ) ); ?>
						</td>
					</tr>
					
					<!-- VAT -->
					<tr>
						<th scope="row">
							<?php
							// Get billing country code from order data
							$billing_country_code = $billing_details['country'] ?? '';

							// Get VAT rate from the countries array if available
							$vat_rate = $order_data['vat_rate'];
							if ( ! empty( $billing_country_code ) && isset( $countries[ $billing_country_code ]['tax_rate'] ) ) {
								$vat_rate = $countries[ $billing_country_code ]['tax_rate'];
							}

							printf(
								'%s (%s%%):',
								esc_html__( 'VAT', 'digicommerce' ),
								esc_html( rtrim( rtrim( number_format( $vat_rate * 100, 3 ), '0' ), '.' ) )
							);
							?>
						</th>
						<td data-label="<?php esc_html_e( 'VAT', 'digicommerce' ); ?>" class="text-dark-blue font-bold end">
							<?php echo wp_kses_post( DigiCommerce_Product::instance()->format_price( $order_data['vat'], 'vat-price' ) ); ?>
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
							<div class="flex justify-end">-<?php echo wp_kses_post( DigiCommerce_Product::instance()->format_price( $order_data['discount_amount'], 'discount-amount' ) ); ?></div>
						</td>
					</tr>
				<?php endif; ?>
				
				<!-- Total -->
				<tr class="order-total">
					<th scope="row"><?php esc_html_e( 'Total:', 'digicommerce' ); ?></th>
					<td data-label="<?php esc_html_e( 'Total', 'digicommerce' ); ?>" class="end">
						<span class="amount">
							<?php echo wp_kses_post( DigiCommerce_Product::instance()->format_price( $order_data['total'], 'total-price' ) ); ?>
						</span>
					</td>
				</tr>
			</tfoot>
		</table>

		<?php
		// Check if order has related subscription
		global $wpdb;
		$subscription_items_table = $wpdb->prefix . 'digicommerce_subscription_items';
		$subscriptions_table      = $wpdb->prefix . 'digicommerce_subscriptions';

		$subscription = $wpdb->get_row( // phpcs:ignore
			$wpdb->prepare(
				"SELECT s.* FROM {$subscription_items_table} si JOIN {$subscriptions_table} s ON si.subscription_id = s.id WHERE si.order_id = %d LIMIT 1", // phpcs:ignore
				$order_id
			),
			ARRAY_A
		);

		if ( $subscription ) :
			// Get status classes (copied from subscriptions template)
			$status_classes = array(
				'active'    => 'bg-green-600 text-white',
				'paused'    => 'bg-yellow text-[#8d752d]',
				'cancelled' => 'bg-red-600 text-white',
				'trash'     => 'bg-red-600 text-white',
			);
			?>
			<div class="no-invoice flex flex-col">
				<h2 class="text-xl font-bold text-dark-blue mt-4 p-4 m-0"><?php esc_html_e( 'Related Subscription', 'digicommerce' ); ?></h2>
				<div class="overflow-hidden">
					<table class="digicommerce-table no-invoice">
						<thead>
							<tr>
								<th scope="col" class="px-6 py-3 ltr:text-left rtl:text-right text-xs font-bold text-dark-blue"><?php esc_html_e( 'ID', 'digicommerce' ); ?></th>
								<th scope="col" class="px-6 py-3 ltr:text-left rtl:text-right text-xs font-bold text-dark-blue"><?php esc_html_e( 'Next Payment', 'digicommerce' ); ?></th>
								<th scope="col" class="px-6 py-3 ltr:text-left rtl:text-right text-xs font-bold text-dark-blue"><?php esc_html_e( 'Status', 'digicommerce' ); ?></th>
								<th scope="col" class="px-6 py-3 ltr:text-left rtl:text-right text-xs font-bold text-dark-blue"><?php esc_html_e( 'Amount', 'digicommerce' ); ?></th>
								<th scope="col" class="relative px-6 py-3"><span class="sr-only"><?php esc_html_e( 'Actions', 'digicommerce' ); ?></span></th>
							</tr>
						</thead>
						<tbody class="bg-white divide-y divide-gray-200">
							<tr>
								<td class="px-6 py-4" data-label="<?php esc_html_e( 'ID', 'digicommerce' ); ?>">
									<span class="whitespace-nowrap text-medium font-bold text-dark-blue">
										<?php
										// Link
										$num_link = add_query_arg(
											array(
												'section'           => 'subscriptions',
												'view-subscription' => $subscription['id'],
											),
											get_permalink()
										);
										?>
										<a href="<?php echo esc_url( $num_link ); ?>" class="whitespace-nowrap text-medium font-bold text-dark-blue hover:text-gold default-transition">
											<?php echo esc_html( $subscription['subscription_number'] ); ?>
										</a>
									</span>
								</td>
								<td class="px-6 py-4 whitespace-nowrap text-sm text-dark-blue" data-label="<?php esc_html_e( 'Next Payment', 'digicommerce' ); ?>">
									<?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $subscription['next_payment'] ) ) ); ?>
								</td>
								<td class="px-6 py-4 whitespace-nowrap" data-label="<?php esc_html_e( 'Status', 'digicommerce' ); ?>">
									<span class="inline-flex py-1 px-2 font-bold uppercase text-sm rounded <?php echo esc_attr( $status_classes[ $subscription['status'] ] ?? '' ); ?>">
										<?php echo esc_html( ucfirst( $subscription['status'] ) ); ?>
									</span>
								</td>
								<td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-green-600" data-label="<?php esc_html_e( 'Amount', 'digicommerce' ); ?>">
									<?php echo wp_kses_post( DigiCommerce_Product::instance()->format_price( $subscription['total'], 'subscription-price' ) ); ?>
								</td>
								<td class="px-6 py-4 whitespace-nowrap ltr:text-right rtl:text-left text-sm font-medium space-x-2" data-label="<?php esc_html_e( 'Actions', 'digicommerce' ); ?>">
									<?php
									// Link
									$sub_link = add_query_arg(
										array(
											'section'           => 'subscriptions',
											'view-subscription' => $subscription['id'],
										),
										get_permalink()
									);
									?>
									<a href="<?php echo esc_url( $sub_link ); ?>" class="whitespace-nowrap text-medium font-bold text-dark-blue hover:text-gold default-transition">
										<?php esc_html_e( 'View details', 'digicommerce' ); ?>
									</a>
								</td>
							</tr>
						</tbody>
					</table>
				</div>
			</div>
		<?php endif; ?>
	</div>
<?php else : ?>
	<p class="text-2xl font-bold m-0">
		<?php esc_html_e( 'Order not found.', 'digicommerce' ); ?>
	</p>
<?php endif; ?>