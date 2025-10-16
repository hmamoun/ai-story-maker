<?php
/**
 * Admin Settings Page for AI Story Maker.
 *
 * @package AI_Story_Maker
 * @author  Hayan Mamoun
 * @license GPLv2 or later
 * @link    https://github.com/hmamoun/ai-story-maker/wiki
 * @since   0.1.0
 */

// phpcs:disable WordPress.Files.FileName.NotClassName
// phpcs:disable WordPress.Files.FileName.NotClass

namespace exedotcom\aistorymaker;

use WpOrg\Requests\Response;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class AISTMA_Settings_Page
 *
 * Renders and processes the settings form for the AI Story Maker plugin.
 */
class AISTMA_Settings_Page {

	/**
	 * Instance of the log manager.
	 *
	 * @var AISTMA_Log_Manager
	 */
	protected $aistma_log_manager;

	/**
	 * Singleton instance.
	 *
	 * @var AISTMA_Settings_Page
	 */
	private static $instance = null;

	/**
	 * Get singleton instance.
	 *
	 * @return AISTMA_Settings_Page
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor initializes the settings page and log manager.
	 */
	public function __construct() {
		// Prevent multiple instances
		if ( null !== self::$instance ) {
			return self::$instance;
		}
		
		$this->aistma_log_manager = new AISTMA_Log_Manager();
		add_action( 'wp_ajax_aistma_save_setting', [ $this, 'aistma_ajax_save_setting' ] );
		add_action( 'wp_ajax_aistma_save_social_media_global_settings', [ $this, 'aistma_ajax_save_social_media_global_settings' ] );
		add_action( 'wp_ajax_aistma_save_social_media_account', [ $this, 'aistma_ajax_save_social_media_account' ] );
		add_action( 'wp_ajax_aistma_delete_social_media_account', [ $this, 'aistma_ajax_delete_social_media_account' ] );
		add_action( 'wp_ajax_aistma_test_social_media_account', [ $this, 'aistma_ajax_test_social_media_account' ] );
		add_action( 'wp_ajax_aistma_facebook_oauth_callback', [ $this, 'aistma_ajax_facebook_oauth_callback' ] );
		
		// Hook into init to handle Facebook OAuth redirect
		add_action( 'init', [ $this, 'handle_facebook_oauth_redirect' ] );
		
		self::$instance = $this;
	}

	/**
	 * Handles AJAX request to save a single setting.
	 */
	public function aistma_ajax_save_setting() {
		// Check nonce for security
		if ( ! isset( $_POST['aistma_security'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['aistma_security'] ) ), 'aistma_save_setting' ) ) {
			wp_send_json_error( [ 'message' => __( 'Security check failed. Please try again.', 'ai-story-maker' ) ] );
			$this->aistma_log_manager->log( 'error', ' Security check failed. Please try again.' );
			wp_die();
		}

		$setting_name  = isset( $_POST['setting_name'] ) ? sanitize_text_field( wp_unslash( $_POST['setting_name'] ) ) : '';
		$setting_value = isset( $_POST['setting_value'] ) ? sanitize_text_field( wp_unslash( $_POST['setting_value'] ) ) : null;

		if ( empty( $setting_name ) ) {
			wp_send_json_error( [ 'message' => __( 'No setting name provided.', 'ai-story-maker' ) ] );
			wp_die();
		}

