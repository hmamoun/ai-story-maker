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
	 * Validate prompts data before saving.
	 *
	 * @param array $data The prompts data to validate.
	 * @return array Validation result with 'valid' boolean and 'message' string.
	 */
	private function validate_prompts_data( $data ) {
		// Check if prompts array exists
		if ( ! isset( $data['prompts'] ) || ! is_array( $data['prompts'] ) ) {
			return array(
				'valid' => true, // Allow saving with no prompts
				'message' => __( 'No prompts to validate', 'ai-story-maker' )
			);
		}

		// Check each prompt - if it has any changes (active, category, photos, auto_publish), it must have text
		foreach ( $data['prompts'] as $index => $prompt ) {
			// Check if prompt has any meaningful changes (not just empty text)
			$has_changes = false;
			
			// Check if prompt is marked as active
			if ( isset( $prompt['active'] ) && $prompt['active'] ) {
				$has_changes = true;
			}
			
			// Check if prompt has a category selected
			if ( isset( $prompt['category'] ) && ! empty( trim( $prompt['category'] ) ) ) {
				$has_changes = true;
			}
			
			// Check if prompt has photos configured
			if ( isset( $prompt['photos'] ) && $prompt['photos'] > 0 ) {
				$has_changes = true;
			}
			
			// Check if prompt has auto_publish enabled
			if ( isset( $prompt['auto_publish'] ) && $prompt['auto_publish'] ) {
				$has_changes = true;
			}
			
			// If prompt has changes but no text content, it's invalid
			if ( $has_changes && ( ! isset( $prompt['text'] ) || empty( trim( $prompt['text'] ) ) ) ) {
				return array(
					'valid' => false,
					'message' => sprintf( 
						// translators: %d is the prompt number (1-based index)
						__( 'Prompt #%d has settings configured but no text content. Please provide text content or remove the settings.', 'ai-story-maker' ), 
						$index + 1 
					)
				);
			}
		}

		return array(
			'valid' => true,
			'message' => __( 'Validation passed', 'ai-story-maker' )
		);
	}

	/**
	 * Sanitize and escape prompt data for JSON storage.
	 *
	 * @param array $data The prompts data to sanitize.
	 * @return array Sanitized prompts data.
	 */
	private function sanitize_prompts_data( $data ) {
		if ( ! is_array( $data ) ) {
			return array();
		}

		// Sanitize default_settings
		if ( isset( $data['default_settings'] ) && is_array( $data['default_settings'] ) ) {
			foreach ( $data['default_settings'] as $key => $value ) {
				// Sanitize text content and escape special characters
				$data['default_settings'][ $key ] = $this->sanitize_text_for_json( $value );
			}
		}

		// Sanitize prompts array
		if ( isset( $data['prompts'] ) && is_array( $data['prompts'] ) ) {
			foreach ( $data['prompts'] as $index => $prompt ) {
				if ( is_array( $prompt ) ) {
					// Sanitize text field (main prompt content)
					if ( isset( $prompt['text'] ) ) {
						$data['prompts'][ $index ]['text'] = $this->sanitize_text_for_json( $prompt['text'] );
					}

					// Sanitize category field
					if ( isset( $prompt['category'] ) ) {
						$data['prompts'][ $index ]['category'] = sanitize_text_field( $prompt['category'] );
					}

					// Sanitize numeric fields
					if ( isset( $prompt['photos'] ) ) {
						$data['prompts'][ $index ]['photos'] = absint( $prompt['photos'] );
					}

					// Sanitize boolean fields
					if ( isset( $prompt['active'] ) ) {
						$data['prompts'][ $index ]['active'] = (bool) $prompt['active'];
					}

					if ( isset( $prompt['auto_publish'] ) ) {
						$data['prompts'][ $index ]['auto_publish'] = (bool) $prompt['auto_publish'];
					}

					// Sanitize prompt_id
					if ( isset( $prompt['prompt_id'] ) ) {
						$data['prompts'][ $index ]['prompt_id'] = sanitize_text_field( $prompt['prompt_id'] );
					}
				}
			}
		}

		return $data;
	}

	/**
	 * Sanitize text content for JSON storage.
	 *
	 * @param string $text The text to sanitize.
	 * @return string Sanitized text.
	 */
	private function sanitize_text_for_json( $text ) {
		if ( ! is_string( $text ) ) {
			return '';
		}

		// First sanitize the text content
		$text = sanitize_textarea_field( $text );

		// Normalize whitespace but preserve line breaks for better readability
		$text = preg_replace( '/[ \t]+/', ' ', $text ); // Normalize spaces and tabs
		$text = preg_replace( '/\r\n|\r|\n/', "\n", $text ); // Normalize line endings to \n
		$text = trim( $text ); // Remove leading/trailing whitespace

		// Don't escape JSON characters here - let wp_json_encode handle it properly
		// This prevents double-escaping and maintains proper JSON structure
		
		return $text;
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

			// Get system_content from form if provided
			$system_content = isset( $_POST['system_content'] ) ? sanitize_textarea_field( wp_unslash( $_POST['system_content'] ) ) : '';

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

			// Handle default_settings - prioritize form input over existing data
			$existing_settings = get_option( 'aistma_prompts', '{}' );
			$existing_data = json_decode( $existing_settings, true );
			
			// Initialize default_settings
			if ( ! isset( $updated_prompts['default_settings'] ) ) {
				$updated_prompts['default_settings'] = array();
			}
			
			// Use system_content from form if provided, otherwise use existing or default
			if ( ! empty( $system_content ) ) {
				$updated_prompts['default_settings']['system_content'] = $system_content;
			} elseif ( ! isset( $updated_prompts['default_settings']['system_content'] ) ) {
				// Use existing system_content if available
				if ( is_array( $existing_data ) && isset( $existing_data['default_settings']['system_content'] ) ) {
					$updated_prompts['default_settings']['system_content'] = $existing_data['default_settings']['system_content'];
				} else {
					// Use default system_content
					$updated_prompts['default_settings']['system_content'] = 'Write clearly and engagingly, keeping it simple and accurate — only add details when requested.';
				}
			}

			// Sanitize the data before validation and saving
			$updated_prompts = $this->sanitize_prompts_data( $updated_prompts );

			// Validate before saving
			$validation_result = $this->validate_prompts_data( $updated_prompts );
			if ( ! $validation_result['valid'] ) {
				echo '<div id="aistma-notice" class="notice notice-error"><p>❌ ' .
				esc_html__( 'Validation Error: ', 'ai-story-maker' ) . esc_html( $validation_result['message'] ) .
				'</p></div>';
				
				$this->aistma_log_manager->log( 'error', 'Validation failed: ' . $validation_result['message'] );
				// Don't return here, continue to render the form with the data
			} else {
				// Use wp_json_encode for proper JSON encoding with escaping
				update_option( 'aistma_prompts', wp_json_encode( $updated_prompts ) );

				echo '<div id="aistma-notice" class="notice notice-info"><p>✅ ' .
				esc_html__( 'Prompts saved successfully!', 'ai-story-maker' ) .
				'</p></div>';

				$this->aistma_log_manager->log( 'info', 'Prompts saved successfully with sanitization applied.' );
			}
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
				'text'         => 'Write your prompt here.. ',
				'category'     => '',
				'photos'       => 0,
				'active'       => true,
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
