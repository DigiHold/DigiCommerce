<?php
$orders  = DigiCommerce_Orders::instance();
$product = DigiCommerce_Product::instance();

if ( ! $order ) {
	wp_die( esc_html__( 'Order not found.', 'digicommerce' ) );
}

// Calculate totals
$total_items    = count( $order['items'] );
$total_price    = 0;
$total_vat      = 0;
$total_with_vat = 0;

// Get subscription data if exists
$subscription_data = $orders->get_order_subscription_data( $order_id );

$billing = $order['billing_details'] ?? array();

// Show update message if order was just updated
if ( isset( $_GET['updated'] ) && '1' === trim( sanitize_text_field( $_GET['updated'] ) ) ) {
	?>
	<div class="notice notice-success">
		<p><?php esc_html_e( 'Order updated successfully.', 'digicommerce' ); ?></p>
	</div>
	<?php
}
?>
<div class="wrap">
	<h1><?php esc_html_e( 'Edit Order', 'digicommerce' ); ?> <?php echo esc_html( $order['order_number'] ); ?></h1>
	<form id="edit-order-form" method="post" action="<?php echo esc_url( admin_url( 'admin.php?page=digi-orders&action=edit&id=' . $order_id ) ); ?>">
		<?php wp_nonce_field( 'edit_order_nonce', 'edit_order_nonce_field' ); ?>
		<div id="poststuff">
			<!-- Order Details -->
			<div id="post-body" class="metabox-holder columns-2">
				<div id="post-body-content">
					<div class="postbox order-items">
						<div class="postbox-header">
							<h2 class="hndle"><?php esc_html_e( 'Order Items', 'digicommerce' ); ?></h2>
						</div>
						<table class="widefat">
							<thead>
								<tr>
									<th><?php esc_html_e( 'Product', 'digicommerce' ); ?></th>
									<th><?php esc_html_e( 'Variation', 'digicommerce' ); ?></th>
									<th><?php esc_html_e( 'Base Price', 'digicommerce' ); ?></th>
									<?php
									if ( ! DigiCommerce()->get_option( 'remove_taxes' ) ) {
										?>
										<th><?php esc_html_e( 'VAT', 'digicommerce' ); ?></th>
										<?php
									}
									?>
									<th><?php esc_html_e( 'Total', 'digicommerce' ); ?></th>
								</tr>
							</thead>
							<tbody>
								<?php
								$total_base_price = 0;
								$total_vat        = 0;

								foreach ( $order['items'] as $item ) :
									// Get base price
									$base_price = floatval( $item['price'] ); // Always use the base price

									if ( ! DigiCommerce()->get_option( 'remove_taxes' ) ) {
										// Calculate VAT for this item
										$vat_amount = $order['vat_rate'] > 0 ? round( $base_price * $order['vat_rate'], 2 ) : 0;
									} else {
										$vat_amount = 0;
									}

									// Add to totals
									$total_base_price += $base_price;
									$total_vat        += $vat_amount;
									?>
									<tr>
										<td><?php echo esc_html( $item['name'] ); ?></td>
										<td><?php echo esc_html( $item['variation_name'] ?? '—' ); ?></td>
										<td><?php echo $product->format_price( $base_price, 'price' ); // phpcs:ignore ?></td>
										<?php
										if ( ! DigiCommerce()->get_option( 'remove_taxes' ) ) {
											?>
											<td><?php echo $product->format_price( $vat_amount, 'vat' ); // phpcs:ignore ?></td>
											<?php
										}
										?>
										<td><?php echo $product->format_price( $base_price + $vat_amount, 'total' ); // phpcs:ignore ?></td>
									</tr>
									<?php
									// Show subscription info if needed
									if ( ! empty( $item['subscription_enabled'] ) ) {
										?>
										<tr>
											<td colspan="5" class="subscription-info">
												<?php
												$info_parts = array();

												// Add subscription period info
												$period         = ! empty( $item['subscription_period'] ) ? $item['subscription_period'] : 'month';
												$period_display = '';
												switch ( $period ) {
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
														$period_display = $period . 'ly';
												}
												$info_parts[] = sprintf(
													// translators: %s: subscription period
													esc_html__( 'Billed %s', 'digicommerce' ),
													$period_display
												);

												// Add signup fee info if exists
												if ( ! empty( $item['subscription_signup_fee'] ) && floatval( $item['subscription_signup_fee'] ) > 0 ) {
													$info_parts[] = sprintf(
														// translators: %s: signup fee
														esc_html__( 'One-time signup fee of %s', 'digicommerce' ),
														$product->format_price( $item['subscription_signup_fee'] )
													);
												}

												// Add free trial info if exists
												if ( ! empty( $item['subscription_free_trial'] ) &&
													! empty( $item['subscription_free_trial']['duration'] ) &&
													intval( $item['subscription_free_trial']['duration'] ) > 0 ) {
													$info_parts[] = sprintf(
														// translators: %1$d: free trial duration, %2$s: free trial period
														esc_html__( '%1$d %2$s free trial', 'digicommerce' ),
														intval( $item['subscription_free_trial']['duration'] ),
														esc_html( $item['subscription_free_trial']['period'] )
													);
												}

												echo implode( ' - ', $info_parts ); // phpcs:ignore
												?>
											</td>
										</tr>
										<?php
									}
								endforeach;

								// Calculate total with VAT
								$total_with_vat = $total_base_price + $total_vat;
								?>
							</tbody>
							<tfoot>
								<tr>
									<th colspan="2">
										<?php
										printf(
											// translators: %d: total items
											esc_html__( 'Total (%d items)', 'digicommerce' ),
											count( $order['items'] )
										);
										?>
									</th>
									<th><?php echo $product->format_price( $total_base_price, 'price' ); // phpcs:ignore ?></th>
									<?php
									if ( ! DigiCommerce()->get_option( 'remove_taxes' ) ) {
										?>
										<th><?php echo $product->format_price( $total_vat, 'vat' ); // phpcs:ignore ?></th>
										<?php
									}
									?>
									<th><?php echo $product->format_price( $total_with_vat, 'total' ); // phpcs:ignore ?></th>
								</tr>
								<?php if ( ! empty( $order['discount_amount'] ) && $order['discount_amount'] > 0 ) : ?>
								<tr>
									<th colspan="4">
										<?php
										printf(
											// translators: %1$s: discount code, %2$s: discount amount
											esc_html__( 'Coupon (%1$s%2$s)', 'digicommerce' ),
											esc_html( $order['discount_code'] ),
											'percentage' === $order['discount_type'] ? ' - ' . esc_attr( $order['discount_amount'] ) . '%' : ''
										);
										?>
									</th>
									<th>-<?php echo $product->format_price( $order['discount_amount'], 'discount' ); // phpcs:ignore ?></th>
								</tr>
								<tr>
									<th colspan="2"><?php esc_html_e( 'Final Total', 'digicommerce' ); ?></th>
									<th colspan="2"><?php echo $product->format_price( $total_base_price, 'price' ); // phpcs:ignore ?></th>
									<th><?php echo $product->format_price( $total_with_vat - $order['discount_amount'], 'total' ); // phpcs:ignore ?></th>
								</tr>
								<?php endif; ?>
							</tfoot>
						</table>
					</div>

					<div class="postbox order-billing">
						<div class="postbox-header">
							<h2 class="hndle"><?php esc_html_e( 'Billing Details', 'digicommerce' ); ?></h2>
						</div>
						<div class="inside">
							<div class="digi-billing-details">
								<p>
									<label for="billing_first_name"><?php esc_html_e( 'First Name:', 'digicommerce' ); ?></label>
									<input type="text" name="billing_first_name" value="<?php echo esc_attr( $billing['first_name'] ?? '' ); ?>" class="widefat">
								</p>
								<p>
									<label for="billing_last_name"><?php esc_html_e( 'Last Name:', 'digicommerce' ); ?></label>
									<input type="text" name="billing_last_name" value="<?php echo esc_attr( $billing['last_name'] ?? '' ); ?>" class="widefat">
								</p>
								<p>
									<label for="billing_email"><?php esc_html_e( 'Email:', 'digicommerce' ); ?></label>
									<input type="email" name="billing_email" value="<?php echo esc_attr( $billing['email'] ?? '' ); ?>" class="widefat">
								</p>
								<p>
									<label for="billing_phone"><?php esc_html_e( 'Phone:', 'digicommerce' ); ?></label>
									<input type="text" name="billing_phone" value="<?php echo esc_attr( $billing['phone'] ?? '' ); ?>" class="widefat">
								</p>
								<p>
									<label for="billing_company"><?php esc_html_e( 'Company:', 'digicommerce' ); ?></label>
									<input type="text" name="billing_company" value="<?php echo esc_attr( $billing['company'] ?? '' ); ?>" class="widefat">
								</p>
								<p>
									<label for="billing_vat_number"><?php esc_html_e( 'VAT Number:', 'digicommerce' ); ?></label>
									<input type="text" name="billing_vat_number" value="<?php echo esc_attr( $billing['vat_number'] ?? '' ); ?>" class="widefat">
								</p>
								<p>
									<label for="billing_address"><?php esc_html_e( 'Address:', 'digicommerce' ); ?></label>
									<input type="text" name="billing_address" value="<?php echo esc_attr( $billing['address'] ?? '' ); ?>" class="widefat">
								</p>
								<p>
									<label for="billing_city"><?php esc_html_e( 'City:', 'digicommerce' ); ?></label>
									<input type="text" name="billing_city" value="<?php echo esc_attr( $billing['city'] ?? '' ); ?>" class="widefat">
								</p>
								<p>
									<label for="billing_postcode"><?php esc_html_e( 'Postcode:', 'digicommerce' ); ?></label>
									<input type="text" name="billing_postcode" value="<?php echo esc_attr( $billing['postcode'] ?? '' ); ?>" class="widefat">
								</p>
								<p class="country-wrap">
									<label for="billing_country"><?php esc_html_e( 'Country:', 'digicommerce' ); ?></label>
									<select name="billing_country" class="widefat digicommerce__search">
										<option value=""><?php esc_html_e( 'Select your country', 'digicommerce' ); ?></option>
										<?php
										$countries = DigiCommerce()->get_countries();
										foreach ( $countries as $code => $country ) {
											printf(
												'<option value="%s" %s>%s</option>',
												esc_attr( $code ),
												selected( $billing['country'] ?? '', $code, false ),
												esc_html( $country['name'] )
											);
										}
										?>
									</select>
								</p>
							</div>
						</div>
					</div>

					<?php
					// Get subscription data if exists
					$subscription_data = $orders->get_order_subscription_data( $order_id );
					if ( $subscription_data ) :
						?>
						<div class="postbox related-subscription">
							<div class="postbox-header">
								<h2 class="hndle"><?php esc_html_e( 'Related Subscription', 'digicommerce' ); ?></h2>
							</div>
							<table class="widefat striped">
								<tr>
									<td><strong><?php esc_html_e( 'Subscription ID:', 'digicommerce' ); ?></strong></td>
									<td class="edit-order">
										<a href="<?php echo esc_url( admin_url( 'admin.php?page=digi-subscriptions&action=edit&id=' . $subscription_data['id'] ) ); ?>">
											#<?php echo esc_html( $subscription_data['subscription_number'] ); ?>
										</a>
										<a href="<?php echo esc_url( admin_url( 'admin.php?page=digi-subscriptions&action=edit&id=' . $subscription_data['id'] ) ); ?>" class="button">
											<?php esc_html_e( 'Edit Subscription', 'digicommerce' ); ?>
										</a>
									</td>
								</tr>
								<tr>
									<td><strong><?php esc_html_e( 'Status:', 'digicommerce' ); ?></strong></td>
									<td><span class="subscription-status <?php echo esc_attr( $subscription_data['status'] ); ?>"><?php echo esc_html( ucfirst( $subscription_data['status'] ) ); ?></span></td>
								</tr>
								<tr>
									<td><strong><?php esc_html_e( 'Next Payment:', 'digicommerce' ); ?></strong></td>
									<td><?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $subscription_data['next_payment'] ) ) ); ?></td>
								</tr>
								<tr>
									<td><strong><?php esc_html_e( 'Billing Period:', 'digicommerce' ); ?></strong></td>
									<td>
										<?php
										$billing_period = $subscription_data['billing_period'];
										if ( 'day' === $billing_period ) {
											esc_html_e( 'Daily', 'digicommerce' );
										} elseif ( 'week' === $billing_period ) {
											esc_html_e( 'Weekly', 'digicommerce' );
										} elseif ( 'month' === $billing_period ) {
											esc_html_e( 'Monthly', 'digicommerce' );
										} elseif ( 'year' === $billing_period ) {
											esc_html_e( 'Yearly', 'digicommerce' );
										}
										?>
									</td>
								</tr>
							</table>
						</div>
					<?php endif; ?>
				</div>
				
				<div id="postbox-container-1" class="postbox-container">
					<div id="submitdiv" class="postbox order-details">
						<div class="postbox-header">
							<h2 class="hndle"><?php esc_html_e( 'Order Details', 'digicommerce' ); ?></h2>
						</div>
						<div class="inside">
							<div id="misc-publishing-actions">
								<div class="misc-pub-section first">
									<?php esc_html_e( 'Order Status:', 'digicommerce' ); ?>
									<select name="order_status" class="widefat">
										<?php
										$statuses = array(
											'processing' => esc_html__( 'Processing', 'digicommerce' ),
											'completed'  => esc_html__( 'Completed', 'digicommerce' ),
											'cancelled'  => esc_html__( 'Cancelled', 'digicommerce' ),
											'refunded'   => esc_html__( 'Refunded', 'digicommerce' ),
										);

										foreach ( $statuses as $value => $label ) {
											printf(
												'<option value="%s" %s>%s</option>',
												esc_attr( $value ),
												selected( $order['status'], $value, false ),
												esc_html( $label )
											);
										}
										?>
									</select>
								</div>

								<div class="misc-pub-section">
									<?php esc_html_e( 'Order Number:', 'digicommerce' ); ?>
									<strong><?php echo esc_html( $order['order_number'] ); ?></strong>
								</div>

								<div class="misc-pub-section">
									<?php esc_html_e( 'Order Date:', 'digicommerce' ); ?>
									<strong><?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $order['date_created'] ) ) ); ?></strong>
								</div>

								<div class="misc-pub-section">
									<?php esc_html_e( 'Payment Method:', 'digicommerce' ); ?>
									<strong><?php echo esc_html( $order['payment_method'] ); ?></strong>
								</div>
								<?php if ( $subscription_data ) : ?>
									<div class="misc-pub-section">
										<?php esc_html_e( 'Subscription Status:', 'digicommerce' ); ?>
										<strong><?php echo esc_html( $subscription_data['status'] ); ?></strong>
									</div>
									<div class="misc-pub-section">
										<?php esc_html_e( 'Next Payment:', 'digicommerce' ); ?>
										<strong><?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $subscription_data['next_payment'] ) ) ); ?></strong>
									</div>
								<?php endif; ?>
							</div>
							<div id="major-publishing-actions">
								<div id="publishing-action">
									<span class="spinner"></span>
									<button type="submit" class="button button-primary" id="update-order-btn"><?php esc_html_e( 'Update Order', 'digicommerce' ); ?></button>
								</div>
								<div class="clear"></div>
							</div>
						</div>
					</div>

					<div class="postbox order-notes">
						<div class="postbox-header">
							<h2 class="hndle"><?php esc_html_e( 'Order Notes', 'digicommerce' ); ?></h2>
						</div>
						<div class="inside">
							<div class="order-notes">
								<div class="message">
									<p><?php esc_html_e( 'Add a note to this order that only you will see.', 'digicommerce' ); ?></p>
								</div>

								<?php if ( ! empty( $order['notes'] ) ) : ?>
									<div class="notes-list">
										<strong><?php esc_html_e( 'Your Notes:', 'digicommerce' ); ?></strong>
										<?php foreach ( $order['notes'] as $note ) : ?>
											<div class="note-entry">
												<div class="note-content"><?php echo esc_html( $note['content'] ); ?></div>
												<div class="note-meta">
													<strong>
														<?php
														printf(
															// translators: %1$s: author name, %2$s: note date
															esc_html__( 'By %1$s on %2$s', 'digicommerce' ),
															esc_html( $note['author'] ),
															esc_html( date_i18n( get_option( 'date_format' ), strtotime( $note['date'] ) ) )
														);
														?>
													</strong>
												</div>
											</div>
										<?php endforeach; ?>
									</div>
									<br>
								<?php endif; ?>
								
								<textarea name="order_note" class="widefat" rows="4"></textarea>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</form>
</div>