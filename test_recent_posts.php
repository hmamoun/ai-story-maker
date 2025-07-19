<?php
/**
 * Test script to verify recent posts functionality in story generation.
 * This script tests both local and master API story generation with recent posts.
 */

// Load WordPress
require_once( 'wp-load.php' );

// Test parameters
$test_category = 'News'; // Change this to match your category
$test_prompt = 'Write a news article about technology trends in 2024.';

echo "=== Testing Recent Posts Functionality ===\n\n";

// Test 1: Get recent posts for the category
echo "1. Testing recent posts retrieval for category: $test_category\n";
$story_generator = new \exedotcom\aistorymaker\AISTMA_Story_Generator();
$recent_posts = $story_generator->aistma_get_recent_posts( 10, $test_category );

echo "Found " . count( $recent_posts ) . " recent posts:\n";
foreach ( $recent_posts as $post ) {
    echo "- " . $post['title'] . "\n";
}
echo "\n";

// Test 2: Test master instructions with recent posts
echo "2. Testing master instructions with recent posts\n";
// Use reflection to access private method
$reflection = new ReflectionClass( $story_generator );
$method = $reflection->getMethod( 'aistma_get_master_instructions' );
$method->setAccessible( true );
$master_instructions = $method->invoke( $story_generator, $recent_posts );
echo "Master instructions length: " . strlen( $master_instructions ) . " characters\n";
echo "Instructions include recent posts: " . ( strpos( $master_instructions, 'Exclude references' ) !== false ? 'YES' : 'NO' ) . "\n\n";

// Test 3: Test system content construction
echo "3. Testing system content construction\n";
$default_settings = array(
    'model' => 'gpt-4-turbo',
    'system_content' => 'Write fact-based articles.',
    'timeout' => 30
);

$prompt = array(
    'prompt_id' => 'test_prompt',
    'text' => $test_prompt,
    'category' => $test_category,
    'photos' => 0,
    'auto_publish' => 0
);

$admin_prompt_settings = 'The response must follow JSON structure: {"title": "Title", "content": "Content", "excerpt": "Excerpt", "references": []}';

// Simulate the story generation process
$merged_settings = array_merge( $default_settings, $prompt );
$master_instructions = $method->invoke( $story_generator, $recent_posts );
$merged_settings['system_content'] = $master_instructions . "\n" . $admin_prompt_settings;

echo "System content length: " . strlen( $merged_settings['system_content'] ) . " characters\n";
echo "System content includes recent posts: " . ( strpos( $merged_settings['system_content'], 'Exclude references' ) !== false ? 'YES' : 'NO' ) . "\n\n";

// Test 4: Check if recent posts are being sent to master API
echo "4. Testing master API request data construction\n";
$subscription_info = array(
    'valid' => true,
    'domain' => $_SERVER['HTTP_HOST'] ?? 'localhost'
);

// Simulate the request data that would be sent to master API
$request_data = array(
    'domain' => $subscription_info['domain'],
    'prompt_id' => $prompt['prompt_id'],
    'prompt_text' => $test_prompt,
    'settings' => array(
        'model' => $merged_settings['model'] ?? 'gpt-4-turbo',
        'max_tokens' => 1500,
        'system_content' => $merged_settings['system_content'] ?? '',
        'timeout' => $merged_settings['timeout'] ?? 30,
    ),
    'recent_posts' => $recent_posts,
    'category' => $prompt['category'] ?? '',
    'photos' => $prompt['photos'] ?? 0,
);

echo "Request data includes recent_posts: " . ( isset( $request_data['recent_posts'] ) && ! empty( $request_data['recent_posts'] ) ? 'YES' : 'NO' ) . "\n";
echo "Number of recent posts in request: " . count( $request_data['recent_posts'] ) . "\n";
echo "System content in request includes recent posts: " . ( strpos( $request_data['settings']['system_content'], 'Exclude references' ) !== false ? 'YES' : 'NO' ) . "\n\n";

// Test 5: Verify the recent posts data structure
echo "5. Verifying recent posts data structure\n";
if ( ! empty( $recent_posts ) ) {
    $sample_post = $recent_posts[0];
    echo "Sample post structure:\n";
    echo "- Has 'title': " . ( isset( $sample_post['title'] ) ? 'YES' : 'NO' ) . "\n";
    echo "- Has 'excerpt': " . ( isset( $sample_post['excerpt'] ) ? 'YES' : 'NO' ) . "\n";
    echo "- Title: " . ( $sample_post['title'] ?? 'N/A' ) . "\n";
    echo "- Excerpt length: " . strlen( $sample_post['excerpt'] ?? '' ) . " characters\n";
} else {
    echo "No recent posts found for category: $test_category\n";
}

echo "\n=== Test Complete ===\n";
echo "\nRecommendations:\n";
echo "1. Check your WordPress error logs for recent posts logging\n";
echo "2. Verify that the category '$test_category' exists and has published posts\n";
echo "3. Test story generation to see if recent posts are being included\n";
echo "4. Check the master API logs to see if recent posts are being received\n"; 