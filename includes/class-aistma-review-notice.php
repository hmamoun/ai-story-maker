<?php
/**
 * AI Story Maker — Admin Review Notice
 *
 * Displays a friendly admin notice asking users to rate the plugin on WordPress.org.
 * Appears at the top of all WP admin pages for site administrators.
 *
 * Architecture
 * ------------
 * AISTMA_Review_Notice is self-contained: it owns its hooks, AJAX handlers,
 * and asset enqueueing. It intentionally shares META_KEY_NEVER_SHOW with
 * AISTMA_Rating_Request so that a rating submitted via the post-save popup
 * modal also silences this notice — and vice versa — preventing users from
 * seeing two independent rating requests.
 *
 * Flow
 * ----
 *  1. Notice shows when engagement threshold is met and notice is not
 *     permanently suppressed or within the 30-day dismiss cooldown.
 *  2. Dismiss (×)   → stores per-user timestamp → notice re-appears in 30 days.
 *  3. 4–5 stars     → AJAX `aistma_notice_rate`     → mark never_show
 *                   → JS redirects to WP.org review form (new tab).
 *  4. 1–3 stars     → JS reveals inline feedback textarea.
 *     Submit/Skip   → AJAX `aistma_notice_feedback` → mark never_show
 *                   → JS shows thank-you message → auto-hides notice.
 *
 * Engagement threshold (OR logic — either condition is sufficient):
 *  A. User has generated ≥ 5 stories (uses AISTMA_Rating_Request counter).
 *  B. Plugin has been active for ≥ 7 days (via aistma_activated_at option).
 *
 * @package AI_Story_Maker
 * @since   2.4.0
 */

namespace exedotcom\aistorymaker;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Class AISTMA_Review_Notice
 *
 * Manages the admin review notice: display logic, asset enqueueing, and AJAX handlers.
 */
class AISTMA_Review_Notice {

	// =========================================================================
	// Constants
	// =========================================================================

	/**
	 * Site-wide option: UTC timestamp of first plugin activation.
	 * Written once via add_option() — never overwritten. Used for the
	 * "plugin active for N days" engagement threshold.
	 */
	const OPTION_ACTIVATED_AT = 'aistma_activated_at';

	/**
	 * User meta key: UTC timestamp of the last "remind me later" dismissal.
	 * Notice re-appears after DISMISS_COOLDOWN_DAYS days.
	 */
	const META_KEY_DISMISSED_AT = 'aistma_review_notice_dismissed_at';

	/**
	 * User meta key: permanent suppression flag.
	 *
	 * Intentionally mirrors AISTMA_Rating_Request::META_KEY_NEVER_SHOW so that
	 * a rating submitted through either UI suppresses both the modal and the notice.
	 */
	const META_KEY_NEVER_SHOW = 'aistma_rating_never_show';

	/** Minimum story generations before the notice appears (Criterion A). */
	const GENERATION_THRESHOLD = 5;

	/** Minimum days the plugin must be active before the notice appears (Criterion B). */
	const DAYS_ACTIVE_THRESHOLD = 7;

	/** Days before a dismissed notice re-appears. */
	const DISMISS_COOLDOWN_DAYS = 30;

	/** WordPress.org review URL — pre-filtered to 5 stars, opens the form directly. */
	const REVIEW_URL = 'https://wordpress.org/support/plugin/ai-story-maker/reviews/?filter=5#new-post';

	// =========================================================================
	// Boot
	// =========================================================================

	/**
	 * Constructor — registers all WordPress hooks.
	 *
	 * AJAX actions are registered unconditionally (for all logged-in users) so they
	 * are always reachable. Capability checks are enforced inside each handler.
	 *
	 * Display/asset hooks are deferred to admin_init where we can safely call
	 * current_user_can() and gate them on manage_options.
	 */
	public function __construct() {
		// Gate display and asset hooks on manage_options (checked at admin_init).
		add_action( 'admin_init', array( $this, 'register_display_hooks' ) );

		// AJAX handlers — always registered; capability is checked inside.
		add_action( 'wp_ajax_aistma_notice_dismiss',  array( $this, 'ajax_dismiss' ) );
		add_action( 'wp_ajax_aistma_notice_rate',     array( $this, 'ajax_rate' ) );
		add_action( 'wp_ajax_aistma_notice_feedback', array( $this, 'ajax_feedback' ) );
	}

