<?php
/**
 * Orders section template for My Account page
 *
 * This template can be overridden by copying it to yourtheme/digicommerce/account/sections/orders.php
 *
 * @version 1.0.0
 */

defined( 'ABSPATH' ) || exit;

// Get user's orders
$orders = DigiCommerce_Orders::instance()->get_user_orders(
	get_current_user_id(),
	array(
		'status'  => array( 'processing', 'completed', 'cancelled', 'refunded' ),
		'orderby' => 'date_created',
		'order'   => 'DESC',
	)
);

$product = DigiCommerce_Product::instance();

$allowed_html = array(
	'span' => array(
		'class' => array(),
	),
);
?>

<!-- Orders Header -->
<div class="flex flex-col pb-6">
	<h2 class="text-[2rem] leading-normal font-bold text-dark-blue m-0 no-margin"><?php esc_html_e( 'My Orders', 'digicommerce' ); ?></h2>
	<p class="text-medium m-0 no-margin"><?php esc_html_e( 'View and manage your orders.', 'digicommerce' ); ?></p>
</div>

<?php if ( ! empty( $orders ) ) : ?>
	<div class="overflow-hidden border border-solid border-gray-200 rounded-lg">
		<table class="digicommerce-table">
			<thead class="bg-light-blue-bg">
				<tr>
					<th scope="col" class="px-6 py-3 ltr:text-left rtl:text-right text-xs font-bold text-dark-blue"><?php esc_html_e( 'Order', 'digicommerce' ); ?></th>
					<th scope="col" class="px-6 py-3 ltr:text-left rtl:text-right text-xs font-bold text-dark-blue"><?php esc_html_e( 'Date', 'digicommerce' ); ?></th>
					<th scope="col" class="px-6 py-3 ltr:text-left rtl:text-right text-xs font-bold text-dark-blue"><?php esc_html_e( 'Status', 'digicommerce' ); ?></th>
					<th scope="col" class="px-6 py-3 ltr:text-left rtl:text-right text-xs font-bold text-dark-blue"><?php esc_html_e( 'Total', 'digicommerce' ); ?></th>
					<th scope="col" class="relative px-6 py-3"><span class="sr-only"><?php esc_html_e( 'View', 'digicommerce' ); ?></span></th>
				</tr>
			</thead>
			<tbody class="bg-white divide-y divide-gray-200">
				<?php
				foreach ( $orders as $order ) : // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
					switch ( $order['status'] ) {
						case 'completed':
							$status_class = 'bg-green-600 text-white';
							break;
						case 'processing':
							$status_class = 'bg-yellow text-[#8d752d]';
							break;
						case 'cancelled':
							$status_class = 'bg-red-600 text-white';
							break;
						case 'refunded':
							$status_class = 'bg-[#FFA500] text-white';
							break;
						default:
							$status_class = 'bg-dark-blue text-white';
					}
					?>
					<tr>
						<td class="px-6 py-4" data-label="<?php esc_html_e( 'Order', 'digicommerce' ); ?>">
							<?php
							// Link
							$order_link = add_query_arg(
								array(
									'section'    => 'orders',
									'view-order' => $order['order_id'],
								),
								get_permalink()
							);
							?>
							<a href="<?php echo esc_url( $order_link ); ?>" class="whitespace-nowrap text-medium font-bold text-dark-blue hover:text-gold default-transition">
								<?php echo esc_html( $order['order_number'] ); ?>
							</a>
						</td>
						<td class="px-6 py-4 whitespace-nowrap text-sm text-dark-blue" data-label="<?php esc_html_e( 'Date', 'digicommerce' ); ?>">
							<?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $order['date_created'] ) ) ); ?>
						</td>
						<td class="px-6 py-4 whitespace-nowrap" data-label="<?php esc_html_e( 'Status', 'digicommerce' ); ?>">
							<span class="inline-flex py-1 px-2 font-bold uppercase text-sm rounded <?php echo esc_attr( $status_class ); ?>">
								<?php echo esc_html( ucfirst( $order['status'] ) ); ?>
							</span>
						</td>
						<td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-green-600 flex justify-between" data-label="<?php esc_html_e( 'Total', 'digicommerce' ); ?>">
							<?php
							echo wp_kses(
								$product->format_price(
									$order['total'],
									'order-price'
								),
								$allowed_html
							);
							?>
						</td>
						<td class="px-6 py-4 whitespace-nowrap ltr:text-right rtl:text-left text-sm font-medium" data-label="<?php esc_html_e( 'Details', 'digicommerce' ); ?>">
							<?php
							// Link
							$details_link = add_query_arg(
								array(
									'section'    => 'orders',
									'view-order' => $order['order_id'],
								),
								get_permalink()
							);
							?>
							<a href="<?php echo esc_url( $details_link ); ?>" class="whitespace-nowrap text-medium font-bold text-dark-blue hover:text-gold default-transition">
								<?php esc_html_e( 'View details', 'digicommerce' ); ?>
							</a>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	</div>
<?php else : ?>
	<div class="bg-light-blue-bg p-6 text-center rounded-lg">
		<svg class="mx-auto h-12 w-12 text-dark-blue-20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" /></svg>
		<h3 class="mt-2 text-medium font-medium text-dark-blue"><?php esc_html_e( 'No orders found', 'digicommerce' ); ?></h3>
		<p class="mt-1 text-sm text-dark-blue/50"><?php esc_html_e( 'You haven\'t placed any orders yet.', 'digicommerce' ); ?></p>
	</div>
<?php endif; ?>