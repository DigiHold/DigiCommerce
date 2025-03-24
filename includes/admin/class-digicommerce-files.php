<?php
/**
 * DigiCommerce Files Handler
 *
 * Handles secure file uploads, downloads, and management for digital products
 */
class DigiCommerce_Files {
	/**
	 * Singleton instance
	 *
	 * @var DigiCommerce_Files
	 */
	private static $instance = null;

	/**
	 * Upload directory path
	 *
	 * @var string
	 */
	private $upload_dir;

	/**
	 * Token expiry time in seconds
	 *
	 * @var int
	 */
	private $token_expiry = 3600; // 1 hour token validity

	/**
	 * Chunk size for file streaming
	 *
	 * @var int
	 */
	private $chunk_size = 1024 * 1024; // 1MB chunks for better memory management

	/**
	 * Cache group for file-related data
	 *
	 * @var string
	 */
	private $cache_group = 'digicommerce_files';

	/**
	 * S3 handler instance
	 *
	 * @var DigiCommerce_Pro_S3
	 */
	private static $product_file_cache = array();

	/**
	 * S3 handler instance
	 *
	 * @var DigiCommerce_Pro_S3
	 */
	private $s3;

	/**
	 * Pro status
	 *
	 * @var bool
	 */
	private $pro;

	/**
	 * Constructor
	 */
	private function __construct() {
		$wp_upload_dir    = wp_upload_dir();
		$this->upload_dir = trailingslashit( $wp_upload_dir['basedir'] ) . 'digicommerce-files';

		add_action( 'init', array( $this, 'register_download_endpoint' ), 1 );
		add_filter( 'query_vars', array( $this, 'add_query_vars' ) );

		// Register hooks with appropriate priority
		add_action( 'wp_ajax_digicommerce_download_token', array( $this, 'download_token' ) );
		add_action( 'wp_ajax_nopriv_digicommerce_download_token', array( $this, 'download_token' ) );
		add_action( 'template_redirect', array( $this, 'handle_download_request' ), 1 );
		add_action( 'wp_ajax_digicommerce_upload_file', array( $this, 'handle_upload_ajax' ) );
		add_filter( 'digicommerce_before_remove_file', array( $this, 'delete_physical_file' ), 10, 2 );
		add_filter( 'upload_mimes', array( $this, 'add_svg_mime_type' ) );

		// Initialize object cache group
		wp_cache_add_non_persistent_groups( $this->cache_group );

		// Wait for plugins_loaded to initialize S3 and directory
		add_action( 'plugins_loaded', array( $this, 'init_s3' ), 20 );
	}

	/**
	 * Get an instance of this class
	 *
	 * @return DigiCommerce_Files
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Initialize S3 handler and directory after all plugins are loaded
	 */
	public function init_s3() {
		// Initialize pro status
		$this->pro = class_exists( 'DigiCommerce_Pro' );
		if ( $this->pro && class_exists( 'DigiCommerce_Pro_S3' ) ) {
			$this->s3 = DigiCommerce_Pro_S3::instance();
		}

		if ( ! $this->pro || ( $this->s3 && ! DigiCommerce()->get_option( 'enable_s3' ) ) ) {
			$this->initialize_directory();
		}
	}

	/**
	 * Register the download endpoint
	 */
	public function register_download_endpoint() {
		add_rewrite_rule(
			'^download/([^/]+)/?$',
			'index.php?digicommerce_download=$matches[1]',
			'top'
		);

		// Only flush rewrite rules once
		if ( ! get_option( 'digicommerce_rewrite_rules_flushed' ) ) {
			flush_rewrite_rules( false );
			update_option( 'digicommerce_rewrite_rules_flushed', true );
		}
	}

	/**
	 * Add custom query vars
	 *
	 * @param array $vars Query vars.
	 */
	public function add_query_vars( $vars ) {
		$vars[] = 'digicommerce_download';
		return $vars;
	}

