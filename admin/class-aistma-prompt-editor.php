<?php
/**
 * Prompt Editor for AI Story Maker.
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
 * Class AISTMA_Prompt_Editor
 *
 * Handles the admin prompt editor page for managing story prompts via JSON.
 *
 * @since 0.1.0
 */
class AISTMA_Prompt_Editor {


	/**
	 * Log manager instance.
	 *
	 * @var AISTMA_Log_Manager
	 */
	protected $aistma_log_manager;

	/**
	 * AISTMA_Prompt_Editor constructor.
	 *
	 * Initializes the logger.
	 */
	public function __construct() {
		$this->aistma_log_manager = new AISTMA_Log_Manager();
	}

	/**
	 * Renders the Prompt Editor admin page.
	 *
	 * This method handles form submission, sanitizes and updates prompt settings,
	 * then passes data to the view.
	 *
	 * @return void
	 */
	public function aistma_prompt_editor_render() {
		// Handle prompt submission.
		if ( isset( $_SERVER['REQUEST_METHOD'] ) && 'POST' === $_SERVER['REQUEST_METHOD'] && isset( $_POST['save_prompts_v2'] ) ) {
			check_admin_referer( 'save_story_prompts', 'story_prompts_nonce' );

			$raw_prompts_input = isset( $_POST['prompts'] ) ? sanitize_textarea_field( wp_unslash( $_POST['prompts'] ) ) : '';
			$updated_prompts   = $raw_prompts_input ? json_decode( $raw_prompts_input, true ) : array();

			// Check for JSON decode errors
			if ( json_last_error() !== JSON_ERROR_NONE ) {
				echo '<div id="aistma-notice" class="notice notice-error"><p>❌ ' .
				esc_html__( 'Error: Invalid JSON data received. Please try again.', 'ai-story-maker' ) .
				' JSON Error: ' . esc_html( json_last_error_msg() ) . '</p></div>';
				
				$this->aistma_log_manager->log( 'error', 'JSON decode error: ' . json_last_error_msg() . ' Raw data: ' . $raw_prompts_input );
				return;
			}

			// Validate the JSON structure and ensure it has the required properties
			if ( ! is_array( $updated_prompts ) ) {
				$updated_prompts = array();
			}

			// Ensure the structure has both default_settings and prompts
			if ( ! isset( $updated_prompts['default_settings'] ) ) {
				$updated_prompts['default_settings'] = array();
			}
			if ( ! isset( $updated_prompts['prompts'] ) ) {
				$updated_prompts['prompts'] = array();
			}

			// Preserve existing default_settings if not provided in the form
			$existing_settings = get_option( 'aistma_prompts', '{}' );
			$existing_data = json_decode( $existing_settings, true );
			if ( is_array( $existing_data ) && isset( $existing_data['default_settings'] ) && empty( $updated_prompts['default_settings'] ) ) {
				$updated_prompts['default_settings'] = $existing_data['default_settings'];
			}

			update_option( 'aistma_prompts', wp_json_encode( $updated_prompts ) );

			echo '<div id="aistma-notice" class="notice notice-info"><p>✅ ' .
			esc_html__( 'Prompts saved successfully!', 'ai-story-maker' ) .
			'</p></div>';

			$this->aistma_log_manager->log( 'info', 'Prompts saved successfully.' );
		}

		// Prepare data for rendering.
		$raw_json         = get_option( 'aistma_prompts', '{}' );
		$settings         = json_decode( $raw_json, true );
		
		// Check for JSON decode errors in existing data
		if ( json_last_error() !== JSON_ERROR_NONE ) {
			$this->aistma_log_manager->log( 'error', 'JSON decode error loading existing prompts: ' . json_last_error_msg() . ' Raw data: ' . $raw_json );
			$settings = array();
		}
		
		// Validate the settings structure
		if ( ! is_array( $settings ) ) {
			$settings = array();
		}
		
		$prompts          = isset( $settings['prompts'] ) ? $settings['prompts'] : array();
		$default_settings = isset( $settings['default_settings'] ) ? $settings['default_settings'] : array();
		$categories       = get_categories( array( 'hide_empty' => false ) );

		if ( ! is_array( $prompts ) ) {
			$prompts = array();
		}

		if ( count( $prompts ) === 0 ) {
			$prompts[] = array(
				'text'         => 'Write your first prompt here.. ',
				'category'     => '',
				'photos'       => 0,
				'active'       => false,
				'auto_publish' => false,
			);
		}

		// Ensure we preserve the default_settings structure
		if ( ! isset( $settings['default_settings'] ) ) {
			$settings['default_settings'] = array();
		}

		// Make variables available to the template.
		$data = compact( 'prompts', 'default_settings', 'categories' );

		// Load view.
		include AISTMA_PATH . 'admin/templates/prompt-editor-template.php';
	}
}
