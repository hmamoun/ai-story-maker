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

// phpcs:disable WordPress.Files.FileName.NotClassName
// phpcs:disable WordPress.Files.FileName.NotClass


if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'AISTMA_PATH', plugin_dir_path( __FILE__ ) );
define( 'AISTMA_URL', plugin_dir_url( __FILE__ ) );

use exedotcom\aistorymaker\AISTMA_Story_Generator;

require_once plugin_dir_path( __FILE__ ) . 'includes/class-aistma-plugin.php';

// Hooks.
register_activation_hook( __FILE__, array( 'exedotcom\\aistorymaker\\AISTMA_Plugin', 'aistma_activate' ) );
register_deactivation_hook( __FILE__, array( 'exedotcom\\aistorymaker\\AISTMA_Plugin', 'aistma_deactivate' ) );

/**
 * Handle AJAX request to generate stories.
 */
;
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
			$results         = $story_generator->generate_ai_stories_with_lock( true );
			error_log(print_r($results));
			if ( ! empty( $results['errors'] ) ) {
				wp_send_json_error( $results['errors'] );
			} else {
				wp_send_json_success( $results['successes'] );
			}
		} catch ( \Throwable $e ) {
			wp_send_json_error( array( 'message' => 'Fatal error: ' . $e->getMessage() ) );
		}
	}
);



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
function aistma_get_master_url(string $path = ''): string {
    $base_url = defined('AISTMA_MASTER_URL') ? rtrim(AISTMA_MASTER_URL, '/') : 'https://exedotcom.ca';
    return rtrim($base_url, '/') . '/' . ltrim($path, '/');
}

function aistma_get_instructions_url(): string {
    $default_url = aistma_get_master_url('wp-json/exaig/v1/aistma-general-instructions');
    return apply_filters('aistma_instructions_url', $default_url);
}


//delete_transient( 'aistma_generating_lock' );