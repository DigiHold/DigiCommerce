<?php
defined( 'ABSPATH' ) || exit;

/**
 * Setup Wizard
 *
 * @package DigiCommerce
 */
class DigiCommerce_Setup_Wizard {
	/**
	 * Instance of the class
	 *
	 * @var DigiCommerce_Setup_Wizard|null
	 */
	private static $instance = null;

	/**
	 * Get instance of the class
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
	public function __construct() {
		// Enqueue required scripts/styles.
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		add_action( 'wp_ajax_digicommerce_setup_wizard_save', array( $this, 'ajax_save_step' ) );
		add_action( 'wp_ajax_digicommerce_skip_setup', array( $this, 'ajax_skip_setup' ) );

		// Add wizard to footer.
		add_action( 'admin_footer', array( $this, 'setup_wizard' ) );

		// Remove notices during wizard.
		add_action( 'admin_init', array( $this, 'remove_notices' ) );
	}

	/**
	 * Setup wizard
	 */
	public function setup_wizard() {
		?>
		<div class="digicommerce-setup-wizard">
			<div class="digicommerce-setup">
				<?php
				$this->setup_wizard_welcome();
				$this->setup_wizard_business();
				if ( ! class_exists( 'DigiCommerce_Pro' ) ) {
					$this->setup_wizard_addons();
				}
				$this->setup_wizard_ready();
				?>
			</div>
		</div>
		<?php
	}

	/**
	 * Remove notices during wizard
	 */
	public function remove_notices() {
		// Remove all notices from other plugins.
		remove_all_actions( 'admin_notices' );
		remove_all_actions( 'all_admin_notices' );
	}

	/**
	 * Enqueue scripts
	 */
	public function enqueue_scripts() {
		wp_enqueue_style(
			'digicommerce-setup',
			DIGICOMMERCE_PLUGIN_URL . 'assets/css/admin/wizard.css',
			array(),
			DIGICOMMERCE_VERSION
		);

		wp_enqueue_script(
			'choices',
			DIGICOMMERCE_PLUGIN_URL . 'assets/js/vendor/choices.js',
			array(),
			'11.0.2',
			true
		);

		wp_enqueue_script(
			'digicommerce-setup',
			DIGICOMMERCE_PLUGIN_URL . 'assets/js/admin/wizard.js',
			array( 'choices' ),
			DIGICOMMERCE_VERSION,
			true
		);

		wp_localize_script(
			'digicommerce-setup',
			'digicommerceSetup',
			array(
				'ajaxurl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'digicommerce_setup_wizard' ),
				'i18n'    => array(
					'saving' => esc_html__( 'Saving your settings...', 'digicommerce' ),
					'error'  => esc_html__( 'Error occurred. Please try again.', 'digicommerce' ),
					'select' => esc_html__( 'Type to search...', 'digicommerce' ),
				),
			)
		);
	}

	/**
	 * Setup wizard welcome
	 */
	public function setup_wizard_welcome() {
		?>
		<div class="digicommerce-setup-content setup-wizard">
			<img src="<?php echo esc_url( DIGICOMMERCE_PLUGIN_URL . 'assets/img/wizard-logo.svg' ); // phpcs:ignore ?>" alt="DigiCommerce Setup Wizard" class="wizard-logo">

			<div class="setup-wizard-content">
				<div class="setup-wizard-text">
					<h2><?php esc_html_e( 'Welcome to the family !', 'digicommerce' ); ?></h2>
					<p><?php esc_html_e( 'This setup wizard will help you configure the basic settings. It\'s completely optional and shouldn\'t take longer than two minutes.', 'digicommerce' ); ?></p>
				</div>
				<p class="digicommerce-setup-actions">
					<a href="#" class="continue gold"><?php esc_html_e( 'Let\'s Go!', 'digicommerce' ); ?></a>
					<a href="#" class="skip normal"><?php esc_html_e( 'Skip Setup', 'digicommerce' ); ?></a>
				</p>
			</div>
		</div>
		<?php
	}

