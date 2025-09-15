<?php
defined( 'ABSPATH' ) || exit;

/**
 * Handles all account related functionalities with enhanced security
 */
class DigiCommerce_Account {

	/**
	 * Instance of the class
	 *
	 * @var DigiCommerce_Account
	 */
	private static $instance = null;

	/**
	 * Security class instance
	 *
	 * @var DigiCommerce_Security
	 */
	private $security;

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
	 * Initialize the class
	 */
	public function __construct() {
		// AJAX handlers for logged-out users
		add_action( 'wp_ajax_nopriv_digicommerce_login', array( $this, 'handle_login' ) );
		add_action( 'wp_ajax_nopriv_digicommerce_register', array( $this, 'handle_register' ) );
		add_action( 'wp_ajax_nopriv_digicommerce_lost_password', array( $this, 'handle_lost_password' ) );
		add_action( 'wp_ajax_nopriv_digicommerce_reset_password', array( $this, 'handle_reset_password' ) );

		// AJAX handlers for logged-in users
		add_action( 'wp_ajax_digicommerce_update_profile', array( $this, 'handle_update_profile' ) );
		add_action( 'wp_ajax_digicommerce_change_password', array( $this, 'handle_change_password' ) );

		// Page redirections
		add_action( 'init', array( $this, 'handle_login_redirects' ) );
		add_action( 'wp_logout', array( $this, 'handle_logout_redirect' ) );
		add_filter( 'login_url', array( $this, 'custom_login_url' ), 10, 2 );
		add_filter( 'lostpassword_url', array( $this, 'custom_lostpassword_url' ), 10, 2 );

		// Add this to bypass logout confirmation
		add_action( 'login_form_logout', array( $this, 'handle_logout_redirect' ) );

		// Initialize the security instance
		$this->security = DigiCommerce_Security::instance();
	}

	/**
	 * Handle login form submission with rate limiting
	 *
	 * @throws Exception If any error occurs.
	 */
	public function handle_login() {
		try {
			// Verify nonce
			if ( ! check_ajax_referer( 'digicommerce_login_nonce', 'nonce', false ) ) {
				throw new Exception( esc_html__( 'Security check failed.', 'digicommerce' ) );
			}

			// Check rate limiting
			$ip_address = $this->security->get_client_ip();
			if ( ! $this->security->check_rate_limit( 'login', $ip_address ) ) {
				throw new Exception( esc_html__( 'Too many login attempts. Please try again later.', 'digicommerce' ) );
			}

			// Check reCAPTCHA if enabled
			$recaptcha_token = isset( $_POST['recaptcha_token'] ) ? sanitize_text_field( $_POST['recaptcha_token'] ) : ''; // phpcs:ignore
			if ( ! empty( DigiCommerce()->get_option( 'recaptcha_site_key' ) ) ) {
				if ( ! $this->security->verify_recaptcha( $recaptcha_token ) ) {
					$this->security->increment_rate_limit( 'login', $ip_address );
					throw new Exception( esc_html__( 'reCAPTCHA verification failed.', 'digicommerce' ) );
				}
			}

			// Get and sanitize login data
			$username = isset( $_POST['username'] ) ? sanitize_user( $_POST['username'] ) : ''; // phpcs:ignore
			$password = isset( $_POST['password'] ) ? sanitize_text_field( wp_unslash( $_POST['password'] ) ) : '';
			$remember = isset( $_POST['remember'] ) ? (bool) $_POST['remember'] : false;

			if ( empty( $username ) || empty( $password ) ) {
				throw new Exception( esc_html__( 'Please enter both username and password.', 'digicommerce' ) );
			}

			// Attempt login
			$creds = array(
				'user_login'    => $username,
				'user_password' => $password,
				'remember'      => $remember,
			);

			$user = wp_signon( $creds, false );

			if ( is_wp_error( $user ) ) {
				$this->security->increment_rate_limit( 'login', $ip_address );
				throw new Exception( esc_html__( 'Username or password invalid.', 'digicommerce' ) );
			}

			// Important: Set auth cookie and current user
			wp_set_auth_cookie( $user->ID, $remember );
			wp_set_current_user( $user->ID );

			// Reset failed login attempts on successful login
			$this->security->reset_rate_limit( 'login', $ip_address );

			// Check for redirect_to URL first
			$redirect_url = '';
			if ( ! empty( $_POST['redirect_to'] ) ) {
				$redirect_to = esc_url_raw( wp_unslash( $_POST['redirect_to'] ) );
				if ( wp_validate_redirect( $redirect_to ) ) {
					$redirect_url = $redirect_to;
				}
			}

			// If no redirect_to or invalid URL, use default redirects
			if ( empty( $redirect_url ) ) {
				if ( current_user_can( 'manage_options' ) ) { // phpcs:ignore
					$redirect_url = admin_url();
				} else {
					$redirect_url = get_permalink( DigiCommerce()->get_option( 'account_page_id' ) );
				}
			}

			wp_send_json_success(
				array(
					'message'      => esc_html__( 'Login successful. Redirecting...', 'digicommerce' ),
					'redirect_url' => $redirect_url,
				)
			);

		} catch ( Exception $e ) {
			wp_send_json_error(
				array(
					'message' => $e->getMessage(),
				)
			);
		}
	}

