/**
 * AI Story Maker Test Suite Admin JavaScript
 * 
 * @package AISTMA_Test_Suite
 * @since 1.0.0
 */

jQuery(document).ready(function($) {
    
    // Global variables
    let currentPage = 1;
    let currentFilters = {};
    
    // Initialize based on current page
    const currentPageSlug = window.location.search.match(/page=([^&]+)/);
    if (currentPageSlug) {
        const page = currentPageSlug[1];
        
        switch (page) {
            case 'aistma-test-suite':
                initTestsPage();
                break;
            case 'aistma-test-history':
                initHistoryPage();
                break;
            case 'aistma-debug-log':
                initDebugLogPage();
                break;
        }
    }
    
    /**
     * Initialize Tests Page
     */
    function initTestsPage() {
        // Run all tests
        $('#run-all-tests').on('click', function() {
            runAllTests();
        });
        
        // Run single test
        $(document).on('click', '.run-single-test', function() {
            const testFile = $(this).closest('.test-item').data('test-file');
            runSingleTest(testFile);
        });
        
        // Export results
        $('#export-results').on('click', function() {
            exportResults();
        });
        
        // Clear results
        $('#clear-results').on('click', function() {
            clearResults();
        });
    }
    
    /**
     * Initialize History Page
     */
    function initHistoryPage() {
        // Load initial history
        loadHistory();
        
        // Filter form submission
        $('#history-filters-form').on('submit', function(e) {
            e.preventDefault();
            currentPage = 1;
            loadHistory();
        });
        
        // Clear filters
        $('#clear-filters').on('click', function() {
            $('#history-filters-form')[0].reset();
            currentPage = 1;
            loadHistory();
        });
        
        // Export history
        $('#export-history').on('click', function() {
            exportHistory();
        });
        
        // Clear history
        $('#clear-history').on('click', function() {
            if (confirm(aistma_test_ajax.strings.confirm_clear)) {
                clearHistory();
            }
        });
        
        // Date range change
        $('#date-range-filter').on('change', function() {
            const range = $(this).val();
            if (range) {
                setDateRange(range);
            }
        });
        
        // Modal close
        $(document).on('click', '.modal-close', function() {
            $('#test-details-modal').hide();
        });
        
        // Click outside modal to close
        $(document).on('click', '.aistma-modal', function(e) {
            if (e.target === this) {
                $(this).hide();
            }
        });
    }
    
    /**
     * Initialize Debug Log Page
     */
    function initDebugLogPage() {
        // Load initial log content
        loadDebugLog();
        
        // Refresh log
        $('#refresh-log').on('click', function() {
            loadDebugLog();
        });
        
        // Download log
        $('#download-log').on('click', function() {
            downloadDebugLog();
        });
        
        // Clear log
        $('#clear-log').on('click', function() {
            if (confirm(aistma_test_ajax.strings.confirm_clear_log)) {
                clearDebugLog();
            }
        });
        
        // Search and filter changes
        $('#log-lines, #log-search, #log-filter-level').on('change', function() {
            loadDebugLog();
        });
        
        // Search input with delay
        let searchTimeout;
        $('#log-search').on('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(function() {
                loadDebugLog();
            }, 500);
        });
    }
    
    /**
     * Run all tests
     */
    function runAllTests() {
        const $button = $('#run-all-tests');
        const $progress = $('.aistma-test-progress');
        const $summary = $('.aistma-test-summary');
        const $results = $('.aistma-test-results');
        const $recommendations = $('.aistma-test-recommendations');
        
        // Show progress
        $button.prop('disabled', true).text(aistma_test_ajax.strings.running_tests);
        $progress.show();
        $summary.hide();
        $results.hide();
        $recommendations.hide();
        
        // Animate progress bar
        let progress = 0;
        const progressInterval = setInterval(function() {
            progress += Math.random() * 15;
            if (progress > 90) progress = 90;
            $('.progress-fill').css('width', progress + '%');
        }, 200);
        
        // Make AJAX request
        $.ajax({
            url: aistma_test_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'run_aistma_tests',
                nonce: aistma_test_ajax.nonce
            },
            success: function(response) {
                clearInterval(progressInterval);
                $('.progress-fill').css('width', '100%');
                
                if (response.success) {
                    displayTestResults(response.data);
                } else {
                    showError('Error running tests: ' + response.data);
                }
            },
            error: function() {
                clearInterval(progressInterval);
                showError('Network error occurred while running tests');
            },
            complete: function() {
                $button.prop('disabled', false).text('Run All Tests');
                $progress.hide();
            }
        });
    }
    
    /**
     * Run single test
     */
    function runSingleTest(testFile) {
        const $testItem = $('.test-item[data-test-file="' + testFile + '"]');
        const $result = $testItem.find('.test-result');
        const $button = $testItem.find('.run-single-test');
        
        $button.prop('disabled', true).text('Running...');
        $result.hide();
        
        $.ajax({
            url: aistma_test_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'run_aistma_test',
                nonce: aistma_test_ajax.nonce,
                test_file: testFile
            },
            success: function(response) {
                if (response.success) {
                    displaySingleTestResult($testItem, response.data);
                } else {
                    showError('Error running test: ' + response.data);
                }
            },
            error: function() {
                showError('Network error occurred while running test');
            },
            complete: function() {
                $button.prop('disabled', false).text('Run');
            }
        });
    }
    
    /**
     * Display test results
     */
    function displayTestResults(data) {
        const { results, summary, execution_time } = data;
        
        // Update summary
        $('#total-tests').text(summary.total);
        $('#passed-tests').text(summary.passed);
        $('#failed-tests').text(summary.failed);
        $('#error-tests').text(summary.errors);
        $('#success-rate').text(summary.success_rate + '%');
        $('#execution-time').text(execution_time + 'ms');
        
        // Display results
        const $container = $('#test-results-container');
        $container.empty();
        
        results.forEach(function(result) {
            const statusClass = result.status;
            const statusIcon = getStatusIcon(result.status);
            
            const html = `
                <div class="test-result-item ${statusClass}">
                    <div class="test-result-header">
                        <span class="test-result-name">${result.name}</span>
                        <span class="test-result-status ${statusClass}">${statusIcon} ${result.status}</span>
                    </div>
                    <div class="test-message">${result.result}</div>
                    <div class="test-duration">Duration: ${result.duration}ms</div>
                    ${result.logs && result.logs.length > 0 ? `
                        <div class="test-logs">
                            <h4>Test Logs:</h4>
                            <pre class="log-content">${formatLogs(result.logs)}</pre>
                        </div>
                    ` : ''}
                </div>
            `;
            
            $container.append(html);
        });
        
        // Generate recommendations
        generateRecommendations(summary, results);
        
        // Show sections
        $('.aistma-test-summary').show();
        $('.aistma-test-results').show();
        $('.aistma-test-recommendations').show();
        $('#export-results').show();
    }
    
    /**
     * Display single test result
     */
    function displaySingleTestResult($testItem, result) {
        const $result = $testItem.find('.test-result');
        const statusClass = result.status;
        const statusIcon = getStatusIcon(result.status);
        
        $result.html(`
            <div class="test-status ${statusClass}">${statusIcon} ${result.status}</div>
            <div class="test-message">${result.result}</div>
            <div class="test-duration">Duration: ${result.duration}ms</div>
            ${result.logs && result.logs.length > 0 ? `
                <div class="test-logs">
                    <h4>Test Logs:</h4>
                    <pre class="log-content">${formatLogs(result.logs)}</pre>
                </div>
            ` : ''}
        `).addClass(statusClass).show();
    }
    
    /**
     * Get status icon
     */
    function getStatusIcon(status) {
        switch (status) {
            case 'passed':
                return '✅';
            case 'failed':
                return '❌';
            case 'error':
                return '⚠️';
            default:
                return '⏳';
        }
    }
    
    /**
     * Format logs
     */
    function formatLogs(logs) {
        return logs.map(function(log) {
            return `[${log.timestamp}] [${log.level}] ${log.message}`;
        }).join('\n');
    }
    
    /**
     * Generate recommendations
     */
    function generateRecommendations(summary, results) {
        const $container = $('#recommendations-container');
        $container.empty();
        
        const recommendations = [];
        
        // Overall success rate
        if (summary.success_rate < 50) {
            recommendations.push({
                type: 'error',
                title: 'Critical Issues Detected',
                message: 'More than 50% of tests failed. This indicates serious problems with the AI Story Maker plugin.'
            });
        } else if (summary.success_rate < 80) {
            recommendations.push({
                type: 'warning',
                title: 'Issues Detected',
                message: 'Some tests failed. Review the detailed results and address the issues.'
            });
        } else {
            recommendations.push({
                type: 'info',
                title: 'Good Performance',
                message: 'Most tests passed. The plugin appears to be working correctly.'
            });
        }
        
        // Specific recommendations based on failed tests
        results.forEach(function(result) {
            if (result.status !== 'passed') {
                switch (result.category) {
                    case 'Verification':
                        recommendations.push({
                            type: 'error',
                            title: 'Verification System Issue',
                            message: 'The verification system is not working properly. Check plugin activation and class loading.'
                        });
                        break;
                    case 'API':
                        recommendations.push({
                            type: 'warning',
                            title: 'API Issue',
                            message: 'API connectivity problems. Check API configuration and network connectivity.'
                        });
                        break;
                    case 'Database':
                        recommendations.push({
                            type: 'error',
                            title: 'Database Issue',
                            message: 'Database tables are missing or corrupted. Consider reinstalling the plugin.'
                        });
                        break;
                }
            }
        });
        
        // Display recommendations
        recommendations.forEach(function(rec) {
            const html = `
                <div class="recommendation-item ${rec.type}">
                    <strong>${rec.title}</strong><br>
                    ${rec.message}
                </div>
            `;
            $container.append(html);
        });
    }
    
    /**
     * Load test history
     */
    function loadHistory() {
        const filters = getHistoryFilters();
        
        $.ajax({
            url: aistma_test_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'get_aistma_test_history',
                nonce: aistma_test_ajax.nonce,
                ...filters,
                page: currentPage
            },
            success: function(response) {
                if (response.success) {
                    displayHistory(response.data);
                } else {
                    showError('Error loading history: ' + response.data);
                }
            },
            error: function() {
                showError('Network error occurred while loading history');
            }
        });
    }
    
    /**
     * Get history filters
     */
    function getHistoryFilters() {
        const formData = new FormData($('#history-filters-form')[0]);
        const filters = {};
        
        for (let [key, value] of formData.entries()) {
            if (value) {
                filters[key] = value;
            }
        }
        
        return filters;
    }
    
    /**
     * Display history
     */
    function displayHistory(data) {
        const { history, summary } = data;
        
        // Update summary
        if (summary.total_tests > 0) {
            $('#history-total-tests').text(summary.total_tests);
            $('#history-passed-tests').text(summary.passed_tests);
            $('#history-failed-tests').text(summary.failed_tests);
            $('#history-error-tests').text(summary.error_tests);
            $('#history-success-rate').text(summary.success_rate + '%');
            $('#history-avg-duration').text(Math.round(summary.avg_duration) + 'ms');
            
            $('.aistma-history-summary').show();
        } else {
            $('.aistma-history-summary').hide();
        }
        
        // Display history table
        const $tbody = $('#history-table-body');
        $tbody.empty();
        
        if (history.length === 0) {
            $tbody.html(`
                <tr>
                    <td colspan="6" class="no-results">
                        No test history found matching the current filters.
                    </td>
                </tr>
            `);
            $('.aistma-pagination').hide();
        } else {
            history.forEach(function(record) {
                const statusIcon = getStatusIcon(record.test_status);
                const relativeTime = getRelativeTime(record.created_at);
                
                const html = `
                    <tr>
                        <td>${record.test_name}</td>
                        <td>${record.test_category}</td>
                        <td><span class="status-${record.test_status}">${statusIcon} ${record.test_status}</span></td>
                        <td>${record.test_duration}ms</td>
                        <td>${relativeTime}</td>
                        <td>
                            <button type="button" class="button button-small view-test-details" data-test-id="${record.id}">
                                View Details
                            </button>
                        </td>
                    </tr>
                `;
                
                $tbody.append(html);
            });
            
            // Setup pagination if needed
            setupPagination(summary);
        }
        
        // Setup test details modal
        setupTestDetailsModal();
    }
    
    /**
     * Setup pagination
     */
    function setupPagination(summary) {
        // This would be implemented based on your pagination needs
        $('.aistma-pagination').show();
    }
    
    /**
     * Setup test details modal
     */
    function setupTestDetailsModal() {
        $(document).off('click', '.view-test-details').on('click', '.view-test-details', function() {
            const testId = $(this).data('test-id');
            // Load and display test details in modal
            showTestDetailsModal(testId);
        });
    }
    
    /**
     * Show test details modal
     */
    function showTestDetailsModal(testId) {
        // Clear previous values
        $('#modal-test-name').text('');
        $('#modal-test-description').text('');
        $('#modal-test-category').text('');
        $('#modal-test-status').text('');
        $('#modal-test-duration').text('');
        $('#modal-test-created').text('');
        $('#modal-test-result').text('');
        $('#modal-test-logs').text('');

        $.ajax({
            url: aistma_test_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'get_aistma_test_details',
                nonce: aistma_test_ajax.nonce,
                id: testId
            },
            success: function(response) {
                if (response.success && response.data) {
                    const r = response.data;
                    $('#modal-test-name').text(r.test_name || '');
                    $('#modal-test-description').text(r.test_description || '');
                    $('#modal-test-category').text(r.test_category || '');
                    $('#modal-test-status').text(r.test_status || '');
                    $('#modal-test-duration').text((r.test_duration != null ? r.test_duration : '') + (r.test_duration != null ? 'ms' : ''));
                    $('#modal-test-created').text(r.created_at || '');
                    $('#modal-test-result').text(r.test_result || '');

                    // Logs: may be array or string
                    if (Array.isArray(r.test_logs)) {
                        $('#modal-test-logs').text(formatLogs(r.test_logs));
                    } else if (typeof r.test_logs === 'string') {
                        $('#modal-test-logs').text(r.test_logs);
                    } else {
                        $('#modal-test-logs').text('');
                    }

                    $('#test-details-modal').show();
                } else {
                    showError('Error loading test details: ' + (response.data || 'Unknown error'));
                }
            },
            error: function() {
                showError('Network error occurred while loading test details');
            }
        });
    }
    
    /**
     * Export history
     */
    function exportHistory() {
        const format = $('#export-format').val();
        const filters = getHistoryFilters();
        
        const form = $('<form>', {
            method: 'POST',
            action: aistma_test_ajax.ajax_url,
            target: '_blank'
        });
        
        form.append($('<input>', {
            type: 'hidden',
            name: 'action',
            value: 'export_aistma_test_history'
        }));
        
        form.append($('<input>', {
            type: 'hidden',
            name: 'nonce',
            value: aistma_test_ajax.nonce
        }));
        
        form.append($('<input>', {
            type: 'hidden',
            name: 'format',
            value: format
        }));
        
        Object.keys(filters).forEach(function(key) {
            form.append($('<input>', {
                type: 'hidden',
                name: key,
                value: filters[key]
            }));
        });
        
        $('body').append(form);
        form.submit();
        form.remove();
    }
    
    /**
     * Clear history
     */
    function clearHistory() {
        const filters = getHistoryFilters();
        
        $.ajax({
            url: aistma_test_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'clear_aistma_test_history',
                nonce: aistma_test_ajax.nonce,
                ...filters
            },
            success: function(response) {
                if (response.success) {
                    showSuccess(response.data);
                    loadHistory();
                } else {
                    showError('Error clearing history: ' + response.data);
                }
            },
            error: function() {
                showError('Network error occurred while clearing history');
            }
        });
    }
    
    /**
     * Set date range
     */
    function setDateRange(range) {
        const today = new Date();
        let startDate, endDate;
        
        switch (range) {
            case 'today':
                startDate = endDate = today.toISOString().split('T')[0];
                break;
            case 'yesterday':
                const yesterday = new Date(today);
                yesterday.setDate(yesterday.getDate() - 1);
                startDate = endDate = yesterday.toISOString().split('T')[0];
                break;
            case 'last_7_days':
                const weekAgo = new Date(today);
                weekAgo.setDate(weekAgo.getDate() - 7);
                startDate = weekAgo.toISOString().split('T')[0];
                endDate = today.toISOString().split('T')[0];
                break;
            case 'last_30_days':
                const monthAgo = new Date(today);
                monthAgo.setDate(monthAgo.getDate() - 30);
                startDate = monthAgo.toISOString().split('T')[0];
                endDate = today.toISOString().split('T')[0];
                break;
            case 'this_month':
                startDate = new Date(today.getFullYear(), today.getMonth(), 1).toISOString().split('T')[0];
                endDate = today.toISOString().split('T')[0];
                break;
            case 'last_month':
                const lastMonth = new Date(today.getFullYear(), today.getMonth() - 1, 1);
                startDate = lastMonth.toISOString().split('T')[0];
                endDate = new Date(today.getFullYear(), today.getMonth(), 0).toISOString().split('T')[0];
                break;
        }
        
        $('#start-date-filter').val(startDate);
        $('#end-date-filter').val(endDate);
    }
    
    /**
     * Load debug log
     */
    function loadDebugLog() {
        const lines = $('#log-lines').val();
        const search = $('#log-search').val();
        const filterLevel = $('#log-filter-level').val();
        
        $.ajax({
            url: aistma_test_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'get_aistma_debug_log',
                nonce: aistma_test_ajax.nonce,
                lines: lines,
                search: search,
                filter_level: filterLevel
            },
            success: function(response) {
                if (response.success) {
                    displayDebugLog(response.data);
                } else {
                    showError('Error loading debug log: ' + response.data);
                }
            },
            error: function() {
                showError('Network error occurred while loading debug log');
            }
        });
    }
    
    /**
     * Display debug log
     */
    function displayDebugLog(data) {
        const { log_data, stats } = data;
        
        // Update log info
        $('#log-info-text').text(`Showing last ${log_data.filtered_lines} lines of ${stats.size_formatted}`);
        
        // Update log content
        $('#log-content').text(log_data.content);
        
        // Show/hide sections based on content
        if (stats.aistma_entries > 0) {
            $('.aistma-recent-entries').show();
            // This would load AISTMA entries
        } else {
            $('.aistma-recent-entries').hide();
        }
        
        if (stats.error_count > 0) {
            $('.aistma-error-summary').show();
            // This would load error summary
        } else {
            $('.aistma-error-summary').hide();
        }
    }
    
    /**
     * Download debug log
     */
    function downloadDebugLog() {
        const form = $('<form>', {
            method: 'POST',
            action: aistma_test_ajax.ajax_url,
            target: '_blank'
        });
        
        form.append($('<input>', {
            type: 'hidden',
            name: 'action',
            value: 'download_aistma_debug_log'
        }));
        
        form.append($('<input>', {
            type: 'hidden',
            name: 'nonce',
            value: aistma_test_ajax.nonce
        }));
        
        $('body').append(form);
        form.submit();
        form.remove();
    }
    
    /**
     * Clear debug log
     */
    function clearDebugLog() {
        $.ajax({
            url: aistma_test_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'clear_aistma_debug_log',
                nonce: aistma_test_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    showSuccess(response.data);
                    loadDebugLog();
                } else {
                    showError('Error clearing debug log: ' + response.data);
                }
            },
            error: function() {
                showError('Network error occurred while clearing debug log');
            }
        });
    }
    
    /**
     * Export results
     */
    function exportResults() {
        // This would export current test results
        showSuccess('Export functionality would be implemented here');
    }
    
    /**
     * Clear results
     */
    function clearResults() {
        $('.aistma-test-summary').hide();
        $('.aistma-test-results').hide();
        $('.aistma-test-recommendations').hide();
        $('#export-results').hide();
        $('#test-results-container').empty();
        $('#recommendations-container').empty();
        
        // Clear individual test results
        $('.test-result').hide().removeClass('pass fail error');
    }
    
    /**
     * Get relative time
     */
    function getRelativeTime(timestamp) {
        const now = new Date();
        const time = new Date(timestamp);
        const diff = Math.floor((now - time) / 1000);
        
        if (diff < 60) {
            return diff + ' seconds ago';
        } else if (diff < 3600) {
            return Math.floor(diff / 60) + ' minutes ago';
        } else if (diff < 86400) {
            return Math.floor(diff / 3600) + ' hours ago';
        } else {
            return Math.floor(diff / 86400) + ' days ago';
        }
    }
    
    /**
     * Show success message
     */
    function showSuccess(message) {
        const notice = $(`
            <div class="notice notice-success">
                <p>${message}</p>
            </div>
        `);
        
        $('.wrap h1').after(notice);
        
        setTimeout(function() {
            notice.fadeOut(function() {
                $(this).remove();
            });
        }, 3000);
    }
    
    /**
     * Show error message
     */
    function showError(message) {
        const notice = $(`
            <div class="notice notice-error">
                <p>${message}</p>
            </div>
        `);
        
        $('.wrap h1').after(notice);
        
        setTimeout(function() {
            notice.fadeOut(function() {
                $(this).remove();
            });
        }, 5000);
    }
}); 