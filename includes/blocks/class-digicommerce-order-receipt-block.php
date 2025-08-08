<?php
/**
 * DigiCommerce Order Receipt Block
 *
 * @package DigiCommerce
 */

defined( 'ABSPATH' ) || exit;

/**
 * Order Receipt Block Class
 */
class DigiCommerce_Order_Receipt_Block {

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
		// Register using block.json from assets/blocks folder
		register_block_type(
			DIGICOMMERCE_PLUGIN_DIR . 'assets/blocks/order-receipt',
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
		// Check if we're on the success page with valid order
		if ( ! isset( $_GET['order_id'] ) || ! isset( $_GET['token'] ) ) {
			return '';
		}

		$order_id = intval( $_GET['order_id'] );
		$token    = sanitize_text_field( $_GET['token'] );

		// Verify order access
		if ( ! DigiCommerce_Orders::instance()->verify_order_access( $order_id, $token ) ) {
			return '';
		}

		// Get order data
		$order_data = DigiCommerce_Orders::instance()->get_order( $order_id );
		if ( ! $order_data ) {
			return '';
		}

		$countries = DigiCommerce()->get_countries();

		// Fetch business details
		$business_name         = DigiCommerce()->get_option( 'business_name' );
		$business_address      = DigiCommerce()->get_option( 'business_address' );
		$business_address2     = DigiCommerce()->get_option( 'business_address2' );
		$business_city         = DigiCommerce()->get_option( 'business_city' );
		$business_postal       = DigiCommerce()->get_option( 'business_postal' );
		$business_vat          = DigiCommerce()->get_option( 'business_vat_number' );
		$business_country      = DigiCommerce()->get_option( 'business_country' );
		$business_country_name = isset( $countries[ $business_country ] ) ? $countries[ $business_country ]['name'] : $business_country;
		$logo_id               = DigiCommerce()->get_option( 'business_logo' );
		$logo_url              = $logo_id ? wp_get_attachment_url( $logo_id ) : null;

		// Billing details
		$billing_details      = $order_data['billing_details'] ?? array();
		$company              = ! empty( $billing_details['company'] ) ? $billing_details['company'] : '';
		$first_name           = ! empty( $billing_details['first_name'] ) ? $billing_details['first_name'] : '';
		$last_name            = ! empty( $billing_details['last_name'] ) ? $billing_details['last_name'] : '';
		$billing_address      = ! empty( $billing_details['address'] ) ? $billing_details['address'] : '';
		$billing_city         = ! empty( $billing_details['city'] ) ? $billing_details['city'] : '';
		$billing_postcode     = ! empty( $billing_details['postcode'] ) ? $billing_details['postcode'] : '';
		$billing_state        = ! empty( $billing_details['state'] ) ? $billing_details['state'] : '';
		$vat_number           = ! empty( $billing_details['vat_number'] ) ? $billing_details['vat_number'] : '';
		$billing_country      = ! empty( $billing_details['country'] ) ? $billing_details['country'] : '';
		$billing_country_name = isset( $countries[ $billing_country ] ) ? $countries[ $billing_country ]['name'] : $billing_country;

		// Get wrapper attributes with all the styling supports
		$wrapper_attributes = get_block_wrapper_attributes();

		ob_start();
		?>
		<div <?php echo $wrapper_attributes; // phpcs:ignore ?>>
			<div class="digicommerce-receipt-header">
				<div class="digicommerce-receipt-header__content">
					<?php if ( $logo_url ) : ?>
						<div class="digicommerce-receipt-header__logo">
							<img src="<?php echo esc_url( $logo_url ); ?>" alt="<?php esc_attr_e( 'Business Logo', 'digicommerce' ); ?>">
						</div>
					<?php endif; ?>

					<div class="digicommerce-receipt-header__invoice">
						<div class="digicommerce-receipt-header__order-id">
							<?php esc_html_e( 'Invoice ID:', 'digicommerce' ); ?>
							<?php echo esc_html( $order_data['order_number'] ?? '—' ); ?>
						</div>
						<div class="digicommerce-receipt-header__order-date">
							<strong><?php esc_html_e( 'Date:', 'digicommerce' ); ?></strong>
							<?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $order_data['date_created'] ) ) ); ?>
						</div>
					</div>
				</div>

				<div class="digicommerce-receipt-info">
					<div class="digicommerce-receipt-info__business">
						<?php if ( ! empty( $business_name ) ) : ?>
							<span class="digicommerce-receipt-info__business-name"><?php echo esc_html( $business_name ); ?></span>
						<?php endif; ?>
						<div class="digicommerce-receipt-info__business-address">
							<?php if ( ! empty( $business_address ) ) : ?>
								<span><?php echo esc_html( $business_address ); ?></span>
							<?php endif; ?>
							<?php if ( ! empty( $business_address2 ) ) : ?>
								<span><?php echo esc_html( $business_address2 ); ?></span>
							<?php endif; ?>
							<?php if ( ! empty( $business_city ) && ! empty( $business_postal ) ) : ?>
								<span><?php echo esc_html( DigiCommerce_Orders::instance()->format_city_postal( $business_city, $business_postal, $business_country, $countries ) ); ?></span>
							<?php endif; ?>
							<?php if ( ! empty( $business_country_name ) ) : ?>
								<span><?php echo esc_html( $business_country_name ); ?></span>
							<?php endif; ?>
							<?php if ( ! empty( $business_vat ) ) : ?>
								<span><?php echo esc_html( $business_vat ); ?></span>
							<?php endif; ?>
						</div>
					</div>

					<div class="digicommerce-receipt-info__billing">
						<?php if ( ! empty( $company ) ) : ?>
							<span class="digicommerce-receipt-info__billing-company"><?php echo esc_html( $company ); ?></span>
						<?php endif; ?>
						<div class="digicommerce-receipt-info__billing-address">
							<?php if ( ! empty( $first_name ) && ! empty( $last_name ) ) : ?>
								<span><?php echo esc_html( $first_name . ' ' . $last_name ); ?></span>
							<?php endif; ?>
							<?php if ( ! empty( $billing_address ) ) : ?>
								<span><?php echo esc_html( $billing_address ); ?></span>
							<?php endif; ?>
							<?php if ( ! empty( $billing_city ) && ! empty( $billing_postcode ) ) : ?>
								<span><?php echo esc_html( DigiCommerce_Orders::instance()->format_city_postal( $billing_city, $billing_postcode, $billing_country, $countries ) ); ?></span>
							<?php endif; ?>
							<?php if ( ! empty( $billing_state ) ) : ?>
								<span><?php echo esc_html( $billing_state ); ?></span>
							<?php endif; ?>
							<?php if ( ! empty( $billing_country_name ) ) : ?>
								<span><?php echo esc_html( $billing_country_name ); ?></span>
							<?php endif; ?>
							<?php if ( ! empty( $vat_number ) ) : ?>
								<span><?php esc_html_e( 'VAT: ', 'digicommerce' ); ?><?php echo esc_html( $vat_number ); ?></span>
							<?php endif; ?>
						</div>

						<div class="digicommerce-receipt-info__status">
							<strong><?php esc_html_e( 'Status:', 'digicommerce' ); ?></strong>
							<?php echo esc_html( ucfirst( $order_data['status'] ?? '' ) ); ?>
						</div>
					</div>
				</div>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}
}

// Initialize the block
DigiCommerce_Order_Receipt_Block::init();