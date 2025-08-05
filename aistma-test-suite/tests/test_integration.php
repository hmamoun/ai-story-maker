<?php
/**
 * Integration Test
 * 
 * Tests the complete flow:
 * 1. Local plugin checks subscription
 * 2. Local plugin calls master server API
 * 3. Master server validates subscription
 * 4. Master server generates content
 * 5. Local plugin receives and processes response
 * 
 * @package AISTMA_Test_Suite
 * @since 1.0.0
 */

class Test_Integration extends AISTMA_Test_Base {
    
    /**
     * Test name
     */
    protected $test_name = "Integration Test";
    

    /**
     * Test name
     */
    protected $test_plugin_name = "AI Story Maker";
    

    
    /**
     * Test description
     */
    protected $test_description = "Tests the complete integration flow between local plugin and master server";
    
    /**
     * Test category
     */
    protected $test_category = "Integration";
    
    /**
     * Master server URL for API calls
     */
    private $master_url;
    
    /**
     * Subscription URL for subscription checks
     */
    private $subscription_url;
    
    /**
     * Test domain
     */
    private $test_domain;
    
    /**
     * Constructor
     */
    public function __construct() {
        // Set master URL for API calls
        if (defined('AISTMA_MASTER_URL')) {
            $this->master_url = rtrim(AISTMA_MASTER_URL, '/');
        } else {
            $this->master_url = 'https://exedotcom.ca';
        }
        
        // Set subscription URL for subscription checks
        if (defined('AISTMA_SUBSCRIBTION_URL')) {
            //$this->subscription_url = rtrim(AISTMA_SUBSCRIBTION_URL, '/');
            /**
             * in the test, both subscription and story generation are done on the same server, like in the real world
             */
            $this->subscription_url = rtrim(AISTMA_MASTER_URL, '/');
        } else {
            $this->subscription_url = 'https://exedotcom.ca';
        }
        
        // Get current domain with port if it exists (same as the actual plugin)
        $this->test_domain = $_SERVER['HTTP_HOST'] ?? 'localhost';
        // Ensure port is included if present
        if (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] != '80' && $_SERVER['SERVER_PORT'] != '443') {
            if (strpos($this->test_domain, ':') === false) {
                $this->test_domain .= ':' . $_SERVER['SERVER_PORT'];
            }
        }
        
