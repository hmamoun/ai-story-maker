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
	'aistma_prompts',
	'aistma_clear_log_cron',
	'aistma_openai_api_key',
    'aistma_unsplash_api_key',
    'aistma_unsplash_api_secret',
    'aistma_generate_story_cron',

);
foreach ($options as $option) {
	if (get_option($option)) delete_option($option);
}
// delete database table
global $wpdb;
$table_name = $wpdb->prefix . 'aistma_log_table';
// safe: removing the table when uninstalling
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery , WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange
$wpdb->query( $wpdb->prepare( "DROP TABLE IF EXISTS `%s`", $table_name ));
// bmark Schedule on uninstall
wp_clear_scheduled_hook( 'aistma_generate_story_event' );