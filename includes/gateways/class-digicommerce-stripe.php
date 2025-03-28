<?php
/**
 * Stripe Payment Gateway for DigiCommerce
 */
class DigiCommerce_Stripe {
	/**
	 * Singleton instance
	 *
	 * @var DigiCommerce_Stripe
	 */
	private static $instance = null;

	/**
	 * Stripe secret key
	 *
	 * @var string
	 */
	private $secret_key;

	/**
	 * Stripe publishable key
	 *
	 * @var string
	 */
	private $publishable_key;

	/**
	 * Test mode flag
	 *
	 * @var bool
	 */
	private $is_test_mode;

	/**
	 * Get singleton instance
	 *
	 * @return DigiCommerce_Stripe
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
		$this->is_test_mode    = DigiCommerce()->get_option( 'stripe_mode', 'test' ) === 'test';
		$this->secret_key      = $this->is_test_mode
			? DigiCommerce()->get_option( 'stripe_test_secret_key', '' )
			: DigiCommerce()->get_option( 'stripe_live_secret_key', '' );
		$this->publishable_key = $this->is_test_mode
			? DigiCommerce()->get_option( 'stripe_test_publishable_key', '' )
			: DigiCommerce()->get_option( 'stripe_live_publishable_key', '' );

		// Load Stripe SDK
		require_once DIGICOMMERCE_PLUGIN_DIR . 'vendor/autoload.php';

		// Initialize Stripe with API key
		\Stripe\Stripe::setApiKey( $this->secret_key );

		add_action( 'wp_ajax_digicommerce_process_stripe_payment', array( $this, 'handle_stripe_payment' ) );
		add_action( 'wp_ajax_nopriv_digicommerce_process_stripe_payment', array( $this, 'handle_stripe_payment' ) );
		add_action( 'wp_ajax_digicommerce_verify_subscription', array( $this, 'verify_subscription_status' ) );
		add_action( 'wp_ajax_nopriv_digicommerce_verify_subscription', array( $this, 'verify_subscription_status' ) );
	}

	/**
	 * Handle Stripe payment
	 *
	 * @return string
	 */
	public function handle_stripe_payment() {
		try {
			check_ajax_referer( 'digicommerce_process_checkout', 'nonce' );

			// Get stripe payment data from request
			$stripe_payment_data = isset( $_POST['stripe_payment_data'] ) ?
				json_decode( stripslashes( $_POST['stripe_payment_data'] ), true ) : null; // phpcs:ignore

			// Get cart and session data
			$cart         = DigiCommerce_Checkout::instance();
			$cart_items   = $cart->get_cart_items();
			$session_key  = $cart->get_current_session_key();
			$session_data = $cart->get_session( $session_key );

			// Get user billing data
			$billing_data = array(
				'email'      => sanitize_email( $_POST['email'] ?? '' ), // phpcs:ignore
				'first_name' => sanitize_text_field( $_POST['first_name'] ?? '' ), // phpcs:ignore
				'last_name'  => sanitize_text_field( $_POST['last_name'] ?? '' ), // phpcs:ignore
				'company'    => sanitize_text_field( $_POST['company'] ?? '' ), // phpcs:ignore
				'phone'      => sanitize_text_field( $_POST['phone'] ?? '' ), // phpcs:ignore
				'address'    => sanitize_text_field( $_POST['address'] ?? '' ), // phpcs:ignore
				'city'       => sanitize_text_field( $_POST['city'] ?? '' ), // phpcs:ignore
				'postcode'   => sanitize_text_field( $_POST['postcode'] ?? '' ), // phpcs:ignore
				'country'    => sanitize_text_field( $_POST['country'] ?? '' ), // phpcs:ignore
				'vat_number' => sanitize_text_field( $_POST['vat_number'] ?? '' ), // phpcs:ignore
			);

			// Calculate initial payment amount (signup fee or first payment)
			$initial_payment  = 0;
			$has_subscription = false;
			$has_free_trial   = false;
			$has_signup_fee   = false;

			// Track one-time products vs subscription products
			$one_time_products_total     = 0;
			$subscription_products_total = 0;

			foreach ( $cart_items as $item ) {
				if ( ! empty( $item['subscription_enabled'] ) ) {
					$has_subscription = true;

					// If there's a signup fee, charge that as initial payment
					if ( ! empty( $item['subscription_signup_fee'] ) && floatval( $item['subscription_signup_fee'] ) > 0 ) {
						$has_signup_fee   = true;
						$initial_payment += floatval( $item['subscription_signup_fee'] );
					} else { // phpcs:ignore
						// If there's a free trial
						if ( ! empty( $item['subscription_free_trial'] ) &&
							intval( $item['subscription_free_trial']['duration'] ) > 0 ) {
							$has_free_trial = true;
						} else {
							$subscription_products_total += floatval( $item['price'] );
						}
					}
				} else {
					// Add one-time product price
					$one_time_products_total += floatval( $item['price'] );
				}
			}

			// If we only have subscription products with no signup fee, don't do initial payment
			// Otherwise, add one-time products to initial payment
			if ( $one_time_products_total > 0 ) {
				$initial_payment += $one_time_products_total;
			}

			// Apply VAT and discount calculations to initial payment
			$subtotal         = $initial_payment;
			$business_country = DigiCommerce()->get_option( 'business_country' );
			$buyer_country    = $billing_data['country'];
			$vat_number       = $billing_data['vat_number'];

			// Initialize VAT amount
			$vat_amount = 0;
			$tax_rate   = 0;
			$apply_vat  = false;

			// Only calculate VAT if taxes are not disabled
			if ( ! DigiCommerce()->get_option( 'remove_taxes' ) ) {
				$countries = DigiCommerce()->get_countries();

				if ( $buyer_country === $business_country ) {
					// Domestic sale: Always charge seller's country VAT
					$tax_rate   = $countries[ $business_country ]['tax_rate'] ?? 0;
					$vat_amount = round( $subtotal * $tax_rate, 2 );
					$apply_vat  = true;
				} elseif ( ! empty( $countries[ $buyer_country ]['eu'] ) && ! empty( $countries[ $business_country ]['eu'] ) ) {
					// EU cross-border sale
					if ( empty( $vat_number ) || ! DigiCommerce_Orders::instance()->validate_vat_number( $vat_number, $buyer_country ) ) {
						// No valid VAT number - charge buyer's country rate
						$tax_rate   = $countries[ $buyer_country ]['tax_rate'] ?? 0;
						$vat_amount = round( $subtotal * $tax_rate, 2 );
						$apply_vat  = true;
					}
					// With valid VAT number - no VAT (vat_amount remains 0)
				}
				// Non-EU sale - no VAT (vat_amount remains 0)
			}

			$total_with_vat = $subtotal + $vat_amount;

			// Apply discount if exists
			$discount_amount = 0;
			if ( ! empty( $session_data['discount'] ) ) {
				$discount_data = $session_data['discount'];
				if ( 'percentage' === $discount_data['type'] ) {
					$discount_amount = round( ( $total_with_vat * $discount_data['amount'] ) / 100, 2 );
				} else {
					$discount_amount = min( $discount_data['amount'], $total_with_vat );
				}
			}

			$total = $total_with_vat - $discount_amount;

			// IMPORTANT: Create customer once and store it
			$customer = null;
			if ( ! empty( $stripe_payment_data['customer_id'] ) ) {
				// If we already have a customer ID, retrieve it
				$customer = \Stripe\Customer::retrieve( $stripe_payment_data['customer_id'] );
			}

			// If no customer yet, create one
			if ( ! $customer ) {
				$customer = $this->get_or_create_customer(
					[ // phpcs:ignore
						'email'    => $billing_data['email'],
						'name'     => trim( $billing_data['first_name'] . ' ' . $billing_data['last_name'] ),
						'phone'    => $billing_data['phone'],
						'address'  => [ // phpcs:ignore
							'line1'       => $billing_data['address'],
							'city'        => $billing_data['city'],
							'postal_code' => $billing_data['postcode'],
							'country'     => $billing_data['country'],
						],
						'metadata' => [ // phpcs:ignore
							'vat_number' => $billing_data['vat_number'],
							'company'    => $billing_data['company'],
							'user_id'    => get_current_user_id() ? : 'guest', // phpcs:ignore
						],
					]
				);
			}

			$response = [ // phpcs:ignore
				'success' => true,
				'data'    => [ // phpcs:ignore
					'customerId' => $customer->id,
				],
			];

			// Handle subscription creation if payment_method and setup_intent_id exist
			if ( ! empty( $stripe_payment_data['payment_method'] ) &&
				! empty( $stripe_payment_data['setup_intent_id'] ) ) {

				try {
					// Retrieve and attach payment method if needed
					$payment_method = \Stripe\PaymentMethod::retrieve( $stripe_payment_data['payment_method'] );
					if ( $payment_method->customer !== $customer->id ) {
						$payment_method->attach( [ 'customer' => $customer->id ] ); // phpcs:ignore
					}

					// Update customer's default payment method
					\Stripe\Customer::update(
						$customer->id,
						[ // phpcs:ignore
							'invoice_settings' => [ // phpcs:ignore
								'default_payment_method' => $stripe_payment_data['payment_method'],
							],
						]
					);

					// Create subscription only if needed
					if ( $has_subscription ) {
						// Create subscription parameters
						$subscription_params = [ // phpcs:ignore
							'customer'               => $customer->id,
							'default_payment_method' => $stripe_payment_data['payment_method'],
							'items'                  => $this->prepare_subscription_items( $cart_items, $billing_data ),
							'metadata'               => $this->prepare_metadata( $cart_items, $billing_data ),
							'expand'                 => [ 'latest_invoice.payment_intent' ], // phpcs:ignore
							'payment_settings'       => [ // phpcs:ignore
								'payment_method_types' => [ 'card' ], // phpcs:ignore
								'save_default_payment_method' => 'on_subscription',
							],
							'collection_method'      => 'charge_automatically',
						];

						// PREVENT DOUBLE CHARGING - Add discount handling
						if ( $has_free_trial ) {
							// If there's a trial period specified in the product
							foreach ( $cart_items as $item ) {
								if ( ! empty( $item['subscription_enabled'] ) &&
									! empty( $item['subscription_free_trial'] ) &&
									! empty( $item['subscription_free_trial']['duration'] ) ) {
									$trial_days = $this->get_trial_period_days( $item['subscription_free_trial'] );
									if ( $trial_days > 0 ) {
										$subscription_params['trial_period_days'] = $trial_days;
									}
									break;
								}
							}
						} elseif ( $has_signup_fee || $one_time_products_total > 0 ) {
							// If we're charging an initial payment (signup fee or one-time products),
							// delay the first subscription invoice by using billing cycle anchor
							$subscription_params['billing_cycle_anchor'] = strtotime( '+1 ' . $this->get_stripe_interval( $item['subscription_period'] ) );
							$subscription_params['proration_behavior']   = 'none';
						} elseif ( $subscription_products_total > 0 && $initial_payment == 0 ) { // phpcs:ignore
							// Only subscription products with no initial payment
							// Use default_incomplete so we can confirm the payment manually
							$subscription_params['payment_behavior'] = 'default_incomplete';

							// Handle discount for subscription-only payments with no initial payment
							if ( ! empty( $session_data['discount'] ) ) {
								// Create a one-time coupon in Stripe
								$coupon = \Stripe\Coupon::create(
									[ // phpcs:ignore
										'duration' => 'once',
										'currency' => strtolower( DigiCommerce()->get_option( 'currency', 'USD' ) ),
										'name'     => 'One-time discount (' . ( $session_data['discount']['code'] ?? 'custom' ) . ')',
										// Determine if percentage or fixed amount
										'percentage' === $session_data['discount']['type']
											? 'percent_off' : 'amount_off' => 'percentage' === $session_data['discount']['type']
											? floatval( $session_data['discount']['amount'] )
											: $this->convert_to_cents( floatval( $session_data['discount']['amount'] ) ),
									]
								);

								// Add coupon to subscription
								$subscription_params['coupon'] = $coupon->id;
							}
						}

						// Create subscription
						$subscription = \Stripe\Subscription::create( $subscription_params );

						// Check if subscription payment requires authentication
						if ( ! empty( $subscription_params['trial_period_days'] ) ||
							! empty( $subscription_params['billing_cycle_anchor'] ) ) {
							// No payment needed for trial subscriptions or when using billing_cycle_anchor
							$response['data']['subscriptionId'] = $subscription->id;
							wp_send_json_success( $response['data'] );
							return;
						}

						// For non-trial, immediate payment subscriptions, check if payment requires action
						if ( isset( $subscription->latest_invoice->payment_intent ) &&
							( 'requires_action' === $subscription->latest_invoice->payment_intent->status ||
							'requires_confirmation' === $subscription->latest_invoice->payment_intent->status ) ) {

							wp_send_json_success(
								[ // phpcs:ignore
									'subscriptionId' => $subscription->id,
									'requiresAction' => true,
									'clientSecret'   => $subscription->latest_invoice->payment_intent->client_secret,
								]
							);
							return;
						}

						// Return subscription ID for successful subscriptions
						$response['data']['subscriptionId'] = $subscription->id;
					}

					wp_send_json_success( $response['data'] );

				} catch ( \Stripe\Exception\CardException $e ) {
					wp_send_json_error( [ 'message' => $e->getMessage() ] ); // phpcs:ignore
				} catch ( Exception $e ) {
					wp_send_json_error( [ 'message' => $e->getMessage() ] ); // phpcs:ignore
				}
			} else {
				// For subscriptions, always create SetupIntent
				if ( $has_subscription ) {
					$setup_intent = \Stripe\SetupIntent::create(
						[ // phpcs:ignore
							'customer'             => $customer->id,
							'payment_method_types' => ['card'], // phpcs:ignore
							'usage'                => 'off_session',
							'metadata'             => $this->prepare_metadata( $cart_items, $billing_data ),
						]
					);

					$response['data']['setupIntent'] = [ // phpcs:ignore
						'client_secret' => $setup_intent->client_secret,
						'id'            => $setup_intent->id,
					];
				}

				// Create PaymentIntent if there's an immediate payment needed
				if ( $total > 0 ) {
					$payment_intent = \Stripe\PaymentIntent::create(
						[ // phpcs:ignore
							'amount'               => $this->convert_to_cents( $total ),
							'currency'             => strtolower( DigiCommerce()->get_option( 'currency', 'USD' ) ),
							'customer'             => $customer->id,
							'setup_future_usage'   => $has_subscription ? 'off_session' : null,
							'payment_method_types' => ['card'], // phpcs:ignore
							'metadata'             => array_merge(
								$this->prepare_metadata( $cart_items, $billing_data ),
								[ // phpcs:ignore
									'subtotal'        => $subtotal,
									'vat_amount'      => $vat_amount,
									'vat_applied'     => $apply_vat ? 'yes' : 'no',
									'tax_rate'        => $tax_rate,
									'discount_amount' => $discount_amount,
									'total'           => $total,
								]
							),
							'description'          => $this->get_payment_description( $cart_items ),
						]
					);

					$response['data']['paymentIntent'] = [ // phpcs:ignore
						'client_secret' => $payment_intent->client_secret,
						'id'            => $payment_intent->id,
					];
				}
			}

			wp_send_json_success( $response['data'] );

		} catch ( Exception $e ) {
			wp_send_json_error( [ 'message' => $e->getMessage() ] ); // phpcs:ignore
		}
	}

