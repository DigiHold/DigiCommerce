<?php
defined( 'ABSPATH' ) || exit;

/**
 * DigiCommerce blocks
 */
class DigiCommerce_Blocks {
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
		add_filter( 'block_categories_all', array( $this, 'register_block_category' ), 10, 2 );
		add_action( 'enqueue_block_editor_assets', array( $this, 'enqueue_editor_assets' ) );
		
		// Load block classes early, before init
		add_action( 'plugins_loaded', array( $this, 'load_block_classes' ), 10 );
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
	 * Load block classes
	 */
	public function load_block_classes() {
		// Load individual block classes
		$block_classes = array(
			'product-button'       => 'class-digicommerce-product-button-block.php',
			'product-title'       => 'class-digicommerce-product-title-block.php',
			'product-price'       => 'class-digicommerce-product-price-block.php',
			'product-description' => 'class-digicommerce-product-description-block.php',
			'product-content'     => 'class-digicommerce-product-content-block.php',
			'product-gallery'     => 'class-digicommerce-product-gallery-block.php',
			'product-features'    => 'class-digicommerce-product-features-block.php',
			'product-meta'        => 'class-digicommerce-product-meta-block.php',
			'product-share'       => 'class-digicommerce-product-share-block.php',
			'add-to-cart'         => 'class-digicommerce-add-to-cart-block.php',
			'products-grid'       => 'class-digicommerce-products-grid-block.php',
			'products-filters'    => 'class-digicommerce-products-filters-block.php',
			'products-sorting'    => 'class-digicommerce-products-sorting-block.php',
			'success-message'     => 'class-digicommerce-success-message-block.php',
			'order-receipt'       => 'class-digicommerce-order-receipt-block.php',
			'order-details'       => 'class-digicommerce-order-details-block.php',
		);

		foreach ( $block_classes as $block_name => $file_name ) {
			$file_path = DIGICOMMERCE_PLUGIN_DIR . 'includes/blocks/' . $file_name;
			if ( file_exists( $file_path ) ) {
				require_once $file_path;
			}
		}
	}

	/**
	 * Register block category
	 *
	 * @param array   $categories List of block categories.
	 * @param WP_Post $post Current post object.
	 */
	public function register_block_category( $categories, $post ) {
		return array_merge(
			array(
				array(
					'slug'  => 'digicommerce',
					'title' => esc_html__( 'DigiCommerce', 'digicommerce' ),
				),
			),
			$categories
		);
	}

	/**
	 * Get all available blocks dynamically
	 */
	private function get_available_blocks() {
		$blocks = array();
		$blocks_dir = DIGICOMMERCE_PLUGIN_DIR . 'assets/blocks/';
		
		if ( is_dir( $blocks_dir ) ) {
			$block_folders = array_filter( glob( $blocks_dir . '*' ), 'is_dir' );
			foreach ( $block_folders as $block_folder ) {
				$blocks[] = basename( $block_folder );
			}
		}
		
		return $blocks;
	}

	/**
	 * Get blocks that should be loaded for current context
	 */
	private function get_blocks_for_current_context() {
		$blocks_to_load = array();

		// Check post content first (for regular posts/pages)
		global $post;
		if ( $post && has_blocks( $post->post_content ) ) {
			$blocks = parse_blocks( $post->post_content );
			$blocks_to_load = array_merge( $blocks_to_load, $this->extract_digicommerce_blocks( $blocks ) );
		}

		// Check current template for FSE themes
		if ( function_exists( 'wp_is_block_theme' ) && wp_is_block_theme() ) {
			$template_blocks = $this->get_template_blocks();
			$blocks_to_load = array_merge( $blocks_to_load, $template_blocks );
		}

		// Fallback: Load blocks based on page context
		if ( empty( $blocks_to_load ) ) {
			$blocks_to_load = $this->get_blocks_by_page_context();
		}

		return array_unique( $blocks_to_load );
	}

	/**
	 * Extract DigiCommerce blocks from parsed blocks
	 */
	private function extract_digicommerce_blocks( $blocks ) {
		$digi_blocks = array();

		foreach ( $blocks as $block ) {
			// Check if it's a DigiCommerce block
			if ( strpos( $block['blockName'], 'digicommerce/' ) === 0 ) {
				$block_name = str_replace( 'digicommerce/', '', $block['blockName'] );
				$digi_blocks[] = $block_name;
			}

			// Recursively check inner blocks
			if ( ! empty( $block['innerBlocks'] ) ) {
				$inner_digi_blocks = $this->extract_digicommerce_blocks( $block['innerBlocks'] );
				$digi_blocks = array_merge( $digi_blocks, $inner_digi_blocks );
			}
		}

		return $digi_blocks;
	}

	/**
	 * Get blocks used in current template
	 */
	private function get_template_blocks() {
		$template_blocks = array();

		// Try to get current template content
		$template_content = $this->get_current_template_content();
		
		if ( $template_content ) {
			$blocks = parse_blocks( $template_content );
			$template_blocks = $this->extract_digicommerce_blocks( $blocks );
		}

		return $template_blocks;
	}

