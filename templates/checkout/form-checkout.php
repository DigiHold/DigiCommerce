<?php
/**
 * Checkout form template
 */

defined( 'ABSPATH' ) || exit;

$product = DigiCommerce_Product::instance();

// Settings
$stripe_enabled     = DigiCommerce()->is_stripe_enabled();
$paypal_enabled     = DigiCommerce()->is_paypal_enabled();
$login              = DigiCommerce()->get_option( 'login_during_checkout' );
$recaptcha_enabled  = DigiCommerce()->get_option( 'recaptcha_site_key' );
$recaptcha_site_key = DigiCommerce()->get_option( 'recaptcha_site_key' );
$minimal_fields     = DigiCommerce()->get_option( 'minimal_fields' );
$minimal_style      = DigiCommerce()->get_option( 'minimal_style' );
$order_agreement    = DigiCommerce()->get_option( 'order_agreement', '' );

$cart_items = DigiCommerce_Checkout::instance()->get_cart_items();
$subtotal   = 0;

$countries = DigiCommerce()->get_countries();
$tax_rates = array();
if ( ! DigiCommerce()->get_option( 'remove_taxes' ) ) {
	foreach ( $countries as $code => $country ) {
		if ( $country['tax_rate'] > 0 ) {
			$tax_rates[ $code ] = array(
				// Format the rate as a string with exactly 3 decimal places
				'rate' => number_format( (float) $country['tax_rate'], 3, '.', '' ),
				'eu'   => ! empty( $country['eu'] ),
			);
		}
	}
}

$allowed_html = array(
	'span' => array(
		'class' => array(),
	),
);

if ( ! $minimal_style ) {
	$checkout_wrap     = 'digicommerce digicommerce-checkout max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12';
	$wrap_classes      = 'lg:grid lg:grid-cols-12 lg:gap-x-12';
	$checkout_classes  = 'lg:col-span-7';
	$box_classes       = 'bg-white shadow sm:rounded-lg';
	$box_inner_classes = 'px-4 py-5 sm:p-6';
} else {
	$checkout_wrap     = 'digicommerce digicommerce-checkout digicommerce-one-col max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-12';
	$wrap_classes      = 'flex flex-col';
	$checkout_classes  = 'flex flex-col';
	$box_classes       = 'box';
	$box_inner_classes = 'box-inner';
}
?>

