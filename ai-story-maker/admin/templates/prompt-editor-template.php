<?php
/*
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
// admin/templates/prompt-editor-template.php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Assume these variables are set before including the template:
// $prompts, $default_settings, $categories

?>
<div class="wrap">
    <div class="ai-storymaker-settings">
        <h2><?php esc_html_e( 'Story Generation Settings', 'ai-story-maker' ); ?></h2>
        <?php wp_nonce_field( 'save_story_prompts', 'story_prompts_nonce' ); ?>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th width="15%"><?php esc_html_e( 'Model', 'ai-story-maker' ); ?></th>
                    <th><?php esc_html_e( 'General Behaviour', 'ai-story-maker' ); ?></th>
                </tr>
            </thead>
            <tr>
                <td>
                    <select name="model" id="model">
                        <option value="gpt-4o-mini" <?php selected( $default_settings['model'] ?? '', 'gpt-4o-mini' ); ?>>GPT-4o Mini</option>
                        <option value="gpt-4o" <?php selected( $default_settings['model'] ?? '', 'gpt-4o' ); ?>>GPT-4o</option>
                        <option value="gpt-4o-large" <?php selected( $default_settings['model'] ?? '', 'gpt-4o-large' ); ?>>GPT-4o Large</option>
                    </select>
                </td>
                <td>
                    <textarea name="system_content" id="system_content" rows="5" style="width: 100%;"><?php echo esc_textarea( $default_settings['system_content'] ?? '' ); ?></textarea>
                </td>
            </tr>
        </table>
        <hr>
        <h2><?php esc_html_e( 'Story Prompts', 'ai-story-maker' ); ?></h2>
        <p>
            Add or remove prompts to generate multiple stories.  
            Each prompt can have:
        </p>
        <ul>
            <li>A category (choose one from your WordPress categories).</li>
            <li>The number of photos to include.</li>
            <li>An active or inactive status (inactive prompts are ignored).</li>
        </ul>
        <p>
            Use “Add Prompt” to create a new prompt, and “Delete” to remove one.  
            Click “Save Changes” to update the prompt list.  
            Click “Generate Story” on a specific prompt to create a story immediately.
        </p>

        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th ><?php esc_html_e( 'Prompt', 'ai-story-maker' ); ?></th>
                    <th width="10%"><?php esc_html_e( 'Category', 'ai-story-maker' ); ?></th>
                    <th width="5%"><?php esc_html_e( 'No. of Photos', 'ai-story-maker' ); ?></th>
                    <th width="5%"><?php esc_html_e( 'Active', 'ai-story-maker' ); ?></th>
                    <th width="10%"><?php esc_html_e( 'Actions', 'ai-story-maker' ); ?></th>
                </tr>
            </thead>
            <tbody id="prompt-list">
                <?php foreach ( $prompts as $index => $prompt ) : ?>
                    <tr data-index="<?php echo esc_attr( $index ); ?>">
                        <td contenteditable="true" class="editable" data-field="text"><?php echo esc_html( $prompt['text'] ?? '' ); ?></td>
                        <td contenteditable="true" data-field="category">
                            <select name="category">
                                <?php foreach ( $categories as $cat ) : ?>
                                    <option value="<?php echo esc_attr( $cat->slug ); ?>" <?php selected( $prompt['category'] ?? '', $cat->slug ); ?>>
                                        <?php echo esc_html( $cat->name ); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                        <td contenteditable="true" data-field="photos">
                            <select>
                                <option value="0" <?php selected( $prompt['photos'] ?? '', '0' ); ?>>0</option>
                                <option value="1" <?php selected( $prompt['photos'] ?? '', '1' ); ?>>1</option>
                                <option value="2" <?php selected( $prompt['photos'] ?? '', '2' ); ?>>2</option>
                                <option value="3" <?php selected( $prompt['photos'] ?? '', '3' ); ?>>3</option>
                            </select>
                        </td>
                        <td>
                            <input type="checkbox" class="toggle-active" data-field="active" <?php checked( $prompt['active'] ?? 0, "1" ); ?> />
                        </td>
                        <td>
                            <button class="delete-prompt button button-danger" ><?php esc_html_e( 'Delete', 'ai-story-maker' ); ?></button>
                            <!-- <button class="make-story button button-primary" data-index="<?php echo esc_attr( $index ); ?>"><?php esc_html_e( 'Make', 'ai-story-maker' ); ?></button> -->
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <button id="add-prompt" class="button button-primary"><?php esc_html_e( '+', 'ai-story-maker' ); ?></button>
        <hr>
        <form method="POST" class="ai-storymaker-settings" id="prompt-form">
            <?php wp_nonce_field( 'save_story_prompts', 'story_prompts_nonce' ); ?>
            <input type="hidden" name="prompts" id="prompts-data" value="">
            <input type="hidden" id="generate-story-nonce" value="<?php echo esc_attr( wp_create_nonce( 'generate_story_nonce' ) ); ?>">
            <input type="submit" name="save_prompts_v2" value="<?php esc_attr_e( 'Save Changes', 'ai-story-maker' ); ?>" class="button button-primary">
            <button id="make-stories-button" class="button button-primary"><?php esc_html_e( 'Generate Active Stories', 'ai-story-maker' ); ?></button>
        </form>
    </div>
    <h2>Schedule</h2>
    <p>
        <?php
        printf(
            /* translators: %s: next scheduled run, %s: remaining time */
            esc_html__( 'Stories will be generated when anyone visits the site after <strong>%s</strong> (%s remaining).', 'ai-story-maker' ),
            esc_html( $nextRun ),
            esc_html( $RemainingTime )
        );
        ?>
    </p>
</div>