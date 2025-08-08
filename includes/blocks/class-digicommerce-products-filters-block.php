<?php
/**
 * DigiCommerce Products Filters Block
 *
 * @package DigiCommerce
 */

defined( 'ABSPATH' ) || exit;

/**
 * Products Filters Block Class
 */
class DigiCommerce_Products_Filters_Block {

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
			DIGICOMMERCE_PLUGIN_DIR . 'assets/blocks/products-filters',
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
		$show_categories = isset( $attributes['showCategories'] ) ? $attributes['showCategories'] : true;
		$show_tags = isset( $attributes['showTags'] ) ? $attributes['showTags'] : true;
		$show_count = isset( $attributes['showCount'] ) ? $attributes['showCount'] : true;
		$categories_title = isset( $attributes['categoriesTitle'] ) ? $attributes['categoriesTitle'] : __( 'Categories', 'digicommerce' );
		$tags_title = isset( $attributes['tagsTitle'] ) ? $attributes['tagsTitle'] : __( 'Tags', 'digicommerce' );
		$filter_style = isset( $attributes['filterStyle'] ) ? $attributes['filterStyle'] : 'checkboxes';

		// Get current filters from URL
		if ( 'dropdown' === $filter_style ) {
			// For dropdown, expect single value
			$selected_categories = isset( $_GET['product_cat'] ) ? array( intval( $_GET['product_cat'] ) ) : array();
			$selected_tags = isset( $_GET['product_tag'] ) ? array( intval( $_GET['product_tag'] ) ) : array();
		} else {
			// For checkboxes, expect array
			$selected_categories = isset( $_GET['product_cat'] ) ? array_map( 'intval', (array) $_GET['product_cat'] ) : array();
			$selected_tags = isset( $_GET['product_tag'] ) ? array_map( 'intval', (array) $_GET['product_tag'] ) : array();
		}

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
			<form class="digicommerce-products-filters__form" method="get" action="<?php echo esc_url( $base_url ); ?>">
				<?php
				// Preserve other query parameters
				foreach ( $_GET as $key => $value ) {
					if ( ! in_array( $key, array( 'product_cat', 'product_tag' ), true ) && ! is_array( $value ) ) {
						echo '<input type="hidden" name="' . esc_attr( $key ) . '" value="' . esc_attr( $value ) . '">';
					}
				}
				?>

				<?php if ( $show_categories ) : ?>
					<div class="digicommerce-products-filters__section">
						<h3 class="digicommerce-products-filters__title">
							<?php echo esc_html( $categories_title ); ?>
						</h3>
						<?php 
						if ( 'checkboxes' === $filter_style ) {
							self::render_checkbox_filters( 'digi_product_cat', 'product_cat', $selected_categories, $show_count );
						} else {
							self::render_dropdown_filter( 'digi_product_cat', 'product_cat', $selected_categories, $show_count, __( 'All Categories', 'digicommerce' ) );
						}
						?>
					</div>
				<?php endif; ?>

				<?php if ( $show_tags ) : ?>
					<div class="digicommerce-products-filters__section">
						<h3 class="digicommerce-products-filters__title">
							<?php echo esc_html( $tags_title ); ?>
						</h3>
						<?php 
						if ( 'checkboxes' === $filter_style ) {
							self::render_checkbox_filters( 'digi_product_tag', 'product_tag', $selected_tags, $show_count );
						} else {
							self::render_dropdown_filter( 'digi_product_tag', 'product_tag', $selected_tags, $show_count, __( 'All Tags', 'digicommerce' ) );
						}
						?>
					</div>
				<?php endif; ?>

				<div class="digicommerce-products-filters__actions">
					<button type="submit" class="digicommerce-products-filters__submit wp-element-button">
						<?php esc_html_e( 'Apply Filters', 'digicommerce' ); ?>
					</button>
					
					<?php if ( ! empty( $selected_categories ) || ! empty( $selected_tags ) ) : ?>
						<a href="<?php echo esc_url( remove_query_arg( array( 'product_cat', 'product_tag' ) ) ); ?>" class="digicommerce-products-filters__clear wp-element-button">
							<?php esc_html_e( 'Clear Filters', 'digicommerce' ); ?>
						</a>
					<?php endif; ?>
				</div>
			</form>
		</div>
		<?php

		return ob_get_clean();
	}

	/**
	 * Render checkbox filters
	 *
	 * @param string $taxonomy Taxonomy name.
	 * @param string $field_name Field name for form submission.
	 * @param array  $selected Selected term IDs.
	 * @param bool   $show_count Show post count.
	 */
	private static function render_checkbox_filters( $taxonomy, $field_name, $selected, $show_count ) {
		$terms = get_terms( array(
			'taxonomy'   => $taxonomy,
			'hide_empty' => true,
		) );

		if ( ! empty( $terms ) && ! is_wp_error( $terms ) ) {
			echo '<div class="digicommerce-products-filters__checkboxes">';
			foreach ( $terms as $term ) {
				$checked = in_array( $term->term_id, $selected, true ) ? 'checked' : '';
				?>
				<label class="digicommerce-products-filters__checkbox-label">
					<input 
						type="checkbox" 
						name="<?php echo esc_attr( $field_name ); ?>[]" 
						value="<?php echo esc_attr( $term->term_id ); ?>"
						<?php echo $checked; ?>
						class="digicommerce-products-filters__checkbox"
					>
					<span class="digicommerce-products-filters__checkbox-text">
						<?php echo esc_html( $term->name ); ?>
						<?php if ( $show_count ) : ?>
							<span class="digicommerce-products-filters__count">(<?php echo esc_html( $term->count ); ?>)</span>
						<?php endif; ?>
					</span>
				</label>
				<?php
			}
			echo '</div>';
		}
	}

	/**
	 * Render dropdown filter
	 *
	 * @param string $taxonomy Taxonomy name.
	 * @param string $field_name Field name for form submission.
	 * @param array  $selected Selected term IDs.
	 * @param bool   $show_count Show post count.
	 * @param string $placeholder Placeholder text.
	 */
	private static function render_dropdown_filter( $taxonomy, $field_name, $selected, $show_count, $placeholder ) {
		$terms = get_terms( array(
			'taxonomy'   => $taxonomy,
			'hide_empty' => true,
		) );
	
		if ( ! empty( $terms ) && ! is_wp_error( $terms ) ) {
			$current_value = ! empty( $selected ) ? $selected[0] : '';
			?>
			<select 
				name="<?php echo esc_attr( $field_name ); ?>" 
				class="digicommerce-products-filters__select"
			>
				<option value=""><?php echo esc_html( $placeholder ); ?></option>
				<?php foreach ( $terms as $term ) : ?>
					<option 
						value="<?php echo esc_attr( $term->term_id ); ?>"
						<?php selected( $current_value, $term->term_id ); ?>
					>
						<?php 
						echo esc_html( $term->name );
						if ( $show_count ) {
							echo ' (' . esc_html( $term->count ) . ')';
						}
						?>
					</option>
				<?php endforeach; ?>
			</select>
			<?php
		}
	}
}

// Initialize the block
DigiCommerce_Products_Filters_Block::init();