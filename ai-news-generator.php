<?php
/**
 * Plugin Name: AI News Generator
 * Description: Automatically generates and publishes AI-powered news articles as WordPress posts.
 * Version: 1.0
 * Author: Your Name
 */

/*


 TODO: Parameterize the schedule information.
 TODO: Check the plugin's liability and determine if it is suitable for public release.

 */
if (!defined('ABSPATH')) exit; // Prevent direct access

add_filter('template_include', function ($template) {
    if (is_single()) { // Applies to all single posts
        $plugin_template = plugin_dir_path(__FILE__) . 'templates/single-ai-news.php';
        if (file_exists($plugin_template)) {
            return $plugin_template;
        } else {
            error_log('❌ Template file missing: ' . $plugin_template);
        }
    }
    return $template;
});

?>
<?php

// include admin only if in admin area
if (is_admin()) {
    include_once plugin_dir_path(__FILE__) . 'includes/admin-page.php';
}
include_once plugin_dir_path(__FILE__) . 'includes/generate-news-article.php';
include_once plugin_dir_path(__FILE__) . 'includes/get-photos-unsplash.php';
include_once plugin_dir_path(__FILE__) . 'includes/get-photos-pexels.php';
include_once plugin_dir_path(__FILE__) . 'includes/news-scroller.php';

include_once plugin_dir_path(__FILE__) . 'includes/cron-job-log.php';