		// Validate and update specific settings
		switch ( $setting_name ) {
			case 'aistma_openai_api_key':
				if ( ! AISTMA_API_Keys::aistma_validate_aistma_openai_api_key( sanitize_text_field( $setting_value ) ) ) {
					wp_send_json_error( [ 'message' => __( 'Invalid OpenAI API key.', 'ai-story-maker' ) ] );
					$this->aistma_log_manager->log( 'error', ' Invalid OpenAI API key.' );
					wp_die();
				}
				update_option( 'aistma_openai_api_key', sanitize_text_field( $setting_value ) );
				break;
			case 'aistma_unsplash_api_key':
				update_option( 'aistma_unsplash_api_key', sanitize_text_field( $setting_value ) );
				break;
			case 'aistma_unsplash_api_secret':
				update_option( 'aistma_unsplash_api_secret', sanitize_text_field( $setting_value ) );
				break;
			case 'aistma_clear_log_cron':
				if ( get_option( 'aistma_clear_log_cron' ) !== sanitize_text_field( $setting_value ) ) {
					wp_clear_scheduled_hook( 'schd_ai_story_maker_clear_log' );
				}
				update_option( 'aistma_clear_log_cron', sanitize_text_field( $setting_value ) );
				break;
			case 'aistma_generate_story_cron':
				$interval = intval( $setting_value );
				$n        = absint( get_option( 'aistma_generate_story_cron', 2 ) );
				if ( 0 === $interval ) {
					wp_clear_scheduled_hook( 'aistma_generate_story_event' );
				}
				update_option( 'aistma_generate_story_cron', $interval );
				if ( $n !== $interval ) {
					wp_clear_scheduled_hook( 'aistma_generate_story_event' );
					$generator = new AISTMA_Story_Generator();
					$generator->reschedule_cron_event();
					$this->aistma_log_manager->log( 'info', 'Schedule changed via admin. Running updated check.' );
				}
				break;
			case 'aistma_opt_auther':
				update_option( 'aistma_opt_auther', intval( $setting_value ) );
				break;
			case 'aistma_show_ai_attribution':
				update_option( 'aistma_show_ai_attribution', $setting_value ? 1 : 0 );
				break;
			case 'aistma_show_exedotcom_attribution':
				update_option( 'aistma_show_exedotcom_attribution', $setting_value ? 1 : 0 );
				break;
			default:
				wp_send_json_error( [ 'message' => __( 'Unknown setting.', 'ai-story-maker' ) ] );
				wp_die();
		}

