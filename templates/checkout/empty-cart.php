<?php
/**
 * Empty cart template template
 *
 * @package digicommerce
 */

defined( 'ABSPATH' ) || exit;

// Get settings with default values.
$empty_cart_title = DigiCommerce()->get_option(
	'empty_cart_title',
	esc_html__( 'Your cart is empty', 'digicommerce' )
);

$empty_cart_text = DigiCommerce()->get_option(
	'empty_cart_text',
	esc_html__( 'Looks like you haven\'t added any products to your cart yet.', 'digicommerce' )
);

$empty_cart_button_text = DigiCommerce()->get_option(
	'empty_cart_button_text',
	esc_html__( 'Browse Products', 'digicommerce' )
);

$empty_cart_button_url = DigiCommerce()->get_option(
	'empty_cart_button_url',
	get_home_url()
);
?>

<div class="text-center py-12">
	<div class="mb-8">
		<svg viewBox="0 0 100 100" width="96" height="96" class="mx-auto text-gray-400"><path fill="#4C4A4A" d="M4.545 0h90.152v77.273H4.623z"></path><path fill="#CDCACA" d="M0 18.75v75.547C0 97.969 2.969 100 6.64 100h86.72c3.671 0 6.64-2.031 6.64-5.703V18.75c0 .078-99.922 0-100 0M14.062 6.328 4.688 0v15.625h9.375V6.328zM95.312 0l-9.375 6.25v9.375h9.375z"></path><path fill="#958F8F" d="M14.108 18.94V6.817L0 18.94zM85.815 6.817V18.94H100L85.815 6.82zm-63.36 30.304c-2.33 0-4.273 1.894-4.273 4.167s1.864 4.167 4.273 4.167c2.331 0 4.274-1.894 4.274-4.167.077-2.273-1.865-4.167-4.274-4.167m52.057 0c-2.33 0-4.273 1.894-4.273 4.167s1.864 4.167 4.273 4.167c2.331 0 4.274-1.894 4.274-4.167.077-2.273-1.865-4.167-4.274-4.167"></path><path fill="#958F8F" d="M48.485 71.97c-15.494 0-28.03-13.651-28.03-30.482 0-1.163.856-2.094 1.946-2.094s1.947.93 1.947 2.094c0 14.504 10.823 26.216 24.137 26.216s24.137-11.79 24.137-26.216c0-1.163.857-2.094 1.947-2.094s1.946.93 1.946 2.094c0 16.83-12.613 30.482-28.03 30.482"></path><path fill="#FFF" d="M48.485 69.697c-15.494 0-28.03-12.698-28.03-28.356 0-1.09.856-1.947 1.946-1.947s1.947.857 1.947 1.947c0 13.477 10.823 24.461 24.137 24.461s24.137-10.984 24.137-24.46c0-1.091.857-1.948 1.947-1.948s1.946.857 1.946 1.947c0 15.658-12.613 28.356-28.03 28.356"></path></svg>
	</div>
	<h2 class="mt-2 text-2xl font-bold text-dark-blue">
		<?php echo wp_kses_post( $empty_cart_title ); ?>
	</h2>
	<p class="mt-2">
		<?php echo wp_kses_post( $empty_cart_text ); ?>
	</p>
	<div class="mt-6">
		<a href="<?php echo esc_url( $empty_cart_button_url ); ?>" class="inline-flex items-center px-6 py-3 border border-solid border-transparent text-base font-medium rounded-md shadow-sm no-underline text-dark-blue bg-gold hover:bg-dark-blue hover:text-gold default-transition">
			<?php echo wp_kses_post( $empty_cart_button_text ); ?>
		</a>
	</div>
</div>