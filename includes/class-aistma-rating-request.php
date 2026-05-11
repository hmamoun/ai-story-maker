<?php
/**
 * AI Story Maker Rating Request Modal
 *
 * Handles display logic and tracking for rating requests after user generates stories.
 * Shows a modal after the user's 5th generation to drive marketplace engagement.
 *
 * @package AI_Story_Maker
 * @since   2.3.0
 */

namespace exedotcom\aistorymaker;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Class AISTMA_Rating_Request
 *
 * Manages rating request display logic and generation count tracking.
 */
class AISTMA_Rating_Request {

	// User meta keys
	const META_KEY_GENERATION_COUNT = 'aistma_generation_count';
	const META_KEY_RATING_SHOWN = 'aistma_rating_last_shown';
	const META_KEY_NEVER_SHOW = 'aistma_rating_never_show';

	// Trigger settings
	const GENERATION_THRESHOLD = 5;
	const REMINDER_COOLDOWN_DAYS = 7;

	/**
	 * Increment generation count for a user.
	 *
	 * @param int $user_id The user ID.
	 * @return int New generation count.
	 */
	public static function increment_generation_count( $user_id ) {
		$user_id = absint( $user_id );
		$current = self::get_generation_count( $user_id );
		$new_count = $current + 1;

		update_user_meta( $user_id, self::META_KEY_GENERATION_COUNT, $new_count );

		return $new_count;
	}

	/**
	 * Get current generation count for a user.
	 *
	 * @param int $user_id The user ID.
	 * @return int Current generation count (default 0).
	 */
	public static function get_generation_count( $user_id ) {
		$user_id = absint( $user_id );
		$count = get_user_meta( $user_id, self::META_KEY_GENERATION_COUNT, true );
		return absint( $count );
	}

	/**
	 * Mark rating modal as shown for a user.
	 *
	 * @param int $user_id The user ID.
	 * @return void
	 */
	public static function mark_rating_shown( $user_id ) {
		$user_id = absint( $user_id );
		update_user_meta( $user_id, self::META_KEY_RATING_SHOWN, time() );
	}

	/**
	 * Check if rating should be shown to user.
	 *
	 * Logic:
	 * - Generation count >= 5
	 * - Never shown OR shown 7+ days ago
	 * - User hasn't clicked "never ask again"
	 *
	 * @param int $user_id The user ID.
	 * @return bool True if rating should be shown, false otherwise.
	 */
	public static function should_show_rating( $user_id ) {
		$user_id = absint( $user_id );

		// Check if user permanently dismissed
		$never_show = get_user_meta( $user_id, self::META_KEY_NEVER_SHOW, true );
		if ( $never_show ) {
			return false;
		}

		// Check generation threshold
		$generation_count = self::get_generation_count( $user_id );
		if ( $generation_count < self::GENERATION_THRESHOLD ) {
			return false;
		}

		// Check if never shown before
		$last_shown = get_user_meta( $user_id, self::META_KEY_RATING_SHOWN, true );
		if ( ! $last_shown ) {
			return true;
		}

		// Check cooldown (7 days)
		$last_shown_timestamp = absint( $last_shown );
		$days_since = floor( ( time() - $last_shown_timestamp ) / DAY_IN_SECONDS );

		return $days_since >= self::REMINDER_COOLDOWN_DAYS;
	}

	/**
	 * Mark rating to never show again for user.
	 *
	 * @param int $user_id The user ID.
	 * @return void
	 */
	public static function mark_never_show( $user_id ) {
		$user_id = absint( $user_id );
		update_user_meta( $user_id, self::META_KEY_NEVER_SHOW, true );
	}

	/**
	 * Get rating modal data for display.
	 *
	 * @param int $user_id The user ID.
	 * @return array|false Array with modal data if should show, false otherwise.
	 */
	public static function get_modal_data( $user_id ) {
		if ( ! self::should_show_rating( $user_id ) ) {
			return false;
		}

		return array(
			'should_show' => true,
			'generation_count' => self::get_generation_count( $user_id ),
			'user_id' => $user_id,
			'wordpress_review_url' => 'https://wordpress.org/plugins/ai-story-maker/#reviews',
		);
	}

	/**
	 * Reset generation count and rating state (for testing).
	 *
	 * @param int $user_id The user ID.
	 * @return void
	 */
	public static function reset_for_testing( $user_id ) {
		$user_id = absint( $user_id );

		delete_user_meta( $user_id, self::META_KEY_GENERATION_COUNT );
		delete_user_meta( $user_id, self::META_KEY_RATING_SHOWN );
		delete_user_meta( $user_id, self::META_KEY_NEVER_SHOW );
	}

}