	/**
	 * Handle register form submission
	 *
	 * @throws Exception If any error occurs.
	 */
	public function handle_register() {
		if ( ! DigiCommerce()->get_option( 'register_form' ) ) {
			return;
		}

		try {
			// Verify nonce
			if ( ! check_ajax_referer( 'digicommerce_register_nonce', 'nonce', false ) ) {
				throw new Exception( esc_html__( 'Security check failed.', 'digicommerce' ) );
			}

			// Check reCAPTCHA if enabled
			$recaptcha_token = isset( $_POST['recaptcha_token'] ) ? sanitize_text_field( $_POST['recaptcha_token'] ) : ''; // phpcs:ignore
			if ( ! empty( DigiCommerce()->get_option( 'recaptcha_site_key' ) ) ) {
				if ( ! $this->security->verify_recaptcha( $recaptcha_token ) ) {
					throw new Exception( esc_html__( 'reCAPTCHA verification failed.', 'digicommerce' ) );
				}
			}

			// Get and sanitize registration data
			$email           = isset( $_POST['email'] ) ? sanitize_email( $_POST['email'] ) : ''; // phpcs:ignore
			$username        = isset( $_POST['username'] ) ? sanitize_user( $_POST['username'] ) : ''; // phpcs:ignore
			$password        = isset( $_POST['password'] ) ? $_POST['password'] : ''; // phpcs:ignore
			$password_repeat = isset( $_POST['password_repeat'] ) ? $_POST['password_repeat'] : ''; // phpcs:ignore

			// Validate fields
			if ( empty( $email ) || empty( $username ) || empty( $password ) || empty( $password_repeat ) ) {
				throw new Exception( esc_html__( 'Please fill in all fields.', 'digicommerce' ) );
			}

			// Validate email
			if ( ! is_email( $email ) ) {
				throw new Exception( esc_html__( 'Invalid email address.', 'digicommerce' ) );
			}

			// Check if email exists
			if ( email_exists( $email ) ) {
				throw new Exception( esc_html__( 'This email is already registered.', 'digicommerce' ) );
			}

			// Check if username exists
			if ( username_exists( $username ) ) {
				throw new Exception( esc_html__( 'This username is already taken.', 'digicommerce' ) );
			}

			// Validate password match
			if ( $password !== $password_repeat ) {
				throw new Exception( esc_html__( 'Passwords do not match.', 'digicommerce' ) );
			}

			// Validate password strength
			if ( strlen( $password ) < 8 ) {
				throw new Exception( esc_html__( 'Password must be at least 8 characters long.', 'digicommerce' ) );
			}

			// Create user
			$user_id = wp_create_user( $username, $password, $email );

			if ( is_wp_error( $user_id ) ) {
				throw new Exception( $user_id->get_error_message() );
			}

			// Log the user in
			$creds = array(
				'user_login'    => $username,
				'user_password' => $password,
				'remember'      => true,
			);

			$user = wp_signon( $creds, false );

			if ( is_wp_error( $user ) ) {
				throw new Exception( esc_html__( 'Error logging in after registration.', 'digicommerce' ) );
			}

			// Set auth cookie
			wp_set_auth_cookie( $user->ID, true );
			wp_set_current_user( $user->ID );

			// Determine redirect URL
			$redirect_url = get_permalink( DigiCommerce()->get_option( 'account_page_id' ) );
			if ( ! $redirect_url ) {
				$redirect_url = home_url();
			}

			wp_send_json_success(
				array(
					'message'      => esc_html__( 'Registration successful! Redirecting...', 'digicommerce' ),
					'redirect_url' => $redirect_url,
				)
			);

		} catch ( Exception $e ) {
			wp_send_json_error(
				array(
					'message' => $e->getMessage(),
				)
			);
		}
	}

