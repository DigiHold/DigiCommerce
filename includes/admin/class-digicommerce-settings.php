<?php
defined( 'ABSPATH' ) || exit;

/**
 * Administration options management for DigiCommerce
 */
class DigiCommerce_Settings {
	/**
	 * Instance of the class
	 *
	 * @var DigiCommerce_Settings
	 */
	private static $instance = null;

	/**
	 * Get instance of the class
	 */
	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor: Initialize hooks
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'add_plugin_admin_menu' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'admin_init', array( $this, 'process_form_submission' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		add_filter( 'admin_footer_text', array( $this, 'footer_text' ), 99 );
		add_filter( 'update_footer', array( $this, 'update_footer' ), 99 );
		add_filter( 'language_attributes', array( $this, 'attribute_to_html' ) );
	}

	/**
	 * Initialize the administration page
	 */
	public function add_plugin_admin_menu() {
		// Add main menu
		add_menu_page(
			esc_html__( 'DigiCommerce', 'digicommerce' ),
			esc_html__( 'DigiCommerce', 'digicommerce' ),
			'manage_options',
			'digicommerce-settings',
			array( $this, 'render_settings_page' ),
			'data:image/svg+xml;base64,' . base64_encode( '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 512 512"><path d="M431.8096,405.658c7.805,0,14.8577,3.0646,19.9946,8.0013,5.291,5.125,8.2577,12.0753,8.2461,19.3185,0,7.5368-3.1537,14.3732-8.2461,19.31-5.303,5.1279-12.4966,8.0048-19.9946,7.9962-7.4913.0114-14.6783-2.8663-19.9697-7.9962-5.2996-5.1175-8.274-12.0661-8.2657-19.3099,0-7.5505,3.1626-14.3732,8.2657-19.3185,5.2926-5.1289,12.4784-8.008,19.9697-8.0013h0ZM161.2157,405.658c7.8033,0,14.8666,3.0646,19.9698,8.0013,5.3053,5.1176,8.2816,12.0708,8.2693,19.3185,0,7.5368-3.1572,14.3732-8.2693,19.31-5.2898,5.1323-12.4779,8.0105-19.9698,7.9962-7.489.013-14.6738-2.8652-19.9609-7.9962-5.3041-5.1148-8.2804-12.0649-8.2693-19.3099,0-7.5505,3.1572-14.3732,8.2693-19.3185,5.2881-5.1301,12.4718-8.0097,19.9609-8.0013h0ZM70.8173,51.7125l13.3742,54.3146H11.9984c-6.6265,0-11.9984,5.1967-11.9984,11.6071s5.3719,11.6071,11.9984,11.6071h168.0415c6.6251,0,11.9957,5.1955,11.9957,11.6045s-5.3707,11.6045-11.9957,11.6045h-84.4148l4.059,16.5034h-15.0427c-6.6265,0-11.9984,5.1967-11.9984,11.6071s5.3719,11.6071,11.9984,11.6071h107.8548c6.6265.0781,11.9329,5.3382,11.8521,11.7486-.0794,6.3002-5.3395,11.3888-11.8521,11.4656h-81.3891l4.2156,17.1315H19.4637c-6.6222,0-11.9904,5.1949-11.9904,11.6045s5.37,11.6045,11.9904,11.6045h189.6423c6.6265.0776,11.9334,5.3371,11.8532,11.7475-.0788,6.301-5.3398,11.3905-11.8532,11.4667h-82.3443l4.4522,18.0986c-10.6172,1.1959-20.169,5.8934-27.3728,12.8521-8.392,8.127-13.6001,19.3375-13.6001,31.6785s5.2099,23.5567,13.6001,31.675c8.4009,8.1322,19.9893,13.167,32.757,13.167h4.3081c-6.2068,2.5447-11.849,6.2217-16.6186,10.8302-9.8154,9.4669-15.3236,22.3293-15.3042,35.7377,0,13.9499,5.8449,26.5783,15.3042,35.7239,9.4539,9.1456,22.5169,14.7999,36.9281,14.7999,14.4201,0,27.4884-5.6543,36.9423-14.7999,9.4539-9.1456,15.2988-21.774,15.2988-35.7239.0189-13.4072-5.487-26.2688-15.2988-35.7377-4.7717-4.6085-10.4157-8.2854-16.624-10.8302h229.9554c-6.2038,2.5444-11.8426,6.2215-16.608,10.8302-9.4539,9.1456-15.3042,21.7826-15.3042,35.7377s5.8502,26.5783,15.3042,35.7239c9.4539,9.1456,22.5062,14.7999,36.9281,14.7999s27.4635-5.6543,36.9228-14.7999c9.4592-9.1456,15.3273-21.774,15.3273-35.7239s-5.8698-26.592-15.3273-35.7377c-4.7546-4.6126-10.3878-8.2904-16.5884-10.8302h17.122c6.5973,0,11.9815-5.1949,11.9815-11.6097s-5.3842-11.6097-11.9815-11.6097H136.5981c-6.1366,0-11.7236-2.4348-15.7897-6.3529-4.0501-3.9284-6.5777-9.3246-6.5777-15.2697,0-5.9365,2.5276-11.3327,6.5777-15.2594,4.0644-3.9181,9.6531-6.3632,15.7897-6.3632h291.9422c9.7154,0,18.714-3.1438,25.8822-8.5967,7.1309-5.4289,12.4173-13.1429,14.7456-22.3212l42.25-167.6998c.3924-1.1921.5887-2.4365.5816-3.6875,0-6.4097-5.3362-11.6097-11.9815-11.6097H108.8518l-16.7983-68.2231c-1.1326-5.4143-6.0524-9.3065-11.7627-9.3057H30.0631c-6.626-.0019-11.9991,5.1929-12.0011,11.6028v.0069c0,6.409,5.3707,11.6045,11.9957,11.6045h40.7595v-.0017Z" fill="currentColor" fill-rule="evenodd"/></svg>' ),
			50
		);

		// Add submenu for the options page
		add_submenu_page(
			'digicommerce-settings',
			esc_html__( 'Settings', 'digicommerce' ),
			esc_html__( 'Settings', 'digicommerce' ),
			'manage_options',
			'digicommerce-settings',
			array( $this, 'render_settings_page' )
		);
	}

	/**
	 * Register settings
	 */
	public function register_settings() {
		$text = array(
			'type'              => 'string',
			'sanitize_callback' => array( $this, 'sanitize_text_field_callback' ),
		);

		$email = array(
			'type'              => 'string',
			'sanitize_callback' => array( $this, 'sanitize_email_callback' ),
		);

		$absint = array(
			'type'              => 'integer',
			'sanitize_callback' => array( $this, 'sanitize_absint_callback' ),
		);

		$kses = array(
			'type'              => 'string',
			'sanitize_callback' => array( $this, 'sanitize_kses_callback' ),
		);

		$bool = array(
			'type'              => 'boolean',
			'sanitize_callback' => array( $this, 'sanitize_bool_callback' ),
		);

		$email_bool = array(
			'type'              => 'boolean',
			'sanitize_callback' => array( $this, 'sanitize_bool_callback' ),
			'default'           => 1,
		);

		$hex = array(
			'type'              => 'string',
			'sanitize_callback' => array( $this, 'sanitize_hex_callback' ),
		);

		$raw = array(
			'type'              => 'string',
			'sanitize_callback' => array( $this, 'sanitize_url_callback' ),
		);

		$social = array(
			'type'              => 'array',
			'sanitize_callback' => array( $this, 'sanitize_social_links' ),
		);

		$separator = array(
			'type'              => 'string',
			'sanitize_callback' => array( $this, 'sanitize_separator_callback' ),
		);

		// General Tab.
		register_setting( 'digicommerce_options', 'digicommerce_business_name', $text ); // phpcs:ignore
		register_setting( 'digicommerce_options', 'digicommerce_business_vat_number', $text ); // phpcs:ignore
		register_setting( 'digicommerce_options', 'digicommerce_business_country', $text ); // phpcs:ignore
		register_setting( 'digicommerce_options', 'digicommerce_business_address', $text ); // phpcs:ignore
		register_setting( 'digicommerce_options', 'digicommerce_business_address2', $text ); // phpcs:ignore
		register_setting( 'digicommerce_options', 'digicommerce_business_city', $text ); // phpcs:ignore
		register_setting( 'digicommerce_options', 'digicommerce_business_postal', $text ); // phpcs:ignore
		register_setting( 'digicommerce_options', 'digicommerce_business_logo', $absint ); // phpcs:ignore
		register_setting( 'digicommerce_options', 'digicommerce_currency', $text ); // phpcs:ignore
		register_setting( 'digicommerce_options', 'digicommerce_currency_position', $text ); // phpcs:ignore
		register_setting( 'digicommerce_options', 'digicommerce_thousand_sep', $separator ); // phpcs:ignore
		register_setting( 'digicommerce_options', 'digicommerce_decimal_sep', $separator ); // phpcs:ignore
		register_setting( 'digicommerce_options', 'digicommerce_num_decimals', $text ); // phpcs:ignore
		register_setting( 'digicommerce_options', 'digicommerce_block_admin', $bool ); // phpcs:ignore
		register_setting( 'digicommerce_options', 'digicommerce_redirect_login', $bool ); // phpcs:ignore
		register_setting( 'digicommerce_options', 'digicommerce_redirect_after_logout', $absint ); // phpcs:ignore
		register_setting( 'digicommerce_options', 'digicommerce_register_form', $bool ); // phpcs:ignore
		register_setting( 'digicommerce_options', 'digicommerce_register_text', $kses ); // phpcs:ignore
		register_setting( 'digicommerce_options', 'digicommerce_login_text', $kses ); // phpcs:ignore
		register_setting( 'digicommerce_options', 'digicommerce_invoices_footer', $kses ); // phpcs:ignore

		// Product Tab.
		register_setting( 'digicommerce_options', 'digicommerce_product_slug', $text ); // phpcs:ignore
		register_setting( 'digicommerce_options', 'digicommerce_product_cat_slug', $text ); // phpcs:ignore
		register_setting( 'digicommerce_options', 'digicommerce_product_tag_slug', $text ); // phpcs:ignore
		register_setting( 'digicommerce_options', 'digicommerce_product_cpt', $bool ); // phpcs:ignore

		// Pages Tab.
		register_setting( 'digicommerce_options', 'digicommerce_account_page_id', $absint ); // phpcs:ignore
		register_setting( 'digicommerce_options', 'digicommerce_reset_password_page_id', $absint ); // phpcs:ignore
		register_setting( 'digicommerce_options', 'digicommerce_checkout_page_id', $absint ); // phpcs:ignore
		register_setting( 'digicommerce_options', 'digicommerce_payment_success_page_id', $absint ); // phpcs:ignore

		// reCAPTCHA Tab.
		register_setting( 'digicommerce_options', 'digicommerce_recaptcha_site_key', $text ); // phpcs:ignore
		register_setting( 'digicommerce_options', 'digicommerce_recaptcha_secret_key', $text ); // phpcs:ignore

		// Payment Tab.
		register_setting( 'digicommerce_options', 'digicommerce_stripe_mode', $text ); // phpcs:ignore
		register_setting( 'digicommerce_options', 'digicommerce_stripe_test_publishable_key', $text ); // phpcs:ignore
		register_setting( 'digicommerce_options', 'digicommerce_stripe_test_secret_key', $text ); // phpcs:ignore
		register_setting( 'digicommerce_options', 'digicommerce_stripe_live_publishable_key', $text ); // phpcs:ignore
		register_setting( 'digicommerce_options', 'digicommerce_stripe_live_secret_key', $text ); // phpcs:ignore
		register_setting( 'digicommerce_options', 'digicommerce_stripe_webhook_secret', $text ); // phpcs:ignore
		register_setting( 'digicommerce_options', 'digicommerce_paypal_sandbox', $bool ); // phpcs:ignore
		register_setting( 'digicommerce_options', 'digicommerce_paypal_client_id', $text ); // phpcs:ignore
		register_setting( 'digicommerce_options', 'digicommerce_paypal_secret', $text ); // phpcs:ignore
		register_setting( 'digicommerce_options', 'digicommerce_paypal_webhook_id', $text ); // phpcs:ignore

		// Checkout Tab.
		register_setting( 'digicommerce_options', 'digicommerce_remove_taxes', $bool ); // phpcs:ignore
		register_setting( 'digicommerce_options', 'digicommerce_login_during_checkout', $bool ); // phpcs:ignore
		register_setting( 'digicommerce_options', 'digicommerce_minimal_style', $bool ); // phpcs:ignore
		register_setting( 'digicommerce_options', 'digicommerce_minimal_fields', $bool ); // phpcs:ignore
		register_setting( 'digicommerce_options', 'digicommerce_order_agreement', $kses ); // phpcs:ignore
		register_setting( 'digicommerce_options', 'digicommerce_modal_terms', $kses ); // phpcs:ignore
		register_setting( 'digicommerce_options', 'digicommerce_remove_product', $bool ); // phpcs:ignore

		// Empty Cart Tab.
		register_setting( 'digicommerce_options', 'digicommerce_empty_cart_title', $kses ); // phpcs:ignore
		register_setting( 'digicommerce_options', 'digicommerce_empty_cart_text', $kses ); // phpcs:ignore
		register_setting( 'digicommerce_options', 'digicommerce_empty_cart_button_text', $kses ); // phpcs:ignore
		register_setting( 'digicommerce_options', 'digicommerce_empty_cart_button_url', $raw ); // phpcs:ignore

		// Emails Tab.
		register_setting( 'digicommerce_options', 'digicommerce_email_from_name', $text ); // phpcs:ignore
		register_setting( 'digicommerce_options', 'digicommerce_email_from_address', $email ); // phpcs:ignore
		register_setting( 'digicommerce_options', 'digicommerce_email_header_logo', $absint ); // phpcs:ignore
		register_setting( 'digicommerce_options', 'digicommerce_email_header_logo_width', $text ); // phpcs:ignore
		register_setting( 'digicommerce_options', 'digicommerce_email_new_account', $email_bool ); // phpcs:ignore
		register_setting( 'digicommerce_options', 'digicommerce_email_order_confirmation', $email_bool ); // phpcs:ignore
		register_setting( 'digicommerce_options', 'digicommerce_email_order_cancelled', $email_bool ); // phpcs:ignore
		register_setting( 'digicommerce_options', 'digicommerce_email_order_refunded', $email_bool ); // phpcs:ignore
		register_setting( 'digicommerce_options', 'digicommerce_email_new_order_admin', $email_bool ); // phpcs:ignore
		register_setting( 'digicommerce_options', 'digicommerce_email_footer_text', $kses ); // phpcs:ignore
		register_setting( 'digicommerce_options', 'digicommerce_show_social_links_in_email', $bool ); // phpcs:ignore
		register_setting( 'digicommerce_options', 'digicommerce_social_links', $social ); // phpcs:ignore

		// Styling Tab.
		register_setting( 'digicommerce_options', 'digicommerce_disable_styling', $bool ); // phpcs:ignore
		register_setting( 'digicommerce_options', 'digicommerce_color_gold', $hex ); // phpcs:ignore
		register_setting( 'digicommerce_options', 'digicommerce_color_yellow', $hex ); // phpcs:ignore
		register_setting( 'digicommerce_options', 'digicommerce_color_border', $hex ); // phpcs:ignore
		register_setting( 'digicommerce_options', 'digicommerce_color_light_blue', $hex ); // phpcs:ignore
		register_setting( 'digicommerce_options', 'digicommerce_color_light_blue_bg', $hex ); // phpcs:ignore
		register_setting( 'digicommerce_options', 'digicommerce_color_dark_blue', $hex ); // phpcs:ignore
		register_setting( 'digicommerce_options', 'digicommerce_color_dark_blue_10', $hex ); // phpcs:ignore
		register_setting( 'digicommerce_options', 'digicommerce_color_dark_blue_20', $hex ); // phpcs:ignore
		register_setting( 'digicommerce_options', 'digicommerce_color_hover_blue', $hex ); // phpcs:ignore
		register_setting( 'digicommerce_options', 'digicommerce_color_grey', $hex ); // phpcs:ignore
		register_setting( 'digicommerce_options', 'digicommerce_color_dark_grey', $hex ); // phpcs:ignore
	}

	/**
	 * Sanitize text fields.
	 *
	 * @param string $input Text to sanitize.
	 * @return string
	 */
	public function sanitize_text_field_callback( $input ) {
		return sanitize_text_field( $input );
	}

	/**
	 * Sanitize email fields.
	 *
	 * @param string $input Email to sanitize.
	 * @return string
	 */
	public function sanitize_email_callback( $input ) {
		return sanitize_email( $input );
	}

	/**
	 * Sanitize integer fields.
	 *
	 * @param mixed $input Value to convert to integer.
	 * @return int
	 */
	public function sanitize_absint_callback( $input ) {
		return absint( $input );
	}

	/**
	 * Sanitize HTML content with allowed tags.
	 *
	 * @param string $input HTML content to sanitize.
	 * @return string
	 */
	public function sanitize_kses_callback( $input ) {
		return wp_kses_post( $input );
	}

	/**
	 * Sanitize boolean values.
	 *
	 * @param mixed $input Value to convert to boolean.
	 * @return int 0 or 1
	 */
	public function sanitize_bool_callback( $input ) {
		return isset( $input ) && 1 === (int) $input ? 1 : 0;
	}

	/**
	 * Sanitize URL fields.
	 *
	 * @param string $input URL to sanitize.
	 * @return string
	 */
	public function sanitize_url_callback( $input ) {
		return esc_url_raw( $input );
	}

