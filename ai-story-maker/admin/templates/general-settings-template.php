<?php
/**
 * Template for the general settings page.
 *
 * @package AI_Story_Maker
 * @since   0.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
?>
<div class="wrap">
			<form method="POST" class="aistma-style-settings">
				<?php wp_nonce_field( 'save_story_maker_settings', 'story_maker_nonce' ); ?>
				<h2><?php esc_html_e( 'API Keys', 'ai-story-maker' ); ?></h2>
				<p>
					<?php esc_html_e( 'AI Story Maker integrates with OpenAI and Unsplash APIs to generate content and images. Please enter your API keys below. Registration may be required to obtain them.', 'ai-story-maker' ); ?>
				</p>
				<label for="aistma_openai_api_key">
					<?php esc_html_e( 'OpenAI', 'ai-story-maker' ); ?> <a href="https://platform.openai.com/" target="_blank"><?php esc_html_e( 'API', 'ai-story-maker' ); ?></a> <?php esc_html_e( 'Key:', 'ai-story-maker' ); ?>
				</label>
				<input type="text" name="aistma_openai_api_key" placeholder="<?php esc_attr_e( 'OpenAI API Key', 'ai-story-maker' ); ?>" value="<?php echo esc_attr( get_option( 'aistma_openai_api_key' ) ); ?>">
				<label for="aistma_unsplash_api_key">
					<?php esc_html_e( 'Unsplash', 'ai-story-maker' ); ?> <a href="https://unsplash.com/developers" target="_blank"><?php esc_html_e( 'API Key and Secret', 'ai-story-maker' ); ?></a>:
				</label>
				<div class="inline-fields">
					<label for="aistma_unsplash_api_key"><?php esc_html_e( 'Key:', 'ai-story-maker' ); ?></label>
					<input type="text" name="aistma_unsplash_api_key" placeholder="<?php esc_attr_e( 'Key', 'ai-story-maker' ); ?>" value="<?php echo esc_attr( get_option( 'aistma_unsplash_api_key' ) ); ?>">
					<label for="aistma_unsplash_api_secret"><?php esc_html_e( 'Secret:', 'ai-story-maker' ); ?></label>
					<input type="text" name="aistma_unsplash_api_secret" placeholder="<?php esc_attr_e( 'Secret', 'ai-story-maker' ); ?>" value="<?php echo esc_attr( get_option( 'aistma_unsplash_api_secret' ) ); ?>">
				</div>

				<h2><?php esc_html_e( 'Subscription Settings', 'ai-story-maker' ); ?></h2>
				<p>
					<?php esc_html_e( 'AI Story Maker offers a subscription service to access premium features. Please enter your subscription key below.', 'ai-story-maker' ); ?>
				</p>
				<?php
					$is_dev     = defined( 'WORDPRESS_ENV' ) && WORDPRESS_ENV === 'development';
					$slug       = 'ai-story-maker-plans';
					$base_url   = $is_dev ? get_site_url( null, $slug . '/' ) : 'https://www.exedotcom.ca/' . $slug . '/';
					$action_url = add_query_arg(
						array(
							'domain' => rawurlencode( get_site_url() ),
						),
						$base_url
					);
					?>
					<a href="<?php echo esc_url( $action_url ); ?>" target="_blank" class="button button-primary">
						<?php esc_html_e( 'Buy Credits', 'ai-story-maker' ); ?>
					</a>
					<input type="submit" name="save_settings" value="<?php esc_attr_e( 'Save', 'ai-story-maker' ); ?>" class="button button-primary submit-button">
					</form>
					<form method="POST" class="aistma-style-settings">
					<?php wp_nonce_field( 'save_story_maker_settings', 'story_maker_nonce' ); ?>
				
				
					<h2><?php esc_html_e( 'Settings', 'ai-story-maker' ); ?></h2>
				<table class="form-table">
					<tr>
						<td>
				<label for="aistma_clear_log_cron"><?php esc_html_e( 'Log Retention (Days):', 'ai-story-maker' ); ?></label>
				<p>
					<?php
					printf(
					/* translators: %s: link to log page */
						wp_kses_post( __( 'AI Story Maker maintains a detailed log of its activities. Choose how many days to retain the logs, or set to 0 to keep them indefinitely. You can view the log <a href="%s">here</a>.', 'ai-story-maker' ) ),
						esc_url( admin_url( 'admin.php?page=aistma-settings&tab=log' ) )
					);

					?>
				</p>
				<select name="aistma_clear_log_cron">
					<?php for ( $i = 0; $i <= 30; $i++ ) : ?>
						<option value="<?php echo esc_attr( $i ); ?>" <?php selected( get_option( 'aistma_clear_log_cron' ), $i ); ?>>
						<?php echo esc_attr( $i ); ?> <?php esc_html_e( 'Day(s)', 'ai-story-maker' ); ?>
						</option>
					<?php endfor; ?>
				</select>

				</td>
				<td>
				<label for="aistma_generate_story_cron"><?php esc_html_e( 'Generate New Stories Every (Days):', 'ai-story-maker' ); ?></label>
				<p>
					<?php esc_html_e( 'AI Story Maker can automatically generate new stories at regular intervals. Set to 0 to disable scheduled generation.', 'ai-story-maker' ); ?>
				</p>
				<select name="aistma_generate_story_cron">
					<?php for ( $i = 0; $i <= 30; $i++ ) : ?>
						<option value="<?php echo esc_attr( $i ); ?>" <?php selected( get_option( 'aistma_generate_story_cron' ), $i ); ?>>
						<?php echo esc_attr( $i ); ?> <?php esc_html_e( 'Day(s)', 'ai-story-maker' ); ?>
						</option>
					<?php endfor; ?>
				</select>
