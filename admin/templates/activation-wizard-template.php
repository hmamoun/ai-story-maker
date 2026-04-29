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
		<!-- Header -->
		<div class="aistma-wizard-header">
			<h2><?php esc_html_e( 'Welcome to AI Story Maker', 'ai-story-maker' ); ?></h2>
			<p><?php esc_html_e( 'Choose a prompt below to generate your first AI story.', 'ai-story-maker' ); ?></p>
			<button type="button" class="aistma-wizard-close" aria-label="<?php esc_attr_e( 'Close wizard', 'ai-story-maker' ); ?>">&times;</button>
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
								<button type="button" class="aistma-select-prompt button button-secondary">
									<?php esc_html_e( 'Select', 'ai-story-maker' ); ?>
								</button>
							</div>
						</div>
						<?php
					}
				}
				?>
			</div>
		</div>

		<!-- Footer -->
		<div class="aistma-wizard-footer">
			<label class="aistma-wizard-checkbox">
				<input type="checkbox" id="aistma-wizard-dont-show" />
				<?php esc_html_e( "Don't show again", 'ai-story-maker' ); ?>
			</label>
			<div class="aistma-wizard-actions">
				<button type="button" class="button button-secondary aistma-wizard-cancel">
					<?php esc_html_e( 'Cancel', 'ai-story-maker' ); ?>
				</button>
				<button type="button" class="button button-primary aistma-wizard-generate" disabled>
					<?php esc_html_e( 'Generate Story', 'ai-story-maker' ); ?>
				</button>
			</div>
		</div>

		<!-- Loading State -->
		<div class="aistma-wizard-loading" style="display: none;">
			<div class="aistma-spinner"></div>
			<p><?php esc_html_e( 'Generating your story...', 'ai-story-maker' ); ?></p>
		</div>
	</div>
</div>
