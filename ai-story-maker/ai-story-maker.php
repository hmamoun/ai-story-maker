<?php
/**
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
namespace AI_Story_Maker;
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Plugin {
    protected $log_manager;
    public function __construct() {

        add_action( 'init', array( $this, 'wpdocs_load_textdomain') );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_styles' ) );
        add_filter( 'template_include', array( $this, 'template_include_filter' ) );
        $this->load_dependencies();
        $this->log_manager = new Log_Manager();
    }

    /**
     * Load the plugin text domain for translations.
     */
    function wpdocs_load_textdomain() {
        load_plugin_textdomain( 'ai-story-maker', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' ); 
    }

    /**
     * Load required dependency files.
     */
    private function load_dependencies() {
        $includes = plugin_dir_path( __FILE__ ) . 'admin/class-ai-story-maker-admin.php';
        if ( file_exists( $includes ) ) {
            require_once $includes;
        }
        $includes = plugin_dir_path( __FILE__ ) . 'includes/class-ai-story-maker-story-generator.php';
        if ( file_exists( $includes ) ) {
            require_once $includes;
        }
        $includes = plugin_dir_path( __FILE__ ) . 'includes/story-scroller.php';
        if ( file_exists( $includes ) ) {
            require_once $includes;
        }
        $includes = plugin_dir_path( __FILE__ ) . 'includes/class-ai-story-maker-log-management.php';
        if ( file_exists( $includes ) ) {
            require_once $includes;
        }
    }

    /**
     * Enqueue admin styles only on the plugin settings page.
     *
     * @param string $hook The current admin page hook.
     */
    public function enqueue_admin_styles( $hook ) {
        if ( $hook !== 'toplevel_page_story-maker-settings' ) {
            return;
        }
        wp_enqueue_style(
            'ai-storymaker-admin-css',
            plugin_dir_url( __FILE__ ) . 'admin/css/admin.css',
            array(),
            '1.0'
        );
    }

    /**
     * Filter the template used for single posts.
     *
     * @param string $template The path to the template.
     * @return string The modified template path.
     */
    function template_include_filter( $template ) {
        if ( is_single() ) {
            $post_id = get_the_ID();
            // Check if this post was generated by AI Story Maker by verifying if the request ID meta exists.
            if ( get_post_meta( $post_id, 'ai_story_maker_request_id', true ) ) {
                $plugin_template = plugin_dir_path( __FILE__ ) . '/public/single-ai-story.php';
                if ( file_exists( $plugin_template ) ) {
                    return $plugin_template;
                }
            }
        }
        return $template;
    }

    /**
     * Activation callback to create necessary log tables or other setup.
     */
    public static function activate() {
        if ( function_exists( 'ai_storymaker_create_log_table' ) ) {
            ai_storymaker_create_log_table();
        }
        
        // bmark Schedule on activation
        if ( ! wp_next_scheduled( 'ai_story_generator_repeating_event' ) ) {
            wp_schedule_event( time(), 'daily', 'ai_story_generator_repeating_event' );
        }
    }

    /**
     * Deactivation callback to clear scheduled events.
     */
    public static function deactivate() {
        wp_clear_scheduled_hook( 'ai_story_generator_repeating_event' );
    }
}

// Instantiate the main plugin class.
new Plugin();

// Register activation hook using the Plugin's static method.
register_activation_hook( __FILE__, array( 'AI_Story_Maker\Plugin', 'activate' ) );

// Register deactivation hook to clear scheduled events.
register_deactivation_hook( __FILE__, array( 'AI_Story_Maker\Plugin', 'deactivate' ) );


/**
 * AJAX action to generate an AI story.
 */
add_action( 'wp_ajax_generate_ai_stories', function() {
    // Verify nonce and user capabilities.
    if ( ! check_ajax_referer( 'generate_story_nonce', 'nonce', false ) ) {
        wp_send_json_error( [ 'message' => 'Security check failed.' ] );
    }
    // Instantiate your generator class and generate the story.
    $story_generator = new Story_Generator();
    $results = $story_generator->generate_ai_stories();
    if ( ! empty( $results['errors'] ) ) {
        wp_send_json_error( $results['errors'] );
    } else {
        wp_send_json_success( $results['successes'] );
    }
});
function ai_story_maker_check_schedule() {
    $log_manager = new Log_Manager();
    $next_event = wp_next_scheduled('ai_story_generator_repeating_event');
    
    if ($next_event) {
        $time_diff = $next_event - time();
        
        if ($time_diff < -5) {
            // bmark Schedule execute
            $story_generator = new Story_Generator();
            $story_generator->generate_ai_stories();
            // Log the event
            $log_manager::log('info', 'Generated stories schedule.');
            // $ai_story_maker_log_manager::log('info', 'Generated stories schedule.');
        }
    } else {
        // Check if the schedule is set; if not, set it.
        $n = absint(get_option('opt_ai_story_repeat_interval_days'));
        
        if (0 !== $n) {
            // bmark Schedule in the case of no schedule
            $next_schedule = gmtdate('Y-m-d H:i:s', time() + $n * DAY_IN_SECONDS);
            wp_schedule_single_event(time() + $n * DAY_IN_SECONDS, 'ai_story_generator_repeating_event');
            // Log the next schedule
            /* translators: %s: next schedule */
            $log_manager::log('info', sprintf(__('Set next schedule to %s' , 'ai-story-maker'), $next_schedule));

        } 
    }
}          
ai_story_maker_check_schedule(); 