	/**
	 * Initialize directory structure and protection files
	 */
	private function initialize_directory() {
		// Create main directory if it doesn't exist
		if ( ! file_exists( $this->upload_dir ) ) {
			wp_mkdir_p( $this->upload_dir );
		}

		// Create .htaccess file
		$htaccess_path = $this->upload_dir . '/.htaccess';
		if ( ! file_exists( $htaccess_path ) ) {
			$htaccess_content = '# Deny access to all files
<FilesMatch ".*">
    Order Deny,Allow
    Deny from all
</FilesMatch>

# Prevent directory listing
Options -Indexes

# Deny access to specific file types
<FilesMatch "\.(php|php\.|php3|php4|php5|phtml|pl|py|jsp|asp|htm|html|shtml|sh|cgi)$">
    Order Deny,Allow
    Deny from all
</FilesMatch>

# Additional security headers
<IfModule mod_headers.c>
    Header set X-Content-Type-Options "nosniff"
    Header set X-Frame-Options "DENY"
    Header set X-XSS-Protection "1; mode=block"
</IfModule>';

			file_put_contents( $htaccess_path, $htaccess_content ); // phpcs:ignore
		}

		// Create index.php
		$index_path = $this->upload_dir . '/index.php';
		if ( ! file_exists( $index_path ) ) {
			file_put_contents( $index_path, "<?php\n// Silence is golden." ); // phpcs:ignore
		}
	}

	/**
	 * AJAX handler for download token generation
	 */
	public function download_token() {
		check_ajax_referer( 'digicommerce_download_nonce', 'nonce' );

		$file_id     = isset( $_POST['file_id'] ) ? sanitize_text_field( $_POST['file_id'] ) : '';
		$order_id    = isset( $_POST['order_id'] ) ? intval( $_POST['order_id'] ) : 0;
		$order_token = isset( $_POST['order_token'] ) ? sanitize_text_field( $_POST['order_token'] ) : '';

		// Generate fresh download URL with new token
		$download_url = $this->generate_secure_download_url( $file_id, $order_id, false, $order_token );

		if ( ! $download_url ) {
			wp_send_json_error(
				array(
					'message' => esc_html__( 'Failed to generate download URL', 'digicommerce' ),
				)
			);
			return;
		}

		wp_send_json_success(
			array(
				'download_url' => $download_url,
				'message'      => esc_html__( 'Download starting...', 'digicommerce' ),
			)
		);
	}

	/**
	 * Handle download requests
	 */
	public function handle_download_request() {
		if ( ! get_query_var( 'digicommerce_download' ) ) {
			return;
		}

		try {
			$token = get_query_var( 'digicommerce_download' );

			if ( ! $token ) {
				wp_die( esc_html__( 'Unable to process download request.', 'digicommerce' ) );
				return;
			}

			// Get and validate token data
			$token_data = get_transient( 'digi_download_' . $token );

			if ( ! $token_data || ! is_array( $token_data ) ) {
				wp_die( esc_html__( 'Download link has expired. Please click the download button again.', 'digicommerce' ) );
				return;
			}

			// Check expiration
			if ( time() > $token_data['expires'] ) {
				delete_transient( 'digi_download_' . $token );
				wp_die( esc_html__( 'Download link has expired. Please click the download button again.', 'digicommerce' ) );
				return;
			}

			// Check access based on context
			if ( ! empty( $token_data['license_key'] ) ) {
				// License update context
				$license = DigiCommerce_Pro_License::instance()->get_license_by_key( $token_data['license_key'] );
				if ( ! $license || 'active' !== $license['status'] ) {
					wp_die( esc_html__( 'Invalid or inactive license.', 'digicommerce' ) );
					return;
				}
			} elseif ( ! empty( $token_data['order_token'] ) ) {
				// Thank you page context
				if ( ! DigiCommerce_Orders::instance()->verify_order_token( $token_data['order_id'], $token_data['order_token'] ) ) {
					wp_die( esc_html__( 'Invalid order access.', 'digicommerce' ) );
					return;
				}
			} elseif ( empty( $token_data['is_email'] ) ) {
				// Regular account page context
				if ( ! is_user_logged_in() || ! DigiCommerce_Orders::instance()->verify_order_access( $token_data['order_id'] ) ) {
					wp_die( esc_html__( 'Please log in to download your files.', 'digicommerce' ) );
					return;
				}
			}

			// Get and validate file path
			$file_path = $this->get_file_path( $token_data['file_id'] );

			if ( ! $file_path ) {
				delete_transient( 'digi_download_' . $token );
				wp_die( esc_html__( 'File not available. Please contact support.', 'digicommerce' ) );
				return;
			}

			// Handle the file download
			if ( $this->pro && $this->s3 && DigiCommerce()->get_option( 'enable_s3' ) ) {
				if ( ! $this->send_file( $file_path, true ) ) {
					wp_die( esc_html__( 'Unable to download file. Please try again.', 'digicommerce' ) );
					return;
				}
			} else { // phpcs:ignore
				if ( ! $this->send_file( $file_path, false ) ) {
					wp_die( esc_html__( 'File not available. Please contact support.', 'digicommerce' ) );
					return;
				}
			}

			// Log and cleanup after successful download
			$this->log_download( $token_data['file_id'], $token_data['order_id'], $token_data['user_id'] ?? 0 );
			delete_transient( 'digi_download_' . $token );
			exit;

		} catch ( Exception $e ) {
			wp_die( esc_html__( 'An error occurred. Please try again.', 'digicommerce' ) );
		}
	}