<div class="<?php echo esc_attr( $checkout_wrap ); ?>">
	<?php do_action( 'digicommerce_before_checkout' ); ?>
	<?php
	if ( empty( $cart_items ) ) :
		DigiCommerce()->get_template( 'checkout/empty-cart.php', '' );
	else :
		?>
		<!-- Form Message -->
		<div id="checkout-message" class="hidden rounded-md p-4 mb-6 mt-4"></div>

		<div class="<?php echo esc_attr( $wrap_classes ); ?>">

			<!-- Checkout Form -->
			<div class="<?php echo esc_attr( $checkout_classes ); ?>">
				<?php if ( $login && ! is_user_logged_in() ) : ?>
					<!-- Login Form -->
					<div class="login-checkout-wrap bg-white shadow sm:rounded-lg mb-4 px-4 py-3 sm:p-6">
						<div class="flex justify-between gap-2">
							<p class=" text-dark-blue m-0 no-margin">
								<?php esc_html_e( 'Have an account ?', 'digicommerce' ); ?>
							</p>
							<a href="#" class="login-checkout-link"><?php esc_html_e( 'Click here to login', 'digicommerce' ); ?></a>
						</div>

						<form id="digicommerce-login-checkout" class="digi__form w-full flex-col hidden">
							<div class="flex flex-col pt-4">
								<div class="flex items-center gap-4">
									<div class="flex items-center gap-4 flex-1">
										<div class="flex-1 field relative">
											<input type="text" id="username" name="username" class="default-transition" required>
											<label for="username">
												<?php esc_html_e( 'Username or Email', 'digicommerce' ); ?>
											</label>
										</div>

										<div class="flex-1 field relative">
											<input type="password" id="password" name="password" class="default-transition" required>
											<label for="password">
												<?php esc_html_e( 'Password', 'digicommerce' ); ?>
											</label>

											<button type="button" class="pass__icon p-0 m-0 no-background default-transition">
												<svg class="w-6 h-6" data-hide fill="none" viewBox="0 0 24 24" stroke="currentColor">
													<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
													<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
												</svg>
												<svg class="w-6 h-6 hidden" data-show fill="none" viewBox="0 0 24 24" stroke="currentColor">
													<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
												</svg>
											</button>
										</div>
									</div>

									<button type="submit" class="digi__button flex items-center justify-center gap-2 bg-gold hover:bg-dark-blue text-dark-blue hover:text-gold py-4 px-6 rounded-md default-transition">
										<span class="text"><?php esc_html_e( 'Login', 'digicommerce' ); ?></span>
										<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
											<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
										</svg>
									</button>
								</div>

								<?php if ( $recaptcha_enabled ) : ?>
									<div class="g-recaptcha-branding flex items-center justify-end gap-2 mt-2">
										<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" width="10" height="10">
											<path d="m505.879 256h-113.631-31.723l28.652-28.653c-13.159-61.484-67.76-107.6-133.179-107.6-5.901 0-11.807.371-17.461 1.108v-114.117c5.781-.369 11.56-.614 17.461-.614 95.918 0 179.168 53.982 221.102 133.3l28.778-28.777v145.353z" fill="#495586" />
											<path d="m198.692 280.963c-3.813-2.582-4.914-7.749-2.334-11.559 2.461-3.813 7.621-4.921 11.434-2.46l27.673 18.075 56.934-74.396c2.707-3.567 7.994-4.305 11.562-1.477 3.687 2.706 4.423 7.994 1.598 11.559l-61.365 80.3c-2.58 3.689-7.622 4.672-11.435 2.214z" fill="#303c64" />
											<path d="m255.999 6.124v113.624 31.728l-28.651-28.652c-61.488 13.157-107.597 67.755-107.597 133.176 0 5.901.492 11.804 1.102 17.46h-114.114c-.373-5.778-.618-11.559-.618-17.46 0-95.918 54.108-179.168 133.422-221.104l-28.893-28.772z" fill="#69a7ff" />
											<path d="m352.284 352.286 80.422 80.423c-45.253 45.129-107.721 73.167-176.707 73.167-95.918 0-179.168-54.108-221.101-133.424l-28.777 28.898v-145.35h113.63 31.723l-28.651 28.651c13.158 61.487 67.758 107.6 133.177 107.6 37.627-.001 71.693-15.249 96.284-39.965z" fill="#ababab" />
										</svg>
										<div class="flex gap-2">
											<span class="text-[.6rem]"><?php esc_html_e( 'protected by', 'digicommerce' ); ?> <strong>reCAPTCHA</strong></span>
											<div class="flex gap-1 text-[.6rem]">
												<a href="https://www.google.com/intl/fr/policies/privacy/" target="_blank" class="text-dark-blue hover:text-gold"><?php esc_html_e( 'Confidentiality', 'digicommerce' ); ?></a><span aria-hidden="true" role="presentation"> - </span><a href="https://www.google.com/intl/fr/policies/terms/" target="_blank" class="text-dark-blue hover:text-gold"><?php esc_html_e( 'Terms', 'digicommerce' ); ?></a>
											</div>
										</div>
									</div>
								<?php endif; ?>

								<div id="login-message" class="message hidden"></div>

								<?php wp_nonce_field( 'digicommerce_login_checkout_nonce', 'digicommerce_login_checkout_nonce' ); ?>
							</div>
						</form>
					</div>
				<?php endif; ?>

				<form id="digicommerce-checkout-form" class="digi__form w-full m-0" data-tax-rates="<?php echo esc_attr( json_encode( $tax_rates ) ); ?>">
					<!-- Personal Information -->
					<div class="<?php echo esc_attr( $box_classes ); ?>">
						<div class="<?php echo esc_attr( $box_inner_classes ); ?>">
							<?php if ( ! $minimal_style ) : ?>
								<h3 class="text-xl leading-6 font-medium text-dark-blue pb-6 m-0">
									<?php esc_html_e( 'Personal Information', 'digicommerce' ); ?>
								</h3>
							<?php endif; ?>

							<div class="grid lg:grid-cols-2 gap-6">
								<div class="field relative col-span-6 lg:col-span-2">
									<input type="email" id="billing_email" name="billing_email" class="default-transition" value="<?php echo esc_attr( $user_data['email'] ?? '' ); ?>" required>
									<label class="flex justify-start gap-[.1rem]" for="billing_email">
										<?php esc_html_e( 'Email', 'digicommerce' ); ?>
										<span class="text-red-500">*</span>
									</label>
								</div>

								<div class="field relative col-span-6 lg:col-span-1">
									<input type="text" id="billing_first_name" name="billing_first_name" class="default-transition" value="<?php echo esc_attr( $user_data['first_name'] ?? '' ); ?>" required>
									<label class="flex justify-start gap-[.1rem]" for="billing_first_name">
										<?php esc_html_e( 'First name', 'digicommerce' ); ?>
										<span class="text-red-500">*</span>
									</label>
								</div>

								<div class="field relative col-span-6 lg:col-span-1">
									<input type="text" id="billing_last_name" name="billing_last_name" class="default-transition" value="<?php echo esc_attr( $user_data['last_name'] ?? '' ); ?>" required>
									<label class="flex justify-start gap-[.1rem]" for="billing_last_name">
										<?php esc_html_e( 'Last name', 'digicommerce' ); ?>
										<span class="text-red-500">*</span>
									</label>
								</div>

								<?php if ( ! $minimal_fields ) : ?>
									<div class="field relative col-span-6 lg:col-span-1">
										<input type="tel" id="billing_phone" name="billing_phone" class="default-transition" value="<?php echo esc_attr( $user_data['phone'] ?? '' ); ?>">
										<label for="billing_phone">
											<?php esc_html_e( 'Phone', 'digicommerce' ); ?>
										</label>
									</div>
								<?php endif; ?>

								<div class="field relative col-span-6 lg:col-span-1">
									<input type="text" id="billing_company" name="billing_company" class="default-transition" value="<?php echo esc_attr( $user_data['company'] ?? '' ); ?>">
									<label for="billing_company">
										<?php esc_html_e( 'Company name', 'digicommerce' ); ?>
									</label>
								</div>

								<div class="field relative col-span-6 lg:col-span-1">
									<select id="country" name="billing_country" class="shadow-sm focus:ring-dark-blue focus:border-dark-blue block w-full sm:text-sm border border-solid border-gray-300 rounded-md" required>
										<option value=""><?php esc_html_e( 'Select your country', 'digicommerce' ); ?></option>
										<?php
										$selected_country = $user_data['country'] ?? '';

										foreach ( $countries as $code => $country ) {
											printf(
												'<option value="%s" %s>%s</option>',
												esc_attr( $code ),
												selected( $code, $selected_country, false ),
												esc_html( $country['name'] )
											);
										}
										?>
									</select>
								</div>

								<?php if ( ! $minimal_fields ) : ?>
									<div class="field relative col-span-6 lg:col-span-1">
										<input type="text" id="billing_address" name="billing_address" class="default-transition" value="<?php echo esc_attr( $user_data['address'] ?? '' ); ?>" required>
										<label class="flex justify-start gap-[.1rem]" for="billing_address">
											<?php esc_html_e( 'Street address', 'digicommerce' ); ?>
											<span class="text-red-500">*</span>
										</label>
									</div>

									<div class="field relative col-span-6 lg:col-span-1">
										<input type="text" id="billing_postcode" name="billing_postcode" class="default-transition" value="<?php echo esc_attr( $user_data['postcode'] ?? '' ); ?>" required>
										<label class="flex justify-start gap-[.1rem]" for="billing_postcode">
											<?php esc_html_e( 'Postal code', 'digicommerce' ); ?>
											<span class="text-red-500">*</span>
										</label>
									</div>

									<div class="field relative col-span-6 lg:col-span-1">
										<input type="text" id="billing_city" name="billing_city" class="default-transition" value="<?php echo esc_attr( $user_data['city'] ?? '' ); ?>" required>
										<label class="flex justify-start gap-[.1rem]" for="billing_city">
											<?php esc_html_e( 'City', 'digicommerce' ); ?>
											<span class="text-red-500">*</span>
										</label>
									</div>
								<?php endif; ?>

								<!-- After the country field -->
								<div id="vat_number_field" class="field relative col-span-6 lg:col-span-2" style="display: none;">
									<div class="field relative">
										<input type="text" id="vat_number" name="billing_vat_number" class="default-transition" value="<?php echo esc_attr( $user_data['vat_number'] ?? '' ); ?>">
										<label class="flex items-center gap-[.1rem]" for="vat_number">
											<?php esc_html_e( 'VAT number', 'digicommerce' ); ?>
											<span class="text-sm text-gray-500"><?php esc_html_e( '(optional)', 'digicommerce' ); ?></span>
										</label>
									</div>
									<span class="text-sm text-gray-500" id="vat_number_description">
										⚠️ <?php esc_html_e( 'EU format: starts with your country code, no spaces.', 'digicommerce' ); ?>
									</span>
								</div>

								<?php do_action( 'digicommerce_checkout_before_payment' ); ?>

								<!-- Payment Methods -->
								<div class="col-span-6 lg:col-span-2">
									<?php
									if ( $stripe_enabled || $paypal_enabled ) {
										?>
										<div class="digicommerce-radio flex flex-col esm:flex-row">
											<?php
											if ( $stripe_enabled ) {
												?>
												<label for="payment_method_stripe" class="flex-1 flex items-center gap-4 m-0 p-0">
													<input type="radio" id="payment_method_stripe" class="w-5 h-5 text-dark-blue focus:ring-blue-500" name="payment_method" value="stripe" <?php echo ( $stripe_enabled ) ? ' checked' : ''; ?>>
													<span class="payment_method_name flex items-center gap-2 p-4 rounded-t-lg w-full cursor-pointer default-transition">
														<span class="radio-icon flex items-center w-6 h-6 border border-solid border-border rounded-full default-transition"></span>
														<span class="flex-1 flex items-center justify-between gap-2">
															<span><?php esc_html_e( 'Credit Card', 'digicommerce' ); ?></span>
															<span class="payment_method__icon flex">
																<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 120" width="95" height="18">
																	<g>
																		<rect y="0" width="200" height="120" rx="8" ry="8" style="fill: #25459a;" />
																		<path d="M100.2673,52.7878c-.0753,5.5719,5.2916,8.6814,9.3346,10.53,4.154,1.897,5.5492,3.1133,5.5334,4.8094-.0317,2.5962-3.3137,3.7419-6.3856,3.7865-5.359.0781-8.4744-1.3576-10.9518-2.4437l-1.9303,8.4768c2.4853,1.0749,7.0871,2.0123,11.8595,2.0532,11.2015,0,18.5304-5.1888,18.5701-13.2341.0436-10.2102-15.0503-10.7755-14.9472-15.3394.0357-1.3837,1.4428-2.8603,4.5266-3.236,1.526-.1897,5.7395-.3348,10.5158,1.7296l1.8748-8.2016c-2.5685-.8778-5.8703-1.7184-9.9807-1.7184-10.5435,0-17.9596,5.2594-18.0191,12.7878M146.2821,40.7067c-2.0453,0-3.7695,1.1196-4.5385,2.838l-16.0016,35.8527h11.1936l2.2276-5.7765h13.6788l1.2922,5.7765h9.8657l-8.6092-38.6907h-9.1086M147.8478,51.1586l3.2304,14.5285h-8.847l5.6166-14.5285M86.6955,40.7067l-8.8233,38.6907h10.6664l8.8193-38.6907h-10.6624M70.9159,40.7067l-11.1024,26.3344-4.4909-22.3917c-.5272-2.4995-2.6081-3.9427-4.919-3.9427h-18.1499l-.2537,1.1233c3.7259.7588,7.9592,1.9825,10.5237,3.2918,1.5696.7997,2.0175,1.499,2.5328,3.3997l8.5062,30.876h11.2728l17.2818-38.6907h-11.2015" style="fill: #fff;" />
																	</g>
																	<g>
																		<rect x="220" y="0" width="200" height="120" rx="8" ry="8" style="fill: #10427a;" />
																		<g>
																			<g>
																				<rect x="308.4445" y="26.7953" width="27.0118" height="49.9502" style="fill: #ff5f00;" />
																				<path d="M310.1596,51.7704c0-10.1489,4.6306-19.1505,11.748-24.9751-5.2309-4.2361-11.8338-6.7953-19.0369-6.7953-17.0646,0-30.8707,14.2084-30.8707,31.7704s13.806,31.7704,30.8707,31.7704c7.2032,0,13.806-2.5593,19.0369-6.7953-7.1174-5.7363-11.748-14.8262-11.748-24.9751Z" style="fill: #eb001b;" />
																				<path d="M371.9009,51.7704c0,17.562-13.8061,31.7704-30.8707,31.7704-7.2032,0-13.8061-2.5593-19.0369-6.7953,7.2032-5.8246,11.748-14.8262,11.748-24.9751s-4.6306-19.1505-11.748-24.9751c5.2309-4.2361,11.8338-6.7953,19.0369-6.7953,17.0646,0,30.8707,14.2967,30.8707,31.7704Z" style="fill: #f79e1b;" />
																			</g>
																			<path d="M286.5714,103.8235v-5.2941c0-2.0294-1.3333-3.3529-3.619-3.3529-1.1429,0-2.381.3529-3.2381,1.5-.6667-.9706-1.619-1.5-3.0476-1.5-.9524,0-1.9048.2647-2.6667,1.2353v-1.0588h-2v8.4706h2v-4.6765c0-1.5.8571-2.2059,2.1905-2.2059s2,.7941,2,2.2059v4.6765h2v-4.6765c0-1.5.9524-2.2059,2.1905-2.2059,1.3333,0,2,.7941,2,2.2059v4.6765h2.1905ZM316.1905,95.3529h-3.2381v-2.5588h-2v2.5588h-1.8095v1.6765h1.8095v3.8824c0,1.9412.8571,3.0882,3.1429,3.0882.8571,0,1.8095-.2647,2.4762-.6176l-.5714-1.5882c-.5714.3529-1.2381.4412-1.7143.4412-.9524,0-1.3333-.5294-1.3333-1.4118v-3.7941h3.2381v-1.6765h0ZM333.1429,95.1765c-1.1429,0-1.9048.5294-2.381,1.2353v-1.0588h-2v8.4706h2v-4.7647c0-1.4118.6667-2.2059,1.9048-2.2059.381,0,.8571.0882,1.2381.1765l.5714-1.7647c-.381-.0882-.9524-.0882-1.3333-.0882h0ZM307.5238,96.0588c-.9524-.6176-2.2857-.8824-3.7143-.8824-2.2857,0-3.8095,1.0588-3.8095,2.7353,0,1.4118,1.1429,2.2059,3.1429,2.4706l.9524.0882c1.0476.1765,1.619.4412,1.619.8824,0,.6176-.7619,1.0588-2.0952,1.0588s-2.381-.4412-3.0476-.8824l-.9524,1.4118c1.0476.7059,2.4762,1.0588,3.9048,1.0588,2.6667,0,4.1905-1.1471,4.1905-2.7353,0-1.5-1.2381-2.2941-3.1429-2.5588l-.9524-.0882c-.8571-.0882-1.5238-.2647-1.5238-.7941,0-.6177.6667-.9706,1.7143-.9706,1.1429,0,2.2857.4412,2.8571.7059l.8571-1.5h0ZM360.6667,95.1765c-1.1429,0-1.9048.5294-2.381,1.2353v-1.0588h-2v8.4706h2v-4.7647c0-1.4118.6667-2.2059,1.9048-2.2059.381,0,.8571.0882,1.2381.1765l.5714-1.7647c-.381-.0882-.9524-.0882-1.3333-.0882h0ZM335.1429,99.5882c0,2.5588,1.9048,4.4118,4.8571,4.4118,1.3333,0,2.2857-.2647,3.2381-.9706l-.9524-1.5c-.7619.5294-1.5238.7941-2.381.7941-1.619,0-2.7619-1.0588-2.7619-2.7353,0-1.5882,1.1429-2.6471,2.7619-2.7353.8571,0,1.619.2647,2.381.7941l.9524-1.5c-.9524-.7059-1.9048-.9706-3.2381-.9706-2.9524,0-4.8571,1.8529-4.8571,4.4118h0ZM353.619,99.5882v-4.2353h-2v1.0588c-.6667-.7941-1.619-1.2353-2.8571-1.2353-2.5714,0-4.5714,1.8529-4.5714,4.4118s2,4.4118,4.5714,4.4118c1.3333,0,2.2857-.4412,2.8571-1.2353v1.0588h2v-4.2353ZM346.2857,99.5882c0-1.5,1.0476-2.7353,2.7619-2.7353,1.619,0,2.7619,1.1471,2.7619,2.7353,0,1.5-1.1429,2.7353-2.7619,2.7353-1.7143-.0882-2.7619-1.2353-2.7619-2.7353h0ZM322.381,95.1765c-2.6667,0-4.5714,1.7647-4.5714,4.4118s1.9048,4.4118,4.6667,4.4118c1.3333,0,2.6667-.3529,3.7143-1.1471l-.9524-1.3235c-.7619.5294-1.7143.8824-2.6667.8824-1.2381,0-2.4762-.5294-2.7619-2.0294h6.7619v-.7059c.0952-2.7353-1.619-4.5-4.1905-4.5h0ZM322.381,96.7647c1.2381,0,2.0952.7059,2.2857,2.0294h-4.7619c.1905-1.1471,1.0476-2.0294,2.4762-2.0294h0ZM372,99.5882v-7.5882h-2v4.4118c-.6667-.7941-1.619-1.2353-2.8571-1.2353-2.5714,0-4.5714,1.8529-4.5714,4.4118s2,4.4118,4.5714,4.4118c1.3333,0,2.2857-.4412,2.8571-1.2353v1.0588h2v-4.2353ZM364.6667,99.5882c0-1.5,1.0476-2.7353,2.7619-2.7353,1.619,0,2.7619,1.1471,2.7619,2.7353,0,1.5-1.1429,2.7353-2.7619,2.7353-1.7143-.0882-2.7619-1.2353-2.7619-2.7353h0ZM297.8095,99.5882v-4.2353h-2v1.0588c-.6667-.7941-1.619-1.2353-2.8571-1.2353-2.5714,0-4.5714,1.8529-4.5714,4.4118s2,4.4118,4.5714,4.4118c1.3333,0,2.2857-.4412,2.8571-1.2353v1.0588h2v-4.2353ZM290.381,99.5882c0-1.5,1.0476-2.7353,2.7619-2.7353,1.619,0,2.7619,1.1471,2.7619,2.7353,0,1.5-1.1429,2.7353-2.7619,2.7353-1.7143-.0882-2.7619-1.2353-2.7619-2.7353Z" style="fill: #fff;" />
																		</g>
																	</g>
																	<g>
																		<rect x="440" y=".0521" width="200" height="120" rx="8" ry="8" style="fill: #13a8e0;" />
																		<g>
																			<path d="M458.412,43.0828l-3.8332-9.6586-3.8113,9.6586h7.6445ZM542.8572,39.2367c-.7696.483-1.6799.4991-2.7703.4991h-6.8035v-5.3814h6.8961c.976,0,1.9943.0453,2.6558.437.7265.353,1.176,1.1042,1.176,2.1419,0,1.0589-.4276,1.911-1.1541,2.3034ZM591.3929,43.0828l-3.8756-9.6586-3.8544,9.6586h7.73ZM500.9194,53.5372h-5.7413l-.0212-18.9752-8.1208,18.9752h-4.9173l-8.1421-18.992v18.992h-11.3908l-2.1519-5.404h-11.6608l-2.1739,5.404h-6.0827l10.029-24.2279h8.3208l9.5251,22.9388v-22.9388h9.1406l7.3293,16.4357,6.7329-16.4357h9.3244v24.2279h.0007ZM523.8021,53.5372h-18.7088v-24.2279h18.7088v5.0452h-13.1081v4.3671h12.7936v4.9663h-12.7936v4.8384h13.1081v5.0109h0ZM550.1809,35.8343c0,3.8628-2.4933,5.8586-3.9463,6.4578,1.2254.4823,2.2721,1.3344,2.7703,2.0403.7908,1.205.9272,2.2815.9272,4.4453v4.7595h-5.6488l-.0212-3.0553c0-1.4579.135-3.5545-.8841-4.72-.8184-.8521-2.0657-1.037-4.082-1.037h-6.012v8.8123h-5.6v-24.2279h12.8813c2.8622,0,4.971.0782,6.7816,1.1605,1.7717,1.0823,2.8339,2.6622,2.8339,5.3646ZM559.1435,53.5372h-5.7145v-24.2279h5.7145v24.2279ZM625.4389,53.5372h-7.9364l-10.6155-18.1845v18.1845h-11.4057l-2.1795-5.404h-11.6339l-2.1145,5.404h-6.5534c-2.7223,0-6.1689-.6226-8.1208-2.6797-1.9682-2.0571-2.9922-4.8435-2.9922-9.2493,0-3.5932.612-6.878,3.0191-9.4737,1.8106-1.9336,4.6459-2.8252,8.5053-2.8252h5.4219v5.1914h-5.3081c-2.0438,0-3.1979.3142-4.3095,1.4352-.9548,1.0202-1.6099,2.9487-1.6099,5.4881,0,2.5957.4989,4.4672,1.5399,5.6898.8622.9588,2.429,1.2496,3.9032,1.2496h2.5152l7.8933-19.0533h8.3915l9.482,22.9162v-22.9162h8.5272l9.8445,16.8735v-16.8735h5.7364v24.2272ZM440.4509,58.2959h9.5696l2.1576-5.3814h4.8304l2.1519,5.3814h18.8276v-4.1142l1.6806,4.1318h9.7738l1.6806-4.1932v4.1756h46.7901l-.0219-8.8335h.9053c.6339.0227.8191.0833.8191,1.1656v7.668h24.2v-2.0564c1.9519,1.0815,4.988,2.0564,8.983,2.0564h10.1809l2.1788-5.3814h4.8304l2.1307,5.3814h19.6191v-5.1117l2.971,5.1117h15.7216V24.5052h-15.559v3.9907l-2.1788-3.9907h-15.9654v3.9907l-2.0007-3.9907h-21.5654c-3.6099,0-6.783.521-9.3463,1.9731v-1.9731h-14.882v1.9731c-1.6311-1.4966-3.8537-1.9731-6.3251-1.9731h-54.3696l-3.6481,8.7276-3.7463-8.7276h-17.1251v3.9907l-1.8813-3.9907h-14.6049l-6.7823,16.066v17.7248h0Z" style="fill: #fff;" />
																			<path d="M639.706,76.1669h-10.2071c-1.0191,0-1.6961.0395-2.2664.4377-.5908.3924-.8184.9748-.8184,1.7436,0,.9142.4989,1.5361,1.2247,1.805.5908.2127,1.2254.2748,2.1583.2748l3.0353.084c3.0629.0782,5.1074.6226,6.3541,1.9504.2269.1849.3633.3924.5194.6v-6.8955ZM639.706,92.1437c-1.3604,2.0571-4.0113,3.0999-7.6,3.0999h-10.8155v-5.1965h10.7717c1.0685,0,1.8163-.1454,2.2664-.6.3901-.3749.6622-.9193.6622-1.5807,0-.7059-.2721-1.2664-.6841-1.6026-.4064-.3698-.9979-.5378-1.9731-.5378-5.2587-.1849-11.8191.1681-11.8191-7.5006,0-3.515,2.1583-7.2149,8.0353-7.2149h11.1562v-4.8216h-10.3654c-3.1279,0-5.4.7746-7.0092,1.9789v-1.9789h-15.3307c-2.4516,0-5.3293.6285-6.6905,1.9789v-1.9789h-27.3767v1.9789c-2.1788-1.6252-5.8551-1.9789-7.5519-1.9789h-18.0579v1.9789c-1.7237-1.7261-5.5569-1.9789-7.8933-1.9789h-20.2099l-4.6247,5.1753-4.3314-5.1753h-30.1894v33.8142h29.6212l4.7654-5.2572,4.489,5.2572,18.2587.0168v-7.9544h1.7951c2.4226.0387,5.2799-.0621,7.8007-1.189v9.1258h15.0601v-8.8131h.7265c.9272,0,1.0184.0395,1.0184.9975v7.8148h45.7498c2.9046,0,5.9406-.7688,7.6219-2.1638v2.1638h14.5117c3.0198,0,5.9689-.4377,8.2127-1.5587v-6.2992ZM617.3661,82.4625c1.0905,1.167,1.6749,2.6403,1.6749,5.1344,0,5.2133-3.1498,7.6468-8.7979,7.6468h-10.9081v-5.1965h10.8643c1.0622,0,1.8156-.1454,2.2876-.6.3852-.3749.6615-.9193.6615-1.5807,0-.7059-.2989-1.2664-.6834-1.6026-.4283-.3698-1.0191-.5378-1.9943-.5378-5.2375-.1849-11.7965.1681-11.7965-7.5006,0-3.515,2.1357-7.2149,8.0071-7.2149h11.2276v5.1578h-10.2735c-1.0184,0-1.6806.0395-2.2438.4377-.6134.3924-.841.9748-.841,1.7436,0,.9142.5208,1.5361,1.2254,1.805.5908.2127,1.2254.2748,2.1795.2748l3.0148.084c3.0403.0767,5.1272.6204,6.3958,1.949ZM566.8304,80.9658c-.7491.4597-1.6756.4991-2.7654.4991h-6.8035v-5.4428h6.8961c.9972,0,1.995.0219,2.6728.4377.7258.3924,1.1597,1.1429,1.1597,2.1799s-.4339,1.8722-1.1597,2.326ZM570.2127,83.9817c1.2466.4757,2.2657,1.3285,2.7435,2.0345.7908,1.1831.9053,2.2873.9279,4.4233v4.8041h-5.6226v-3.032c0-1.4579.1357-3.6166-.9053-4.7434-.8184-.8682-2.0657-1.0757-4.1088-1.0757h-5.9852v8.8511h-5.6276v-24.2338h12.93c2.8353,0,4.9003.1293,6.7385,1.1437,1.7675,1.1042,2.8792,2.6169,2.8792,5.3814-.0007,3.868-2.4954,5.8418-3.9696,6.4468ZM577.2876,71.0098h18.6919v5.0116h-13.1145v4.4058h12.7943v4.9444h-12.7943v4.8216l13.1145.0219v5.0284h-18.6919v-24.2338h0ZM539.5018,82.1935h-7.2375v-6.1713h7.3025c2.0219,0,3.4254.8521,3.4254,2.9713,0,2.0958-1.3385,3.2-3.4905,3.2ZM526.6862,93.0396l-8.5986-9.8712,8.5986-9.5577v19.429ZM504.4806,90.1933h-13.7696v-4.8216h12.2954v-4.9444h-12.2954v-4.4058h14.041l6.1258,7.0622-6.3972,7.1096ZM549.0049,78.9935c0,6.7318-4.8572,8.1218-9.7526,8.1218h-6.988v8.1283h-10.8855l-6.8961-8.0224-7.1668,8.0224h-22.1837v-24.2338h22.5251l6.8905,7.9435,7.1237-7.9435h17.8954c4.4445,0,9.4382,1.2723,9.4382,7.9837Z" style="fill: #fff;" />
																		</g>
																	</g>
																</svg>
															</span>
														</span>
													</span>
												</label>
												<?php
											}

											if ( $paypal_enabled ) {
												?>
												<label for="payment_method_paypal" class="flex-1 flex items-center gap-4 m-0 p-0">
													<input type="radio" id="payment_method_paypal" class="w-5 h-5 text-dark-blue focus:ring-blue-500" name="payment_method" value="paypal" <?php echo ( ! $stripe_enabled && $paypal_enabled ) ? ' checked' : ''; ?>>
													<span class="payment_method_name flex items-center gap-2 p-4 rounded-t-lg w-full cursor-pointer default-transition">
														<span class="radio-icon flex items-center w-6 h-6 border border-solid border-border rounded-full default-transition"></span>
														<span class="flex-1 flex items-center justify-between gap-2">
															<span><?php esc_html_e( 'PayPal', 'digicommerce' ); ?></span>
															<span class="payment_method__icon flex">
																<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 114 31" width="80" height="23">
																	<path d="M42.65 6.52h-6.28a.87.87 0 00-.86.73l-2.53 16.09a.52.52 0 00.51.6h3a.87.87 0 00.86-.74l.68-4.33a.87.87 0 01.86-.74h1.99c4.13 0 6.52-2 7.14-5.96.28-1.74.01-3.1-.8-4.05-.9-1.05-2.47-1.6-4.57-1.6zm.72 5.87c-.34 2.25-2.06 2.25-3.72 2.25h-.95l.66-4.2a.52.52 0 01.52-.44h.43c1.14 0 2.2 0 2.76.64.33.39.43.96.3 1.75zm18.03-.07h-3a.52.52 0 00-.52.44l-.13.84-.21-.3c-.65-.95-2.1-1.26-3.55-1.26-3.32 0-6.16 2.51-6.7 6.04a5.67 5.67 0 001.11 4.61c.92 1.08 2.23 1.53 3.78 1.53 2.68 0 4.16-1.72 4.16-1.72l-.13.83a.52.52 0 00.51.61h2.7a.87.87 0 00.87-.74l1.62-10.28a.52.52 0 00-.51-.6zm-4.19 5.84a3.35 3.35 0 01-3.39 2.87c-.87 0-1.57-.28-2.01-.8a2.55 2.55 0 01-.48-2.12 3.36 3.36 0 013.37-2.89c.85 0 1.55.28 2 .82.46.54.64 1.3.51 2.12zm20.19-5.84h-3.02a.87.87 0 00-.72.38l-4.17 6.13-1.76-5.89a.87.87 0 00-.84-.62h-2.96a.52.52 0 00-.5.69l3.33 9.76-3.13 4.41a.52.52 0 00.43.83h3.01a.87.87 0 00.72-.38l10.04-14.5a.52.52 0 00-.43-.81z" fill="#253B80" />
																	<path d="M87.4 6.52h-6.28a.87.87 0 00-.86.73l-2.54 16.09a.52.52 0 00.52.6h3.22a.61.61 0 00.6-.51l.72-4.56a.87.87 0 01.86-.74h1.98c4.14 0 6.52-2 7.14-5.96.29-1.74.01-3.1-.8-4.05-.89-1.05-2.47-1.6-4.57-1.6zm.72 5.87c-.35 2.25-2.07 2.25-3.73 2.25h-.95l.67-4.2a.52.52 0 01.51-.44h.44c1.13 0 2.2 0 2.75.64.33.39.43.96.3 1.75zm18.02-.07h-3a.52.52 0 00-.52.44l-.13.84-.2-.3c-.66-.95-2.1-1.26-3.56-1.26-3.32 0-6.15 2.51-6.7 6.04a5.67 5.67 0 001.12 4.61c.91 1.08 2.22 1.53 3.78 1.53a5.75 5.75 0 004.16-1.72l-.14.84a.51.51 0 00.12.42.52.52 0 00.4.18h2.7a.87.87 0 00.87-.74l1.62-10.28a.51.51 0 00-.12-.42.54.54 0 00-.4-.18zm-4.19 5.85a3.35 3.35 0 01-3.38 2.86c-.87 0-1.57-.28-2.02-.8a2.56 2.56 0 01-.47-2.12 3.36 3.36 0 013.36-2.89c.86 0 1.55.29 2 .82.47.54.65 1.3.52 2.13zm7.73-11.21l-2.57 16.38a.51.51 0 00.12.42.52.52 0 00.4.18h2.58c.43 0 .8-.31.87-.74l2.53-16.08a.53.53 0 00-.3-.56.52.52 0 00-.21-.04h-2.9a.52.52 0 00-.52.44z" fill="#179BD7" />
																	<path d="M6.93 27.07l.48-3.05-1.07-.03h-5.1L4.77 1.51a.29.29 0 01.29-.25h8.6c2.86 0 4.83.6 5.86 1.77.48.55.79 1.13.94 1.76.15.66.16 1.46 0 2.42v.7l.48.27c.36.19.7.44.97.74.41.47.68 1.07.8 1.78.11.73.07 1.6-.12 2.58a9.09 9.09 0 01-1.05 2.92 6 6 0 01-3.93 2.85c-.83.22-1.78.33-2.82.33h-.67a2.03 2.03 0 00-2 1.7l-.04.27-.85 5.37-.04.2c0 .06-.03.1-.05.11a.14.14 0 01-.09.04H6.93z" fill="#253B80" />
																	<path d="M21.4 7.36l-.09.5c-1.13 5.83-5.01 7.84-9.97 7.84H8.82c-.61 0-1.12.44-1.22 1.04l-1.29 8.2-.37 2.32a.65.65 0 00.64.74h4.48c.53 0 .98-.38 1.06-.9l.05-.23.84-5.35.05-.3c.09-.52.54-.9 1.07-.9h.67c4.34 0 7.73-1.76 8.72-6.86.42-2.13.2-3.9-.9-5.16-.34-.38-.76-.7-1.22-.94z" fill="#179BD7" />
																	<path d="M20.21 6.88a14.02 14.02 0 00-3.33-.4h-6.74a1.07 1.07 0 00-1.06.9l-1.44 9.1-.04.26a1.23 1.23 0 011.21-1.04h2.53c4.96 0 8.84-2.01 9.97-7.84l.1-.5a6.05 6.05 0 00-1.2-.48z" fill="#222D65" />
																	<path d="M9.08 7.39a1.07 1.07 0 011.06-.91h6.75c.8 0 1.54.05 2.22.16.46.07.92.18 1.36.32.33.11.65.25.93.4.34-2.16 0-3.62-1.16-4.95C18.95.95 16.64.32 13.67.32h-8.6c-.6 0-1.12.44-1.22 1.04L.27 24.08a.74.74 0 00.73.85H6.3l1.33-8.46L9.08 7.4z" fill="#253B80" />
																</svg>
															</span>
														</span>
													</span>
												</label>
												<?php
											}
											?>
										</div>
										<?php
									}
									?>

									<div class="flex flex-col bg-light-blue-bg p-4 rounded-b-lg w-full">
										<?php
										if ( $stripe_enabled ) {
											?>
											<div class="digicommerce-stripe flex flex-col">
												<!-- Stripe Payment Method -->
												<input type="hidden" name="payment_method" value="stripe">
												<input type="hidden" name="payment_intent_id" id="payment_intent_id">

												<!-- Secure Payment Notice -->
												<div class="mb-6 flex flex-col gap-1">
													<p class="flex items-center gap-2 m-0 text-medium font-bold text-dark-blue no-margin">
														<svg class="flex-shrink-0 h-5 w-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
															<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
														</svg>
														<?php esc_html_e( 'Safe &amp; Secure payment', 'digicommerce' ); ?>
													</p>
													<p class="text-sm m-0 no-margin"><?php esc_html_e( 'Your payment will be processed by Stripe over a secure SSL connection and encrypted with AES-256.', 'digicommerce' ); ?></p>
												</div>

												<div>
													<div id="card-element" class="p-4 bg-white border border-solid border-gray-300 rounded-lg"></div>
													<div id="card-errors" class="hidden mt-2 text-sm text-red-600" role="alert"></div>
												</div>
											</div>
											<?php
										}

										if ( $paypal_enabled ) {
											?>
											<!-- PayPal Payment Method -->
											<div class="digicommerce-paypal<?php echo ( $stripe_enabled && $paypal_enabled ) ? ' hidden' : 'flex'; ?> flex-col">
												<input type="hidden" name="payment_method" value="paypal">

												<!-- Secure Payment Notice -->
												<div class="mb-6 flex flex-col gap-1">
													<p class="flex items-center gap-2 m-0 text-medium font-bold text-dark-blue no-margin">
														<svg class="flex-shrink-0 h-5 w-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
															<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
														</svg>
														<?php esc_html_e( 'Safe &amp; Secure payment', 'digicommerce' ); ?>
													</p>
													<p class="text-sm m-0 no-margin"><?php esc_html_e( 'Your payment will be processed securely through PayPal, ensuring a fast and hassle-free checkout experience with industry-leading encryption.', 'digicommerce' ); ?></p>
												</div>

												<div>
													<p class="text-sm text-gray-600 no-margin"><?php esc_html_e( 'You will be redirected to PayPal to complete your payment.', 'digicommerce' ); ?></p>
												</div>
											</div>
											<?php
										}

										if ( ! $stripe_enabled && ! $paypal_enabled ) {
											?>
											<!-- No Payment Methods Configured -->
											<div class="flex items-center gap-4">
												<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 63 48" width="40" height="30">
													<g clip-path="url(#clip0)">
														<path d="M55.4 46H22c-3.6 0-5.8-4-4-7l16.8-28c1.8-3 6.2-2.9 8 .1l16.5 28c1.9 3-.4 6.9-4 6.9z" fill="#FFE599"></path>
														<path d="M46 46H8.3c-3.6 0-5.8-4-4-7L23.4 7.5c1.8-3 6.1-3 8 0L50 39c1.9 3-.3 7-4 7z" fill="#ccb161"></path>
														<path fill-rule="evenodd" clip-rule="evenodd" d="M22 46h24c3.7 0 5.9-4 4-7L34 12.3 18 39c-1.8 3 .4 7 4 7z" fill="#09053a"></path>
														<rect width="15.5" height="4.1" rx="2.1" transform="matrix(0 -1 -1 0 28.4 32.6)" fill="#fff"></rect>
														<rect width="4.2" height="4.1" rx="2.1" transform="matrix(0 -1 -1 0 28.4 38.9)" fill="#fff"></rect>
													</g>
													<defs>
														<clipPath id="clip0">
															<path fill="#fff" d="M0 0h63v48H0z"></path>
														</clipPath>
													</defs>
												</svg>
												<p class="text-sm text-hover-blue flex-1 m-0">
													<?php esc_html_e( 'At least one payment method must be configured to proceed with the checkout.', 'digicommerce' ); ?>
												</p>
											</div>
											<?php
										}
										?>

										<?php if ( $minimal_style ) { ?>
											<!-- Order Items -->
											<div class="order-summary">
												<div class="border-0 border-b border-solid border-gray-200 pt-6 pb-4">
													<?php
													foreach ( $cart_items as $index => $item ) :
														$class = '';
														if ( ! empty( $item['variation_name'] ) ) {
															$class = ' has-variation-name';
														}

														// Check if product has subscription enabled
														$subscription_enabled = false;
														$subscription_period  = '';
														$free_trial           = array();
														$signup_fee           = 0;

														if ( class_exists( 'DigiCommerce_Pro' ) ) {
															if ( ! empty( $item['variation_name'] ) ) {  // Changed from variation_id to variation_name
																// Get variation subscription settings from price variations
																$price_variations = get_post_meta( $item['product_id'], 'digi_price_variations', true );

																if ( ! empty( $price_variations ) ) {
																	// Find the matching variation by comparing name only
																	foreach ( $price_variations as $variation ) {
																		if ( $variation['name'] === $item['variation_name'] ) {
																			$subscription_enabled = ! empty( $variation['subscription_enabled'] );
																			$subscription_period  = ! empty( $variation['subscription_period'] ) ? $variation['subscription_period'] : 'month';
																			$free_trial           = ! empty( $variation['subscription_free_trial'] ) ? $variation['subscription_free_trial'] : array(
																				'duration' => 0,
																				'period'   => 'days',
																			);
																			$signup_fee           = ! empty( $variation['subscription_signup_fee'] ) ? floatval( $variation['subscription_signup_fee'] ) : 0;
																			break;
																		}
																	}
																}
															} else {
																// Get regular product subscription settings
																$subscription_enabled = ! empty( $item['subscription_enabled'] );
																$subscription_period  = ! empty( $item['subscription_period'] ) ? $item['subscription_period'] : 'month';
																$free_trial           = ! empty( $item['subscription_free_trial'] ) ? $item['subscription_free_trial'] : array(
																	'duration' => 0,
																	'period'   => 'days',
																);
																$signup_fee           = ! empty( $item['subscription_signup_fee'] ) ? floatval( $item['subscription_signup_fee'] ) : 0;
															}
														}

														// Add to subtotal using signup fee if it's a subscription with signup fee, otherwise use regular price
														if ( $subscription_enabled && ! empty( $signup_fee ) && $signup_fee > 0 ) {
															$subtotal += $signup_fee;
														} else {
															$subtotal += $item['price'];
														}
														?>
														<div class="cart-item flex items-center justify-between gap-2 py-2 first:pt-0 last:pb-0" data-item-index="<?php echo esc_attr( $index ); ?>">
															<div class="flex-1">
																<div class="cart-item-name<?php echo esc_attr( $class ); ?> flex justify-between flex-col md:flex-row gap-1 text-medium font-bold text-dark-blue">
																	<div class="flex-1 flex flex-col gap-2">
																		<?php echo esc_html( $item['name'] ); ?>
																		<?php if ( $subscription_enabled ) : ?>
																			<div class="flex flex-col gap-1 text-sm font-normal text-gray-600">
																				<div class="flex items-center gap-2">
																					<?php
																					// Convert period to readable format
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
																						// translators: %s: subscription period
																						esc_html__( 'Billed %s until cancellation', 'digicommerce' ),
																						esc_html( $period_display )
																					);
																					?>
																				</div>

																				<?php
																				if ( ! empty( $signup_fee ) ) {
																					?>
																					<div class="flex items-center gap-1">
																						<?php
																						printf(
																							// translators: 1: signup fee, 2: recurring price
																							esc_html__( 'First payment of %1$s then %2$s', 'digicommerce' ),
																							wp_kses(
																								$product->format_price( $signup_fee, '' ),
																								$allowed_html
																							),
																							wp_kses(
																								$product->format_price( $item['price'], '' ),
																								$allowed_html
																							)
																						);
																						?>
																					</div>
																					<?php
																				}

																				if ( ! empty( $free_trial ) && ! empty( $free_trial['duration'] ) ) {
																					?>
																					<div class="flex items-center">
																						<?php
																						printf(
																							// translators: 1: free trial duration, 2: free trial period
																							esc_html__( '%1$d %2$s free trial', 'digicommerce' ),
																							esc_html( $free_trial['duration'] ),
																							esc_html( $free_trial['period'] )
																						);
																						?>
																					</div>
																					<?php
																				}
																				?>
																			</div>
																		<?php endif; ?>
																	</div>

																	<div class="cart-item-info flex flex-col items-start md:items-end text-sm font-normal">
																		<?php
																		// Display variation name if it's a variable product
																		if ( ! empty( $item['variation_name'] ) ) {
																			echo esc_html( $item['variation_name'] );
																		}

																		// Determine which price to display
																		$display_price = $item['price'];
																		if ( $subscription_enabled && ! empty( $signup_fee ) ) {
																			$display_price = $signup_fee;
																		}

																		echo wp_kses(
																			$product->format_price(
																				$display_price,
																				'product-price text-green-600'
																			),
																			$allowed_html
																		);
																		?>
																	</div>
																</div>
															</div>

															<?php if ( ! DigiCommerce()->get_option( 'remove_product' ) ) : ?>
																<button class="remove-item-btn flex items-center justify-center w-5 h-5 text-red-500 hover:text-red-400 rounded-full shadow-none p-0 m-0 no-background default-transition" data-index="<?php echo esc_attr( $index ); ?>">
																	<div class="icon flex"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512" fill="currentColor" width="11" height="12">
																			<path d="M135.2 17.7C140.6 6.8 151.7 0 163.8 0L284.2 0c12.1 0 23.2 6.8 28.6 17.7L320 32l96 0c17.7 0 32 14.3 32 32s-14.3 32-32 32L32 96C14.3 96 0 81.7 0 64S14.3 32 32 32l96 0 7.2-14.3zM32 128l384 0 0 320c0 35.3-28.7 64-64 64L96 512c-35.3 0-64-28.7-64-64l0-320zm96 64c-8.8 0-16 7.2-16 16l0 224c0 8.8 7.2 16 16 16s16-7.2 16-16l0-224c0-8.8-7.2-16-16-16zm96 0c-8.8 0-16 7.2-16 16l0 224c0 8.8 7.2 16 16 16s16-7.2 16-16l0-224c0-8.8-7.2-16-16-16zm96 0c-8.8 0-16 7.2-16 16l0 224c0 8.8 7.2 16 16 16s16-7.2 16-16l0-224c0-8.8-7.2-16-16-16z"></path>
																		</svg></div>
																	<span class="sr-only"><?php esc_html_e( 'Remove', 'digicommerce' ); ?></span>
																</button>
															<?php endif; ?>
														</div>
													<?php endforeach; ?>
												</div>

												<div class="flex flex-col gap-2 pt-4">

													<?php
													if ( ! DigiCommerce()->get_option( 'remove_taxes' ) ) {
														?>
														<!-- Subtotal -->
														<div class="flex justify-between">
															<div class="text-sm text-dark-blue">
																<?php esc_html_e( 'Subtotal', 'digicommerce' ); ?>
															</div>

															<div class="text-sm text-green-600">
																<span id="cart-subtotal">
																	<?php
																	echo wp_kses(
																		$product->format_price(
																			$subtotal,
																			'subtotal-price'
																		),
																		$allowed_html
																	);
																	?>
																</span>
															</div>
														</div>

														<?php
														$country_code = $user_data['country'] ?? '';
														$tax_rate     = 0;
														if ( ! empty( $country_code ) && isset( $countries[ $country_code ] ) ) {
															$tax_rate = $countries[ $country_code ]['tax_rate'];
														}
														$vat_amount = $tax_rate > 0 ? round( $subtotal * $tax_rate, 2 ) : 0;
														?>
														<!-- VAT -->
														<div id="vat_section" class="flex justify-between">
															<div class="text-sm text-dark-blue">
																<?php esc_html_e( 'VAT', 'digicommerce' ); ?>
																<span id="vat_rate"><?php echo esc_html( '(' . ( $tax_rate * 100 ) . '%)' ); ?></span>
															</div>

															<div class="text-sm text-green-600">
																<span id="cart-vat">
																	<?php
																	echo wp_kses(
																		$product->format_price(
																			$vat_amount,
																			'vat-price'
																		),
																		$allowed_html
																	);
																	?>
																</span>
															</div>
														</div>
														<?php
													} else {
														$vat_amount = 0;
													}
													?>

													<!-- Total -->
													<div class="flex justify-between">
														<div class="text-base font-bold text-dark-blue">
															<?php esc_html_e( 'Total', 'digicommerce' ); ?>
														</div>

														<div class="text-base font-bold text-green-600">
															<?php
															$total = apply_filters(
																'digicommerce_calculate_total',
																$subtotal + $vat_amount,
																array(
																	'subtotal' => $subtotal,
																	'vat' => $vat_amount,
																	'cart_items' => $cart_items,
																)
															);
															?>
															<span id="cart-total" data-current-total="<?php echo esc_attr( $total ); ?>">
																<?php
																echo wp_kses(
																	$product->format_price(
																		$total,
																		'total-price'
																	),
																	$allowed_html
																);
																?>
															</span>
														</div>
													</div>

													<?php do_action( 'digicommerce_after_checkout_total' ); ?>
												</div>
											</div>
										<?php } ?>
									</div>
								</div>
							</div>
						</div>
					</div>

					<?php wp_nonce_field( 'digicommerce_process_checkout', 'checkout_nonce' ); ?>

					<!-- Submit Button -->
					<div class="pt-6">
						<div class="flex flex-col items-center gap-4">
							<?php
							// If comes from abandoned cart
							if ( isset( $_GET['from_abandoned'] ) && DigiCommerce()->get_option( 'email_abandoned_cart' ) ) :
								?>
								<input type="hidden" name="from_abandoned" value="1">
								<?php
							endif;

							if ( $stripe_enabled || $paypal_enabled ) {
								?>
								<input type="hidden" id="calculated_vat" name="calculated_vat" value="0">
								<input type="hidden" id="calculated_total" name="calculated_total" value="0">

								<button type="submit" class="digi__button digicommerce-checkout-button w-full flex items-center justify-center gap-2 bg-gold hover:bg-dark-blue text-dark-blue hover:text-gold py-4 px-6 rounded-md default-transition">
									<span class="text"><?php esc_html_e( 'Complete Purchase', 'digicommerce' ); ?></span>
									<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
										<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
									</svg>
								</button>

								<?php
								if ( $paypal_enabled ) {
									?>
									<!-- PayPal Button Container -->
									<div id="paypal-button-container" class="w-full hidden"></div>
									<?php
								}
								?>

								<?php if ( ! empty( $order_agreement ) ) : ?>
									<div class="text-[.68rem] text-center max-w-xl text-gray-600">
										<?php echo wp_kses_post( $order_agreement ); ?>
									</div>
									<?php
							endif;
							}
							?>
						</div>
					</div>
				</form>
			</div>

			<?php if ( ! $minimal_style ) { ?>
				<!-- Order Summary -->
				<div class="lg:col-span-5 pt-8 lg:pt-0">
					<div class="bg-white shadow sm:rounded-lg sticky top-8">
						<div class="px-4 py-5 sm:p-6">
							<h3 class="text-xl leading-6 font-medium text-dark-blue pb-6 m-0">
								<?php esc_html_e( 'Order Summary', 'digicommerce' ); ?>
							</h3>

							<div class="space-y-4">
								<!-- Order Items -->
								<div class="border-0 border-t border-b border-solid border-gray-200 py-4">
									<?php
									foreach ( $cart_items as $index => $item ) :
										$class = '';
										if ( ! empty( $item['variation_name'] ) ) {
											$class = ' has-variation-name';
										}

										// Check if product has subscription enabled
										$subscription_enabled = false;
										$subscription_period  = '';
										$free_trial           = array();
										$signup_fee           = 0;

										if ( class_exists( 'DigiCommerce_Pro' ) ) {
											if ( ! empty( $item['variation_name'] ) ) { // Changed from variation_id to variation_name
												// Get variation subscription settings from price variations
												$price_variations = get_post_meta( $item['product_id'], 'digi_price_variations', true );

												if ( ! empty( $price_variations ) ) {
													// Find the matching variation by comparing name only
													foreach ( $price_variations as $variation ) {
														if ( $variation['name'] === $item['variation_name'] ) {
															$subscription_enabled = ! empty( $variation['subscription_enabled'] );
															$subscription_period  = ! empty( $variation['subscription_period'] ) ? $variation['subscription_period'] : 'month';
															$free_trial           = ! empty( $variation['subscription_free_trial'] ) ? $variation['subscription_free_trial'] : array(
																'duration' => 0,
																'period'   => 'days',
															);
															$signup_fee           = ! empty( $variation['subscription_signup_fee'] ) ? floatval( $variation['subscription_signup_fee'] ) : 0;
															break;
														}
													}
												}
											} else {
												// Get regular product subscription settings
												$subscription_enabled = ! empty( $item['subscription_enabled'] );
												$subscription_period  = ! empty( $item['subscription_period'] ) ? $item['subscription_period'] : 'month';
												$free_trial           = ! empty( $item['subscription_free_trial'] ) ? $item['subscription_free_trial'] : array(
													'duration' => 0,
													'period'   => 'days',
												);
												$signup_fee           = ! empty( $item['subscription_signup_fee'] ) ? floatval( $item['subscription_signup_fee'] ) : 0;
											}
										}

										// Add to subtotal using signup fee if it's a subscription with signup fee, otherwise use regular price
										if ( $subscription_enabled && ! empty( $signup_fee ) && $signup_fee > 0 ) {
											$subtotal += $signup_fee;
										} else {
											$subtotal += $item['price'];
										}
										?>
										<div class="cart-item flex items-center justify-between gap-2 py-2 first:pt-0 last:pb-0" data-item-index="<?php echo esc_attr( $index ); ?>">
											<div class="flex-1">
												<div class="cart-item-name<?php echo esc_attr( $class ); ?> flex justify-between flex-col md:flex-row gap-1 text-medium font-bold text-dark-blue">
													<div class="flex-1 flex flex-col gap-2">
														<?php echo esc_html( $item['name'] ); ?>
														<?php if ( $subscription_enabled ) : ?>
															<div class="flex flex-col gap-1 text-sm font-normal text-gray-600">
																<div class="flex items-center gap-2">
																	<?php
																	// Convert period to readable format
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
																		// translators: %s: subscription period
																		esc_html__( 'Billed %s until cancellation', 'digicommerce' ),
																		esc_html( $period_display )
																	);
																	?>
																</div>

																<?php
																if ( ! empty( $signup_fee ) ) {
																	?>
																	<div class="flex items-center gap-1">
																		<?php
																		printf(
																			// translators: 1: signup fee, 2: recurring price
																			esc_html__( 'First payment of %1$s then %2$s', 'digicommerce' ),
																			wp_kses(
																				$product->format_price( $signup_fee, '' ),
																				$allowed_html
																			),
																			wp_kses(
																				$product->format_price( $item['price'], '' ),
																				$allowed_html
																			)
																		);
																		?>
																	</div>
																	<?php
																}

																if ( ! empty( $free_trial ) && ! empty( $free_trial['duration'] ) ) {
																	?>
																	<div class="flex items-center">
																		<?php
																		printf(
																			// translators: 1: free trial duration, 2: free trial period
																			esc_html__( '%1$d %2$s free trial', 'digicommerce' ),
																			esc_html( $free_trial['duration'] ),
																			esc_html( $free_trial['period'] )
																		);
																		?>
																	</div>
																	<?php
																}
																?>
															</div>
														<?php endif; ?>
													</div>

													<div class="cart-item-info flex flex-col items-start md:items-end text-sm font-normal">
														<?php
														// Display variation name if it's a variable product
														if ( ! empty( $item['variation_name'] ) ) {
															echo esc_html( $item['variation_name'] );
														}

														// Determine which price to display
														$display_price = $item['price'];
														if ( $subscription_enabled && ! empty( $signup_fee ) ) {
															$display_price = $signup_fee;
														}

														echo wp_kses(
															$product->format_price(
																$display_price,
																'product-price text-green-600'
															),
															$allowed_html
														);
														?>
													</div>
												</div>
											</div>

											<?php if ( ! DigiCommerce()->get_option( 'remove_product' ) ) : ?>
												<button class="remove-item-btn flex items-center justify-center w-5 h-5 text-red-500 hover:text-red-400 rounded-full shadow-none p-0 m-0 no-background default-transition" data-index="<?php echo esc_attr( $index ); ?>">
													<div class="icon flex"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512" fill="currentColor" width="11" height="12">
															<path d="M135.2 17.7C140.6 6.8 151.7 0 163.8 0L284.2 0c12.1 0 23.2 6.8 28.6 17.7L320 32l96 0c17.7 0 32 14.3 32 32s-14.3 32-32 32L32 96C14.3 96 0 81.7 0 64S14.3 32 32 32l96 0 7.2-14.3zM32 128l384 0 0 320c0 35.3-28.7 64-64 64L96 512c-35.3 0-64-28.7-64-64l0-320zm96 64c-8.8 0-16 7.2-16 16l0 224c0 8.8 7.2 16 16 16s16-7.2 16-16l0-224c0-8.8-7.2-16-16-16zm96 0c-8.8 0-16 7.2-16 16l0 224c0 8.8 7.2 16 16 16s16-7.2 16-16l0-224c0-8.8-7.2-16-16-16zm96 0c-8.8 0-16 7.2-16 16l0 224c0 8.8 7.2 16 16 16s16-7.2 16-16l0-224c0-8.8-7.2-16-16-16z"></path>
														</svg></div>
													<span class="sr-only"><?php esc_html_e( 'Remove', 'digicommerce' ); ?></span>
												</button>
											<?php endif; ?>
										</div>
									<?php endforeach; ?>
								</div>

								<div class="flex flex-col gap-2">
									<?php
									if ( ! DigiCommerce()->get_option( 'remove_taxes' ) ) {
										?>
										<!-- Subtotal -->
										<div class="flex justify-between">
											<div class="text-sm text-dark-blue">
												<?php esc_html_e( 'Subtotal', 'digicommerce' ); ?>
											</div>

											<div class="text-sm text-green-600">
												<span id="cart-subtotal">
													<?php
													echo wp_kses(
														$product->format_price(
															$subtotal,
															'subtotal-price'
														),
														$allowed_html
													);
													?>
												</span>
											</div>
										</div>

										<?php
										$country_code = $user_data['country'] ?? '';
										$tax_rate     = 0;
										if ( ! empty( $country_code ) && isset( $countries[ $country_code ] ) ) {
											$tax_rate = $countries[ $country_code ]['tax_rate'];
										}
										$vat_amount = $tax_rate > 0 ? round( $subtotal * $tax_rate, 2 ) : 0;
										?>
										<!-- VAT -->
										<div id="vat_section" class="flex justify-between">
											<div class="text-sm text-dark-blue">
												<?php esc_html_e( 'VAT', 'digicommerce' ); ?>
												<span id="vat_rate"><?php echo esc_html( '(' . ( $tax_rate * 100 ) . '%)' ); ?></span>
											</div>

											<div class="text-sm text-green-600">
												<span id="cart-vat">
													<?php
													echo wp_kses(
														$product->format_price(
															$vat_amount,
															'vat-price'
														),
														$allowed_html
													);
													?>
												</span>
											</div>
										</div>
										<?php
									} else {
										$vat_amount = 0;
									}
									?>

									<!-- Total -->
									<div class="flex justify-between">
										<div class="text-base font-bold text-dark-blue">
											<?php esc_html_e( 'Total', 'digicommerce' ); ?>
										</div>

										<div class="text-base font-bold text-green-600">
											<?php
											$total = apply_filters(
												'digicommerce_calculate_total',
												$subtotal + $vat_amount,
												array(
													'subtotal' => $subtotal,
													'vat' => $vat_amount,
													'cart_items' => $cart_items,
												)
											);
											?>
											<span id="cart-total" data-current-total="<?php echo esc_attr( $total ); ?>">
												<?php
												echo wp_kses(
													$product->format_price(
														$total,
														'total-price'
													),
													$allowed_html
												);
												?>
											</span>
										</div>
									</div>

									<?php do_action( 'digicommerce_after_checkout_total' ); ?>
								</div>
							</div>
						</div>
					</div>
				</div>
			<?php } ?>
		</div>
	<?php endif; ?>
</div>

<?php if ( ! empty( $cart_items ) ) { ?>
	<!-- Loading Overlay -->
	<div id="loading-overlay" class="fixed inset-0 bg-gray-500 bg-opacity-75 items-center justify-center z-50 hidden">
		<div class="bg-white p-6 rounded-lg shadow-xl max-w-sm w-full mx-4">
			<div class="animate-spin rounded-full h-12 w-12 pb-4 border-t-0 border-l-0 border-r-0 border-b-2 border-solid border-dark-blue mx-auto"></div>
			<p class="text-center mt-4 mb-0 text-gray-700" id="loading-message">
				<?php esc_html_e( 'Processing your payment...', 'digicommerce' ); ?>
			</p>
		</div>
	</div>
<?php } ?>