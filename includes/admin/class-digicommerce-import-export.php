<?php
defined( 'ABSPATH' ) || exit;

/**
 * Import/Export functionality for DigiCommerce
 */
class DigiCommerce_Import_Export {
	/**
	 * Instance of the class
	 *
	 * @var DigiCommerce_Import_Export
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
		// Add the menu item
		add_action( 'admin_menu', array( $this, 'add_import_export_menu' ), 99 );

		// Handle export action
		add_action( 'admin_post_digicommerce_export', array( $this, 'process_export' ) );
		add_action( 'admin_post_digicommerce_import', array( $this, 'process_import' ) );

		// Enqueue assets
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

		// Custom footer texts
		add_filter( 'admin_footer_text', array( $this, 'footer_text' ), 99 );
		add_filter( 'update_footer', array( $this, 'update_footer' ), 99 );

		// Add dir attr to HTML for LTR compatibility with Tailwind
		add_filter( 'language_attributes', array( $this, 'attribute_to_html' ) );
	}

	/**
	 * Add Import/Export submenu
	 */
	public function add_import_export_menu() {
		add_submenu_page(
			'digicommerce-settings', 
			__('Import/Export', 'digicommerce'), 
			__('Import/Export', 'digicommerce'), 
			'manage_options', 
			'digicommerce-import-export', 
			array($this, 'render_import_export_page')
		);
	}

