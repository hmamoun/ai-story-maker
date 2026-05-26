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
				<span class="aistma-close-label"><?php esc_html_e( 'Close', 'ai-story-maker' ); ?></span>
				<span class="aistma-close-icon" aria-hidden="true">&#x2715;</span>
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

		<!-- Site Topic Section -->
		<div class="aistma-site-topic-section" style="background:#f0f6fc;border:1px solid #c3c4c7;border-radius:6px;padding:14px 18px;margin-bottom:0;">
			<h3 style="margin:0 0 6px;font-size:14px;"><?php esc_html_e( 'Generate a story about YOUR site', 'ai-story-maker' ); ?></h3>
			<p style="margin:0 0 10px;color:#646970;font-size:13px;"><?php esc_html_e( 'Describe your site and we will build a custom prompt — or pick a topic below.', 'ai-story-maker' ); ?></p>
			<div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;">
				<div class="aistma-site-topic-input-wrap">
					<input
						type="text"
						id="aistma-site-topic"
						value="<?php echo esc_attr( get_bloginfo( 'description' ) ); ?>"
						placeholder="<?php esc_attr_e( 'e.g. A blog about home gardening and urban farming', 'ai-story-maker' ); ?>"
					/>
					<button type="button" id="aistma-fetch-site-meta" class="aistma-ai-icon-btn" title="<?php esc_attr_e( 'Fill from WordPress site info', 'ai-story-maker' ); ?>" aria-label="<?php esc_attr_e( 'Fill from WordPress site info', 'ai-story-maker' ); ?>">
						<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" width="16" height="16" fill="currentColor" aria-hidden="true">
							<path d="M10 2a.75.75 0 0 1 .75.75v1.5a.75.75 0 0 1-1.5 0v-1.5A.75.75 0 0 1 10 2ZM10 15a.75.75 0 0 1 .75.75v1.5a.75.75 0 0 1-1.5 0v-1.5A.75.75 0 0 1 10 15ZM2 10a.75.75 0 0 1 .75-.75h1.5a.75.75 0 0 1 0 1.5h-1.5A.75.75 0 0 1 2 10ZM15 10a.75.75 0 0 1 .75-.75h1.5a.75.75 0 0 1 0 1.5h-1.5A.75.75 0 0 1 15 10ZM4.22 4.22a.75.75 0 0 1 1.06 0l1.06 1.06a.75.75 0 1 1-1.06 1.06L4.22 5.28a.75.75 0 0 1 0-1.06ZM13.66 13.66a.75.75 0 0 1 1.06 0l1.06 1.06a.75.75 0 1 1-1.06 1.06l-1.06-1.06a.75.75 0 0 1 0-1.06ZM4.22 15.78a.75.75 0 0 1 0-1.06l1.06-1.06a.75.75 0 1 1 1.06 1.06l-1.06 1.06a.75.75 0 0 1-1.06 0ZM13.66 6.34a.75.75 0 0 1 0-1.06l1.06-1.06a.75.75 0 1 1 1.06 1.06l-1.06 1.06a.75.75 0 0 1-1.06 0ZM10 6.5a3.5 3.5 0 1 0 0 7 3.5 3.5 0 0 0 0-7Z"/>
						</svg>
					</button>
				</div>
				<button type="button" id="aistma-generate-my-prompt" class="button button-primary">
					<?php esc_html_e( 'Create my prompt', 'ai-story-maker' ); ?>
				</button>
			</div>
			<div id="aistma-site-prompt-status" style="display:none;margin-top:8px;font-size:13px;"></div>
		</div>

		<!-- OR Divider -->
		<div class="aistma-or-divider">
			<span><?php esc_html_e( 'OR', 'ai-story-maker' ); ?></span>
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