	/**
	 * Setup wizard business
	 */
	public function setup_wizard_business() {
		$options    = DigiCommerce_Settings::instance()->get_options();
		$currencies = DigiCommerce()->get_currencies();
		$countries  = DigiCommerce()->get_countries();
		?>
		<div class="digicommerce-setup-content setup-form hidden">
			<div class="setup-wizard-text">
				<h2><?php esc_html_e( 'Business Information', 'digicommerce' ); ?></h2>
				<p><?php esc_html_e( 'Please enter your business details below. You can update these details anytime from DigiCommerce → Settings.', 'digicommerce' ); ?></p>
			</div>
			<form method="post" class="business-form">
				<div class="business-form-fields">
					<div class="form-fields-wrapper">
						<div class="form-fields">
							<label for="wizard_business_name"><?php esc_html_e( 'Business Name', 'digicommerce' ); ?></label>
							<input type="text" id="wizard_business_name" name="business_name" value="<?php echo esc_attr( $options['business_name'] ?? '' ); ?>" class="regular-text">
						</div>

						<div class="form-fields">
							<label for="wizard_business_vat_number"><?php esc_html_e( 'VAT Number', 'digicommerce' ); ?></label>
							<input type="text" id="wizard_business_vat_number" name="business_vat_number" value="<?php echo esc_attr( $options['business_vat_number'] ?? '' ); ?>" class="regular-text">
						</div>
					</div>

					<div class="form-fields">
						<label for="wizard_business_country"><?php esc_html_e( 'Country', 'digicommerce' ); ?></label>
						<select name="business_country" id="wizard_business_country" class="digicommerce__search">
							<option value=""><?php esc_html_e( 'Select your country', 'digicommerce' ); ?></option>
							<?php foreach ( $countries as $code => $country ) : ?>
								<option value="<?php echo esc_attr( $code ); ?>" <?php selected( $options['business_country'] ?? '', $code ); ?>>
									<?php echo esc_html( $country['name'] ); ?>
								</option>
							<?php endforeach; ?>
						</select>
					</div>

					<div class="form-fields-wrapper">
						<div class="form-fields">
							<label for="wizard_business_address"><?php esc_html_e( 'Address', 'digicommerce' ); ?></label>
							<input type="text" id="wizard_business_address" name="business_address" value="<?php echo esc_attr( $options['business_address'] ?? '' ); ?>" class="regular-text">
						</div>

						<div class="form-fields">
							<label for="wizard_business_address2"><?php esc_html_e( 'Address Line 2', 'digicommerce' ); ?></label>
							<input type="text" id="wizard_business_address2" name="business_address2" value="<?php echo esc_attr( $options['business_address2'] ?? '' ); ?>" class="regular-text">
						</div>
					</div>

					<div class="form-fields-wrapper">
						<div class="form-fields">
							<label for="wizard_business_city"><?php esc_html_e( 'City', 'digicommerce' ); ?></label>
							<input type="text" id="wizard_business_city" name="business_city" value="<?php echo esc_attr( $options['business_city'] ?? '' ); ?>" class="regular-text">
						</div>

						<div class="form-fields">
							<label for="wizard_business_postal"><?php esc_html_e( 'Postal Code', 'digicommerce' ); ?></label>
							<input type="text" id="wizard_business_postal" name="business_postal" value="<?php echo esc_attr( $options['business_postal'] ?? '' ); ?>" class="regular-text">
						</div>
					</div>

					<div class="form-fields">
						<label for="wizard_email_from_address"><?php esc_html_e( 'Business Email', 'digicommerce' ); ?></label>
						<input type="email" id="wizard_email_from_address" name="email_from_address" value="<?php echo esc_attr( $options['email_from_address'] ?? get_bloginfo( 'admin_email' ) ); ?>" class="regular-text">
					</div>

					<div class="form-fields subscribe-checkbox">
						<label class="checkbox-label">
							<input type="checkbox" name="subscribe_newsletter" id="subscribe_newsletter">
							<span><?php esc_html_e( 'Get exclusive discounts and tips directly in your inbox - join our community of successful sellers!', 'digicommerce' ); ?></span>
						</label>
					</div>
				</div>

				<div class="digicommerce-setup-actions">
					<button type="submit" value="<?php esc_attr_e( 'Continue', 'digicommerce' ); ?>"><?php esc_html_e( 'Continue', 'digicommerce' ); ?></button>
				</div>
			</form>
		</div>
		<?php
	}

