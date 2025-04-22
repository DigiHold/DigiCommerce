<?php
defined( 'ABSPATH' ) || exit;

/**
 * Orders class for DigiCommerce
 * Handles all order-related functionality using custom database tables
 */
class DigiCommerce_Orders {
	/**
	 * Singleton instance
	 *
	 * @var DigiCommerce_Orders
	 */
	private static $instance = null;

	/**
	 * Main orders table name
	 *
	 * @var string
	 */
	private $table_orders;

	/**
	 * Order items table name
	 *
	 * @var string
	 */
	private $table_items;

	/**
	 * Order notes table name
	 *
	 * @var string
	 */
	private $table_notes;

	/**
	 * Order meta table name
	 *
	 * @var string
	 */
	private $table_meta;

	/**
	 * Order billing details table name
	 *
	 * @var string
	 */
	private $table_billing;

	/**
	 * Returns the singleton instance
	 *
	 * @return DigiCommerce_Orders
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
		global $wpdb;
		// Set up table names
		$this->table_orders  = $wpdb->prefix . 'digicommerce_orders';
		$this->table_items   = $wpdb->prefix . 'digicommerce_order_items';
		$this->table_notes   = $wpdb->prefix . 'digicommerce_order_notes';
		$this->table_meta    = $wpdb->prefix . 'digicommerce_order_meta';
		$this->table_billing = $wpdb->prefix . 'digicommerce_order_billing';

		// General
		add_action( 'admin_menu', array( $this, 'add_menu_items' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
		add_action( 'admin_init', array( $this, 'handle_edit_order_form' ) );
		add_action( 'admin_init', array( $this, 'handle_actions' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_frontend_assets' ) );

		// Screen options
		add_action( 'load-digicommerce_page_digi-orders', array( $this, 'add_screen_options' ) );
		add_filter( 'set-screen-option', array( $this, 'set_screen_option' ), 10, 3 );

		// Custom footer texts
		add_filter( 'admin_footer_text', array( $this, 'footer_text' ), 99 );
		add_filter( 'update_footer', array( $this, 'update_footer' ), 99 );
	}

	/**
	 * Install database tables
	 */
	public function install_tables() {
		global $wpdb;
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$charset_collate = $wpdb->get_charset_collate();

		// Main orders table
		$sql_orders = "CREATE TABLE IF NOT EXISTS {$this->table_orders} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            order_number varchar(32) NOT NULL,
            user_id bigint(20) unsigned NOT NULL,
            status varchar(20) NOT NULL DEFAULT 'processing',
            payment_method varchar(50) DEFAULT NULL,
            refund_id varchar(255) DEFAULT NULL,
            total decimal(10,2) NOT NULL DEFAULT 0.00,
            subtotal decimal(10,2) NOT NULL DEFAULT 0.00,
            discount_amount decimal(10,2) DEFAULT 0.00,
            discount_type varchar(20) DEFAULT NULL,
            discount_code varchar(32) DEFAULT NULL,
            vat decimal(10,2) DEFAULT 0.00,
            vat_rate decimal(4,2) DEFAULT 0.00,
            vat_number varchar(50) DEFAULT NULL,
            date_created datetime NOT NULL,
            date_modified datetime NOT NULL,
            token varchar(64) DEFAULT NULL,
            token_expiry datetime DEFAULT NULL,
            session_key varchar(64) DEFAULT NULL,
            PRIMARY KEY (id),
            KEY order_number (order_number),
            KEY user_id (user_id),
            KEY status (status),
            KEY date_created (date_created)
        ) $charset_collate;";

