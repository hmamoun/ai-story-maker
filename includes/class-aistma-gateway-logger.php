<?php
/**
 * AI Story Maker Gateway Logger Integration
 *
 * Integrates with exedotcom API Gateway logger for tracking events.
 * Uses the Exaig_Logger pattern to log wizard and generation events.
 *
 * @package AI_Story_Maker
 * @since   2.2.0
 */

namespace exedotcom\aistorymaker;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Class AISTMA_Gateway_Logger
 *
 * Handles event logging to exedotcom API Gateway.
 */
class AISTMA_Gateway_Logger {

	/**
	 * Log wizard activation event.
	 *
	 * @param int $user_id The user ID activating the wizard.
	 * @return bool True if logged successfully, false otherwise.
	 */
	public static function log_wizard_activated( $user_id ) {
		return self::log_event(
			array(
				'event_type'  => 'aistma_wizard_activated',
				'user_id'     => $user_id,
				'credits_granted' => get_option( 'aistma_startup_credit_amount', 5 ),
				'timestamp'   => current_time( 'mysql' ),
			)
		);
	}

	/**
	 * Log prompt selection event.
	 *
	 * @param int    $user_id   The user ID.
	 * @param string $prompt_id The selected prompt ID.
	 * @param array  $data      Additional event data (optional).
	 * @return bool True if logged successfully, false otherwise.
	 */
	public static function log_prompt_selected( $user_id, $prompt_id, $data = array() ) {
		$event_data = array_merge(
			array(
				'event_type' => 'aistma_prompt_selected',
				'user_id'    => $user_id,
				'prompt_id'  => $prompt_id,
				'timestamp'  => current_time( 'mysql' ),
			),
			$data
		);

		return self::log_event( $event_data );
	}

	/**
	 * Log story generation event.
	 *
	 * @param int    $user_id     The user ID.
	 * @param int    $post_id     The generated post ID.
	 * @param string $prompt_id   The prompt ID used.
	 * @param int    $credits_used Credits deducted for generation.
	 * @param array  $data        Additional event data (optional).
	 * @return bool True if logged successfully, false otherwise.
	 */
	public static function log_story_generated( $user_id, $post_id, $prompt_id, $credits_used = 1, $data = array() ) {
		$event_data = array_merge(
			array(
				'event_type'    => 'aistma_story_generated',
				'user_id'       => $user_id,
				'post_id'       => $post_id,
				'prompt_id'     => $prompt_id,
				'credits_used'  => $credits_used,
				'timestamp'     => current_time( 'mysql' ),
			),
			$data
		);

		return self::log_event( $event_data );
	}

	/**
	 * Log rating submission event.
	 *
	 * @param int    $user_id   The user ID.
	 * @param int    $post_id   The post being rated.
	 * @param int    $rating    The rating value (1-5).
	 * @param string $feedback  User feedback (optional).
	 * @return bool True if logged successfully, false otherwise.
	 */
	public static function log_rating_submitted( $user_id, $post_id, $rating, $feedback = '' ) {
		return self::log_event(
			array(
				'event_type' => 'aistma_rating_submitted',
				'user_id'    => $user_id,
				'post_id'    => $post_id,
				'rating'     => absint( $rating ),
				'feedback'   => sanitize_textarea_field( $feedback ),
				'timestamp'  => current_time( 'mysql' ),
			)
		);
	}

	/**
	 * Log weekly schedule opt-in event.
	 *
	 * @param int  $user_id    The user ID.
	 * @param bool $enabled    Whether weekly scheduling is enabled.
	 * @param int  $frequency  Generation frequency (days).
	 * @return bool True if logged successfully, false otherwise.
	 */
	public static function log_weekly_schedule_enabled( $user_id, $enabled = true, $frequency = 7 ) {
		return self::log_event(
			array(
				'event_type'  => 'aistma_weekly_schedule_enabled',
				'user_id'     => $user_id,
				'enabled'     => (bool) $enabled,
				'frequency'   => absint( $frequency ),
				'timestamp'   => current_time( 'mysql' ),
			)
		);
	}

	/**
	 * Generic event logging method that integrates with exedotcom gateway logger.
	 *
	 * @param array $event_data Event data to log.
	 * @return bool True if logged successfully, false otherwise.
	 */
	public static function log_event( $event_data ) {
		// Ensure exedotcom logger exists
		if ( ! class_exists( '\\Exedotcom\\ApiGateway\\Exaig_Logger' ) ) {
			// Logger not available, fail gracefully without blocking
			return false;
		}

		try {
			$event_type = $event_data['event_type'] ?? 'unknown';
			$user_id    = $event_data['user_id'] ?? 0;

			// Prepare the log details
			$log_details = array(
				'event_type' => $event_type,
				'user_id'    => $user_id,
				'domain'     => sanitize_text_field( $_SERVER['HTTP_HOST'] ?? get_home_url() ),
			);

			// Add all additional data from event
			foreach ( $event_data as $key => $value ) {
				if ( ! in_array( $key, array( 'event_type', 'timestamp' ), true ) ) {
					$log_details[ $key ] = $value;
				}
			}

			// Use exedotcom's logger
			$request_url = wp_get_referer() ?: get_home_url();
			$request_ip  = self::get_client_ip();

			// Call the logger - don't let failures block execution
			\Exedotcom\ApiGateway\Exaig_Logger::log(
				$request_url,
				$request_ip,
				'aistma',
				self::get_action_from_event( $event_type ),
				'success',
				$log_details
			);

			return true;
		} catch ( \Throwable $e ) {
			// Log failures gracefully - don't block story generation or user actions
			if ( class_exists( '\\exedotcom\\aistorymaker\\AISTMA_Log_Manager' ) ) {
				$log_manager = new AISTMA_Log_Manager();
				$log_manager->log( 'warning', 'Gateway logger error: ' . $e->getMessage() );
			}
			return false;
		}
	}

	/**
	 * Get the action name from an event type for logging.
	 *
	 * @param string $event_type The event type constant.
	 * @return string The action name for the logger.
	 */
	private static function get_action_from_event( $event_type ) {
		$action_map = array(
			'aistma_wizard_activated'       => 'wizard_activated',
			'aistma_prompt_selected'        => 'prompt_selected',
			'aistma_story_generated'        => 'story_generated',
			'aistma_rating_submitted'       => 'rating_submitted',
			'aistma_weekly_schedule_enabled' => 'weekly_schedule_enabled',
		);

		return $action_map[ $event_type ] ?? sanitize_key( $event_type );
	}

	/**
	 * Get client IP address safely.
	 *
	 * @return string The client IP address.
	 */
	private static function get_client_ip() {
		$ip = '';

		if ( ! empty( $_SERVER['HTTP_CLIENT_IP'] ) ) {
			$ip = $_SERVER['HTTP_CLIENT_IP'];
		} elseif ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
			$ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
		} elseif ( ! empty( $_SERVER['REMOTE_ADDR'] ) ) {
			$ip = $_SERVER['REMOTE_ADDR'];
		}

		// Basic validation
		$ip = filter_var( $ip, FILTER_VALIDATE_IP );

		return $ip ? sanitize_text_field( $ip ) : '0.0.0.0';
	}
}
