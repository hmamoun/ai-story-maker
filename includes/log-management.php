<?php
/*
This plugin is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.
 */

 function ai_storymaker_create_log_table() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'ai_storymaker_logs';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE IF NOT EXISTS $table_name (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        log_type ENUM('success', 'error' , 'info','message') NOT NULL,
        message TEXT NOT NULL,
        request_id VARCHAR(100) DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) $charset_collate;";


    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

    dbDelta($sql);
}

function ai_storymaker_log($type, $message, $request_id = null) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'ai_storymaker_logs';

    $wpdb->insert($table_name, [
        'log_type'   => $type,
        'message'    => sanitize_text_field($message),
        'request_id' => sanitize_text_field($request_id),
        'created_at' => current_time('mysql'),
    ]);
    error_log('Story maker log: ' . $message);
}

function fn_ai_storymaker_logs_page() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'ai_storymaker_logs';
    $logs = $wpdb->get_results("SELECT * FROM $table_name ORDER BY created_at DESC LIMIT 50");

    echo '<div class="wrap"><h1>AI StoryMaker Logs</h1>';
    echo '<table class="widefat"><thead><tr><th>ID</th><th>Type</th><th>Message</th><th>Request ID</th><th>Timestamp</th></tr></thead><tbody>';

    foreach ($logs as $log) {
        echo '<tr>';
        echo '<td>' . esc_html($log->id) . '</td>';
        echo '<td><strong style="color:' . ($log->log_type === 'error' ? 'red' : 'green') . '">' . esc_html($log->log_type) . '</strong></td>';
        echo '<td>' . esc_html($log->message) . '</td>';
        echo '<td>' . esc_html($log->request_id ?: 'N/A') . '</td>';
        echo '<td>' . esc_html($log->created_at) . '</td>';
        echo '</tr>';
    }

    echo '</tbody></table></div>';
}
// schedule clearning log table
function fn_ai_storymaker_clear_log(){
    $current_time = time();
    $next_scheduled = wp_next_scheduled('sc_ai_storymaker_clear_log');
    if ($next_scheduled < $current_time || !$next_scheduled) {
        $interval = get_option('opt_ai_storymaker_clear_log', 30);
        $interval_seconds = $interval * DAY_IN_SECONDS;
        wp_clear_scheduled_hook('sc_ai_storymaker_clear_log');
        wp_schedule_single_event(time() + $interval_seconds, 'sc_ai_storymaker_clear_log');
        global $wpdb;
        $table_name = $wpdb->prefix . 'ai_storymaker_logs';
        // Cleanup logs older than retention period
        $wpdb->query("DELETE FROM $table_name WHERE created_at < NOW() - INTERVAL $interval DAY");
        ai_storymaker_log('success', 'Log cleaned,next run after : ' . date('Y-m-d H:i:s', time() + $interval_seconds));
    }
}
fn_ai_storymaker_clear_log();


