<?php
/*

Plugin URI: https://github.com/hmamoun/ai-story-maker/wiki
Description: AI-powered content generator for WordPress — create engaging stories with a single click.
Version: 0.1.0
Author: Hayan Mamoun
Author URI: https://exedotcom.ca
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Text Domain: ai-story-maker
Domain Path: /languages
Requires PHP: 7.4
Requires at least: 5.8
Tested up to: 6.7
*/
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
<div class="wrap">
    <div class="aistma-style-settings">
    <h2>AI Story Settings</h2>
  <p>
    Use the this part to choselect a default AI model (e.g., GPT-4) and define the <strong>Instructions</strong> a set of instructions that will apply to all prompts. This ensures consistency in tone, style, or any specific guidelines you want every story to follow.
  </p>
  

  


        <?php wp_nonce_field( 'save_story_prompts', 'story_prompts_nonce' ); ?>

            <div>
                <label for="model"><?php esc_html_e( 'Model', 'ai-story-maker' ); ?></label>
                <select name="model" id="model">
                    <option value="gpt-4o-mini" <?php selected( $data['default_settings']['model'] ?? '', 'gpt-4o-mini' ); ?>>GPT-4o Mini</option>
                    <option value="gpt-4o" <?php selected( $data['default_settings']['model'] ?? '', 'gpt-4o' ); ?>>GPT-4o</option>
                    
                </select>
            </div>
            <div>
                <label for="system_content"><?php esc_html_e( 'General Instructions', 'ai-story-maker' ); ?></label>
                <textarea name="system_content" id="system_content" rows="5" style="width: 100%;"><?php echo esc_textarea( $data['default_settings']['system_content'] ?? '' ); ?></textarea>
            </div>
            <h2>Prompt List</h2>
  <p>
    Below, you can create and manage multiple prompts. Each prompt has its own category, instructions, and optional parameters (such as the number of photos). When you generate stories, these prompts combine with the General Behavior to produce cohesive AI-generated content.
  </p>
        <table class="wp-list-table widefat fixed striped" border="1">
            <thead>
                <tr>
                    <th><?php esc_html_e( 'Prompt', 'ai-story-maker' ); ?></th>
                    <th width="10%"><?php esc_html_e( 'Category', 'ai-story-maker' ); ?></th>
                    <th width="5%"><?php esc_html_e( 'Photos Count', 'ai-story-maker' ); ?></th>
                    <th width="5%"><?php esc_html_e( 'Active Prompt', 'ai-story-maker' ); ?></th>
                    <th width="5%"><?php esc_html_e( 'Auto Publish Post', 'ai-story-maker' ); ?></th>
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
                                    <option value="<?php echo esc_attr( $cat->name ); ?>" <?php selected( $prompt['category'] ?? '', $cat->name ); ?>>
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
                            <input type="checkbox" class="toggle-active" data-field="auto_publish" <?php checked( $prompt['auto_publish'] ?? 0, "1" ); ?> />
                        </td>
                        <td>
                            <button class="delete-prompt button button-danger"><?php esc_html_e( 'Delete', 'ai-story-maker' ); ?></button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <button id="add-prompt" class="button button-primary" ><?php esc_html_e( 'Add a new prompt', 'ai-story-maker' ); ?></button>

        <form method="POST" id="prompt-form">
            <?php wp_nonce_field( 'save_story_prompts', 'story_prompts_nonce' ); ?>
            <input type="hidden" name="prompts" id="prompts-data" value="">
            <input type="hidden" id="generate-story-nonce" value="<?php echo esc_attr( wp_create_nonce( 'generate_story_nonce' ) ); ?>">
            <input type="submit" name="save_prompts_v2" value="<?php esc_attr_e( 'Save Prompts', 'ai-story-maker' ); ?>" class="button button-primary">

        </form>
                                    <hr>
                                    <div class="pre-generate-info">
  <p>
    Please review your general settings and prompts below. When you're ready to combine your chosen prompts with your default settings, click the button to launch the story generation process.
  </p>
  <p>
    You can always check the next scheduled generation time in the <strong>AI Story Maker</strong> tab.
  </p>
</div>             
<?php
$is_generating = get_transient( 'aistma_generating_lock' );
$button_disabled = $is_generating ? 'disabled' : '';
$button_text = $is_generating
    ? __( 'Story generation in progress [recheck in 10 minutes]', 'ai-story-maker' )
    : __( 'Generate AI Stories', 'ai-story-maker' );
?>

<button
    id="aistma-generate-stories-button"
    class="button button-primary"
    <?php echo esc_html($button_disabled); ?>
>
    <?php echo esc_html( $button_text ); ?>
</button>

</div>


</div>
