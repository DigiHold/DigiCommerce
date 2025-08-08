<?php
/**
 * DigiCommerce Success Message Block
 *
 * @package DigiCommerce
 */

defined( 'ABSPATH' ) || exit;

/**
 * Success Message Block Class
 */
class DigiCommerce_Success_Message_Block {

	/**
	 * Initialize the block
	 */
	public static function init() {
		add_action( 'init', array( __CLASS__, 'register_block' ) );
	}

	/**
	 * Register the block
	 */
	public static function register_block() {
		register_block_type(
			DIGICOMMERCE_PLUGIN_DIR . 'assets/blocks/success-message',
			array(
				'render_callback' => array( __CLASS__, 'render_block' ),
			)
		);
	}

	/**
	 * Render the block
	 *
	 * @param array    $attributes Block attributes.
	 * @param string   $content Block content.
	 * @param WP_Block $block Block instance.
	 * @return string Rendered block.
	 */
	public static function render_block( $attributes, $content, $block ) {
		// Get order data from success page context
		$order_id = isset( $_GET['order_id'] ) ? intval( $_GET['order_id'] ) : 0;
		$token    = isset( $_GET['token'] ) ? sanitize_text_field( wp_unslash( $_GET['token'] ) ) : '';

		if ( ! $order_id || ! $token ) {
			return self::render_expired_session( $attributes );
		}

		// Verify order access
		if ( ! DigiCommerce_Orders::instance()->verify_order_access( $order_id, $token ) ) {
			return self::render_expired_session( $attributes );
		}

		// Get order data
		$order_data = DigiCommerce_Orders::instance()->get_order( $order_id );
		if ( ! $order_data ) {
			return self::render_expired_session( $attributes );
		}

		$billing_details = $order_data['billing_details'] ?? array();
		$first_name      = $billing_details['first_name'] ?? '';

		// Get customizable attributes with defaults
		$success_title   = ! empty( $attributes['successTitle'] ) ? $attributes['successTitle'] : __( 'Thank you for your purchase {name}!', 'digicommerce' );
		$success_message = ! empty( $attributes['successMessage'] ) ? $attributes['successMessage'] : __( 'View the details of your order below.', 'digicommerce' );

		// Replace placeholders
		$success_title   = str_replace( '{name}', esc_html( $first_name ), $success_title );
		$success_message = str_replace( '{name}', esc_html( $first_name ), $success_message );

		// Get wrapper attributes
		$wrapper_attributes = get_block_wrapper_attributes( array(
			'class' => 'digicommerce-success-message',
		) );

		return sprintf(
			'<div %1$s>
				<h2 class="digicommerce-success-title">%2$s</h2>
				<p class="digicommerce-success-subtitle">%3$s</p>
			</div>',
			$wrapper_attributes,
			wp_kses_post( $success_title ),
			wp_kses_post( $success_message )
		);
	}

	/**
	 * Render expired session message
	 *
	 * @param array $attributes Block attributes.
	 * @return string
	 */
	private static function render_expired_session( $attributes ) {
		// Get customizable attributes with defaults
		$expired_title       = ! empty( $attributes['expiredTitle'] ) ? $attributes['expiredTitle'] : __( 'Your session has expired', 'digicommerce' );
		$expired_message     = ! empty( $attributes['expiredMessage'] ) ? $attributes['expiredMessage'] : __( 'Your session has expired. Please log in to view your orders.', 'digicommerce' );
		$expired_button_text = ! empty( $attributes['expiredButtonText'] ) ? $attributes['expiredButtonText'] : __( 'Go to your account', 'digicommerce' );
		$show_icon           = isset( $attributes['showIcon'] ) ? $attributes['showIcon'] : true;

		$account_url = get_permalink( DigiCommerce()->get_option( 'account_page_id' ) );

		$wrapper_attributes = get_block_wrapper_attributes( array(
			'class' => 'digicommerce digicommerce-success',
		) );

		$icon_html = '';
		if ( $show_icon ) {
			$icon_html = '<div class="digicommerce-expired-icon">
				<svg fill="none" viewBox="0 0 24 24" width="96" height="96" stroke="currentColor" aria-hidden="true">
					<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
				</svg>
			</div>';
		}

		return sprintf(
			'<div %1$s>
				<div class="digicommerce-expired-session">
					%2$s
					<h2 class="digicommerce-expired-title">%3$s</h2>
					<p class="digicommerce-expired-text">%4$s</p>
					<div class="digicommerce-expired-action">
						<a href="%5$s" class="digicommerce-button wp-element-button">%6$s</a>
					</div>
				</div>
			</div>',
			$wrapper_attributes,
			$icon_html,
			wp_kses_post( $expired_title ),
			wp_kses_post( $expired_message ),
			esc_url( $account_url ),
			esc_html( $expired_button_text )
		);
	}
}

// Initialize the block
DigiCommerce_Success_Message_Block::init();