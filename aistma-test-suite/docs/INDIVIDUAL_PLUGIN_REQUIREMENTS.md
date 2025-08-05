# Individual Plugin Requirements

## Overview

The AI Story Maker Test Suite now supports individual plugin requirements for each test. This allows tests to specify their own plugin dependencies, making the test suite more flexible and targeted.

## How It Works

### Test-Level Plugin Requirements

Each test can specify its own required plugin using the `$required_plugin` property:

```php
class My_Test extends AISTMA_Test_Base {
    
    protected $test_name = "My Test";
    protected $test_description = "Description of my test";
    protected $test_category = "General";
    
    /**
     * Required plugin for this test (optional)
     */
    protected $required_plugin = "WooCommerce";
    
    public function run_test() {
        // Your test logic here
        return "Test completed successfully";
    }
}
```

### Behavior

1. **When Required Plugin is Active**: Test runs normally
2. **When Required Plugin is Not Active**: 
   - Test appears greyed out in the UI
   - Run button is disabled with tooltip showing required plugin
   - Test is skipped during "Run All Tests"
   - Test shows as "skipped" in results

## UI Changes

### Disabled Test Appearance
- Tests with inactive required plugins appear greyed out
- Disabled badge shows next to test name
- Run button is disabled with helpful tooltip
- Requirement notice shows which plugin is needed

### Test Summary
- Skipped tests are counted separately in summary
- Success rate calculation excludes skipped tests
- Clear visual indication of test status

## Example Tests

### WooCommerce Integration Test
```php
class WooCommerce_Integration_Test extends AISTMA_Test_Base {
    
    protected $test_name = "WooCommerce Integration Test";
    protected $test_description = "Tests WooCommerce integration features";
    protected $test_category = "E-commerce";
    protected $required_plugin = "WooCommerce";
    
    public function run_test() {
        // Test WooCommerce functionality
        $this->check_function_exists('wc_get_product');
        $this->check_class_exists('WC_Product');
        return "WooCommerce integration test passed";
    }
}
```

### Plugin Requirement Test
```php
class Plugin_Requirement_Test extends AISTMA_Test_Base {
    
    protected $test_name = "Plugin Requirement Test";
    protected $test_description = "Verifies plugin requirement functionality";
    protected $test_category = "General";
    protected $required_plugin = "AI Story Maker";
    
    public function run_test() {
        // Test the requirement system itself
        return "Plugin requirement test passed";
    }
}
```

## Implementation Details

### Test Base Class Methods
- `get_required_plugin()` - Returns the required plugin name
- `is_required_plugin_active()` - Checks if the required plugin is active

### Test Runner Changes
- `get_test_info()` - Extracts plugin requirement from test file
- `run_all_tests()` - Skips tests with inactive required plugins
- `run_single_test()` - Checks plugin requirement before execution
- `get_test_summary()` - Includes skipped test count

### CSS Classes
- `.test-item.disabled` - Styles for disabled test items
- `.test-status-badge.disabled` - Styles for disabled badge
- `.test-requirement-notice` - Styles for requirement notice

## Benefits

1. **Granular Control**: Each test can specify its own dependencies
2. **Better UX**: Users see exactly which plugins are needed
3. **Flexible Testing**: Tests can be written for specific plugin integrations
4. **Clear Feedback**: Visual indicators show test availability
5. **Safe Execution**: Prevents errors from missing dependencies

## Usage Guidelines

### When to Use Plugin Requirements
- Tests that depend on specific plugin functionality
- Integration tests for third-party plugins
- Tests that check plugin-specific features
- Tests that require plugin database tables or options

### When Not to Use Plugin Requirements
- General WordPress functionality tests
- Tests that work without any specific plugin
- Core plugin functionality tests (use main plugin requirement instead)

## Migration from Global Plugin Requirement

The global plugin requirement (`$test_plugin_name`) still works for tests that should only run when the main plugin is active. Individual plugin requirements are for tests that need specific additional plugins.

### Example Migration
```php
// Old approach - test only runs when AI Story Maker is active
class Old_Test extends AISTMA_Test_Base {
    // No individual requirement - uses global requirement
}

// New approach - test requires specific plugin
class New_Test extends AISTMA_Test_Base {
    protected $required_plugin = "Specific Plugin";
}
```

## Extra Pro Debugging Tip

To debug plugin detection issues, you can add logging to individual tests:

```php
public function run_test() {
    $this->log_info("Required plugin: " . $this->get_required_plugin());
    $this->log_info("Plugin active: " . ($this->is_required_plugin_active() ? 'true' : 'false'));
    
    // Your test logic here
}
```

This helps identify exactly which plugin is being checked and whether it's being detected correctly. 