<?php
defined( 'ABSPATH' ) || exit;

/**
 * Plugin Security Management
 */
class DigiCommerce_Security {
	/**
	 * Unique instance
	 *
	 * @var DigiCommerce_Security
	 */
	private static $instance = null;

	/**
	 * Maximum number of login attempts
	 */
	const MAX_LOGIN_ATTEMPTS = 5;

	/**
	 * Lockout duration in seconds (15 minutes)
	 */
	const LOCKOUT_DURATION = 900;

	/**
	 * Private constructor
	 */
	private function __construct() {
		$this->init_hooks();
	}

	/**
	 * Returns the unique instance
	 */
	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Initialize hooks
	 */
	private function init_hooks() {
		add_filter( 'authenticate', array( $this, 'check_login_attempts' ), 30, 3 );
	}

	/**
	 * Verify reCAPTCHA
	 *
	 * @param string $token reCAPTCHA token.
	 */
	public function verify_recaptcha( $token ) {
		$recaptcha_secret = DigiCommerce()->get_option( 'recaptcha_secret_key' );

		if ( empty( $recaptcha_secret ) ) {
			return true;
		}

		$response = wp_remote_post(
			'https://www.google.com/recaptcha/api/siteverify',
			array(
				'body' => array(
					'secret'   => $recaptcha_secret,
					'response' => $token,
				),
			)
		);

		if ( is_wp_error( $response ) ) {
			return false;
		}

		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body );

		// Check both success and score.
		if ( ! isset( $data->success ) || ! $data->success ) {
			return false;
		}

		// For v3, check the score (0.0 to 1.0).
		if ( ! isset( $data->score ) || $data->score < 0.5 ) { // Google's recommended threshold
			return false;
		}

		return true;
	}

	/**
	 * Check rate limit for a given action
	 *
	 * @param string $action Action name.
	 * @param string $ip IP address.
	 */
	public function check_rate_limit( $action, $ip ) {
		$attempts = get_transient( "digicommerce_{$action}_attempts_{$ip}" );
		return ! $attempts || $attempts < self::MAX_LOGIN_ATTEMPTS;
	}

	/**
	 * Increment rate limit counter
	 *
	 * @param string $action Action name.
	 * @param string $ip IP address.
	 */
	public function increment_rate_limit( $action, $ip ) {
		$transient_value = get_transient( "digicommerce_{$action}_attempts_{$ip}" );
		$attempts        = false !== $transient_value ? $transient_value : 0;
		set_transient(
			"digicommerce_{$action}_attempts_{$ip}",
			$attempts + 1,
			self::LOCKOUT_DURATION
		);
	}

	/**
	 * Reset rate limit counter
	 *
	 * @param string $action Action name.
	 * @param string $ip IP address.
	 */
	public function reset_rate_limit( $action, $ip ) {
		delete_transient( "digicommerce_{$action}_attempts_{$ip}" );
	}

	/**
	 * Check login attempts
	 *
	 * @param WP_User|WP_Error $user User object if login successful, WP_Error otherwise.
	 * @param string           $username Username.
	 * @param string           $password Password.
	 */
	public function check_login_attempts( $user, $username, $password ) {
		if ( empty( $username ) ) {
			return $user;
		}

		$ip       = $this->get_client_ip();
		$attempts = get_transient( 'digicommerce_login_attempts_' . $ip );

		if ( $attempts && $attempts >= self::MAX_LOGIN_ATTEMPTS ) {
			return new WP_Error(
				'too_many_attempts',
				sprintf(
					'Too many login attempts. Please try again in %d minutes.',
					ceil( self::LOCKOUT_DURATION / 60 )
				)
			);
		}

		return $user;
	}

	/**
	 * Handle failed login attempt
	 *
	 * @param string $ip IP address.
	 */
	private function handle_failed_login( $ip ) {
		$attempts = get_transient( 'digicommerce_login_attempts_' . $ip );
		$attempts = $attempts ? $attempts + 1 : 1;

		set_transient(
			'digicommerce_login_attempts_' . $ip,
			$attempts,
			self::LOCKOUT_DURATION
		);

		return $attempts;
	}

	/**
	 * Reset login attempts
	 *
	 * @param string $ip IP address.
	 */
	private function reset_login_attempts( $ip ) {
		delete_transient( 'digicommerce_login_attempts_' . $ip );
	}

	/**
	 * Get client IP securely
	 */
	public function get_client_ip() {
		$ip = '';

		if ( isset( $_SERVER['HTTP_CLIENT_IP'] ) ) {
			$ip = sanitize_text_field( wp_unslash( $_SERVER['HTTP_CLIENT_IP'] ) );
		} elseif ( isset( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
			$ip = sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_FORWARDED_FOR'] ) );
		} elseif ( isset( $_SERVER['REMOTE_ADDR'] ) ) {
			$ip = sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) );
		}

		return filter_var( $ip, FILTER_VALIDATE_IP );
	}

	/**
	 * Check password strength
	 *
	 * @param string $password Password.
	 */
	public function check_password_strength( $password ) {
		$errors = array();

		if ( strlen( $password ) < 8 ) {
			$errors[] = 'Password must contain at least 8 characters';
		}

		if ( ! preg_match( '/[0-9]/', $password ) ) {
			$errors[] = 'Password must contain at least one number';
		}

		if ( ! preg_match( '/[A-Z]/', $password ) ) {
			$errors[] = 'Password must contain at least one uppercase letter';
		}

		if ( ! preg_match( '/[^A-Za-z0-9]/', $password ) ) {
			$errors[] = 'Password must contain at least one special character';
		}

		return empty( $errors ) ? true : $errors;
	}

	/**
	 * Generate strong random password
	 *
	 * @param int $length Password length.
	 */
	public function generate_strong_password( $length = 12 ) {
		$chars    = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()_+-=[]{}|;:,.<>?';
		$password = '';

		for ( $i = 0; $i < $length; $i++ ) {
			$password .= $chars[ random_int( 0, strlen( $chars ) - 1 ) ];
		}

		// Ensure password contains at least one uppercase, one number and one special character
		$password = substr_replace( $password, chr( random_int( 65, 90 ) ), random_int( 0, $length - 1 ), 1 );
		$password = substr_replace( $password, chr( random_int( 48, 57 ) ), random_int( 0, $length - 1 ), 1 );
		$password = substr_replace( $password, '!@#$%^&*()_+-=[]{}|;:,.<>?'[ random_int( 0, 27 ) ], random_int( 0, $length - 1 ), 1 );

		return $password;
	}

	/**
	 * Sanitize form data
	 *
	 * @param string $data Data to sanitize.
	 * @param string $type Data type.
	 */
	public function sanitize_form_data( $data, $type = 'text' ) {
		switch ( $type ) {
			case 'email':
				return sanitize_email( $data );
			case 'url':
				return esc_url_raw( $data );
			case 'int':
				return intval( $data );
			case 'float':
				return floatval( $data );
			case 'html':
				return wp_kses_post( $data );
			default:
				return sanitize_text_field( $data );
		}
	}
}
DigiCommerce_Security::instance();
