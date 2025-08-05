<?php
/**
 * WooCommerce Integration Test
 * 
 * This test demonstrates individual plugin requirements.
 * It will only be enabled when WooCommerce is active.
 * 
 * @package AISTMA_Test_Suite
 * @since 1.0.0
 */

class WooCommerce_Integration_Test extends AISTMA_Test_Base {
    
    /**
     * Test name
     */
    protected $test_name = "WooCommerce Integration Test";
    
    /**
     * Test description
     */
    protected $test_description = "Tests WooCommerce integration features and functionality";
    
    /**
     * Test category
     */
    protected $test_category = "E-commerce";
    
    /**
     * Required plugin for this test
     */
    protected $required_plugin = "WooCommerce";
    
    /**
     * Run the test
     */
    public function run_test() {
        $this->log_info("Starting WooCommerce integration test");
        
        // Check if WooCommerce is active
        if (!$this->is_required_plugin_active()) {
            throw new Exception("WooCommerce is required for this test");
        }
        
        $this->log_info("WooCommerce is active - proceeding with test");
        
        // Test WooCommerce functions
        $this->check_function_exists('wc_get_product');
        $this->check_function_exists('wc_get_order');
        $this->check_function_exists('wc_get_cart');
        
        // Test WooCommerce classes
        $this->check_class_exists('WC_Product');
        $this->check_class_exists('WC_Order');
        $this->check_class_exists('WC_Cart');
        
        // Test WooCommerce database tables
        $this->check_table_exists($GLOBALS['wpdb']->prefix . 'woocommerce_order_items');
        $this->check_table_exists($GLOBALS['wpdb']->prefix . 'woocommerce_order_itemmeta');
        
        // Test WooCommerce options
        $this->check_option_exists('woocommerce_currency');
        $this->check_option_exists('woocommerce_weight_unit');
        
        $this->log_info("WooCommerce integration test completed successfully");
        return "WooCommerce integration test passed - all required components are available";
    }
} 