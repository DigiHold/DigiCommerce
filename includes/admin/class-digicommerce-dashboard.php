<?php
/**
 * DigiCommerce Dashboard Widget: Sales Summary
 */
class DigiCommerce_Dashboard_Widget {
    private static $instance = null;
    private $table_orders;
    private $currency;
    private $currency_position;
    private $stats_cache_key = 'digicommerce_dashboard_stats';

    public static function instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        global $wpdb;
        $this->table_orders = $wpdb->prefix . 'digicommerce_orders';

        // Get currency settings
        $this->currency = DigiCommerce()->get_option('currency', 'USD');
        $this->currency_position = DigiCommerce()->get_option('currency_position', 'left');

        // Add dashboard widget
        add_action('wp_dashboard_setup', array($this, 'add_dashboard_widget'));

        // Order status change hooks
        add_action('digicommerce_order_created', array($this, 'invalidate_stats_cache'));
        add_action('digicommerce_order_status_changed', array($this, 'invalidate_stats_cache'));
        add_action('digicommerce_order_deleted', array($this, 'invalidate_stats_cache'));
    }

    public function add_dashboard_widget() {
        wp_add_dashboard_widget(
            'digicommerce_dashboard_sales',
            esc_html__('DigiCommerce Sales Summary', 'digicommerce'),
            array($this, 'render_dashboard_widget')
        );

        // Enqueue required scripts
        add_action('admin_enqueue_scripts', array($this, 'enqueue_dashboard_scripts'));
    }

    public function enqueue_dashboard_scripts($hook) {
        if ('index.php' !== $hook) {
            return;
        }

        wp_enqueue_style(
            'digicommerce-dashboard-style',
            DIGICOMMERCE_PLUGIN_URL . 'assets/css/admin/dashboard.css',
            array(),
            DIGICOMMERCE_VERSION
        );
    }

    /**
     * Invalidate stats cache when orders change
     */
    public function invalidate_stats_cache() {
        wp_cache_delete($this->stats_cache_key, 'digicommerce');
    }

    /**
     * Get cached stats or calculate if cache is empty
     */
    private function get_stats($period = '') {
        // Try to get from object cache first
        $all_stats = wp_cache_get($this->stats_cache_key, 'digicommerce');
        
        if ($all_stats === false || !isset($all_stats[$period])) {
            // Calculate stats if not in cache
            $stats = $this->calculate_fresh_stats($period);

            // Cache all periods together
            $all_stats = wp_cache_get($this->stats_cache_key, 'digicommerce') ?: array();
            $all_stats[$period] = $stats;
            wp_cache_set($this->stats_cache_key, $all_stats, 'digicommerce');
        }

        return $all_stats[$period];
    }

    /**
     * Calculate fresh stats for a given period
     */
    private function calculate_fresh_stats($period) {
        global $wpdb;

        $stats = array(
            'earnings' => 0,
            'sales' => 0,
            'vat' => 0
        );

        $date_range = $this->get_date_range($period);
        if (empty($date_range)) {
            return $stats;
        }

        $results = $wpdb->get_row($wpdb->prepare(
            "SELECT 
                COUNT(*) as total_sales,
                SUM(total) as total_earnings,
                SUM(vat) as total_vat
            FROM {$this->table_orders}
            WHERE status = 'completed'
            AND date_created BETWEEN %s AND %s",
            $date_range['start'],
            $date_range['end']
        ));

        if ($results) {
            $stats['earnings'] = floatval($results->total_earnings);
            $stats['sales'] = intval($results->total_sales);
            $stats['vat'] = floatval($results->total_vat);
        }

        return $stats;
    }

    private function get_date_range($period) {
        $now = current_time('mysql');
        $today = date('Y-m-d 00:00:00', strtotime($now));
        $tomorrow = date('Y-m-d 00:00:00', strtotime($today . ' +1 day'));

        switch ($period) {
            case 'today':
                return array(
                    'start' => $today,
                    'end' => $tomorrow,
                );

            case 'month':
                $start_month = date('Y-m-01 00:00:00', strtotime($now));
                $end_month = date('Y-m-t 23:59:59', strtotime($now));
                return array(
                    'start' => $start_month,
                    'end' => $end_month,
                );

            case 'last_month':
                $start_last_month = date('Y-m-01 00:00:00', strtotime('first day of last month'));
                $end_last_month = date('Y-m-t 23:59:59', strtotime('last day of last month'));
                return array(
                    'start' => $start_last_month,
                    'end' => $end_last_month,
                );

            case 'all_time':
                return array(
                    'start' => '1970-01-01 00:00:00',
                    'end' => $tomorrow,
                );
        }

        return array();
    }

    private function format_price($amount) {
        $currencies = DigiCommerce()->get_currencies();
        $symbol = $currencies[$this->currency]['symbol'];
        $amount = number_format($amount, 2);

        switch ($this->currency_position) {
            case 'left':
                return $symbol . $amount;
            case 'right':
                return $amount . $symbol;
            case 'left_space':
                return $symbol . ' ' . $amount;
            case 'right_space':
                return $amount . ' ' . $symbol;
            default:
                return $symbol . $amount;
        }
    }

    public function render_dashboard_widget() {
        if (!current_user_can('manage_options')) {
            wp_die(-1);
        }

        // Get stats for all periods
        $today_stats = $this->get_stats('today');
        $month_stats = $this->get_stats('month');
        $last_month_stats = $this->get_stats('last_month');
        $all_time_stats = $this->get_stats('all_time');
        ?>
        <div class="digicommerce-dashboard">
            <div class="digicommerce-blocks">
                <!-- Current month -->
                <div class="digicommerce-element digicommerce-current-month">
                    <h3><?php esc_html_e('Current Month', 'digicommerce'); ?></h3>
                    <hr>
                    <div class="stats">
                        <div class="earnings">
                            <?php esc_html_e('Earnings', 'digicommerce'); ?>
                            <span class="price"><?php echo $this->format_price($month_stats['earnings']); ?></span>
                        </div>
                        <div class="sales">
                            <?php esc_html_e('Sales', 'digicommerce'); ?>
                            <span><?php echo esc_html($month_stats['sales']); ?></span>
                        </div>
                    </div>
                </div>

                <!-- Today -->
                <div class="digicommerce-element digicommerce-today">
                    <h3><?php esc_html_e('Today', 'digicommerce'); ?></h3>
                    <hr>
                    <div class="stats">
                        <div class="earnings">
                            <?php esc_html_e('Earnings', 'digicommerce'); ?>
                            <span class="price"><?php echo $this->format_price($today_stats['earnings']); ?></span>
                        </div>
                        <div class="sales">
                            <?php esc_html_e('Sales', 'digicommerce'); ?>
                            <span><?php echo esc_html($today_stats['sales']); ?></span>
                        </div>
                    </div>
                </div>

                <!-- Last month -->
                <div class="digicommerce-element digicommerce-last-month">
                    <h3><?php esc_html_e('Last Month', 'digicommerce'); ?></h3>
                    <hr>
                    <div class="stats">
                        <div class="earnings">
                            <?php esc_html_e('Earnings', 'digicommerce'); ?>
                            <span class="price"><?php echo $this->format_price($last_month_stats['earnings']); ?></span>
                        </div>
                        <div class="sales">
                            <?php esc_html_e('Sales', 'digicommerce'); ?>
                            <span><?php echo esc_html($last_month_stats['sales']); ?></span>
                        </div>
                    </div>
                </div>

                <!-- All time -->
                <div class="digicommerce-element digicommerce-total">
                    <h3><?php esc_html_e('All Time', 'digicommerce'); ?></h3>
                    <hr>
                    <div class="stats">
                        <div class="earnings">
                            <?php esc_html_e('Earnings', 'digicommerce'); ?>
                            <span class="price"><?php echo $this->format_price($all_time_stats['earnings']); ?></span>
                        </div>
                        <div class="sales">
                            <?php esc_html_e('Sales', 'digicommerce'); ?>
                            <span><?php echo esc_html($all_time_stats['sales']); ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
}

// Initialize the class
DigiCommerce_Dashboard_Widget::instance();