	/**
	 * Handle lost password request with rate limiting
	 *
	 * @throws Exception If any error occurs.
	 */
	public function handle_lost_password() {
		try {
			// Verify nonce
			if ( ! check_ajax_referer( 'digicommerce_lost_password_nonce', 'nonce', false ) ) {
				throw new Exception( esc_html__( 'Security check failed.', 'digicommerce' ) );
			}

			// Check rate limiting
			$ip_address = $this->security->get_client_ip();
			if ( ! $this->security->check_rate_limit( 'password_reset', $ip_address ) ) {
				throw new Exception( esc_html__( 'Too many password reset attempts. Please try again later.', 'digicommerce' ) );
			}

			// Check reCAPTCHA if enabled
			$recaptcha_token = isset( $_POST['recaptcha_token'] ) ? sanitize_text_field( $_POST['recaptcha_token'] ) : ''; // phpcs:ignore
			if ( ! empty( DigiCommerce()->get_option( 'recaptcha_site_key' ) ) ) {
				if ( ! $this->security->verify_recaptcha( $recaptcha_token ) ) {
					throw new Exception( esc_html__( 'reCAPTCHA verification failed.', 'digicommerce' ) );
				}
			}

			$user_email = isset( $_POST['user_email'] ) ? sanitize_user( $_POST['user_email'] ) : ''; // phpcs:ignore

			if ( empty( $user_email ) ) {
				throw new Exception( esc_html__( 'Please enter an email address.', 'digicommerce' ) );
			}

			if ( strpos( $user_email, '@' ) ) {
				$user = get_user_by( 'email', sanitize_email( $user_email ) );
			} else {
				$user = get_user_by( 'login', $user_email );
			}

			if ( ! $user ) {
				$this->security->increment_rate_limit( 'password_reset', $ip_address );
				throw new Exception( esc_html__( 'Invalid email address.', 'digicommerce' ) );
			}

			// Get password reset key
			$key = get_password_reset_key( $user );
			if ( is_wp_error( $key ) ) {
				throw new Exception( esc_html__( 'Error generating password reset link.', 'digicommerce' ) );
			}

			// Send lost password email
			DigiCommerce_Emails::instance()->send_password_reset( $user->user_email, $key, $user->user_login );

			// Reset the rate limit counter on successful email send
			$this->security->reset_rate_limit( 'password_reset', $ip_address );

			wp_send_json_success(
				array(
					'message'      => esc_html__( 'Check your email for the confirmation link.', 'digicommerce' ),
					'redirect_url' => null,
				)
			);

		} catch ( Exception $e ) {
			wp_send_json_error(
				array(
					'message' => $e->getMessage(),
				)
			);
		}
	}