	/**
	 * Verify subscription status
	 *
	 * @throws Exception If subscription status is not active.
	 */
	public function verify_subscription_status() {
		try {
			check_ajax_referer( 'digicommerce_process_checkout', 'nonce' );

			$subscription_id   = sanitize_text_field( $_POST['subscription_id'] ?? '' ); // phpcs:ignore
			$payment_intent_id = sanitize_text_field( $_POST['payment_intent_id'] ?? '' ); // phpcs:ignore

			if ( empty( $subscription_id ) ) {
				throw new Exception( 'Subscription ID is required' );
			}

			// Retrieve and check subscription status
			$subscription = \Stripe\Subscription::retrieve(
				[ // phpcs:ignore
					'id'     => $subscription_id,
					'expand' => ['latest_invoice.payment_intent'], // phpcs:ignore
				]
			);

			if ( 'active' !== $subscription->status && 'trialing' !== $subscription->status ) {
				throw new Exception( 'Subscription is not active' );
			}

			// If there was a payment, verify it succeeded
			if ( $payment_intent_id ) {
				$payment_intent = \Stripe\PaymentIntent::retrieve( $payment_intent_id );
				if ( 'succeeded' !== $payment_intent->status ) {
					throw new Exception( 'Payment was not successful' );
				}
			}

			wp_send_json_success( [ 'status' => $subscription->status ] ); // phpcs:ignore

		} catch ( Exception $e ) {
			wp_send_json_error( [ 'message' => $e->getMessage() ] ); // phpcs:ignore
		}
	}

