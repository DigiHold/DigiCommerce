<?php
defined( 'ABSPATH' ) || exit;

/**
 * DigiCommerce Theme Compatibility for Block Themes
 */
class DigiCommerce_Theme_Compatibility {

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
		// Register templates
		add_action( 'init', [ $this, 'register_plugin_templates' ] );

		// Render block templates for DigiCommerce pages
		add_action( 'template_redirect', [ $this, 'render_block_template' ], 10 );

		// Provide template content from the plugin if not overridden in the theme
		add_filter( 'pre_get_block_file_template', [ $this, 'get_block_file_template' ], 10, 3 );

		// Add DigiCommerce templates to the Site Editor
		add_filter( 'get_block_templates', [ $this, 'add_digicommerce_templates' ], 10, 3 );
	}

	/**
	 * Singleton instance access.
	 */
	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Register DigiCommerce block templates with WordPress.
	 */
	public function register_plugin_templates() {
		if ( ! function_exists( 'register_block_template' ) ) {
			return;
		}

		foreach ( $this->get_registered_template_slugs() as $slug ) {
			$info = $this->get_template_info( $slug );
			$file = $this->get_template_file_path( $slug );

			if ( ! $info || ! file_exists( $file ) ) {
				continue;
			}

			$content = file_get_contents( $file );
			if ( empty( $content ) ) {
				continue;
			}

			register_block_template(
				'digicommerce//' . $slug,
				[
					'title'       => $info['title'],
					'description' => $info['description'],
					'post_types'  => $info['post_types'],
					'content'     => $content,
					'type'        => 'wp_template',
				]
			);
		}
	}

	/**
	 * Renders the correct template content for DigiCommerce pages,
	 * bypassing the theme if needed.
	 */
	public function render_block_template() {
		if ( is_embed() || is_admin() ) {
			return;
		}

		$template_slug = $this->get_current_template_slug();
		if ( ! $template_slug || $this->theme_has_template( $template_slug ) ) {
			return;
		}

		$template_id = get_stylesheet() . '//' . $template_slug;
		$block_template = get_block_template( $template_id, 'wp_template' );
		if ( ! $block_template ) {
			return;
		}

		global $_wp_current_template_content, $_wp_current_template_id;
		$_wp_current_template_content = $block_template->content;
		$_wp_current_template_id      = $template_id;

		add_action( 'wp_head', '_block_template_viewport_meta_tag', 0 );
		remove_action( 'wp_head', '_wp_render_title_tag', 1 );
		add_action( 'wp_head', '_block_template_render_title_tag', 1 );

		// Ensure proper query flags for single product views.
		global $wp_query;
		if ( is_singular( 'digi_product' ) ) {
			$wp_query->is_single     = true;
			$wp_query->is_singular   = true;
			$wp_query->is_home       = false;
			$wp_query->is_page       = false;
			$wp_query->is_posts_page = false;
		}

		include ABSPATH . WPINC . '/template-canvas.php';
		exit;
	}

	/**
	 * Provides template content from the plugin if it’s not overridden in the theme.
	 */
	public function get_block_file_template( $template, $id, $template_type ) {
		if ( 'wp_template' !== $template_type ) {
			return $template;
		}

		$template_name_parts = explode( '//', $id );
		if ( count( $template_name_parts ) < 2 ) {
			return $template;
		}

		list( $theme_slug, $template_slug ) = $template_name_parts;

		if ( ! $this->is_digicommerce_template( $template_slug ) || $this->theme_has_template( $template_slug ) ) {
			return $template;
		}

		$template_file = $this->get_template_file_path( $template_slug );
		if ( ! file_exists( $template_file ) ) {
			return $template;
		}

		$content = file_get_contents( $template_file );
		if ( empty( $content ) ) {
			return $template;
		}

		$template_info = $this->get_template_info( $template_slug );
		if ( ! $template_info ) {
			return $template;
		}

		return $this->create_template_object( $id, $template_slug, $template_info, $content );
	}

	/**
	 * Adds DigiCommerce templates to the Site Editor list.
	 */
	public function add_digicommerce_templates( $query_result, $query, $template_type ) {
		if ( 'wp_template' !== $template_type ) {
			return $query_result;
		}

		$theme_slug = get_stylesheet();
		foreach ( $this->get_registered_template_slugs() as $template_slug ) {
			if ( $this->template_exists_in_query( $query_result, $template_slug ) ) {
				continue;
			}

			if ( ! $this->template_matches_query_filters( $template_slug, $query ) ) {
				continue;
			}

			if ( $this->theme_has_template( $template_slug ) ) {
				continue;
			}

			$template_info = $this->get_template_info( $template_slug );
			$template_file = $this->get_template_file_path( $template_slug );

			if ( ! $template_info || ! file_exists( $template_file ) ) {
				continue;
			}

			$content = file_get_contents( $template_file );
			if ( empty( $content ) ) {
				continue;
			}

			$template_id     = $theme_slug . '//' . $template_slug;
			$plugin_template = $this->create_template_object( $template_id, $template_slug, $template_info, $content );
			if ( $plugin_template ) {
				$query_result[] = $plugin_template;
			}
		}

		return $query_result;
	}

	/**
	 * Determines which template slug to use based on the current view context.
	 */
	private function get_current_template_slug() {
		if ( is_singular( 'digi_product' ) ) {
			return 'single-digi_product';
		}

		if (
			is_post_type_archive( 'digi_product' ) ||
			( is_tax() && is_tax( get_object_taxonomies( 'digi_product' ) ) )
		) {
			return 'archive-digi_product';
		}

		global $post;
		if ( $post && is_page() ) {
			$success_page_id  = DigiCommerce()->get_option( 'payment_success_page_id' );

			if ( $post->ID == $success_page_id ) {
				return 'page-payment-success';
			}
		}

		return false;
	}

	/**
	 * Returns all slugs of templates provided by DigiCommerce.
	 */
	private function get_registered_template_slugs() {
		return [
			'single-digi_product',
			'archive-digi_product',
			'page-payment-success',
		];
	}

	/**
	 * Checks if a given slug belongs to a DigiCommerce template.
	 */
	private function is_digicommerce_template( $template_slug ) {
		return in_array( $template_slug, $this->get_registered_template_slugs(), true );
	}

	/**
	 * Checks whether a theme file overrides a given template.
	 */
	private function theme_has_template( $template_slug ) {
		$paths = [
			get_stylesheet_directory() . "/templates/{$template_slug}.html",
			get_template_directory()   . "/templates/{$template_slug}.html",
		];

		foreach ( $paths as $path ) {
			if ( file_exists( $path ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Returns the full file path to the plugin’s block template.
	 */
	private function get_template_file_path( $template_slug ) {
		return DIGICOMMERCE_PLUGIN_DIR . 'templates/block-templates/' . $template_slug . '.html';
	}

	/**
	 * Returns metadata about a DigiCommerce template.
	 */
	private function get_template_info( $template_slug ) {
		$templates = [
			'single-digi_product' => [
				'title'       => __( 'Single Product', 'digicommerce' ),
				'description' => __( 'Template for single DigiCommerce product', 'digicommerce' ),
				'post_types'  => [ 'digi_product' ],
			],
			'archive-digi_product' => [
				'title'       => __( 'Product Archive', 'digicommerce' ),
				'description' => __( 'Template for DigiCommerce product archive', 'digicommerce' ),
				'post_types'  => [ 'digi_product' ],
			],
			'page-payment-success' => [
				'title'       => __( 'Payment Success', 'digicommerce' ),
				'description' => __( 'Template for payment success page', 'digicommerce' ),
				'post_types'  => [ 'page' ],
			],
		];

		return $templates[ $template_slug ] ?? false;
	}

	/**
	 * Checks if a template already exists in the current query result.
	 */
	private function template_exists_in_query( $query_result, $template_slug ) {
		foreach ( $query_result as $template ) {
			if ( $template->slug === $template_slug ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Checks if a template matches the query filters (like slug__in or slug__not_in).
	 */
	private function template_matches_query_filters( $template_slug, $query ) {
		if ( isset( $query['slug__not_in'] ) && in_array( $template_slug, $query['slug__not_in'], true ) ) {
			return false;
		}
		if ( isset( $query['slug__in'] ) && ! in_array( $template_slug, $query['slug__in'], true ) ) {
			return false;
		}
		return true;
	}

	/**
	 * Builds a WP_Block_Template object from file content and metadata.
	 */
	private function create_template_object( $id, $slug, $info, $content ) {
		$template = new WP_Block_Template();
		$template->id              = $id;
		$template->theme           = get_stylesheet();
		$template->slug            = $slug;
		$template->type            = 'wp_template';
		$template->title           = $info['title'];
		$template->description     = $info['description'];
		$template->content         = $content;
		$template->source          = 'plugin';
		$template->origin          = 'plugin';
		$template->status          = 'publish';
		$template->has_theme_file  = false;
		$template->is_custom       = false;
		$template->area            = 'uncategorized';
		$template->author          = 'DigiCommerce';
		$template->post_types      = $info['post_types'];
		$template->wp_id           = null;
		$template->modified        = null;

		return $template;
	}
}