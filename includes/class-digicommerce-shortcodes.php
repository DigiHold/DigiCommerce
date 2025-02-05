<?php
/**
 * Manages all shortcodes for the plugin
 */
class DigiCommerce_Shortcodes {
	private static $instance = null;

	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Initialize the class
	 */
	public function __construct() {
		add_shortcode( 'digicommerce_account', array( $this, 'render_account' ) );
		add_shortcode( 'digicommerce_reset_password', array( $this, 'render_reset_password' ) );
		add_shortcode( 'digicommerce_checkout', array( $this, 'render_checkout' ) );
		add_shortcode( 'digicommerce_payment_success', array( $this, 'render_payment_success' ) );
	}

	/**
	 * Render account page
	 *
	 * @param array $atts Shortcode attributes
	 * @return string
	 */
	public function render_account( $atts ) {
		ob_start();

		if ( is_user_logged_in() ) {
			$args = array(
				'user'         => wp_get_current_user(),
				'billing_info' => DigiCommerce()->get_billing_info(),
			);

			DigiCommerce()->get_template( 'account/my-account.php', $args );
		} else {
			$args = array(
				'recaptcha_enabled'  => ! empty( DigiCommerce()->get_option( 'recaptcha_site_key' ) ),
				'recaptcha_site_key' => DigiCommerce()->get_option( 'recaptcha_site_key' ),
				'register_text'      => DigiCommerce()->get_option( 'register_text' ),
				'login_text'         => DigiCommerce()->get_option( 'login_text' ),
			);

			DigiCommerce()->get_template( 'account/form-login.php', $args );
		}

		return ob_get_clean();
	}

	/**
	 * Render reset password page
	 *
	 * @param array $atts Shortcode attributes
	 * @return string
	 */
	public function render_reset_password( $atts ) {
		// Allow backend editing for users with the necessary capabilities
		if ( is_admin() && current_user_can( 'edit_pages' ) ) {
			return; // Allow WordPress to handle the page editing.
		}

		// Display message for logged-in admin users on the frontend
		if ( is_user_logged_in() && ! is_admin() && ! wp_doing_ajax() && current_user_can( 'manage_options' ) ) {
			ob_start();
			?>
			<div class="w-[375px] max-w-[90%] py-16 mdl:py-28 mx-auto">
				<div class="flex flex-col items-center">
					<?php esc_html_e( 'You cannot see the password reset form because you are already logged in', 'digicommerce' ); ?>
				</div>
			</div>
			<?php
			return ob_get_clean();
		}

		// Redirect all logged-in non-admin users if they are accessing the page on the frontend
		if ( is_user_logged_in() && ! is_admin() && ! wp_doing_ajax() ) {
			wp_safe_redirect( home_url() );
			exit();
		}

		ob_start();

		$login = isset( $_GET['login'] ) ? sanitize_user( $_GET['login'] ) : '';
		$key   = isset( $_GET['key'] ) ? sanitize_text_field( $_GET['key'] ) : '';

		// Verify reset key
		$user         = check_password_reset_key( $key, $login );
		$is_valid_key = ! is_wp_error( $user );

		$args = array(
			'login'              => $login,
			'key'                => $key,
			'is_valid_key'       => $is_valid_key,
			'recaptcha_enabled'  => ! empty( DigiCommerce()->get_option( 'recaptcha_site_key' ) ),
			'recaptcha_site_key' => DigiCommerce()->get_option( 'recaptcha_site_key' ),
		);

		DigiCommerce()->get_template( 'account/form-reset-password.php', $args );

		return ob_get_clean();
	}

	/**
	 * Render checkout page
	 *
	 * @param array $atts Shortcode attributes
	 * @return string
	 */
	public function render_checkout( $atts ) {
		ob_start();

		// Get user data if logged in
		$user_data = array();
		if ( is_user_logged_in() ) {
			$user_data = DigiCommerce()->get_billing_info();
		}

		$args = array(
			'user_data' => $user_data,
		);

		DigiCommerce()->get_template( 'checkout/form-checkout.php', $args );

		return ob_get_clean();
	}

	/**
	 * Render payment success page
	 *
	 * @param array $atts Shortcode attributes
	 * @return string
	 */
	public function render_payment_success( $atts ) {
		ob_start();

		// Get order ID and token from URL
		$order_id = isset( $_GET['order_id'] ) ? absint( $_GET['order_id'] ) : 0;
		$token    = isset( $_GET['token'] ) ? sanitize_text_field( $_GET['token'] ) : '';

		// Get the order data if order_id and token exist
		$order_data = ( $order_id && $token ) ? DigiCommerce_Orders::instance()->get_order( $order_id ) : array();

		// Prepare arguments for the template
		$args = array(
			'order_id'        => $order_id,
			'token'           => $token,
			'token_valid'     => DigiCommerce_Orders::instance()->verify_order_token( $order_id, $token ),
			'order_data'      => $order_data,
			'billing_info'    => DigiCommerce()->get_billing_info( $order_data['user_id'] ?? 0 ),
			'billing_details' => $order_data['billing_details'] ?? array(),
		);

		DigiCommerce()->get_template( 'checkout/payment-success.php', $args );

		return ob_get_clean();
	}
}