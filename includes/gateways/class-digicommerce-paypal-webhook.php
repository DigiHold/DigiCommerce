<?php
/**
 * PayPal Webhook Handler class
 */
class DigiCommerce_PayPal_Webhook {
	/**
	 * Singleton instance
	 *
	 * @var DigiCommerce_PayPal_Webhook
	 */
	private static $instance = null;

	/**
	 * Get the singleton instance
	 *
	 * @return DigiCommerce_PayPal_Webhook
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
				'methods'             => 'POST',
				'callback'            => array( $this, 'handle_webhook' ),
				'permission_callback' => '__return_true',
			)
		);
	}

	/**
	 * Main webhook handler
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response Response object.
	 */
	public function handle_webhook( $request ) {
		try {
			// Get request body
			$payload        = $request->get_body();
			$payload_length = strlen( $payload );

			// Parse the JSON payload
			$event = json_decode( $payload );

			// Validate event data
			if ( empty( $event ) || ! isset( $event->event_type ) || ! isset( $event->resource ) ) {
				return new WP_REST_Response(
					array(
						'status'  => 'error',
						'message' => 'Invalid payload format',
					),
					400
				);
			}

			// Extract event ID and type for verification and logging
			$event_id   = $event->id ?? 'unknown';
			$event_type = $event->event_type ?? 'unknown';

			// Process the event based on type
			switch ( $event_type ) {
				case 'PAYMENT.CAPTURE.COMPLETED':
					$this->process_payment_completed( $event );
					break;

				case 'PAYMENT.CAPTURE.REFUNDED':
					$this->process_refund( $event );
					break;

				case 'BILLING.SUBSCRIPTION.ACTIVATED':
				case 'BILLING.SUBSCRIPTION.UPDATED':
				case 'BILLING.SUBSCRIPTION.CANCELLED':
				case 'BILLING.SUBSCRIPTION.SUSPENDED':
				case 'BILLING.SUBSCRIPTION.EXPIRED':
					$this->process_subscription_status_change( $event );
					break;

				case 'BILLING.SUBSCRIPTION.PAYMENT.FAILED':
					$this->process_subscription_payment_failed( $event );
					break;

				default:
					// Not an error, just not a handled event type
			}

			// Always return success
			return new WP_REST_Response(
				array(
					'status'     => 'success',
					'message'    => 'Webhook processed successfully',
					'event_type' => $event_type,
				),
				200
			);

		} catch ( Exception $e ) {
			// Return 200 even on errors to prevent PayPal from retrying
			return new WP_REST_Response(
				array(
					'status'  => 'success', // Still return success to prevent retries
					'message' => 'Webhook acknowledged',
				),
				200
			);
		}
	}

	/**
	 * Process a payment completion event
	 *
	 * @param object $event The webhook event.
	 * @return bool Success status.
	 */
	private function process_payment_completed( $event ) {
		try {
			global $wpdb;
			$resource = $event->resource;

			// Get the capture ID and PayPal order ID
			$capture_id      = isset( $resource->id ) ? $resource->id : null;
			$paypal_order_id = null;

			if ( isset( $resource->supplementary_data ) &&
				isset( $resource->supplementary_data->related_ids ) &&
				isset( $resource->supplementary_data->related_ids->order_id ) ) {
				$paypal_order_id = $resource->supplementary_data->related_ids->order_id;
			}

			// We need both the capture ID and PayPal order ID to proceed
			if ( ! $capture_id || ! $paypal_order_id ) {
				return false;
			}

			// Find the order using PayPal order ID
			$order_id = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT order_id FROM {$wpdb->prefix}digicommerce_order_meta 
					WHERE meta_key = %s AND meta_value = %s AND order_id > 0",
					'_paypal_order_id',
					$paypal_order_id
				)
			);

			if ( $order_id ) {
				// Check if this capture ID is already linked to this order
				$exists = $wpdb->get_var(
					$wpdb->prepare(
						"SELECT COUNT(*) FROM {$wpdb->prefix}digicommerce_order_meta 
						WHERE order_id = %d AND meta_key = %s",
						$order_id,
						'_paypal_capture_id'
					)
				);

				if ( ! $exists ) {
					// Add the capture ID to the order
					$wpdb->insert(
						$wpdb->prefix . 'digicommerce_order_meta',
						array(
							'order_id'   => $order_id,
							'meta_key'   => '_paypal_capture_id',
							'meta_value' => $capture_id,
						),
						array( '%d', '%s', '%s' ),
					);
				}
			} else {
				// Store the capture ID temporarily with order_id = 0
				// This helps with refunds that may come in before we've processed the order
				$this->store_paypal_id( $capture_id, '_paypal_capture_id' );
			}

