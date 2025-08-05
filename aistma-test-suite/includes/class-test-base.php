<?php
/**
 * Base Test Class
 * 
 * All test classes should extend this base class.
 * 
 * @package AISTMA_Test_Suite
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * AISTMA_Test_Base Class
 */
abstract class AISTMA_Test_Base {
    
    /**
     * Test name
     */
    protected $test_name = '';
    
    /**
     * Test description
     */
    protected $test_description = '';
    
    /**
     * Test category
     */
    protected $test_category = 'General';
    
    /**
     * Required plugin name for this test (optional)
     * If specified, the test will only be enabled when this plugin is active
     */
    protected $required_plugin = '';
    
    /**
     * Test logs
     */
    protected $logs = array();
    
    /**
     * Test start time
     */
    protected $start_time;
    
    /**
     * Test end time
     */
    protected $end_time;
    
    /**
     * Test result
     */
    protected $result = null;
    
    /**
     * Test status
     */
    protected $status = 'pending';
    
    /**
     * Run the test
     * 
     * This method must be implemented by all test classes
     * 
     * @return string Test result message
     * @throws Exception If test fails
     */
    abstract public function run_test();
    
    /**
     * Get test name
     */
    public function get_test_name() {
        return $this->test_name;
    }
    
    /**
     * Get test description
     */
    public function get_test_description() {
        return $this->test_description;
    }
    
    /**
     * Get test category
     */
    public function get_test_category() {
        return $this->test_category;
    }
    
    /**
     * Get required plugin name
     */
    public function get_required_plugin() {
        return $this->required_plugin;
    }
    
    /**
     * Check if the required plugin is active
     */
    public function is_required_plugin_active() {
        if (empty($this->required_plugin)) {
            return true; // No plugin requirement
        }
        
        if (!function_exists('is_plugin_active')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }
        
        // Try different possible plugin file names
        $possible_files = array(
            strtolower(str_replace(' ', '-', $this->required_plugin)) . '/' . strtolower(str_replace(' ', '-', $this->required_plugin)) . '.php',
            strtolower(str_replace(' ', '-', $this->required_plugin)) . '.php'
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
            if (isset($plugin_data['Name']) && $plugin_data['Name'] === $this->required_plugin) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Get test logs
     */
    public function get_logs() {
        return $this->logs;
    }
    
    /**
     * Get test result
     */
    public function get_result() {
        return $this->result;
    }
    
    /**
     * Get test status
     */
    public function get_status() {
        return $this->status;
    }
    
    /**
     * Get test duration
     */
    public function get_duration() {
        if ($this->start_time && $this->end_time) {
            return round(($this->end_time - $this->start_time) * 1000, 2);
        }
        return 0;
    }
    
    /**
     * Execute the test
     */
    public function execute() {
        $this->start_time = microtime(true);
        $this->status = 'running';
        
        try {
            $this->log_info('Starting test: ' . $this->test_name);
            $this->result = $this->run_test();
            $this->status = 'passed';
            $this->log_info('Test completed successfully');
        } catch (Exception $e) {
            $this->status = 'failed';
            $this->result = $e->getMessage();
            $this->log_error('Test failed: ' . $e->getMessage());
        } catch (Error $e) {
            $this->status = 'error';
            $this->result = $e->getMessage();
            $this->log_error('Test error: ' . $e->getMessage());
        }
        
        $this->end_time = microtime(true);
        
        return array(
            'name' => $this->test_name,
            'description' => $this->test_description,
            'category' => $this->test_category,
            'status' => $this->status,
            'result' => $this->result,
            'duration' => $this->get_duration(),
            'logs' => $this->logs,
            'timestamp' => current_time('mysql')
        );
    }
    
    /**
     * Log info message
     */
    protected function log_info($message) {
        $this->log('INFO', $message);
    }
    
    /**
     * Log warning message
     */
    protected function log_warning($message) {
        $this->log('WARNING', $message);
    }
    
    /**
     * Log error message
     */
    protected function log_error($message) {
        $this->log('ERROR', $message);
    }
    
    /**
     * Log debug message
     */
    protected function log_debug($message) {
        $this->log('DEBUG', $message);
    }
    
    /**
     * Log message
     */
    private function log($level, $message) {
        $this->logs[] = array(
            'level' => $level,
            'message' => $message,
            'timestamp' => current_time('mysql')
        );
    }
    
    /**
     * Check if WordPress is loaded
     */
    protected function check_wordpress_loaded() {
        if (!defined('ABSPATH')) {
            throw new Exception('WordPress is not loaded');
        }
    }
    
    /**
     * Check if AI Story Maker plugin is active
     */
    protected function check_aistma_active() {
        if (!is_plugin_active('ai-story-maker/ai-story-maker.php')) {
            throw new Exception('AI Story Maker plugin is not active');
        }
    }
    
    /**
     * Check if class exists
     */
    protected function check_class_exists($class_name) {
        if (!class_exists($class_name)) {
            throw new Exception("Required class not found: $class_name");
        }
    }
    
    /**
     * Check if function exists
     */
    protected function check_function_exists($function_name) {
        if (!function_exists($function_name)) {
            throw new Exception("Required function not found: $function_name");
        }
    }
    
    /**
     * Check if table exists
     */
    protected function check_table_exists($table_name) {
        global $wpdb;
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
            throw new Exception("Required table not found: $table_name");
        }
    }
    
    /**
     * Check if option exists
     */
    protected function check_option_exists($option_name) {
        if (get_option($option_name) === false) {
            throw new Exception("Required option not found: $option_name");
        }
    }
    
    /**
     * Check if file exists
     */
    protected function check_file_exists($file_path) {
        if (!file_exists($file_path)) {
            throw new Exception("Required file not found: $file_path");
        }
    }
    
    /**
     * Check if directory exists
     */
    protected function check_directory_exists($dir_path) {
        if (!is_dir($dir_path)) {
            throw new Exception("Required directory not found: $dir_path");
        }
    }
    
    /**
     * Check if user has capability
     */
    protected function check_user_capability($capability) {
        if (!current_user_can($capability)) {
            throw new Exception("User does not have required capability: $capability");
        }
    }
    
    /**
     * Make HTTP request
     */
    protected function make_http_request($url, $args = array()) {
        $defaults = array(
            'timeout' => 30,
            'sslverify' => false
        );
        
        $args = wp_parse_args($args, $defaults);
        $response = wp_remote_request($url, $args);
        
        if (is_wp_error($response)) {
            throw new Exception('HTTP request failed: ' . $response->get_error_message());
        }
        
        return $response;
    }
    
    /**
     * Get memory usage
     */
    protected function get_memory_usage() {
        return array(
            'current' => memory_get_usage(true),
            'peak' => memory_get_peak_usage(true),
            'limit' => ini_get('memory_limit')
        );
    }
    
    /**
     * Get database query count
     */
    protected function get_db_query_count() {
        global $wpdb;
        return $wpdb->num_queries;
    }
    
    /**
     * Get load time
     */
    protected function get_load_time() {
        return timer_stop();
    }
    
    /**
     * Format bytes
     */
    protected function format_bytes($bytes) {
        $units = array('B', 'KB', 'MB', 'GB');
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        
        return round($bytes / pow(1024, $pow), 2) . ' ' . $units[$pow];
    }
} 