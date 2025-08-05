<?php
/**
 * Debug Logger Class
 * 
 * Handles debug.log file viewing and management.
 * 
 * @package AISTMA_Test_Suite
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * AISTMA_Debug_Logger Class
 */
class AISTMA_Debug_Logger {
    
    /**
     * Debug log file path
     */
    private $debug_log_path;
    
    /**
     * Initialize
     */
    public function init() {
        $this->debug_log_path = WP_CONTENT_DIR . '/debug.log';
        add_action('wp_ajax_get_aistma_debug_log', array($this, 'ajax_get_debug_log'));
        add_action('wp_ajax_clear_aistma_debug_log', array($this, 'ajax_clear_debug_log'));
        add_action('wp_ajax_download_aistma_debug_log', array($this, 'ajax_download_debug_log'));
    }
    
    /**
     * Check if debug log exists
     */
    public function debug_log_exists() {
        return file_exists($this->debug_log_path);
    }
    
    /**
     * Get debug log file size
     */
    public function get_debug_log_size() {
        if (!$this->debug_log_exists()) {
            return 0;
        }
        
        return filesize($this->debug_log_path);
    }
    
    /**
     * Format file size
     */
    public function format_file_size($bytes) {
        $units = array('B', 'KB', 'MB', 'GB');
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        
        return round($bytes / pow(1024, $pow), 2) . ' ' . $units[$pow];
    }
    
    /**
     * Get debug log content
     */
    public function get_debug_log_content($lines = 1000, $search = '', $filter_level = '') {
        if (!$this->debug_log_exists()) {
            return array(
                'content' => '',
                'total_lines' => 0,
                'filtered_lines' => 0,
                'file_size' => 0
            );
        }
        
        $content = file_get_contents($this->debug_log_path);
        $all_lines = explode("\n", $content);
        $total_lines = count($all_lines);
        
        // Filter lines
        $filtered_lines = array();
        foreach ($all_lines as $line) {
            $line = trim($line);
            if (empty($line)) {
                continue;
            }
            
            // Apply search filter
            if (!empty($search) && stripos($line, $search) === false) {
                continue;
            }
            
            // Apply level filter
            if (!empty($filter_level)) {
                $level_patterns = array(
                    'error' => '/\[ERROR\]|PHP Fatal error|PHP Parse error|PHP Warning/i',
                    'warning' => '/\[WARNING\]|PHP Warning/i',
                    'info' => '/\[INFO\]/i',
                    'debug' => '/\[DEBUG\]/i'
                );
                
                if (isset($level_patterns[$filter_level])) {
                    if (!preg_match($level_patterns[$filter_level], $line)) {
                        continue;
                    }
                }
            }
            
            $filtered_lines[] = $line;
        }
        
        // Limit lines
        $filtered_lines = array_slice($filtered_lines, -$lines);
        
        return array(
            'content' => implode("\n", $filtered_lines),
            'total_lines' => $total_lines,
            'filtered_lines' => count($filtered_lines),
            'file_size' => $this->get_debug_log_size()
        );
    }
    
    /**
     * Get debug log statistics
     */
    public function get_debug_log_stats() {
        if (!$this->debug_log_exists()) {
            return array(
                'exists' => false,
                'size' => 0,
                'size_formatted' => '0 B',
                'last_modified' => null,
                'error_count' => 0,
                'warning_count' => 0,
                'aistma_entries' => 0
            );
        }
        
        $content = file_get_contents($this->debug_log_path);
        $lines = explode("\n", $content);
        
        $error_count = 0;
        $warning_count = 0;
        $aistma_entries = 0;
        
        foreach ($lines as $line) {
            if (preg_match('/PHP Fatal error|PHP Parse error|PHP Warning|\[ERROR\]/i', $line)) {
                $error_count++;
            }
            
            if (preg_match('/PHP Warning|\[WARNING\]/i', $line)) {
                $warning_count++;
            }
            
            if (stripos($line, 'AISTMA') !== false) {
                $aistma_entries++;
            }
        }
        
        return array(
            'exists' => true,
            'size' => $this->get_debug_log_size(),
            'size_formatted' => $this->format_file_size($this->get_debug_log_size()),
            'last_modified' => filemtime($this->debug_log_path),
            'error_count' => $error_count,
            'warning_count' => $warning_count,
            'aistma_entries' => $aistma_entries,
            'total_lines' => count($lines)
        );
    }
    
    /**
     * Clear debug log
     */
    public function clear_debug_log() {
        if (!$this->debug_log_exists()) {
            return false;
        }
        
        return file_put_contents($this->debug_log_path, '') !== false;
    }
    
