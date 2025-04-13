<?php
defined( 'ABSPATH' ) || exit;

/**
 * DigiCommerce Product Custom Post Type
 *
 * Handles product post type and stores product data in default WordPress meta tables.
 */
class DigiCommerce_Product {
	/**
	 * Instance of the class
	 *
	 * @var DigiCommerce_Product
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
	 * Constructor: Initialize hooks
	 */
	public function __construct() {
		// Register post type and taxonomies.
		add_action( 'init', array( $this, 'register_post_type' ) );
		add_action( 'init', array( $this, 'register_taxonomies' ) );
		add_action( 'init', array( $this, 'register_meta' ) );

		// Block editor assets.
		add_action( 'enqueue_block_editor_assets', array( $this, 'enqueue_block_editor_assets' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_styles' ) );

		// If CPT is not disabled.
		if ( ! DigiCommerce()->get_option( 'product_cpt' ) ) {
			// Enqueue.
			add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

			// Templates.
			add_filter( 'template_include', array( $this, 'load_archive_template' ) );
			add_filter( 'template_include', array( $this, 'load_single_template' ) );
		}

		// Register REST API endpoints.
		add_action( 'rest_api_init', array( $this, 'register_rest_routes' ) );
	}

	/**
	 * Register the Digital Product post type
	 */
	public function register_post_type() {
		$labels = array(
			'name'               => esc_html__( 'Digital Products', 'digicommerce' ),
			'singular_name'      => esc_html__( 'Digital Product', 'digicommerce' ),
			'menu_name'          => esc_html__( 'Products', 'digicommerce' ),
			'add_new'            => esc_html__( 'Add New', 'digicommerce' ),
			'add_new_item'       => esc_html__( 'Add New Product', 'digicommerce' ),
			'edit_item'          => esc_html__( 'Edit Product', 'digicommerce' ),
			'new_item'           => esc_html__( 'New Product', 'digicommerce' ),
			'view_item'          => esc_html__( 'View Product', 'digicommerce' ),
			'search_items'       => esc_html__( 'Search Products', 'digicommerce' ),
			'not_found'          => esc_html__( 'No products found', 'digicommerce' ),
			'not_found_in_trash' => esc_html__( 'No products found in Trash', 'digicommerce' ),
			'all_items'          => esc_html__( 'All Products', 'digicommerce' ),
			'archives'           => esc_html__( 'Product Archives', 'digicommerce' ),
		);

		$args = array(
			'labels'             => $labels,
			'public'             => true,
			'publicly_queryable' => true,
			'show_ui'            => true,
			'show_in_menu'       => true,
			'menu_position'      => 55,
			'menu_icon'          => 'dashicons-cloud-upload',
			'query_var'          => true,
			'rewrite'            => array( 'slug' => DigiCommerce()->get_option( 'product_slug', 'digital-product' ) ),
			'capability_type'    => 'post',
			'has_archive'        => true,
			'hierarchical'       => false,
			'supports'           => array(
				'title',
				'editor',
				'thumbnail',
				'excerpt',
				'custom-fields',
				'revisions',
				'author',
			),
			'show_in_rest'       => true,
		);

		register_post_type( 'digi_product', $args );
	}

	/**
	 * Register taxonomies for the Digital Product post type
	 */
	public function register_taxonomies() {
		// Product Categories
		$category_labels = array(
			'name'              => esc_html__( 'Product Categories', 'digicommerce' ),
			'singular_name'     => esc_html__( 'Category', 'digicommerce' ),
			'search_items'      => esc_html__( 'Search Categories', 'digicommerce' ),
			'all_items'         => esc_html__( 'All Categories', 'digicommerce' ),
			'parent_item'       => esc_html__( 'Parent Category', 'digicommerce' ),
			'parent_item_colon' => esc_html__( 'Parent Category:', 'digicommerce' ),
			'edit_item'         => esc_html__( 'Edit Category', 'digicommerce' ),
			'update_item'       => esc_html__( 'Update Category', 'digicommerce' ),
			'add_new_item'      => esc_html__( 'Add New Category', 'digicommerce' ),
			'new_item_name'     => esc_html__( 'New Category Name', 'digicommerce' ),
			'menu_name'         => esc_html__( 'Categories', 'digicommerce' ),
		);

		register_taxonomy(
			'digi_product_cat',
			'digi_product',
			array(
				'hierarchical'      => true,
				'labels'            => $category_labels,
				'show_ui'           => true,
				'show_admin_column' => true,
				'query_var'         => true,
				'show_in_rest'      => true,
				'rewrite'           => array( 'slug' => DigiCommerce()->get_option( 'product_cat_slug', 'digital-product-category' ) ),
			)
		);

		// Product Tags
		$tag_labels = array(
			'name'          => esc_html__( 'Product Tags', 'digicommerce' ),
			'singular_name' => esc_html__( 'Tag', 'digicommerce' ),
			'search_items'  => esc_html__( 'Search Tags', 'digicommerce' ),
			'all_items'     => esc_html__( 'All Tags', 'digicommerce' ),
			'edit_item'     => esc_html__( 'Edit Tag', 'digicommerce' ),
			'update_item'   => esc_html__( 'Update Tag', 'digicommerce' ),
			'add_new_item'  => esc_html__( 'Add New Tag', 'digicommerce' ),
			'new_item_name' => esc_html__( 'New Tag Name', 'digicommerce' ),
			'menu_name'     => esc_html__( 'Tags', 'digicommerce' ),
		);

		register_taxonomy(
			'digi_product_tag',
			'digi_product',
			array(
				'hierarchical'      => false,
				'labels'            => $tag_labels,
				'show_ui'           => true,
				'show_admin_column' => true,
				'query_var'         => true,
				'show_in_rest'      => true,
				'rewrite'           => array( 'slug' => DigiCommerce()->get_option( 'product_tag_slug', 'digital-product-tag' ) ),
			)
		);
	}

	/**
	 * Register meta fields
	 */
	public function register_meta() {
		register_post_meta(
			'digi_product',
			'digi_price_mode',
			array(
				'show_in_rest'  => true,
				'single'        => true,
				'type'          => 'string',
				'default'       => 'single',
				'auth_callback' => function () {
					return current_user_can( 'edit_posts' );
				},
			)
		);

		register_post_meta(
			'digi_product',
			'digi_price',
			array(
				'show_in_rest'  => true,
				'single'        => true,
				'type'          => 'number',
				'default'       => 0,
				'auth_callback' => function () {
					return current_user_can( 'edit_posts' );
				},
			)
		);

		register_post_meta(
			'digi_product',
			'digi_sale_price',
			array(
				'show_in_rest'  => true,
				'single'        => true,
				'type'          => 'number',
				'default'       => 0,
				'auth_callback' => function () {
					return current_user_can( 'edit_posts' );
				},
			)
		);

		register_post_meta(
			'digi_product',
			'digi_price_variations',
			array(
				'show_in_rest'  => array(
					'schema' => array(
						'type'  => 'array',
						'items' => array(
							'type'       => 'object',
							'properties' => array(
								'id'                      => array( 'type' => 'string' ),
								'name'                    => array( 'type' => 'string' ),
								'price'                   => array( 'type' => 'number' ),
								'salePrice'               => array(
									'type'    => array( 'number', 'null' ),
									'default' => null,
								),
								'files'                   => array(
									'type'  => 'array',
									'items' => array(
										'type'       => 'object',
										'properties' => array(
											'name'     => array( 'type' => 'string' ),
											'file'     => array( 'type' => 'string' ),
											'id'       => array( 'type' => 'string' ),
											'type'     => array( 'type' => 'string' ),
											'size'     => array( 'type' => 'integer' ),
											'itemName' => array( 'type' => 'string' ),
											'versions' => array(
												'type'  => 'array',
												'items' => array(
													'type' => 'object',
													'properties' => array(
														'version' => array( 'type' => 'string' ),
														'changelog' => array( 'type' => 'string' ),
														'release_date' => array( 'type' => 'string' ),
													),
												),
											),
										),
									),
								),
								'isDefault'               => array( 'type' => 'boolean' ),
								'subscription_enabled'    => array(
									'type'    => 'boolean',
									'default' => false,
								),
								'subscription_period'     => array(
									'type'    => 'string',
									'default' => 'month',
								),
								'subscription_free_trial' => array(
									'type'       => 'object',
									'properties' => array(
										'duration' => array( 'type' => 'integer' ),
										'period'   => array( 'type' => 'string' ),
									),
								),
								'subscription_signup_fee' => array(
									'type'    => 'number',
									'default' => 0,
								),
								'license_enabled'         => array(
									'type'    => 'boolean',
									'default' => false,
								),
								'license_limit'           => array(
									'type'    => 'integer',
									'default' => 1,
								),
							),
						),
					),
				),
				'single'        => true,
				'type'          => 'array',
				'default'       => array(),
				'auth_callback' => function () {
					return current_user_can( 'edit_posts' );
				},
			)
		);

		register_post_meta(
			'digi_product',
			'digi_files',
			array(
				'show_in_rest'  => array(
					'schema' => array(
						'type'  => 'array',
						'items' => array(
							'type'       => 'object',
							'properties' => array(
								'name'     => array( 'type' => 'string' ),
								'file'     => array( 'type' => 'string' ),
								'id'       => array( 'type' => 'string' ),
								'type'     => array( 'type' => 'string' ),
								'size'     => array( 'type' => 'integer' ),
								'itemName' => array( 'type' => 'string' ),
								'versions' => array(
									'type'  => 'array',
									'items' => array(
										'type'       => 'object',
										'properties' => array(
											'version'      => array( 'type' => 'string' ),
											'changelog'    => array( 'type' => 'string' ),
											'release_date' => array( 'type' => 'string' ),
										),
									),
								),
							),
						),
					),
				),
				'single'        => true,
				'type'          => 'array',
				'default'       => array(),
				'auth_callback' => function () {
					return current_user_can( 'edit_posts' );
				},
			)
		);

		register_post_meta(
			'digi_product',
			'digi_gallery',
			array(
				'show_in_rest'  => array(
					'schema' => array(
						'type'  => 'array',
						'items' => array(
							'type'       => 'object',
							'properties' => array(
								'id'  => array( 'type' => 'number' ),
								'url' => array( 'type' => 'string' ),
								'alt' => array( 'type' => 'string' ),
							),
						),
					),
				),
				'single'        => true,
				'type'          => 'array',
				'default'       => array(),
				'auth_callback' => function () {
					return current_user_can( 'edit_posts' );
				},
			)
		);

		register_post_meta(
			'digi_product',
			'digi_product_description',
			array(
				'show_in_rest'  => true,
				'single'        => true,
				'type'          => 'string',
				'default'       => '',
				'auth_callback' => function () {
					return current_user_can( 'edit_posts' );
				},
			)
		);

		register_post_meta(
			'digi_product',
			'digi_features',
			array(
				'show_in_rest'  => array(
					'schema' => array(
						'type'  => 'array',
						'items' => array(
							'type'       => 'object',
							'properties' => array(
								'name' => array( 'type' => 'string' ),
								'text' => array( 'type' => 'string' ),
							),
						),
					),
				),
				'single'        => true,
				'type'          => 'array',
				'default'       => array(),
				'auth_callback' => function () {
					return current_user_can( 'edit_posts' );
				},
			)
		);

		register_post_meta(
			'digi_product',
			'digi_instructions',
			array(
				'show_in_rest'  => true,
				'single'        => true,
				'type'          => 'string',
				'default'       => '',
				'auth_callback' => function () {
					return current_user_can( 'edit_posts' );
				},
			)
		);

		if ( class_exists( 'DigiCommerce_Pro' ) &&
			class_exists( 'DigiCommerce_Pro_License' ) &&
			DigiCommerce()->get_option( 'enable_license', false ) ) {
			register_post_meta(
				'digi_product',
				'digi_upgrade_paths',
				array(
					'show_in_rest'  => array(
						'schema' => array(
							'type'  => 'array',
							'items' => array(
								'type'       => 'object',
								'properties' => array(
									'product_id'      => array( 'type' => 'string' ),
									'variation_id'    => array( 'type' => 'string' ),
									'prorate'         => array( 'type' => 'boolean' ),
									'include_coupon'  => array( 'type' => 'boolean' ),
									'discount_type'   => array( 'type' => 'string' ),
									'discount_amount' => array( 'type' => 'string' ),
								),
							),
						),
					),
					'single'        => true,
					'type'          => 'array',
					'default'       => array(),
					'auth_callback' => function () {
						return current_user_can( 'edit_posts' );
					},
				)
			);

			register_post_meta(
				'digi_product',
				'digi_api_data',
				array(
					'show_in_rest'  => array(
						'schema' => array(
							'type'       => 'object',
							'properties' => array(
								'homepage'       => array( 'type' => 'string' ),
								'author'         => array( 'type' => 'string' ),
								'contributors'   => array(
									'type'  => 'array',
									'items' => array(
										'type'       => 'object',
										'properties' => array(
											'username' => array( 'type' => 'string' ),
											'avatar'   => array( 'type' => 'string' ),
											'name'     => array( 'type' => 'string' ),
										),
									),
								),
								'requires'       => array( 'type' => 'string' ),
								'requires_php'   => array( 'type' => 'string' ),
								'tested'         => array( 'type' => 'string' ),
								'description'    => array( 'type' => 'string' ),
								'installation'   => array( 'type' => 'string' ),
								'changelog'      => array( 'type' => 'string' ),
								'upgrade_notice' => array( 'type' => 'string' ),
								'icons'          => array(
									'type'       => 'object',
									'properties' => array(
										'default' => array( 'type' => 'string' ),
									),
								),
								'banners'        => array(
									'type'       => 'object',
									'properties' => array(
										'low'  => array( 'type' => 'string' ),
										'high' => array( 'type' => 'string' ),
									),
								),
							),
						),
					),
					'single'        => true,
					'type'          => 'object',
					'default'       => array(),
					'auth_callback' => function () {
						return current_user_can( 'edit_posts' );
					},
				)
			);
		}
	}

	/**
	 * Enqueue block editor assets
	 */
	public function enqueue_block_editor_assets() {
		$screen = get_current_screen();

		if ( ! $screen || 'digi_product' !== $screen->post_type ) {
			return;
		}

		wp_enqueue_script(
			'digi-sidebar',
			DIGICOMMERCE_PLUGIN_URL . 'assets/js/blocks/sidebar.js',
			array(
				'wp-i18n',
				'wp-plugins',
				'wp-element',
				'wp-components',
				'wp-data',
				'wp-editor',
				'wp-block-editor',
			),
			DIGICOMMERCE_VERSION,
			true
		);

		// Pro plugin features.
		$pro_active      = class_exists( 'DigiCommerce_Pro' );
		$s3_enabled      = $pro_active && class_exists( 'DigiCommerce_Pro_S3' ) && DigiCommerce()->get_option( 'enable_s3', false );
		$license_enabled = $pro_active && class_exists( 'DigiCommerce_Pro_License' ) && DigiCommerce()->get_option( 'enable_license', false );

		// Add localization for file uploads.
		wp_localize_script(
			'digi-sidebar',
			'digicommerceVars',
			array(
				'ajaxurl'          => admin_url( 'admin-ajax.php' ),
				'upload_nonce'     => wp_create_nonce( 'digicommerce_upload' ),
				'delete_nonce'     => wp_create_nonce( 'wp_rest' ),
				'download_nonce'   => wp_create_nonce( 'digicommerce_download_nonce' ),
				'pro_active'       => $pro_active,
				's3_enabled'       => $s3_enabled,
				'license_enabled'  => $license_enabled,
				'i18n'             => array(
					'downloading'      => esc_html__( 'Downloading...', 'digicommerce' ),
					'download_failed'  => esc_html__( 'Download failed. Please try again.', 'digicommerce' ),
					's3_upload_failed' => esc_html__( 'Upload to Amazon S3 failed. Please check your connection.', 'digicommerce' ),
					's3_uploading'     => esc_html__( 'Uploading to Amazon S3...', 'digicommerce' ),
				),
				'checkout_page_id' => DigiCommerce()->get_option( 'checkout_page_id', '' ),
				'checkout_url'     => get_permalink( DigiCommerce()->get_option( 'checkout_page_id', '' ) ),
			)
		);
	}

	/**
	 * Enqueue admin styles
	 *
	 * @param string $hook The current admin page.
	 */
	public function enqueue_admin_styles( $hook ) {
		global $post_type;

		if ( 'digi_product' === $post_type ) {
			wp_enqueue_style(
				'digi-sidebar',
				DIGICOMMERCE_PLUGIN_URL . 'assets/css/blocks/sidebar.css',
				array(),
				DIGICOMMERCE_VERSION
			);
		}
	}

	/**
	 * Enqueue scripts and styles
	 */
	public function enqueue_scripts() {
		if ( is_singular( 'digi_product' ) ) {
			wp_enqueue_script(
				'photoswipe',
				DIGICOMMERCE_PLUGIN_URL . 'assets/js/vendor/photoswipe.min.js',
				array(),
				'5.4.4',
				true
			);

			wp_enqueue_script(
				'photoswipe-lightbox',
				DIGICOMMERCE_PLUGIN_URL . 'assets/js/vendor/photoswipe-lightbox.min.js',
				array( 'photoswipe' ),
				'5.4.4',
				true
			);

			wp_enqueue_style(
				'photoswipe-style',
				DIGICOMMERCE_PLUGIN_URL . 'assets/css/vendor/photoswipe.css',
				array(),
				'5.4.4',
			);

			wp_enqueue_script(
				'digicommerce-scripts',
				DIGICOMMERCE_PLUGIN_URL . 'assets/js/front/lightbox.js',
				array( 'photoswipe' ),
				DIGICOMMERCE_VERSION,
				true
			);
		}
	}

	/**
	 * Get currency symbol from currency code
	 *
	 * @param string $currency_code Currency code.
	 */
	public function get_currency_symbol( $currency_code ) {
		$currencies = DigiCommerce()->get_currencies();
		return isset( $currencies[ $currency_code ] ) ? $currencies[ $currency_code ]['symbol'] : $currency_code;
	}

	/**
	 * Format price with currency
	 *
	 * @param float  $price Price to format.
	 * @param string $class CSS class to add to the price.
	 * @param bool   $plain Whether to return plain price without HTML.
	 */
	public function format_price( $price, $class = '', $plain = false ) {
		// Get settings if not provided
		$currency          = DigiCommerce()->get_option( 'currency', 'USD' );
		$currency_position = DigiCommerce()->get_option( 'currency_position', 'left' );

		// Ensure the price is numeric
		$numeric_price = is_numeric( $price ) ? (float) $price : 0.0;

		// Format the price without forcing decimal places
		$formatted_price = $plain
			? number_format( $numeric_price, 2 )
			: '<span class="price">' . number_format( $numeric_price, 2 ) . '</span>';

		$currency_symbol = $plain
			? $this->get_currency_symbol( $currency )
			: '<span class="price-symbol">' . esc_html( $this->get_currency_symbol( $currency ) ) . '</span>';

		// Determine if we need to add gap class
		$needs_gap = in_array( $currency_position, array( 'left_space', 'right_space' ) );
		$gap_class = $needs_gap ? 'gap-1' : '';

		// Add wrapper class
		$wrapper_class = 'price-wrapper';
		if ( ! empty( $class ) ) {
			$wrapper_class .= ' ' . esc_attr( $class );
		}
		if ( ! empty( $gap_class ) ) {
			$wrapper_class .= ' ' . $gap_class;
		}

		// Combine currency and price based on position
		$combined_price = '';
		switch ( $currency_position ) {
			case 'left':
				$combined_price = $currency_symbol . $formatted_price;
				break;
			case 'left_space':
				$combined_price = $currency_symbol . $formatted_price;
				break;
			case 'right':
				$combined_price = $formatted_price . $currency_symbol;
				break;
			case 'right_space':
				$combined_price = $formatted_price . $currency_symbol;
				break;
			default:
				$combined_price = $currency_symbol . $formatted_price;
				break;
		}

		// Add wrapper span if plain is false
		if ( ! $plain ) {
			$combined_price = '<span class="' . $wrapper_class . '">' . $combined_price . '</span>';
		}

		return $combined_price;
	}

	/**
	 * Load custom template for product archives, categories, and tags
	 *
	 * @param string $template Template file to load.
	 */
	public function load_archive_template( $template ) {
		// Check if we're on a product archive, category, or tag page
		if ( is_post_type_archive( 'digi_product' ) ||
			is_tax( 'digi_product_cat' ) ||
			is_tax( 'digi_product_tag' )
		) {
			// Get archive template data
			$query_obj     = get_queried_object();
			$template_data = array(
				'is_archive'     => is_post_type_archive( 'digi_product' ),
				'is_tax'         => is_tax( 'digi_product_cat' ) || is_tax( 'digi_product_tag' ),
				'taxonomy'       => $query_obj instanceof WP_Term ? $query_obj->taxonomy : '',
				'term'           => $query_obj instanceof WP_Term ? $query_obj : null,
				'posts_per_page' => get_option( 'posts_per_page' ),
			);

			// Load the archive template
			return DigiCommerce()->get_template(
				'product-archive.php',
				$template_data
			);
		}
		return $template;
	}

	/**
	 * Use custom template for single digi_product posts
	 *
	 * @param string $template Template file to load.
	 */
	public function load_single_template( $template ) {
		if ( is_singular( 'digi_product' ) ) {
			$product_id = get_the_ID();

			// Get product metadata
			$price_mode_value = get_post_meta( $product_id, 'digi_price_mode', true );
			$price_mode       = $price_mode_value ? $price_mode_value : 'single';
			$single_price     = get_post_meta( $product_id, 'digi_price', true );
			$price_variations = get_post_meta( $product_id, 'digi_price_variations', true );
			$files            = get_post_meta( $product_id, 'digi_files', true );
			$has_files        = ! empty( $files ) && is_array( $files );

			// Include your template
			return DigiCommerce()->get_template(
				'single-product.php',
				array(
					'product_id'        => $product_id,
					'price_mode'        => $price_mode,
					'single_price'      => $single_price,
					'price_variations'  => $price_variations,
					'files'             => $files,
					'has_files'         => $has_files,
					'currency'          => DigiCommerce()->get_option( 'currency', 'USD' ),
					'currency_position' => DigiCommerce()->get_option( 'currency_position', 'left' ),
				)
			);
		}
		return $template;
	}

	/**
	 * Register REST API routes
	 */
	public function register_rest_routes() {
		register_rest_route(
			'wp/v2/digicommerce',
			'/delete-file',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'handle_file_deletion' ),
				'permission_callback' => function ( $request ) {
					// First check if user can edit posts
					if ( ! current_user_can( 'edit_posts' ) ) {
						return false;
					}

					// Then verify the nonce from the X-WP-Nonce header
					$nonce = $request->get_header( 'X-WP-Nonce' );
					return wp_verify_nonce( $nonce, 'wp_rest' );
				},
				'args'                => array(
					'file' => array(
						'required' => true,
						'type'     => 'object',
					),
				),
			)
		);
	}

