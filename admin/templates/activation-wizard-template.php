<?php
/**
 * Activation Wizard Modal Template
 *
 * Displays the wizard modal with prompt selection cards.
 *
 * @package AI_Story_Maker
 * @since   2.2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<div id="aistma-wizard-modal" class="aistma-wizard-modal" style="display: none;">
	<div class="aistma-wizard-overlay"></div>
	<div class="aistma-wizard-content">
		<!-- Top Control Bar -->
		<div class="aistma-wizard-top-bar">
			<label class="aistma-wizard-checkbox">
				<input type="checkbox" id="aistma-wizard-dont-show" />
				<?php esc_html_e( "Don't show again", 'ai-story-maker' ); ?>
			</label>
			<button type="button" class="button button-secondary aistma-wizard-close">
				<?php esc_html_e( 'Close', 'ai-story-maker' ); ?>
			</button>
		</div>

		<!-- Header -->
		<div class="aistma-wizard-header">
			<h2><?php esc_html_e( 'Welcome to AI Story Maker', 'ai-story-maker' ); ?></h2>
			<p><?php esc_html_e( 'Choose a prompt below to generate your first AI story.', 'ai-story-maker' ); ?></p>
			<p style="font-size: 13px; color: #646970; margin-top: 10px;">
				<?php esc_html_e( 'You\'ll receive 5 free credits to get started.', 'ai-story-maker' ); ?>
			</p>
		</div>

		<!-- Prompts Grid -->
		<div class="aistma-wizard-body">
			<div class="aistma-prompts-grid">
				<?php
				if ( ! empty( $prompts ) && is_array( $prompts ) ) {
					foreach ( $prompts as $prompt ) {
						?>
						<div class="aistma-prompt-card" data-prompt-id="<?php echo esc_attr( $prompt['id'] ); ?>">
							<div class="aistma-prompt-card-header">
								<h3><?php echo esc_html( $prompt['name'] ); ?></h3>
								<span class="aistma-prompt-category"><?php echo esc_html( $prompt['category'] ); ?></span>
							</div>
							<div class="aistma-prompt-card-body">
								<p><?php echo esc_html( $prompt['description'] ); ?></p>
								<div class="aistma-prompt-example">
									<small><?php echo esc_html( $prompt['example'] ); ?></small>
								</div>
							</div>
							<div class="aistma-prompt-card-footer">
								<button type="button" class="aistma-select-prompt button button-primary">
									<?php esc_html_e( 'Generate Post Now', 'ai-story-maker' ); ?>
								</button>
							</div>
						</div>
						<?php
					}
				}
				?>
			</div>
		</div>

		<!-- Loading State -->
		<div class="aistma-wizard-loading" style="display: none;">
			<div class="aistma-spinner"></div>
			<p><?php esc_html_e( 'Generating your story...', 'ai-story-maker' ); ?></p>
		</div>
	</div>
</div>
