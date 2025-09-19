<?php
/**
 * Welcome Tab Template for AI Story Maker.
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
	<h2>AI Story Maker</h2>
<p>
	AI Story Maker leverages OpenAI's advanced language models to automatically create engaging stories for your WordPress site.
	Getting started is easy — simply enter your API keys and set up your prompts.
</p>

<h3>Getting Started</h3>
<ul>
	<li>
		<strong>Settings:</strong> Enter your OpenAI and Unsplash API keys and configure your story generation preferences.
	</li>
	<li>
		<strong>Prompts:</strong> Visit the Prompts tab to create and manage the instructions that guide story generation.
	</li>
	<li>
		<strong>Shortcode:</strong> Use the <code>[aistma_scroller]</code> shortcode to display your AI-generated stories anywhere on your site.
	</li>
</ul>
<p>
	Generated stories are saved as WordPress posts. You can display them using the custom template included with the plugin or by embedding the shortcode into any page or post.
</p>

<h3>Easy to Use</h3>
<p>
	AI Story Maker is designed for simplicity and flexibility, making it easy for users of any skill level to start generating rich, AI-driven content within minutes.
</p>
<?php
$plugin_data = get_plugin_data( AISTMA_PATH . 'ai-story-maker.php' );
$version = $plugin_data['Version'];
?>
<h3>Future Enhancements</h3>
<p>
	This is version <?php echo esc_html( 	$version ); ?>. Future updates will bring support for additional AI models like Gemini, Grok, and DeepSeek,
	along with enhanced options for embedding premium-quality images from various sources, for full features list, please visit the <a href="https://exedotcom.ca/ai-story-maker/" target="_blank">AI Story Maker</a> website.
</p>


<div class="aistma-generate-stories-section">
	<h3><?php esc_html_e( 'Generate Stories Now', 'ai-story-maker' ); ?></h3>
	<p>
		<?php esc_html_e( 'Click the button below to manually generate AI stories using your configured prompts and settings.', 'ai-story-maker' ); ?>
	</p>
	
	<?php
	$is_generating   = get_transient( 'aistma_generating_lock' );
	$button_disabled = $is_generating ? 'disabled' : '';
	$button_text     = $is_generating
		? __( 'Story generation in progress [recheck in 10 minutes]', 'ai-story-maker' )
		: __( 'Generate AI Stories', 'ai-story-maker' );
	?>

	<input type="hidden" id="generate-story-nonce" value="<?php echo esc_attr( wp_create_nonce( 'generate_story_nonce' ) ); ?>">
	<button
		id="aistma-generate-stories-button"
		class="button button-primary"
		<?php echo esc_attr( $button_disabled ); ?>
	>
		<?php echo esc_html( $button_text ); ?>
	</button>
	
	<div class="aistma-analytics-promotion">
		<div style="background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%); padding: 20px; border-radius: 8px; text-align: center; margin-top: 20px; border: 1px solid #dee2e6;">
			<h4 style="margin: 0 0 10px 0; color: #495057;">
				📊 <?php esc_html_e('Analytics Dashboard', 'ai-story-maker'); ?>
			</h4>
			<p style="margin: 0 0 15px 0; color: #6c757d; font-size: 14px;">
				<?php esc_html_e('View comprehensive analytics and insights about your AI story generation.', 'ai-story-maker'); ?>
			</p>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=aistma-settings&tab=analytics' ) ); ?>" class="button button-primary">
				<?php esc_html_e('View Analytics', 'ai-story-maker'); ?>
			</a>
		</div>
	</div>
</div>

		<?php
		$next_event    = wp_next_scheduled( 'aistma_generate_story_event' );
		$is_generating = get_transient( 'aistma_generating_lock' );

		if ( $next_event ) {
			$time_diff = $next_event - time();
			$days      = floor( $time_diff / ( 60 * 60 * 24 ) );
			$hours     = floor( ( $time_diff % ( 60 * 60 * 24 ) ) / ( 60 * 60 ) );
			$minutes   = floor( ( $time_diff % ( 60 * 60 ) ) / 60 );

			$formatted_countdown = sprintf( '%dd %dh %dm', $days, $hours, $minutes );
			$formatted_datetime  = date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $next_event );

			?>
	<div class="notice notice-info">
		<strong>
			🕒 Next AI story generation scheduled in <?php echo esc_html( $formatted_countdown ); ?><br>
			📅 Scheduled for: <em><?php echo esc_html( $formatted_datetime ); ?></em><br>
			<?php if ( $is_generating ) : ?>
				<span style="color: #d98500;"><strong>Currently generating stories... Please recheck in 10 minutes.</strong></span>
			<?php endif; ?>
		</strong>
	</div>
			<?php
		} else {
			?>
	<div class="notice notice-warning">
		<strong>
			<?php esc_html_e( 'No scheduled story generation found.', 'ai-story-maker' ); ?>
		</strong>
	</div>
			<?php
		}
		?>
	</div>

</div>
