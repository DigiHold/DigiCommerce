<?php
/**
 * DigiCommerce
 *
 * @package       DigiCommerce
 * @author        DigiCommerce
 *
 * @wordpress-plugin
 * Plugin Name:       DigiCommerce
 * Plugin URI:        https://digicommerce.me/
 * Description:       Powerful ecommerce plugin to sell services and digital products.
 * Version:           1.0.0
 * Author:            DigiCommerce
 * Author URI:        https://digicommerce.me?utm_source=wordpress.org&utm_medium=referral&utm_campaign=plugin_directory&utm_content=digicommerce
 * Text Domain:       digicommerce
 * Domain Path:       /languages
 * Requires PHP:      7.4
 * Requires at least: 6.0
 */

defined( 'ABSPATH' ) || exit;

// Define constants first.
if ( ! defined( 'DIGICOMMERCE_VERSION' ) ) {
	define( 'DIGICOMMERCE_VERSION', '1.0.0' );
}
if ( ! defined( 'DIGICOMMERCE_PLUGIN_DIR' ) ) {
	define( 'DIGICOMMERCE_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
}
if ( ! defined( 'DIGICOMMERCE_PLUGIN_URL' ) ) {
	define( 'DIGICOMMERCE_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
}
if ( ! defined( 'DIGICOMMERCE_PLUGIN_BASENAME' ) ) {
	define( 'DIGICOMMERCE_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
}

if ( ! class_exists( 'DigiCommerce' ) ) {
	/**
	 * Main DigiCommerce class
	 */
	final class DigiCommerce {

		/**
		 * Unique instance
		 *
		 * @var DigiCommerce
		 */
		private static $instance = null;

		/**
		 * Plugin options
		 *
		 * @var array
		 */
		private $options = array();

		/**
		 * Private constructor
		 */
		private function __construct() {
			// Security.
			require_once DIGICOMMERCE_PLUGIN_DIR . 'includes/class-digicommerce-security.php';

			if ( is_admin() ) {
				// Plugin settings.
				require_once DIGICOMMERCE_PLUGIN_DIR . 'includes/admin/class-digicommerce-settings.php';

				// Reports.
				require_once DIGICOMMERCE_PLUGIN_DIR . 'includes/admin/class-digicommerce-reports.php';

				// Dashboard widget reports.
				require_once DIGICOMMERCE_PLUGIN_DIR . 'includes/admin/class-digicommerce-dashboard.php';
			}

			// Blocks.
			require_once DIGICOMMERCE_PLUGIN_DIR . 'includes/admin/class-digicommerce-blocks.php';

			// Orders post type.
			require_once DIGICOMMERCE_PLUGIN_DIR . 'includes/class-digicommerce-orders.php';

			// Shortcodes.
			require_once DIGICOMMERCE_PLUGIN_DIR . 'includes/class-digicommerce-shortcodes.php';

			// Product post type.
			require_once DIGICOMMERCE_PLUGIN_DIR . 'includes/admin/class-digicommerce-product.php';

			// Login and account.
			require_once DIGICOMMERCE_PLUGIN_DIR . 'includes/front/class-digicommerce-account.php';

			// Login handler.
			require_once DIGICOMMERCE_PLUGIN_DIR . 'includes/front/class-digicommerce-login-handler.php';

			// Checkout.
			require_once DIGICOMMERCE_PLUGIN_DIR . 'includes/class-digicommerce-checkout.php';

			// Download items.
			require_once DIGICOMMERCE_PLUGIN_DIR . 'includes/admin/class-digicommerce-files.php';

			// Emails.
			require_once DIGICOMMERCE_PLUGIN_DIR . 'includes/class-digicommerce-emails.php';

			// Installation.
			register_activation_hook( __FILE__, array( $this, 'install' ) );

			// Clean up sessions are deactivation.
			register_deactivation_hook( __FILE__, array( $this, 'deactivate' ) );

			// Internationalization.
			add_action( 'plugins_loaded', array( $this, 'load_plugin_textdomain' ) );

			// Scripts and styles.
			add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

			// Load base Tailwinds CSS without priorit to prevent theme conflicts.
			add_action( 'wp_enqueue_scripts', array( $this, 'base_css' ) );

			// Admin protection if enabled and setup wizard.
			add_action( 'admin_init', array( $this, 'admin_init' ) );
			add_filter( 'show_admin_bar', array( $this, 'handle_admin_bar' ), 20, 1 );

			// Initialize shortcodes.
			add_action( 'init', array( $this, 'init' ) );

			// Add page state labels in admin.
			add_filter( 'display_post_states', array( $this, 'add_page_state_labels' ), 10, 2 );

			// Loads options.
			$this->load_options();

			// Add theme compatibility.
			$this->themes_compatibility();

			// Add body class.
			add_action( 'template_redirect', array( $this, 'maybe_add_recaptcha_class' ) );

			// Add custom color in head.
			add_action( 'wp_head', array( $this, 'custom_colors' ), 10 );

			// Add dir attribute for LTR/RTL support
			add_filter( 'language_attributes', array( $this, 'attribute_to_html' ) );
		}

		/**
		 * Prevents cloning of the instance
		 */
		private function __clone() {}

		/**
		 * Prevents deserialization of the instance
		 *
		 * @throws Exception When attempting to unserialize the singleton instance.
		 */
		public function __wakeup() {
			throw new Exception( 'Cannot unserialize a singleton.' );
		}

		/**
		 * Gets the single instance of the class
		 *
		 * @return DigiCommerce
		 */
		public static function instance() {
			if ( is_null( self::$instance ) ) {
				self::$instance = new self();
			}
			return self::$instance;
		}

		/**
		 * Plugin installation
		 */
		public function install() {
			global $wpdb;

			$table_name      = $wpdb->prefix . 'digicommerce';
			$charset_collate = $wpdb->get_charset_collate();

			// Ensure table exists.
			$sql = "CREATE TABLE IF NOT EXISTS $table_name (
                id mediumint(9) NOT NULL AUTO_INCREMENT,
                option_name varchar(191) NOT NULL,
                option_value longtext NOT NULL,
                created_at datetime DEFAULT CURRENT_TIMESTAMP,
                updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY  (id),
                UNIQUE KEY option_name (option_name)
            ) $charset_collate;";

			require_once ABSPATH . 'wp-admin/includes/upgrade.php';
			dbDelta( $sql );

			// Check if the flag exists in the custom table.
			if ( ! $this->get_flag( 'digicommerce_pages_created' ) ) {
				$this->create_pages(); // Create pages only if not already created.
				$this->set_flag( 'digicommerce_pages_created', true ); // Set the flag in the custom table.
			}

			// Call orders table installation
			DigiCommerce_Orders::instance()->install_tables();
			DigiCommerce_Checkout::instance()->install_tables();

			// Flush rewrite rules
			flush_rewrite_rules();
		}

		/**
		 * Plugin deactivation
		 */
		public static function deactivate() {
			wp_clear_scheduled_hook( 'digicommerce_cleanup_sessions' );

			// Clean up all sessions on deactivation
			global $wpdb;
			$table_name = $wpdb->prefix . 'digicommerce_sessions';

			// Check if the table exists before truncating
			$table_exists = $wpdb->get_var(
				$wpdb->prepare(
					'SHOW TABLES LIKE %s',
					$table_name
				)
			);

			if ( $table_exists === $table_name ) {
				$wpdb->query( "TRUNCATE TABLE $table_name" ); // phpcs:ignore
			}
		}

		/**
		 * Creates plugin pages
		 */
		private function create_pages() {
			$pages = $this->get_pages_data();

			foreach ( $pages as $slug => $page_data ) {
				$page_exists = get_page_by_path( $slug );

				if ( ! $page_exists ) {
					$translated_title = wp_strip_all_tags( $page_data['title'] );
					$translated_slug  = sanitize_title( $translated_title );

					$page_id = wp_insert_post(
						array(
							'post_title'   => $translated_title,
							'post_content' => wp_kses_post( $page_data['content'] ),
							'post_status'  => 'publish',
							'post_type'    => 'page',
							'post_name'    => $translated_slug,
						)
					);

					if ( ! is_wp_error( $page_id ) ) {
						$this->set_option( $page_data['option_name'], $page_id );
					}
				}
			}
		}

		/**
		 * Adds page state labels
		 *
		 * @param string $flag_name Flag name.
		 */
		private function get_flag( $flag_name ) {
			global $wpdb;
			$table_name = $wpdb->prefix . 'digicommerce';

			// Query the custom table for the flag
			$result = $wpdb->get_var(
				$wpdb->prepare( "SELECT option_value FROM $table_name WHERE option_name = %s", $flag_name ) // phpcs:ignore
			);

			return $result ? maybe_unserialize( $result ) : false;
		}

		/**
		 * Sets a flag
		 *
		 * @param string $flag_name Flag name.
		 * @param mixed  $value Flag value.
		 */
		public function set_flag( $flag_name, $value ) {
			global $wpdb;
			$table_name = $wpdb->prefix . 'digicommerce';

			// Serialize the value if necessary
			$serialized_value = maybe_serialize( $value );

			// Insert or update the flag in the custom table
			$wpdb->replace(
				$table_name,
				array(
					'option_name'  => $flag_name,
					'option_value' => $serialized_value,
				),
				array( '%s', '%s' ) // Data types
			);
		}

		/**
		 * Loads options from database
		 */
		private function load_options() {
			global $wpdb;
			$table_name = $wpdb->prefix . 'digicommerce';

			$row = $wpdb->get_row(
				$wpdb->prepare( "SELECT option_value FROM $table_name WHERE option_name = %s", 'digicommerce_options' ) // phpcs:ignore
			);

			if ( $row ) {
				$this->options = maybe_unserialize( $row->option_value );
			}
		}

		/**
		 * Theme compatibility
		 */
		public function themes_compatibility() {
			// Get theme template (parent theme) and stylesheet (child theme if used).
			$template   = get_template();
			$stylesheet = get_stylesheet();

			// Map of theme slugs to compatibility class file names.
			$theme_compatibility = array(
				'blocksy',
				'hello-elementor',
				'hestia',
				'kadence',
				'neve',
				'oceanwp',
			);

			// Check if current theme has compatibility file.
			foreach ( $theme_compatibility as $theme_slug ) {
				// Check if this theme or its parent is active.
				if ( $theme_slug === $template || $theme_slug === $stylesheet ) {
					$file_path = DIGICOMMERCE_PLUGIN_DIR . 'includes/compatibility/class-digicommerce-' . $theme_slug . '.php';

					if ( file_exists( $file_path ) ) {
						require_once $file_path;
						break; // Stop after first match.
					}
				}
			}
		}

		/**
		 * Gets an option
		 *
		 * @param string $key Option key.
		 * @param mixed  $default Default value.
		 */
		public function get_option( $key, $default = false ) { // phpcs:ignore
			return isset( $this->options[ $key ] ) ? $this->options[ $key ] : $default;
		}

		/**
		 * Sets an option
		 *
		 * @param string $key Option key.
		 * @param mixed  $value Option value.
		 */
		public function set_option( $key, $value ) {
			global $wpdb;

			$table_name = $wpdb->prefix . 'digicommerce';

			// Only proceed if the value is not empty
			if ( ! empty( $value ) ) {
				$this->options[ $key ] = $value;

				$serialized_options = maybe_serialize( $this->options );

				// First check if the record exists
				$exists = $wpdb->get_var(
					$wpdb->prepare(
						"SELECT COUNT(*) FROM {$table_name} WHERE option_name = %s", // phpcs:ignore
						'digicommerce_options'
					)
				);

				if ( $exists ) {
					$result = $wpdb->replace(
						$table_name,
						array(
							'option_name'  => 'digicommerce_options',
							'option_value' => $serialized_options,
						),
						array(
							'%s',
							'%s',
						)
					);
				} else {
					$result = $wpdb->insert(
						$table_name,
						array(
							'option_name'  => 'digicommerce_options',
							'option_value' => $serialized_options,
						),
						array(
							'%s',
							'%s',
						)
					);
				}

				return $result !== false; // phpcs:ignore
			}

			// If the value is empty, remove it from options array
			if ( isset( $this->options[ $key ] ) ) {
				unset( $this->options[ $key ] );

				$serialized_options = maybe_serialize( $this->options );

				// Update the database with the modified options array
				$result = $wpdb->replace(
					$table_name,
					array(
						'option_name'  => 'digicommerce_options',
						'option_value' => $serialized_options,
					),
					array(
						'%s',
						'%s',
					)
				);

				return $result !== false; // phpcs:ignore
			}

			return true;
		}

		/**
		 * Load translations
		 */
		public function load_plugin_textdomain() {
			load_plugin_textdomain(
				'digicommerce',
				false,
				dirname( plugin_basename( __FILE__ ) ) . '/languages/'
			);
		}

		/**
		 * Enqueue scripts and styles
		 */
		public function enqueue_scripts() {
			static $localized = false;

			$should_load_css = (
				// Load everywhere if pro plugin exists and side cart is enabled.
				( class_exists( 'DigiCommerce_Pro' ) && DigiCommerce()->get_option( 'enable_side_cart' ) )
				// Or load only on plugin pages otherwise.
				|| $this->is_plugin_page()
			);

			// Check if styling is not disabled via option and filter.
			$should_load_css = $should_load_css
			&& ! DigiCommerce()->get_option( 'disable_styling' )
			&& apply_filters( 'digicommerce_style', true );

			if ( $should_load_css ) {
				wp_enqueue_style(
					'digicommerce-styles',
					DIGICOMMERCE_PLUGIN_URL . 'assets/css/front.css',
					array(),
					DIGICOMMERCE_VERSION
				);
			}

			if ( $this->is_plugin_page() ) {
				// Initialize variables array.
				$localized_vars = array();

				if (
					(
						$this->is_account_page() ||
						$this->is_reset_password_page() ||
						( $this->is_checkout_page() && $this->get_option( 'login_during_checkout' ) ) ||
						( is_singular( 'digi_product' ) && DigiCommerce()->get_option( 'enable_reviews' ) )
					) &&
					! is_user_logged_in()
				) {
					// reCAPTCHA if set.
					$recaptcha_site_key = $this->get_option( 'recaptcha_site_key' );
					if ( ! empty( $recaptcha_site_key ) ) {
						wp_enqueue_script(
							'google-recaptcha',
							'https://www.google.com/recaptcha/api.js?render=' . esc_attr( $recaptcha_site_key ),
							array(),
							'1.0.0',
							true
						);

						$localized_vars['recaptcha_site_key'] = $recaptcha_site_key;
					}
				}

				// Login.
				if ( ( $this->is_account_page() || $this->is_reset_password_page() ) && ! is_user_logged_in() ) {
					wp_enqueue_script(
						'digicommerce-scripts',
						DIGICOMMERCE_PLUGIN_URL . 'assets/js/front/account.js',
						array(),
						DIGICOMMERCE_VERSION,
						true
					);
				}

				// Choices select.
				if ( ( $this->is_account_page() && is_user_logged_in() ) || $this->is_single_product() || $this->is_checkout_page() ) {
					wp_enqueue_script(
						'choices',
						DIGICOMMERCE_PLUGIN_URL . 'assets/js/vendor/choices.js',
						array(),
						'11.0.2',
						true
					);
				}

				// Profile.
				if ( $this->is_account_page() && is_user_logged_in() ) {
					wp_enqueue_script(
						'digicommerce-profile',
						DIGICOMMERCE_PLUGIN_URL . 'assets/js/front/profile.js',
						array( 'choices' ),
						DIGICOMMERCE_VERSION,
						true
					);
				}

				// Reset password.
				if ( $this->is_reset_password_page() ) {
					wp_enqueue_script(
						'digicommerce-reset-password',
						DIGICOMMERCE_PLUGIN_URL . 'assets/js/front/reset-password.js',
						array(),
						DIGICOMMERCE_VERSION,
						true
					);
				}

				// Stripe.
				if ( $this->is_checkout_page() || ( $this->is_account_page() && is_user_logged_in() ) ) {
					if ( $this->is_stripe_enabled() ) {
						wp_enqueue_script( 'stripe-js', 'https://js.stripe.com/v3/', array(), null, true ); // phpcs:ignore

						$is_test_mode           = $this->get_option( 'stripe_mode', 'test' ) === 'test';
						$stripe_publishable_key = $is_test_mode ? $this->get_option( 'stripe_test_publishable_key', '' ) : $this->get_option( 'stripe_live_publishable_key', '' );

						$localized_vars['stripeEnabled']  = $this->is_stripe_enabled();
						$localized_vars['publishableKey'] = $stripe_publishable_key;
					}
				}

				// Checkout page.
				if ( $this->is_checkout_page() ) {
					// Initialize dependencies array.
					$dependencies = array( 'choices' );

					// Add Stripe.js as a dependency.
					if ( $this->is_stripe_enabled() ) {
						$dependencies[] = 'stripe-js';
					}

					// PayPal
					if ( $this->is_paypal_enabled() ) {
						// Check for subscriptions.
						$cart             = DigiCommerce_Checkout::instance();
						$cart_items       = $cart->get_cart_items();
						$has_subscription = false;

						foreach ( $cart_items as $item ) {
							if ( ! empty( $item['subscription_enabled'] ) ) {
								$has_subscription = true;
								break;
							}
						}

						// Build PayPal SDK parameters.
						$params = array(
							'client-id'  => $this->get_option( 'paypal_client_id' ),
							'currency'   => strtoupper( $this->get_option( 'currency', 'USD' ) ),
							'components' => 'buttons',
						);

						if ( $has_subscription ) {
							$params['vault']  = 'true';
							$params['intent'] = 'subscription';
						} else {
							$params['intent'] = 'capture';
						}

						if ( $this->get_option( 'paypal_sandbox', '0' ) ) {
							$params['buyer-country'] = 'US';
							$params['debug']         = 'false';
						}

						// Enqueue PayPal SDK.
						wp_enqueue_script(
							'paypal-sdk',
							add_query_arg( $params, 'https://www.paypal.com/sdk/js' ),
							array(),
							null, // phpcs:ignore
							true
						);

						// Get session data once
						$session_key  = $cart->get_current_session_key();
						$session_data = $cart->get_session( $session_key );

						// Add PayPal specific vars
						$localized_vars['paypalEnabled'] = true;
						$localized_vars['currency']      = $params['currency'];
						$localized_vars['cartItems']     = json_encode( $cart_items ); // phpcs:ignore
						$localized_vars['countries']     = DigiCommerce()->get_countries();
						$localized_vars['cartDiscount']  = isset( $session_data['discount'] ) ? json_encode( $session_data['discount'] ) : null; // phpcs:ignore
					}

					if ( ! DigiCommerce()->get_option( 'remove_taxes' ) ) {
						wp_enqueue_script(
							'digicommerce-vat',
							DIGICOMMERCE_PLUGIN_URL . 'assets/js/front/vat.js',
							array(),
							DIGICOMMERCE_VERSION,
							true
						);

						$localized_vars['businessCountry'] = DigiCommerce()->get_option( 'business_country' );
					}

					if ( ! empty( DigiCommerce()->get_option( 'modal_terms', '' ) ) ) {
						wp_enqueue_script(
							'digicommerce-modal',
							DIGICOMMERCE_PLUGIN_URL . 'assets/js/front/modal.js',
							array(),
							DIGICOMMERCE_VERSION,
							true
						);
					}

					if ( ! DigiCommerce()->get_option( 'remove_product' ) ) {
						wp_enqueue_script(
							'digicommerce-delete-button',
							DIGICOMMERCE_PLUGIN_URL . 'assets/js/front/delete-button.js',
							array(),
							DIGICOMMERCE_VERSION,
							true
						);
					}

					wp_enqueue_script(
						'digicommerce-checkout',
						DIGICOMMERCE_PLUGIN_URL . 'assets/js/front/checkout.js',
						$dependencies,
						DIGICOMMERCE_VERSION,
						true
					);

					if ( DigiCommerce()->get_option( 'login_during_checkout' ) ) {
						wp_enqueue_script(
							'digicommerce-login-checkout',
							DIGICOMMERCE_PLUGIN_URL . 'assets/js/front/login-checkout.js',
							array( 'digicommerce-checkout' ),
							DIGICOMMERCE_VERSION,
							true
						);
					}

					$localized_vars['order_nonce']          = wp_create_nonce( 'digicommerce_order_nonce' );
					$localized_vars['payment_success_page'] = get_permalink( $this->get_option( 'payment_success_page_id', '' ) );
					$localized_vars['empty_cart_template']  = $this->get_rendered_template( 'checkout/empty-cart.php' );
					$localized_vars['country_nonce']        = wp_create_nonce( 'digicommerce_country_nonce' );
					$localized_vars['cartItems']            = json_encode( DigiCommerce_Checkout::instance()->get_cart_items() ); // phpcs:ignore
				}

				// Download item script.
				if ( ( $this->is_account_page() && is_user_logged_in() ) || $this->is_payment_success_page() ) {
					wp_enqueue_script(
						'digicommerce-download-button',
						DIGICOMMERCE_PLUGIN_URL . 'assets/js/front/download-button.js',
						array(),
						DIGICOMMERCE_VERSION,
						true
					);

					$localized_vars['download_nonce'] = wp_create_nonce( 'digicommerce_download_nonce' );
				}

				// Single product.
				if ( $this->is_single_product() ) {
					wp_enqueue_script(
						'digicommerce-single-product',
						DIGICOMMERCE_PLUGIN_URL . 'assets/js/front/single-product.js',
						array(),
						DIGICOMMERCE_VERSION,
						true
					);

					$localized_vars['download_nonce'] = wp_create_nonce( 'digicommerce_download_nonce' );
				}

				// Default settings.
				$localized_vars['ajaxurl']        = admin_url( 'admin-ajax.php' );
				$localized_vars['i18n']           = $this->get_js_translations();
				$localized_vars['proVersion']     = class_exists( 'DigiCommerce_Pro' );
				$localized_vars['abandonedCart']  = DigiCommerce()->get_option( 'enable_abandoned_cart' );
				$localized_vars['enableSideCart'] = DigiCommerce()->get_option( 'enable_side_cart' );
				$localized_vars['autoOpen']       = DigiCommerce()->get_option( 'side_cart_trigger' );
				$localized_vars['removeTaxes']    = DigiCommerce()->get_option( 'remove_taxes' );

				// Single localization for all scripts.
				if ( ! $localized && ! empty( $localized_vars ) ) {
					// Determine which script to localize based on context
					$script_handle = 'digicommerce-scripts'; // default

					if ( $this->is_account_page() && is_user_logged_in() ) {
						$script_handle = 'digicommerce-profile';
					} elseif ( $this->is_checkout_page() ) {
						$script_handle = 'digicommerce-checkout';
					} elseif ( $this->is_single_product() ) {
						$script_handle = 'digicommerce-single-product';
					} elseif ( ( $this->is_account_page() && is_user_logged_in() ) || $this->is_payment_success_page() ) {
						$script_handle = 'digicommerce-download-button';
					}

					wp_localize_script(
						$script_handle,
						'digicommerceVars',
						$localized_vars
					);

					$localized = true;
				}
			}
		}

		/**
		 * Load base Tailwinds CSS without priorit to prevent theme conflicts
		 */
		public function base_css() {
			$should_load_css = (
				// Load everywhere if pro plugin exists and side cart is enabled.
				( class_exists( 'DigiCommerce_Pro' ) && DigiCommerce()->get_option( 'enable_side_cart' ) )
				// Or load only on plugin pages otherwise.
				|| $this->is_plugin_page()
			);

			// Check if styling is not disabled via option and filter.
			$should_load_css = $should_load_css
			&& ! DigiCommerce()->get_option( 'disable_styling' )
			&& apply_filters( 'digicommerce_style', true );

			if ( $should_load_css ) {
				wp_enqueue_style(
					'digicommerce-base',
					DIGICOMMERCE_PLUGIN_URL . 'assets/css/base.css',
					array(),
					DIGICOMMERCE_VERSION
				);
			}
		}

		/**
		 * Returns translations for JavaScript
		 */
		public function get_js_translations() {
			return array(
				'logging_in'            => esc_html__( 'Connecting...', 'digicommerce' ),
				'registering_in'        => esc_html__( 'Registering...', 'digicommerce' ),
				'invalid'               => esc_html__( 'Username or password invalid.', 'digicommerce' ),
				'sending_email'         => esc_html__( 'Sending email...', 'digicommerce' ),
				'password_requirements' => esc_html__( 'Please meet all password requirements', 'digicommerce' ),
				'resetting'             => esc_html__( 'Resetting...', 'digicommerce' ),
				'server_error'          => esc_html__( 'Server error occurred', 'digicommerce' ),
				'unknown_error'         => esc_html__( 'An unknown error occurred', 'digicommerce' ),
				'error'                 => esc_html__( 'An error occurred', 'digicommerce' ),
				'success'               => esc_html__( 'Operation successful', 'digicommerce' ),
				'required'              => esc_html__( 'This field is required', 'digicommerce' ),
				'select_country'        => esc_html__( 'Please select your country', 'digicommerce' ),
				'invalid_email'         => esc_html__( 'Please enter a valid email address', 'digicommerce' ),
				'processing_payment'    => esc_html__( 'Processing payment...', 'digicommerce' ),
				'payment_error'         => esc_html__( 'An error occurred during payment.', 'digicommerce' ),
				'required_fields'       => esc_html__( 'Please fill in all required fields.', 'digicommerce' ),
				'saving'                => esc_html__( 'Saving...', 'digicommerce' ),
				'updating'              => esc_html__( 'Updating...', 'digicommerce' ),
				'vat_number'            => esc_html__( 'VAT number must start with your country code', 'digicommerce' ),
				'vat_short'             => esc_html__( 'VAT number is too short', 'digicommerce' ),
				'vat_invalid'           => esc_html__( 'Invalid VAT number format. Please check the country code and format.', 'digicommerce' ),
				'downloading'           => esc_html__( 'Downloading...', 'digicommerce' ),
				'download_failed'       => esc_html__( 'Download failed', 'digicommerce' ),
				'select_option'         => esc_html__( 'Select an option', 'digicommerce' ),
				'purchase_for'          => esc_html__( 'Purchase for', 'digicommerce' ),
			);
		}

		/**
		 * Check if we are on a plugin page
		 */
		public function is_plugin_page() {
			return $this->is_account_page() ||
				$this->is_reset_password_page() ||
				$this->is_checkout_page() ||
				$this->is_payment_success_page() ||
				is_singular( 'digi_product' ) ||
				is_singular( 'digi_order' );
		}

		/**
		 * Check if we are on the login page
		 */
		public function is_login_page() {
			return $this->is_account_page() && ! is_user_logged_in();
		}

		/**
		 * Check if this is the account page
		 */
		public function is_account_page() {
			global $post;
			if ( ! $post ) {
				return false;
			}

			$page_id = $this->get_option( 'account_page_id' );
			return $page_id && $post->ID === $page_id;
		}

		/**
		 * Check if this is the password reset page
		 */
		public function is_reset_password_page() {
			global $post;
			if ( ! $post ) {
				return false;
			}

			$page_id = $this->get_option( 'reset_password_page_id' );
			return $page_id && $post->ID === $page_id;
		}

		/**
		 * Check if this is the checkout page
		 */
		public function is_checkout_page() {
			global $post;
			if ( ! $post ) {
				return false;
			}

			$page_id = $this->get_option( 'checkout_page_id' );
			return $page_id && $post->ID === $page_id;
		}

		/**
		 * Check if this is the payment success page
		 */
		public function is_payment_success_page() {
			global $post;
			if ( ! $post ) {
				return false;
			}

			$page_id = $this->get_option( 'payment_success_page_id' );
			return $page_id && $post->ID === $page_id;
		}

		/**
		 * Check if single product
		 */
		public function is_single_product() {
			return is_singular( 'digi_product' );
		}

		/**
		 * Protects admin access if enabled and run wizard
		 */
		public function admin_init() {
			if ( $this->get_option( 'block_admin' ) && ! current_user_can( 'administrator' ) && ! wp_doing_ajax() ) { // phpcs:ignore
				wp_safe_redirect( home_url() );
				exit;
			}

			// Wizard
			if ( is_admin() && ! $this->get_flag( 'digicommerce_setup_wizard_completed' ) ) {
				require_once DIGICOMMERCE_PLUGIN_DIR . 'includes/admin/class-digicommerce-wizard.php';
			}
		}

		/**
		 * Manages the display of the admin bar
		 *
		 * @param bool $show Show admin bar.
		 */
		public function handle_admin_bar( $show ) {
			if ( $this->get_option( 'block_admin' ) && ! current_user_can( 'administrator' ) ) { // phpcs:ignore
				return false;
			}
			return $show;
		}

		/**
		 * Initializes
		 */
		public function init() {
			new DigiCommerce_Shortcodes();

			// If Stripe enabled
			if ( $this->is_stripe_enabled() ) {
				require_once DIGICOMMERCE_PLUGIN_DIR . 'includes/gateways/class-digicommerce-stripe.php';

				// Load Stripe webhook handler
				require_once DIGICOMMERCE_PLUGIN_DIR . 'includes/gateways/class-digicommerce-stripe-webhook.php';
			}

			// If PayPal enabled
			if ( $this->is_paypal_enabled() ) {
				require_once DIGICOMMERCE_PLUGIN_DIR . 'includes/gateways/class-digicommerce-paypal.php';

				// Load PayPal webhook handler
				require_once DIGICOMMERCE_PLUGIN_DIR . 'includes/gateways/class-digicommerce-paypal-webhook.php';
			}
		}

		/**
		 * Add class in body if reCAPTCHA is enabled
		 */
		public function maybe_add_recaptcha_class() {
			if (
				! empty( $this->get_option( 'recaptcha_site_key' ) ) &&
				$this->is_plugin_page() &&
				(
					$this->is_account_page() ||
					$this->is_reset_password_page() ||
					( $this->is_checkout_page() && $this->get_option( 'login_during_checkout' ) ) ||
					( is_singular( 'digi_product' ) && DigiCommerce()->get_option( 'enable_reviews' ) )
				) &&
				! is_user_logged_in()
			) {
				add_filter(
					'body_class',
					function ( $classes ) {
						$classes[] = 'digi-captcha';
						return $classes;
					}
				);
			}
		}

		/**
		 * Returns pages
		 */
		private function get_pages_data() {
			return array(
				'my-account'      => array(
					'title'       => esc_html__( 'My Account', 'digicommerce' ),
					'content'     => '[digicommerce_account]',
					'option_name' => 'account_page_id',
				),
				'reset-password'  => array(
					'title'       => esc_html__( 'Reset Password', 'digicommerce' ),
					'content'     => '[digicommerce_reset_password]',
					'option_name' => 'reset_password_page_id',
				),
				'checkout'        => array(
					'title'       => esc_html__( 'Checkout', 'digicommerce' ),
					'content'     => '[digicommerce_checkout]',
					'option_name' => 'checkout_page_id',
				),
				'payment-success' => array(
					'title'       => esc_html__( 'Payment Success', 'digicommerce' ),
					'content'     => '[digicommerce_payment_success]',
					'option_name' => 'payment_success_page_id',
				),
			);
		}

		/**
		 * Add custom post states for DigiCommerce pages
		 *
		 * @param array   $post_states Post states.
		 * @param WP_Post $post Post object.
		 */
		public function add_page_state_labels( $post_states, $post ) {
			if ( 'page' === $post->post_type ) {
				$page_mappings = array(
					'account_page_id'         => esc_html__( 'Account Page', 'digicommerce' ),
					'reset_password_page_id'  => esc_html__( 'Password Reset Page', 'digicommerce' ),
					'checkout_page_id'        => esc_html__( 'Checkout Page', 'digicommerce' ),
					'payment_success_page_id' => esc_html__( 'Success Page', 'digicommerce' ),
				);

				foreach ( $page_mappings as $option_name => $label ) {
					if ( $post->ID === (int) $this->get_option( $option_name ) ) {
						$post_states[ 'digicommerce_' . $option_name ] = $label;
					}
				}
			}

			return $post_states;
		}

		/**
		 * Returns countries
		 */
		public function get_countries() {
			$file = DIGICOMMERCE_PLUGIN_DIR . 'includes/data/countries.php';
			if ( file_exists( $file ) ) {
				return include $file;
			}
			return array();
		}

		/**
		 * Returns currencies
		 */
		public function get_currencies() {
			$file = DIGICOMMERCE_PLUGIN_DIR . 'includes/data/currencies.php';
			if ( file_exists( $file ) ) {
				return include $file;
			}
			return array();
		}

		/**
		 * Gets a template from the plugin
		 *
		 * @param string $template_name Template name.
		 * @param array  $args Arguments.
		 * @param bool   $return_path Return path.
		 */
		public function get_template( $template_name, $args = array(), $return_path = false ) {
			if ( $args && is_array( $args ) ) {
				extract( $args ); // phpcs:ignore
			}

			// Locate template in the theme or plugin directory
			$template = locate_template(
				array(
					'digicommerce/' . $template_name,
					$template_name,
				)
			);

			// If not found in the theme, use the plugin directory
			if ( ! $template ) {
				$template = DIGICOMMERCE_PLUGIN_DIR . 'templates/' . $template_name;
			}

			// Validate the template path
			if ( file_exists( $template ) ) {
				if ( $return_path ) {
					return $template;
				}
				include $template;
				return;
			}

			return false;
		}

		/**
		 * Gets rendered template, used to get template in JS
		 *
		 * @param string $template_name Template name.
		 */
		public function get_rendered_template( $template_name ) {
			// Start output buffering
			ob_start();

			// Get and include the template
			$this->get_template( $template_name, '' );

			// Get the buffered content and clean the buffer
			$content = ob_get_clean();

			return $content;
		}

		/**
		 * Gets user's billing information
		 *
		 * @param int|null $user_id Optional user ID. If not provided, gets current user.
		 * @return array Billing information array or empty array if user not found
		 */
		public function get_billing_info( $user_id = null ) {
			// If no user_id provided, try to get current user
			if ( is_null( $user_id ) ) {
				if ( ! is_user_logged_in() ) {
					return array();
				}
				$user = wp_get_current_user();
			} else {
				$user = get_user_by( 'id', $user_id );
				if ( ! $user ) {
					return array();
				}
			}

			return array(
				'first_name' => $user->first_name ?? '',
				'last_name'  => $user->last_name ?? '',
				'email'      => $user->user_email ?? '',
				'phone'      => get_user_meta( $user->ID, 'billing_phone', true ) ?? '',
				'company'    => get_user_meta( $user->ID, 'billing_company', true ) ?? '',
				'address'    => get_user_meta( $user->ID, 'billing_address', true ) ?? '',
				'postcode'   => get_user_meta( $user->ID, 'billing_postcode', true ) ?? '',
				'city'       => get_user_meta( $user->ID, 'billing_city', true ) ?? '',
				'country'    => get_user_meta( $user->ID, 'billing_country', true ) ?? '',
				'vat_number' => get_user_meta( $user->ID, 'billing_vat_number', true ) ?? '',
			);
		}

		/**
		 * Helper function to get value with fallback
		 *
		 * @param string $checkout_value Checkout value.
		 * @param string $profile_value Profile value.
		 */
		public function get_billing_value( $checkout_value, $profile_value ) {
			if ( ! empty( $profile_value ) ) {
				return $profile_value;  // Profile value takes priority
			} elseif ( ! empty( $checkout_value ) ) {
				return $checkout_value; // Fallback to checkout value if no profile value
			}
			return ''; // Return empty string if both values are empty
		}

		/**
		 * Check if Stripe is enabled
		 */
		public function is_stripe_enabled() {
			$is_test_mode           = $this->get_option( 'stripe_mode', 'test' ) === 'test';
			$stripe_publishable_key = $is_test_mode
				? $this->get_option( 'stripe_test_publishable_key', '' )
				: $this->get_option( 'stripe_live_publishable_key', '' );
			$stripe_secret_key      = $is_test_mode
				? $this->get_option( 'stripe_test_secret_key', '' )
				: $this->get_option( 'stripe_live_secret_key', '' );

			// Both keys must be present for Stripe to be considered enabled
			return ! empty( $stripe_publishable_key ) && ! empty( $stripe_secret_key );
		}

		/**
		 * Check if PayPal is enabled
		 *
		 * @return bool
		 */
		public function is_paypal_enabled() {
			$paypal_client_id = $this->get_option( 'paypal_client_id', '' );
			$paypal_secret    = $this->get_option( 'paypal_secret', '' );

			// Both client ID and secret must be present for PayPal to be considered enabled
			return ! empty( $paypal_client_id ) && ! empty( $paypal_secret );
		}

		/**
		 * Add custom colors to head if set
		 */
		public function custom_colors() {
			$default_colors = array(
				'color_gold'          => '#ccb161',
				'color_yellow'        => '#ffe599',
				'color_border'        => '#caced9',
				'color_light_blue'    => '#e1e4ed',
				'color_light_blue_bg' => '#f6f7f9',
				'color_dark_blue'     => '#09053a',
				'color_dark_blue_10'  => '#E6E5EB',
				'color_dark_blue_20'  => '#BAB8C8',
				'color_hover_blue'    => '#362f85',
				'color_grey'          => '#646071',
				'color_dark_grey'     => '#5b5766',
			);

			$custom_colors = array(
				'color_gold'          => '--dc-gold',
				'color_yellow'        => '--dc-yellow',
				'color_border'        => '--dc-border',
				'color_light_blue'    => '--dc-light-blue',
				'color_light_blue_bg' => '--dc-light-blue-bg',
				'color_dark_blue'     => '--dc-dark-blue',
				'color_dark_blue_10'  => '--dc-dark-blue-10',
				'color_dark_blue_20'  => '--dc-dark-blue-20',
				'color_hover_blue'    => '--dc-hover-blue',
				'color_grey'          => '--dc-grey',
				'color_dark_grey'     => '--dc-dark-grey',
			);

			$css = '';
			foreach ( $custom_colors as $option_name => $var_name ) {
				$color = $this->get_option( $option_name );
				if ( ! empty( $color ) && $color !== $default_colors[ $option_name ] ) {
					$css .= $var_name . ': ' . $color . ';';
				}
			}

			if ( ! empty( $css ) ) {
				echo '<style>:root {' . esc_html( $css ) . '}</style>';
			}
		}

		/**
		 * Returns an array of allowed SVG elements and attributes.
		 *
		 * @return array
		 */
		public function allowed_svg_el() {
			return array(
				'svg'    => array(
					'class'   => true,
					'fill'    => true,
					'stroke'  => true,
					'viewbox' => true,
					'width'   => true,
					'height'  => true,
					'xmlns'   => true,
				),
				'path'   => array(
					'd'               => true,
					'fill'            => true,
					'stroke'          => true,
					'stroke-linecap'  => true,
					'stroke-linejoin' => true,
					'stroke-width'    => true,
				),
				'circle' => array(
					'cx'     => true,
					'cy'     => true,
					'r'      => true,
					'fill'   => true,
					'stroke' => true,
				),
				'line'   => array(
					'x1' => true,
					'y1' => true,
					'x2' => true,
					'y2' => true,
				),
				'rect'   => array(
					'width'  => true,
					'height' => true,
					'x'      => true,
					'y'      => true,
					'rx'     => true,
					'ry'     => true,
				),
			);
		}

		/**
		 * Adds dir="ltr" attribute when site is not in RTL mode
		 *
		 * @param string $lang_attr Language attributes.
		 * @return string Modified language attributes.
		 */
		public function attribute_to_html( $lang_attr ) {
			if ( ! is_rtl() ) {
				// Only add dir="ltr" when the site is NOT in RTL mode
				return $lang_attr . ' dir="ltr"';
			}

			return $lang_attr;
		}
	}
}

/**
 * Returns the main instance of the plugin
 */
function DigiCommerce() { // phpcs:ignore
	return DigiCommerce::instance();
}

// Starting the plugin
DigiCommerce();

/**
 * Global functions for ease of use
 */
function is_digicommerce_login() {
	$instance = DigiCommerce::instance();
	return method_exists( $instance, 'is_login_page' ) ? $instance->is_login_page() : false;
}

/**
 * Check if we are on the account page
 */
function is_digicommerce_account() {
	$instance = DigiCommerce::instance();
	return method_exists( $instance, 'is_account_page' ) ? $instance->is_account_page() : false;
}

/**
 * Check if we are on the password reset page
 */
function is_digicommerce_reset_pass() {
	$instance = DigiCommerce::instance();
	return method_exists( $instance, 'is_reset_password_page' ) ? $instance->is_reset_password_page() : false;
}

/**
 * Check if we are on the checkout page
 */
function is_digicommerce_checkout() {
	$instance = DigiCommerce::instance();
	return method_exists( $instance, 'is_checkout_page' ) ? $instance->is_checkout_page() : false;
}

/**
 * Check if we are on the payment success page
 */
function is_digicommerce_payment_success() {
	$instance = DigiCommerce::instance();
	return method_exists( $instance, 'is_payment_success_page' ) ? $instance->is_payment_success_page() : false;
}

/**
 * Check if we are on the single product page
 */
function is_digicommerce_single_product() {
	$instance = DigiCommerce::instance();
	return method_exists( $instance, 'is_single_product' ) ? $instance->is_single_product() : false;
}
