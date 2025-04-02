<?php
/**
 * uninstall.php
 * Uninstall script for the AI Story Maker plugin.
 * This script is executed when the plugin is uninstalled.
 * It removes all plugin options and the custom database table.
 * Plugin Name: AI Story Maker
 * Plugin URI: https://github.com/hmamoun/ai-story-maker/wiki
 * Description: AI-powered WordPress plugin that generates engaging stories, articles, and images using Large Language Models.
 * Version: 1.0
 * Author: Hayan Mamoun
 * Author URI: https://exedotcom.ca
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: ai-story-maker
 * Domain Path: /languages
 * Requires PHP: 7.4
 * Requires at least: 5.0
 * Tested up to: 6.7
 */
 if (!defined('ABSPATH')) exit;

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
wp_clear_scheduled_hook( 'ai_story_generator_repeating_event' );