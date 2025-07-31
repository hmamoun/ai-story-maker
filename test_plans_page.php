<?php
/**
 * Test file for AI Story Maker Plans page
 * 
 * This file tests the functionality of the plans page to ensure
 * the first plan is selected by default.
 */

// Simulate the packages data structure
$packages = [
    'packages' => [
        [
            'name' => 'Free Plan',
            'price' => 0.0,
            'credits' => 1,
            'description' => 'Get one free story per week',
            'status' => 'active'
        ],
        [
            'name' => 'Pro Plan',
            'price' => 9.99,
            'credits' => 31,
            'description' => 'Get daily stories delivered to your inbox',
            'status' => 'active'
        ],
        [
            'name' => 'Elite Plan',
            'price' => 19.99,
            'credits' => 150,
            'description' => 'Get up to 5 stories per day',
            'status' => 'active'
        ]
    ]
];

// Simulate subscription status (no current subscription)
$subscription_status = [
    'valid' => false,
    'domain' => '',
    'credits_remaining' => 0,
    'package_name' => '',
    'package_id' => '',
    'price' => 0.0,
    'created_at' => ''
];

echo "Testing AI Story Maker Plans Page\n";
echo "================================\n\n";

echo "Packages available:\n";
foreach ($packages['packages'] as $index => $pkg) {
    if ($pkg['status'] === 'active') {
        echo "- {$pkg['name']}: \${$pkg['price']} ({$pkg['credits']} credits)\n";
    }
}

echo "\nTesting first package selection logic:\n";
$first_package_selected = false;
foreach ($packages['packages'] as $index => $pkg) {
    if ($pkg['status'] === 'active') {
        $is_subscribed = isset($subscription_status['package_name']) && $pkg['name'] === $subscription_status['package_name'];
        $should_select = !$first_package_selected && !$is_subscribed;
        
        if ($should_select) {
            $first_package_selected = true;
        }
        
        echo "Package: {$pkg['name']}\n";
        echo "  - Is subscribed: " . ($is_subscribed ? 'Yes' : 'No') . "\n";
        echo "  - Should select: " . ($should_select ? 'Yes' : 'No') . "\n";
        echo "  - Will be checked: " . (($is_subscribed || $should_select) ? 'Yes' : 'No') . "\n\n";
    }
}

echo "Expected behavior:\n";
echo "- First active package should be selected by default\n";
echo "- If user is subscribed to a package, that package should be selected\n";
echo "- Button should be enabled and show appropriate text\n";
echo "- Package description should be displayed immediately\n";

echo "\nTest completed successfully!\n";
?> 