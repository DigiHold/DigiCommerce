<?php
/**
 * Admin new order notification email template
 *
 * @var int $order_id
 * @var array $order_data
 */

defined( 'ABSPATH' ) || exit;

// Get product instance for price formatting
$product = DigiCommerce_Product::instance();

// Get countries for proper country name display
$countries = DigiCommerce()->get_countries();

// Billing details
$data                 = $order_data['billing_details'] ?? array();
$company              = ! empty( $data ) ? $data['company'] : '';
$first_name           = ! empty( $data ) ? $data['first_name'] : '';
$last_name            = ! empty( $data ) ? $data['last_name'] : '';
$phone                = ! empty( $data ) ? $data['phone'] : '';
$billing_address      = ! empty( $data ) ? $data['address'] : '';
$billing_city         = ! empty( $data ) ? $data['city'] : '';
$billing_postcode     = ! empty( $data ) ? $data['postcode'] : '';
$vat_number           = ! empty( $data ) ? $data['vat_number'] : '';
$billing_country      = ! empty( $data ) ? $data['country'] : '';
$billing_country_name = isset( $countries[ $billing_country ] ) ? $countries[ $billing_country ]['name'] : $billing_country;

// Payment method
$payment_method = $order_data['payment_method'];
if ( 'stripe' === $payment_method ) {
	$payment_method = esc_html__( 'Credit Card', 'digicommerce' );
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>
		<?php
		printf(
			// translators: %s: order number
			esc_html__( 'New Order %s', 'digicommerce' ),
			esc_attr( $order_data['order_number'] )
		);
		?>
	</title>
	<style type="text/css">
		<?php echo wp_strip_all_tags( DigiCommerce_Emails::instance()->get_styles() ); // phpcs:ignore ?>
		.admin-note {
			margin: 20px 0;
			padding: 20px;
			background-color: #E0F2FE;
			border-radius: 8px;
			color: #0C4A6E;
		}
		.admin-note p {
			margin: 0;
		}
		.customer-info {
			margin: 20px 0;
			padding: 20px;
			background-color: #F3F4F6;
			border-radius: 8px;
		}
		.customer-info h3 {
			margin-top: 0;
		}
		.customer-info p:last-child {
			margin: 0;
		}
	</style>
</head>
<body>
	<div class="container">
		<?php echo wp_kses_post( DigiCommerce_Emails::instance()->get_header() ); ?>

		<div class="content">
			<h2><?php esc_html_e( 'New Order Received', 'digicommerce' ); ?></h2>

			<div class="admin-note">
				<p>
				<?php
				printf(
					/* translators: %s: order number */
					esc_html__( 'You have received a new order (%s). The order details are shown below for your reference:', 'digicommerce' ),
					esc_attr( $order_data['order_number'] )
				);
				?>
				</p>
			</div>

			<div class="order-info">
				<h3><?php esc_html_e( 'Order Details', 'digicommerce' ); ?></h3>
				<p><strong><?php esc_html_e( 'Order Number:', 'digicommerce' ); ?></strong> <?php echo esc_html( $order_data['order_number'] ); ?></p>
				<p><strong><?php esc_html_e( 'Date:', 'digicommerce' ); ?></strong> <?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $order_data['date_created'] ) ) ); ?></p>
				<p><strong><?php esc_html_e( 'Payment Method:', 'digicommerce' ); ?></strong> <?php echo esc_html( $payment_method ); ?></p>

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
						foreach ( $order_licenses as $license ) :
							?>
							<div class="license-info">
								<p><strong><?php echo esc_html( $license['product_name'] ); ?></strong></p>
								<p>
									<strong><?php esc_html_e( 'License Key:', 'digicommerce' ); ?></strong> 
									<span class="license-key"><?php echo esc_html( $license['license_key'] ); ?></span>
								</p>
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
									<p>
										<a href="<?php echo esc_url( $license_url ); ?>" style="color: #4f46e5; text-decoration: none;">
											<?php esc_html_e( 'Manage Your Licenses', 'digicommerce' ); ?> →
										</a>
									</p>
								<?php endif; ?>
							</div>
							<?php
						endforeach;
						?>
						<div style="margin-top: 15px;">
							<p style="color: #4b5563; font-size: 14px;">
								<?php esc_html_e( 'You can view and manage all your licenses from your account dashboard.', 'digicommerce' ); ?>
							</p>
						</div>
						<?php
					endif;
				endif;
				?>
			</div>

			<div class="customer-info">
				<h3><?php esc_html_e( 'Customer Information', 'digicommerce' ); ?></h3>
				<p><strong><?php esc_html_e( 'Name:', 'digicommerce' ); ?></strong> <?php echo esc_html( $first_name . ' ' . $last_name ); ?></p>
				<p><strong><?php esc_html_e( 'Email:', 'digicommerce' ); ?></strong> <?php echo esc_html( $order_data['billing_details']['email'] ); ?></p>
				<?php if ( ! empty( $phone ) ) : ?>
					<p><strong><?php esc_html_e( 'Phone:', 'digicommerce' ); ?></strong> <?php echo esc_html( $phone ); ?></p>
					<?php
				endif;
				// Check if user exists before showing profile link
				if ( ! empty( $order_data['user_id'] ) && get_user_by( 'id', $order_data['user_id'] ) ) :
					$user_edit_link = add_query_arg(
						array(
							'user_id' => $order_data['user_id'],
							'action'  => 'edit',
						),
						admin_url( 'user-edit.php' )
					);
					?>
					<p><strong><?php esc_html_e( 'Customer Account:', 'digicommerce' ); ?></strong> <a href="<?php echo esc_url( $user_edit_link ); ?>"><?php esc_html_e( 'View Customer Profile', 'digicommerce' ); ?></a></p>
				<?php endif; ?>
			</div>

			<table class="order-items">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Product', 'digicommerce' ); ?></th>
						<th><?php esc_html_e( 'Price', 'digicommerce' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $order_data['items'] as $item ) : ?>
						<tr>
							<td>
								<?php
								// Check if product exists and get its status
								if ( isset( $item['product_id'] ) && get_post_status( $item['product_id'] ) ) {
									$product_link = add_query_arg(
										array(
											'post'   => $item['product_id'],
											'action' => 'edit',
										),
										admin_url( 'post.php' )
									);
									printf(
										'<a href="%s">%s</a>',
										esc_url( $product_link ),
										esc_html( $item['name'] )
									);
								} else {
									echo esc_html( $item['name'] );
								}
								if ( ! empty( $item['variation_name'] ) ) {
									echo '<br><small>' . esc_html( $item['variation_name'] ) . '</small>';
								}
								?>
							</td>
							<td><?php echo wp_kses_post( $product->format_price( $item['price'], 'item' ) ); ?></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>

			<div class="order-total">
				<p>
					<strong><?php esc_html_e( 'Subtotal:', 'digicommerce' ); ?></strong>
					<?php echo wp_kses_post( $product->format_price( $order_data['subtotal'] ) ); ?>
				</p>
				<p>
					<strong>
						<?php
						printf(
							'%s (%s%%):',
							esc_html__( 'VAT', 'digicommerce' ),
							esc_html( rtrim( rtrim( number_format( $order_data['vat_rate'] * 100, 3 ), '0' ), '.' ) )
						);
						?>
					</strong>
					<?php echo wp_kses_post( $product->format_price( $order_data['vat'] ) ); ?>
				</p>
				<?php if ( ! empty( $order_data['discount_code'] ) ) : ?>
					<p>
						<strong><?php esc_html_e( 'Discount:', 'digicommerce' ); ?></strong>
						-<?php echo wp_kses_post( $product->format_price( $order_data['discount_amount'] ) ); ?>
					</p>
				<?php endif; ?>
				<p>
					<strong><?php esc_html_e( 'Total:', 'digicommerce' ); ?></strong>
					<?php echo wp_kses_post( $product->format_price( $order_data['total'] ) ); ?>
				</p>
			</div>

			<div class="billing-info">
				<h3><?php esc_html_e( 'Billing Address', 'digicommerce' ); ?></h3>
				<p>
					<?php echo esc_html( $first_name . ' ' . $last_name ); ?><br>

					<?php
					if ( ! empty( $company ) ) :
						echo esc_html( $company );
						?>
						<br>
						<?php
					endif;

					if ( ! empty( $address ) ) :
						echo esc_html( $address );
						?>
						<br>
						<?php
					endif;

					if ( ! empty( $city ) && ! empty( $postcode ) ) {
						echo esc_html(
							DigiCommerce_Orders::instance()->format_city_postal(
								$city,
								$postcode,
								$country,
								$countries
							)
						);
						echo '<br>';
					}

					if ( ! empty( $billing_country_name ) ) {
						echo esc_html( $billing_country_name ) . '<br>';
					}

					if ( ! empty( $phone ) ) :
						echo esc_html( $phone );
					endif;
					?>
				</p>
			</div>

			<div class="button-container">
				<?php
				// Check if order exists in admin
				$edit_link = add_query_arg(
					array(
						'action' => 'edit',
						'id'     => $order_data['id'],
					),
					admin_url( 'admin.php?page=digi-orders' )
				);

				// Verify the order exists and is accessible
				if ( DigiCommerce_Orders::instance()->get_order( $order_id ) ) :
					?>
					<a href="<?php echo esc_url( $edit_link ); ?>" class="button">
						<?php esc_html_e( 'View Order Details', 'digicommerce' ); ?>
					</a>
				<?php endif; ?>
			</div>
		</div>

		<?php echo wp_kses_post( DigiCommerce_Emails::instance()->get_footer() ); ?>
	</div>
</body>
</html>