	/**
	 * Render the Import/Export standalone page
	 */
	public function render_import_export_page() {
		if (!current_user_can('manage_options')) {
			return;
		}
		
		// Define help links
		$help = array();
	
		// Add 'pro' option only if DigiCommerce Pro not activated
		if (!class_exists('DigiCommerce_Pro')) {
			$help['pro'] = array(
				'title' => esc_html__('Upgrade to pro', 'digicommerce'),
				'svg'   => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 32" width="15" height="15" fill="#fff" class="default-transition"><path d="m2.8373 20.9773c-.6083-3.954-1.2166-7.9079-1.8249-11.8619-.1349-.8765.8624-1.4743 1.5718-.9422 1.8952 1.4214 3.7903 2.8427 5.6855 4.2641.624.468 1.513.3157 1.9456-.3333l4.7333-7.1c.5002-.7503 1.6026-.7503 2.1028 0l4.7333 7.1c.4326.649 1.3216.8012 1.9456.3333 1.8952-1.4214 3.7903-2.8427 5.6855-4.2641.7094-.5321 1.7067.0657 1.5719.9422-.6083 3.954-1.2166 7.9079-1.8249 11.8619z"></path><path d="m27.7902 27.5586h-23.5804c-.758 0-1.3725-.6145-1.3725-1.3725v-3.015h26.3255v3.015c-.0001.758-.6146 1.3725-1.3726 1.3725z"></path></svg>',
				'url'   => 'https://digicommerce.me/pricing',
			);
		}
	
		$help['support'] = array(
			'title' => esc_html__('Support', 'digicommerce'),
			'svg'   => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" width="15" height="15" fill="#fff" class="default-transition"><path d="M256 448c141.4 0 256-93.1 256-208S397.4 32 256 32S0 125.1 0 240c0 45.1 17.7 86.8 47.7 120.9c-1.9 24.5-11.4 46.3-21.4 62.9c-5.5 9.2-11.1 16.6-15.2 21.6c-2.1 2.5-3.7 4.4-4.9 5.7c-.6 .6-1 1.1-1.3 1.4l-.3 .3c0 0 0 0 0 0c0 0 0 0 0 0s0 0 0 0s0 0 0 0c-4.6 4.6-5.9 11.4-3.4 17.4c2.5 6 8.3 9.9 14.8 9.9c28.7 0 57.6-8.9 81.6-19.3c22.9-10 42.4-21.9 54.3-30.6c31.8 11.5 67 17.9 104.1 17.9zM169.8 149.3c7.9-22.3 29.1-37.3 52.8-37.3l58.3 0c34.9 0 63.1 28.3 63.1 63.1c0 22.6-12.1 43.5-31.7 54.8L280 248.4c-.2 13-10.9 23.6-24 23.6c-13.3 0-24-10.7-24-24l0-13.5c0-8.6 4.6-16.5 12.1-20.8l44.3-25.4c4.7-2.7 7.6-7.7 7.6-13.1c0-8.4-6.8-15.1-15.1-15.1l-58.3 0c-3.4 0-6.4 2.1-7.5 5.3l-.4 1.2c-4.4 12.5-18.2 19-30.6 14.6s-19-18.2-14.6-30.6l.4-1.2zM224 336a32 32 0 1 1 64 0 32 32 0 1 1 -64 0z"/></svg>',
			'url'   => 'https://digihold.me/my-account/',
		);
	
		$help['documentation'] = array(
			'title' => esc_html__('Documentation', 'digicommerce'),
			'svg'   => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" width="15" height="15" fill="#fff" class="default-transition"><path d="M0 32C0 14.3 14.3 0 32 0L96 0c17.7 0 32 14.3 32 32l0 64L0 96 0 32zm0 96l128 0 0 256L0 384 0 128zM0 416l128 0 0 64c0 17.7-14.3 32-32 32l-64 0c-17.7 0-32-14.3-32-32l0-64zM160 32c0-17.7 14.3-32 32-32l64 0c17.7 0 32 14.3 32 32l0 64L160 96l0-64zm0 96l128 0 0 256-128 0 0-256zm0 288l128 0 0 64c0 17.7-14.3 32-32 32l-64 0c-17.7 0-32-14.3-32-32l0-64zm203.6-19.9L320 232.6l0-89.9 100.4-26.9 66 247.4L363.6 396.1zM412.2 85L320 109.6 320 11l36.9-9.9c16.9-4.6 34.4 5.5 38.9 22.6L412.2 85zM371.8 427l122.8-32.9 16.3 61.1c4.5 17-5.5 34.5-22.5 39.1l-61.4 16.5c-16.9 4.6-34.4-5.5-38.9-22.6L371.8 427z"/></svg>',
			'url'   => 'https://docs.digicommerce.me/',
		);
		
		// Define allowed SVG tags
		$allowed_html = array(
			'svg'  => array(
				'xmlns'   => true,
				'viewbox' => true,
				'width'   => true,
				'height'  => true,
				'fill'    => true,
				'class'   => true,
			),
			'path' => array(
				'd'    => true,
				'fill' => true,
			),
		);
		
		// UTM parameters
		$utm_params = '?utm_source=WordPress&utm_medium=header&utm_campaign=digi';
		?>
		<div class="digicommerce">
			<div class="flex flex-col md:flex-row items-center justify-between gap-4 bg-dark-blue box-border ltr:-ml-5 rtl:-mr-5 px-8 py-6">
				<div class="digicommerce-logo">
					<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 2148.09 350" width="250" height="40.73">
						<g>
							<path d="M425.4756,249.9932V108.5933h69.6904c15.7559,0,29.624,2.8628,41.6123,8.585,11.9844,5.7256,21.3418,13.8369,28.0771,24.3408,6.7324,10.5039,10.1006,23.0283,10.1006,37.5718,0,14.6797-3.3682,27.3047-10.1006,37.875-6.7354,10.5732-16.0928,18.7197-28.0771,24.4424-11.9883,5.7256-25.8564,8.585-41.6123,8.585h-69.6904ZM473.1475,212.8252h19.998c6.7324,0,12.625-1.2783,17.6758-3.8379,5.0498-2.5566,8.9883-6.3633,11.8164-11.4131,2.8281-5.0508,4.2422-11.2109,4.2422-18.4834,0-7.1357-1.4141-13.1958-4.2422-18.1797-2.8281-4.9805-6.7666-8.7524-11.8164-11.312-5.0508-2.5566-10.9434-3.8379-17.6758-3.8379h-19.998v67.064Z" fill="#fff"/>
							<path d="M592.3252,249.9932V108.5933h47.6719v141.3999h-47.6719Z" fill="#fff"/>
							<path d="M736.3496,253.2246c-11.4473,0-21.9863-1.7861-31.6133-5.3525-9.6289-3.5664-17.9775-8.6514-25.0479-15.251-7.0693-6.5967-12.5586-14.4082-16.4629-23.4326-3.9072-9.0205-5.8574-18.9873-5.8574-29.8955s1.9502-20.8721,5.8574-29.896c3.9043-9.0205,9.4248-16.832,16.5645-23.4316,7.1357-6.5967,15.585-11.6816,25.3506-15.251,9.7627-3.5669,20.5029-5.353,32.2188-5.353,14.0049,0,26.4941,2.3574,37.4717,7.0698,10.9736,4.7153,20.0293,11.4478,27.1689,20.2002l-30.502,26.8657c-4.4443-5.1162-9.2607-8.9888-14.4434-11.6147-5.1855-2.626-10.9424-3.939-17.2705-3.939-5.252,0-9.999.8076-14.2412,2.4238s-7.8467,3.9736-10.8076,7.0698c-2.9629,3.0996-5.252,6.8018-6.8672,11.1104-1.6162,4.311-2.4248,9.2256-2.4248,14.7456,0,5.252.8086,10.0684,2.4248,14.4434,1.6152,4.377,3.9043,8.1143,6.8672,11.2109,2.9609,3.0986,6.4961,5.4883,10.6055,7.1709,4.1064,1.6855,8.7178,2.5244,13.8369,2.5244,5.3848,0,10.6367-.9082,15.7559-2.7266,5.1162-1.8184,10.5703-4.9492,16.3623-9.3936l26.6641,32.7246c-8.6201,5.792-18.4512,10.2354-29.4922,13.332-11.0439,3.0957-21.75,4.6455-32.1182,4.6455ZM756.5498,229.1865v-53.7314h41.4102v59.792l-41.4102-6.0605Z" fill="#fff"/>
							<path d="M818.3613,249.9932V108.5933h47.6719v141.3999h-47.6719Z" fill="#fff"/>
							<path d="M962.1826,253.2246c-11.3115,0-21.7842-1.7861-31.4111-5.3525-9.6289-3.5664-17.9775-8.6514-25.0479-15.251-7.0693-6.5967-12.5586-14.4082-16.4629-23.4326-3.9072-9.0205-5.8574-18.9873-5.8574-29.8955s1.9502-20.8721,5.8574-29.896c3.9043-9.0205,9.3936-16.832,16.4629-23.4316,7.0703-6.5967,15.4189-11.6816,25.0479-15.251,9.627-3.5669,20.0996-5.353,31.4111-5.353,13.8691,0,26.1592,2.4238,36.8652,7.272,10.7061,4.8477,19.5596,11.8516,26.5635,21.0078l-30.0986,26.8662c-4.1758-5.252-8.7871-9.3237-13.8369-12.2212-5.0498-2.894-10.7402-4.3428-17.0693-4.3428-4.9834,0-9.4932.8076-13.5332,2.4238s-7.5088,3.9736-10.4033,7.0698c-2.8975,3.0996-5.1514,6.8364-6.7666,11.2109-1.6162,4.3779-2.4248,9.2607-2.4248,14.645,0,5.3877.8086,10.2705,2.4248,14.6445,1.6152,4.3779,3.8691,8.1152,6.7666,11.2109,2.8945,3.0996,6.3633,5.4541,10.4033,7.0703s8.5498,2.4238,13.5332,2.4238c6.3291,0,12.0195-1.4453,17.0693-4.3428,5.0498-2.8945,9.6611-6.9688,13.8369-12.2207l30.0986,26.8662c-7.0039,9.0234-15.8574,15.9922-26.5635,20.9062-10.7061,4.915-22.9961,7.373-36.8652,7.373Z" fill="#fff"/>
							<path d="M1110.6504,253.2246c-11.583,0-22.2539-1.8174-32.0166-5.4541-9.7656-3.6357-18.2148-8.7861-25.3506-15.4521-7.1396-6.666-12.6953-14.5098-16.665-23.5332-3.9736-9.0205-5.959-18.8525-5.959-29.4922,0-10.772,1.9854-20.6353,5.959-29.5928,3.9697-8.9541,9.5254-16.7661,16.665-23.4321,7.1357-6.666,15.585-11.8169,25.3506-15.4531,9.7627-3.6357,20.3672-5.4536,31.8154-5.4536,11.5801,0,22.2197,1.8179,31.916,5.4536,9.6953,3.6362,18.1104,8.7871,25.25,15.4531,7.1357,6.666,12.6904,14.478,16.6641,23.4321,3.9707,8.9575,5.96,18.8208,5.96,29.5928,0,10.6396-1.9893,20.4717-5.96,29.4922-3.9736,9.0234-9.5283,16.8672-16.6641,23.5332-7.1396,6.666-15.5547,11.8164-25.25,15.4521-9.6963,3.6367-20.2695,5.4541-31.7148,5.4541ZM1110.4492,214.6426c4.4434,0,8.585-.8076,12.4229-2.4238s7.2021-3.9385,10.0996-6.9688c2.8945-3.0303,5.1514-6.7324,6.7676-11.1104,1.6152-4.374,2.4238-9.3232,2.4238-14.8467,0-5.52-.8086-10.4692-2.4238-14.8467-1.6162-4.3745-3.873-8.0801-6.7676-11.1104-2.8975-3.0298-6.2617-5.3525-10.0996-6.9688s-7.9795-2.4238-12.4229-2.4238-8.585.8076-12.4238,2.4238c-3.8379,1.6162-7.2051,3.939-10.0996,6.9688-2.8975,3.0303-5.1514,6.7358-6.7666,11.1104-1.6162,4.3774-2.4248,9.3267-2.4248,14.8467,0,5.5234.8086,10.4727,2.4248,14.8467,1.6152,4.3779,3.8691,8.0801,6.7666,11.1104,2.8945,3.0303,6.2617,5.3525,10.0996,6.9688,3.8389,1.6162,7.9795,2.4238,12.4238,2.4238Z" fill="#fff"/>
							<path d="M1207.6094,249.9932V108.5933h39.1885l56.5596,92.314h-20.6035l54.9434-92.314h39.1885l.4043,141.3999h-43.4307l-.4033-75.9521h6.8672l-37.5713,63.2256h-21.0078l-39.1885-63.2256h8.4844v75.9521h-43.4307Z" fill="#fff"/>
							<path d="M1400.3164,249.9932V108.5933h39.1885l56.5596,92.314h-20.6035l54.9434-92.314h39.1885l.4043,141.3999h-43.4307l-.4033-75.9521h6.8672l-37.5713,63.2256h-21.0078l-39.1885-63.2256h8.4844v75.9521h-43.4307Z" fill="#fff"/>
							<path d="M1639.8877,214.0371h70.7002v35.9561h-117.5645V108.5933h114.9385v35.9561h-68.0742v69.4878ZM1636.6562,161.1133h63.0234v34.3398h-63.0234v-34.3398Z" fill="#fff"/>
							<path d="M1728.9668,249.9932V108.5933h68.0742c13.1963,0,24.6094,2.1558,34.2393,6.4639,9.626,4.3115,17.0693,10.4727,22.3213,18.4829,5.252,8.0137,7.8779,17.4731,7.8779,28.3813s-2.626,20.3003-7.8779,28.1782-12.6953,13.9072-22.3213,18.0791c-9.6299,4.1758-21.043,6.2627-34.2393,6.2627h-41.6123l21.21-19.5947v55.1465h-47.6719ZM1776.6387,200.0986l-21.21-21.6133h38.582c6.5967,0,11.4795-1.4805,14.6455-4.4443,3.1621-2.9604,4.7471-7.0005,4.7471-12.1196s-1.585-9.1567-4.7471-12.1201c-3.166-2.9604-8.0488-4.4443-14.6455-4.4443h-38.582l21.21-21.6138v76.3555ZM1813.6055,249.9932l-34.7441-51.5098h50.5l35.1475,51.5098h-50.9033Z" fill="#fff"/>
							<path d="M1952.5801,253.2246c-11.3115,0-21.7842-1.7861-31.4111-5.3525-9.6289-3.5664-17.9775-8.6514-25.0479-15.251-7.0693-6.5967-12.5586-14.4082-16.4629-23.4326-3.9072-9.0205-5.8574-18.9873-5.8574-29.8955s1.9502-20.8721,5.8574-29.896c3.9043-9.0205,9.3936-16.832,16.4629-23.4316,7.0703-6.5967,15.4189-11.6816,25.0479-15.251,9.627-3.5669,20.0996-5.353,31.4111-5.353,13.8691,0,26.1592,2.4238,36.8652,7.272,10.7061,4.8477,19.5596,11.8516,26.5635,21.0078l-30.0986,26.8662c-4.1758-5.252-8.7871-9.3237-13.8369-12.2212-5.0498-2.894-10.7402-4.3428-17.0693-4.3428-4.9834,0-9.4932.8076-13.5332,2.4238s-7.5088,3.9736-10.4033,7.0698c-2.8975,3.0996-5.1514,6.8364-6.7666,11.2109-1.6162,4.3779-2.4248,9.2607-2.4248,14.645,0,5.3877.8086,10.2705,2.4248,14.6445,1.6152,4.3779,3.8691,8.1152,6.7666,11.2109,2.8945,3.0996,6.3633,5.4541,10.4033,7.0703s8.5498,2.4238,13.5332,2.4238c6.3291,0,12.0195-1.4453,17.0693-4.3428,5.0498-2.8945,9.6611-6.9688,13.8369-12.2207l30.0986,26.8662c-7.0039,9.0234-15.8574,15.9922-26.5635,20.9062-10.7061,4.915-22.9961,7.373-36.8652,7.373Z" fill="#fff"/>
							<path d="M2076.6055,214.0371h70.7002v35.9561h-117.5645V108.5933h114.9385v35.9561h-68.0742v69.4878ZM2073.374,161.1133h63.0234v34.3398h-63.0234v-34.3398Z" fill="#fff"/>
						</g>
						<g>
							<circle cx="175" cy="175" r="175" fill="#ccb161"/>
							<path d="M349.8016,184.1762c-4.2758,82.7633-66.0552,150.3104-146.1534,163.4835l-81.4756-81.4756c-.3885-.3363-.7648-.6865-1.128-1.05-3.8777-3.8755-6.2738-9.2269-6.2738-15.1382-.009-6.1388,2.6257-11.9842,7.2311-16.0431l-8.3358-8.3358c-.3449-.299-.6796-.6111-1.0026-.9341-3.4402-3.4402-5.5752-8.1907-5.5752-13.4225,0-1.6406.2107-3.2339.6052-4.7542l-32.7454-32.7454c-2.0957-1.7274-2.3942-4.8267-.6668-6.9224.9339-1.133,2.3252-1.7894,3.7935-1.7897h38.6684l-45.2032-45.2032c-1.9201-1.9218-1.9187-5.0363.0031-6.9565.9211-.9202,2.1694-1.4378,3.4714-1.4392h28.3828l-24.457-24.457c-.9239-.9211-1.4422-2.1728-1.4401-3.4774-.0008-2.7163,2.2005-4.9189,4.9168-4.9197h20.5931c1.3409,0,2.5565.5359,3.4439,1.4051l.0729.0729,31.3753,31.3753h137.1708c1.4694-.003,2.8623.6545,3.7939,1.7908l70.9348,70.9363Z" fill="#ab8b2b" fill-rule="evenodd"/>
							<path d="M247.1094,238.4189c3.1996,0,6.0907,1.2987,8.1966,3.3906,2.169,2.1718,3.3851,5.117,3.3804,8.1863,0,3.1938-1.2928,6.0907-3.3804,8.1827-2.1739,2.173-5.1228,3.3921-8.1966,3.3884-3.071.0049-6.0172-1.2146-8.1863-3.3884-2.1725-2.1686-3.3918-5.1131-3.3884-8.1827,0-3.1996,1.2965-6.0907,3.3884-8.1863,2.1696-2.1734,5.1154-3.3934,8.1863-3.3906h0ZM136.1827,238.4189c3.1988,0,6.0944,1.2987,8.1864,3.3906,2.1748,2.1686,3.3949,5.1151,3.3899,8.1863,0,3.1938-1.2943,6.0907-3.3899,8.1827-2.1685,2.1749-5.1152,3.3945-8.1864,3.3884-3.07.0055-6.0153-1.2141-8.1827-3.3884-2.1743-2.1675-3.3944-5.1126-3.3899-8.1827,0-3.1996,1.2943-6.0907,3.3899-8.1863,2.1678-2.1739,5.1126-3.3942,8.1827-3.3906h0ZM99.125,88.4322l5.4826,23.0161h-29.5947c-2.7165,0-4.9186,2.2021-4.9186,4.9186s2.2021,4.9186,4.9186,4.9186h68.8866c2.7159,0,4.9175,2.2016,4.9175,4.9175s-2.2016,4.9175-4.9175,4.9175h-34.6048l1.664,6.9934h-6.1666c-2.7165,0-4.9186,2.2021-4.9186,4.9186s2.2021,4.9186,4.9186,4.9186h44.2138c2.7165.0331,4.8917,2.2621,4.8586,4.9786-.0325,2.6698-2.1889,4.8261-4.8586,4.8586h-33.3645l1.7281,7.2596h-39.2962c-2.7147,0-4.9153,2.2014-4.9153,4.9175s2.2014,4.9175,4.9153,4.9175h77.7416c2.7165.0329,4.892,2.2616,4.8591,4.9781-.0323,2.6701-2.189,4.8268-4.8591,4.8591h-33.756l1.8251,7.6694c-4.3524.5068-8.268,2.4974-11.2211,5.4461-3.4402,3.4438-5.5752,8.1944-5.5752,13.424s2.1357,9.9823,5.5752,13.4225c3.4439,3.4461,8.1944,5.5796,13.4283,5.5796h1.766c-2.5444,1.0783-4.8574,2.6365-6.8126,4.5894-4.0237,4.0117-6.2817,9.4622-6.2738,15.1441,0,5.9114,2.396,11.2627,6.2738,15.1382,3.8755,3.8755,9.2305,6.2716,15.1382,6.2716,5.9114,0,11.2685-2.396,15.1441-6.2716,3.8755-3.8755,6.2716-9.2269,6.2716-15.1382.0077-5.6814-2.2493-11.1316-6.2716-15.1441-1.9561-1.9529-4.2698-3.511-6.8148-4.5894h94.2674c-2.5432,1.0782-4.8547,2.6364-6.8082,4.5894-3.8755,3.8755-6.2738,9.2305-6.2738,15.1441s2.3982,11.2627,6.2738,15.1382c3.8755,3.8755,9.2261,6.2716,15.1382,6.2716s11.2583-2.396,15.136-6.2716c3.8777-3.8755,6.2832-9.2269,6.2832-15.1382s-2.4062-11.2685-6.2832-15.1441c-1.9491-1.9546-4.2584-3.5131-6.8002-4.5894h7.019c2.7045,0,4.9117-2.2014,4.9117-4.9197s-2.2072-4.9197-4.9117-4.9197H126.0911c-2.5156,0-4.8059-1.0318-6.4728-2.6921-1.6603-1.6647-2.6965-3.9514-2.6965-6.4706,0-2.5156,1.0361-4.8023,2.6965-6.4663,1.6661-1.6603,3.9572-2.6965,6.4728-2.6965h119.6781c3.9827,0,7.6716-1.3322,10.6101-3.6429,2.9232-2.3005,5.0903-5.5694,6.0448-9.4588l17.3199-71.0639c.1609-.5052.2413-1.0325.2384-1.5626,0-2.7162-2.1875-4.9197-4.9117-4.9197H114.7168l-6.8863-28.91c-.4643-2.2944-2.4811-3.9437-4.822-3.9433h-20.5902c-2.7163-.0008-4.9189,2.2005-4.9197,4.9168v.0029c0,2.7159,2.2016,4.9175,4.9175,4.9175h16.7089v-.0007Z" fill="#fff" fill-rule="evenodd"/>
						</g>
					</svg>
				</div>

				<div class="digicommerce-help flex flex-col esm:flex-row items-center gap-4">
					<?php
					foreach ( $help as $id => $array ) :
						$url = $array['url'];
						// Add UTM parameters appropriately
						if ( 'support' === $id ) {
							// For support URL, check if there are existing parameters
							$url .= ( strpos( $url, '?' ) !== false ) ? '&' : '?';
							$url .= 'section=support';
							$url .= '&utm_source=WordPress&utm_medium=header&utm_campaign=digi';
						} else {
							// For other URLs, simply append the UTM parameters
							$url .= $utm_params;
						}
						?>
						<a class="flex items-center gap-2 text-white hover:text-white/80 active:text-white/80 focus:text-white/80 default-transition" href="<?php echo esc_url( $url ); ?>" target="_blank" rel="noopener noreferrer">
							<div class="digicommerce-help-icon flex items-center justify-center w-8 h-8 bg-white/50 rounded-full p-2 default-transition">
								<?php echo wp_kses( $array['svg'], $allowed_html ); ?>
							</div>

							<div><?php echo esc_attr( $array['title'] ); ?></div>
						</a>
						<?php
					endforeach;
					?>
				</div>
			</div>
	
			<?php
			if (class_exists('DigiCommerce_Pro') && !DigiCommerce_Pro_Updates::instance()->has_pro_access()) {
				?>
				<div class="digicommerce-notice notice-warning flex items-center gap-4 min-h-[48px] bg-[#fff7ee] text-[#08053a] shadow-[0px_1px_2px_rgba(16,24,40,0.1)] m-5 ltr:ml-0 rtl:mr-0 p-4 rounded-md border border-solid border-[rgba(247,144,9,0.32)]">
					<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" width="16" height="16" fill="#f56e28"><path d="M256 32c14.2 0 27.3 7.5 34.5 19.8l216 368c7.3 12.4 7.3 27.7 .2 40.1S486.3 480 472 480L40 480c-14.3 0-27.6-7.7-34.7-20.1s-7-27.8 .2-40.1l216-368C228.7 39.5 241.8 32 256 32zm0 128c-13.3 0-24 10.7-24 24l0 112c0 13.3 10.7 24 24 24s24-10.7 24-24l0-112c0-13.3-10.7-24-24-24zm32 224a32 32 0 1 0 -64 0 32 32 0 1 0 64 0z"/></svg>
					<p class="m-0">
						<?php
						printf(
							// Translators: %1$s and %2$s are the opening and closing <a> tags, %3$s is the closing </a> tag.
							esc_html__('Activate your license to enable access to updates, support & PRO features. %1$sActivate now%2$s%3$s', 'digicommerce'),
							'<a href="' . esc_url(admin_url('admin.php?page=digicommerce-updates')) . '" class="inline-flex items-center gap-1 underline default-transition">',
							'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" width="12" height="12"><path d="M11.2 2.8a.8.8 0 00-1.3 1L12.6 7h-11a.8.8 0 100 1.7h11L10 12a.8.8 0 101.3 1L15 8.6a.8.8 0 000-1.2l-3.8-4.5z"/></svg>',
							'</a>'
						);
						?>
					</p>
				</div>
				<?php
			}
			?>
			
			<?php $this->show_import_notices(); ?>
	
			<div class="flex flex-col bg-white box-border p-6 rounded-md m-5 ltr:ml-0 rtl:mr-0">
				<h1 class="text-2xl font-bold m-0 mb-4"><?php esc_html_e('Import/Export', 'digicommerce'); ?></h1>
            	<p class="mb-8"><?php esc_html_e('Import and export your DigiCommerce settings, products, and data.', 'digicommerce'); ?></p>
				<?php $this->render_import_export_content(); ?>
			</div>
		</div>
		<?php
	}

