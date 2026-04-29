/**
 * Activation Wizard & Preview Modal JavaScript
 *
 * Handles user interactions for the wizard and preview modals.
 *
 * @package AI_Story_Maker
 * @since   2.2.0
 */

(function ($) {
	'use strict';

	/**
	 * Activation Wizard Class
	 */
	const AistmaWizard = {
		selectedPromptId: null,
		generatedPost: null,
		dontShowAgain: false,

		/**
		 * Initialize wizard interactions
		 */
		init: function () {
			this.cacheDom();
			this.bindEvents();
		},

		/**
		 * Cache DOM elements
		 */
		cacheDom: function () {
			this.$modal = $('#aistma-wizard-modal');
			this.$overlay = this.$modal.find('.aistma-wizard-overlay');
			this.$cards = this.$modal.find('.aistma-prompt-card');
			this.$generateBtn = this.$modal.find('.aistma-wizard-generate');
			this.$closeBtn = this.$modal.find('.aistma-wizard-close');
			this.$cancelBtn = this.$modal.find('.aistma-wizard-cancel');
			this.$dontShowCheckbox = this.$modal.find('#aistma-wizard-dont-show');
			this.$loading = this.$modal.find('.aistma-wizard-loading');
			this.$content = this.$modal.find('.aistma-wizard-content');
		},

		/**
		 * Bind event listeners
		 */
		bindEvents: function () {
			const self = this;

			// Close button
			this.$closeBtn.on('click', function () {
				self.close();
			});

			// Cancel button
			this.$cancelBtn.on('click', function () {
				self.close();
			});

			// Overlay click
			this.$overlay.on('click', function () {
				self.close();
			});

			// Prompt card selection
			this.$cards.on('click', function () {
				self.selectPrompt($(this));
			});

			// Select button within card
			this.$modal.on('click', '.aistma-select-prompt', function (e) {
				e.stopPropagation();
				self.selectPrompt($(this).closest('.aistma-prompt-card'));
			});

			// Generate button
			this.$generateBtn.on('click', function () {
				self.generate();
			});

			// Don't show again checkbox
			this.$dontShowCheckbox.on('change', function () {
				self.dontShowAgain = $(this).is(':checked');
			});

			// Prevent modal close on content click
			this.$content.on('click', function (e) {
				e.stopPropagation();
			});
		},

		/**
		 * Select a prompt
		 */
		selectPrompt: function ($card) {
			// Remove previous selection
			this.$cards.removeClass('selected');

			// Add selection to clicked card
			$card.addClass('selected');

			// Store selected prompt ID
			this.selectedPromptId = $card.data('prompt-id');

			// Enable generate button
			this.$generateBtn.prop('disabled', false);
		},

		/**
		 * Generate story from selected prompt
		 */
		generate: function () {
			const self = this;

			if (!this.selectedPromptId) {
				alert(aistmaWizardL10n.selectPrompt);
				return;
			}

			// Show loading state
			this.$loading.show();
			this.$generateBtn.prop('disabled', true);

			// AJAX request
			$.ajax({
				url: ajaxurl,
				type: 'POST',
				dataType: 'json',
				data: {
					action: 'aistma_wizard_generate',
					nonce: aistmaWizardL10n.generateNonce,
					prompt_id: this.selectedPromptId,
				},
				success: function (response) {
					if (response.success) {
						self.generatedPost = response.data;
						self.close();
						AistmaPreview.show(self.generatedPost);
					} else {
						self.showError(response.data.message);
					}
				},
				error: function () {
					self.showError(aistmaWizardL10n.generateError);
				},
				complete: function () {
					self.$loading.hide();
					self.$generateBtn.prop('disabled', false);
				},
			});
		},

		/**
		 * Show error message
		 */
		showError: function (message) {
			alert(message || aistmaWizardL10n.unknownError);
		},

		/**
		 * Close the wizard modal
		 */
		close: function () {
			if (this.dontShowAgain) {
				this.dismissWizard();
			}
			this.$modal.fadeOut(200);
		},

		/**
		 * Dismiss wizard via AJAX
		 */
		dismissWizard: function () {
			$.ajax({
				url: ajaxurl,
				type: 'POST',
				data: {
					action: 'aistma_wizard_dismiss',
					nonce: aistmaWizardL10n.dismissNonce,
				},
			});
		},

		/**
		 * Show the wizard modal
		 */
		show: function () {
			this.$modal.fadeIn(200);
		},
	};

	/**
	 * Preview Modal Class
	 */
	const AistmaPreview = {
		postData: null,
		weeklyToggleChecked: true,

		/**
		 * Initialize preview interactions
		 */
		init: function () {
			this.cacheDom();
			this.bindEvents();
			this.initWeeklyModal();
		},

		/**
		 * Cache DOM elements
		 */
		cacheDom: function () {
			this.$modal = $('#aistma-preview-modal');
			this.$overlay = this.$modal.find('.aistma-preview-overlay');
			this.$closeBtn = this.$modal.find('.aistma-preview-close');
			this.$cancelBtn = this.$modal.find('.aistma-preview-cancel');
			this.$editBtn = this.$modal.find('.aistma-preview-edit');
			this.$saveBtn = this.$modal.find('.aistma-preview-save');
			this.$loading = this.$modal.find('#aistma-preview-loading');
			this.$errorDiv = this.$modal.find('#aistma-preview-error');
			this.$title = this.$modal.find('#aistma-preview-title');
			this.$excerpt = this.$modal.find('#aistma-preview-excerpt');
			this.$imageContainer = this.$modal.find('#aistma-preview-image-container');
			this.$image = this.$modal.find('#aistma-preview-image');
			this.$creditsRemaining = this.$modal.find('#aistma-preview-credits-remaining');
			this.$content = this.$modal.find('.aistma-preview-content');
			this.$weeklyToggle = this.$modal.find('#aistma-weekly-toggle');
			this.$weeklyConfirmationModal = $('#aistma-weekly-confirmation-modal');
		},

		/**
		 * Initialize weekly confirmation modal
		 */
		initWeeklyModal: function () {
			const self = this;

			// Weekly toggle change handler
			this.$weeklyToggle.on('change', function () {
				self.weeklyToggleChecked = $(this).is(':checked');
			});

			// Weekly confirmation modal buttons
			if (this.$weeklyConfirmationModal.length) {
				const $weeklyOverlay = this.$weeklyConfirmationModal.find('.aistma-weekly-overlay');
				const $weeklyClose = this.$weeklyConfirmationModal.find('.aistma-weekly-close');
				const $weeklyCancel = this.$weeklyConfirmationModal.find('.aistma-weekly-cancel');
				const $weeklyConfirm = this.$weeklyConfirmationModal.find('.aistma-weekly-confirm');

				$weeklyClose.on('click', function () {
					self.closeWeeklyModal();
				});

				$weeklyCancel.on('click', function () {
					self.$weeklyToggle.prop('checked', false);
					self.weeklyToggleChecked = false;
					self.closeWeeklyModal();
				});

				$weeklyConfirm.on('click', function () {
					self.confirmWeekly();
				});

				$weeklyOverlay.on('click', function (e) {
					if (e.target === this) {
						self.closeWeeklyModal();
					}
				});
			}
		},

		/**
		 * Bind event listeners
		 */
		bindEvents: function () {
			const self = this;

			// Close button
			this.$closeBtn.on('click', function () {
				self.close();
			});

			// Cancel button
			this.$cancelBtn.on('click', function () {
				self.close();
			});

			// Overlay click
			this.$overlay.on('click', function () {
				self.close();
			});

			// Edit button
			this.$editBtn.on('click', function () {
				self.editContent();
			});

			// Save button
			this.$saveBtn.on('click', function () {
				self.save();
			});

			// Prevent modal close on content click
			this.$content.on('click', function (e) {
				e.stopPropagation();
			});
		},

		/**
		 * Show the preview modal with generated content
		 */
		show: function (postData) {
			this.postData = postData;

			// Populate content
			this.$title.text(postData.title || 'Untitled');
			this.$excerpt.html(postData.excerpt || '');
			this.$creditsRemaining.text(postData.credits_remaining || 0);

			// Show/hide image if available
			if (postData.featured_image_url) {
				this.$image.attr('src', postData.featured_image_url);
				this.$imageContainer.show();
			} else {
				this.$imageContainer.hide();
			}

			// Hide loading/error states
			this.$loading.hide();
			this.$errorDiv.hide();

			// Show modal
			this.$modal.fadeIn(200);
		},

		/**
		 * Edit content (open in WordPress editor)
		 */
		editContent: function () {
			if (this.postData && this.postData.post_id) {
				const editUrl =
					aistmaWizardL10n.editPostUrl +
					'&post=' +
					this.postData.post_id;
				window.location.href = editUrl;
			}
		},

		/**
		 * Save the generated story
		 */
		save: function () {
			const self = this;

			if (!this.postData || !this.postData.post_id) {
				alert(aistmaWizardL10n.saveError);
				return;
			}

			// Check if weekly is checked and show confirmation modal
			if (this.weeklyToggleChecked && this.postData.prompt_id) {
				// Show weekly confirmation modal before saving
				this.showWeeklyModal();
				return;
			}

			// Proceed with normal save if weekly is not checked
			this.performSave();
		},

		/**
		 * Perform the actual save operation
		 */
		performSave: function () {
			const self = this;

			// Show loading
			this.$loading.show();
			this.$saveBtn.prop('disabled', true);

			// AJAX request
			$.ajax({
				url: ajaxurl,
				type: 'POST',
				dataType: 'json',
				data: {
					action: 'aistma_wizard_save',
					nonce: aistmaWizardL10n.saveNonce,
					post_id: this.postData.post_id,
				},
				success: function (response) {
					if (response.success) {
						// Update credits display
						self.$creditsRemaining.text(response.data.credits_remaining || 0);

						// Close modal and show success message
						setTimeout(function () {
							self.close();
							alert(aistmaWizardL10n.saveSuccess);

						// Show rating modal if applicable
						if (response.data.show_rating && typeof AistmaRating !== 'undefined') {
							AistmaRating.show();
						}

							// Optionally redirect to posts page
							if (aistmaWizardL10n.redirectAfterSave) {
								window.location.href = aistmaWizardL10n.postsPageUrl;
							}
						}, 500);
					} else {
						self.showError(response.data.message || aistmaWizardL10n.saveError);
					}
				},
				error: function () {
					self.showError(aistmaWizardL10n.saveError);
				},
				complete: function () {
					self.$loading.hide();
					self.$saveBtn.prop('disabled', false);
				},
			});
		},

		/**
		 * Show the weekly confirmation modal
		 */
		showWeeklyModal: function () {
			if (!this.$weeklyConfirmationModal.length) {
				// Modal not available, just save normally
				this.performSave();
				return;
			}

			// Update credits display in modal
			const $creditsRemaining = this.$weeklyConfirmationModal.find('#aistma-weekly-credits-remaining');
			if ($creditsRemaining.length) {
				$creditsRemaining.text(this.postData.credits_remaining || 0);
			}

			this.$weeklyConfirmationModal.fadeIn(200);
		},

		/**
		 * Close the weekly confirmation modal
		 */
		closeWeeklyModal: function () {
			if (this.$weeklyConfirmationModal.length) {
				this.$weeklyConfirmationModal.fadeOut(200);
			}
		},

		/**
		 * Confirm weekly auto-generation and save
		 */
		confirmWeekly: function () {
			const self = this;

			if (!this.postData || !this.postData.post_id) {
				return;
			}

			// Close the weekly modal
			this.closeWeeklyModal();

			// Get the prompt ID from post data
			const promptId = this.postData.prompt_id || null;

			if (promptId) {
				// Enable weekly via AJAX
				$.ajax({
					url: ajaxurl,
					type: 'POST',
					dataType: 'json',
					data: {
						action: 'aistma_confirm_weekly',
						nonce: aistmaWizardL10n.weeklyNonce || '',
						prompt_id: promptId,
					},
					success: function () {
						// Log the event
						console.log('Weekly auto-generation enabled for prompt ' + promptId);
					},
					error: function () {
						console.error('Failed to enable weekly auto-generation');
					},
				});
			}

			// Now perform the save
			this.performSave();
		},

		/**
		 * Show error message
		 */
		showError: function (message) {
			this.$errorDiv.find('#aistma-preview-error-message').text(message);
			this.$errorDiv.show();
		},

		/**
		 * Close the preview modal
		 */
		close: function () {
			this.$modal.fadeOut(200);
		},
	};

	/**
	 * Initialize on document ready
	 */
	$(document).ready(function () {
		AistmaWizard.init();
		AistmaPreview.init();

		// Check if wizard should be shown
		if (aistmaWizardL10n.showWizard === '1') {
			AistmaWizard.show();
		}
	});
})(jQuery);
