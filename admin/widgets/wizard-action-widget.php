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
			(function() {
				'use strict';

				function openWizardFromWidget() {
					// Wait for jQuery to be available
					if (typeof jQuery === 'undefined') {
						setTimeout(openWizardFromWidget, 50);
						return;
					}

					const $ = jQuery;
					const btn = $('#aistma-widget-open-wizard');
					if (!btn.length) return;

					btn.on('click', function(e) {
						e.preventDefault();

						// Use AistmaWizard.show() to properly initialize the wizard
						if (typeof AistmaWizard !== 'undefined' && AistmaWizard.show) {
							AistmaWizard.show();
						} else {
							// Fallback if AistmaWizard is not available
							const modal = $('#aistma-wizard-modal');
							if (modal.length) {
								modal.fadeIn(200);
							}
						}
					});
				}

				// Wait for DOM and jQuery to be ready
				if (document.readyState === 'loading') {
					document.addEventListener('DOMContentLoaded', openWizardFromWidget);
				} else {
					openWizardFromWidget();
				}
			})();
		</script>
		<?php
	}
}
