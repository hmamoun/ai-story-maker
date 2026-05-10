<?php
/**
 * Weekly Confirmation Modal Template
 *
 * Displays confirmation for enabling weekly auto-generation.
 *
 * @package AI_Story_Maker
 * @since   2.3.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<div id="aistma-weekly-confirmation-modal" class="aistma-weekly-confirmation-modal" style="display: none;">
	<div class="aistma-weekly-overlay"></div>
	<div class="aistma-weekly-modal-content">
		<!-- Header -->
		<div class="aistma-weekly-header">
			<h2><?php esc_html_e( 'Enable Weekly Auto-Generation?', 'ai-story-maker' ); ?></h2>
			<button type="button" class="aistma-weekly-close" aria-label="<?php esc_attr_e( 'Close', 'ai-story-maker' ); ?>">&times;</button>
		</div>

		<!-- Body -->
		<div class="aistma-weekly-body">
			<p>
				<?php esc_html_e( "We'll generate a story like this every week using the prompt you just chose.", 'ai-story-maker' ); ?>
			</p>
			<div class="aistma-weekly-info">
				<div class="aistma-weekly-info-item">
					<strong><?php esc_html_e( 'Credit Cost:', 'ai-story-maker' ); ?></strong>
					<span><?php esc_html_e( '1 credit per generation', 'ai-story-maker' ); ?></span>
				</div>
				<div class="aistma-weekly-info-item">
					<strong><?php esc_html_e( 'Your Credits:', 'ai-story-maker' ); ?></strong>
					<span id="aistma-weekly-credits-remaining">0</span>
				</div>
				<div class="aistma-weekly-info-item">
					<strong><?php esc_html_e( 'Frequency:', 'ai-story-maker' ); ?></strong>
					<span><?php esc_html_e( 'Every 7 days', 'ai-story-maker' ); ?></span>
				</div>
			</div>
			<p class="aistma-weekly-note">
				<?php esc_html_e( 'You can disable this anytime in your settings.', 'ai-story-maker' ); ?>
			</p>
		</div>

		<!-- Footer -->
		<div class="aistma-weekly-footer">
			<button type="button" class="button button-secondary aistma-weekly-cancel">
				<?php esc_html_e( '✗ Not now', 'ai-story-maker' ); ?>
			</button>
			<button type="button" class="button button-primary aistma-weekly-confirm">
				<?php esc_html_e( '✓ Yes, enable weekly', 'ai-story-maker' ); ?>
			</button>
		</div>
	</div>
</div>
