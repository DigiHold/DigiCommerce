<?php
/**
 * My Account template
 *
 * This template can be overridden by copying it to yourtheme/digicommerce/account/my-account.php
 *
 * @version 1.0.0
 */

defined( 'ABSPATH' ) || exit;

$sections = apply_filters(
	'digicommerce_account_sections',
	array(
		'profile'  => array(
			'title' => esc_html__( 'My Profile', 'digicommerce' ),
			'icon'  => '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>',
		),
		'orders'   => array(
			'title' => esc_html__( 'Orders', 'digicommerce' ),
			'icon'  => '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" /></svg>',
		),
		'security' => array(
			'title' => esc_html__( 'Security', 'digicommerce' ),
			'icon'  => '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" /></svg>',
		),
	)
);

$active_section = isset( $_GET['section'] ) ? sanitize_key( $_GET['section'] ) : 'profile'; // phpcs:ignore
?>
<div class="digicommerce">
	<div class="lg:grid lg:grid-cols-12 lg:gap-x-5">
		<!-- Sidebar -->
		<aside class="pb-6 sm:px-2 lg:py-0 lg:col-span-3">
			<nav class="space-y-1">
				<?php foreach ( $sections as $key => $section ) : ?>
					<a href="<?php echo esc_url( add_query_arg( 'section', $key ) ); ?>" 
					class="<?php echo esc_attr( $active_section === $key ? 'bg-light-blue-bg text-dark-blue' : 'text-gray-800 hover:text-dark-blue hover:bg-light-blue-bg' ); ?> group rounded-md px-3 py-2 flex items-center text-sm font-medium transition-colors">
						<?php echo wp_kses( $section['icon'], DigiCommerce()->allowed_svg_el() ); ?>
						<span class="truncate ltr:ml-3 rtl:mr-3"><?php echo esc_html( $section['title'] ); ?></span>
					</a>
				<?php endforeach; ?>

				<div class="border-t border-solid border-gray-200 my-4"></div>
				
				<a href="<?php echo esc_url( wp_logout_url( home_url() ) ); ?>" 
				class="text-gray-800 hover:text-red-600 hover:bg-red-50 group rounded-md px-3 py-2 flex items-center text-sm font-medium transition-colors">
					<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
						<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
					</svg>
					<span class="ltr:ml-3 rtl:mr-3"><?php esc_html_e( 'Logout', 'digicommerce' ); ?></span>
				</a>
			</nav>
		</aside>

		<!-- Main content -->
		<div class="sm:px-6 lg:px-0 lg:col-span-9">
			<?php
			// Check for view-order parameter when in orders section
			if ( 'orders' === $active_section && isset( $_GET['view-order'] ) ) { // phpcs:ignore
				$order_id = absint( $_GET['view-order'] ); // phpcs:ignore
				if ( $order_id && DigiCommerce_Orders::instance()->verify_order_access( $order_id ) ) {
					DigiCommerce()->get_template(
						'account/sections/view-order.php',
						array(
							'order_data' => DigiCommerce_Orders::instance()->get_order( $order_id ),
							'billing_info' => $billing_info,
						)
					);
				} else {
					DigiCommerce()->get_template(
						'account/sections/orders.php',
						array(
							'user' => $user,
						)
					);
				}
			} else {
				// Handle other sections as before
				switch ( $active_section ) {
					case 'profile':
						DigiCommerce()->get_template(
							'account/sections/profile.php',
							array(
								'user'         => $user,
								'billing_info' => $billing_info,
							)
						);
						break;

					case 'orders':
						DigiCommerce()->get_template(
							'account/sections/orders.php',
							array(
								'user' => $user,
							)
						);
						break;

					case 'security':
						DigiCommerce()->get_template(
							'account/sections/security.php',
							array(
								'user' => $user,
							)
						);
						break;

					default:
						do_action( 'digicommerce_account_section_' . $active_section, $user );
						break;
				}
			}
			?>
		</div>
	</div>
</div>