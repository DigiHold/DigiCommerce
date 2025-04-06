<?php
/**
 * Order cancelled email template
 *
 * @var int $order_id Order ID
 * @var array $billing_details Billing information
 */

defined( 'ABSPATH' ) || exit;

$order = DigiCommerce_Orders::instance()->get_order( $order_id ); // phpcs:ignore
if ( ! $order ) {
	return;
}

// Get product instance for price formatting
$product = DigiCommerce_Product::instance();

// Get countries for proper country name display
$countries = DigiCommerce()->get_countries();

// Billing details
$data                 = $order['billing_details'] ?? array();
$company              = ! empty( $data ) ? $data['company'] : '';
$first_name           = ! empty( $data ) ? $data['first_name'] : '';
$last_name            = ! empty( $data ) ? $data['last_name'] : '';
$billing_address      = ! empty( $data ) ? $data['address'] : '';
$billing_city         = ! empty( $data ) ? $data['city'] : '';
$billing_postcode     = ! empty( $data ) ? $data['postcode'] : '';
$vat_number           = ! empty( $data ) ? $data['vat_number'] : '';
$billing_country      = ! empty( $data ) ? $data['country'] : '';
$billing_country_name = isset( $countries[ $billing_country ] ) ? $countries[ $billing_country ]['name'] : $billing_country;
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
			esc_html__( 'Order %s Cancelled', 'digicommerce' ),
			esc_attr( $order['order_number'] )
		);
		?>
	</title>
	<style type="text/css">
		<?php echo wp_strip_all_tags( DigiCommerce_Emails::instance()->get_styles() ); // phpcs:ignore ?>
		.status-badge {
			display: inline-block;
			padding: 8px 16px;
			background-color: #B91C1C;
			color: white;
			font-weight: 500;
			font-size: 14px;
			border-radius: 9999px;
		}
		.cancellation-notice {
			margin: 20px 0;
			padding: 20px;
			background-color: #FEE2E2;
			border: 1px solid #B91C1C;
			border-radius: 8px;
			color: #7F1D1D;
		}
	</style>
</head>
<body>
	<div class="container">
		<?php echo wp_kses_post( DigiCommerce_Emails::instance()->get_header() ); ?>

		<div class="content">
			<h2><?php esc_html_e( 'Order Cancelled', 'digicommerce' ); ?></h2>
			
			<p>
				<?php
				printf(
					/* translators: %s: customer first name */
					esc_html__( 'Hi %s,', 'digicommerce' ),
					esc_html( $first_name )
				);
				?>
			</p>

			<div class="cancellation-notice">
				<p>
					<?php
					printf(
						/* translators: %s: order number */
						esc_html__( 'Your order %s has been cancelled.', 'digicommerce' ),
						esc_html( $order['order_number'] )
					);
					?>
				</p>
			</div>

			<div class="order-info">
				<h3><?php esc_html_e( 'Order Details', 'digicommerce' ); ?></h3>
				<p><strong><?php esc_html_e( 'Order Number:', 'digicommerce' ); ?></strong> <?php echo esc_html( $order['order_number'] ); ?></p>
				<p><strong><?php esc_html_e( 'Date:', 'digicommerce' ); ?></strong> <?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $order['date_created'] ) ) ); ?></p>
				<p><strong><?php esc_html_e( 'Email:', 'digicommerce' ); ?></strong> <?php echo esc_html( $data['email'] ); ?></p>
				<p class="status-badge"><?php esc_html_e( 'Cancelled', 'digicommerce' ); ?></p>
			</div>

			<table class="order-items">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Product', 'digicommerce' ); ?></th>
						<th><?php esc_html_e( 'Price', 'digicommerce' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $order['items'] as $item ) : ?>
						<tr>
							<td>
								<div class="inline-flex flex-col gap-2">
									<?php
									echo esc_html( $item['name'] );
									if ( ! empty( $item['variation_name'] ) ) {
										echo ' - ' . esc_html( $item['variation_name'] );
									}
									?>
								</div>
							</td>
							<td><?php echo wp_kses_post( $product->format_price( $item['price'] ) ); ?></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>

			<div class="order-total">
				<p>
					<strong><?php esc_html_e( 'Subtotal:', 'digicommerce' ); ?></strong>
					<?php echo wp_kses_post( $product->format_price( $order['subtotal'] ) ); ?>
				</p>
				<?php if ( ! empty( $order['vat'] ) ) : ?>
					<p>
						<strong>
							<?php
							printf(
								'%s (%s%%):',
								esc_html__( 'VAT', 'digicommerce' ),
								esc_html( rtrim( rtrim( number_format( $order['vat_rate'] * 100, 3 ), '0' ), '.' ) )
							);
							?>
						</strong>
						<?php echo wp_kses_post( $product->format_price( $order['vat'] ) ); ?>
					</p>
				<?php endif; ?>

				<?php if ( ! empty( $order['discount_code'] ) ) : ?>
					<p>
						<strong><?php esc_html_e( 'Discount:', 'digicommerce' ); ?></strong>
						-<?php echo wp_kses_post( $product->format_price( $order['discount_amount'] ) ); ?>
					</p>
				<?php endif; ?>

				<p>
					<strong><?php esc_html_e( 'Total:', 'digicommerce' ); ?></strong>
					<?php echo wp_kses_post( $product->format_price( $order['total'] ) ); ?>
				</p>
			</div>

			<div class="billing-info">
				<h3><?php esc_html_e( 'Billing Information', 'digicommerce' ); ?></h3>
				<p>
					<?php
					if ( ! empty( $company ) ) :
						echo esc_html( $company );
						?>
						<br>
						<?php
					endif;

					echo esc_html( $first_name . ' ' . $last_name );
					?>
					<br>
					
					<?php
					if ( ! empty( $billing_address ) ) :
						echo esc_html( $billing_address );
						?>
						<br>
						<?php
					endif;

					if ( ! empty( $billing_city ) && ! empty( $billing_postcode ) ) {
						echo esc_html(
							DigiCommerce_Orders::instance()->format_city_postal(
								$billing_city,
								$billing_postcode,
								$billing_country,
								$countries
							)
						);
						echo '<br>';
					}

					if ( ! empty( $billing_country_name ) ) {
						echo esc_html( $billing_country_name ) . '<br>';
					}

					if ( ! empty( $vat_number ) ) :
						esc_html_e( 'VAT: ', 'digicommerce' );
						?>
						<?php echo esc_html( $vat_number ); ?><br>
					<?php endif; ?>
				</p>
			</div>
		</div>

		<?php echo wp_kses_post( DigiCommerce_Emails::instance()->get_footer() ); ?>
	</div>
</body>
</html>