	/**
	 * Display import results notices
	 */
	public function show_import_notices() {
		// Show import results if available
		if ( isset( $_GET['page'] ) && 'digicommerce-import-export' === $_GET['page'] && 
		isset( $_GET['import-results'] ) ) {
			
			$import_results = get_transient( 'digicommerce_import_results' );
			if ( $import_results ) {
				if ( isset( $import_results['success'] ) && $import_results['success'] ) {
					$message = esc_html__( 'Import completed successfully!', 'digicommerce' );
					
					// Add details about what was imported
					$imported_items = array();
					if ( isset( $import_results['settings'] ) && $import_results['settings'] ) {
						$imported_items[] = esc_html__( 'Settings', 'digicommerce' );
					}
					if ( isset( $import_results['products'] ) && $import_results['products'] ) {
						$imported_items[] = sprintf(
							/* translators: %d: number of products imported */
							esc_html__( 'Products (%d)', 'digicommerce' ),
							$import_results['products_count']
						);
					}
					if ( isset( $import_results['bookings'] ) && $import_results['bookings'] ) {
						$imported_items[] = esc_html__( 'Bookings', 'digicommerce' );
					}
					if ( isset( $import_results['programs'] ) && $import_results['programs'] ) {
						$imported_items[] = esc_html__( 'Programs', 'digicommerce' );
					}
					if ( isset( $import_results['affiliates'] ) && $import_results['affiliates'] ) {
						$imported_items[] = esc_html__( 'Affiliates', 'digicommerce' );
					}
					
					if ( ! empty( $imported_items ) ) {
						$message .= ' ' . esc_html__( 'Imported:', 'digicommerce' ) . ' ' . implode( ', ', $imported_items );
					}
					
					add_settings_error(
						'digicommerce_messages',
						'digicommerce_message',
						$message,
						'updated'
					);
				} else {
					$error_message = isset( $import_results['message'] ) ? $import_results['message'] : esc_html__( 'Import failed.', 'digicommerce' );
					add_settings_error(
						'digicommerce_messages',
						'digicommerce_message',
						$error_message,
						'error'
					);
				}
				
				// Clean up the transient
				delete_transient( 'digicommerce_import_results' );
			}
		}
		
		// Display all settings errors
		settings_errors( 'digicommerce_messages' );
	}

