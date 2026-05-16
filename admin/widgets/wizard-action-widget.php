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
		<div id="aistma-widget-content" style="text-align: center; padding: 30px; display: flex; flex-direction: column; align-items: center; justify-content: center;">
			<p style="margin: 0 0 15px 0;"><?php esc_html_e( 'Generate engaging stories with our AI-powered wizard.', 'ai-story-maker' ); ?></p>
			<button type="button" id="aistma-widget-open-wizard" class="button button-primary button-large" style="margin: 0 0 15px 0;">
				<?php esc_html_e( 'Create a Story Now', 'ai-story-maker' ); ?>
			</button>
			<p style="margin: 0; color: #666; font-size: 12px;">
				<?php esc_html_e( 'Click the button to launch the story creation wizard.', 'ai-story-maker' ); ?>
			</p>
		</div>

		<script type="text/javascript">
			document.addEventListener('DOMContentLoaded', function() {
				const btn = document.getElementById('aistma-widget-open-wizard');
				if (!btn) {
					console.warn('aistma-widget-open-wizard button not found');
					return;
				}

				btn.addEventListener('click', function(e) {
					e.preventDefault();
					console.log('Widget button clicked');

					// Ensure jQuery and AistmaWizard are available
					function showWizard() {
						if (typeof jQuery === 'undefined') {
							console.warn('jQuery not available yet');
							setTimeout(showWizard, 100);
							return;
						}

						if (typeof AistmaWizard === 'undefined') {
							console.warn('AistmaWizard not available yet');
							setTimeout(showWizard, 100);
							return;
						}

						if (typeof AistmaWizard.show !== 'function') {
							console.error('AistmaWizard.show is not a function');
							return;
						}

						console.log('Calling AistmaWizard.show()');
						AistmaWizard.show();
					}

					showWizard();
				});
			});
		</script>
		<?php
	}
}
