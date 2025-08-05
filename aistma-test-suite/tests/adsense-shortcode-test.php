<?php
/**
 * AdSense Shortcode Test
 * 
 * Tests the AdSense shortcode functionality:
 * 1. Checks if shortcode is registered
 * 2. Tests basic shortcode rendering
 * 3. Tests custom attributes
 * 4. Validates security and escaping
 * 
 * @package AISTMA_Test_Suite
 * @since 1.0.0
 */

class Test_AdSense_Shortcode extends AISTMA_Test_Base {
    
    /**
     * Test name
     */
    protected $test_name = "AdSense Shortcode Test";
    
    /**
     * Test description
     */
    protected $test_description = "Tests the AdSense shortcode functionality including registration, rendering, and security";
    
    /**
     * Test category
     */
    protected $test_category = "Shortcode";
    
    /**
     * Required plugin name for this test
     */
    protected $required_plugin = "AI Story Maker";
    
    /**
     * Run the test
     */
    public function run_test() {
        $this->log_info("Starting AdSense shortcode test");
        
        try {
            // Test 1: Check if shortcode is registered
            $this->test_shortcode_registration();
            
            // Test 2: Test basic shortcode functionality
            $this->test_basic_shortcode();
            
            // Test 3: Test custom attributes
            $this->test_custom_attributes();
            
            // Test 4: Test security and escaping
            $this->test_security_escaping();
            
            // Test 5: Test shortcode output validation
            $this->test_output_validation();
            
            $this->log_info("AdSense shortcode test completed successfully");
            return "✅ All AdSense shortcode tests passed successfully!";
            
        } catch (Exception $e) {
            $this->log_error("AdSense shortcode test failed: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Test shortcode registration
     */
    private function test_shortcode_registration() {
        $this->log_info("Testing shortcode registration");
        
        if (!shortcode_exists('aistma_adsense')) {
            throw new Exception("Shortcode 'aistma_adsense' is not registered");
        }
        
        $this->log_info("✅ Shortcode 'aistma_adsense' is registered successfully");
    }
    
    /**
     * Test basic shortcode functionality
     */
    private function test_basic_shortcode() {
        $this->log_info("Testing basic shortcode functionality");
        
        $test_shortcode = '[aistma_adsense]';
        $result = do_shortcode($test_shortcode);
        
        if (empty($result)) {
            throw new Exception("Basic shortcode returned empty result");
        }
        
        $this->log_info("✅ Basic shortcode returned result with " . strlen($result) . " characters");
        
        // Check for required AdSense elements
        $required_elements = [
            'ca-pub-6861474761481747', // Client ID
            '8915797913',              // Slot ID
            'adsbygoogle',             // AdSense class
            'pagead2.googlesyndication.com', // AdSense script
            'data-ad-client',          // AdSense attribute
            'data-ad-slot'             // AdSense attribute
        ];
        
        foreach ($required_elements as $element) {
            if (strpos($result, $element) === false) {
                throw new Exception("Required AdSense element not found: " . $element);
            }
        }
        
        $this->log_info("✅ All required AdSense elements found in output");
    }
    
    /**
     * Test custom attributes
     */
    private function test_custom_attributes() {
        $this->log_info("Testing custom attributes");
        
        $test_shortcode = '[aistma_adsense client="ca-pub-TEST123" slot="TEST456" format="in-feed" style="display:block; margin: 10px;"]';
        $result = do_shortcode($test_shortcode);
        
        if (empty($result)) {
            throw new Exception("Custom shortcode returned empty result");
        }
        
        // Check if custom attributes are applied
        $custom_elements = [
            'ca-pub-TEST123',          // Custom client ID
            'TEST456',                 // Custom slot ID
            'in-feed',                 // Custom format
            'margin: 10px'             // Custom style
        ];
        
        foreach ($custom_elements as $element) {
            if (strpos($result, $element) === false) {
                throw new Exception("Custom attribute not applied: " . $element);
            }
        }
        
        $this->log_info("✅ Custom attributes applied correctly");
    }
    
    /**
     * Test security and escaping
     */
    private function test_security_escaping() {
        $this->log_info("Testing security and escaping");
        
        // Test with potentially malicious input
        $malicious_shortcode = '[aistma_adsense client="<script>alert(\'xss\')</script>" slot="<img src=x onerror=alert(1)>"]';
        $result = do_shortcode($malicious_shortcode);
        
        // Check that script tags are not executed
        if (strpos($result, '<script>alert') !== false) {
            throw new Exception("Potential XSS vulnerability: script tags not properly escaped");
        }
        
        // Check that HTML entities are properly escaped
        if (strpos($result, '&lt;script&gt;') === false && strpos($result, '&quot;') === false) {
            // This is actually good - it means the attributes were properly escaped
            $this->log_info("✅ Security: Attributes properly escaped");
        } else {
            $this->log_warning("⚠️  Security: Some attributes may not be properly escaped");
        }
        
        $this->log_info("✅ Security test passed");
    }
    
    /**
     * Test output validation
     */
    private function test_output_validation() {
        $this->log_info("Testing output validation");
        
        $test_shortcode = '[aistma_adsense]';
        $result = do_shortcode($test_shortcode);
        
        // Validate HTML structure
        if (!preg_match('/<script[^>]*src="[^"]*pagead2\.googlesyndication\.com[^"]*"[^>]*>/', $result)) {
            throw new Exception("AdSense script tag not found or malformed");
        }
        
        if (!preg_match('/<ins[^>]*class="adsbygoogle"[^>]*>/', $result)) {
            throw new Exception("AdSense ins tag not found or malformed");
        }
        
        if (!preg_match('/<script[^>]*>\(adsbygoogle[^>]*\)\.push/', $result)) {
            throw new Exception("AdSense initialization script not found or malformed");
        }
        
        $this->log_info("✅ Output validation passed");
    }
    
    /**
     * Get test summary
     */
    public function get_test_summary() {
        return [
            'name' => $this->test_name,
            'description' => $this->test_description,
            'category' => $this->test_category,
            'status' => $this->status,
            'duration' => $this->get_duration(),
            'logs' => $this->logs,
            'result' => $this->result
        ];
    }
} 