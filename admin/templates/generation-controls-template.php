<?php
/**
 * Reusable generation controls block (lock-aware button + schedule notice).
 *
 * @package AI_Story_Maker
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>
<div class="aistma-generation-controls" style="margin-top:20px;">
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
	
	<?php
	// Check if social media auto-publish is enabled
	$social_media_accounts = get_option( 'aistma_social_media_accounts', array() );
	$auto_publish_enabled = isset( $social_media_accounts['global_settings']['auto_publish'] ) && $social_media_accounts['global_settings']['auto_publish'];
	$has_enabled_accounts = false;
	
	if ( isset( $social_media_accounts['accounts'] ) && is_array( $social_media_accounts['accounts'] ) ) {
		foreach ( $social_media_accounts['accounts'] as $account ) {
			if ( isset( $account['enabled'] ) && $account['enabled'] ) {
				$has_enabled_accounts = true;
				break;
			}
		}
	}
	
	if ( $auto_publish_enabled && $has_enabled_accounts ) : ?>
		<p style="margin-top: 10px; color: #0073aa; font-size: 13px;">
			<span class="dashicons dashicons-share" style="font-size: 16px; vertical-align: middle;"></span>
			<?php esc_html_e( 'Social media auto-publish is enabled. New stories will be automatically shared to your connected accounts.', 'ai-story-maker' ); ?>
		</p>
	<?php elseif ( $has_enabled_accounts ) : ?>
		<p style="margin-top: 10px; color: #666; font-size: 13px;">
			<span class="dashicons dashicons-share" style="font-size: 16px; vertical-align: middle;"></span>
			<?php 
			printf( 
				/* translators: %s: link to social media settings */
				wp_kses_post( __( 'Social media accounts connected. <a href="%s">Enable auto-publish</a> to share new stories automatically.', 'ai-story-maker' ) ),
				esc_url( admin_url( 'admin.php?page=aistma-settings&tab=social_media' ) )
			); 
			?>
		</p>
	<?php endif; ?>

	<div id="aistma-notice" class="notice" style="display:none; margin-top:10px;"></div>

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
		<div style="margin-top:10px;">
			<strong>
				🕒 <?php echo esc_html__( 'Next AI story generation scheduled in', 'ai-story-maker' ); ?> <?php echo esc_html( $formatted_countdown ); ?><br>
				📅 <?php echo esc_html__( 'Scheduled for:', 'ai-story-maker' ); ?> <em><?php echo esc_html( $formatted_datetime ); ?></em><br>
				<?php if ( $is_generating ) : ?>
					<span style="color: #d98500;"><strong><?php echo esc_html__( 'Currently generating stories... Please recheck in 10 minutes.', 'ai-story-maker' ); ?></strong></span>
				<?php endif; ?>
			</strong>
		</div>
		<?php
	} else {
		?>
		<div class="notice notice-warning" style="margin-top:10px;">
			<strong>
				<?php esc_html_e( 'No scheduled story generation found.', 'ai-story-maker' ); ?>
			</strong>
		</div>
		<?php
	}
	?>
</div>