	/**
	 * Function to manually flush rewrite rules
	 */
	public function flush_rewrite_rules() {
		$this->register_download_endpoint();
		flush_rewrite_rules( false );
		update_option( 'digicommerce_rewrite_rules_flushed', true );
	}

	/**
	 * Get file path with caching
	 *
	 * @param string $file_id File ID.
	 */
	private function get_file_path( $file_id ) {
		try {
			// Get product ID
			$product_id = $this->get_product_by_file_id( $file_id );

			if ( ! $product_id ) {
				return false;
			}

			// Check variations first
			$variations = get_post_meta( $product_id, 'digi_price_variations', true );
			if ( ! empty( $variations ) && is_array( $variations ) ) {
				foreach ( $variations as $variation ) {
					if ( ! empty( $variation['files'] ) && is_array( $variation['files'] ) ) {
						foreach ( $variation['files'] as $file ) {
							if ( isset( $file['id'] ) && $file['id'] === $file_id && isset( $file['file'] ) ) {
								// Return only file name for AWS S3 and full path for local files
								if ( $this->pro && $this->s3 && DigiCommerce()->get_option( 'enable_s3' ) ) {
									$file_path = $file['file'];
								} else {
									$file_path = trailingslashit( $this->upload_dir ) . $file['file'];
								}
								return $file_path;
							}
						}
					}
				}
			}

			// Check regular files
			$files = get_post_meta( $product_id, 'digi_files', true );
			if ( ! empty( $files ) && is_array( $files ) ) {
				foreach ( $files as $file ) {
					if ( isset( $file['id'] ) && $file['id'] === $file_id && isset( $file['file'] ) ) {
						// Return only file name for AWS S3 and full path for local files
						if ( $this->pro && $this->s3 && DigiCommerce()->get_option( 'enable_s3' ) ) {
							$file_path = $file['file'];
						} else {
							$file_path = trailingslashit( $this->upload_dir ) . $file['file'];
						}
						return $file_path;
					}
				}
			}
			return false;

		} catch ( Exception $e ) {
			return false;
		}
	}

