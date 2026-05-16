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
			console.log('AISTMA: Widget script loaded (inline)');

			// Ensure jQuery is available
			if (typeof jQuery === 'undefined') {
				console.error('AISTMA: jQuery is not available at widget script load time');
			} else {
				console.log('AISTMA: jQuery is available, version', jQuery.fn.jquery);

				jQuery(document).ready(function($) {
					'use strict';
					console.log('AISTMA: Document ready event fired');

					function openWizardFromWidget() {
						console.log('AISTMA: openWizardFromWidget() called');

						const btn = $('#aistma-widget-open-wizard');
						console.log('AISTMA: Button lookup result - length:', btn.length, 'element:', btn[0]);

						if (!btn.length) {
							console.error('AISTMA: Widget button #aistma-widget-open-wizard not found in DOM');

							// Additional debugging: check if any button exists
							const allButtons = $('button[id*="widget"]');
							console.log('AISTMA: Found ' + allButtons.length + ' buttons with "widget" in ID');
							allButtons.each(function() {
								console.log('  - Button ID:', this.id);
							});

							return;
						}

						console.log('AISTMA: Button found, attaching click handler');

						btn.on('click', function(e) {
							e.preventDefault();
							console.log('AISTMA: Widget button clicked');

							const modal = $('#aistma-wizard-modal');
							console.log('AISTMA: Modal lookup result - length:', modal.length);

							if (modal.length) {
								console.log('AISTMA: Modal found, opening');
								// Reset wizard state for fresh start
								modal.find('.aistma-prompt-card').removeClass('selected');
								modal.find('#aistma-wizard-dont-show').prop('checked', false);

								// Show modal
								modal.fadeIn(200);
								modal.show();

								// Log widget action
								if (typeof AistmaWizard !== 'undefined') {
									AistmaWizard.selectedPromptId = null;
									console.log('AISTMA: AistmaWizard object found and reset');
								} else {
									console.warn('AISTMA: AistmaWizard object not defined');
								}
							} else {
								console.error('AISTMA: Modal not found - ID #aistma-wizard-modal');

								// Additional debugging: check if modal exists with different selectors
								const modalViaClass = $('.aistma-wizard-modal');
								console.log('AISTMA: Found ' + modalViaClass.length + ' elements with class .aistma-wizard-modal');

								const allModals = $('[id*="wizard-modal"]');
								console.log('AISTMA: Found ' + allModals.length + ' modals with "wizard-modal" in ID');
								allModals.each(function() {
									console.log('  - Modal ID:', this.id);
								});
							}
						});

						console.log('AISTMA: Click handler attached to button');
					}

					// Use a small timeout to ensure DOM is fully ready
					setTimeout(function() {
						openWizardFromWidget();
					}, 100);
				});
			}
		</script>
		<?php
	}
}
