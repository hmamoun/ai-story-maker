<?php
/**
 * Test script for verification system fix
 */

// Load WordPress
require_once dirname(__FILE__) . '/WordPress/wp-load.php';

// Test the verification system
echo "<h1>Verification System Test</h1>\n";

// Test 1: Check if classes are loaded
echo "<h2>Test 1: Class Loading</h2>\n";
$classes_to_check = [
    'Exedotcom\AISTMA\Exaig_AISTMA_Subscription_Repository',
    'Exedotcom\AISTMA\Exaig_AISTMA_Credit_Service',
    'Exedotcom\AISTMA\Exaig_AISTMA_Story_Generator',
    'Exedotcom\AISTMA\Exaig_AISTMA_Frontend_Handler',
    'Exedotcom\AISTMA\Exaig_AISTMA_Story_Generation_API',
    'Exedotcom\Exedotcom_Email_Verification'
];

foreach ($classes_to_check as $class) {
    if (class_exists($class)) {
        echo "✅ $class - Loaded successfully<br>\n";
    } else {
        echo "❌ $class - Not found<br>\n";
    }
}

// Test 2: Test verification code generation
echo "<h2>Test 2: Verification Code Generation</h2>\n";
if (class_exists('Exedotcom\Exedotcom_Email_Verification')) {
    $code = \Exedotcom\Exedotcom_Email_Verification::generate_verification_code();
    echo "Generated code: $code<br>\n";
    echo "Code length: " . strlen($code) . " (should be 6)<br>\n";
    echo "Is numeric: " . (is_numeric($code) ? 'Yes' : 'No') . "<br>\n";
} else {
    echo "❌ Email verification class not available<br>\n";
}

// Test 3: Test verification code storage
echo "<h2>Test 3: Verification Code Storage</h2>\n";
if (class_exists('Exedotcom\Exedotcom_Email_Verification')) {
    $test_email = 'test@example.com';
    $test_action = 'subscription';
    $test_code = '123456';
    $test_data = ['package_name' => 'Test Package', 'domain' => 'test.com'];
    
    $stored = \Exedotcom\Exedotcom_Email_Verification::store_verification_code($test_email, $test_action, $test_code, $test_data);
    echo "Code storage result: " . ($stored ? 'Success' : 'Failed') . "<br>\n";
    
    // Test verification
    $verified = \Exedotcom\Exedotcom_Email_Verification::verify_code($test_email, $test_action, $test_code);
    echo "Code verification result: " . ($verified ? 'Success' : 'Failed') . "<br>\n";
} else {
    echo "❌ Email verification class not available<br>\n";
}

// Test 4: Check for any PHP errors
echo "<h2>Test 4: Error Check</h2>\n";
$error_log = error_get_last();
if ($error_log) {
    echo "❌ Last error: " . $error_log['message'] . "<br>\n";
} else {
    echo "✅ No PHP errors detected<br>\n";
}

echo "<h2>Test Complete</h2>\n";
echo "If all tests pass, the verification system should be working correctly.<br>\n";
echo "Check the WordPress debug log for any additional error messages.<br>\n";
?> 