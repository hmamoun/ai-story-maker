<?php
/**
 * Plugin Requirement Test
 * 
 * This test verifies that the test suite correctly checks for the required plugin.
 * 
 * @package AISTMA_Test_Suite
 * @since 1.0.0
 */

class Plugin_Requirement_Test extends AISTMA_Test_Base {
    
    /**
     * Test name
     */
    protected $test_name = "Plugin Requirement Test";
    
    /**
     * Test description
     */
    protected $test_description = "Verifies that the AI Story Maker plugin is active and required for tests to run";
    
    /**
     * Test category
     */
    protected $test_category = "General";
    
    /**
     * Run the test
     */
    public function run_test() {
        $this->log_info("Starting plugin requirement test");
        
        // Get the test suite instance
        $test_suite = AISTMA_Test_Suite::get_instance();
        $required_plugin_name = $test_suite->get_test_plugin_name();
        
        $this->log_info("Required plugin: " . $required_plugin_name);
        
        // Check if the plugin is active
        $is_active = $test_suite->is_test_plugin_active();
        
        if ($is_active) {
            $this->log_info("Plugin is active - tests should be available");
            
            // Verify that tests are discoverable
            $test_runner = $test_suite->get_test_runner();
            $available_tests = $test_runner->get_available_tests();
            
            $this->log_info("Available tests count: " . count($available_tests));
            
            if (count($available_tests) > 0) {
                $this->log_info("Tests are discoverable - requirement check working correctly");
                return "Plugin requirement test passed - plugin is active and tests are available";
            } else {
                throw new Exception("Plugin is active but no tests are discoverable");
            }
        } else {
            $this->log_warning("Plugin is not active - tests should not be available");
            
            // Verify that tests are not discoverable
            $test_runner = $test_suite->get_test_runner();
            $available_tests = $test_runner->get_available_tests();
            
            $this->log_info("Available tests count: " . count($available_tests));
            
            if (count($available_tests) === 0) {
                $this->log_info("Tests are not discoverable - requirement check working correctly");
                return "Plugin requirement test passed - plugin is not active and tests are properly hidden";
            } else {
                throw new Exception("Plugin is not active but tests are still discoverable");
            }
        }
    }
} 