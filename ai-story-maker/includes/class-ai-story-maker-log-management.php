<?php
/*

Plugin URI: https://github.com/hmamoun/ai-story-maker/wiki
Description: AI-powered content generator for WordPress — create engaging stories with a single click.
Version: 0.1.0
Author: Hayan Mamoun
Author URI: https://exedotcom.ca
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Text Domain: ai-story-maker
Domain Path: /languages
Requires PHP: 7.4
Requires at least: 5.8
Tested up to: 6.7
*/

namespace exedotcom\aistorymaker;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Log_Manager
 *
 * Provides methods to manage and validate plugin logs.
 */
class AISTMA_Log_Manager {



	/**
	 * Log_Manager constructor.
	 */
	public function __construct() {
		// add_action( 'admin_menu', [ $this, 'add_logs_page' ] );
		add_action( 'admin_init', [ __CLASS__, 'aistma_create_log_table' ] );
	}	

	/**
	 * Creates the log table if it doesn't exist.
	 */
	public static function aistma_create_log_table() {
		global $wpdb;
		$table_name      = $wpdb->prefix . 'aistma_log_table';
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
	 * 
	 * @return void
	 * the function has a short name not to overload the code
	 */
	public static function log( $type, $message, $request_id = null ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'aistma_log_table';
		// safe: log goes to a custom table
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
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
		wp_cache_delete( 'aistma_log_table' );
	}

	/**
	 * Displays the logs page with caching.
	 * 
	 * @return void
	 * 
	 */
	public static function aistma_log_table_render() {
		global $wpdb;
	
		$table_name = esc_sql( $wpdb->prefix . 'aistma_log_table' );
		$logs       = wp_cache_get( 'aistma_log_table' );
	
		if ( false === $logs ) {
			// Table name is hardcoded internally and safely validated — this is safe.
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			$logs = $wpdb->get_results(
				// safe: log table is a custom table
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared 
				"SELECT * FROM {$table_name} ORDER BY created_at DESC LIMIT 0, 25"
			);
			wp_cache_set( 'aistma_log_table', $logs, '', 300 );
		}
	
		// Render UI
		echo '<div class="wrap"><div class="aistma-style-settings">';
		echo '<h2>' . esc_html__( 'AI Story Maker Logs', 'ai-story-maker' ) . '</h2>';
	
		// Clear Logs Button
		echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" style="margin-bottom: 20px;">';
		wp_nonce_field( 'aistma_clear_logs_action', 'aistma_clear_logs_nonce' );
		echo '<input type="hidden" name="redirect_to" value="' .  esc_url( admin_url( 'admin.php?page=aistma-settings&tab=log' ) ) . '">';
		echo '<input type="hidden" name="action" value="aistma_clear_logs">';
		echo '<input type="submit" class="button button-secondary" value="' . esc_attr__( 'Clear Logs', 'ai-story-maker' ) . '">';
		echo '</form>';
	
		// Logs Table
		echo '<table class="widefat"><thead><tr>
				<th>' . esc_html__( 'ID', 'ai-story-maker' ) . '</th>
				<th>' . esc_html__( 'Type', 'ai-story-maker' ) . '</th>
				<th>' . esc_html__( 'Message', 'ai-story-maker' ) . '</th>
				<th>' . esc_html__( 'Request ID', 'ai-story-maker' ) . '</th>
				<th>' . esc_html__( 'Timestamp', 'ai-story-maker' ) . '</th>
			</tr></thead><tbody>';
	
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
	
		echo '</tbody></table></div></div>';
	}
	

	/**
	 * Scheduled function to clear old logs.
	 * 
	 * @return void
	 */
	public static function aistma_clear_logs() {
		global $wpdb;
		$current_time   = time();
		$next_scheduled = wp_next_scheduled( 'schd_ai_story_maker_clear_log' );

		if ( $next_scheduled < $current_time || ! $next_scheduled || ( isset( $_POST['action'] ) && 'aistma_clear_logs' === $_POST['action'] 
		// verify nonce
		&& check_admin_referer( 'aistma_clear_logs_action', 'aistma_clear_logs_nonce' )

		) ) {
			
			$interval         = intval( get_option( 'aistma_clear_log_cron', 30 ) );
			$interval_seconds = $interval * DAY_IN_SECONDS;

			// Clear previous schedule and set up the next one.
			wp_clear_scheduled_hook( 'schd_ai_story_maker_clear_log' );
			wp_schedule_single_event( time() + $interval_seconds, 'schd_ai_story_maker_clear_log' );

			$table_name     = esc_sql( $wpdb->prefix . 'aistma_log_table' );

			// if the function is called from the admin page, we need to clear the logs
			if ( isset( $_POST['action'] ) && 'aistma_clear_logs' === $_POST['action'] ) {
				// safe : log table is a custom table
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$result =$wpdb->query( "TRUNCATE TABLE {$table_name}" );
			}
			else {
				$date_threshold = gmdate( 'Y-m-d H:i:s', strtotime( "-{$interval} days" ) );
				// safe : log table is a custom table
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$result = $wpdb->delete(
					$table_name,
					[ 'created_at <' => $date_threshold ],
					[ '%s' ]
				);

			}
			if ( $result ) {
				// Log the cleanup event.
				self::log( 'success', 'Log cleaned, next run after: ' . gmdate( 'Y-m-d H:i:s', time() + $interval_seconds ) );
			} else {
				// Log the error event.
				self::log( 'error', 'Failed to clean logs.' );
			}

			if (isset(  $_POST['redirect_to'])) {
				$redirect_url = esc_url_raw( wp_unslash($_POST['redirect_to']) );
				wp_redirect( $redirect_url );
				exit;
			} 


		}
	}
}

// Hook the log cleanup to our class method.
add_action( 'schd_ai_story_maker_clear_log', [ 'exedotcom\aistorymaker\AISTMA_Log_Manager', 'aistma_clear_logs' ] );

