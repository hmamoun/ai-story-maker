<?php
/**
 * AI Story Maker Plugin Main Class
 *
 * This class serves as the core plugin class, handling initialization,
 * dependencies, and core functionality for the AI Story Maker plugin.
 *
 * @package AI_Story_Maker
 * @since   0.1.0
 */

// phpcs:disable WordPress.Files.FileName.NotClassName
// phpcs:disable WordPress.Files.FileName.NotClass

namespace exedotcom\aistorymaker;

use exedotcom\aistorymaker\AISTMA_Log_Manager;


/**
 * Main plugin class.
 */
class AISTMA_Plugin {


	/**
	 * Constructor to initialize plugin.
	 */
	public function __construct() {
		add_action( 'admin_enqueue_scripts', array( $this, 'aistma_enqueue_admin_styles' ) );
		add_filter( 'template_include', array( $this, 'aistma_template_include_filter' ) );
		$this->aistma_load_dependencies(
			array(
				'admin/class-aistma-admin.php',
				'includes/class-aistma-story-generator.php',
				'includes/shortcode-story-scroller.php',
				'includes/class-aistma-log-manager.php',
			)
		);
		add_action( 'admin_post_aistma_clear_logs', array( AISTMA_Log_Manager::class, 'aistma_clear_logs' ) );
	}

	/**
	 * Load required class files.
	 *
	 * @param array $files List of relative file paths to include.
	 */
	public static function aistma_load_dependencies( $files = array() ) {
		foreach ( $files as $file ) {
			$path = AISTMA_PATH . $file;
			if ( file_exists( $path ) ) {
				include_once $path;
			} elseif ( class_exists( 'exedotcom\aistorymaker\AISTMA_Log_Manager' ) ) {
				( new AISTMA_Log_Manager() )->log( "Missing dependency file: $path" );
			}
		}
	}

	/**
	 * Enqueue admin styles on plugin settings page.
	 *
	 * @param string $hook Admin page slug.
	 */
	public function aistma_enqueue_admin_styles( $hook ) {
		if ( 'toplevel_page_aistma-settings' !== $hook ) {
			return;
		}
		wp_enqueue_style(
			'ai-storymaker-admin-css',
			AISTMA_URL . 'admin/css/admin.css',
			array(),
			filemtime( AISTMA_PATH . 'admin/css/admin.css' )
		);
	}

	/**
	 * Use custom template for AI Story Maker posts.
	 *
	 * @param  string $template Template file path.
	 * @return string
	 */
	public function aistma_template_include_filter( $template ) {
		if ( is_single() ) {
			$post_id = get_the_ID();
			if ( get_post_meta( $post_id, 'ai_story_maker_request_id', true ) ) {
				$plugin_template = AISTMA_PATH . '/public/templates/aistma-post-template.php';
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
