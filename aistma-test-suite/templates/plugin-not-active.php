<?php
/**
 * Plugin Not Active Template
 * 
 * @package AISTMA_Test_Suite
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap">
    <h1><?php _e('AI Story Maker Test Suite', 'aistma-test-suite'); ?></h1>
    
    <div class="notice notice-warning">
        <h3><?php _e('Plugin Not Active', 'aistma-test-suite'); ?></h3>
        <p>
            <strong><?php _e('Required Plugin:', 'aistma-test-suite'); ?></strong>
            <?php echo esc_html($plugin_name); ?>
        </p>
        <p>
            <?php _e('The test suite requires the specified plugin to be active before tests can be displayed and run.', 'aistma-test-suite'); ?>
        </p>
        <p>
            <strong><?php _e('To enable tests:', 'aistma-test-suite'); ?></strong>
            <ol>
                <li><?php _e('Go to', 'aistma-test-suite'); ?> <a href="<?php echo admin_url('plugins.php'); ?>"><?php _e('Plugins', 'aistma-test-suite'); ?></a></li>
                <li><?php _e('Find and activate the plugin:', 'aistma-test-suite'); ?> <strong><?php echo esc_html($plugin_name); ?></strong></li>
                <li><?php _e('Return to this page to view and run tests', 'aistma-test-suite'); ?></li>
            </ol>
        </p>
    </div>
    
    <div class="aistma-test-info">
        <h3><?php _e('About This Test Suite', 'aistma-test-suite'); ?></h3>
        <p>
            <?php _e('This test suite is designed to provide comprehensive testing for the AI Story Maker plugin. It includes:', 'aistma-test-suite'); ?>
        </p>
        <ul>
            <li><?php _e('Plugin functionality tests', 'aistma-test-suite'); ?></li>
            <li><?php _e('Database integrity checks', 'aistma-test-suite'); ?></li>
            <li><?php _e('API endpoint validation', 'aistma-test-suite'); ?></li>
            <li><?php _e('Performance monitoring', 'aistma-test-suite'); ?></li>
            <li><?php _e('Error detection and reporting', 'aistma-test-suite'); ?></li>
        </ul>
        
        <h3><?php _e('Test Categories Available', 'aistma-test-suite'); ?></h3>
        <p><?php _e('Once the plugin is active, you will have access to tests in the following categories:', 'aistma-test-suite'); ?></p>
        <ul>
            <li><strong><?php _e('General:', 'aistma-test-suite'); ?></strong> <?php _e('Basic plugin functionality and WordPress integration', 'aistma-test-suite'); ?></li>
            <li><strong><?php _e('Database:', 'aistma-test-suite'); ?></strong> <?php _e('Database table structure and data integrity', 'aistma-test-suite'); ?></li>
            <li><strong><?php _e('API:', 'aistma-test-suite'); ?></strong> <?php _e('API endpoints and external service integration', 'aistma-test-suite'); ?></li>
            <li><strong><?php _e('Performance:', 'aistma-test-suite'); ?></strong> <?php _e('Memory usage, load times, and optimization', 'aistma-test-suite'); ?></li>
            <li><strong><?php _e('Security:', 'aistma-test-suite'); ?></strong> <?php _e('Permission checks and security validation', 'aistma-test-suite'); ?></li>
        </ul>
        
        <h3><?php _e('Important Notes', 'aistma-test-suite'); ?></h3>
        <div class="notice notice-info">
            <p>
                <strong><?php _e('Production Use:', 'aistma-test-suite'); ?></strong>
                <?php _e('This test suite is designed for debugging production issues. Remember to deactivate and delete it after use.', 'aistma-test-suite'); ?>
            </p>
            <p>
                <strong><?php _e('Test Safety:', 'aistma-test-suite'); ?></strong>
                <?php _e('Tests are designed to be non-destructive and safe to run on production environments.', 'aistma-test-suite'); ?>
            </p>
        </div>
    </div>
</div> 