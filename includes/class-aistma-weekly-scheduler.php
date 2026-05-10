<?php
/**
 * AI Story Maker Weekly Scheduler Class
 *
 * Manages weekly auto-generation of stories for users.
 *
 * @package AI_Story_Maker
 * @since   2.3.0
 */

namespace exedotcom\aistorymaker;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class AISTMA_Weekly_Scheduler
 *
 * Handles weekly story auto-generation configuration and status tracking.
 */
class AISTMA_Weekly_Scheduler {

	/**
	 * User meta key for weekly enabled flag.
	 *
	 * @var string
	 */
	const META_KEY_WEEKLY_ENABLED = 'aistma_weekly_enabled';

	/**
	 * User meta key for weekly prompt ID.
	 *
	 * @var string
	 */
	const META_KEY_WEEKLY_PROMPT_ID = 'aistma_weekly_prompt_id';

	/**
	 * User meta key for last weekly generation timestamp.
	 *
	 * @var string
	 */
	const META_KEY_WEEKLY_LAST_GENERATED = 'aistma_weekly_last_generated';

	/**
	 * Check if weekly auto-generation is enabled for a user.
	 *
	 * @param int $user_id The user ID.
	 * @return bool True if weekly generation is enabled.
	 */
	public static function is_weekly_enabled( $user_id ) {
		$enabled = get_user_meta( $user_id, self::META_KEY_WEEKLY_ENABLED, true );
		return (bool) $enabled;
	}

	/**
	 * Get the saved prompt ID for weekly generation.
	 *
	 * @param int $user_id The user ID.
	 * @return int|false The prompt ID, or false if not set.
	 */
	public static function get_weekly_prompt( $user_id ) {
		$prompt_id = get_user_meta( $user_id, self::META_KEY_WEEKLY_PROMPT_ID, true );
		return $prompt_id ? absint( $prompt_id ) : false;
	}

	/**
	 * Enable weekly auto-generation and save the prompt.
	 *
	 * @param int $user_id   The user ID.
	 * @param int $prompt_id The prompt post ID to use for weekly generation.
	 * @return bool True on success.
	 */
	public static function enable_weekly( $user_id, $prompt_id ) {
		$user_id   = absint( $user_id );
		$prompt_id = absint( $prompt_id );

		if ( ! $user_id || ! $prompt_id ) {
			return false;
		}

		// Verify the prompt exists
		$prompt_post = get_post( $prompt_id );
		if ( ! $prompt_post || 'aistma_prompt' !== $prompt_post->post_type ) {
			return false;
		}

		// Save the prompt ID
		update_user_meta( $user_id, self::META_KEY_WEEKLY_PROMPT_ID, $prompt_id );

		// Enable the flag
		update_user_meta( $user_id, self::META_KEY_WEEKLY_ENABLED, 1 );

		return true;
	}

	/**
	 * Disable weekly auto-generation without deleting the saved prompt.
	 *
	 * @param int $user_id The user ID.
	 * @return bool True on success.
	 */
	public static function disable_weekly( $user_id ) {
		$user_id = absint( $user_id );

		if ( ! $user_id ) {
			return false;
		}

		update_user_meta( $user_id, self::META_KEY_WEEKLY_ENABLED, 0 );
		return true;
	}

	/**
	 * Check if it's time to generate a weekly story for a user.
	 *
	 * Should be called during cron event to determine if weekly generation should proceed.
	 *
	 * @param int $user_id The user ID.
	 * @return bool True if weekly generation should happen now.
	 */
	public static function should_generate_weekly( $user_id ) {
		if ( ! self::is_weekly_enabled( $user_id ) ) {
			return false;
		}

		// Get last generation timestamp
		$last_generated = get_user_meta( $user_id, self::META_KEY_WEEKLY_LAST_GENERATED, true );
		if ( ! $last_generated ) {
			// Never generated before, so generate now
			return true;
		}

		// Check if 7 days have passed since last generation
		$last_generated_time = absint( $last_generated );
		$time_since_last      = time() - $last_generated_time;
		$seven_days_in_seconds = 7 * DAY_IN_SECONDS;

		return $time_since_last >= $seven_days_in_seconds;
	}

	/**
	 * Update the last weekly generation timestamp.
	 *
	 * @param int $user_id The user ID.
	 * @return bool True on success.
	 */
	public static function update_last_generated( $user_id ) {
		$user_id = absint( $user_id );

		if ( ! $user_id ) {
			return false;
		}

		update_user_meta( $user_id, self::META_KEY_WEEKLY_LAST_GENERATED, time() );
		return true;
	}

	/**
	 * Get all users with weekly generation enabled.
	 *
	 * @return array Array of user IDs with weekly enabled.
	 */
	public static function get_weekly_enabled_users() {
		$args = array(
			'meta_key'     => self::META_KEY_WEEKLY_ENABLED,
			'meta_value'   => 1,
			'fields'       => 'ID',
			'number'       => -1,
		);

		return get_users( $args );
	}

	/**
	 * Get weekly generation data for a user.
	 *
	 * @param int $user_id The user ID.
	 * @return array|false Array with keys: enabled, prompt_id, last_generated, or false if disabled.
	 */
	public static function get_user_weekly_data( $user_id ) {
		if ( ! self::is_weekly_enabled( $user_id ) ) {
			return false;
		}

		return array(
			'enabled'        => true,
			'prompt_id'      => self::get_weekly_prompt( $user_id ),
			'last_generated' => get_user_meta( $user_id, self::META_KEY_WEEKLY_LAST_GENERATED, true ),
		);
	}
}
