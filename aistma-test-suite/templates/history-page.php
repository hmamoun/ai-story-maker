<?php
/**
 * History Page Template
 * 
 * @package AISTMA_Test_Suite
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

$admin_manager = AISTMA_Test_Suite::get_instance()->get_admin_manager();
$filter_options = $admin_manager->get_filter_options();
$export_formats = $admin_manager->get_export_formats();
?>

<div class="wrap">
    <h1><?php _e('Test History', 'aistma-test-suite'); ?></h1>
    
    <!-- Filters -->
    <div class="aistma-history-filters">
        <h2><?php _e('Filters', 'aistma-test-suite'); ?></h2>
        <form id="history-filters-form">
            <div class="filter-row">
                <div class="filter-group">
                    <label for="test-name-filter"><?php _e('Test Name:', 'aistma-test-suite'); ?></label>
                    <select id="test-name-filter" name="test_name">
                        <option value=""><?php _e('All Tests', 'aistma-test-suite'); ?></option>
                        <?php foreach ($filter_options['test_names'] as $test_name): ?>
                            <option value="<?php echo esc_attr($test_name); ?>"><?php echo esc_html($test_name); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label for="category-filter"><?php _e('Category:', 'aistma-test-suite'); ?></label>
                    <select id="category-filter" name="category">
                        <option value=""><?php _e('All Categories', 'aistma-test-suite'); ?></option>
                        <?php foreach ($filter_options['categories'] as $category): ?>
                            <option value="<?php echo esc_attr($category); ?>"><?php echo esc_html($category); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label for="status-filter"><?php _e('Status:', 'aistma-test-suite'); ?></label>
                    <select id="status-filter" name="status">
                        <option value=""><?php _e('All Statuses', 'aistma-test-suite'); ?></option>
                        <?php foreach ($filter_options['statuses'] as $status): ?>
                            <option value="<?php echo esc_attr($status); ?>"><?php echo esc_html(ucfirst($status)); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            
            <div class="filter-row">
                <div class="filter-group">
                    <label for="start-date-filter"><?php _e('Start Date:', 'aistma-test-suite'); ?></label>
                    <input type="date" id="start-date-filter" name="start_date">
                </div>
                
                <div class="filter-group">
                    <label for="end-date-filter"><?php _e('End Date:', 'aistma-test-suite'); ?></label>
                    <input type="date" id="end-date-filter" name="end_date">
                </div>
                
                <div class="filter-group">
                    <label for="date-range-filter"><?php _e('Quick Date Range:', 'aistma-test-suite'); ?></label>
                    <select id="date-range-filter" name="date_range">
                        <option value=""><?php _e('Custom Range', 'aistma-test-suite'); ?></option>
                        <?php foreach ($filter_options['date_ranges'] as $key => $label): ?>
                            <option value="<?php echo esc_attr($key); ?>"><?php echo esc_html($label); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            
            <div class="filter-actions">
                <button type="submit" class="button button-primary"><?php _e('Apply Filters', 'aistma-test-suite'); ?></button>
                <button type="button" class="button button-secondary" id="clear-filters"><?php _e('Clear Filters', 'aistma-test-suite'); ?></button>
            </div>
        </form>
    </div>
    
    <!-- Summary -->
    <div class="aistma-history-summary" style="display: none;">
        <h2><?php _e('Summary', 'aistma-test-suite'); ?></h2>
        <div class="summary-stats">
            <div class="stat-item">
                <span class="stat-label"><?php _e('Total Tests:', 'aistma-test-suite'); ?></span>
                <span class="stat-value" id="history-total-tests">0</span>
            </div>
            <div class="stat-item">
                <span class="stat-label"><?php _e('Passed:', 'aistma-test-suite'); ?></span>
                <span class="stat-value passed" id="history-passed-tests">0</span>
            </div>
            <div class="stat-item">
                <span class="stat-label"><?php _e('Failed:', 'aistma-test-suite'); ?></span>
                <span class="stat-value failed" id="history-failed-tests">0</span>
            </div>
            <div class="stat-item">
                <span class="stat-label"><?php _e('Errors:', 'aistma-test-suite'); ?></span>
                <span class="stat-value error" id="history-error-tests">0</span>
            </div>
            <div class="stat-item">
                <span class="stat-label"><?php _e('Success Rate:', 'aistma-test-suite'); ?></span>
                <span class="stat-value" id="history-success-rate">0%</span>
            </div>
            <div class="stat-item">
                <span class="stat-label"><?php _e('Average Duration:', 'aistma-test-suite'); ?></span>
                <span class="stat-value" id="history-avg-duration">0ms</span>
            </div>
        </div>
    </div>
    
    <!-- Actions -->
    <div class="aistma-history-actions">
        <div class="action-group">
            <label for="export-format"><?php _e('Export Format:', 'aistma-test-suite'); ?></label>
            <select id="export-format" name="export_format">
                <?php foreach ($export_formats as $format => $label): ?>
                    <option value="<?php echo esc_attr($format); ?>"><?php echo esc_html($label); ?></option>
                <?php endforeach; ?>
            </select>
            <button type="button" class="button button-secondary" id="export-history">
                <?php _e('Export History', 'aistma-test-suite'); ?>
            </button>
        </div>
        
        <div class="action-group">
            <button type="button" class="button button-secondary" id="clear-history">
                <?php _e('Clear History', 'aistma-test-suite'); ?>
            </button>
        </div>
    </div>
    
    <!-- History Table -->
    <div class="aistma-history-table">
        <h2><?php _e('Test History', 'aistma-test-suite'); ?></h2>
        
        <div class="table-container">
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php _e('Test Name', 'aistma-test-suite'); ?></th>
                        <th><?php _e('Category', 'aistma-test-suite'); ?></th>
                        <th><?php _e('Status', 'aistma-test-suite'); ?></th>
                        <th><?php _e('Duration', 'aistma-test-suite'); ?></th>
                        <th><?php _e('Created', 'aistma-test-suite'); ?></th>
                        <th><?php _e('Actions', 'aistma-test-suite'); ?></th>
                    </tr>
                </thead>
                <tbody id="history-table-body">
                    <tr>
                        <td colspan="6" class="no-results">
                            <?php _e('No test history found. Run some tests to see results here.', 'aistma-test-suite'); ?>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        
        <!-- Pagination -->
        <div class="aistma-pagination" style="display: none;">
            <div class="pagination-info">
                <span id="pagination-info"></span>
            </div>
            <div class="pagination-links">
                <button type="button" class="button button-small" id="prev-page" disabled>
                    <?php _e('Previous', 'aistma-test-suite'); ?>
                </button>
                <span id="page-numbers"></span>
                <button type="button" class="button button-small" id="next-page" disabled>
                    <?php _e('Next', 'aistma-test-suite'); ?>
                </button>
            </div>
        </div>
    </div>
    
    <!-- Test Details Modal -->
    <div id="test-details-modal" class="aistma-modal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="modal-test-name"></h3>
                <button type="button" class="modal-close">&times;</button>
            </div>
            <div class="modal-body">
                <div class="test-details">
                    <div class="detail-row">
                        <span class="detail-label"><?php _e('Description:', 'aistma-test-suite'); ?></span>
                        <span class="detail-value" id="modal-test-description"></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label"><?php _e('Category:', 'aistma-test-suite'); ?></span>
                        <span class="detail-value" id="modal-test-category"></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label"><?php _e('Status:', 'aistma-test-suite'); ?></span>
                        <span class="detail-value" id="modal-test-status"></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label"><?php _e('Duration:', 'aistma-test-suite'); ?></span>
                        <span class="detail-value" id="modal-test-duration"></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label"><?php _e('Created:', 'aistma-test-suite'); ?></span>
                        <span class="detail-value" id="modal-test-created"></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label"><?php _e('Result:', 'aistma-test-suite'); ?></span>
                        <span class="detail-value" id="modal-test-result"></span>
                    </div>
                </div>
                
                <div class="test-logs">
                    <h4><?php _e('Test Logs:', 'aistma-test-suite'); ?></h4>
                    <pre id="modal-test-logs"></pre>
                </div>
            </div>
        </div>
    </div>
</div> 