	/**
	 * Serve the update file directly
	 *
	 * @param string $license_key License key.
	 */
	public function serve_update_file( $license_key ) {
		if ( ! class_exists( 'DigiCommerce_Pro_License' ) ) {
			return new WP_Error( 'missing_license_class', esc_html__( 'License class not found.', 'digicommerce' ) );
		}

		// Get license
		$license = DigiCommerce_Pro_License::instance()->get_license_by_key( $license_key );
		if ( ! $license || 'active' !== $license['status'] ) {
			return new WP_Error( 'invalid_license', esc_html__( 'Invalid or inactive license.', 'digicommerce' ) );
		}

		// Get latest file
		$latest_file    = null;
		$latest_version = '1.0.0';

		// Check variations first
		$variations = get_post_meta( $license['product_id'], 'digi_price_variations', true );
		if ( ! empty( $variations ) && is_array( $variations ) ) {
			foreach ( $variations as $variation ) {
				if ( ! empty( $variation['files'] ) && is_array( $variation['files'] ) ) {
					foreach ( $variation['files'] as $file ) {
						if ( ! empty( $file['versions'] ) && is_array( $file['versions'] ) ) {
							foreach ( $file['versions'] as $version_info ) {
								if ( version_compare( $version_info['version'], $latest_version, '>' ) ) {
									$latest_version = $version_info['version'];
									$latest_file    = $file;
								}
							}
						}
					}
				}
			}
		}

		// Then check regular files
		$files = get_post_meta( $license['product_id'], 'digi_files', true );
		if ( ! empty( $files ) && is_array( $files ) ) {
			foreach ( $files as $file ) {
				if ( ! empty( $file['versions'] ) && is_array( $file['versions'] ) ) {
					foreach ( $file['versions'] as $version_info ) {
						if ( version_compare( $version_info['version'], $latest_version, '>' ) ) {
							$latest_version = $version_info['version'];
							$latest_file    = $file;
						}
					}
				}
			}
		}

		if ( ! $latest_file ) {
			return new WP_Error( 'no_update', esc_html__( 'No update file available.', 'digicommerce' ) );
		}

		// Get file path using DigiCommerce_Files class
		$file_path = $this->get_file_path( $latest_file['id'] );
		if ( ! $file_path ) {
			return new WP_Error( 'file_not_found', esc_html__( 'Update file not found.', 'digicommerce' ) );
		}

		// Make sure file exists
		if ( ! file_exists( $file_path ) ) {
			return new WP_Error( 'file_not_found', esc_html__( 'Update file not found.', 'digicommerce' ) );
		}

		// Get file size
		$size     = filesize( $file_path );
		$filename = basename( $file_path );

		// Send headers
		nocache_headers();
		header( 'Content-Type: application/zip' );
		header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
		header( 'Content-Length: ' . $size );
		header( 'X-Content-Type-Options: nosniff' );

		// Clear any output buffers
		while ( ob_get_level() ) {
			ob_end_clean();
		}

		// Open file and send it in chunks
		$fp = fopen( $file_path, 'rb' );

		// Make sure file was opened successfully
		if ( false === $fp ) {
			return new WP_Error( 'file_error', esc_html__( 'Unable to read update file.', 'digicommerce' ) );
		}

		// Disable max execution time
		set_time_limit( 0 );

		// Send file contents
		while ( ! feof( $fp ) ) {
			echo fread( $fp, 8192 ); // phpcs:ignore
			flush();
		}

		fclose( $fp ); // phpcs:ignore
		exit();
	}

	/**
	 * Handle AJAX file upload
	 */
	public function handle_upload_ajax() {
		$this->initialize_directory();

		check_ajax_referer( 'digicommerce_upload', 'upload_nonce' );

		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error(
				array(
					'message' => esc_html__( 'Permission denied', 'digicommerce' ),
				)
			);
		}

		if ( ! isset( $_FILES['file'] ) ) {
			wp_send_json_error(
				array(
					'message' => esc_html__( 'No file uploaded', 'digicommerce' ),
				)
			);
		}

