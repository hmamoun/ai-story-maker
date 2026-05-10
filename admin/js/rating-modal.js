/**
 * Rating Request Modal JavaScript
 *
 * Handles user interactions for the rating request modal.
 *
 * @package AI_Story_Maker
 * @since   2.3.0
 */

(function ($) {
	'use strict';

	/**
	 * Aistma Rating Modal Class
	 */
	const AistmaRating = {
		selectedRating: 0,
		neverAsk: false,

		/**
		 * Initialize rating modal interactions
		 */
		init: function () {
			this.cacheDom();
			this.bindEvents();
		},

		/**
		 * Cache DOM elements
		 */
		cacheDom: function () {
			this.$modal = $('#aistma-rating-modal');
			this.$overlay = this.$modal.find('.aistma-rating-overlay');
			this.$stars = this.$modal.find('.aistma-star');
			this.$starsContainer = this.$modal.find('.aistma-rating-stars');
			this.$submitBtn = this.$modal.find('.aistma-rating-submit');
			this.$remindLaterBtn = this.$modal.find('.aistma-rating-remind-later');
			this.$neverAskCheckbox = this.$modal.find('.aistma-rating-never-ask');
			this.$closeBtn = this.$modal.find('.aistma-rating-close');
			this.$loading = this.$modal.find('.aistma-rating-loading');
		},

		/**
		 * Bind event listeners
		 */
		bindEvents: function () {
			const self = this;

			// Star rating interactions
			this.$stars.on('mouseenter', function () {
				const rating = parseInt($(this).data('rating'), 10);
				self.highlightStars(rating);
			});

			this.$starsContainer.on('mouseleave', function () {
				self.highlightStars(self.selectedRating);
			});

			this.$stars.on('click', function (e) {
				e.preventDefault();
				const rating = parseInt($(this).data('rating'), 10);
				self.selectRating(rating);
			});

			// Never ask checkbox
			this.$neverAskCheckbox.on('change', function () {
				self.neverAsk = $(this).is(':checked');
			});

			// Submit button - redirect to WordPress.org
			this.$submitBtn.on('click', function (e) {
				e.preventDefault();
				self.submitRating();
			});

			// Remind later button
			this.$remindLaterBtn.on('click', function (e) {
				e.preventDefault();
				self.remindLater();
			});

			// Close button
			this.$closeBtn.on('click', function (e) {
				e.preventDefault();
				self.close();
			});

			// Overlay click
			this.$overlay.on('click', function (e) {
				if (e.target === this) {
					self.close();
				}
			});
		},

		/**
		 * Highlight stars on hover/selection
		 *
		 * @param {number} rating The rating number (1-5)
		 */
		highlightStars: function (rating) {
			this.$stars.each(function () {
				const $star = $(this);
				const starRating = parseInt($star.data('rating'), 10);

				if (starRating <= rating && rating > 0) {
					$star.addClass('hover').removeClass('active');
				} else {
					$star.removeClass('hover');
					if (starRating <= this.selectedRating) {
						$star.addClass('active');
					} else {
						$star.removeClass('active');
					}
				}
			}.bind(this));
		},

		/**
		 * Select a rating
		 *
		 * @param {number} rating The rating number (1-5)
		 */
		selectRating: function (rating) {
			this.selectedRating = rating;

			// Update visual state
			this.$stars.each(function () {
				const $star = $(this);
				const starRating = parseInt($star.data('rating'), 10);

				if (starRating <= rating) {
					$star.addClass('active clicked').removeClass('hover');
				} else {
					$star.removeClass('active clicked hover');
				}
			});

			// Update container data attribute
			this.$starsContainer.attr('data-rating', rating);
		},

		/**
		 * Submit rating to WordPress.org
		 */
		submitRating: function () {
			const self = this;

			// Show loading state
			this.$loading.show();
			this.$submitBtn.prop('disabled', true);

			// Log the rating submission event via AJAX
			$.ajax({
				url: ajaxurl,
				type: 'POST',
				dataType: 'json',
				data: {
					action: 'aistma_submit_rating',
					nonce: aistmaRatingL10n.submitNonce,
					rating: this.selectedRating,
					never_ask: this.neverAsk ? 1 : 0,
				},
				success: function (response) {
					if (response.success) {
						// Redirect to WordPress.org reviews page
						window.open(
							'https://wordpress.org/plugins/ai-story-maker/#reviews',
							'_blank',
							'noopener,noreferrer'
						);

						// Close modal after a short delay
						setTimeout(function () {
							self.close();
						}, 500);
					} else {
						self.showError(response.data.message || 'Error submitting rating');
					}
				},
				error: function () {
					// Still redirect to WordPress.org even if logging fails
					window.open(
						'https://wordpress.org/plugins/ai-story-maker/#reviews',
						'_blank',
						'noopener,noreferrer'
					);
					setTimeout(function () {
						self.close();
					}, 500);
				},
				complete: function () {
					self.$loading.hide();
					self.$submitBtn.prop('disabled', false);
				},
			});
		},

		/**
		 * Remind user later (7 days)
		 */
		remindLater: function () {
			const self = this;

			// Check if "never ask" is checked
			if (this.neverAsk) {
				this.neverAskAgain();
				return;
			}

			// Show loading state
			this.$loading.show();

			$.ajax({
				url: ajaxurl,
				type: 'POST',
				dataType: 'json',
				data: {
					action: 'aistma_dismiss_rating',
					nonce: aistmaRatingL10n.dismissNonce,
				},
				success: function (response) {
					if (response.success) {
						self.close();
					} else {
						self.showError(response.data.message || 'Error dismissing rating');
					}
				},
				error: function () {
					// Close modal even if logging fails
					self.close();
				},
				complete: function () {
					self.$loading.hide();
				},
			});
		},

		/**
		 * Never ask again
		 */
		neverAskAgain: function () {
			const self = this;

			// Show loading state
			this.$loading.show();

			$.ajax({
				url: ajaxurl,
				type: 'POST',
				dataType: 'json',
				data: {
					action: 'aistma_never_show_rating',
					nonce: aistmaRatingL10n.dismissNonce,
				},
				success: function (response) {
					if (response.success) {
						self.close();
					} else {
						self.showError(response.data.message || 'Error updating preference');
					}
				},
				error: function () {
					// Close modal even if logging fails
					self.close();
				},
				complete: function () {
					self.$loading.hide();
				},
			});
		},

		/**
		 * Close the rating modal
		 */
		close: function () {
			const self = this;

			// Check if "never ask" is checked when closing
			if (this.neverAsk) {
				// Make sure we still mark it before closing
				this.$loading.show();
				$.ajax({
					url: ajaxurl,
					type: 'POST',
					dataType: 'json',
					data: {
						action: 'aistma_never_show_rating',
						nonce: aistmaRatingL10n.dismissNonce,
					},
					complete: function () {
						self.$loading.hide();
						self.$modal.fadeOut(300, function () {
							$(this).hide();
						});
					},
				});
			} else {
				this.$modal.fadeOut(300, function () {
					$(this).hide();
				});
			}
		},

		/**
		 * Show error message
		 *
		 * @param {string} message Error message to display
		 */
		showError: function (message) {
			// Log error to console for debugging
			// phpcs:ignore WordPress.Security.EscapeOutput.UnsafeEcho
			console.error('Rating modal error:', message);

			// Could extend this to show user-facing error in modal if needed
		},

		/**
		 * Show the rating modal
		 */
		show: function () {
			this.$modal.fadeIn(300);
		},
	};

	// Initialize when document is ready
	$(document).ready(function () {
		AistmaRating.init();

		// Show modal if data attribute indicates it should be shown
		if ($('#aistma-rating-modal').data('show-rating')) {
			AistmaRating.show();
		}
	});

	// Expose for external use if needed
	window.AistmaRating = AistmaRating;

})(jQuery);
