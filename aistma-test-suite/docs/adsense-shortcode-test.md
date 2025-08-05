# AdSense Shortcode Test

This test validates the AdSense shortcode functionality in the AI Story Maker plugin.

## Test Overview

The AdSense Shortcode Test (`adsense-shortcode-test.php`) is designed to verify that the `[aistma_adsense]` shortcode works correctly and securely.

## Test Categories

### 1. Shortcode Registration
- Verifies that the `aistma_adsense` shortcode is properly registered
- Checks if the shortcode can be called via `do_shortcode()`

### 2. Basic Functionality
- Tests the default shortcode `[aistma_adsense]`
- Validates that all required AdSense elements are present:
  - Client ID (`ca-pub-6861474761481747`)
  - Slot ID (`8915797913`)
  - AdSense class (`adsbygoogle`)
  - AdSense script URL
  - Required data attributes

### 3. Custom Attributes
- Tests shortcode with custom attributes
- Validates that custom client, slot, format, and style are applied
- Example: `[aistma_adsense client="ca-pub-TEST123" slot="TEST456" format="in-feed"]`

### 4. Security and Escaping
- Tests with potentially malicious input
- Validates that XSS attempts are properly escaped
- Ensures attributes are safely processed

### 5. Output Validation
- Validates HTML structure of generated AdSense code
- Checks for proper script tags and initialization code
- Ensures AdSense compliance

## How to Run the Test

### Via WordPress Admin
1. Install and activate the AI Story Maker Test Suite plugin
2. Go to **AI Story Maker Test Suite** in the admin menu
3. Find "AdSense Shortcode Test" in the Shortcode category
4. Click "Run Test" to execute

### Via AJAX
```javascript
jQuery.post(ajaxurl, {
    action: 'run_aistma_test',
    test_file: 'adsense-shortcode-test.php'
}, function(response) {
    console.log(response);
});
```

### Via PHP
```php
// Load the test
require_once AISTMA_TEST_SUITE_TESTS_DIR . 'adsense-shortcode-test.php';

// Create and run the test
$test = new Test_AdSense_Shortcode();
$result = $test->execute();
echo $result;
```

## Expected Results

### Success Criteria
- ✅ Shortcode is registered and accessible
- ✅ Basic shortcode returns valid AdSense HTML
- ✅ Custom attributes are properly applied
- ✅ Security measures prevent XSS attacks
- ✅ Output follows AdSense requirements

### Failure Indicators
- ❌ Shortcode not registered
- ❌ Empty or malformed output
- ❌ Missing required AdSense elements
- ❌ Security vulnerabilities detected
- ❌ Invalid HTML structure

## Test Dependencies

- **Required Plugin**: AI Story Maker
- **WordPress Functions**: `shortcode_exists()`, `do_shortcode()`
- **AdSense Elements**: Client ID, Slot ID, Script URLs

## Debugging

### Common Issues
1. **Shortcode not registered**: Check if AI Story Maker plugin is active
2. **Empty output**: Verify shortcode function exists and is working
3. **Missing elements**: Check AdSense configuration in shortcode function
4. **Security warnings**: Review escaping implementation

### Debug Logs
The test provides detailed logging:
```php
$this->log_info("Testing shortcode registration");
$this->log_error("Shortcode not found");
$this->log_warning("Security concern detected");
```

## Related Tests

- **Integration Test**: Tests complete plugin functionality
- **Plugin Requirement Test**: Verifies plugin dependencies
- **WooCommerce Integration Test**: Tests e-commerce integration

## Future Enhancements

- **Performance Testing**: Measure shortcode execution time
- **Memory Usage**: Track memory consumption
- **AdSense API Testing**: Validate against Google's AdSense API
- **Mobile Responsiveness**: Test ad display on mobile devices

---

**Extra Pro Debugging Tip**: Use browser developer tools to inspect the rendered AdSense code and verify that all attributes are properly set. You can also use the WordPress debug log to track shortcode execution by adding logging statements to the shortcode function. 