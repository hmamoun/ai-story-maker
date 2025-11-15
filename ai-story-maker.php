<?php
/**
 * Plugin Name: AI Story Maker
 * Plugin URI: https://www.storymakerplugin.com/
 * Description: AI-powered content generator for WordPress — create engaging stories with a single click.
 * Version: 2.1.3
 * Author: Hayan Mamoun
 * Author URI: https://exedotcom.ca
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: ai-story-maker
 * Domain Path: /languages
 * Requires PHP: 7.4
 * Requires at least: 5.8
 * Tested up to: 6.8.2
 *
 * @package AI_Story_Maker
 */

// phpcs:disable WordPress.Files.FileName.NotClassName
// phpcs:disable WordPress.Files.FileName.NotClass


if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
define( 'AISTMA_PATH', plugin_dir_path( __FILE__ ) );
define( 'AISTMA_URL', plugin_dir_url( __FILE__ ) );


use exedotcom\aistorymaker\AISTMA_Story_Generator;

require_once plugin_dir_path( __FILE__ ) . 'includes/class-aistma-plugin.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/class-aistma-posts-gadget.php';
require_once plugin_dir_path( __FILE__ ) . 'admin/class-aistma-standalone-editor.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/class-aistma-content-editor-handler.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/class-aistma-open-graph.php';

// Hooks.
register_activation_hook( __FILE__, array( 'exedotcom\\aistorymaker\\AISTMA_Plugin', 'aistma_activate' ) );
register_deactivation_hook( __FILE__, array( 'exedotcom\\aistorymaker\\AISTMA_Plugin', 'aistma_deactivate' ) );

// Initialize Posts Gadget
if ( class_exists( '\\exedotcom\\aistorymaker\\AISTMA_Posts_Gadget' ) ) {
    new \exedotcom\aistorymaker\AISTMA_Posts_Gadget( new \exedotcom\aistorymaker\AISTMA_Plugin() );
    
    // Debug: Add a temporary comment to verify class loaded
    add_action( 'wp_footer', function() {
        echo '<!-- Posts Gadget class loaded successfully -->';
    });
}

// Initialize Standalone Content Editor
if ( class_exists( '\\exedotcom\\aistorymaker\\AISTMA_Standalone_Editor' ) ) {
    new \exedotcom\aistorymaker\AISTMA_Standalone_Editor();
}

// Initialize Content Editor Handler
if ( class_exists( '\\exedotcom\\aistorymaker\\AISTMA_Content_Editor_Handler' ) ) {
    new \exedotcom\aistorymaker\AISTMA_Content_Editor_Handler();
}

// Initialize Open Graph Meta Tags Handler
if ( class_exists( '\\exedotcom\\aistorymaker\\AISTMA_Open_Graph' ) ) {
    new \exedotcom\aistorymaker\AISTMA_Open_Graph();
}

/**
 * Handle AJAX request to generate stories.
 */
add_action(
	'wp_ajax_generate_ai_stories',
	function () {
		if ( ! check_ajax_referer( 'generate_story_nonce', 'nonce', false ) ) {
			wp_send_json_error( array( 'message' => 'Security check failed.' ) );
		}
		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( array( 'message' => 'You do not have permission to perform this action.' ) );
		}
		try {
			$story_generator = new AISTMA_Story_Generator();
			$result = $story_generator->generate_ai_stories_with_lock( true );
			
			if ( $result['success'] ) {
				wp_send_json_success( array( 'message' => $result['message'] ) );
			} else {
				wp_send_json_error( array( 'message' => $result['message'] ) );
			}
		} catch ( \Throwable $e ) {
			wp_send_json_error( array( 'message' => 'Fatal error: ' . $e->getMessage() ) );
		}
	}
);




// Initialize Settings Page instance early to handle AJAX and OAuth
add_action( 'plugins_loaded', function() {
    // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- OAuth callback parameter check, actual security verification in Settings Page class
    if ( is_admin() || ( defined( 'DOING_AJAX' ) && DOING_AJAX ) || isset( $_GET['aistma_facebook_oauth'] ) ) {
        new \exedotcom\aistorymaker\AISTMA_Settings_Page();
    }
});

/**
 * Hook for scheduled story generation.
 */
add_action( 'aistma_generate_story_event', __NAMESPACE__ . '\\aistma_handle_generate_story_event' );

/**
 * Callback for WP-Cron to generate new stories.
 */
function aistma_handle_generate_story_event() {
	$result = AISTMA_Story_Generator::generate_ai_stories_with_lock();
	// Log the result for cron jobs (only in debug mode)
	if ( defined( 'WP_DEBUG' ) && WP_DEBUG && function_exists( 'wp_debug_log' ) ) {
		if ( $result['success'] ) {
			wp_debug_log( 'AI Story Maker Cron: ' . $result['message'] );
		} else {
			wp_debug_log( 'AI Story Maker Cron Error: ' . $result['message'] );
		}
	}
}
function aistma_get_master_url(string $path = ''): string {
	$base_url = defined('AISTMA_MASTER_URL') ? AISTMA_MASTER_URL : 'https://www.storymakerplugin.com';
    return rtrim($base_url, '/') . '/' . ltrim($path, '/');
}
function aistma_get_api_url(string $path = ''): string {
    $base_url = defined('AISTMA_MASTER_API') ? AISTMA_MASTER_API : 'https://www.storymakerplugin.com';
    return rtrim($base_url, '/') . '/' . ltrim($path, '/');
}
function aistma_get_instructions_url(): string {
    $default_url = aistma_get_api_url('wp-json/exaig/v1/aistma-general-instructions');
	return apply_filters('aistma_instructions_url', $default_url);
}
