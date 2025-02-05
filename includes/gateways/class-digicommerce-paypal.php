<?php
/**
 * PayPal Payment Gateway for DigiCommerce
 */
class DigiCommerce_PayPal {
	private static $instance = null;
	private $client_id;
	private $client_secret;
	private $is_sandbox;

	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		$this->is_sandbox    = DigiCommerce()->get_option( 'paypal_sandbox', '0' );
		$this->client_id     = DigiCommerce()->get_option( 'paypal_client_id', '' );
		$this->client_secret = DigiCommerce()->get_option( 'paypal_secret', '' );

		add_action( 'wp_ajax_digicommerce_create_paypal_plan', array( $this, 'create_paypal_plan' ) );
		add_action( 'wp_ajax_nopriv_digicommerce_create_paypal_plan', array( $this, 'create_paypal_plan' ) );
	}

	private function get_api_url() {
		return $this->is_sandbox
			? 'https://api-m.sandbox.paypal.com'
			: 'https://api-m.paypal.com';
	}

	private function get_access_token() {
		if ( empty( $this->client_id ) || empty( $this->client_secret ) ) {
			throw new Exception( __( 'PayPal configuration incomplete', 'digicommerce' ) );
		}

		$api_url = $this->get_api_url() . '/v1/oauth2/token';

		$response = wp_remote_post(
			$api_url,
			array(
				'method'      => 'POST',
				'timeout'     => 45,
				'redirection' => 5,
				'httpversion' => '1.1',
				'blocking'    => true,
				'headers'     => array(
					'Authorization' => 'Basic ' . base64_encode( $this->client_id . ':' . $this->client_secret ),
					'Content-Type'  => 'application/x-www-form-urlencoded',
					'Accept'        => 'application/json',
				),
				'body'        => 'grant_type=client_credentials',
				'sslverify'   => true,
			)
		);

		if ( is_wp_error( $response ) ) {
			throw new Exception( $response->get_error_message() );
		}

		$body   = json_decode( wp_remote_retrieve_body( $response ), true );
		$status = wp_remote_retrieve_response_code( $response );

		if ( $status !== 200 || empty( $body['access_token'] ) ) {
			$error_message = isset( $body['error_description'] ) ? $body['error_description'] : 'Unknown error';
			throw new Exception( __( 'Failed to get PayPal access token: ', 'digicommerce' ) . $error_message );
		}

		return $body['access_token'];
	}

	public function create_paypal_plan() {
		try {

			if ( ! check_ajax_referer( 'digicommerce_process_checkout', 'nonce', false ) ) {
				throw new Exception( __( 'Security check failed', 'digicommerce' ) );
			}

			// Get cart data
			$checkout     = DigiCommerce_Checkout::instance();
			$session_key  = $checkout->get_current_session_key();
			$session_data = $checkout->get_session( $session_key );

			if ( empty( $session_data['cart'] ) ) {
				throw new Exception( __( 'Cart is empty', 'digicommerce' ) );
			}

			// Find subscription item
			$subscription_item = null;
			foreach ( $session_data['cart'] as $item ) {
				if ( ! empty( $item['subscription_enabled'] ) ) {
					$subscription_item = $item;
					break;
				}
			}

			if ( ! $subscription_item ) {
				throw new Exception( __( 'No subscription product found', 'digicommerce' ) );
			}

			// Get base price and VAT information
			$base_price = floatval( $subscription_item['price'] );
			$signup_fee = ! empty( $subscription_item['subscription_signup_fee'] ) ?
				floatval( $subscription_item['subscription_signup_fee'] ) : 0;

			$business_country = DigiCommerce()->get_option('business_country');
			$country          = isset($_POST['country']) ? sanitize_text_field($_POST['country']) : '';
			$vat_number       = isset($_POST['vat_number']) ? sanitize_text_field($_POST['vat_number']) : '';

			if (empty($country)) {
				throw new Exception(__('Country is required', 'digicommerce'));
			}

			// Initialize VAT rate
			$vat_rate = 0;

			// Only calculate VAT if taxes are not disabled
			if ( ! DigiCommerce()->get_option( 'remove_taxes' ) ) {
				$countries = DigiCommerce()->get_countries();
				
				if ($country === $business_country) {
					// Domestic sale: Always charge seller's country VAT
					$vat_rate = $countries[$business_country]['tax_rate'] ?? 0;
				} 
				elseif (!empty($countries[$country]['eu']) && !empty($countries[$business_country]['eu'])) {
					// EU cross-border sale
					if (empty($vat_number) || ! DigiCommerce_Orders::instance()->validate_vat_number($vat_number, $country)) {
						// No valid VAT number - charge buyer's country rate
						$vat_rate = $countries[$country]['tax_rate'] ?? 0;
					}
					// With valid VAT number - no VAT (vat_rate remains 0)
				}
				// Non-EU sale - no VAT (vat_rate remains 0)
			}

			// Calculate subscription price (no discount, only VAT)
			$subscription_vat_amount = $base_price * $vat_rate;
			$subscription_price      = $base_price + $subscription_vat_amount;

			// Calculate signup fee with VAT if exists
			$signup_fee_with_vat = 0;
			$final_signup_fee    = 0;
			if ( $signup_fee > 0 ) {
				$signup_fee_vat      = $signup_fee * $vat_rate;
				$signup_fee_with_vat = $signup_fee + $signup_fee_vat;
				$final_signup_fee    = $signup_fee_with_vat;
			}

			// Apply discount if exists
			$has_discount             = false;
			$final_subscription_price = $subscription_price;

			if ( ! empty( $session_data['discount'] ) ) {
				$has_discount = true;
				$discount     = $session_data['discount'];

				if ( $signup_fee > 0 ) {
					// Apply discount to signup fee if exists
					if ( $discount['type'] === 'percentage' ) {
						$discount_amount = ( $signup_fee_with_vat * floatval( $discount['amount'] ) ) / 100;
					} else {
						$discount_amount = min( floatval( $discount['amount'] ), $signup_fee_with_vat );
					}
					$final_signup_fee = $signup_fee_with_vat - $discount_amount;
				} else {
					// Apply discount to subscription price if no signup fee
					if ( $discount['type'] === 'percentage' ) {
						$discount_amount = ( $subscription_price * floatval( $discount['amount'] ) ) / 100;
					} else {
						$discount_amount = min( floatval( $discount['amount'] ), $subscription_price );
					}
					$final_subscription_price = $subscription_price - $discount_amount;
				}
			}

			// Create PayPal plan
			$plan_id = $this->create_plan(
				$subscription_item['product_id'],
				$subscription_item['name'] . ( $subscription_item['variation_name'] ? " - {$subscription_item['variation_name']}" : '' ),
				$final_subscription_price, // Pass discounted subscription price
				array(
					'subscription_enabled'    => true,
					'subscription_period'     => $subscription_item['subscription_period'],
					'subscription_free_trial' => $subscription_item['subscription_free_trial'],
					'subscription_signup_fee' => $final_signup_fee,
					'has_discount'            => $has_discount,
					'regular_price'           => $subscription_price,
				)
			);

			wp_send_json_success(
				array(
					'plan_id'           => $plan_id,
					'has_signup_fee'    => ( $signup_fee > 0 ),
					'signup_fee_amount' => $final_signup_fee,
				)
			);

		} catch ( Exception $e ) {
			wp_send_json_error(
				array(
					'message' => $e->getMessage(),
					'code'    => $e->getCode(),
				)
			);
		}
	}

	public function create_plan( $product_id, $name, $price, $subscription_data ) {
		try {
			// For subscription upgrades, use the full price instead of prorated
			if (!empty($subscription_data['is_subscription_upgrade'])) {
				$price = $subscription_data['full_price'];  // Use full upgrade price
			}
			
			$access_token = $this->get_access_token();

			// Create PayPal product
			$product_request = array(
				'name'     => wp_strip_all_tags( $name ),
				'type'     => 'DIGITAL',
				'category' => 'SOFTWARE',
			);

			$product_response = wp_remote_post(
				$this->get_api_url() . '/v1/catalogs/products',
				array(
					'headers' => array(
						'Authorization' => 'Bearer ' . $access_token,
						'Content-Type'  => 'application/json',
					),
					'body'    => json_encode( $product_request ),
				)
			);

			if ( is_wp_error( $product_response ) ) {
				throw new Exception( 'Failed to create PayPal product: ' . $product_response->get_error_message() );
			}

			$product_data = json_decode( wp_remote_retrieve_body( $product_response ), true );

			if ( empty( $product_data['id'] ) ) {
				throw new Exception( 'Failed to get product ID from PayPal response' );
			}

			$product_id_paypal = $product_data['id'];

			// Create billing cycles array
			$billing_cycles = array();
			$sequence       = 1;

			// If has discount but no signup fee, add discounted first payment
			if ( $subscription_data['has_discount'] && empty( $subscription_data['subscription_signup_fee'] ) ) {
				// Add discounted first payment
				$billing_cycles[] = array(
					'frequency'      => array(
						'interval_unit'  => strtoupper( $subscription_data['subscription_period'] ),
						'interval_count' => 1,
					),
					'tenure_type'    => 'TRIAL',
					'sequence'       => $sequence++,
					'total_cycles'   => 1,
					'pricing_scheme' => array(
						'fixed_price' => array(
							'currency_code' => strtoupper( DigiCommerce()->get_option( 'currency', 'USD' ) ),
							'value'         => number_format( $price, 2, '.', '' ), // Discounted price
						),
					),
				);

				// Add regular price for subsequent payments
				$billing_cycles[] = array(
					'frequency'      => array(
						'interval_unit'  => strtoupper( $subscription_data['subscription_period'] ),
						'interval_count' => 1,
					),
					'tenure_type'    => 'REGULAR',
					'sequence'       => $sequence++,
					'total_cycles'   => 0,
					'pricing_scheme' => array(
						'fixed_price' => array(
							'currency_code' => strtoupper( DigiCommerce()->get_option( 'currency', 'USD' ) ),
							'value'         => number_format( $subscription_data['regular_price'], 2, '.', '' ), // Full price
						),
					),
				);
			} else {
				// Handle signup fee if exists
				if ( ! empty( $subscription_data['subscription_signup_fee'] ) &&
					floatval( $subscription_data['subscription_signup_fee'] ) > 0 ) {

					$billing_cycles[] = array(
						'frequency'      => array(
							'interval_unit'  => 'DAY',
							'interval_count' => 1,
						),
						'tenure_type'    => 'TRIAL',
						'sequence'       => $sequence++,
						'total_cycles'   => 1,
						'pricing_scheme' => array(
							'fixed_price' => array(
								'currency_code' => strtoupper( DigiCommerce()->get_option( 'currency', 'USD' ) ),
								'value'         => number_format( $subscription_data['subscription_signup_fee'], 2, '.', '' ),
							),
						),
					);
				}

				// Add free trial if exists
				if ( ! empty( $subscription_data['subscription_free_trial'] ) &&
					! empty( $subscription_data['subscription_free_trial']['duration'] ) &&
					intval( $subscription_data['subscription_free_trial']['duration'] ) > 0 ) {

					$trial_duration = intval( $subscription_data['subscription_free_trial']['duration'] );
					$trial_period   = strtoupper( $subscription_data['subscription_free_trial']['period'] );

					// Convert plural to singular for PayPal
					$trial_period = rtrim( $trial_period, 'S' ); // Remove trailing 'S'

					$billing_cycles[] = array(
						'frequency'      => array(
							'interval_unit'  => $trial_period,
							'interval_count' => $trial_duration,
						),
						'tenure_type'    => 'TRIAL',
						'sequence'       => $sequence++,
						'total_cycles'   => 1,
						'pricing_scheme' => array(
							'fixed_price' => array(
								'currency_code' => strtoupper( DigiCommerce()->get_option( 'currency', 'USD' ) ),
								'value'         => '0',
							),
						),
					);
				}

				// Regular subscription payment
				$billing_cycles[] = array(
					'frequency'      => array(
						'interval_unit'  => strtoupper( $subscription_data['subscription_period'] ),
						'interval_count' => 1,
					),
					'tenure_type'    => 'REGULAR',
					'sequence'       => $sequence,
					'total_cycles'   => 0,
					'pricing_scheme' => array(
						'fixed_price' => array(
							'currency_code' => strtoupper( DigiCommerce()->get_option( 'currency', 'USD' ) ),
							'value'         => number_format( $subscription_data['regular_price'] ?? $price, 2, '.', '' ),
						),
					),
				);
			}

			// Create plan data
			$plan_data = array(
				'product_id'          => $product_id_paypal,
				'name'                => wp_strip_all_tags( $name ),
				'billing_cycles'      => $billing_cycles,
				'payment_preferences' => array(
					'auto_bill_outstanding'     => true,
					'payment_failure_threshold' => 3,
				),
			);

			$plan_response = wp_remote_post(
				$this->get_api_url() . '/v1/billing/plans',
				array(
					'headers' => array(
						'Authorization' => 'Bearer ' . $access_token,
						'Content-Type'  => 'application/json',
						'Prefer'        => 'return=representation',
					),
					'body'    => json_encode( $plan_data ),
				)
			);

			if ( is_wp_error( $plan_response ) ) {
				throw new Exception( 'Failed to create PayPal plan: ' . $plan_response->get_error_message() );
			}

			$response_body = wp_remote_retrieve_body( $plan_response );
			$response_data = json_decode( $response_body, true );

			if ( empty( $response_data['id'] ) ) {
				throw new Exception( 'PayPal plan ID not found in response' );
			}

			return $response_data['id'];

		} catch ( Exception $e ) {
			throw $e;
		}
	}

	/**
	 * Process refund for PayPal payments
	 *
	 * @param int        $order_id The order ID to refund
	 * @param float|null $amount The amount to refund
	 * @return bool Whether the refund was successful
	 * @throws Exception If the refund fails
	 */
	public function process_refund( $order_id, $amount = null ) {
		try {
			global $wpdb;

			// Get PayPal IDs
			$order_meta = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT meta_key, meta_value FROM {$wpdb->prefix}digicommerce_order_meta 
            WHERE order_id = %d AND meta_key IN ('_paypal_order_id', '_paypal_subscription_id', '_paypal_capture_id')",
					$order_id
				),
				ARRAY_A
			);

			if ( empty( $order_meta ) ) {
				throw new Exception( 'PayPal order/subscription ID not found' );
			}

			$paypal_ids   = array_column( $order_meta, 'meta_value', 'meta_key' );
			$access_token = $this->get_access_token();

			$refund_processed = false;

			// Get subscription ID if exists
			$subscription_id = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT si.subscription_id 
            FROM {$wpdb->prefix}digicommerce_subscription_items si
            WHERE si.order_id = %d",
					$order_id
				)
			);

			// Handle subscription
			if ( ! empty( $paypal_ids['_paypal_subscription_id'] ) && $subscription_id ) {

				// 1. Process payment refund first
				if ( $amount > 0 ) {
					$capture_id = null;

					// First try to get capture ID from stored meta
					if ( ! empty( $paypal_ids['_paypal_capture_id'] ) ) {
						$capture_id = $paypal_ids['_paypal_capture_id'];
					}

					// If no stored capture ID, try to get from recent transactions
					if ( ! $capture_id ) {
						// Get transactions from last 30 days
						$start_time        = date( 'Y-m-d\TH:i:s\Z', strtotime( '-30 days' ) );
						$end_time          = date( 'Y-m-d\TH:i:s\Z' );
						$transactions_url  = $this->get_api_url() . "/v1/billing/subscriptions/{$paypal_ids['_paypal_subscription_id']}/transactions";
						$transactions_url .= "?start_time={$start_time}&end_time={$end_time}";

						$transactions_response = wp_remote_get(
							$transactions_url,
							array(
								'headers' => array(
									'Authorization' => 'Bearer ' . $access_token,
									'Content-Type'  => 'application/json',
								),
							)
						);

						if ( ! is_wp_error( $transactions_response ) ) {
							$transactions_data = json_decode( wp_remote_retrieve_body( $transactions_response ), true );
							if ( ! empty( $transactions_data['transactions'] ) ) {
								foreach ( $transactions_data['transactions'] as $transaction ) {
									if ( $transaction['status'] === 'COMPLETED' ) {
										$capture_id = $transaction['id'];
										break;
									}
								}
							}
						}
					}

					// If still no capture ID, try to get from order
					if ( ! $capture_id && ! empty( $paypal_ids['_paypal_order_id'] ) ) {
						$order_url      = $this->get_api_url() . "/v2/checkout/orders/{$paypal_ids['_paypal_order_id']}";
						$order_response = wp_remote_get(
							$order_url,
							array(
								'headers' => array(
									'Authorization' => 'Bearer ' . $access_token,
									'Content-Type'  => 'application/json',
								),
							)
						);

						if ( ! is_wp_error( $order_response ) ) {
							$order_data = json_decode( wp_remote_retrieve_body( $order_response ), true );
							$capture_id = $order_data['purchase_units'][0]['payments']['captures'][0]['id'] ?? null;
						}
					}

					// Process refund if we have a capture ID
					if ( $capture_id ) {
						$refund_url = $this->get_api_url() . "/v2/payments/captures/{$capture_id}/refund";

						$refund_data = array(
							'amount' => array(
								'value'         => number_format( $amount, 2, '.', '' ),
								'currency_code' => strtoupper( DigiCommerce()->get_option( 'currency', 'USD' ) ),
							),
						);

						$request_id      = uniqid( 'refund_', true );
						$refund_response = wp_remote_post(
							$refund_url,
							array(
								'headers' => array(
									'Authorization'     => 'Bearer ' . $access_token,
									'Content-Type'      => 'application/json',
									'PayPal-Request-Id' => $request_id,
								),
								'body'    => json_encode( $refund_data ),
							)
						);

						if ( ! is_wp_error( $refund_response ) ) {
							$refund_code = wp_remote_retrieve_response_code( $refund_response );
							if ( $refund_code === 201 || $refund_code === 200 ) {
								$refund_processed = true;

								// Log refund details
								$log_data = array(
									'amount'          => $amount,
									'capture_id'      => $capture_id,
									'refund_response' => json_decode( wp_remote_retrieve_body( $refund_response ), true ),
									'request_id'      => $request_id,
									'timestamp'       => current_time( 'mysql' ),
								);

								$wpdb->insert(
									$wpdb->prefix . 'digicommerce_order_meta',
									array(
										'order_id'   => $order_id,
										'meta_key'   => 'refund_log',
										'meta_value' => maybe_serialize( $log_data ),
									),
									array( '%d', '%s', '%s' )
								);
							} else {
								throw new Exception( 'Failed to process refund: ' . wp_remote_retrieve_body( $refund_response ) );
							}
						} else {
							throw new Exception( 'Failed to process refund: ' . $refund_response->get_error_message() );
						}
					} else {
						throw new Exception( 'No valid capture ID found for refund' );
					}
				}

				// 2. Cancel subscription
				$subscription_url = $this->get_api_url() . "/v1/billing/subscriptions/{$paypal_ids['_paypal_subscription_id']}/cancel";
				$cancel_response  = wp_remote_post(
					$subscription_url,
					array(
						'headers' => array(
							'Authorization' => 'Bearer ' . $access_token,
							'Content-Type'  => 'application/json',
						),
						'body'    => json_encode( array( 'reason' => 'Refund requested' ) ),
					)
				);

				if ( ! is_wp_error( $cancel_response ) ) {
						$cancel_code = wp_remote_retrieve_response_code( $cancel_response );
					if ( $cancel_code === 204 ) {
						// Update subscription status
						$wpdb->update(
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
						$wpdb->update(
							$wpdb->prefix . 'digicommerce_subscription_schedule',
							array( 'status' => 'cancelled' ),
							array(
								'subscription_id' => $subscription_id,
								'status'          => 'pending',
							),
							array( '%s' ),
							array( '%d', '%s' )
						);

						// Add subscription note
						$wpdb->insert(
							$wpdb->prefix . 'digicommerce_subscription_meta',
							array(
								'subscription_id' => $subscription_id,
								'meta_key'        => 'note',
								'meta_value'      => esc_html__( 'Subscription cancelled due to refund request.', 'digicommerce' ),
							),
							array( '%d', '%s', '%s' )
						);

						do_action( 'digicommerce_subscription_cancelled', $subscription_id );
					} else {
						throw new Exception( 'Failed to cancel subscription: ' . wp_remote_retrieve_body( $cancel_response ) );
					}
				} else {
					throw new Exception( 'Failed to cancel subscription: ' . $cancel_response->get_error_message() );
				}
			} else {
				// Handle regular order refund
				if ( ! empty( $paypal_ids['_paypal_order_id'] ) && $amount > 0 ) {
					$order_url      = $this->get_api_url() . "/v2/checkout/orders/{$paypal_ids['_paypal_order_id']}";
					$order_response = wp_remote_get(
						$order_url,
						array(
							'headers' => array(
								'Authorization' => 'Bearer ' . $access_token,
								'Content-Type'  => 'application/json',
							),
						)
					);

					if ( ! is_wp_error( $order_response ) ) {
						$order_data = json_decode( wp_remote_retrieve_body( $order_response ), true );
						$capture_id = $order_data['purchase_units'][0]['payments']['captures'][0]['id'] ?? null;

						if ( $capture_id ) {
							$refund_url = $this->get_api_url() . "/v2/payments/captures/{$capture_id}/refund";
							$request_id = uniqid( 'refund_', true );

							$refund_data = array(
								'amount' => array(
									'value'         => number_format( $amount, 2, '.', '' ),
									'currency_code' => strtoupper( DigiCommerce()->get_option( 'currency', 'USD' ) ),
								),
							);

							$refund_response = wp_remote_post(
								$refund_url,
								array(
									'headers' => array(
										'Authorization' => 'Bearer ' . $access_token,
										'Content-Type'  => 'application/json',
										'PayPal-Request-Id' => $request_id,
									),
									'body'    => json_encode( $refund_data ),
								)
							);

							if ( ! is_wp_error( $refund_response ) ) {
									$refund_code = wp_remote_retrieve_response_code( $refund_response );
								if ( $refund_code === 201 || $refund_code === 200 ) {
									$refund_processed = true;

									$log_data = array(
										'amount'          => $amount,
										'capture_id'      => $capture_id,
										'refund_response' => json_decode( wp_remote_retrieve_body( $refund_response ), true ),
										'request_id'      => $request_id,
										'timestamp'       => current_time( 'mysql' ),
									);

									$wpdb->insert(
										$wpdb->prefix . 'digicommerce_order_meta',
										array(
											'order_id'   => $order_id,
											'meta_key'   => 'refund_log',
											'meta_value' => maybe_serialize( $log_data ),
										),
										array( '%d', '%s', '%s' )
									);
								} else {
									throw new Exception( 'Failed to process refund: ' . wp_remote_retrieve_body( $refund_response ) );
								}
							} else {
								throw new Exception( 'Failed to process refund: ' . $refund_response->get_error_message() );
							}
						} else {
							throw new Exception( 'No capture ID found in order' );
						}
					} else {
						throw new Exception( 'Failed to get order details: ' . $order_response->get_error_message() );
					}
				}
			}

			// Update order status if refund was processed
			if ( $refund_processed ) {
				$wpdb->update(
					$wpdb->prefix . 'digicommerce_orders',
					array(
						'status'        => 'refunded',
						'date_modified' => current_time( 'mysql' ),
					),
					array( 'id' => $order_id ),
					array( '%s', '%s' ),
					array( '%d' )
				);

				do_action( 'digicommerce_order_refunded', $order_id, $amount );
			}

			return true;

		} catch ( Exception $e ) {
			throw new Exception( 'Refund failed: ' . $e->getMessage() );
		}
	}

	/**
	 * Verify PayPal webhook signature
	 */
	public function verify_webhook_signature( $headers, $payload, $webhook_id ) {
		if ( empty( $webhook_id ) ) {
			throw new Exception( 'Webhook ID not configured' );
		}

		$transmission_id = $headers['PayPal-Transmission-Id'] ?? '';
		$timestamp       = $headers['PayPal-Transmission-Time'] ?? '';
		$signature       = $headers['PayPal-Transmission-Sig'] ?? '';
		$cert_url        = $headers['PayPal-Cert-Url'] ?? '';
		$auth_algo       = $headers['PayPal-Auth-Algo'] ?? '';

		if ( empty( $transmission_id ) || empty( $timestamp ) ||
			empty( $signature ) || empty( $cert_url ) || empty( $auth_algo ) ) {
			throw new Exception( 'Missing required webhook headers' );
		}

		$verify_data = array(
			'auth_algo'         => $auth_algo,
			'cert_url'          => $cert_url,
			'transmission_id'   => $transmission_id,
			'transmission_sig'  => $signature,
			'transmission_time' => $timestamp,
			'webhook_id'        => $webhook_id,
			'webhook_event'     => json_decode( $payload ),
		);

		$response = wp_remote_post(
			$this->get_api_url() . '/v1/notifications/verify-webhook-signature',
			array(
				'headers' => array(
					'Authorization' => 'Bearer ' . $this->get_access_token(),
					'Content-Type'  => 'application/json',
				),
				'body'    => json_encode( $verify_data ),
			)
		);

		if ( is_wp_error( $response ) ) {
			throw new Exception( $response->get_error_message() );
		}

		$body = json_decode( wp_remote_retrieve_body( $response ) );
		return isset( $body->verification_status ) && $body->verification_status === 'SUCCESS';
	}
}

DigiCommerce_PayPal::instance();
