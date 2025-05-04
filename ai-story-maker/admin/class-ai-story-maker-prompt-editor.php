<?php
/**
 * Prompt Editor for AI Story Maker.
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
        // Handle prompt submission
        if ( isset( $_SERVER['REQUEST_METHOD'] ) && $_SERVER['REQUEST_METHOD'] === 'POST' && isset( $_POST['save_prompts_v2'] ) ) {
            check_admin_referer( 'save_story_prompts', 'story_prompts_nonce' );

            $raw_prompts_input = isset( $_POST['prompts'] ) ? sanitize_textarea_field( wp_unslash( $_POST['prompts'] ) ) : '';
            $updated_prompts   = $raw_prompts_input ? json_decode( $raw_prompts_input, true ) : [];

            update_option( 'aistma_prompts', json_encode( $updated_prompts, JSON_PRETTY_PRINT ) );

            echo '<div id="aistma-notice" class="notice notice-info"><p>✅ ' .
                esc_html__( 'Prompts saved successfully!', 'ai-story-maker' ) .
                '</p></div>';

            $this->aistma_log_manager->log( 'info', 'Prompts saved successfully.' );
        }

        // Prepare data for rendering
        $raw_json         = get_option( 'aistma_prompts', '{}' );
        $settings         = json_decode( $raw_json, true );
        $prompts          = isset( $settings['prompts'] ) ? $settings['prompts'] : [];
        $default_settings = isset( $settings['default_settings'] ) ? $settings['default_settings'] : [];
        $categories       = get_categories( array( 'hide_empty' => false ) );

        if ( ! is_array( $prompts ) ) {
            $prompts = [];
        }

        if ( count( $prompts ) === 0 ) {
            $prompts[] = [
                'text'          => 'Write your first prompt here.. ',
                'category'      => '',
                'photos'        => 0,
                'active'        => false,
                'auto_publish'  => false,
            ];
        }

        // Make variables available to the template
        $data = compact( 'prompts', 'default_settings', 'categories' );

        // Load view
        include AI_STORY_MAKER_PATH . 'admin/templates/prompt-editor-template.php';
    }
}