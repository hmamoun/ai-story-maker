<?php
/**
 * AI Story Maker Dashboard Widget
 *
 * Displays a widget on the admin dashboard with an action button
 * to open the wizard modal.
 *
 * @package AI_Story_Maker
 * @since   2.2.0
 */

namespace exedotcom\aistorymaker;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class AISTMA_Wizard_Action_Widget
 */
class AISTMA_Wizard_Action_Widget {

	/**
	 * Register the widget.
	 */
	public static function register_widget() {
		wp_add_dashboard_widget(
			'aistma_wizard_action_widget',
			__( 'Create Story with AI', 'ai-story-maker' ),
			array( __CLASS__, 'render_widget' )
		);
	}

	/**
	 * Render the widget content.
	 */
	public static function render_widget() {
		?>
		<div id="aistma-widget-content" style="text-align: center; padding: 20px;">
			<p><?php esc_html_e( 'Generate engaging stories with our AI-powered wizard.', 'ai-story-maker' ); ?></p>
			<button type="button" id="aistma-widget-open-wizard" class="button button-primary button-hero" style="margin-top: 10px;">
				<?php esc_html_e( 'Create a Story Now', 'ai-story-maker' ); ?>
			</button>
			<p style="margin-top: 15px; color: #666; font-size: 12px;">
				<?php esc_html_e( 'Click the button to launch the story creation wizard.', 'ai-story-maker' ); ?>
			</p>
		</div>

		<script>
			(function() {
				const btn = document.getElementById('aistma-widget-open-wizard');
				if (btn) {
					btn.addEventListener('click', function() {
						// Show the wizard modal if jQuery and the wizard object exist
						if (typeof jQuery !== 'undefined' && typeof AistmaWizard !== 'undefined') {
							jQuery('#aistma-wizard-modal').fadeIn(200);
							AistmaWizard.$modal = jQuery('#aistma-wizard-modal');
							AistmaWizard.selectedPromptId = null;
							AistmaWizard.$cards.removeClass('selected');
						}
					});
				}
			})();
		</script>
		<?php
	}
}
