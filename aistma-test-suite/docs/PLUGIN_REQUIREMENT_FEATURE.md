# Plugin Requirement Feature

## Overview

The AI Story Maker Test Suite now includes a plugin requirement feature that ensures tests are only displayed and executed when the specified plugin is active. This provides better security and prevents confusion when the target plugin is not installed or activated.

## Configuration

### Setting the Required Plugin

In the main test suite class (`aistma-test-suite.php`), you can specify which plugin is required:

```php
/**
 * Test plugin name - tests will only be shown if this plugin is enabled
 */
protected $test_plugin_name = "AI Story Maker";
```

### How It Works

1. **Plugin Detection**: The system checks if the specified plugin is active using multiple methods:
   - Direct plugin file path checking
   - Plugin name matching in the active plugins list
   - Fallback to showing tests if no plugin name is specified

2. **Test Discovery**: When discovering available tests, the system first checks if the required plugin is active before returning any test files.

3. **UI Protection**: The admin interface shows a warning page when the required plugin is not active, with instructions on how to activate it.

4. **AJAX Protection**: All AJAX endpoints check the plugin requirement before executing any tests.

## Behavior

### When Plugin is Active
- Tests are discoverable and displayed in the admin interface
- All test functionality works normally
- AJAX endpoints return test results as expected

### When Plugin is Not Active
- Tests are not discoverable (empty array returned)
- Admin interface shows a warning page with activation instructions
- AJAX endpoints return error messages
- No test execution is allowed

## Files Modified

### Core Files
- `aistma-test-suite.php` - Added plugin requirement property and checking methods
- `includes/class-admin-manager.php` - Modified to check plugin requirement before rendering tests
- `includes/class-test-runner.php` - Modified to check plugin requirement in all methods

### New Files
- `templates/plugin-not-active.php` - Template shown when plugin is not active
- `tests/plugin-requirement-test.php` - Test to verify the requirement functionality

## Methods Added

### AISTMA_Test_Suite Class
- `is_test_plugin_active()` - Checks if the required plugin is active
- `get_test_plugin_name()` - Returns the name of the required plugin

## Usage Examples

### Checking Plugin Status in Tests
```php
class My_Test extends AISTMA_Test_Base {
    
    public function run_test() {
        $test_suite = AISTMA_Test_Suite::get_instance();
        
        if (!$test_suite->is_test_plugin_active()) {
            throw new Exception("Required plugin is not active");
        }
        
        // Your test logic here
        return "Test completed successfully";
    }
}
```

### Customizing the Required Plugin
```php
// In aistma-test-suite.php
protected $test_plugin_name = "My Custom Plugin";
```

## Security Benefits

1. **Prevents Confusion**: Users won't see tests for plugins that aren't installed
2. **Reduces Errors**: Prevents test failures due to missing dependencies
3. **Better UX**: Clear messaging about what's required
4. **Production Safety**: Prevents accidental test execution on systems without the target plugin

## Debugging

### Testing the Feature
Use the included `Plugin_Requirement_Test` to verify the functionality:

1. Activate the AI Story Maker plugin and run the test
2. Deactivate the AI Story Maker plugin and run the test
3. Verify that the test passes in both scenarios

### Common Issues

1. **Plugin Not Detected**: Ensure the plugin name matches exactly
2. **Tests Still Showing**: Check that the plugin requirement check is being called
3. **AJAX Errors**: Verify that the nonce and permissions are correct

## Future Enhancements

- Support for multiple required plugins
- Plugin version requirements
- Conditional test categories based on plugin features
- Automatic plugin activation prompts

## Extra Pro Debugging Tip

When debugging plugin detection issues, you can temporarily add logging to the `is_test_plugin_active()` method:

```php
public function is_test_plugin_active() {
    error_log('Checking plugin: ' . $this->test_plugin_name);
    
    // ... existing code ...
    
    error_log('Plugin active: ' . ($result ? 'true' : 'false'));
    return $result;
}
```

This will help you see exactly what's happening during the plugin detection process. 