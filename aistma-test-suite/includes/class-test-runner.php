<?php
/**
 * Test Runner Class
 * 
 * Discovers and executes test files.
 * 
 * @package AISTMA_Test_Suite
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * AISTMA_Test_Runner Class
 */
class AISTMA_Test_Runner {
    
    /**
     * Available tests
     */
    private $available_tests = array();
    
    /**
     * Test results
     */
    private $test_results = array();
    
    /**
     * Initialize
     */
    public function init() {
        add_action('wp_ajax_run_aistma_test', array($this, 'ajax_run_single_test'));
        add_action('wp_ajax_run_aistma_tests', array($this, 'ajax_run_all_tests'));
        add_action('wp_ajax_get_aistma_tests', array($this, 'ajax_get_available_tests'));
    }
    
    /**
     * Discover available tests
     */
    public function discover_tests() {
        $this->available_tests = array();
        
        // Check if the test plugin is active
        $test_suite = AISTMA_Test_Suite::get_instance();
        if (!$test_suite->is_test_plugin_active()) {
            return array();
        }
        
        if (!is_dir(AISTMA_TEST_SUITE_TESTS_DIR)) {
            return array();
        }
        
        $test_files = glob(AISTMA_TEST_SUITE_TESTS_DIR . '*.php');
        
        foreach ($test_files as $test_file) {
            $test_info = $this->get_test_info($test_file);
            if ($test_info) {
                $this->available_tests[] = $test_info;
            }
        }
        
        return $this->available_tests;
    }
    
    /**
     * Get test information from file
     */
    private function get_test_info($test_file) {
        $file_content = file_get_contents($test_file);
        
        if (!$file_content) {
            return false;
        }
        
        // Extract class name
        if (preg_match('/class\s+(\w+)\s+extends\s+AISTMA_Test_Base/', $file_content, $matches)) {
            $class_name = $matches[1];
        } else {
            return false;
        }
        
        // Extract test name
        if (preg_match('/protected\s+\$test_name\s*=\s*["\']([^"\']+)["\']/', $file_content, $matches)) {
            $test_name = $matches[1];
        } else {
            $test_name = $class_name;
        }
        
        // Extract test description
        if (preg_match('/protected\s+\$test_description\s*=\s*["\']([^"\']+)["\']/', $file_content, $matches)) {
            $test_description = $matches[1];
        } else {
            $test_description = '';
        }
        
        // Extract test category
        if (preg_match('/protected\s+\$test_category\s*=\s*["\']([^"\']+)["\']/', $file_content, $matches)) {
            $test_category = $matches[1];
        } else {
            $test_category = 'General';
        }
        
        // Extract required plugin
        $required_plugin = '';
        if (preg_match('/protected\s+\$required_plugin\s*=\s*["\']([^"\']+)["\']/', $file_content, $matches)) {
            $required_plugin = $matches[1];
        }
        
        // Check if required plugin is active
        $plugin_active = true;
        if (!empty($required_plugin)) {
            $plugin_active = $this->is_plugin_active($required_plugin);
        }
        
        return array(
            'file' => $test_file,
            'class' => $class_name,
            'name' => $test_name,
            'description' => $test_description,
            'category' => $test_category,
            'required_plugin' => $required_plugin,
            'plugin_active' => $plugin_active,
            'filename' => basename($test_file)
        );
    }
    
    /**
     * Run single test
     */
    public function run_single_test($test_file) {
        if (!file_exists($test_file)) {
            throw new Exception('Test file not found: ' . $test_file);
        }
        
        // Include the base class first
        require_once AISTMA_TEST_SUITE_PLUGIN_DIR . 'includes/class-test-base.php';
        
        // Include the test file
        require_once $test_file;
        
        // Get test info
        $test_info = $this->get_test_info($test_file);
        if (!$test_info) {
            throw new Exception('Invalid test file: ' . $test_file);
        }
        
        // Check if required plugin is active
        if (!empty($test_info['required_plugin']) && !$test_info['plugin_active']) {
            throw new Exception('Test requires plugin "' . $test_info['required_plugin'] . '" to be active');
        }
        
        // Create test instance
        $class_name = $test_info['class'];
        if (!class_exists($class_name)) {
            throw new Exception('Test class not found: ' . $class_name);
        }
        
        $test_instance = new $class_name();
        
        // Execute test
        $result = $test_instance->execute();
        
        // Save to history
        $this->save_test_result($result);
        
        return $result;
    }
    
    /**
     * Run all tests
     */
    public function run_all_tests() {
        $this->test_results = array();
        $tests = $this->discover_tests();
        
        foreach ($tests as $test) {
            // Skip tests that have inactive required plugins
            if (!empty($test['required_plugin']) && !$test['plugin_active']) {
                $this->test_results[] = array(
                    'name' => $test['name'],
                    'description' => $test['description'],
                    'category' => $test['category'],
                    'status' => 'skipped',
                    'result' => 'Test skipped - required plugin "' . $test['required_plugin'] . '" is not active',
                    'duration' => 0,
                    'logs' => array(),
                    'timestamp' => current_time('mysql'),
                    'required_plugin' => $test['required_plugin'],
                    'plugin_active' => false
                );
                continue;
            }
            
            try {
                $result = $this->run_single_test($test['file']);
                $this->test_results[] = $result;
            } catch (Exception $e) {
                $this->test_results[] = array(
                    'name' => $test['name'],
                    'description' => $test['description'],
                    'category' => $test['category'],
                    'status' => 'error',
                    'result' => $e->getMessage(),
                    'duration' => 0,
                    'logs' => array(),
                    'timestamp' => current_time('mysql')
                );
            }
        }
        
        return $this->test_results;
    }
    
