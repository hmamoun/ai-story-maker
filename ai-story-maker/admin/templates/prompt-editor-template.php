<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Now use the $data array to access your values.
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
                        <option value="gpt-4o-mini" <?php selected( $data['default_settings']['model'] ?? '', 'gpt-4o-mini' ); ?>>GPT-4o Mini</option>
                        <option value="gpt-4o" <?php selected( $data['default_settings']['model'] ?? '', 'gpt-4o' ); ?>>GPT-4o</option>
                        <option value="gpt-4o-large" <?php selected( $data['default_settings']['model'] ?? '', 'gpt-4o-large' ); ?>>GPT-4o Large</option>
                    </select>
                </td>
                <td>
                    <textarea name="system_content" id="system_content" rows="5" style="width: 100%;"><?php echo esc_textarea( $data['default_settings']['system_content'] ?? '' ); ?></textarea>
                </td>
            </tr>
        </table>
        <hr>
        <h2><?php esc_html_e( 'Story Prompts', 'ai-story-maker' ); ?></h2>
        <p><?php esc_html_e( 'Add or remove prompts to generate multiple stories. Each prompt can have:', 'ai-story-maker' ); ?></p>
        <ul>
            <li><?php esc_html_e( 'A category (choose one from your WordPress categories).', 'ai-story-maker' ); ?></li>
            <li><?php esc_html_e( 'The number of photos to include.', 'ai-story-maker' ); ?></li>
            <li><?php esc_html_e( 'An active or inactive status (inactive prompts are ignored).', 'ai-story-maker' ); ?></li>
        </ul>
        <p><?php esc_html_e( 'Use "Add Prompt" to create a new prompt, and "Delete" to remove one. Click "Save Changes" to update the prompt list. Click "Generate Story" on a specific prompt to create a story immediately.', 'ai-story-maker' ); ?></p>

        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th><?php esc_html_e( 'Prompt', 'ai-story-maker' ); ?></th>
                    <th width="10%"><?php esc_html_e( 'Category', 'ai-story-maker' ); ?></th>
                    <th width="5%"><?php esc_html_e( 'No. of Photos', 'ai-story-maker' ); ?></th>
                    <th width="5%"><?php esc_html_e( 'Active', 'ai-story-maker' ); ?></th>
                    <th width="10%"><?php esc_html_e( 'Actions', 'ai-story-maker' ); ?></th>
                </tr>
            </thead>
            <tbody id="prompt-list">
                <?php foreach ( $data['prompts'] as $index => $prompt ) : ?>
                    <tr data-index="<?php echo esc_attr( $index ); ?>">
                        <input type="hidden" data-field="prompt_id" id="prompt_id" value="<?php echo esc_attr( $prompt['prompt_id'] ?? wp_generate_uuid4() ); ?>">
                        <td contenteditable="true" class="editable" data-field="text"><?php echo esc_html( $prompt['text'] ?? '' ); ?></td>
                        <td contenteditable="true" data-field="category">
                            <select name="category">
                                <?php foreach ( $data['categories'] as $cat ) : ?>
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
                            <button class="delete-prompt button button-danger"><?php esc_html_e( 'Delete', 'ai-story-maker' ); ?></button>
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
            <input type="submit" name="save_prompts_v2" value="<?php esc_attr_e( 'Save Prompts', 'ai-story-maker' ); ?>" class="button button-primary">
            <button id="make-stories-button" class="button button-primary"><?php esc_html_e( 'Generate Active Stories', 'ai-story-maker' ); ?></button>
        </form>

                                    

    </div>


</div>