	/**
	 * Render the import/export content
	 */
	private function render_import_export_content() {
		// Check if user has permission
		if (!current_user_can('manage_options')) {
			return;
		}
		?>

		<!-- Export Section -->
		<div class="section">
			<div class="section-header">
				<h2 class="section-title"><?php esc_html_e('Export', 'digicommerce'); ?></h2>
			</div>
			<div class="section-content">
				<div class="card">
					<div class="card-header">
						<h3 class="card-title"><?php esc_html_e('Export DigiCommerce Data', 'digicommerce'); ?></h3>
						<p class="card-description"><?php esc_html_e('Select which data you want to export:', 'digicommerce'); ?></p>
					</div>
					
					<form id="digicommerce-export-form" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
						<input type="hidden" name="action" value="digicommerce_export">
						<?php wp_nonce_field('digicommerce_export', 'digicommerce_export_nonce'); ?>

						<div class="checkbox-group">
							<label class="checkbox-item checkbox-parent">
								<input type="checkbox" name="export_all" id="export-all" value="1">
								<span><?php esc_html_e('Export All', 'digicommerce'); ?></span>
							</label>
							
							<div class="checkbox-children">
								<label class="checkbox-item">
									<input type="checkbox" name="export_settings" id="export-settings" value="1" checked>
									<span><?php esc_html_e('Settings', 'digicommerce'); ?></span>
								</label>
								
								<label class="checkbox-item">
									<input type="checkbox" name="export_products" id="export-products" value="1">
									<span><?php esc_html_e('Products', 'digicommerce'); ?></span>
								</label>
								
								<label class="checkbox-item">
									<input type="checkbox" name="export_bookings" id="export-bookings" value="1">
									<span><?php esc_html_e('Bookings', 'digicommerce'); ?></span>
								</label>
								
								<label class="checkbox-item">
									<input type="checkbox" name="export_programs" id="export-programs" value="1">
									<span><?php esc_html_e('Programs', 'digicommerce'); ?></span>
								</label>
								
								<label class="checkbox-item">
									<input type="checkbox" name="export_affiliates" id="export-affiliates" value="1">
									<span><?php esc_html_e('Affiliates', 'digicommerce'); ?></span>
								</label>
							</div>
						</div>
						
						<div class="button-container">
							<button type="submit" name="digicommerce_export_submit" class="btn btn-primary">
								<span><?php esc_html_e('Export', 'digicommerce'); ?></span>
								<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 384 512" width="18" height="18" fill="currentColor">
									<path d="M16 480c-8.8 0-16-7.2-16-16s7.2-16 16-16l352 0c8.8 0 16 7.2 16 16s-7.2 16-16 16L16 480zM203.3 379.3c-6.2 6.2-16.4 6.2-22.6 0l-128-128c-6.2-6.2-6.2-16.4 0-22.6s16.4-6.2 22.6 0L176 329.4 176 224l0-176c0-8.8 7.2-16 16-16s16 7.2 16 16l0 176 0 105.4L308.7 228.7c6.2-6.2 16.4-6.2 22.6 0s6.2 16.4 0 22.6l-128 128z"></path>
								</svg>
							</button>
						</div>
					</form>
				</div>
			</div>
		</div>
		
		<!-- Import Section -->
		<div class="section">
			<div class="section-header">
				<h2 class="section-title"><?php esc_html_e('Import', 'digicommerce'); ?></h2>
			</div>
			<div class="section-content">
				<div class="card">
					<div class="card-header">
						<h3 class="card-title"><?php esc_html_e('Import DigiCommerce Data', 'digicommerce'); ?></h3>
						<p class="card-description"><?php esc_html_e('Upload a DigiCommerce JSON file to import data:', 'digicommerce'); ?></p>
					</div>
					
					<form id="digicommerce-import-form" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" enctype="multipart/form-data">
						<input type="hidden" name="action" value="digicommerce_import">
						<?php wp_nonce_field('digicommerce_import', 'digicommerce_import_nonce'); ?>

						<div class="dropzone" id="dropzone-area">
							<div class="dropzone-icon">
								<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
									<path fill-rule="evenodd" d="M1.5 6a2.25 2.25 0 012.25-2.25h16.5A2.25 2.25 0 0122.5 6v12a2.25 2.25 0 01-2.25 2.25H3.75A2.25 2.25 0 011.5 18V6zM3 16.06V18c0 .414.336.75.75.75h16.5A.75.75 0 0021 18v-1.94l-2.69-2.689a1.5 1.5 0 00-2.12 0l-.88.879.97.97a.75.75 0 11-1.06 1.06l-5.16-5.159a1.5 1.5 0 00-2.12 0L3 16.061zm10.125-7.81a1.125 1.125 0 112.25 0 1.125 1.125 0 01-2.25 0z" clip-rule="evenodd" />
								</svg>
							</div>
							<div class="dropzone-text">
								<span><?php esc_html_e('Drag and drop or', 'digicommerce'); ?></span>
								<label for="import-file" class="dropzone-browse"><?php esc_html_e('browse', 'digicommerce'); ?></label>
								<input id="import-file" name="import_file" type="file" accept=".json" class="sr-only" required>
							</div>
						</div>
						
						<div class="file-preview" id="file-preview">
							<div class="file-icon">
								<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 384 512" width="24" height="24" fill="currentColor">
									<path d="M0 64C0 28.7 28.7 0 64 0H224V128c0 17.7 14.3 32 32 32H384V448c0 35.3-28.7 64-64 64H64c-35.3 0-64-28.7-64-64V64zm384 64H256V0L384 128z"/>
								</svg>
							</div>
							<div class="file-info">
								<div class="file-name">filename.json</div>
								<div class="file-size">12.5 KB</div>
							</div>
							<div class="file-remove">
								<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="20" height="20" fill="currentColor">
									<path d="M6 18L18 6M6 6l12 12" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
								</svg>
							</div>
						</div>
						
						<div class="import-options">
							<h4 class="import-options-title"><?php esc_html_e('Import Options:', 'digicommerce'); ?></h4>
							<div class="checkbox-group">
								<label class="checkbox-item">
									<input type="checkbox" name="import_settings_overwrite" id="import-settings-overwrite" value="1">
									<span><?php esc_html_e('Overwrite existing settings', 'digicommerce'); ?></span>
								</label>
								
								<label class="checkbox-item">
									<input type="checkbox" name="import_license" id="import-license" value="1" checked>
									<span><?php esc_html_e('Import license (if available)', 'digicommerce'); ?></span>
								</label>
							</div>
						</div>
						
						<div class="button-container">
							<button type="submit" name="digicommerce_import_submit" class="btn btn-primary">
								<span><?php esc_html_e('Import', 'digicommerce'); ?></span>
								<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 384 512" width="18" height="18" fill="currentColor">
									<path d="M203.3 36.7c-6.2-6.2-16.4-6.2-22.6 0l-128 128c-6.2 6.2-6.2 16.4 0 22.6s16.4 6.2 22.6 0L176 86.6 176 192l0 176c0 8.8 7.2 16 16 16s16-7.2 16-16l0-176 0-105.4L308.7 187.3c6.2 6.2 16.4 6.2 22.6 0s6.2-16.4 0-22.6l-128-128zM16 448c-8.8 0-16 7.2-16 16s7.2 16 16 16l352 0c8.8 0 16-7.2 16-16s-7.2-16-16-16L16 448z"></path>
								</svg>
							</button>
						</div>
					</form>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Process export
	 */
	public function process_export() {
		if ( ! isset( $_POST['digicommerce_export_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['digicommerce_export_nonce'] ) ), 'digicommerce_export' ) ) {
			wp_die( esc_html__( 'Security check failed', 'digicommerce' ) );
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to export data.', 'digicommerce' ) );
		}

		// Determine what to export
		$export_all = isset( $_POST['export_all'] ) && '1' === $_POST['export_all'];
		$export_settings = $export_all || (isset( $_POST['export_settings'] ) && '1' === $_POST['export_settings']);
		$export_products = $export_all || (isset( $_POST['export_products'] ) && '1' === $_POST['export_products']);
		$export_bookings = $export_all || (isset( $_POST['export_bookings'] ) && '1' === $_POST['export_bookings']);
		$export_programs = $export_all || (isset( $_POST['export_programs'] ) && '1' === $_POST['export_programs']);
		$export_affiliates = $export_all || (isset( $_POST['export_affiliates'] ) && '1' === $_POST['export_affiliates']);

		// Create export data
		$export_data = array(
			'export_date' => current_time( 'mysql' ),
			'site_url' => get_site_url(),
			'digicommerce_version' => DIGICOMMERCE_VERSION,
			'has_pro' => class_exists('DigiCommerce_Pro'),
		);

		// Export settings
		if ( $export_settings ) {
			$export_data['settings'] = $this->get_settings_data();

			// Export license data if PRO is active and license exists
			if (class_exists('DigiCommerce_Pro_Updates')) {
				$license_key = get_option('digicommerce_pro_license_key');
				$license_status = get_option('digicommerce_pro_license_status');
				
				if (!empty($license_key) && !empty($license_status)) {
					$export_data['license'] = array(
						'key' => $license_key,
						'status' => $license_status
					);
				}
			}
		}

		// Export products
		if ( $export_products ) {
			$export_data['products'] = $this->get_products_data();
		}

		// Export bookings if addon is active
		if ( $export_bookings && class_exists( 'DigiCommerce_Pro_Booking' ) ) {
			$export_data['bookings'] = $this->get_bookings_data();
		}

		// Export programs if addon is active
		if ( $export_programs && class_exists( 'DigiCommerce_Pro_Programs' ) ) {
			$export_data['programs'] = $this->get_programs_data();
		}

		// Export affiliates if addon is active
		if ( $export_affiliates && class_exists( 'DigiCommerce_Pro_Affiliation' ) ) {
			$export_data['affiliates'] = $this->get_affiliates_data();
		}

		// Generate filename
		$filename = 'digicommerce-export-' . date( 'Y-m-d-H-i-s' ) . '.json';

		// Send file to browser
		header( 'Content-Description: File Transfer' );
		header( 'Content-Disposition: attachment; filename=' . $filename );
		header( 'Content-Type: application/json; charset=' . get_option( 'blog_charset' ), true );
		
		echo wp_json_encode( $export_data, JSON_PRETTY_PRINT );
		exit;
	}

	/**
	 * Process import with detailed error logging
	 */
	public function process_import() {
		if ( ! isset( $_POST['digicommerce_import_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['digicommerce_import_nonce'] ) ), 'digicommerce_import' ) ) {
			wp_die( esc_html__( 'Security check failed', 'digicommerce' ) );
		}
	
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to import data.', 'digicommerce' ) );
		}
	
		// Check if file was uploaded
		if ( ! isset( $_FILES['import_file'] ) || empty( $_FILES['import_file']['tmp_name'] ) ) {
			wp_die( esc_html__( 'Please upload a valid file.', 'digicommerce' ) );
		}
	
		// Get file extension and check if it's valid
		$filename = isset($_FILES['import_file']['name']) ? $_FILES['import_file']['name'] : '';
		$file_info = wp_check_filetype($filename);
		
		// First try the standard method to check file type
		if (empty($file_info['ext']) || 'json' !== $file_info['ext']) {
			// Try to read the file and validate JSON content
			try {
				$file_contents = file_get_contents($_FILES['import_file']['tmp_name']);
				$import_data = json_decode($file_contents, true);
				$json_error = json_last_error();
				
				if ($json_error === JSON_ERROR_NONE && is_array($import_data)) {
					// Content is valid JSON, so we'll proceed despite extension issues
				} else {
					wp_die( esc_html__( 'Please upload a valid JSON file.', 'digicommerce' ) );
				}
			} catch (Exception $e) {
				wp_die( esc_html__( 'Error reading uploaded file.', 'digicommerce' ) );
			}
		} else {
			// Read and decode the file
			$import_file = $_FILES['import_file']['tmp_name'];
			$file_contents = file_get_contents($import_file);
			$import_data = json_decode($file_contents, true);
			
			if (null === $import_data) {
				wp_die( esc_html__( 'Invalid JSON data in the uploaded file.', 'digicommerce' ) );
			}
		}
		
		// At this point, $import_data should contain valid data from the file
		if (!is_array($import_data)) {
			wp_die( esc_html__( 'Invalid data structure in the uploaded file.', 'digicommerce' ) );
		}
	
		// Import settings
		$settings_overwrite = isset( $_POST['import_settings_overwrite'] ) && '1' === $_POST['import_settings_overwrite'];
		$import_license = isset( $_POST['import_license'] ) && '1' === $_POST['import_license'];
		
		// Process each data type
		$import_results = array(
			'success' => true,
			'settings' => false,
			'products' => false,
			'bookings' => false,
			'programs' => false,
			'affiliates' => false,
			'products_count' => 0,
		);
	
		// Import settings
		if (isset($import_data['settings'])) {
			try {
				$import_results['settings'] = $this->import_settings($import_data['settings'], $settings_overwrite);
				
				// Create essential pages if they don't exist
				$this->create_essential_pages($import_data['settings']);
				
			} catch (Exception $e) {
				$import_results['success'] = false;
			}
		}
	
		// Import license if available and requested
		if ($import_license && isset($import_data['license']) && class_exists('DigiCommerce_Pro_Updates')) {
			try {
				$license_result = $this->import_license($import_data['license']);
			} catch (Exception $e) {
				// License import exception handling
			}
		}
	
		// Import products
		if (isset($import_data['products'])) {
			try {
				$products_result = $this->import_products($import_data['products']);
				$import_results['products'] = $products_result['success'];
				$import_results['products_count'] = $products_result['count'];
			} catch (Exception $e) {
				$import_results['success'] = false;
			}
		}
	
		// Import bookings if addon is active or can be activated
		if (isset($import_data['bookings'])) {
			if (class_exists('DigiCommerce_Pro_Booking')) {
				try {
					$import_results['bookings'] = $this->import_bookings($import_data['bookings']);
				} catch (Exception $e) {
					$import_results['success'] = false;
				}
			} elseif (class_exists('DigiCommerce_Pro')) {
				try {
					// Enable booking feature if PRO is active
					update_option('digicommerce_enable_booking', 1);
					// Initialize the instance to create tables
					require_once DIGICOMMERCE_PRO_PLUGIN_DIR . 'includes/addons/class-digicommerce-pro-booking.php';
					$booking_instance = DigiCommerce_Pro_Booking::instance();
					$import_results['bookings'] = $this->import_bookings($import_data['bookings']);
				} catch (Exception $e) {
					$import_results['success'] = false;
				}
			}
		}
	
		// Import programs if addon is active or can be activated
		if (isset($import_data['programs'])) {
			if (class_exists('DigiCommerce_Pro_Programs')) {
				try {
					$import_results['programs'] = $this->import_programs($import_data['programs']);
				} catch (Exception $e) {
					$import_results['success'] = false;
				}
			} elseif (class_exists('DigiCommerce_Pro')) {
				try {
					// Enable programs feature if PRO is active
					update_option('digicommerce_enable_programs', 1);
					// Initialize the instance to create tables
					require_once DIGICOMMERCE_PRO_PLUGIN_DIR . 'includes/addons/class-digicommerce-pro-programs.php';
					$programs_instance = DigiCommerce_Pro_Programs::instance();
					$import_results['programs'] = $this->import_programs($import_data['programs']);
				} catch (Exception $e) {
					$import_results['success'] = false;
				}
			}
		}
	
		// Import affiliates if addon is active or can be activated
		if (isset($import_data['affiliates'])) {
			if (class_exists('DigiCommerce_Pro_Affiliation')) {
				try {
					$import_results['affiliates'] = $this->import_affiliates($import_data['affiliates']);
				} catch (Exception $e) {
					$import_results['success'] = false;
				}
			} elseif (class_exists('DigiCommerce_Pro')) {
				try {
					// Enable affiliation feature if PRO is active
					update_option('digicommerce_enable_affiliation', 1);
					// Initialize the instance to create tables
					require_once DIGICOMMERCE_PRO_PLUGIN_DIR . 'includes/addons/class-digicommerce-pro-affiliation.php';
					$affiliation_instance = DigiCommerce_Pro_Affiliation::instance();
					$import_results['affiliates'] = $this->import_affiliates($import_data['affiliates']);
				} catch (Exception $e) {
					$import_results['success'] = false;
				}
			}
		}
		
		// Store import results in a transient
		set_transient('digicommerce_import_results', $import_results, 60);
	
		// Redirect to the settings page with a success message
		wp_safe_redirect(
			add_query_arg(
				array(
					'page'           => 'digicommerce-import-export',
					'import-results' => '1',
				),
				admin_url('admin.php')
			)
		);
		exit;
	}

	/**
	 * Create essential pages if they don't exist
	 * 
	 * @param array $settings_data Imported settings data.
	 * @return void
	 */
	private function create_essential_pages($settings_data) {
		$essential_pages = array(
			'account_page_id' => array(
				'title' => __('My Account', 'digicommerce'),
				'content' => '[digicommerce_account]'
			),
			'checkout_page_id' => array(
				'title' => __('Checkout', 'digicommerce'),
				'content' => '[digicommerce_checkout]'
			),
			'payment_success_page_id' => array(
				'title' => __('Payment Success', 'digicommerce'),
				'content' => '[digicommerce_payment_success]'
			),
			'reset_password_page_id' => array(
				'title' => __('Reset Password', 'digicommerce'),
				'content' => '[digicommerce_reset_password]'
			),
			'programs_page_id' => array(
				'title' => __('Programs', 'digicommerce'),
				'content' => '[digicommerce_programs]'
			),
			'affiliate_account_page_id' => array(
				'title' => __('Affiliate Dashboard', 'digicommerce'),
				'content' => '[digicommerce_affiliate_dashboard]'
			),
			'certificate_page_id' => array(
				'title' => __('Certificate', 'digicommerce'),
				'content' => '[digicommerce_certificate]'
			)
		);
		
		$updated_settings = false;
		
		foreach ($essential_pages as $page_key => $page_data) {
			// Check if the page ID exists in settings and is valid
			$page_id = isset($settings_data[$page_key]) ? intval($settings_data[$page_key]) : 0;
			
			if ($page_id > 0) {
				// Check if the page actually exists
				$page = get_post($page_id);
				
				if (!$page || $page->post_type !== 'page' || $page->post_status === 'trash') {
					// Page doesn't exist or is in trash, create a new one
					$page_id = 0;
				}
			}
			
			// Create the page if it doesn't exist
			if ($page_id <= 0) {
				$page_id = wp_insert_post(array(
					'post_title'     => $page_data['title'],
					'post_content'   => $page_data['content'],
					'post_status'    => 'publish',
					'post_type'      => 'page',
					'comment_status' => 'closed'
				));
				
				if ($page_id && !is_wp_error($page_id)) {
					// Update the setting with the new page ID using DigiCommerce's method
					DigiCommerce()->set_option($page_key, $page_id);
					$updated_settings = true;
				}
			} else {
				// Page exists, ensure settings are linked properly
				DigiCommerce()->set_option($page_key, $page_id);
				$updated_settings = true;
			}
		}
	}

	/**
	 * Import license data
	 * 
	 * @param array $license_data License data to import
	 * @return bool Success status
	 */
	private function import_license($license_data) {
		if (empty($license_data) || !isset($license_data['key'])) {
			return false;
		}
	
		$license_key = sanitize_text_field($license_data['key']);
		
		// Handle status - ensure it's processed correctly
		$license_status = 'active'; // Default to active (the API expects active, not valid)
		$license_details = array();
		
		if (isset($license_data['status'])) {
			if (is_string($license_data['status'])) {
				$license_status = $license_data['status'];
			} elseif (is_array($license_data['status'])) {
				$license_status = isset($license_data['status']['status']) ? $license_data['status']['status'] : 'active';
				$license_details = $license_data['status'];
			}
		}
		
		// Update license key and status directly
		update_option('digicommerce_pro_license_key', $license_key);
		
		// Store the status in the format expected by the plugin
		$status_data = array(
			'status' => $license_status,
			'expires_at' => isset($license_details['expires_at']) ? $license_details['expires_at'] : '',
			'last_check' => isset($license_details['last_check']) ? $license_details['last_check'] : current_time('mysql')
		);
		
		update_option('digicommerce_pro_license_status', $status_data);
		
		// Clear any cached license data
		delete_transient('digicommerce_pro_license_details');
		
		// Store license details in transient too
		set_transient('digicommerce_pro_license_details', $status_data, DAY_IN_SECONDS);
		
		// Hard-coded API URL since we can't access the private property
		$api_url = 'https://digihold.me/wp-json/digicommerce/v2';
		
		// Try to make an API call to verify the license
		if (class_exists('DigiCommerce_Pro_Updates')) {
			// Make the API request directly
			$verify_url = trailingslashit($api_url) . 'license/verify';
			$response = wp_remote_post(
				$verify_url,
				array(
					'timeout' => 15,
					'body'    => array(
						'license_key' => $license_key,
						'site_url'    => home_url(),
					),
				)
			);
			
			if (!is_wp_error($response)) {
				$result = json_decode(wp_remote_retrieve_body($response), true);
				if (!empty($result) && is_array($result) && isset($result['status']) && $result['status'] === 'active') {
					// Update stored data with response
					$license_data = array(
						'status'     => $result['status'],
						'expires_at' => isset($result['expires_at']) ? $result['expires_at'] : '',
						'last_check' => current_time('mysql'),
					);
					
					update_option('digicommerce_pro_license_status', $license_data);
					set_transient('digicommerce_pro_license_details', $license_data, 12 * HOUR_IN_SECONDS);
				}
			}
		}
		
		// Manually trigger a license check to refresh any cached data
		delete_transient('digicommerce_pro_update_check');
		
		return true;
	}

	/**
	 * Get settings data for export
	 * 
	 * @return array Settings data.
	 */
	private function get_settings_data() {
		global $wpdb;
		
		// Get settings from the digicommerce table
		$table_name = $wpdb->prefix . 'digicommerce';
		
		$settings = $wpdb->get_var($wpdb->prepare(
			"SELECT option_value FROM $table_name WHERE option_name = %s",
			'digicommerce_options'
		));
		
		if ($settings) {
			$settings_data = maybe_unserialize($settings);
			
			if (is_array($settings_data)) {
				return $settings_data;
			}
		}
		
		return array();
	}

	/**
	 * Get products data for export
	 * 
	 * @return array Products data.
	 */
	private function get_products_data() {
		$products = get_posts( array(
			'post_type' => 'digi_product',
			'post_status' => array( 'publish', 'draft', 'pending' ),
			'posts_per_page' => -1,
		) );
		
		$products_data = array();
		
		foreach ( $products as $product ) {
			// Get product meta
			$product_meta = get_post_meta( $product->ID );
			
			// Get categories
			$categories = wp_get_post_terms( $product->ID, 'digi_product_cat', array( 'fields' => 'names' ) );
			
			// Get tags
			$tags = wp_get_post_terms( $product->ID, 'digi_product_tag', array( 'fields' => 'names' ) );
			
			// Get featured image
			$featured_image_id = get_post_thumbnail_id( $product->ID );
			$featured_image = array();
			if ( $featured_image_id ) {
				$featured_image = $this->get_attachment_data( $featured_image_id );
			}
			
			// Get gallery images
			$gallery_images = array();
			if ( ! empty( $product_meta['digi_gallery_ids'] ) ) {
				$gallery_ids = maybe_unserialize( $product_meta['digi_gallery_ids'][0] );
				if ( is_array( $gallery_ids ) ) {
					foreach ( $gallery_ids as $attachment_id ) {
						$image_data = $this->get_attachment_data( $attachment_id );
						if ( ! empty( $image_data ) ) {
							$gallery_images[] = $image_data;
						}
					}
				}
			}
			
			// Prepare data
			$product_data = array(
				'title' => $product->post_title,
				'content' => $product->post_content,
				'excerpt' => $product->post_excerpt,
				'status' => $product->post_status,
				'slug' => $product->post_name,
				'date' => $product->post_date,
				'modified' => $product->post_modified,
				'author' => $product->post_author,
				'categories' => $categories,
				'tags' => $tags,
				'featured_image' => $featured_image,
				'gallery_images' => $gallery_images,
				'meta' => array(),
			);
			
			// Add meta data
			foreach ( $product_meta as $meta_key => $meta_value ) {
				if ( strpos( $meta_key, 'digi_' ) === 0 ) {
					$product_data['meta'][ $meta_key ] = maybe_unserialize( $meta_value[0] );
				}
			}
			
			$products_data[] = $product_data;
		}
		
		return $products_data;
	}
	
	/**
	 * Get attachment data for export
	 * 
	 * @param int $attachment_id Attachment ID
	 * @return array Attachment data
	 */
	private function get_attachment_data( $attachment_id ) {
		if ( ! $attachment_id ) {
			return array();
		}
		
		$attachment = get_post( $attachment_id );
		if ( ! $attachment ) {
			return array();
		}
		
		$attachment_url = wp_get_attachment_url( $attachment_id );
		$attachment_path = get_attached_file( $attachment_id );
		
		if ( ! file_exists( $attachment_path ) ) {
			return array();
		}
		
		$file_data = file_get_contents( $attachment_path );
		if ( ! $file_data ) {
			return array();
		}
		
		$attachment_data = array(
			'id' => $attachment_id,
			'title' => $attachment->post_title,
			'filename' => basename( $attachment_path ),
			'url' => $attachment_url,
			'alt' => get_post_meta( $attachment_id, '_wp_attachment_image_alt', true ),
			'mime_type' => $attachment->post_mime_type,
			'file_data' => base64_encode( $file_data ),
		);
		
		return $attachment_data;
	}

	/**
	 * Get bookings data for export
	 * 
	 * @return array Bookings data.
	 */
	private function get_bookings_data() {
		global $wpdb;
		
		$bookings_data = array();
		
		// Get booking services
		$services = get_posts( array(
			'post_type' => 'digi_booking_service',
			'post_status' => array( 'publish', 'draft', 'pending' ),
			'posts_per_page' => -1,
		) );
		
		$services_data = array();
		
		foreach ( $services as $service ) {
			// Get service meta
			$service_meta = get_post_meta( $service->ID );
			
			// Get categories
			$categories = wp_get_post_terms( $service->ID, 'digi_booking_type', array( 'fields' => 'names' ) );
			
			// Get featured image
			$featured_image_id = get_post_thumbnail_id( $service->ID );
			$featured_image = array();
			if ( $featured_image_id ) {
				$featured_image = $this->get_attachment_data( $featured_image_id );
			}
			
			// Prepare data
			$service_data = array(
				'title' => $service->post_title,
				'content' => $service->post_content,
				'excerpt' => $service->post_excerpt,
				'status' => $service->post_status,
				'slug' => $service->post_name,
				'date' => $service->post_date,
				'modified' => $service->post_modified,
				'author' => $service->post_author,
				'categories' => $categories,
				'featured_image' => $featured_image,
				'meta' => array(),
			);
			
			// Add meta data
			foreach ( $service_meta as $meta_key => $meta_value ) {
				if ( strpos( $meta_key, 'digi_' ) === 0 ) {
					$service_data['meta'][ $meta_key ] = maybe_unserialize( $meta_value[0] );
				}
			}
			
			$services_data[] = $service_data;
		}
		
		$bookings_data['services'] = $services_data;
		
		// Other booking data...
		
		return $bookings_data;
	}
	
	/**
	 * Enhanced get_programs_data method with image export
	 */
	private function get_programs_data() {
		global $wpdb;
		
		$programs_data = array();
		
		// Get programs
		$programs = get_posts( array(
			'post_type' => 'digi_program',
			'post_status' => array( 'publish', 'draft', 'pending' ),
			'posts_per_page' => -1,
		) );
		
		$programs_list = array();
		
		foreach ( $programs as $program ) {
			// Get program meta
			$program_meta = get_post_meta( $program->ID );
			
			// Get featured image
			$featured_image_id = get_post_thumbnail_id( $program->ID );
			$featured_image = array();
			if ( $featured_image_id ) {
				$featured_image = $this->get_attachment_data( $featured_image_id );
			}
			
			// Prepare data
			$program_data = array(
				'title' => $program->post_title,
				'content' => $program->post_content,
				'excerpt' => $program->post_excerpt,
				'status' => $program->post_status,
				'slug' => $program->post_name,
				'date' => $program->post_date,
				'modified' => $program->post_modified,
				'author' => $program->post_author,
				'featured_image' => $featured_image,
				'meta' => array(),
			);
			
			// Add meta data
			foreach ( $program_meta as $meta_key => $meta_value ) {
				if ( strpos( $meta_key, 'digi_' ) === 0 ) {
					$program_data['meta'][ $meta_key ] = maybe_unserialize( $meta_value[0] );
				}
			}
			
			$programs_list[] = $program_data;
		}
		
		$programs_data['programs'] = $programs_list;
		
		// Get lessons
		$lessons = get_posts( array(
			'post_type' => 'digi_lesson',
			'post_status' => array( 'publish', 'draft', 'pending' ),
			'posts_per_page' => -1,
		) );
		
		$lessons_list = array();
		
		foreach ( $lessons as $lesson ) {
			// Get lesson meta
			$lesson_meta = get_post_meta( $lesson->ID );
			
			// Get sections
			$sections = wp_get_post_terms( $lesson->ID, 'digi_program_section', array( 'fields' => 'all' ) );
			$sections_data = array();
			
			foreach ( $sections as $section ) {
				$sections_data[] = array(
					'name' => $section->name,
					'slug' => $section->slug,
					'term_id' => $section->term_id,
					'order' => get_term_meta( $section->term_id, 'section_order', true ),
				);
			}
			
			// Get featured image
			$featured_image_id = get_post_thumbnail_id( $lesson->ID );
			$featured_image = array();
			if ( $featured_image_id ) {
				$featured_image = $this->get_attachment_data( $featured_image_id );
			}
			
			// Prepare data
			$lesson_data = array(
				'title' => $lesson->post_title,
				'content' => $lesson->post_content,
				'excerpt' => $lesson->post_excerpt,
				'status' => $lesson->post_status,
				'slug' => $lesson->post_name,
				'date' => $lesson->post_date,
				'modified' => $lesson->post_modified,
				'author' => $lesson->post_author,
				'sections' => $sections_data,
				'featured_image' => $featured_image,
				'meta' => array(),
			);
			
			// Add meta data
			foreach ( $lesson_meta as $meta_key => $meta_value ) {
				if ( strpos( $meta_key, 'digi_' ) === 0 ) {
					$lesson_data['meta'][ $meta_key ] = maybe_unserialize( $meta_value[0] );
				}
			}
			
			$lessons_list[] = $lesson_data;
		}
		
		$programs_data['lessons'] = $lessons_list;
		
		// Other program data...
		
		return $programs_data;
	}

	/**
	 * Get affiliates data for export
	 * 
	 * @return array Affiliates data.
	 */
	private function get_affiliates_data() {
		global $wpdb;
		
		$affiliates_data = array();
		
		// Get affiliate data from tables
		$table_affiliates = $wpdb->prefix . 'digicommerce_affiliates';
		$table_referrals = $wpdb->prefix . 'digicommerce_affiliate_referrals';
		$table_visits = $wpdb->prefix . 'digicommerce_affiliate_visits';
		$table_payouts = $wpdb->prefix . 'digicommerce_affiliate_payouts';
		$table_meta = $wpdb->prefix . 'digicommerce_affiliate_meta';
		
		// Check if tables exist
		if ( $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $table_affiliates ) ) === $table_affiliates ) {
			$affiliates = $wpdb->get_results( "SELECT * FROM {$table_affiliates}" );
			$affiliates_data['affiliates'] = $affiliates;
		}
		
		if ( $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $table_referrals ) ) === $table_referrals ) {
			$referrals = $wpdb->get_results( "SELECT * FROM {$table_referrals}" );
			$affiliates_data['referrals'] = $referrals;
		}
		
		if ( $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $table_visits ) ) === $table_visits ) {
			$visits = $wpdb->get_results( "SELECT * FROM {$table_visits}" );
			$affiliates_data['visits'] = $visits;
		}
		
		if ( $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $table_payouts ) ) === $table_payouts ) {
			$payouts = $wpdb->get_results( "SELECT * FROM {$table_payouts}" );
			$affiliates_data['payouts'] = $payouts;
		}
		
		if ( $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $table_meta ) ) === $table_meta ) {
			$meta = $wpdb->get_results( "SELECT * FROM {$table_meta}" );
			$affiliates_data['meta'] = $meta;
		}
		
		return $affiliates_data;
	}

	/**
	 * Import settings
	 * 
	 * @param array $settings_data Settings data to import.
	 * @param bool  $overwrite     Whether to overwrite existing settings.
	 * @return bool Success status.
	 */
	private function import_settings($settings_data, $overwrite = false) {
		if (empty($settings_data)) {
			return false;
		}
			
		// Get DigiCommerce instance if available
		$digi = function_exists('DigiCommerce') ? DigiCommerce() : null;
		
		// Process each setting individually
		foreach ($settings_data as $key => $value) {
			// Skip empty values
			if (empty($value) && $value !== 0) continue;
			
			// Use DigiCommerce's set_option if available
			if ($digi && method_exists($digi, 'set_option')) {
				$digi->set_option($key, $value);
			} else {
				// Fall back to WordPress options
				$option_key = (strpos($key, 'digicommerce_') === 0) ? $key : 'digicommerce_' . $key;
				update_option($option_key, $value);
			}
		}
		
		// Enable module settings in WordPress options table for compatibility
		$module_keys = ['enable_booking', 'enable_programs', 'enable_affiliation'];
		foreach ($module_keys as $key) {
			if (isset($settings_data[$key]) && $settings_data[$key]) {
				update_option('digicommerce_' . $key, 1);
			}
		}
		
		return true;
	}

	/**
	 * Import products
	 * 
	 * @param array $products_data Products data to import.
	 * @return array with success status and count of imported products
	 */
	private function import_products( $products_data ) {
		if ( empty( $products_data ) ) {
			return ['success' => false, 'count' => 0];
		}
	
		$imported_count = 0;
		$updated_count = 0;
		$error_count = 0;
		
		foreach ( $products_data as $index => $product_data ) {
			try {
				// Check if product exists by slug
				$existing_product = get_page_by_path( $product_data['slug'], OBJECT, 'digi_product' );
				
				// Prepare post data
				$post_data = array(
					'post_title' => sanitize_text_field( $product_data['title'] ),
					'post_content' => wp_kses_post( $product_data['content'] ),
					'post_excerpt' => wp_kses_post( $product_data['excerpt'] ),
					'post_status' => sanitize_text_field( $product_data['status'] ),
					'post_name' => sanitize_title( $product_data['slug'] ),
					'post_type' => 'digi_product',
					'post_author' => get_current_user_id(),
				);
				
				if ( $existing_product ) {
					// Update existing product
					$post_data['ID'] = $existing_product->ID;
					$product_id = wp_update_post( $post_data );
					$updated_count++;
				} else {
					// Create new product
					$product_id = wp_insert_post( $post_data );
					$imported_count++;
				}
				
				if ( is_wp_error( $product_id ) ) {
					$error_count++;
					continue;
				}
				
				// Add categories
				if ( ! empty( $product_data['categories'] ) ) {
					$cat_result = $this->set_terms_by_name( $product_id, $product_data['categories'], 'digi_product_cat' );
				}
				
				// Add tags
				if ( ! empty( $product_data['tags'] ) ) {
					$tag_result = $this->set_terms_by_name( $product_id, $product_data['tags'], 'digi_product_tag' );
				}
				
				// Import featured image if available
				if ( ! empty( $product_data['featured_image'] ) ) {
					$featured_image_id = $this->import_attachment( $product_data['featured_image'], $product_id );
					if ( $featured_image_id ) {
						set_post_thumbnail( $product_id, $featured_image_id );
					}
				}
				
				// Import meta data first (to handle any non-gallery data)
				if ( ! empty( $product_data['meta'] ) ) {
					foreach ( $product_data['meta'] as $meta_key => $meta_value ) {
						// Skip gallery data, we'll handle it separately
						if ($meta_key !== 'digi_gallery' && $meta_key !== 'digi_gallery_ids') {
							$meta_result = update_post_meta( $product_id, $meta_key, $meta_value );
						}
					}
				}
	
				// Handle gallery - First approach: Import from gallery_images array
				$gallery_items = [];
				$gallery_ids = [];
				
				if (!empty($product_data['gallery_images'])) {
					foreach ($product_data['gallery_images'] as $attachment_data) {
						$gallery_image_id = $this->import_attachment($attachment_data, $product_id);
						
						if ($gallery_image_id) {
							// Add to gallery items array for digi_gallery
							$gallery_items[] = array(
								'id' => $gallery_image_id,
								'url' => wp_get_attachment_image_url($gallery_image_id, 'medium') ?: wp_get_attachment_url($gallery_image_id),
								'alt' => $attachment_data['alt'] ?? ''
							);
							
							// Add to gallery IDs array for digi_gallery_ids
							$gallery_ids[] = $gallery_image_id;
						}
					}
				}
	
				// Second approach: Process gallery from meta if the first approach didn't provide images
				if (empty($gallery_items) && !empty($product_data['meta']['digi_gallery'])) {
					foreach ($product_data['meta']['digi_gallery'] as $item) {
						if (!isset($item['url'])) {
							continue; // Skip items without URL
						}
						
						// Try to extract filename from URL
						$url = $item['url'];
						$filename = basename(parse_url($url, PHP_URL_PATH));
						
						// First: Try to find existing attachment with this filename
						$existing_attachment = $this->get_attachment_by_filename($filename);
						$gallery_image_id = $existing_attachment ? $existing_attachment->ID : null;
						
						// Second: If not found, try to download it from the URL
						if (!$gallery_image_id && filter_var($url, FILTER_VALIDATE_URL)) {
							$gallery_image_id = $this->import_attachment_from_url($url, $product_id, $item['alt'] ?? '');
						}
						
						// If we have a gallery image ID now, add it to our arrays
						if ($gallery_image_id) {
							// Add to gallery items array for digi_gallery
							$gallery_items[] = array(
								'id' => $gallery_image_id,
								'url' => wp_get_attachment_image_url($gallery_image_id, 'medium') ?: wp_get_attachment_url($gallery_image_id),
								'alt' => $item['alt'] ?? ''
							);
							
							// Add to gallery IDs array for digi_gallery_ids
							$gallery_ids[] = $gallery_image_id;
						}
					}
				}
				
				// Update gallery meta with the correct local URLs and IDs
				if (!empty($gallery_items)) {
					update_post_meta($product_id, 'digi_gallery', $gallery_items);
					update_post_meta($product_id, 'digi_gallery_ids', $gallery_ids);
				}
			} catch (Exception $e) {
				$error_count++;
			}
		}
		
		return ['success' => ($error_count === 0), 'count' => $imported_count];
	}
	
	/**
	 * Import attachment from export data
	 * 
	 * @param array $attachment_data Attachment data
	 * @param int $parent_id Parent post ID
	 * @return int|false Attachment ID or false on failure
	 */
	private function import_attachment( $attachment_data, $parent_id = 0 ) {
		if ( empty( $attachment_data ) || empty( $attachment_data['file_data'] ) ) {
			return false;
		}
		
		$filename = sanitize_file_name( $attachment_data['filename'] );
		$mime_type = sanitize_text_field( $attachment_data['mime_type'] );
		
		// Check if attachment already exists by filename
		$existing_attachment = $this->get_attachment_by_filename( $filename );
		if ( $existing_attachment ) {
			return $existing_attachment->ID;
		}
		
		// Prepare file data
		$upload_dir = wp_upload_dir();
		$file_path = $upload_dir['path'] . '/' . $filename;
		
		// Decode base64 data
		$file_data = base64_decode( $attachment_data['file_data'] );
		if ( ! $file_data ) {
			return false;
		}
		
		// Save file to uploads directory
		$file_saved = file_put_contents( $file_path, $file_data );
		if ( ! $file_saved ) {
			return false;
		}
		
		// Prepare attachment data
		$attachment = array(
			'post_mime_type' => $mime_type,
			'post_title' => sanitize_text_field( $attachment_data['title'] ),
			'post_status' => 'inherit',
			'post_parent' => $parent_id,
			'guid' => $upload_dir['url'] . '/' . $filename,
		);
		
		// Insert attachment
		$attachment_id = wp_insert_attachment( $attachment, $file_path, $parent_id );
		if ( is_wp_error( $attachment_id ) ) {
			return false;
		}
		
		// Generate metadata
		require_once( ABSPATH . 'wp-admin/includes/image.php' );
		$attachment_data = wp_generate_attachment_metadata( $attachment_id, $file_path );
		wp_update_attachment_metadata( $attachment_id, $attachment_data );
		
		// Set alt text if available
		if ( ! empty( $attachment_data['alt'] ) ) {
			update_post_meta( $attachment_id, '_wp_attachment_image_alt', sanitize_text_field( $attachment_data['alt'] ) );
		}
		
		return $attachment_id;
	}
	
	/**
	 * Get attachment by filename
	 * 
	 * @param string $filename Filename to search for
	 * @return WP_Post|false Attachment post or false if not found
	 */
	private function get_attachment_by_filename( $filename ) {
		$args = array(
			'post_type' => 'attachment',
			'post_status' => 'inherit',
			'posts_per_page' => 1,
			'meta_query' => array(
				array(
					'key' => '_wp_attached_file',
					'value' => $filename,
					'compare' => 'LIKE',
				),
			),
		);
		
		$attachments = get_posts( $args );
		
		if ( ! empty( $attachments ) ) {
			return $attachments[0];
		}
		
		return false;
	}

	/**
	 * Import attachment from URL
	 * 
	 * @param string $url The URL of the image
	 * @param int $parent_id The parent post ID
	 * @param string $alt_text Optional alt text for the image
	 * @return int|false Attachment ID or false on failure
	 */
	private function import_attachment_from_url($url, $parent_id = 0, $alt_text = '') {
		// Check if attachment exists by URL (prevent duplicates)
		$existing_attachment_id = $this->get_attachment_by_url($url);
		if ($existing_attachment_id) {
			return $existing_attachment_id;
		}
		
		// Get the file
		$response = wp_remote_get($url);
		if (is_wp_error($response)) {
			return false;
		}
		
		$file_contents = wp_remote_retrieve_body($response);
		if (empty($file_contents)) {
			return false;
		}
		
		// Get the filename
		$filename = basename(parse_url($url, PHP_URL_PATH));
		
		// Upload the file
		$upload = wp_upload_bits($filename, null, $file_contents);
		if ($upload['error']) {
			return false;
		}
		
		// Get file type
		$wp_filetype = wp_check_filetype($filename, null);
		
		// Prepare attachment data
		$attachment = array(
			'post_mime_type' => $wp_filetype['type'],
			'post_title' => sanitize_file_name($filename),
			'post_content' => '',
			'post_status' => 'inherit',
			'post_parent' => $parent_id
		);
		
		// Insert attachment
		$attachment_id = wp_insert_attachment($attachment, $upload['file'], $parent_id);
		if (!$attachment_id) {
			return false;
		}
		
		// Generate metadata
		require_once(ABSPATH . 'wp-admin/includes/image.php');
		$attachment_data = wp_generate_attachment_metadata($attachment_id, $upload['file']);
		wp_update_attachment_metadata($attachment_id, $attachment_data);
		
		// Set alt text if provided
		if (!empty($alt_text)) {
			update_post_meta($attachment_id, '_wp_attachment_image_alt', sanitize_text_field($alt_text));
		}
		
		return $attachment_id;
	}

	/**
	 * Get attachment by URL
	 * 
	 * @param string $url The URL to search for
	 * @return int|false Attachment ID or false if not found
	 */
	private function get_attachment_by_url($url) {
		$url = preg_replace('/\-\d+x\d+(?=\.(jpg|jpeg|png|gif)$)/i', '', $url); // Remove size suffix
		
		global $wpdb;
		$attachment_id = $wpdb->get_var($wpdb->prepare(
			"SELECT post_id FROM $wpdb->postmeta WHERE meta_key = '_wp_attached_file' AND meta_value LIKE %s",
			'%' . $wpdb->esc_like(basename($url)) . '%'
		));
		
		return $attachment_id ? (int) $attachment_id : false;
	}

	/**
	 * Import bookings
	 * 
	 * @param array $bookings_data Bookings data to import.
	 * @return bool Success status.
	 */
	private function import_bookings( $bookings_data ) {
		global $wpdb;
		
		if ( empty( $bookings_data ) ) {
			return false;
		}
		
		// First, ensure the booking feature is enabled
		update_option( 'digicommerce_enable_booking', 1 );
		
		// Make sure booking tables exist
		$table_bookings = $wpdb->prefix . 'digicommerce_bookings';
		$table_slots = $wpdb->prefix . 'digicommerce_booking_slots';
		$table_meta = $wpdb->prefix . 'digicommerce_booking_meta';
		
		// Check if tables exist, create them if not
		if ( $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $table_bookings ) ) !== $table_bookings ) {
			// Tables don't exist, initialize the booking class to create them
			if ( class_exists( 'DigiCommerce_Pro_Booking' ) ) {
				try {
					DigiCommerce_Pro_Booking::instance();
				} catch (Exception $e) {
					return false;
				}
			} else {
				return false;
			}
		}
		
		$errors = 0;
		
		// Import booking services
		if ( ! empty( $bookings_data['services'] ) ) {
			$services_imported = 0;
			$services_updated = 0;
			
			foreach ( $bookings_data['services'] as $service_data ) {
				try {
					// Check if service exists by slug
					$existing_service = get_page_by_path( $service_data['slug'], OBJECT, 'digi_booking_service' );
					
					// Prepare post data
					$post_data = array(
						'post_title' => sanitize_text_field( $service_data['title'] ),
						'post_content' => wp_kses_post( $service_data['content'] ),
						'post_excerpt' => wp_kses_post( $service_data['excerpt'] ),
						'post_status' => sanitize_text_field( $service_data['status'] ),
						'post_name' => sanitize_title( $service_data['slug'] ),
						'post_type' => 'digi_booking_service',
						'post_author' => get_current_user_id(),
					);
					
					if ( $existing_service ) {
						// Update existing service
						$post_data['ID'] = $existing_service->ID;
						$service_id = wp_update_post( $post_data );
						if (!is_wp_error($service_id)) {
							$services_updated++;
						}
					} else {
						// Create new service
						$service_id = wp_insert_post( $post_data );
						if (!is_wp_error($service_id)) {
							$services_imported++;
						}
					}
					
					if ( is_wp_error( $service_id ) ) {
						$errors++;
						continue;
					}
					
					// Import featured image if available
					if ( ! empty( $service_data['featured_image'] ) ) {
						$featured_image_id = $this->import_attachment( $service_data['featured_image'], $service_id );
						if ( $featured_image_id ) {
							set_post_thumbnail( $service_id, $featured_image_id );
						}
					}
					
					// Add categories
					if ( ! empty( $service_data['categories'] ) ) {
						$this->set_terms_by_name( $service_id, $service_data['categories'], 'digi_booking_type' );
					}
					
					// Add meta data
					if ( ! empty( $service_data['meta'] ) ) {
						foreach ( $service_data['meta'] as $meta_key => $meta_value ) {
							update_post_meta( $service_id, $meta_key, $meta_value );
						}
					}
				} catch (Exception $e) {
					$errors++;
				}
			}
		}
		
		// Import booking slots
		if ( ! empty( $bookings_data['slots'] ) ) {
			$table_slots = $wpdb->prefix . 'digicommerce_booking_slots';
			
			// Check if table exists
			if ( $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $table_slots ) ) === $table_slots ) {
				try {
					// Clear existing slots that might conflict
					$wpdb->query( "DELETE FROM {$table_slots}" );
					
					$slots_imported = 0;
					$slots_errors = 0;
					
					// Import slots
					foreach ( $bookings_data['slots'] as $slot ) {
						// Verify that $slot is an object before trying to clone it
						if (!is_object($slot)) {
							$slots_errors++;
							$errors++;
							continue;
						}
						
						// Create an array from the slot data instead of cloning
						$slot_data = (array)$slot;
						
						// Remove id to let auto-increment work
						unset($slot_data['id']);
						
						// Insert slot
						$result = $wpdb->insert($table_slots, $slot_data);
						if ($result) {
							$slots_imported++;
						} else {
							$slots_errors++;
							$errors++;
						}
					}
				} catch (Exception $e) {
					$errors++;
				}
			} else {
				$errors++;
			}
		}
		
		// Import booking meta
		if ( ! empty( $bookings_data['meta'] ) ) {
			$table_meta = $wpdb->prefix . 'digicommerce_booking_meta';
			
			// Check if table exists
			if ( $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $table_meta ) ) === $table_meta ) {
				try {
					$meta_imported = 0;
					$meta_errors = 0;
					
					// Import meta
					foreach ( $bookings_data['meta'] as $meta ) {
						// Verify that $meta is an object before trying to work with it
						if (!is_object($meta)) {
							$meta_errors++;
							$errors++;
							continue;
						}
						
						// Create an array from the meta data instead of cloning
						$meta_data = (array)$meta;
						
						// Remove id to let auto-increment work
						unset($meta_data['id']);
						
						// Insert meta
						$result = $wpdb->insert($table_meta, $meta_data);
						if ($result) {
							$meta_imported++;
						} else {
							$meta_errors++;
							$errors++;
						}
					}
				} catch (Exception $e) {
					$errors++;
				}
			} else {
				$errors++;
			}
		}
		
		// Import bookings
		if ( ! empty( $bookings_data['bookings'] ) ) {
			$table_bookings = $wpdb->prefix . 'digicommerce_bookings';
			
			// Check if table exists
			if ( $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $table_bookings ) ) === $table_bookings ) {
				try {
					$bookings_imported = 0;
					$bookings_errors = 0;
					
					// Import bookings
					foreach ( $bookings_data['bookings'] as $booking ) {
						// Verify that $booking is an object before trying to work with it
						if (!is_object($booking)) {
							$bookings_errors++;
							$errors++;
							continue;
						}
						
						// Create an array from the booking data instead of cloning
						$booking_data = (array)$booking;
						
						// Remove id to let auto-increment work
						unset($booking_data['id']);
						
						// Insert booking
						$result = $wpdb->insert($table_bookings, $booking_data);
						if ($result) {
							$bookings_imported++;
						} else {
							$bookings_errors++;
							$errors++;
						}
					}
				} catch (Exception $e) {
					$errors++;
				}
			} else {
				$errors++;
			}
		}
		
		return ($errors === 0);
	}

	/**
	 * Import programs
	 * 
	 * @param array $programs_data Programs data to import.
	 * @return bool Success status.
	 */
	private function import_programs( $programs_data ) {
		global $wpdb;
		
		if ( empty( $programs_data ) ) {
			return false;
		}
		
		// First, ensure the programs feature is enabled
		update_option( 'digicommerce_enable_programs', 1 );
		
		// Make sure programs tables exist
		$table_enrollments = $wpdb->prefix . 'digicommerce_program_enrollments';
		$table_progress = $wpdb->prefix . 'digicommerce_program_progress';
		
		// Check if tables exist, create them if not
		if ( $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $table_enrollments ) ) !== $table_enrollments ) {
			// Tables don't exist, initialize the programs class to create them
			if ( class_exists( 'DigiCommerce_Pro_Programs' ) ) {
				try {
					DigiCommerce_Pro_Programs::instance();
				} catch (Exception $e) {
					return false;
				}
			} else {
				return false;
			}
		}
		
		$errors = 0;
		
		// Map old section IDs to new ones
		$section_map = array();
		
		// Import program sections first
		if ( ! empty( $programs_data['sections'] ) ) {
			$sections_created = 0;
			$sections_updated = 0;
			
			foreach ( $programs_data['sections'] as $section_data ) {
				try {
					// Check if section exists by slug
					$existing_section = get_term_by( 'slug', $section_data['slug'], 'digi_program_section' );
					
					// Store old term ID for mapping
					$old_term_id = isset($section_data['term_id']) ? intval($section_data['term_id']) : 0;
					
					if ( $existing_section ) {
						// Update existing section
						$update_result = wp_update_term( $existing_section->term_id, 'digi_program_section', array(
							'name' => sanitize_text_field( $section_data['name'] ),
							'description' => sanitize_textarea_field( $section_data['description'] ),
							'parent' => intval( $section_data['parent'] ),
						) );
						
						if (!is_wp_error($update_result)) {
							$sections_updated++;
							
							// Update section order
							if ( isset( $section_data['order'] ) ) {
								update_term_meta( $existing_section->term_id, 'section_order', intval( $section_data['order'] ) );
							}
							
							// Map old section term_id to new term_id
							if ($old_term_id > 0) {
								$section_map[ $old_term_id ] = $existing_section->term_id;
							}
							$section_map[ $section_data['slug'] ] = $existing_section->term_id;
						} else {
							$errors++;
						}
					} else {
						// Create new section
						$new_section = wp_insert_term( 
							sanitize_text_field( $section_data['name'] ), 
							'digi_program_section', 
							array(
								'slug' => sanitize_title( $section_data['slug'] ),
								'description' => sanitize_textarea_field( $section_data['description'] ),
								'parent' => intval( $section_data['parent'] ),
							) 
						);
						
						if ( ! is_wp_error( $new_section ) ) {
							$sections_created++;
							
							// Set section order
							if ( isset( $section_data['order'] ) ) {
								update_term_meta( $new_section['term_id'], 'section_order', intval( $section_data['order'] ) );
							}
							
							// Map old section term_id to new term_id
							if ($old_term_id > 0) {
								$section_map[ $old_term_id ] = $new_section['term_id'];
							}
							$section_map[ $section_data['slug'] ] = $new_section['term_id'];
						} else {
							$errors++;
						}
					}
				} catch (Exception $e) {
					$errors++;
				}
			}
		}
		
		// Import programs
		$program_map = array(); // Map old slugs to new IDs
		if ( ! empty( $programs_data['programs'] ) ) {
			$programs_created = 0;
			$programs_updated = 0;
			
			foreach ( $programs_data['programs'] as $program_data ) {
				try {
					// Check if program exists by slug
					$existing_program = get_page_by_path( $program_data['slug'], OBJECT, 'digi_program' );
					
					// Prepare post data
					$post_data = array(
						'post_title' => sanitize_text_field( $program_data['title'] ),
						'post_content' => wp_kses_post( $program_data['content'] ),
						'post_excerpt' => wp_kses_post( $program_data['excerpt'] ),
						'post_status' => sanitize_text_field( $program_data['status'] ),
						'post_name' => sanitize_title( $program_data['slug'] ),
						'post_type' => 'digi_program',
						'post_author' => get_current_user_id(),
					);
					
					if ( $existing_program ) {
						// Update existing program
						$post_data['ID'] = $existing_program->ID;
						$program_id = wp_update_post( $post_data );
						if (!is_wp_error($program_id)) {
							$programs_updated++;
						}
					} else {
						// Create new program
						$program_id = wp_insert_post( $post_data );
						if (!is_wp_error($program_id)) {
							$programs_created++;
						}
					}
					
					if ( is_wp_error( $program_id ) ) {
						$errors++;
						continue;
					}
					
					// Import featured image if available
					if ( ! empty( $program_data['featured_image'] ) ) {
						$featured_image_id = $this->import_attachment( $program_data['featured_image'], $program_id );
						if ( $featured_image_id ) {
							set_post_thumbnail( $program_id, $featured_image_id );
						}
					}
					
					// Map the program slug to the new ID
					$program_map[ $program_data['slug'] ] = $program_id;
					
					// Add meta data
					if ( ! empty( $program_data['meta'] ) ) {
						foreach ( $program_data['meta'] as $meta_key => $meta_value ) {
							update_post_meta( $program_id, $meta_key, $meta_value );
						}
					}
				} catch (Exception $e) {
					$errors++;
				}
			}
		}
		
		// Import lessons
		if ( ! empty( $programs_data['lessons'] ) ) {
			$lessons_created = 0;
			$lessons_updated = 0;
			
			foreach ( $programs_data['lessons'] as $lesson_data ) {
				try {
					// Check if lesson exists by slug
					$existing_lesson = get_page_by_path( $lesson_data['slug'], OBJECT, 'digi_lesson' );
					
					// Prepare post data
					$post_data = array(
						'post_title' => sanitize_text_field( $lesson_data['title'] ),
						'post_content' => wp_kses_post( $lesson_data['content'] ),
						'post_excerpt' => wp_kses_post( $lesson_data['excerpt'] ),
						'post_status' => sanitize_text_field( $lesson_data['status'] ),
						'post_name' => sanitize_title( $lesson_data['slug'] ),
						'post_type' => 'digi_lesson',
						'post_author' => get_current_user_id(),
					);
					
					if ( $existing_lesson ) {
						// Update existing lesson
						$post_data['ID'] = $existing_lesson->ID;
						$lesson_id = wp_update_post( $post_data );
						if (!is_wp_error($lesson_id)) {
							$lessons_updated++;
						}
					} else {
						// Create new lesson
						$lesson_id = wp_insert_post( $post_data );
						if (!is_wp_error($lesson_id)) {
							$lessons_created++;
						}
					}
					
					if ( is_wp_error( $lesson_id ) ) {
						$errors++;
						continue;
					}
					
					// Import featured image if available
					if ( ! empty( $lesson_data['featured_image'] ) ) {
						$featured_image_id = $this->import_attachment( $lesson_data['featured_image'], $lesson_id );
						if ( $featured_image_id ) {
							set_post_thumbnail( $lesson_id, $featured_image_id );
						}
					}
					
					// Add sections
					if ( ! empty( $lesson_data['sections'] ) ) {
						$section_ids = array();
						
						foreach ( $lesson_data['sections'] as $section ) {
							// Try to map by term_id first, then by slug
							if ( isset( $section['term_id'] ) && isset( $section_map[ $section['term_id'] ] ) ) {
								$section_ids[] = $section_map[ $section['term_id'] ];
							} elseif ( isset( $section['slug'] ) && isset( $section_map[ $section['slug'] ] ) ) {
								$section_ids[] = $section_map[ $section['slug'] ];
							}
						}
						
						if ( ! empty( $section_ids ) ) {
							wp_set_object_terms( $lesson_id, $section_ids, 'digi_program_section' );
						}
					}
					
					// Add meta data
					if ( ! empty( $lesson_data['meta'] ) ) {
						foreach ( $lesson_data['meta'] as $meta_key => $meta_value ) {
							// Handle program_id mapping if needed
							if ( $meta_key === 'digi_program_id' && ! empty( $program_map ) ) {
								// Try to find the program ID by the original program slug
								foreach ( $program_map as $slug => $id ) {
									if ( $id == $meta_value ) {
										$meta_value = $id;
										break;
									}
								}
							}
							
							update_post_meta( $lesson_id, $meta_key, $meta_value );
						}
					}
				} catch (Exception $e) {
					$errors++;
				}
			}
		}
		
		// Import quizzes
		if ( ! empty( $programs_data['quizzes'] ) ) {
			$quizzes_created = 0;
			$quizzes_updated = 0;
			
			foreach ( $programs_data['quizzes'] as $quiz_data ) {
				try {
					// Check if quiz exists by slug
					$existing_quiz = get_page_by_path( $quiz_data['slug'], OBJECT, 'digi_quiz' );
					
					// Prepare post data
					$post_data = array(
						'post_title' => sanitize_text_field( $quiz_data['title'] ),
						'post_content' => wp_kses_post( $quiz_data['content'] ),
						'post_status' => sanitize_text_field( $quiz_data['status'] ),
						'post_name' => sanitize_title( $quiz_data['slug'] ),
						'post_type' => 'digi_quiz',
						'post_author' => get_current_user_id(),
					);
					
					if ( $existing_quiz ) {
						// Update existing quiz
						$post_data['ID'] = $existing_quiz->ID;
						$quiz_id = wp_update_post( $post_data );
						if (!is_wp_error($quiz_id)) {
							$quizzes_updated++;
						}
					} else {
						// Create new quiz
						$quiz_id = wp_insert_post( $post_data );
						if (!is_wp_error($quiz_id)) {
							$quizzes_created++;
						}
					}
					
					if ( is_wp_error( $quiz_id ) ) {
						$errors++;
						continue;
					}
					
					// Add meta data
					if ( ! empty( $quiz_data['meta'] ) ) {
						foreach ( $quiz_data['meta'] as $meta_key => $meta_value ) {
							// Handle quiz settings mapping for section_id
							if ( $meta_key === 'digi_quiz_settings' && is_array( $meta_value ) ) {
								if ( isset( $meta_value['section_id'] ) && isset( $section_map[ $meta_value['section_id'] ] ) ) {
									$meta_value['section_id'] = $section_map[ $meta_value['section_id'] ];
								}
							}
							
							update_post_meta( $quiz_id, $meta_key, $meta_value );
						}
					}
				} catch (Exception $e) {
					$errors++;
				}
			}
		}
		
		// Import enrollments
		if ( ! empty( $programs_data['enrollments'] ) ) {
			$table_enrollments = $wpdb->prefix . 'digicommerce_program_enrollments';
			
			// Check if table exists
			if ( $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $table_enrollments ) ) === $table_enrollments ) {
				try {
					$enrollments_imported = 0;
					$enrollments_errors = 0;
					
					// Import enrollments
					foreach ( $programs_data['enrollments'] as $enrollment ) {
						// Verify it's either an object or array before proceeding
						if (!is_object($enrollment) && !is_array($enrollment)) {
							$enrollments_errors++;
							$errors++;
							continue;
						}
						
						// Convert to array if it's an object
						$enrollment_data = is_object($enrollment) ? (array)$enrollment : $enrollment;
						
						// Remove id to let auto-increment work
						unset($enrollment_data['id']);
						
						// Insert enrollment
						$result = $wpdb->insert($table_enrollments, $enrollment_data);
						if ($result) {
							$enrollments_imported++;
						} else {
							$enrollments_errors++;
							$errors++;
						}
					}
				} catch (Exception $e) {
					$errors++;
				}
			} else {
				$errors++;
			}
		}
		
		// Import progress
		if ( ! empty( $programs_data['progress'] ) ) {
			$table_progress = $wpdb->prefix . 'digicommerce_program_progress';
			
			// Check if table exists
			if ( $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $table_progress ) ) === $table_progress ) {
				try {
					$progress_imported = 0;
					$progress_errors = 0;
					
					// Import progress
					foreach ( $programs_data['progress'] as $progress ) {
						// Verify it's either an object or array before proceeding
						if (!is_object($progress) && !is_array($progress)) {
							$progress_errors++;
							$errors++;
							continue;
						}
						
						// Convert to array if it's an object
						$progress_data = is_object($progress) ? (array)$progress : $progress;
						
						// Remove id to let auto-increment work
						unset($progress_data['id']);
						
						// Insert progress
						$result = $wpdb->insert($table_progress, $progress_data);
						if ($result) {
							$progress_imported++;
						} else {
							$progress_errors++;
							$errors++;
						}
					}
				} catch (Exception $e) {
					$errors++;
				}
			} else {
				$errors++;
			}
		}
		
		return ($errors === 0);
	}

	/**
	 * Import affiliates
	 * 
	 * @param array $affiliates_data Affiliates data to import.
	 * @return bool Success status.
	 */
	private function import_affiliates( $affiliates_data ) {
		global $wpdb;
		
		if ( empty( $affiliates_data ) ) {
			return false;
		}
		
		// First, ensure the affiliation feature is enabled
		update_option( 'digicommerce_enable_affiliation', 1 );
		
		// Make sure affiliation tables exist
		$table_affiliates = $wpdb->prefix . 'digicommerce_affiliates';
		$table_referrals = $wpdb->prefix . 'digicommerce_affiliate_referrals';
		$table_visits = $wpdb->prefix . 'digicommerce_affiliate_visits';
		$table_payouts = $wpdb->prefix . 'digicommerce_affiliate_payouts';
		$table_meta = $wpdb->prefix . 'digicommerce_affiliate_meta';
		
		// Check if tables exist, create them if not
		if ( $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $table_affiliates ) ) !== $table_affiliates ) {
			// Tables don't exist, initialize the affiliation class to create them
			if ( class_exists( 'DigiCommerce_Pro_Affiliation' ) ) {
				try {
					DigiCommerce_Pro_Affiliation::instance();
				} catch (Exception $e) {
					return false;
				}
			} else {
				return false;
			}
		}
		
		$errors = 0;
		
		// Import affiliates
		if ( ! empty( $affiliates_data['affiliates'] ) ) {
			
			// Map old affiliate IDs to new ones
			$affiliate_map = array();
			$affiliates_created = 0;
			$affiliates_updated = 0;
			
			// Import affiliates
			foreach ( $affiliates_data['affiliates'] as $affiliate ) {
				try {
					// Verify it's either an object or array before proceeding
					if (!is_object($affiliate) && !is_array($affiliate)) {
						$errors++;
						continue;
					}
					
					// Convert to array if it's an object
					$affiliate_data = is_object($affiliate) ? (array)$affiliate : $affiliate;
					
					$old_id = isset($affiliate_data['id']) ? $affiliate_data['id'] : 0;
					$affiliate_id = isset($affiliate_data['affiliate_id']) ? $affiliate_data['affiliate_id'] : '';
					
					if (empty($affiliate_id)) {
						$errors++;
						continue;
					}
					
					// Remove id to let auto-increment work
					unset($affiliate_data['id']);
					
					// Check if affiliate already exists
					$existing_affiliate = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$table_affiliates} WHERE affiliate_id = %s", $affiliate_id ) );
					
					if ( $existing_affiliate ) {
						// Update existing affiliate
						$result = $wpdb->update( $table_affiliates, $affiliate_data, array( 'id' => $existing_affiliate ) );
						if ($result !== false) {
							$affiliates_updated++;
							$affiliate_map[ $old_id ] = $existing_affiliate;
						} else {
							$errors++;
						}
					} else {
						// Insert new affiliate
						$result = $wpdb->insert( $table_affiliates, $affiliate_data );
						if ($result) {
							$affiliates_created++;
							$new_id = $wpdb->insert_id;
							$affiliate_map[ $old_id ] = $new_id;
						} else {
							$errors++;
						}
					}
				} catch (Exception $e) {
					$errors++;
				}
			}
			
			// Import referrals
			if ( ! empty( $affiliates_data['referrals'] ) ) {
				
				// Check if table exists
				if ( $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $table_referrals ) ) === $table_referrals ) {
					try {
						$referrals_imported = 0;
						$referrals_errors = 0;
						
						// Import referrals
						foreach ( $affiliates_data['referrals'] as $referral ) {
							// Verify it's either an object or array before proceeding
							if (!is_object($referral) && !is_array($referral)) {
								$referrals_errors++;
								$errors++;
								continue;
							}
							
							// Convert to array if it's an object
							$referral_data = is_object($referral) ? (array)$referral : $referral;
							
							// Remove id to let auto-increment work
							unset($referral_data['id']);
							
							// Insert referral
							$result = $wpdb->insert($table_referrals, $referral_data);
							if ($result) {
								$referrals_imported++;
							} else {
								$referrals_errors++;
								$errors++;
							}
						}
					} catch (Exception $e) {
						$errors++;
					}
				} else {
					$errors++;
				}
			}
			
			// Import visits
			if ( ! empty( $affiliates_data['visits'] ) ) {
				
				// Check if table exists
				if ( $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $table_visits ) ) === $table_visits ) {
					try {
						$visits_imported = 0;
						$visits_errors = 0;
						
						// Import visits
						foreach ( $affiliates_data['visits'] as $visit ) {
							// Verify it's either an object or array before proceeding
							if (!is_object($visit) && !is_array($visit)) {
								$visits_errors++;
								$errors++;
								continue;
							}
							
							// Convert to array if it's an object
							$visit_data = is_object($visit) ? (array)$visit : $visit;
							
							// Remove id to let auto-increment work
							unset($visit_data['id']);
							
							// Insert visit
							$result = $wpdb->insert($table_visits, $visit_data);
							if ($result) {
								$visits_imported++;
							} else {
								$visits_errors++;
								$errors++;
							}
						}
					} catch (Exception $e) {
						$errors++;
					}
				} else {
					$errors++;
				}
			}
			
			// Import payouts
			if ( ! empty( $affiliates_data['payouts'] ) ) {
				
				// Check if table exists
				if ( $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $table_payouts ) ) === $table_payouts ) {
					try {
						$payouts_imported = 0;
						$payouts_errors = 0;
						
						// Import payouts
						foreach ( $affiliates_data['payouts'] as $payout ) {
							// Verify it's either an object or array before proceeding
							if (!is_object($payout) && !is_array($payout)) {
								$payouts_errors++;
								$errors++;
								continue;
							}
							
							// Convert to array if it's an object
							$payout_data = is_object($payout) ? (array)$payout : $payout;
							
							// Remove id to let auto-increment work
							unset($payout_data['id']);
							
							// Insert payout
							$result = $wpdb->insert($table_payouts, $payout_data);
							if ($result) {
								$payouts_imported++;
							} else {
								$payouts_errors++;
								$errors++;
							}
						}
					} catch (Exception $e) {
						$errors++;
					}
				} else {
					$errors++;
				}
			}
			
			// Import meta
			if ( ! empty( $affiliates_data['meta'] ) ) {
				
				// Check if table exists
				if ( $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $table_meta ) ) === $table_meta ) {
					try {
						$meta_imported = 0;
						$meta_errors = 0;
						
						// Import meta
						foreach ( $affiliates_data['meta'] as $meta ) {
							// Verify it's either an object or array before proceeding
							if (!is_object($meta) && !is_array($meta)) {
								$meta_errors++;
								$errors++;
								continue;
							}
							
							// Convert to array if it's an object
							$meta_data = is_object($meta) ? (array)$meta : $meta;
							
							// Remove id to let auto-increment work
							unset($meta_data['id']);
							
							// Insert meta
							$result = $wpdb->insert($table_meta, $meta_data);
							if ($result) {
								$meta_imported++;
							} else {
								$meta_errors++;
								$errors++;
							}
						}
					} catch (Exception $e) {
						$errors++;
					}
				} else {
					$errors++;
				}
			}
		}
		
		return ($errors === 0);
	}

	/**
	 * Set terms by name for a post
	 *
	 * @param int    $post_id    Post ID.
	 * @param array  $term_names Array of term names.
	 * @param string $taxonomy   Taxonomy name.
	 */
	private function set_terms_by_name( $post_id, $term_names, $taxonomy ) {
		$term_ids = array();
		
		foreach ( $term_names as $term_name ) {
			$term = get_term_by( 'name', $term_name, $taxonomy );
			
			if ( $term ) {
				$term_ids[] = $term->term_id;
			} else {
				// Create term if it doesn't exist
				$new_term = wp_insert_term( sanitize_text_field( $term_name ), $taxonomy );
				if ( ! is_wp_error( $new_term ) ) {
					$term_ids[] = $new_term['term_id'];
				}
			}
		}
		
		if ( ! empty( $term_ids ) ) {
			wp_set_object_terms( $post_id, $term_ids, $taxonomy );
		}
	}

	/**
	 * Enqueue assets
	 * 
	 * @param string $hook Current admin page hook.
	 */
	public function enqueue_scripts( $hook ) {
		if ( 'digicommerce_page_digicommerce-import-export' !== $hook ) {
			return;
		}

		wp_enqueue_style(
			'digicommerce-import-export',
			DIGICOMMERCE_PLUGIN_URL . 'assets/css/admin/import-export.css',
			array(),
			DIGICOMMERCE_VERSION
		);

		wp_enqueue_script(
			'digicommerce-import-export',
			DIGICOMMERCE_PLUGIN_URL . 'assets/js/admin/import-export.js',
			array(),
			DIGICOMMERCE_VERSION,
			true
		);

		// Add localization
		wp_localize_script(
			'digicommerce-import-export',
			'digiCommerceImportExport',
			array(
				'selectAll' => esc_html__( 'Select All', 'digicommerce' ),
				'deselectAll' => esc_html__( 'Deselect All', 'digicommerce' ),
				'confirmImport' => esc_html__( 'Are you sure you want to import this file? This may overwrite existing data.', 'digicommerce' ),
			)
		);
	}

	/**
	 * Customize admin footer
	 *
	 * @param string $text Footer text.
	 */
	public function footer_text( $text ) {
		$screen = get_current_screen();

		if ( 'digicommerce_page_digicommerce-import-export' === $screen->id ) {
			$text = sprintf(
				/* translators: %1$s: Plugin review link */
				esc_html__( 'Please rate %2$sDigiCommerce%3$s %4$s&#9733;&#9733;&#9733;&#9733;&#9733;%5$s on %6$sWordPress.org%7$s to help us spread the word.', 'digicommerce' ),
				'https://wordpress.org/support/plugin/digicommerce/reviews/#new-post',
				'<strong>',
				'</strong>',
				'<a href="https://wordpress.org/support/plugin/digicommerce/reviews/#new-post" target="_blank" rel="noopener noreferrer">',
				'</a>',
				'<a href="https://wordpress.org/support/plugin/digicommerce/reviews/#new-post" target="_blank" rel="noopener noreferrer">',
				'</a>'
			);
		}

		return $text;
	}

	/**
	 * Customize admin footer version
	 *
	 * @param string $version Footer version.
	 */
	public function update_footer( $version ) {
		$screen = get_current_screen();

		if ( 'digicommerce_page_digicommerce-import-export' === $screen->id ) {
			$name = class_exists( 'DigiCommerce_Pro' ) ? 'DigiCommerce Pro' : 'DigiCommerce';

			$version .= sprintf( ' | %1$s %2$s', $name, DIGICOMMERCE_VERSION );
		}

		return $version;
	}

	/**
	 * Add dir attr to HTML for LTR direction for compatibility with Tailwind
	 *
	 * @param string $lang_attr HTML lang attribute.
	 */
	public function attribute_to_html( $lang_attr ) {
		if ( ! is_rtl() ) {
			// Only add dir="ltr" when the site is NOT in RTL mode
			return $lang_attr . ' dir="ltr"';
		}

		return $lang_attr;
	}
}

// Initialize the class.
DigiCommerce_Import_Export::instance();