	/**
	 * Register the admin_notices and admin_enqueue_scripts hooks.
	 *
	 * Called on admin_init — only wires up hooks for manage_options users.
	 *
	 * @return void
	 */
	public function register_display_hooks() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		add_action( 'admin_notices',         array( $this, 'render_notice' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
	}

	// =========================================================================
	// Display Logic
	// =========================================================================

	/**
	 * Determine whether the review notice should be shown to the current user.
	 *
	 * Checks (short-circuit on first false):
	 *  1. Not permanently suppressed.
	 *  2. Not within the 30-day dismiss cooldown.
	 *  3. Engagement threshold met (5+ generations OR 7+ days active).
	 *
	 * @return bool True if the notice should be rendered.
	 */
	public static function should_show_notice() {
		$user_id = get_current_user_id();
		if ( $user_id <= 0 ) {
			return false;
		}

		// 1. Permanently suppressed? (shared flag with post-save rating modal).
		if ( get_user_meta( $user_id, self::META_KEY_NEVER_SHOW, true ) ) {
			return false;
		}

		// 2. Within the 30-day dismiss cooldown?
		$dismissed_at = absint( get_user_meta( $user_id, self::META_KEY_DISMISSED_AT, true ) );
		if ( $dismissed_at > 0 ) {
			$days_since = ( time() - $dismissed_at ) / DAY_IN_SECONDS;
			if ( $days_since < self::DISMISS_COOLDOWN_DAYS ) {
				return false;
			}
		}

		// 3. Has the user done enough with the plugin?
		return self::has_met_engagement_threshold( $user_id );
	}

	/**
	 * Check whether the user meets at least one engagement criterion.
	 *
	 * Criterion A: generated ≥ 5 stories.
	 * Criterion B: plugin active for ≥ 7 days.
	 *
	 * Using OR so long-time users who haven't generated stories still see the
	 * notice — and prolific users see it sooner regardless of install age.
	 *
	 * @param int $user_id WordPress user ID.
	 * @return bool
	 */
	private static function has_met_engagement_threshold( $user_id ) {
		// Criterion A: generation count.
		if ( class_exists( __NAMESPACE__ . '\\AISTMA_Rating_Request' ) ) {
			if ( AISTMA_Rating_Request::get_generation_count( $user_id ) >= self::GENERATION_THRESHOLD ) {
				return true;
			}
		}

		// Criterion B: plugin age.
		$activated_at = absint( get_option( self::OPTION_ACTIVATED_AT, 0 ) );
		if ( $activated_at > 0 ) {
			$days_active = ( time() - $activated_at ) / DAY_IN_SECONDS;
			if ( $days_active >= self::DAYS_ACTIVE_THRESHOLD ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Render the admin notice HTML.
	 *
	 * Outputs nothing when should_show_notice() returns false.
	 * The notice is structured as a WP .notice bar with:
	 *  - Icon + message text.
	 *  - Five clickable star buttons.
	 *  - WP-standard dismiss (×) button.
	 *  - Hidden low-rating feedback section.
	 *  - Hidden thank-you section.
	 *
	 * @return void
	 */
	public function render_notice() {
		if ( ! self::should_show_notice() ) {
			return;
		}
		?>
		<div id="aistma-review-notice" class="aistma-review-notice notice">

			<!-- Primary row: icon, message, stars, dismiss -->
			<div class="aistma-rn-inner">

				<span class="aistma-rn-icon" aria-hidden="true">🌟</span>

				<p class="aistma-rn-message">
					<strong><?php esc_html_e( 'Enjoying AI Story Maker?', 'ai-story-maker' ); ?></strong>
					<?php esc_html_e( 'Your review helps other creators find it. How would you rate it?', 'ai-story-maker' ); ?>
				</p>

				<!-- Star rating buttons -->
				<div
					class="aistma-rn-stars"
					role="group"
					aria-label="<?php esc_attr_e( 'Rate AI Story Maker', 'ai-story-maker' ); ?>"
				>
					<?php for ( $i = 1; $i <= 5; $i++ ) : ?>
						<button
							type="button"
							class="aistma-rn-star"
							data-value="<?php echo absint( $i ); ?>"
							aria-label="<?php
								/* translators: %d: star count */
								printf( esc_attr__( '%d out of 5 stars', 'ai-story-maker' ), absint( $i ) );
							?>"
						>★</button>
					<?php endfor; ?>
				</div>

				<!-- WP-style dismiss button -->
				<button
					type="button"
					class="aistma-rn-dismiss notice-dismiss"
					aria-label="<?php esc_attr_e( 'Dismiss this notice', 'ai-story-maker' ); ?>"
				></button>

			</div><!-- .aistma-rn-inner -->

			<!-- Low-rating feedback area (1–3 stars) — hidden until needed -->
			<div class="aistma-rn-feedback" style="display:none;" aria-live="polite">
				<p class="aistma-rn-feedback-prompt">
					<?php esc_html_e( "We're sorry to hear that. What can we improve?", 'ai-story-maker' ); ?>
				</p>
				<textarea
					class="aistma-rn-feedback-text"
					rows="3"
					placeholder="<?php esc_attr_e( 'Your feedback (optional)…', 'ai-story-maker' ); ?>"
					maxlength="1000"
				></textarea>
				<div class="aistma-rn-feedback-actions">
					<button type="button" class="button button-primary aistma-rn-feedback-submit">
						<?php esc_html_e( 'Send Feedback', 'ai-story-maker' ); ?>
					</button>
					<button type="button" class="button aistma-rn-feedback-skip">
						<?php esc_html_e( 'Skip', 'ai-story-maker' ); ?>
					</button>
				</div>
			</div><!-- .aistma-rn-feedback -->

			<!-- Thank-you message — shown after feedback is submitted -->
			<div class="aistma-rn-thankyou" style="display:none;" aria-live="polite">
				<p>
					<?php esc_html_e( '🙏 Thank you for your feedback! We will use it to make AI Story Maker better.', 'ai-story-maker' ); ?>
				</p>
			</div><!-- .aistma-rn-thankyou -->

		</div><!-- #aistma-review-notice -->
		<?php
	}

	// =========================================================================
	// Asset Enqueueing
	// =========================================================================

	/**
	 * Enqueue notice scripts and styles on all admin pages where the notice may appear.
	 *
	 * Assets are only loaded when should_show_notice() returns true, keeping
	 * the feature completely invisible (zero overhead) for users who don't qualify.
	 *
	 * @param string $hook_suffix Current admin page hook suffix (unused; gated by should_show_notice).
	 * @return void
	 */
	public function enqueue_assets( $hook_suffix ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter
		if ( ! self::should_show_notice() ) {
			return;
		}

		wp_enqueue_style(
			'aistma-review-notice-css',
			AISTMA_URL . 'admin/css/review-notice.css',
			array(),
			filemtime( AISTMA_PATH . 'admin/css/review-notice.css' )
		);

		wp_enqueue_script(
			'aistma-review-notice-js',
			AISTMA_URL . 'admin/js/review-notice.js',
			array( 'jquery' ),
			filemtime( AISTMA_PATH . 'admin/js/review-notice.js' ),
			true // Load in footer.
		);

		wp_localize_script(
			'aistma-review-notice-js',
			'aistmaReviewNotice',
			array(
				'ajaxUrl'       => admin_url( 'admin-ajax.php' ),
				'reviewUrl'     => esc_url( self::REVIEW_URL ),
				'nonceDismiss'  => wp_create_nonce( 'aistma_notice_dismiss_nonce' ),
				'nonceRate'     => wp_create_nonce( 'aistma_notice_rate_nonce' ),
				'nonceFeedback' => wp_create_nonce( 'aistma_notice_feedback_nonce' ),
				'i18n'          => array(
					'sending'      => __( 'Sending…', 'ai-story-maker' ),
					'sendFeedback' => __( 'Send Feedback', 'ai-story-maker' ),
					'errorGeneric' => __( 'Something went wrong. Please try again.', 'ai-story-maker' ),
				),
			)
		);
	}

	// =========================================================================
	// AJAX Handlers
	// =========================================================================

	/**
	 * AJAX: dismiss the notice (30-day cooldown).
	 *
	 * Stores current timestamp in user meta. After DISMISS_COOLDOWN_DAYS days
	 * the notice will re-appear via should_show_notice().
	 *
	 * POST: nonce
	 *
	 * @return void Sends JSON and exits.
	 */
	public function ajax_dismiss() {
		if ( ! check_ajax_referer( 'aistma_notice_dismiss_nonce', 'nonce', false ) ) {
			wp_send_json_error( array( 'message' => __( 'Security check failed.', 'ai-story-maker' ) ) );
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'ai-story-maker' ) ) );
		}

		update_user_meta( get_current_user_id(), self::META_KEY_DISMISSED_AT, time() );

		wp_send_json_success();
	}

	/**
	 * AJAX: user selected 4–5 stars — permanently suppress and return review URL.
	 *
	 * The JS will open the review URL in a new tab after receiving success.
	 *
	 * POST: nonce, rating (int 1–5)
	 *
	 * @return void Sends JSON and exits.
	 */
	public function ajax_rate() {
		if ( ! check_ajax_referer( 'aistma_notice_rate_nonce', 'nonce', false ) ) {
			wp_send_json_error( array( 'message' => __( 'Security check failed.', 'ai-story-maker' ) ) );
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'ai-story-maker' ) ) );
		}

		$user_id = get_current_user_id();
		$rating  = isset( $_POST['rating'] ) ? absint( $_POST['rating'] ) : 0;

		if ( $rating < 1 || $rating > 5 ) {
			wp_send_json_error( array( 'message' => __( 'Invalid rating value.', 'ai-story-maker' ) ) );
		}

		// Permanently suppress both the notice and the post-save modal.
		update_user_meta( $user_id, self::META_KEY_NEVER_SHOW, true );

		// Log to gateway (non-blocking — failure is acceptable).
		if ( class_exists( __NAMESPACE__ . '\\AISTMA_Gateway_Logger' ) ) {
			AISTMA_Gateway_Logger::log_rating_submitted( $user_id, 0, $rating );
		}

		wp_send_json_success( array(
			'review_url' => esc_url( self::REVIEW_URL ),
		) );
	}

	/**
	 * AJAX: user submitted low-rating feedback (1–3 stars) or skipped it.
	 *
	 * Appends the feedback entry to the `aistma_review_feedback` option and
	 * permanently suppresses both the notice and the post-save modal.
	 *
	 * POST: nonce, rating (int 1–5), feedback (string, may be empty)
	 *
	 * @return void Sends JSON and exits.
	 */
	public function ajax_feedback() {
		if ( ! check_ajax_referer( 'aistma_notice_feedback_nonce', 'nonce', false ) ) {
			wp_send_json_error( array( 'message' => __( 'Security check failed.', 'ai-story-maker' ) ) );
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'ai-story-maker' ) ) );
		}

		$user_id  = get_current_user_id();
		$rating   = isset( $_POST['rating'] ) ? absint( $_POST['rating'] ) : 0;
		$feedback = isset( $_POST['feedback'] )
			? sanitize_textarea_field( wp_unslash( $_POST['feedback'] ) )
			: '';

		if ( $rating < 1 || $rating > 5 ) {
			wp_send_json_error( array( 'message' => __( 'Invalid rating value.', 'ai-story-maker' ) ) );
		}

		// Persist feedback (append to list; autoload=false keeps it out of the options cache).
		$all_feedback = get_option( 'aistma_review_feedback', array() );
		if ( ! is_array( $all_feedback ) ) {
			$all_feedback = array();
		}
		$all_feedback[] = array(
			'user_id'   => $user_id,
			'rating'    => $rating,
			'feedback'  => $feedback,
			'submitted' => current_time( 'mysql' ),
		);
		update_option( 'aistma_review_feedback', $all_feedback, false );

		// Permanently suppress notice and modal.
		update_user_meta( $user_id, self::META_KEY_NEVER_SHOW, true );

		// Email the site admin so feedback doesn't go unread.
		if ( ! empty( $feedback ) ) {
			$user       = get_userdata( $user_id );
			$user_label = $user ? $user->user_email : sprintf( __( 'User #%d', 'ai-story-maker' ), $user_id );
			$subject    = sprintf(
				/* translators: %d: star rating 1–3 */
				__( '[AI Story Maker] New %d-star feedback from your site', 'ai-story-maker' ),
				$rating
			);
			$body = sprintf(
				/* translators: 1: star rating, 2: user email or ID, 3: site URL, 4: feedback text */
				__(
					"A user left feedback on AI Story Maker:\n\nRating : %d / 5\nUser   : %s\nSite   : %s\n\nFeedback:\n%s",
					'ai-story-maker'
				),
				$rating,
				$user_label,
				home_url(),
				$feedback
			);
			wp_mail( get_option( 'admin_email' ), $subject, $body );
		}

		// Log to gateway.
		if ( class_exists( __NAMESPACE__ . '\\AISTMA_Gateway_Logger' ) ) {
			AISTMA_Gateway_Logger::log_rating_submitted( $user_id, 0, $rating, $feedback );
		}

		wp_send_json_success( array(
			'message' => __( 'Thank you for your feedback!', 'ai-story-maker' ),
		) );
	}

	// =========================================================================
	// Static Helpers
	// =========================================================================

	/**
	 * Record the plugin's first activation timestamp.
	 *
	 * Uses add_option() so the value is written once and never overwritten
	 * on subsequent activations or updates. Call from the activation hook.
	 *
	 * @return void
	 */
	public static function record_activation_time() {
		// add_option is a no-op if the option already exists — safe to call repeatedly.
		add_option( self::OPTION_ACTIVATED_AT, time(), '', false );
	}
}
