<?php
/*
Plugin Name: AI Story Maker
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