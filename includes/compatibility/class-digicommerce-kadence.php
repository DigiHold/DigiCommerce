<?php
/**
 * DigiCommerce Kadence Theme Compatibility
 *
 * @package       DigiCommerce/Compatibility
 * @version       1.0.0
 */

 defined('ABSPATH') || exit;

/**
 * Theme compatibility class
 */
class DigiCommerce_Kadence {
	private static $instance = null;

	/**
     * Instance.
     */
	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
     * Constructor.
     */
	private function __construct() {
		add_action( 'digicommerce_before_wrapper', array( $this, 'before_wrapper' ) );
		add_action( 'digicommerce_after_wrapper', array( $this, 'after_wrapper' ) );
	}

	/**
     * Add container start.
     */
	public static function before_wrapper() {
		echo '<div class="site-container">';
	}

	/**
     * Add container end.
     */
	public static function after_wrapper() {
		echo '</div>';
	}
}

DigiCommerce_Kadence::instance();