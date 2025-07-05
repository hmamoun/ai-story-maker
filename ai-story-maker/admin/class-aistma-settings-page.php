<?php
/**
 * Admin Settings Page for AI Story Maker.
 *
 * @package AI_Story_Maker
 * @author  Hayan Mamoun
 * @license GPLv2 or later
 * @link    https://github.com/hmamoun/ai-story-maker/wiki
 * @since   0.1.0
 */

// phpcs:disable WordPress.Files.FileName.NotClassName
// phpcs:disable WordPress.Files.FileName.NotClass

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
	 * Instance of the log manager.
	 *
	 * @var AISTMA_Log_Manager
	 */
	protected $aistma_log_manager;

	/**
	 * Constructor initializes the settings page and log manager.
	 */
	public function __construct() {
		$this->aistma_log_manager = new AISTMA_Log_Manager();
		add_action( 'wp_ajax_aistma_save_setting', [ $this, 'aistma_ajax_save_setting' ] );
	}

	/**
	 * Handles AJAX request to save a single setting.
	 */
	public function aistma_ajax_save_setting() {
		error_log(1);
		// Check nonce for security
		if ( ! isset( $_POST['aistma_security'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['aistma_security'] ) ), 'aistma_save_setting' ) ) {
			wp_send_json_error( [ 'message' => __( 'Security check failed. Please try again.', 'ai-story-maker' ) ] );
			$this->aistma_log_manager->log( 'error', ' Security check failed. Please try again.' );
			wp_die();
		}

		$setting_name  = isset( $_POST['setting_name'] ) ? sanitize_text_field( wp_unslash( $_POST['setting_name'] ) ) : '';
		$setting_value = isset( $_POST['setting_value'] ) ? wp_unslash( $_POST['setting_value'] ) : null;

		if ( empty( $setting_name ) ) {
			wp_send_json_error( [ 'message' => __( 'No setting name provided.', 'ai-story-maker' ) ] );
			wp_die();
		}

		// Validate and update specific settings
		switch ( $setting_name ) {
			case 'aistma_openai_api_key':
				if ( ! AISTMA_API_Keys::aistma_validate_aistma_openai_api_key( sanitize_text_field( $setting_value ) ) ) {
					wp_send_json_error( [ 'message' => __( 'Invalid OpenAI API key.', 'ai-story-maker' ) ] );
					$this->aistma_log_manager->log( 'error', ' Invalid OpenAI API key.' );
					wp_die();
				}
				update_option( 'aistma_openai_api_key', sanitize_text_field( $setting_value ) );
				break;
			case 'aistma_unsplash_api_key':
				update_option( 'aistma_unsplash_api_key', sanitize_text_field( $setting_value ) );
				break;
			case 'aistma_unsplash_api_secret':
				update_option( 'aistma_unsplash_api_secret', sanitize_text_field( $setting_value ) );
				break;
			case 'aistma_clear_log_cron':
				if ( get_option( 'aistma_clear_log_cron' ) !== sanitize_text_field( $setting_value ) ) {
					wp_clear_scheduled_hook( 'schd_ai_story_maker_clear_log' );
				}
				update_option( 'aistma_clear_log_cron', sanitize_text_field( $setting_value ) );
				break;
			case 'aistma_generate_story_cron':
				$interval = intval( $setting_value );
				$n        = absint( get_option( 'aistma_generate_story_cron' ) );
				if ( 0 === $interval ) {
					wp_clear_scheduled_hook( 'aistma_generate_story_event' );
				}
				update_option( 'aistma_generate_story_cron', $interval );
				if ( $n !== $interval ) {
					wp_clear_scheduled_hook( 'aistma_generate_story_event' );
					$generator = new AISTMA_Story_Generator();
					$generator->reschedule_cron_event();
					$this->aistma_log_manager->log( 'info', 'Schedule changed via admin. Running updated check.' );
				}
				break;
			case 'aistma_opt_auther':
				update_option( 'aistma_opt_auther', intval( $setting_value ) );
				break;
			case 'aistma_show_ai_attribution':
				update_option( 'aistma_show_ai_attribution', $setting_value ? 1 : 0 );
				break;
			case 'aistma_show_exedotcom_attribution':
				update_option( 'aistma_show_exedotcom_attribution', $setting_value ? 1 : 0 );
				break;
			default:
				wp_send_json_error( [ 'message' => __( 'Unknown setting.', 'ai-story-maker' ) ] );
				wp_die();
		}

		$this->aistma_log_manager->log( 'info', 'Setting ' . $setting_name . ' updated.' );
		wp_send_json_success( [ 'message' => __( 'Setting saved!', 'ai-story-maker' ) ] );
		wp_die();
	}

	/**
	 * Renders the plugin settings page.
	 *
	 * @return void
	 */
	public function aistma_setting_page_render() {
		// Only render the settings form. No POST handling here.
		include AISTMA_PATH . 'admin/templates/general-settings-template.php';
	}
}
