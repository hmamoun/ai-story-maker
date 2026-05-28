<?php
/**
 * AI Story Maker Activation Wizard
 *
 * Handles the wizard display logic, default prompts, and user interaction tracking.
 * The wizard shows once after plugin activation with 10 default prompts.
 *
 * @package AI_Story_Maker
 * @since   2.2.0
 */

namespace exedotcom\aistorymaker;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Class AISTMA_Activation_Wizard
 *
 * Manages the activation wizard display and default prompts.
 */
class AISTMA_Activation_Wizard {

	const WIZARD_SHOWN_KEY = 'aistma_wizard_shown';
	const WIZARD_LAST_SHOWN_KEY = 'aistma_wizard_last_shown_time';
	const WIZARD_PROMPTS_KEY = 'aistma_default_prompts_v2';

	/**
	 * Check if wizard should be displayed for current user.
	 * Shows once per day unless dismissed with "Don't show again".
	 *
	 * @return bool True if wizard should be shown.
	 */
	public static function maybe_show_wizard() {
		// Only show for admins who can edit posts
		if ( ! current_user_can( 'edit_posts' ) ) {
			return false;
		}

		$user_id = get_current_user_id();
		$wizard_shown = get_user_meta( $user_id, self::WIZARD_SHOWN_KEY, true );

		// If user selected "Don't show again", never show
		if ( ! empty( $wizard_shown ) ) {
			return false;
		}

		// Check if wizard was shown in the last 24 hours
		$last_shown = get_user_meta( $user_id, self::WIZARD_LAST_SHOWN_KEY, true );
		if ( ! empty( $last_shown ) ) {
			$last_shown_time = strtotime( $last_shown );
			$current_time = time();
			$hours_elapsed = ( $current_time - $last_shown_time ) / 3600;

			// If less than 24 hours have passed, don't show
			if ( $hours_elapsed < 24 ) {
				return false;
			}
		}

		// Show wizard
		return true;
	}

	/**
	 * Get default prompts for the wizard.
	 *
	 * @return array Array of prompt objects with metadata.
	 */
	public static function get_default_prompts() {
		// Check if prompts are cached
		$cached_prompts = get_option( self::WIZARD_PROMPTS_KEY );
		if ( ! empty( $cached_prompts ) && is_array( $cached_prompts ) ) {
			return $cached_prompts;
		}

		$prompts = array(
			array(
				'id'          => 'travel-adventure',
				'name'        => 'Travel Adventure',
				'description' => 'Create an engaging travel story about exploring new destinations.',
				'category'    => 'Travel',
				'example'     => 'A mysterious island with ancient ruins...',
				'photos'      => 2,
			),
			array(
				'id'          => 'tech-innovation',
				'name'        => 'Tech Innovation',
				'description' => 'Generate a story about cutting-edge technology and its impact.',
				'category'    => 'Technology',
				'example'     => 'The future of artificial intelligence...',
				'photos'      => 1,
			),
			array(
				'id'          => 'wellness-guide',
				'name'        => 'Wellness Guide',
				'description' => 'Write a comprehensive guide about health and wellness tips.',
				'category'    => 'Health',
				'example'     => '10 ways to improve your daily wellness routine...',
				'photos'      => 2,
			),
			array(
				'id'          => 'business-insights',
				'name'        => 'Business Insights',
				'description' => 'Share valuable business strategies and entrepreneurship lessons.',
				'category'    => 'Business',
				'example'     => 'How to scale your startup from 0 to 6 figures...',
				'photos'      => 1,
			),
			array(
				'id'          => 'food-culture',
				'name'        => 'Food & Culture',
				'description' => 'Tell stories about food, recipes, and cultural cuisines.',
				'category'    => 'Food',
				'example'     => 'A culinary journey through Mediterranean cuisine...',
				'photos'      => 3,
			),
			array(
				'id'          => 'personal-growth',
				'name'        => 'Personal Growth',
				'description' => 'Inspire readers with personal development stories.',
				'category'    => 'Self-Help',
				'example'     => '5 transformative habits that changed my life...',
				'photos'      => 1,
			),
		);

		// Cache prompts for 1 week
		update_option( self::WIZARD_PROMPTS_KEY, $prompts );

		return $prompts;
	}