    /**
     * Run tests by category
     */
    public function run_tests_by_category($category) {
        $this->test_results = array();
        $tests = $this->discover_tests();
        
        foreach ($tests as $test) {
            if ($test['category'] === $category) {
                try {
                    $result = $this->run_single_test($test['file']);
                    $this->test_results[] = $result;
                } catch (Exception $e) {
                    $this->test_results[] = array(
                        'name' => $test['name'],
                        'description' => $test['description'],
                        'category' => $test['category'],
                        'status' => 'error',
                        'result' => $e->getMessage(),
                        'duration' => 0,
                        'logs' => array(),
                        'timestamp' => current_time('mysql')
                    );
                }
            }
        }
        
        return $this->test_results;
    }
    
    /**
     * Save test result to history
     */
    private function save_test_result($result) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'aistma_test_history';
        
        $wpdb->insert(
            $table_name,
            array(
                'test_name' => $result['name'],
                'test_description' => $result['description'],
                'test_category' => $result['category'],
                'test_status' => $result['status'],
                'test_result' => $result['result'],
                'test_duration' => $result['duration'],
                'test_logs' => json_encode($result['logs']),
                'created_at' => $result['timestamp']
            ),
            array('%s', '%s', '%s', '%s', '%s', '%f', '%s', '%s')
        );
    }
    
    /**
     * Get test results summary
     */
    public function get_test_summary($results = null) {
        if ($results === null) {
            $results = $this->test_results;
        }
        
        $total = count($results);
        $passed = 0;
        $failed = 0;
        $errors = 0;
        $skipped = 0;
        $total_duration = 0;
        
        foreach ($results as $result) {
            $total_duration += $result['duration'];
            
            switch ($result['status']) {
                case 'passed':
                    $passed++;
                    break;
                case 'failed':
                    $failed++;
                    break;
                case 'error':
                    $errors++;
                    break;
                case 'skipped':
                    $skipped++;
                    break;
            }
        }
        
        return array(
            'total' => $total,
            'passed' => $passed,
            'failed' => $failed,
            'errors' => $errors,
            'skipped' => $skipped,
            'success_rate' => $total > 0 ? round(($passed / $total) * 100, 2) : 0,
            'total_duration' => $total_duration
        );
    }
    
    /**
     * AJAX handler for running single test
     */
    public function ajax_run_single_test() {
        check_ajax_referer('aistma_test_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions', 'aistma-test-suite'));
        }
        
        // Check if the test plugin is active
        $test_suite = AISTMA_Test_Suite::get_instance();
        if (!$test_suite->is_test_plugin_active()) {
            wp_send_json_error(__('Required plugin is not active', 'aistma-test-suite'));
        }
        
        $test_file = sanitize_text_field($_POST['test_file']);
        
        try {
            $result = $this->run_single_test($test_file);
            wp_send_json_success($result);
        } catch (Exception $e) {
            wp_send_json_error($e->getMessage());
        }
    }
    
    /**
     * AJAX handler for running all tests
     */
    public function ajax_run_all_tests() {
        check_ajax_referer('aistma_test_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions', 'aistma-test-suite'));
        }
        
        // Check if the test plugin is active
        $test_suite = AISTMA_Test_Suite::get_instance();
        if (!$test_suite->is_test_plugin_active()) {
            wp_send_json_error(__('Required plugin is not active', 'aistma-test-suite'));
        }
        
        $start_time = microtime(true);
        $results = $this->run_all_tests();
        $execution_time = round((microtime(true) - $start_time) * 1000, 2);
        
        $summary = $this->get_test_summary($results);
        
        wp_send_json_success(array(
            'results' => $results,
            'summary' => $summary,
            'execution_time' => $execution_time
        ));
    }
    
    /**
     * AJAX handler for getting available tests
     */
    public function ajax_get_available_tests() {
        check_ajax_referer('aistma_test_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions', 'aistma-test-suite'));
        }
        
        // Check if the test plugin is active
        $test_suite = AISTMA_Test_Suite::get_instance();
        if (!$test_suite->is_test_plugin_active()) {
            wp_send_json_error(__('Required plugin is not active', 'aistma-test-suite'));
        }
        
        $tests = $this->discover_tests();
        
        // Group tests by category
        $grouped_tests = array();
        foreach ($tests as $test) {
            $category = $test['category'];
            if (!isset($grouped_tests[$category])) {
                $grouped_tests[$category] = array();
            }
            $grouped_tests[$category][] = $test;
        }
        
        wp_send_json_success($grouped_tests);
    }
    
    /**
     * Get available tests
     */
    public function get_available_tests() {
        return $this->discover_tests();
    }
    
    /**
     * Check if a plugin is active
     */
    private function is_plugin_active($plugin_name) {
        if (empty($plugin_name)) {
            return true;
        }
        
        if (!function_exists('is_plugin_active')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }
        
        // Try different possible plugin file names
        $possible_files = array(
            strtolower(str_replace(' ', '-', $plugin_name)) . '/' . strtolower(str_replace(' ', '-', $plugin_name)) . '.php',
            strtolower(str_replace(' ', '-', $plugin_name)) . '.php'
        );
        
        foreach ($possible_files as $plugin_file) {
            if (is_plugin_active($plugin_file)) {
                return true;
            }
        }
        
        // Also check by plugin name in the plugins list
        $active_plugins = get_option('active_plugins');
        foreach ($active_plugins as $plugin) {
            $plugin_data = get_plugin_data(WP_PLUGIN_DIR . '/' . $plugin);
            if (isset($plugin_data['Name']) && $plugin_data['Name'] === $plugin_name) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Get test results
     */
    public function get_test_results() {
        return $this->test_results;
    }
} 