	/**
	 * Handle password reset with enhanced security
	 *
	 * @throws Exception If any error occurs.
	 */
	public function handle_reset_password() {
		try {
			// Verify nonce
			if ( ! check_ajax_referer( 'digicommerce_reset_password_nonce', 'nonce', false ) ) {
				throw new Exception( esc_html__( 'Security check failed.', 'digicommerce' ) );
			}

			$rp_key   = isset( $_POST['rp_key'] ) ? sanitize_text_field( wp_unslash( $_POST['rp_key'] ) ) : '';
			$rp_login = isset( $_POST['rp_login'] ) ? sanitize_user( wp_unslash( $_POST['rp_login'] ) ) : '';
			$password = isset( $_POST['password'] ) ? sanitize_text_field( wp_unslash( $_POST['password'] ) ) : '';

			// Check if reset link has expired
			$expires = isset( $_POST['expires'] ) ? (int) $_POST['expires'] : 0;
			if ( $expires < time() ) {
				throw new Exception( esc_html__( 'This password reset link has expired. Please request a new one.', 'digicommerce' ) );
			}

			// Validate password strength
			$password_strength = $this->security->check_password_strength( $password );
			if ( is_array( $password_strength ) ) {
				throw new Exception( implode( ' ', $password_strength ) );
			}

			// Verify key and login
			$user = check_password_reset_key( $rp_key, $rp_login );
			if ( is_wp_error( $user ) ) {
				$this->invalidate_reset_key( $user, $rp_key );
				throw new Exception( esc_html__( 'This password reset link is invalid or has expired.', 'digicommerce' ) );
			}

			// Reset password
			reset_password( $user, $password );

			// Invalidate all existing password reset keys
			$this->invalidate_reset_key( $user, $rp_key );

			// Log the password change
			$this->log_security_event( 'password_reset', $user->ID );

			wp_send_json_success(
				array(
					'message'      => esc_html__( 'Your password has been reset. Redirecting to login...', 'digicommerce' ),
					'redirect_url' => get_permalink( DigiCommerce()->get_option( 'account_page_id' ) ),
				)
			);

		} catch ( Exception $e ) {
			wp_send_json_error(
				array(
					'message' => $e->getMessage(),
				)
			);
		}
	}

	/**
	 * Invalidate password reset key
	 *
	 * @param WP_User $user User object.
	 * @param string  $key  Reset key.
	 */
	private function invalidate_reset_key( $user, $key ) {
		global $wpdb;
		$hash = time() . wp_generate_password( 20, false );
		$wpdb->update( // phpcs:ignore
			$wpdb->users,
			array( 'user_activation_key' => '' ),
			array( 'user_login' => $user->user_login )
		);
	}

	/**
	 * Log security events
	 *
	 * @param string $event_type Event type.
	 * @param int    $user_id    User ID.
	 */
	private function log_security_event( $event_type, $user_id ) {
		$security_log = array(
			'event_type' => sanitize_text_field( $event_type ),
			'user_id'    => absint( $user_id ),
			'ip_address' => $this->security->get_client_ip(),
			'timestamp'  => current_time( 'mysql' ),
			'user_agent' => isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( $_SERVER['HTTP_USER_AGENT'] ) : '', // phpcs:ignore
		);

		do_action( 'digicommerce_security_event_logged', $security_log );
	}