</td>
<td>
						
				<label for="aistma_opt_auther"><?php esc_html_e( 'Select Story Author:', 'ai-story-maker' ); ?></label>
				<p>
					<?php esc_html_e( 'Select the author for AI-generated stories. If you need to create a new author, you can do so', 'ai-story-maker' ); ?>
					<a href="<?php echo esc_url( admin_url( 'user-new.php?role=author' ) ); ?>" target="_blank"><?php esc_html_e( 'here', 'ai-story-maker' ); ?></a>.
					<?php esc_html_e( 'Ensure the role is set to "Author".', 'ai-story-maker' ); ?>
				</p>
				<select name="aistma_opt_auther">
					<?php
					$users = get_users( array( 'role__in' => array( 'author', 'administrator' ) ) );
					foreach ( $users as $user ) :
						?>
						<option value="<?php echo esc_attr( $user->ID ); ?>" <?php selected( get_option( 'aistma_opt_auther' ), $user->ID ); ?>>
						<?php echo esc_html( $user->display_name ); ?>
						</option>
					<?php endforeach; ?>
				</select>
				</td></tr>
						<tr>
							<td>
				<p>
					<label for="aistma_show_ai_attribution">
						<input type="checkbox" name="aistma_show_ai_attribution" id="aistma_show_ai_attribution" value="1" <?php checked( get_option( 'aistma_show_ai_attribution', 1 ), 1 ); ?> />
						<?php esc_html_e( 'Show "Generated by AI" attribution at the end of each story', 'ai-story-maker' ); ?>
					</label>
				</p>
	
				<p style="margin-top: -10px; font-size: 12px; color: #666;">
					<?php esc_html_e( 'Recommended: Keeping this enabled promotes transparency and trust with your readers.', 'ai-story-maker' ); ?>
					<?php esc_html_e( 'Note: Future regulations may require disclosure of AI-generated content.', 'ai-story-maker' ); ?>
				</p>
			</td><td>

				<p>
					<label for="aistma_show_exedotcom_attribution">
						<input type="checkbox" name="aistma_show_exedotcom_attribution" id="aistma_show_exedotcom_attribution" value="1" <?php checked( get_option( 'aistma_show_exedotcom_attribution', 0 ), 1 ); ?> />
						<?php esc_html_e( 'Show "Created by AI Story Maker" attribution', 'ai-story-maker' ); ?>
					</label>
				</p>
				<p style="margin-top: -10px; font-size: 12px; color: #666;">
					<?php esc_html_e( 'Support our work by showing a small attribution link to Exedotcom.ca. This helps us continue developing and improving AI Story Maker.', 'ai-story-maker' ); ?>
					<?php esc_html_e( 'Your support means a lot to us!', 'ai-story-maker' ); ?>
				</p>

</td></tr></table>
				<input type="submit" name="save_settings" value="<?php esc_attr_e( 'Save', 'ai-story-maker' ); ?>" class="button button-primary submit-button">
			</form>
		</div>
<?php