	/**
	 * Mark wizard as dismissed. Updates last shown time for 24-hour throttling.
	 *
	 * @return bool True if successfully marked.
	 */
	public static function dismiss_wizard() {
		$user_id = get_current_user_id();
		// Update last shown timestamp for 24-hour throttling
		update_user_meta( $user_id, self::WIZARD_LAST_SHOWN_KEY, current_time( 'mysql' ) );
		// Mark as permanently dismissed ("Don't show again")
		return update_user_meta( $user_id, self::WIZARD_SHOWN_KEY, current_time( 'mysql' ) );
	}

	/**
	 * Update last shown time without permanently dismissing.
	 * Used internally to track 24-hour throttling.
	 *
	 * @return void
	 */
	public static function mark_wizard_shown_today() {
		$user_id = get_current_user_id();
		if ( $user_id > 0 ) {
			update_user_meta( $user_id, self::WIZARD_LAST_SHOWN_KEY, current_time( 'mysql' ) );
		}
	}

	/**
	 * Reset wizard for testing purposes.
	 *
	 * @param int $user_id Optional user ID. If not provided, resets for all users.
	 * @return void
	 */
	public static function reset_wizard( $user_id = -1 ) {
		global $wpdb;
		
		if ( $user_id > 0 ) {
			// Reset for specific user
			delete_user_meta( $user_id, self::WIZARD_SHOWN_KEY );
			delete_user_meta( $user_id, self::WIZARD_LAST_SHOWN_KEY );
		} elseif ( $user_id === -1 ) {
			// Reset for current user if -1
			$current_user_id = get_current_user_id();
			if ( $current_user_id > 0 ) {
				delete_user_meta( $current_user_id, self::WIZARD_SHOWN_KEY );
				delete_user_meta( $current_user_id, self::WIZARD_LAST_SHOWN_KEY );
			}
		} else {
			// Reset for ALL users if 0
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->query( $wpdb->prepare( "DELETE FROM `{$wpdb->usermeta}` WHERE meta_key = %s", self::WIZARD_SHOWN_KEY ) );
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->query( $wpdb->prepare( "DELETE FROM `{$wpdb->usermeta}` WHERE meta_key = %s", self::WIZARD_LAST_SHOWN_KEY ) );
		}
	}

	/**
	 * Build the richest available site-context string for the wizard prompt field.
	 *
	 * Combines Site Title, Tagline, and — when a static front page is set —
	 * the homepage SEO meta description from the most common SEO plugins
	 * (Yoast SEO, Rank Math, All in One SEO, SEOPress). Empty parts are
	 * dropped and adjacent duplicates are removed so the string stays clean.
	 *
	 * @return string Plain-text context string, ready for use as an input value.
	 */
	public static function build_site_context() {
		$parts = array_filter( array(
			trim( get_bloginfo( 'name' ) ),
			trim( get_bloginfo( 'description' ) ),
		) );

		// Pull the homepage SEO meta description when a static front page exists.
		$front_page_id = 'page' === get_option( 'show_on_front' ) ? (int) get_option( 'page_on_front' ) : 0;
		if ( $front_page_id > 0 ) {
			$seo_desc = get_post_meta( $front_page_id, '_yoast_wpseo_metadesc', true )
				?: get_post_meta( $front_page_id, 'rank_math_description', true )
				?: get_post_meta( $front_page_id, '_aioseo_description', true )
				?: get_post_meta( $front_page_id, '_seopress_titles_desc', true );

			if ( ! empty( $seo_desc ) ) {
				$parts[] = trim( $seo_desc );
			}
		}

		// Deduplicate adjacent identical values (e.g. tagline === SEO description).
		$unique = array();
		foreach ( $parts as $part ) {
			if ( empty( $unique ) || end( $unique ) !== $part ) {
				$unique[] = $part;
			}
		}

		return implode( ' — ', $unique );
	}

	/**
	 * Get the HTML for the wizard modal.
	 *
	 * @return string The wizard modal HTML.
	 */
	public static function get_wizard_modal_html() {
		$prompts      = self::get_default_prompts();
		$site_context = self::build_site_context();
		ob_start();
		include AISTMA_PATH . 'admin/templates/activation-wizard-template.php';
		return ob_get_clean();
	}

	/**
	 * Get the HTML for the preview modal.
	 *
	 * @return string The preview modal HTML.
	 */
	public static function get_preview_modal_html() {
		ob_start();
		include AISTMA_PATH . 'admin/templates/preview-modal-template.php';
		return ob_get_clean();
	}
}
