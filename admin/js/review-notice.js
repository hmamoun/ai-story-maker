/**
 * AI Story Maker — Admin Review Notice
 *
 * Handles all user interactions with the inline admin notice bar:
 *
 *   Star hover      → highlight 1..N stars (visual feedback).
 *   4–5 star click  → AJAX `aistma_notice_rate` → open WP.org review in new tab
 *                      → hide notice.
 *   1–3 star click  → reveal feedback textarea.
 *   Feedback submit → AJAX `aistma_notice_feedback` → show thank-you → hide notice.
 *   Feedback skip   → AJAX `aistma_notice_feedback` (empty) → hide notice.
 *   Dismiss (×)     → AJAX `aistma_notice_dismiss` → hide notice.
 *
 * Data is passed from PHP via wp_localize_script() as `aistmaReviewNotice`:
 *   ajaxUrl, reviewUrl, nonceDismiss, nonceRate, nonceFeedback, i18n.
 *
 * @package AI_Story_Maker
 * @since   2.4.0
 */

( function ( $ ) {
	'use strict';

	/**
	 * ReviewNotice — manages the admin notice interaction lifecycle.
	 */
	const ReviewNotice = {

		/** Currently selected star value (0 = none). */
		selectedRating: 0,

		// Cached jQuery objects — populated in init().
		$notice:       null,
		$stars:        null,
		$feedbackArea: null,
		$feedbackText: null,
		$thankyou:     null,

		/**
		 * Bootstrap the component.
		 * Bails silently if the notice element is absent (notice suppressed server-side).
		 */
		init: function () {
			this.$notice = $( '#aistma-review-notice' );
			if ( ! this.$notice.length ) {
				return;
			}

			this.$stars        = this.$notice.find( '.aistma-rn-star' );
			this.$feedbackArea = this.$notice.find( '.aistma-rn-feedback' );
			this.$feedbackText = this.$notice.find( '.aistma-rn-feedback-text' );
			this.$thankyou     = this.$notice.find( '.aistma-rn-thankyou' );

			this.bindEvents();
		},

		// -----------------------------------------------------------------------
		// Event binding
		// -----------------------------------------------------------------------

		bindEvents: function () {
			const self = this;

			// Star hover — highlight stars up to hovered position.
			this.$stars.on( 'mouseenter', function () {
				self.highlightUpTo( parseInt( $( this ).data( 'value' ), 10 ) );
			} );

			// Star mouse-leave — revert to selected state.
			this.$notice.find( '.aistma-rn-stars' ).on( 'mouseleave', function () {
				self.highlightUpTo( self.selectedRating );
			} );

			// Star click.
			this.$stars.on( 'click', function () {
				self.handleStarClick( parseInt( $( this ).data( 'value' ), 10 ) );
			} );

			// Keyboard: Enter / Space on star buttons (already handled by click for <button>).

			// WP-style dismiss button (renders as ×).
			this.$notice.on( 'click', '.aistma-rn-dismiss', function () {
				self.dismiss();
			} );

			// Feedback submit.
			this.$notice.on( 'click', '.aistma-rn-feedback-submit', function () {
				self.submitFeedback( $( this ) );
			} );

			// Feedback skip (no text needed).
			this.$notice.on( 'click', '.aistma-rn-feedback-skip', function () {
				self.skipFeedback();
			} );
		},

		// -----------------------------------------------------------------------
		// Stars
		// -----------------------------------------------------------------------

		/**
		 * Light up stars 1 through `count`; dim the rest.
		 *
		 * @param {number} count 0–5
		 */
		highlightUpTo: function ( count ) {
			this.$stars.each( function () {
				$( this ).toggleClass(
					'aistma-rn-star--lit',
					parseInt( $( this ).data( 'value' ), 10 ) <= count
				);
			} );
		},

		/**
		 * Handle a star click: record selection, animate, branch on rating value.
		 *
		 * @param {number} rating 1–5
		 */
		handleStarClick: function ( rating ) {
			this.selectedRating = rating;
			this.highlightUpTo( rating );

			// Brief pulse animation on the clicked star.
			this.$stars
				.filter( '[data-value="' + rating + '"]' )
				.addClass( 'aistma-rn-star--pulse' );

			// Disable further star interaction while processing.
			this.$stars.prop( 'disabled', true );

			if ( rating >= 4 ) {
				// High rating → log + redirect to WordPress.org.
				this.submitHighRating( rating );
			} else {
				// Low rating → reveal feedback textarea.
				this.$feedbackArea.slideDown( 200 );
				this.$feedbackText.trigger( 'focus' );
				// Re-enable stars so user can change their mind.
				this.$stars.prop( 'disabled', false );
			}
		},

		// -----------------------------------------------------------------------
		// High-rating flow (4–5 stars)
		// -----------------------------------------------------------------------

		/**
		 * Log the high rating server-side, then open the review page.
		 *
		 * The redirect happens in `.always()` so network failures don't block the user.
		 *
		 * @param {number} rating
		 */
		submitHighRating: function ( rating ) {
			const self = this;

			$.post( aistmaReviewNotice.ajaxUrl, {
				action: 'aistma_notice_rate',
				nonce:  aistmaReviewNotice.nonceRate,
				rating: rating,
			} ).always( function () {
				window.open(
					aistmaReviewNotice.reviewUrl,
					'_blank',
					'noopener,noreferrer'
				);
				self.hideNotice();
			} );
		},

		// -----------------------------------------------------------------------
		// Low-rating feedback flow (1–3 stars)
		// -----------------------------------------------------------------------

		/**
		 * Submit feedback text (may be empty) and suppress the notice permanently.
		 *
		 * @param {jQuery} $btn The submit button element.
		 */
		submitFeedback: function ( $btn ) {
			const self     = this;
			const feedback = this.$feedbackText.val();

			// Loading state.
			$btn.prop( 'disabled', true ).text( aistmaReviewNotice.i18n.sending );

			$.post( aistmaReviewNotice.ajaxUrl, {
				action:   'aistma_notice_feedback',
				nonce:    aistmaReviewNotice.nonceFeedback,
				rating:   this.selectedRating,
				feedback: feedback,
			} )
			.done( function ( response ) {
				if ( response && response.success ) {
					// Slide feedback area out, slide thank-you in, then auto-hide.
					self.$feedbackArea.slideUp( 200, function () {
						self.$thankyou.slideDown( 200 );
						setTimeout( function () {
							self.hideNotice();
						}, 3000 );
					} );
				} else {
					self.showButtonError( $btn );
				}
			} )
			.fail( function () {
				// On network error: still hide to avoid blocking UX.
				self.hideNotice();
			} )
			.always( function () {
				$btn.prop( 'disabled', false ).text( aistmaReviewNotice.i18n.sendFeedback );
			} );
		},

		/**
		 * Skip providing feedback — still log the rating and suppress the notice.
		 */
		skipFeedback: function () {
			const self = this;

			$.post( aistmaReviewNotice.ajaxUrl, {
				action:   'aistma_notice_feedback',
				nonce:    aistmaReviewNotice.nonceFeedback,
				rating:   this.selectedRating,
				feedback: '',
			} ).always( function () {
				self.hideNotice();
			} );
		},

		// -----------------------------------------------------------------------
		// Dismiss flow
		// -----------------------------------------------------------------------

		/**
		 * Dismiss the notice with a 30-day cooldown.
		 *
		 * The notice hides immediately for snappy UX; the AJAX runs in the background.
		 */
		dismiss: function () {
			const self = this;

			// Hide immediately — don't wait for server.
			this.hideNotice();

			$.post( aistmaReviewNotice.ajaxUrl, {
				action: 'aistma_notice_dismiss',
				nonce:  aistmaReviewNotice.nonceDismiss,
			} );
		},

		// -----------------------------------------------------------------------
		// Utilities
		// -----------------------------------------------------------------------

		/**
		 * Animate the notice out and remove it from the DOM.
		 */
		hideNotice: function () {
			this.$notice.slideUp( 250, function () {
				$( this ).remove();
			} );
		},

		/**
		 * Temporarily show an error label on the submit button.
		 *
		 * @param {jQuery} $btn
		 */
		showButtonError: function ( $btn ) {
			$btn.prop( 'disabled', false ).text( aistmaReviewNotice.i18n.errorGeneric );
			setTimeout( function () {
				$btn.text( aistmaReviewNotice.i18n.sendFeedback );
			}, 3000 );
		},
	};

	// Boot on DOM ready.
	$( document ).ready( function () {
		ReviewNotice.init();
	} );

} )( jQuery );