    /**
     * Download debug log
     */
    public function download_debug_log() {
        if (!$this->debug_log_exists()) {
            return false;
        }
        
        $filename = 'debug-log-' . date('Y-m-d-H-i-s') . '.txt';
        
        header('Content-Type: text/plain');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . $this->get_debug_log_size());
        
        readfile($this->debug_log_path);
        exit;
    }
    
    /**
     * Get recent AISTMA entries
     */
    public function get_recent_aistma_entries($limit = 50) {
        if (!$this->debug_log_exists()) {
            return array();
        }
        
        $content = file_get_contents($this->debug_log_path);
        $lines = explode("\n", $content);
        
        $aistma_lines = array();
        foreach ($lines as $line) {
            if (stripos($line, 'AISTMA') !== false) {
                $aistma_lines[] = $line;
            }
        }
        
        return array_slice($aistma_lines, -$limit);
    }
    
    /**
     * Get error summary
     */
    public function get_error_summary() {
        if (!$this->debug_log_exists()) {
            return array();
        }
        
        $content = file_get_contents($this->debug_log_path);
        $lines = explode("\n", $content);
        
        $errors = array();
        foreach ($lines as $line) {
            if (preg_match('/PHP Fatal error|PHP Parse error|PHP Warning|\[ERROR\]/i', $line)) {
                $errors[] = $line;
            }
        }
        
        return array_slice($errors, -20); // Last 20 errors
    }
    
    /**
     * AJAX handler for getting debug log
     */
    public function ajax_get_debug_log() {
        check_ajax_referer('aistma_test_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions', 'aistma-test-suite'));
        }
        
        $lines = intval($_POST['lines'] ?? 1000);
        $search = sanitize_text_field($_POST['search'] ?? '');
        $filter_level = sanitize_text_field($_POST['filter_level'] ?? '');
        
        $log_data = $this->get_debug_log_content($lines, $search, $filter_level);
        $stats = $this->get_debug_log_stats();
        
        wp_send_json_success(array(
            'log_data' => $log_data,
            'stats' => $stats
        ));
    }
    
    /**
     * AJAX handler for clearing debug log
     */
    public function ajax_clear_debug_log() {
        check_ajax_referer('aistma_test_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions', 'aistma-test-suite'));
        }
        
        $success = $this->clear_debug_log();
        
        if ($success) {
            wp_send_json_success(__('Debug log cleared successfully', 'aistma-test-suite'));
        } else {
            wp_send_json_error(__('Failed to clear debug log', 'aistma-test-suite'));
        }
    }
    
    /**
     * AJAX handler for downloading debug log
     */
    public function ajax_download_debug_log() {
        check_ajax_referer('aistma_test_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions', 'aistma-test-suite'));
        }
        
        $this->download_debug_log();
    }
    
    /**
     * Get WordPress debug configuration
     */
    public function get_debug_config() {
        return array(
            'WP_DEBUG' => defined('WP_DEBUG') ? WP_DEBUG : false,
            'WP_DEBUG_LOG' => defined('WP_DEBUG_LOG') ? WP_DEBUG_LOG : false,
            'WP_DEBUG_DISPLAY' => defined('WP_DEBUG_DISPLAY') ? WP_DEBUG_DISPLAY : false,
            'SCRIPT_DEBUG' => defined('SCRIPT_DEBUG') ? SCRIPT_DEBUG : false,
            'error_reporting' => error_reporting(),
            'display_errors' => ini_get('display_errors'),
            'log_errors' => ini_get('log_errors'),
            'error_log' => ini_get('error_log')
        );
    }
    
    /**
     * Check if debug logging is enabled
     */
    public function is_debug_logging_enabled() {
        return defined('WP_DEBUG_LOG') && WP_DEBUG_LOG;
    }
    
    /**
     * Get log file path
     */
    public function get_log_file_path() {
        return $this->debug_log_path;
    }
    
    /**
     * Get log file permissions
     */
    public function get_log_file_permissions() {
        if (!$this->debug_log_exists()) {
            return null;
        }
        
        $perms = fileperms($this->debug_log_path);
        return substr(sprintf('%o', $perms), -4);
    }
    
    /**
     * Check if log file is writable
     */
    public function is_log_file_writable() {
        return $this->debug_log_exists() && is_writable($this->debug_log_path);
    }
    
    /**
     * Get log file age
     */
    public function get_log_file_age() {
        if (!$this->debug_log_exists()) {
            return null;
        }
        
        $modified_time = filemtime($this->debug_log_path);
        $age_seconds = time() - $modified_time;
        
        if ($age_seconds < 60) {
            return $age_seconds . ' seconds';
        } elseif ($age_seconds < 3600) {
            return round($age_seconds / 60) . ' minutes';
        } elseif ($age_seconds < 86400) {
            return round($age_seconds / 3600) . ' hours';
        } else {
            return round($age_seconds / 86400) . ' days';
        }
    }
} 