<?php
/**
 * Rating Request Modal Template
 *
 * Displays the rating request modal asking users to rate on WordPress.org
 *
 * @package AI_Story_Maker
 * @since   2.3.0
 */

// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>

<div id="aistma-rating-modal" class="aistma-rating-modal" style="display: none;">
	<div class="aistma-rating-overlay"></div>
	<div class="aistma-rating-content">
		<!-- Header -->
		<div class="aistma-rating-header">
			<button type="button" class="aistma-rating-close" aria-label="<?php esc_attr_e( 'Close', 'ai-story-maker' ); ?>">
				<span class="dashicon dashicon-no"></span>
			</button>
			<h2><?php esc_html_e( 'Love AI Story Maker?', 'ai-story-maker' ); ?> 🌟</h2>
		</div>

		<!-- Body -->
		<div class="aistma-rating-body">
			<p class="aistma-rating-message">
				<?php esc_html_e( 'Please rate us on WordPress.org to help other creators discover this plugin!', 'ai-story-maker' ); ?>
			</p>

			<!-- Star Rating Selector -->
			<div class="aistma-rating-stars-container">
				<div class="aistma-rating-stars" data-rating="0">
					<?php for ( $i = 1; $i <= 5; $i++ ) : ?>
						<button 
							type="button" 
							class="aistma-star" 
							data-rating="<?php echo absint( $i ); ?>" 
							aria-label="<?php printf( esc_attr__( 'Rate %d stars', 'ai-story-maker' ), absint( $i ) ); ?>"
							title="<?php printf( esc_attr__( 'Rate as %d stars', 'ai-story-maker' ), absint( $i ) ); ?>"
						>
							★
						</button>
					<?php endfor; ?>
				</div>
			</div>

			<!-- Current Rating Display (optional) -->
			<p class="aistma-rating-current" style="display: none;">
				<?php esc_html_e( 'Current plugin rating on WordPress.org: ', 'ai-story-maker' ); ?>
				<span class="aistma-rating-value">—</span>
			</p>
		</div>

		<!-- Footer -->
		<div class="aistma-rating-footer">
			<!-- Rate Button -->
			<a 
				href="https://wordpress.org/plugins/ai-story-maker/#reviews" 
				target="_blank" 
				rel="noopener noreferrer" 
				class="button button-primary aistma-rating-submit"
			>
				<?php esc_html_e( '★★★★★ Rate on WordPress.org', 'ai-story-maker' ); ?>
			</a>

			<!-- Secondary Actions -->
			<div class="aistma-rating-secondary-actions">
				<button type="button" class="button button-link aistma-rating-remind-later">
					<?php esc_html_e( 'Maybe later', 'ai-story-maker' ); ?>
				</button>
				<label class="aistma-rating-checkbox">
					<input type="checkbox" class="aistma-rating-never-ask" />
					<?php esc_html_e( "Don't ask again", 'ai-story-maker' ); ?>
				</label>
			</div>
		</div>

		<!-- Loading State -->
		<div class="aistma-rating-loading" style="display: none;">
			<div class="spinner"></div>
		</div>
	</div>
</div>