	/**
	 * Get or create Stripe customer
	 *
	 * @param array $data Customer data.
	 * @throws Exception If customer creation fails.
	 */
	private function get_or_create_customer( $data ) {
		$user_id = get_current_user_id();

		if ( $user_id ) {
			$stripe_customer_id = get_user_meta( $user_id, '_stripe_customer_id', true );

			if ( $stripe_customer_id ) {
				try {
					$customer = \Stripe\Customer::retrieve( $stripe_customer_id );

					// Update existing customer
					$customer = \Stripe\Customer::update( $stripe_customer_id, $data );
					return $customer;
				} catch ( Exception $e ) { // phpcs:ignore
					// Customer not found - create new
				}
			}
		}

		// Create new customer
		try {
			$customer = \Stripe\Customer::create( $data );

			if ( $user_id ) {
				update_user_meta( $user_id, '_stripe_customer_id', $customer->id );
			}

			return $customer;
		} catch ( Exception $e ) {
			throw $e;
		}
	}

	/**
	 * Prepare metadata for Stripe payment
	 *
	 * @param array $cart_items Cart items.
	 * @param array $billing_data Billing data.
	 */
	private function prepare_metadata( $cart_items, $billing_data ) {
		$items_description = array();
		foreach ( $cart_items as $item ) {
			$desc = $item['name'];
			if ( ! empty( $item['variation_name'] ) ) {
				$desc .= ' (' . $item['variation_name'] . ')';
			}
			$items_description[] = $desc;
		}

		return array(
			'customer_email' => $billing_data['email'],
			'customer_name'  => trim( $billing_data['first_name'] . ' ' . $billing_data['last_name'] ),
			'company'        => $billing_data['company'],
			'address'        => $billing_data['address'],
			'city'           => $billing_data['city'],
			'postcode'       => $billing_data['postcode'],
			'country'        => $billing_data['country'],
			'vat_number'     => $billing_data['vat_number'],
			'items'          => substr( implode( ', ', $items_description ), 0, 499 ),
		);
	}

