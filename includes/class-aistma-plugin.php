<?php
/**
 * AI Story Maker Plugin Main Class
 *
 * This class serves as the core plugin class, handling initialization,
 * dependencies, and core functionality for the AI Story Maker plugin.
 *
 * @package AI_Story_Maker
 * @since   0.1.0
 */

// phpcs:disable WordPress.Files.FileName.NotClassName
// phpcs:disable WordPress.Files.FileName.NotClass

namespace exedotcom\aistorymaker;

use exedotcom\aistorymaker\AISTMA_Log_Manager;


/**
 * Main plugin class.
 */
class AISTMA_Plugin {


	/**
	 * Constructor to initialize plugin.
	 */
	public function __construct() {
		add_action( 'admin_enqueue_scripts', array( $this, 'aistma_enqueue_admin_styles' ) );
		add_filter( 'template_include', array( $this, 'aistma_template_include_filter' ) );
		$this->aistma_load_dependencies(
			array(
				'includes/class-aistma-log-manager.php',
				'includes/class-aistma-traffic-logger.php',
				'includes/class-aistma-credits-manager.php',
				'includes/class-aistma-gateway-logger.php',
				'includes/class-aistma-story-generator.php',
				'includes/shortcode-story-scroller.php',
				'admin/class-aistma-admin.php',
			)
		);

		// Log traffic on front-end single post views.
		if ( ! is_admin() ) {
			add_action( 'template_redirect', array( '\\exedotcom\\aistorymaker\\AISTMA_Traffic_Logger', 'maybe_log_current_view' ), 5 );
		}
		add_action( 'admin_post_aistma_clear_logs', array( AISTMA_Log_Manager::class, 'aistma_clear_logs' ) );
	}

	/**
	 * Load required class files.
	 *
	 * @param array $files List of relative file paths to include.
	 */
	public static function aistma_load_dependencies( $files = array() ) {
		foreach ( $files as $file ) {
			$path = AISTMA_PATH . $file;
			if ( file_exists( $path ) ) {
				include_once $path;
			} elseif ( class_exists( 'exedotcom\aistorymaker\AISTMA_Log_Manager' ) ) {
				( new AISTMA_Log_Manager() )->log( "Missing dependency file: $path" );
			}
		}
	}

	/**
	 * Enqueue admin styles on plugin settings page.
	 *
	 * @param string $hook Admin page slug.
	 */
	public function aistma_enqueue_admin_styles( $hook ) {
		if ( 'toplevel_page_aistma-settings' !== $hook ) {
			return;
		}
		wp_enqueue_style(
			'ai-storymaker-admin-css',
			AISTMA_URL . 'admin/css/admin.css',
			array(),
			filemtime( AISTMA_PATH . 'admin/css/admin.css' )
		);
	}

	/**
	 * Use custom template for AI Story Maker posts.
	 *
	 * @param  string $template Template file path.
	 * @return string
	 */
	public function aistma_template_include_filter( $template ) {
		if ( is_single() ) {
			$post_id = get_the_ID();
			if ( get_post_meta( $post_id, 'ai_story_maker_request_id', true ) ) {
				$plugin_template = AISTMA_PATH . '/public/templates/aistma-post-template.php';
				if ( file_exists( $plugin_template ) ) {
					return $plugin_template;
				}
			}
		}
		return $template;
	}

	/**
	 * Activate the plugin and schedule cron events.
	 */
	public static function aistma_activate() {
		$log_manager = new AISTMA_Log_Manager();

		if ( function_exists( 'ai_storymaker_aistma_create_log_table' ) ) {
			ai_storymaker_aistma_create_log_table();
		}

		// Ensure traffic logging table exists
		if ( class_exists( __NAMESPACE__ . '\\AISTMA_Traffic_Logger' ) ) {
			AISTMA_Traffic_Logger::ensure_tables();
		}

		// Initialize credits system for current user
		$current_user_id = get_current_user_id();
		if ( $current_user_id > 0 ) {
			// Load credits manager
			if ( class_exists( __NAMESPACE__ . '\\AISTMA_Credits_Manager' ) ) {
				$startup_credits = absint( get_option( 'aistma_startup_credit_amount', 5 ) );
				
				// Only grant credits if user doesn't have any yet (first time)
				$existing_balance = AISTMA_Credits_Manager::get_user_credits( $current_user_id );
				if ( 0 === $existing_balance ) {
					AISTMA_Credits_Manager::add_credits( $current_user_id, $startup_credits, 'Plugin activation - startup grant' );
					$log_manager->log( 'info', sprintf( 'User %d granted %d startup credits on plugin activation.', $current_user_id, $startup_credits ) );
				}
				
				// Log the wizard activation event to gateway
				if ( class_exists( __NAMESPACE__ . '\\AISTMA_Gateway_Logger' ) ) {
					AISTMA_Gateway_Logger::log_wizard_activated( $current_user_id );
				}
			}
		}

		// Migration: Populate original subscription email for existing users
		$subscription_email = get_option( 'aistma_subscription_email' );
		$original_subscription_email = get_option( 'aistma_original_subscription_email' );
		if ( ! empty( $subscription_email ) && empty( $original_subscription_email ) ) {
			update_option( 'aistma_original_subscription_email', $subscription_email );
			$log_manager->log( 'info', 'Migration: Populated original_subscription_email from existing subscription_email' );
		}

		if ( ! wp_next_scheduled( 'aistma_generate_story_event' ) ) {
			$n = absint( get_option( 'aistma_generate_story_cron', 2 ) );
			if ( 0 !== $n ) {
				$next_schedule_timestamp = time() + $n * DAY_IN_SECONDS;
				wp_schedule_event( $next_schedule_timestamp, 'daily', 'aistma_generate_story_event' );
				/* translators: Formatting the date for the next schedule to be come readable. */
				// $log_manager->log( 'info', sprintf( __( 'Set next schedule to %s', 'ai-story-maker' ), self::format_date_for_display( $next_schedule_timestamp ) ) );
			}
		}
	}

	/**
	 * Deactivate plugin and clean up.
	 */
	public static function aistma_deactivate() {
		wp_clear_scheduled_hook( 'aistma_generate_story_event' );
		delete_transient( 'aistma_generating_lock' );
		
		// Reset wizard for all users on deactivation
		if ( class_exists( __NAMESPACE__ . '\\AISTMA_Activation_Wizard' ) ) {
			AISTMA_Activation_Wizard::reset_wizard();
		}
	}

	/**
	 * Convert GMT timestamp to WordPress timezone for display.
	 *
	 * @param int $gmt_timestamp The GMT timestamp to convert.
	 * @return string The formatted date/time in WordPress timezone.
	 */
	private static function format_date_for_display( $gmt_timestamp ) {
		// Convert GMT timestamp to WordPress timezone
		$wp_timestamp = get_date_from_gmt( gmdate( 'Y-m-d H:i:s', $gmt_timestamp ), 'Y-m-d H:i:s' );
		return $wp_timestamp;
	}
}

new AISTMA_Plugin();