	/**
	 * Setup wizard addons
	 */
	public function setup_wizard_addons() {
		?>
		<div class="digicommerce-setup-content setup-addons hidden">
			<div class="setup-wizard-text">
				<h2><?php esc_html_e( 'Enhance Your Store with Pro version', 'digicommerce' ); ?></h2>
				<p><?php esc_html_e( 'Take your store to the next level with these powerful features.', 'digicommerce' ); ?></p>
			</div>
			
			<div class="addons-wrapper">
				<?php
				$blocs = array(
					'subscriptions'   => array(
						'title'       => esc_html__( 'Subscriptions', 'digicommerce' ),
						'description' => esc_html__( 'Create predictable recurring revenue with hassle-free automated billing.', 'digicommerce' ),
						'viewbox'     => '0 0 576 512',
						'path'        => 'M96 0l0 64L0 64l0 96 448 0 0-96-96 0 0-64L288 0l0 64L160 64l0-64L96 0zM448 192l-16 0L0 192 0 512l330.8 0C285.6 480.1 256 427.5 256 368c0-97.2 78.8-176 176-176c5.4 0 10.7 .2 16 .7l0-.7zM576 368a144 144 0 1 0 -288 0 144 144 0 1 0 288 0zM448 288l0 16 0 48 32 0 16 0 0 32-16 0-48 0-16 0 0-16 0-64 0-16 32 0z',
					),
					'booking'         => array(
						'title'       => esc_html__( 'Booking', 'digicommerce' ),
						'description' => esc_html__( 'Allow customers to easily book appointments with automated scheduling.', 'digicommerce' ),
						'viewbox'     => '0 0 640 512',
						'path'        => 'M96 96l0 48c0 8.8 7.4 15.7 15.7 18.6C130.5 169.1 144 187 144 208s-13.5 38.9-32.3 45.4C103.4 256.3 96 263.2 96 272l0 48c0 35.3 28.7 64 64 64l416 0c35.3 0 64-28.7 64-64l0-48c0-8.8-7.4-15.7-15.7-18.6C605.5 246.9 592 229 592 208s13.5-38.9 32.3-45.4c8.3-2.9 15.7-9.8 15.7-18.6l0-48c0-35.3-28.7-64-64-64L160 32c-35.3 0-64 28.7-64 64zm416 32l-288 0 0 160 288 0 0-160zM224 96l288 0c17.7 0 32 14.3 32 32l0 160c0 17.7-14.3 32-32 32l-288 0c-17.7 0-32-14.3-32-32l0-160c0-17.7 14.3-32 32-32zM48 120c0-13.3-10.7-24-24-24S0 106.7 0 120L0 360c0 66.3 53.7 120 120 120l400 0c13.3 0 24-10.7 24-24s-10.7-24-24-24l-400 0c-39.8 0-72-32.2-72-72l0-240z',
					),
					'license'         => array(
						'title'       => esc_html__( 'License', 'digicommerce' ),
						'description' => esc_html__( 'Create and manage digital product licenses automatically for secure customer access.', 'digicommerce' ),
						'viewbox'     => '0 0 512 512',
						'path'        => 'M352 0L128 0l0 134.7 23.6-9.7 22.5-9.2L189 135.1l15.6 20.2 25.3 3.4 24.1 3.3 3.3 24.1 3.4 25.3L280.9 227l19.2 14.9L291 264.4 281.3 288l9.7 23.6 9.2 22.5L280.9 349l-20.2 15.6-3.4 25.3L254 414l-24.1 3.3-5.9 .8 0 93.9s0 0 0 0l288 0 0-352-160 0L352 0zm32 0l0 128 128 0L384 0zM92.3 154.6l-3.7 4.8L68.6 185.2l-32.2 4.4-6 .8-.8 6-4.4 32.2L-.5 248.5l-4.8 3.7 2.3 5.6L9.3 288-3 318.1l-2.3 5.6 4.8 3.7 25.7 19.9 4.4 32.2 .8 6 6 .8L64 390.2 64 512l64-40 64 40 0-121.8 27.6-3.8 6-.8 .8-6 4.4-32.2 25.7-19.9 4.8-3.7-2.3-5.6L246.7 288 259 257.9l2.3-5.6-4.8-3.7-25.7-19.9-4.4-32.2-.8-6-6-.8-32.2-4.4-19.9-25.7-3.7-4.8-5.6 2.3L128 169.3 97.9 157l-5.6-2.3zM64 288a64 64 0 1 1 128 0A64 64 0 1 1 64 288z',
					),
					'abandonned-cart' => array(
						'title'       => esc_html__( 'Abandonned Cart', 'digicommerce' ),
						'description' => esc_html__( 'Automatically remind abandoned carts to complete their purchase.', 'digicommerce' ),
						'viewbox'     => '0 0 640 512',
						'path'        => 'M0 24C0 10.7 10.7 0 24 0L69.5 0c22 0 41.5 12.8 50.6 32l411 0c26.3 0 45.5 25 38.6 50.4L538.8 197.2c-13.7-3.4-28.1-5.2-42.8-5.2c-68.4 0-127.7 39-156.8 96l-168.5 0 5.4 28.5c2.2 11.3 12.1 19.5 23.6 19.5l123.2 0c-1.9 10.4-2.9 21.1-2.9 32c0 5.4 .2 10.7 .7 16l-121 0c-34.6 0-64.3-24.6-70.7-58.5L77.4 54.5c-.7-3.8-4-6.5-7.9-6.5L24 48C10.7 48 0 37.3 0 24zM128 464a48 48 0 1 1 96 0 48 48 0 1 1 -96 0zM496 224a144 144 0 1 1 0 288 144 144 0 1 1 0-288zm59.3 107.3c6.2-6.2 6.2-16.4 0-22.6s-16.4-6.2-22.6 0L496 345.4l-36.7-36.7c-6.2-6.2-16.4-6.2-22.6 0s-6.2 16.4 0 22.6L473.4 368l-36.7 36.7c-6.2 6.2-6.2 16.4 0 22.6s16.4 6.2 22.6 0L496 390.6l36.7 36.7c6.2 6.2 16.4 6.2 22.6 0s6.2-16.4 0-22.6L518.6 368l36.7-36.7z',
					),
					'side-cart'       => array(
						'title'       => esc_html__( 'Side Cart', 'digicommerce' ),
						'description' => esc_html__( 'Show customers their cart contents instantly without interrupting their shopping.', 'digicommerce' ),
						'viewbox'     => '0 0 640 512',
						'path'        => 'M243.1 2.7c11.8 6.1 16.3 20.6 10.2 32.4L171.7 192l232.6 0L322.7 35.1c-6.1-11.8-1.5-26.3 10.2-32.4s26.2-1.5 32.4 10.2L458.4 192l85.6 0c17.7 0 32 14.3 32 32s-14.3 32-32 32L492.1 463.5C485 492 459.4 512 430 512L146 512c-29.4 0-55-20-62.1-48.5L32 256c-17.7 0-32-14.3-32-32s14.3-32 32-32l85.6 0L210.7 12.9c6.1-11.8 20.6-16.3 32.4-10.2zM144 296a24 24 0 1 0 0-48 24 24 0 1 0 0 48zm312-24a24 24 0 1 0 -48 0 24 24 0 1 0 48 0z',
					),
					'coupon'          => array(
						'title'       => esc_html__( 'Coupon', 'digicommerce' ),
						'description' => esc_html__( 'Create targeted discounts to attract new customers and drive repeat sales.', 'digicommerce' ),
						'viewbox'     => '0 0 512 512',
						'path'        => 'M345 39.1L472.8 168.4c52.4 53 52.4 138.2 0 191.2L360.8 472.9c-9.3 9.4-24.5 9.5-33.9 .2s-9.5-24.5-.2-33.9L438.6 325.9c33.9-34.3 33.9-89.4 0-123.7L310.9 72.9c-9.3-9.4-9.2-24.6 .2-33.9s24.6-9.2 33.9 .2zM0 229.5L0 80C0 53.5 21.5 32 48 32l149.5 0c17 0 33.3 6.7 45.3 18.7l168 168c25 25 25 65.5 0 90.5L277.3 442.7c-25 25-65.5 25-90.5 0l-168-168C6.7 262.7 0 246.5 0 229.5zM144 144a32 32 0 1 0 -64 0 32 32 0 1 0 64 0z',
					),
				);

				foreach ( $blocs as $id => $bloc ) :
					?>
					<div class="addon-box">
						<div class="box-icon">
							<svg xmlns="http://www.w3.org/2000/svg" viewBox="<?php echo esc_attr( $bloc['viewbox'] ); ?>" class="fill-dark-blue w-10"><path d="<?php echo esc_attr( $bloc['path'] ); ?>"/></svg>
						</div>
						<div class="box-content">
							<h3><?php echo esc_attr( $bloc['title'] ); ?></h3>
							<p><?php echo esc_attr( $bloc['description'] ); ?></p>
						</div>
					</div>
					<?php
				endforeach;
				?>
			</div>

			<div class="addons-footer">
				<p><?php esc_html_e( 'Get all these features and more with DigiCommerce Pro !', 'digicommerce' ); ?></p>
				<p class="digicommerce-setup-actions">
					<a href="https://digicommerce.me/pricing" target="_blank" class="gold"><?php esc_html_e( 'Get Pro Version !', 'digicommerce' ); ?></a>
					<a href="#" class="continue normal"><?php esc_html_e( 'No thanks', 'digicommerce' ); ?></a>
				</p>
			</div>
		</div>
		<?php
	}

