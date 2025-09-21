<?php
/**
 * Prompt Editor Template for AI Story Maker.
 *
 * @package AI_Story_Maker
 * @author  Hayan Mamoun
 * @license GPLv2 or later
 * @link    https://github.com/hmamoun/ai-story-maker/wiki
 * @since   0.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="wrap">
	<div class="aistma-style-settings">
	<h2>AI Story Settings</h2>
		<?php wp_nonce_field( 'save_story_prompts', 'story_prompts_nonce' ); ?>

			<!-- Model selection hidden but still needed for JavaScript -->
			<input type="hidden" name="model" id="model" value="<?php echo esc_attr( $data['default_settings']['model'] ?? 'gpt-4o-mini' ); ?>">
			<div>
				<label for="system_content"><?php esc_html_e( 'General Instructions', 'ai-story-maker' ); ?></label>
				<textarea name="system_content" id="system_content" rows="5" style="width: 100%;"><?php echo esc_textarea( $data['default_settings']['system_content'] ?? '' ); ?></textarea>
			</div>
			<h2>Prompt List</h2>

		<table class="wp-list-table widefat fixed striped" border="1">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Prompt', 'ai-story-maker' ); ?></th>
					<th width="10%">
						<?php esc_html_e( 'Category', 'ai-story-maker' ); ?>
						<br>
						<small>
							<a href="<?php echo esc_url( admin_url( 'edit-tags.php?taxonomy=category' ) ); ?>" target="_blank" style="text-decoration: none; color: #0073aa;">
								<?php esc_html_e( 'Manage Categories', 'ai-story-maker' ); ?>
							</a>
						</small>
					</th>
					<th width="5%"><?php esc_html_e( 'Images per Post', 'ai-story-maker' ); ?></th>
					<th width="5%"><?php esc_html_e( 'Active', 'ai-story-maker' ); ?></th>
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
								<?php foreach ( $data['categories'] as $category_obj ) : ?>
									<option value="<?php echo esc_attr( $category_obj->name ); ?>" <?php selected( $prompt['category'] ?? '', $category_obj->name ); ?>>
									<?php echo esc_html( $category_obj->name ); ?>
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
							<input type="checkbox" class="toggle-active" data-field="active" <?php checked( $prompt['active'] ?? 0, '1' ); ?> />
						</td>
						<td>
							<input type="checkbox" class="toggle-active" data-field="auto_publish" <?php checked( $prompt['auto_publish'] ?? 0, '1' ); ?> />
						</td>
						<td>
							<button class="delete-prompt button button-danger"><?php esc_html_e( 'Delete', 'ai-story-maker' ); ?></button>
						</td>
					</tr>
				<?php endforeach; ?>
				<tr>
					<td colspan="6" style="text-align: right; padding: 20px;">
						<button id="add-prompt" class="button button-primary"><?php esc_html_e( 'Add a new prompt', 'ai-story-maker' ); ?></button>
					</td>
				</tr>
			</tbody>
		</table>
<br>
		<form method="POST" id="prompt-form">
			<?php wp_nonce_field( 'save_story_prompts', 'story_prompts_nonce' ); ?>
			<input type="hidden" name="prompts" id="prompts-data" value="">
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
<?php // Generation controls moved to a reusable template included globally. ?>


</div>