	/**
	 * Handle file deletion request
	 *
	 * @param WP_REST_Request $request Request object.
	 */
	public function handle_file_deletion( $request ) {
		$file = $request->get_param( 'file' );
		if ( empty( $file ) ) {
			return new WP_Error( 'no_file', __( 'No file information provided', 'digicommerce' ) );
		}

		// If S3 is enabled, skip local filesystem check
		if ( DigiCommerce()->get_option( 'enable_s3', false ) ) {
			$deleted = apply_filters( 'digicommerce_before_remove_file', false, $file );

			if ( ! $deleted ) {
				return new WP_Error( 'delete_failed', __( 'Failed to delete the file from S3', 'digicommerce' ) );
			}

			return rest_ensure_response(
				array(
					'success' => true,
					'message' => esc_html__( 'File deleted successfully from S3', 'digicommerce' ),
					'status'  => 'deleted',
				)
			);
		}

		// For local storage, check if file exists in the filesystem
		$file_path = trailingslashit( wp_upload_dir()['basedir'] ) . 'digicommerce-files/' . $file['file'];

		// If the file doesn't exist physically, consider it already deleted
		if ( ! file_exists( $file_path ) ) {
			return rest_ensure_response(
				array(
					'success' => true,
					'message' => esc_html__( 'File already removed from server', 'digicommerce' ),
					'status'  => 'not_found',
				)
			);
		}

		// Attempt to delete the physical file
		$deleted = apply_filters( 'digicommerce_before_remove_file', false, $file );

		if ( ! $deleted && file_exists( $file_path ) ) {
			return new WP_Error( 'delete_failed', __( 'Failed to delete the file', 'digicommerce' ) );
		}

		return rest_ensure_response(
			array(
				'success' => true,
				'message' => esc_html__( 'File deleted successfully', 'digicommerce' ),
				'status'  => 'deleted',
			)
		);
	}
}
DigiCommerce_Product::instance();
