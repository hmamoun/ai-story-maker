<?php
/**
 * Debug Log Page Template
 * 
 * @package AISTMA_Test_Suite
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

$admin_manager = AISTMA_Test_Suite::get_instance()->get_admin_manager();
$debug_logger = AISTMA_Test_Suite::get_instance()->get_debug_logger();
$log_filter_levels = $admin_manager->get_log_filter_levels();
?>

<div class="wrap">
    <h1><?php _e('Debug Log Viewer', 'aistma-test-suite'); ?></h1>
    
    <!-- Debug Configuration -->
    <div class="aistma-debug-config">
        <h2><?php _e('Debug Configuration', 'aistma-test-suite'); ?></h2>
        <div class="config-grid">
            <div class="config-item">
                <span class="config-label"><?php _e('WP_DEBUG:', 'aistma-test-suite'); ?></span>
                <span class="config-value <?php echo $debug_config['WP_DEBUG'] ? 'enabled' : 'disabled'; ?>">
                    <?php echo $debug_config['WP_DEBUG'] ? __('Enabled', 'aistma-test-suite') : __('Disabled', 'aistma-test-suite'); ?>
                </span>
            </div>
            <div class="config-item">
                <span class="config-label"><?php _e('WP_DEBUG_LOG:', 'aistma-test-suite'); ?></span>
                <span class="config-value <?php echo $debug_config['WP_DEBUG_LOG'] ? 'enabled' : 'disabled'; ?>">
                    <?php echo $debug_config['WP_DEBUG_LOG'] ? __('Enabled', 'aistma-test-suite') : __('Disabled', 'aistma-test-suite'); ?>
                </span>
            </div>
            <div class="config-item">
                <span class="config-label"><?php _e('WP_DEBUG_DISPLAY:', 'aistma-test-suite'); ?></span>
                <span class="config-value <?php echo $debug_config['WP_DEBUG_DISPLAY'] ? 'enabled' : 'disabled'; ?>">
                    <?php echo $debug_config['WP_DEBUG_DISPLAY'] ? __('Enabled', 'aistma-test-suite') : __('Disabled', 'aistma-test-suite'); ?>
                </span>
            </div>
            <div class="config-item">
                <span class="config-label"><?php _e('SCRIPT_DEBUG:', 'aistma-test-suite'); ?></span>
                <span class="config-value <?php echo $debug_config['SCRIPT_DEBUG'] ? 'enabled' : 'disabled'; ?>">
                    <?php echo $debug_config['SCRIPT_DEBUG'] ? __('Enabled', 'aistma-test-suite') : __('Disabled', 'aistma-test-suite'); ?>
                </span>
            </div>
        </div>
    </div>
    
    <!-- Log File Status -->
    <div class="aistma-log-status">
        <h2><?php _e('Log File Status', 'aistma-test-suite'); ?></h2>
        <div class="status-grid">
            <div class="status-item">
                <span class="status-label"><?php _e('File Exists:', 'aistma-test-suite'); ?></span>
                <span class="status-value <?php echo $log_stats['exists'] ? 'yes' : 'no'; ?>">
                    <?php echo $log_stats['exists'] ? __('Yes', 'aistma-test-suite') : __('No', 'aistma-test-suite'); ?>
                </span>
            </div>
            <div class="status-item">
                <span class="status-label"><?php _e('File Size:', 'aistma-test-suite'); ?></span>
                <span class="status-value"><?php echo esc_html($log_stats['size_formatted']); ?></span>
            </div>
            <div class="status-item">
                <span class="status-label"><?php _e('Last Modified:', 'aistma-test-suite'); ?></span>
                <span class="status-value">
                    <?php echo $log_stats['last_modified'] ? $admin_manager->format_date(date('Y-m-d H:i:s', $log_stats['last_modified'])) : __('N/A', 'aistma-test-suite'); ?>
                </span>
            </div>
            <div class="status-item">
                <span class="status-label"><?php _e('Total Lines:', 'aistma-test-suite'); ?></span>
                <span class="status-value"><?php echo number_format($log_stats['total_lines']); ?></span>
            </div>
            <div class="status-item">
                <span class="status-label"><?php _e('Error Count:', 'aistma-test-suite'); ?></span>
                <span class="status-value error"><?php echo number_format($log_stats['error_count']); ?></span>
            </div>
            <div class="status-item">
                <span class="status-label"><?php _e('Warning Count:', 'aistma-test-suite'); ?></span>
                <span class="status-value warning"><?php echo number_format($log_stats['warning_count']); ?></span>
            </div>
            <div class="status-item">
                <span class="status-label"><?php _e('AISTMA Entries:', 'aistma-test-suite'); ?></span>
                <span class="status-value info"><?php echo number_format($log_stats['aistma_entries']); ?></span>
            </div>
        </div>
    </div>
    
    <!-- Log Controls -->
    <div class="aistma-log-controls">
        <h2><?php _e('Log Controls', 'aistma-test-suite'); ?></h2>
        <div class="control-grid">
            <div class="control-group">
                <label for="log-lines"><?php _e('Number of Lines:', 'aistma-test-suite'); ?></label>
                <select id="log-lines" name="lines">
                    <option value="100">100</option>
                    <option value="500">500</option>
                    <option value="1000" selected>1,000</option>
                    <option value="5000">5,000</option>
                    <option value="10000">10,000</option>
                </select>
            </div>
            
            <div class="control-group">
                <label for="log-search"><?php _e('Search:', 'aistma-test-suite'); ?></label>
                <input type="text" id="log-search" name="search" placeholder="<?php _e('Search log entries...', 'aistma-test-suite'); ?>">
            </div>
            
            <div class="control-group">
                <label for="log-filter-level"><?php _e('Filter Level:', 'aistma-test-suite'); ?></label>
                <select id="log-filter-level" name="filter_level">
                    <?php foreach ($log_filter_levels as $level => $label): ?>
                        <option value="<?php echo esc_attr($level); ?>"><?php echo esc_html($label); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="control-group">
                <button type="button" class="button button-primary" id="refresh-log">
                    <?php _e('Refresh Log', 'aistma-test-suite'); ?>
                </button>
                <button type="button" class="button button-secondary" id="download-log">
                    <?php _e('Download Log', 'aistma-test-suite'); ?>
                </button>
                <button type="button" class="button button-secondary" id="clear-log">
                    <?php _e('Clear Log', 'aistma-test-suite'); ?>
                </button>
            </div>
        </div>
    </div>
    
    <!-- Log Content -->
    <div class="aistma-log-content">
        <h2><?php _e('Log Content', 'aistma-test-suite'); ?></h2>
        
        <?php if (!$log_stats['exists']): ?>
            <div class="notice notice-warning">
                <p><?php _e('Debug log file does not exist. Make sure WP_DEBUG_LOG is enabled in wp-config.php.', 'aistma-test-suite'); ?></p>
            </div>
        <?php else: ?>
            <div class="log-info">
                <span id="log-info-text">
                    <?php printf(__('Showing last %d lines of %s', 'aistma-test-suite'), 1000, $log_stats['size_formatted']); ?>
                </span>
                <span id="log-filter-info" style="display: none;"></span>
            </div>
            
            <div class="log-container">
                <pre id="log-content" class="log-content"><?php _e('Loading log content...', 'aistma-test-suite'); ?></pre>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Recent AISTMA Entries -->
    <div class="aistma-recent-entries" style="display: none;">
        <h2><?php _e('Recent AISTMA Entries', 'aistma-test-suite'); ?></h2>
        <div class="entries-container">
            <pre id="aistma-entries" class="entries-content"></pre>
        </div>
    </div>
    
    <!-- Error Summary -->
    <div class="aistma-error-summary" style="display: none;">
        <h2><?php _e('Recent Errors', 'aistma-test-suite'); ?></h2>
        <div class="errors-container">
            <pre id="error-summary" class="errors-content"></pre>
        </div>
    </div>
    
    <!-- Help Information -->
    <div class="aistma-log-help">
        <h3><?php _e('Debug Log Help', 'aistma-test-suite'); ?></h3>
        <p><?php _e('To enable debug logging, add the following to your wp-config.php file:', 'aistma-test-suite'); ?></p>
        <pre><code>define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);</code></pre>
        
        <h4><?php _e('Log File Location:', 'aistma-test-suite'); ?></h4>
        <p><?php echo esc_html($debug_logger->get_log_file_path()); ?></p>
        
        <h4><?php _e('File Permissions:', 'aistma-test-suite'); ?></h4>
        <p><?php echo esc_html($debug_logger->get_log_file_permissions() ?: __('N/A', 'aistma-test-suite')); ?></p>
        
        <h4><?php _e('File Writable:', 'aistma-test-suite'); ?></h4>
        <p><?php echo $debug_logger->is_log_file_writable() ? __('Yes', 'aistma-test-suite') : __('No', 'aistma-test-suite'); ?></p>
        
        <?php if ($debug_logger->get_log_file_age()): ?>
            <h4><?php _e('File Age:', 'aistma-test-suite'); ?></h4>
            <p><?php echo esc_html($debug_logger->get_log_file_age()); ?></p>
        <?php endif; ?>
    </div>
</div> 