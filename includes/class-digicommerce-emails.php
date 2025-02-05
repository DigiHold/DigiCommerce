<?php
/**
 * Email handling class
 */
class DigiCommerce_Emails {

	/**
	 * Instance of the class
	 */
	private static $instance = null;

	/**
	 * Get instance
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
		add_action( 'init', array( $this, 'init_mailer' ) );
		add_filter( 'wp_mail_content_type', array( $this, 'set_html_content_type' ) );
	}

	/**
	 * Initialize mailer settings
	 */
	public function init_mailer() {
		// Set from name and email if configured
		add_filter(
			'wp_mail_from',
			function ( $email ) {
				$from_email = DigiCommerce()->get_option( 'email_from_address' );
				return $from_email ? $from_email : $email;
			}
		);

		add_filter(
			'wp_mail_from_name',
			function ( $name ) {
				$from_name = DigiCommerce()->get_option( 'email_from_name' );
				return $from_name ? $from_name : $name;
			}
		);
	}

	/**
	 * Set HTML content type for emails
	 */
	public function set_html_content_type() {
		return 'text/html';
	}

	/**
	 * Send order confirmation email
	 */
	public function send_order_confirmation( $order_id ) {
		$order = DigiCommerce_Orders::instance()->get_order( $order_id );
		if ( ! $order ) {
			return false;
		}

		// Get email subject
		$subject = apply_filters(
			'digicommerce_order_confirmation_subject',
			sprintf(
				/* translators: %1$s: Site name, %2$s: Order ID */
				esc_html__( '[%1$s] Order Confirmation %2$s', 'digicommerce' ),
				get_bloginfo( 'name' ),
				$order['order_number'] ?? $order_id
			),
			$order_id
		);

		// Get email content
		ob_start();
		DigiCommerce()->get_template(
			'emails/order-confirmation.php',
			array(
				'order_id' => $order_id,
			)
		);
		$message = ob_get_clean();

		// Send email
		$headers = array(
			'Content-Type: text/html; charset=UTF-8',
		);

		// Use the email from the order data
		$recipient_email = $order['billing_details']['email'] ?? '';
		if ( empty( $recipient_email ) ) {
			return false;
		}

		return wp_mail( $recipient_email, $subject, $message, $headers );
	}

	/**
	 * Send welcome email
	 */
	public function send_welcome_email( $user_email, $password ) {
		$subject = apply_filters(
			'digicommerce_welcome_email_subject',
			sprintf(
				/* translators: %s: Site name */
				esc_html__( 'Welcome to %s', 'digicommerce' ),
				get_bloginfo( 'name' )
			)
		);

		ob_start();
		DigiCommerce()->get_template(
			'emails/welcome.php',
			array(
				'email'     => $user_email,
				'password'  => $password,
				'login_url' => get_permalink( DigiCommerce()->get_option( 'account_page_id' ) ),
			)
		);
		$message = ob_get_clean();

		$headers = array(
			'Content-Type: text/html; charset=UTF-8',
		);

		return wp_mail( $user_email, $subject, $message, $headers );
	}

	/**
	 * Send password reset email
	 */
	public function send_password_reset( $user_email, $key, $user_login ) {
		$subject = apply_filters(
			'digicommerce_password_reset_subject',
			sprintf(
				/* translators: %s: Site name */
				esc_html__( '%s Password Reset', 'digicommerce' ),
				get_bloginfo( 'name' )
			)
		);

		$reset_url = add_query_arg(
			array(
				'action'  => 'rp',
				'key'     => $key,
				'login'   => rawurlencode( $user_login ),
				'expires' => time() + ( 60 * 60 ), // 1 hour expiration
			),
			get_permalink( DigiCommerce()->get_option( 'reset_password_page_id' ) )
		);

		ob_start();
		DigiCommerce()->get_template(
			'emails/password-reset.php',
			array(
				'reset_url'  => $reset_url,
				'user_login' => $user_login,
			)
		);
		$message = ob_get_clean();

		$headers = array(
			'Content-Type: text/html; charset=UTF-8',
		);

		return wp_mail( $user_email, $subject, $message, $headers );
	}

	/**
	 * Send order cancelled notification
	 */
	public function send_order_cancelled( $order_id ) {
		$order = DigiCommerce_Orders::instance()->get_order( $order_id );
		if ( ! $order ) {
			return false;
		}

		// Get email subject
		$subject = apply_filters(
			'digicommerce_order_cancelled_subject',
			sprintf(
				/* translators: %1$s: Site name, %2$s: Order ID */
				esc_html__( '[%1$s] Order %2$s Cancelled', 'digicommerce' ),
				get_bloginfo( 'name' ),
				$order['order_number'] ?? $order_id
			),
			$order_id
		);

		// Get email content
		ob_start();
		DigiCommerce()->get_template(
			'emails/order-cancelled.php',
			array(
				'order_id' => $order_id,
			)
		);
		$message = ob_get_clean();

		// Send email
		$headers = array(
			'Content-Type: text/html; charset=UTF-8',
		);

		// Use the email from the order data
		$recipient_email = $order['billing_details']['email'] ?? '';
		if ( empty( $recipient_email ) ) {
			return false;
		}

		return wp_mail( $recipient_email, $subject, $message, $headers );
	}

