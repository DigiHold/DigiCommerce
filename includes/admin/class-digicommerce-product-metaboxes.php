<?php
/**
 * DigiCommerce Product Metaboxes for Classic Editor - Core Version
 *
 * @package DigiCommerce
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * DigiCommerce_Product_Metaboxes class
 *
 * Handles metaboxes for classic editor with hooks for Pro extensions
 */
class DigiCommerce_Product_Metaboxes {

	/**
	 * Instance of this class.
	 *
	 * @var object
	 */
	private static $instance = null;

	/**
	 * Get instance of the class
	 *
	 * @return DigiCommerce_Product_Metaboxes
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor
	 */
	public function __construct() {
		add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );
		add_action( 'save_post', array( $this, 'save_meta_boxes' ), 10, 2 );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
	}

	/**
	 * Add meta boxes for product settings
	 *
	 * @param string $post_type Current post type.
	 */
	public function add_meta_boxes( $post_type ) {
		if ( 'digi_product' !== $post_type ) {
			return;
		}

		add_meta_box(
			'digicommerce_pricing',
			__( 'Pricing', 'digicommerce' ),
			array( $this, 'render_pricing_metabox' ),
			'digi_product',
			'normal',
			'high'
		);

		add_meta_box(
			'digicommerce_files',
			__( 'Downloadable Files', 'digicommerce' ),
			array( $this, 'render_files_metabox' ),
			'digi_product',
			'normal',
			'default'
		);

		add_meta_box(
			'digicommerce_gallery',
			__( 'Gallery', 'digicommerce' ),
			array( $this, 'render_gallery_metabox' ),
			'digi_product',
			'side',
			'default'
		);

		add_meta_box(
			'digicommerce_product_data',
			__( 'Product Data', 'digicommerce' ),
			array( $this, 'render_product_data_metabox' ),
			'digi_product',
			'normal',
			'default'
		);

		add_meta_box(
			'digicommerce_bundle',
			__( 'Bundle Products', 'digicommerce' ),
			array( $this, 'render_bundle_metabox' ),
			'digi_product',
			'normal',
			'default'
		);
	
		// Pro features - only add if Pro is active and license enabled
		if ( class_exists( 'DigiCommerce_Pro' ) &&
			 class_exists( 'DigiCommerce_Pro_License' ) &&
			 DigiCommerce()->get_option( 'enable_license', false ) ) {
			
			add_meta_box(
				'digicommerce_upgrade_paths',
				__( 'Upgrade Paths', 'digicommerce' ),
				array( $this, 'render_upgrade_paths_metabox' ),
				'digi_product',
				'normal',
				'default'
			);
	
			add_meta_box(
				'digicommerce_api_data',
				__( 'API Data', 'digicommerce' ),
				array( $this, 'render_api_data_metabox' ),
				'digi_product',
				'normal',
				'default'
			);
		}
	}

	/**
	 * Render pricing metabox with hooks for Pro features
	 *
	 * @param WP_Post $post Current post object.
	 */
	public function render_pricing_metabox( $post ) {
		wp_nonce_field( 'digicommerce_product_metabox', 'digicommerce_product_nonce' );

		$price_mode = get_post_meta( $post->ID, 'digi_price_mode', true ) ?: 'single';
		$price = get_post_meta( $post->ID, 'digi_price', true ) ?: 0;
		$sale_price = get_post_meta( $post->ID, 'digi_sale_price', true ) ?: '';
		$price_variations = get_post_meta( $post->ID, 'digi_price_variations', true ) ?: array();

		?>
		<div class="digicommerce-pricing-wrap">
			<p>
				<label>
					<input type="radio" name="digi_price_mode" value="single" <?php checked( $price_mode, 'single' ); ?> />
					<?php esc_html_e( 'Single Price', 'digicommerce' ); ?>
				</label>
				&nbsp;&nbsp;
				<label>
					<input type="radio" name="digi_price_mode" value="variations" <?php checked( $price_mode, 'variations' ); ?> />
					<?php esc_html_e( 'Price Variations', 'digicommerce' ); ?>
				</label>
			</p>

			<div class="pricing-single" style="<?php echo 'variations' === $price_mode ? 'display:none;' : ''; ?>">
				<div class="pricing-basic-fields">
					<p>
						<label for="digi_price"><?php esc_html_e( 'Regular Price', 'digicommerce' ); ?></label>
						<input type="number" id="digi_price" name="digi_price" value="<?php echo esc_attr( $price ); ?>" step="0.01" min="0" />
					</p>
					<p>
						<label for="digi_sale_price"><?php esc_html_e( 'Sale Price', 'digicommerce' ); ?></label>
						<input type="number" id="digi_sale_price" name="digi_sale_price" value="<?php echo esc_attr( $sale_price ); ?>" step="0.01" min="0" />
					</p>
					<p>
						<label><?php esc_html_e( 'Direct Purchase URL', 'digicommerce' ); ?></label>
						<div class="digi-url-field-wrapper">
							<input type="text" class="digi-direct-url" readonly style="cursor: pointer; width: 100%;" />
							<div class="digi-url-tooltip" style="display: none;">
								<?php esc_html_e( 'Click to copy', 'digicommerce' ); ?>
							</div>
						</div>
					</p>
				</div>

				<?php
				/**
				 * Hook for Pro features to add single price settings
				 *
				 * @param WP_Post $post Current post object.
				 */
				do_action( 'digicommerce_single_price_settings', $post );
				?>
			</div>

			<div class="pricing-variations" style="<?php echo 'single' === $price_mode ? 'display:none;' : ''; ?>">
				<div class="variations-list">
					<?php $this->render_price_variations( $price_variations ); ?>
				</div>
				<p>
					<button type="button" class="button add-variation"><?php esc_html_e( 'Add Variation', 'digicommerce' ); ?></button>
				</p>
			</div>
		</div>

		<script type="text/template" id="variation-template">
			<?php $this->render_variation_template(); ?>
		</script>
		<?php
	}

	/**
	 * Render price variations
	 *
	 * @param array $variations Price variations.
	 */
	private function render_price_variations( $variations ) {
		if ( empty( $variations ) ) {
			return;
		}

		foreach ( $variations as $index => $variation ) {
			$this->render_single_variation( $variation, $index );
		}
	}

	/**
	 * Render single variation with file support
	 *
	 * @param array $variation Variation data.
	 * @param int   $index     Variation index.
	 */
	private function render_single_variation( $variation, $index ) {
		?>
		<div class="variation-item" data-index="<?php echo esc_attr( $index ); ?>">
			<h4><?php esc_html_e( 'Variation', 'digicommerce' ); ?> #<?php echo esc_html( $index + 1 ); ?></h4>
			
			<div class="variation-basic-fields">
				<p>
					<label><?php esc_html_e( 'Name', 'digicommerce' ); ?></label>
					<input type="text" name="variations[<?php echo esc_attr( $index ); ?>][name]" value="<?php echo esc_attr( $variation['name'] ?? '' ); ?>" />
				</p>
				<p>
					<label><?php esc_html_e( 'Price', 'digicommerce' ); ?></label>
					<input type="number" name="variations[<?php echo esc_attr( $index ); ?>][price]" value="<?php echo esc_attr( $variation['price'] ?? '' ); ?>" step="0.01" min="0" />
				</p>
				<p>
					<label><?php esc_html_e( 'Sale Price', 'digicommerce' ); ?></label>
					<input type="number" name="variations[<?php echo esc_attr( $index ); ?>][salePrice]" value="<?php echo esc_attr( $variation['salePrice'] ?? '' ); ?>" step="0.01" min="0" />
				</p>
				<p>
					<label><?php esc_html_e( 'Direct Purchase URL', 'digicommerce' ); ?></label>
					<div class="digi-url-field-wrapper">
						<input type="text" class="digi-direct-url-variation" data-variation-index="<?php echo esc_attr( $index ); ?>" readonly style="cursor: pointer; width: 100%;" />
						<div class="digi-url-tooltip" style="display: none;">
							<?php esc_html_e( 'Click to copy', 'digicommerce' ); ?>
						</div>
					</div>
				</p>
				<p>
					<label>
						<input type="checkbox" name="variations[<?php echo esc_attr( $index ); ?>][isDefault]" value="1" <?php checked( ! empty( $variation['isDefault'] ) ); ?> />
						<?php esc_html_e( 'Default Selection', 'digicommerce' ); ?>
					</label>
				</p>
			</div>

			<!-- Variation Files Section -->
			<div class="variation-files-section">
				<h5><?php esc_html_e( 'Download Files', 'digicommerce' ); ?></h5>
				<div class="variation-files-container">
					<?php $this->render_variation_files( $variation['files'] ?? array(), $index ); ?>
				</div>
				<button type="button" class="button add-variation-file-btn" data-variation-index="<?php echo esc_attr( $index ); ?>">
					<?php esc_html_e( 'Add Download File', 'digicommerce' ); ?>
				</button>
			</div>

			<?php
			/**
			 * Hook for Pro features to add variation settings
			 *
			 * @param array $variation Variation data.
			 * @param int   $index     Variation index.
			 */
			do_action( 'digicommerce_variation_settings', $variation, $index );
			?>

			<p>
				<button type="button" class="button-link-delete remove-variation"><?php esc_html_e( 'Remove', 'digicommerce' ); ?></button>
			</p>
		</div>
		<?php
	}

	/**
	 * Render variation template for JavaScript with file support
	 */
	private function render_variation_template() {
		?>
		<div class="variation-item" data-index="{{INDEX}}">
			<h4><?php esc_html_e( 'Variation', 'digicommerce' ); ?> #<span class="variation-number">{{NUMBER}}</span></h4>
			
			<div class="variation-basic-fields">
				<p>
					<label><?php esc_html_e( 'Name', 'digicommerce' ); ?></label>
					<input type="text" name="variations[{{INDEX}}][name]" value="" />
				</p>
				<p>
					<label><?php esc_html_e( 'Price', 'digicommerce' ); ?></label>
					<input type="number" name="variations[{{INDEX}}][price]" value="" step="0.01" min="0" />
				</p>
				<p>
					<label><?php esc_html_e( 'Sale Price', 'digicommerce' ); ?></label>
					<input type="number" name="variations[{{INDEX}}][salePrice]" value="" step="0.01" min="0" />
				</p>
				<p>
					<label><?php esc_html_e( 'Direct Purchase URL', 'digicommerce' ); ?></label>
					<div class="digi-url-field-wrapper">
						<input type="text" class="digi-direct-url-variation" data-variation-index="{{INDEX}}" readonly style="cursor: pointer; width: 100%;" />
						<div class="digi-url-tooltip" style="display: none;">
							<?php esc_html_e( 'Click to copy', 'digicommerce' ); ?>
						</div>
					</div>
				</p>
				<p>
					<label>
						<input type="checkbox" name="variations[{{INDEX}}][isDefault]" value="1" />
						<?php esc_html_e( 'Default Selection', 'digicommerce' ); ?>
					</label>
				</p>
			</div>

			<!-- Variation Files Section -->
			<div class="variation-files-section">
				<h5><?php esc_html_e( 'Download Files', 'digicommerce' ); ?></h5>
				<div class="variation-files-container">
					<p class="no-variation-files"><?php esc_html_e( 'No files added yet.', 'digicommerce' ); ?></p>
				</div>
				<button type="button" class="button add-variation-file-btn" data-variation-index="{{INDEX}}">
					<?php esc_html_e( 'Add Download File', 'digicommerce' ); ?>
				</button>
			</div>

			<?php
			/**
			 * Hook for Pro features to add variation template settings
			 */
			do_action( 'digicommerce_variation_template_settings' );
			?>

			<p>
				<button type="button" class="button-link-delete remove-variation"><?php esc_html_e( 'Remove', 'digicommerce' ); ?></button>
			</p>
		</div>
		<?php
	}

	/**
	 * Render files metabox with modern REST API integration
	 *
	 * @param WP_Post $post Current post object.
	 */
	public function render_files_metabox( $post ) {
		$files = get_post_meta( $post->ID, 'digi_files', true ) ?: array();
		?>
		<div class="digicommerce-files-wrap" data-post-id="<?php echo esc_attr( $post->ID ); ?>">
			<div class="files-container files-list">
				<?php foreach ( $files as $index => $file ) : ?>
					<?php $this->render_file_item( $file, $index ); ?>
				<?php endforeach; ?>
			</div>
			<div class="file-actions">
				<button type="button" class="button button-primary upload-file-btn">
					<?php esc_html_e( 'Add Download File', 'digicommerce' ); ?>
				</button>
			</div>
			<input type="hidden" name="digi_files" id="digi_files" value="<?php echo esc_attr( wp_json_encode( $files ) ); ?>" />
		</div>
		<?php
	}

	/**
	 * Render individual file item
	 *
	 * @param array $file File data.
	 * @param int   $index File index.
	 */
	private function render_file_item( $file, $index ) {
		?>
		<div class="file-item" data-index="<?php echo esc_attr( $index ); ?>">
			<div class="file-header">
				<h4><?php esc_html_e( 'Download File', 'digicommerce' ); ?> #<?php echo esc_html( $index + 1 ); ?></h4>
				<button type="button" class="button-link-delete remove-file-btn">
					<?php esc_html_e( 'Remove', 'digicommerce' ); ?>
				</button>
			</div>
			
			<div class="file-details">
				<div class="field-group">
					<label><?php esc_html_e( 'File Name', 'digicommerce' ); ?></label>
					<input type="text" class="file-name-input" value="<?php echo esc_attr( $file['name'] ?? '' ); ?>" 
						placeholder="<?php esc_attr_e( 'Enter file name', 'digicommerce' ); ?>" />
				</div>
				
				<div class="field-group">
					<label><?php esc_html_e( 'Item Name', 'digicommerce' ); ?></label>
					<input type="text" class="file-item-name-input" value="<?php echo esc_attr( $file['itemName'] ?? '' ); ?>" 
						placeholder="<?php esc_attr_e( 'Enter item name', 'digicommerce' ); ?>" />
				</div>
				
				<div class="field-group">
					<label><?php esc_html_e( 'File Path', 'digicommerce' ); ?></label>
					<input type="text" class="file-path-input" value="<?php echo esc_attr( $file['file'] ?? '' ); ?>" readonly />
				</div>
				
				<?php if ( isset( $file['size'] ) ) : ?>
				<div class="field-group">
					<label><?php esc_html_e( 'File Size', 'digicommerce' ); ?></label>
					<span class="file-size"><?php echo esc_html( $this->format_file_size( $file['size'] ) ); ?></span>
				</div>
				<?php endif; ?>
			</div>
			
			<?php if ( class_exists( 'DigiCommerce_Pro_License' ) && DigiCommerce()->get_option( 'enable_license', false ) ) : ?>
			<div class="file-versions">
				<h5><?php esc_html_e( 'Versions', 'digicommerce' ); ?></h5>
				<div class="versions-container">
					<?php $this->render_file_versions( $file['versions'] ?? array(), $index ); ?>
				</div>
				<button type="button" class="button add-version-btn" data-file-index="<?php echo esc_attr( $index ); ?>">
					<?php esc_html_e( 'Add Version', 'digicommerce' ); ?>
				</button>
			</div>
			<?php endif; ?>
			
			<!-- Hidden data -->
			<input type="hidden" class="file-id" value="<?php echo esc_attr( $file['id'] ?? '' ); ?>" />
			<input type="hidden" class="file-type" value="<?php echo esc_attr( $file['type'] ?? '' ); ?>" />
		</div>
		<?php
	}

	/**
	 * Render variation files
	 *
	 * @param array $files Variation files.
	 * @param int   $variation_index Variation index.
	 */
	private function render_variation_files( $files, $variation_index ) {
		if ( empty( $files ) ) {
			echo '<p class="no-variation-files">' . esc_html__( 'No files added yet.', 'digicommerce' ) . '</p>';
			return;
		}

		foreach ( $files as $file_index => $file ) {
			?>
			<div class="variation-file-item" data-file-index="<?php echo esc_attr( $file_index ); ?>">
				<div class="variation-file-header">
					<span><?php echo esc_html( $file['name'] ?? 'Unnamed File' ); ?></span>
					<button type="button" class="button-link-delete remove-variation-file-btn">
						<?php esc_html_e( 'Remove', 'digicommerce' ); ?>
					</button>
				</div>
				<div class="variation-file-details">
					<input type="text" class="variation-file-name" value="<?php echo esc_attr( $file['name'] ?? '' ); ?>" 
						placeholder="<?php esc_attr_e( 'File name', 'digicommerce' ); ?>" />
					<input type="text" class="variation-file-item-name" value="<?php echo esc_attr( $file['itemName'] ?? '' ); ?>" 
						placeholder="<?php esc_attr_e( 'Item name', 'digicommerce' ); ?>" />
					<input type="text" class="variation-file-path" value="<?php echo esc_attr( $file['file'] ?? '' ); ?>" readonly />
						
					<?php if ( class_exists( 'DigiCommerce_Pro_License' ) && DigiCommerce()->get_option( 'enable_license', false ) ) : ?>
					<div class="variation-file-versions">
						<?php $this->render_file_versions( $file['versions'] ?? array(), "{$variation_index}_{$file_index}" ); ?>
					</div>
					<?php endif; ?>
					
					<!-- Hidden fields -->
					<input type="hidden" class="variation-file-id" value="<?php echo esc_attr( $file['id'] ?? '' ); ?>" />
					<input type="hidden" class="variation-file-type" value="<?php echo esc_attr( $file['type'] ?? '' ); ?>" />
				</div>
			</div>
			<?php
		}
	}

	/**
	 * Render file versions
	 *
	 * @param array $versions Versions array.
	 * @param int   $file_index File index.
	 */
	private function render_file_versions( $versions, $file_index ) {
		if ( empty( $versions ) ) {
			echo '<p class="no-versions">' . esc_html__( 'No versions added yet.', 'digicommerce' ) . '</p>';
			return;
		}
	
		foreach ( $versions as $version_index => $version ) {
			?>
			<div class="version-item" data-version-index="<?php echo esc_attr( $version_index ); ?>">
				<div class="version-header">
					<span class="version-label"><?php esc_html_e( 'Version', 'digicommerce' ); ?> <?php echo esc_html( $version['version'] ?? ( $version_index + 1 ) ); ?></span>
					<button type="button" class="button-link-delete remove-version-btn">
						<?php esc_html_e( 'Remove', 'digicommerce' ); ?>
					</button>
				</div>
				<div class="version-fields">
					<p>
						<label><?php esc_html_e( 'Version Number', 'digicommerce' ); ?></label>
						<input type="text" class="version-number" value="<?php echo esc_attr( $version['version'] ?? '' ); ?>" 
							placeholder="1.0.0" />
					</p>
					<p>
						<label><?php esc_html_e( 'Changelog', 'digicommerce' ); ?></label>
						<textarea class="version-changelog" rows="3" 
								  placeholder="<?php esc_attr_e( 'Describe what\'s new in this version...', 'digicommerce' ); ?>"><?php echo esc_textarea( $version['changelog'] ?? '' ); ?></textarea>
					</p>
				</div>
			</div>
			<?php
		}
	}

	/**
	 * Format file size for display
	 *
	 * @param int $bytes File size in bytes.
	 * @return string Formatted file size.
	 */
	private function format_file_size( $bytes ) {
		if ( $bytes === 0 ) {
			return '0 Bytes';
		}
		
		$k = 1024;
		$sizes = array( 'Bytes', 'KB', 'MB', 'GB' );
		$i = floor( log( $bytes ) / log( $k ) );
		
		return round( $bytes / pow( $k, $i ), 2 ) . ' ' . $sizes[ $i ];
	}

	/**
	 * Render gallery metabox
	 *
	 * @param WP_Post $post Current post object.
	 */
	public function render_gallery_metabox( $post ) {
		$gallery = get_post_meta( $post->ID, 'digi_gallery', true ) ?: array();
		?>
		<div class="digicommerce-gallery-wrap">
			<p>
				<button type="button" class="button select-gallery"><?php esc_html_e( 'Select Images', 'digicommerce' ); ?></button>
			</p>
			<div class="gallery-preview">
				<?php $this->render_gallery_preview( $gallery ); ?>
			</div>
			<input type="hidden" name="digi_gallery" id="digi_gallery" value="<?php echo esc_attr( wp_json_encode( $gallery ) ); ?>" />
		</div>
		<?php
	}

	/**
	 * Render gallery preview
	 *
	 * @param array $gallery Gallery images.
	 */
	private function render_gallery_preview( $gallery ) {
		if ( empty( $gallery ) ) {
			echo '<p>' . esc_html__( 'No images selected.', 'digicommerce' ) . '</p>';
			return;
		}

		echo '<div class="gallery-images">';
		foreach ( $gallery as $image ) {
			echo '<div class="gallery-image">';
			echo '<img src="' . esc_url( $image['url'] ) . '" alt="' . esc_attr( $image['alt'] ) . '" style="max-width: 100px; height: auto;" />';
			echo '</div>';
		}
		echo '</div>';
	}

	/**
	 * Render product data metabox
	 *
	 * @param WP_Post $post Current post object.
	 */
	public function render_product_data_metabox( $post ) {
		$description = get_post_meta( $post->ID, 'digi_product_description', true );
		$features = get_post_meta( $post->ID, 'digi_features', true ) ?: array();
		$instructions = get_post_meta( $post->ID, 'digi_instructions', true );
		?>
		<div class="digicommerce-product-data-wrap">
			<h4><?php esc_html_e( 'Product Description', 'digicommerce' ); ?></h4>
			<p>
				<textarea name="digi_product_description" rows="4" style="width: 100%;"><?php echo esc_textarea( $description ); ?></textarea>
			</p>

			<h4><?php esc_html_e( 'Features', 'digicommerce' ); ?></h4>
			<div class="features-list">
				<?php $this->render_features_list( $features ); ?>
			</div>
			<p>
				<button type="button" class="button add-feature"><?php esc_html_e( 'Add Feature', 'digicommerce' ); ?></button>
			</p>

			<h4><?php esc_html_e( 'Download Instructions', 'digicommerce' ); ?></h4>
			<p>
				<textarea name="digi_instructions" rows="4" style="width: 100%;"><?php echo esc_textarea( $instructions ); ?></textarea>
			</p>
		</div>
		<?php
	}

	/**
	 * Render features list
	 *
	 * @param array $features Features array.
	 */
	private function render_features_list( $features ) {
		if ( empty( $features ) ) {
			echo '<p>' . esc_html__( 'No features added yet.', 'digicommerce' ) . '</p>';
			return;
		}

		foreach ( $features as $index => $feature ) {
			?>
			<div class="feature-item">
				<p>
					<label><?php esc_html_e( 'Feature Name', 'digicommerce' ); ?></label>
					<input type="text" name="features[<?php echo esc_attr( $index ); ?>][name]" value="<?php echo esc_attr( $feature['name'] ?? '' ); ?>" />
				</p>
				<p>
					<label><?php esc_html_e( 'Feature Description', 'digicommerce' ); ?></label>
					<input type="text" name="features[<?php echo esc_attr( $index ); ?>][text]" value="<?php echo esc_attr( $feature['text'] ?? '' ); ?>" />
				</p>
				<p>
					<button type="button" class="button-link-delete remove-feature"><?php esc_html_e( 'Remove', 'digicommerce' ); ?></button>
				</p>
			</div>
			<?php
		}
	}

	/**
	 * Render bundle metabox
	 *
	 * @param WP_Post $post Current post object.
	 */
	public function render_bundle_metabox( $post ) {
		$bundle_products = get_post_meta( $post->ID, 'digi_bundle_products', true ) ?: array();
		
		// Get all products except current one
		$products = get_posts( array(
			'post_type' => 'digi_product',
			'post_status' => 'publish',
			'numberposts' => -1,
			'exclude' => array( $post->ID ),
			'fields' => 'ids'
		) );
		?>
		<div class="digicommerce-bundle-wrap">
			<p><?php esc_html_e( 'Select products to include in this bundle. Customer will receive downloads for all selected products with a single master license.', 'digicommerce' ); ?></p>
			
			<div class="bundle-products-list">
				<?php $this->render_bundle_products_list( $bundle_products, $products ); ?>
			</div>
			
			<p>
				<button type="button" class="button add-bundle-product"><?php esc_html_e( 'Add Product', 'digicommerce' ); ?></button>
			</p>
			
			<?php if ( ! empty( $bundle_products ) ) : ?>
				<div class="bundle-preview">
					<h4><?php esc_html_e( 'Bundle Preview', 'digicommerce' ); ?></h4>
					<p><?php printf( esc_html__( 'This bundle includes %d products.', 'digicommerce' ), count( array_filter( $bundle_products ) ) ); ?></p>
				</div>
			<?php endif; ?>
		</div>
		
		<script type="text/template" id="bundle-product-template">
			<div class="bundle-product-item">
				<p>
					<label><?php esc_html_e( 'Product', 'digicommerce' ); ?></label>
					<select name="bundle_products[{{INDEX}}]">
						<option value=""><?php esc_html_e( 'Select a product...', 'digicommerce' ); ?></option>
						<?php foreach ( $products as $product_id ) : ?>
							<option value="<?php echo esc_attr( $product_id ); ?>"><?php echo esc_html( get_the_title( $product_id ) ); ?></option>
						<?php endforeach; ?>
					</select>
				</p>
				<p>
					<button type="button" class="button-link-delete remove-bundle-product"><?php esc_html_e( 'Remove', 'digicommerce' ); ?></button>
				</p>
			</div>
		</script>
		<?php
	}

	/**
	 * Render bundle products list
	 *
	 * @param array $bundle_products Bundle products array.
	 * @param array $products Available products.
	 */
	private function render_bundle_products_list( $bundle_products, $products ) {
		if ( empty( $bundle_products ) ) {
			echo '<p>' . esc_html__( 'No products selected yet.', 'digicommerce' ) . '</p>';
			return;
		}

		foreach ( $bundle_products as $index => $selected_product ) {
			?>
			<div class="bundle-product-item">
				<p>
					<label><?php esc_html_e( 'Product', 'digicommerce' ); ?></label>
					<select name="bundle_products[<?php echo esc_attr( $index ); ?>]">
						<option value=""><?php esc_html_e( 'Select a product...', 'digicommerce' ); ?></option>
						<?php foreach ( $products as $product_id ) : ?>
							<option value="<?php echo esc_attr( $product_id ); ?>" <?php selected( $selected_product, $product_id ); ?>>
								<?php echo esc_html( get_the_title( $product_id ) ); ?>
							</option>
						<?php endforeach; ?>
					</select>
				</p>
				<p>
					<button type="button" class="button-link-delete remove-bundle-product"><?php esc_html_e( 'Remove', 'digicommerce' ); ?></button>
				</p>
			</div>
			<?php
		}
	}

	/**
	 * Render upgrade paths metabox
	 *
	 * @param WP_Post $post Current post object.
	 */
	public function render_upgrade_paths_metabox( $post ) {
		$upgrade_paths = get_post_meta( $post->ID, 'digi_upgrade_paths', true ) ?: array();
		
		// Get all products first, then filter for licensed ones
		$all_products = get_posts( array(
			'post_type' => 'digi_product',
			'post_status' => 'publish',
			'numberposts' => -1,
		) );
		
		// Filter for products with license enabled
		$products = array();
		foreach ( $all_products as $product ) {
			$license_enabled = get_post_meta( $product->ID, 'digi_license_enabled', true );
			$price_variations = get_post_meta( $product->ID, 'digi_price_variations', true );
			
			// Check if product has license enabled directly or through variations
			$has_license = false;
			if ( $license_enabled ) {
				$has_license = true;
			} elseif ( is_array( $price_variations ) ) {
				foreach ( $price_variations as $variation ) {
					if ( ! empty( $variation['license_enabled'] ) ) {
						$has_license = true;
						break;
					}
				}
			}
			
			if ( $has_license ) {
				$products[] = $product;
			}
		}
		?>
		<div class="digicommerce-upgrade-paths-wrap">
			<?php if ( empty( $products ) ) : ?>
				<div class="notice notice-info inline">
					<p><?php esc_html_e( 'No products with license system enabled found. You need licensed products to create upgrade paths.', 'digicommerce' ); ?></p>
				</div>
			<?php else : ?>
				<div class="upgrade-paths-list">
					<?php $this->render_upgrade_paths_list( $upgrade_paths, $products ); ?>
				</div>
				
				<p>
					<button type="button" class="button add-upgrade-path"><?php esc_html_e( 'Add Upgrade Path', 'digicommerce' ); ?></button>
				</p>
			<?php endif; ?>
		</div>
		
		<?php if ( ! empty( $products ) ) : ?>
			<script type="text/template" id="upgrade-path-template">
				<div class="upgrade-path-item">
					<h4><?php esc_html_e( 'Upgrade Path', 'digicommerce' ); ?> #<span class="path-number">{{NUMBER}}</span></h4>
					
					<p>
						<label><?php esc_html_e( 'Target Product', 'digicommerce' ); ?></label>
						<select name="upgrade_paths[{{INDEX}}][product_id]" class="target-product-select" data-index="{{INDEX}}">
							<option value=""><?php esc_html_e( 'Select a product...', 'digicommerce' ); ?></option>
							<?php foreach ( $products as $product ) : ?>
								<option value="<?php echo esc_attr( $product->ID ); ?>" 
										data-variations="<?php echo esc_attr( wp_json_encode( get_post_meta( $product->ID, 'digi_price_variations', true ) ?: array() ) ); ?>">
									<?php echo esc_html( $product->post_title ); ?>
									<?php if ( $product->ID === $post->ID ) : ?>
										<?php esc_html_e( ' (Current Product)', 'digicommerce' ); ?>
									<?php endif; ?>
								</option>
							<?php endforeach; ?>
						</select>
					</p>
					
					<p>
						<label><?php esc_html_e( 'Target Variation', 'digicommerce' ); ?></label>
						<select name="upgrade_paths[{{INDEX}}][variation_id]" class="target-variation-select" disabled>
							<option value=""><?php esc_html_e( 'Select a product first...', 'digicommerce' ); ?></option>
						</select>
					</p>
					
					<p>
						<label>
							<input type="checkbox" name="upgrade_paths[{{INDEX}}][prorate]" value="1" />
							<?php esc_html_e( 'Prorate', 'digicommerce' ); ?>
						</label>
					</p>
					
					<p>
						<label>
							<input type="checkbox" name="upgrade_paths[{{INDEX}}][include_coupon]" value="1" class="include-coupon-checkbox" />
							<?php esc_html_e( 'Include Coupon', 'digicommerce' ); ?>
						</label>
					</p>
					
					<div class="coupon-options" style="display: none;">
						<p>
							<label><?php esc_html_e( 'Discount Type', 'digicommerce' ); ?></label>
							<select name="upgrade_paths[{{INDEX}}][discount_type]">
								<option value="fixed"><?php esc_html_e( 'Fixed Amount', 'digicommerce' ); ?></option>
								<option value="percentage"><?php esc_html_e( 'Percentage', 'digicommerce' ); ?></option>
							</select>
						</p>
						
						<p>
							<label><?php esc_html_e( 'Amount', 'digicommerce' ); ?></label>
							<input type="number" name="upgrade_paths[{{INDEX}}][discount_amount]" value="" step="0.01" min="0" />
						</p>
					</div>
					
					<p>
						<button type="button" class="button-link-delete remove-upgrade-path"><?php esc_html_e( 'Remove Path', 'digicommerce' ); ?></button>
					</p>
				</div>
			</script>
			
			<script type="text/javascript">
				// Product variations data for JavaScript
				window.digicommerceProductVariations = {
					<?php foreach ( $products as $product ) : ?>
						<?php 
						$variations = get_post_meta( $product->ID, 'digi_price_variations', true ) ?: array();
						$licensed_variations = array_filter( $variations, function( $var ) {
							return ! empty( $var['license_enabled'] );
						});
						?>
						'<?php echo esc_js( $product->ID ); ?>': <?php echo wp_json_encode( array_values( $licensed_variations ) ); ?>,
					<?php endforeach; ?>
				};
			</script>
		<?php endif; ?>
		<?php
	}

	/**
	 * Render API data metabox
	 *
	 * @param WP_Post $post Current post object.
	 */
	public function render_api_data_metabox( $post ) {
		$api_data = get_post_meta( $post->ID, 'digi_api_data', true ) ?: array();
		?>
		<div class="digicommerce-api-data-wrap">
			<div class="api-data-sections">
				<div class="api-section">
					<h4><?php esc_html_e( 'Basic Information', 'digicommerce' ); ?></h4>
					
					<p>
						<label for="api_homepage"><?php esc_html_e( 'Homepage', 'digicommerce' ); ?></label>
						<input type="url" id="api_homepage" name="api_data[homepage]" value="<?php echo esc_attr( $api_data['homepage'] ?? '' ); ?>" />
					</p>
					
					<p>
						<label for="api_author"><?php esc_html_e( 'Author', 'digicommerce' ); ?></label>
						<input type="text" id="api_author" name="api_data[author]" value="<?php echo esc_attr( $api_data['author'] ?? '' ); ?>" />
					</p>
				</div>
				
				<div class="api-section">
					<h4><?php esc_html_e( 'Requirements', 'digicommerce' ); ?></h4>
					
					<p>
						<label for="api_requires"><?php esc_html_e( 'Requires WordPress Version', 'digicommerce' ); ?></label>
						<input type="text" id="api_requires" name="api_data[requires]" value="<?php echo esc_attr( $api_data['requires'] ?? '' ); ?>" />
					</p>
					
					<p>
						<label for="api_requires_php"><?php esc_html_e( 'Requires PHP Version', 'digicommerce' ); ?></label>
						<input type="text" id="api_requires_php" name="api_data[requires_php]" value="<?php echo esc_attr( $api_data['requires_php'] ?? '' ); ?>" />
					</p>
					
					<p>
						<label for="api_tested"><?php esc_html_e( 'Tested up to', 'digicommerce' ); ?></label>
						<input type="text" id="api_tested" name="api_data[tested]" value="<?php echo esc_attr( $api_data['tested'] ?? '' ); ?>" />
					</p>
				</div>
				
				<div class="api-section">
					<h4><?php esc_html_e( 'Description & Installation', 'digicommerce' ); ?></h4>
					
					<p>
						<label for="api_description"><?php esc_html_e( 'Description', 'digicommerce' ); ?></label>
						<textarea id="api_description" name="api_data[description]" rows="4"><?php echo esc_textarea( $api_data['description'] ?? '' ); ?></textarea>
					</p>
					
					<p>
						<label for="api_installation"><?php esc_html_e( 'Installation', 'digicommerce' ); ?></label>
						<textarea id="api_installation" name="api_data[installation]" rows="4"><?php echo esc_textarea( $api_data['installation'] ?? '' ); ?></textarea>
					</p>
					
					<p>
						<label for="api_upgrade_notice"><?php esc_html_e( 'Upgrade Notice', 'digicommerce' ); ?></label>
						<textarea id="api_upgrade_notice" name="api_data[upgrade_notice]" rows="2"><?php echo esc_textarea( $api_data['upgrade_notice'] ?? '' ); ?></textarea>
					</p>
				</div>
				
				<div class="api-section">
					<h4><?php esc_html_e( 'Assets', 'digicommerce' ); ?></h4>
					
					<p>
						<label for="api_icon"><?php esc_html_e( 'Plugin Icon URL', 'digicommerce' ); ?></label>
						<input type="url" id="api_icon" name="api_data[icons][default]" value="<?php echo esc_attr( $api_data['icons']['default'] ?? '' ); ?>" />
					</p>
					
					<p>
						<label for="api_banner_low"><?php esc_html_e( 'Banner Low Resolution URL', 'digicommerce' ); ?></label>
						<input type="url" id="api_banner_low" name="api_data[banners][low]" value="<?php echo esc_attr( $api_data['banners']['low'] ?? '' ); ?>" />
					</p>
					
					<p>
						<label for="api_banner_high"><?php esc_html_e( 'Banner High Resolution URL', 'digicommerce' ); ?></label>
						<input type="url" id="api_banner_high" name="api_data[banners][high]" value="<?php echo esc_attr( $api_data['banners']['high'] ?? '' ); ?>" />
					</p>
				</div>
				
				<div class="api-section">
					<h4><?php esc_html_e( 'Contributors', 'digicommerce' ); ?></h4>
					<div class="contributors-list">
						<?php $this->render_contributors_list( $api_data['contributors'] ?? array() ); ?>
					</div>
					<p>
						<button type="button" class="button add-contributor"><?php esc_html_e( 'Add Contributor', 'digicommerce' ); ?></button>
					</p>
				</div>
			</div>
		</div>
		
		<script type="text/template" id="contributor-template">
			<div class="contributor-item">
				<p>
					<label><?php esc_html_e( 'Username', 'digicommerce' ); ?></label>
					<input type="text" name="api_data[contributors][{{INDEX}}][username]" value="" placeholder="<?php esc_attr_e( 'WordPress.org username', 'digicommerce' ); ?>" />
				</p>
				<p>
					<label><?php esc_html_e( 'Display Name', 'digicommerce' ); ?></label>
					<input type="text" name="api_data[contributors][{{INDEX}}][name]" value="" />
				</p>
				<p>
					<label><?php esc_html_e( 'Avatar URL', 'digicommerce' ); ?></label>
					<input type="url" name="api_data[contributors][{{INDEX}}][avatar]" value="" />
				</p>
				<p>
					<button type="button" class="button-link-delete remove-contributor"><?php esc_html_e( 'Remove', 'digicommerce' ); ?></button>
				</p>
			</div>
		</script>
		<?php
	}

	/**
	 * Save meta boxes
	 *
	 * @param int     $post_id Post ID.
	 * @param WP_Post $post    Post object.
	 */
	public function save_meta_boxes( $post_id, $post ) {
		// Verify nonce
		if ( ! isset( $_POST['digicommerce_product_nonce'] ) || ! wp_verify_nonce( $_POST['digicommerce_product_nonce'], 'digicommerce_product_metabox' ) ) {
			return;
		}

		// Check if user has permission to edit
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		// Skip autosave
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		// Only save for our post type
		if ( 'digi_product' !== $post->post_type ) {
			return;
		}

		// Save price mode
		if ( isset( $_POST['digi_price_mode'] ) ) {
			update_post_meta( $post_id, 'digi_price_mode', sanitize_text_field( $_POST['digi_price_mode'] ) );
		}

		// Save single pricing
		if ( isset( $_POST['digi_price'] ) ) {
			update_post_meta( $post_id, 'digi_price', floatval( $_POST['digi_price'] ) );
		}

		if ( isset( $_POST['digi_sale_price'] ) ) {
			$sale_price = ! empty( $_POST['digi_sale_price'] ) ? floatval( $_POST['digi_sale_price'] ) : '';
			update_post_meta( $post_id, 'digi_sale_price', $sale_price );
		}

		// Save variations
		if ( isset( $_POST['variations'] ) && is_array( $_POST['variations'] ) ) {
			$variations = array();
			foreach ( $_POST['variations'] as $variation ) {
				if ( ! empty( $variation['name'] ) || ! empty( $variation['price'] ) ) {
					// Handle files data
					$files = array();
					if ( isset( $variation['files'] ) ) {
						if ( is_string( $variation['files'] ) ) {
							// JSON string from hidden input
							$decoded_files = json_decode( stripslashes( $variation['files'] ), true );
							if ( json_last_error() === JSON_ERROR_NONE && is_array( $decoded_files ) ) {
								foreach ( $decoded_files as $file ) {
									if ( ! empty( $file['file'] ) ) {
										$files[] = array(
											'id'       => $file['id'] ?? uniqid(),
											'name'     => sanitize_text_field( $file['name'] ?? '' ),
											'file'     => sanitize_text_field( $file['file'] ?? '' ),
											'type'     => sanitize_text_field( $file['type'] ?? '' ),
											'size'     => intval( $file['size'] ?? 0 ),
											'itemName' => sanitize_text_field( $file['itemName'] ?? '' ),
											's3'       => ( $file['s3'] === 'true' || $file['s3'] === '1' || $file['s3'] === true ),
											'versions' => isset( $file['versions'] ) && is_array( $file['versions'] ) ? $file['versions'] : array(),
										);
									}
								}
							}
						} elseif ( is_array( $variation['files'] ) ) {
							// Direct array
							$files = $variation['files'];
						}
					}
					
					$variation_data = array(
						'id'        => $variation['id'] ?? uniqid(),
						'name'      => sanitize_text_field( $variation['name'] ?? '' ),
						'price'     => floatval( $variation['price'] ?? 0 ),
						'salePrice' => ! empty( $variation['salePrice'] ) ? floatval( $variation['salePrice'] ) : null,
						'isDefault' => ! empty( $variation['isDefault'] ),
						'files'     => $files,
					);

					// Allow Pro features to add their data
					$variation_data = apply_filters( 'digicommerce_save_variation_data', $variation_data, $variation );

					$variations[] = $variation_data;
				}
			}
			update_post_meta( $post_id, 'digi_price_variations', $variations );
		}

		// Save files
		if ( isset( $_POST['digi_files'] ) ) {
			$files_data = $_POST['digi_files'];
			
			// Handle JSON string from hidden input
			if ( is_string( $files_data ) ) {
				$s3_enabled = class_exists( 'DigiCommerce_Pro' ) && class_exists( 'DigiCommerce_Pro_S3' ) && DigiCommerce()->get_option( 'enable_s3', false );
				$decoded_files = json_decode( stripslashes( $files_data ), true );
				if ( json_last_error() === JSON_ERROR_NONE && is_array( $decoded_files ) ) {
					$files = array();
					foreach ( $decoded_files as $file ) {
						if ( ! empty( $file['file'] ) ) {
							$files[] = array(
								'id'       => $file['id'] ?? uniqid(),
								'name'     => sanitize_text_field( $file['name'] ?? '' ),
								'file'     => sanitize_text_field( $file['file'] ?? '' ),
								'type'     => sanitize_text_field( $file['type'] ?? '' ),
								'size'     => intval( $file['size'] ?? 0 ),
								'itemName' => sanitize_text_field( $file['itemName'] ?? '' ),
								's3'       => $s3_enabled,
								'versions' => isset( $file['versions'] ) && is_array( $file['versions'] ) ? $file['versions'] : array(),
							);
						}
					}
					update_post_meta( $post_id, 'digi_files', $files );
				}
			}
		} else {
			// If no files posted, keep existing files
			$existing_files = get_post_meta( $post_id, 'digi_files', true );
			if ( ! $existing_files ) {
				update_post_meta( $post_id, 'digi_files', array() );
			}
		}

		// Save gallery
		if ( isset( $_POST['digi_gallery'] ) ) {
			$gallery_data = $_POST['digi_gallery'];
			
			// Handle JSON string
			if ( is_string( $gallery_data ) ) {
				$decoded_gallery = json_decode( stripslashes( $gallery_data ), true );
				if ( json_last_error() === JSON_ERROR_NONE && is_array( $decoded_gallery ) ) {
					$gallery = array();
					foreach ( $decoded_gallery as $image ) {
						if ( ! empty( $image['id'] ) && ! empty( $image['url'] ) ) {
							$gallery[] = array(
								'id'  => intval( $image['id'] ),
								'url' => esc_url_raw( $image['url'] ),
								'alt' => sanitize_text_field( $image['alt'] ?? '' ),
							);
						}
					}
					update_post_meta( $post_id, 'digi_gallery', $gallery );
				}
			} elseif ( is_array( $gallery_data ) ) {
				// Handle array data
				$gallery = array();
				foreach ( $gallery_data as $image ) {
					if ( ! empty( $image['id'] ) && ! empty( $image['url'] ) ) {
						$gallery[] = array(
							'id'  => intval( $image['id'] ),
							'url' => esc_url_raw( $image['url'] ),
							'alt' => sanitize_text_field( $image['alt'] ?? '' ),
						);
					}
				}
				update_post_meta( $post_id, 'digi_gallery', $gallery );
			}
		}

		// Save product description
		if ( isset( $_POST['digi_product_description'] ) ) {
			update_post_meta( $post_id, 'digi_product_description', sanitize_textarea_field( $_POST['digi_product_description'] ) );
		}

		// Save instructions
		if ( isset( $_POST['digi_instructions'] ) ) {
			update_post_meta( $post_id, 'digi_instructions', sanitize_textarea_field( $_POST['digi_instructions'] ) );
		}

		// Save features
		if ( isset( $_POST['features'] ) && is_array( $_POST['features'] ) ) {
			$features = array();
			foreach ( $_POST['features'] as $feature ) {
				if ( ! empty( $feature['name'] ) || ! empty( $feature['text'] ) ) {
					$features[] = array(
						'name' => sanitize_text_field( $feature['name'] ?? '' ),
						'text' => sanitize_text_field( $feature['text'] ?? '' ),
					);
				}
			}
			update_post_meta( $post_id, 'digi_features', $features );
		} else {
			// If no features posted, keep existing or set empty array
			if ( ! get_post_meta( $post_id, 'digi_features', true ) ) {
				update_post_meta( $post_id, 'digi_features', array() );
			}
		}

		// Save bundle products
		if ( isset( $_POST['bundle_products'] ) && is_array( $_POST['bundle_products'] ) ) {
			$bundle_products = array();
			foreach ( $_POST['bundle_products'] as $product_id ) {
				if ( ! empty( $product_id ) && is_numeric( $product_id ) ) {
					$bundle_products[] = intval( $product_id );
				}
			}
			update_post_meta( $post_id, 'digi_bundle_products', $bundle_products );
		} else {
			// Always save an empty array, never null
			update_post_meta( $post_id, 'digi_bundle_products', array() );
		}

		// Save upgrade paths (Pro feature)
		if ( class_exists( 'DigiCommerce_Pro' ) && 
			 class_exists( 'DigiCommerce_Pro_License' ) && 
			 DigiCommerce()->get_option( 'enable_license', false ) ) {
			
			if ( isset( $_POST['upgrade_paths'] ) && is_array( $_POST['upgrade_paths'] ) ) {
				$upgrade_paths = array();
				foreach ( $_POST['upgrade_paths'] as $index => $path ) {					
					if ( ! empty( $path['product_id'] ) ) {
						$upgrade_path = array(
							'product_id'      => sanitize_text_field( $path['product_id'] ),
							'variation_id'    => sanitize_text_field( $path['variation_id'] ?? '' ),
							'prorate'         => ! empty( $path['prorate'] ),
							'include_coupon'  => ! empty( $path['include_coupon'] ),
							'discount_type'   => sanitize_text_field( $path['discount_type'] ?? 'fixed' ),
							'discount_amount' => sanitize_text_field( $path['discount_amount'] ?? '' ),
						);
						
						$upgrade_paths[] = $upgrade_path;
					}
				}
				
				update_post_meta( $post_id, 'digi_upgrade_paths', $upgrade_paths );
				
				// Verify what was saved
				$saved_data = get_post_meta( $post_id, 'digi_upgrade_paths', true );
			} else {
				update_post_meta( $post_id, 'digi_upgrade_paths', array() );
			}

			// Save API data
			if ( isset( $_POST['api_data'] ) && is_array( $_POST['api_data'] ) ) {
				$api_data = array();
				
				// Basic info
				$api_data['homepage'] = esc_url_raw( $_POST['api_data']['homepage'] ?? '' );
				$api_data['author'] = sanitize_text_field( $_POST['api_data']['author'] ?? '' );
				
				// Requirements
				$api_data['requires'] = sanitize_text_field( $_POST['api_data']['requires'] ?? '' );
				$api_data['requires_php'] = sanitize_text_field( $_POST['api_data']['requires_php'] ?? '' );
				$api_data['tested'] = sanitize_text_field( $_POST['api_data']['tested'] ?? '' );
				
				// Descriptions
				$api_data['description'] = wp_kses_post( $_POST['api_data']['description'] ?? '' );
				$api_data['installation'] = wp_kses_post( $_POST['api_data']['installation'] ?? '' );
				$api_data['upgrade_notice'] = sanitize_textarea_field( $_POST['api_data']['upgrade_notice'] ?? '' );
				
				// Assets
				$api_data['icons'] = array(
					'default' => esc_url_raw( $_POST['api_data']['icons']['default'] ?? '' )
				);
				$api_data['banners'] = array(
					'low' => esc_url_raw( $_POST['api_data']['banners']['low'] ?? '' ),
					'high' => esc_url_raw( $_POST['api_data']['banners']['high'] ?? '' )
				);
				
				// Contributors
				$contributors = array();
				if ( isset( $_POST['api_data']['contributors'] ) && is_array( $_POST['api_data']['contributors'] ) ) {
					foreach ( $_POST['api_data']['contributors'] as $contributor ) {
						if ( ! empty( $contributor['username'] ) || ! empty( $contributor['name'] ) ) {
							$contributors[] = array(
								'username' => sanitize_text_field( $contributor['username'] ?? '' ),
								'name'     => sanitize_text_field( $contributor['name'] ?? '' ),
								'avatar'   => esc_url_raw( $contributor['avatar'] ?? '' ),
							);
						}
					}
				}
				$api_data['contributors'] = $contributors;
				
				update_post_meta( $post_id, 'digi_api_data', $api_data );
			}
		}

		// Allow Pro extensions to save their data
		do_action( 'digicommerce_save_product_metabox', $post_id, $post );
	}

