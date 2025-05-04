<?php
/**
 * Plugin Name: AI Story Maker
 * Plugin URI: https://github.com/hmamoun/ai-story-maker/wiki
 * Description: AI-powered content generator for WordPress — create engaging stories with a single click.
 * Version: 0.1.0
 * Author: Hayan Mamoun
 * Author URI: https://exedotcom.ca
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: ai-story-maker
 * Domain Path: /languages
 * Requires PHP: 7.4
 * Requires at least: 5.8
 * Tested up to: 6.7
 *
 * @package AI_Story_Maker
 */

namespace exedotcom\aistorymaker;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'AI_STORY_MAKER_PATH', plugin_dir_path( __FILE__ ) );
define( 'AI_STORY_MAKER_URL', plugin_dir_url( __FILE__ ) );

use exedotcom\aistorymaker\AISTMA_Story_Generator;
use exedotcom\aistorymaker\AISTMA_Log_Manager;

/**
 * Class AISTMA_Plugin
 *
 * Main plugin class that handles hooks and setup.
 */
class AISTMA_Plugin {

    /**
     * Constructor to initialize plugin.
     */
    public function __construct() {
        add_action( 'init', array( $this, 'aistma_load_plugin_textdomain') );
        add_action( 'admin_enqueue_scripts', array( $this, 'aistma_enqueue_admin_styles' ) );
        add_filter( 'template_include', array( $this, 'aistma_template_include_filter' ) );
        $this->aistma_load_dependencies([
            'admin/class-ai-story-maker-admin.php',
            'includes/class-ai-story-maker-story-generator.php',
            'includes/shortcode-story-scroller.php',
            'includes/class-ai-story-maker-log-management.php'
        ]);
        add_action( 'admin_post_aistma_clear_logs', [AISTMA_Log_Manager::class, 'aistma_clear_logs'] );
    }

    /**
     * Load required class files.
     *
     * @param array $files List of relative file paths to include.
     */
    public static function aistma_load_dependencies( $files = [] ) {
        foreach ( $files as $file ) {
            $path = AI_STORY_MAKER_PATH . $file;
            if ( file_exists( $path ) ) {
                include_once $path;
            } else {
                if ( class_exists( 'exedotcom\aistorymaker\AISTMA_Log_Manager' ) ) {
                    ( new AISTMA_Log_Manager() )->log( "Missing dependency file: $path" );
                }
            }
        }
    }

    /**
     * Load plugin text domain for translations.
     */
    public function aistma_load_plugin_textdomain() {
        load_plugin_textdomain( 'ai-story-maker', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
    }

    /**
     * Enqueue admin styles on plugin settings page.
     *
     * @param string $hook Admin page slug.
     */
    public function aistma_enqueue_admin_styles( $hook ) {
        if ( $hook !== 'toplevel_page_aistma-settings' ) {
            return;
        }
        wp_enqueue_style(
            'ai-storymaker-admin-css',
            AI_STORY_MAKER_URL . 'admin/css/admin.css',
            array(),
            filemtime( AI_STORY_MAKER_PATH . 'admin/css/admin.css' )
        );
    }

    /**
     * Use custom template for AI Story Maker posts.
     *
     * @param string $template Template file path.
     * @return string
     */
    public function aistma_template_include_filter( $template ) {
        if ( is_single() ) {
            $post_id = get_the_ID();
            if ( get_post_meta( $post_id, 'ai_story_maker_request_id', true ) ) {
                $plugin_template = AI_STORY_MAKER_PATH . '/public/templates/aistma-post-template.php';
                if ( file_exists( $plugin_template ) ) {
                    return $plugin_template;
                }
            }
        }
        return $template;
    }

    /**
     * Activate the plugin and schedule cron events.
     */
    public static function aistma_activate() {
        $log_manager = new AISTMA_Log_Manager();

        if ( function_exists( 'ai_storymaker_aistma_create_log_table' ) ) {
            ai_storymaker_aistma_create_log_table();
        }

        if ( ! wp_next_scheduled( 'aistma_generate_story_event' ) ) {
            $n = absint( get_option( 'aistma_generate_story_cron' ) );
            if ( 0 !== $n ) {
                wp_schedule_event( time() + $n * DAY_IN_SECONDS, 'daily', 'aistma_generate_story_event' );
                /* translators: Formatting the date for the next schedule to be come readable. */
                $log_manager->log( 'info', sprintf( __( 'Set next schedule to %s', 'ai-story-maker' ), gmdate( 'Y-m-d H:i:s', time() + $n * DAY_IN_SECONDS ) ) );
            }
        }
    }

    /**
     * Deactivate plugin and clean up.
     */
    public static function aistma_deactivate() {
        wp_clear_scheduled_hook( 'aistma_generate_story_event' );
        delete_transient( 'aistma_generating_lock' );
    }
}

new AISTMA_Plugin();

// Hooks
register_activation_hook( __FILE__, array( 'exedotcom\\aistorymaker\\AISTMA_Plugin', 'aistma_activate' ) );
register_deactivation_hook( __FILE__, array( 'exedotcom\\aistorymaker\\AISTMA_Plugin', 'aistma_deactivate' ) );

/**
 * Handle AJAX request to generate stories.
 */
add_action( 'wp_ajax_generate_ai_stories', function() {
    if ( ! check_ajax_referer( 'generate_story_nonce', 'nonce', false ) ) {
        wp_send_json_error( [ 'message' => 'Security check failed.' ] );
    }

    if ( ! current_user_can( 'edit_posts' ) ) {
        wp_send_json_error( [ 'message' => 'You do not have permission to perform this action.' ] );
    }

    try {
        $story_generator = new AISTMA_Story_Generator();
        $results = $story_generator->generate_ai_stories_with_lock( true );

        if ( ! empty( $results['errors'] ) ) {
            wp_send_json_error( $results['errors'] );
        } else {
            wp_send_json_success( $results['successes'] );
        }
    } catch ( \Throwable $e ) {
        wp_send_json_error( [ 'message' => 'Fatal error: ' . $e->getMessage() ] );
    }
} );

if ( defined( 'WP_ENV' ) && WP_ENV === 'exedotcom-development' ) {
    define( 'ALTERNATE_WP_CRON', true );
}

/**
 * Hook for scheduled story generation.
 */
add_action( 'aistma_generate_story_event', __NAMESPACE__ . '\\aistma_handle_generate_story_event' );

/**
 * Callback for WP-Cron to generate new stories.
 */
function aistma_handle_generate_story_event() {
    $generator = new AISTMA_Story_Generator();
    $generator->generate_ai_stories_with_lock();
}