<?php
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
            "exedotcom\aistorymaker\AISTMA_Log_Manager",
            "exedotcom\aistorymaker\AISTMA_Story_Generator"
        );
        
        foreach ($required_classes as $class) {
            if (!class_exists($class)) {
                throw new Exception("Required class not found: $class");
            }
        }
        
        $this->log_info("Sample test completed successfully");
        return "Sample test passed successfully";
    }
}