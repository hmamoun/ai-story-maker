<?php
/**
 * Integration Test Script
 * 
 * This script tests the complete flow:
 * 1. Local plugin checks subscription
 * 2. Local plugin calls master server API
 * 3. Master server validates subscription
 * 4. Master server generates content
 * 5. Local plugin receives and processes response
 * 
 * Run this from the command line: php test_integration.php
 */

// Configuration
$master_url = 'http://localhost:8080'; // Update this to your master server URL
$test_domain = 'test-domain.com';

echo "AI Story Maker Integration Test\n";
echo "==============================\n";
echo "Master URL: $master_url\n";
echo "Test Domain: $test_domain\n\n";

// Step 1: Test subscription status check
echo "Step 1: Testing subscription status check...\n";
$subscription_response = test_subscription_check($master_url, $test_domain);
if (!$subscription_response['success']) {
    echo "❌ Subscription check failed: " . $subscription_response['error'] . "\n";
    echo "Please ensure the test domain has a valid subscription in the master server database.\n\n";
} else {
    echo "✅ Subscription check passed\n";
    echo "Package: " . $subscription_response['package_name'] . "\n";
    echo "Credits remaining: " . $subscription_response['credits_remaining'] . "\n\n";
}

// Step 2: Test story generation
echo "Step 2: Testing story generation...\n";
$generation_response = test_story_generation($master_url, $test_domain);
if (!$generation_response['success']) {
    echo "❌ Story generation failed: " . $generation_response['error'] . "\n\n";
} else {
    echo "✅ Story generation successful!\n";
    echo "Title: " . $generation_response['content']['title'] . "\n";
    echo "Tokens used: " . $generation_response['usage']['total_tokens'] . "\n";
    echo "Request ID: " . $generation_response['usage']['request_id'] . "\n\n";
}

// Step 3: Test local plugin simulation
echo "Step 3: Testing local plugin simulation...\n";
$local_simulation = test_local_plugin_simulation($master_url, $test_domain);
if (!$local_simulation['success']) {
    echo "❌ Local plugin simulation failed: " . $local_simulation['error'] . "\n\n";
} else {
    echo "✅ Local plugin simulation successful!\n";
    echo "Post created with ID: " . $local_simulation['post_id'] . "\n";
    echo "Generation method: " . $local_simulation['generation_method'] . "\n\n";
}

echo "Integration test completed!\n";

/**
 * Test subscription status check
 */
function test_subscription_check($master_url, $domain) {
    $test_data = [
        'domain' => $domain
    ];

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $master_url . '/wp-json/exaig/v1/subscription-status',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($test_data),
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Accept: application/json'
        ],
        CURLOPT_TIMEOUT => 30,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false
    ]);

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    echo "HTTP Status: $http_code\n";
    if ($error) {
        echo "cURL Error: $error\n";
        return ['success' => false, 'error' => 'cURL Error: ' . $error];
    }

    if ($response === false) {
        return ['success' => false, 'error' => 'Failed to get response'];
    }

    echo "Raw Response: $response\n";

    $response_data = json_decode($response, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        return ['success' => false, 'error' => 'Invalid JSON response: ' . json_last_error_msg()];
    }

    if (!is_array($response_data)) {
        return ['success' => false, 'error' => 'Response is not an array'];
    }

    return $response_data;
}

/**
 * Test story generation
 */
function test_story_generation($master_url, $domain) {
    $test_data = [
        'domain' => $domain,
        'prompt_id' => 'test_' . time(),
        'prompt_text' => 'Write a short article about artificial intelligence in healthcare. Include specific examples of how AI is being used to improve patient outcomes.',
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

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $master_url . '/wp-json/exaig/v1/generate-story',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($test_data),
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Accept: application/json'
        ],
        CURLOPT_TIMEOUT => 60,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false
    ]);

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    echo "HTTP Status: $http_code\n";
    if ($error) {
        echo "cURL Error: $error\n";
        return ['success' => false, 'error' => 'cURL Error: ' . $error];
    }

    if ($response === false) {
        return ['success' => false, 'error' => 'Failed to get response'];
    }

    echo "Raw Response: $response\n";

    $response_data = json_decode($response, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        return ['success' => false, 'error' => 'Invalid JSON response: ' . json_last_error_msg()];
    }

    if (!is_array($response_data)) {
        return ['success' => false, 'error' => 'Response is not an array'];
    }

    return $response_data;
}

/**
 * Test local plugin simulation
 */
function test_local_plugin_simulation($master_url, $domain) {
    // Simulate what the local plugin would do
    $test_data = [
        'domain' => $domain,
        'prompt_id' => 'local_test_' . time(),
        'prompt_text' => 'Write a brief article about renewable energy trends in 2024.',
        'settings' => [
            'model' => 'gpt-4-turbo',
            'max_tokens' => 600,
            'system_content' => 'You are a knowledgeable writer specializing in technology and business topics.',
            'timeout' => 30
        ],
        'recent_posts' => [],
        'category' => 'Business',
        'photos' => 1
    ];

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $master_url . '/wp-json/exaig/v1/generate-story',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($test_data),
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Accept: application/json'
        ],
        CURLOPT_TIMEOUT => 60,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false
    ]);

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($response === false) {
        return ['success' => false, 'error' => 'Failed to get response'];
    }

    $response_data = json_decode($response, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        return ['success' => false, 'error' => 'Invalid JSON response'];
    }

    if (!$response_data['success']) {
        return $response_data;
    }

    // Simulate post creation (without actually creating a post)
    $post_data = [
        'post_title' => $response_data['content']['title'],
        'post_content' => $response_data['content']['content'],
        'post_excerpt' => $response_data['content']['excerpt'] ?? '',
        'post_status' => 'draft',
        'post_type' => 'post',
        'meta_input' => [
            'aistma_sources' => $response_data['content']['references'] ?? [],
            'aistma_token_usage' => $response_data['usage']['total_tokens'] ?? 0,
            'aistma_request_id' => $response_data['usage']['request_id'] ?? '',
            'aistma_generation_method' => 'master_api'
        ]
    ];

    return [
        'success' => true,
        'post_id' => 'simulated_' . time(),
        'generation_method' => 'master_api',
        'content' => $response_data['content'],
        'usage' => $response_data['usage']
    ];
} 