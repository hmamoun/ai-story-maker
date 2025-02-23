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
error_log('ℹ️ AI News Generator plugin loaded.');
// Schedule WP Cron event on plugin activation
register_activation_hook(__FILE__, function() {
    if (!wp_next_scheduled('generate news_cron')) {
        wp_schedule_event(time(), 'daily', 'generate_ai_news_cron');
    }
});

// Unschedule WP Cron event on plugin deactivation
register_deactivation_hook(__FILE__, function() {
    wp_clear_scheduled_hook('generate_ai_news_cron');
});

// Hook into WP Cron to generate AI content
add_action('generate_ai_news_cron', 'generate_ai_news');

// add_filter('theme_page_templates', function($templates) {
//     $templates['single-ai-news.php'] = 'AI News Template';
//     return $templates;
// });


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
include_once plugin_dir_path(__FILE__) . 'includes/news-scroller.php';