	/**
	 * Setup wizard ready
	 */
	public function setup_wizard_ready() {
		?>
		<div class="digicommerce-setup-content setup-ready hidden">
			<img src="<?php echo esc_url( DIGICOMMERCE_PLUGIN_URL . 'assets/img/wizard-ready.svg' ); // phpcs:ignore ?>" alt="DigiCommerce Setup Wizard" class="wizard-ready">

			<div class="setup-wizard-content">
				<div class="setup-wizard-text">
					<h2><?php esc_html_e( 'Thanks to be part of the family !', 'digicommerce' ); ?></h2>
					<p><?php esc_html_e( 'Your store is now configured and ready to go. Here are some helpful resources to get you started.', 'digicommerce' ); ?></p>
				</div>

				<div class="digicommerce-setup-next-steps">
					<a class="outline-gold" href="<?php echo esc_url( admin_url( 'post-new.php?post_type=digi_product' ) ); ?>">
						<?php esc_html_e( 'Create Your First Product', 'digicommerce' ); ?>
					</a>
					<a class="outline-dark" href="https://docs.digicommerce.me/" target="_blank">
						<?php esc_html_e( 'Read The Documentation', 'digicommerce' ); ?>
					</a>
				</div>
				
				<p class="digicommerce-setup-actions">
					<a href="<?php echo esc_url( admin_url() ); ?>" class="dashboard-link">
						<svg xmlns="http://www.w3.org/2000/svg" width="14" height="15" fill="currentColor"><path fill-rule="evenodd" clip-rule="evenodd" d="M4 12a.7.7 0 001.1-1L2.8 8.3h9.4a.7.7 0 100-1.4H2.8l2.3-2.7a.7.7 0 00-1-1l-3.3 4-.1.1c-.1.3 0 .6.2.8L4 12z"/></svg>
						<?php esc_html_e( 'Return to Dashboard', 'digicommerce' ); ?>
					</a>
				</p>
			</div>
		</div>
		<?php
	}

