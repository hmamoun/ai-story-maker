<?php
/**
 * Plugin Name: AI Story Maker
 * Plugin URI: https://github.com/hmamoun/ai-story-maker
 * Description: AI-powered WordPress plugin that generates engaging stories, articles, and images using Large Language Models.
 * Version: 1.0
 * Author: Hayan Mamoun
 * Author URI: https://exedotcom.ca
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html

This plugin is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.
 */

if (!defined('ABSPATH')) exit; 
function ai_storymaker_enqueue_admin_styles($hook) {

    // Load CSS only on the plugin settings page
    if ($hook !== 'toplevel_page_story-maker-settings') {
        return;
    }
    wp_enqueue_style(
        'ai-storymaker-admin-css',
        plugin_dir_url(__FILE__) . 'admin/css/story-style-admin.css',
        array(),
        '1.0'
    );

}
add_action('admin_enqueue_scripts', 'ai_storymaker_enqueue_admin_styles');


add_filter('template_include', function ($template) {
    if (is_single()) { // Applies to all single posts
        $plugin_template = plugin_dir_path(__FILE__) . 'templates/single-ai-story.php';
        if (file_exists($plugin_template)) {
            return $plugin_template;
        } else {
            ai_storymaker_log('error', 'Template file missing: ' . $plugin_template);
        }
    }
    return $template;
});
include_once plugin_dir_path(__FILE__) . 'admin/admin-page.php';
include_once plugin_dir_path(__FILE__) . 'includes/generate-story.php';
include_once plugin_dir_path(__FILE__) . 'includes/get-photos-unsplash.php';
include_once plugin_dir_path(__FILE__) . 'includes/get-photos-pexels.php';
include_once plugin_dir_path(__FILE__) . 'includes/story-scroller.php';

// include admin only if in admin area
if (is_admin()) {

    include_once plugin_dir_path(__FILE__) . 'includes/log-management.php';

    register_activation_hook(__FILE__, 'ai_storymaker_create_log_table');
}



