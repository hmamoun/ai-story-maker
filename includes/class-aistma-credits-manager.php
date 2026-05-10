<?php
/**
 * AI Story Maker Credits Manager
 *
 * Handles user credit balance, deductions, and transaction history.
 * Credits are stored in user meta for portability and simplicity.
 *
 * @package AI_Story_Maker
 * @since   2.2.0
 */

namespace exedotcom\aistorymaker;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Class AISTMA_Credits_Manager
 *
 * Manages user credits for story generation.
 */
class AISTMA_Credits_Manager {

	const META_KEY_BALANCE = 'aistma_user_credits';
	const META_KEY_HISTORY = 'aistma_credit_history';

	/**
	 * Get current credit balance for a user.
	 *
	 * @param int $user_id The user ID.
	 * @return int The current credit balance (default 0 if never set).
	 */
	public static function get_user_credits( $user_id ) {
		$balance = get_user_meta( $user_id, self::META_KEY_BALANCE, true );
		return absint( $balance );
	}

	/**
	 * Check if user has sufficient credits.
	 *
	 * @param int $user_id The user ID.
	 * @param int $amount  Number of credits to check (default 1).
	 * @return bool True if user has at least $amount credits.
	 */
	public static function has_credits( $user_id, $amount = 1 ) {
		$balance = self::get_user_credits( $user_id );
		return $balance >= absint( $amount );
	}

	/**
	 * Deduct credits from a user's balance.
	 *
	 * @param int    $user_id The user ID.
	 * @param int    $amount  Number of credits to deduct (default 1).
	 * @param string $reason  Reason for deduction (optional).
	 * @return int|false New balance after deduction, or false if insufficient credits.
	 */
	public static function deduct_credits( $user_id, $amount = 1, $reason = '' ) {
		$user_id = absint( $user_id );
		$amount  = absint( $amount );

		if ( $amount <= 0 ) {
			return false;
		}

		if ( ! self::has_credits( $user_id, $amount ) ) {
			return false;
		}

		$current_balance = self::get_user_credits( $user_id );
		$new_balance     = $current_balance - $amount;

		update_user_meta( $user_id, self::META_KEY_BALANCE, $new_balance );

		// Log transaction
		self::log_transaction( $user_id, 'deduction', $amount, $new_balance, $reason );

		return $new_balance;
	}

	/**
	 * Add credits to a user's balance.
	 *
	 * @param int    $user_id The user ID.
	 * @param int    $amount  Number of credits to add (default 1).
	 * @param string $reason  Reason for addition (optional).
	 * @return int New balance after addition.
	 */
	public static function add_credits( $user_id, $amount = 1, $reason = '' ) {
		$user_id = absint( $user_id );
		$amount  = absint( $amount );

		if ( $amount <= 0 ) {
			return self::get_user_credits( $user_id );
		}

		$current_balance = self::get_user_credits( $user_id );
		$new_balance     = $current_balance + $amount;

		update_user_meta( $user_id, self::META_KEY_BALANCE, $new_balance );

		// Log transaction
		self::log_transaction( $user_id, 'addition', $amount, $new_balance, $reason );

		return $new_balance;
	}

	/**
	 * Get credit transaction history for a user.
	 *
	 * @param int $user_id The user ID.
	 * @param int $limit   Maximum number of transactions to return (default 50).
	 * @return array Array of transaction records.
	 */
	public static function get_credit_history( $user_id, $limit = 50 ) {
		$user_id = absint( $user_id );
		$limit   = absint( $limit );

		$history = get_user_meta( $user_id, self::META_KEY_HISTORY, true );

		if ( ! is_array( $history ) ) {
			return array();
		}

		// Sort by timestamp descending (most recent first)
		usort(
			$history,
			function ( $a, $b ) {
				return $b['timestamp'] <=> $a['timestamp'];
			}
		);

		// Return limited results
		return array_slice( $history, 0, $limit );
	}

	/**
	 * Record a credit transaction in history.
	 *
	 * @param int    $user_id The user ID.
	 * @param string $type    Transaction type ('addition' or 'deduction').
	 * @param int    $amount  Amount of credits transacted.
	 * @param int    $balance New balance after transaction.
	 * @param string $reason  Reason for transaction (optional).
	 * @return void
	 */
	private static function log_transaction( $user_id, $type, $amount, $balance, $reason = '' ) {
		$user_id = absint( $user_id );

		$transaction = array(
			'timestamp' => current_time( 'mysql' ),
			'type'      => sanitize_text_field( $type ),
			'amount'    => absint( $amount ),
			'balance'   => absint( $balance ),
			'reason'    => sanitize_text_field( $reason ),
		);

		$history = get_user_meta( $user_id, self::META_KEY_HISTORY, true );

		if ( ! is_array( $history ) ) {
			$history = array();
		}

		$history[] = $transaction;

		// Keep only last 1000 transactions per user (prevent meta bloat)
		if ( count( $history ) > 1000 ) {
			$history = array_slice( $history, -1000 );
		}

		update_user_meta( $user_id, self::META_KEY_HISTORY, $history );
	}

	/**
	 * Reset credits for a user (admin use only).
	 *
	 * @param int $user_id The user ID.
	 * @param int $amount  Amount to set (default 0).
	 * @return int New balance.
	 */
	public static function reset_credits( $user_id, $amount = 0 ) {
		$user_id = absint( $user_id );
		$amount  = absint( $amount );

		update_user_meta( $user_id, self::META_KEY_BALANCE, $amount );

		// Log this administrative action
		self::log_transaction( $user_id, 'reset', 0, $amount, 'Admin reset' );

		return $amount;
	}

	/**
	 * Clear transaction history for a user (admin use only).
	 *
	 * @param int $user_id The user ID.
	 * @return void
	 */
	public static function clear_history( $user_id ) {
		$user_id = absint( $user_id );
		delete_user_meta( $user_id, self::META_KEY_HISTORY );
	}
}
