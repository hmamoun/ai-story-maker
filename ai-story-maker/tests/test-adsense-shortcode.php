<?php
/**
 * Test AdSense Shortcode
 *
 * This file tests the AdSense shortcode functionality
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Test if shortcode is registered
if ( shortcode_exists( 'aistma_adsense' ) ) {
	echo "✅ Shortcode 'aistma_adsense' is registered successfully!\n";
} else {
	echo "❌ Shortcode 'aistma_adsense' is NOT registered!\n";
}

// Test basic shortcode functionality
$test_shortcode = '[aistma_adsense]';
$result = do_shortcode( $test_shortcode );

echo "Testing basic shortcode: " . $test_shortcode . "\n";
echo "Result length: " . strlen( $result ) . " characters\n";

// Check if result contains expected AdSense elements
if ( strpos( $result, 'ca-pub-6861474761481747' ) !== false ) {
	echo "✅ AdSense client ID found in output!\n";
} else {
	echo "❌ AdSense client ID NOT found in output!\n";
}

if ( strpos( $result, '8915797913' ) !== false ) {
	echo "✅ AdSense slot ID found in output!\n";
} else {
	echo "❌ AdSense slot ID NOT found in output!\n";
}

if ( strpos( $result, 'adsbygoogle' ) !== false ) {
	echo "✅ AdSense class found in output!\n";
} else {
	echo "❌ AdSense class NOT found in output!\n";
}

// Test shortcode with custom attributes
$test_custom_shortcode = '[aistma_adsense client="ca-pub-TEST123" slot="TEST456" format="in-feed" style="display:block; margin: 10px;"]';
$custom_result = do_shortcode( $test_custom_shortcode );

echo "\nTesting custom shortcode: " . $test_custom_shortcode . "\n";
echo "Custom result length: " . strlen( $custom_result ) . " characters\n";

// Check if custom attributes are applied
if ( strpos( $custom_result, 'ca-pub-TEST123' ) !== false ) {
	echo "✅ Custom client ID applied correctly!\n";
} else {
	echo "❌ Custom client ID NOT applied!\n";
}

if ( strpos( $custom_result, 'TEST456' ) !== false ) {
	echo "✅ Custom slot ID applied correctly!\n";
} else {
	echo "❌ Custom slot ID NOT applied!\n";
}

if ( strpos( $custom_result, 'in-feed' ) !== false ) {
	echo "✅ Custom format applied correctly!\n";
} else {
	echo "❌ Custom format NOT applied!\n";
}

// Test security - check for proper escaping
if ( strpos( $custom_result, 'esc_attr' ) === false && strpos( $custom_result, '&quot;' ) === false ) {
	echo "✅ Output appears to be properly escaped!\n";
} else {
	echo "⚠️  Output may not be properly escaped!\n";
}

echo "\nAdSense shortcode test completed!\n";

// Display sample output (first 200 characters)
echo "\nSample output (first 200 chars):\n";
echo substr( $result, 0, 200 ) . "...\n"; 