	/**
	 * Get payment description
	 *
	 * @param array $cart_items Cart items.
	 */
	private function get_payment_description( $cart_items ) {
		$descriptions = array();
		foreach ( $cart_items as $item ) {
			$desc = $item['name'];
			if ( ! empty( $item['variation_name'] ) ) {
				$desc .= ' (' . $item['variation_name'] . ')';
			}
			$descriptions[] = $desc;
		}
		return implode( ', ', $descriptions );
	}

	/**
	 * Get trial period days
	 *
	 * @param array $trial_data Trial data.
	 */
	public function get_trial_period_days( $trial_data ) {
		if ( empty( $trial_data ) || empty( $trial_data['duration'] ) || empty( $trial_data['period'] ) ) {
			return null;
		}

		$duration = intval( $trial_data['duration'] );
		if ( $duration <= 0 ) {
			return null;
		}

		switch ( $trial_data['period'] ) {
			case 'days':
				return $duration;
			case 'weeks':
				return $duration * 7;
			case 'months':
				return $duration * 30;
			default:
				return null;
		}
	}

	/**
	 * Prepare subscription items for Stripe
	 *
	 * @param array $cart_items Cart items.
	 * @param array $billing_data Billing data.
	 */
	public function prepare_subscription_items( $cart_items, $billing_data ) {
		$stripe_items = array();

		// Get business country and buyer country
		$business_country = DigiCommerce()->get_option( 'business_country' );
		$buyer_country    = $billing_data['country'];
		$vat_number       = $billing_data['vat_number'];

		// Initialize VAT rate
		$vat_rate  = 0;
		$apply_vat = false;

		// Only calculate VAT if taxes are not disabled
		if ( ! DigiCommerce()->get_option( 'remove_taxes' ) ) {
			$countries = DigiCommerce()->get_countries();

			if ( $buyer_country === $business_country ) {
				// Domestic sale: Always charge seller's country VAT
				$vat_rate  = $countries[ $business_country ]['tax_rate'] ?? 0;
				$apply_vat = true;
			} elseif ( ! empty( $countries[ $buyer_country ]['eu'] ) && ! empty( $countries[ $business_country ]['eu'] ) ) {
				// EU cross-border sale
				if ( empty( $vat_number ) || ! DigiCommerce_Orders::instance()->validate_vat_number( $vat_number, $buyer_country ) ) {
					// No valid VAT number - charge buyer's country rate
					$vat_rate  = $countries[ $buyer_country ]['tax_rate'] ?? 0;
					$apply_vat = true;
				}
				// With valid VAT number - no VAT (vat_rate remains 0, apply_vat remains false)
			}
			// Non-EU sale - no VAT (vat_rate remains 0, apply_vat remains false)
		}

		foreach ( $cart_items as $item ) {
			if ( ! empty( $item['subscription_enabled'] ) ) {
				// For subscription upgrades, use the full variation price instead of the prorated price
				$base_price  = ! empty( $item['meta']['subscription_upgrade'] ) ?
					floatval( $item['meta']['variation_price'] ) : // Full price for upgrades
					floatval( $item['price'] ); // Regular price for new subscriptions
				$vat_amount  = $apply_vat ? round( $base_price * $vat_rate, 2 ) : 0;
				$total_price = $base_price + $vat_amount;

				$price_in_cents = $this->convert_to_cents( $total_price );

				// Create price with total amount (including VAT)
				$price = \Stripe\Price::create(
					[ // phpcs:ignore
						'unit_amount'  => $price_in_cents,
						'currency'     => strtolower( DigiCommerce()->get_option( 'currency', 'USD' ) ),
						'recurring'    => [ // phpcs:ignore
							'interval' => $this->get_stripe_interval( $item['subscription_period'] ),
						],
						'product_data' => [ // phpcs:ignore
							'name'     => $item['name'],
							'metadata' => [ // phpcs:ignore
								'base_price'       => $base_price,
								'vat_rate'         => $vat_rate,
								'vat_amount'       => $vat_amount,
								'has_vat'          => $apply_vat ? 'yes' : 'no',
								'country'          => $buyer_country,
								'business_country' => $business_country,
								'vat_type'         => $buyer_country === $business_country ? 'domestic' : ( ! empty( $countries[ $buyer_country ]['eu'] ) ? 'eu' : 'non_eu' ),
							],
						],
					]
				);

				$stripe_items[] = [ // phpcs:ignore
					'price' => $price->id,
				];
			}
		}

		return $stripe_items;
	}