	/**
	 * Handle account information update
	 *
	 * @throws Exception If any error occurs.
	 */
	public function handle_update_profile() {
		try {
			// Verify nonce
			if ( ! check_ajax_referer( 'digicommerce_update_profile_nonce', 'nonce', false ) ) {
				throw new Exception( esc_html__( 'Security check failed.', 'digicommerce' ) );
			}

			if ( ! is_user_logged_in() ) {
				throw new Exception( esc_html__( 'You must be logged in to update your profile.', 'digicommerce' ) );
			}

			$user_id = get_current_user_id();

			// First update WordPress core user data
			$userdata = array(
				'ID'         => $user_id,
				'first_name' => sanitize_text_field( $_POST['billing_first_name'] ), // phpcs:ignore
				'last_name'  => sanitize_text_field( $_POST['billing_last_name'] ), // phpcs:ignore
				'user_email' => sanitize_email( $_POST['billing_email'] ), // phpcs:ignore
			);

			$user_id = wp_update_user( $userdata );
			if ( is_wp_error( $user_id ) ) {
				throw new Exception( $user_id->get_error_message() );
			}

			// Create a mapping of user meta fields to order table fields
			$field_mapping = array(
				'billing_first_name' => 'first_name',
				'billing_last_name'  => 'last_name',
				'billing_email'      => 'email',
				'billing_phone'      => 'phone',
				'billing_company'    => 'company',
				'billing_address'    => 'address',
				'billing_postcode'   => 'postcode',
				'billing_city'       => 'city',
				'billing_state'      => 'state',
				'billing_country'    => 'country',
				'billing_vat_number' => 'vat_number',
			);

			// Initialize arrays
			$billing_fields     = array();
			$order_billing_data = array();

			// Populate both arrays using the mapping
			foreach ( $field_mapping as $meta_key => $order_key ) {
				$sanitized_value = 'billing_email' === $meta_key
					? sanitize_email( $_POST[ $meta_key ] ) // phpcs:ignore
					: sanitize_text_field( $_POST[ $meta_key ] ); // phpcs:ignore

				$billing_fields[ $meta_key ]      = $sanitized_value;
				$order_billing_data[ $order_key ] = $sanitized_value;
			}

			// Update user meta
			foreach ( $billing_fields as $key => $value ) {
				update_user_meta( $user_id, $key, $value );
			}

			// Get all orders for this user and update them
			global $wpdb;
			$orders = $wpdb->get_results( // phpcs:ignore
				$wpdb->prepare(
					"SELECT id FROM {$wpdb->prefix}digicommerce_orders WHERE user_id = %d",
					$user_id
				),
				ARRAY_A
			);

			// Update billing details for each order
			if ( ! empty( $orders ) ) {
				foreach ( $orders as $order ) {
					$wpdb->update( // phpcs:ignore
						$wpdb->prefix . 'digicommerce_order_billing',
						$order_billing_data,
						array( 'order_id' => $order['id'] ),
						array_fill( 0, count( $order_billing_data ), '%s' ),
						array( '%d' )
					);
				}
			}

			wp_send_json_success(
				array(
					'message' => esc_html__( 'Your information has been successfully updated.', 'digicommerce' ),
				)
			);

		} catch ( Exception $e ) {
			wp_send_json_error(
				array(
					'message' => $e->getMessage(),
				)
			);
		}
	}

	/**
	 * Handle password change for logged-in users
	 *
	 * @throws Exception If any error occurs.
	 */
	public function handle_change_password() {
		try {
			// Verify nonce
			if ( ! check_ajax_referer( 'digicommerce_change_password_nonce', 'nonce', false ) ) {
				throw new Exception( esc_html__( 'Security check failed.', 'digicommerce' ) );
			}

			if ( ! is_user_logged_in() ) {
				throw new Exception( esc_html__( 'You must be logged in to change your password.', 'digicommerce' ) );
			}

			$user             = wp_get_current_user();
			$current_password = isset( $_POST['current_password'] ) ? $_POST['current_password'] : ''; // phpcs:ignore
			$new_password     = isset( $_POST['new_password'] ) ? $_POST['new_password'] : ''; // phpcs:ignore

			// Verify current password
			if ( ! wp_check_password( $current_password, $user->user_pass, $user->ID ) ) {
				throw new Exception( esc_html__( 'Current password is incorrect.', 'digicommerce' ) );
			}

			// Validate new password strength
			$password_strength = $this->security->check_password_strength( $new_password );
			if ( is_array( $password_strength ) ) {
				throw new Exception( implode( ' ', $password_strength ) );
			}

			// Update password
			wp_set_password( $new_password, $user->ID );

			// Log the user back in
			wp_set_auth_cookie( $user->ID );

			wp_send_json_success(
				array(
					'message' => esc_html__( 'Password updated successfully.', 'digicommerce' ),
				)
			);

		} catch ( Exception $e ) {
			wp_send_json_error(
				array(
					'message' => $e->getMessage(),
				)
			);
		}
	}

