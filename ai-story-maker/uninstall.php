<?php
/*
 * This plugin is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
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
$wpdb->query("DROP TABLE IF EXISTS {$table_name}");

// remove plugin cron events

// ..etc., based on what needs to be removed