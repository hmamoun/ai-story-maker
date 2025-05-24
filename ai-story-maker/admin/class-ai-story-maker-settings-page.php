<?php
/**
 * Admin Settings Page for AI Story Maker.
 *
 * @package AI_Story_Maker
 * @author Hayan Mamoun
 * @license GPLv2 or later
 * @link https://github.com/hmamoun/ai-story-maker/wiki
 * @since 0.1.0
 */

namespace exedotcom\aistorymaker;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class AISTMA_Settings_Page
 *
 * Renders and processes the settings form for the AI Story Maker plugin.
 */
class AISTMA_Settings_Page {

	/**
	 * @var AISTMA_Log_Manager
	 */
	protected $aistma_log_manager;

	/**
	 * Constructor initializes the settings page and log manager.
	 */
	public function __construct() {
		$this->aistma_log_manager = new AISTMA_Log_Manager();
	}

	/**
	 * Renders the plugin settings page and handles form submissions.
	 *
	 * @return void
	 */
	public function aistma_setting_page_render() {

		// Handle form submission
		if ( isset( $_POST['save_settings'] ) ) {
			$story_maker_nonce = isset( $_POST['story_maker_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['story_maker_nonce'] ) ) : '';

			if ( ! $story_maker_nonce || ! wp_verify_nonce( $story_maker_nonce, 'save_story_maker_settings' ) ) {
				echo '<div class="error"><p> ' . esc_html__( 'Security check failed. Please try again.', 'ai-story-maker' ) . '</p></div>';
				$this->aistma_log_manager->log( 'error', ' Security check failed. Please try again.' );
				return;
			}

			if ( ! isset( $_POST['aistma_openai_api_key'] ) || AISTMA_API_Keys::aistma_validate_aistma_openai_api_key( sanitize_text_field( wp_unslash( $_POST['aistma_openai_api_key'] ) ) ) === false ) {
				echo '<div class="error"><p> ' . esc_html__( 'Invalid OpenAI API key.', 'ai-story-maker' ) . '</p></div>';
				$this->aistma_log_manager->log( 'error', ' Invalid OpenAI API key.' );
				return;
			}

			// If log retention days were changed, clear the old scheduled hook
			if (
				isset( $_POST['aistma_clear_log_cron'] ) &&
				get_option( 'aistma_clear_log_cron' ) !== sanitize_text_field( wp_unslash( $_POST['aistma_clear_log_cron'] ) )
			) {
				wp_clear_scheduled_hook( 'schd_ai_story_maker_clear_log' );
			}

			// Save Options
			update_option( 'aistma_openai_api_key', sanitize_text_field( wp_unslash( $_POST['aistma_openai_api_key'] ) ) );
			if ( isset( $_POST['aistma_unsplash_api_key'], $_POST['aistma_unsplash_api_secret'] ) ) {
				update_option(
					'aistma_unsplash_api_key',
					sanitize_text_field( wp_unslash( $_POST['aistma_unsplash_api_key'] ) )
				);
			
				update_option(
					'aistma_unsplash_api_secret',
					sanitize_text_field( wp_unslash( $_POST['aistma_unsplash_api_secret'] ) )
				);
			}
			update_option( 'aistma_clear_log_cron', sanitize_text_field( wp_unslash( $_POST['aistma_clear_log_cron'] ) ) );

			if ( isset( $_POST['aistma_generate_story_cron'] ) ) {
				$interval = intval( sanitize_text_field( wp_unslash( $_POST['aistma_generate_story_cron'] ) ) );
				$n        = absint( get_option( 'aistma_generate_story_cron' ) );

				if ( $interval === 0 ) {
					wp_clear_scheduled_hook( 'aistma_generate_story_event' );
				}

				update_option( 'aistma_generate_story_cron', $interval );

				if ( $n !== $interval ) {
					wp_clear_scheduled_hook( 'aistma_generate_story_event' );
					$generator = new AISTMA_Story_Generator();
					$generator->reschedule_cron_event();
					$this->aistma_log_manager->log( 'info', 'Schedule changed via admin. Running updated check.' );
				}
			}

			if ( isset( $_POST['aistma_opt_auther'] ) ) {
				update_option( 'aistma_opt_auther', intval( $_POST['aistma_opt_auther'] ) );
			}
			update_option( 'aistma_show_ai_attribution', isset( $_POST['aistma_show_ai_attribution'] ) ? 1 : 0 );
			update_option( 'aistma_show_exedotcom_attribution', isset( $_POST['aistma_show_exedotcom_attribution'] ) ? 1 : 0 );

			echo '<div class="notice notice-info"><p>' . esc_html__( 'Settings saved!', 'ai-story-maker' ) . '</p></div>';
			$this->aistma_log_manager->log( 'info', 'Settings saved' );
		}

		// Render settings form
		include AISTMA_PATH . 'admin/templates/general-settings-template.php';
	}
}