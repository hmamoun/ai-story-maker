<?php
/**
 * Tests Page Template
 * 
 * @package AISTMA_Test_Suite
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

$admin_manager = AISTMA_Test_Suite::get_instance()->get_admin_manager();
$categories = $admin_manager->get_test_categories();
?>

<div class="wrap">
    <h1><?php _e('AI Story Maker Test Suite', 'aistma-test-suite'); ?></h1>
    
    <div class="notice notice-info">
        <p>
            <strong><?php _e('Purpose:', 'aistma-test-suite'); ?></strong>
            <?php _e('This plugin provides comprehensive testing for the AI Story Maker plugin. Use it to diagnose issues in production environments.', 'aistma-test-suite'); ?>
        </p>
        <p>
            <strong><?php _e('Required Plugin:', 'aistma-test-suite'); ?></strong>
            <?php 
            $test_suite = AISTMA_Test_Suite::get_instance();
            echo esc_html($test_suite->get_test_plugin_name()); 
            ?>
            <span class="dashicons dashicons-yes-alt" style="color: #28a745;"></span>
        </p>
        <p>
            <strong><?php _e('Important:', 'aistma-test-suite'); ?></strong>
            <?php _e('Remember to deactivate and delete this plugin after debugging to keep your production environment clean.', 'aistma-test-suite'); ?>
        </p>
    </div>
    
    <!-- Test Controls -->
    <div class="aistma-test-controls">
        <button type="button" class="button button-primary" id="run-all-tests">
            <?php _e('Run All Tests', 'aistma-test-suite'); ?>
        </button>
        <button type="button" class="button button-secondary" id="run-category-tests" style="display: none;">
            <?php _e('Run Category Tests', 'aistma-test-suite'); ?>
        </button>
        <button type="button" class="button button-secondary" id="export-results" style="display: none;">
            <?php _e('Export Results', 'aistma-test-suite'); ?>
        </button>
        <button type="button" class="button button-secondary" id="clear-results">
            <?php _e('Clear Results', 'aistma-test-suite'); ?>
        </button>
    </div>
    
    <!-- Progress Bar -->
    <div class="aistma-test-progress" style="display: none;">
        <div class="progress-bar">
            <div class="progress-fill"></div>
        </div>
        <p class="progress-text"><?php _e('Running tests...', 'aistma-test-suite'); ?></p>
    </div>
    
    <!-- Test Summary -->
    <div class="aistma-test-summary" style="display: none;">
        <h2><?php _e('Test Summary', 'aistma-test-suite'); ?></h2>
        <div class="summary-stats">
            <div class="stat-item">
                <span class="stat-label"><?php _e('Total Tests:', 'aistma-test-suite'); ?></span>
                <span class="stat-value" id="total-tests">0</span>
            </div>
            <div class="stat-item">
                <span class="stat-label"><?php _e('Passed:', 'aistma-test-suite'); ?></span>
                <span class="stat-value passed" id="passed-tests">0</span>
            </div>
            <div class="stat-item">
                <span class="stat-label"><?php _e('Failed:', 'aistma-test-suite'); ?></span>
                <span class="stat-value failed" id="failed-tests">0</span>
            </div>
            <div class="stat-item">
                <span class="stat-label"><?php _e('Errors:', 'aistma-test-suite'); ?></span>
                <span class="stat-value error" id="error-tests">0</span>
            </div>
            <div class="stat-item">
                <span class="stat-label"><?php _e('Skipped:', 'aistma-test-suite'); ?></span>
                <span class="stat-value skipped" id="skipped-tests">0</span>
            </div>
            <div class="stat-item">
                <span class="stat-label"><?php _e('Success Rate:', 'aistma-test-suite'); ?></span>
                <span class="stat-value" id="success-rate">0%</span>
            </div>
            <div class="stat-item">
                <span class="stat-label"><?php _e('Execution Time:', 'aistma-test-suite'); ?></span>
                <span class="stat-value" id="execution-time">0ms</span>
            </div>
        </div>
    </div>
    
    <!-- Available Tests -->
    <div class="aistma-available-tests">
        <h2><?php _e('Available Tests', 'aistma-test-suite'); ?></h2>
        
        <?php if (empty($categories)): ?>
            <div class="notice notice-warning">
                <p><?php _e('No test files found. Please add test files to the tests/ directory.', 'aistma-test-suite'); ?></p>
            </div>
        <?php else: ?>
            <?php foreach ($categories as $category => $tests): ?>
                <div class="test-category">
                    <h3><?php echo esc_html($category); ?> (<?php echo count($tests); ?> tests)</h3>
                    <div class="test-list">
                        <?php foreach ($tests as $test): ?>
                            <?php 
                            $is_disabled = !empty($test['required_plugin']) && !$test['plugin_active'];
                            $test_class = $is_disabled ? 'test-item disabled' : 'test-item';
                            ?>
                            <div class="<?php echo esc_attr($test_class); ?>" data-test-file="<?php echo esc_attr($test['file']); ?>">
                                <div class="test-header">
                                    <span class="test-name">
                                        <?php echo esc_html($test['name']); ?>
                                        <?php if ($is_disabled): ?>
                                            <span class="test-status-badge disabled">
                                                <?php _e('Disabled', 'aistma-test-suite'); ?>
                                            </span>
                                        <?php endif; ?>
                                    </span>
                                    <div class="test-actions">
                                        <?php if ($is_disabled): ?>
                                            <button type="button" class="button button-small" disabled title="<?php echo esc_attr(sprintf(__('Requires plugin: %s', 'aistma-test-suite'), $test['required_plugin'])); ?>">
                                                <?php _e('Run', 'aistma-test-suite'); ?>
                                            </button>
                                        <?php else: ?>
                                            <button type="button" class="button button-small run-single-test">
                                                <?php _e('Run', 'aistma-test-suite'); ?>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="test-description">
                                    <?php echo esc_html($test['description']); ?>
                                    <?php if ($is_disabled): ?>
                                        <div class="test-requirement-notice">
                                            <strong><?php _e('Required Plugin:', 'aistma-test-suite'); ?></strong>
                                            <?php echo esc_html($test['required_plugin']); ?>
                                            <span class="dashicons dashicons-warning" style="color: #ffc107;"></span>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="test-result" style="display: none;">
                                    <div class="test-status"></div>
                                    <div class="test-message"></div>
                                    <div class="test-duration"></div>
                                    <div class="test-logs" style="display: none;">
                                        <h4><?php _e('Test Logs:', 'aistma-test-suite'); ?></h4>
                                        <pre class="log-content"></pre>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    
    <!-- Test Results -->
    <div class="aistma-test-results" style="display: none;">
        <h2><?php _e('Detailed Results', 'aistma-test-suite'); ?></h2>
        <div id="test-results-container"></div>
    </div>
    
    <!-- Recommendations -->
    <div class="aistma-test-recommendations" style="display: none;">
        <h2><?php _e('Recommendations', 'aistma-test-suite'); ?></h2>
        <div id="recommendations-container"></div>
    </div>
    
    <!-- Test Information -->
    <div class="aistma-test-info">
        <h3><?php _e('How to Add Tests', 'aistma-test-suite'); ?></h3>
        <p><?php _e('To add a new test, create a PHP file in the tests/ directory with the following structure:', 'aistma-test-suite'); ?></p>
        <pre><code>&lt;?php
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
}</code></pre>
        
        <h3><?php _e('Available Helper Methods', 'aistma-test-suite'); ?></h3>
        <ul>
            <li><code>check_wordpress_loaded()</code> - <?php _e('Check if WordPress is loaded', 'aistma-test-suite'); ?></li>
            <li><code>check_aistma_active()</code> - <?php _e('Check if AI Story Maker plugin is active', 'aistma-test-suite'); ?></li>
            <li><code>check_class_exists($class_name)</code> - <?php _e('Check if a class exists', 'aistma-test-suite'); ?></li>
            <li><code>check_function_exists($function_name)</code> - <?php _e('Check if a function exists', 'aistma-test-suite'); ?></li>
            <li><code>check_table_exists($table_name)</code> - <?php _e('Check if a database table exists', 'aistma-test-suite'); ?></li>
            <li><code>check_option_exists($option_name)</code> - <?php _e('Check if a WordPress option exists', 'aistma-test-suite'); ?></li>
            <li><code>check_file_exists($file_path)</code> - <?php _e('Check if a file exists', 'aistma-test-suite'); ?></li>
            <li><code>check_directory_exists($dir_path)</code> - <?php _e('Check if a directory exists', 'aistma-test-suite'); ?></li>
            <li><code>check_user_capability($capability)</code> - <?php _e('Check if user has a capability', 'aistma-test-suite'); ?></li>
            <li><code>make_http_request($url, $args)</code> - <?php _e('Make an HTTP request', 'aistma-test-suite'); ?></li>
            <li><code>get_memory_usage()</code> - <?php _e('Get memory usage information', 'aistma-test-suite'); ?></li>
            <li><code>get_db_query_count()</code> - <?php _e('Get database query count', 'aistma-test-suite'); ?></li>
            <li><code>get_load_time()</code> - <?php _e('Get page load time', 'aistma-test-suite'); ?></li>
        </ul>
        
        <h3><?php _e('Logging Methods', 'aistma-test-suite'); ?></h3>
        <ul>
            <li><code>$this->log_info($message)</code> - <?php _e('Log info message', 'aistma-test-suite'); ?></li>
            <li><code>$this->log_warning($message)</code> - <?php _e('Log warning message', 'aistma-test-suite'); ?></li>
            <li><code>$this->log_error($message)</code> - <?php _e('Log error message', 'aistma-test-suite'); ?></li>
            <li><code>$this->log_debug($message)</code> - <?php _e('Log debug message', 'aistma-test-suite'); ?></li>
        </ul>
    </div>
</div> 