<?php
/**
 * Plugin Name: AI Story Maker Test Suite
 * Plugin URI: https://github.com/your-repo/ai-story-maker-test-suite
 * Description: Scalable test suite for AI Story Maker plugin. Install temporarily for debugging production issues.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: aistma-test-suite
 * 
 * @package AISTMA_Test_Suite
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('AISTMA_TEST_SUITE_VERSION', '1.0.0');
define('AISTMA_TEST_SUITE_PLUGIN_FILE', __FILE__);
define('AISTMA_TEST_SUITE_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('AISTMA_TEST_SUITE_PLUGIN_URL', plugin_dir_url(__FILE__));
define('AISTMA_TEST_SUITE_TESTS_DIR', AISTMA_TEST_SUITE_PLUGIN_DIR . 'tests/');

/**
 * Main Test Suite Class
 */
class AISTMA_Test_Suite {
    
    private static $instance = null;
    private $test_runner;
    private $admin_manager;
    private $history_manager;
    private $debug_logger;
    
    /**
     * Test plugin name - tests will only be shown if this plugin is enabled
     */
    protected $test_plugin_name = "AI Story Maker";
    
    /**
     * Get singleton instance
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        $this->init_components();
        $this->init_hooks();
    }
    
    /**
     * Initialize components
     */
    private function init_components() {
        require_once AISTMA_TEST_SUITE_PLUGIN_DIR . 'includes/class-test-runner.php';
        require_once AISTMA_TEST_SUITE_PLUGIN_DIR . 'includes/class-admin-manager.php';
        require_once AISTMA_TEST_SUITE_PLUGIN_DIR . 'includes/class-history-manager.php';
        require_once AISTMA_TEST_SUITE_PLUGIN_DIR . 'includes/class-debug-logger.php';
        
        $this->test_runner = new AISTMA_Test_Runner();
        $this->admin_manager = new AISTMA_Admin_Manager();
        $this->history_manager = new AISTMA_History_Manager();
        $this->debug_logger = new AISTMA_Debug_Logger();
    }
    
    /**
     * Initialize hooks
     */
    private function init_hooks() {
        add_action('init', array($this, 'init'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }
    
    /**
     * Initialize plugin
     */
    public function init() {
        // Load text domain
        load_plugin_textdomain('aistma-test-suite', false, dirname(plugin_basename(__FILE__)) . '/languages');
        
        // Initialize components
        $this->test_runner->init();
        $this->admin_manager->init();
        $this->history_manager->init();
        $this->debug_logger->init();
    }
    
    /**
     * Plugin activation
     */
    public function activate() {
        // Create necessary directories
        wp_mkdir_p(AISTMA_TEST_SUITE_TESTS_DIR);
        wp_mkdir_p(AISTMA_TEST_SUITE_PLUGIN_DIR . 'assets/css');
        wp_mkdir_p(AISTMA_TEST_SUITE_PLUGIN_DIR . 'assets/js');
        wp_mkdir_p(AISTMA_TEST_SUITE_PLUGIN_DIR . 'templates');
        
        // Create database tables
        $this->history_manager->create_tables();
        
        // Create sample test file
        $this->create_sample_test();
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    /**
     * Plugin deactivation
     */
    public function deactivate() {
        // Clean up any temporary data
        delete_transient('aistma_test_results');
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    /**
     * Create sample test file
     */
    private function create_sample_test() {
        $sample_test_file = AISTMA_TEST_SUITE_TESTS_DIR . 'sample-test.php';
        
        if (!file_exists($sample_test_file)) {
            $sample_content = '<?php
/**
 * Sample Test File
 * 
 * This is a sample test file to demonstrate the test structure.
 * Copy this file and modify it to create your own tests.
 * 
 * @package AISTMA_Test_Suite
 * @since 1.0.0
 */

class Sample_Test extends AISTMA_Test_Base {
    
    /**
     * Test name
     */
    protected $test_name = "Sample Test";
    
    /**
     * Test description
     */
    protected $test_description = "This is a sample test to demonstrate the test structure";
    
    /**
     * Test category
     */
    protected $test_category = "General";
    
    /**
     * Run the test
     */
    public function run_test() {
        // Your test logic goes here
        $this->log_info("Starting sample test");
        
        // Example: Check if WordPress is loaded
        if (!defined("ABSPATH")) {
            throw new Exception("WordPress is not loaded");
        }
        
        // Example: Check if AI Story Maker plugin is active
        $test_suite = AISTMA_Test_Suite::get_instance();
        if (!$test_suite->is_test_plugin_active()) {
            throw new Exception("Required plugin is not active: " . $test_suite->get_test_plugin_name());
        }
        
        // Example: Check if required classes exist
        $required_classes = array(
            "exedotcom\\aistorymaker\\AISTMA_Log_Manager",
            "exedotcom\\aistorymaker\\AISTMA_Story_Generator"
        );
        
        foreach ($required_classes as $class) {
            if (!class_exists($class)) {
                throw new Exception("Required class not found: $class");
            }
        }
        
        $this->log_info("Sample test completed successfully");
        return "Sample test passed successfully";
    }
}';
            
            file_put_contents($sample_test_file, $sample_content);
        }
    }
    
    /**
     * Get test runner instance
     */
    public function get_test_runner() {
        return $this->test_runner;
    }
    
    /**
     * Get admin manager instance
     */
    public function get_admin_manager() {
        return $this->admin_manager;
    }
    
    /**
     * Get history manager instance
     */
    public function get_history_manager() {
        return $this->history_manager;
    }
    
    /**
     * Get debug logger instance
     */
    public function get_debug_logger() {
        return $this->debug_logger;
    }
    
    /**
     * Check if the test plugin is active
     */
    public function is_test_plugin_active() {
        if (empty($this->test_plugin_name)) {
            return true; // If no plugin name specified, show tests anyway
        }
        
        // Check if the plugin is active by name
        if (!function_exists('is_plugin_active')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }
        
        // Try different possible plugin file names
        $possible_files = array(
            'ai-story-maker/ai-story-maker.php',
            'ai-story-maker.php',
            strtolower(str_replace(' ', '-', $this->test_plugin_name)) . '/' . strtolower(str_replace(' ', '-', $this->test_plugin_name)) . '.php',
            strtolower(str_replace(' ', '-', $this->test_plugin_name)) . '.php'
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
            if (isset($plugin_data['Name']) && $plugin_data['Name'] === $this->test_plugin_name) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Get test plugin name
     */
    public function get_test_plugin_name() {
        return $this->test_plugin_name;
    }
}

// Initialize the plugin
AISTMA_Test_Suite::get_instance(); 