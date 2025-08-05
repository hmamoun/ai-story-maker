# AI Story Maker Test Suite

A scalable WordPress plugin for testing the AI Story Maker plugin. This plugin provides a comprehensive testing framework that can be installed temporarily for debugging production issues.

## Features

- **Scalable Test Framework**: Add new tests by simply creating PHP files in the `tests/` directory
- **Admin Interface**: Run tests individually or all at once through the WordPress admin
- **Test History**: View and export test history with detailed reports
- **Debug Log Viewer**: Browse and search the WordPress debug.log file
- **Database Storage**: All test results are stored in the database for historical analysis
- **Export Functionality**: Export test results in JSON, CSV, or HTML formats

## Installation

1. Copy the `AISTMA_Test_Suite` folder to your WordPress `wp-content/plugins/` directory
2. Activate the plugin through WordPress admin
3. Navigate to **Tools > AISTMA Tests** to access the test interface

## Usage

### Running Tests

1. **Run All Tests**: Click "Run All Tests" to execute all available tests
2. **Run Individual Tests**: Click "Run" next to any specific test
3. **View Results**: Test results are displayed immediately with detailed logs
4. **Export Results**: Export current test results for analysis

### Adding New Tests

To add a new test, create a PHP file in the `tests/` directory with the following structure:

```php
<?php
class Your_Test_Name extends AISTMA_Test_Base {
    
    protected $test_name = "Your Test Name";
    protected $test_description = "Description of what this test does";
    protected $test_category = "Category Name";
    
    public function run_test() {
        // Your test logic here
        $this->log_info("Starting test");
        
        // Example checks
        $this->check_wordpress_loaded();
        $this->check_aistma_active();
        $this->check_class_exists("exedotcom\\aistorymaker\\AISTMA_Log_Manager");
        
        return "Test completed successfully";
    }
}
```

### Available Helper Methods

The base test class provides many helper methods:

#### WordPress Checks
- `check_wordpress_loaded()` - Check if WordPress is loaded
- `check_aistma_active()` - Check if AI Story Maker plugin is active
- `check_user_capability($capability)` - Check if user has a capability

#### Class and Function Checks
- `check_class_exists($class_name)` - Check if a class exists
- `check_function_exists($function_name)` - Check if a function exists

#### Database Checks
- `check_table_exists($table_name)` - Check if a database table exists
- `check_option_exists($option_name)` - Check if a WordPress option exists

#### File System Checks
- `check_file_exists($file_path)` - Check if a file exists
- `check_directory_exists($dir_path)` - Check if a directory exists

#### HTTP and Performance
- `make_http_request($url, $args)` - Make an HTTP request
- `get_memory_usage()` - Get memory usage information
- `get_db_query_count()` - Get database query count
- `get_load_time()` - Get page load time

#### Logging Methods
- `$this->log_info($message)` - Log info message
- `$this->log_warning($message)` - Log warning message
- `$this->log_error($message)` - Log error message
- `$this->log_debug($message)` - Log debug message

### Test Categories

Organize your tests by category:
- **Verification** - Tests for verification system
- **API** - Tests for API functionality
- **Database** - Tests for database operations
- **Security** - Tests for security features
- **Performance** - Tests for performance metrics
- **General** - General functionality tests

## Admin Pages

### 1. Tests Page (`Tools > AISTMA Tests`)
- Run all tests or individual tests
- View test results and recommendations
- Export test results
- Clear test results

### 2. History Page (`Tools > AISTMA Tests > History`)
- View test history with filtering
- Export history in multiple formats
- Clear test history
- View detailed test information

### 3. Debug Log Page (`Tools > AISTMA Tests > Debug Log`)
- View WordPress debug.log file
- Search and filter log entries
- Download log file
- Clear log file
- View debug configuration

## Database Tables

The plugin creates the following database table:

### `wp_aistma_test_history`
- `id` - Primary key
- `test_name` - Name of the test
- `test_description` - Test description
- `test_category` - Test category
- `test_status` - Test status (passed/failed/error)
- `test_result` - Test result message
- `test_duration` - Test execution time in milliseconds
- `test_logs` - JSON encoded test logs
- `created_at` - Timestamp when test was run

## Export Formats

### JSON Export
Complete test data including metadata, summary, and detailed results.

### CSV Export
Tabular format with test name, description, category, status, result, duration, and timestamp.

### HTML Report
Formatted HTML report with styling, summary statistics, and detailed results table.

## Security Features

- **Nonce Verification**: All AJAX requests are protected with WordPress nonces
- **Capability Checks**: Only users with `manage_options` capability can access the plugin
- **Input Sanitization**: All user inputs are properly sanitized
- **SQL Prepared Statements**: Database queries use prepared statements to prevent SQL injection

## Performance Considerations

- **Test Discovery**: Tests are discovered automatically from the `tests/` directory
- **Caching**: Test results are cached to improve performance
- **Memory Management**: Tests are executed in isolation to prevent memory leaks
- **Timeout Protection**: Long-running tests are automatically terminated

## Troubleshooting

### Common Issues

1. **Tests not appearing**: Ensure test files extend `AISTMA_Test_Base` and are in the `tests/` directory
2. **Permission errors**: Check that the plugin has write permissions to the `tests/` directory
3. **Database errors**: Verify that the database table was created during plugin activation
4. **AJAX errors**: Check browser console for JavaScript errors

### Debug Information

- Check the WordPress debug log for detailed error messages
- Use the Debug Log page to view real-time log entries
- Export test results for offline analysis

## Production Usage

### Best Practices

1. **Temporary Installation**: Install only when needed for debugging
2. **Clean Removal**: Deactivate and delete the plugin after debugging
3. **Backup**: Always backup your database before running tests
4. **Test Environment**: Test the test suite in a staging environment first

### Security Considerations

- The plugin should only be installed on sites you control
- Remove the plugin immediately after debugging
- Never leave the plugin active on production sites
- Consider IP restrictions for admin access

## Development

### Adding New Test Categories

1. Create a new test file in the `tests/` directory
2. Set the `$test_category` property to your new category name
3. The category will automatically appear in the admin interface

### Extending the Base Class

You can extend `AISTMA_Test_Base` to add custom helper methods:

```php
class Custom_Test_Base extends AISTMA_Test_Base {
    
    protected function custom_helper_method() {
        // Your custom logic here
    }
}
```

### Custom Export Formats

To add new export formats, modify the `export_test_history()` method in `class-history-manager.php`.

## Support

For issues or questions:
1. Check the WordPress debug log for error messages
2. Review the test history for failed tests
3. Use the Debug Log page to identify system issues
4. Export test results for detailed analysis

## Changelog

### Version 1.0.0
- Initial release
- Scalable test framework
- Admin interface for test management
- Test history with export functionality
- Debug log viewer
- Database storage for test results

## License

This plugin is licensed under the GPL v2 or later.

---

**Important**: This plugin is designed for temporary debugging use. Always remove it from production sites after debugging is complete. 