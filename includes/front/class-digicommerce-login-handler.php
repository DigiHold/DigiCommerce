<?php
defined( 'ABSPATH' ) || exit;

/**
 * Handles DigiCommerce login/admin redirections
 */
class DigiCommerce_Login_Handler {
	/**
	 * Instance of the class
	 *
	 * @var DigiCommerce_Login_Handler
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
		// Always add logout redirect
		add_action( 'wp_logout', array( $this, 'handle_logout_redirect' ), 20 );

		if ( DigiCommerce()->get_option( 'redirect_login' ) ) {
			// Trigger early to intercept wp-login.php requests
			add_action( 'init', array( $this, 'handle_login_redirect' ), 1 );

			// Modify login/reset form URLs
			add_filter( 'lostpassword_url', array( $this, 'redirect_lostpassword_url' ), 10, 2 );
			add_filter( 'register_url', array( $this, 'redirect_register_url' ) );
			add_filter( 'login_url', array( $this, 'redirect_login_url' ), 10, 3 );
		}
	}

	/**
	 * Handle WordPress login pages redirection
	 */
	public function handle_login_redirect() {
		global $pagenow;

		// List of pages to redirect
		$restricted_pages = array( 'wp-login.php', 'wp-register.php' );

		// If user is already logged in and trying to access wp-admin, let them through
		if ( is_admin() && is_user_logged_in() ) {
			return;
		}

		// Don't redirect if this is a logout request
		if ( 'wp-login.php' === $pagenow && isset( $_GET['action'] ) && 'logout' === $_GET['action'] ) {
			return;
		}

		// Get My Account page URL
		$account_page_id = DigiCommerce()->get_option( 'account_page_id' );
		$redirect_url    = $account_page_id ? get_permalink( $account_page_id ) : home_url();

		// If trying to access a restricted page
		if ( in_array( $pagenow, $restricted_pages ) ) {
			// Validate nonce when processing sensitive actions or redirects
			$valid_nonce = isset( $_GET['digicommerce_redirect_nonce'] ) && wp_verify_nonce( sanitize_key( $_GET['digicommerce_redirect_nonce'] ), 'digicommerce_login_redirect' );

			// Only allow action parameter if valid nonce or if it's a standard WordPress action
			$process_action = $valid_nonce || ( isset( $_GET['action'] ) && in_array( $_GET['action'], array( 'logout' ) ) );

			// Preserve action (reset password, register, etc.) as parameter
			if ( $process_action && isset( $_GET['action'] ) ) { // phpcs:ignore
				// Sanitize the action parameter
				$action = sanitize_key( $_GET['action'] ); // phpcs:ignore

				// Validate the action against allowed values
				$allowed_actions = array( 'register', 'lostpassword', 'rp', 'resetpass' );
				if ( in_array( $action, $allowed_actions ) ) {
					$redirect_url = add_query_arg( 'action', $action, $redirect_url );
				}
			}

			// Only process redirect parameter if nonce is valid
			if ( $valid_nonce && isset( $_GET['redirect_to'] ) ) { // phpcs:ignore
				// Sanitize the redirect URL - ensure it's a local URL to prevent open redirect vulnerabilities
				$redirect_to = esc_url_raw( wp_unslash( $_GET['redirect_to'] ) ); // phpcs:ignore

				// Only use the redirect if it's to the same site
				if ( wp_validate_redirect( $redirect_to ) ) {
					$redirect_url = add_query_arg( 'redirect_to', urlencode( $redirect_to ), $redirect_url );
				}
			}

			// Add nonce to final redirect URL
			$redirect_url = add_query_arg( 'digicommerce_redirect_nonce', wp_create_nonce( 'digicommerce_login_redirect' ), $redirect_url );

			wp_safe_redirect( $redirect_url );
			exit();
		}

		// Handle wp-admin redirect for non-logged users
		if ( is_admin() && ! is_user_logged_in() && ( ! defined( 'DOING_AJAX' ) || ! DOING_AJAX ) ) {
			// Add nonce to redirect URL
			$redirect_url = add_query_arg( 'digicommerce_redirect_nonce', wp_create_nonce( 'digicommerce_login_redirect' ), $redirect_url );

			wp_safe_redirect( $redirect_url );
			exit();
		}
	}

	/**
	 * Modify lost password form URL
	 *
	 * @param string $url URL to the lost password page.
	 * @param string $redirect URL to redirect to.
	 */
	public function redirect_lostpassword_url( $url, $redirect ) {
		// Get My Account page URL
		$account_page_id = DigiCommerce()->get_option( 'account_page_id' );
		$account_url     = $account_page_id ? get_permalink( $account_page_id ) : home_url();

		$account_url = add_query_arg( 'action', 'lostpassword', $account_url );

		// Add nonce for security
		$account_url = add_query_arg( 'digicommerce_redirect_nonce', wp_create_nonce( 'digicommerce_login_redirect' ), $account_url );

		return $account_url;
	}

	/**
	 * Modify registration form URL
	 *
	 * @param string $url URL to the registration page.
	 */
	public function redirect_register_url( $url ) {
		// Get My Account page URL
		$account_page_id = DigiCommerce()->get_option( 'account_page_id' );
		$account_url     = $account_page_id ? get_permalink( $account_page_id ) : home_url();

		$account_url = add_query_arg( 'action', 'register', $account_url );

		// Add nonce for security
		$account_url = add_query_arg( 'digicommerce_redirect_nonce', wp_create_nonce( 'digicommerce_login_redirect' ), $account_url );

		return $account_url;
	}

	/**
	 * Modify login form URL
	 *
	 * @param string $url URL to the login page.
	 * @param string $redirect URL to redirect to.
	 * @param bool   $force_reauth Whether to force reauthentication.
	 */
	public function redirect_login_url( $url, $redirect, $force_reauth ) {
		// Get My Account page URL
		$account_page_id = DigiCommerce()->get_option( 'account_page_id' );
		$account_url     = $account_page_id ? get_permalink( $account_page_id ) : home_url();

		if ( $redirect ) {
			$account_url = add_query_arg( 'redirect_to', urlencode( $redirect ), $account_url );
		}

		if ( $force_reauth ) {
			$account_url = add_query_arg( 'reauth', '1', $account_url );
		}

		// Add nonce for security
		$account_url = add_query_arg( 'digicommerce_redirect_nonce', wp_create_nonce( 'digicommerce_login_redirect' ), $account_url );

		return $account_url;
	}

	/**
	 * Handle logout redirection
	 */
	public function handle_logout_redirect() {
		// Get the selected page ID from options
		$redirect_page_id = DigiCommerce()->get_option( 'redirect_after_logout' );
		$account_page_id  = DigiCommerce()->get_option( 'account_page_id' );

		if ( $redirect_page_id ) {
			// If a specific logout page is set, redirect there
			wp_safe_redirect( get_permalink( $redirect_page_id ) );
		} else {
			// Otherwise redirect to account page or home if no account page
			wp_safe_redirect( $account_page_id ? get_permalink( $account_page_id ) : home_url() );
		}
		exit();
	}
}
DigiCommerce_Login_Handler::instance();
