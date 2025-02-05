<?php
/**
 * PayPal Webhook Handler class
 */
class DigiCommerce_PayPal_Webhook {
	private static $instance = null;

	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		// Add webhook endpoint
		add_action( 'rest_api_init', array( $this, 'register_webhook_endpoint' ) );
	}

	/**
	 * Register webhook endpoint
	 */
	public function register_webhook_endpoint() {
		register_rest_route(
			'digicommerce/v2',
			'/paypal-webhook',
			array(
				'methods' => 'POST',
				'callback' => array($this, 'handle_webhook'),
				'permission_callback' => '__return_true',
				'args' => array(
					'event_type' => array(
						'required' => true,
						'type' => 'string',
						'description' => 'PayPal webhook event type',
						'enum' => array(
							'PAYMENT.SALE.COMPLETED',
							'PAYMENT.SALE.REFUNDED',
							'BILLING.SUBSCRIPTION.ACTIVATED',
							'BILLING.SUBSCRIPTION.UPDATED',
							'BILLING.SUBSCRIPTION.CANCELLED',
							'BILLING.SUBSCRIPTION.SUSPENDED',
							'BILLING.SUBSCRIPTION.PAYMENT.SUCCEEDED',
							'BILLING.SUBSCRIPTION.PAYMENT.FAILED'
						),
						'sanitize_callback' => 'sanitize_text_field'
					),
					'resource' => array(
						'required' => true,
						'type' => 'object',
						'description' => 'PayPal webhook event resource data'
					)
				)
			)
		);
	}

	/**
	 * Handle webhook events
	 */
	public function handle_webhook($request) {
		try {
			$payload = $request->get_body();
			$webhook_id = DigiCommerce()->get_option('paypal_webhook_id');
	
			if (empty($webhook_id)) {
				return new WP_Error(
					'invalid_webhook',
					'PayPal webhook ID is not configured',
					array('status' => 401)
				);
			}
	
			// Verify webhook signature
			$headers = getallheaders();
			$paypal = DigiCommerce_PayPal::instance();
	
			if (!$paypal->verify_webhook_signature($headers, $payload, $webhook_id)) {
				return new WP_Error(
					'invalid_signature',
					'Invalid webhook signature',
					array('status' => 401)
				);
			}
	
			$event = json_decode($payload);
	
			// Validate event data
			if (empty($event) || !isset($event->event_type) || !isset($event->resource)) {
				return new WP_Error(
					'invalid_payload',
					'Invalid webhook payload',
					array('status' => 400)
				);
			}
	
			// Handle based on event type
			switch ($event->event_type) {
				case 'PAYMENT.SALE.COMPLETED':
					$this->handle_payment_completed($event);
					break;
	
				case 'PAYMENT.SALE.REFUNDED':
					$this->handle_refund_event($event);
					break;
	
				case 'BILLING.SUBSCRIPTION.ACTIVATED':
				case 'BILLING.SUBSCRIPTION.UPDATED':
				case 'BILLING.SUBSCRIPTION.CANCELLED':
				case 'BILLING.SUBSCRIPTION.SUSPENDED':
					$this->handle_subscription_status_change($event);
					break;
	
				case 'BILLING.SUBSCRIPTION.PAYMENT.SUCCEEDED':
					$this->handle_subscription_payment($event);
					break;
	
				case 'BILLING.SUBSCRIPTION.PAYMENT.FAILED':
					$this->handle_subscription_payment_failed($event);
					break;
	
				default:
					return new WP_Error(
						'unsupported_event',
						'Unsupported webhook event type',
						array('status' => 400)
					);
			}
	
			return new WP_REST_Response(
				array(
					'status' => 'success',
					'message' => 'Webhook processed successfully',
					'event_type' => $event->event_type
				),
				200
			);
	
		} catch (Exception $e) {
			return new WP_Error(
				'webhook_error',
				$e->getMessage(),
				array('status' => 500)
			);
		}
	}

	/**
	 * Handle refund events
	 */
	private function handle_refund_event( $event ) {
		global $wpdb;

		try {
			// Get PayPal sale ID
			$sale_id = $event->resource->sale_id;

			// Find order by PayPal sale ID
			$order_id = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT order_id FROM {$wpdb->prefix}digicommerce_order_meta 
                WHERE meta_key IN ('paypal_sale_id', 'paypal_subscription_payment_id') 
                AND meta_value = %s",
					$sale_id
				)
			);

			if ( ! $order_id ) {
				throw new Exception( 'Order not found for sale ID: ' . $sale_id );
			}

			// Start transaction
			$wpdb->query( 'START TRANSACTION' );

			try {
				// Update order status
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

				// Store refund details
				$wpdb->insert(
					$wpdb->prefix . 'digicommerce_order_meta',
					array(
						'order_id'   => $order_id,
						'meta_key'   => 'paypal_refund_id',
						'meta_value' => $event->resource->refund_id,
					),
					array( '%d', '%s', '%s' )
				);

				// Add order note
				$wpdb->insert(
					$wpdb->prefix . 'digicommerce_order_notes',
					array(
						'order_id' => $order_id,
						'content'  => sprintf(
							'Order refunded in PayPal. Refund ID: %s',
							$event->resource->refund_id
						),
						'author'   => 'PayPal',
						'date'     => current_time( 'mysql' ),
					),
					array( '%d', '%s', '%s', '%s' )
				);

				// Handle subscription if exists
				$subscription_id = $wpdb->get_var(
					$wpdb->prepare(
						"SELECT si.subscription_id 
                    FROM {$wpdb->prefix}digicommerce_subscription_items si
                    WHERE si.order_id = %d",
						$order_id
					)
				);

				if ( $subscription_id ) {
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
							'meta_value'      => 'Subscription cancelled due to refund in PayPal.',
						),
						array( '%d', '%s', '%s' )
					);

					do_action( 'digicommerce_subscription_cancelled', $subscription_id );
				}

				$wpdb->query( 'COMMIT' );
				do_action( 'digicommerce_order_refunded', $order_id, $event->resource->amount->total );

			} catch ( Exception $e ) {
				$wpdb->query( 'ROLLBACK' );
				throw $e;
			}
		} catch ( Exception $e ) {
			throw $e;
		}
	}

	/**
	 * Handle subscription status changes
	 */
	private function handle_subscription_status_change($event) {
		global $wpdb;
	
		try {
			$subscription_id = $event->resource->id;
	
			// Get local subscription ID and current status
			$subscription_data = $wpdb->get_row(
				$wpdb->prepare(
					"SELECT si.subscription_id, s.status as current_status
					FROM {$wpdb->prefix}digicommerce_subscription_items si
					JOIN {$wpdb->prefix}digicommerce_order_meta om ON si.order_id = om.order_id
					JOIN {$wpdb->prefix}digicommerce_subscriptions s ON si.subscription_id = s.id
					WHERE om.meta_key = 'paypal_subscription_id' 
					AND om.meta_value = %s",
					$subscription_id
				)
			);
	
			if (!$subscription_data) {
				throw new Exception('Local subscription not found for PayPal subscription ID: ' . $subscription_id);
			}
	
			$local_subscription_id = $subscription_data->subscription_id;
			$current_status = $subscription_data->current_status;
	
			// Map PayPal status to local status
			$status_map = array(
				'ACTIVE' => 'active',
				'SUSPENDED' => 'paused',
				'CANCELLED' => 'cancelled',
				'EXPIRED' => 'expired',
			);
	
			$new_status = $status_map[$event->resource->status] ?? 'cancelled';
	
			// Start transaction
			$wpdb->query('START TRANSACTION');
	
			try {
				// Only update if status actually changed
				if ($current_status !== $new_status) {
					// Update subscription status
					$wpdb->update(
						$wpdb->prefix . 'digicommerce_subscriptions',
						array(
							'status' => $new_status,
							'date_modified' => current_time('mysql'),
						),
						array('id' => $local_subscription_id),
						array('%s', '%s'),
						array('%d')
					);
	
					// If cancelled, update all pending schedules
					if ($new_status === 'cancelled') {
						// Cancel pending schedules
						$wpdb->update(
							$wpdb->prefix . 'digicommerce_subscription_schedule',
							array('status' => 'cancelled'),
							array(
								'subscription_id' => $local_subscription_id,
								'status' => 'pending',
							),
							array('%s'),
							array('%d', '%s')
						);
					}
	
					// Add subscription note
					$wpdb->insert(
						$wpdb->prefix . 'digicommerce_subscription_meta',
						array(
							'subscription_id' => $local_subscription_id,
							'meta_key' => 'note',
							'meta_value' => sprintf(
								'Subscription status changed from %s to %s in PayPal.',
								$current_status,
								$new_status
							),
						),
						array('%d', '%s', '%s')
					);
	
					// Store the PayPal status update timestamp
					$wpdb->insert(
						$wpdb->prefix . 'digicommerce_subscription_meta',
						array(
							'subscription_id' => $local_subscription_id,
							'meta_key' => 'paypal_status_updated',
							'meta_value' => current_time('mysql'),
						),
						array('%d', '%s', '%s')
					);
	
					$wpdb->query('COMMIT');
					do_action('digicommerce_subscription_updated', $local_subscription_id, $new_status);
				}
	
			} catch (Exception $e) {
				$wpdb->query('ROLLBACK');
				throw $e;
			}
	
		} catch (Exception $e) {
			throw $e;
		}
	}

	/**
	 * Handle successful subscription payments
	 */
	private function handle_subscription_payment( $event ) {
		global $wpdb;

		try {
			$subscription_id = $event->resource->id;

			// Get local subscription ID
			$local_subscription_id = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT si.subscription_id 
                FROM {$wpdb->prefix}digicommerce_subscription_items si
                JOIN {$wpdb->prefix}digicommerce_order_meta om ON si.order_id = om.order_id
                WHERE om.meta_key = 'paypal_subscription_id' 
                AND om.meta_value = %s",
					$subscription_id
				)
			);

			if ( ! $local_subscription_id ) {
				throw new Exception( 'Local subscription not found for PayPal subscription ID: ' . $subscription_id );
			}

			// Update next payment date
			if ( ! empty( $event->resource->billing_info->next_billing_time ) ) {
				$next_payment = date( 'Y-m-d H:i:s', strtotime( $event->resource->billing_info->next_billing_time ) );

				$wpdb->update(
					$wpdb->prefix . 'digicommerce_subscriptions',
					array(
						'next_payment'  => $next_payment,
						'date_modified' => current_time( 'mysql' ),
					),
					array( 'id' => $local_subscription_id ),
					array( '%s', '%s' ),
					array( '%d' )
				);

				// Update license expiration to match next payment
				$wpdb->query($wpdb->prepare(
					"UPDATE {$wpdb->prefix}digicommerce_licenses l
					JOIN {$wpdb->prefix}digicommerce_subscription_items si ON l.order_id = si.order_id
					SET l.expires_at = %s
					WHERE si.subscription_id = %d",
					$next_payment,
					$local_subscription_id
				));
			}

			// Store payment details
			$wpdb->insert(
				$wpdb->prefix . 'digicommerce_subscription_payments',
				array(
					'subscription_id' => $local_subscription_id,
					'transaction_id'  => $event->resource->transaction_id,
					'amount'          => $event->resource->amount->value,
					'currency'        => $event->resource->amount->currency_code,
					'status'          => 'completed',
					'date_created'    => current_time( 'mysql' ),
				),
				array( '%d', '%s', '%f', '%s', '%s', '%s' )
			);

			do_action( 'digicommerce_subscription_payment_success', $local_subscription_id );

		} catch ( Exception $e ) {
			throw $e;
		}
	}

	/**
	 * Handle failed subscription payments
	 */
	private function handle_subscription_payment_failed( $event ) {
		global $wpdb;

		try {
			$subscription_id = $event->resource->id;

			// Get local subscription ID
			$local_subscription_id = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT si.subscription_id 
                FROM {$wpdb->prefix}digicommerce_subscription_items si
                JOIN {$wpdb->prefix}digicommerce_order_meta om ON si.order_id = om.order_id
                WHERE om.meta_key = 'paypal_subscription_id' 
                AND om.meta_value = %s",
					$subscription_id
				)
			);

			if ( ! $local_subscription_id ) {
				throw new Exception( 'Local subscription not found for PayPal subscription ID: ' . $subscription_id );
			}

			// Store failed payment attempt
			$wpdb->insert(
				$wpdb->prefix . 'digicommerce_subscription_payments',
				array(
					'subscription_id' => $local_subscription_id,
					'transaction_id'  => $event->resource->transaction_id ?? null,
					'amount'          => $event->resource->amount->value,
					'currency'        => $event->resource->amount->currency_code,
					'status'          => 'failed',
					'date_created'    => current_time( 'mysql' ),
				),
				array( '%d', '%s', '%f', '%s', '%s', '%s' )
			);

			// Add subscription note
			$wpdb->insert(
				$wpdb->prefix . 'digicommerce_subscription_meta',
				array(
					'subscription_id' => $local_subscription_id,
					'meta_key'        => 'note',
					'meta_value'      => 'Subscription payment failed in PayPal.',
				),
				array( '%d', '%s', '%s' )
			);

			do_action( 'digicommerce_subscription_payment_failed', $local_subscription_id );

		} catch ( Exception $e ) {
			throw $e;
		}
	}
}

// Initialize the class
DigiCommerce_PayPal_Webhook::instance();