			// Process subscription payment if applicable
			$is_subscription_payment = false;
			if ( isset( $resource->billing_agreement_id ) ||
				( isset( $resource->supplementary_data ) &&
				isset( $resource->supplementary_data->related_ids ) &&
				isset( $resource->supplementary_data->related_ids->billing_agreement_id ) ) ) {
				$is_subscription_payment = true;
			}

			if ( $is_subscription_payment ) {
				$this->process_subscription_payment( $event );
			}

			return true;

		} catch ( Exception $e ) {
			return false;
		}
	}

	/**
	 * Store a PayPal ID in the database for future reference
	 *
	 * @param string $id The PayPal ID to store.
	 * @param string $meta_key The meta key to use for storage.
	 */
	private function store_paypal_id( $id, $meta_key ) {
		global $wpdb;

		// Check if this ID is already in the database
		$exists = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->prefix}digicommerce_order_meta 
				WHERE meta_key = %s AND meta_value = %s",
				$meta_key,
				$id
			)
		);

		if ( ! $exists ) {
			// We're not attaching to a specific order yet, just recording the ID
			// This is useful for future lookups, especially with refunds
			$wpdb->insert(
				$wpdb->prefix . 'digicommerce_order_meta',
				array(
					'order_id'   => 0, // Placeholder, can be updated later
					'meta_key'   => $meta_key,
					'meta_value' => $id,
				),
				array( '%d', '%s', '%s' )
			);
		}
	}

	/**
	 * Process a refund event
	 *
	 * @param object $event The webhook event.
	 * @return bool Success status.
	 */
	private function process_refund( $event ) {
		global $wpdb;

		try {
			// Get refund ID and amount
			$refund_id = $event->resource->id ?? '';
			$amount    = 0;
			if ( isset( $event->resource->amount ) && isset( $event->resource->amount->value ) ) {
				$amount = (float) $event->resource->amount->value;
			}

			// Extract capture ID from links
			$capture_id = null;
			if ( isset( $event->resource->links ) && is_array( $event->resource->links ) ) {
				foreach ( $event->resource->links as $link ) {
					if ( isset( $link->rel ) && 'up' === $link->rel && isset( $link->href ) ) {
						$href_parts = explode( '/', $link->href );
						$capture_id = end( $href_parts );
						break;
					}
				}
			}

			if ( ! $capture_id ) {
				return false;
			}

			// First try to find the order directly using the capture ID
			$order_id = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT order_id FROM {$wpdb->prefix}digicommerce_order_meta 
					WHERE meta_key = %s AND meta_value = %s AND order_id > 0",
					'_paypal_capture_id',
					$capture_id,
				)
			);

			// If we couldn't find the order directly, try some alternative lookup methods
			if ( ! $order_id ) {
				// Try to look up the order ID using the relationship between a temporary capture ID
				// record (order_id=0) and a PayPal order ID linked to a real order
				$order_id = $wpdb->get_var(
					$wpdb->prepare(
						"SELECT om_order.order_id 
						FROM {$wpdb->prefix}digicommerce_order_meta AS om_capture
						JOIN {$wpdb->prefix}digicommerce_order_meta AS om_order ON om_capture.meta_value = %s
						WHERE om_capture.meta_key = %s 
						AND om_order.meta_key = %s
						AND om_order.order_id > 0
						LIMIT 1",
						$capture_id,
						'_paypal_capture_id',
						'_paypal_order_id'
					)
				);

				// If still no order, try to find by PayPal order ID from the resource if available
				if ( ! $order_id && isset( $event->resource->supplementary_data ) &&
					isset( $event->resource->supplementary_data->related_ids ) &&
					isset( $event->resource->supplementary_data->related_ids->order_id ) ) {

					$paypal_order_id = $event->resource->supplementary_data->related_ids->order_id;
					$order_id        = $wpdb->get_var(
						$wpdb->prepare(
							"SELECT order_id FROM {$wpdb->prefix}digicommerce_order_meta 
							WHERE meta_key = %s AND meta_value = %s AND order_id > 0",
							'_paypal_order_id',
							$paypal_order_id
						)
					);
				}

				// Last resort - try by customer email
				if ( ! $order_id && isset( $event->resource->payer ) && isset( $event->resource->payer->email_address ) ) {
					$email    = $event->resource->payer->email_address;
					$order_id = $wpdb->get_var(
						$wpdb->prepare(
							"SELECT o.id FROM {$wpdb->prefix}digicommerce_orders o
							JOIN {$wpdb->prefix}digicommerce_order_meta om ON o.id = om.order_id
							WHERE om.meta_key = 'billing_email' AND om.meta_value = %s
							ORDER BY o.id DESC LIMIT 1",
							$email
						)
					);
				}
			}

			// If no order found, log it but don't error
			if ( ! $order_id ) {
				return true;
			}

			// Process the refund
			// Mark order as refunded
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

			// Store refund ID
			$wpdb->insert(
				$wpdb->prefix . 'digicommerce_order_meta',
				array(
					'order_id'   => $order_id,
					'meta_key'   => 'paypal_refund_id',
					'meta_value' => $refund_id,
				),
				array( '%d', '%s', '%s' )
			);

			// Add order note
			$wpdb->insert(
				$wpdb->prefix . 'digicommerce_order_notes',
				array(
					'order_id' => $order_id,
					'content'  => sprintf(
						// translators: %s: refund ID
						esc_html__( 'Order refunded in PayPal. Refund ID: %s', 'digicommerce' ),
						$refund_id
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
						'meta_value'      => esc_html__( 'Subscription cancelled due to refund in PayPal.', 'digicommerce' ),
					),
					array( '%d', '%s', '%s' )
				);

				do_action( 'digicommerce_subscription_cancelled', $subscription_id );
			}

			// Trigger refund action
			do_action( 'digicommerce_order_refunded', $order_id, $amount );

			return true;

		} catch ( Exception $e ) {
			return false;
		}
	}

	/**
	 * Process a subscription payment event
	 *
	 * @param object $event The webhook event.
	 * @return bool Success status.
	 */
	private function process_subscription_payment( $event ) {
		global $wpdb;

		try {
			// Extract subscription ID
			$resource        = $event->resource;
			$subscription_id = null;

			// Try to extract billing agreement ID from different PayPal formats
			if ( isset( $resource->billing_agreement_id ) ) {
				$subscription_id = $resource->billing_agreement_id;
			} elseif ( isset( $resource->supplementary_data ) &&
					isset( $resource->supplementary_data->related_ids ) &&
					isset( $resource->supplementary_data->related_ids->billing_agreement_id ) ) {
				$subscription_id = $resource->supplementary_data->related_ids->billing_agreement_id;
			}

			if ( ! $subscription_id ) {
				return false;
			}

			// Get local subscription ID
			$local_subscription_id = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT si.subscription_id 
					FROM {$wpdb->prefix}digicommerce_subscription_items si
					JOIN {$wpdb->prefix}digicommerce_order_meta om ON si.order_id = om.order_id
					WHERE om.meta_key IN ('_paypal_subscription_id', 'paypal_subscription_id') 
					AND om.meta_value = %s",
					$subscription_id
				)
			);

			if ( ! $local_subscription_id ) {
				return false;
			}

			// Update next payment date if available
			if ( isset( $resource->billing_info ) && isset( $resource->billing_info->next_billing_time ) ) {
				$next_payment = date( 'Y-m-d H:i:s', strtotime( $resource->billing_info->next_billing_time ) ); // phpcs:ignore WordPress.DateTime.RestrictedFunctions.date_date

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
				$wpdb->query(
					$wpdb->prepare(
						"UPDATE {$wpdb->prefix}digicommerce_licenses l 
						JOIN {$wpdb->prefix}digicommerce_subscription_items si 
						ON l.order_id = si.order_id AND l.product_id = si.product_id 
						SET l.expires_at = %s 
						WHERE si.subscription_id = %d",
						$next_payment,
						$local_subscription_id
					)
				);
			}

			// Extract transaction ID and amount
			$transaction_id = isset( $resource->id ) ? $resource->id : null;
			$amount         = 0;
			$currency       = '';

			if ( isset( $resource->amount ) ) {
				$amount = isset( $resource->amount->value ) ? $resource->amount->value :
							( isset( $resource->amount->total ) ? $resource->amount->total : 0 );

				$currency = isset( $resource->amount->currency_code ) ? $resource->amount->currency_code :
							( isset( $resource->amount->currency ) ? $resource->amount->currency : '' );
			}

			// Store payment details
			if ( $transaction_id ) {
				$wpdb->insert(
					$wpdb->prefix . 'digicommerce_subscription_payments',
					array(
						'subscription_id' => $local_subscription_id,
						'transaction_id'  => $transaction_id,
						'amount'          => $amount,
						'currency'        => $currency,
						'status'          => 'completed',
						'date_created'    => current_time( 'mysql' ),
					),
					array( '%d', '%s', '%f', '%s', '%s', '%s' )
				);
			}

			do_action( 'digicommerce_subscription_payment_success', $local_subscription_id );
			return true;

		} catch ( Exception $e ) {
			return false;
		}
	}

	/**
	 * Process a subscription status change event
	 *
	 * @param object $event The webhook event.
	 * @return bool Success status.
	 */
	private function process_subscription_status_change( $event ) {
		global $wpdb;

		try {
			if ( ! isset( $event->resource->id ) || ! isset( $event->resource->status ) ) {
				return false;
			}

			$subscription_id = $event->resource->id;
			$paypal_status   = $event->resource->status;

			// Get local subscription ID and current status
			$subscription_data = $wpdb->get_row(
				$wpdb->prepare(
					"SELECT si.subscription_id, s.status as current_status
					FROM {$wpdb->prefix}digicommerce_subscription_items si
					JOIN {$wpdb->prefix}digicommerce_order_meta om ON si.order_id = om.order_id
					JOIN {$wpdb->prefix}digicommerce_subscriptions s ON si.subscription_id = s.id
					WHERE om.meta_key IN ('_paypal_subscription_id', 'paypal_subscription_id') 
					AND om.meta_value = %s",
					$subscription_id
				)
			);

			if ( ! $subscription_data ) {
				return false;
			}

			$local_subscription_id = $subscription_data->subscription_id;
			$current_status        = $subscription_data->current_status;

			// Map PayPal status to local status
			$status_map = array(
				'ACTIVE'    => 'active',
				'SUSPENDED' => 'paused',
				'CANCELLED' => 'cancelled',
				'EXPIRED'   => 'expired',
			);

			$new_status = isset( $status_map[ $paypal_status ] ) ? $status_map[ $paypal_status ] : 'cancelled';

			// Only update if status actually changed
			if ( $current_status !== $new_status ) {
				// Update subscription status
				$wpdb->update(
					$wpdb->prefix . 'digicommerce_subscriptions',
					array(
						'status'        => $new_status,
						'date_modified' => current_time( 'mysql' ),
					),
					array( 'id' => $local_subscription_id ),
					array( '%s', '%s' ),
					array( '%d' )
				);

				// If cancelled, cancel all pending schedules
				if ( 'cancelled' === $new_status ) {
					$wpdb->update(
						$wpdb->prefix . 'digicommerce_subscription_schedule',
						array( 'status' => 'cancelled' ),
						array(
							'subscription_id' => $local_subscription_id,
							'status'          => 'pending',
						),
						array( '%s' ),
						array( '%d', '%s' )
					);
				}

				// Add subscription note
				$wpdb->insert(
					$wpdb->prefix . 'digicommerce_subscription_meta',
					array(
						'subscription_id' => $local_subscription_id,
						'meta_key'        => 'note',
						'meta_value'      => sprintf(
							'Subscription status changed from %s to %s in PayPal.',
							$current_status,
							$new_status
						),
					),
					array( '%d', '%s', '%s' )
				);

				do_action( 'digicommerce_subscription_updated', $local_subscription_id, $new_status );
			}

			return true;

		} catch ( Exception $e ) {
			return false;
		}
	}

	/**
	 * Process a subscription payment failure event
	 *
	 * @param object $event The webhook event.
	 * @return bool Success status.
	 */
	private function process_subscription_payment_failed( $event ) {
		global $wpdb;

		try {
			if ( ! isset( $event->resource->id ) ) {
				return false;
			}

			$subscription_id = $event->resource->id;

			// Get local subscription ID
			$local_subscription_id = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT si.subscription_id 
					FROM {$wpdb->prefix}digicommerce_subscription_items si
					JOIN {$wpdb->prefix}digicommerce_order_meta om ON si.order_id = om.order_id
					WHERE om.meta_key IN ('_paypal_subscription_id', 'paypal_subscription_id') 
					AND om.meta_value = %s",
					$subscription_id
				)
			);

			if ( ! $local_subscription_id ) {
				return false;
			}

			// Extract transaction details
			$transaction_id = isset( $event->resource->id ) ? $event->resource->id : null;
			$amount         = 0;
			$currency       = '';

			if ( isset( $event->resource->amount ) ) {
				$amount = isset( $event->resource->amount->value ) ? $event->resource->amount->value :
						( isset( $event->resource->amount->total ) ? $event->resource->amount->total : 0 );

				$currency = isset( $event->resource->amount->currency_code ) ? $event->resource->amount->currency_code :
							( isset( $event->resource->amount->currency ) ? $event->resource->amount->currency : '' );
			}

			// Store failed payment attempt
			$wpdb->insert(
				$wpdb->prefix . 'digicommerce_subscription_payments',
				array(
					'subscription_id' => $local_subscription_id,
					'transaction_id'  => $transaction_id,
					'amount'          => $amount,
					'currency'        => $currency,
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
			return true;

		} catch ( Exception $e ) {
			return false;
		}
	}
}

// Initialize the class
DigiCommerce_PayPal_Webhook::instance();