	/**
	 * Convert subscription period to Stripe interval
	 *
	 * @param string $period Subscription period.
	 */
	public function get_stripe_interval( $period ) {
		$intervals = array(
			'day'   => 'day',
			'week'  => 'week',
			'month' => 'month',
			'year'  => 'year',
		);

		return isset( $intervals[ $period ] ) ? $intervals[ $period ] : 'month';
	}

	/**
	 * Process refunds for any type of order (normal, subscription, or mixed)
	 *
	 * @param int    $order_id Order ID.
	 * @param float  $amount Refund amount.
	 * @param string $reason Refund reason.
	 * @throws Exception If refund fails.
	 */
	public function process_refund( $order_id, $amount = null, $reason = '' ) {
		try {
			global $wpdb;

			// Get order details
			$order = DigiCommerce_Orders::instance()->get_order( $order_id );
			if ( ! $order ) {
				throw new Exception( 'Order not found' );
			}

			// Get payment data from order meta
			$payment_intent_id = $wpdb->get_var( // phpcs:ignore
				$wpdb->prepare(
					"SELECT meta_value FROM {$wpdb->prefix}digicommerce_order_meta 
                WHERE order_id = %d AND meta_key = '_stripe_payment_intent_id'",
					$order_id
				)
			);

			// Get subscription ID
			$subscription_id = $wpdb->get_var( // phpcs:ignore
				$wpdb->prepare(
					"SELECT si.subscription_id 
                FROM {$wpdb->prefix}digicommerce_subscription_items si
                WHERE si.order_id = %d",
					$order_id
				)
			);

			$refund_results = array();

			// Handle one-time payment refund if exists
			if ( $payment_intent_id ) {
				try {
					$one_time_refund = $this->process_one_time_refund( $payment_intent_id, $amount );
					if ( $one_time_refund ) {
						$refund_results['one_time'] = $one_time_refund;
					}
				} catch ( Exception $e ) {
					$refund_results['one_time'] = array(
						'status' => 'failed',
						'error'  => $e->getMessage(),
					);
				}
			}

			// Handle subscription refund if exists
			if ( $subscription_id ) {
				try {
					// Get Stripe subscription ID
					$stripe_subscription_id = $wpdb->get_var( // phpcs:ignore
						$wpdb->prepare(
							"SELECT meta_value 
                        FROM {$wpdb->prefix}digicommerce_order_meta 
                        WHERE order_id = %d AND meta_key = '_stripe_subscription_id'",
							$order_id
						)
					);

					if ( $stripe_subscription_id ) {
						$subscription_refund = $this->process_subscription_refund( $order_id, $subscription_id, $stripe_subscription_id, $amount );
						if ( $subscription_refund ) {
							$refund_results['subscription'] = $subscription_refund;
						}
					}
				} catch ( Exception $e ) {
					$refund_results['subscription'] = array(
						'status' => 'failed',
						'error'  => $e->getMessage(),
					);
				}
			}

			// Log refund attempt
			$this->log_refund( $order_id, $amount, $reason, $refund_results );

			// Check if any refund was successful
			$has_success = false;
			foreach ( $refund_results as $result ) {
				if ( isset( $result['status'] ) && 'succeeded' === $result['status'] ) {
					$has_success = true;
					break;
				}
			}

			if ( ! $has_success ) {
				throw new Exception( 'No successful refunds processed' );
			}

			do_action( 'digicommerce_order_refunded', $order_id, $amount );

			return $refund_results;

		} catch ( Exception $e ) {
			// Log the error
			$this->log_refund( $order_id, $amount, $reason, array( 'error' => $e->getMessage() ) );
			throw $e;
		}
	}