		$this->aistma_log_manager->log( 'info', 'Setting ' . $setting_name . ' updated.' );
		wp_send_json_success( [ 'message' => __( 'Setting saved!', 'ai-story-maker' ) ] );
		wp_die();
	}

	public function aistma_get_available_packages(): string {

		$url      = aistma_get_api_url( 'wp-json/exaig/v1/packages-summary' );
		$response = wp_remote_get(
			$url,
			array(
				'timeout' => 10,
				'headers' => array(
					'X-Caller-Url' => home_url(),
					'X-Caller-IP'  => isset( $_SERVER['SERVER_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['SERVER_ADDR'] ) ) : '',
				),
			)
		);

		// Prepare the standardized wrapper structure
		$standard_response = [
			'headers'       => (object) [],
			'body'          => '[]',
			'response'      => [ 'code' => 200, 'message' => 'OK' ],
			'cookies'       => [],
			'filename'      => null,
			'http_response' => [ 'data' => null, 'headers' => null, 'status' => null ],
		];

		$fallback_package = [
			'name'           => 'subscription server not available',
			'description'    => 'Service temporarily unavailable or returned no packages',
			'price'          => 0,
			'status'         => 'inactive',
			'stories'        => 0,
			'interval'       => 'month',
			'interval_count' => 1,
		];

		if ( is_wp_error( $response ) ) {
			// Network/transport error: return wrapper with a single fallback package
			$standard_response['body'] = wp_json_encode( [ $fallback_package ] );
			return wp_json_encode( $standard_response );
		}

		$body = wp_remote_retrieve_body( $response );

		// If body is not a string, or empty, return the fallback package
		if ( ! is_string( $body ) || '' === trim( $body ) ) {
			$standard_response['body'] = wp_json_encode( [ $fallback_package ] );
			return wp_json_encode( $standard_response );
		}

		// Try to decode to determine whether packages exist
		$decoded = json_decode( $body, true );
		if ( json_last_error() !== JSON_ERROR_NONE ) {
			// Malformed payload from destination; provide fallback
			$standard_response['body'] = wp_json_encode( [ $fallback_package ] );
			return wp_json_encode( $standard_response );
		}

		// If destination returned no packages, provide one fallback package
		if ( is_array( $decoded ) && empty( $decoded ) ) {
			$standard_response['body'] = wp_json_encode( [ $fallback_package ] );
			return wp_json_encode( $standard_response );
		}

		// Happy path: wrap the original body as a JSON string
		$standard_response['body'] = is_string( $body ) ? $body : wp_json_encode( $decoded );
		return wp_json_encode( $standard_response );
	}

	/**
	 * Renders the plugin subscriptions page.
	 *
	 * @return void
	 */
	public function aistma_subscriptions_page_render() {
		$response_body = $this->aistma_get_available_packages();
		include AISTMA_PATH . 'admin/templates/subscriptions-template.php';
	}

	/**
	 * Renders the plugin settings page.
	 *
	 * @return void
	 */
	public function aistma_settings_page_render() {
		include AISTMA_PATH . 'admin/templates/settings-template.php';
	}

	/**
	 * Handles AJAX request to save social media global settings.
	 */
	public function aistma_ajax_save_social_media_global_settings() {
		// Check nonce for security
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'aistma_social_media_settings' ) ) {
			wp_send_json_error( [ 'message' => __( 'Security check failed. Please try again.', 'ai-story-maker' ) ] );
			wp_die();
		}

		// Check user permissions
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [ 'message' => __( 'You do not have permission to perform this action.', 'ai-story-maker' ) ] );
			wp_die();
		}

		$settings = isset( $_POST['settings'] ) ? map_deep( wp_unslash( $_POST['settings'] ), 'sanitize_text_field' ) : array();

		// Sanitize settings
		$sanitized_settings = array(
			'auto_publish' => isset( $settings['auto_publish'] ) ? (bool) $settings['auto_publish'] : false,
			'include_hashtags' => isset( $settings['include_hashtags'] ) ? (bool) $settings['include_hashtags'] : false,
			'default_hashtags' => isset( $settings['default_hashtags'] ) ? sanitize_text_field( $settings['default_hashtags'] ) : '',
		);

		// Get current social media accounts
		$social_media_accounts = get_option( 'aistma_social_media_accounts', array( 'accounts' => array(), 'global_settings' => array() ) );
		
		// Update global settings
		$social_media_accounts['global_settings'] = $sanitized_settings;
		
		// Save to database
		$result = update_option( 'aistma_social_media_accounts', $social_media_accounts );

		if ( $result ) {
			$this->aistma_log_manager->log( 'info', 'Social media global settings updated successfully.' );
			wp_send_json_success( [ 'message' => __( 'Global settings saved successfully!', 'ai-story-maker' ) ] );
		} else {
			$this->aistma_log_manager->log( 'error', 'Failed to update social media global settings.' );
			wp_send_json_error( [ 'message' => __( 'Failed to save settings. Please try again.', 'ai-story-maker' ) ] );
		}
		wp_die();
	}

	/**
	 * Handles AJAX request to save a social media account.
	 */
	public function aistma_ajax_save_social_media_account() {
		// Check nonce for security
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'aistma_social_media_settings' ) ) {
			wp_send_json_error( [ 'message' => __( 'Security check failed. Please try again.', 'ai-story-maker' ) ] );
			wp_die();
		}

		// Check user permissions
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [ 'message' => __( 'You do not have permission to perform this action.', 'ai-story-maker' ) ] );
			wp_die();
		}

		$account_data = isset( $_POST['account_data'] ) ? map_deep( wp_unslash( $_POST['account_data'] ), 'sanitize_text_field' ) : array();

		// Validate required fields
		if ( empty( $account_data['platform'] ) || empty( $account_data['account_name'] ) ) {
			wp_send_json_error( [ 'message' => __( 'Platform and account name are required.', 'ai-story-maker' ) ] );
			wp_die();
		}

		// Generate unique ID if not provided
		$account_id = ! empty( $account_data['account_id'] ) ? sanitize_text_field( $account_data['account_id'] ) : wp_generate_uuid4();

		// Sanitize account data
		$sanitized_account = array(
			'id' => $account_id,
			'platform' => sanitize_text_field( $account_data['platform'] ),
			'name' => sanitize_text_field( $account_data['account_name'] ),
			'enabled' => isset( $account_data['enabled'] ) ? (bool) $account_data['enabled'] : false,
			'credentials' => array(),
			'settings' => array(),
			'created_at' => current_time( 'mysql' ),
		);

		// Handle platform-specific credentials
		switch ( $sanitized_account['platform'] ) {
			case 'facebook':
				// Facebook accounts can only be created via OAuth
				wp_send_json_error( [ 'message' => __( 'Facebook accounts can only be connected using OAuth. Please use the "Connect Facebook Page" button.', 'ai-story-maker' ) ] );
				wp_die();
				break;
			case 'twitter':
				$sanitized_account['credentials'] = array(
					'api_key' => isset( $account_data['api_key'] ) ? sanitize_text_field( $account_data['api_key'] ) : '',
					'api_secret' => isset( $account_data['api_secret'] ) ? sanitize_text_field( $account_data['api_secret'] ) : '',
					'access_token' => isset( $account_data['access_token'] ) ? sanitize_text_field( $account_data['access_token'] ) : '',
					'access_token_secret' => isset( $account_data['access_token_secret'] ) ? sanitize_text_field( $account_data['access_token_secret'] ) : '',
				);
				break;
			case 'linkedin':
				$sanitized_account['credentials'] = array(
					'access_token' => isset( $account_data['access_token'] ) ? sanitize_text_field( $account_data['access_token'] ) : '',
					'company_id' => isset( $account_data['company_id'] ) ? sanitize_text_field( $account_data['company_id'] ) : '',
				);
				break;
			case 'instagram':
				$sanitized_account['credentials'] = array(
					'access_token' => isset( $account_data['access_token'] ) ? sanitize_text_field( $account_data['access_token'] ) : '',
					'account_id' => isset( $account_data['account_id'] ) ? sanitize_text_field( $account_data['account_id'] ) : '',
				);
				break;
		}

		// Get current social media accounts
		$social_media_accounts = get_option( 'aistma_social_media_accounts', array( 'accounts' => array(), 'global_settings' => array() ) );
		
		// Add or update account
		$account_found = false;
		foreach ( $social_media_accounts['accounts'] as $key => $existing_account ) {
			if ( $existing_account['id'] === $account_id ) {
				$social_media_accounts['accounts'][ $key ] = $sanitized_account;
				$account_found = true;
				break;
			}
		}

		if ( ! $account_found ) {
			$social_media_accounts['accounts'][] = $sanitized_account;
		}

		// Save to database
		$result = update_option( 'aistma_social_media_accounts', $social_media_accounts );

		if ( $result ) {
			$this->aistma_log_manager->log( 'info', 'Social media account saved: ' . $sanitized_account['name'] . ' (' . $sanitized_account['platform'] . ')' );
			wp_send_json_success( [ 'message' => __( 'Account saved successfully!', 'ai-story-maker' ) ] );
		} else {
			$this->aistma_log_manager->log( 'error', 'Failed to save social media account: ' . $sanitized_account['name'] );
			wp_send_json_error( [ 'message' => __( 'Failed to save account. Please try again.', 'ai-story-maker' ) ] );
		}
		wp_die();
	}

	/**
	 * Handles AJAX request to delete a social media account.
	 */
	public function aistma_ajax_delete_social_media_account() {
		// Check nonce for security
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'aistma_social_media_settings' ) ) {
			wp_send_json_error( [ 'message' => __( 'Security check failed. Please try again.', 'ai-story-maker' ) ] );
			wp_die();
		}

		// Check user permissions
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [ 'message' => __( 'You do not have permission to perform this action.', 'ai-story-maker' ) ] );
			wp_die();
		}

		$account_id = isset( $_POST['account_id'] ) ? sanitize_text_field( wp_unslash( $_POST['account_id'] ) ) : '';

		// Validate account ID
		if ( empty( $account_id ) ) {
			wp_send_json_error( [ 'message' => __( 'Account ID is required for deletion.', 'ai-story-maker' ) ] );
			wp_die();
		}

		// Get current social media accounts
		$social_media_accounts = get_option( 'aistma_social_media_accounts', array( 'accounts' => array(), 'global_settings' => array() ) );
		
		// Find and remove the account
		$account_found = false;
		$deleted_account_name = '';
		foreach ( $social_media_accounts['accounts'] as $key => $existing_account ) {
			if ( $existing_account['id'] === $account_id ) {
				$deleted_account_name = $existing_account['name'];
				unset( $social_media_accounts['accounts'][ $key ] );
				$account_found = true;
				break;
			}
		}

		if ( ! $account_found ) {
			wp_send_json_error( [ 'message' => __( 'Account not found.', 'ai-story-maker' ) ] );
			wp_die();
		}

		// Re-index array to maintain proper array structure
		$social_media_accounts['accounts'] = array_values( $social_media_accounts['accounts'] );

		// Save to database
		$result = update_option( 'aistma_social_media_accounts', $social_media_accounts );

		if ( $result !== false ) {
			$this->aistma_log_manager->log( 'info', 'Social media account deleted: ' . $deleted_account_name . ' (ID: ' . $account_id . ')' );
			wp_send_json_success( [ 'message' => __( 'Account deleted successfully!', 'ai-story-maker' ) ] );
		} else {
			$this->aistma_log_manager->log( 'error', 'Failed to delete social media account: ' . $deleted_account_name );
			wp_send_json_error( [ 'message' => __( 'Failed to delete account. Please try again.', 'ai-story-maker' ) ] );
		}
		wp_die();
	}

	/**
	 * Handles AJAX request to test a social media account connection.
	 */
	public function aistma_ajax_test_social_media_account() {
		// Check nonce for security
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'aistma_social_media_settings' ) ) {
			wp_send_json_error( [ 'message' => __( 'Security check failed. Please try again.', 'ai-story-maker' ) ] );
			wp_die();
		}

		// Check user permissions
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [ 'message' => __( 'You do not have permission to perform this action.', 'ai-story-maker' ) ] );
			wp_die();
		}

		$account_id = isset( $_POST['account_id'] ) ? sanitize_text_field( wp_unslash( $_POST['account_id'] ) ) : '';

		// Validate account ID
		if ( empty( $account_id ) ) {
			wp_send_json_error( [ 'message' => __( 'Account ID is required for testing.', 'ai-story-maker' ) ] );
			wp_die();
		}

		// Get current social media accounts
		$social_media_accounts = get_option( 'aistma_social_media_accounts', array( 'accounts' => array(), 'global_settings' => array() ) );
		
		// Find the account
		$account = null;
		foreach ( $social_media_accounts['accounts'] as $existing_account ) {
			if ( $existing_account['id'] === $account_id ) {
				$account = $existing_account;
				break;
			}
		}

		if ( ! $account ) {
			wp_send_json_error( [ 'message' => __( 'Account not found.', 'ai-story-maker' ) ] );
			wp_die();
		}

		// Test connection based on platform
		$test_result = $this->test_social_media_connection( $account );

		if ( $test_result['success'] ) {
			$this->aistma_log_manager->log( 'info', 'Social media account test successful: ' . $account['name'] . ' (' . $account['platform'] . ')' );
			wp_send_json_success( [ 'message' => $test_result['message'] ] );
		} else {
			$this->aistma_log_manager->log( 'error', 'Social media account test failed: ' . $account['name'] . ' - ' . $test_result['message'] );
			wp_send_json_error( [ 'message' => $test_result['message'] ] );
		}
		wp_die();
	}

	/**
	 * Test social media account connection based on platform.
	 *
	 * @param array $account Account configuration array.
	 * @return array Test result with success status and message.
	 */
	private function test_social_media_connection( $account ) {
		switch ( $account['platform'] ) {
			case 'facebook':
				return $this->test_facebook_connection( $account );
			case 'twitter':
				return $this->test_twitter_connection( $account );
			case 'linkedin':
				return $this->test_linkedin_connection( $account );
			case 'instagram':
				return $this->test_instagram_connection( $account );
			default:
				return array(
					'success' => false,
					'message' => __( 'Unsupported platform for testing.', 'ai-story-maker' )
				);
		}
	}

	/**
	 * Test Facebook page connection.
	 *
	 * @param array $account Account configuration.
	 * @return array Test result.
	 */
	private function test_facebook_connection( $account ) {
		if ( empty( $account['credentials']['access_token'] ) || empty( $account['credentials']['page_id'] ) ) {
			return array(
				'success' => false,
				'message' => __( 'Missing Facebook credentials (access token or page ID).', 'ai-story-maker' )
			);
		}

		// Test Facebook Graph API connection
		$access_token = $account['credentials']['access_token'];
		$page_id = $account['credentials']['page_id'];
		$test_url = "https://graph.facebook.com/v18.0/{$page_id}?access_token=" . urlencode( $access_token ) . '&fields=name,id';

		$response = wp_remote_get( $test_url, array(
			'timeout' => 10,
			'headers' => array(
				'User-Agent' => 'AI Story Maker WordPress Plugin'
			)
		) );

		if ( is_wp_error( $response ) ) {
			return array(
				'success' => false,
				'message' => __( 'Network error: ', 'ai-story-maker' ) . $response->get_error_message()
			);
		}

		$response_code = wp_remote_retrieve_response_code( $response );
		$response_body = wp_remote_retrieve_body( $response );
		$data = json_decode( $response_body, true );

		if ( $response_code === 200 && isset( $data['name'] ) ) {
			return array(
				'success' => true,
				/* translators: %s: Facebook page name */
				'message' => sprintf( __( 'Successfully connected to Facebook page: %s', 'ai-story-maker' ), $data['name'] )
			);
		} else {
			$error_message = isset( $data['error']['message'] ) ? $data['error']['message'] : __( 'Unknown Facebook API error', 'ai-story-maker' );
			return array(
				'success' => false,
				'message' => __( 'Facebook API error: ', 'ai-story-maker' ) . $error_message
			);
		}
	}

	/**
	 * Test Twitter connection (placeholder for future implementation).
	 *
	 * @param array $account Account configuration.
	 * @return array Test result.
	 */
	private function test_twitter_connection( $account ) {
		return array(
			'success' => false,
			'message' => __( 'Twitter connection testing not yet implemented.', 'ai-story-maker' )
		);
	}

	/**
	 * Test LinkedIn connection (placeholder for future implementation).
	 *
	 * @param array $account Account configuration.
	 * @return array Test result.
	 */
	private function test_linkedin_connection( $account ) {
		return array(
			'success' => false,
			'message' => __( 'LinkedIn connection testing not yet implemented.', 'ai-story-maker' )
		);
	}

	/**
	 * Test Instagram connection (placeholder for future implementation).
	 *
	 * @param array $account Configuration.
	 * @return array Test result.
	 */
	private function test_instagram_connection( $account ) {
		return array(
			'success' => false,
			'message' => __( 'Instagram connection testing not yet implemented.', 'ai-story-maker' )
		);
	}

	/**
	 * Handle Facebook OAuth redirect callback.
	 * This runs on every page load to check for Facebook OAuth callbacks.
	 */
	public function handle_facebook_oauth_redirect() {
		// Check if this is a Facebook OAuth callback
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- OAuth callback uses state parameter verification
		if ( ! isset( $_GET['code'] ) || ! isset( $_GET['state'] ) || ! isset( $_GET['aistma_facebook_oauth'] ) ) {
			return;
		}

		// Verify state parameter for security
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- OAuth callback uses state parameter verification
		$state = sanitize_text_field( wp_unslash( $_GET['state'] ) );
		$stored_state = get_transient( 'aistma_facebook_oauth_state_' . get_current_user_id() );
		
		if ( ! $stored_state || $state !== $stored_state ) {
			wp_die( esc_html__( 'Invalid OAuth state parameter. Please try again.', 'ai-story-maker' ) );
		}

		// Clean up the state transient
		delete_transient( 'aistma_facebook_oauth_state_' . get_current_user_id() );

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- OAuth callback uses state parameter verification
		$code = sanitize_text_field( wp_unslash( $_GET['code'] ) );
		
		// Exchange code for access token
		$result = $this->exchange_facebook_code_for_token( $code );
		
		if ( $result['success'] ) {
			// Redirect back to social media settings with success
			$redirect_url = add_query_arg( [
				'page' => 'aistma-settings',
				'tab' => 'social-media',
				'facebook_oauth' => 'success',
				'account_name' => urlencode( $result['account_name'] ),
				'_wpnonce' => wp_create_nonce( 'aistma_facebook_oauth_result' ),
			], admin_url( 'admin.php' ) );
		} else {
			// Redirect back with error
			$redirect_url = add_query_arg( [
				'page' => 'aistma-settings',
				'tab' => 'social-media',
				'facebook_oauth' => 'error',
				'error_message' => urlencode( $result['message'] ),
				'_wpnonce' => wp_create_nonce( 'aistma_facebook_oauth_result' ),
			], admin_url( 'admin.php' ) );
		}
		
		wp_safe_redirect( $redirect_url );
		exit;
	}

	/**
	 * Exchange Facebook OAuth code for access token and save account.
	 *
	 * @param string $code The OAuth authorization code.
	 * @return array Result of the token exchange.
	 */
	private function exchange_facebook_code_for_token( $code ) {
		// Get Facebook App credentials from transients
		$facebook_app_id = get_transient( 'aistma_facebook_app_id_' . get_current_user_id() );
		$facebook_app_secret = get_transient( 'aistma_facebook_app_secret_' . get_current_user_id() );

		// Clean up transients
		delete_transient( 'aistma_facebook_app_id_' . get_current_user_id() );
		delete_transient( 'aistma_facebook_app_secret_' . get_current_user_id() );

		if ( empty( $facebook_app_id ) || empty( $facebook_app_secret ) ) {
			return array(
				'success' => false,
				'message' => __( 'Facebook App credentials not found. Please try the connection process again.', 'ai-story-maker' )
			);
		}

		$redirect_uri = $this->get_facebook_redirect_uri();

		// Exchange code for access token
		$token_url = 'https://graph.facebook.com/v19.0/oauth/access_token';
		$token_params = array(
			'client_id' => $facebook_app_id,
			'client_secret' => $facebook_app_secret,
			'redirect_uri' => $redirect_uri,
			'code' => $code,
		);

		$token_response = wp_remote_post( $token_url, array(
			'body' => $token_params,
			'timeout' => 30,
		) );

		if ( is_wp_error( $token_response ) ) {
			return array(
				'success' => false,
				'message' => __( 'Network error during token exchange: ', 'ai-story-maker' ) . $token_response->get_error_message()
			);
		}

		$token_body = wp_remote_retrieve_body( $token_response );
		$token_data = json_decode( $token_body, true );

		if ( ! isset( $token_data['access_token'] ) ) {
			$error_message = isset( $token_data['error']['message'] ) 
				? $token_data['error']['message'] 
				: __( 'Failed to get access token from Facebook', 'ai-story-maker' );
			
			return array(
				'success' => false,
				'message' => $error_message
			);
		}

		$access_token = $token_data['access_token'];

		// Get user's Facebook pages
		$pages_url = 'https://graph.facebook.com/v19.0/me/accounts?access_token=' . urlencode( $access_token );
		$pages_response = wp_remote_get( $pages_url, array( 'timeout' => 30 ) );

		if ( is_wp_error( $pages_response ) ) {
			return array(
				'success' => false,
				'message' => __( 'Network error getting Facebook pages: ', 'ai-story-maker' ) . $pages_response->get_error_message()
			);
		}

		$pages_body = wp_remote_retrieve_body( $pages_response );
		$pages_data = json_decode( $pages_body, true );

		if ( ! isset( $pages_data['data'] ) || empty( $pages_data['data'] ) ) {
			return array(
				'success' => false,
				'message' => __( 'No Facebook pages found for this account. Please make sure you have admin access to at least one Facebook page.', 'ai-story-maker' )
			);
		}

		// For now, we'll use the first page. In a full implementation, you might want to let users choose
		$page = $pages_data['data'][0];
		$page_id = $page['id'];
		$page_name = $page['name'];
		$page_access_token = $page['access_token'];

		// Save the Facebook page account with the app credentials
		$account_data = array(
			'id' => wp_generate_uuid4(),
			'platform' => 'facebook',
			'name' => $page_name,
			'enabled' => true,
			'credentials' => array(
				'access_token' => $page_access_token,
				'page_id' => $page_id,
				'facebook_app_id' => $facebook_app_id,
				'facebook_app_secret' => $facebook_app_secret,
			),
			'settings' => array(),
			'created_at' => current_time( 'mysql' ),
		);

		// Get current accounts and add the new one
		$social_media_accounts = get_option( 'aistma_social_media_accounts', array( 'accounts' => array(), 'global_settings' => array() ) );
		$social_media_accounts['accounts'][] = $account_data;

		$result = update_option( 'aistma_social_media_accounts', $social_media_accounts );

		if ( $result ) {
			$this->aistma_log_manager->log( 'info', 'Facebook page connected via OAuth: ' . $page_name . ' (ID: ' . $page_id . ') with App ID: ' . $facebook_app_id );
			
		return array(
			'success' => true,
			// translators: %s is the Facebook page name
			'message' => sprintf( __( 'Successfully connected Facebook page: %s', 'ai-story-maker' ), $page_name ),
			'account_name' => $page_name
		);
		} else {
			return array(
				'success' => false,
				'message' => __( 'Failed to save Facebook account. Please try again.', 'ai-story-maker' )
			);
		}
	}

	/**
	 * Generate Facebook OAuth URL.
	 *
	 * @param string $facebook_app_id Facebook App ID.
	 * @param string $facebook_app_secret Facebook App Secret.
	 * @return string|false The OAuth URL or false on error.
	 */
	public function get_facebook_oauth_url( $facebook_app_id = '', $facebook_app_secret = '' ) {
		if ( empty( $facebook_app_id ) ) {
			return false;
		}

		// Store the app credentials temporarily for the OAuth callback
		set_transient( 'aistma_facebook_app_id_' . get_current_user_id(), $facebook_app_id, 10 * MINUTE_IN_SECONDS );
		set_transient( 'aistma_facebook_app_secret_' . get_current_user_id(), $facebook_app_secret, 10 * MINUTE_IN_SECONDS );

		// Generate and store state parameter for security
		$state = wp_generate_password( 32, false );
		set_transient( 'aistma_facebook_oauth_state_' . get_current_user_id(), $state, 10 * MINUTE_IN_SECONDS );

		$redirect_uri = $this->get_facebook_redirect_uri();
		
		$oauth_params = array(
			'client_id' => $facebook_app_id,
			'redirect_uri' => $redirect_uri,
			'scope' => 'pages_manage_posts,pages_read_engagement,pages_show_list',
			'response_type' => 'code',
			'state' => $state,
		);

		return 'https://www.facebook.com/v19.0/dialog/oauth?' . http_build_query( $oauth_params );
	}

	/**
	 * Get the Facebook OAuth redirect URI.
	 *
	 * @return string The redirect URI.
	 */
	private function get_facebook_redirect_uri() {
		return add_query_arg( [
			'aistma_facebook_oauth' => '1',
		], admin_url( 'admin.php' ) );
	}

	/**
	 * AJAX handler for getting Facebook OAuth URL.
	 */
	public function aistma_ajax_facebook_oauth_callback() {
		// Check nonce for security
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'aistma_social_media_settings' ) ) {
			wp_send_json_error( [ 'message' => __( 'Security check failed. Please try again.', 'ai-story-maker' ) ] );
			wp_die();
		}

		// Check user permissions
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [ 'message' => __( 'You do not have permission to perform this action.', 'ai-story-maker' ) ] );
			wp_die();
		}

		// Get Facebook App credentials from the AJAX request
		$facebook_app_id = isset( $_POST['facebook_app_id'] ) ? sanitize_text_field( wp_unslash( $_POST['facebook_app_id'] ) ) : '';
		$facebook_app_secret = isset( $_POST['facebook_app_secret'] ) ? sanitize_text_field( wp_unslash( $_POST['facebook_app_secret'] ) ) : '';

		$oauth_url = $this->get_facebook_oauth_url( $facebook_app_id, $facebook_app_secret );
		
		if ( $oauth_url ) {
			wp_send_json_success( [ 'oauth_url' => $oauth_url ] );
		} else {
			wp_send_json_error( [ 'message' => __( 'Facebook App ID or App Secret not provided.', 'ai-story-maker' ) ] );
		}
		
		wp_die();
	}


}