        // Log which URLs we're using for debugging
        $this->log_info("Using master URL for API calls: " . $this->master_url);
        $this->log_info("Using subscription URL for subscription checks: " . $this->subscription_url);
        $this->log_info("Testing with domain: " . $this->test_domain);
    }
    
    /**
     * Run the test
     */
    public function run_test() {
        $this->log_info("Starting integration test");
        $this->log_info("Master URL: " . $this->master_url);
        $this->log_info("Subscription URL: " . $this->subscription_url);
        $this->log_info("Testing domain: " . $this->test_domain);
        
        // Check if WordPress is loaded
        $this->check_wordpress_loaded();
        
        // Check if AI Story Maker plugin is active
        $this->check_aistma_active();
        
        // Step 1: Test subscription status check
        $this->log_info("Step 1: Testing subscription status check for domain: " . $this->test_domain);
        $subscription_response = $this->test_subscription_check();
        if (!$subscription_response['valid']) {
            throw new Exception("Subscription check failed for domain '{$this->test_domain}': " . $subscription_response['error']);
        }
        $this->log_info("✅ Subscription check passed for domain '{$this->test_domain}' - Package: " . $subscription_response['package_name']);
        
        // Step 2: Test story generation
        $this->log_info("Step 2: Testing story generation");
        $generation_response = $this->test_story_generation();
        if (!$generation_response['success']) {
            throw new Exception("Story generation failed: " . $generation_response['error']);
        }
        $this->log_info("✅ Story generation successful - Title: " . $generation_response['content']['title'] . $generation_response['content']['body'] );
        
       
        
        $this->log_info("Integration test completed successfully");
        return "Integration test passed for domain '{$this->test_domain}' - All components working correctly";
    }
    
    /**
     * Test subscription status check (uses subscription URL)
     */
    private function test_subscription_check() {
        // Use the same method as the actual plugin
        $api_url = $this->master_url . '/wp-json/exaig/v1/verify-subscription?domain=' . urlencode($this->test_domain);
// url should be "http://bb-wp2:8082/ai-story-maker-plans/?domain=localhost&port=8080&email=hmamoun%40gmail.com"
        $response = $this->make_http_request(
            $api_url,
            [
                'method' => 'GET',
                'headers' => [
                    'User-Agent' => 'AI-Story-Maker/1.0',
                ],
                'timeout' => 30,
                'sslverify' => false
            ]
        );

        if (!$response['success']) {
            return $response;
        }

        $response_data = json_decode($response['body'], true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return ['success' => false, 'error' => 'Invalid JSON response: ' . json_last_error_msg()];
        }

        if (!is_array($response_data)) {
            return ['success' => false, 'error' => 'Response is not an array'];
        }

        return $response_data;
    }
    
    /**
     * Test story generation (uses master URL)
     */
    private function test_story_generation() {
        $test_data = [
            'domain' => $this->test_domain,
            'prompt_id' => 'test_' . time(),
            'prompt_text' => 'Write a short article about artificial intelligence in healthcare. Include specific examples of how AI is being used to improve patient outcomes. return json format',
            'settings' => [
                'model' => 'gpt-4-turbo',
                'max_tokens' => 800,
                'system_content' => 'You are a professional writer who creates engaging, informative articles. Always provide accurate information and include relevant examples.',
                'timeout' => 30
            ],
            'recent_posts' => [],
            'category' => 'Technology',
            'photos' => 2
        ];

        $response = $this->make_http_request(
            $this->master_url . '/wp-json/exaig/v1/generate-story',
            [
                'method' => 'POST',
                'body' => json_encode($test_data),
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                    'X-Caller-Url' => home_url(),
                    'X-Caller-IP' => isset($_SERVER['SERVER_ADDR']) ? sanitize_text_field(wp_unslash($_SERVER['SERVER_ADDR'])) : '',
                ],
                'timeout' => 60,
                'sslverify' => false
            ]
        );


        //log the number of requested photos, and the number of photo tags returned in the response
        $this->log_info("Number of requested photos: " . $test_data['photos']);
        // Count photo tags that start with {photo
        $content = $response_data['content']['content'] ?? '';
        $photo_tags_count = preg_match_all('/\{photo[^}]*\}/', $content, $matches);
        $this->log_info("Number of photo tags returned: " . $photo_tags_count);

        if (!$response['success']) {
            return $response;
        }

        $response_data = json_decode($response['body'], true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return ['success' => false, 'error' => 'Invalid JSON response: ' . json_last_error_msg()];
        }

        if (!is_array($response_data)) {
            return ['success' => false, 'error' => 'Response is not an array'];
        }

        return $response_data;
    }
    

    /**
     * Override the make_http_request method to add better error handling
     */
    protected function make_http_request($url, $args = array()) {
        try {
            $response = wp_remote_request($url, $args);
            
            if (is_wp_error($response)) {
                return [
                    'success' => false,
                    'error' => 'HTTP request failed: ' . $response->get_error_message()
                ];
            }
            
            $http_code = wp_remote_retrieve_response_code($response);
            $body = wp_remote_retrieve_body($response);
            
            if ($http_code !== 200) {
                return [
                    'success' => false,
                    'error' => "HTTP request failed with status code: $http_code",
                    'body' => $body
                ];
            }
            
            return [
                'success' => true,
                'body' => $body,
                'http_code' => $http_code
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => 'Exception during HTTP request: ' . $e->getMessage()
            ];
        }
    }
} 