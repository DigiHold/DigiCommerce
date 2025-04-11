<?php
defined( 'ABSPATH' ) || exit;

/**
 * Stripe Webhook Handler class
 */
class DigiCommerce_Stripe_Webhook {
	/**
	 * The single instance of the class
	 *
	 * @var DigiCommerce_Stripe_Webhook
	 */
	private static $instance = null;

	/**
	 * Instance
	 *
	 * @return DigiCommerce_Stripe_Webhook instance
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
			'/stripe-webhook',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'handle_webhook' ),
				// Permission callback intentionally returns true for payment webhooks
				// Security is handled via signature verification inside the handler
				'permission_callback' => '__return_true',
				'args'                => array(
					'type' => array(
						'required'          => true,
						'type'              => 'string',
						'description'       => 'Stripe webhook event type',
						'enum'              => array(
							'charge.refunded',
							'invoice.payment_succeeded',
							'invoice.payment_failed',
							'customer.subscription.paused',
							'customer.subscription.updated',
						),
						'sanitize_callback' => 'sanitize_text_field',
					),
					'data' => array(
						'required'    => true,
						'type'        => 'object',
						'description' => 'Stripe webhook event data',
					),
				),
			)
		);
	}

	/**
	 * Handle webhook events
	 *
	 * @param WP_REST_Request $request Request object.
	 */
	public function handle_webhook( $request ) {
		$webhook_secret = DigiCommerce()->get_option( 'stripe_webhook_secret' );

		try {
			if ( empty( $webhook_secret ) ) {
				return new WP_Error(
					'invalid_webhook',
					'Stripe webhook secret is not configured',
					array( 'status' => 401 ),
				);
			}

			$payload    = $request->get_body();
			$sig_header = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? ''; // phpcs:ignore

			if ( empty( $sig_header ) ) {
				return new WP_Error(
					'missing_signature',
					'Stripe signature header is missing',
					array( 'status' => 401 ),
				);
			}

			try {
				$event = \Stripe\Webhook::constructEvent(
					$payload,
					$sig_header,
					$webhook_secret
				);
			} catch ( \Stripe\Exception\SignatureVerificationException $e ) {
				return new WP_Error(
					'invalid_signature',
					'Invalid Stripe signature',
					array( 'status' => 401 ),
				);
			} catch ( \UnexpectedValueException $e ) {
				return new WP_Error(
					'invalid_payload',
					'Invalid payload',
					array( 'status' => 400 ),
				);
			}

			// Validate event data
			if ( empty( $event->type ) || empty( $event->data->object ) ) {
				return new WP_Error(
					'invalid_event',
					'Invalid event data',
					array( 'status' => 400 ),
				);
			}

			// Handle based on event type
			switch ( $event->type ) {
				case 'charge.refunded':
					$this->handle_refund_event( $event );
					break;

				case 'invoice.payment_succeeded':
				case 'invoice.payment_failed':
					if ( class_exists( 'DigiCommerce_Pro' ) ) {
						$this->handle_invoice_event( $event );
					}
					break;

				case 'customer.subscription.paused':
				case 'customer.subscription.updated':
					if ( class_exists( 'DigiCommerce_Pro' ) ) {
						$this->handle_subscription_update_event( $event );
					}
					break;

				default:
					return new WP_Error(
						'unsupported_event',
						'Unsupported webhook event type',
						array( 'status' => 400 ),
					);
			}

			return new WP_REST_Response(
				array(
					'status'     => 'success',
					'message'    => 'Webhook processed successfully',
					'event_type' => $event->type,
				),
				200
			);

		} catch ( Exception $e ) {
			return new WP_Error(
				'webhook_error',
				$e->getMessage(),
				array( 'status' => 500 ),
			);
		}
	}