	/**
	 * Send order refunded notification
	 */
	public function send_order_refunded( $order_id ) {
		$order = DigiCommerce_Orders::instance()->get_order( $order_id );
		if ( ! $order ) {
			return false;
		}

		// Get email subject
		$subject = apply_filters(
			'digicommerce_order_refunded_subject',
			sprintf(
				/* translators: %1$s: Site name, %2$s: Order ID */
				esc_html__( '[%1$s] Order %2$s Refunded', 'digicommerce' ),
				get_bloginfo( 'name' ),
				$order['order_number'] ?? $order_id
			),
			$order_id
		);

		// Get email content
		ob_start();
		DigiCommerce()->get_template(
			'emails/order-refunded.php',
			array(
				'order_id' => $order_id,
			)
		);
		$message = ob_get_clean();

		// Send email
		$headers = array(
			'Content-Type: text/html; charset=UTF-8',
		);

		// Use the email from the order data
		$recipient_email = $order['billing_details']['email'] ?? '';
		if ( empty( $recipient_email ) ) {
			return false;
		}

		return wp_mail( $recipient_email, $subject, $message, $headers );
	}

	/**
	 * Send new order notification to admin
	 */
	public function send_new_order_admin( $order_id ) {
		$order = DigiCommerce_Orders::instance()->get_order( $order_id );
		if ( ! $order ) {
			return false;
		}

		// Get admin email
		$admin_email = get_option( 'admin_email' );

		// Get email subject
		$subject = apply_filters(
			'digicommerce_new_order_admin_subject',
			sprintf(
				/* translators: %1$s: Site name, %2$s: Order number */
				esc_html__( '[%1$s] New Order %2$s', 'digicommerce' ),
				get_bloginfo( 'name' ),
				$order['order_number'] ?? $order_id
			),
			$order_id
		);

		// Get email content
		ob_start();
		DigiCommerce()->get_template(
			'emails/admin-new-order.php',
			array(
				'order_id'   => $order_id,
				'order_data' => $order,
			)
		);
		$message = ob_get_clean();

		// Set email headers
		$headers = array(
			'Content-Type: text/html; charset=UTF-8',
		);

		return wp_mail( $admin_email, $subject, $message, $headers );
	}

	/**
	 * Get header for emails
	 */
	public static function get_header() {
		// Start building the header HTML
		$header_html = '<div class="header">';

		$logo       = DigiCommerce()->get_option( 'email_header_logo' );
		$logo_url   = $logo ? wp_get_attachment_url( $logo ) : '';
		$logo_width = DigiCommerce()->get_option( 'email_header_logo_width' );
		if ( ! empty( $logo_url ) ) {
			// Get the logo metadata to determine its width and height
			$img_metadata = wp_get_attachment_metadata( $logo );
			$img_width    = isset( $img_metadata['width'] ) ? $img_metadata['width'] : '';
			$img_height   = isset( $img_metadata['height'] ) ? $img_metadata['height'] : '';

			$header_html .= '<img src="' . esc_url( $logo_url ) . '"';
			if ( ! empty( $img_width ) ) {
				$header_html .= ' width="' . esc_attr( $img_width ) . '"';
			}
			if ( ! empty( $img_height ) ) {
				$header_html .= ' height="' . esc_attr( $img_height ) . '"';
			}
			if ( ! empty( $logo_width ) ) {
				$header_html .= ' style="max-width:' . esc_attr( $logo_width ) . 'px;height:auto;"';
			}
			$header_html .= ' alt="' . esc_attr( get_bloginfo( 'name' ) ) . '" class="logo" />';
		} else {
			$logo_id = get_theme_mod( 'custom_logo' );
			if ( $logo_id ) {
				// Add the site logo if it exists
				$header_html .= wp_get_attachment_image( $logo_id, 'full', false, array( 'class' => 'logo' ) );
			} else {
				// Fallback to site name if no logos exist
				$header_html .= '<h1>' . esc_html( get_bloginfo( 'name' ) ) . '</h1>';
			}
		}

		// Close the header div
		$header_html .= '</div>';

		// Return the constructed HTML
		return $header_html;
	}

	/**
	 * Get footer for emails
	 */
	public static function get_footer() {
		// Start building the footer HTML
		$footer_html = '<div class="footer">';

		// Check if social links should be shown
		if ( DigiCommerce()->get_option( 'show_social_links_in_email' ) === 1
			&& ! empty( DigiCommerce()->get_option( 'social_links' ) ) ) {
			$footer_html .= '<div class="social-links">';

			// Get the social links from options
			$social_links = DigiCommerce()->get_option( 'social_links', array() );

			// Loop through each social link and generate the HTML
			foreach ( $social_links as $link ) {
				$footer_html .= '<a href="' . esc_url( $link['url'] ) . '" target="_blank"><img src="' . DIGICOMMERCE_PLUGIN_URL . 'assets/img/social/' . esc_attr( $link['platform'] ) . '.png" alt="' . esc_attr( $link['platform'] ) . '"></a>';
			}

			$footer_html .= '</div>';
		}

		// Add static footer text
		$footer_text = DigiCommerce()->get_option( 'email_footer_text' );
		if ( ! empty( $footer_text ) ) {
			$footer_html .= str_replace(
				array( '{year}', '{site}' ),
				array( date( 'Y' ), get_bloginfo( 'name' ) ),
				wp_kses_post( $footer_text )
			);
		}

		$footer_html .= '</div>';

		// Return the constructed HTML
		return $footer_html;
	}

	/**
	 * Get common email styles
	 */
	public static function get_styles() {
		return include DIGICOMMERCE_PLUGIN_DIR . 'includes/data/email-styles.php';
	}
}
DigiCommerce_Emails::instance();
