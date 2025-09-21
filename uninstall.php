<?php
/**
 * Fired when the plugin is uninstalled.
 *
 * @link  https://www.aithemes.com
 * @since 1.0.0
 *
 * @package    Ai_Story_Maker
 * @subpackage Ai_Story_Maker/includes
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}


// remove plugin options.
// delete multiple options.
$options = array(
	'aistma_prompts',
	'aistma_clear_log_cron',
	'aistma_openai_api_key',
	'aistma_unsplash_api_key',
	'aistma_unsplash_api_secret',
	'aistma_generate_story_cron',
	'aistma_show_exedotcom_attribution',
	'aistma_widget_activity_days',
	'aistma_widget_recent_posts_limit',
	'aistma_widget_hide_empty_columns',
);
foreach ( $options as $option ) {
	if ( get_option( $option ) ) {
		delete_option( $option );
	}
}
// delete database table.
global $wpdb;

// Drop custom log table.
$log_table = $wpdb->prefix . 'aistma_log_table';
$safe_log_table = esc_sql( $log_table );
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
$wpdb->query( "DROP TABLE IF EXISTS `{$safe_log_table}`" );

// Drop traffic info table if exists.
$traffic_table = $wpdb->prefix . 'aistma_traffic_info';
$safe_traffic_table = esc_sql( $traffic_table );
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
$wpdb->query( "DROP TABLE IF EXISTS `{$safe_traffic_table}`" );
// bmark Schedule on uninstall.
wp_clear_scheduled_hook( 'aistma_generate_story_event' );

/**
 * remove transient
 */
delete_transient( 'aistma_exaig_cached_master_instructions' );

// Remove plugin-related post meta keys.
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
$wpdb->query( "DELETE FROM `{$wpdb->postmeta}` WHERE meta_key IN ('_aistma_generated','_ai_story_maker_sources','ai_story_maker_request_id')" );

