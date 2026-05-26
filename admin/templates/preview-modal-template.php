<?php
/**
 * Preview Modal Template
 *
 * Displays the generated story preview before saving.
 *
 * @package AI_Story_Maker
 * @since   2.2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<div id="aistma-preview-modal" class="aistma-preview-modal" style="display: none;">
	<div class="aistma-preview-overlay"></div>
	<div class="aistma-preview-content">
		<!-- Header -->
		<div class="aistma-preview-header">
			<h2 id="aistma-preview-title" class="aistma-preview-title">Loading...</h2>
			<button type="button" class="aistma-preview-close" aria-label="<?php esc_attr_e( 'Close preview', 'ai-story-maker' ); ?>">&times;</button>
		</div>

		<!-- Body -->
		<div class="aistma-preview-body">
			<!-- Featured Image -->
			<div id="aistma-preview-image-container" class="aistma-preview-image-container" style="display: none;">
				<img id="aistma-preview-image" class="aistma-preview-image" src="" alt="Featured Image" />
			</div>

			<!-- Credits Info -->
			<div class="aistma-preview-credits">
				<span><?php esc_html_e( 'Credits remaining:', 'ai-story-maker' ); ?> </span>
				<strong id="aistma-preview-credits-remaining">0</strong>
			</div>

			<!-- Excerpt -->
			<div id="aistma-preview-excerpt-container" class="aistma-preview-excerpt-container">
				<p id="aistma-preview-excerpt" class="aistma-preview-excerpt">Loading preview...</p>
			</div>

			<!-- Loading State -->
			<div id="aistma-preview-loading" class="aistma-preview-loading" style="display: none;">
				<div class="aistma-spinner"></div>
				<p><?php esc_html_e( 'Generating your story...', 'ai-story-maker' ); ?></p>
			</div>

			<!-- Error State -->
			<div id="aistma-preview-error" class="aistma-preview-error" style="display: none;">
				<p id="aistma-preview-error-message"></p>
			</div>
		</div>

		<!-- Weekly Toggle -->
		<div class="aistma-preview-weekly-toggle">
			<label for="aistma-weekly-toggle" class="aistma-weekly-toggle-label">
				<input type="checkbox" id="aistma-weekly-toggle" class="aistma-weekly-toggle-checkbox">
				<span class="aistma-weekly-toggle-text"><?php esc_html_e( 'Auto-generate stories every week', 'ai-story-maker' ); ?></span>
			</label>
			<p class="aistma-weekly-toggle-help"><?php esc_html_e( 'This will use 1 credit per week. Can disable anytime.', 'ai-story-maker' ); ?></p>
		</div>

		<!-- Footer -->
		<div class="aistma-preview-footer">
			<div class="aistma-preview-actions">
				<button type="button" class="button button-secondary aistma-preview-cancel">
					<?php esc_html_e( 'Cancel', 'ai-story-maker' ); ?>
				</button>
				<button type="button" class="button aistma-preview-edit" id="aistma-preview-edit-button">
					<?php esc_html_e( 'Edit Content', 'ai-story-maker' ); ?>
				</button>
				<button type="button" class="button button-primary aistma-preview-save" id="aistma-preview-save-button">
					<?php esc_html_e( 'Save & Continue', 'ai-story-maker' ); ?>
				</button>
			</div>
		</div>
	</div>
</div>
