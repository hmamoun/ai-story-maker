<?php
/**
 * Log manager for AI Story Maker plugin.
 *
 * @package AI_Story_Maker
 * @author  Hayan Mamoun
 * @license GPLv2 or later
 * @link    https://github.com/hmamoun/ai-story-maker
 * @since   0.1.0
 */

// phpcs:disable WordPress.Files.FileName.NotClassName
// phpcs:disable WordPress.Files.FileName.NotClass
namespace exedotcom\aistorymaker;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Class AISTMA_Log_Manager
 *
 * Handles logging functionality for the AI Story Maker plugin.
 */
class AISTMA_Log_Manager {


	/**
	 * Hook table creation on admin init.
	 *
	 * @return void
	 */
	public function __construct() {
		//add_action( 'admin_init', array( __CLASS__, 'aistma_create_log_table' ) );
		$this->aistma_create_log_table();
		
		// Fix any existing empty log types
		self::aistma_fix_empty_log_types();
	}

	/**
	 * Create the log table if it doesn't exist.
	 *
	 * @return void
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

		include_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}

	/**
	 * Insert a log entry.
	 *
	 * @param  string      $type       The type of log entry.
	 * @param  string      $message    The log message.
	 * @param  string|null $request_id Optional request ID.
	 * @return void
	 */
	public static function log( $type, $message, $request_id = null ) {

		global $wpdb;
		$table = $wpdb->prefix . 'aistma_log_table';
		
		// Replace empty or null type with 'info'
		if ( empty( $type ) || is_null( $type ) || '' === trim( $type ) ) {
			$type = 'info';
		}
		
		// Log table is a custom table.
     // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$wpdb->insert(
			$table,
			array(
				'log_type'   => sanitize_text_field( $type ),
				'message'    => sanitize_text_field( $message ),
				'request_id' => sanitize_text_field( $request_id ),
				'created_at' => current_time( 'mysql' ),
			),
			array( '%s', '%s', '%s', '%s' )
		);
		wp_cache_delete( 'aistma_log_table' );
		wp_cache_delete( 'aistma_log_table_all' );
		wp_cache_delete( 'aistma_log_table_filtered' );
	}

		/**
	 * Render logs table in admin.
	 *
	 * @return void
	 */
	public static function aistma_log_table_render() {
		global $wpdb;
		
		// Check if we should show all logs or only success and error
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Reading filter preference for display only
		$show_all_logs = isset( $_GET['show_all_logs'] ) && '1' === $_GET['show_all_logs'];
		
		// Create cache key based on filter
		$cache_key = $show_all_logs ? 'aistma_log_table_all' : 'aistma_log_table_filtered';
		$logs = wp_cache_get( $cache_key );

		if ( false === $logs ) {
			// Build query based on filter
			$where_clause = $show_all_logs ? '' : "WHERE log_type IN ('success', 'error')";
			$safe_table = esc_sql( $wpdb->prefix . 'aistma_log_table' );
			
			// Log table is a custom table.
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$logs = $wpdb->get_results( "SELECT * FROM `{$safe_table}` {$where_clause} ORDER BY created_at DESC LIMIT 0, 25" );
			wp_cache_set( $cache_key, $logs, '', 300 );
		}

		// Make logs available to the template and handle empty log types
		$logs = is_array( $logs ) ? $logs : array();
		
		// Process logs to replace empty types with 'info'
		foreach ( $logs as $log ) {
			if ( empty( $log->log_type ) || is_null( $log->log_type ) || '' === trim( $log->log_type ) ) {
				$log->log_type = 'info';
			}
		}

		// Include the template.
		include plugin_dir_path( __FILE__ ) . '../admin/templates/log-table-template.php';
	}

	/**
	 * Fix empty log types by setting them to 'info'.
	 *
	 * @return int Number of rows updated
	 */
	public static function aistma_fix_empty_log_types() {
		global $wpdb;
		$table = $wpdb->prefix . 'aistma_log_table';
		$safe_table = esc_sql( $table );
		
		// Update empty, null, or whitespace-only log types to 'info'
		// Log table is a custom table.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$updated = $wpdb->query( 
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name is controlled, not user input
				"UPDATE `{$safe_table}` SET log_type = %s WHERE log_type IS NULL OR log_type = '' OR TRIM(log_type) = ''",
				'info'
			)
		);
		
		// Clear cache after update
		wp_cache_delete( 'aistma_log_table' );
		wp_cache_delete( 'aistma_log_table_all' );
		wp_cache_delete( 'aistma_log_table_filtered' );
		
		return $updated;
	}

	/**
	 * Clear logs manually or via cron.
	 *
	 * @return void
	 */
	public static function aistma_clear_logs() {
		global $wpdb;

		$current_time = time();
		$next_run     = wp_next_scheduled( 'schd_ai_story_maker_clear_log' );

		if ( $next_run < $current_time || ! $next_run || ( isset( $_POST['action'] ) && 'aistma_clear_logs' === $_POST['action'] && check_admin_referer( 'aistma_clear_logs_action', 'aistma_clear_logs_nonce' ) ) ) {
			$interval = (int) get_option( 'aistma_clear_log_cron', 30 );
			wp_clear_scheduled_hook( 'schd_ai_story_maker_clear_log' );
			wp_schedule_single_event( time() + $interval * DAY_IN_SECONDS, 'schd_ai_story_maker_clear_log' );

			if ( isset( $_POST['action'] ) && 'aistma_clear_logs' === $_POST['action'] ) {
				// Log table is a custom table.
                // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
				$wpdb->query( "TRUNCATE TABLE `{$wpdb->prefix}aistma_log_table`" );
			} else {
				$threshold = gmdate( 'Y-m-d H:i:s', strtotime( "-{$interval} days" ) );
				// Log table is a custom table.
             // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
				$wpdb->delete( $wpdb->prefix . 'aistma_log_table', array( 'created_at <' => $threshold ), array( '%s' ) );
			}

			self::log( 'info', 'Logs cleared.' );

			if ( isset( $_POST['redirect_to'] ) ) {
				wp_safe_redirect( esc_url_raw( wp_unslash( $_POST['redirect_to'] ) ) );
				exit;
			}
		}
	}
}

// Schedule hook.
add_action( 'schd_ai_story_maker_clear_log', array( 'exedotcom\aistorymaker\AISTMA_Log_Manager', 'aistma_clear_logs' ) );