	/**
	 * Get current template content
	 */
	private function get_current_template_content() {
		// Try to get template content from various sources
		global $_wp_current_template_content;
		
		if ( ! empty( $_wp_current_template_content ) ) {
			return $_wp_current_template_content;
		}

		// Get template based on current context
		$template_slug = $this->get_current_template_slug();
		if ( $template_slug ) {
			// Check plugin templates first
			$plugin_template = DIGICOMMERCE_PLUGIN_DIR . 'templates/block-templates/' . $template_slug . '.html';
			if ( file_exists( $plugin_template ) ) {
				return file_get_contents( $plugin_template );
			}

			// Check theme templates
			$theme_template = get_stylesheet_directory() . '/templates/' . $template_slug . '.html';
			if ( file_exists( $theme_template ) ) {
				return file_get_contents( $theme_template );
			}
		}

		return '';
	}

	/**
	 * Get current template slug
	 */
	private function get_current_template_slug() {
		// Single product
		if ( is_singular( 'digi_product' ) ) {
			return 'single-digi_product';
		}

		// Product archive
		if ( is_post_type_archive( 'digi_product' ) ) {
			return 'archive-digi_product';
		}

		// Check specific DigiCommerce pages
		global $post;
		if ( $post && is_page() ) {
			$checkout_page_id = DigiCommerce()->get_option( 'checkout_page_id' );
			$success_page_id = DigiCommerce()->get_option( 'payment_success_page_id' );

			if ( $checkout_page_id && $post->ID == $checkout_page_id ) {
				return 'page-checkout';
			}

			if ( $success_page_id && $post->ID == $success_page_id ) {
				return 'page-payment-success';
			}
		}

		return false;
	}

	/**
	 * Get blocks based on page context (fallback)
	 */
	private function get_blocks_by_page_context() {
		$blocks = array();

		// Single product page
		if ( is_singular( 'digi_product' ) ) {
			$blocks = array(
				'product-title',
				'product-price',
				'product-description',
				'product-content',
				'product-gallery',
				'product-features',
				'product-meta',
				'product-share',
				'add-to-cart',
			);
		}

		// Product archive
		elseif ( is_post_type_archive( 'digi_product' ) ) {
			$blocks = array(
				'products-grid',
				'products-sorting',
				'products-filters',
			);
		}

		elseif ( DigiCommerce()->is_payment_success_page() ) {
			$blocks = array(
				'success-message',
				'order-receipt',
				'order-details',
			);
		}

		return $blocks;
	}

	/**
	 * Enqueue editor assets
	 */
	public function enqueue_editor_assets() {
		// First enqueue the globals script
		wp_enqueue_script(
			'digicommerce-globals',
			DIGICOMMERCE_PLUGIN_URL . 'assets/js/blocks/globals.js',
			array(
				'wp-blocks',
				'wp-i18n',
				'wp-element',
				'wp-editor',
				'wp-components',
				'wp-data',
				'wp-block-editor',
				'wp-hooks',
			),
			DIGICOMMERCE_VERSION,
			true
		);

		// Enqueue individual block scripts with dependencies
		$this->enqueue_block_scripts();
	}

	/**
	 * Enqueue block scripts with proper dependencies
	 */
	private function enqueue_block_scripts() {
		$build_dir = DIGICOMMERCE_PLUGIN_DIR . 'assets/blocks/';

		if ( is_dir( $build_dir ) ) {
			$block_folders = array_filter( glob( $build_dir . '*' ), 'is_dir' );

			foreach ( $block_folders as $block_folder ) {
				$block_name = basename( $block_folder );
				$script_path = $block_folder . '/index.js';
				$script_handle = 'digicommerce-' . $block_name;

				if ( file_exists( $script_path ) && ! wp_script_is( $script_handle, 'enqueued' ) ) {
					wp_enqueue_script(
						$script_handle,
						DIGICOMMERCE_PLUGIN_URL . 'assets/blocks/' . $block_name . '/index.js',
						array( 'digicommerce-globals' ),
						DIGICOMMERCE_VERSION,
						true
					);
				}
			}
		}
	}

	/**
	 * Get products for selector
	 */
	private function get_products_for_selector() {
		$products = get_posts(
			array(
				'post_type'      => 'digi_product',
				'posts_per_page' => -1,
				'post_status'    => 'publish',
			)
		);

		return array_map(
			function ( $product ) {
				return array(
					'value'      => $product->ID,
					'label'      => $product->post_title,
					'price'      => get_post_meta( $product->ID, 'digi_price', true ),
					'sale_price' => get_post_meta( $product->ID, 'digi_sale_price', true ),
					'variations' => get_post_meta( $product->ID, 'digi_price_variations', true ),
				);
			},
			$products
		);
	}
}

// Initialize blocks.
DigiCommerce_Blocks::instance();