	/**
	 * Sanitize hex color values.
	 *
	 * @param string $input Color hex code to sanitize.
	 * @return string
	 */
	public function sanitize_hex_callback( $input ) {
		return sanitize_hex_color( $input );
	}

	/**
	 * Sanitize separator fields (thousand_sep, decimal_sep).
	 *
	 * @param string $input Separator to sanitize.
	 * @return string
	 */
	public function sanitize_separator_callback( $input ) {
		$input = wp_unslash( $input );
		$input = trim( $input );
		return mb_substr( $input, 0, 1 );
	}

	/**
	 * Process form submission
	 */
	public function process_form_submission() {
		if ( ! isset( $_POST['digicommerce_save_settings'] ) ) {
			return;
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		if ( ! check_admin_referer( 'digicommerce_settings_nonce' ) ) {
			return;
		}

		$options = array(
			// General Tab.
			'business_name'               => sanitize_text_field( $_POST['business_name'] ), // phpcs:ignore
			'business_vat_number'         => sanitize_text_field( $_POST['business_vat_number'] ), // phpcs:ignore
			'business_country'            => sanitize_text_field( $_POST['business_country'] ), // phpcs:ignore
			'business_address'            => sanitize_text_field( $_POST['business_address'] ), // phpcs:ignore
			'business_address2'           => sanitize_text_field( $_POST['business_address2'] ), // phpcs:ignore
			'business_city'               => sanitize_text_field( $_POST['business_city'] ), // phpcs:ignore
			'business_postal'             => sanitize_text_field( $_POST['business_postal'] ), // phpcs:ignore
			'business_logo'               => absint( $_POST['business_logo'] ), // phpcs:ignore
			'currency'                    => sanitize_text_field( $_POST['currency'] ), // phpcs:ignore
			'currency_position'           => sanitize_text_field( $_POST['currency_position'] ), // phpcs:ignore
			'thousand_sep'                => $this->sanitize_separator_callback( $_POST['thousand_sep'] ?? '' ), // phpcs:ignore
			'decimal_sep'                 => $this->sanitize_separator_callback( $_POST['decimal_sep'] ?? '' ), // phpcs:ignore
			'num_decimals'                => absint( $_POST['num_decimals'] ), // phpcs:ignore
			'block_admin'                 => isset( $_POST['block_admin'] ) ? 1 : 0, // phpcs:ignore
			'redirect_login'              => isset( $_POST['redirect_login'] ) ? 1 : 0, // phpcs:ignore
			'redirect_after_logout'       => absint( $_POST['redirect_after_logout'] ), // phpcs:ignore
			'register_form'               => isset( $_POST['register_form'] ) ? 1 : 0, // phpcs:ignore
			'register_text'               => wp_kses_post( wp_unslash( $_POST['register_text'] ) ), // phpcs:ignore
			'login_text'                  => wp_kses_post( wp_unslash( $_POST['login_text'] ) ), // phpcs:ignore
			'invoices_footer'             => wp_kses_post( wp_unslash( $_POST['invoices_footer'] ) ), // phpcs:ignore

			// Product Tab.
			'product_slug'                => sanitize_text_field( $_POST['product_slug'] ), // phpcs:ignore
			'product_cat_slug'            => sanitize_text_field( $_POST['product_cat_slug'] ), // phpcs:ignore
			'product_tag_slug'            => sanitize_text_field( $_POST['product_tag_slug'] ), // phpcs:ignore
			'product_cpt'                 => isset( $_POST['product_cpt'] ) ? 1 : 0, // phpcs:ignore

			// Pages Tab.
			'account_page_id'             => absint( $_POST['account_page_id'] ), // phpcs:ignore
			'reset_password_page_id'      => absint( $_POST['reset_password_page_id'] ), // phpcs:ignore
			'checkout_page_id'            => absint( $_POST['checkout_page_id'] ), // phpcs:ignore
			'payment_success_page_id'     => absint( $_POST['payment_success_page_id'] ), // phpcs:ignore

			// reCAPTCHA Tab.
			'recaptcha_site_key'          => sanitize_text_field( $_POST['recaptcha_site_key'] ), // phpcs:ignore
			'recaptcha_secret_key'        => sanitize_text_field( $_POST['recaptcha_secret_key'] ), // phpcs:ignore

			// Payment Tab.
			'stripe_mode'                 => sanitize_text_field( $_POST['stripe_mode'] ), // phpcs:ignore
			'stripe_test_publishable_key' => sanitize_text_field( $_POST['stripe_test_publishable_key'] ), // phpcs:ignore
			'stripe_test_secret_key'      => sanitize_text_field( $_POST['stripe_test_secret_key'] ), // phpcs:ignore
			'stripe_live_publishable_key' => sanitize_text_field( $_POST['stripe_live_publishable_key'] ), // phpcs:ignore
			'stripe_live_secret_key'      => sanitize_text_field( $_POST['stripe_live_secret_key'] ), // phpcs:ignore
			'stripe_webhook_secret'       => sanitize_text_field( $_POST['stripe_webhook_secret'] ), // phpcs:ignore
			'paypal_sandbox'              => isset( $_POST['paypal_sandbox'] ) ? 1 : 0, // phpcs:ignore
			'paypal_client_id'            => sanitize_text_field( $_POST['paypal_client_id'] ), // phpcs:ignore
			'paypal_secret'               => sanitize_text_field( $_POST['paypal_secret'] ), // phpcs:ignore
			'paypal_webhook_id'           => sanitize_text_field( $_POST['paypal_webhook_id'] ), // phpcs:ignore

			// Checkout Tab.
			'remove_taxes'                => isset( $_POST['remove_taxes'] ) ? 1 : 0, // phpcs:ignore
			'login_during_checkout'       => isset( $_POST['login_during_checkout'] ) ? 1 : 0, // phpcs:ignore
			'minimal_style'               => isset( $_POST['minimal_style'] ) ? 1 : 0, // phpcs:ignore
			'minimal_fields'              => isset( $_POST['minimal_fields'] ) ? 1 : 0, // phpcs:ignore
			'order_agreement'             => wp_kses_post( wp_unslash( $_POST['order_agreement'] ) ), // phpcs:ignore
			'modal_terms'                 => wp_kses_post( wp_unslash( $_POST['modal_terms'] ) ), // phpcs:ignore
			'remove_product'              => isset( $_POST['remove_product'] ) ? 1 : 0, // phpcs:ignore

			// Empty Cart Tab.
			'empty_cart_title'            => wp_kses_post( wp_unslash( $_POST['empty_cart_title'] ) ), // phpcs:ignore
			'empty_cart_text'             => wp_kses_post( wp_unslash( $_POST['empty_cart_text'] ) ), // phpcs:ignore
			'empty_cart_button_text'      => wp_kses_post( wp_unslash( $_POST['empty_cart_button_text'] ) ), // phpcs:ignore
			'empty_cart_button_url'       => sanitize_text_field( $_POST['empty_cart_button_url'] ), // phpcs:ignore

			// Emails Tab.
			'email_from_name'             => sanitize_text_field( $_POST['email_from_name'] ), // phpcs:ignore
			'email_from_address'          => sanitize_email( $_POST['email_from_address'] ), // phpcs:ignore
			'email_header_logo'           => absint( $_POST['email_header_logo'] ), // phpcs:ignore
			'email_header_logo_width'     => sanitize_text_field( $_POST['email_header_logo_width'] ), // phpcs:ignore
			'email_new_account'           => isset( $_POST['email_new_account'] ) ? 1 : 0, // phpcs:ignore
			'email_order_confirmation'    => isset( $_POST['email_order_confirmation'] ) ? 1 : 0, // phpcs:ignore
			'email_order_cancelled'       => isset( $_POST['email_order_cancelled'] ) ? 1 : 0, // phpcs:ignore
			'email_order_refunded'        => isset( $_POST['email_order_refunded'] ) ? 1 : 0, // phpcs:ignore
			'email_new_order_admin'       => isset( $_POST['email_new_order_admin'] ) ? 1 : 0, // phpcs:ignore
			'email_footer_text'           => wp_kses_post( wp_unslash( $_POST['email_footer_text'] ) ), // phpcs:ignore
			'show_social_links_in_email'  => isset( $_POST['show_social_links_in_email'] ) ? 1 : 0, // phpcs:ignore
			'social_links'                => $this->sanitize_social_links( $_POST['social_links'] ?? array() ), // phpcs:ignore

			// Styling Tab.
			'disable_styling'             => isset( $_POST['disable_styling'] ) ? 1 : 0, // phpcs:ignore
			'color_gold'                  => sanitize_hex_color( $_POST['color_gold'] ?? '' ), // phpcs:ignore
			'color_yellow'                => sanitize_hex_color( $_POST['color_yellow'] ?? '' ), // phpcs:ignore
			'color_border'                => sanitize_hex_color( $_POST['color_border'] ?? '' ), // phpcs:ignore
			'color_light_blue'            => sanitize_hex_color( $_POST['color_light_blue'] ?? '' ), // phpcs:ignore
			'color_light_blue_bg'         => sanitize_hex_color( $_POST['color_light_blue_bg'] ?? '' ), // phpcs:ignore
			'color_dark_blue'             => sanitize_hex_color( $_POST['color_dark_blue'] ?? '' ), // phpcs:ignore
			'color_dark_blue_10'          => sanitize_hex_color( $_POST['color_dark_blue_10'] ?? '' ), // phpcs:ignore
			'color_dark_blue_20'          => sanitize_hex_color( $_POST['color_dark_blue_20'] ?? '' ), // phpcs:ignore
			'color_hover_blue'            => sanitize_hex_color( $_POST['color_hover_blue'] ?? '' ), // phpcs:ignore
			'color_grey'                  => sanitize_hex_color( $_POST['color_grey'] ?? '' ), // phpcs:ignore
			'color_dark_grey'             => sanitize_hex_color( $_POST['color_dark_grey'] ?? '' ), // phpcs:ignore

		);

		$options = apply_filters( 'digicommerce_process_settings_options', $options, $options );

		foreach ( $options as $key => $value ) {
			DigiCommerce()->set_option( $key, $value );
		}

		// Check if any slug settings have changed and flush rules if needed
		if ( isset( $_POST['product_slug'] ) || isset( $_POST['product_cat_slug'] ) || isset( $_POST['product_tag_slug'] ) ) {
			flush_rewrite_rules();
		}

		// Get the active tab from form submission
		$active_tab = isset( $_POST['active_tab'] ) ? sanitize_key( $_POST['active_tab'] ) : 'general';

		// Check if any slug settings have changed
		$old_product_slug = DigiCommerce()->get_option( 'product_slug' );
		$old_cat_slug     = DigiCommerce()->get_option( 'product_cat_slug' );
		$old_tag_slug     = DigiCommerce()->get_option( 'product_tag_slug' );

		// Redirect to the settings page with a success message
		wp_safe_redirect(
			add_query_arg(
				array(
					'page'             => 'digicommerce-settings',
					'tab'              => $active_tab,
					'settings-updated' => 'true',
				),
				admin_url( 'admin.php' )
			)
		);
		exit;
	}

	/**
	 * Sanitization method for social medias
	 *
	 * @param array $links Social links.
	 */
	public function sanitize_social_links( $links ) {
		if ( ! is_array( $links ) ) {
			return array();
		}

		return array_map(
			function ( $link ) {
				return array(
					'platform' => sanitize_text_field( $link['platform'] ),
					'url'      => esc_url_raw( $link['url'] ),
				);
			},
			$links
		);
	}

	/**
	 * Display settings page
	 */
	public function render_settings_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// Get current tab
		$current_tab = isset( $_GET['tab'] ) ? sanitize_key( $_GET['tab'] ) : 'general'; // phpcs:ignore

		// Define tabs
		$tabs = array(
			'general'   => esc_html__( 'General', 'digicommerce' ),
			'product'   => esc_html__( 'Product', 'digicommerce' ),
			'pages'     => esc_html__( 'Pages', 'digicommerce' ),
			'recaptcha' => esc_html__( 'reCAPTCHA', 'digicommerce' ),
			'payment'   => esc_html__( 'Payment', 'digicommerce' ),
			'checkout'  => esc_html__( 'Checkout', 'digicommerce' ),
			'emails'    => esc_html__( 'Emails', 'digicommerce' ),
			'styling'   => esc_html__( 'Styling', 'digicommerce' ),
		);

		$tabs = apply_filters( 'digicommerce_settings_tabs', $tabs );

		// Show settings saved notice
		if ( isset( $_GET['settings-updated'] ) && 'true' === $_GET['settings-updated'] ) { // phpcs:ignore
			add_settings_error(
				'digicommerce_messages',
				'digicommerce_message',
				esc_html__( 'Settings saved.', 'digicommerce' ),
				'updated'
			);
		}

		$help = array();

		// Add 'pro' option only if DigiCommerce Pro not activated
		if ( ! class_exists( 'DigiCommerce_Pro' ) ) {
			$help['pro'] = array(
				'title' => esc_html__( 'Upgrade to pro', 'digicommerce' ),
				'svg'   => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 32" width="15" height="15" fill="#fff" class="default-transition"><path d="m2.8373 20.9773c-.6083-3.954-1.2166-7.9079-1.8249-11.8619-.1349-.8765.8624-1.4743 1.5718-.9422 1.8952 1.4214 3.7903 2.8427 5.6855 4.2641.624.468 1.513.3157 1.9456-.3333l4.7333-7.1c.5002-.7503 1.6026-.7503 2.1028 0l4.7333 7.1c.4326.649 1.3216.8012 1.9456.3333 1.8952-1.4214 3.7903-2.8427 5.6855-4.2641.7094-.5321 1.7067.0657 1.5719.9422-.6083 3.954-1.2166 7.9079-1.8249 11.8619z"></path><path d="m27.7902 27.5586h-23.5804c-.758 0-1.3725-.6145-1.3725-1.3725v-3.015h26.3255v3.015c-.0001.758-.6146 1.3725-1.3726 1.3725z"></path></svg>',
				'url'   => 'https://digicommerce.me/pricing',
			);
		}

		$help['support'] = array(
			'title' => esc_html__( 'Support', 'digicommerce' ),
			'svg'   => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" width="15" height="15" fill="#fff" class="default-transition"><path d="M256 448c141.4 0 256-93.1 256-208S397.4 32 256 32S0 125.1 0 240c0 45.1 17.7 86.8 47.7 120.9c-1.9 24.5-11.4 46.3-21.4 62.9c-5.5 9.2-11.1 16.6-15.2 21.6c-2.1 2.5-3.7 4.4-4.9 5.7c-.6 .6-1 1.1-1.3 1.4l-.3 .3c0 0 0 0 0 0c0 0 0 0 0 0s0 0 0 0s0 0 0 0c-4.6 4.6-5.9 11.4-3.4 17.4c2.5 6 8.3 9.9 14.8 9.9c28.7 0 57.6-8.9 81.6-19.3c22.9-10 42.4-21.9 54.3-30.6c31.8 11.5 67 17.9 104.1 17.9zM169.8 149.3c7.9-22.3 29.1-37.3 52.8-37.3l58.3 0c34.9 0 63.1 28.3 63.1 63.1c0 22.6-12.1 43.5-31.7 54.8L280 248.4c-.2 13-10.9 23.6-24 23.6c-13.3 0-24-10.7-24-24l0-13.5c0-8.6 4.6-16.5 12.1-20.8l44.3-25.4c4.7-2.7 7.6-7.7 7.6-13.1c0-8.4-6.8-15.1-15.1-15.1l-58.3 0c-3.4 0-6.4 2.1-7.5 5.3l-.4 1.2c-4.4 12.5-18.2 19-30.6 14.6s-19-18.2-14.6-30.6l.4-1.2zM224 336a32 32 0 1 1 64 0 32 32 0 1 1 -64 0z"/></svg>',
			'url'   => 'https://digihold.me/my-account/',
		);

		$help['documentation'] = array(
			'title' => esc_html__( 'Documentation', 'digicommerce' ),
			'svg'   => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" width="15" height="15" fill="#fff" class="default-transition"><path d="M0 32C0 14.3 14.3 0 32 0L96 0c17.7 0 32 14.3 32 32l0 64L0 96 0 32zm0 96l128 0 0 256L0 384 0 128zM0 416l128 0 0 64c0 17.7-14.3 32-32 32l-64 0c-17.7 0-32-14.3-32-32l0-64zM160 32c0-17.7 14.3-32 32-32l64 0c17.7 0 32 14.3 32 32l0 64L160 96l0-64zm0 96l128 0 0 256-128 0 0-256zm0 288l128 0 0 64c0 17.7-14.3 32-32 32l-64 0c-17.7 0-32-14.3-32-32l0-64zm203.6-19.9L320 232.6l0-89.9 100.4-26.9 66 247.4L363.6 396.1zM412.2 85L320 109.6 320 11l36.9-9.9c16.9-4.6 34.4 5.5 38.9 22.6L412.2 85zM371.8 427l122.8-32.9 16.3 61.1c4.5 17-5.5 34.5-22.5 39.1l-61.4 16.5c-16.9 4.6-34.4-5.5-38.9-22.6L371.8 427z"/></svg>',
			'url'   => 'https://docs.digicommerce.me/',
		);

		// Define allowed SVG tags.
		$allowed_html = array(
			'svg'  => array(
				'xmlns'   => true,
				'viewbox' => true,
				'width'   => true,
				'height'  => true,
				'fill'    => true,
				'class'   => true,
			),
			'path' => array(
				'd'    => true,
				'fill' => true,
			),
		);

		// UTM parameters
		$utm_params = '?utm_source=WordPress&utm_medium=header&utm_campaign=digi';
		?>
		<div class="digicommerce">
			<div class="flex flex-col md:flex-row items-center justify-between gap-4 bg-dark-blue box-border ltr:-ml-5 rtl:-mr-5 px-8 py-6">
				<div class="digicommerce-logo">
					<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 2148.09 350" width="250" height="40.73">
						<g>
							<path d="M425.4756,249.9932V108.5933h69.6904c15.7559,0,29.624,2.8628,41.6123,8.585,11.9844,5.7256,21.3418,13.8369,28.0771,24.3408,6.7324,10.5039,10.1006,23.0283,10.1006,37.5718,0,14.6797-3.3682,27.3047-10.1006,37.875-6.7354,10.5732-16.0928,18.7197-28.0771,24.4424-11.9883,5.7256-25.8564,8.585-41.6123,8.585h-69.6904ZM473.1475,212.8252h19.998c6.7324,0,12.625-1.2783,17.6758-3.8379,5.0498-2.5566,8.9883-6.3633,11.8164-11.4131,2.8281-5.0508,4.2422-11.2109,4.2422-18.4834,0-7.1357-1.4141-13.1958-4.2422-18.1797-2.8281-4.9805-6.7666-8.7524-11.8164-11.312-5.0508-2.5566-10.9434-3.8379-17.6758-3.8379h-19.998v67.064Z" fill="#fff"/>
							<path d="M592.3252,249.9932V108.5933h47.6719v141.3999h-47.6719Z" fill="#fff"/>
							<path d="M736.3496,253.2246c-11.4473,0-21.9863-1.7861-31.6133-5.3525-9.6289-3.5664-17.9775-8.6514-25.0479-15.251-7.0693-6.5967-12.5586-14.4082-16.4629-23.4326-3.9072-9.0205-5.8574-18.9873-5.8574-29.8955s1.9502-20.8721,5.8574-29.896c3.9043-9.0205,9.4248-16.832,16.5645-23.4316,7.1357-6.5967,15.585-11.6816,25.3506-15.251,9.7627-3.5669,20.5029-5.353,32.2188-5.353,14.0049,0,26.4941,2.3574,37.4717,7.0698,10.9736,4.7153,20.0293,11.4478,27.1689,20.2002l-30.502,26.8657c-4.4443-5.1162-9.2607-8.9888-14.4434-11.6147-5.1855-2.626-10.9424-3.939-17.2705-3.939-5.252,0-9.999.8076-14.2412,2.4238s-7.8467,3.9736-10.8076,7.0698c-2.9629,3.0996-5.252,6.8018-6.8672,11.1104-1.6162,4.311-2.4248,9.2256-2.4248,14.7456,0,5.252.8086,10.0684,2.4248,14.4434,1.6152,4.377,3.9043,8.1143,6.8672,11.2109,2.9609,3.0986,6.4961,5.4883,10.6055,7.1709,4.1064,1.6855,8.7178,2.5244,13.8369,2.5244,5.3848,0,10.6367-.9082,15.7559-2.7266,5.1162-1.8184,10.5703-4.9492,16.3623-9.3936l26.6641,32.7246c-8.6201,5.792-18.4512,10.2354-29.4922,13.332-11.0439,3.0957-21.75,4.6455-32.1182,4.6455ZM756.5498,229.1865v-53.7314h41.4102v59.792l-41.4102-6.0605Z" fill="#fff"/>
							<path d="M818.3613,249.9932V108.5933h47.6719v141.3999h-47.6719Z" fill="#fff"/>
							<path d="M962.1826,253.2246c-11.3115,0-21.7842-1.7861-31.4111-5.3525-9.6289-3.5664-17.9775-8.6514-25.0479-15.251-7.0693-6.5967-12.5586-14.4082-16.4629-23.4326-3.9072-9.0205-5.8574-18.9873-5.8574-29.8955s1.9502-20.8721,5.8574-29.896c3.9043-9.0205,9.3936-16.832,16.4629-23.4316,7.0703-6.5967,15.4189-11.6816,25.0479-15.251,9.627-3.5669,20.0996-5.353,31.4111-5.353,13.8691,0,26.1592,2.4238,36.8652,7.272,10.7061,4.8477,19.5596,11.8516,26.5635,21.0078l-30.0986,26.8662c-4.1758-5.252-8.7871-9.3237-13.8369-12.2212-5.0498-2.894-10.7402-4.3428-17.0693-4.3428-4.9834,0-9.4932.8076-13.5332,2.4238s-7.5088,3.9736-10.4033,7.0698c-2.8975,3.0996-5.1514,6.8364-6.7666,11.2109-1.6162,4.3779-2.4248,9.2607-2.4248,14.645,0,5.3877.8086,10.2705,2.4248,14.6445,1.6152,4.3779,3.8691,8.1152,6.7666,11.2109,2.8945,3.0996,6.3633,5.4541,10.4033,7.0703s8.5498,2.4238,13.5332,2.4238c6.3291,0,12.0195-1.4453,17.0693-4.3428,5.0498-2.8945,9.6611-6.9688,13.8369-12.2207l30.0986,26.8662c-7.0039,9.0234-15.8574,15.9922-26.5635,20.9062-10.7061,4.915-22.9961,7.373-36.8652,7.373Z" fill="#fff"/>
							<path d="M1110.6504,253.2246c-11.583,0-22.2539-1.8174-32.0166-5.4541-9.7656-3.6357-18.2148-8.7861-25.3506-15.4521-7.1396-6.666-12.6953-14.5098-16.665-23.5332-3.9736-9.0205-5.959-18.8525-5.959-29.4922,0-10.772,1.9854-20.6353,5.959-29.5928,3.9697-8.9541,9.5254-16.7661,16.665-23.4321,7.1357-6.666,15.585-11.8169,25.3506-15.4531,9.7627-3.6357,20.3672-5.4536,31.8154-5.4536,11.5801,0,22.2197,1.8179,31.916,5.4536,9.6953,3.6362,18.1104,8.7871,25.25,15.4531,7.1357,6.666,12.6904,14.478,16.6641,23.4321,3.9707,8.9575,5.96,18.8208,5.96,29.5928,0,10.6396-1.9893,20.4717-5.96,29.4922-3.9736,9.0234-9.5283,16.8672-16.6641,23.5332-7.1396,6.666-15.5547,11.8164-25.25,15.4521-9.6963,3.6367-20.2695,5.4541-31.7148,5.4541ZM1110.4492,214.6426c4.4434,0,8.585-.8076,12.4229-2.4238s7.2021-3.9385,10.0996-6.9688c2.8945-3.0303,5.1514-6.7324,6.7676-11.1104,1.6152-4.374,2.4238-9.3232,2.4238-14.8467,0-5.52-.8086-10.4692-2.4238-14.8467-1.6162-4.3745-3.873-8.0801-6.7676-11.1104-2.8975-3.0298-6.2617-5.3525-10.0996-6.9688s-7.9795-2.4238-12.4229-2.4238-8.585.8076-12.4238,2.4238c-3.8379,1.6162-7.2051,3.939-10.0996,6.9688-2.8975,3.0303-5.1514,6.7358-6.7666,11.1104-1.6162,4.3774-2.4248,9.3267-2.4248,14.8467,0,5.5234.8086,10.4727,2.4248,14.8467,1.6152,4.3779,3.8691,8.0801,6.7666,11.1104,2.8945,3.0303,6.2617,5.3525,10.0996,6.9688,3.8389,1.6162,7.9795,2.4238,12.4238,2.4238Z" fill="#fff"/>
							<path d="M1207.6094,249.9932V108.5933h39.1885l56.5596,92.314h-20.6035l54.9434-92.314h39.1885l.4043,141.3999h-43.4307l-.4033-75.9521h6.8672l-37.5713,63.2256h-21.0078l-39.1885-63.2256h8.4844v75.9521h-43.4307Z" fill="#fff"/>
							<path d="M1400.3164,249.9932V108.5933h39.1885l56.5596,92.314h-20.6035l54.9434-92.314h39.1885l.4043,141.3999h-43.4307l-.4033-75.9521h6.8672l-37.5713,63.2256h-21.0078l-39.1885-63.2256h8.4844v75.9521h-43.4307Z" fill="#fff"/>
							<path d="M1639.8877,214.0371h70.7002v35.9561h-117.5645V108.5933h114.9385v35.9561h-68.0742v69.4878ZM1636.6562,161.1133h63.0234v34.3398h-63.0234v-34.3398Z" fill="#fff"/>
							<path d="M1728.9668,249.9932V108.5933h68.0742c13.1963,0,24.6094,2.1558,34.2393,6.4639,9.626,4.3115,17.0693,10.4727,22.3213,18.4829,5.252,8.0137,7.8779,17.4731,7.8779,28.3813s-2.626,20.3003-7.8779,28.1782-12.6953,13.9072-22.3213,18.0791c-9.6299,4.1758-21.043,6.2627-34.2393,6.2627h-41.6123l21.21-19.5947v55.1465h-47.6719ZM1776.6387,200.0986l-21.21-21.6133h38.582c6.5967,0,11.4795-1.4805,14.6455-4.4443,3.1621-2.9604,4.7471-7.0005,4.7471-12.1196s-1.585-9.1567-4.7471-12.1201c-3.166-2.9604-8.0488-4.4443-14.6455-4.4443h-38.582l21.21-21.6138v76.3555ZM1813.6055,249.9932l-34.7441-51.5098h50.5l35.1475,51.5098h-50.9033Z" fill="#fff"/>
							<path d="M1952.5801,253.2246c-11.3115,0-21.7842-1.7861-31.4111-5.3525-9.6289-3.5664-17.9775-8.6514-25.0479-15.251-7.0693-6.5967-12.5586-14.4082-16.4629-23.4326-3.9072-9.0205-5.8574-18.9873-5.8574-29.8955s1.9502-20.8721,5.8574-29.896c3.9043-9.0205,9.3936-16.832,16.4629-23.4316,7.0703-6.5967,15.4189-11.6816,25.0479-15.251,9.627-3.5669,20.0996-5.353,31.4111-5.353,13.8691,0,26.1592,2.4238,36.8652,7.272,10.7061,4.8477,19.5596,11.8516,26.5635,21.0078l-30.0986,26.8662c-4.1758-5.252-8.7871-9.3237-13.8369-12.2212-5.0498-2.894-10.7402-4.3428-17.0693-4.3428-4.9834,0-9.4932.8076-13.5332,2.4238s-7.5088,3.9736-10.4033,7.0698c-2.8975,3.0996-5.1514,6.8364-6.7666,11.2109-1.6162,4.3779-2.4248,9.2607-2.4248,14.645,0,5.3877.8086,10.2705,2.4248,14.6445,1.6152,4.3779,3.8691,8.1152,6.7666,11.2109,2.8945,3.0996,6.3633,5.4541,10.4033,7.0703s8.5498,2.4238,13.5332,2.4238c6.3291,0,12.0195-1.4453,17.0693-4.3428,5.0498-2.8945,9.6611-6.9688,13.8369-12.2207l30.0986,26.8662c-7.0039,9.0234-15.8574,15.9922-26.5635,20.9062-10.7061,4.915-22.9961,7.373-36.8652,7.373Z" fill="#fff"/>
							<path d="M2076.6055,214.0371h70.7002v35.9561h-117.5645V108.5933h114.9385v35.9561h-68.0742v69.4878ZM2073.374,161.1133h63.0234v34.3398h-63.0234v-34.3398Z" fill="#fff"/>
						</g>
						<g>
							<circle cx="175" cy="175" r="175" fill="#ccb161"/>
							<path d="M349.8016,184.1762c-4.2758,82.7633-66.0552,150.3104-146.1534,163.4835l-81.4756-81.4756c-.3885-.3363-.7648-.6865-1.128-1.05-3.8777-3.8755-6.2738-9.2269-6.2738-15.1382-.009-6.1388,2.6257-11.9842,7.2311-16.0431l-8.3358-8.3358c-.3449-.299-.6796-.6111-1.0026-.9341-3.4402-3.4402-5.5752-8.1907-5.5752-13.4225,0-1.6406.2107-3.2339.6052-4.7542l-32.7454-32.7454c-2.0957-1.7274-2.3942-4.8267-.6668-6.9224.9339-1.133,2.3252-1.7894,3.7935-1.7897h38.6684l-45.2032-45.2032c-1.9201-1.9218-1.9187-5.0363.0031-6.9565.9211-.9202,2.1694-1.4378,3.4714-1.4392h28.3828l-24.457-24.457c-.9239-.9211-1.4422-2.1728-1.4401-3.4774-.0008-2.7163,2.2005-4.9189,4.9168-4.9197h20.5931c1.3409,0,2.5565.5359,3.4439,1.4051l.0729.0729,31.3753,31.3753h137.1708c1.4694-.003,2.8623.6545,3.7939,1.7908l70.9348,70.9363Z" fill="#ab8b2b" fill-rule="evenodd"/>
							<path d="M247.1094,238.4189c3.1996,0,6.0907,1.2987,8.1966,3.3906,2.169,2.1718,3.3851,5.117,3.3804,8.1863,0,3.1938-1.2928,6.0907-3.3804,8.1827-2.1739,2.173-5.1228,3.3921-8.1966,3.3884-3.071.0049-6.0172-1.2146-8.1863-3.3884-2.1725-2.1686-3.3918-5.1131-3.3884-8.1827,0-3.1996,1.2965-6.0907,3.3884-8.1863,2.1696-2.1734,5.1154-3.3934,8.1863-3.3906h0ZM136.1827,238.4189c3.1988,0,6.0944,1.2987,8.1864,3.3906,2.1748,2.1686,3.3949,5.1151,3.3899,8.1863,0,3.1938-1.2943,6.0907-3.3899,8.1827-2.1685,2.1749-5.1152,3.3945-8.1864,3.3884-3.07.0055-6.0153-1.2141-8.1827-3.3884-2.1743-2.1675-3.3944-5.1126-3.3899-8.1827,0-3.1996,1.2943-6.0907,3.3899-8.1863,2.1678-2.1739,5.1126-3.3942,8.1827-3.3906h0ZM99.125,88.4322l5.4826,23.0161h-29.5947c-2.7165,0-4.9186,2.2021-4.9186,4.9186s2.2021,4.9186,4.9186,4.9186h68.8866c2.7159,0,4.9175,2.2016,4.9175,4.9175s-2.2016,4.9175-4.9175,4.9175h-34.6048l1.664,6.9934h-6.1666c-2.7165,0-4.9186,2.2021-4.9186,4.9186s2.2021,4.9186,4.9186,4.9186h44.2138c2.7165.0331,4.8917,2.2621,4.8586,4.9786-.0325,2.6698-2.1889,4.8261-4.8586,4.8586h-33.3645l1.7281,7.2596h-39.2962c-2.7147,0-4.9153,2.2014-4.9153,4.9175s2.2014,4.9175,4.9153,4.9175h77.7416c2.7165.0329,4.892,2.2616,4.8591,4.9781-.0323,2.6701-2.189,4.8268-4.8591,4.8591h-33.756l1.8251,7.6694c-4.3524.5068-8.268,2.4974-11.2211,5.4461-3.4402,3.4438-5.5752,8.1944-5.5752,13.424s2.1357,9.9823,5.5752,13.4225c3.4439,3.4461,8.1944,5.5796,13.4283,5.5796h1.766c-2.5444,1.0783-4.8574,2.6365-6.8126,4.5894-4.0237,4.0117-6.2817,9.4622-6.2738,15.1441,0,5.9114,2.396,11.2627,6.2738,15.1382,3.8755,3.8755,9.2305,6.2716,15.1382,6.2716,5.9114,0,11.2685-2.396,15.1441-6.2716,3.8755-3.8755,6.2716-9.2269,6.2716-15.1382.0077-5.6814-2.2493-11.1316-6.2716-15.1441-1.9561-1.9529-4.2698-3.511-6.8148-4.5894h94.2674c-2.5432,1.0782-4.8547,2.6364-6.8082,4.5894-3.8755,3.8755-6.2738,9.2305-6.2738,15.1441s2.3982,11.2627,6.2738,15.1382c3.8755,3.8755,9.2261,6.2716,15.1382,6.2716s11.2583-2.396,15.136-6.2716c3.8777-3.8755,6.2832-9.2269,6.2832-15.1382s-2.4062-11.2685-6.2832-15.1441c-1.9491-1.9546-4.2584-3.5131-6.8002-4.5894h7.019c2.7045,0,4.9117-2.2014,4.9117-4.9197s-2.2072-4.9197-4.9117-4.9197H126.0911c-2.5156,0-4.8059-1.0318-6.4728-2.6921-1.6603-1.6647-2.6965-3.9514-2.6965-6.4706,0-2.5156,1.0361-4.8023,2.6965-6.4663,1.6661-1.6603,3.9572-2.6965,6.4728-2.6965h119.6781c3.9827,0,7.6716-1.3322,10.6101-3.6429,2.9232-2.3005,5.0903-5.5694,6.0448-9.4588l17.3199-71.0639c.1609-.5052.2413-1.0325.2384-1.5626,0-2.7162-2.1875-4.9197-4.9117-4.9197H114.7168l-6.8863-28.91c-.4643-2.2944-2.4811-3.9437-4.822-3.9433h-20.5902c-2.7163-.0008-4.9189,2.2005-4.9197,4.9168v.0029c0,2.7159,2.2016,4.9175,4.9175,4.9175h16.7089v-.0007Z" fill="#fff" fill-rule="evenodd"/>
						</g>
					</svg>
				</div>

				<div class="digicommerce-help flex flex-col esm:flex-row items-center gap-4">
					<?php
					foreach ( $help as $id => $array ) :
						$url = $array['url'];
						// Add UTM parameters appropriately
						if ( 'support' === $id ) {
							// For support URL, check if there are existing parameters
							$url .= ( strpos( $url, '?' ) !== false ) ? '&' : '?';
							$url .= 'section=support';
							$url .= '&utm_source=WordPress&utm_medium=header&utm_campaign=digi';
						} else {
							// For other URLs, simply append the UTM parameters
							$url .= $utm_params;
						}
						?>
						<a class="flex items-center gap-2 text-white hover:text-white/80 active:text-white/80 focus:text-white/80 default-transition" href="<?php echo esc_url( $url ); ?>" target="_blank" rel="noopener noreferrer">
							<div class="digicommerce-help-icon flex items-center justify-center w-8 h-8 bg-white/50 rounded-full p-2 default-transition">
								<?php echo wp_kses( $array['svg'], $allowed_html ); ?>
							</div>

							<div><?php echo esc_attr( $array['title'] ); ?></div>
						</a>
						<?php
					endforeach;
					?>
				</div>
			</div>

			<?php
			if ( class_exists( 'DigiCommerce_Pro' ) && ! DigiCommerce_Pro_Updates::instance()->has_pro_access() ) {
				?>
				<div class="digicommerce-notice notice-warning flex items-center gap-4 min-h-[48px] bg-[#fff7ee] text-[#08053a] shadow-[0px_1px_2px_rgba(16,24,40,0.1)] m-5 ltr:ml-0 rtl:mr-0 p-4 rounded-md border border-solid border-[rgba(247,144,9,0.32)]">
					<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" width="16" height="16" fill="#f56e28"><path d="M256 32c14.2 0 27.3 7.5 34.5 19.8l216 368c7.3 12.4 7.3 27.7 .2 40.1S486.3 480 472 480L40 480c-14.3 0-27.6-7.7-34.7-20.1s-7-27.8 .2-40.1l216-368C228.7 39.5 241.8 32 256 32zm0 128c-13.3 0-24 10.7-24 24l0 112c0 13.3 10.7 24 24 24s24-10.7 24-24l0-112c0-13.3-10.7-24-24-24zm32 224a32 32 0 1 0 -64 0 32 32 0 1 0 64 0z"/></svg>
					<p class="m-0">
						<?php
						printf(
							// Translators: %1$s and %2$s are the opening and closing <a> tags, %3$s is the closing </a> tag.
							esc_html__( 'Activate your license to enable access to updates, support & PRO features. %1$sActivate now%2$s%3$s', 'digicommerce' ),
							'<a href="' . esc_url( admin_url( 'admin.php?page=digicommerce-updates' ) ) . '" class="inline-flex items-center gap-1 underline default-transition">',
							'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" width="12" height="12"><path d="M11.2 2.8a.8.8 0 00-1.3 1L12.6 7h-11a.8.8 0 100 1.7h11L10 12a.8.8 0 101.3 1L15 8.6a.8.8 0 000-1.2l-3.8-4.5z"/></svg>',
							'</a>'
						);
						?>
					</p>
				</div>
				<?php
			}
			?>

			<?php settings_errors( 'digicommerce_messages' ); ?>

			<div class="flex flex-col 2xl:grid 2xl:grid-cols-12 m-5 ltr:ml-0 rtl:mr-0">
				<div class="digicommerce-tabs 2xl:col-span-2">
					<?php foreach ( $tabs as $tab_id => $tab_label ) : ?>
						<a href="
						<?php
						echo esc_url(
							add_query_arg(
								array(
									'page' => 'digicommerce-settings',
									'tab'  => $tab_id,
								),
								admin_url( 'admin.php' )
							)
						);
						?>
									" class="digicommerce-tab cursor-pointer flex justify-start w-full no-underline text-dark-blue hover:text-dark-blue bg-light-blue hover:bg-[#f2f5ff] select-none text-center box-border p-4 text-medium border-0 border-b border-solid border-[rgba(0,0,0,0.05)] first:2xl:rounded-[.375rem_0_0] last:2xl:rounded-[0_0_0_.375rem] last:border-b-0 default-transition <?php echo esc_attr( $current_tab === $tab_id ? 'active' : '' ); ?>" data-tab="<?php echo esc_attr( $tab_id ); ?>">
							<span class="relative">
								<?php echo esc_html( $tab_label ); ?>
							</span>
						</a>
					<?php endforeach; ?>
				</div>

				<div class="flex flex-col gap-12 bg-white box-border p-6 2xl:rounded-[0_.375rem_.375rem_0] 2xl:col-span-10">
					<!-- Add the current tab to form action URL -->
					<form method="post" action="" class="flex flex-col justify-between h-full">
						<?php wp_nonce_field( 'digicommerce_settings_nonce' ); ?>
						<input type="hidden" name="active_tab" value="<?php echo esc_attr( $current_tab ); ?>">

						<!-- Tab Contents -->
						<?php foreach ( $tabs as $tab_id => $tab_label ) : ?>
							<div id="<?php echo esc_attr( $tab_id ); ?>" class="digicommerce-tab-content hidden gap-10 <?php echo esc_attr( $current_tab === $tab_id ? 'active' : '' ); ?>">
								<?php $this->render_tab_content( $tab_id ); ?>
							</div>
						<?php endforeach; ?>

						<p class="submit p-0 m-0 mt-20">
							<button type="submit" name="digicommerce_save_settings" class="digi__button">
								<span class="text"><?php esc_html_e( 'Save settings', 'digicommerce' ); ?></span>
								<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" width="18" height="18"><path d="M11.2 2.8a.8.8 0 00-1.3 1L12.6 7h-11a.8.8 0 100 1.7h11L10 12a.8.8 0 101.3 1L15 8.6a.8.8 0 000-1.2l-3.8-4.5z"/></svg>
							</button>
						</p>
					</form>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Render tab content
	 *
	 * @param string $tab_id Tab ID.
	 */
	private function render_tab_content( $tab_id ) {
		if ( 'general' === $tab_id ) {
			$this->render_general_tab();
		} elseif ( 'product' === $tab_id ) {
			$this->render_product_tab();
		} elseif ( 'pages' === $tab_id ) {
			$this->render_pages_tab();
		} elseif ( 'recaptcha' === $tab_id ) {
			$this->render_recaptcha_tab();
		} elseif ( 'payment' === $tab_id ) {
			$this->render_payment_tab();
		} elseif ( 'checkout' === $tab_id ) {
			$this->render_checkout_tab();
		} elseif ( 'emails' === $tab_id ) {
			$this->render_emails_tab();
		} elseif ( 'styling' === $tab_id ) {
			$this->render_styling_tab();
		} else {
			do_action( 'digicommerce_render_tab_content', $tab_id );
		}
	}

	/**
	 * Render General Tab
	 */
	private function render_general_tab() {
		$options    = $this->get_options();
		$countries  = DigiCommerce()->get_countries();
		$currencies = DigiCommerce()->get_currencies();
		?>

		<!-- Tab Description -->
		<div class="digicommerce-tab-description">
			<?php esc_html_e( 'Configure the general settings of your store including currency, access restrictions, and redirections.', 'digicommerce' ); ?>
		</div>

		<!-- Business Info -->
		<div class="flex flex-col 3xl:flex-row gap-4">
			<div class="w-full 3xl:w-1/6 flex">
				<p class="text-dark-blue text-medium"><?php esc_html_e( 'Business Info', 'digicommerce' ); ?></p>
			</div>

			<div class="w-full 3xl:w-1/2 flex flex-col gap-4">
				<div class="flex flex-col 3xl:flex-row gap-4">
					<p class="flex flex-col gap-2 flex-1">
						<label class="cursor-pointer" for="business_name"><?php esc_html_e( 'Business Name', 'digicommerce' ); ?></label>
						<input type="text" id="business_name" name="business_name" value="<?php echo esc_attr( $options['business_name'] ); ?>" class="regular-text">
					</p>

					<p class="flex flex-col gap-2 flex-1">
						<label class="cursor-pointer" for="business_vat_number"><?php esc_html_e( 'Business VAT number', 'digicommerce' ); ?></label>
						<input type="text" id="business_vat_number" name="business_vat_number" value="<?php echo esc_attr( $options['business_vat_number'] ); ?>" class="regular-text">
					</p>
				</div>

				<div class="flex flex-col 3xl:flex-row gap-4">
					<p class="flex flex-col gap-2 flex-1">
						<label class="cursor-pointer" for="business_address"><?php esc_html_e( 'Address', 'digicommerce' ); ?></label>
						<input type="text" id="business_address" name="business_address" value="<?php echo esc_attr( $options['business_address'] ); ?>" class="regular-text">
					</p>

					<p class="flex flex-col gap-2 flex-1">
						<label class="cursor-pointer" for="business_address2"><?php esc_html_e( 'Address line 2', 'digicommerce' ); ?></label>
						<input type="text" id="business_address2" name="business_address2" value="<?php echo esc_attr( $options['business_address2'] ); ?>" class="regular-text">
					</p>
				</div>

				<div class="flex flex-col 3xl:flex-row gap-4">
					<p class="flex flex-col gap-2 flex-1">
						<label class="cursor-pointer" for="business_city"><?php esc_html_e( 'City', 'digicommerce' ); ?></label>
						<input type="text" id="business_city" name="business_city" value="<?php echo esc_attr( $options['business_city'] ); ?>" class="regular-text">
					</p>

					<p class="flex flex-col gap-2 flex-1">
						<label class="cursor-pointer" for="business_postal"><?php esc_html_e( 'Postal Code', 'digicommerce' ); ?></label>
						<input type="text" id="business_postal" name="business_postal" value="<?php echo esc_attr( $options['business_postal'] ); ?>" class="regular-text">
					</p>
				</div>

				<p class="business-country flex flex-col gap-2 flex-1">
					<label class="cursor-pointer" for="business_country"><?php esc_html_e( 'Country', 'digicommerce' ); ?></label>
					<select name="business_country" id="business_country" class="digicommerce__search">
						<option value=""><?php esc_html_e( 'Select your country', 'digicommerce' ); ?></option>
						<?php foreach ( $countries as $code => $country ) : ?>
							<option value="<?php echo esc_attr( $code ); ?>" <?php selected( $options['business_country'], $code ); ?>>
								<?php echo esc_html( $country['name'] ); ?>
							</option>
						<?php endforeach; ?>
					</select>
				</p>
			</div>
		</div>

		<!-- Business Logo -->
		<div class="flex flex-col 3xl:flex-row gap-4">
			<div class="w-full 3xl:w-1/6 flex">
				<p class="text-dark-blue text-medium"><?php esc_html_e( 'Business Logo', 'digicommerce' ); ?></p>
			</div>
			<div class="w-full 3xl:w-1/2 flex flex-col gap-4">
				<div class="image-wrap flex flex-col gap-4">
					<?php
					$logo_id = $options['business_logo'];
					?>
					<div class="image-preview <?php echo esc_attr( $logo_id ? 'flex' : 'hidden' ); ?>">
						<?php
						if ( $logo_id ) {
							echo wp_get_attachment_image( $logo_id, 'medium', false, array( 'class' => 'max-w-64' ) );
						}
						?>
					</div>
					<input type="hidden" name="business_logo" class="image-input" value="<?php echo esc_attr( $options['business_logo'] ); ?>">
					<div class="flex flex-col esm:flex-row gap-2">
						<button type="button" class="upload-logo flex items-center justify-center gap-2 bg-dark-blue hover:bg-[#6c698a] text-white hover:text-white py-2 px-4 rounded default-transition">
							<span class="text"><?php esc_html_e( 'Upload Logo', 'digicommerce' ); ?></span>
						</button>
						<?php if ( $logo_id ) : ?>
							<button type="button" class="remove-logo flex items-center justify-center gap-2 bg-red-600 hover:bg-red-400 text-white hover:text-white py-2 px-4 rounded default-transition">
								<span class="text"><?php esc_html_e( 'Remove Logo', 'digicommerce' ); ?></span>
							</button>
						<?php endif; ?>
					</div>
					<p class="description"><?php esc_html_e( 'Upload your business logo. Used for the invoices.', 'digicommerce' ); ?></p>
				</div>
			</div>
		</div>

		<!-- Currency Settings -->
		<div class="flex flex-col 3xl:flex-row gap-4">
			<div class="w-full 3xl:w-1/6 flex">
				<p class="text-dark-blue text-medium"><?php esc_html_e( 'Currency Settings', 'digicommerce' ); ?></p>
			</div>
			<div class="w-full 3xl:w-1/2 flex flex-col gap-4">
				<p class="flex flex-col gap-2">
					<label class="cursor-pointer" for="currency"><?php esc_html_e( 'Currency', 'digicommerce' ); ?></label>
					<select name="currency" id="currency" class="digicommerce__search" data-placeholder="<?php esc_html_e( 'Type your currency', 'digicommerce' ); ?>">
						<?php foreach ( $currencies as $code => $currency ) : ?>
							<option value="<?php echo esc_attr( $code ); ?>" <?php selected( $options['currency'], $code ); ?>>
								<?php echo esc_html( $currency['name'] . ' (' . $currency['symbol'] . ')' ); ?>
							</option>
						<?php endforeach; ?>
					</select>
				</p>
				<p class="flex flex-col gap-2">
					<label class="cursor-pointer" for="currency_position"><?php esc_html_e( 'Currency Position', 'digicommerce' ); ?></label>
					<select name="currency_position" id="currency_position">
						<option value="left" <?php selected( $options['currency_position'], 'left' ); ?>><?php esc_html_e( 'Left', 'digicommerce' ); ?></option>
						<option value="right" <?php selected( $options['currency_position'], 'right' ); ?>><?php esc_html_e( 'Right', 'digicommerce' ); ?></option>
						<option value="left_space" <?php selected( $options['currency_position'], 'left_space' ); ?>><?php esc_html_e( 'Left with space', 'digicommerce' ); ?></option>
						<option value="right_space" <?php selected( $options['currency_position'], 'right_space' ); ?>><?php esc_html_e( 'Right with space', 'digicommerce' ); ?></option>
					</select>
				</p>
				<p class="flex flex-col gap-2">
					<label class="cursor-pointer" for="thousand_sep"><?php esc_html_e( 'Thousand Separator', 'digicommerce' ); ?></label>
					<input type="text" id="thousand_sep" name="thousand_sep" value="<?php echo esc_attr( $options['thousand_sep'] ); ?>" class="regular-text" style="width:5rem;min-width:5rem">
					<small><?php esc_html_e( 'Set the thousand separator of displayed prices.', 'digicommerce' ); ?></small>
				</p>
				<p class="flex flex-col gap-2">
					<label class="cursor-pointer" for="decimal_sep"><?php esc_html_e( 'Decimal Separator', 'digicommerce' ); ?></label>
					<input type="text" id="decimal_sep" name="decimal_sep" value="<?php echo esc_attr( $options['decimal_sep'] ); ?>" class="regular-text" style="width:5rem;min-width:5rem">
					<small><?php esc_html_e( 'Set the decimal separator of displayed prices.', 'digicommerce' ); ?></small>
				</p>
				<p class="flex flex-col gap-2">
					<label class="cursor-pointer" for="num_decimals"><?php esc_html_e( 'Number Of Decimals', 'digicommerce' ); ?></label>
					<input type="number" min="0" step="1" id="num_decimals" name="num_decimals" value="<?php echo esc_attr( $options['num_decimals'] ); ?>" class="regular-text" style="width:5rem;min-width:5rem">
					<small><?php esc_html_e( 'Set the number of decimal points shown in displayed prices.', 'digicommerce' ); ?></small>
				</p>
			</div>
		</div>

		<!-- Access Restrictions -->
		<div class="flex flex-col 3xl:flex-row gap-4">
			<div class="w-full 3xl:w-1/6 flex">
				<p class="text-dark-blue text-medium"><?php esc_html_e( 'Access Restrictions', 'digicommerce' ); ?></p>
			</div>
			<div class="w-full 3xl:w-1/2 flex flex-col gap-4">
				<label class="flex items-center gap-2 cursor-pointer">
					<input type="checkbox" name="block_admin" value="1" <?php checked( $options['block_admin'], 1 ); ?>>
					<span class="flex-1"><?php esc_html_e( 'Prevent non-admin users from accessing administration', 'digicommerce' ); ?></span>
				</label>
			</div>
		</div>

		<!-- Global Redirections -->
		<div class="flex flex-col 3xl:flex-row gap-4">
			<div class="w-full 3xl:w-1/6 flex">
				<p class="text-dark-blue text-medium"><?php esc_html_e( 'Global Redirections', 'digicommerce' ); ?></p>
			</div>
			<div class="w-full 3xl:w-1/2 flex flex-col gap-4">
				<label class="flex items-center gap-2 cursor-pointer">
					<input type="checkbox" name="redirect_login" value="1" <?php checked( $options['redirect_login'], 1 ); ?>>
					<span class="flex-1"><?php esc_html_e( 'Redirect visitors to the login page instead of default WordPress logins pages', 'digicommerce' ); ?></span>
				</label>
			</div>
		</div>

		<!-- Redirection After Logout -->
		<div class="flex flex-col 3xl:flex-row gap-4">
			<div class="w-full 3xl:w-1/6 flex">
				<p class="text-dark-blue text-medium"><?php esc_html_e( 'Redirection after logout', 'digicommerce' ); ?></p>
			</div>
			<div class="w-full 3xl:w-1/2 flex flex-col gap-4">
				<?php
				wp_dropdown_pages(
					array(
						'name'             => 'redirect_after_logout',
						'show_option_none' => esc_html__( 'My Account page (default)', 'digicommerce' ),
						'selected'         => $options['redirect_after_logout'], // phpcs:ignore
					)
				);
				?>
			</div>
		</div>

		<!-- Register Form -->
		<div class="flex flex-col 3xl:flex-row gap-4">
			<div class="w-full 3xl:w-1/6 flex">
				<p class="text-dark-blue text-medium"><?php esc_html_e( 'Register Form', 'digicommerce' ); ?></p>
			</div>
			<div class="w-full 3xl:w-1/2 flex flex-col gap-4">
				<label class="flex items-center gap-2 cursor-pointer">
					<input type="checkbox" name="register_form" value="1" <?php checked( $options['register_form'], 1 ); ?>>
					<span class="flex-1"><?php esc_html_e( 'Add a register form', 'digicommerce' ); ?></span>
				</label>
			</div>
		</div>

		<!-- Register Text -->
		<div class="flex flex-col 3xl:flex-row gap-4">
			<div class="w-full 3xl:w-1/6 flex">
				<p class="text-dark-blue text-medium"><?php esc_html_e( 'Register Text', 'digicommerce' ); ?></p>
			</div>
			<div class="w-full 3xl:w-1/2 flex flex-col gap-4">
				<?php
				wp_editor(
					$options['register_text'],
					'register_text',
					array(
						'textarea_name' => 'register_text',
						'textarea_rows' => 2,
						'media_buttons' => false,
						'tinymce'       => array(
							'toolbar1' => 'bold,italic,link,unlink',
							'toolbar2' => '',
						),
						'quicktags'     => true,
					)
				);
				?>
				<small><?php esc_html_e( 'Customize the registration text that appears below the title of the login form. Add id="show-register" to a link to display the register form.', 'digicommerce' ); ?></small>
			</div>
		</div>

		<!-- Login Text -->
		<div class="flex flex-col 3xl:flex-row gap-4">
			<div class="w-full 3xl:w-1/6 flex">
				<p class="text-dark-blue text-medium"><?php esc_html_e( 'Login Text', 'digicommerce' ); ?></p>
			</div>
			<div class="w-full 3xl:w-1/2 flex flex-col gap-4">
				<?php
				wp_editor(
					$options['login_text'],
					'login_text',
					array(
						'textarea_name' => 'login_text',
						'textarea_rows' => 2,
						'media_buttons' => false,
						'tinymce'       => array(
							'toolbar1' => 'bold,italic,link,unlink',
							'toolbar2' => '',
						),
						'quicktags'     => true,
					)
				);
				?>
				<small><?php esc_html_e( 'Customize the registration text that appears below the title of the register form. Add id="show-login" to a link to go back to login form.', 'digicommerce' ); ?></small>
			</div>
		</div>

		<!-- Invoices Footer -->
		<div class="flex flex-col 3xl:flex-row gap-4">
			<div class="w-full 3xl:w-1/6 flex">
				<p class="text-dark-blue text-medium"><?php esc_html_e( 'Invoices Footer', 'digicommerce' ); ?></p>
			</div>
			<div class="w-full 3xl:w-1/2 flex flex-col gap-4">
				<?php
				wp_editor(
					$options['invoices_footer'],
					'invoices_footer',
					array(
						'textarea_name' => 'invoices_footer',
						'textarea_rows' => 2,
						'media_buttons' => false,
						'tinymce'       => array(
							'toolbar1' => 'bold,italic,link,unlink',
							'toolbar2' => '',
						),
						'quicktags'     => true,
					)
				);
				?>
				<small><?php esc_html_e( 'Display copyright and business informations on each invoice downloaded from the order pages. {year} will display the current year and {site} for your site name.', 'digicommerce' ); ?></small>
			</div>
		</div>
		<?php
	}

	/**
	 * Render Product Tab
	 */
	private function render_product_tab() {
		$options = $this->get_options();
		?>

		<!-- Tab Description -->
		<div class="digicommerce-tab-description">
			<?php esc_html_e( 'Settings for the Product custom post type and the single product pages.', 'digicommerce' ); ?>
		</div>

		<!-- Product URLs -->
		<div class="flex flex-col 3xl:flex-row gap-4">
			<div class="w-full 3xl:w-1/6 flex flex-col gap-2">
				<p class="text-dark-blue text-medium"><?php esc_html_e( 'Product URLs', 'digicommerce' ); ?></p>
				
				<div class="flex">
					<a href="https://docs.digicommerce.me/docs/product-tab/?utm_source=WordPress&amp;utm_medium=settings&amp;utm_campaign=digi#product-urls" target="_blank" rel="noopener noreferrer" class="flex items-center gap-1 text-dark-blue hover:text-gold text-sm default-transition">
						<?php esc_html_e( 'Learn more', 'digicommerce' ); ?>
						<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" width="12" height="12"><path d="M11.2 2.8a.8.8 0 00-1.3 1L12.6 7h-11a.8.8 0 100 1.7h11L10 12a.8.8 0 101.3 1L15 8.6a.8.8 0 000-1.2l-3.8-4.5z"/></svg>
					</a>
				</div>
			</div>
			<div class="w-full 3xl:w-1/2 flex flex-col gap-4">
				<div class="bg-blue-50 border-l-4 border-blue-400 p-4 mb-4">
					<p class="text-sm text-blue-700">
						<?php esc_html_e( 'Changing URLs of existing products will break all current links to your products. Search engines will need to reindex your content, and any bookmarked or shared links will stop working. If you have existing products, it\'s recommended to set up proper 301 redirects before changing these URLs. Also, if you are using another eCommerce plugin like WooCommerce, make sure to use different slugs to avoid conflicts.', 'digicommerce' ); ?>
					</p>
				</div>

				<p class="flex flex-col gap-2">
					<label class="cursor-pointer" for="product_slug"><?php esc_html_e( 'Product Base', 'digicommerce' ); ?></label>
					<input type="text" id="product_slug" name="product_slug" value="<?php echo esc_attr( $options['product_slug'] ); ?>" placeholder="digital-product" class="regular-text">
					<small class="text-gray-500"><?php esc_html_e( 'Example: your-site.com/digital-product/product-name', 'digicommerce' ); ?></small>
				</p>

				<p class="flex flex-col gap-2">
					<label class="cursor-pointer" for="product_cat_slug"><?php esc_html_e( 'Product Category Base', 'digicommerce' ); ?></label>
					<input type="text" id="product_cat_slug" name="product_cat_slug" value="<?php echo esc_attr( $options['product_cat_slug'] ); ?>" placeholder="digital-product-category" class="regular-text">
					<small class="text-gray-500"><?php esc_html_e( 'Example: your-site.com/digital-product-category/category-name', 'digicommerce' ); ?></small>
				</p>

				<p class="flex flex-col gap-2">
					<label class="cursor-pointer" for="product_tag_slug"><?php esc_html_e( 'Product Tag Base', 'digicommerce' ); ?></label>
					<input type="text" id="product_tag_slug" name="product_tag_slug" value="<?php echo esc_attr( $options['product_tag_slug'] ); ?>" placeholder="digital-product-tag" class="regular-text">
					<small class="text-gray-500"><?php esc_html_e( 'Example: your-site.com/digital-product-tag/tag-name', 'digicommerce' ); ?></small>
				</p>
			</div>
		</div>

		<!-- Product CPT -->
		<div class="flex flex-col 3xl:flex-row gap-4">
			<div class="w-full 3xl:w-1/6 flex flex-col gap-2">
				<p class="text-dark-blue text-medium"><?php esc_html_e( 'Product Post Type', 'digicommerce' ); ?></p>

				<div class="flex">
					<a href="https://docs.digicommerce.me/docs/product-tab/?utm_source=WordPress&amp;utm_medium=settings&amp;utm_campaign=digi#product-post-type" target="_blank" rel="noopener noreferrer" class="flex items-center gap-1 text-dark-blue hover:text-gold text-sm default-transition">
						<?php esc_html_e( 'Learn more', 'digicommerce' ); ?>
						<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" width="12" height="12"><path d="M11.2 2.8a.8.8 0 00-1.3 1L12.6 7h-11a.8.8 0 100 1.7h11L10 12a.8.8 0 101.3 1L15 8.6a.8.8 0 000-1.2l-3.8-4.5z"/></svg>
					</a>
				</div>
			</div>
			<div class="w-full 3xl:w-1/2 flex flex-col gap-4">
				<label class="flex items-center gap-2 cursor-pointer">
					<input type="checkbox" name="product_cpt" value="1" <?php checked( $options['product_cpt'], 1 ); ?>>
					<span class="flex-1"><?php esc_html_e( 'Disable product pages if selling exclusively via the DigiCommerce button or shortcode.', 'digicommerce' ); ?></span>
				</label>
			</div>
		</div>
		<?php
	}

	/**
	 * Render Pages Tab
	 */
	private function render_pages_tab() {
		$options = $this->get_options();
		?>

		<!-- Tab Description -->
		<div class="digicommerce-tab-description">
			<?php esc_html_e( 'Select and configure essential pages for your store functionality such as Account, Checkout, and Payment pages.', 'digicommerce' ); ?>
		</div>

		<div class="flex flex-col 3xl:flex-row gap-4">
			<div class="w-full 3xl:w-1/6 flex">
				<p class="text-dark-blue text-medium"><?php esc_html_e( 'Essential Pages', 'digicommerce' ); ?></p>
			</div>
			<div class="w-full 3xl:w-1/2 flex flex-col gap-4">
				<?php
				$essential_pages = array(
					'account_page_id'         => array(
						'label'       => esc_html__( 'My Account Page', 'digicommerce' ),
						'description' => esc_html__( 'Page containing the [digicommerce_account] shortcode', 'digicommerce' ),
					),
					'reset_password_page_id'  => array(
						'label'       => esc_html__( 'Reset Password Page', 'digicommerce' ),
						'description' => esc_html__( 'Page containing the [digicommerce_reset_password] shortcode', 'digicommerce' ),
					),
					'checkout_page_id'        => array(
						'label'       => esc_html__( 'Checkout Page', 'digicommerce' ),
						'description' => esc_html__( 'Page containing the [digicommerce_checkout] shortcode', 'digicommerce' ),
					),
					'payment_success_page_id' => array(
						'label'       => esc_html__( 'Payment Success Page', 'digicommerce' ),
						'description' => esc_html__( 'Page containing the [digicommerce_payment_success] shortcode', 'digicommerce' ),
					),
				);

				foreach ( $essential_pages as $page_id => $page_data ) :
					?>
					<p>
						<label class="cursor-pointer" for="<?php echo esc_attr( $page_id ); ?>">
							<?php echo esc_html( $page_data['label'] ); ?>
							<span class="description">
								<?php
								printf(
									// translators: %s: shortcode.
									esc_html__( 'containing the %s shortcode', 'digicommerce' ),
									'<strong>' . esc_html( '[digicommerce_' . str_replace( '_page_id', '', $page_id ) . ']' ) . '</strong>'
								);
								?>
							</span>
						</label>
						<div class="flex flex-col md:flex-row md:items-center gap-4">
							<?php
							wp_dropdown_pages(
								array(
									'name'              => $page_id, // phpcs:ignore
									'id'                => $page_id, // phpcs:ignore
									'class'             => 'digicommerce__search',
									'show_option_none'  => esc_html__( 'Select a page', 'digicommerce' ),
									'option_none_value' => '',
									'selected'          => $options[ $page_id ], // phpcs:ignore
								)
							);

							if ( ! empty( $options[ $page_id ] ) ) {
								printf(
									' <a href="%s" target="_blank" class="edit-page-link text-dark-blue hover:text-gold default-transition">%s</a>',
									esc_url( get_edit_post_link( $options[ $page_id ] ) ),
									esc_html__( 'Edit page', 'digicommerce' )
								);
							}
							?>
						</div>
					</p>
				<?php endforeach; ?>
			</div>
		</div>
		<?php
	}

	/**
	 * Render reCAPTCHA Tab
	 */
	private function render_recaptcha_tab() {
		$options = $this->get_options();
		?>

		<!-- Tab Description -->
		<div class="digicommerce-tab-description">
			<?php esc_html_e( 'Set up Google reCAPTCHA v3 integration to protect your forms from spam and abuse.', 'digicommerce' ); ?>
		</div>

		<div class="flex flex-col 3xl:flex-row gap-4">
			<div class="w-full 3xl:w-1/6 flex flex-col gap-2">
				<p class="text-dark-blue text-medium"><?php esc_html_e( 'reCAPTCHA v3', 'digicommerce' ); ?></p>

				<div class="flex">
					<a href="https://docs.digicommerce.me/docs/recaptcha-tab/?utm_source=WordPress&amp;utm_medium=settings&amp;utm_campaign=digi" target="_blank" rel="noopener noreferrer" class="flex items-center gap-1 text-dark-blue hover:text-gold text-sm default-transition">
						<?php esc_html_e( 'See instructions', 'digicommerce' ); ?>
						<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" width="12" height="12"><path d="M11.2 2.8a.8.8 0 00-1.3 1L12.6 7h-11a.8.8 0 100 1.7h11L10 12a.8.8 0 101.3 1L15 8.6a.8.8 0 000-1.2l-3.8-4.5z"/></svg>
					</a>
				</div>
			</div>
			<div class="w-full 3xl:w-1/2 flex flex-col 3xl:flex-row gap-4">
				<div class="flex flex-col gap-2 flex-1">
					<label class="cursor-pointer" for="recaptcha_site_key"><?php esc_html_e( 'reCAPTCHA site key', 'digicommerce' ); ?></label>
					<input type="text" id="recaptcha_site_key" name="recaptcha_site_key" value="<?php echo esc_attr( $options['recaptcha_site_key'] ); ?>" class="regular-text">
				</div>

				<div class="flex flex-col gap-2 flex-1">
					<label class="cursor-pointer" for="recaptcha_secret_key"><?php esc_html_e( 'reCAPTCHA secret key', 'digicommerce' ); ?></label>
					<input type="text" id="recaptcha_secret_key" name="recaptcha_secret_key" value="<?php echo esc_attr( $options['recaptcha_secret_key'] ); ?>" class="regular-text">
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Render Payment Tab
	 */
	private function render_payment_tab() {
		$options = $this->get_options();
		?>

		<!-- Tab Description -->
		<div class="digicommerce-tab-description">
			<?php esc_html_e( 'Configure including Stripe and PayPal payment gateways for processing transactions.', 'digicommerce' ); ?>
		</div>

		<!-- Stripe Settings -->
		<div class="flex flex-col 3xl:flex-row gap-4 stripe">
			<div class="w-full 3xl:w-1/6 flex flex-col gap-2">
				<p class="text-dark-blue text-medium"><?php esc_html_e( 'Stripe', 'digicommerce' ); ?></p>

				<div class="flex">
					<a href="https://docs.digicommerce.me/docs/payment-tab?utm_source=WordPress&amp;utm_medium=settings&amp;utm_campaign=digi#stripe" target="_blank" rel="noopener noreferrer" class="flex items-center gap-1 text-dark-blue hover:text-gold text-sm default-transition">
						<?php esc_html_e( 'See instructions', 'digicommerce' ); ?>
						<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" width="12" height="12"><path d="M11.2 2.8a.8.8 0 00-1.3 1L12.6 7h-11a.8.8 0 100 1.7h11L10 12a.8.8 0 101.3 1L15 8.6a.8.8 0 000-1.2l-3.8-4.5z"/></svg>
					</a>
				</div>
			</div>
			<div class="w-full 3xl:w-1/2 flex flex-col gap-4">
				<!-- Mode Selection -->
				<div class="flex flex-row gap-4">
					<label class="flex items-center justify-center gap-2 cursor-pointer">
						<input type="radio" name="stripe_mode" value="test" <?php checked( $options['stripe_mode'], 'test' ); ?>>
						<?php esc_html_e( 'Test Mode', 'digicommerce' ); ?>
					</label>
					<label class="flex items-center justify-center gap-2 cursor-pointer">
						<input type="radio" name="stripe_mode" value="live" <?php checked( $options['stripe_mode'], 'live' ); ?>>
						<?php esc_html_e( 'Live Mode', 'digicommerce' ); ?>
					</label>
				</div>

				<!-- Test Mode Keys -->
				<div class="test-mode-keys hidden gap-4 flex-col 3xl:flex-row" <?php echo 'live' === $options['stripe_mode'] ? '' : 'style="display:flex;"'; ?>>
					<div class="flex flex-col gap-2 flex-1">
						<label class="cursor-pointer" for="stripe_test_publishable_key"><?php esc_html_e( 'Test Publishable Key', 'digicommerce' ); ?></label>
						<input type="text" id="stripe_test_publishable_key" name="stripe_test_publishable_key" value="<?php echo esc_attr( $options['stripe_test_publishable_key'] ); ?>" class="regular-text">
					</div>

					<div class="flex flex-col gap-2 flex-1">
						<label class="cursor-pointer" for="stripe_test_secret_key"><?php esc_html_e( 'Test Secret Key', 'digicommerce' ); ?></label>
						<input type="password" id="stripe_test_secret_key" name="stripe_test_secret_key" value="<?php echo esc_attr( $options['stripe_test_secret_key'] ); ?>" class="regular-text">
					</div>
				</div>

				<!-- Live Mode Keys -->
				<div class="live-mode-keys hidden gap-4 flex-col 3xl:flex-row" <?php echo 'test' === $options['stripe_mode'] ? '' : 'style="display:flex;"'; ?>>
					<div class="flex flex-col gap-2 flex-1">
						<label class="cursor-pointer" for="stripe_live_publishable_key"><?php esc_html_e( 'Live Publishable Key', 'digicommerce' ); ?></label>
						<input type="text" id="stripe_live_publishable_key" name="stripe_live_publishable_key" value="<?php echo esc_attr( $options['stripe_live_publishable_key'] ); ?>" class="regular-text">
					</div>

					<div class="flex flex-col gap-2 flex-1">
						<label class="cursor-pointer" for="stripe_live_secret_key"><?php esc_html_e( 'Live Secret Key', 'digicommerce' ); ?></label>
						<input type="password" id="stripe_live_secret_key" name="stripe_live_secret_key" value="<?php echo esc_attr( $options['stripe_live_secret_key'] ); ?>" class="regular-text">
					</div>
				</div>

				<!-- Webhook Settings -->
				<div class="flex flex-col gap-4">
					<div class="flex flex-col gap-2">
						<label class="cursor-pointer" for="stripe_webhook_secret"><?php esc_html_e( 'Webhook Signing Secret', 'digicommerce' ); ?></label>
						<input type="password" id="stripe_webhook_secret" name="stripe_webhook_secret" value="<?php echo esc_attr( $options['stripe_webhook_secret'] ); ?>" class="regular-text">
						<small><?php esc_html_e( 'The webhook signing secret can be found in your Stripe dashboard after adding the webhook endpoint.', 'digicommerce' ); ?></small>
					</div>
					
					<div class="flex flex-col gap-2">
						<p class="text-medium"><?php esc_html_e( 'Stripe Webhook URL', 'digicommerce' ); ?></p>
						<code class="bg-light-blue text-dark-blue p-4 rounded-md select-all"><?php echo esc_url( rest_url( 'digicommerce/v2/stripe-webhook' ) ); ?></code>
						<small>
							<?php
							printf(
								/* translators: %s: Documentation link */
								esc_html__( 'Add this webhook URL to your Stripe dashboard. %s for more details.', 'digicommerce' ),
								'<a href="https://docs.digicommerce.me/docs/payment-tab?utm_source=WordPress&amp;utm_medium=settings&amp;utm_campaign=digi#stripe-webhook" target="_blank" rel="noopener noreferrer">Read the documentation</a>'
							);
							?>
						</small>
					</div>
				</div>
			</div>
		</div>

		<!-- PayPal Settings -->
		<div class="flex flex-col 3xl:flex-row gap-4 paypal">
			<div class="w-full 3xl:w-1/6 flex flex-col gap-2">
				<p class="text-dark-blue text-medium"><?php esc_html_e( 'Paypal', 'digicommerce' ); ?></p>

				<div class="flex">
					<a href="https://docs.digicommerce.me/docs/payment-tab?utm_source=WordPress&amp;utm_medium=settings&amp;utm_campaign=digi#paypal" target="_blank" rel="noopener noreferrer" class="flex items-center gap-1 text-dark-blue hover:text-gold text-sm default-transition">
						<?php esc_html_e( 'See instructions', 'digicommerce' ); ?>
						<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" width="12" height="12"><path d="M11.2 2.8a.8.8 0 00-1.3 1L12.6 7h-11a.8.8 0 100 1.7h11L10 12a.8.8 0 101.3 1L15 8.6a.8.8 0 000-1.2l-3.8-4.5z"/></svg>
					</a>
				</div>
			</div>
			<div class="w-full 3xl:w-1/2 flex flex-col gap-4">
				<div class="flex flex-col gap-2">
					<label class="flex items-center gap-2 cursor-pointer">
						<input type="checkbox" name="paypal_sandbox" value="1" <?php checked( $options['paypal_sandbox'], '1' ); ?>>
						<span class="flex-1"><?php esc_html_e( 'Sandbox', 'digicommerce' ); ?></span>
					</label>
				</div>

				<div class="flex flex-col 3xl:flex-row gap-4">
					<div class="flex flex-col gap-2 flex-1">
						<label class="cursor-pointer" for="paypal_client_id"><?php esc_html_e( 'PayPal Client ID', 'digicommerce' ); ?></label>
						<input type="text" id="paypal_client_id" name="paypal_client_id" value="<?php echo esc_attr( $options['paypal_client_id'] ); ?>" class="regular-text">
					</div>

					<div class="flex flex-col gap-2 flex-1">
						<label class="cursor-pointer" for="paypal_secret"><?php esc_html_e( 'PayPal Secret', 'digicommerce' ); ?></label>
						<input type="password" id="paypal_secret" name="paypal_secret" value="<?php echo esc_attr( $options['paypal_secret'] ); ?>" class="regular-text">
					</div>
				</div>

				<!-- Webhook Settings -->
				<div class="flex flex-col gap-4">
					<div class="flex flex-col gap-2">
						<label class="cursor-pointer" for="paypal_webhook_id"><?php esc_html_e( 'PayPal Webhook ID', 'digicommerce' ); ?></label>
						<input type="password" id="paypal_webhook_id" name="paypal_webhook_id" value="<?php echo esc_attr( $options['paypal_webhook_id'] ); ?>" class="regular-text">
						<small><?php esc_html_e( 'The webhook ID can be found in your PayPal developer dashboard after adding the webhook endpoint.', 'digicommerce' ); ?></small>
					</div>
					
					<div class="flex flex-col gap-2">
						<p class="text-medium"><?php esc_html_e( 'PayPal Webhook URL', 'digicommerce' ); ?></p>
						<code class="bg-light-blue text-dark-blue p-4 rounded-md select-all"><?php echo esc_url( rest_url( 'digicommerce/v2/paypal-webhook' ) ); ?></code>
						<small>
							<?php
							printf(
								/* translators: %s: Documentation link */
								esc_html__( 'Add this webhook URL to your PayPal developer dashboard. %s for more details.', 'digicommerce' ),
								'<a href="https://docs.digicommerce.me/docs/payment-tab?utm_source=WordPress&amp;utm_medium=settings&amp;utm_campaign=digi#paypal-webhook" target="_blank" rel="noopener noreferrer">Read the documentation</a>'
							);
							?>
						</small>
					</div>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Render Checkout Tab
	 */
	private function render_checkout_tab() {
		$options = $this->get_options();
		?>
		
		<!-- Tab Description -->
		<div class="digicommerce-tab-description">
			<?php esc_html_e( 'Configure the checkout page to create the perfect sales experience.', 'digicommerce' ); ?>
		</div>

		<!-- Remove taxes -->
		<div class="flex flex-col 3xl:flex-row gap-4">
			<div class="w-full 3xl:w-1/6 flex flex-col gap-2">
				<p class="text-dark-blue text-medium"><?php esc_html_e( 'Remove taxes', 'digicommerce' ); ?></p>

				<div class="flex">
					<a href="https://docs.digicommerce.me/docs/checkout-tab?utm_source=WordPress&amp;utm_medium=settings&amp;utm_campaign=digi#remove-taxes" target="_blank" rel="noopener noreferrer" class="flex items-center gap-1 text-dark-blue hover:text-gold text-sm default-transition">
						<?php esc_html_e( 'See instructions', 'digicommerce' ); ?>
						<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" width="12" height="12"><path d="M11.2 2.8a.8.8 0 00-1.3 1L12.6 7h-11a.8.8 0 100 1.7h11L10 12a.8.8 0 101.3 1L15 8.6a.8.8 0 000-1.2l-3.8-4.5z"/></svg>
					</a>
				</div>
			</div>
			<div class="w-full 3xl:w-1/2 flex flex-col gap-4">
				<label class="flex items-center gap-2 cursor-pointer">
					<input type="checkbox" name="remove_taxes" value="1" <?php checked( $options['remove_taxes'], 1 ); ?>>
					<span class="flex-1"><?php esc_html_e( 'If you do not want to charge any VAT, just check this box.', 'digicommerce' ); ?></span>
				</label>
			</div>
		</div>

		<!-- Login form during checkout -->
		<div class="flex flex-col 3xl:flex-row gap-4">
			<div class="w-full 3xl:w-1/6 flex flex-col gap-2">
				<p class="text-dark-blue text-medium"><?php esc_html_e( 'Login Form', 'digicommerce' ); ?></p>

				<div class="flex">
					<a href="https://docs.digicommerce.me/docs/checkout-tab?utm_source=WordPress&amp;utm_medium=settings&amp;utm_campaign=digi#login-during-checkout" target="_blank" rel="noopener noreferrer" class="flex items-center gap-1 text-dark-blue hover:text-gold text-sm default-transition">
						<?php esc_html_e( 'See instructions', 'digicommerce' ); ?>
						<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" width="12" height="12"><path d="M11.2 2.8a.8.8 0 00-1.3 1L12.6 7h-11a.8.8 0 100 1.7h11L10 12a.8.8 0 101.3 1L15 8.6a.8.8 0 000-1.2l-3.8-4.5z"/></svg>
					</a>
				</div>
			</div>
			<div class="w-full 3xl:w-1/2 flex flex-col gap-4">
				<label class="flex items-center gap-2 cursor-pointer">
					<input type="checkbox" name="login_during_checkout" value="1" <?php checked( $options['login_during_checkout'], 1 ); ?>>
					<span class="flex-1"><?php esc_html_e( 'Add a login form during checkout to allow your users to easily log in and have their details automatically pre-filled in the checkout fields.', 'digicommerce' ); ?></span>
				</label>
			</div>
		</div>

		<!-- Minimal Style -->
		<div class="flex flex-col 3xl:flex-row gap-4">
			<div class="w-full 3xl:w-1/6 flex flex-col gap-2">
				<p class="text-dark-blue text-medium"><?php esc_html_e( 'Minimal Style', 'digicommerce' ); ?></p>

				<div class="flex">
					<a href="https://docs.digicommerce.me/docs/checkout-tab?utm_source=WordPress&amp;utm_medium=settings&amp;utm_campaign=digi#minimal-style" target="_blank" rel="noopener noreferrer" class="flex items-center gap-1 text-dark-blue hover:text-gold text-sm default-transition">
						<?php esc_html_e( 'See instructions', 'digicommerce' ); ?>
						<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" width="12" height="12"><path d="M11.2 2.8a.8.8 0 00-1.3 1L12.6 7h-11a.8.8 0 100 1.7h11L10 12a.8.8 0 101.3 1L15 8.6a.8.8 0 000-1.2l-3.8-4.5z"/></svg>
					</a>
				</div>
			</div>
			<div class="w-full 3xl:w-1/2 flex flex-col gap-4">
				<label class="flex items-center gap-2 cursor-pointer">
					<input type="checkbox" name="minimal_style" value="1" <?php checked( $options['minimal_style'], 1 ); ?>>
					<span class="flex-1"><?php esc_html_e( 'Revamp the checkout page with a cleaner and more minimalistic template', 'digicommerce' ); ?></span>
				</label>
			</div>
		</div>

		<!-- Minimal Fields -->
		<div class="flex flex-col 3xl:flex-row gap-4">
			<div class="w-full 3xl:w-1/6 flex flex-col gap-2">
				<p class="text-dark-blue text-medium"><?php esc_html_e( 'Minimal Fields', 'digicommerce' ); ?></p>

				<div class="flex">
					<a href="https://docs.digicommerce.me/docs/checkout-tab?utm_source=WordPress&amp;utm_medium=settings&amp;utm_campaign=digi#minimal-fields" target="_blank" rel="noopener noreferrer" class="flex items-center gap-1 text-dark-blue hover:text-gold text-sm default-transition">
						<?php esc_html_e( 'See instructions', 'digicommerce' ); ?>
						<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" width="12" height="12"><path d="M11.2 2.8a.8.8 0 00-1.3 1L12.6 7h-11a.8.8 0 100 1.7h11L10 12a.8.8 0 101.3 1L15 8.6a.8.8 0 000-1.2l-3.8-4.5z"/></svg>
					</a>
				</div>
			</div>
			<div class="w-full 3xl:w-1/2 flex flex-col gap-4">
				<label class="flex items-center gap-2 cursor-pointer">
					<input type="checkbox" name="minimal_fields" value="1" <?php checked( $options['minimal_fields'], 1 ); ?>>
					<span class="flex-1"><?php esc_html_e( 'Reduce the number of fields to only the essential ones for a faster and streamlined checkout process', 'digicommerce' ); ?></span>
				</label>
			</div>
		</div>

		<?php do_action( 'digicommerce_settings_after_minimal_fields' ); ?>

		<!-- Order Agreement -->
		<div class="flex flex-col 3xl:flex-row gap-4">
			<div class="w-full 3xl:w-1/6 flex flex-col gap-2">
				<p class="text-dark-blue text-medium"><?php esc_html_e( 'Order Agreement', 'digicommerce' ); ?></p>

				<div class="flex">
					<a href="https://docs.digicommerce.me/docs/checkout-tab?utm_source=WordPress&amp;utm_medium=settings&amp;utm_campaign=digi#agreement" target="_blank" rel="noopener noreferrer" class="flex items-center gap-1 text-dark-blue hover:text-gold text-sm default-transition">
						<?php esc_html_e( 'See instructions', 'digicommerce' ); ?>
						<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" width="12" height="12"><path d="M11.2 2.8a.8.8 0 00-1.3 1L12.6 7h-11a.8.8 0 100 1.7h11L10 12a.8.8 0 101.3 1L15 8.6a.8.8 0 000-1.2l-3.8-4.5z"/></svg>
					</a>
				</div>
			</div>
			<div class="w-full 3xl:w-1/2 flex flex-col gap-4">
				<?php
				wp_editor(
					$options['order_agreement'],
					'order_agreement',
					array(
						'textarea_name' => 'order_agreement',
						'textarea_rows' => 5,
						'media_buttons' => false,
						'tinymce'       => array(
							'toolbar1' => 'bold,italic,link,unlink,bullist,numlist,undo,redo',
							'toolbar2' => '',
						),
						'quicktags'     => true,
					)
				);
				?>
				<small><?php esc_html_e( 'Add a text that will display after the order button at the checkout page, you can add HTML content if you want to add a link to your terms & conditions page.', 'digicommerce' ); ?></small>
			</div>
		</div>

		<!-- Modal Terms -->
		<div class="flex flex-col 3xl:flex-row gap-4">
			<div class="w-full 3xl:w-1/6 flex flex-col gap-2">
				<p class="text-dark-blue text-medium"><?php esc_html_e( 'Modal Terms', 'digicommerce' ); ?></p>

				<div class="flex">
					<a href="https://docs.digicommerce.me/docs/checkout-tab?utm_source=WordPress&amp;utm_medium=settings&amp;utm_campaign=digi#modal" target="_blank" rel="noopener noreferrer" class="flex items-center gap-1 text-dark-blue hover:text-gold text-sm default-transition">
						<?php esc_html_e( 'See instructions', 'digicommerce' ); ?>
						<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" width="12" height="12"><path d="M11.2 2.8a.8.8 0 00-1.3 1L12.6 7h-11a.8.8 0 100 1.7h11L10 12a.8.8 0 101.3 1L15 8.6a.8.8 0 000-1.2l-3.8-4.5z"/></svg>
					</a>
				</div>
			</div>
			<div class="w-full 3xl:w-1/2 flex flex-col gap-4">
				<?php
				wp_editor(
					$options['modal_terms'],
					'modal_terms',
					array(
						'textarea_name' => 'modal_terms',
						'textarea_rows' => 5,
						'media_buttons' => false,
						'tinymce'       => array(
							'toolbar1' => 'bold,italic,link,unlink,bullist,numlist,undo,redo',
							'toolbar2' => '',
						),
						'quicktags'     => true,
					)
				);
				?>
				<small><?php esc_html_e( 'Add your terms & conditions into a modal on the checkout page, to display it, just add a link with the "modal" class into the Order Agreement field.', 'digicommerce' ); ?></small>
			</div>
		</div>

		<!-- Remove product -->
		<div class="flex flex-col 3xl:flex-row gap-4">
			<div class="w-full 3xl:w-1/6 flex flex-col gap-2">
				<p class="text-dark-blue text-medium"><?php esc_html_e( 'Remove product', 'digicommerce' ); ?></p>

				<div class="flex">
					<a href="https://docs.digicommerce.me/docs/checkout-tab?utm_source=WordPress&amp;utm_medium=settings&amp;utm_campaign=digi#remove-product" target="_blank" rel="noopener noreferrer" class="flex items-center gap-1 text-dark-blue hover:text-gold text-sm default-transition">
						<?php esc_html_e( 'See instructions', 'digicommerce' ); ?>
						<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" width="12" height="12"><path d="M11.2 2.8a.8.8 0 00-1.3 1L12.6 7h-11a.8.8 0 100 1.7h11L10 12a.8.8 0 101.3 1L15 8.6a.8.8 0 000-1.2l-3.8-4.5z"/></svg>
					</a>
				</div>
			</div>
			<div class="w-full 3xl:w-1/2 flex flex-col gap-4">
				<label class="flex items-center gap-2 cursor-pointer">
					<input type="checkbox" name="remove_product" value="1" <?php checked( $options['remove_product'], 1 ); ?>>
					<span class="flex-1"><?php esc_html_e( 'Remove the delete button on the checkout page, preventing product removal during checkout.', 'digicommerce' ); ?></span>
				</label>
			</div>
		</div>
		
		<!-- Empty cart settings -->
		<div class="flex flex-col 3xl:flex-row gap-4">
			<div class="w-full 3xl:w-1/6 flex flex-col gap-2">
				<p class="text-dark-blue text-medium"><?php esc_html_e( 'Empty cart settings', 'digicommerce' ); ?></p>
				
				<div class="flex">
					<a href="https://docs.digicommerce.me/docs/checkout-tab?utm_source=WordPress&amp;utm_medium=settings&amp;utm_campaign=digi#empty-cart" target="_blank" rel="noopener noreferrer" class="flex items-center gap-1 text-dark-blue hover:text-gold text-sm default-transition">
						<?php esc_html_e( 'Learn more', 'digicommerce' ); ?>
						<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" width="12" height="12"><path d="M11.2 2.8a.8.8 0 00-1.3 1L12.6 7h-11a.8.8 0 100 1.7h11L10 12a.8.8 0 101.3 1L15 8.6a.8.8 0 000-1.2l-3.8-4.5z"/></svg>
					</a>
				</div>
			</div>

			<div class="w-full 3xl:w-1/2 flex flex-col gap-4">
				<div class="flex flex-col 3xl:flex-row gap-4">
					<p class="flex flex-col gap-2 flex-1">
						<label class="cursor-pointer" for="empty_cart_title"><?php esc_html_e( 'Title', 'digicommerce' ); ?></label>
						<input type="text" id="empty_cart_title" name="empty_cart_title" value="<?php echo esc_attr( $options['empty_cart_title'] ); ?>" class="regular-text">
					</p>

					<p class="flex flex-col gap-2 flex-1">
						<label class="cursor-pointer" for="empty_cart_text"><?php esc_html_e( 'Text', 'digicommerce' ); ?></label>
						<input type="text" id="empty_cart_text" name="empty_cart_text" value="<?php echo esc_attr( $options['empty_cart_text'] ); ?>" class="regular-text">
					</p>
				</div>

				<div class="flex flex-col 3xl:flex-row gap-4">
					<p class="flex flex-col gap-2 flex-1">
						<label class="cursor-pointer" for="empty_cart_button_text"><?php esc_html_e( 'Button Text', 'digicommerce' ); ?></label>
						<input type="text" id="empty_cart_button_text" name="empty_cart_button_text" value="<?php echo esc_attr( $options['empty_cart_button_text'] ); ?>" class="regular-text">
					</p>

					<p class="flex flex-col gap-2 flex-1">
						<label class="cursor-pointer" for="empty_cart_button_url"><?php esc_html_e( 'Button URL', 'digicommerce' ); ?></label>
						<input type="text" id="empty_cart_button_url" name="empty_cart_button_url" value="<?php echo esc_attr( $options['empty_cart_button_url'] ); ?>" class="regular-text">
					</p>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Render Emails Tab
	 */
	private function render_emails_tab() {
		$options = $this->get_options();
		?>

		<!-- Tab Description -->
		<div class="digicommerce-tab-description">
			<?php esc_html_e( 'Customize your store\'s email settings to manage notifications for key events, such as account creation, new orders, and order status updates, ensuring a seamless communication experience for your customers.', 'digicommerce' ); ?>
		</div>

		<!-- Email Sender Settings -->
		<div class="flex flex-col 3xl:flex-row gap-4">
			<div class="w-full 3xl:w-1/6 flex">
				<p class="text-dark-blue text-medium"><?php esc_html_e( 'Sender Details', 'digicommerce' ); ?></p>
			</div>
			<div class="w-full 3xl:w-1/2 flex flex-col gap-4">
				<div class="flex flex-col 3xl:flex-row gap-4">
					<p class="flex flex-col gap-2 flex-1">
						<label class="cursor-pointer" for="email_from_name"><?php esc_html_e( 'From Name', 'digicommerce' ); ?></label>
						<input type="text" id="email_from_name" name="email_from_name" value="<?php echo esc_attr( $options['email_from_name'] ); ?>" class="regular-text">
						<small class="text-gray-500"><?php esc_html_e( 'The name that appears as the email sender.', 'digicommerce' ); ?></small>
					</p>

					<p class="flex flex-col gap-2 flex-1">
						<label class="cursor-pointer" for="email_from_address"><?php esc_html_e( 'From Email Address', 'digicommerce' ); ?></label>
						<input type="email" id="email_from_address" name="email_from_address" value="<?php echo esc_attr( $options['email_from_address'] ); ?>" class="regular-text">
						<small class="text-gray-500"><?php esc_html_e( 'The email address that will be used to send store emails.', 'digicommerce' ); ?></small>
					</p>
				</div>
			</div>
		</div>

		<!-- Email Branding -->
		<div class="flex flex-col 3xl:flex-row gap-4">
			<div class="w-full 3xl:w-1/6 flex">
				<p class="text-dark-blue text-medium"><?php esc_html_e( 'Email Branding', 'digicommerce' ); ?></p>
			</div>
			<div class="w-full 3xl:w-1/2 flex flex-col gap-4">
				<!-- Header Logo -->
				<div class="image-wrap flex flex-col gap-4">
					<?php
					$logo_id = $options['email_header_logo'];
					?>
					<div class="image-preview <?php echo esc_attr( $logo_id ? 'flex' : 'hidden' ); ?>">
						<?php
						if ( $logo_id ) {
							echo wp_get_attachment_image( $logo_id, 'medium', false, array( 'class' => 'max-w-64' ) );
						}
						?>
					</div>
					<input type="hidden" name="email_header_logo" class="image-input" value="<?php echo esc_attr( $options['email_header_logo'] ); ?>">
					<div class="flex flex-col esm:flex-row gap-2">
						<button type="button" class="upload-logo flex items-center justify-center gap-2 bg-dark-blue hover:bg-[#6c698a] text-white hover:text-white py-2 px-4 rounded default-transition">
							<span class="text"><?php esc_html_e( 'Upload Email Logo', 'digicommerce' ); ?></span>
						</button>
						<?php if ( $logo_id ) : ?>
							<button type="button" class="remove-logo flex items-center justify-center gap-2 bg-red-600 hover:bg-red-400 text-white hover:text-white py-2 px-4 rounded default-transition">
								<span class="text"><?php esc_html_e( 'Remove Logo', 'digicommerce' ); ?></span>
							</button>
						<?php endif; ?>
					</div>
					<small><?php esc_html_e( 'Add a PNG format to be compatible with all emails providers.', 'digicommerce' ); ?></small>
				</div>

				<!-- Header Logo Width -->
				<div class="flex flex-col gap-2">
					<label class="cursor-pointer" for="email_header_logo_width"><?php esc_html_e( 'Logo Width', 'digicommerce' ); ?></label>
					<input type="number" min="0" step="1" id="email_header_logo_width" name="email_header_logo_width" value="<?php echo esc_attr( $options['email_header_logo_width'] ); ?>" class="regular-text" style="width:8rem;min-width:8rem">
					<small><?php esc_html_e( 'Set a custom width for your logo to look sharp on retina screens. For example, upload an 800x132px logo and set its width to 400px for optimal clarity.', 'digicommerce' ); ?></small>
				</div>
			</div>
		</div>

		<!-- Email Notifications -->
		<div class="flex flex-col 3xl:flex-row gap-4">
			<div class="w-full 3xl:w-1/6 flex">
				<p class="text-dark-blue text-medium"><?php esc_html_e( 'Email Notifications', 'digicommerce' ); ?></p>
			</div>
			<div class="w-full 3xl:w-1/2 flex flex-col gap-4">
				<label class="flex items-center gap-2 cursor-pointer">
					<input type="checkbox" name="email_new_account" value="1" <?php checked( $options['email_new_account'], 1 ); ?>>
					<span class="flex-1"><?php esc_html_e( 'Send welcome email when a new account is created', 'digicommerce' ); ?></span>
				</label>

				<label class="flex items-center gap-2 cursor-pointer">
					<input type="checkbox" name="email_order_confirmation" value="1" <?php checked( $options['email_order_confirmation'], 1 ); ?>>
					<span class="flex-1"><?php esc_html_e( 'Send order confirmation email when a new order is placed', 'digicommerce' ); ?></span>
				</label>

				<label class="flex items-center gap-2 cursor-pointer">
					<input type="checkbox" name="email_order_cancelled" value="1" <?php checked( $options['email_order_cancelled'], 1 ); ?>>
					<span class="flex-1"><?php esc_html_e( 'Send notification email when an order is cancelled', 'digicommerce' ); ?></span>
				</label>

				<label class="flex items-center gap-2 cursor-pointer">
					<input type="checkbox" name="email_order_refunded" value="1" <?php checked( $options['email_order_refunded'], 1 ); ?>>
					<span class="flex-1"><?php esc_html_e( 'Send notification email when an order is refunded', 'digicommerce' ); ?></span>
				</label>

				<label class="flex items-center gap-2 cursor-pointer">
					<input type="checkbox" name="email_new_order_admin" value="1" <?php checked( $options['email_new_order_admin'], 1 ); ?>>
					<span class="flex-1"><?php esc_html_e( 'Send notification email to admin when a new order is placed', 'digicommerce' ); ?></span>
				</label>
			</div>
		</div>

		<!-- Footer text -->
		<div class="flex flex-col 3xl:flex-row gap-4">
			<div class="w-full 3xl:w-1/6 flex">
				<p class="text-dark-blue text-medium"><?php esc_html_e( 'Email Footer Text', 'digicommerce' ); ?></p>
			</div>
			<div class="w-full 3xl:w-1/2 flex flex-col gap-4">
				<p class="flex flex-col gap-2">
					<?php
					wp_editor(
						$options['email_footer_text'],
						'email_footer_text',
						array(
							'textarea_name' => 'email_footer_text',
							'textarea_rows' => 4,
							'media_buttons' => false,
							'tinymce'       => array(
								'toolbar1' => 'bold,italic,link,unlink',
								'toolbar2' => '',
							),
							'quicktags'     => true,
						)
					);
					?>
					<small><?php esc_html_e( 'Add a text that will display at the bottom of all store emails. {year} will display the current year and {site} for your site name.', 'digicommerce' ); ?></small>
				</p>
			</div>
		</div>

		<!-- Social Media Links -->
		<div class="flex flex-col 3xl:flex-row gap-4">
			<div class="w-full 3xl:w-1/6 flex">
				<p class="text-dark-blue text-medium"><?php esc_html_e( 'Social Media', 'digicommerce' ); ?></p>
			</div>
			<div class="w-full 9xl:w-1/2 flex flex-col gap-4">
				<label class="flex items-center gap-2 cursor-pointer">
					<input type="checkbox" name="show_social_links_in_email" value="1" <?php checked( $options['show_social_links_in_email'], 1 ); ?>>
					<?php esc_html_e( 'Show social media links in emails', 'digicommerce' ); ?>
				</label>

				<div class="social-links-repeater">
					<div class="social-links-container inline-flex flex-col gap-4 w-full">
						<?php
						// Retrieve saved data
						$social_links = $options['social_links'];

						if ( ! empty( $social_links ) ) :
							foreach ( $social_links as $index => $link ) :
								?>
								<div class="social-link-row flex flex-col mdl:flex-row mdl:items-center gap-4 border rounded p-2.5 border-solid border-[#ddd] cursor-move" draggable="true">
									<div class="flex gap-2 w-full">
										<div class="drag-handle flex items-center">
											<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="7" r="1"></circle><circle cx="12" cy="12" r="1"></circle><circle cx="12" cy="17" r="1"></circle><circle cx="7" cy="7" r="1"></circle><circle cx="7" cy="12" r="1"></circle><circle cx="7" cy="17" r="1"></circle></svg>
										</div>
										<select name="social_links[<?php echo esc_attr( $index ); ?>][platform]" id="social_platform_<?php echo esc_attr( $index ); ?>" class="regular-text" style="max-width:100%">
											<option value="facebook" <?php selected( $link['platform'], 'facebook' ); ?>>Facebook</option>
											<option value="twitter" <?php selected( $link['platform'], 'twitter' ); ?>>X</option>
											<option value="instagram" <?php selected( $link['platform'], 'instagram' ); ?>>Instagram</option>
											<option value="linkedin" <?php selected( $link['platform'], 'linkedin' ); ?>>LinkedIn</option>
											<option value="youtube" <?php selected( $link['platform'], 'youtube' ); ?>>YouTube</option>
											<option value="pinterest" <?php selected( $link['platform'], 'pinterest' ); ?>>Pinterest</option>
											<option value="tiktok" <?php selected( $link['platform'], 'tiktok' ); ?>>TikTok</option>
											<option value="github" <?php selected( $link['platform'], 'github' ); ?>>GitHub</option>
										</select>
									</div>
									<div class="flex flex-col gap-2 w-full">
										<input type="url" id="social_url_<?php echo esc_attr( $index ); ?>" name="social_links[<?php echo esc_attr( $index ); ?>][url]" placeholder="https://<?php esc_html_e( 'profile', 'digicommerce' ); ?>.com/" value="<?php echo esc_url( $link['url'] ); ?>" class="regular-text">
									</div>
									<button type="button" class="remove-social-link flex items-center justify-center gap-2 bg-red-600 hover:bg-red-400 text-white hover:text-white py-2 px-4 rounded default-transition"><?php esc_html_e( 'Remove', 'digicommerce' ); ?></button>
								</div>
								<?php
							endforeach;
						endif;
						?>
					</div>
					<button type="button" class="add-social-link mt-4 flex items-center justify-center gap-2 bg-dark-blue hover:bg-[#6c698a] text-white hover:text-white py-2 px-4 rounded default-transition">
						<?php esc_html_e( 'Add Social', 'digicommerce' ); ?>
					</button>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Render Styling Tab
	 */
	private function render_styling_tab() {
		$options = $this->get_options();
		?>

		<!-- Tab Description -->
		<div class="digicommerce-tab-description">
			<?php esc_html_e( 'Customize your store\'s with your own colors or disable completely the default styles.', 'digicommerce' ); ?>
		</div>

		<!-- Disable styling -->
		<div class="flex flex-col 3xl:flex-row gap-4">
			<div class="w-full 3xl:w-1/6 flex">
				<p class="text-dark-blue text-medium"><?php esc_html_e( 'Disable CSS', 'digicommerce' ); ?></p>
			</div>
			<div class="w-full 3xl:w-1/2 flex flex-col gap-4">
				<label class="flex items-center gap-2 cursor-pointer">
					<input type="checkbox" name="disable_styling" value="1" <?php checked( $options['disable_styling'], 1 ); ?>>
					<span class="flex-1"><?php esc_html_e( 'Disable all plugin styles by default', 'digicommerce' ); ?></span>
				</label>
			</div>
		</div>

		<!-- Colors Settings -->
		<div class="flex flex-col 3xl:flex-row gap-4">
			<div class="w-full 3xl:w-1/6 flex">
				<p class="text-dark-blue text-medium"><?php esc_html_e( 'Colors', 'digicommerce' ); ?></p>
			</div>
			<div class="w-full 3xl:w-1/2 flex flex-col gap-4">
				<div class="grid grid-cols-1 esm:grid-cols-3 md:grid-cols-5 gap-4">
					<!-- Gold -->
					<div class="flex flex-col gap-2">
						<label class="cursor-pointer" for="color_gold"><?php esc_html_e( 'Gold', 'digicommerce' ); ?></label>
						<input type="color" id="color_gold" name="color_gold" value="<?php echo esc_attr( isset( $options['color_gold'] ) && $options['color_gold'] ? $options['color_gold'] : '#CCB161' ); ?>" class="digi-color">
						<small class="text-gray-500"><?php esc_html_e( 'Default: #CCB161', 'digicommerce' ); ?></small>
					</div>

					<!-- Yellow -->
					<div class="flex flex-col gap-2">
						<label class="cursor-pointer" for="color_yellow"><?php esc_html_e( 'Yellow', 'digicommerce' ); ?></label>
						<input type="color" id="color_yellow" name="color_yellow" value="<?php echo esc_attr( isset( $options['color_yellow'] ) && $options['color_yellow'] ? $options['color_yellow'] : '#FFE599' ); ?>" class="digi-color">
						<small class="text-gray-500"><?php esc_html_e( 'Default: #FFE599', 'digicommerce' ); ?></small>
					</div>

					<!-- Border -->
					<div class="flex flex-col gap-2">
						<label class="cursor-pointer" for="color_border"><?php esc_html_e( 'Border', 'digicommerce' ); ?></label>
						<input type="color" id="color_border" name="color_border" value="<?php echo esc_attr( isset( $options['color_border'] ) && $options['color_border'] ? $options['color_border'] : '#CACED9' ); ?>" class="digi-color">
						<small class="text-gray-500"><?php esc_html_e( 'Default: #CACED9', 'digicommerce' ); ?></small>
					</div>

					<!-- Light Blue -->
					<div class="flex flex-col gap-2">
						<label class="cursor-pointer" for="color_light_blue"><?php esc_html_e( 'Light Blue', 'digicommerce' ); ?></label>
						<input type="color" id="color_light_blue" name="color_light_blue" value="<?php echo esc_attr( isset( $options['color_light_blue'] ) && $options['color_light_blue'] ? $options['color_light_blue'] : '#E1E4ED' ); ?>" class="digi-color">
						<small class="text-gray-500"><?php esc_html_e( 'Default: #E1E4ED', 'digicommerce' ); ?></small>
					</div>

					<!-- Light Blue Background -->
					<div class="flex flex-col gap-2">
						<label class="cursor-pointer" for="color_light_blue_bg"><?php esc_html_e( 'Light Blue Background', 'digicommerce' ); ?></label>
						<input type="color" id="color_light_blue_bg" name="color_light_blue_bg" value="<?php echo esc_attr( isset( $options['color_light_blue_bg'] ) && $options['color_light_blue_bg'] ? $options['color_light_blue_bg'] : '#F6F7F9' ); ?>" class="digi-color">
						<small class="text-gray-500"><?php esc_html_e( 'Default: #F6F7F9', 'digicommerce' ); ?></small>
					</div>

					<!-- Dark Blue -->
					<div class="flex flex-col gap-2">
						<label class="cursor-pointer" for="color_dark_blue"><?php esc_html_e( 'Dark Blue', 'digicommerce' ); ?></label>
						<input type="color" id="color_dark_blue" name="color_dark_blue" value="<?php echo esc_attr( isset( $options['color_dark_blue'] ) && $options['color_dark_blue'] ? $options['color_dark_blue'] : '#09053A' ); ?>" class="digi-color">
						<small class="text-gray-500"><?php esc_html_e( 'Default: #09053A', 'digicommerce' ); ?></small>
					</div>

					<!-- Dark Blue 10 -->
					<div class="flex flex-col gap-2">
						<label class="cursor-pointer" for="color_dark_blue_10"><?php esc_html_e( 'Dark Blue 10', 'digicommerce' ); ?></label>
						<input type="color" id="color_dark_blue_10" name="color_dark_blue_10" value="<?php echo esc_attr( isset( $options['color_dark_blue_10'] ) && $options['color_dark_blue_10'] ? $options['color_dark_blue_10'] : '#E6E5EB' ); ?>" class="digi-color">
						<small class="text-gray-500"><?php esc_html_e( 'Default: #E6E5EB', 'digicommerce' ); ?></small>
					</div>

					<!-- Dark Blue 20 -->
					<div class="flex flex-col gap-2">
						<label class="cursor-pointer" for="color_dark_blue_20"><?php esc_html_e( 'Dark Blue 20', 'digicommerce' ); ?></label>
						<input type="color" id="color_dark_blue_20" name="color_dark_blue_20" value="<?php echo esc_attr( isset( $options['color_dark_blue_20'] ) && $options['color_dark_blue_20'] ? $options['color_dark_blue_20'] : '#BAB8C8' ); ?>" class="digi-color">
						<small class="text-gray-500"><?php esc_html_e( 'Default: #BAB8C8', 'digicommerce' ); ?></small>
					</div>

					<!-- Hover Blue -->
					<div class="flex flex-col gap-2">
						<label class="cursor-pointer" for="color_hover_blue"><?php esc_html_e( 'Hover Blue', 'digicommerce' ); ?></label>
						<input type="color" id="color_hover_blue" name="color_hover_blue" value="<?php echo esc_attr( isset( $options['color_hover_blue'] ) && $options['color_hover_blue'] ? $options['color_hover_blue'] : '#362F85' ); ?>" class="digi-color">
						<small class="text-gray-500"><?php esc_html_e( 'Default: #362F85', 'digicommerce' ); ?></small>
					</div>

					<!-- Grey -->
					<div class="flex flex-col gap-2">
						<label class="cursor-pointer" for="color_grey"><?php esc_html_e( 'Grey', 'digicommerce' ); ?></label>
						<input type="color" id="color_grey" name="color_grey" value="<?php echo esc_attr( isset( $options['color_grey'] ) && $options['color_grey'] ? $options['color_grey'] : '#646071' ); ?>" class="digi-color">
						<small class="text-gray-500"><?php esc_html_e( 'Default: #646071', 'digicommerce' ); ?></small>
					</div>

					<!-- Dark Grey -->
					<div class="flex flex-col gap-2">
						<label class="cursor-pointer" for="color_dark_grey"><?php esc_html_e( 'Dark Grey', 'digicommerce' ); ?></label>
						<input type="color" id="color_dark_grey" name="color_dark_grey" value="<?php echo esc_attr( isset( $options['color_dark_grey'] ) && $options['color_dark_grey'] ? $options['color_dark_grey'] : '#5B5766' ); ?>" class="digi-color">
						<small class="text-gray-500"><?php esc_html_e( 'Default: #5B5766', 'digicommerce' ); ?></small>
					</div>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Get options helper
	 */
	public function get_options() {
		$options = array(
			// General Tab.
			'business_name'               => DigiCommerce()->get_option( 'business_name' ),
			'business_vat_number'         => DigiCommerce()->get_option( 'business_vat_number' ),
			'business_country'            => DigiCommerce()->get_option( 'business_country' ),
			'business_address'            => DigiCommerce()->get_option( 'business_address' ),
			'business_address2'           => DigiCommerce()->get_option( 'business_address2' ),
			'business_city'               => DigiCommerce()->get_option( 'business_city' ),
			'business_postal'             => DigiCommerce()->get_option( 'business_postal' ),
			'business_logo'               => DigiCommerce()->get_option( 'business_logo' ),
			'currency'                    => DigiCommerce()->get_option( 'currency', 'USD' ),
			'currency_position'           => DigiCommerce()->get_option( 'currency_position', 'left' ),
			'thousand_sep'                => DigiCommerce()->get_option( 'thousand_sep', ',' ),
			'decimal_sep'                 => DigiCommerce()->get_option( 'decimal_sep', '.' ),
			'num_decimals'                => DigiCommerce()->get_option( 'num_decimals', '2' ),
			'block_admin'                 => DigiCommerce()->get_option( 'block_admin', false ),
			'redirect_login'              => DigiCommerce()->get_option( 'redirect_login', false ),
			'redirect_after_logout'       => DigiCommerce()->get_option( 'redirect_after_logout', '' ),
			'register_form'               => DigiCommerce()->get_option( 'register_form', false ),
			'register_text'               => wp_kses_post(
				DigiCommerce()->get_option(
					'register_text',
					sprintf(
					/* translators: %1$s: Link HTML */
						esc_html__( 'Not a customer yet? %1$sLet\'s get started%2$s', 'digicommerce' ),
						'<a href="#" id="show-register">',
						'</a>',
					)
				)
			),
			'login_text'                  => wp_kses_post(
				DigiCommerce()->get_option(
					'login_text',
					sprintf(
					/* translators: %1$s: Link HTML */
						esc_html__( 'Already a member? %1$sLogin to your account%2$s', 'digicommerce' ),
						'<a href="#" id="show-login">',
						'</a>',
					)
				)
			),
			'invoices_footer'             => wp_kses_post(
				DigiCommerce()->get_option(
					'invoices_footer',
					sprintf(
					/* translators: %1$s: Year placeholder, %2$s: Site name placeholder */
						esc_html__( 'Copyright © %1$s %2$s All rights reserved.', 'digicommerce' ),
						'{year}',
						'{site}'
					)
				)
			),

			// Product Tab.
			'product_slug'                => DigiCommerce()->get_option( 'product_slug', 'digital-product' ),
			'product_cat_slug'            => DigiCommerce()->get_option( 'product_cat_slug', 'digital-product-category' ),
			'product_tag_slug'            => DigiCommerce()->get_option( 'product_tag_slug', 'digital-product-tag' ),
			'product_cpt'                 => DigiCommerce()->get_option( 'product_cpt', false ),

			// Pages Tab.
			'account_page_id'             => DigiCommerce()->get_option( 'account_page_id', '' ),
			'reset_password_page_id'      => DigiCommerce()->get_option( 'reset_password_page_id', '' ),
			'checkout_page_id'            => DigiCommerce()->get_option( 'checkout_page_id', '' ),
			'payment_success_page_id'     => DigiCommerce()->get_option( 'payment_success_page_id', '' ),

			// reCAPTCHA Tab.
			'recaptcha_site_key'          => DigiCommerce()->get_option( 'recaptcha_site_key', '' ),
			'recaptcha_secret_key'        => DigiCommerce()->get_option( 'recaptcha_secret_key', '' ),

			// Payment Tab.
			'stripe_mode'                 => DigiCommerce()->get_option( 'stripe_mode', 'test' ),
			'stripe_test_publishable_key' => DigiCommerce()->get_option( 'stripe_test_publishable_key', '' ),
			'stripe_test_secret_key'      => DigiCommerce()->get_option( 'stripe_test_secret_key', '' ),
			'stripe_live_publishable_key' => DigiCommerce()->get_option( 'stripe_live_publishable_key', '' ),
			'stripe_live_secret_key'      => DigiCommerce()->get_option( 'stripe_live_secret_key', '' ),
			'stripe_webhook_secret'       => DigiCommerce()->get_option( 'stripe_webhook_secret', '' ),
			'paypal_sandbox'              => DigiCommerce()->get_option( 'paypal_sandbox', '0' ),
			'paypal_client_id'            => DigiCommerce()->get_option( 'paypal_client_id', '' ),
			'paypal_secret'               => DigiCommerce()->get_option( 'paypal_secret', '' ),
			'paypal_webhook_id'           => DigiCommerce()->get_option( 'paypal_webhook_id', '' ),

			// Checkout Tab.
			'remove_taxes'                => DigiCommerce()->get_option( 'remove_taxes', false ),
			'login_during_checkout'       => DigiCommerce()->get_option( 'login_during_checkout', false ),
			'minimal_style'               => DigiCommerce()->get_option( 'minimal_style', false ),
			'minimal_fields'              => DigiCommerce()->get_option( 'minimal_fields', false ),
			'order_agreement'             => DigiCommerce()->get_option( 'order_agreement', '' ),
			'modal_terms'                 => DigiCommerce()->get_option( 'modal_terms', '' ),
			'remove_product'              => DigiCommerce()->get_option( 'remove_product', false ),

			// Empty cart Tab.
			'empty_cart_title'            => DigiCommerce()->get_option( 'empty_cart_title', esc_html__( 'Your cart is empty', 'digicommerce' ) ),
			'empty_cart_text'             => DigiCommerce()->get_option( 'empty_cart_text', esc_html__( 'Looks like you haven\'t added any products to your cart yet.', 'digicommerce' ) ),
			'empty_cart_button_text'      => DigiCommerce()->get_option( 'empty_cart_button_text', esc_html__( 'Browse Products', 'digicommerce' ) ),
			'empty_cart_button_url'       => DigiCommerce()->get_option( 'empty_cart_button_url', esc_url( get_home_url() ) ),

			// Emails Tab.
			'email_from_name'             => DigiCommerce()->get_option( 'email_from_name', esc_html( get_bloginfo( 'name' ) ) ),
			'email_from_address'          => DigiCommerce()->get_option( 'email_from_address', esc_html( get_bloginfo( 'admin_email' ) ) ),
			'email_header_logo'           => DigiCommerce()->get_option( 'email_header_logo', '' ),
			'email_header_logo_width'     => DigiCommerce()->get_option( 'email_header_logo_width', '' ),
			'email_new_account'           => DigiCommerce()->get_option( 'email_new_account' ),
			'email_order_confirmation'    => DigiCommerce()->get_option( 'email_order_confirmation' ),
			'email_order_cancelled'       => DigiCommerce()->get_option( 'email_order_cancelled' ),
			'email_order_refunded'        => DigiCommerce()->get_option( 'email_order_refunded' ),
			'email_new_order_admin'       => DigiCommerce()->get_option( 'email_new_order_admin' ),
			'email_footer_text'           => wp_kses_post(
				DigiCommerce()->get_option(
					'email_footer_text',
					sprintf(
						/* translators: %1$s: Year placeholder, %2$s: Site name placeholder */
						__( '<p>Copyright © %1$s %2$s All rights reserved.<br>You received this message because you\'re a member of %2$s.</p> <p>Our mailing address is:<br>%2$s - 48 Street address - City, 1022 Country</p>', 'digicommerce' ),
						'{year}',
						'{site}'
					)
				)
			),
			'show_social_links_in_email'  => DigiCommerce()->get_option( 'show_social_links_in_email', false ),
			'social_links'                => DigiCommerce()->get_option( 'social_links', array() ),

			// Styling Tab.
			'disable_styling'             => DigiCommerce()->get_option( 'disable_styling' ),
			'color_gold'                  => DigiCommerce()->get_option( 'color_gold', '' ),
			'color_yellow'                => DigiCommerce()->get_option( 'color_yellow', '' ),
			'color_border'                => DigiCommerce()->get_option( 'color_border', '' ),
			'color_light_blue'            => DigiCommerce()->get_option( 'color_light_blue', '' ),
			'color_light_blue_bg'         => DigiCommerce()->get_option( 'color_light_blue_bg', '' ),
			'color_dark_blue'             => DigiCommerce()->get_option( 'color_dark_blue', '' ),
			'color_dark_blue_10'          => DigiCommerce()->get_option( 'color_dark_blue_10', '' ),
			'color_dark_blue_20'          => DigiCommerce()->get_option( 'color_dark_blue_20', '' ),
			'color_hover_blue'            => DigiCommerce()->get_option( 'color_hover_blue', '' ),
			'color_grey'                  => DigiCommerce()->get_option( 'color_grey', '' ),
			'color_dark_grey'             => DigiCommerce()->get_option( 'color_dark_grey', '' ),
		);

		return apply_filters( 'digicommerce_get_options', $options );
	}

	/**
	 * Add admin scripts
	 *
	 * @param string $hook The current admin page.
	 */
	public function enqueue_scripts( $hook ) {
		if ( 'toplevel_page_digicommerce-settings' !== $hook ) {
			return;
		}

		wp_enqueue_style(
			'digicommerce-admin',
			DIGICOMMERCE_PLUGIN_URL . 'assets/css/admin/admin.css',
			array(),
			DIGICOMMERCE_VERSION
		);

		wp_enqueue_script(
			'choices',
			DIGICOMMERCE_PLUGIN_URL . 'assets/js/vendor/choices.js',
			array(),
			'11.0.2',
			true
		);

		wp_enqueue_script(
			'digicommerce-admin',
			DIGICOMMERCE_PLUGIN_URL . 'assets/js/admin/options.js',
			array( 'choices', 'wp-color-picker' ),
			DIGICOMMERCE_VERSION,
			true
		);

		wp_localize_script(
			'digicommerce-admin',
			'digiCommerceAdmin',
			array(
				'mediaUploader' => array(
					'title'      => esc_html__( 'Select or Upload Business Logo', 'digicommerce' ),
					'buttonText' => esc_html__( 'Use this image', 'digicommerce' ),
					'removeText' => esc_html__( 'Remove Logo', 'digicommerce' ),
				),
				'socialMedia'   => array(
					'placeholder' => esc_html__( 'profile', 'digicommerce' ),
					'remove'      => esc_html__( 'Remove', 'digicommerce' ),
					'addSocial'   => esc_html__( 'Add Social', 'digicommerce' ),
				),
				'colors'        => array(
					'color_gold'          => array( 'default' => '#CCB161' ),
					'color_yellow'        => array( 'default' => '#FFE599' ),
					'color_border'        => array( 'default' => '#CACED9' ),
					'color_light_blue'    => array( 'default' => '#E1E4ED' ),
					'color_light_blue_bg' => array( 'default' => '#F6F7F9' ),
					'color_dark_blue'     => array( 'default' => '#09053A' ),
					'color_dark_blue_10'  => array( 'default' => '#E6E5EB' ),
					'color_dark_blue_20'  => array( 'default' => '#BAB8C8' ),
					'color_hover_blue'    => array( 'default' => '#362F85' ),
					'color_grey'          => array( 'default' => '#646071' ),
					'color_dark_grey'     => array( 'default' => '#5B5766' ),
				),
			)
		);

		// Then enqueue media
		wp_enqueue_media();
	}

	/**
	 * Customize admin footer
	 *
	 * @param string $text The current footer text.
	 */
	public function footer_text( $text ) {
		$screen = get_current_screen();

		if ( 'toplevel_page_digicommerce-settings' === $screen->id ) {
			$text = sprintf(
				/* translators: %1$s: Plugin review link */
				esc_html__( 'Please rate %2$sDigiCommerce%3$s %4$s&#9733;&#9733;&#9733;&#9733;&#9733;%5$s on %6$sWordPress.org%7$s to help us spread the word.', 'digicommerce' ),
				'https://wordpress.org/support/plugin/digicommerce/reviews/#new-post',
				'<strong>',
				'</strong>',
				'<a href="https://wordpress.org/support/plugin/digicommerce/reviews/#new-post" target="_blank" rel="noopener noreferrer">',
				'</a>',
				'<a href="https://wordpress.org/support/plugin/digicommerce/reviews/#new-post" target="_blank" rel="noopener noreferrer">',
				'</a>'
			);
		}

		return $text;
	}

	/**
	 * Customize admin footer version
	 *
	 * @param string $version The current footer version.
	 */
	public function update_footer( $version ) {
		$screen = get_current_screen();

		if ( 'toplevel_page_digicommerce-settings' === $screen->id ) {
			$name = class_exists( 'DigiCommerce_Pro' ) ? 'DigiCommerce Pro' : 'DigiCommerce';

			$version .= sprintf( ' | %1$s %2$s', $name, DIGICOMMERCE_VERSION );
		}

		return $version;
	}

	/**
	 * Add dir attr to HTML for LTR direction for compatibility with Tailwind
	 *
	 * @param string $lang_attr The current lang attribute.
	 */
	public function attribute_to_html( $lang_attr ) {
		if ( ! is_rtl() ) {
			// Only add dir="ltr" when the site is NOT in RTL mode
			return $lang_attr . ' dir="ltr"';
		}

		return $lang_attr;
	}
}

// Initialize the settings class.
DigiCommerce_Settings::instance();