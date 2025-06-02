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
		add_action( 'admin_init', array( __CLASS__, 'aistma_create_log_table' ) );
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
	}

	/**
	 * Render logs table in admin.
	 *
	 * @return void
	 */
	public static function aistma_log_table_render() {
		global $wpdb;
		$logs = wp_cache_get( 'aistma_log_table' );

		if ( false === $logs ) {
			// Log table is a custom table.
         // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			$logs = $wpdb->get_results( "SELECT * FROM `{$wpdb->prefix}aistma_log_table` ORDER BY created_at DESC LIMIT 0, 25" );
			wp_cache_set( 'aistma_log_table', $logs, '', 300 );
		}

		// Make logs available to the template.
		$logs = is_array( $logs ) ? $logs : array();

		// Include the template.
		include plugin_dir_path( __FILE__ ) . '../admin/templates/log-table-template.php';
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