		// Order items table
		$sql_items = "CREATE TABLE IF NOT EXISTS {$this->table_items} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            order_id bigint(20) unsigned NOT NULL,
            product_id bigint(20) unsigned NOT NULL,
            name varchar(255) NOT NULL,
            variation_name varchar(255) DEFAULT NULL,
            price decimal(10,2) NOT NULL,
            vat_amount decimal(10,2) NOT NULL,
            discount_amount decimal(10,2) DEFAULT 0.00,
            total decimal(10,2) NOT NULL,
            subscription_enabled tinyint(1) DEFAULT 0,
            subscription_period varchar(20) DEFAULT NULL,
            subscription_free_trial text DEFAULT NULL,
            subscription_signup_fee decimal(10,2) DEFAULT 0.00,
			meta longtext DEFAULT NULL,
            PRIMARY KEY (id),
            KEY order_id (order_id),
            KEY product_id (product_id)
        ) $charset_collate;";

		// Order notes table
		$sql_notes = "CREATE TABLE IF NOT EXISTS {$this->table_notes} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            order_id bigint(20) unsigned NOT NULL,
            content text NOT NULL,
            author varchar(100) NOT NULL,
            date datetime NOT NULL,
            PRIMARY KEY (id),
            KEY order_id (order_id)
        ) $charset_collate;";

		// Order meta table
		$sql_meta = "CREATE TABLE IF NOT EXISTS {$this->table_meta} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            order_id bigint(20) unsigned NOT NULL,
            meta_key varchar(255) NOT NULL,
            meta_value longtext,
            PRIMARY KEY (id),
            KEY order_id (order_id),
            KEY meta_key (meta_key)
        ) $charset_collate;";

		// Billing details table
		$sql_billing = "CREATE TABLE IF NOT EXISTS {$this->table_billing} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            order_id bigint(20) unsigned NOT NULL,
            first_name varchar(100) DEFAULT NULL,
            last_name varchar(100) DEFAULT NULL,
            company varchar(100) DEFAULT NULL,
            email varchar(200) DEFAULT NULL,
            phone varchar(50) DEFAULT NULL,
            address text DEFAULT NULL,
            city varchar(100) DEFAULT NULL,
            postcode varchar(20) DEFAULT NULL,
            country varchar(2) DEFAULT NULL,
            vat_number varchar(50) DEFAULT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY order_id (order_id)
        ) $charset_collate;";

		// Create tables
		dbDelta( $sql_orders );
		dbDelta( $sql_items );
		dbDelta( $sql_notes );
		dbDelta( $sql_meta );
		dbDelta( $sql_billing );
	}

	/**
	 * Add admin menu items
	 */
	public function add_menu_items() {
		add_submenu_page(
			'digicommerce-settings',
			esc_html__( 'Orders', 'digicommerce' ),
			esc_html__( 'Orders', 'digicommerce' ),
			'manage_options',
			'digi-orders',
			array( $this, 'render_orders_page' )
		);
	}

	/**
	 * Creates a new order
	 *
	 * @param array $order_data Order data.
	 * @throws Exception If the order creation fails.
	 */
	public function create_order( $order_data ) {
		global $wpdb;

		$order_number = $this->generate_order_number();

		// Get session ID from checkout class
		$session_key = DigiCommerce_Checkout::instance()->get_current_session_key();

		$token        = wp_generate_password( 32, false );
		$token_expiry = gmdate( 'Y-m-d H:i:s', time() + HOUR_IN_SECONDS );

		// Start transaction
		$wpdb->query( 'START TRANSACTION' ); // phpcs:ignore

		try {
			// Get seller's country and buyer's country
			$business_country = DigiCommerce()->get_option( 'business_country' );
			$buyer_country    = $order_data['billing_details']['country'] ?? '';
			$countries        = DigiCommerce()->get_countries();
			$vat_number       = $order_data['billing_details']['vat_number'] ?? '';

			// Initialize VAT rate and amount
			$tax_rate   = 0;
			$vat_amount = 0;

			// Only calculate VAT if taxes are not disabled
			if ( ! DigiCommerce()->get_option( 'remove_taxes' ) ) {
				if ( $buyer_country === $business_country ) {
					// Domestic sale: Always charge seller's country VAT
					$tax_rate   = $countries[ $business_country ]['tax_rate'] ?? 0;
					$vat_amount = $order_data['subtotal'] * $tax_rate;
				} elseif ( ! empty( $countries[ $buyer_country ]['eu'] ) && ! empty( $countries[ $business_country ]['eu'] ) ) {
					// EU cross-border sale
					if ( empty( $vat_number ) || ! $this->validate_vat_number( $vat_number, $buyer_country ) ) {
						// No valid VAT number - charge buyer's country rate
						$tax_rate   = $countries[ $buyer_country ]['tax_rate'] ?? 0;
						$vat_amount = $order_data['subtotal'] * $tax_rate;
					}
					// With valid VAT number - no VAT (vat_amount and tax_rate remain 0)
				}
				// Non-EU sale - no VAT (vat_amount and tax_rate remain 0)
			}

			// Calculate total with VAT
			$total_with_vat = $order_data['subtotal'] + $vat_amount;

			// Apply discount if it exists
			$discount_amount = $order_data['discount_amount'] ?? 0;
			if ( $discount_amount > 0 ) {
				$total_with_vat -= $discount_amount;
			}

			$vat_number = null;
			if ( ! empty( $order_data['billing_details']['vat_number'] ) ) {
				$vat_number = strtoupper( preg_replace( '/[^A-Z0-9]/', '', $order_data['billing_details']['vat_number'] ) );
			}

			// Create token for order access
			$token        = wp_generate_password( 32, false );
			$token_expiry = gmdate( 'Y-m-d H:i:s', time() + HOUR_IN_SECONDS );

			// Insert main order
			$inserted = $wpdb->insert( // phpcs:ignore
				$this->table_orders,
				array(
					'order_number'    => $order_number,
					'user_id'         => $order_data['user_id'],
					'status'          => 'processing',
					'payment_method'  => $order_data['payment_method'] ?? '',
					'total'           => $total_with_vat,
					'subtotal'        => $order_data['subtotal'],
					'discount_amount' => $order_data['discount_amount'] ?? 0,
					'discount_type'   => $order_data['discount_type'],
					'discount_code'   => $order_data['discount_code'],
					'vat'             => $vat_amount,
					'vat_rate'        => $tax_rate,
					'vat_number'      => $vat_number,
					'date_created'    => current_time( 'mysql' ),
					'date_modified'   => current_time( 'mysql' ),
					'token'           => $token,
					'token_expiry'    => $token_expiry,
					'session_key'     => $session_key,
				),
				array(
					'%s',
					'%d',
					'%s',
					'%s',
					'%f',
					'%f',
					'%f',
					'%s',
					'%s',
					'%f',
					'%f',
					'%s',
					'%s',
					'%s',
					'%s',
					'%s',
					'%s',
				)
			);

			if ( ! $inserted ) {
				throw new Exception( 'Failed to insert order' );
			}

			$order_id = $wpdb->insert_id;

			// Insert order items
			if ( ! empty( $order_data['items'] ) ) {
				$total_discount = $order_data['discount_amount'] ?? 0;
				$subtotal       = $order_data['subtotal'];

				foreach ( $order_data['items'] as $item ) {
					// Calculate proportional discount for this item
					$item_discount = 0;
					if ( $total_discount > 0 && $subtotal > 0 ) {
						// Calculate discount proportion based on item price relative to subtotal
						$item_discount = ( $item['price'] / $subtotal ) * $total_discount;
					}

					$item_price = $item['price'];
					$item_total = $item_price * ( 1 + $tax_rate );

					$wpdb->insert( // phpcs:ignore
						$this->table_items,
						array(
							'order_id'                => $order_id,
							'product_id'              => $item['product_id'],
							'name'                    => $item['name'],
							'variation_name'          => $item['variation_name'] ?? null,
							'price'                   => $item_price,
							'vat_amount'              => $item_price * $tax_rate,
							'discount_amount'         => $item_discount,
							'total'                   => $item_total - $item_discount,
							'subscription_enabled'    => $item['subscription_enabled'] ?? 0,
							'subscription_period'     => $item['subscription_period'] ?? null,
							'subscription_free_trial' => isset( $item['subscription_free_trial'] ) ? maybe_serialize( $item['subscription_free_trial'] ) : null,
							'subscription_signup_fee' => $item['subscription_signup_fee'] ?? 0.00,
							'meta'                    => ! empty( $item['meta'] ) ? maybe_serialize( $item['meta'] ) : null,
						),
						array( '%d', '%d', '%s', '%s', '%f', '%f', '%f', '%f', '%d', '%s', '%s', '%f', '%s' )
					);
				}
			}

			// Insert billing details
			if ( ! empty( $order_data['billing_details'] ) ) {
				$billing_data = array_merge(
					array( 'order_id' => $order_id ),
					$order_data['billing_details']
				);

				$wpdb->query( // phpcs:ignore
					$wpdb->prepare( "INSERT INTO {$this->table_billing} (order_id, first_name, last_name, company, address, city, postcode, country, email, phone, vat_number)  VALUES (%d, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s) ON DUPLICATE KEY UPDATE  first_name = VALUES(first_name), last_name = VALUES(last_name), company = VALUES(company), address = VALUES(address), city = VALUES(city), postcode = VALUES(postcode), country = VALUES(country), email = VALUES(email), phone = VALUES(phone), vat_number = VALUES(vat_number)", $billing_data['order_id'], $billing_data['first_name'], $billing_data['last_name'], $billing_data['company'], $billing_data['address'], $billing_data['city'], $billing_data['postcode'], $billing_data['country'], $billing_data['email'], $billing_data['phone'], $billing_data['vat_number'] ) // phpcs:ignore
				);
			}

			// Handle subscriptions if DigiCommerce Pro is active
			if ( class_exists( 'DigiCommerce_Pro' ) ) {
				foreach ( $order_data['items'] as $item ) {
					if ( ! empty( $item['subscription_enabled'] ) ) {
						// Create subscription with data from the item
						DigiCommerce_Pro_Subscriptions::instance()->maybe_create_subscription(
							array(
								'order_id'                => $order_id,
								'user_id'                 => $order_data['user_id'],
								'product_id'              => $item['product_id'],
								'name'                    => $item['name'],
								'price'                   => $item['price'],
								'payment_method'          => $order_data['payment_method'],
								'billing_details'         => $order_data['billing_details'],
								'subscription_enabled'    => $item['subscription_enabled'],
								'subscription_period'     => $item['subscription_period'],
								'subscription_free_trial' => $item['subscription_free_trial'],
								'subscription_signup_fee' => $item['subscription_signup_fee'],
							)
						);
					}
				}
			}

			$wpdb->query( 'COMMIT' ); // phpcs:ignore

			// Record coupon usage if discount was applied
			if ( ! empty( $order_data['discount_code'] ) && ! empty( $order_data['discount_amount'] ) ) {
				if ( class_exists( 'DigiCommerce_Pro' ) ) {
					// Get coupon ID from code
					$coupon = DigiCommerce_Pro_Coupons::instance()->get_coupon_by_code( $order_data['discount_code'] );
					if ( $coupon ) {
						DigiCommerce_Pro_Coupons::instance()->record_coupon_usage(
							$order_id,
							$coupon['id'],
							$order_data['discount_amount']
						);
					}
				}
			}

			// Send email notifications to admin only if enabled in settings
			if ( DigiCommerce()->get_option( 'email_new_order_admin' ) ) {
				DigiCommerce_Emails::instance()->send_new_order_admin( $order_id );
			}

			do_action( 'digicommerce_order_created', $order_id, $order_data );

			return $order_id;

		} catch ( Exception $e ) {
			$wpdb->query( 'ROLLBACK' ); // phpcs:ignore
			return false;
		}
	}

	/**
	 * Gets order data
	 *
	 * @param int $order_id Order ID.
	 */
	public function get_order( $order_id ) {
		global $wpdb;

		// Get main order data
		$order = $wpdb->get_row( // phpcs:ignore
			$wpdb->prepare( "SELECT * FROM {$this->table_orders} WHERE id = %d", $order_id ), // phpcs:ignore
			ARRAY_A
		);

		if ( ! $order ) {
			return false;
		}

		// Get order items
		$order['items'] = $wpdb->get_results( // phpcs:ignore
			$wpdb->prepare( "SELECT * FROM {$this->table_items} WHERE order_id = %d", $order_id ), // phpcs:ignore
			ARRAY_A
		);

		// Unserialize if it exists
		if ( ! empty( $order['items'] ) ) {
			foreach ( $order['items'] as &$item ) {
				$item['meta']                    = ! empty( $item['meta'] ) ? maybe_unserialize( $item['meta'] ) : array();
				$item['subscription_free_trial'] = ! empty( $item['subscription_free_trial'] ) ?
					maybe_unserialize( $item['subscription_free_trial'] ) : null;
			}
		}

		// Get billing details
		$order['billing_details'] = $wpdb->get_row( // phpcs:ignore
			$wpdb->prepare( "SELECT * FROM {$this->table_billing} WHERE order_id = %d", $order_id ), // phpcs:ignore
			ARRAY_A
		);

		// Get order notes
		$order['notes'] = $wpdb->get_results( // phpcs:ignore
			$wpdb->prepare( "SELECT * FROM {$this->table_notes} WHERE order_id = %d ORDER BY date DESC", $order_id ), // phpcs:ignore
			ARRAY_A
		);

		return $order;
	}

	/**
	 * Gets user orders
	 *
	 * @param int   $user_id User ID.
	 * @param array $args    Arguments.
	 */
	public function get_user_orders( $user_id, $args = array() ) {
		global $wpdb;

		$defaults = array(
			'status'  => array( 'processing', 'completed', 'cancelled', 'refunded' ),
			'orderby' => 'date_created',
			'order'   => 'DESC',
			'limit'   => -1,
			'offset'  => 0,
		);

		$args = wp_parse_args( $args, $defaults );

		$orderby = esc_sql( $args['orderby'] );
		$order   = esc_sql( $args['order'] );
		$limit   = $args['limit'] > 0 ? 'LIMIT ' . intval( $args['limit'] ) : '';
		$offset  = $args['offset'] > 0 ? 'OFFSET ' . intval( $args['offset'] ) : '';

		// Start building the query
		$select = "SELECT o.id as order_id, o.*, b.id as billing_id, b.* FROM {$this->table_orders} o 
				LEFT JOIN {$this->table_billing} b ON o.id = b.order_id";

		// Handle status filtering
		if ( ! empty( $args['status'] ) ) {
			if ( is_array( $args['status'] ) ) {
				// For array of statuses, we need to handle the IN clause specially
				$status_placeholders = array();
				$status_values       = array();

				foreach ( $args['status'] as $status ) {
					$status_placeholders[] = '%s';
					$status_values[]       = $status;
				}

				$query = $wpdb->prepare( "$select WHERE o.user_id = %d AND o.status IN (" . implode( ',', $status_placeholders ) . ") ORDER BY o.{$orderby} {$order} {$limit} {$offset}", array_merge( array( $user_id ), $status_values ) ); // phpcs:ignore
			} else {
				// Single status
				$query = $wpdb->prepare( "$select WHERE o.user_id = %d AND o.status = %s ORDER BY o.{$orderby} {$order} {$limit} {$offset}", $user_id, $args['status'] ); // phpcs:ignore
			}
		} else {
			// No status filter
			$query = $wpdb->prepare( "$select WHERE o.user_id = %d ORDER BY o.{$orderby} {$order} {$limit} {$offset}", $user_id ); // phpcs:ignore
		}

		$results = $wpdb->get_results( $query, ARRAY_A ); // phpcs:ignore

		if ( empty( $results ) ) {
			return array();
		}

		// Get items for all orders
		$order_ids = array_filter( wp_list_pluck( $results, 'id' ) );

		// If there are no valid IDs, return an empty array to avoid running a query
		if ( empty( $order_ids ) ) {
			return $results;
		}

		// Safely construct the query for items
		$placeholders = implode( ',', array_fill( 0, count( $order_ids ), '%d' ) );
		$items_query  = $wpdb->prepare( "SELECT * FROM {$this->table_items} WHERE order_id IN ($placeholders)", ...$order_ids ); // phpcs:ignore
		$items = $wpdb->get_results( $items_query, ARRAY_A ); // phpcs:ignore

		// Organize items by order
		$items_by_order = array();
		foreach ( $items as $item ) {
			$items_by_order[ $item['order_id'] ][] = $item;
		}

		// Add items to orders
		foreach ( $results as &$order ) {
			$order['items'] = isset( $items_by_order[ $order['id'] ] ) ? $items_by_order[ $order['id'] ] : array();
		}

		return $results;
	}

	/**
	 * Generates a unique order number
	 */
	private function generate_order_number() {
		global $wpdb;

		$prefix = apply_filters( 'digicommerce_order_number_prefix', '#' );

		// Get the last order number
		$last_order = $wpdb->get_var( "SELECT order_number FROM {$this->table_orders} ORDER BY id DESC LIMIT 1" ); // phpcs:ignore

		if ( $last_order ) {
			// Extract the numeric part
			$numeric_part = (int) str_replace( $prefix, '', $last_order );
			// Increment it
			$next_number = $numeric_part + 1;
		} else {
			// Start from 1
			$next_number = 1;
		}

		// Format the number
		if ( $next_number < 10000 ) {
			$formatted_number = $prefix . sprintf( '%04d', $next_number );
		} else {
			$formatted_number = $prefix . $next_number;
		}

		return $formatted_number;
	}

	/**
	 * Add order note
	 *
	 * @param int    $order_id     Order ID.
	 * @param string $note_content Note content.
	 * @param string $author       Author.
	 */
	public function add_order_note( $order_id, $note_content, $author = '' ) {
		global $wpdb;

		if ( empty( $author ) ) {
			$current_user = wp_get_current_user();
			$author       = $current_user->display_name;
		}

		return $wpdb->insert( // phpcs:ignore
			$this->table_notes,
			array(
				'order_id' => $order_id,
				'content'  => $note_content,
				'author'   => $author,
				'date'     => current_time( 'mysql' ),
			),
			array( '%d', '%s', '%s', '%s' )
		);
	}

	/**
	 * Update billing details
	 *
	 * @param int   $order_id     Order ID.
	 * @param array $billing_data Billing data.
	 */
	public function update_billing_details( $order_id, $billing_data ) {
		global $wpdb;

		return $wpdb->update( // phpcs:ignore
			$this->table_billing,
			$billing_data,
			array( 'order_id' => $order_id ),
			array_fill( 0, count( $billing_data ), '%s' ),
			array( '%d' )
		);
	}

	/**
	 * Verify order access
	 *
	 * @param int    $order_id Order ID.
	 * @param string $token    Token.
	 */
	public function verify_order_access( $order_id, $token = null ) {
		// If token is provided (success page), only verify token
		if ( null !== $token ) {
			$has_token_access = $this->verify_order_token( $order_id, $token );
			return $has_token_access;
		}

		// For regular order access (account page), verify user ownership
		if ( current_user_can( 'manage_options' ) ) {
			return true;
		}

		if ( ! is_user_logged_in() ) {
			return false;
		}

		global $wpdb;
		$count = $wpdb->get_var( // phpcs:ignore
			$wpdb->prepare( "SELECT COUNT(*) FROM {$this->table_orders} WHERE id = %d AND user_id = %d", $order_id, get_current_user_id() ) // phpcs:ignore
		);

		return ( $count > 0 );
	}

	/**
	 * Verify order token
	 *
	 * @param int    $order_id Order ID.
	 * @param string $token    Token.
	 */
	public function verify_order_token( $order_id, $token ) {
		global $wpdb;

		// Fetch order details
		$order = $wpdb->get_row( // phpcs:ignore
			$wpdb->prepare( "SELECT * FROM {$this->table_orders} WHERE id = %d", $order_id ), // phpcs:ignore
			ARRAY_A
		);

		if ( ! $order ) {
			return false;
		}

		// 1. Token validation
		if ( ! hash_equals( $order['token'], $token ) ) {
			return false;
		}

		// 2. Token expiry check
		if ( strtotime( $order['token_expiry'] ) <= time() ) {
			return false;
		}

		return true;
	}

	/**
	 * Render orders page in admin
	 */
	public function render_orders_page() {
		// Add permission check
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to view orders.', 'digicommerce' ) );
		}

		// Handle actions
		if ( isset( $_GET['action'] ) && 'edit' === $_GET['action'] && isset( $_GET['id'] ) ) { // phpcs:ignore
			// Verify nonce when accessing the edit page
			if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( sanitize_key( $_GET['_wpnonce'] ), 'edit_order_' . intval( $_GET['id'] ) ) ) {
				wp_die( esc_html__( 'Security check failed.', 'digicommerce' ) );
			}

			// Render the Edit Order page
			$this->render_edit_order_page( intval( $_GET['id'] ) ); // phpcs:ignore
		} else {
			// Render the Orders List page
			$this->render_orders_list_page();
		}
	}

	/**
	 * Add screen options
	 */
	public function add_screen_options() {
		$screen = get_current_screen();

		// Only add to our orders page
		if ( ! is_null( $screen ) && 'digicommerce_page_digi-orders' === $screen->id ) {
			add_screen_option(
				'per_page',
				array(
					'label'   => esc_html__( 'Orders per page', 'digicommerce' ),
					'default' => 20,
					'option'  => 'digicommerce_orders_per_page',
				)
			);
		}
	}

	/**
	 * Save screen options
	 *
	 * @param string $status Status.
	 * @param string $option Option.
	 * @param int    $value Value.
	 */
	public function set_screen_option( $status, $option, $value ) {
		if ( 'digicommerce_orders_per_page' === $option ) {
			return $value;
		}
		return $status;
	}

	/**
	 * Handle actions
	 */
	public function handle_actions() {
		if ( ! isset( $_GET['page'] ) || 'digi-orders' !== $_GET['page'] ) {
			return;
		}

		// Single action handling
		if ( isset( $_GET['action'] ) && ! empty( $_GET['id'] ) ) {
			$order_id = intval( $_GET['id'] );
			$action   = sanitize_text_field( $_GET['action'] ); // phpcs:ignore

			// Skip nonce verification for edit action since it uses form nonce
			if ( 'edit' === $action ) {
				return;
			}

			// Verify nonce with combined action and order ID
			if ( ! isset( $_GET['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['nonce'] ) ), "digi_order_{$action}_{$order_id}" ) ) {
				wp_die( esc_html__( 'Security check failed.', 'digicommerce' ) );
			}

			switch ( $action ) {
				case 'trash':
					$this->update_order_status( $order_id, 'trash' );
					wp_safe_redirect(
						add_query_arg(
							array(
								'page' => 'digi-orders',
							),
							admin_url( 'admin.php' )
						)
					);
					break;
				case 'restore':
					$this->update_order_status( $order_id, 'processing' );
					wp_redirect( admin_url( 'admin.php?page=digi-orders&status=trash' ) );
					break;
				case 'delete_permanently':
					$this->delete_order_permanently( $order_id );
					wp_redirect( admin_url( 'admin.php?page=digi-orders&status=trash' ) );
					break;
			}
			exit;
		}

		// Handle bulk actions
		if ( ( isset( $_GET['action'] ) && '-1' !== $_GET['action'] ) ||
		( isset( $_GET['action2'] ) && '-1' !== $_GET['action2'] ) ) {
			// Verify the bulk actions nonce
			if ( ! check_admin_referer( 'digi_orders_bulk_action', '_wpnonce_bulk' ) ) {
				wp_die( esc_html__( 'Security check failed.', 'digicommerce' ) );
			}

			// Determine the action to process
			$action = ( '-1' !== $_GET['action'] ) ? sanitize_text_field( $_GET['action'] ) : sanitize_text_field( $_GET['action2'] ); // phpcs:ignore
			$ids    = isset( $_GET['post'] ) ? array_map( 'intval', $_GET['post'] ) : array();

			if ( ! empty( $ids ) ) {
				foreach ( $ids as $id ) {
					switch ( $action ) {
						case 'mark_completed':
							$this->update_order_status( $id, 'completed' );
							break;
						case 'mark_processing':
							$this->update_order_status( $id, 'processing' );
							break;
						case 'mark_cancelled':
							$this->update_order_status( $id, 'cancelled' );
							break;
						case 'mark_refunded':
							$this->update_order_status( $id, 'refunded' );
							break;
						case 'trash':
							$this->delete_order( $id );
							break;
						case 'restore':
							$this->restore_order( $id );
							break;
						case 'delete':
							$this->delete_order_permanently( $id );
							break;
					}
				}
			}

			// Redirect after processing
			wp_redirect( add_query_arg( 'bulk_action_done', '1', admin_url( 'admin.php?page=digi-orders' ) ) );
			exit;
		}
	}

	/**
	 * Render orders list page in admin
	 */
	public function render_orders_list_page() {
		global $wpdb;

		// Get screen options
		$screen   = get_current_screen();
		$per_page = (int) get_user_meta( get_current_user_id(), 'digicommerce_orders_per_page', true );
		if ( ! $per_page ) {
			$per_page = 20; // Default value
		}

		// Get total counts for each status
		$total_all        = $wpdb->get_var( "SELECT COUNT(*) FROM {$this->table_orders} WHERE status != 'trash'" ); // phpcs:ignore
		$total_completed  = $wpdb->get_var( "SELECT COUNT(*) FROM {$this->table_orders} WHERE status = 'completed'" ); // phpcs:ignore
		$total_processing = $wpdb->get_var( "SELECT COUNT(*) FROM {$this->table_orders} WHERE status = 'processing'" ); // phpcs:ignore
		$total_cancelled  = $wpdb->get_var( "SELECT COUNT(*) FROM {$this->table_orders} WHERE status = 'cancelled'" ); // phpcs:ignore
		$total_refunded   = $wpdb->get_var( "SELECT COUNT(*) FROM {$this->table_orders} WHERE status = 'refunded'" ); // phpcs:ignore
		$total_trash      = $wpdb->get_var( "SELECT COUNT(*) FROM {$this->table_orders} WHERE status = 'trash'" ); // phpcs:ignore

		// Get the current status filter
		$valid_statuses = array( 'all', 'completed', 'processing', 'cancelled', 'refunded', 'trash' );
		$current_status = isset( $_GET['status'] ) ? sanitize_text_field( wp_unslash( $_GET['status'] ) ) : 'all';

		// Validate the status against allowed values
		if ( ! in_array( $current_status, $valid_statuses, true ) ) {
			$current_status = 'all'; // Default to 'all' if an invalid status is provided
		}

		// Get total items for current view
		$where = '1=1';
		if ( 'all' !== $current_status ) {
			$where .= $wpdb->prepare( ' AND status = %s', $current_status );
		} else {
			$where .= " AND status != 'trash'";
		}

		// Calculate total items for current view
		$total_items = $wpdb->get_var( "SELECT COUNT(*) FROM {$this->table_orders} WHERE {$where}" ); // phpcs:ignore

		$pagenum = $this->get_pagenum();
		$offset  = ( $pagenum - 1 ) * $per_page;

		// Get search query with nonce verification if search is performed
		$search_query = '';
		if ( isset( $_GET['s'] ) && isset( $_GET['is_search'] ) ) {
			// When performing a dedicated search, verify the nonce
			if ( ! isset( $_GET['search_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['search_nonce'] ) ), 'digicommerce_orders_search' ) ) {
				wp_die( esc_html__( 'Security check failed.', 'digicommerce' ) );
			}
			$search_query = sanitize_text_field( wp_unslash( $_GET['s'] ) );
		}

		// Fetch orders with appropriate filtering
		$orders = $this->get_orders(
			array(
				'offset'        => $offset,
				'limit'         => $per_page,
				'status'        => 'all' === $current_status ? '' : $current_status,
				'exclude_trash' => 'all' === $current_status,
				's'             => $search_query,
			)
		);

		// Include the template
		include DIGICOMMERCE_PLUGIN_DIR . 'admin/orders-list.php';
	}

	/**
	 * Update order status
	 *
	 * @param int    $order_id Order ID.
	 * @param string $status   New status.
	 * @return bool
	 * @throws Exception If refund fails.
	 */
	public function update_order_status( $order_id, $status ) {
		global $wpdb;

		$valid_statuses = array( 'processing', 'completed', 'cancelled', 'refunded', 'trash' );

		if ( ! in_array( $status, $valid_statuses, true ) ) {
			return false;
		}

		// Get current status before updating
		$current_status = $wpdb->get_var( // phpcs:ignore
			$wpdb->prepare( "SELECT status FROM {$this->table_orders} WHERE id = %d", $order_id ) // phpcs:ignore
		);

		// If status is being changed to refunded, process the refund first
		if ( 'refunded' === $status && 'refunded' !== $current_status ) {
			try {
				// Get order details
				$order = $this->get_order( $order_id );
				if ( ! $order ) {
					throw new Exception( 'Order not found' );
				}

				// Process refund based on payment method
				switch ( $order['payment_method'] ) {
					case 'stripe':
						$stripe         = DigiCommerce_Stripe::instance();
						$refund_results = $stripe->process_refund(
							$order_id,
							$order['total']
						);

						// Check refund results
						if ( empty( $refund_results ) ) {
							throw new Exception( 'No refund results returned' );
						}

						// Store refund details in order meta
						$wpdb->insert( // phpcs:ignore
							$this->table_meta,
							array(
								'order_id'   => $order_id,
								'meta_key'   => 'refund_details', // phpcs:ignore
								'meta_value' => maybe_serialize( $refund_results ), // phpcs:ignore
							),
							array( '%d', '%s', '%s' )
						);

						// Store individual refund IDs if available
						if ( ! empty( $refund_results['one_time']['refund_id'] ) ) {
							$wpdb->insert( // phpcs:ignore
								$this->table_meta,
								array(
									'order_id'   => $order_id,
									'meta_key'   => 'stripe_refund_id', // phpcs:ignore
									'meta_value' => $refund_results['one_time']['refund_id'], // phpcs:ignore
								),
								array( '%d', '%s', '%s' )
							);
						}

						if ( ! empty( $refund_results['subscription']['refund_id'] ) ) {
							$wpdb->insert( // phpcs:ignore
								$this->table_meta,
								array(
									'order_id'   => $order_id,
									'meta_key'   => 'stripe_subscription_refund_id', // phpcs:ignore
									'meta_value' => $refund_results['subscription']['refund_id'], // phpcs:ignore
								),
								array( '%d', '%s', '%s' )
							);
						}

						// Add note about the refund
						$note = 'Order refunded via Stripe.';

						$this->add_order_note( $order_id, trim( $note ) );
						break;
					case 'paypal':
						// Handle PayPal refund
						$paypal         = DigiCommerce_PayPal::instance();
						$refund_results = $paypal->process_refund( $order_id, $order['total'] );

						// Check refund results
						if ( empty( $refund_results ) ) {
							throw new Exception( 'No refund results returned' );
						}

						// Store refund details in order meta
						$wpdb->insert( // phpcs:ignore
							$this->table_meta,
							array(
								'order_id'   => $order_id,
								'meta_key'   => 'refund_details', // phpcs:ignore
								'meta_value' => maybe_serialize( $refund_results ), // phpcs:ignore
							),
							array( '%d', '%s', '%s' )
						);

						// Add note about the refund
						$note = 'Order refunded via PayPal.';

						$this->add_order_note( $order_id, trim( $note ) );
						break;

					default:
						throw new Exception( 'Unsupported payment method for refund: ' . $order['payment_method'] );
				}
			} catch ( Exception $e ) {
				$this->add_order_note( $order_id, 'Refund failed: ' . $e->getMessage() );
				return false;
			}
		}

		// For non-refund status changes
		$updated = $wpdb->update( // phpcs:ignore
			$this->table_orders,
			array(
				'status'        => $status,
				'date_modified' => current_time( 'mysql' ),
			),
			array( 'id' => $order_id ),
			array( '%s', '%s' ),
			array( '%d' )
		);

		if ( $updated ) {
			do_action( 'digicommerce_order_status_updated', $order_id, $status, $current_status );
			return true;
		}

		return false;
	}

	/**
	 * Delete orders
	 *
	 * @param int $order_id Order ID.
	 */
	public function delete_order( $order_id ) {
		global $wpdb;

		// Update the order status to 'trash'
		$updated = $wpdb->update( // phpcs:ignore
			$this->table_orders,
			array( 'status' => 'trash' ),
			array( 'id' => $order_id ),
			array( '%s' ),
			array( '%d' )
		);

		return false !== $updated;
	}

	/**
	 * Restore an order from trash
	 *
	 * @param int $order_id Order ID.
	 * @return bool
	 */
	public function restore_order( $order_id ) {
		global $wpdb;

		$updated = $wpdb->update( // phpcs:ignore
			$this->table_orders,
			array( 'status' => 'processing' ), // Default status after restoring
			array( 'id' => $order_id ),
			array( '%s' ),
			array( '%d' )
		);

		return false !== $updated;
	}

	/**
	 * Permanently delete an order
	 *
	 * @param int $order_id Order ID.
	 * @return bool
	 */
	public function delete_order_permanently( $order_id ) {
		global $wpdb;

		$deleted = $wpdb->delete( // phpcs:ignore
			$this->table_orders,
			array( 'id' => $order_id ),
			array( '%d' )
		);

		if ( $deleted ) {
			do_action( 'digicommerce_order_deleted', $order_id );
		}

		return false !== $deleted;
	}

	/**
	 * Get subscription data for order
	 *
	 * @param int $order_id Order ID.
	 */
	public function get_order_subscription_data( $order_id ) {
		if ( ! class_exists( 'DigiCommerce_Pro' ) ) {
			return false;
		}

		global $wpdb;
		return $wpdb->get_row( // phpcs:ignore
			$wpdb->prepare(
				"SELECT s.* FROM {$wpdb->prefix}digicommerce_subscriptions s
            INNER JOIN {$wpdb->prefix}digicommerce_subscription_items si 
            ON s.id = si.subscription_id
            WHERE si.order_id = %d",
				$order_id
			),
			ARRAY_A
		);
	}

	/**
	 * Render order item page in admin
	 *
	 * @param int $order_id Order ID.
	 */
	public function render_edit_order_page( $order_id ) {
		if ( ! $order_id || ! is_numeric( $order_id ) ) {
			wp_die( esc_html__( 'Invalid order ID.', 'digicommerce' ) );
		}

		$order = $this->get_order( $order_id );
		if ( ! $order ) {
			wp_die( esc_html__( 'Order not found.', 'digicommerce' ) );
		}

		// Include your custom edit page
		include DIGICOMMERCE_PLUGIN_DIR . 'admin/edit-order.php';
	}

	/**
	 * Handle the order edit form submission
	 */
	public function handle_edit_order_form() {
		// Check if this is an order edit form submission
		if ( ! isset( $_POST['edit_order_nonce_field'] ) ) {
			return;
		}

		// Verify nonce
		if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['edit_order_nonce_field'] ) ), 'edit_order_nonce' ) ) {
			wp_die( esc_html__( 'Security check failed.', 'digicommerce' ) );
		}

		// Check permissions
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to edit orders.', 'digicommerce' ) );
		}

		global $wpdb;

		// Get order ID from URL
		$order_id = isset( $_GET['id'] ) ? intval( $_GET['id'] ) : 0;
		if ( ! $order_id ) {
			wp_die( esc_html__( 'Invalid order ID.', 'digicommerce' ) );
		}

		// Get current order status
		$current_order  = $this->get_order( $order_id );
		$current_status = $current_order['status'];

		// Update order status if changed
		if ( isset( $_POST['order_status'] ) ) {
			$new_status     = sanitize_text_field( $_POST['order_status'] ); // phpcs:ignore
			$valid_statuses = array( 'processing', 'completed', 'cancelled', 'refunded' );

			if ( in_array( $new_status, $valid_statuses ) ) {
				$this->update_order_status( $order_id, $new_status );

				// Send cancelled email if status changed to cancelled and email notification is enabled
				if ( 'cancelled' === $new_status && 'cancelled' !== $current_status && DigiCommerce()->get_option( 'email_order_cancelled' ) ) {
					DigiCommerce_Emails::instance()->send_order_cancelled( $order_id );
				}

				// Send refunded email if status changed to refunded and email notification is enabled
				if ( 'refunded' === $new_status && 'refunded' !== $current_status && DigiCommerce()->get_option( 'email_order_refunded' ) ) {
					DigiCommerce_Emails::instance()->send_order_refunded( $order_id );
				}
			}
		}

		// Update billing details
		$billing_fields = array(
			'first_name' => 'sanitize_text_field',
			'last_name'  => 'sanitize_text_field',
			'email'      => 'sanitize_email',
			'phone'      => 'sanitize_text_field',
			'company'    => 'sanitize_text_field',
			'address'    => 'sanitize_textarea_field',
			'city'       => 'sanitize_text_field',
			'postcode'   => 'sanitize_text_field',
			'country'    => 'sanitize_text_field',
			'vat_number' => 'sanitize_text_field',
		);

		$billing_data = array();
		foreach ( $billing_fields as $field => $sanitize_function ) {
			if ( isset( $_POST[ 'billing_' . $field ] ) ) {
				$billing_data[ $field ] = $sanitize_function( $_POST[ 'billing_' . $field ] ); // phpcs:ignore
			}
		}

		if ( ! empty( $billing_data ) ) {
			$this->update_billing_details( $order_id, $billing_data );

			// Get user ID from orders table
			$user_id = $wpdb->get_var( // phpcs:ignore
				$wpdb->prepare( "SELECT user_id FROM {$this->table_orders} WHERE id = %d", $order_id ) // phpcs:ignore
			);

			if ( $user_id ) {
				// First update WordPress core user data
				$userdata = array(
					'ID'         => $user_id,
					'first_name' => $billing_data['first_name'],
					'last_name'  => $billing_data['last_name'],
					'user_email' => $billing_data['email'],
				);

				// Then update user meta fields
				foreach ( $billing_data as $field => $value ) {
					$meta_key = 'billing_' . $field;
					update_user_meta( $user_id, $meta_key, $value );
				}
			}
		}

		// Handle note
		if ( ! empty( $_POST['order_note'] ) ) {
			$note_content = sanitize_textarea_field( $_POST['order_note'] ); // phpcs:ignore
			$this->add_order_note( $order_id, $note_content );
		}

		// Set update message
		$redirect_url = add_query_arg(
			array(
				'page'     => 'digi-orders',
				'action'   => 'edit',
				'id'       => $order_id,
				'_wpnonce' => wp_create_nonce( 'edit_order_' . $order_id ),
				'updated'  => '1',
			),
			admin_url( 'admin.php' )
		);

		wp_redirect( $redirect_url ); // phpcs:ignore
		exit;
	}

	/**
	 * Get orders for admin list
	 *
	 * @param array $args Arguments for fetching orders.
	 */
	private function get_orders( $args = array() ) {
		global $wpdb;

		$defaults = array(
			'offset'  => 0,
			'limit'   => 20,
			'orderby' => 'date_created',
			'order'   => 'DESC',
			'status'  => '',
			's'       => '', // Search query
		);

		$args = wp_parse_args( $args, $defaults );

		$where = 'WHERE 1=1';

		// Exclude trash for "all" status
		if ( 'all' === $args['status'] || '' === $args['status'] ) {
			$where .= " AND o.status != 'trash'";
		} elseif ( ! empty( $args['status'] ) ) {
			$where .= $wpdb->prepare( ' AND o.status = %s', $args['status'] );
		}

		// Search query
		if ( ! empty( $args['s'] ) ) {
			$search = '%' . $wpdb->esc_like( $args['s'] ) . '%';
			$where .= $wpdb->prepare(
				' AND (o.order_number LIKE %s OR b.first_name LIKE %s OR b.last_name LIKE %s)',
				$search,
				$search,
				$search
			);
		}

		// Sanitize orderby and order parameters
		$allowed_orderby = array( 'id', 'order_number', 'user_id', 'status', 'total', 'date_created', 'date_modified' );
		$orderby_column  = in_array( $args['orderby'], $allowed_orderby ) ? $args['orderby'] : 'date_created';
		$order_direction = strtoupper( $args['order'] ) === 'ASC' ? 'ASC' : 'DESC';

		// Construct the query with fully prepared SQL
		$query = $wpdb->prepare( "SELECT o.*, b.first_name, b.last_name FROM {$this->table_orders} o LEFT JOIN {$this->table_billing} b ON o.id = b.order_id $where ORDER BY o." . $orderby_column . " " . $order_direction . " LIMIT %d OFFSET %d", $args['limit'], $args['offset'] ); // phpcs:ignore

		return $wpdb->get_results( $query, ARRAY_A ); // phpcs:ignore
	}

	/**
	 * Get current admin page number
	 */
	private function get_pagenum() {
		// Only allow pagination for users with appropriate permissions
		if ( ! current_user_can( 'manage_options' ) ) {
			return 1;
		}

		$pagenum = 1;

		// Check if paged parameter exists and verify nonce when necessary
		if ( isset( $_REQUEST['paged'] ) ) {
			// Only verify nonce if this is a form submission or explicit navigation
			if ( isset( $_REQUEST['_wpnonce'] ) && wp_verify_nonce( sanitize_key( $_REQUEST['_wpnonce'] ), 'digi_orders_pagination' ) ) {
				$pagenum = absint( $_REQUEST['paged'] );
			} elseif ( ! isset( $_REQUEST['_wpnonce'] ) && is_admin() ) {
				// Allow pagination from WP admin navigation links which don't include nonces
				$pagenum = absint( $_REQUEST['paged'] );
			}
		}

		return max( 1, $pagenum );
	}

	/**
	 * Correct format for EU countries
	 *
	 * @param string $city City name.
	 * @param string $postal_code Postal code.
	 * @param string $country_code Country code.
	 * @param array  $countries List of countries.
	 */
	public function format_city_postal( $city, $postal_code, $country_code, $countries ) {
		// Check if the country is in the EU
		$is_eu = isset( $countries[ $country_code ]['eu'] ) ? $countries[ $country_code ]['eu'] : false;

		// Return formatted address based on region
		if ( $is_eu ) {
			return "{$postal_code}, {$city}";
		} else {
			return "{$city}, {$postal_code}";
		}
	}

	/**
	 * Enqueue admin assets
	 *
	 * @param string $hook Current page hook.
	 */
	public function enqueue_admin_assets( $hook ) {
		if ( strpos( $hook, 'digi-orders' ) === false ) {
			return;
		}

		wp_enqueue_style(
			'digicommerce-orders-admin',
			DIGICOMMERCE_PLUGIN_URL . 'assets/css/admin/orders-admin.css',
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
			'digicommerce-orders-admin',
			DIGICOMMERCE_PLUGIN_URL . 'assets/js/admin/orders-admin.js',
			array( 'choices' ),
			DIGICOMMERCE_VERSION,
			true
		);
	}

	/**
	 * Enqueue frontend assets
	 *
	 * @param string $hook Current page hook.
	 */
	public function enqueue_frontend_assets( $hook ) {
		// Only enqueue on account page
		if ( ! DigiCommerce()->is_account_page() ) {
			return;
		}

		// Only enqueue when viewing an order or subscription
		if ( ! isset( $_GET['view-order'] ) && ! isset( $_GET['view-subscription'] ) ) { // phpcs:ignore
			return;
		}

		wp_enqueue_script(
			'html2canvas',
			DIGICOMMERCE_PLUGIN_URL . 'assets/js/vendor/html2canvas.js',
			array(),
			'1.4.1',
			true
		);

		wp_enqueue_script(
			'jspdf',
			DIGICOMMERCE_PLUGIN_URL . 'assets/js/vendor/jspdf.js',
			array(),
			'3.0.1',
			true
		);

		wp_enqueue_script(
			'digicommerce-pdf-generator',
			DIGICOMMERCE_PLUGIN_URL . 'assets/js/front/pdf-generator.js',
			array( 'html2canvas', 'jspdf' ),
			DIGICOMMERCE_VERSION,
			true
		);

		// Localize the script
		wp_localize_script(
			'digicommerce-pdf-generator',
			'digicommercePDF',
			array(
				'i18n' => array(
					'unknown'           => esc_html__( 'Unknown', 'digicommerce' ),
					'invoiceId'         => esc_html__( 'Invoice ID', 'digicommerce' ),
					'date'              => esc_html__( 'Date', 'digicommerce' ),
					'nextDate'          => esc_html__( 'Next Payment', 'digicommerce' ),
					'from'              => esc_html__( 'From', 'digicommerce' ),
					'billTo'            => esc_html__( 'Bill To', 'digicommerce' ),
					'orderDetails'      => esc_html__( 'Order Details', 'digicommerce' ),
					'product'           => esc_html__( 'Product', 'digicommerce' ),
					'total'             => esc_html__( 'Total', 'digicommerce' ),
					'totalLabel'        => esc_html__( 'Total:', 'digicommerce' ),
					'invoice'           => esc_html__( 'invoice', 'digicommerce' ),
					'allRightsReserved' => str_replace(
						array( '{year}', '{site}' ),
						array( date( 'Y' ), esc_html( get_bloginfo( 'name' ) ) ), // phpcs:ignore
						wp_kses_post( DigiCommerce()->get_option( 'invoices_footer', esc_html__( 'Copyright © {year} {site} All rights reserved.', 'digicommerce' ) ) )
					),
					'generating'        => esc_html__( 'Generating PDF...', 'digicommerce' ),
					'errorMessage'      => esc_html__( 'Failed to generate PDF. Please try again.', 'digicommerce' ),
				),
			)
		);
	}

	/**
	 * Validate VAT number
	 *
	 * @param string $vat_number VAT number.
	 * @param string $country_code Country code.
	 */
	public function validate_vat_number( $vat_number, $country_code ) {
		// Remove any spaces or special characters
		$vat_number = strtoupper( preg_replace( '/[^A-Z0-9]/', '', $vat_number ) );

		// Check if VAT number starts with country code
		if ( ! str_starts_with( $vat_number, $country_code ) ) {
			return false;
		}

		// Define VAT number formats for EU countries
		$formats = array(
			'AT' => '/^ATU[0-9]{8}$/',
			'BE' => '/^BE[0-9]{10}$/',
			'BG' => '/^BG[0-9]{9,10}$/',
			'CY' => '/^CY[0-9]{8}[A-Z]$/',
			'CZ' => '/^CZ[0-9]{8,10}$/',
			'DE' => '/^DE[0-9]{9}$/',
			'DK' => '/^DK[0-9]{8}$/',
			'EE' => '/^EE[0-9]{9}$/',
			'EL' => '/^EL[0-9]{9}$/',
			'ES' => '/^ES[A-Z0-9][0-9]{7}[A-Z0-9]$/',
			'FI' => '/^FI[0-9]{8}$/',
			'FR' => '/^FR[0-9A-Z]{2}[0-9]{9}$/',
			'HR' => '/^HR[0-9]{11}$/',
			'HU' => '/^HU[0-9]{8}$/',
			'IE' => '/^IE[0-9][A-Z0-9][0-9]{5}[A-Z]$/',
			'IT' => '/^IT[0-9]{11}$/',
			'LT' => '/^LT([0-9]{9}|[0-9]{12})$/',
			'LU' => '/^LU[0-9]{8}$/',
			'LV' => '/^LV[0-9]{11}$/',
			'MT' => '/^MT[0-9]{8}$/',
			'NL' => '/^NL[0-9]{9}B[0-9]{2}$/',
			'PL' => '/^PL[0-9]{10}$/',
			'PT' => '/^PT[0-9]{9}$/',
			'RO' => '/^RO[0-9]{2,10}$/',
			'SE' => '/^SE[0-9]{12}$/',
			'SI' => '/^SI[0-9]{8}$/',
			'SK' => '/^SK[0-9]{10}$/',
		);

		// Check if country has a defined format
		if ( ! isset( $formats[ $country_code ] ) ) {
			return false;
		}

		// Validate against the country's format
		return (bool) preg_match( $formats[ $country_code ], $vat_number );
	}

	/**
	 * Customize admin footer
	 *
	 * @param string $text Footer text.
	 */
	public function footer_text( $text ) {
		$screen = get_current_screen();

		if ( 'digicommerce_page_digi-orders' === $screen->id ) {
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
	 * @param string $version Version string.
	 */
	public function update_footer( $version ) {
		$screen = get_current_screen();

		if ( 'digicommerce_page_digi-orders' === $screen->id ) {
			$name = class_exists( 'DigiCommerce_Pro' ) ? 'DigiCommerce Pro' : 'DigiCommerce';

			$version .= sprintf( ' | %1$s %2$s', $name, DIGICOMMERCE_VERSION );
		}

		return $version;
	}
}

// Initialize the class
DigiCommerce_Orders::instance();
