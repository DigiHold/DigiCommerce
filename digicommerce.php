<?php
/**
 * Plugin Name: DigiCommerce
 * Plugin URI: https://digicommerce.me/
 * Description: Powerful ecommerce plugin to sell digital products, services and online courses.
 * Version: 1.0.3
 * Author: DigiHold
 * Author URI: https://digihold.me?utm_source=wordpress.org&utm_medium=referral&utm_campaign=plugin_directory&utm_content=digicommerce
 * Text Domain: digicommerce
 * Domain Path: /languages
 * Requires at least: 6.0
 * Tested up to: 6.7
 * Requires PHP: 7.4
 * Network: false
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 *
 * @package DigiCommerce
 */

defined( 'ABSPATH' ) || exit;

// Define constants first.
if ( ! defined( 'DIGICOMMERCE_VERSION' ) ) {
	define( 'DIGICOMMERCE_VERSION', '1.0.3' );
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

			// Check if block theme
			if ( function_exists( 'wp_is_block_theme' ) && wp_is_block_theme() ) {
				require_once DIGICOMMERCE_PLUGIN_DIR . 'includes/class-digicommerce-theme-compatibility.php';
				DigiCommerce_Theme_Compatibility::instance();
			}

			if ( is_admin() ) {
				// Plugin settings.
				require_once DIGICOMMERCE_PLUGIN_DIR . 'includes/admin/class-digicommerce-settings.php';

				// Import/export.
				require_once DIGICOMMERCE_PLUGIN_DIR . 'includes/admin/class-digicommerce-import-export.php';

				// Pro Addons
				if ( ! class_exists( 'DigiCommerce_Pro' ) ) {
					require_once DIGICOMMERCE_PLUGIN_DIR . 'includes/admin/class-digicommerce-pro-addons.php';
				}

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

			// Review notice.
			require_once DIGICOMMERCE_PLUGIN_DIR . 'includes/admin/class-digicommerce-review-notice.php';

			// Installation.
			register_activation_hook( __FILE__, array( $this, 'install' ) );

			// Clean up sessions are deactivation.
			register_deactivation_hook( __FILE__, array( $this, 'deactivate' ) );

			// Scripts and styles.
			add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ), 10 );

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

			// Add ReCAPTCHA body class.
			add_action( 'template_redirect', array( $this, 'maybe_add_recaptcha_class' ) );

			// Add body classes
			add_filter( 'body_class', array( $this, 'body_classes' ) );

			// Add custom color in head.
			add_action( 'wp_enqueue_scripts', array( $this, 'custom_colors' ), 10 );

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

			// Create table.
			$sql = "CREATE TABLE $table_name (
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
			if ( ! $this->get_flag( 'pages_created' ) ) {
				$this->create_pages(); // Create pages only if not already created.
				$this->set_flag( 'pages_created', true ); // Set the flag in the custom table.
			}

			// Call tables installation
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
			$table_exists = $wpdb->get_var( // phpcs:ignore
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

			// Determine if Gutenberg is active
			$use_gutenberg = function_exists( 'register_block_type' ) &&
							! class_exists( 'Classic_Editor' ) &&
							! isset( $GLOBALS['syntaxhighlighter_settings'] ) &&
							! function_exists( 'amt_has_disabled_gutenberg' );

			foreach ( $pages as $slug => $page_data ) {
				$page_exists = get_page_by_path( $slug );

				if ( ! $page_exists ) {
					$translated_title = wp_strip_all_tags( $page_data['title'] );
					$translated_slug  = sanitize_title( $translated_title );

					// Format the content based on whether Gutenberg is active
					if ( $use_gutenberg ) {
						// Create content with Gutenberg shortcode block
						$post_content = '<!-- wp:shortcode -->' . $page_data['content'] . '<!-- /wp:shortcode -->';
					} else {
						// Create content with regular shortcode
						$post_content = $page_data['content'];
					}

					$page_id = wp_insert_post(
						array(
							'post_title'   => $translated_title,
							'post_content' => wp_kses_post( $post_content ),
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
		 * Gets a flag from the JSON structure
		 *
		 * @param string $flag_name Flag name.
		 * @param mixed  $default_val Default value if flag doesn't exist.
		 * @return mixed Flag value or default
		 */
		public function get_flag( $flag_name, $default_val = false ) {
			global $wpdb;
			$table_name = $wpdb->prefix . 'digicommerce';

			// Get the flags JSON data
			$flags_json = $wpdb->get_var( // phpcs:ignore
				$wpdb->prepare(
					"SELECT option_value FROM {$table_name} WHERE option_name = %s", // phpcs:ignore
					'digicommerce_setup'
				)
			);

			if ( $flags_json ) {
				$flags = json_decode( $flags_json, true );
				return isset( $flags[ $flag_name ] ) ? $flags[ $flag_name ] : $default_val;
			}

			return $default_val;
		}

		/**
		 * Sets a flag in the JSON structure
		 *
		 * @param string $flag_name Flag name.
		 * @param mixed  $value Flag value.
		 * @return bool Whether the operation was successful
		 */
		public function set_flag( $flag_name, $value ) {
			global $wpdb;
			$table_name = $wpdb->prefix . 'digicommerce';

			// Get current flags
			$flags_json = $wpdb->get_var( // phpcs:ignore
				$wpdb->prepare(
					"SELECT option_value FROM {$table_name} WHERE option_name = %s", // phpcs:ignore
					'digicommerce_setup'
				)
			);

			$flags = $flags_json ? json_decode( $flags_json, true ) : array();

			// Update the specific flag
			$flags[ $flag_name ] = $value;

			// Encode back to JSON
			$updated_json = wp_json_encode( $flags );

			// Check if the record exists
			$exists = $wpdb->get_var( // phpcs:ignore
				$wpdb->prepare(
					"SELECT COUNT(*) FROM {$table_name} WHERE option_name = %s", // phpcs:ignore
					'digicommerce_setup'
				)
			);

			if ( $exists ) {
				// Update existing record
				$result = $wpdb->update( // phpcs:ignore
					$table_name,
					array( 'option_value' => $updated_json ),
					array( 'option_name' => 'digicommerce_setup' ),
					array( '%s' ),
					array( '%s' )
				);
			} else {
				// Insert new record
				$result = $wpdb->insert( // phpcs:ignore
					$table_name,
					array(
						'option_name'  => 'digicommerce_setup',
						'option_value' => $updated_json,
						'created_at'   => current_time( 'mysql' ),
						'updated_at'   => current_time( 'mysql' ),
					),
					array( '%s', '%s', '%s', '%s' )
				);
			}

			return false !== $result;
		}

		/**
		 * Loads options from database
		 */
		private function load_options() {
			global $wpdb;
			$table_name = $wpdb->prefix . 'digicommerce';

			$row = $wpdb->get_row( // phpcs:ignore
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

			// Only proceed if the value is not empty (but allow 0)
			if ( ! empty( $value ) || $value === 0 || $value === '0' ) {
				$this->options[ $key ] = $value;

				$serialized_options = maybe_serialize( $this->options );

				// First check if the record exists
				$exists = $wpdb->get_var( // phpcs:ignore
					$wpdb->prepare(
						"SELECT COUNT(*) FROM {$table_name} WHERE option_name = %s", // phpcs:ignore
						'digicommerce_options'
					)
				);

				if ( $exists ) {
					$result = $wpdb->replace( // phpcs:ignore
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
					$result = $wpdb->insert( // phpcs:ignore
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
				$result = $wpdb->replace( // phpcs:ignore
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
		 * Check for db version
		 *
		 * @param string $addon addon.
		 */
		public function get_db_version( $addon ) {
			$versions = get_option( 'digicommerce_db_versions', array() );
			return isset( $versions[ $addon ] ) ? $versions[ $addon ] : false;
		}

		/**
		 * Update db version
		 *
		 * @param string $addon addon.
		 * @param string $version version.
		 */
		public function update_db_version( $addon, $version ) {
			$versions           = get_option( 'digicommerce_db_versions', array() );
			$versions[ $addon ] = $version;
			update_option( 'digicommerce_db_versions', $versions );
		}

		/**
		 * Enqueue scripts and styles
		 */
		public function enqueue_scripts() {
			static $localized = false;

			$should_load_css = (
				// Load everywhere if pro plugin exists and side cart is enabled.
				( class_exists( 'DigiCommerce_Pro' ) && $this->get_option( 'enable_side_cart' ) )
				// Or if pro plugin exists and affiliation is enabled and we're on an affiliate page
				|| ( class_exists( 'DigiCommerce_Pro' ) && $this->get_option( 'enable_affiliation' ) && $this->is_affiliate_page() )
				// Or load only on plugin pages otherwise.
				|| $this->is_plugin_page()
			);

			// Check if styling is not disabled via option and filter.
			$should_load_css = $should_load_css
			&& ! $this->get_option( 'disable_styling' )
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
						( is_singular( 'digi_product' ) && $this->get_option( 'enable_reviews' ) )
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

					if ( ! $this->get_option( 'remove_taxes' ) ) {
						wp_enqueue_script(
							'digicommerce-vat',
							DIGICOMMERCE_PLUGIN_URL . 'assets/js/front/vat.js',
							array(),
							DIGICOMMERCE_VERSION,
							true
						);

						$localized_vars['businessCountry'] = $this->get_option( 'business_country' );
					}

					if ( ! empty( $this->get_option( 'modal_terms', '' ) ) ) {
						wp_enqueue_script(
							'digicommerce-modal',
							DIGICOMMERCE_PLUGIN_URL . 'assets/js/front/modal.js',
							array(),
							DIGICOMMERCE_VERSION,
							true
						);
					}

					if ( ! $this->get_option( 'remove_product' ) ) {
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

					if ( $this->get_option( 'login_during_checkout' ) ) {
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
				$localized_vars['proVersion']     = class_exists( 'DigiCommerce_Pro' ) ? true : false;
				$localized_vars['abandonedCart']  = $this->get_option( 'enable_abandoned_cart' );
				$localized_vars['enableSideCart'] = (bool) $this->get_option( 'enable_side_cart', false );
				$localized_vars['autoOpen']       = (bool) $this->get_option( 'side_cart_trigger', false );
				$localized_vars['removeTaxes']    = $this->get_option( 'remove_taxes' );

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
				( class_exists( 'DigiCommerce_Pro' ) && $this->get_option( 'enable_side_cart' ) )
				// Or load only on plugin pages otherwise.
				|| $this->is_plugin_page()
			);

			// Check if styling is not disabled via option and filter.
			$should_load_css = $should_load_css
			&& ! $this->get_option( 'disable_styling' )
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
				
				// PayPal specific messages
				'paypal_failed'                    => esc_html__( 'PayPal payment failed', 'digicommerce' ),
				'paypal_cancelled'                 => esc_html__( 'Payment cancelled', 'digicommerce' ),
				'paypal_processing_failed'         => esc_html__( 'Payment processing failed', 'digicommerce' ),
				'paypal_subscription_creation_failed' => esc_html__( 'Failed to create subscription', 'digicommerce' ),
				'paypal_plan_creation_failed'      => esc_html__( 'Failed to create PayPal plan', 'digicommerce' ),
				'paypal_order_creation_failed'     => esc_html__( 'Failed to create PayPal order', 'digicommerce' ),
				'checkout_form_not_found'          => esc_html__( 'Checkout form not found', 'digicommerce' ),
				'invalid_order_total'              => esc_html__( 'Invalid order total', 'digicommerce' ),
				'multiple_subscriptions_error'     => esc_html__( 'PayPal does not support multiple subscription products in one transaction.', 'digicommerce' ),
				'mixed_cart_error'                 => esc_html__( 'PayPal subscriptions cannot be combined with one-time products. Please checkout subscription and one-time products separately.', 'digicommerce' ),

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
				$this->is_affiliate_page() ||
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
		 * Check if this is an affiliate page
		 */
		public function is_affiliate_page() {
			global $post;
			if ( ! $post ) {
				return false;
			}

			$page_id = $this->get_option( 'affiliate_account_page_id' );
			return $page_id && $post->ID === $page_id;
		}

		/**
		 * Protects admin access if enabled and run wizard
		 */
		public function admin_init() {
			if ( $this->get_option( 'block_admin' ) &&
				! wp_doing_ajax() &&
				! ( current_user_can( 'manage_options' ) ||
					current_user_can( 'edit_posts' ) ||
					current_user_can( 'edit_pages' ) )
			) {
				wp_safe_redirect( home_url() );
				exit;
			}

			// Wizard
			if ( is_admin() && ! $this->get_flag( 'wizard_completed' ) ) {
				require_once DIGICOMMERCE_PLUGIN_DIR . 'includes/admin/class-digicommerce-wizard.php';
			}
		}

		/**
		 * Manages the display of the admin bar
		 *
		 * @param bool $show Show admin bar.
		 */
		public function handle_admin_bar( $show ) {
			if ( $this->get_option( 'block_admin' ) &&
				! ( current_user_can( 'manage_options' ) ||
					current_user_can( 'edit_posts' ) ||
					current_user_can( 'edit_pages' ) )
			) {
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
					( is_singular( 'digi_product' ) && $this->get_option( 'enable_reviews' ) )
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
		 * Add DigiCommerce page body classes
		 *
		 * @param array $classes Existing body classes.
		 * @return array Modified body classes.
		 */
		public function body_classes( $classes ) {
			if ( $this->is_account_page() ) {
				$classes[] = 'digi-account';
			} elseif ( $this->is_checkout_page() ) {
				$classes[] = 'digi-checkout';
			} elseif ( $this->is_payment_success_page() ) {
				$classes[] = 'digi-payment';
			} elseif ( $this->is_reset_password_page() ) {
				$classes[] = 'digi-reset';
			} elseif ( $this->is_single_product() ) {
				$classes[] = 'digi-product';
			} elseif ( is_post_type_archive( 'digi_product' ) ) {
				$classes[] = 'digi-archive';
			}

			return $classes;
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
				'state'      => get_user_meta( $user->ID, 'billing_state', true ) ?? '',
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
				// Register and enqueue
				wp_register_style( 'digicommerce-theme-vars', false, array(), DIGICOMMERCE_VERSION );
				wp_enqueue_style( 'digicommerce-theme-vars' );
				wp_add_inline_style( 'digicommerce-theme-vars', ':root {' . esc_html( $css ) . '}' );
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