		$result = $this->handle_upload( $_FILES['file'] );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( $result->get_error_message() );
		}

		wp_send_json_success( $result );
	}

	/**
	 * Handle file upload
	 *
	 * @param array $file File data.
	 */
	public function handle_upload( $file ) {
		if ( ! isset( $file['tmp_name'] ) || UPLOAD_ERR_OK !== $file['error'] ) {
			return new WP_Error( 'upload_error', esc_html__( 'File upload failed', 'digicommerce' ) );
		}

		// Basic security checks
		$allowed_types = array(
			'pdf',
			'doc',
			'docx',
			'xls',
			'xlsx',
			'txt',
			'zip',
			'rar',
			'7z',
			'jpg',
			'jpeg',
			'png',
			'gif',
			'svg',
			'mp4',
			'mp3',
			'wav',
		);

		$file_ext = strtolower( pathinfo( $file['name'], PATHINFO_EXTENSION ) );
		if ( ! in_array( $file_ext, $allowed_types ) ) {
			return new WP_Error( 'invalid_type', esc_html__( 'Invalid file type', 'digicommerce' ) );
		}

		// Generate unique ID and filename
		$file_id = wp_generate_uuid4();
		$year    = date( 'Y' ); // phpcs:ignore
		$month   = date( 'm' ); // phpcs:ignore

		// Create year/month directories if they don't exist
		$year_dir  = $this->upload_dir . '/' . $year;
		$month_dir = $year_dir . '/' . $month;

		if ( ! file_exists( $year_dir ) ) {
			wp_mkdir_p( $year_dir );
		}
		if ( ! file_exists( $month_dir ) ) {
			wp_mkdir_p( $month_dir );
		}

		$filename      = wp_unique_filename( $month_dir, $file['name'] );
		$relative_path = $year . '/' . $month . '/' . $filename;
		$full_path     = trailingslashit( $this->upload_dir ) . $relative_path;

		// If S3 is enabled, upload to S3
		if ( $this->pro && $this->s3 && DigiCommerce()->get_option( 'enable_s3' ) ) {
			$s3_key = 'digicommerce/' . $relative_path;

			// First move to temp location
			if ( ! move_uploaded_file( $file['tmp_name'], $full_path ) ) {
				return new WP_Error( 'move_error', __( 'Failed to move uploaded file', 'digicommerce' ) );
			}

			// Upload to S3
			$s3_url = $this->s3->upload_file( $full_path, $s3_key );
			if ( ! $s3_url ) {
				return new WP_Error( 's3_upload_error', __( 'Failed to upload file to S3', 'digicommerce' ) );
			}

			// Delete local file and cleanup empty directories
			@unlink( $full_path ); // phpcs:ignore
			$this->cleanup_empty_directories( $month_dir );
			$this->cleanup_empty_directories( $year_dir );

			return array(
				'id'   => $file_id,
				'name' => $file['name'],
				'file' => $s3_key,
				'type' => $file['type'],
				'size' => $file['size'],
				's3'   => true,
			);
		}

		// Regular local upload
		if ( ! move_uploaded_file( $file['tmp_name'], $full_path ) ) {
			return new WP_Error( 'move_error', __( 'Failed to move uploaded file', 'digicommerce' ) );
		}

		return array(
			'id'   => $file_id,
			'name' => $file['name'],
			'file' => $relative_path,
			'type' => $file['type'],
			'size' => $file['size'],
			's3'   => false,
		);
	}

	/**
	 * Clean up empty directories
	 *
	 * @param string $dir Directory path.
	 */
	private function cleanup_empty_directories( $dir ) {
		if ( is_dir( $dir ) ) {
			$files = array_diff( scandir( $dir ), array( '.', '..' ) );
			if ( empty( $files ) ) {
				@rmdir( $dir ); // phpcs:ignore
				return true;
			}
		}
		return false;
	}

	/**
	 * Optimized file serving with memory management
	 *
	 * @param string $file_path File path.
	 * @param bool   $is_s3     Is file on S3.
	 */
	private function send_file( $file_path, $is_s3 = false ) {
		if ( $is_s3 ) {
			try {
				$s3_result = $this->s3->get_object( $file_path );

				if ( ! $s3_result || ! isset( $s3_result['Body'] ) ) {
					return false;
				}

				// Get file info and send headers
				$filename  = basename( $file_path );
				$mime_type = $s3_result['ContentType'] ?? 'application/octet-stream';

				// Clean output buffer
				if ( ob_get_level() ) {
					ob_end_clean();
				}

				// Send headers
				nocache_headers();
				header( 'Content-Type: ' . $mime_type );
				header( 'Content-Disposition: attachment; filename="' . rawurlencode( $filename ) . '"' );
				if ( isset( $s3_result['ContentLength'] ) ) {
					header( 'Content-Length: ' . $s3_result['ContentLength'] );
				}
				header( 'X-Content-Type-Options: nosniff' );

				// Stream the file
				$body = $s3_result['Body'];
				if ( $body ) {
					$total_read = 0;
					while ( ! $body->eof() ) {
						$chunk       = $body->read( 8192 );
						$chunk_length = strlen( $chunk );
						$total_read   += $chunk_length;
						echo $chunk; // phpcs:ignore
						flush();
					}
				}

				return true;

			} catch ( Exception $e ) {
				return false;
			}
		}

		// Regular local file delivery
		if ( ! file_exists( $file_path ) ) {
			return false;
		}

		$size      = filesize( $file_path );
		$mime_type = $this->get_mime_type( $file_path );
		$filename  = basename( $file_path );

		// Support for range requests (resumable downloads)
		$range = isset( $_SERVER['HTTP_RANGE'] ) ? $this->get_range_header( $_SERVER['HTTP_RANGE'], $size ) : null;

		// Clean output buffer
		if ( ob_get_level() ) {
			ob_end_clean();
		}

		// Set headers
		nocache_headers();
		header( 'Content-Type: ' . $mime_type );
		header( 'Content-Disposition: attachment; filename="' . rawurlencode( $filename ) . '"' );
		header( 'Accept-Ranges: bytes' );

		if ( $range ) {
			header( 'HTTP/1.1 206 Partial Content' );
			header( 'Content-Length: ' . ( $range['end'] - $range['start'] + 1 ) );
			header( 'Content-Range: bytes ' . $range['start'] . '-' . $range['end'] . '/' . $size );
		} else {
			header( 'Content-Length: ' . $size );
		}

		// Security headers
		header( 'X-Content-Type-Options: nosniff' );
		header( 'X-Frame-Options: DENY' );

		// Open file in binary mode
		$handle = fopen( $file_path, 'rb' );

		if ( false === $handle ) {
			return false;
		}

		// Set time limit to 0 for large files
		set_time_limit( 0 );

		// Set initial position for range requests
		if ( $range ) {
			fseek( $handle, $range['start'] );
		}

		// Send file in chunks
		while ( ! feof( $handle ) ) {
			$buffer = fread( $handle, $this->chunk_size );
			echo $buffer; // phpcs:ignore
			flush();

			if ( connection_status() != CONNECTION_NORMAL ) {
				fclose( $handle );
				return false;
			}
		}

		fclose( $handle );
		return true;
	}

	/**
	 * Parse range header
	 *
	 * @param string $range_header Range header.
	 * @param int    $file_size    File size.
	 */
	private function get_range_header( $range_header, $file_size ) {
		$range       = array();
		$range_parts = explode( '=', $range_header );

		if ( count( $range_parts ) == 2 ) {
			$range_values = explode( '-', $range_parts[1] );

			if ( count( $range_values ) == 2 ) {
				$range['start'] = empty( $range_values[0] ) ? 0 : intval( $range_values[0] );
				$range['end']   = empty( $range_values[1] ) ? ( $file_size - 1 ) : intval( $range_values[1] );

				if ( $range['start'] < 0 || $range['end'] >= $file_size || $range['start'] > $range['end'] ) {
					return null;
				}

				return $range;
			}
		}

		return null;
	}

	/**
	 * Cached mime type detection
	 *
	 * @param string $file_path File path.
	 */
	private function get_mime_type( $file_path ) {
		$cache_key = 'mime_' . md5( $file_path );

		$mime_type = wp_cache_get( $cache_key, $this->cache_group );
		if ( false === $mime_type ) {
			$mime_type = mime_content_type( $file_path );
			wp_cache_set( $cache_key, $mime_type, $this->cache_group, 3600 );
		}

		return $mime_type;
	}

	/**
	 * Optimized product query (continued)
	 *
	 * @param string $file_id File ID.
	 */
	private function get_product_by_file_id( $file_id ) {
		global $wpdb;

		$cache_key = 'product_' . $file_id;

		// Check cache first
		$product_id = wp_cache_get( $cache_key, $this->cache_group );
		if ( false !== $product_id ) {
			return $product_id;
		}

		// Check both regular files and variation files
		$product_id = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT post_id 
            FROM {$wpdb->postmeta} 
            WHERE (meta_key = 'digi_files' OR meta_key = 'digi_price_variations')
            AND meta_value LIKE %s 
            LIMIT 1",
				'%' . $wpdb->esc_like( $file_id ) . '%'
			)
		);

		if ( $product_id ) {
			wp_cache_set( $cache_key, $product_id, $this->cache_group, 3600 );
		}

		return $product_id;
	}

	/**
	 * Log download attempt with rate limiting
	 *
	 * @param string $file_id  File ID.
	 * @param int    $order_id Order ID.
	 * @param int    $user_id  User ID.
	 */
	private function log_download( $file_id, $order_id, $user_id ) {
		$log = array(
			'file_id'    => $file_id,
			'order_id'   => $order_id,
			'user_id'    => $user_id,
			'ip'         => $this->get_client_ip(),
			'date'       => current_time( 'mysql' ),
			'user_agent' => $_SERVER['HTTP_USER_AGENT'],
		);

		// Rate limiting check
		$rate_limit_key = 'download_rate_' . $user_id . '_' . $file_id;
		$download_count = get_transient( $rate_limit_key );

		if ( false === $download_count ) {
			set_transient( $rate_limit_key, 1, HOUR_IN_SECONDS );
		} else {
			set_transient( $rate_limit_key, $download_count + 1, HOUR_IN_SECONDS );
		}

		// Get existing logs with cache check
		$cache_key     = 'download_logs_' . $order_id;
		$download_logs = wp_cache_get( $cache_key, $this->cache_group );

		if ( false === $download_logs ) {
			$download_logs = get_post_meta( $order_id, '_download_logs', true );
			if ( ! is_array( $download_logs ) ) {
				$download_logs = array();
			}
			wp_cache_set( $cache_key, $download_logs, $this->cache_group, 3600 );
		}

		// Add new log at the beginning
		array_unshift( $download_logs, $log );

		// Keep only last 100 logs
		$download_logs = array_slice( $download_logs, 0, 100 );

		// Update logs
		update_post_meta( $order_id, '_download_logs', $download_logs );
		wp_cache_set( $cache_key, $download_logs, $this->cache_group, 3600 );

		// Track total downloads
		$this->increment_download_count( $file_id );
	}

	/**
	 * Increment download count with caching
	 *
	 * @param string $file_id File ID.
	 */
	private function increment_download_count( $file_id ) {
		$cache_key = 'download_count_' . $file_id;
		$count     = wp_cache_get( $cache_key, $this->cache_group );

		if ( false === $count ) {
			$count = get_option( 'digi_download_count_' . $file_id, 0 );
		}

		++$count;
		wp_cache_set( $cache_key, $count, $this->cache_group, 3600 );
		update_option( 'digi_download_count_' . $file_id, $count, false );
	}

	/**
	 * Get client IP address securely
	 */
	private function get_client_ip() {
		$ip_headers = array(
			'HTTP_CF_CONNECTING_IP', // Cloudflare
			'HTTP_X_REAL_IP', // Nginx proxy
			'HTTP_CLIENT_IP',
			'HTTP_X_FORWARDED_FOR',
			'HTTP_X_FORWARDED',
			'HTTP_X_CLUSTER_CLIENT_IP',
			'HTTP_FORWARDED_FOR',
			'HTTP_FORWARDED',
			'REMOTE_ADDR',
		);

		foreach ( $ip_headers as $header ) {
			if ( ! empty( $_SERVER[ $header ] ) ) {
				$ip = explode( ',', $_SERVER[ $header ] );
				$ip = trim( reset( $ip ) );
				if ( filter_var( $ip, FILTER_VALIDATE_IP ) ) {
					return $ip;
				}
			}
		}

		return '0.0.0.0';
	}

	/**
	 * Delete physical file with cleanup
	 *
	 * @param bool  $result Result of previous deletion.
	 * @param array $file   File data.
	 */
	public function delete_physical_file( $result, $file ) {
		// If S3 is enabled, use S3 methods for deletion
		if ( $this->pro && $this->s3 && DigiCommerce()->get_option( 'enable_s3' ) ) {
			try {
				if ( ! isset( $file['file'] ) ) {
					return false;
				}

				$s3_deleted = $this->s3->delete_file( $file['file'] );
				if ( $s3_deleted ) {
					// Clear related caches
					$this->clear_file_caches( $file['id'] );
					return true;
				}
				return false;
			} catch ( Exception $e ) {
				return false;
			}
		}

		// If S3 is not enabled, handle local file deletion
		$this->initialize_directory();

		if ( empty( $file['file'] ) ) {
			return false;
		}

		$file_path = trailingslashit( $this->upload_dir ) . $file['file'];

		if ( file_exists( $file_path ) && is_file( $file_path ) ) {
			$deleted = @unlink( $file_path ); // phpcs:ignore

			if ( $deleted ) {
				// Clear related caches
				$this->clear_file_caches( $file['id'] );

				// Clean up empty directories
				$dir      = dirname( $file_path );
				$base_dir = rtrim( $this->upload_dir, '/' );

				while ( $dir !== $base_dir && is_dir( $dir ) ) {
					$files = array_diff( scandir( $dir ), array( '.', '..' ) );
					if ( count( $files ) === 0 ) {
						@rmdir( $dir ); // phpcs:ignore
						$dir = dirname( $dir );
					} else {
						break;
					}
				}

				return true;
			}
		}

		return false;
	}

	/**
	 * Clear all caches related to a file
	 *
	 * @param string $file_id File ID.
	 */
	private function clear_file_caches( $file_id ) {
		wp_cache_delete( 'file_path_' . $file_id, $this->cache_group );
		wp_cache_delete( 'product_' . $file_id, $this->cache_group );
		wp_cache_delete( 'download_count_' . $file_id, $this->cache_group );
		unset( self::$product_file_cache[ 'file_path_' . $file_id ] );
	}

	/**
	 * Add SVG to allowed MIME types
	 *
	 * @param array $mimes Allowed MIME types.
	 */
	public function add_svg_mime_type( $mimes ) {
		$mimes['svg'] = 'image/svg+xml';
		return $mimes;
	}

	/**
	 * Generate secure download URL
	 *
	 * @param string $file_id     File ID.
	 * @param int    $order_id    Order ID.
	 * @param bool   $is_email    Is email context.
	 * @param string $order_token Order token.
	 */
	public function generate_secure_download_url( $file_id, $order_id, $is_email = false, $order_token = null ) {
		// Generate token
		$token = bin2hex( random_bytes( 32 ) );

		// Prepare token data
		$token_data = array(
			'file_id'     => $file_id,
			'order_id'    => $order_id,
			'user_id'     => get_current_user_id(),
			'expires'     => time() + $this->token_expiry,
			'is_email'    => $is_email,
			'order_token' => $order_token,
		);

		// Store token
		$stored = set_transient( 'digi_download_' . $token, $token_data, $this->token_expiry );

		$url = home_url( "download/{$token}" );

		return $url;
	}
}
DigiCommerce_Files::instance();
