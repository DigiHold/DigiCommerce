<?php
defined( 'ABSPATH' ) || exit;

/**
 * DigiCommerce review notice
 */
class DigiCommerce_Review_Notice {
	/**
	 * Instance of this class.
	 *
	 * @var null
	 */
	private static $instance = null;

	/**
	 * Constructor
	 */
	private function __construct() {
		// Add review notice
		add_action( 'admin_notices', array( $this, 'maybe_show_review_notice' ) );

		// Handle AJAX dismiss for review notice
		add_action( 'wp_ajax_digicommerce_dismiss_review_notice', array( $this, 'ajax_dismiss_review_notice' ) );

		// Enqueue admin scripts
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );

		// Set activation timestamp on plugin activation if not already set
		if ( ! get_option( 'digicommerce_activation_timestamp' ) ) {
			update_option( 'digicommerce_activation_timestamp', time() );
		}
	}

	/**
	 * Get the instance of this class.
	 */
	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Check if review notice should be shown
	 */
	public function maybe_show_review_notice() {
		// Get current screen
		$screen = get_current_screen();

		// Don't show on edit pages
		if ( $screen && 0 === strpos( $screen->base, 'edit' ) ) {
			return;
		}

		// Don't show notice if user has dismissed it
		if ( get_option( 'digicommerce_review_notice_dismissed' ) ) {
			return;
		}

		// Don't show if temporarily dismissed
		if ( get_transient( 'digicommerce_review_notice_dismissed_temporarily' ) ) {
			return;
		}

		// Get activation timestamp
		$activation_timestamp = get_option( 'digicommerce_activation_timestamp' );

		// If no timestamp, set it now
		if ( empty( $activation_timestamp ) ) {
			update_option( 'digicommerce_activation_timestamp', time() );
			return;
		}

		// Check if plugin has been active for at least 7 days
		$one_week = 7 * DAY_IN_SECONDS;
		if ( ( time() - $activation_timestamp ) < $one_week ) {
			return;
		}

		// Show the notice
		?>
		<div class="notice digicommerce-review-notice">
			<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" width="80" height="80"><circle cx="256" cy="256" r="256" fill="#cdb162"/><path d="M511.7098,269.4235c-6.2549,121.0709-96.6293,219.8827-213.8016,239.1531l-119.1872-119.1872c-.5684-.492-1.1188-1.0043-1.6501-1.536-5.6725-5.6693-9.1776-13.4976-9.1776-22.1451-.0132-8.9802,3.841-17.5312,10.5781-23.4688l-12.1941-12.1941c-.5045-.4373-.9941-.8939-1.4667-1.3664-5.0325-5.0325-8.1557-11.9819-8.1557-19.6352,0-2.4.3083-4.7307.8853-6.9547l-47.9019-47.9019c-3.0657-2.527-3.5024-7.0607-.9754-10.1264,1.3662-1.6574,3.4014-2.6176,5.5493-2.6181h56.5664l-66.1259-66.1259c-2.8089-2.8114-2.8068-7.3674.0045-10.1763,1.3474-1.3462,3.1735-2.1033,5.0781-2.1053h41.52l-35.7771-35.7771c-1.3515-1.3475-2.1098-3.1784-2.1067-5.0869-.0012-3.9735,3.219-7.1956,7.1925-7.1968h30.1248c1.9616,0,3.7397.784,5.0379,2.0555l.1067.1067,45.8976,45.8976h200.6613c2.1495-.0044,4.1871.9574,5.5499,2.6197l103.7674,103.7696Z" fill="#ab8b2b" fill-rule="evenodd"/><path d="M361.4858,348.7728c4.6805,0,8.9099,1.8997,11.9904,4.96,3.1729,3.177,4.952,7.4854,4.9451,11.9755,0,4.672-1.8912,8.9099-4.9451,11.9701-3.1801,3.1788-7.494,4.9621-11.9904,4.9568-4.4924.0071-8.8023-1.7768-11.9755-4.9568-3.1781-3.1723-4.9618-7.4797-4.9568-11.9701,0-4.6805,1.8965-8.9099,4.9568-11.9755,3.1739-3.1794,7.483-4.9641,11.9755-4.96h0ZM199.2159,348.7728c4.6795,0,8.9152,1.8997,11.9755,4.96,3.1815,3.1724,4.9663,7.4826,4.9589,11.9755,0,4.672-1.8933,8.9099-4.9589,11.9701-3.1722,3.1815-7.4827,4.9657-11.9755,4.9568-4.491.0081-8.7996-1.7761-11.9701-4.9568-3.1808-3.1707-4.9656-7.479-4.9589-11.9701,0-4.6805,1.8933-8.9099,4.9589-11.9755,3.1712-3.1801,7.4791-4.9652,11.9701-4.96h0ZM145.0057,129.3637l8.0203,33.6693h-43.2928c-3.9738,0-7.1952,3.2214-7.1952,7.1952s3.2214,7.1952,7.1952,7.1952h100.7712c3.9729,0,7.1936,3.2207,7.1936,7.1936s-3.2207,7.1936-7.1936,7.1936h-50.6219l2.4341,10.2304h-9.0208c-3.9738,0-7.1952,3.2214-7.1952,7.1952s3.2214,7.1952,7.1952,7.1952h64.6784c3.9738.0484,7.1559,3.3091,7.1075,7.2829-.0476,3.9055-3.202,7.0599-7.1075,7.1075h-48.8075l2.528,10.6197h-57.4848c-3.9712,0-7.1904,3.2203-7.1904,7.1936s3.2203,7.1936,7.1904,7.1936h113.7248c3.9738.0481,7.1562,3.3084,7.1082,7.2822-.0472,3.906-3.2022,7.0609-7.1082,7.1082h-49.3802l2.6699,11.2192c-6.3669.7413-12.0949,3.6533-16.4149,7.9669-5.0325,5.0379-8.1557,11.9872-8.1557,19.6373s3.1243,14.6027,8.1557,19.6352c5.0379,5.0411,11.9872,8.1621,19.6437,8.1621h2.5835c-3.7221,1.5774-7.1056,3.8568-9.9659,6.7136-5.8861,5.8685-9.1892,13.8418-9.1776,22.1536,0,8.6475,3.5051,16.4757,9.1776,22.1451,5.6693,5.6693,13.5029,9.1744,22.1451,9.1744,8.6475,0,16.4843-3.5051,22.1536-9.1744,5.6693-5.6693,9.1744-13.4976,9.1744-22.1451.0113-8.3111-3.2904-16.2839-9.1744-22.1536-2.8615-2.8568-6.2461-5.1361-9.9691-6.7136h137.8997c-3.7203,1.5773-7.1018,3.8567-9.9595,6.7136-5.6693,5.6693-9.1776,13.5029-9.1776,22.1536s3.5083,16.4757,9.1776,22.1451c5.6693,5.6693,13.4965,9.1744,22.1451,9.1744s16.4693-3.5051,22.1419-9.1744c5.6725-5.6693,9.1915-13.4976,9.1915-22.1451s-3.52-16.4843-9.1915-22.1536c-2.8512-2.8593-6.2294-5.1392-9.9477-6.7136h10.2677c3.9563,0,7.1851-3.2203,7.1851-7.1968s-3.2288-7.1968-7.1851-7.1968h-199.4944c-3.68,0-7.0304-1.5093-9.4688-3.9381-2.4288-2.4352-3.9445-5.7803-3.9445-9.4656,0-3.68,1.5157-7.0251,3.9445-9.4592,2.4373-2.4288,5.7888-3.9445,9.4688-3.9445h175.072c5.8261,0,11.2224-1.9488,15.5211-5.3291,4.2763-3.3653,7.4464-8.1472,8.8427-13.8368l25.3365-103.9563c.2353-.739.353-1.5104.3488-2.2859,0-3.9733-3.2-7.1968-7.1851-7.1968h-234.5749l-10.0736-42.2912c-.6792-3.3563-3.6295-5.7691-7.0539-5.7685h-30.1205c-3.9735-.0012-7.1956,3.219-7.1968,7.1925v.0043c0,3.9729,3.2207,7.1936,7.1936,7.1936h24.4427v-.0011Z" fill="#fff" fill-rule="evenodd"/></svg>

			<div class="digicommerce-review-content">
				<p class="digicommerce-review-text">
					<?php
					printf(
						/* translators: %1$s: Plugin review link */
						esc_html__( 'Do you like %2$sDigiCommerce%3$s? Please consider leaving a 5-star review %4$s&#9733;&#9733;&#9733;&#9733;&#9733;%5$s on %6$sWordPress.org%7$s to help us spread the word!', 'digicommerce' ),
						'https://wordpress.org/support/plugin/digicommerce/reviews/#new-post',
						'<strong>',
						'</strong>',
						'<a href="https://wordpress.org/support/plugin/digicommerce/reviews/#new-post" target="_blank" rel="noopener noreferrer">',
						'</a>',
						'<a href="https://wordpress.org/support/plugin/digicommerce/reviews/#new-post" target="_blank" rel="noopener noreferrer">',
						'</a>'
					);
					?>
				</p>

				<p class="digicommerce-review-buttons">
					<a href="https://wordpress.org/support/plugin/digicommerce/reviews/#new-post" class="button digicommerce-leave-review" target="_blank">
						<?php esc_html_e( 'Leave a Review', 'digicommerce' ); ?>
					</a>
					<a href="#" class="button digicommerce-dismiss-review-notice" data-permanent="true">
						<?php esc_html_e( 'Already Done', 'digicommerce' ); ?>
					</a>
					<a href="#" class="button digicommerce-dismiss-review-notice digicommerce-later-review">
						<?php esc_html_e( 'Maybe Later', 'digicommerce' ); ?>
					</a>
				</p>
			</div>
		</div>
		<?php
	}

	/**
	 * Enqueue script for the notice dismiss functionality
	 */
	public function enqueue_admin_scripts() {
		// Enqueue CSS file for review notice
		wp_enqueue_style(
			'digicommerce-review-notice-css',
			DIGICOMMERCE_PLUGIN_URL . 'assets/css/admin/review-notice.css',
			array(),
			DIGICOMMERCE_VERSION
		);

		// Enqueue JS file for review notice
		wp_enqueue_script(
			'digicommerce-review-notice-js',
			DIGICOMMERCE_PLUGIN_URL . 'assets/js/admin/review-notice.js',
			array(),
			DIGICOMMERCE_VERSION,
			true
		);

		wp_localize_script(
			'digicommerce-review-notice-js',
			'digicommerceReviewVars',
			array(
				'ajaxurl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'digicommerce_dismiss_review' ),
			)
		);
	}

	/**
	 * Handle AJAX dismiss review notice
	 */
	public function ajax_dismiss_review_notice() {
		// Verify nonce
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'digicommerce_dismiss_review' ) ) {
			wp_send_json_error( 'Invalid nonce' );
		}

		$permanent = isset( $_POST['permanent'] ) && 'true' === $_POST['permanent'];

		if ( $permanent ) {
			update_option( 'digicommerce_review_notice_dismissed', true );
		} else {
			// Remind again after 30 days
			set_transient( 'digicommerce_review_notice_dismissed_temporarily', true, 30 * DAY_IN_SECONDS );
		}

		wp_send_json_success();
	}
}

// Initialize blocks.
DigiCommerce_Review_Notice::instance();