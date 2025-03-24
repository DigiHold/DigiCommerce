<?php
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
			// Preserve action (reset password, register, etc.) as parameter
			if ( isset( $_GET['action'] ) ) {
				$redirect_url = add_query_arg( 'action', $_GET['action'], $redirect_url );
			}

			// Preserve redirect parameter if it exists
			if ( isset( $_GET['redirect_to'] ) ) {
				$redirect_url = add_query_arg( 'redirect_to', urlencode( $_GET['redirect_to'] ), $redirect_url );
			}

			wp_safe_redirect( $redirect_url );
			exit();
		}

		// Handle wp-admin redirect for non-logged users
		if ( is_admin() && ! is_user_logged_in() && ( ! defined( 'DOING_AJAX' ) || ! DOING_AJAX ) ) {
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

		return add_query_arg( 'action', 'lostpassword', $account_url );
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

		return add_query_arg( 'action', 'register', $account_url );
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
