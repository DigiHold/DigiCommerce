<?php
/**
 * Reports Class for DigiCommerce
 * Handles all reporting functionality
 */
class DigiCommerce_Reports {
    /**
     * Instance of the class
     */
    private static $instance = null;

    /**
     * Get instance of the class
     */
    public static function instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor: Initialize hooks
     */
    public function __construct() {
        // Add menu item
        add_action('admin_menu', array($this, 'add_reports_menu'), 99);

        // Add scripts and styles
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));

        // Register AJAX endpoints
        add_action('wp_ajax_digicommerce_reports_overview', array($this, 'get_overview_data'));
        add_action('wp_ajax_digicommerce_reports_products', array($this, 'get_products_data'));
        add_action('wp_ajax_digicommerce_reports_customers', array($this, 'get_customers_data'));
        add_action('wp_ajax_digicommerce_reports_taxes', array($this, 'get_taxes_data'));
		if (class_exists('DigiCommerce_Pro')) {
			if (DigiCommerce()->get_option('enable_coupon_code', false)) {
				add_action('wp_ajax_digicommerce_reports_coupons', array($this, 'get_coupons_data'));
			}
			if (DigiCommerce()->get_option('enable_subscriptions', false)) {
				add_action('wp_ajax_digicommerce_reports_subscriptions', array($this, 'get_subscriptions_data'));
			}
			if (DigiCommerce()->get_option('enable_abandoned_cart', false)) {
				add_action('wp_ajax_digicommerce_reports_abandoned_cart', array($this, 'get_abandoned_cart_data'));
			}
		}

		// Custom footer texts
		add_filter( 'admin_footer_text', array( $this, 'footer_text' ), 99 );
		add_filter( 'update_footer', array( $this, 'update_footer' ), 99 );
    }

    /**
     * Add reports menu
     */
    public function add_reports_menu() {
        add_submenu_page(
            'digicommerce-settings',
            esc_html__('Reports', 'digicommerce'),
            esc_html__('Reports', 'digicommerce'),
            'manage_options',
            'digicommerce-reports',
            array($this, 'render_reports_page')
        );
    }

    /**
     * Enqueue scripts and styles
     */
    public function enqueue_scripts($hook) {
        if ('digicommerce_page_digicommerce-reports' !== $hook) {
            return;
        }

        // Enqueue Chart.js
        wp_enqueue_script(
            'chartjs',
            'https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js',
            array(),
            '4.4.1',
            true
        );

        // Enqueue our reports script
        wp_enqueue_script(
            'digicommerce-reports',
            DIGICOMMERCE_PLUGIN_URL . 'assets/js/admin/reports.js',
            array('chartjs'),
            DIGICOMMERCE_VERSION,
            true
        );

        // Enqueue our reports styles
        wp_enqueue_style(
            'digicommerce-reports',
            DIGICOMMERCE_PLUGIN_URL . 'assets/css/admin/reports.css',
            array(),
            DIGICOMMERCE_VERSION
        );

        // Localize script
        wp_localize_script(
            'digicommerce-reports',
            'digicommerceReports',
            array(
                'nonce' => wp_create_nonce('digicommerce_reports_nonce'),
                'ajaxurl' => admin_url('admin-ajax.php'),
                'currency' => DigiCommerce()->get_option('currency', 'USD'),
                'currency_position' => DigiCommerce()->get_option( 'currency_position', 'left' ),
                'countries' => DigiCommerce()->get_countries(),
                'i18n' => array(
					'revenue' => esc_html__('Revenue', 'digicommerce'),
					'sales' => esc_html__('Sales', 'digicommerce'),
					'orders' => esc_html__('Orders', 'digicommerce'),
					'customers' => esc_html__('Customers', 'digicommerce'),
					'loading' => esc_html__('Loading...', 'digicommerce'),
					'error_loading' => esc_html__('Error loading report data. Please try again.', 'digicommerce'),
					'product' => esc_html__('Product', 'digicommerce'),
					'orders_header' => esc_html__('Orders', 'digicommerce'),
					'revenue_header' => esc_html__('Revenue', 'digicommerce'),
					'country' => esc_html__('Country', 'digicommerce'),
					'vat_rate' => esc_html__('VAT Rate', 'digicommerce'),
					'vat_amount' => esc_html__('VAT Amount', 'digicommerce'),
					'total_amount' => esc_html__('Total Amount', 'digicommerce'),
					'customer' => esc_html__('Customer', 'digicommerce'),
					'total_spent' => esc_html__('Total Spent', 'digicommerce'),
					'last_order' => esc_html__('Last Order', 'digicommerce'),
					'customer_lifetime' => esc_html__('Customer Lifetime Value', 'digicommerce'),
					'avg_lifetime' => esc_html__('Average Lifetime Value', 'digicommerce'),
					'max_lifetime' => esc_html__('Maximum Lifetime Value', 'digicommerce'),
					'coupon_code' => esc_html__('Coupon Code', 'digicommerce'),
					'usage_count' => esc_html__('Usage Count', 'digicommerce'),
					'total_discount' => esc_html__('Total Discount', 'digicommerce'),
					'subscription_status' => esc_html__('Status', 'digicommerce'),
					'subscription_count' => esc_html__('Subscriptions', 'digicommerce'),
					'mrr' => esc_html__('Monthly Recurring Revenue', 'digicommerce'),
					'churn_rate' => esc_html__('Churn Rate', 'digicommerce'),
					'no_data' => esc_html__('No data available', 'digicommerce'),
					'active_subscriptions' => esc_html__('Active Subscriptions', 'digicommerce'),
					'total_abandoned' => esc_html__('Total Abandoned', 'digicommerce'),
					'total_recovered' => esc_html__('Total Recovered', 'digicommerce'),  
					'recovery_rate' => esc_html__('Recovery Rate', 'digicommerce'),
					'customer_email' => esc_html__('Customer Email', 'digicommerce'),
					'recovered_date' => esc_html__('Recovery Date', 'digicommerce'),
					'order_id' => esc_html__('Order ID', 'digicommerce'),  
					'revenue' => esc_html__('Revenue', 'digicommerce'),
					'coupon_used' => esc_html__('Coupon Used', 'digicommerce'),
				)
            )
        );
    }

    /**
     * AJAX handler for overview data
     */
    public function get_overview_data() {
		check_ajax_referer('digicommerce_reports_nonce', 'nonce');
		
		if (!current_user_can('manage_options')) {
			wp_send_json_error('Insufficient permissions');
		}
	
		$range = isset($_POST['range']) ? sanitize_text_field($_POST['range']) : 'this_month';
		
		// Enhanced date validation
		$start_date = null;
		$end_date = null;
		
		if ($range === 'custom') {
			// Validate start date
			if (isset($_POST['start_date']) && $this->validate_date($_POST['start_date'])) {
				$start_date = sanitize_text_field($_POST['start_date']);
			} else {
				wp_send_json_error('Invalid start date format');
			}
			
			// Validate end date
			if (isset($_POST['end_date']) && $this->validate_date($_POST['end_date'])) {
				$end_date = sanitize_text_field($_POST['end_date']);
			} else {
				wp_send_json_error('Invalid end date format');
			}
			
			// Additional validation for date range
			$start = new DateTime($start_date);
			$end = new DateTime($end_date);
			
			if ($end < $start) {
				wp_send_json_error('End date must be after start date');
			}
		}
	
		$data = array(
			'summary' => $this->get_reports_data($range, $start_date, $end_date),
			'top_products' => $this->get_top_products(),
			'payment_methods' => $this->get_revenue_by_payment_method(),
			'refund_stats' => $this->get_refund_stats()
		);
	
		// Add subscription data if Pro is active
		if (class_exists('DigiCommerce_Pro')) {
			$data['subscription_stats'] = $this->get_subscription_analytics();
		}
	
		wp_send_json_success($data);
	}

	/**
	 * Validate date format
	 */
	private function validate_date($date) {
		$d = DateTime::createFromFormat('Y-m-d', $date);
		return $d && $d->format('Y-m-d') === $date;
	}

    /**
     * AJAX handler for products data
     */
    public function get_products_data() {
        check_ajax_referer('digicommerce_reports_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }

        $range = isset($_POST['range']) ? sanitize_text_field($_POST['range']) : 'this_month';
        $start_date = null;
        $end_date = null;

        if ($range === 'custom') {
            if (isset($_POST['start_date']) && $this->validate_date($_POST['start_date'])) {
                $start_date = sanitize_text_field($_POST['start_date']);
            } else {
                wp_send_json_error('Invalid start date format');
            }
            
            if (isset($_POST['end_date']) && $this->validate_date($_POST['end_date'])) {
                $end_date = sanitize_text_field($_POST['end_date']);
            } else {
                wp_send_json_error('Invalid end date format');
            }
        }

        $dates = $this->get_date_range($range, $start_date, $end_date);
        $data = $this->get_top_products(-1, $dates['start'], $dates['end']);
        wp_send_json_success($data);
    }

    /**
     * AJAX handler for customers data
     */
    public function get_customers_data() {
        check_ajax_referer('digicommerce_reports_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }

        $range = isset($_POST['range']) ? sanitize_text_field($_POST['range']) : 'this_month';
        $start_date = null;
        $end_date = null;

        if ($range === 'custom') {
            if (isset($_POST['start_date']) && $this->validate_date($_POST['start_date'])) {
                $start_date = sanitize_text_field($_POST['start_date']);
            } else {
                wp_send_json_error('Invalid start date format');
            }
            
            if (isset($_POST['end_date']) && $this->validate_date($_POST['end_date'])) {
                $end_date = sanitize_text_field($_POST['end_date']);
            } else {
                wp_send_json_error('Invalid end date format');
            }
        }

        $dates = $this->get_date_range($range, $start_date, $end_date);
        $data = array(
            'top_customers' => $this->get_top_customers(-1, $dates['start'], $dates['end']),
            'lifetime_value' => $this->get_customer_lifetime_value($dates['start'], $dates['end'])
        );

        wp_send_json_success($data);
    }

    /**
     * AJAX handler for taxes data
     */
    public function get_taxes_data() {
        check_ajax_referer('digicommerce_reports_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }

        $range = isset($_POST['range']) ? sanitize_text_field($_POST['range']) : 'this_month';
        $start_date = null;
        $end_date = null;

        if ($range === 'custom') {
            if (isset($_POST['start_date']) && $this->validate_date($_POST['start_date'])) {
                $start_date = sanitize_text_field($_POST['start_date']);
            } else {
                wp_send_json_error('Invalid start date format');
            }
            
            if (isset($_POST['end_date']) && $this->validate_date($_POST['end_date'])) {
                $end_date = sanitize_text_field($_POST['end_date']);
            } else {
                wp_send_json_error('Invalid end date format');
            }
        }

        $dates = $this->get_date_range($range, $start_date, $end_date);
        $data = array(
            'vat_totals' => $this->get_vat_totals($dates['start'], $dates['end'])
        );

        wp_send_json_success($data);
    }

	/**
	 * AJAX handler for coupons data
	 */
	public function get_coupons_data() {
		check_ajax_referer('digicommerce_reports_nonce', 'nonce');
		
		if (!current_user_can('manage_options')) {
			wp_send_json_error('Insufficient permissions');
		}

		$range = isset($_POST['range']) ? sanitize_text_field($_POST['range']) : 'this_month';
		$start_date = null;
		$end_date = null;

		if ($range === 'custom') {
			if (isset($_POST['start_date']) && $this->validate_date($_POST['start_date'])) {
				$start_date = sanitize_text_field($_POST['start_date']);
			} else {
				wp_send_json_error('Invalid start date format');
			}
			
			if (isset($_POST['end_date']) && $this->validate_date($_POST['end_date'])) {
				$end_date = sanitize_text_field($_POST['end_date']);
			} else {
				wp_send_json_error('Invalid end date format');
			}
		}

		$dates = $this->get_date_range($range, $start_date, $end_date);
		$stats = $this->get_coupon_usage_stats($dates['start'], $dates['end']);

		wp_send_json_success(array('usage' => $stats));
	}

	/**
	 * AJAX handler for subscriptions data
	 */
	public function get_subscriptions_data() {
		check_ajax_referer('digicommerce_reports_nonce', 'nonce');
		
		if (!current_user_can('manage_options')) {
			wp_send_json_error('Insufficient permissions');
		}

		$range = isset($_POST['range']) ? sanitize_text_field($_POST['range']) : 'this_month';
		$start_date = null;
		$end_date = null;

		if ($range === 'custom') {
			if (isset($_POST['start_date']) && $this->validate_date($_POST['start_date'])) {
				$start_date = sanitize_text_field($_POST['start_date']);
			} else {
				wp_send_json_error('Invalid start date format');
			}
			
			if (isset($_POST['end_date']) && $this->validate_date($_POST['end_date'])) {
				$end_date = sanitize_text_field($_POST['end_date']);
			} else {
				wp_send_json_error('Invalid end date format');
			}
		}

		$dates = $this->get_date_range($range, $start_date, $end_date);
		$stats = $this->get_subscription_stats($dates['start'], $dates['end']);

		wp_send_json_success(array('stats' => $stats));
	}

	/**
	 * AJAX handler for abandoned cart data
	 */
	public function get_abandoned_cart_data() {
		check_ajax_referer('digicommerce_reports_nonce', 'nonce');
		
		if (!current_user_can('manage_options')) {
			wp_send_json_error('Insufficient permissions');
		}

		$range = isset($_POST['range']) ? sanitize_text_field($_POST['range']) : 'this_month';
		$start_date = null;
		$end_date = null;

		if ($range === 'custom') {
			if (isset($_POST['start_date']) && $this->validate_date($_POST['start_date'])) {
				$start_date = sanitize_text_field($_POST['start_date']);
			} else {
				wp_send_json_error('Invalid start date format');
			}
			
			if (isset($_POST['end_date']) && $this->validate_date($_POST['end_date'])) {
				$end_date = sanitize_text_field($_POST['end_date']);
			} else {
				wp_send_json_error('Invalid end date format');
			}
		}

		$dates = $this->get_date_range($range, $start_date, $end_date);
		$stats = $this->get_abandoned_cart_stats($dates['start'], $dates['end']);

		wp_send_json_success(array('stats' => $stats));
	}

    /**
     * Get reports data
     */
    public function get_reports_data($range = 'this_month', $start_date = null, $end_date = null) {
		global $wpdb;
	
		// Get date range
		$dates = $this->get_date_range($range, $start_date, $end_date);
		
		// Get current period data
		$current_data = $this->get_period_data($dates['start'], $dates['end']);
	
		// Get chart data
		$chart_data = $this->get_chart_data($dates['start'], $dates['end']);
	
		return array(
			'revenue' => $current_data['revenue'],
			'net' => $current_data['net'],
			'orders' => $current_data['orders'],
			'average' => $current_data['average'],
			'chart' => $chart_data
		);
	}

    /**
     * Get period data
     */
    private function get_period_data($start_date, $end_date) {
		global $wpdb;
	
		$results = $wpdb->get_row($wpdb->prepare(
			"SELECT 
				COUNT(*) as orders,
				SUM(total) as revenue,
				SUM(subtotal) as net,
				CASE 
					WHEN COUNT(*) > 0 THEN SUM(total) / COUNT(*)
					ELSE 0 
				END as average
			FROM {$wpdb->prefix}digicommerce_orders
			WHERE status IN ('completed')
			AND date_created BETWEEN %s AND %s",
			$start_date,
			$end_date
		));
	
		return array(
			'orders' => intval($results->orders),
			'revenue' => floatval($results->revenue),
			'net' => floatval($results->net),
			'average' => floatval($results->average)
		);
	}

    /**
     * Get chart data
     */
    private function get_chart_data($start_date, $end_date) {
        global $wpdb;

        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT 
                DATE(date_created) as date,
                SUM(total) as revenue
            FROM {$wpdb->prefix}digicommerce_orders
            WHERE status IN ('completed')
            AND date_created BETWEEN %s AND %s
            GROUP BY DATE(date_created)
            ORDER BY date_created ASC",
            $start_date,
            $end_date
        ));

        $labels = array();
        $data = array();

        $current = new DateTime($start_date);
        $end = new DateTime($end_date);
        $interval = new DateInterval('P1D');

        // Create a lookup array for the results
        $revenue_by_date = array();
        foreach ($results as $row) {
            $revenue_by_date[$row->date] = $row->revenue;
        }

        // Fill in all dates
        while ($current <= $end) {
            $date = $current->format('Y-m-d');
            $labels[] = $current->format('M j');
            $data[] = isset($revenue_by_date[$date]) ? floatval($revenue_by_date[$date]) : 0;
            $current->add($interval);
        }

        return array(
            'labels' => $labels,
            'revenue' => $data
        );
    }

    /**
     * Get top products
     */
    private function get_top_products($limit = 5, $start_date = null, $end_date = null) {
        global $wpdb;
    
        $query = "SELECT 
            i.product_id,
            i.name,
            COUNT(DISTINCT i.order_id) as orders,
            SUM(i.total) as revenue
        FROM {$wpdb->prefix}digicommerce_order_items i
        JOIN {$wpdb->prefix}digicommerce_orders o ON i.order_id = o.id
        WHERE o.status IN ('completed')";

        if ($start_date && $end_date) {
            $query .= $wpdb->prepare(" AND o.date_created BETWEEN %s AND %s", $start_date, $end_date);
        }

        $query .= " GROUP BY i.product_id, i.name
                   ORDER BY revenue DESC";

        if ($limit > 0) {
            $query .= $wpdb->prepare(" LIMIT %d", $limit);
        }
    
        return $wpdb->get_results($query);
    }

    /**
     * Get VAT totals by country
     */
    private function get_vat_totals($start_date = null, $end_date = null) {
        global $wpdb;

        $query = "SELECT 
            b.country,
            o.vat_rate,
            COUNT(*) as orders,
            SUM(o.vat) as vat_amount,
            SUM(o.total) as total_amount
        FROM {$wpdb->prefix}digicommerce_orders o
        JOIN {$wpdb->prefix}digicommerce_order_billing b ON o.id = b.order_id
        WHERE o.status IN ('completed')
        AND o.vat > 0";

        if ($start_date && $end_date) {
            $query .= $wpdb->prepare(" AND o.date_created BETWEEN %s AND %s", $start_date, $end_date);
        }

        $query .= " GROUP BY b.country, o.vat_rate
                   ORDER BY vat_amount DESC";

        return $wpdb->get_results($query);
    }

    /**
     * Get revenue by payment method
     */
    private function get_revenue_by_payment_method() {
		global $wpdb;
	
		return $wpdb->get_results(
			"SELECT 
				payment_method,
				COUNT(*) as orders,
				SUM(total) as revenue
			FROM {$wpdb->prefix}digicommerce_orders
			WHERE status IN ('completed')
			AND payment_method != ''
			GROUP BY payment_method
			ORDER BY revenue DESC"
		);
	}

    /**
     * Get refund stats
     */
    private function get_refund_stats() {
        global $wpdb;

        return $wpdb->get_row(
            "SELECT 
                COUNT(*) as total_refunds,
                SUM(total) as refunded_amount,
                AVG(total) as average_refund
            FROM {$wpdb->prefix}digicommerce_orders
            WHERE status = 'refunded'"
        );
    }

    /**
     * Get top customers
     */
    private function get_top_customers($limit = 5, $start_date = null, $end_date = null) {
        global $wpdb;

        $query = "SELECT 
            o.user_id,
            u.display_name as name,
            COUNT(*) as orders,
            SUM(o.total) as total_spent,
            MAX(o.date_created) as last_order
        FROM {$wpdb->prefix}digicommerce_orders o
        JOIN {$wpdb->users} u ON o.user_id = u.ID
        WHERE o.status IN ('completed')";

        if ($start_date && $end_date) {
            $query .= $wpdb->prepare(" AND o.date_created BETWEEN %s AND %s", $start_date, $end_date);
        }

        $query .= " GROUP BY o.user_id, u.display_name
                   ORDER BY total_spent DESC";

        if ($limit > 0) {
            $query .= $wpdb->prepare(" LIMIT %d", $limit);
        }

        return $wpdb->get_results($query);
    }

    /**
     * Get customer lifetime value
     */
    private function get_customer_lifetime_value($start_date = null, $end_date = null) {
        global $wpdb;

        $subquery = "SELECT 
            user_id,
            SUM(total) as total_spent
        FROM {$wpdb->prefix}digicommerce_orders
        WHERE status IN ('completed')";

        if ($start_date && $end_date) {
            $subquery .= $wpdb->prepare(" AND date_created BETWEEN %s AND %s", $start_date, $end_date);
        }

        $subquery .= " GROUP BY user_id";

        return $wpdb->get_row("
            SELECT 
                AVG(total_spent) as avg_lifetime_value,
                MAX(total_spent) as max_lifetime_value
            FROM ($subquery) as customer_totals
        ");
    }

	/**
	 * Get coupon usage statistics
	 */
	public function get_coupon_usage_stats($start_date = null, $end_date = null) {
		// Use Pro Coupons class instance
		if (class_exists('DigiCommerce_Pro') && DigiCommerce()->get_option('enable_coupon_code', false)) {
			return DigiCommerce_Pro_Coupons::instance()->get_coupon_usage_stats($start_date, $end_date);
		}
		return array();
	}

	/**
	 * Get subscription statistics
	 */
	public function get_subscription_stats($start_date = null, $end_date = null) {
		// Use Pro Subscriptions class instance 
		if (class_exists('DigiCommerce_Pro') && DigiCommerce()->get_option('enable_subscriptions', false)) {
			return DigiCommerce_Pro_Subscriptions::instance()->get_subscription_stats($start_date, $end_date);
		}
		return array();
	}

	/**
	 * Get abandoned cart statistics
	 */
	public function get_abandoned_cart_stats($start_date = null, $end_date = null) {
		// Use Pro Coupons class instance
		if (class_exists('DigiCommerce_Pro') && DigiCommerce()->get_option('enable_abandoned_cart', false)) {
			return DigiCommerce_Pro_Abandoned_Cart::instance()->get_abandoned_cart_stats($start_date, $end_date);
		}
		return array();
	}

    /**
     * Get subscription analytics if Pro version is active
     */
    private function get_subscription_analytics() {
		if (!class_exists('DigiCommerce_Pro')) {
			return false;
		}
	
		global $wpdb;
	
		return $wpdb->get_row(
			"SELECT 
				COUNT(*) as total_subscriptions,
				SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_subscriptions,
				AVG(total) as average_subscription_value  /* Changed from amount to total */
			FROM {$wpdb->prefix}digicommerce_subscriptions"
		);
	}

    /**
     * Get date range
     */
    private function get_date_range($range, $start_date = null, $end_date = null) {
        $now = new DateTime();
        $now->setTime(23, 59, 59);

        switch ($range) {
			case 'today':
				$start = new DateTime();
				$start->setTime(0, 0, 0);
				return array(
					'start' => $start->format('Y-m-d H:i:s'),
					'end' => $now->format('Y-m-d H:i:s')
				);

			case 'yesterday':
				$start = new DateTime('yesterday');
				$start->setTime(0, 0, 0);
				$end = new DateTime('yesterday');
				$end->setTime(23, 59, 59);
				return array(
					'start' => $start->format('Y-m-d H:i:s'),
					'end' => $end->format('Y-m-d H:i:s')
				);

			case 'this_week':
				$start = new DateTime();
				$start->modify('this week monday');
				$start->setTime(0, 0, 0);
				return array(
					'start' => $start->format('Y-m-d H:i:s'),
					'end' => $now->format('Y-m-d H:i:s')
				);

			case 'last_week':
				$start = new DateTime();
				$start->modify('last week monday');
				$start->setTime(0, 0, 0);
				$end = clone $start;
				$end->modify('sunday');
				$end->setTime(23, 59, 59);
				return array(
					'start' => $start->format('Y-m-d H:i:s'),
					'end' => $end->format('Y-m-d H:i:s')
				);

			case 'this_month':
				$start = new DateTime('first day of this month');
				$start->setTime(0, 0, 0);
				return array(
					'start' => $start->format('Y-m-d H:i:s'),
					'end' => $now->format('Y-m-d H:i:s')
				);

			case 'last_month':
				$start = new DateTime('first day of last month');
				$start->setTime(0, 0, 0);
				$end = new DateTime('last day of last month');
				$end->setTime(23, 59, 59);
				return array(
					'start' => $start->format('Y-m-d H:i:s'),
					'end' => $end->format('Y-m-d H:i:s')
				);

			case 'this_quarter':
				$start = new DateTime();
				$start->setTime(0, 0, 0);
				$start->setDate($start->format('Y'), ceil($start->format('n') / 3) * 3 - 2, 1);
				return array(
					'start' => $start->format('Y-m-d H:i:s'),
					'end' => $now->format('Y-m-d H:i:s')
				);

			case 'last_quarter':
				$start = new DateTime();
				$start->setTime(0, 0, 0);
				$start->modify('-3 months');
				$start->setDate($start->format('Y'), ceil($start->format('n') / 3) * 3 - 2, 1);
				$end = clone $start;
				$end->modify('+2 months');
				$end->modify('last day of this month');
				$end->setTime(23, 59, 59);
				return array(
					'start' => $start->format('Y-m-d H:i:s'),
					'end' => $end->format('Y-m-d H:i:s')
				);

			case 'this_year':
				$start = new DateTime('first day of January ' . date('Y'));
				$start->setTime(0, 0, 0);
				return array(
					'start' => $start->format('Y-m-d H:i:s'),
					'end' => $now->format('Y-m-d H:i:s')
				);

			case 'last_year':
				$start = new DateTime('first day of January ' . (date('Y') - 1));
				$start->setTime(0, 0, 0);
				$end = new DateTime('last day of December ' . (date('Y') - 1));
				$end->setTime(23, 59, 59);
				return array(
					'start' => $start->format('Y-m-d H:i:s'),
					'end' => $end->format('Y-m-d H:i:s')
				);

			case 'custom':
				try {
					// Validate date format and existence
					if (!$start_date || !$end_date) {
						throw new Exception('Both start and end dates are required for custom range');
					}
	
					// Create DateTime objects and validate dates
					$start = new DateTime($start_date);
					$end = new DateTime($end_date);
	
					// Validate date range
					if ($end < $start) {
						throw new Exception('End date must be after start date');
					}
	
					// Set proper time bounds
					$start->setTime(0, 0, 0);
					$end->setTime(23, 59, 59);
	
					return array(
						'start' => $start->format('Y-m-d H:i:s'),
						'end' => $end->format('Y-m-d H:i:s')
					);
				} catch (Exception $e) {					
					// Fall back to current month only if there's an error
					$start = new DateTime('first day of this month');
					$start->setTime(0, 0, 0);
					return array(
						'start' => $start->format('Y-m-d H:i:s'),
						'end' => $now->format('Y-m-d H:i:s')
					);
				}
				break;
		}

		// Default to this month if no valid range specified
		$start = new DateTime('first day of this month');
		$start->setTime(0, 0, 0);
		return array(
			'start' => $start->format('Y-m-d H:i:s'),
			'end' => $now->format('Y-m-d H:i:s')
		);
	}

    /**
     * Render reports page
     */
    public function render_reports_page() {
        // Get current tab
        $current_tab = isset($_GET['tab']) ? sanitize_key($_GET['tab']) : 'overview';

        // Define tabs
        $tabs = array(
            'overview' => esc_html__('Overview', 'digicommerce'),
            'products' => esc_html__('Products', 'digicommerce'),
            'customers' => esc_html__('Customers', 'digicommerce'),
            'taxes' => esc_html__('Taxes', 'digicommerce'),
        );

		// If DigiCommerce Pro
		if ( class_exists('DigiCommerce_Pro') ) {
			// Add Coupons tab if enabled
			if ( DigiCommerce()->get_option( 'enable_coupon_code', false ) ) {
				$tabs['coupons'] = esc_html__( 'Coupons', 'digicommerce' );
			}
		
			// Add Subscriptions tab if enabled
			if ( DigiCommerce()->get_option( 'enable_subscriptions', false ) ) {
				$tabs['subscriptions'] = esc_html__( 'Subscriptions', 'digicommerce' );
			}
		
			// Add Abandoned Cart tab if enabled
			if ( DigiCommerce()->get_option( 'enable_abandoned_cart', false ) ) {
				$tabs['abandoned_cart'] = esc_html__( 'Abandoned Cart', 'digicommerce' );
			}
		}

        // Allow filtering of tabs
        $tabs = apply_filters('digicommerce_report_tabs', $tabs);

		$help = array();

		// Add 'pro' option only if DigiCommerce Pro not activated
		if ( ! class_exists( 'DigiCommerce_Pro' ) ) {
			$help['pro'] = array(
				'title' => esc_html__( 'Upgrade to pro', 'digicommerce' ),
				'svg'   => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 32" width="15" height="15" fill="#fff" class="default-transition"><path d="m2.8373 20.9773c-.6083-3.954-1.2166-7.9079-1.8249-11.8619-.1349-.8765.8624-1.4743 1.5718-.9422 1.8952 1.4214 3.7903 2.8427 5.6855 4.2641.624.468 1.513.3157 1.9456-.3333l4.7333-7.1c.5002-.7503 1.6026-.7503 2.1028 0l4.7333 7.1c.4326.649 1.3216.8012 1.9456.3333 1.8952-1.4214 3.7903-2.8427 5.6855-4.2641.7094-.5321 1.7067.0657 1.5719.9422-.6083 3.954-1.2166 7.9079-1.8249 11.8619z"></path><path d="m27.7902 27.5586h-23.5804c-.758 0-1.3725-.6145-1.3725-1.3725v-3.015h26.3255v3.015c-.0001.758-.6146 1.3725-1.3726 1.3725z"></path></svg>',
				'url'   => 'https://digicommerce.me/pricing'
			);
		}

		$help['support'] = array(
			'title' => esc_html__( 'Support', 'digicommerce' ),
			'svg'   => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" width="15" height="15" fill="#fff" class="default-transition"><path d="M256 448c141.4 0 256-93.1 256-208S397.4 32 256 32S0 125.1 0 240c0 45.1 17.7 86.8 47.7 120.9c-1.9 24.5-11.4 46.3-21.4 62.9c-5.5 9.2-11.1 16.6-15.2 21.6c-2.1 2.5-3.7 4.4-4.9 5.7c-.6 .6-1 1.1-1.3 1.4l-.3 .3c0 0 0 0 0 0c0 0 0 0 0 0s0 0 0 0s0 0 0 0c-4.6 4.6-5.9 11.4-3.4 17.4c2.5 6 8.3 9.9 14.8 9.9c28.7 0 57.6-8.9 81.6-19.3c22.9-10 42.4-21.9 54.3-30.6c31.8 11.5 67 17.9 104.1 17.9zM169.8 149.3c7.9-22.3 29.1-37.3 52.8-37.3l58.3 0c34.9 0 63.1 28.3 63.1 63.1c0 22.6-12.1 43.5-31.7 54.8L280 248.4c-.2 13-10.9 23.6-24 23.6c-13.3 0-24-10.7-24-24l0-13.5c0-8.6 4.6-16.5 12.1-20.8l44.3-25.4c4.7-2.7 7.6-7.7 7.6-13.1c0-8.4-6.8-15.1-15.1-15.1l-58.3 0c-3.4 0-6.4 2.1-7.5 5.3l-.4 1.2c-4.4 12.5-18.2 19-30.6 14.6s-19-18.2-14.6-30.6l.4-1.2zM224 336a32 32 0 1 1 64 0 32 32 0 1 1 -64 0z"/></svg>',
			'url'   => 'https://digicommerce.me/my-account/?section=support'
		);

		$help['documentation'] = array(
			'title' => esc_html__( 'Documentation', 'digicommerce' ),
			'svg'   => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" width="15" height="15" fill="#fff" class="default-transition"><path d="M0 32C0 14.3 14.3 0 32 0L96 0c17.7 0 32 14.3 32 32l0 64L0 96 0 32zm0 96l128 0 0 256L0 384 0 128zM0 416l128 0 0 64c0 17.7-14.3 32-32 32l-64 0c-17.7 0-32-14.3-32-32l0-64zM160 32c0-17.7 14.3-32 32-32l64 0c17.7 0 32 14.3 32 32l0 64L160 96l0-64zm0 96l128 0 0 256-128 0 0-256zm0 288l128 0 0 64c0 17.7-14.3 32-32 32l-64 0c-17.7 0-32-14.3-32-32l0-64zm203.6-19.9L320 232.6l0-89.9 100.4-26.9 66 247.4L363.6 396.1zM412.2 85L320 109.6 320 11l36.9-9.9c16.9-4.6 34.4 5.5 38.9 22.6L412.2 85zM371.8 427l122.8-32.9 16.3 61.1c4.5 17-5.5 34.5-22.5 39.1l-61.4 16.5c-16.9 4.6-34.4-5.5-38.9-22.6L371.8 427z"/></svg>',
			'url'   => 'https://docs.digicommerce.me/'
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
        ?>
        <div class="digicommerce">
            <div class="flex flex-col md:flex-row items-center justify-between gap-4 bg-dark-blue box-border -ml-5 px-8 py-6">
				<div class="digicommerce-logo flex items-center flex-col esm:flex-row gap-4">
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

					<span class="flex gap-4 text-xl font-bold text-white">
						<span>/</span>
						<?php esc_html_e('Reports', 'digicommerce'); ?>
					</span>
				</div>

				<div class="digicommerce-help flex flex-col esm:flex-row items-center gap-4">
					<?php
					foreach ( $help as $id => $array ) :
						?>
						<a class="flex items-center gap-2 text-white hover:text-white/80 default-transition" href="<?php echo esc_url( $array['url'] ); ?>?utm_source=WordPress&amp;utm_medium=header&amp;utm_campaign=digi" target="_blank" rel="noopener noreferrer">
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

            <div class="flex flex-col 2xl:grid 2xl:grid-cols-12 m-5 ml-0">
                <!-- Left sidebar with tabs -->
                <div class="digicommerce-tabs 2xl:col-span-2">
                    <?php foreach ($tabs as $tab_id => $tab_label) : ?>
                        <a href="#" data-tab="<?php echo esc_attr($tab_id); ?>" 
                           class="digicommerce-tab cursor-pointer flex justify-start w-full no-underline text-dark-blue hover:text-dark-blue bg-light-blue hover:bg-[#f2f5ff] select-none text-center box-border p-4 text-medium border-0 border-b border-solid border-[rgba(0,0,0,0.05)] first:2xl:rounded-[.375rem_0_0] last:2xl:rounded-[0_0_0_.375rem] last:border-b-0 default-transition <?php echo $current_tab === $tab_id ? 'active' : ''; ?>">
                            <?php echo esc_html($tab_label); ?>
                        </a>
                    <?php endforeach; ?>
                </div>

                <!-- Main content area -->
                <div class="flex flex-col gap-12 bg-white box-border p-6 2xl:rounded-[0_.375rem_.375rem_0] 2xl:col-span-10">
                    <!-- Date Range Selector -->
                    <div class="date-range-selector flex flex-wrap gap-4 items-center">
                        <select class="digi-date-range">
                            <option value="today"><?php esc_html_e('Today', 'digicommerce'); ?></option>
                            <option value="yesterday"><?php esc_html_e('Yesterday', 'digicommerce'); ?></option>
                            <option value="this_week"><?php esc_html_e('This Week', 'digicommerce'); ?></option>
                            <option value="last_week"><?php esc_html_e('Last Week', 'digicommerce'); ?></option>
                            <option value="this_month" selected><?php esc_html_e('This Month', 'digicommerce'); ?></option>
                            <option value="last_month"><?php esc_html_e('Last Month', 'digicommerce'); ?></option>
                            <option value="this_quarter"><?php esc_html_e('This Quarter', 'digicommerce'); ?></option>
                            <option value="last_quarter"><?php esc_html_e('Last Quarter', 'digicommerce'); ?></option>
                            <option value="this_year"><?php esc_html_e('This Year', 'digicommerce'); ?></option>
                            <option value="last_year"><?php esc_html_e('Last Year', 'digicommerce'); ?></option>
                            <option value="custom"><?php esc_html_e('Custom', 'digicommerce'); ?></option>
                        </select>

                        <div class="custom-date-range masked">
                            <input type="date" class="start-date" />
                            <input type="date" class="end-date" />
                            <button type="button" class="apply-custom-range flex items-center justify-center gap-2 bg-dark-blue hover:bg-[#6c698a] text-white hover:text-white py-2 px-4 rounded default-transition">
                                <?php esc_html_e('Apply', 'digicommerce'); ?>
                            </button>
                        </div>
                    </div>

                    <!-- Stats Cards -->
                    <div class="stats-overview grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                        <!-- Gross Revenue -->
                        <div class="stat-card bg-light-blue p-4 rounded-lg">
                            <h3 class="text-dark-blue mb-2"><?php esc_html_e('Gross Revenue', 'digicommerce'); ?></h3>
                            <p class="text-2xl font-bold revenue-amount">0</p>
                        </div>

                        <!-- Net Revenue -->
                        <div class="stat-card bg-light-blue p-4 rounded-lg">
                            <h3 class="text-dark-blue mb-2"><?php esc_html_e('Net Revenue', 'digicommerce'); ?></h3>
                            <p class="text-2xl font-bold net-amount">0</p>
                        </div>

                        <!-- Orders -->
                        <div class="stat-card bg-light-blue p-4 rounded-lg">
                            <h3 class="text-dark-blue mb-2"><?php esc_html_e('Orders', 'digicommerce'); ?></h3>
                            <p class="text-2xl font-bold orders-amount">0</p>
                        </div>

                        <!-- Average Order Value -->
                        <div class="stat-card bg-light-blue p-4 rounded-lg">
                            <h3 class="text-dark-blue mb-2"><?php esc_html_e('Average Order', 'digicommerce'); ?></h3>
                            <p class="text-2xl font-bold average-amount">0</p>
                        </div>
                    </div>

                    <!-- Charts -->
                    <div class="charts-container" style="height: 400px;">
                        <canvas id="revenueChart"></canvas>
                    </div>

                    <!-- Tab specific content will be loaded here via AJAX -->
                    <div class="tab-content"></div>
                </div>
            </div>
        </div>
        <?php
    }
	
	/**
	 * Customize admin footer
	 */
	public function footer_text( $text ) {
		$screen = get_current_screen();

		if ( 'digicommerce_page_digicommerce-reports' === $screen->id ) {
			$text = sprintf(
				/* translators: %1$s: Plugin review link */
				esc_html__( 'Please rate %2$sDigiCommerce%3$s %4$s&#9733;&#9733;&#9733;&#9733;&#9733;%5$s on %6$sWordPress.org%7$s to help us spread the word.', 'digicommerce' ),
				'https://wordpress.org/support/plugin/digicommerce/reviews/?filter=5#new-post',
				'<strong>',
				'</strong>',
				'<a href="https://wordpress.org/support/plugin/digicommerce/reviews/?filter=5#new-post" target="_blank" rel="noopener noreferrer">',
				'</a>',
				'<a href="https://wordpress.org/support/plugin/digicommerce/reviews/?filter=5#new-post" target="_blank" rel="noopener noreferrer">',
				'</a>'
			);
		}

		return $text;
	}

	/**
	 * Customize admin footer version
	 */
	public function update_footer( $version ) {
		$screen = get_current_screen();

		if ( 'digicommerce_page_digicommerce-reports' === $screen->id ) {
			$name = class_exists( 'DigiCommerce_Pro' ) ? 'DigiCommerce Pro' : 'DigiCommerce';

			$version .= sprintf( ' | %1$s %2$s', $name, DIGICOMMERCE_VERSION );
		}

		return $version;
	}
}

// Initialize the reports
DigiCommerce_Reports::instance();