	/**
	 * Save setup wizard step
	 */
	public function ajax_save_step() {
		try {
			// Verify nonce.
			check_ajax_referer( 'digicommerce_setup_wizard', 'nonce' );

			if ( ! current_user_can( 'manage_options' ) ) {
				wp_send_json_error( 'Permission denied' );
				return;
			}

			$fields = array(
				'business_name',
				'business_vat_number',
				'business_country',
				'business_address',
				'business_address2',
				'business_city',
				'business_postal',
				'email_from_address',
				'currency',
				'currency_position',
			);

			foreach ( $fields as $field ) {
				if ( isset( $_POST[ $field ] ) ) {
					try {
						$value = sanitize_text_field( $_POST[ $field ] ); // phpcs:ignore
						DigiCommerce()->set_option( $field, $value );
					} catch ( Exception $e ) {
						wp_send_json_error( "Failed to save field: {$field}" );
						return;
					}
				}
			}

			// Handle newsletter subscription.
			if ( isset( $_POST['subscribe_newsletter'] ) && 'true' === $_POST['subscribe_newsletter'] && isset( $_POST['email_from_address'] ) ) {
				$email = sanitize_email( wp_unslash( $_POST['email_from_address'] ) );
				$this->subscribe_to_mailchimp( $email );
			}

			// Set the completion flag when form is saved successfully.
			DigiCommerce()->set_flag( 'digicommerce_setup_wizard_completed', true );

			wp_send_json_success();

		} catch ( Exception $e ) {
			wp_send_json_error( $e->getMessage() );
		}
	}