	/**
	 * Handle login page redirections
	 */
	public function handle_login_redirects() {
		// Don't redirect AJAX requests
		if ( wp_doing_ajax() ) {
			return;
		}

		$current_url = $_SERVER['REQUEST_URI']; // phpcs:ignore

		// Handle wp-login.php access
		if ( strpos( $current_url, 'wp-login.php' ) !== false ) {
			// Don't interfere with logout
			if ( isset( $_GET['action'] ) && 'logout' === $_GET['action'] ) { // phpcs:ignore
				return;
			}

			$redirect_to = get_permalink( DigiCommerce()->get_option( 'account_page_id' ) );
			wp_safe_redirect( $redirect_to );
			exit;
		}

		// Redirect non-admins from wp-admin (but allow WordPress core endpoints)
		if ( strpos( $current_url, '/wp-admin' ) !== false &&
			! ( current_user_can( 'manage_options' ) ||
				current_user_can( 'edit_posts' ) ||
				current_user_can( 'edit_pages' ) )
			) {
			
			// Allow WordPress core endpoints that frontend needs
			$allowed_endpoints = array(
				'/wp-admin/admin-post.php',  // Frontend form processing
				'/wp-admin/admin-ajax.php',  // AJAX requests
				'/wp-admin/async-upload.php' // File uploads
			);
			
			foreach ( $allowed_endpoints as $endpoint ) {
				if ( strpos( $current_url, $endpoint ) !== false ) {
					return; // Allow access to these endpoints
				}
			}
			
			// Block access to actual admin pages
			wp_safe_redirect( home_url() );
			exit;
		}
	}

	/**
	 * Handle logout redirect with security measures
	 */
	public function handle_logout_redirect() {
		global $pagenow;

		// Get redirect URL from options or default to account page
		$redirect_page_id = DigiCommerce()->get_option( 'redirect_after_logout' );
		$redirect_url     = $redirect_page_id ? get_permalink( $redirect_page_id ) : get_permalink( DigiCommerce()->get_option( 'account_page_id' ) );

		// Clear all auth cookies
		wp_clear_auth_cookie();

		// Destroy session
		if ( session_status() === PHP_SESSION_ACTIVE ) {
			session_destroy();
		}

		// Log the logout
		if ( is_user_logged_in() ) {
			$this->log_security_event( 'logout', get_current_user_id() );
		}

		// Remove the WordPress logout confirmation
		if ( 'wp-login.php' === $pagenow && isset( $_GET['action'] ) && 'logout' === $_GET['action'] ) { // phpcs:ignore
			wp_safe_redirect( $redirect_url );
			exit;
		}
	}

	/**
	 * Customize login URL
	 *
	 * @param string $login_url Login URL.
	 * @param string $redirect  Redirect URL.
	 */
	public function custom_login_url( $login_url, $redirect ) {
		return get_permalink( DigiCommerce()->get_option( 'account_page_id' ) );
	}

	/**
	 * Customize lost password URL
	 *
	 * @param string $lostpassword_url Lost password URL.
	 * @param string $redirect         Redirect URL.
	 */
	public function custom_lostpassword_url( $lostpassword_url, $redirect ) {
		return get_permalink( DigiCommerce()->get_option( 'reset_password_page_id' ) );
	}
}
DigiCommerce_Account::instance();
