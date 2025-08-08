<?php
/**
 * DigiCommerce Products Sorting Block
 *
 * @package DigiCommerce
 */

defined( 'ABSPATH' ) || exit;

/**
 * Products Sorting Block Class
 */
class DigiCommerce_Products_Sorting_Block {

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
			DIGICOMMERCE_PLUGIN_DIR . 'assets/blocks/products-sorting',
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
		// Get attributes with defaults
		$show_label = isset( $attributes['showLabel'] ) ? $attributes['showLabel'] : true;
		$label_text = isset( $attributes['labelText'] ) ? $attributes['labelText'] : __( 'Sort by:', 'digicommerce' );

		// Get current sort value from URL
		$current_sort = isset( $_GET['orderby'] ) ? sanitize_text_field( $_GET['orderby'] ) : 'date';

		// Define sorting options
		$sort_options = array(
			'date'       => __( 'Latest', 'digicommerce' ),
			'date-asc'   => __( 'Oldest', 'digicommerce' ),
			'title'      => __( 'Name (A-Z)', 'digicommerce' ),
			'title-desc' => __( 'Name (Z-A)', 'digicommerce' ),
			'price'      => __( 'Price (Low to High)', 'digicommerce' ),
			'price-desc' => __( 'Price (High to Low)', 'digicommerce' ),
		);

		// Get wrapper attributes
		$wrapper_attributes = get_block_wrapper_attributes();

		// Get base URL without pagination
		$base_url = get_permalink();
		if ( is_post_type_archive( 'digi_product' ) ) {
			$base_url = get_post_type_archive_link( 'digi_product' );
		} elseif ( is_tax() ) {
			$base_url = get_term_link( get_queried_object() );
		}

		// Start output buffering
		ob_start();
		?>
		<div <?php echo $wrapper_attributes; ?>>
			<form class="digicommerce-products-sorting__form" method="get" action="<?php echo esc_url( $base_url ); ?>">
				<?php
				// Preserve other query parameters
				foreach ( $_GET as $key => $value ) {
					if ( 'orderby' !== $key && ! is_array( $value ) ) {
						echo '<input type="hidden" name="' . esc_attr( $key ) . '" value="' . esc_attr( $value ) . '">';
					}
				}
				?>
				
				<?php if ( $show_label ) : ?>
					<label for="digicommerce-orderby" class="digicommerce-products-sorting__label">
						<?php echo esc_html( $label_text ); ?>
					</label>
				<?php endif; ?>
				
				<select 
					name="orderby" 
					id="digicommerce-orderby" 
					class="digicommerce-products-sorting__select"
					aria-label="<?php esc_attr_e( 'Sort products', 'digicommerce' ); ?>"
				>
					<?php foreach ( $sort_options as $value => $label ) : ?>
						<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $current_sort, $value ); ?>>
							<?php echo esc_html( $label ); ?>
						</option>
					<?php endforeach; ?>
				</select>
			</form>
		</div>
		<?php

		return ob_get_clean();
	}
}

// Initialize the block
DigiCommerce_Products_Sorting_Block::init();