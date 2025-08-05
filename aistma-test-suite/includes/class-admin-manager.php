<?php
/**
 * Admin Manager Class
 * 
 * Handles admin pages and menus.
 * 
 * @package AISTMA_Test_Suite
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * AISTMA_Admin_Manager Class
 */
class AISTMA_Admin_Manager {
    
    /**
     * Initialize
     */
    public function init() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        // Main menu
        $res = add_menu_page(
            __('AI Story Maker Tests', 'aistma-test-suite'),
            __('AISTMA Tests', 'aistma-test-suite'),
            'manage_options',
            'aistma-test-suite',
            array($this, 'render_tests_page'),
            'dashicons-testimonial',
            30
        );
        
        // History submenu
        add_submenu_page(
            'aistma-test-suite',
            __('Test History', 'aistma-test-suite'),
            __('History', 'aistma-test-suite'),
            'manage_options',
            'aistma-test-history',
            array($this, 'render_history_page')
        );
        
        // Debug Log submenu
        add_submenu_page(
            'aistma-test-suite',
            __('Debug Log', 'aistma-test-suite'),
            __('Debug Log', 'aistma-test-suite'),
            'manage_options',
            'aistma-debug-log',
            array($this, 'render_debug_log_page')
        );
    }
    
    /**
     * Enqueue admin scripts
     */
    public function enqueue_admin_scripts($hook) {
        if (strpos($hook, 'aistma-test') === false) {
            return;
        }
        
        wp_enqueue_style(
            'aistma-test-suite-css',
            AISTMA_TEST_SUITE_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            AISTMA_TEST_SUITE_VERSION
        );
        
        wp_enqueue_script(
            'aistma-test-suite-js',
            AISTMA_TEST_SUITE_PLUGIN_URL . 'assets/js/admin.js',
            array('jquery'),
            AISTMA_TEST_SUITE_VERSION,
            true
        );
        
        wp_localize_script('aistma-test-suite-js', 'aistma_test_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('aistma_test_nonce'),
            'strings' => array(
                'running_tests' => __('Running tests...', 'aistma-test-suite'),
                'tests_complete' => __('Tests complete!', 'aistma-test-suite'),
                'export_success' => __('Results exported successfully!', 'aistma-test-suite'),
                'confirm_clear' => __('Are you sure you want to clear the test history?', 'aistma-test-suite'),
                'confirm_clear_log' => __('Are you sure you want to clear the debug log?', 'aistma-test-suite')
            )
        ));
    }
    
    /**
     * Render tests page
     */
    public function render_tests_page() {
        $test_suite = AISTMA_Test_Suite::get_instance();
        
        // Check if the test plugin is active
        if (!$test_suite->is_test_plugin_active()) {
            $plugin_name = $test_suite->get_test_plugin_name();
            include AISTMA_TEST_SUITE_PLUGIN_DIR . 'templates/plugin-not-active.php';
            return;
        }
        
        $test_runner = $test_suite->get_test_runner();
        $available_tests = $test_runner->get_available_tests();
        
        include AISTMA_TEST_SUITE_PLUGIN_DIR . 'templates/tests-page.php';
    }
    
    /**
     * Render history page
     */
    public function render_history_page() {
        $history_manager = AISTMA_Test_Suite::get_instance()->get_history_manager();
        $categories = $history_manager->get_available_categories();
        $test_names = $history_manager->get_available_test_names();
        
        include AISTMA_TEST_SUITE_PLUGIN_DIR . 'templates/history-page.php';
    }
    
    /**
     * Render debug log page
     */
    public function render_debug_log_page() {
        $debug_logger = AISTMA_Test_Suite::get_instance()->get_debug_logger();
        $debug_config = $debug_logger->get_debug_config();
        $log_stats = $debug_logger->get_debug_log_stats();
        
        include AISTMA_TEST_SUITE_PLUGIN_DIR . 'templates/debug-log-page.php';
    }
    
    /**
     * Get test categories
     */
    public function get_test_categories() {
        $test_runner = AISTMA_Test_Suite::get_instance()->get_test_runner();
        $tests = $test_runner->get_available_tests();
        
        $categories = array();
        foreach ($tests as $test) {
            $category = $test['category'];
            if (!isset($categories[$category])) {
                $categories[$category] = array();
            }
            $categories[$category][] = $test;
        }
        
        return $categories;
    }
    
    /**
     * Get test status color
     */
    public function get_test_status_color($status) {
        switch ($status) {
            case 'passed':
                return '#28a745';
            case 'failed':
                return '#dc3545';
            case 'error':
                return '#dc3545';
            default:
                return '#6c757d';
        }
    }
    
    /**
     * Get test status icon
     */
    public function get_test_status_icon($status) {
        switch ($status) {
            case 'passed':
                return '✅';
            case 'failed':
                return '❌';
            case 'error':
                return '⚠️';
            default:
                return '⏳';
        }
    }
    
    /**
     * Format duration
     */
    public function format_duration($duration) {
        if ($duration < 1000) {
            return round($duration, 2) . 'ms';
        } else {
            return round($duration / 1000, 2) . 's';
        }
    }
    
    /**
     * Get success rate color
     */
    public function get_success_rate_color($rate) {
        if ($rate >= 90) {
            return '#28a745';
        } elseif ($rate >= 70) {
            return '#ffc107';
        } else {
            return '#dc3545';
        }
    }
    
    /**
     * Get log level color
     */
    public function get_log_level_color($level) {
        switch (strtoupper($level)) {
            case 'ERROR':
                return '#dc3545';
            case 'WARNING':
                return '#ffc107';
            case 'INFO':
                return '#17a2b8';
            case 'DEBUG':
                return '#6c757d';
            default:
                return '#6c757d';
        }
    }
    
    /**
     * Format file size
     */
    public function format_file_size($bytes) {
        $units = array('B', 'KB', 'MB', 'GB');
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        
        return round($bytes / pow(1024, $pow), 2) . ' ' . $units[$pow];
    }
    
    /**
     * Format date
     */
    public function format_date($date) {
        return date('M j, Y g:i A', strtotime($date));
    }
    
    /**
     * Get relative time
     */
    public function get_relative_time($timestamp) {
        $time_diff = time() - strtotime($timestamp);
        
        if ($time_diff < 60) {
            return $time_diff . ' seconds ago';
        } elseif ($time_diff < 3600) {
            return round($time_diff / 60) . ' minutes ago';
        } elseif ($time_diff < 86400) {
            return round($time_diff / 3600) . ' hours ago';
        } else {
            return round($time_diff / 86400) . ' days ago';
        }
    }
    
    /**
     * Get pagination info
     */
    public function get_pagination_info($total, $per_page, $current_page) {
        $total_pages = ceil($total / $per_page);
        $start = ($current_page - 1) * $per_page + 1;
        $end = min($current_page * $per_page, $total);
        
        return array(
            'total' => $total,
            'per_page' => $per_page,
            'current_page' => $current_page,
            'total_pages' => $total_pages,
            'start' => $start,
            'end' => $end,
            'has_prev' => $current_page > 1,
            'has_next' => $current_page < $total_pages
        );
    }
    
    /**
     * Get filter options
     */
    public function get_filter_options() {
        $history_manager = AISTMA_Test_Suite::get_instance()->get_history_manager();
        
        return array(
            'categories' => $history_manager->get_available_categories(),
            'test_names' => $history_manager->get_available_test_names(),
            'statuses' => array('passed', 'failed', 'error'),
            'date_ranges' => array(
                'today' => __('Today', 'aistma-test-suite'),
                'yesterday' => __('Yesterday', 'aistma-test-suite'),
                'last_7_days' => __('Last 7 days', 'aistma-test-suite'),
                'last_30_days' => __('Last 30 days', 'aistma-test-suite'),
                'this_month' => __('This month', 'aistma-test-suite'),
                'last_month' => __('Last month', 'aistma-test-suite')
            )
        );
    }
    
    /**
     * Get export formats
     */
    public function get_export_formats() {
        return array(
            'json' => __('JSON', 'aistma-test-suite'),
            'csv' => __('CSV', 'aistma-test-suite'),
            'html' => __('HTML Report', 'aistma-test-suite')
        );
    }
    
    /**
     * Get log filter levels
     */
    public function get_log_filter_levels() {
        return array(
            '' => __('All Levels', 'aistma-test-suite'),
            'error' => __('Errors Only', 'aistma-test-suite'),
            'warning' => __('Warnings Only', 'aistma-test-suite'),
            'info' => __('Info Only', 'aistma-test-suite'),
            'debug' => __('Debug Only', 'aistma-test-suite')
        );
    }
} 