/**
 * Render upgrade paths list - Debug Version
 *
 * @param array $upgrade_paths Upgrade paths array.
 * @param array $products Available products.
 */
private function render_upgrade_paths_list( $upgrade_paths, $products ) {
	if ( empty( $upgrade_paths ) ) {
		echo '<p>' . esc_html__( 'No upgrade paths added yet.', 'digicommerce' ) . '</p>';
		return;
	}

	// Get current post ID for comparison
	global $post;
	$current_post_id = $post->ID;

	foreach ( $upgrade_paths as $index => $path ) {
		$selected_product_id = $path['product_id'] ?? '';
		$selected_variation_id = $path['variation_id'] ?? '';
		$product_variations = array();
		
		// Get variations for selected product
		if ( $selected_product_id ) {
			$all_variations = get_post_meta( $selected_product_id, 'digi_price_variations', true ) ?: array();
			
			$product_variations = array_filter( $all_variations, function( $var ) {
				return ! empty( $var['license_enabled'] );
			});
						
			// Check if our selected variation exists in the filtered list
			$variation_found = false;
			foreach ( $product_variations as $var ) {
				if ( ( $var['id'] ?? '' ) === $selected_variation_id ) {
					$variation_found = true;
					break;
				}
			}
			
			if ( ! $variation_found && ! empty( $selected_variation_id ) ) {
				// Check if it exists in all variations but just not license-enabled
				foreach ( $all_variations as $var ) {
					if ( ( $var['id'] ?? '' ) === $selected_variation_id ) {
						break;
					}
				}
			}
		}
		?>
		<div class="upgrade-path-item" data-index="<?php echo esc_attr( $index ); ?>">
			<h4><?php esc_html_e( 'Upgrade Path', 'digicommerce' ); ?> #<?php echo esc_html( $index + 1 ); ?></h4>
			
			<p>
				<label><?php esc_html_e( 'Target Product', 'digicommerce' ); ?></label>
				<select name="upgrade_paths[<?php echo esc_attr( $index ); ?>][product_id]" class="target-product-select" data-index="<?php echo esc_attr( $index ); ?>">
					<option value=""><?php esc_html_e( 'Select a product...', 'digicommerce' ); ?></option>
					<?php foreach ( $products as $product ) : ?>
						<option value="<?php echo esc_attr( $product->ID ); ?>" 
								<?php selected( $selected_product_id, $product->ID ); ?>
								data-variations="<?php echo esc_attr( wp_json_encode( get_post_meta( $product->ID, 'digi_price_variations', true ) ?: array() ) ); ?>">
							<?php echo esc_html( $product->post_title ); ?>
							<?php if ( $product->ID === $current_post_id ) : ?>
								<?php esc_html_e( ' (Current Product)', 'digicommerce' ); ?>
							<?php endif; ?>
						</option>
					<?php endforeach; ?>
				</select>
			</p>
			
			<p>
				<label><?php esc_html_e( 'Target Variation', 'digicommerce' ); ?></label>
				<select name="upgrade_paths[<?php echo esc_attr( $index ); ?>][variation_id]" class="target-variation-select">
					<?php if ( empty( $product_variations ) ) : ?>
						<option value=""><?php esc_html_e( 'Select a product first...', 'digicommerce' ); ?></option>
					<?php else : ?>
						<option value=""><?php esc_html_e( 'Select a variation...', 'digicommerce' ); ?></option>
						<?php 
						foreach ( $product_variations as $variation ) : 
							$variation_id = $variation['id'] ?? '';
							$is_selected = ( $variation_id === $selected_variation_id );
						?>
							<option value="<?php echo esc_attr( $variation_id ); ?>" <?php selected( $selected_variation_id, $variation_id ); ?>>
								<?php echo esc_html( $variation['name'] ?? 'Unnamed Variation' ); ?>
							</option>
						<?php endforeach; ?>
					<?php endif; ?>
				</select>
			</p>
			
			<p>
				<label>
					<input type="checkbox" name="upgrade_paths[<?php echo esc_attr( $index ); ?>][prorate]" value="1" <?php checked( ! empty( $path['prorate'] ) ); ?> />
					<?php esc_html_e( 'Prorate', 'digicommerce' ); ?>
				</label>
			</p>
			
			<p>
				<label>
					<input type="checkbox" name="upgrade_paths[<?php echo esc_attr( $index ); ?>][include_coupon]" value="1" class="include-coupon-checkbox" <?php checked( ! empty( $path['include_coupon'] ) ); ?> />
					<?php esc_html_e( 'Include Coupon', 'digicommerce' ); ?>
				</label>
			</p>
			
			<div class="coupon-options" style="<?php echo ! empty( $path['include_coupon'] ) ? '' : 'display: none;'; ?>">
				<p>
					<label><?php esc_html_e( 'Discount Type', 'digicommerce' ); ?></label>
					<select name="upgrade_paths[<?php echo esc_attr( $index ); ?>][discount_type]">
						<option value="fixed" <?php selected( $path['discount_type'] ?? 'fixed', 'fixed' ); ?>><?php esc_html_e( 'Fixed Amount', 'digicommerce' ); ?></option>
						<option value="percentage" <?php selected( $path['discount_type'] ?? 'fixed', 'percentage' ); ?>><?php esc_html_e( 'Percentage', 'digicommerce' ); ?></option>
					</select>
				</p>
				
				<p>
					<label><?php esc_html_e( 'Amount', 'digicommerce' ); ?></label>
					<input type="number" name="upgrade_paths[<?php echo esc_attr( $index ); ?>][discount_amount]" value="<?php echo esc_attr( $path['discount_amount'] ?? '' ); ?>" step="0.01" min="0" />
				</p>
			</div>
			
			<p>
				<button type="button" class="button-link-delete remove-upgrade-path"><?php esc_html_e( 'Remove Path', 'digicommerce' ); ?></button>
			</p>
		</div>
		<?php
	}
}

	/**
	 * Render contributors list
	 *
	 * @param array $contributors Contributors array.
	 */
	private function render_contributors_list( $contributors ) {
		if ( empty( $contributors ) ) {
			echo '<p>' . esc_html__( 'No contributors added yet.', 'digicommerce' ) . '</p>';
			return;
		}

		foreach ( $contributors as $index => $contributor ) {
			?>
			<div class="contributor-item">
				<p>
					<label><?php esc_html_e( 'Username', 'digicommerce' ); ?></label>
					<input type="text" name="api_data[contributors][<?php echo esc_attr( $index ); ?>][username]" value="<?php echo esc_attr( $contributor['username'] ?? '' ); ?>" placeholder="<?php esc_attr_e( 'WordPress.org username', 'digicommerce' ); ?>" />
				</p>
				<p>
					<label><?php esc_html_e( 'Display Name', 'digicommerce' ); ?></label>
					<input type="text" name="api_data[contributors][<?php echo esc_attr( $index ); ?>][name]" value="<?php echo esc_attr( $contributor['name'] ?? '' ); ?>" />
				</p>
				<p>
					<label><?php esc_html_e( 'Avatar URL', 'digicommerce' ); ?></label>
					<input type="url" name="api_data[contributors][<?php echo esc_attr( $index ); ?>][avatar]" value="<?php echo esc_attr( $contributor['avatar'] ?? '' ); ?>" />
				</p>
				<p>
					<button type="button" class="button-link-delete remove-contributor"><?php esc_html_e( 'Remove', 'digicommerce' ); ?></button>
				</p>
			</div>
			<?php
		}
	}

	/**
	 * Enqueue scripts for metaboxes
	 *
	 * @param string $hook_suffix Current admin page.
	 */
	public function enqueue_scripts( $hook_suffix ) {
		global $post_type;

		if ( ! in_array( $hook_suffix, array( 'post.php', 'post-new.php' ), true ) || 'digi_product' !== $post_type ) {
			return;
		}

		wp_enqueue_media();

		wp_enqueue_style(
			'digicommerce-product-metaboxes',
			DIGICOMMERCE_PLUGIN_URL . 'assets/css/admin/product-metaboxes.css',
			array(),
			DIGICOMMERCE_VERSION
		);

		wp_enqueue_script(
			'digicommerce-product-metaboxes',
			DIGICOMMERCE_PLUGIN_URL . 'assets/js/admin/product-metaboxes.js',
			array( 'wp-api-fetch', 'wp-i18n' ),
			DIGICOMMERCE_VERSION,
			true
		);

		// Localization
		$pro_active = class_exists( 'DigiCommerce_Pro' );
		$s3_enabled = $pro_active && class_exists( 'DigiCommerce_Pro_S3' ) && DigiCommerce()->get_option( 'enable_s3', false );
		$license_enabled = $pro_active && class_exists( 'DigiCommerce_Pro_License' ) && DigiCommerce()->get_option( 'enable_license', false );
	
		wp_localize_script(
			'digicommerce-product-metaboxes',
			'digicommerceVars',
			array(
				'ajaxurl'          => admin_url( 'admin-ajax.php' ),
				'upload_nonce'     => wp_create_nonce( 'digicommerce_upload' ),
				'delete_nonce'     => wp_create_nonce( 'wp_rest' ),
				'checkout_page_id' => DigiCommerce()->get_option( 'checkout_page_id', '' ),
				'checkout_url'     => get_permalink( DigiCommerce()->get_option( 'checkout_page_id', '' ) ),
				'pro_active'       => $pro_active,
				's3_enabled'       => $s3_enabled,
				'license_enabled'  => $license_enabled,
				'i18n' => array(
					// Purchase URL
					'clickToCopy'                  => __( 'Click to copy', 'digicommerce' ),
					'linkCopied'                   => __( 'Link copied', 'digicommerce' ),

					// File upload/management
					'uploading'                    => __( 'Uploading...', 'digicommerce' ),
					'upload_failed'                => __( 'Upload failed. Please try again.', 'digicommerce' ),
					's3_uploading'                 => __( 'Uploading to Amazon S3...', 'digicommerce' ),
					'deleting'                     => __( 'Deleting...', 'digicommerce' ),
					'delete_failed'                => __( 'Delete failed. Please try again.', 'digicommerce' ),
					'file_too_large'               => __( 'File size too large. Maximum size is 100MB.', 'digicommerce' ),
					'invalid_file'                 => __( 'Invalid file type. Please upload a supported file format.', 'digicommerce' ),
					'file_removed_s3'              => __( 'File removed from product (was already deleted from S3)', 'digicommerce' ),
					'file_removed_server'          => __( 'File removed from product (was already deleted from server)', 'digicommerce' ),
					'file_deleted_s3'              => __( 'File successfully removed from S3', 'digicommerce' ),
					's3_delete_failed'             => __( 'Failed to delete file from S3. Please try again.', 'digicommerce' ),
					
					// Media uploader
					'selectImages'                 => __( 'Select Images', 'digicommerce' ),
					'useImages'                    => __( 'Use Images', 'digicommerce' ),
					'selectFile'                   => __( 'Select File', 'digicommerce' ),
					'useFile'                      => __( 'Use File', 'digicommerce' ),
					'dismissNotice'                => __( 'Dismiss this notice.', 'digicommerce' ),
					
					// General UI
					'removeConfirm'                => __( 'Are you sure you want to remove this item?', 'digicommerce' ),
					'remove'                       => __( 'Remove', 'digicommerce' ),
					
					// Form labels and placeholders
					'fileName'                     => __( 'File Name', 'digicommerce' ),
					'filePath'                     => __( 'File Path', 'digicommerce' ),
					'itemName'                     => __( 'Item Name', 'digicommerce' ),
					'fileSize'                     => __( 'File Size', 'digicommerce' ),
					'unnamedFile'                  => __( 'Unnamed File', 'digicommerce' ),
					'versionPlaceholder'           => __( '1.0.0', 'digicommerce' ),
					'changelogPlaceholder'         => __( 'Describe what\'s new in this version...', 'digicommerce' ),
					
					// Features
					'featureName'                  => __( 'Feature Name', 'digicommerce' ),
					'featureDescription'           => __( 'Feature Description', 'digicommerce' ),
					
					// Versions
					'versions'                     => __( 'Versions', 'digicommerce' ),
					'versionNumber'                => __( 'Version Number', 'digicommerce' ),
					'addVersion'                   => __( 'Add Version', 'digicommerce' ),
					'noVersionsAdded'              => __( 'No versions added yet.', 'digicommerce' ),
					'changelog'                    => __( 'Changelog', 'digicommerce' ),
					'semanticVersioning'           => __( 'Please use semantic versioning (e.g., 1.0.5)', 'digicommerce' ),
					
					// Downloads
					'downloadFiles'                => __( 'Download Files', 'digicommerce' ),
					'addDownloadFile'              => __( 'Add Download File', 'digicommerce' ),
					
					// Bundle/Product selection
					'product'                      => __( 'Product', 'digicommerce' ),
					'selectProduct'                => __( 'Select a product...', 'digicommerce' ),
					'selectVariation'              => __( 'Select a variation...', 'digicommerce' ),
					'selectProductFirst'           => __( 'Select a product first...', 'digicommerce' ),
					'noLicensedVariations'         => __( 'No licensed variations available', 'digicommerce' ),
					'unnamedVariation'             => __( 'Unnamed variation', 'digicommerce' ),
					'errorLoadingVariations'       => __( 'Error loading variations', 'digicommerce' ),
					
					// Status messages
					'saved'                        => __( 'Saved successfully', 'digicommerce' ),
				),
			)
		);

		// Allow Pro features to enqueue their scripts
		do_action( 'digicommerce_enqueue_metabox_scripts', $hook_suffix );
	}
}

// Initialize the class
DigiCommerce_Product_Metaboxes::get_instance();