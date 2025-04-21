<?php
/**
 * uninstall.php
 * Uninstall script for the AI Story Maker plugin.
 * This script is executed when the plugin is uninstalled.
 * It removes all plugin options and the custom database table.
 */
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

 // remove plugin options
// delete multiple options
$options = array(
	'ai_story_prompts',
	'opt_ai_storymaker_clear_log',
	'openai_api_key',
    'unsplash_api_key',
    'unsplash_api_secret',
    'opt_ai_story_repeat_interval_days',

);
foreach ($options as $option) {
	if (get_option($option)) delete_option($option);
}
// delete database table
global $wpdb;
$table_name = $wpdb->prefix . 'ai_storymaker_logs';
// safe: removing the table when uninstalling
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery , WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange
$wpdb->query( $wpdb->prepare( "DROP TABLE IF EXISTS `%s`", $table_name ));
// bmark Schedule on uninstall
wp_clear_scheduled_hook( 'aistima_generate_story_event' );