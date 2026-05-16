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
		closeWithoutGenerating: false,

		/**
		 * Initialize wizard interactions
		 */
		init: function () {
			this.cacheDom();
			this.bindEvents();
		},

		/**
		 * Initialize startup credits if user doesn't have any (called only when wizard is shown)
		 */
		initializeStartupCredits: function () {
			const self = this;
			
			// AJAX request to ensure user has startup credits
			$.ajax({
				url: ajaxurl,
				type: 'POST',
				dataType: 'json',
				data: {
					action: 'aistma_ensure_startup_credits',
					nonce: aistmaWizardL10n.startupCreditsNonce || '',
				},
				success: function (response) {
					if (response.success) {
					}
				},
				error: function () {
					console.error('Failed to ensure startup credits');
				},
			});
		},

		/**
		 * Cache DOM elements
		 */
		cacheDom: function () {
			this.$modal = $('#aistma-wizard-modal');
			this.$overlay = this.$modal.find('.aistma-wizard-overlay');
			this.$cards = this.$modal.find('.aistma-prompt-card');
			this.$closeBtn = this.$modal.find('.aistma-wizard-close');
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

			// Overlay click
			this.$overlay.on('click', function () {
				self.close();
			});

			// Prompt card selection - triggers generation directly
			this.$cards.on('click', function () {
				self.selectAndGenerate($(this));
			});

			// Select button within card - triggers generation directly
			this.$modal.on('click', '.aistma-select-prompt', function (e) {
				e.stopPropagation();
				self.selectAndGenerate($(this).closest('.aistma-prompt-card'));
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
		 * Select a prompt and generate immediately
		 */
		selectAndGenerate: function ($card) {
			// Remove previous selection
			this.$cards.removeClass('selected');

			// Add selection to clicked card
			$card.addClass('selected');

			// Store selected prompt ID
			this.selectedPromptId = $card.data('prompt-id');

			// Trigger generation immediately
			this.generate();
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

			// Initialize startup credits only when user commits to generating (selecting a prompt)
			// This ensures enrollment only happens if user doesn't cancel the wizard
			if (!sessionStorage.getItem('aistma_startup_credits_initialized')) {
				this.initializeStartupCredits();
				sessionStorage.setItem('aistma_startup_credits_initialized', '1');
			}

			// Show loading state
			this.$loading.show();

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
						self.showError(response.data.message, response.data.redirect_url);
					}
				},
				error: function () {
					self.showError(aistmaWizardL10n.generateError);
				},
				complete: function () {
					self.$loading.hide();
				},
			});
		},

		/**
		 * Show error message and optionally redirect
		 */
		showError: function (message, redirectUrl) {
			alert(message || aistmaWizardL10n.unknownError);

			// Redirect if a URL is provided
			if (redirectUrl) {
				window.location.href = redirectUrl;
			}
		},

		/**
		 * Close the wizard modal
		 */
		close: function () {
			// Log if user closed without generating
			if (!this.selectedPromptId) {
				this.logCloseWithoutGeneration();
			}

			if (this.dontShowAgain) {
				this.dismissWizard();
			}
			this.$modal.fadeOut(200);
		},

		/**
		 * Log user closing without generating
		 */
		logCloseWithoutGeneration: function () {
			$.ajax({
				url: ajaxurl,
				type: 'POST',
				data: {
					action: 'aistma_log_wizard_escape',
					nonce: aistmaWizardL10n.escapeNonce || '',
				},
				error: function () {
					console.error('Failed to log wizard escape');
				},
			});
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
		 * Mark wizard as shown today for 24-hour throttling.
		 */
		markShownToday: function () {
			// Set localStorage flag to prevent double-showing on this browser
			const today = new Date().toDateString();
			localStorage.setItem('aistma_wizard_shown_date', today);
			
			// Also mark server-side
			$.ajax({
				url: ajaxurl,
				type: 'POST',
				data: {
					action: 'aistma_mark_wizard_shown_today',
					nonce: aistmaWizardL10n.showTodayNonce || '',
				},
			});
		},

		/**
		 * Show the wizard modal
		 */
		show: function () {
			// Mark as shown today BEFORE displaying (prevents showing again today)
			this.markShownToday();
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

			// If weekly is checked, enable it and save immediately (no confirmation modal)
			if (this.weeklyToggleChecked && this.postData.prompt_id) {
				this.enabledWeeklyAndSave();
				return;
			}

			// Proceed with normal save
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
		 * Enable weekly and save immediately (replaces confirmWeekly)
		 */
		enabledWeeklyAndSave: function () {
			const self = this;

			if (!this.postData || !this.postData.prompt_id) {
				this.performSave();
				return;
			}

			// Enable weekly via AJAX
			$.ajax({
				url: ajaxurl,
				type: 'POST',
				dataType: 'json',
				data: {
					action: 'aistma_confirm_weekly',
					nonce: aistmaWizardL10n.weeklyNonce || '',
					prompt_id: this.postData.prompt_id,
				},
				success: function () {
				},
				error: function () {
					console.error('Failed to enable weekly.');
				},
			});

			// Save immediately
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
			const self = this;

			// Delete the draft post if it exists
			if (this.postData && this.postData.post_id) {
				$.ajax({
					url: ajaxurl,
					type: 'POST',
					dataType: 'json',
					data: {
						action: 'aistma_wizard_cancel',
						nonce: aistmaWizardL10n.cancelNonce || '',
						post_id: this.postData.post_id,
					},
					error: function () {
						// Log error but don't block the modal close
						console.error('Failed to delete draft post.');
					},
				});
			}

			this.$modal.fadeOut(200);
		},
	};

	/**
	 * Initialize on document ready
	 */
	$(document).ready(function () {
		AistmaWizard.init();
		AistmaPreview.init();

		// Check if wizard should be shown (and not already shown today)
		if (aistmaWizardL10n.showWizard === '1') {
			const today = new Date().toDateString();
			const lastShownDate = localStorage.getItem('aistma_wizard_shown_date');
			
			// Only show if not already shown today on this browser
			if (lastShownDate !== today) {
				AistmaWizard.show();
			}
		}
	});
})(jQuery);
