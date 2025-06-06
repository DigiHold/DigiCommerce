<?php
defined( 'ABSPATH' ) || exit;

/**
 * Checkout class for DigiCommerce
 */
class DigiCommerce_Checkout {
	/**
	 * The single instance of the class
	 *
	 * @var DigiCommerce_Checkout
	 */
	private static $instance = null;

	/**
	 * Cart items
	 *
	 * @var array
	 */
	private $cart_items = array();

	/**
	 * Session cookie name
	 *
	 * @var string
	 */
	private $_cookie;

	/**
	 * Flag to indicate if a session cookie is set
	 *
	 * @var bool
	 */
	private $_has_cookie = false;

	/**
	 * Instance
	 *
	 * @return DigiCommerce_Checkout - Main instance
	 */
	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor
	 */
	private function __construct() {
		$this->_cookie = 'digicommerce_session_' . COOKIEHASH;

		// Only initialize session on specific pages or actions
		if ( $this->should_start_session() ) {
			add_action( 'init', array( $this, 'init_session' ), 5 );
		}

		add_action( 'shutdown', array( $this, 'maybe_set_cart_cookies' ), 0 );

		add_action( 'init', array( $this, 'init' ) );
		add_action( 'wp_ajax_digicommerce_add_to_cart', array( $this, 'add_to_cart' ) );
		add_action( 'wp_ajax_nopriv_digicommerce_add_to_cart', array( $this, 'add_to_cart' ) );
		add_action( 'wp_ajax_digicommerce_process_checkout', array( $this, 'process_checkout' ) );
		add_action( 'wp_ajax_nopriv_digicommerce_process_checkout', array( $this, 'process_checkout' ) );

		if ( DigiCommerce()->get_option( 'login_during_checkout' ) ) :
			add_action( 'wp_ajax_digicommerce_login_checkout', array( $this, 'login_checkout' ) );
			add_action( 'wp_ajax_nopriv_digicommerce_login_checkout', array( $this, 'login_checkout' ) );
		endif;

		if ( ! DigiCommerce()->get_option( 'remove_product' ) ) :
			add_action( 'wp_ajax_digicommerce_remove_cart_item', array( $this, 'remove_cart_item' ) );
			add_action( 'wp_ajax_nopriv_digicommerce_remove_cart_item', array( $this, 'remove_cart_item' ) );
		endif;

		// Add modal to footer
		if ( ! empty( DigiCommerce()->get_option( 'modal_terms', '' ) ) ) :
			add_action( 'wp_footer', array( $this, 'modal' ) );
		endif;

		// Add handlers for user login/logout to get/remove sessions
		add_action( 'wp_login', array( $this, 'handle_user_login' ), 10, 2 );
		add_action( 'wp_logout', array( $this, 'handle_user_logout' ) );

		// Add cleanup schedule if not already scheduled
		if ( ! wp_next_scheduled( 'digicommerce_cleanup_sessions' ) ) {
			wp_schedule_event( time(), 'daily', 'digicommerce_cleanup_sessions' );
		}
		add_action( 'digicommerce_cleanup_sessions', array( $this, 'cleanup_expired_sessions' ) );
	}

