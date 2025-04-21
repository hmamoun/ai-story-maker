<?php
/*
    * AI Story Maker 
    * Prompt Editor class
    * Description: This class handles the admin prompt editor page for managing story prompts.
    
 * This plugin is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 */

namespace exedotcom\aistorymaker;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class AISTMA_prompt_editor
 *
 * Handles the admin prompt editor page for managing story prompts.
 */
class AISTMA_prompt_editor {
    protected $aistma_log_manager;

    /**
     * Constructor initializes the prompt editor.
     */
    public function __construct() {
        $this->aistma_log_manager = new AISTMA_Log_Manager();

    }
    /**
     * Renders the Prompt Editor admin page.
     */
    public function render() {
        // Process form submission (business logic stays in the class)
        if ( isset( $_SERVER['REQUEST_METHOD'] ) && $_SERVER['REQUEST_METHOD'] === 'POST' && isset( $_POST['save_prompts_v2'] ) ) {
            check_admin_referer( 'save_story_prompts', 'story_prompts_nonce' );
            $raw_prompts_input = isset( $_POST['prompts'] ) ? sanitize_textarea_field( wp_unslash( $_POST['prompts'] ) ) : '';
            $updated_prompts   = $raw_prompts_input ? json_decode( $raw_prompts_input, true ) : [];
            update_option( 'ai_story_prompts', json_encode( $updated_prompts, JSON_PRETTY_PRINT ) );

            echo '<div id="ai-story-maker-messages" class="notice notice-info"><p>✅ ' 
                . esc_html__( 'Prompts saved successfully!', 'ai-story-maker' ) 
                . '</p></div>';

            $this->aistma_log_manager->log( 'info', 'Prompts saved successfully.' );
        }

        // Gather data for the view.
        $raw_json         = get_option( 'ai_story_prompts', '{}' );
        $settings         = json_decode( $raw_json, true );
        $prompts          = isset( $settings['prompts'] ) ? $settings['prompts'] : [];
        $default_settings = isset( $settings['default_settings'] ) ? $settings['default_settings'] : [];
        $categories       = get_categories( array( 'hide_empty' => false ) );

        if ( ! is_array( $prompts ) ) {
            $prompts = [];
        }
        if ( count( $prompts ) === 0 ) {
            $prompts[] = [
                'text'     => 'Write your first prompt here.. ',
                'category' => '',
                'photos'   => 0,
                'active'   => false,
                'auto_publish' => false,
            ];
        }

        // Prepare an associative array with the needed variables.
        $data = compact( 'prompts', 'default_settings', 'categories' );

        // Load the view template.
        include AI_STORY_MAKER_PATH . 'admin/templates/prompt-editor-template.php';
    }
}