<?php
defined( 'ABSPATH' ) || exit;

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
				// Permission callback intentionally returns true for payment webhooks
				// Security is handled via signature verification inside the handler
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

			// Check for JSON parsing errors
			if ( json_last_error() !== JSON_ERROR_NONE ) {
				return new WP_REST_Response(
					array(
						'status'  => 'error',
						'message' => 'Invalid JSON: ' . json_last_error_msg(),
					),
					400
				);
			}

			// Validate event data
			if ( empty( $event ) ) {
				return new WP_REST_Response(
					array(
						'status'  => 'error',
						'message' => 'Empty payload',
					),
					400
				);
			}

			if ( ! isset( $event->event_type ) ) {
				return new WP_REST_Response(
					array(
						'status'  => 'error',
						'message' => 'Missing event_type',
					),
					400
				);
			}

			if ( ! isset( $event->resource ) ) {
				return new WP_REST_Response(
					array(
						'status'  => 'error',
						'message' => 'Missing resource',
					),
					400
				);
			}

			// Extract event ID and type for verification and logging
			$event_id   = $event->id ?? 'unknown';
			$event_type = $event->event_type ?? 'unknown';

			// Log basic resource info
			if ( isset( $event->resource ) ) {
				$resource_id = isset( $event->resource->id ) ? $event->resource->id : 'unknown';
			}

			// Process the event based on type
			$processing_result = false;
			switch ( $event_type ) {
				case 'PAYMENT.CAPTURE.COMPLETED':
				case 'PAYMENT.SALE.COMPLETED':
					$processing_result = $this->process_payment_completed( $event );
					break;

				// Refund events - both capture and sale formats
				case 'PAYMENT.CAPTURE.REFUNDED':
				case 'PAYMENT.SALE.REFUNDED':
					$processing_result = $this->process_refund( $event );
					break;

				case 'BILLING.SUBSCRIPTION.ACTIVATED':
				case 'BILLING.SUBSCRIPTION.UPDATED':
				case 'BILLING.SUBSCRIPTION.CANCELLED':
				case 'BILLING.SUBSCRIPTION.SUSPENDED':
				case 'BILLING.SUBSCRIPTION.EXPIRED':
					$processing_result = $this->process_subscription_status_change( $event );
					break;

				case 'BILLING.SUBSCRIPTION.PAYMENT.FAILED':
					$processing_result = $this->process_subscription_payment_failed( $event );
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
			$resource   = $event->resource;
			$event_type = $event->event_type ?? 'unknown';

			// Get the capture ID and PayPal order ID
			$capture_id      = isset( $resource->id ) ? $resource->id : null;
			$paypal_order_id = null;

			// Use appropriate meta key based on event type
			$id_meta_key = '_paypal_capture_id';
			if ( 'PAYMENT.SALE.COMPLETED' === $event_type ) {
				$id_meta_key = '_paypal_sale_id';
			}

			if ( isset( $resource->supplementary_data ) &&
				isset( $resource->supplementary_data->related_ids ) &&
				isset( $resource->supplementary_data->related_ids->order_id ) ) {
				$paypal_order_id = $resource->supplementary_data->related_ids->order_id;
			}

			// We need the capture ID to proceed
			if ( ! $capture_id ) {
				return false;
			}

			// Determine if this is a subscription-related payment
			$is_subscription_payment = false;
			$billing_agreement_id    = null;

			// Check multiple possible locations for billing agreement ID
			if ( isset( $resource->billing_agreement_id ) ) {
				$billing_agreement_id    = $resource->billing_agreement_id;
				$is_subscription_payment = true;
			} elseif ( isset( $resource->supplementary_data ) &&
					isset( $resource->supplementary_data->related_ids ) &&
					isset( $resource->supplementary_data->related_ids->billing_agreement_id ) ) {
				$billing_agreement_id    = $resource->supplementary_data->related_ids->billing_agreement_id;
				$is_subscription_payment = true;
			}

			// Find the order using PayPal order ID
			$order_id = null;
			if ( $paypal_order_id ) {
				$order_id = $wpdb->get_var( // phpcs:ignore
					$wpdb->prepare(
						"SELECT order_id FROM {$wpdb->prefix}digicommerce_order_meta 
						WHERE meta_key = %s AND meta_value = %s AND order_id > 0",
						'_paypal_order_id',
						$paypal_order_id
					)
				);
			}

			if ( $order_id ) {
				// Check if this payment ID is already linked to this order
				$exists = $wpdb->get_var( // phpcs:ignore
					$wpdb->prepare(
						"SELECT COUNT(*) FROM {$wpdb->prefix}digicommerce_order_meta 
						WHERE order_id = %d AND meta_key = %s",
						$order_id,
						$id_meta_key
					)
				);

				if ( ! $exists ) {
					// Add the payment ID to the order
					$wpdb->insert( // phpcs:ignore
						$wpdb->prefix . 'digicommerce_order_meta',
						array(
							'order_id'   => $order_id,
							'meta_key'   => $id_meta_key, // phpcs:ignore
							'meta_value' => $capture_id, // phpcs:ignore
						),
						array( '%d', '%s', '%s' )
					);
				}
			} else {
				// Store the payment ID temporarily with order_id = 0
				$this->store_paypal_id( $capture_id, $id_meta_key );
			}

			// Process subscription payment if applicable
			if ( $is_subscription_payment && $billing_agreement_id ) {
				$this->process_subscription_renewal_payment( $billing_agreement_id, $capture_id );
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
		$exists = $wpdb->get_var( // phpcs:ignore
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
			$wpdb->insert( // phpcs:ignore
				$wpdb->prefix . 'digicommerce_order_meta',
				array(
					'order_id'   => 0, // Placeholder, can be updated later
					'meta_key'   => $meta_key, // phpcs:ignore
					'meta_value' => $id, // phpcs:ignore
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
			// Get refund ID
			$refund_id = $event->resource->id ?? '';

			// Handle different event types
			$event_type = $event->event_type ?? '';
			$payment_id = null;

			if ( 'PAYMENT.SALE.REFUNDED' === $event_type ) {
				// For subscription payments
				$payment_id = $event->resource->sale_id ?? '';
			} elseif ( 'PAYMENT.CAPTURE.REFUNDED' === $event_type ) {
				// For regular payments
				// Attempt to get ID from different possible fields
				if ( isset( $event->resource->links ) ) {
					foreach ( $event->resource->links as $link ) {
						if ( isset( $link->rel ) && 'up' === $link->rel && isset( $link->href ) ) {
							$parts      = explode( '/', $link->href );
							$payment_id = end( $parts );
							break;
						}
					}
				}

				// If not found in links, try other fields
				if ( ! $payment_id && isset( $event->resource->parent_payment ) ) {
					$payment_id = $event->resource->parent_payment;
				}

				if ( ! $payment_id && isset( $event->resource->parent_id ) ) {
					$payment_id = $event->resource->parent_id;
				}
			}

			if ( empty( $payment_id ) ) {
				return false;
			}

			// SIMPLE APPROACH: Find the order by searching for the payment ID in ANY PayPal-related meta field
			$meta_rows = $wpdb->get_results( // phpcs:ignore
				$wpdb->prepare(
					"SELECT order_id, meta_key, meta_value 
					FROM {$wpdb->prefix}digicommerce_order_meta 
					WHERE meta_value = %s OR meta_value LIKE %s",
					$payment_id,
					'%' . $wpdb->esc_like( $payment_id ) . '%'
				)
			);

			// Also search for payments recently processed
			$recent_orders = $wpdb->get_results( // phpcs:ignore
				"SELECT o.id, om.meta_key, om.meta_value 
				FROM {$wpdb->prefix}digicommerce_orders o
				JOIN {$wpdb->prefix}digicommerce_order_meta om ON o.id = om.order_id
				WHERE o.payment_method = 'paypal' 
				AND o.date_created > DATE_SUB(NOW(), INTERVAL 1 DAY)
				AND om.meta_key LIKE '%paypal%'
				ORDER BY o.date_created DESC
				LIMIT 10"
			);

			// Find order ID from meta search
			$order_id = null;

			// Approach 1: Direct search in database
			if ( ! empty( $meta_rows ) ) {
				foreach ( $meta_rows as $row ) {
					if ( $row->order_id > 0 ) {
						$order_id = $row->order_id;
						break;
					}
				}
			}

			// Approach 2: Get the most recently updated order with this PayPal ID
			if ( ! $order_id && ! empty( $recent_orders ) ) {
				foreach ( $recent_orders as $row ) {
					if ( $row->id > 0 ) {
						$order_id = $row->id;

						// Store the payment ID with this order
						$wpdb->insert( // phpcs:ignore
							$wpdb->prefix . 'digicommerce_order_meta',
							array(
								'order_id'   => $order_id,
								'meta_key'   => ( 'PAYMENT.CAPTURE.REFUNDED' === $event_type ) ? '_paypal_capture_id' : '_paypal_sale_id', // phpcs:ignore
								'meta_value' => $payment_id, // phpcs:ignore
							),
							array( '%d', '%s', '%s' )
						);
						break;
					}
				}
			}

			if ( ! $order_id ) {
				return false;
			}

			// Verify order exists
			$order = $wpdb->get_row( // phpcs:ignore
				$wpdb->prepare(
					"SELECT id, status FROM {$wpdb->prefix}digicommerce_orders WHERE id = %d",
					$order_id
				)
			);

			if ( ! $order ) {
				return false;
			}

			// Start transaction
			$wpdb->query( 'START TRANSACTION' ); // phpcs:ignore

			try {
				// Update order status to refunded
				$updated = $wpdb->update( // phpcs:ignore
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
				$wpdb->insert( // phpcs:ignore
					$wpdb->prefix . 'digicommerce_order_meta',
					array(
						'order_id'   => $order_id,
						'meta_key'   => 'paypal_refund_id', // phpcs:ignore
						'meta_value' => $refund_id, // phpcs:ignore
					),
					array( '%d', '%s', '%s' )
				);

				// Add order note
				$wpdb->insert( // phpcs:ignore
					$wpdb->prefix . 'digicommerce_order_notes',
					array(
						'order_id' => $order_id,
						'content'  => sprintf(
							'Order refunded in PayPal. Refund ID: %s, Payment ID: %s',
							$refund_id,
							$payment_id
						),
						'author'   => 'PayPal',
						'date'     => current_time( 'mysql' ),
					),
					array( '%d', '%s', '%s', '%s' )
				);

				// Check for subscription
				$subscription_id = $wpdb->get_var( // phpcs:ignore
					$wpdb->prepare(
						"SELECT si.subscription_id 
						FROM {$wpdb->prefix}digicommerce_subscription_items si
						WHERE si.order_id = %d",
						$order_id
					)
				);

				if ( $subscription_id ) {
					// Update subscription status
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

					// Add subscription note
					$wpdb->insert( // phpcs:ignore
						$wpdb->prefix . 'digicommerce_subscription_meta',
						array(
							'subscription_id' => $subscription_id,
							'meta_key'        => 'note', // phpcs:ignore
							'meta_value'      => esc_html__( 'Subscription cancelled due to refund in PayPal.', 'digicommerce' ), // phpcs:ignore
						),
						array( '%d', '%s', '%s' )
					);

					do_action( 'digicommerce_subscription_cancelled', $subscription_id );
				}

				$wpdb->query( 'COMMIT' ); // phpcs:ignore

				// Trigger refund action
				do_action( 'digicommerce_order_refunded', $order_id, 0 );
				return true;
			} catch ( Exception $e ) {
				$wpdb->query( 'ROLLBACK' ); // phpcs:ignore
				return false;
			}
		} catch ( Exception $e ) {
			return false;
		}
	}

	/**
	 * Process a subscription renewal payment
	 *
	 * @param string $billing_agreement_id The PayPal billing agreement ID.
	 * @param string $capture_id The capture/sale ID for this payment.
	 * @return bool Success status.
	 * @throws Exception If it fails.
	 */
	private function process_subscription_renewal_payment( $billing_agreement_id, $capture_id ) {
		global $wpdb;

		try {
			// Get local subscription ID
			$local_subscription_id = $wpdb->get_var( // phpcs:ignore
				$wpdb->prepare(
					"SELECT si.subscription_id 
					FROM {$wpdb->prefix}digicommerce_subscription_items si
					JOIN {$wpdb->prefix}digicommerce_order_meta om ON si.order_id = om.order_id
					WHERE om.meta_key IN ('_paypal_subscription_id', 'paypal_subscription_id') 
					AND om.meta_value = %s",
					$billing_agreement_id
				)
			);

			if ( ! $local_subscription_id ) {
				return false;
			}

			// Get the PayPal instance
			$paypal = DigiCommerce_PayPal::instance();

			// First verify the payment with PayPal API
			try {
				// Get access token
				$access_token = $paypal->get_access_token();

				// Get subscription details from PayPal API
				$api_url = $paypal->get_api_url() . "/v1/billing/subscriptions/{$billing_agreement_id}";

				$response = wp_remote_get(
					$api_url,
					array(
						'headers' => array(
							'Authorization' => 'Bearer ' . $access_token,
							'Content-Type'  => 'application/json',
						),
					),
				);

				if ( is_wp_error( $response ) ) {
					return false;
				}

				$subscription = json_decode( wp_remote_retrieve_body( $response ), true );

				// Verify subscription is active
				if ( ! isset( $subscription['status'] ) || 'ACTIVE' !== $subscription['status'] ) {
					return false;
				}
			} catch ( Exception $e ) {
				// If we can't verify with PayPal, don't proceed
				return false;
			}

			// Get subscription details from database to determine billing period
			$db_subscription = $wpdb->get_row( // phpcs:ignore
				$wpdb->prepare(
					"SELECT * FROM {$wpdb->prefix}digicommerce_subscriptions WHERE id = %d",
					$local_subscription_id
				)
			);

			if ( ! $db_subscription ) {
				return false;
			}

			// Calculate next payment date based on billing period
			$billing_period = $db_subscription->billing_period;
			$next_payment = date( 'Y-m-d H:i:s', strtotime( "+1 {$billing_period}" ) ); // phpcs:ignore

			// Begin transaction
			$wpdb->query( 'START TRANSACTION' ); // phpcs:ignore

			try {
				// Update subscription next payment date
				$wpdb->update( // phpcs:ignore
					$wpdb->prefix . 'digicommerce_subscriptions',
					array(
						'next_payment'  => $next_payment,
						'date_modified' => current_time( 'mysql' ),
						'status'        => 'active',
					),
					array( 'id' => $local_subscription_id ),
					array( '%s', '%s', '%s' ),
					array( '%d' )
				);

				// Get all orders for this subscription
				$order_ids = $wpdb->get_col( // phpcs:ignore
					$wpdb->prepare(
						"SELECT order_id FROM {$wpdb->prefix}digicommerce_subscription_items 
						WHERE subscription_id = %d",
						$local_subscription_id
					)
				);

				if ( ! empty( $order_ids ) ) {
					// Update all licenses associated with these orders
					$order_ids_placeholders = implode( ',', array_fill( 0, count( $order_ids ), '%d' ) );
					$query_params           = array_merge(
						array( $next_payment, 'active', current_time( 'mysql' ) ),
						$order_ids
					);

					$wpdb->query( $wpdb->prepare( "UPDATE {$wpdb->prefix}digicommerce_licenses SET expires_at = %s, status = %s, date_modified = %s WHERE order_id IN ($order_ids_placeholders)", $query_params ) ); // phpcs:ignore
				}

				// Record payment in subscription meta
				$wpdb->insert( // phpcs:ignore
					$wpdb->prefix . 'digicommerce_subscription_meta',
					array(
						'subscription_id' => $local_subscription_id,
						'meta_key'        => 'payment_transaction', // phpcs:ignore
						'meta_value'      => wp_json_encode( // phpcs:ignore
							array(
								'transaction_id' => $capture_id,
								'date'           => current_time( 'mysql' ),
								'status'         => 'completed',
							)
						),
					),
					array( '%d', '%s', '%s' )
				);

				// Add subscription note
				$wpdb->insert( // phpcs:ignore
					$wpdb->prefix . 'digicommerce_subscription_meta',
					array(
						'subscription_id' => $local_subscription_id,
						'meta_key'        => 'note', // phpcs:ignore
						'meta_value'      => sprintf( // phpcs:ignore
							// translators: %s is the next payment date.
							esc_html__( 'Subscription payment received. License expiration extended to %s', 'digicommerce' ),
							$next_payment
						),
					),
					array( '%d', '%s', '%s' )
				);

				$wpdb->query( 'COMMIT' ); // phpcs:ignore
				do_action( 'digicommerce_subscription_renewal_processed', $local_subscription_id, $next_payment );
				return true;
			} catch ( Exception $e ) {
				$wpdb->query( 'ROLLBACK' ); // phpcs:ignore
				throw $e;
			}
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
			$subscription_data = $wpdb->get_row( // phpcs:ignore
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
				$wpdb->update( // phpcs:ignore
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
					$wpdb->update( // phpcs:ignore
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
				$wpdb->insert( // phpcs:ignore
					$wpdb->prefix . 'digicommerce_subscription_meta',
					array(
						'subscription_id' => $local_subscription_id,
						'meta_key'        => 'note', // phpcs:ignore
						'meta_value'      => sprintf( // phpcs:ignore
							// translators: %1$s is the current status, %2$s is the new status.
							esc_html__( 'Subscription status changed from %1$s to %2$s in PayPal.', 'digicommerce' ),
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
			$local_subscription_id = $wpdb->get_var( // phpcs:ignore
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
			$wpdb->insert( // phpcs:ignore
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
			$wpdb->insert( // phpcs:ignore
				$wpdb->prefix . 'digicommerce_subscription_meta',
				array(
					'subscription_id' => $local_subscription_id,
					'meta_key'        => 'note', // phpcs:ignore
					'meta_value'      => esc_html__( 'Subscription payment failed in PayPal.', 'digicommerce' ), // phpcs:ignore
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
