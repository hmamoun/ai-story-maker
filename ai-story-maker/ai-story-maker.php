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
 */
namespace AI_Story_Maker;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Plugin {
    public function __construct() {
        // Load plugin dependencies
        $this->load_dependencies();

        // Setup hooks
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_styles' ) );
        add_filter( 'template_include', array( $this, 'template_include_filter' ) );

        // Admin-specific includes
        if ( is_admin() ) {
            include_once plugin_dir_path( __FILE__ ) . 'includes/log-management.php';
        }
    }

    /**
     * Load required dependency files.
     */
    private function load_dependencies() {
        //include_once plugin_dir_path( __FILE__ ) . 'admin/admin-page.php';
        include_once plugin_dir_path( __FILE__ ) . 'admin/class-ai-story-maker-admin.php';
        include_once plugin_dir_path( __FILE__ ) . 'includes/class-ai-story-maker-generator.php';
        include_once plugin_dir_path( __FILE__ ) . 'includes/get-photos-unsplash.php';
        include_once plugin_dir_path( __FILE__ ) . 'includes/story-scroller.php';
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
            plugin_dir_url( __FILE__ ) . 'admin/css/story-style-admin.css',
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
    public function template_include_filter( $template ) {
        if ( is_single() ) {
            $plugin_template = plugin_dir_path( __FILE__ ) . 'public/single-ai-story.php';
            if ( file_exists( $plugin_template ) ) {
                return $plugin_template;
            } else {
                // Assumes ai_storymaker_log() is defined in one of the includes.
                ai_storymaker_log( 'error', 'Template file missing: ' . $plugin_template );
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
    }
}

// Instantiate the main plugin class.
new Plugin();

// Register activation hook using the Plugin's static method.
register_activation_hook( __FILE__, array( 'AI_Story_Maker\Plugin', 'activate' ) );