	/**
	 * Process refund for one-time payment
	 *
	 * @param string $payment_intent_id Payment intent ID.
	 * @param float  $amount Refund amount.
	 * @throws Exception If refund fails.
	 */
	private function process_one_time_refund( $payment_intent_id, $amount ) {
		$payment_intent = \Stripe\PaymentIntent::retrieve( $payment_intent_id );

		if ( empty( $payment_intent->latest_charge ) ) {
			throw new Exception( 'No charge found in payment intent' );
		}

		$charge = \Stripe\Charge::retrieve( $payment_intent->latest_charge );

		if ( $charge->refunded ) {
			throw new Exception( 'Charge has already been refunded' );
		}

		if ( 'succeeded' !== $charge->status ) {
			throw new Exception( 'Charge status is not succeeded: ' . $charge->status ); // phpcs:ignore
		}

		$refund = \Stripe\Refund::create(
			array(
				'charge' => $payment_intent->latest_charge,
				'amount' => $amount ? $this->convert_to_cents( $amount ) : null,
			)
		);

		return array(
			'status'    => $refund->status,
			'amount'    => $amount ? $amount : $charge->amount / 100,
			'refund_id' => $refund->id,
		);
	}

	/**
	 * Process refund for subscription
	 *
	 * @param int    $order_id Order ID.
	 * @param int    $subscription_id Subscription ID.
	 * @param string $stripe_subscription_id Stripe subscription ID.
	 * @param float  $amount Refund amount.
	 * @throws Exception If refund fails.
	 */
	private function process_subscription_refund( $order_id, $subscription_id, $stripe_subscription_id, $amount ) {
		global $wpdb;

		try {
			$refund_result = array();

			// First check if there was a signup fee payment
			$signup_fee_intent_id = $wpdb->get_var( // phpcs:ignore
				$wpdb->prepare(
					"SELECT meta_value FROM {$wpdb->prefix}digicommerce_order_meta 
                WHERE order_id = %d AND meta_key = '_stripe_signup_fee_intent_id'",
					$order_id
				)
			);

			// Get initial subscription payment intent
			$initial_payment_intent_id = $wpdb->get_var( // phpcs:ignore
				$wpdb->prepare(
					"SELECT meta_value FROM {$wpdb->prefix}digicommerce_order_meta 
                WHERE order_id = %d AND meta_key = '_stripe_initial_payment_intent_id'",
					$order_id
				)
			);

			// Process signup fee refund if exists
			if ( $signup_fee_intent_id ) {
				try {
					$payment_intent = \Stripe\PaymentIntent::retrieve( $signup_fee_intent_id );

					if ( ! empty( $payment_intent->latest_charge ) ) {
						// Calculate refund amount for signup fee
						$signup_fee_amount = $payment_intent->amount / 100;
						$refund_amount     = min( $amount, $signup_fee_amount );

						$refund = \Stripe\Refund::create(
							array(
								'charge' => $payment_intent->latest_charge,
								'amount' => $this->convert_to_cents( $refund_amount ),
							)
						);

						$refund_result['signup_fee'] = array(
							'status'    => $refund->status,
							'amount'    => $refund_amount,
							'refund_id' => $refund->id,
						);

						// Update remaining amount to refund
						$amount -= $refund_amount;
					}
				} catch ( Exception $e ) {
					$refund_result['signup_fee'] = array(
						'status' => 'failed',
						'error'  => $e->getMessage(),
					);
				}
			}

			// Process initial subscription payment refund if there's remaining amount
			if ( $amount > 0 && $initial_payment_intent_id ) {
				try {
					$payment_intent = \Stripe\PaymentIntent::retrieve( $initial_payment_intent_id );
					if ( ! empty( $payment_intent->latest_charge ) ) {
						$refund = \Stripe\Refund::create(
							array(
								'charge' => $payment_intent->latest_charge,
								'amount' => $this->convert_to_cents( $amount ),
							)
						);

						$refund_result['subscription'] = array(
							'status'    => $refund->status,
							'amount'    => $amount,
							'refund_id' => $refund->id,
						);
					}
				} catch ( Exception $e ) {
					$refund_result['subscription'] = array(
						'status' => 'failed',
						'error'  => $e->getMessage(),
					);
				}
			}

			// Cancel the subscription in Stripe
			try {
				$subscription = \Stripe\Subscription::retrieve( $stripe_subscription_id );
				if ( 'canceled' !== $subscription->status ) {
					$result = $subscription->cancel();
				}
			} catch ( Exception $e ) {
				throw $e;
			}

			// Update subscription status in database
			$wpdb->update( // phpcs:ignore
				$wpdb->prefix . 'digicommerce_subscriptions',
				array(
					'status'        => 'cancelled',
					'date_modified' => current_time( 'mysql' ),
				),
				array( 'id' => $subscription_id ),
				array( '%s', '%s' ),
				array( '%d' )
			);

			// Cancel pending schedules
			$wpdb->update( // phpcs:ignore
				$wpdb->prefix . 'digicommerce_subscription_schedule',
				array( 'status' => 'cancelled' ),
				array(
					'subscription_id' => $subscription_id,
					'status'          => 'pending',
				),
				array( '%s' ),
				array( '%d', '%s' )
			);

			// Add subscription note with all refund details
			$note = esc_html__( 'Subscription cancelled due to refund.', 'digicommerce' );

			$wpdb->insert( // phpcs:ignore
				$wpdb->prefix . 'digicommerce_subscription_meta',
				array(
					'subscription_id' => $subscription_id,
					'meta_key'        => 'note', // phpcs:ignore
					'meta_value'      => $note, // phpcs:ignore
				),
				array( '%d', '%s', '%s' )
			);

			// Fire subscription cancelled action
			do_action( 'digicommerce_subscription_cancelled', $subscription_id );

			// Return combined refund results
			$total_refunded   = 0;
			$refund_status    = 'succeeded';
			$latest_refund_id = '';

			foreach ( $refund_result as $refund ) {
				if ( isset( $refund['amount'] ) ) {
					$total_refunded += $refund['amount'];
				}
				if ( isset( $refund['status'] ) && 'succeeded' !== $refund['status'] ) {
					$refund_status = 'partially_succeeded';
				}
				if ( isset( $refund['refund_id'] ) ) {
					$latest_refund_id = $refund['refund_id'];
				}
			}

			return array(
				'status'    => $refund_status,
				'amount'    => $total_refunded,
				'refund_id' => $latest_refund_id,
				'details'   => $refund_result,
			);

		} catch ( Exception $e ) {
			throw $e;
		}
	}

	/**
	 * Log refund details
	 *
	 * @param int    $order_id Order ID.
	 * @param float  $amount Refund amount.
	 * @param string $reason Refund reason.
	 * @param array  $results Refund results.
	 */
	private function log_refund( $order_id, $amount, $reason, $results ) {
		global $wpdb;

		$log_data = array(
			'amount'    => $amount,
			'reason'    => $reason,
			'results'   => $results,
			'timestamp' => current_time( 'mysql' ),
		);

		$wpdb->insert( // phpcs:ignore
			$wpdb->prefix . 'digicommerce_order_meta',
			array(
				'order_id'   => $order_id,
				'meta_key'   => 'refund_log', // phpcs:ignore
				'meta_value' => maybe_serialize( $log_data ), // phpcs:ignore
			)
		);
	}

	/**
	 * Convert amount to cents
	 *
	 * @param float $amount Amount.
	 */
	public function convert_to_cents( $amount ) {
		return round( $amount * 100 );
	}

	/**
	 * Get Stripe publishable key
	 */
	public function get_publishable_key() {
		return $this->publishable_key;
	}
}
DigiCommerce_Stripe::instance();
