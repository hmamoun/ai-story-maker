<?php
/**
 * Test script for Master Server API Endpoint
 * 
 * This script tests the story generation endpoint on the master server.
 * Run this from the command line: php test_master_api.php
 */

// Configuration
$master_url = 'http://localhost:8080'; // Update this to your master server URL
$test_domain = 'test-domain.com';
$test_prompt = 'Write a short article about artificial intelligence in healthcare.';

// Test data
$test_data = [
    'domain' => $test_domain,
    'prompt_id' => 'test_' . time(),
    'prompt_text' => $test_prompt,
    'settings' => [
        'model' => 'gpt-4-turbo',
        'max_tokens' => 500,
        'system_content' => 'You are a helpful assistant that writes engaging articles.',
        'timeout' => 30
    ],
    'recent_posts' => [],
    'category' => 'Technology',
    'photos' => 2
];

echo "Testing Master Server API Endpoint\n";
echo "==================================\n";
echo "Master URL: $master_url\n";
echo "Test Domain: $test_domain\n";
echo "Prompt: $test_prompt\n\n";

// Make the API call
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

echo "HTTP Status Code: $http_code\n";

if ($error) {
    echo "cURL Error: $error\n";
    exit(1);
}

if ($response === false) {
    echo "Failed to get response\n";
    exit(1);
}

$response_data = json_decode($response, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    echo "Failed to parse JSON response: " . json_last_error_msg() . "\n";
    echo "Raw response: $response\n";
    exit(1);
}

echo "\nResponse:\n";
echo "=========\n";
echo json_encode($response_data, JSON_PRETTY_PRINT) . "\n";

if (isset($response_data['success']) && $response_data['success']) {
    echo "\n✅ SUCCESS: Story generated successfully!\n";
    if (isset($response_data['content']['title'])) {
        echo "Title: " . $response_data['content']['title'] . "\n";
    }
    if (isset($response_data['usage']['total_tokens'])) {
        echo "Tokens used: " . $response_data['usage']['total_tokens'] . "\n";
    }
} else {
    echo "\n❌ FAILED: " . ($response_data['error'] ?? 'Unknown error') . "\n";
    exit(1);
}

echo "\nTest completed successfully!\n"; 