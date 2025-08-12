<?php
/**
 * History Manager Class
 * 
 * Manages test history storage and retrieval.
 * 
 * @package AISTMA_Test_Suite
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * AISTMA_History_Manager Class
 */
class AISTMA_History_Manager {
    
    /**
     * Initialize
     */
    public function init() {
        add_action('wp_ajax_get_aistma_test_history', array($this, 'ajax_get_test_history'));
        add_action('wp_ajax_export_aistma_test_history', array($this, 'ajax_export_test_history'));
        add_action('wp_ajax_clear_aistma_test_history', array($this, 'ajax_clear_test_history'));
        add_action('wp_ajax_get_aistma_test_details', array($this, 'ajax_get_test_details'));
    }
    
    /**
     * Create database tables
     */
    public function create_tables() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'aistma_test_history';
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            test_name VARCHAR(255) NOT NULL,
            test_description TEXT,
            test_category VARCHAR(100) NOT NULL,
            test_status VARCHAR(50) NOT NULL,
            test_result TEXT,
            test_duration FLOAT NOT NULL DEFAULT 0,
            test_logs LONGTEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_test_name (test_name),
            INDEX idx_test_category (test_category),
            INDEX idx_test_status (test_status),
            INDEX idx_created_at (created_at)
        ) $charset_collate;";
        
        include_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);
    }
    
    /**
     * Get test history with filters
     */
    public function get_test_history($filters = array()) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'aistma_test_history';
        
        $where_clauses = array();
        $where_values = array();
        
        // Filter by test name
        if (!empty($filters['test_name'])) {
            $where_clauses[] = 'test_name LIKE %s';
            $where_values[] = '%' . $wpdb->esc_like($filters['test_name']) . '%';
        }
        
        // Filter by category
        if (!empty($filters['category'])) {
            $where_clauses[] = 'test_category = %s';
            $where_values[] = $filters['category'];
        }
        
        // Filter by status
        if (!empty($filters['status'])) {
            $where_clauses[] = 'test_status = %s';
            $where_values[] = $filters['status'];
        }
        
        // Filter by date range
        if (!empty($filters['start_date'])) {
            $where_clauses[] = 'created_at >= %s';
            $where_values[] = $filters['start_date'] . ' 00:00:00';
        }
        
        if (!empty($filters['end_date'])) {
            $where_clauses[] = 'created_at <= %s';
            $where_values[] = $filters['end_date'] . ' 23:59:59';
        }
        
        // Build WHERE clause
        $where_sql = '';
        if (!empty($where_clauses)) {
            $where_sql = 'WHERE ' . implode(' AND ', $where_clauses);
        }
        
        // Build ORDER BY clause
        $order_by = !empty($filters['order_by']) ? $filters['order_by'] : 'created_at';
        $order_direction = !empty($filters['order_direction']) ? $filters['order_direction'] : 'DESC';
        $order_sql = "ORDER BY $order_by $order_direction";
        
        // Build LIMIT clause
        $limit = !empty($filters['limit']) ? intval($filters['limit']) : 100;
        $offset = !empty($filters['offset']) ? intval($filters['offset']) : 0;
        $limit_sql = "LIMIT $limit OFFSET $offset";
        
        // Build query
        $query = "SELECT * FROM $table_name $where_sql $order_sql $limit_sql";
        
        if (!empty($where_values)) {
            $query = $wpdb->prepare($query, $where_values);
        }
        
        $results = $wpdb->get_results($query, ARRAY_A);
        
        // Decode logs
        foreach ($results as &$result) {
            if (!empty($result['test_logs'])) {
                $result['test_logs'] = json_decode($result['test_logs'], true);
            }
        }
        
        return $results;
    }
    
    /**
     * Get test history summary
     */
    public function get_test_history_summary($filters = array()) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'aistma_test_history';
        
        $where_clauses = array();
        $where_values = array();
        
        // Apply same filters as get_test_history
        if (!empty($filters['test_name'])) {
            $where_clauses[] = 'test_name LIKE %s';
            $where_values[] = '%' . $wpdb->esc_like($filters['test_name']) . '%';
        }
        
        if (!empty($filters['category'])) {
            $where_clauses[] = 'test_category = %s';
            $where_values[] = $filters['category'];
        }
        
        if (!empty($filters['status'])) {
            $where_clauses[] = 'test_status = %s';
            $where_values[] = $filters['status'];
        }
        
        if (!empty($filters['start_date'])) {
            $where_clauses[] = 'created_at >= %s';
            $where_values[] = $filters['start_date'] . ' 00:00:00';
        }
        
        if (!empty($filters['end_date'])) {
            $where_clauses[] = 'created_at <= %s';
            $where_values[] = $filters['end_date'] . ' 23:59:59';
        }
        
        $where_sql = '';
        if (!empty($where_clauses)) {
            $where_sql = 'WHERE ' . implode(' AND ', $where_clauses);
        }
        
        // Get summary statistics
        $summary_query = "
            SELECT 
                COUNT(*) as total_tests,
                SUM(CASE WHEN test_status = 'passed' THEN 1 ELSE 0 END) as passed_tests,
                SUM(CASE WHEN test_status = 'failed' THEN 1 ELSE 0 END) as failed_tests,
                SUM(CASE WHEN test_status = 'error' THEN 1 ELSE 0 END) as error_tests,
                AVG(test_duration) as avg_duration,
                MIN(created_at) as first_test,
                MAX(created_at) as last_test
            FROM $table_name 
            $where_sql
        ";
        
        if (!empty($where_values)) {
            $summary_query = $wpdb->prepare($summary_query, $where_values);
        }
        
        $summary = $wpdb->get_row($summary_query, ARRAY_A);
        
        // Calculate success rate
        $total = intval($summary['total_tests']);
        $passed = intval($summary['passed_tests']);
        $summary['success_rate'] = $total > 0 ? round(($passed / $total) * 100, 2) : 0;
        
        return $summary;
    }
    
    /**
     * Get available categories
     */
    public function get_available_categories() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'aistma_test_history';
        
        $query = "SELECT DISTINCT test_category FROM $table_name ORDER BY test_category";
        $categories = $wpdb->get_col($query);
        
        return $categories;
    }
    
    /**
     * Get available test names
     */
    public function get_available_test_names() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'aistma_test_history';
        
        $query = "SELECT DISTINCT test_name FROM $table_name ORDER BY test_name";
        $test_names = $wpdb->get_col($query);
        
        return $test_names;
    }

    /**
     * Get single test details by ID
     */
    public function get_test_details($id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'aistma_test_history';

        $record = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $id),
            ARRAY_A
        );

        if ($record && !empty($record['test_logs'])) {
            $decoded_logs = json_decode($record['test_logs'], true);
            // Only replace if JSON decode succeeded; otherwise leave raw string
            if (json_last_error() === JSON_ERROR_NONE) {
                $record['test_logs'] = $decoded_logs;
            }
        }

        return $record;
    }
    
    /**
     * Export test history
     */
    public function export_test_history($filters = array(), $format = 'json') {
        $history = $this->get_test_history($filters);
        $summary = $this->get_test_history_summary($filters);
        
        $export_data = array(
            'export_info' => array(
                'exported_at' => current_time('mysql'),
                'site_url' => get_site_url(),
                'wp_version' => get_bloginfo('version'),
                'plugin_version' => AISTMA_TEST_SUITE_VERSION,
                'filters_applied' => $filters
            ),
            'summary' => $summary,
            'history' => $history
        );
        
        switch ($format) {
            case 'json':
                return json_encode($export_data, JSON_PRETTY_PRINT);
                
            case 'csv':
                return $this->export_to_csv($history);
                
            case 'html':
                return $this->export_to_html($export_data);
                
            default:
                return json_encode($export_data, JSON_PRETTY_PRINT);
        }
    }
    
    /**
     * Export to CSV
     */
    private function export_to_csv($history) {
        $csv_data = array();
        
        // Add headers
        $csv_data[] = array(
            'Test Name',
            'Description',
            'Category',
            'Status',
            'Result',
            'Duration (ms)',
            'Created At'
        );
        
        // Add data
        foreach ($history as $record) {
            $csv_data[] = array(
                $record['test_name'],
                $record['test_description'],
                $record['test_category'],
                $record['test_status'],
                $record['test_result'],
                $record['test_duration'],
                $record['created_at']
            );
        }
        
        $csv_content = '';
        foreach ($csv_data as $row) {
            $csv_content .= '"' . implode('","', array_map('addslashes', $row)) . '"' . "\n";
        }
        
        return $csv_content;
    }
    
    /**
     * Export to HTML
     */
    private function export_to_html($export_data) {
        $html = '<!DOCTYPE html>
<html>
<head>
    <title>AI Story Maker Test History Report</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .header { background: #f0f0f0; padding: 20px; margin-bottom: 20px; }
        .summary { background: #e7f3ff; padding: 15px; margin-bottom: 20px; }
        .summary-item { margin: 10px 0; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background: #f8f9fa; }
        .status-passed { color: #28a745; }
        .status-failed { color: #dc3545; }
        .status-error { color: #dc3545; }
    </style>
</head>
<body>
    <div class="header">
        <h1>AI Story Maker Test History Report</h1>
        <p>Exported: ' . $export_data['export_info']['exported_at'] . '</p>
        <p>Site: ' . $export_data['export_info']['site_url'] . '</p>
    </div>
    
    <div class="summary">
        <h2>Summary</h2>
        <div class="summary-item">Total Tests: ' . $export_data['summary']['total_tests'] . '</div>
        <div class="summary-item">Passed: ' . $export_data['summary']['passed_tests'] . '</div>
        <div class="summary-item">Failed: ' . $export_data['summary']['failed_tests'] . '</div>
        <div class="summary-item">Errors: ' . $export_data['summary']['error_tests'] . '</div>
        <div class="summary-item">Success Rate: ' . $export_data['summary']['success_rate'] . '%</div>
        <div class="summary-item">Average Duration: ' . round($export_data['summary']['avg_duration'], 2) . 'ms</div>
    </div>
    
    <table>
        <thead>
            <tr>
                <th>Test Name</th>
                <th>Category</th>
                <th>Status</th>
                <th>Duration</th>
                <th>Created</th>
            </tr>
        </thead>
        <tbody>';
        
        foreach ($export_data['history'] as $record) {
            $status_class = 'status-' . $record['test_status'];
            $html .= '
            <tr>
                <td>' . esc_html($record['test_name']) . '</td>
                <td>' . esc_html($record['test_category']) . '</td>
                <td class="' . $status_class . '">' . esc_html($record['test_status']) . '</td>
                <td>' . esc_html($record['test_duration']) . 'ms</td>
                <td>' . esc_html($record['created_at']) . '</td>
            </tr>';
        }
        
        $html .= '
        </tbody>
    </table>
</body>
</html>';
        
        return $html;
    }
    
    /**
     * Clear test history
     */
    public function clear_test_history($filters = array()) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'aistma_test_history';
        
        if (empty($filters)) {
            // Clear all history
            $result = $wpdb->query("TRUNCATE TABLE $table_name");
        } else {
            // Clear filtered history
            $where_clauses = array();
            $where_values = array();
            
            if (!empty($filters['test_name'])) {
                $where_clauses[] = 'test_name LIKE %s';
                $where_values[] = '%' . $wpdb->esc_like($filters['test_name']) . '%';
            }
            
            if (!empty($filters['category'])) {
                $where_clauses[] = 'test_category = %s';
                $where_values[] = $filters['category'];
            }
            
            if (!empty($filters['status'])) {
                $where_clauses[] = 'test_status = %s';
                $where_values[] = $filters['status'];
            }
            
            if (!empty($filters['start_date'])) {
                $where_clauses[] = 'created_at >= %s';
                $where_values[] = $filters['start_date'] . ' 00:00:00';
            }
            
            if (!empty($filters['end_date'])) {
                $where_clauses[] = 'created_at <= %s';
                $where_values[] = $filters['end_date'] . ' 23:59:59';
            }
            
            if (!empty($where_clauses)) {
                $where_sql = 'WHERE ' . implode(' AND ', $where_clauses);
                $query = "DELETE FROM $table_name $where_sql";
                
                if (!empty($where_values)) {
                    $query = $wpdb->prepare($query, $where_values);
                }
                
                $result = $wpdb->query($query);
            } else {
                $result = false;
            }
        }
        
        return $result !== false;
    }
    
    /**
     * AJAX handler for getting test history
     */
    public function ajax_get_test_history() {
        check_ajax_referer('aistma_test_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions', 'aistma-test-suite'));
        }
        
        $filters = array(
            'test_name' => sanitize_text_field($_POST['test_name'] ?? ''),
            'category' => sanitize_text_field($_POST['category'] ?? ''),
            'status' => sanitize_text_field($_POST['status'] ?? ''),
            'start_date' => sanitize_text_field($_POST['start_date'] ?? ''),
            'end_date' => sanitize_text_field($_POST['end_date'] ?? ''),
            'limit' => intval($_POST['limit'] ?? 100),
            'offset' => intval($_POST['offset'] ?? 0),
            'order_by' => sanitize_text_field($_POST['order_by'] ?? 'created_at'),
            'order_direction' => sanitize_text_field($_POST['order_direction'] ?? 'DESC')
        );
        
        $history = $this->get_test_history($filters);
        $summary = $this->get_test_history_summary($filters);
        
        wp_send_json_success(array(
            'history' => $history,
            'summary' => $summary
        ));
    }
    
    /**
     * AJAX handler for exporting test history
     */
    public function ajax_export_test_history() {
        check_ajax_referer('aistma_test_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions', 'aistma-test-suite'));
        }
        
        $filters = array(
            'test_name' => sanitize_text_field($_POST['test_name'] ?? ''),
            'category' => sanitize_text_field($_POST['category'] ?? ''),
            'status' => sanitize_text_field($_POST['status'] ?? ''),
            'start_date' => sanitize_text_field($_POST['start_date'] ?? ''),
            'end_date' => sanitize_text_field($_POST['end_date'] ?? '')
        );
        
        $format = sanitize_text_field($_POST['format'] ?? 'json');
        $export_data = $this->export_test_history($filters, $format);
        
        $filename = 'aistma-test-history-' . date('Y-m-d-H-i-s');
        
        switch ($format) {
            case 'csv':
                $filename .= '.csv';
                $content_type = 'text/csv';
                break;
            case 'html':
                $filename .= '.html';
                $content_type = 'text/html';
                break;
            default:
                $filename .= '.json';
                $content_type = 'application/json';
                break;
        }
        
        header('Content-Type: ' . $content_type);
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        echo $export_data;
        exit;
    }

    /**
     * AJAX handler for getting single test details
     */
    public function ajax_get_test_details() {
        check_ajax_referer('aistma_test_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions', 'aistma-test-suite'));
        }

        $id = intval($_POST['id'] ?? 0);
        if ($id <= 0) {
            wp_send_json_error(__('Invalid test ID', 'aistma-test-suite'));
        }

        $record = $this->get_test_details($id);
        if ($record) {
            wp_send_json_success($record);
        } else {
            wp_send_json_error(__('Test not found', 'aistma-test-suite'));
        }
    }
    
    /**
     * AJAX handler for clearing test history
     */
    public function ajax_clear_test_history() {
        check_ajax_referer('aistma_test_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions', 'aistma-test-suite'));
        }
        
        // Build filters and drop empty values so we can detect truly empty filters (meaning clear all)
        $raw_filters = array(
            'test_name' => sanitize_text_field($_POST['test_name'] ?? ''),
            'category' => sanitize_text_field($_POST['category'] ?? ''),
            'status' => sanitize_text_field($_POST['status'] ?? ''),
            'start_date' => sanitize_text_field($_POST['start_date'] ?? ''),
            'end_date' => sanitize_text_field($_POST['end_date'] ?? '')
        );
        $filters = array_filter($raw_filters, function($v) { return $v !== null && $v !== ''; });
        
        $success = $this->clear_test_history($filters);
        
        if ($success) {
            wp_send_json_success(__('Test history cleared successfully', 'aistma-test-suite'));
        } else {
            wp_send_json_error(__('Failed to clear test history', 'aistma-test-suite'));
        }
    }
} 