	/**
	 * Subscribe to Mailchimp
	 *
	 * @param string $email Email address.
	 */
	private function subscribe_to_mailchimp( $email ) {
		$api_key = '0f639c902c7902a4e75b804ec4f663ad-us8';
		$list_id = '1f7310e085';
		$tags    = 'Wizard';

		$dc  = substr( $api_key, strpos( $api_key, '-' ) + 1 );
		$url = "https://{$dc}.api.mailchimp.com/3.0/lists/{$list_id}/members";

		$data = array(
			'email_address' => $email,
			'status'        => 'subscribed',
			'tags'          => array_map( 'trim', explode( ',', $tags ) ),
		);

		$response = wp_remote_post(
			$url,
			array(
				'headers' => array(
					'Authorization' => 'Basic ' . base64_encode( 'user:' . $api_key ), // phpcs:ignore
					'Content-Type'  => 'application/json',
				),
				'body'    => json_encode( $data ), // phpcs:ignore
			)
		);

		if ( is_wp_error( $response ) ) {
			return;
		}

		$response_code = wp_remote_retrieve_response_code( $response );
		$response_body = wp_remote_retrieve_body( $response );
	}

	/**
	 * Skip setup wizard
	 */
	public function ajax_skip_setup() {
		// Verify nonce.
		check_ajax_referer( 'digicommerce_setup_wizard', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Permission denied' );
		}

		DigiCommerce()->set_flag( 'digicommerce_setup_wizard_completed', true );

		wp_send_json_success(
			array(
				'redirect' => admin_url(),
			)
		);
	}
}

// Initialize the setup wizard.
DigiCommerce_Setup_Wizard::instance();