<?php
/**
 * class: Setting page for AI Story Maker
 * description: This class handles the rendering and processing of the AI Story Maker settings page.
 * 
 * Plugin Name: AI Story Maker
 * Plugin URI: https://github.com/hmamoun/ai-story-maker/wiki
 * Description: AI-powered WordPress plugin that generates engaging stories, articles, and images using Large Language Models.
 * Version: 1.0
 * Author: Hayan Mamoun
 * Author URI: https://exedotcom.ca
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: ai-story-maker
 * Domain Path: /languages
 * Requires PHP: 7.4
 * Requires at least: 5.0
 * Tested up to: 6.7
 */
namespace AI_Story_Maker;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Settings_Page
 *
 * Handles the rendering and processing of the AI Story Maker settings page.
 */
class Settings_Page {
	protected $log_manager;
	/**
	 * Constructor initializes the settings page.
	 */
	public function __construct() {
		$this->log_manager = new Log_Manager();

	}
	/**
	 * Renders the settings page.
	 */
	public function render() {

		// Process form submission for saving settings.
		if ( isset( $_POST['save_settings'] ) ) {
			$story_maker_nonce = isset( $_POST['story_maker_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['story_maker_nonce'] ) ) : '';

			if ( ! $story_maker_nonce || ! wp_verify_nonce( $story_maker_nonce, 'save_story_maker_settings' ) ) {
				echo '<div class="error"><p>❌ ' . esc_html__( 'Security check failed. Please try again.', 'ai-story-maker' ) . '</p></div>';
				$this->log_manager->log(  'error', '❌ Security check failed. Please try again.' );
				return;
			}

			if ( ! isset( $_POST['openai_api_key'] ) || API_Keys::validate_openai_api_key( sanitize_text_field( wp_unslash( $_POST['openai_api_key'] ) ) ) === false ) {
				echo '<div class="error"><p>❌ ' . esc_html__( 'Invalid OpenAI API key.', 'ai-story-maker' ) . '</p></div>';
				$this->log_manager->log(  'error', '❌ Invalid OpenAI API key.' );
				return;
			}

			if (
				isset( $_POST['opt_ai_storymaker_clear_log'] ) &&
				get_option( 'opt_ai_storymaker_clear_log' ) !== sanitize_text_field( wp_unslash( $_POST['opt_ai_storymaker_clear_log'] ) )
			) {
				wp_clear_scheduled_hook( 'schd_ai_story_maker_clear_log' );
			}

			// Update API keys and options.
			if ( isset( $_POST['openai_api_key'] ) ) {
				update_option( 'openai_api_key', sanitize_text_field( wp_unslash( $_POST['openai_api_key'] ) ) );
			}
			if ( isset( $_POST['unsplash_api_key'] ) ) {
				update_option( 'unsplash_api_key', sanitize_text_field( wp_unslash( $_POST['unsplash_api_key'] ) ) );
			}
			if ( isset( $_POST['unsplash_api_secret'] ) ) {
				update_option( 'unsplash_api_secret', sanitize_text_field( wp_unslash( $_POST['unsplash_api_secret'] ) ) );
			}
			if ( isset( $_POST['opt_ai_storymaker_clear_log'] ) ) {
				update_option( 'opt_ai_storymaker_clear_log', sanitize_text_field( wp_unslash( $_POST['opt_ai_storymaker_clear_log'] ) ) );
			}
			// bmark Schedule in case of changing settings
			if ( isset( $_POST['opt_ai_story_repeat_interval_days'] ) ) {
				$interval = intval( sanitize_text_field( wp_unslash( $_POST['opt_ai_story_repeat_interval_days'] ) ) );
				$n = absint(get_option('opt_ai_story_repeat_interval_days'));
				// If the value is 0, clear the scheduled event.
				if ( sanitize_text_field( wp_unslash( $_POST['opt_ai_story_repeat_interval_days'] ) ) == 0 ) {
					wp_clear_scheduled_hook( 'ai_story_generator_repeating_event' );
				} 

				update_option( 'opt_ai_story_repeat_interval_days', sanitize_text_field( wp_unslash( $_POST['opt_ai_story_repeat_interval_days'] ) ) );
				if (  $n != $interval ) {
					wp_clear_scheduled_hook( 'ai_story_generator_repeating_event' );
					$generator = new Story_Generator();
					$generator->reschedule_cron_event();
					Log_Manager::log('info', 'Schedule changed via admin. Running updated check.');
					
					
				}
			}
			if ( isset( $_POST['opt_ai_story_auther'] ) ) {
				update_option( 'opt_ai_story_auther', intval( $_POST['opt_ai_story_auther'] ) );
			}

			echo '<div class="notice notice-info"><p>✅ ' . esc_html__( 'Settings saved!', 'ai-story-maker' ) . '</p></div>';
			$this->log_manager->log( 'info', 'Settings saved' );
		}
		?>
		<div class="wrap">
			<form method="POST" class="ai-storymaker-settings">
				<?php wp_nonce_field( 'save_story_maker_settings', 'story_maker_nonce' ); ?>
				<h2><?php esc_html_e( 'API Keys', 'ai-story-maker' ); ?></h2>
				<p>
					<?php esc_html_e( 'AI Story Maker integrates with OpenAI and Unsplash APIs to generate content and images. Please enter your API keys below. Registration may be required to obtain them.', 'ai-story-maker' ); ?>
				</p>
				<label for="openai_api_key">
					<?php esc_html_e( 'OpenAI', 'ai-story-maker' ); ?> <a href="https://platform.openai.com/" target="_blank"><?php esc_html_e( 'API', 'ai-story-maker' ); ?></a> <?php esc_html_e( 'Key:', 'ai-story-maker' ); ?>
				</label>
				<input type="text" name="openai_api_key" placeholder="<?php esc_attr_e( 'OpenAI API Key', 'ai-story-maker' ); ?>" value="<?php echo esc_attr( get_option( 'openai_api_key' ) ); ?>">
				<label for="unsplash_api_key">
					<?php esc_html_e( 'Unsplash', 'ai-story-maker' ); ?> <a href="https://unsplash.com/developers" target="_blank"><?php esc_html_e( 'API Key and Secret', 'ai-story-maker' ); ?></a>:
				</label>
				<div class="inline-fields">
					<label for="unsplash_api_key"><?php esc_html_e( 'Key:', 'ai-story-maker' ); ?></label>
					<input type="text" name="unsplash_api_key" placeholder="<?php esc_attr_e( 'Key', 'ai-story-maker' ); ?>" value="<?php echo esc_attr( get_option( 'unsplash_api_key' ) ); ?>">
					<label for="unsplash_api_secret"><?php esc_html_e( 'Secret:', 'ai-story-maker' ); ?></label>
					<input type="text" name="unsplash_api_secret" placeholder="<?php esc_attr_e( 'Secret', 'ai-story-maker' ); ?>" value="<?php echo esc_attr( get_option( 'unsplash_api_secret' ) ); ?>">
				</div>

				<h2><?php esc_html_e( 'Story Generation Settings', 'ai-story-maker' ); ?></h2>
				<label for="opt_ai_storymaker_clear_log"><?php esc_html_e( 'Log Retention (Days):', 'ai-story-maker' ); ?></label>
				<p>
					<?php
					printf(
						/* translators: %s: link to log page */
						esc_html__( 'AI Story Maker maintains a detailed log of its activities. Choose how many days to retain the logs, or set to 0 to keep them indefinitely. You can view the log <a href="%s">here</a>.', 'ai-story-maker' ),
						esc_url( admin_url( 'admin.php?page=ai-storymaker-logs' ) )
					);
					?>
				</p>
				<select name="opt_ai_storymaker_clear_log">
					<?php for ( $i = 0; $i <= 30; $i++ ) : ?>
						<option value="<?php echo esc_attr( $i ); ?>" <?php selected( get_option( 'opt_ai_storymaker_clear_log' ), $i ); ?>>
							<?php  echo esc_attr( $i ); ?> <?php esc_html_e( 'Day(s)', 'ai-story-maker' ); ?>
						</option>
					<?php endfor; ?>
				</select>
				<hr>
				<label for="opt_ai_story_repeat_interval_days"><?php esc_html_e( 'Generate New Stories Every (Days):', 'ai-story-maker' ); ?></label>
				<p>
					<?php esc_html_e( 'AI Story Maker can automatically generate new stories at regular intervals. Set to 0 to disable scheduled generation.', 'ai-story-maker' ); ?>
				</p>
				<select name="opt_ai_story_repeat_interval_days">
					<?php for ( $i = 0; $i <= 30; $i++ ) : ?>
						<option value="<?php  echo esc_attr( $i ); ?>" <?php selected( get_option( 'opt_ai_story_repeat_interval_days' ), $i ); ?>>
							<?php  echo esc_attr( $i ); ?> <?php esc_html_e( 'Day(s)', 'ai-story-maker' ); ?>
						</option>
					<?php endfor; ?>
				</select>
				<hr>
				<label for="opt_ai_story_auther"><?php esc_html_e( 'Select Story Author:', 'ai-story-maker' ); ?></label>
				<p>
					<?php esc_html_e( 'Select the author for AI-generated stories. If you need to create a new author, you can do so', 'ai-story-maker' ); ?>
					<a href="<?php echo esc_url( admin_url( 'user-new.php?role=author' ) ); ?>" target="_blank"><?php esc_html_e( 'here', 'ai-story-maker' ); ?></a>.
					<?php esc_html_e( 'Ensure the role is set to "Author".', 'ai-story-maker' ); ?>
				</p>
				<select name="opt_ai_story_auther">
					<?php
					$users = get_users( array( 'role__in' => array( 'author', 'administrator' ) ) );
					foreach ( $users as $user ) :
						?>
						<option value="<?php echo esc_attr( $user->ID ); ?>" <?php selected( get_option( 'opt_ai_story_auther' ), $user->ID ); ?>>
							<?php echo esc_html( $user->display_name ); ?>
						</option>
					<?php endforeach; ?>
				</select>
						<hr>
				<input type="submit" name="save_settings" value="<?php esc_attr_e( 'Save Settings', 'ai-story-maker' ); ?>" class="button button-primary submit-button">
			</form>
		</div>
		<?php
	}
}