	/**
	 * Handle refund events for both normal and subscription products
	 *
	 * @param object $event Stripe webhook event object.
	 * @throws Exception If an error occurs.
	 */
	public function handle_refund_event( $event ) {
		global $wpdb;

		try {
			$charge         = $event->data->object;
			$payment_intent = $charge->payment_intent ?? null;

			// Get order ID from various possible meta keys
			$order_id = null;

			// Try payment intent first
			if ( $payment_intent ) {
				$query    = $wpdb->prepare(
					"SELECT order_id FROM {$wpdb->prefix}digicommerce_order_meta 
					WHERE meta_key IN ('_stripe_payment_intent_id', '_stripe_initial_payment_intent_id') 
					AND meta_value = %s",
					$payment_intent
				);
				$order_id = $wpdb->get_var( $query ); // phpcs:ignore
			}

			// If still no order_id, try to find it from invoice
			if ( ! $order_id && isset( $charge->invoice ) ) {
				$invoice_id = $charge->invoice;

				$query      = $wpdb->prepare(
					"SELECT order_id FROM {$wpdb->prefix}digicommerce_order_meta 
					WHERE meta_key = '_stripe_invoice_id' AND meta_value = %s",
					$invoice_id
				);
				$order_id  = $wpdb->get_var( $query ); // phpcs:ignore
			}

			// If still no order_id, try to find by charge ID
			if ( ! $order_id ) {
				$query    = $wpdb->prepare(
					"SELECT order_id FROM {$wpdb->prefix}digicommerce_order_meta 
					WHERE meta_key = '_stripe_charge_id' AND meta_value = %s",
					$charge->id
				);
				$order_id = $wpdb->get_var( $query ); // phpcs:ignore
			}

			// If still no order_id, try to find by customer ID
			if ( ! $order_id && isset( $charge->customer ) ) {
				$customer_id = $charge->customer;

				$query = $wpdb->prepare(
					"SELECT o.id 
					FROM {$wpdb->prefix}digicommerce_orders o
					JOIN {$wpdb->prefix}digicommerce_order_meta m ON o.id = m.order_id 
					WHERE m.meta_key = '_stripe_customer_id' 
					AND m.meta_value = %s 
					ORDER BY o.date_created DESC 
					LIMIT 1",
					$customer_id
				);
				$order_id = $wpdb->get_var( $query ); // phpcs:ignore
			}

			// As a last resort, try to find the order by amount
			if ( ! $order_id && isset( $charge->amount ) ) {
				$amount   = $charge->amount / 100; // Convert from cents

				$query    = $wpdb->prepare(
					"SELECT id FROM {$wpdb->prefix}digicommerce_orders 
					WHERE total = %f AND date_created > DATE_SUB(NOW(), INTERVAL 30 DAY)
					ORDER BY date_created DESC 
					LIMIT 1",
					$amount
				);
				$order_id = $wpdb->get_var( $query ); // phpcs:ignore
			}

			if ( ! $order_id ) {
				return;
			}

			// Verify the charge is actually refunded
			if ( empty( $charge->refunded ) || ! $charge->refunded ) {
				return;
			}

			// Start transaction
			$wpdb->query( 'START TRANSACTION' ); // phpcs:ignore

			try {
				// Update order status to refunded
				$update_result = $wpdb->update( // phpcs:ignore
					$wpdb->prefix . 'digicommerce_orders',
					array(
						'status'        => 'refunded',
						'date_modified' => current_time( 'mysql' ),
					),
					array( 'id' => $order_id ),
					array( '%s', '%s' ),
					array( '%d' )
				);

				if ( false === $update_result ) {
					throw new Exception( 'Failed to update order status' );
				}

				// Get refund ID directly from the refund data if available
				$refund_id = null;

				// Try to get refund ID from the event data
				if ( ! empty( $charge->refunds ) && ! empty( $charge->refunds->data ) && is_array( $charge->refunds->data ) ) {
					$refund_id = $charge->refunds->data[0]->id;
				} else {
					// Fall back to API call
					$refunds   = \Stripe\Refund::all( [ 'charge' => $charge->id ] ); // phpcs:ignore
					$refund_id = ! empty( $refunds->data ) && is_array( $refunds->data ) ? $refunds->data[0]->id : null;
				}

				if ( $refund_id ) {
					// Check if refund ID already exists
					$existing_refund = $wpdb->get_var( // phpcs:ignore
						$wpdb->prepare(
							"SELECT id FROM {$wpdb->prefix}digicommerce_order_meta 
							WHERE order_id = %d AND meta_key = '_stripe_refund_id' AND meta_value = %s",
							$order_id,
							$refund_id
						)
					);

					if ( ! $existing_refund ) {
						// Store refund details in order meta
						$wpdb->insert( // phpcs:ignore
							$wpdb->prefix . 'digicommerce_order_meta',
							array(
								'order_id'   => $order_id,
								'meta_key'   => '_stripe_refund_id', // phpcs:ignore
								'meta_value' => $refund_id, // phpcs:ignore
							),
							array( '%d', '%s', '%s' )
						);
					}

					// Also store in the main order table's refund_id field
					$wpdb->update( // phpcs:ignore
						$wpdb->prefix . 'digicommerce_orders',
						array( 'refund_id' => $refund_id ),
						array( 'id' => $order_id ),
						array( '%s' ),
						array( '%d' )
					);
				}

				// Add order note
				$note_content = $refund_id ?
					sprintf(
						// Translators: %s is the refund ID
						esc_html__( 'Order refunded in Stripe. Refund ID: %s', 'digicommerce' ),
						$refund_id
					) : esc_html__( 'Order refunded in Stripe.', 'digicommerce' );

				$wpdb->insert( // phpcs:ignore
					$wpdb->prefix . 'digicommerce_order_notes',
					array(
						'order_id' => $order_id,
						'content'  => $note_content,
						'author'   => 'Stripe',
						'date'     => current_time( 'mysql' ),
					),
					array( '%d', '%s', '%s', '%s' )
				);

				// Handle subscription if exists
				$subscription_id = $wpdb->get_var( // phpcs:ignore
					$wpdb->prepare(
						"SELECT si.subscription_id 
						FROM {$wpdb->prefix}digicommerce_subscription_items si
						WHERE si.order_id = %d",
						$order_id
					)
				);

				if ( $subscription_id ) {
					// Handle subscription cancellation
					$stripe_subscription_id = $wpdb->get_var( // phpcs:ignore
						$wpdb->prepare(
							"SELECT meta_value FROM {$wpdb->prefix}digicommerce_order_meta 
							WHERE order_id = %d AND meta_key = '_stripe_subscription_id'",
							$order_id
						)
					);

					if ( $stripe_subscription_id ) {
						$stripe_subscription = \Stripe\Subscription::retrieve( $stripe_subscription_id );
						if ( 'canceled' !== $stripe_subscription->status ) {
							$stripe_subscription->cancel();
						}
					}

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
							'meta_value'      => esc_html__( 'Subscription cancelled due to order refund in Stripe.', 'digicommerce' ), // phpcs:ignore
						),
						array( '%d', '%s', '%s' )
					);

					do_action( 'digicommerce_subscription_cancelled', $subscription_id );
				}

				$wpdb->query( 'COMMIT' ); // phpcs:ignore
				do_action( 'digicommerce_order_refunded', $order_id, $charge->amount_refunded / 100 );

			} catch ( Exception $e ) {
				$wpdb->query( 'ROLLBACK' ); // phpcs:ignore
				throw $e;
			}
		} catch ( Exception $e ) {
			throw $e;
		}
	}

	/**
	 * Handle invoice payment events for subscriptions
	 *
	 * @param object $event Stripe webhook event object.
	 * @throws Exception If an error occurs.
	 */
	private function handle_invoice_event( $event ) {
		global $wpdb;

		try {
			$invoice = $event->data->object;
			if ( empty( $invoice->subscription ) ) {
				return;
			}

			// Get subscription_id from order meta and subscription items
			$subscription_id = $wpdb->get_var( // phpcs:ignore
				$wpdb->prepare(
					"SELECT si.subscription_id
					FROM {$wpdb->prefix}digicommerce_subscription_items si
					JOIN {$wpdb->prefix}digicommerce_order_meta om ON si.order_id = om.order_id
					WHERE om.meta_key = '_stripe_subscription_id' 
					AND om.meta_value = %s
					LIMIT 1",
					$invoice->subscription
				)
			);

			if ( ! $subscription_id ) {
				return;
			}

			if ( 'invoice.payment_succeeded' === $event->type ) {
				// First, verify the payment with Stripe
				$stripe_subscription = \Stripe\Subscription::retrieve( $invoice->subscription );

				// Get our database subscription details to determine the correct billing period
				$db_subscription = $wpdb->get_row( // phpcs:ignore
					$wpdb->prepare(
						"SELECT * FROM {$wpdb->prefix}digicommerce_subscriptions WHERE id = %d",
						$subscription_id
					)
				);

				if ( ! $db_subscription ) {
					return;
				}

				// Calculate the next payment date based on the current date and billing period
				// This ensures it advances by exactly one period from today
				$billing_period = $db_subscription->billing_period;
				$next_payment = date( 'Y-m-d H:i:s', strtotime( "+1 {$billing_period}" ) ); // phpcs:ignore

				// Update subscription next payment date
				$result1 = $wpdb->update( // phpcs:ignore
					$wpdb->prefix . 'digicommerce_subscriptions',
					array(
						'next_payment'  => $next_payment,
						'date_modified' => current_time( 'mysql' ),
						'status'        => 'active', // Ensure subscription is active
					),
					array( 'id' => $subscription_id ),
					array( '%s', '%s', '%s' ),
					array( '%d' )
				);

				// Update license expiration to match next payment
				// First, get all orders for this subscription
				$order_ids = $wpdb->get_col( // phpcs:ignore
					$wpdb->prepare(
						"SELECT order_id FROM {$wpdb->prefix}digicommerce_subscription_items 
						WHERE subscription_id = %d",
						$subscription_id
					)
				);

				if ( ! empty( $order_ids ) ) {
					// Update all licenses associated with these orders
					$order_ids_placeholders = implode( ',', array_fill( 0, count( $order_ids ), '%d' ) );
					$query_params = array_merge(
						array( $next_payment, 'active', current_time( 'mysql' ) ),
						$order_ids
					);

					$result2 = $wpdb->query( $wpdb->prepare( "UPDATE {$wpdb->prefix}digicommerce_licenses SET expires_at = %s, status = %s, date_modified = %s WHERE order_id IN ($order_ids_placeholders)", $query_params ) ); // phpcs:ignore
				}

				do_action( 'digicommerce_subscription_payment_success', $subscription_id );
			} else {
				do_action( 'digicommerce_subscription_payment_failed', $subscription_id );
			}
		} catch ( Exception $e ) {
			throw $e;
		}
	}

	/**
	 * Handle subscription update events
	 *
	 * @param object $event Stripe webhook event object.
	 * @throws Exception If an error occurs.
	 */
	private function handle_subscription_update_event( $event ) {
		global $wpdb;

		try {
			$subscription = $event->data->object;

			// Get subscription_id from order meta and subscription items
			$subscription_id = $wpdb->get_var( // phpcs:ignore
				$wpdb->prepare(
					"SELECT si.subscription_id
                FROM {$wpdb->prefix}digicommerce_subscription_items si
                JOIN {$wpdb->prefix}digicommerce_order_meta om ON si.order_id = om.order_id
                WHERE om.meta_key = '_stripe_subscription_id' 
                AND om.meta_value = %s
                LIMIT 1",
					$subscription->id
				)
			);

			if ( ! $subscription_id ) {
				return;
			}

			$data = array( 'date_modified' => current_time( 'mysql' ) );

			if ( 'customer.subscription.paused' === $event->type ) {
				$data['status'] = 'paused';
			} else {
				$data['next_payment'] = date( 'Y-m-d H:i:s', $subscription->current_period_end ); // phpcs:ignore

				// Update status based on Stripe status
				if ( 'active' === $subscription->status ) {
					$data['status'] = 'active';
				} elseif ( 'canceled' === $subscription->status ) {
					$data['status'] = 'cancelled';
				}
			}

			$wpdb->update( // phpcs:ignore
				$wpdb->prefix . 'digicommerce_subscriptions',
				$data,
				array( 'id' => $subscription_id ),
				array_fill( 0, count( $data ), '%s' ),
				array( '%d' )
			);

			do_action( 'digicommerce_subscription_updated', $subscription_id, $subscription->status );

		} catch ( Exception $e ) {
			throw $e;
		}
	}

	/**
	 * Get subscription ID from Stripe subscription ID
	 *
	 * @param string $stripe_subscription_id Stripe subscription ID.
	 */
	private function get_subscription_id_from_stripe( $stripe_subscription_id ) {
		global $wpdb;
		return $wpdb->get_var( // phpcs:ignore
			$wpdb->prepare(
				"SELECT si.subscription_id
            FROM {$wpdb->prefix}digicommerce_subscription_items si
            JOIN {$wpdb->prefix}digicommerce_order_meta om ON si.order_id = om.order_id
            WHERE om.meta_key = '_stripe_subscription_id' 
            AND om.meta_value = %s
            LIMIT 1",
				$stripe_subscription_id
			)
		);
	}
}

// Initialize the class
DigiCommerce_Stripe_Webhook::instance();