	/**
	 * Start session if necessary
	 *
	 * @return array
	 */
	private function should_start_session() {
		// Always start session for AJAX requests
		if ( wp_doing_ajax() ) {
			return true;
		}

		// Start session for checkout page
		if ( DigiCommerce()->is_checkout_page() ) {
			return true;
		}

		// Start session if we have order parameters (success page)
		if ( isset( $_GET['order_id'] ) && isset( $_GET['token'] ) ) { // phpcs:ignore
			return true;
		}

		// Start session for add to cart actions
		if ( isset( $_GET['add-to-cart'] ) ) { // phpcs:ignore
			return true;
		}

		// Check if there's an existing valid session
		if ( isset( $_COOKIE[ $this->_cookie ] ) ) {
			$session_key = sanitize_text_field( $_COOKIE[ $this->_cookie ] ); // phpcs:ignore

			$session_data = $this->get_session( $session_key );
			if ( $session_data ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Initialize session
	 */
	public function init_session() {
		$session_key = $this->get_current_session_key();

		// Get session data
		$session_data = $this->get_session( $session_key );

		// Initialize cart items
		if ( $session_data && isset( $session_data['cart'] ) ) {
			$this->cart_items = $session_data['cart'];
		} else {
			$this->cart_items = array();
			// Create a new session with empty cart for BOTH logged-in and logged-out users
			$this->save_session( $session_key, array( 'cart' => $this->cart_items ) );
			
			// For logged-out users, ensure the session cookie is set
			if ( ! is_user_logged_in() ) {
				$this->set_session_cookie( true, $session_key );
			}
		}

		// Make cart items available globally
		add_filter( 'digicommerce_cart_items', array( $this, 'get_cart_items' ) );
	}

	/**
	 * Initialize
	 */
	public function init() {
		if ( isset( $_GET['add-to-cart'] ) ) {
			// Verify nonce when adding to cart via GET request
			if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( sanitize_key( $_GET['_wpnonce'] ), 'digicommerce_add_to_cart' ) ) {
				wp_die( esc_html__( 'Security check failed.', 'digicommerce' ) );
			}

			$this->add_to_cart( intval( wp_unslash( $_GET['add-to-cart'] ) ) );
			$this->maybe_set_cart_cookies();
		}

		// Check for direct checkout parameters when getting cart items
		$this->maybe_handle_direct_checkout();
	}

	/**
	 * Install tables
	 */
	public function install_tables() {
		global $wpdb;
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$table_name      = $wpdb->prefix . 'digicommerce_sessions';
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE IF NOT EXISTS $table_name (
            session_id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            session_key VARCHAR(64) NOT NULL,
            session_value LONGTEXT NOT NULL,
            session_expiry BIGINT(20) NOT NULL,
            PRIMARY KEY (session_id),
            UNIQUE KEY session_key (session_key)
        ) $charset_collate;";

		dbDelta( $sql );
	}

	/**
	 * Get session cookie
	 */
	private function get_session_cookie() {
		return isset( $_COOKIE[ $this->_cookie ] ) ? sanitize_text_field( $_COOKIE[ $this->_cookie ] ) : false; // phpcs:ignore
	}

	/**
	 * Set session cookie
	 *
	 * @param bool   $force - Force setting the cookie.
	 * @param string $session_key - Session key.
	 */
	public function set_session_cookie( $force = false, $session_key = null ) {
		if ( headers_sent() ) {
			return;
		}

		// Don't set cookie for logged in users
		if ( is_user_logged_in() ) {
			return;
		}

		if ( $force || ! $this->get_session_cookie() ) {
			$key_to_use = $session_key ? $session_key : $this->generate_session_key();

			$secure = apply_filters( 'digicommerce_cookie_secure', is_ssl() );

			setcookie(
				$this->_cookie,
				$key_to_use,
				array(
					'expires'  => time() + 30 * DAY_IN_SECONDS,
					'path'     => COOKIEPATH ? COOKIEPATH : '/',
					'domain'   => COOKIE_DOMAIN,
					'secure'   => $secure,
					'httponly' => true,
					'samesite' => 'Strict',
				)
			);

			$_COOKIE[ $this->_cookie ] = $key_to_use;
			$this->_has_cookie         = true;
		}
	}

	/**
	 * Generate session key
	 */
	private function generate_session_key() {
		$random_string = wp_generate_password( 32, true, true );
		return 't_' . substr( md5( $random_string ), 2 );
	}

	/**
	 * Set cart cookie if necessary
	 */
	public function maybe_set_cart_cookies() {
		if ( ! headers_sent() && did_action( 'wp_loaded' ) ) {
			if ( empty( $this->cart_items ) && isset( $_COOKIE[ $this->_cookie ] ) ) {
				$this->clear_session_cookie();
			}
		}
	}

	/**
	 * Clear session cookie
	 */
	private function clear_session_cookie() {
		if ( headers_sent() ) {
			return;
		}

		setcookie(
			$this->_cookie,
			'',
			array(
				'expires'  => time() - YEAR_IN_SECONDS,
				'path'     => COOKIEPATH ? COOKIEPATH : '/',
				'domain'   => COOKIE_DOMAIN,
				'secure'   => is_ssl(),
				'httponly' => true,
				'samesite' => 'Strict',
			)
		);

		unset( $_COOKIE[ $this->_cookie ] );
		$this->_has_cookie = false;
	}

	/**
	 * Get current session key
	 */
	public function get_current_session_key() {
		// For logged-in users, always use user ID
		if ( is_user_logged_in() ) {
			$key = 'user_' . get_current_user_id();
			return $key;
		}

		// Check for existing cookie
		if ( isset( $_COOKIE[ $this->_cookie ] ) ) {
			$key = sanitize_text_field( $_COOKIE[ $this->_cookie ] ); // phpcs:ignore

			// Return the existing key, even if session data is missing
			return $key;
		}

		// If no cookie exists, generate a new key
		$new_key = $this->generate_session_key();
		$this->set_session_cookie( true, $new_key );

		return $new_key;
	}

	/**
	 * Get session
	 *
	 * @param string $session_key - Session key.
	 */
	public function get_session( $session_key ) {
		global $wpdb;

		$table_name   = $wpdb->prefix . 'digicommerce_sessions';
		$session_data = $wpdb->get_row( // phpcs:ignore
			$wpdb->prepare( "SELECT session_value, session_expiry FROM $table_name WHERE session_key = %s", $session_key ) // phpcs:ignore
		);

		if ( $session_data ) {
			if ( $session_data->session_expiry > time() ) {
				$unserialized = maybe_unserialize( $session_data->session_value );
				return $unserialized;
			}
		}

		return null;
	}

	/**
	 * Save session
	 *
	 * @param string $session_key - Session key.
	 * @param array  $session_value - Session value.
	 */
	public function save_session( $session_key, $session_value ) {
		global $wpdb;

		$table_name     = $wpdb->prefix . 'digicommerce_sessions';
		$session_expiry = time() + 30 * DAY_IN_SECONDS;

		$result = $wpdb->replace( // phpcs:ignore
			$table_name,
			array(
				'session_key'    => $session_key,
				'session_value'  => maybe_serialize( $session_value ),
				'session_expiry' => $session_expiry,
			),
			array( '%s', '%s', '%d' )
		);

		return $result;
	}

	/**
	 * Delete session
	 *
	 * @param string $session_key - Session key.
	 */
	private function delete_session( $session_key ) {
		global $wpdb;

		$table_name = $wpdb->prefix . 'digicommerce_sessions';
		$wpdb->delete( $table_name, array( 'session_key' => $session_key ), array( '%s' ) ); // phpcs:ignore
	}

	/**
	 * Cleanup expired sessions
	 */
	public function cleanup_expired_sessions() {
		global $wpdb;
		$table_name = $wpdb->prefix . 'digicommerce_sessions';

		// Delete expired sessions and empty carts
		$wpdb->query( // phpcs:ignore
			$wpdb->prepare( "DELETE FROM {$table_name} WHERE session_expiry < %d OR ( session_value LIKE %s AND session_key NOT LIKE %s )", // phpcs:ignore
				time(),
				'%' . $wpdb->esc_like( serialize( array( 'cart' => array() ) ) ) . '%', // phpcs:ignore
				'user_%'
			)
		);
	}

	/**
	 * Handle user login
	 *
	 * @param string $user_login - User login.
	 * @param object $user - User object.
	 */
	public function handle_user_login( $user_login, $user ) {
		if ( isset( $_COOKIE[ $this->_cookie ] ) ) {
			$guest_session_key = sanitize_text_field( $_COOKIE[ $this->_cookie ] ); // phpcs:ignore
			$user_session_key  = 'user_' . $user->ID;

			$guest_session = $this->get_session( $guest_session_key );

			if ( $guest_session ) {
				$this->save_session( $user_session_key, $guest_session );
				$this->delete_session( $guest_session_key );
			}

			$this->clear_session_cookie();
		}
	}

	/**
	 * Handle user logout
	 */
	public function handle_user_logout() {
		if ( ! empty( $this->cart_items ) ) {
			$session_key = $this->generate_session_key();
			$this->save_session( $session_key, array( 'cart' => $this->cart_items ) );
			$this->set_session_cookie( true, $session_key );
		}
	}

	/**
	 * Add to cart
	 *
	 * @throws Exception - Exception.
	 */
	public function add_to_cart() {
		try {
			// More lenient nonce check for cart operations
			$nonce_check = check_ajax_referer( 'digicommerce_add_to_cart', 'nonce', false );
        
			// For cart operations, we allow non-logged users but still verify the nonce when possible
			if ( ! $nonce_check && is_user_logged_in() ) {
				wp_send_json_error( array( 'message' => __( 'Security check failed.', 'digicommerce' ) ) );
				return;
			}

			// Ensure session is initialized for AJAX requests
			if ( ! did_action( 'init' ) || empty( $this->cart_items ) ) {
				$this->init_session();
			}

			$product_id      = isset( $_POST['product_id'] ) ? intval( $_POST['product_id'] ) : 0;
			$variation_name  = isset( $_POST['variation_name'] ) ? sanitize_text_field( $_POST['variation_name'] ) : ''; // phpcs:ignore
			$variation_price = isset( $_POST['variation_price'] ) ? floatval( $_POST['variation_price'] ) : 0;

			// Get the product details
			$product = get_post( $product_id );
			if ( ! $product || 'digi_product' !== $product->post_type ) {
				throw new Exception( esc_html__( 'Invalid product.', 'digicommerce' ) );
			}

			// Determine price
			$price = isset( $_POST['product_price'] ) ? floatval( $_POST['product_price'] ) : 0;
			if ( $variation_price ) {
				$price = $variation_price;
			}

			// Get current session and cart data
			$session_key  = $this->get_current_session_key();
			$session_data = $this->get_session( $session_key );

			if ( ! $session_data ) {
				$session_data = array( 'cart' => array() );
			}

			$cart_items     = array();
			$has_variations = ! empty( $variation_name );

			// Process existing cart items
			foreach ( $session_data['cart'] as $cart_item ) {
				if ( $cart_item['product_id'] === $product_id ) {
					if ( $has_variations ) {
						// Skip this product entirely - we'll add the new variation
						continue;
					} else {
						// For non-variation products, if it exists, don't add it again
						wp_send_json_success(
							array(
								'message'  => esc_html__( 'Product already in cart.', 'digicommerce' ),
								'cart'     => $this->cart_items,
								'redirect' => get_permalink( DigiCommerce()->get_option( 'checkout_page_id', '' ) ),
							)
						);
						return;
					}
				}
				// Keep all other products
				$cart_items[] = $cart_item;
			}

			// Get subscription details
			$subscription_data = array();
			if ( $has_variations ) {
				// Get variation subscription settings from price variations
				$price_variations = get_post_meta( $product_id, 'digi_price_variations', true );
				if ( ! empty( $price_variations ) ) {
					foreach ( $price_variations as $variation ) {
						if ( $variation['name'] === $variation_name ) {
							$subscription_data = array(
								'subscription_enabled'    => ! empty( $variation['subscription_enabled'] ),
								'subscription_period'     => $variation['subscription_period'] ?? 'month',
								'subscription_free_trial' => $variation['subscription_free_trial'] ?? array(
									'duration' => 0,
									'period'   => 'days',
								),
								'subscription_signup_fee' => $variation['subscription_signup_fee'] ?? 0,
							);
							break;
						}
					}
				}
			} else {
				// Get regular product subscription settings
				$subscription_data = array(
					'subscription_enabled'    => get_post_meta( $product_id, 'digi_subscription_enabled', true ),
					'subscription_period'     => get_post_meta( $product_id, 'digi_subscription_period', true ),
					'subscription_free_trial' => get_post_meta( $product_id, 'digi_subscription_free_trial', true ),
					'subscription_signup_fee' => get_post_meta( $product_id, 'digi_subscription_signup_fee', true ),
				);
			}

			// Create new cart item
			$new_item = array(
				'product_id'     => $product_id,
				'name'           => $product->post_title,
				'price'          => $price,
				'variation_name' => $variation_name,
			);

			// Add subscription data if enabled
			if ( ! empty( $subscription_data ) && ! empty( $subscription_data['subscription_enabled'] ) ) {
				$new_item['subscription_enabled']    = $subscription_data['subscription_enabled'];
				$new_item['subscription_period']     = $subscription_data['subscription_period'];
				$new_item['subscription_free_trial'] = $subscription_data['subscription_free_trial'];
				$new_item['subscription_signup_fee'] = $subscription_data['subscription_signup_fee'];

				if ( DigiCommerce()->is_paypal_enabled() ) {
					$new_item['needs_paypal_plan'] = true;
				}
			}

			// Add new item to cart
			$cart_items[] = $new_item;

			// Update session data
			$session_data['cart'] = $cart_items;
			$this->cart_items     = $cart_items;

			// Save to session
			$this->save_session( $session_key, $session_data );

			// Get checkout page URL
			$checkout_url = get_permalink( DigiCommerce()->get_option( 'checkout_page_id', '' ) );

			// Determine appropriate success message
			$message = $has_variations ?
				esc_html__( 'Product variation updated in cart.', 'digicommerce' ) :
				esc_html__( 'Product added to cart.', 'digicommerce' );

			wp_send_json_success(
				array(
					'message'  => $message,
					'cart'     => $this->cart_items,
					'redirect' => $checkout_url,
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
	 * Log in during checkout
	 *
	 * @throws Exception - Exception.
	 */
	public function login_checkout() {
		try {
			// Verify nonce
			if ( ! check_ajax_referer( 'digicommerce_login_checkout_nonce', 'nonce', false ) ) {
				throw new Exception( esc_html__( 'Security check failed.', 'digicommerce' ) );
			}

			// Initialize the security instance
			$security = DigiCommerce_Security::instance();

			// Check rate limiting
			$ip_address = $security->get_client_ip();
			if ( ! $security->check_rate_limit( 'login', $ip_address ) ) {
				throw new Exception( esc_html__( 'Too many login attempts. Please try again later.', 'digicommerce' ) );
			}

			// Check reCAPTCHA if enabled
			$recaptcha_token = isset( $_POST['recaptcha_token'] ) ? sanitize_text_field( $_POST['recaptcha_token'] ) : ''; // phpcs:ignore
			if ( ! empty( DigiCommerce()->get_option( 'recaptcha_site_key' ) ) ) {
				if ( ! $security->verify_recaptcha( $recaptcha_token ) ) {
					$security->increment_rate_limit( 'login', $ip_address );
					throw new Exception( esc_html__( 'reCAPTCHA verification failed.', 'digicommerce' ) );
				}
			}

			// Get and sanitize login data
			$username = isset( $_POST['username'] ) ? sanitize_user( $_POST['username'] ) : ''; // phpcs:ignore
			$password = isset( $_POST['password'] ) ? sanitize_text_field( wp_unslash( $_POST['password'] ) ) : '';

			if ( empty( $username ) || empty( $password ) ) {
				throw new Exception( esc_html__( 'Please enter both username and password.', 'digicommerce' ) );
			}

			// Store the guest session key before login
			$guest_session_key = $this->get_current_session_key();
			$guest_session     = $this->get_session( $guest_session_key );

			// Attempt login
			$creds = array(
				'user_login'    => $username,
				'user_password' => $password,
			);

			$user = wp_signon( $creds, false );

			if ( is_wp_error( $user ) ) {
				$security->increment_rate_limit( 'login', $ip_address );
				throw new Exception( esc_html__( 'Username or password invalid.', 'digicommerce' ) );
			}

			// Set auth cookie and current user
			wp_set_auth_cookie( $user->ID );
			wp_set_current_user( $user->ID );

			// Transfer cart from guest session to user session
			if ( $guest_session && isset( $guest_session['cart'] ) ) {
				$user_session_key = 'user_' . $user->ID;

				// Merge with existing user cart if any
				$user_session = $this->get_session( $user_session_key );
				if ( ! $user_session ) {
					$user_session = array( 'cart' => array() );
				}

				// Merge carts avoiding duplicates
				$merged_cart          = $this->merge_carts( $guest_session['cart'], $user_session['cart'] );
				$user_session['cart'] = $merged_cart;

				// Save merged cart to user session
				$this->save_session( $user_session_key, $user_session );

				// Clean up guest session
				$this->delete_session( $guest_session_key );
				$this->clear_session_cookie();
			}

			// Reset failed login attempts
			$security->reset_rate_limit( 'login', $ip_address );

			// Determine redirect URL
			$redirect_url = home_url();
			if ( current_user_can( 'manage_options' ) ) { // phpcs:ignore
				$redirect_url = admin_url();
			} else {
				$redirect_url = get_permalink( DigiCommerce()->get_option( 'checkout_page_id' ) );
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
	 * Merge guest and user carts
	 *
	 * @param array $guest_cart - Guest cart.
	 * @param array $user_cart - User cart.
	 */
	private function merge_carts( $guest_cart, $user_cart ) {
		$merged_cart = $user_cart;

		foreach ( $guest_cart as $guest_item ) {
			$exists = false;
			foreach ( $user_cart as $user_item ) {
				if ( $guest_item['product_id'] === $user_item['product_id'] &&
					$guest_item['variation_name'] === $user_item['variation_name'] ) {
					$exists = true;
					// If subscription data exists, ensure it's merged correctly
					if ( ! empty( $guest_item['subscription_enabled'] ) ) {
						$user_item['subscription_enabled']    = $guest_item['subscription_enabled'];
						$user_item['subscription_period']     = $guest_item['subscription_period'];
						$user_item['subscription_free_trial'] = $guest_item['subscription_free_trial'];
						$user_item['subscription_signup_fee'] = $guest_item['subscription_signup_fee'];
					}
					break;
				}
			}
			if ( ! $exists ) {
				$merged_cart[] = $guest_item;
			}
		}

		return $merged_cart;
	}

	/**
	 * Remove cart item
	 */
	public function remove_cart_item() {
		try {
			// More lenient nonce check for cart operations
			$nonce_check = check_ajax_referer( 'digicommerce_order_nonce', 'nonce', false );
        
			// For cart operations, we allow non-logged users but still verify the nonce when possible
			if ( ! $nonce_check && is_user_logged_in() ) {
				wp_send_json_error( array( 'message' => __( 'Security check failed.', 'digicommerce' ) ) );
				return;
			}
	
			// Ensure session is initialized
			if ( empty( $this->cart_items ) ) {
				$this->init_session();
			}

			// Ensure cart exists
			$session_key  = $this->get_current_session_key();
			$session_data = $this->get_session( $session_key );

			if ( empty( $session_data['cart'] ) || ! is_array( $session_data['cart'] ) ) {
				wp_send_json_error( array( 'message' => esc_html__( 'Cart not initialized or session expired.', 'digicommerce' ) ) );
				return;
			}

			$index = isset( $_POST['index'] ) ? intval( $_POST['index'] ) : -1;

			// Validate index
			if ( $index < 0 || ! array_key_exists( $index, $session_data['cart'] ) ) {
				wp_send_json_error( array( 'message' => esc_html__( 'Invalid cart item.', 'digicommerce' ) ) );
				return;
			}

			// Remove the item and reindex
			unset( $session_data['cart'][ $index ] );
			$session_data['cart'] = array_values( $session_data['cart'] );

			// Save updated cart to the session
			$this->save_session( $session_key, $session_data );

			// Calculate new subtotal
			$new_subtotal = 0;
			foreach ( $session_data['cart'] as $item ) {
				$new_subtotal += floatval( $item['price'] );
			}

			// Get tax rate from session or user data
			$tax_rate = $this->get_session_tax_rate( $session_key );

			// Get class instance
			$product = DigiCommerce_Product::instance();

			// Get discount from session if any
			$session_data    = $this->get_session( $session_key );
			$discount_amount = 0;
			if ( ! empty( $session_data['discount'] ) ) {
				$discount_data = $session_data['discount'];
				if ( 'percentage' === $discount_data['type'] ) {
					// Apply percentage discount to subtotal
					$discount_amount = round( ( $new_subtotal * $discount_data['amount'] ) / 100, 2 );
				} else {
					// Apply fixed discount to subtotal
					$discount_amount = min( $discount_data['amount'], $new_subtotal );
				}
			}

			// Calculate discounted subtotal
			$discounted_subtotal = $new_subtotal - $discount_amount;

			// Calculate VAT on discounted subtotal
			$new_vat = $discounted_subtotal * $tax_rate;

			// Calculate final total
			$new_total = $discounted_subtotal + $new_vat;

			// Format prices
			$formatted_subtotal = $product->format_price( $new_subtotal, 'subtotal-price' );
			$formatted_vat      = $product->format_price( $new_vat, 'vat-price' );
			$formatted_total    = $product->format_price( $new_total, 'total-price' );

			wp_send_json_success(
				array(
					'formatted_prices' => array(
						'subtotal' => $formatted_subtotal,
						'vat'      => $formatted_vat,
						'total'    => $formatted_total,
					),
					'vat_rate'         => $tax_rate,
					'raw_values'       => array(
						'subtotal' => $new_subtotal,
						'vat'      => $new_vat,
						'total'    => $new_total,
					),
				)
			);

		} catch ( Exception $e ) {
			wp_send_json_error(
				array(
					'message' => esc_html__( 'An error occurred while processing your request.', 'digicommerce' ),
					'debug'   => WP_DEBUG ? $e->getMessage() : null,
				)
			);
		}
	}

	/**
	 * Direct checkout handler
	 */
	private function maybe_handle_direct_checkout() {
		// Check for POST upgrade request first
		if ( isset( $_SERVER['REQUEST_METHOD'] ) && 'POST' === $_SERVER['REQUEST_METHOD'] && isset( $_POST['upgrade_license'] ) && isset( $_POST['upgrade_path'] ) ) {
			// Verify nonce
			if ( ! isset( $_POST['upgrade_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['upgrade_nonce'] ) ), 'digicommerce_upgrade_license' ) ) {
				wp_die( 'Security check failed', 'Security Error', array( 'response' => 403 ) );
				return;
			}

			$license_id           = intval( $_POST['upgrade_license'] );
			$upgrade_variation_id = sanitize_text_field( wp_unslash( $_POST['upgrade_path'] ) );

			// Get license details
			$license = DigiCommerce_Pro_License::instance()->get_license_by_id( $license_id );

			if ( ! $license || 'active' !== $license['status'] ) {
				wp_die( 'Invalid license' );
				return;
			}

			// Get upgrade paths and price variations
			$upgrade_paths_value = get_post_meta( $license['product_id'], 'digi_upgrade_paths', true );
			$upgrade_paths       = $upgrade_paths_value ? $upgrade_paths_value : array();

			$price_variations_value = get_post_meta( $license['product_id'], 'digi_price_variations', true );
			$price_variations       = $price_variations_value ? $price_variations_value : array();

			// Find selected upgrade path
			$selected_path = null;
			foreach ( $upgrade_paths as $path ) {
				if ( $path['variation_id'] === $upgrade_variation_id ) {
					$selected_path = $path;
					break;
				}
			}

			if ( ! $selected_path ) {
				return;
			}

			// Find current and target variations
			$current_variation = null;
			$target_variation  = null;

			foreach ( $price_variations as $variation ) {
				if ( strpos( $variation['id'], $license['variation_id'] ) === 0 ) {
					$current_variation = $variation;
				}

				if ( $variation['id'] === $upgrade_variation_id ) {
					$target_variation = $variation;
				}
			}

			if ( ! $current_variation || ! $target_variation ) {
				return;
			}

			global $wpdb;

			// Get the original order amount from completed order
			$original_payment = $wpdb->get_var( $wpdb->prepare( "SELECT subtotal FROM {$wpdb->prefix}digicommerce_orders WHERE id = %d AND status = 'completed'", $license['order_id'] ) ); // phpcs:ignore

			// Get upgrade order IDs from the upgrade_orders column
			$upgrade_orders = ! empty( $license['upgrade_orders'] ) ? json_decode( $license['upgrade_orders'], true ) : array();

			// Get all completed upgrade payments using the upgrade_orders IDs
			$upgrade_payments = array();
			if ( ! empty( $upgrade_orders ) ) {
				$upgrade_payments = $wpdb->get_col( "SELECT subtotal FROM {$wpdb->prefix}digicommerce_orders WHERE id IN ( " . implode(',', array_map( 'intval', $upgrade_orders ) ) . " ) AND status = 'completed'" ); // phpcs:ignore
			}

			$all_payments = array_merge( [ $original_payment ], $upgrade_payments ); // phpcs:ignore

			// Calculate total amount paid from completed orders
			$total_paid = array_sum( array_map( 'floatval', $all_payments ) );

			// Get target variation price
			$upgrade_price = ! empty( $target_variation['salePrice'] ) ?
				floatval( $target_variation['salePrice'] ) :
				floatval( $target_variation['price'] );

			// Calculate final price with proration considering total paid
			$final_price = $selected_path['prorate'] ?
				( $upgrade_price - $total_paid ) :
				$upgrade_price;

			// Apply configured discount
			if ( ! empty( $selected_path['include_coupon'] ) ) {
				if ( 'percentage' === $selected_path['discount_type'] ) {
					$final_price *= ( 1 - ( floatval( $selected_path['discount_amount'] ) / 100 ) );
				} else {
					$final_price -= floatval( $selected_path['discount_amount'] );
				}
			}

			// Get target variation subscription details
			$is_subscription = ! empty( $target_variation['subscription_enabled'] );

			// Get the subscription ID for the original order if this is a subscription upgrade
			$original_subscription_id = null;
			if ( $is_subscription ) {
				// Get subscription ID from the original order
				$original_subscription_id = $wpdb->get_var($wpdb->prepare( "SELECT si.subscription_id FROM {$wpdb->prefix}digicommerce_subscription_items si JOIN {$wpdb->prefix}digicommerce_orders o ON si.order_id = o.id WHERE o.id = %d", $license['order_id'] ) ); // phpcs:ignore
			}

			// Create cart item
			$cart_item = array(
				'product_id'     => $license['product_id'],
				'name'           => get_the_title( $license['product_id'] ),
				'price'          => $final_price,  // This is the prorated price for immediate payment
				'variation_name' => $target_variation['name'],
				'meta'           => array(
					'upgrade_from_license'     => $license_id,
					'upgrade_path_id'          => $upgrade_variation_id,
					'variation_price'          => $upgrade_price,  // Full price for new subscription
					'total_paid'               => $total_paid,
					'subscription_upgrade'     => $is_subscription,
					'original_subscription_id' => $original_subscription_id,
				),
			);

			// Add subscription data if target variation is a subscription
			if ( $is_subscription ) {
				$cart_item['subscription_enabled']    = true;
				$cart_item['subscription_period']     = $target_variation['subscription_period'];
				$cart_item['subscription_free_trial'] = array(
					'duration' => 0,
					'period'   => 'days',
				);
				$cart_item['subscription_signup_fee'] = 0;
				// Note: The actual subscription will be created at full_price
			}

			// Initialize session with upgrade item
			$session_key          = $this->get_current_session_key();
			$session_data_value   = $this->get_session( $session_key );
			$session_data         = $session_data_value ? $session_data_value : array( 'cart' => array() );
			$session_data['cart'] = array( $cart_item );

			// Save session and update cart items
			$this->save_session( $session_key, $session_data );
			$this->cart_items = $session_data['cart'];

			// Redirect to checkout
			wp_redirect( get_permalink( DigiCommerce()->get_option( 'checkout_page_id' ) ) ); // phpcs:ignore
			exit;
		}

		// Check for abandoned cart
		if ( isset( $_GET['from_abandoned'] ) && '1' === $_GET['from_abandoned'] && isset( $_GET['customer_email'] ) && class_exists( 'DigiCommerce_Pro' ) ) {
			$email = '';
			if ( isset( $_GET['customer_email'] ) ) {
				$email = sanitize_email( wp_unslash( $_GET['customer_email'] ) );
			}

			// Add security check to prevent abuse
			if ( empty( $email ) || ! is_email( $email ) ) {
				return;
			}

			// Check if cart is empty (session expired or new session)
			if ( empty( $this->cart_items ) ) {
				global $wpdb;

				// Get the cart data from abandoned_carts table
				$table_abandoned_cart = $wpdb->prefix . 'digicommerce_abandoned_carts';
				$abandoned_cart       = $wpdb->get_row( // phpcs:ignore
					$wpdb->prepare( "SELECT * FROM {$table_abandoned_cart} WHERE email = %s AND recovered = 0 ORDER BY created_at DESC LIMIT 1", $email ) // phpcs:ignore
				);

				if ( $abandoned_cart && ! empty( $abandoned_cart->cart_contents ) ) {
					// Get current session key
					$session_key  = $this->get_current_session_key();
					$session_data = $this->get_session( $session_key ) ? $this->get_session( $session_key ) : array();

					// Restore cart items from abandoned cart
					$cart_items = json_decode( $abandoned_cart->cart_contents, true );
					if ( json_last_error() === JSON_ERROR_NONE && is_array( $cart_items ) ) {
						$session_data['cart'] = $cart_items;
						$this->cart_items     = $cart_items;

						// Save restored cart to session
						$this->save_session( $session_key, $session_data );
					}
				}
			}
		}

		// Get product ID, variation index, and coupon code from the URL parameters.
		$product_id      = isset( $_GET['id'] ) ? intval( $_GET['id'] ) : 0;
		$variation_index = isset( $_GET['variation'] ) ? ( intval( $_GET['variation'] ) - 1 ) : -1;

		if ( ! $product_id ) {
			return; // Exit if no product ID is provided.
		}

		$product = get_post( $product_id );

		if ( ! $product || 'digi_product' !== $product->post_type ) {
			return; // Exit if the product is invalid.
		}

		// Determine price and variation details.
		$price_mode        = get_post_meta( $product_id, 'digi_price_mode', true );
		$price             = 0;
		$variation_name    = '';
		$subscription_data = array();
		$has_variations    = false;

		// Handle variations.
		if ( 'variations' === $price_mode && $variation_index >= 0 ) {
			$has_variations = true;
			$variations     = get_post_meta( $product_id, 'digi_price_variations', true );

			if ( isset( $variations[ $variation_index ] ) ) {
				$variation      = $variations[ $variation_index ];
				$regular_price  = floatval( $variation['price'] );
				$sale_price     = isset( $variation['salePrice'] ) ? floatval( $variation['salePrice'] ) : 0;
				$variation_name = sanitize_text_field( $variation['name'] );

				// Get subscription data from variation
				$subscription_data = array(
					'subscription_enabled'    => ! empty( $variation['subscription_enabled'] ),
					'subscription_period'     => $variation['subscription_period'] ?? 'month',
					'subscription_free_trial' => $variation['subscription_free_trial'] ?? array(
						'duration' => 0,
						'period'   => 'days',
					),
					'subscription_signup_fee' => $variation['subscription_signup_fee'] ?? 0,
				);

				// Use sale price if it exists and is greater than 0
				if ( ! empty( $sale_price ) && $sale_price > 0 ) {
					$price = $sale_price;
				} else {
					$price = $regular_price;
				}
			} else {
				return;
			}
		} else {
			// Handle simple product.
			$regular_price = floatval( get_post_meta( $product_id, 'digi_price', true ) );
			$sale_price    = floatval( get_post_meta( $product_id, 'digi_sale_price', true ) );

			// Get subscription data for simple product
			$subscription_data = array(
				'subscription_enabled'    => get_post_meta( $product_id, 'digi_subscription_enabled', true ),
				'subscription_period'     => get_post_meta( $product_id, 'digi_subscription_period', true ),
				'subscription_free_trial' => get_post_meta( $product_id, 'digi_subscription_free_trial', true ),
				'subscription_signup_fee' => get_post_meta( $product_id, 'digi_subscription_signup_fee', true ),
			);

			// Use sale price if it exists and is greater than 0
			if ( ! empty( $sale_price ) && $sale_price > 0 ) {
				$price = $sale_price;
			} else {
				$price = $regular_price;
			}
		}

		if ( $price <= 0 ) {
			return;
		}

		// Check if the product (and variation, if applicable) is already in the cart.
		$session_key  = $this->get_current_session_key();
		$session_data = $this->get_session( $session_key );

		if ( ! $session_data ) {
			$session_data = array( 'cart' => array() );
		}

		$cart_items = array();
		foreach ( $session_data['cart'] as $cart_item ) {
			if ( $cart_item['product_id'] === $product_id ) {
				if ( $has_variations ) {
					// Skip this product entirely - we'll add the new variation
					continue;
				} else {
					// For non-variation products, if it exists, don't add it
					return;
				}
			}
			// Keep all other products
			$cart_items[] = $cart_item;
		}

		// Add product and variation to cart.
		$cart_item = array(
			'product_id'     => $product_id,
			'name'           => $product->post_title,
			'price'          => $price,
			'variation_name' => $variation_name,
		);

		// Add subscription data if subscription is enabled
		if ( ! empty( $subscription_data['subscription_enabled'] ) ) {
			$cart_item['subscription_enabled']    = $subscription_data['subscription_enabled'];
			$cart_item['subscription_period']     = $subscription_data['subscription_period'];
			$cart_item['subscription_free_trial'] = $subscription_data['subscription_free_trial'];
			$cart_item['subscription_signup_fee'] = $subscription_data['subscription_signup_fee'];

			// Add PayPal plan flag if needed
			if ( DigiCommerce()->is_paypal_enabled() ) {
				$cart_item['needs_paypal_plan'] = true;
			}
		}

		$cart_items[]         = $cart_item;
		$session_data['cart'] = $cart_items;

		// Save updated session data.
		$this->save_session( $session_key, $session_data );
		$this->cart_items = $cart_items;
	}

	/**
	 * Process checkout
	 *
	 * @throws Exception - Exception.
	 */
	public function process_checkout() {
		// Verify nonce
		check_ajax_referer( 'digicommerce_process_checkout', 'checkout_nonce' );

		$data            = array();
		$required_fields = array(
			'country',
			'email',
			'first_name',
			'last_name',
			'phone',
			'company',
			'address',
			'city',
			'postcode',
			'vat_number',
			'payment_method',
			'setup_intent_id',
			'payment_intent_id',
			'order_notes',
			'from_abandoned',
		);

		foreach ( $required_fields as $field ) {
			if ( isset( $_POST[ $field ] ) ) {
				// Apply appropriate sanitization based on field type
				if ( 'email' === $field ) {
					$data[ $field ] = sanitize_email( wp_unslash( $_POST[ $field ] ) );
				} elseif ( in_array( $field, array( 'order_notes' ), true ) ) {
					$data[ $field ] = sanitize_textarea_field( wp_unslash( $_POST[ $field ] ) );
				} elseif ( 'from_abandoned' === $field ) {
					$data[ $field ] = (bool) $_POST[ $field ];
				} else {
					$data[ $field ] = sanitize_text_field( wp_unslash( $_POST[ $field ] ) );
				}
			}
		}
		$minimal_fields = DigiCommerce()->get_option( 'minimal_fields' );

		try {
			// Save selected country to the session
			$selected_country = sanitize_text_field( $data['country'] ?? '' );
			if ( ! empty( $selected_country ) ) {
				$session_key                      = $this->get_current_session_key();
				$session_data                     = $this->get_session( $session_key );
				$session_data['selected_country'] = $selected_country;
				$this->save_session( $session_key, $session_data );
			}

			// Validate email
			$email = isset( $data['email'] ) ? sanitize_email( $data['email'] ) : '';
			if ( empty( $email ) ) {
				wp_send_json_error( esc_html__( 'Billing email is required.', 'digicommerce' ) );
			}

			// Check if user is logged in
			$user_id = get_current_user_id();
			if ( ! $user_id ) {
				$user_id = $this->create_user( $data );
				if ( is_wp_error( $user_id ) ) {
					wp_send_json_error( $user_id->get_error_message() );
				}

				// If the email already exists, create_user will return the user ID
				if ( ! $user_id ) {
					wp_send_json_error( esc_html__( 'Could not create or find the user.', 'digicommerce' ) );
				}
			}

			// Map billing fields
			$billing_fields = array(
				'first_name',
				'last_name',
				'email',
				'phone',
				'company',
				'address',
				'city',
				'postcode',
				'country',
				'vat_number',
			);

			$mapped_data = array();
			foreach ( $billing_fields as $field ) {
				$key         = $field; // Original key in $_POST
				$billing_key = 'billing_' . $field; // Key expected in update_user_billing

				// Use value from $_POST if available, otherwise retain the existing profile value
				$new_value = $data[ $key ] ?? '';
				if ( is_null( $new_value ) ) {
					$existing_value = ( 'email' === $field || 'first_name' === $field || 'last_name' === $field )
						? get_user_meta( $user_id, $field, true ) // Default WordPress fields
						: get_user_meta( $user_id, $billing_key, true ); // Custom billing fields
					$new_value      = $existing_value ? $existing_value : ''; // Retain existing value or set to empty
				}

				$mapped_data[ $billing_key ] = $new_value;
			}

			// Ensure WordPress default fields are also updated
			$default_fields = array(
				'first_name' => $mapped_data['billing_first_name'] ?? '',
				'last_name'  => $mapped_data['billing_last_name'] ?? '',
				'email'      => $mapped_data['billing_email'] ?? '',
			);

			// Update billing details
			try {
				$this->update_user_billing( $user_id, $mapped_data, $default_fields );
			} catch ( Exception $e ) {
				wp_send_json_error( esc_html__( 'Error updating billing details.', 'digicommerce' ) );
			}

			// Get session data for discount
			$session_key  = $this->get_current_session_key();
			$session_data = $this->get_session( $session_key );

			// Get business country from settings
			$business_country = DigiCommerce()->get_option( 'business_country' );
			$buyer_country    = $data['country'] ?? '';
			$vat_number       = $data['vat_number'] ?? '';

			// Calculate subtotal first
			$subtotal = $this->get_cart_total();

			// Apply discount to subtotal if exists in session
			$discount_amount = 0;
			$discount_type   = null;
			$discount_code   = null;
			if ( ! empty( $session_data['discount'] ) ) {
				$discount_data = $session_data['discount'];
				$discount_type = $discount_data['type'];
				$discount_code = $discount_data['code'];

				if ( 'percentage' === $discount_data['type'] ) {
					// Apply percentage discount to subtotal
					$discount_amount = round( ( $subtotal * $discount_data['amount'] ) / 100, 2 );
				} else {
					// Apply fixed discount to subtotal
					$discount_amount = min( $discount_data['amount'], $subtotal );
				}
			}

			// Calculate discounted subtotal
			$discounted_subtotal = $subtotal - $discount_amount;

			// Initialize VAT amount and calculate VAT on discounted subtotal
			$vat      = 0;
			$tax_rate = 0;

			// Only calculate VAT if taxes are not disabled
			if ( ! DigiCommerce()->get_option( 'remove_taxes' ) ) {
				$countries = DigiCommerce()->get_countries();

				if ( $buyer_country === $business_country ) {
					// Domestic sale: Always charge seller's country VAT
					$tax_rate = $countries[ $business_country ]['tax_rate'] ?? 0;
					$vat      = $discounted_subtotal * $tax_rate;
				} elseif ( ! empty( $countries[ $buyer_country ]['eu'] ) && ! empty( $countries[ $business_country ]['eu'] ) ) {
					// EU cross-border sale
					if ( empty( $vat_number ) || ! DigiCommerce_Orders::instance()->validate_vat_number( $vat_number, $buyer_country ) ) {
						// No valid VAT number - charge buyer's country rate
						$tax_rate = $countries[ $buyer_country ]['tax_rate'] ?? 0;
						$vat      = $discounted_subtotal * $tax_rate;
					}
					// With valid VAT number - no VAT (vat remains 0)
				}
				// Non-EU sale - no VAT (vat remains 0)
			}

			// Calculate final total
			$total = $discounted_subtotal + $vat;

			// Prepare order data
			$order_data = array(
				'user_id'           => $user_id,
				'items'             => $this->prepare_order_items(),
				'subtotal'          => $subtotal,
				'discount_amount'   => $discount_amount,
				'discount_type'     => $discount_type,
				'discount_code'     => $discount_code,
				'vat_rate'          => $tax_rate,
				'vat'               => $vat,
				'total'             => $total,
				'payment_method'    => sanitize_text_field( $data['payment_method'] ?? '' ),
				'setup_intent_id'   => sanitize_text_field( $_POST['setup_intent_id'] ?? null ), // phpcs:ignore
				'payment_intent_id' => sanitize_text_field( $_POST['payment_intent_id'] ?? null ), // phpcs:ignore
				'customer_note'     => sanitize_textarea_field( $data['order_notes'] ?? '' ),
				'billing_details'   => array(
					'first_name' => sanitize_text_field( $data['first_name'] ?? '' ),
					'last_name'  => sanitize_text_field( $data['last_name'] ?? '' ),
					'company'    => sanitize_text_field( $data['company'] ?? '' ),
					'email'      => $email,
					'phone'      => $minimal_fields ? '' : sanitize_text_field( $data['phone'] ?? '' ),
					'address'    => $minimal_fields ? '' : sanitize_text_field( $data['address'] ?? '' ),
					'city'       => $minimal_fields ? '' : sanitize_text_field( $data['city'] ?? '' ),
					'postcode'   => $minimal_fields ? '' : sanitize_text_field( $data['postcode'] ?? '' ),
					'country'    => sanitize_text_field( $data['country'] ?? '' ),
					'vat_number' => sanitize_text_field( $data['vat_number'] ?? '' ),
				),
				'from_abandoned'    => isset( $data['from_abandoned'] ) ? 1 : 0,
			);

			// Create the order
			$order_id = DigiCommerce_Orders::instance()->create_order( $order_data );
			if ( is_wp_error( $order_id ) ) {
				wp_send_json_error( $order_id->get_error_message() );
			}

			// Fetch token for the order
			$order = DigiCommerce_Orders::instance()->get_order( $order_id );
			if ( ! $order || empty( $order['token'] ) ) {
				wp_send_json_error( esc_html__( 'Failed to retrieve order token.', 'digicommerce' ) );
			}
			$token = $order['token'];

			// Handle payment method
			$payment_method = sanitize_text_field( $order_data['payment_method'] ?? '' );

			// Add $wpdb to update database
			global $wpdb;

			switch ( $payment_method ) {
				case 'stripe':
					// Get stripe payment data from form submission
					$stripe_payment_data = isset( $_POST['stripe_payment_data'] ) ? json_decode( stripslashes( sanitize_text_field( wp_unslash( $_POST['stripe_payment_data'] ) ) ), true ) : null;

					if ( ! $stripe_payment_data ) {
						throw new Exception( esc_html__( 'Payment data not found', 'digicommerce' ) );
					}

					// Validate required data
					if ( empty( $stripe_payment_data['customer_id'] ) ) {
						throw new Exception( esc_html__( 'Customer data not found', 'digicommerce' ) );
					}

					// Array of payment data to store
					$payment_meta = array();

					// Always add customer ID if present
					if ( ! empty( $stripe_payment_data['customer_id'] ) ) {
						$payment_meta['_stripe_customer_id'] = $stripe_payment_data['customer_id'];
					}

					// Add payment intent ID if present
					if ( ! empty( $stripe_payment_data['payment_intent_id'] ) ) {
						$payment_meta['_stripe_payment_intent_id'] = $stripe_payment_data['payment_intent_id'];
					}

					// Add setup intent ID if present
					if ( ! empty( $stripe_payment_data['setup_intent_id'] ) ) {
						$payment_meta['_stripe_setup_intent_id'] = $stripe_payment_data['setup_intent_id'];
					}

					// Add payment method if present
					if ( ! empty( $stripe_payment_data['payment_method'] ) ) {
						$payment_meta['_stripe_payment_method'] = $stripe_payment_data['payment_method'];
					}

					// Add subscription ID if present
					if ( ! empty( $stripe_payment_data['subscription_id'] ) ) {
						$payment_meta['_stripe_subscription_id'] = $stripe_payment_data['subscription_id'];
					}

					// Store all payment-related meta
					foreach ( $payment_meta as $meta_key => $meta_value ) {
						if ( ! empty( $meta_value ) ) {
							$wpdb->insert( // phpcs:ignore
								$wpdb->prefix . 'digicommerce_order_meta',
								array(
									'order_id'   => $order_id,
									'meta_key'   => $meta_key, // phpcs:ignore
									'meta_value' => $meta_value, // phpcs:ignore
								),
								array( '%d', '%s', '%s' ),
							);
						}
					}
					break;

				case 'paypal':
					$payment_result      = false;
					$has_subscription    = false;
					$has_free_trial      = false;
					$has_initial_payment = false;

					// Check cart items for subscriptions and free trials - same logic as Stripe
					foreach ( $order_data['items'] as $item ) {
						if ( ! empty( $item['subscription_enabled'] ) ) {
							$has_subscription = true;

							// Check for free trial
							if ( ! empty( $item['subscription_free_trial'] ) &&
								intval( $item['subscription_free_trial']['duration'] ) > 0 ) {
								$has_free_trial = true;
							}

							// Check for initial payment (signup fee or first payment)
							if ( floatval( $item['subscription_signup_fee'] ) > 0 || ! $has_free_trial ) {
								$has_initial_payment = true;
							}
						}
					}

					// Determine which type of payment to process
					if ( $has_subscription ) {
						if ( empty( $_POST['paypal_subscription_id'] ) ) {
							throw new Exception( __( 'Subscription ID required for subscription', 'digicommerce' ) );
						}

						$payment_result = $this->process_paypal_payment(
							$order_id,
							isset( $_POST['paypal_order_id'] )
								? sanitize_text_field( wp_unslash( $_POST['paypal_order_id'] ) )
								: sanitize_text_field( wp_unslash( $_POST['paypal_subscription_id'] ) )
						);
					} else {
						// Regular one-time payment
						if ( empty( $_POST['paypal_order_id'] ) ) {
							throw new Exception( esc_html__( 'Payment ID required for one-time payment', 'digicommerce' ) );
						}
						$payment_result = $this->process_paypal_payment(
							$order_id,
							$_POST['paypal_order_id'] // phpcs:ignore
						);
					}

					if ( false === $payment_result ) {
						throw new Exception( esc_html__( 'PayPal payment processing failed.', 'digicommerce' ) );
					}
					break;

				default:
					throw new Exception( esc_html__( 'Invalid payment method selected.', 'digicommerce' ) );
			}

			// Clear discount data from session after successful payment
			$session_data = $this->get_session( $session_key );
			unset( $session_data['discount'] );
			$this->save_session( $session_key, $session_data );

			// Update order status
			$payment_method = sanitize_text_field( $order_data['payment_method'] ?? '' );

			$updated = $wpdb->update( // phpcs:ignore
				$wpdb->prefix . 'digicommerce_orders',
				array(
					'payment_method' => $payment_method,
					'status'         => 'completed',
					'date_modified'  => current_time( 'mysql' ),
				),
				array( 'id' => $order_id ),
				array( '%s', '%s', '%s' ),
				array( '%d' )
			);

			if ( false === $updated ) {
				throw new Exception( esc_html__( 'Failed to update order status', 'digicommerce' ) );
			}

			// Add order note with dynamic payment method
			if ( class_exists( 'DigiCommerce_Orders' ) ) {
				$note = sprintf(
					// translators: %s: payment method
					esc_html__( 'Payment completed via %s', 'digicommerce' ),
					ucfirst( $payment_method )
				);
				DigiCommerce_Orders::instance()->add_order_note( $order_id, $note );
			}

			// Clear session after successful payment
			$this->clear_cart();
			$this->delete_session( $session_key );
			$this->clear_session_cookie();

			// Send confirmation email if enabled
			if ( DigiCommerce()->get_option( 'email_order_confirmation' ) ) {
				DigiCommerce_Emails::instance()->send_order_confirmation( $order_id );
			}

			do_action( 'digicommerce_after_process_checkout', $order_id );

			// Get success page URL
			$success_page_url = get_permalink( DigiCommerce()->get_option( 'payment_success_page_id', '' ) );
			if ( ! $success_page_url ) {
				wp_send_json_error( esc_html__( 'Success page URL not configured.', 'digicommerce' ) );
			}

			// Add order ID and token to success URL
			$redirect_url = add_query_arg(
				array(
					'order_id'      => $order_id,
					'token'         => $token,
					'payment_nonce' => wp_create_nonce( 'digicommerce_payment_' . $order_id ),
				),
				$success_page_url
			);

			wp_send_json_success(
				array(
					'order_id' => $order_id,
					'redirect' => $redirect_url,
				)
			);

		} catch ( Exception $e ) {
			wp_send_json_error( esc_html__( 'An error occurred during checkout. Please try again.', 'digicommerce' ) );
		}
	}

	/**
	 * Process PayPal payment
	 *
	 * @param int         $order_id Order ID.
	 * @param string|null $paypal_order_id PayPal Order ID.
	 * @param string|null $subscription_id PayPal Subscription ID.
	 * @throws Exception - Exception.
	 */
	private function process_paypal_payment( $order_id, $paypal_order_id = null, $subscription_id = null ) {
		try {
			global $wpdb;

			// Store PayPal IDs in order meta
			if ( $subscription_id ) {
				$wpdb->insert( // phpcs:ignore
					$wpdb->prefix . 'digicommerce_order_meta',
					array(
						'order_id'   => $order_id,
						'meta_key'   => '_paypal_subscription_id', // phpcs:ignore
						'meta_value' => $subscription_id, // phpcs:ignore
					)
				);
			}

			if ( $paypal_order_id ) {
				$wpdb->insert( // phpcs:ignore
					$wpdb->prefix . 'digicommerce_order_meta',
					array(
						'order_id'   => $order_id,
						'meta_key'   => '_paypal_order_id', // phpcs:ignore
						'meta_value' => $paypal_order_id, // phpcs:ignore
					)
				);
			}

			return true;

		} catch ( Exception $e ) {
			throw $e;
		}
	}

	/**
	 * Prepare order items
	 */
	private function prepare_order_items() {
		$items_data = array();
		foreach ($this->cart_items as $item) {
			$product = get_post($item['product_id']);
			$item_data = array(
				'product_id' => $item['product_id'],
				'name' => $product->post_title,
				'variation_name' => $item['variation_name'] ?? '',
				'price' => $item['price'],
				'quantity' => 1,
			);
	
			// Check if this is a bundle product (automatically detect)
			$bundle_products = get_post_meta($item['product_id'], 'digi_bundle_products', true);
			$is_bundle = !empty($bundle_products) && is_array($bundle_products) && count(array_filter($bundle_products)) > 0;
	
			if ($is_bundle) {
				$item_data['is_bundle'] = true;
				$item_data['bundle_products'] = array();
				
				foreach ($bundle_products as $bundle_product_id) {
					if (empty($bundle_product_id)) continue; // Skip empty selections
					
					$bundle_product_id = intval($bundle_product_id);
					$bundle_product = get_post($bundle_product_id);
					if ($bundle_product && $bundle_product->post_status === 'publish') {
						// Get files for this bundled product - check both regular files and variation files
						$bundle_files = array();
						
						// Get regular files first
						$regular_files = get_post_meta($bundle_product_id, 'digi_files', true);
						if (!empty($regular_files) && is_array($regular_files)) {
							$bundle_files = $regular_files;
						}
						
						// Check if the bundled product has variations and get files from default variation
						$price_mode = get_post_meta($bundle_product_id, 'digi_price_mode', true);
						if ($price_mode === 'variations') {
							$variations = get_post_meta($bundle_product_id, 'digi_price_variations', true);
							if (!empty($variations) && is_array($variations)) {
								// Find default variation or use first variation
								$default_variation = null;
								foreach ($variations as $variation) {
									if (!empty($variation['isDefault'])) {
										$default_variation = $variation;
										break;
									}
								}
								
								// If no default found, use first variation
								if (!$default_variation && !empty($variations[0])) {
									$default_variation = $variations[0];
								}
								
								// Use variation files if available and not empty
								if ($default_variation && !empty($default_variation['files']) && is_array($default_variation['files'])) {
									$bundle_files = $default_variation['files'];
								}
							}
						}
						
						$item_data['bundle_products'][] = array(
							'product_id' => $bundle_product_id,
							'name' => $bundle_product->post_title,
							'files' => $bundle_files,
						);
					}
				}
			}
	
			// Add subscription data if present
			if (!empty($item['subscription_enabled'])) {
				$item_data['subscription_enabled'] = $item['subscription_enabled'];
				$item_data['subscription_period'] = $item['subscription_period'];
				$item_data['subscription_free_trial'] = $item['subscription_free_trial'];
				$item_data['subscription_signup_fee'] = $item['subscription_signup_fee'];
			}
	
			// Add any meta data from cart item
			if (!empty($item['meta'])) {
				$item_data['meta'] = $item['meta'];
			}
	
			$items_data[] = $item_data;
		}
	
		return $items_data;
	}

	/**
	 * Get session data
	 *
	 * @param string $session_key - Session key.
	 */
	private function get_session_tax_rate( $session_key ) {
		// Get session data
		$session_data = $this->get_session( $session_key );
		$tax_rate     = 0;

		// First check session for selected country
		if ( ! empty( $session_data['selected_country'] ) ) {
			$selected_country = $session_data['selected_country'];
			$countries        = DigiCommerce()->get_countries();
			if ( isset( $countries[ $selected_country ]['tax_rate'] ) ) {
				$tax_rate = floatval( $countries[ $selected_country ]['tax_rate'] );
			}
		} elseif ( is_user_logged_in() ) {
			$user         = wp_get_current_user();
			$user_country = get_user_meta( $user->ID, 'billing_country', true );
			if ( ! empty( $user_country ) ) {
				$countries = DigiCommerce()->get_countries();
				if ( isset( $countries[ $user_country ]['tax_rate'] ) ) {
					$tax_rate = floatval( $countries[ $user_country ]['tax_rate'] );
				}
			}
		}

		return $tax_rate;
	}

	/**
	 * Create a new user account
	 *
	 * @param array $data - User data.
	 */
	private function create_user( $data ) {
		$email = sanitize_email( $data['email'] );

		// Check if the email already exists
		$existing_user = get_user_by( 'email', $email );
		if ( $existing_user ) {
			return $existing_user->ID; // Return the user ID if the email exists
		}

		$username     = sanitize_user( current( explode( '@', $email ) ) );
		$counter      = 1;
		$new_username = $username;

		while ( username_exists( $new_username ) ) {
			$new_username = $username . $counter;
			++$counter;
		}

		$password = wp_generate_password();
		$user_id  = wp_create_user( $new_username, $password, $email );

		if ( ! is_wp_error( $user_id ) ) {
			wp_update_user(
				array(
					'ID'         => $user_id,
					'first_name' => sanitize_text_field( $data['first_name'] ),
					'last_name'  => sanitize_text_field( $data['last_name'] ),
				)
			);

			// Send welcome email with login details only if enabled in settings
			if ( DigiCommerce()->get_option( 'email_new_account' ) ) {
				DigiCommerce_Emails::instance()->send_welcome_email( $email, $password );
			}
		}

		return $user_id;
	}

	/**
	 * Update user billing details
	 *
	 * @param int   $user_id - User ID.
	 * @param array $billing_data - Billing data.
	 * @param array $default_data - Default data.
	 */
	private function update_user_billing( $user_id, $billing_data, $default_data = array() ) {
		// Define billing fields to be processed
		$billing_fields = array(
			'first_name',
			'last_name',
			'company',
			'address',
			'city',
			'postcode',
			'country',
			'phone',
			'email',
			'vat_number',
		);

		// Keep track of changed fields for order update
		$changed_values = array();

		foreach ( $billing_fields as $field ) {
			$meta_key = 'billing_' . $field;

			// Retrieve the new value from $billing_data or fallback to existing value
			$new_value = isset( $billing_data[ $meta_key ] ) ? $billing_data[ $meta_key ] : get_user_meta( $user_id, $meta_key, true );

			// Skip updating if the value is null, empty, or explicitly 'null'
			if ( null === $new_value || 'null' === strtolower( (string) $new_value ) || '' === $new_value ) {
				continue;
			}

			// Sanitize the input
			$sanitized_value = ( 'email' === $field ) ? sanitize_email( $new_value ) : sanitize_text_field( $new_value );

			// Get the current value in user meta
			$current_value = get_user_meta( $user_id, $meta_key, true );

			// Update only if the new value is different
			if ( $sanitized_value !== $current_value ) {
				if ( update_user_meta( $user_id, $meta_key, $sanitized_value ) ) {
					// Store changed value for order update
					$changed_values[ $field ] = $sanitized_value;

					// Synchronization mapping
					$map = array(
						'email'      => 'user_email',
						'first_name' => 'first_name',
						'last_name'  => 'last_name',
					);

					foreach ( $map as $billing_field => $wp_field ) {
						if ( $field === $billing_field ) {
							// Get the current value of the corresponding WordPress field
							$current_value = ( 'user_email' === $wp_field ) ? get_userdata( $user_id )->user_email : get_user_meta( $user_id, $wp_field, true );

							// Update if the new value is different
							if ( $sanitized_value !== $current_value ) {
								if ( 'user_email' === $wp_field ) {
									$update_result = wp_update_user(
										array(
											'ID'         => $user_id,
											'user_email' => $sanitized_value,
										)
									);
								} else {
									update_user_meta( $user_id, $wp_field, $sanitized_value );
								}
							}
						}
					}
				}
			}
		}

		// Update orders if we have any changed values
		if ( ! empty( $changed_values ) ) {
			global $wpdb;

			// Get all orders for this user
			$orders = $wpdb->get_results( // phpcs:ignore
				$wpdb->prepare(
					"SELECT id FROM {$wpdb->prefix}digicommerce_orders WHERE user_id = %d",
					$user_id
				),
				ARRAY_A
			);

			if ( ! empty( $orders ) ) {
				foreach ( $orders as $order ) {
					$wpdb->update( // phpcs:ignore
						$wpdb->prefix . 'digicommerce_order_billing',
						$changed_values,
						array( 'order_id' => $order['id'] ),
						array_fill( 0, count( $changed_values ), '%s' ),
						array( '%d' )
					);
				}
			}
		}
	}

	/**
	 * Clear cart
	 */
	private function clear_cart() {
		$this->cart_items = array();
		$session_key      = $this->get_current_session_key();
		$this->save_session( $session_key, array( 'cart' => $this->cart_items ) );
	}

	/**
	 * Get cart items
	 */
	public function get_cart_items() {
		// If user is logged in, ensure we're getting cart from user session.
		if ( is_user_logged_in() ) {
			$session_key  = 'user_' . get_current_user_id();
			$session_data = $this->get_session( $session_key );
			if ( $session_data && isset( $session_data['cart'] ) ) {
				$this->cart_items = $session_data['cart'];
			}
		}

		return $this->cart_items;
	}

	/**
	 * Get cart total
	 */
	public function get_cart_total() {
		$total = 0;
		foreach ( $this->cart_items as $item ) {
			$total += $item['price'];
		}
		return $total;
	}

	/**
	 * Modal
	 */
	public function modal() {
		if ( ! DigiCommerce()->is_checkout_page() ) :
			return;
		endif;
		?>
		<div id="terms-modal" class="fixed inset-0 z-50 hidden">
			<!-- Modal Overlay -->
			<div class="modal-overlay absolute inset-0 bg-black bg-opacity-90 opacity-0 transition-opacity duration-300 ease-in-out"></div>
			
			<!-- Modal Content -->
			<div class="modal-container relative w-full max-w-2xl mx-auto mt-20 opacity-0 translate-y-[-20px] transition-all duration-300 ease-in-out">
				<div class="bg-white rounded-lg shadow-xl overflow-hidden">
					<!-- Modal Header -->
					<div class="flex items-center justify-between p-4 border-0 border-b border-solid border-gray-300">
						<p class="text-xl font-bold m-0 text-dark-blue">
							<?php esc_html_e( 'Terms & Conditions', 'digicommerce' ); ?>
						</p>
						<button type="button" class="close-modal text-gray-400 hover:text-gray-500 no-background">
							<span class="sr-only"><?php esc_html_e( 'Close', 'digicommerce' ); ?></span>
							<svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
								<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
							</svg>
						</button>
					</div>
					
					<!-- Modal Body -->
					<div class="p-6 space-y-4 max-h-[70vh] overflow-y-auto">
						<?php echo wp_kses_post( DigiCommerce()->get_option( 'modal_terms', '' ) ); ?>
					</div>
					
					<!-- Modal Footer -->
					<div class="flex pt-4">
						<button type="button" class="close-modal flex items-center justify-center gap-2 p-4 w-full text-medium font-bold bg-gold text-dark-blue hover:bg-dark-blue hover:text-gold">
							<?php esc_html_e( 'Place order', 'digicommerce' ); ?>
							<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" /></svg>
						</button>
					</div>
				</div>
			</div>
		</div>
		<?php
	}
}
DigiCommerce_Checkout::instance();