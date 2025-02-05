<?php
/**
 * Stripe Webhook Handler class
 */
class DigiCommerce_Stripe_Webhook {
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
			'/stripe-webhook',
			array(
				'methods' => 'POST',
				'callback' => array($this, 'handle_webhook'),
				'permission_callback' => '__return_true',
				'args' => array(
					'type' => array(
						'required' => true,
						'type' => 'string',
						'description' => 'Stripe webhook event type',
						'enum' => array(
							'charge.refunded',
							'invoice.payment_succeeded',
							'invoice.payment_failed',
							'customer.subscription.paused',
							'customer.subscription.updated'
						),
						'sanitize_callback' => 'sanitize_text_field'
					),
					'data' => array(
						'required' => true,
						'type' => 'object',
						'description' => 'Stripe webhook event data'
					)
				)
			)
		);
	}

	/**
	 * Handle webhook events
	 */
	public function handle_webhook($request) {
		$webhook_secret = DigiCommerce()->get_option('stripe_webhook_secret');
		
		try {
			if (empty($webhook_secret)) {
				return new WP_Error(
					'invalid_webhook',
					'Stripe webhook secret is not configured',
					array('status' => 401)
				);
			}
	
			$payload = $request->get_body();
			$sig_header = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';
	
			if (empty($sig_header)) {
				return new WP_Error(
					'missing_signature',
					'Stripe signature header is missing',
					array('status' => 401)
				);
			}
	
			try {
				$event = \Stripe\Webhook::constructEvent(
					$payload,
					$sig_header,
					$webhook_secret
				);
			} catch (\Stripe\Exception\SignatureVerificationException $e) {
				return new WP_Error(
					'invalid_signature',
					'Invalid Stripe signature',
					array('status' => 401)
				);
			} catch (\UnexpectedValueException $e) {
				return new WP_Error(
					'invalid_payload',
					'Invalid payload',
					array('status' => 400)
				);
			}
	
			// Validate event data
			if (empty($event->type) || empty($event->data->object)) {
				return new WP_Error(
					'invalid_event',
					'Invalid event data',
					array('status' => 400)
				);
			}
	
			// Handle based on event type
			switch ($event->type) {
				case 'charge.refunded':
					$this->handle_refund_event($event);
					break;
	
				case 'invoice.payment_succeeded':
				case 'invoice.payment_failed':
					if (class_exists('DigiCommerce_Pro')) {
						$this->handle_invoice_event($event);
					}
					break;
	
				case 'customer.subscription.paused':
				case 'customer.subscription.updated':
					if (class_exists('DigiCommerce_Pro')) {
						$this->handle_subscription_update_event($event);
					}
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
					'event_type' => $event->type
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
	 * Handle refund events for both normal and subscription products
	 */
	public function handle_refund_event( $event ) {
		global $wpdb;

		try {
			$charge         = $event->data->object;
			$payment_intent = $charge->payment_intent;

			// Get order ID from various possible meta keys
			$order_id = null;

			// Try payment intent first
			if ( $payment_intent ) {
				$order_id = $wpdb->get_var(
					$wpdb->prepare(
						"SELECT order_id FROM {$wpdb->prefix}digicommerce_order_meta 
                    WHERE meta_key IN ('_stripe_payment_intent_id', '_stripe_initial_payment_intent_id') 
                    AND meta_value = %s",
						$payment_intent
					)
				);
			}

			// If still no order_id, try to find it from charge metadata
			if ( ! $order_id && isset( $charge->metadata->order_id ) ) {
				$order_id = intval( $charge->metadata->order_id );
			}

			if ( ! $order_id ) {
				return;
			}

			// Start transaction
			$wpdb->query( 'START TRANSACTION' );

			try {
				// Update order status to refunded
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

				// Get refund details using Stripe API
				$refunds   = \Stripe\Refund::all( array( 'charge' => $charge->id ) );
				$refund_id = ! empty( $refunds->data ) ? $refunds->data[0]->id : null;

				if ( $refund_id ) {
					// Store refund details in order meta
					$wpdb->insert(
						$wpdb->prefix . 'digicommerce_order_meta',
						array(
							'order_id'   => $order_id,
							'meta_key'   => '_stripe_refund_id',
							'meta_value' => $refund_id,
						),
						array( '%d', '%s', '%s' )
					);
				}

				// Add order note
				$note_content = $refund_id ?
					sprintf( 'Order refunded in Stripe. Refund ID: %s', $refund_id ) :
					'Order refunded in Stripe.';

				$wpdb->insert(
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
				$subscription_id = $wpdb->get_var(
					$wpdb->prepare(
						"SELECT si.subscription_id 
                    FROM {$wpdb->prefix}digicommerce_subscription_items si
                    WHERE si.order_id = %d",
						$order_id
					)
				);

				if ( $subscription_id ) {
					// Handle both initial payment and regular subscription refunds
					$stripe_subscription_id = null;

					// Check if this is an initial payment refund
					if ( isset( $charge->metadata->is_subscription_initial ) && $charge->metadata->is_subscription_initial === 'true' ) {
						// Get subscription ID from meta for initial payment
						$stripe_subscription_id = $wpdb->get_var(
							$wpdb->prepare(
								"SELECT meta_value FROM {$wpdb->prefix}digicommerce_order_meta 
                            WHERE order_id = %d AND meta_key = '_stripe_subscription_id'",
								$charge->metadata->order_id
							)
						);
					} else {
						// Regular subscription payment refund
						$stripe_subscription_id = $wpdb->get_var(
							$wpdb->prepare(
								"SELECT meta_value FROM {$wpdb->prefix}digicommerce_order_meta 
                            WHERE order_id = %d AND meta_key = '_stripe_subscription_id'",
								$order_id
							)
						);
					}

					if ( $stripe_subscription_id ) {
						try {
							$stripe_subscription = \Stripe\Subscription::retrieve( $stripe_subscription_id );
							if ( $stripe_subscription->status !== 'canceled' ) {
								$stripe_subscription->cancel();
							}
						} catch ( Exception $e ) {
						}
					}

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
							'meta_value'      => 'Subscription cancelled due to order refund in Stripe.',
						),
						array( '%d', '%s', '%s' )
					);

					do_action( 'digicommerce_subscription_cancelled', $subscription_id );
				}

				$wpdb->query( 'COMMIT' );
				do_action( 'digicommerce_order_refunded', $order_id, $charge->amount_refunded / 100 );

			} catch ( Exception $e ) {
				$wpdb->query( 'ROLLBACK' );
				throw $e;
			}
		} catch ( Exception $e ) {
			throw $e;
		}
	}

	/**
	 * Handle invoice payment events for subscriptions
	 */
	private function handle_invoice_event( $event ) {
		global $wpdb;

		try {
			$invoice = $event->data->object;
			if ( empty( $invoice->subscription ) ) {
				return;
			}

			// Get subscription_id from order meta and subscription items
			$subscription_id = $wpdb->get_var(
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

			if ( $event->type === 'invoice.payment_succeeded' ) {
				// Update next payment date
				$subscription = \Stripe\Subscription::retrieve( $invoice->subscription );
				$next_payment = date( 'Y-m-d H:i:s', $subscription->current_period_end );

				$wpdb->update(
					$wpdb->prefix . 'digicommerce_subscriptions',
					array(
						'next_payment'  => $next_payment,
						'date_modified' => current_time( 'mysql' ),
					),
					array( 'id' => $subscription_id ),
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
					$subscription_id
				));

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
	 */
	private function handle_subscription_update_event( $event ) {
		global $wpdb;

		try {
			$subscription = $event->data->object;

			// Get subscription_id from order meta and subscription items
			$subscription_id = $wpdb->get_var(
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

			if ( $event->type === 'customer.subscription.paused' ) {
				$data['status'] = 'paused';
			} else {
				$data['next_payment'] = date( 'Y-m-d H:i:s', $subscription->current_period_end );

				// Update status based on Stripe status
				if ( $subscription->status === 'active' ) {
					$data['status'] = 'active';
				} elseif ( $subscription->status === 'canceled' ) {
					$data['status'] = 'cancelled';
				}
			}

			$wpdb->update(
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
	 */
	private function get_subscription_id_from_stripe( $stripe_subscription_id ) {
		global $wpdb;
		return $wpdb->get_var(
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
