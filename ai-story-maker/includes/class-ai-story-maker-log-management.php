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
namespace AI_Story_Maker;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Log_Manager
 *
 * Provides methods to manage and validate plugin logs.
 */
class Log_Manager {



	/**
	 * Log_Manager constructor.
	 */
	public function __construct() {
		// add_action( 'admin_menu', [ $this, 'add_logs_page' ] );
		add_action( 'admin_init', [ __CLASS__, 'create_log_table' ] );
	}	

	/**
	 * Creates the log table if it doesn't exist.
	 */
	public static function create_log_table() {
		global $wpdb;
		$table_name      = $wpdb->prefix . 'ai_storymaker_logs';
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE IF NOT EXISTS $table_name (
			id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
			log_type ENUM('success', 'error', 'info', 'message') NOT NULL,
			message TEXT NOT NULL,
			request_id VARCHAR(100) DEFAULT NULL,
			created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
		) $charset_collate;";

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		dbDelta( $sql );
	}

	/**
	 * Logs a message.
	 *
	 * @param string      $type       The type of log (e.g. 'success', 'error').
	 * @param string      $message    The log message.
	 * @param string|null $request_id An optional request ID.
	 */
	public static function log( $type, $message, $request_id = null ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'ai_storymaker_logs';

		$wpdb->insert(
			$table_name,
			[
				'log_type'   => sanitize_text_field( $type ),
				'message'    => sanitize_text_field( $message ),
				'request_id' => sanitize_text_field( $request_id ),
				'created_at' => current_time( 'mysql' ),
			],
			[ '%s', '%s', '%s', '%s' ]
		);

		// Clear cache after inserting a new log.
		wp_cache_delete( 'ai_storymaker_logs' );
	}

	/**
	 * Displays the logs page with caching.
	 */
	public static function display_logs_page() {
		global $wpdb;
		$table_name = esc_sql( $wpdb->prefix . 'ai_storymaker_logs' );

		// Check cache before querying the database.
		$logs = wp_cache_get( 'ai_storymaker_logs' );
		if ( false === $logs ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
			$logs = $wpdb->get_results(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$wpdb->prepare( "SELECT * FROM $table_name ORDER BY created_at DESC LIMIT %d", 50 )
			);
			wp_cache_set( 'ai_storymaker_logs', $logs, '', 300 ); // Cache for 5 minutes.
		}

		echo '<div class="wrap"><h1>AI Story Maker Logs</h1>';
		echo '<table class="widefat"><thead><tr><th>ID</th><th>Type</th><th>Message</th><th>Request ID</th><th>Timestamp</th></tr></thead><tbody>';

		if ( ! empty( $logs ) ) {
			foreach ( $logs as $log ) {
				echo '<tr>';
				echo '<td>' . esc_html( $log->id ) . '</td>';
				echo '<td><strong style="color:' . ( $log->log_type === 'error' ? 'red' : 'green' ) . '">' . esc_html( $log->log_type ) . '</strong></td>';
				echo '<td>' . esc_html( $log->message ) . '</td>';
				echo '<td>' . esc_html( $log->request_id ?: 'N/A' ) . '</td>';
				echo '<td>' . esc_html( $log->created_at ) . '</td>';
				echo '</tr>';
			}
		}

		echo '</tbody></table></div>';
	}

	/**
	 * Scheduled function to clear old logs.
	 */
	public static function clear_logs() {
		global $wpdb;
		$current_time   = time();
		$next_scheduled = wp_next_scheduled( 'schd_ai_story_maker_clear_log' );

		if ( $next_scheduled < $current_time || ! $next_scheduled ) {
			$interval         = intval( get_option( 'opt_ai_storymaker_clear_log', 30 ) );
			$interval_seconds = $interval * DAY_IN_SECONDS;

			// Clear previous schedule and set up the next one.
			wp_clear_scheduled_hook( 'schd_ai_story_maker_clear_log' );
			wp_schedule_single_event( time() + $interval_seconds, 'schd_ai_story_maker_clear_log' );

			$table_name     = esc_sql( $wpdb->prefix . 'ai_storymaker_logs' );
			$date_threshold = gmdate( 'Y-m-d H:i:s', strtotime( "-{$interval} days" ) );

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->delete(
				$table_name,
				[ 'created_at <' => $date_threshold ],
				[ '%s' ]
			);

			// Log the cleanup event.
			self::log( 'success', 'Log cleaned, next run after: ' . gmdate( 'Y-m-d H:i:s', time() + $interval_seconds ) );
		}
	}
}

// Hook the log cleanup to our class method.
add_action( 'schd_ai_story_maker_clear_log', [ 'AI_Story_Maker\Log_Manager', 'clear_logs' ] );
global $ai_story_maker_log_manager;
$ai_story_maker_log_manager = new Log_Manager();
