<?php
/**
 * Story Generator for AI Story Maker plugin.
 *
 * Handles prompt processing, API requests to OpenAI, and post creation.
 *
 * @package AI_Story_Maker
 * @author  Hayan Mamoun
 * @license GPLv2 or later
 * @link    https://github.com/hmamoun/ai-story-maker
 * @since   0.1.0
 */

// phpcs:disable WordPress.Files.FileName.NotClassName
// phpcs:disable WordPress.Files.FileName.NotClass

namespace exedotcom\aistorymaker;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use exedotcom\aistorymaker\AISTMA_Log_Manager;

/**
 * Class AISTMA_Story_Generator
 *
 * Handles the generation of AI stories using OpenAI API.
 */
class AISTMA_Story_Generator {


	/**
	 * OpenAI API key.
	 *
	 * @var string
	 */
	private $api_key;

	/**
	 * Default settings for story generation.
	 *
	 * @var array
	 */
	private $default_settings;

	/**
	 * Log manager instance.
	 *
	 * @var AISTMA_Log_Manager
	 */
	protected $aistma_log_manager;

	/**
	 * Subscription status for the current domain.
	 *
	 * @var array
	 */
	private $subscription_status;

	/**
	 * Constructor.
	 *
	 * Initializes the story generator and sets up necessary hooks.
	 */
	public function __construct() {
		// Load the Log_Manager class.
		
		$this->aistma_log_manager = new AISTMA_Log_Manager();
		// Hook into an action to trigger AI story generation.
		// add_action( 'ai_story_generate', array( $this, 'generate_ai_stories' ) );
	}

	/**
	 * Generate AI stories with a lock to prevent concurrent executions.
	 *
	 * @param  bool $force Whether to force generation regardless of lock.
	 * @return array Array with 'success' boolean and 'message' string.
	 */
	public static function generate_ai_stories_with_lock( $force = false ) {
		$instance = new self();
		$lock_key = 'aistma_generating_lock';
		if ( ! $force && get_transient( $lock_key ) ) {
			$instance->aistma_log_manager->log( 'info', 'Story generation skipped due to active lock.' );
			return array( 'success' => false, 'message' => 'Story generation is already in progress.' );
		}

		// Check gateway authorization before generating. We never fall back to a
		// local OpenAI key — the gateway is the only generation path.
		try {
			$subscription_status  = $instance->aistma_get_subscription_status();
			$gateway_can_generate = $instance->gateway_can_generate();

			if ( $gateway_can_generate ) {
				$instance->aistma_log_manager::log( 'info', 'Gateway authorized generation for domain: ' . ( $subscription_status['domain'] ?? 'unknown' ) . ' - Package: ' . ( $subscription_status['package_name'] ?? 'unknown' ) . ' - Credits remaining: ' . var_export( $subscription_status['credits_remaining'] ?? null, true ) );
			} else {
				$error_message = isset( $subscription_status['error'] )
					? 'Subscription check failed: ' . $subscription_status['error']
					: 'No active plan or credits found. Please visit storymakerplugin.com/#pricing to choose a plan.';
				$instance->aistma_log_manager::log( 'error', $error_message );
				return array( 'success' => false, 'message' => $error_message );
			}
		} catch ( \RuntimeException $e ) {
			$error = $e->getMessage();
			$instance->aistma_log_manager->log( 'error', $error );
			return array( 'success' => false, 'message' => esc_html( $error ) );
		}

		set_transient( $lock_key, true, 10 * MINUTE_IN_SECONDS );
		try {
			// Pass the instance with cached subscription status to generate_ai_stories
			$instance->generate_ai_stories();
			$instance->aistma_log_manager->log( 'info', 'Stories successfully generated.' );
			return array( 'success' => true, 'message' => 'Stories generated successfully.' );
		} catch ( \Throwable $e ) {
			$instance->aistma_log_manager->log( 'error', 'Error generating stories: ' . $e->getMessage() );
			return array( 'success' => false, 'message' => 'Error generating stories: ' . $e->getMessage() );
		} finally {
			// Always delete the lock, even if an error occurs.
			delete_transient( $lock_key );
			// Always schedule the next run after execution.
			$n = absint( get_option( 'aistma_generate_story_cron', 2 ) );
			if ( 0 !== $n ) {
				$next_schedule = time() + $n * DAY_IN_SECONDS;
				wp_schedule_single_event( $next_schedule, 'aistma_generate_story_event' );
			}
		}
	}

	/**
	 * Generate AI Story using OpenAI API or Master Server API.
	 *
	 * @param  string $prompt_id             The prompt ID.
	 * @param  array  $prompt                The prompt data.
	 * @param  array  $default_settings      Default settings for generation.
	 * @param  array  $recent_posts          Recent posts to avoid duplication.
	 * @param  string $admin_prompt_settings Admin prompt settings.
	 * @param  string $api_key               OpenAI API key.
	 * @return void
	 */
	public function generate_ai_story( $prompt_id, $prompt, $default_settings, $api_key, $aistma_master_instructions ) {
		$merged_settings        = array_merge( $default_settings, $prompt );

		// The gateway is the single source of truth for credits. Block here only
		// Gateway is the only generation path. Block if it can't generate.
		if ( ! $this->gateway_can_generate() ) {
			$error = __( 'No active plan or credits found. Please visit storymakerplugin.com/#pricing to choose a plan.', 'ai-story-maker' );
			$this->aistma_log_manager->log( 'warning', $error );
			throw new \RuntimeException( esc_html( $error ) );
		}

		$recent_posts = $this->aistma_get_recent_posts( 20, $prompt['category'] );

		// Append recent posts titles if provided and not empty.
		if ( ! empty( $recent_posts ) && is_array( $recent_posts ) ) {
			$aistma_master_instructions .= "\n" . __( 'Exclude references to the following recent posts:', 'ai-story-maker' );
			foreach ( $recent_posts as $post ) {
				if ( isset( $post['title'] ) && ! empty( $post['title'] ) ) {
					$aistma_master_instructions .= "\n" . __( 'Title: ', 'ai-story-maker' ) . $post['title'];
				}
			}
		}


		// Assign final system content.
		$merged_settings['system_content'] .= $aistma_master_instructions ;

		// Extract dynamic category from prompt text if present (format: {category:xxxxx})
		$prompt_parsing = $this->extract_dynamic_category( $prompt['text'] );
		$the_prompt = $prompt_parsing['text'];

		// Override category if dynamic category was provided
		if ( ! empty( $prompt_parsing['category'] ) ) {
			$prompt['category'] = $prompt_parsing['category'];
		}

		// Add keywords to prompt if provided
		if ( ! empty( $prompt['keywords'] ) ) {
			$keywords = sanitize_text_field( $prompt['keywords'] );
			$the_prompt .= "\n\n" . sprintf(
				__( 'Important: Make sure to naturally include these keywords throughout the article: %s', 'ai-story-maker' ),
				$keywords
			);
		}

		// All generation goes through the master API (gateway).
		$subscription_info = $this->get_subscription_info();
		return $this->generate_story_via_master_api( $prompt_id, $prompt, $merged_settings, $the_prompt, $subscription_info );
	}

	/**
	 * Extract dynamic category from prompt text in format {category:xxxxx}.
	 *
	 * Only the first occurrence is used when multiple tokens are present ("first wins").
	 * Returns array with 'category' (sanitized category name or empty string) and 'text' (cleaned prompt text).
	 *
	 * @param  string $text The prompt text.
	 * @return array Array with 'category' and 'text' keys.
	 */
	private function extract_dynamic_category( $text ) {
		$category = '';
		$cleaned_text = $text;

		// Look for {category:xxxxx} pattern; only the first match is used.
		if ( preg_match( '/\{category:\s*([^}]+)\}/i', $text, $matches ) ) {
			$category = sanitize_text_field( trim( $matches[1] ) );
			// Remove only the first occurrence to match the single-match behaviour above.
			$cleaned_text = preg_replace( '/\{category:\s*([^}]+)\}/i', '', $text, 1 );
			// Clean up extra whitespace
			$cleaned_text = trim( preg_replace( '/\s+/', ' ', $cleaned_text ) );
		}

		return array(
			'category' => $category,
			'text'     => $cleaned_text,
		);
	}

	/**
	 * Generate AI stories using OpenAI API or Master Server API.
	 *
	 * @return void
	 */
	public function generate_ai_stories() {
		$results = array(
			'errors'    => array(),
			'successes' => array(),
		);
		
		// Gateway is the only generation path — no local API key fallback.
		if ( ! $this->gateway_can_generate() ) {
			$error = __( 'No active plan or credits found. Please visit storymakerplugin.com/#pricing to choose a plan.', 'ai-story-maker' );
			$this->aistma_log_manager->log( 'error', $error );
			$results['errors'][] = $error;
			throw new \RuntimeException( esc_html( $error ) );
		}

		$this->api_key = null;
		$this->aistma_log_manager->log( 'info', 'Gateway authorized generation, will use Master API for story generation' );

		$raw_settings = get_option( 'aistma_prompts', '' );
		$settings     = json_decode( $raw_settings, true );

		// Check if the settings are valid.
		if ( JSON_ERROR_NONE !== json_last_error() || empty( $settings['prompts'] ) ) {
			$error = __( 'General instructions or prompts are not set properly', 'ai-story-maker' );
			$this->aistma_log_manager->log( 'error', $error );
			$results['errors'][] = $error;
			throw new \RuntimeException( esc_html( $error ) );
		}
		$this->default_settings = isset( $settings['default_settings'] ) ? $settings['default_settings'] : array();

		$aistma_master_instructions = $this->aistma_get_master_instructions();

		foreach ( $settings['prompts'] as &$prompt ) {
			if ( isset( $prompt['active'] ) && 0 === $prompt['active'] ) {
				continue;
			}
			if ( empty( $prompt['text'] ) ) {
				continue;
			}
			if ( ! isset( $prompt['prompt_id'] ) || empty( $prompt['prompt_id'] ) ) {
				continue;
			}
			
	

		// Generate the AI story immediately if needed.
		try {
				// Generate the story
				$this->generate_ai_story( $prompt['prompt_id'], $prompt, $this->default_settings,  $this->api_key ,$aistma_master_instructions );
				
				$results['successes'][] = __( 'AI story generated successfully.', 'ai-story-maker' );
			} catch ( \Exception $e ) {
				$error = __( 'Error generating AI story: ', 'ai-story-maker' ) . $e->getMessage();
				$this->aistma_log_manager->log( 'error', $error );
				$results['errors'][] = $error;
			}
		}

		// Schedule after generate.
		$n = absint( get_option( 'aistma_generate_story_cron', 2 ) );
		if ( 0 !== $n ) {
			// Cancel the current schedule.
			wp_clear_scheduled_hook( 'aistma_generate_story_event' );
			// Schedule the next event.
			$next_schedule_timestamp = time() + $n * DAY_IN_SECONDS;
			$next_schedule_display = $this->format_date_for_display( $next_schedule_timestamp );
			wp_schedule_single_event( $next_schedule_timestamp, 'aistma_generate_story_event' );

			/* translators: %s: The next scheduled date and time in Y-m-d H:i:s format */
			$error_msg = sprintf( __( 'Set next schedule to %s', 'ai-story-maker' ), $next_schedule_display );
			$this->aistma_log_manager->log( 'info', $error_msg );
		} else {
			$this->aistma_log_manager->log( 'info', __( 'Schedule for next story is unset', 'ai-story-maker' ) );
			wp_clear_scheduled_hook( 'aistma_generate_story_event' );
		}
	}

	/**
	 * Generate story via Master Server API.
	 *
	 * @param  string $prompt_id        The prompt ID.
	 * @param  array  $prompt           The prompt data.
	 * @param  array  $merged_settings  Merged settings.
	 * @param  string $the_prompt       The prompt text.
	 * @param  array  $subscription_info Subscription information.
	 * @return void
	 */
	private function get_gateway_api_key() {
		if ( defined( 'AISTMA_GATEWAY_API_KEY' ) && AISTMA_GATEWAY_API_KEY ) {
			return sanitize_text_field( AISTMA_GATEWAY_API_KEY );
		}

		return sanitize_text_field( get_option( 'aistma_gateway_api_key', '' ) );
	}

	/**
	 * Build standard gateway headers.
	 *
	 * @param bool $include_auth Whether to include the configured auth key.
	 * @return array
	 */
	private function get_gateway_request_headers( $include_auth = true ) {
		$headers = array(
			'User-Agent'   => 'AI-Story-Maker/1.0',
			'X-Caller-Url' => home_url(),
			'X-Caller-IP'  => isset( $_SERVER['SERVER_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['SERVER_ADDR'] ) ) : '',
		);

		$gateway_api_key = $this->get_gateway_api_key();
		if ( $include_auth && ! empty( $gateway_api_key ) ) {
			$headers['Authorization'] = 'Bearer ' . $gateway_api_key;
		}

		return $headers;
	}

	private function generate_story_via_master_api( $prompt_id, $prompt, $merged_settings, $the_prompt, $subscription_info ) {
		// Get recent posts to avoid duplication
		$recent_posts = $this->aistma_get_recent_posts( 20, $prompt['category'] ?? '' );
		$master_url = aistma_get_api_url();
		
		if ( empty( $master_url ) ) {
			$this->aistma_log_manager->log( 'error', 'AISTMA_MASTER_API not defined, falling back to direct OpenAI call' );
			// Fallback to direct OpenAI call
			$this->generate_story_via_openai_api( $prompt_id, $prompt, $merged_settings, $this->api_key, $the_prompt );
			return;
		}

		$api_url = trailingslashit( $master_url ) . 'wp-json/exaig/v1/generate-story';
		
		// Prepare request data
		$request_data = array(
			'domain' => $subscription_info['domain'],
			'prompt_id' => $prompt_id,
			'prompt_text' => $the_prompt,
			'settings' => array(
				'model' => $merged_settings['model'] ?? 'gpt-4-turbo',
				'max_tokens' => 1500,
				'system_content' => $merged_settings['system_content'] ?? '',
				'timeout' => $merged_settings['timeout'] ?? 30,
			),
			'recent_posts' => $recent_posts,
			'category' => $prompt['category'] ?? '',
			'photos' => $prompt['photos'] ?? 0,
		);

		$headers = $this->get_gateway_request_headers();
		$headers['Content-Type'] = 'application/json';

		if ( empty( $headers['Authorization'] ) ) {
			$message = 'Gateway API key missing; falling back to direct OpenAI call for protected generation endpoint.';
			$this->aistma_log_manager->log( 'warning', $message );
			$allow_fallback = apply_filters( 'aistma_allow_gateway_fallback', true );
			if ( ! $allow_fallback ) {
				$this->aistma_log_manager->log( 'error', 'Gateway API key required but fallback disabled.' );
				return;
			}
			$this->generate_story_via_openai_api( $prompt_id, $prompt, $merged_settings, $this->api_key, $the_prompt );
			return;
		}

		$response = wp_remote_post( $api_url, array(
			'timeout' => 60,
			'headers' => $headers,
			'body' => wp_json_encode( $request_data ),
		) );

		if ( is_wp_error( $response ) ) {
			$error_message = $response->get_error_message();
			$this->aistma_log_manager->log( 'error', 'Master API error: ' . $error_message . ', falling back to direct OpenAI call' );
			// Fallback to direct OpenAI call
			$this->generate_story_via_openai_api( $prompt_id, $prompt, $merged_settings, $this->api_key, $the_prompt );
			return;
		}

		$response_code = wp_remote_retrieve_response_code( $response );
		$response_body = wp_remote_retrieve_body( $response );
		$data = json_decode( $response_body, true );

		if ( $response_code !== 200 ) {
			$this->aistma_log_manager->log( 'error', 'Master API returned HTTP ' . $response_code . ', falling back to direct OpenAI call' );
			// Only fall back to direct OpenAI if user has their own API key
			// If they have subscription or credits, they shouldn't be using direct OpenAI
			if ( ! $this->api_key ) {
				$error = __( 'Story generation temporarily unavailable. Please try again later.', 'ai-story-maker' );
				$this->aistma_log_manager->log( 'error', $error );
				throw new \RuntimeException( esc_html( $error ) );
			}
			// Fallback to direct OpenAI call (only if user has personal API key)
			$this->generate_story_via_openai_api( $prompt_id, $prompt, $merged_settings, $this->api_key, $the_prompt );
			return;
		}

		if ( json_last_error() !== JSON_ERROR_NONE ) {
			$this->aistma_log_manager->log( 'error', 'Invalid JSON response from Master API, falling back to direct OpenAI call' );
			// Only fall back to direct OpenAI if user has their own API key
			if ( ! $this->api_key ) {
				$error = __( 'Story generation service error. Please try again later.', 'ai-story-maker' );
				$this->aistma_log_manager->log( 'error', $error );
				throw new \RuntimeException( esc_html( $error ) );
			}
			// Fallback to direct OpenAI call (only if user has personal API key)
			$this->generate_story_via_openai_api( $prompt_id, $prompt, $merged_settings,  $this->api_key, $the_prompt );
			return;
		}

		if ( ! isset( $data['success'] ) || ! $data['success'] ) {
			$error_msg = isset( $data['error'] ) ? $data['error'] : 'Unknown error from Master API';
			$this->aistma_log_manager->log( 'error', 'Master API error: ' . $error_msg . ', falling back to direct OpenAI call' );
			// Only fall back to direct OpenAI if user has their own API key
			if ( ! $this->api_key ) {
				$error = __( 'Unable to generate story. Please try again later.', 'ai-story-maker' );
				$this->aistma_log_manager->log( 'error', $error );
				throw new \RuntimeException( esc_html( $error ) );
			}
			// Fallback to direct OpenAI call (only if user has personal API key)
			return $this->generate_story_via_openai_api( $prompt_id, $prompt, $merged_settings,  $this->api_key, $the_prompt );
		}

		// Success! Process the response from Master API
		return $this->process_master_api_response( $data, $prompt_id, $prompt, $merged_settings );
	}

	/**
	 * Generate story via direct OpenAI API (fallback method).
	 *
	 * @param  string $prompt_id             The prompt ID.
	 * @param  array  $prompt                The prompt data.
	 * @param  array  $merged_settings       Merged settings.
	 * @param  string $api_key               OpenAI API key.
	 * @param  string $the_prompt            The prompt text.
	 * @param  int    $user_id               The user ID for credit deduction.
	 * @return void
	 */
	private function generate_story_via_openai_api( $prompt_id, $prompt, $merged_settings, $api_key, $the_prompt, $user_id = 0 ) {
		// Check if the OpenAI API key is set and is valid.
		if ( empty( $api_key ) ) {
			$api_key = get_option( 'aistma_openai_api_key' );
		}

		if ( ! $api_key ) {
			$error = __( 'No credits and no OpenAI API key configured. Please subscribe or add your own API key.', 'ai-story-maker' );
			$this->aistma_log_manager->log( 'error', $error );
			throw new \RuntimeException( esc_html( $error ) );
		}

		// Enhance system content with tags guidance
		$system_content = $merged_settings['system_content'] ?? '';
		$system_content .= "\n\nReturn your response as a JSON object with the following structure:\n";
		$system_content .= "{\n";
		$system_content .= "  \"title\": \"article title\",\n";
		$system_content .= "  \"content\": \"article content in HTML format\",\n";
		$system_content .= "  \"tags\": [\"tag1\", \"tag2\", \"tag3\"]\n";
		$system_content .= "}\n";
		$system_content .= "Tags should be relevant keywords for the article. Include 3-5 tags.";

		$response = wp_remote_post(
			'https://api.openai.com/v1/chat/completions',
			array(
				'headers' => array(
					'Authorization' => 'Bearer ' . $api_key,
					'Content-Type'  => 'application/json',
				),
				'body'    => wp_json_encode(
					array(
						'model'           => $merged_settings['model'] ?? 'gpt-4-turbo',
						'messages'        => array(
							array(
								'role'    => 'system',
								'content' => $system_content,
							),
							array(
								'role'    => 'user',
								'content' => $the_prompt,
							),
						),
						'max_tokens'      => 1500,
						'response_format' => array( 'type' => 'json_object' ),
					),
					JSON_PRETTY_PRINT
				),
				'timeout' => $merged_settings['timeout'] ?? 30,
			)
		);

		$status_code = wp_remote_retrieve_response_code( $response );
		// Check if response is success.
		if ( 200 !== $status_code ) {
			/* translators: %d: HTTP status code returned by the OpenAI API */
			$error_msg = sprintf( __( 'OpenAI API returned HTTP %d', 'ai-story-maker' ), $status_code );
			$this->aistma_log_manager->log( 'error', $error_msg );
			throw new \RuntimeException( esc_html( $error_msg ) );
		}

		// Check if response is valid.
		if ( is_wp_error( $response ) ) {
			$error = $response->get_error_message();
			$this->aistma_log_manager->log( 'error', $error );
			throw new \RuntimeException( esc_html( $error ) );
		}

		// Check if response is empty.
		$response_body = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( ! isset( $response_body['choices'][0]['message']['content'] ) ) {
			$error = __( 'Invalid response from OpenAI API.', 'ai-story-maker' );
			$this->aistma_log_manager->log( 'error', $error );
			throw new \RuntimeException( esc_html( $error ) );
		}

		$parsed_content = json_decode( $response_body['choices'][0]['message']['content'], true );

		if ( ! isset( $parsed_content['title'], $parsed_content['content'] ) ) {
			$error = __( 'Invalid content structure, try to simplify your prompts', 'ai-story-maker' );
			$this->aistma_log_manager->log( 'error', $error );
			throw new \RuntimeException( esc_html( $error ) );
		}

		// Process the OpenAI response
		return $this->process_openai_response( $response_body, $parsed_content, $prompt_id, $prompt, $merged_settings, $user_id );
	}

	/**
	 * Process response from Master API.
	 *
	 * @param  array  $data           Response data from Master API.
	 * @param  string $prompt_id      The prompt ID.
	 * @param  array  $prompt         The prompt data.
	 * @param  array  $merged_settings Merged settings.
	 * @return void
	 */
	private function process_master_api_response( $data, $prompt_id, $prompt, $merged_settings ) {
		// Check for the new response format first
		if ( isset( $data['content']['title'], $data['content']['content'] ) ) {
			// New format: direct title and content
			$title = isset( $data['content']['title'] ) ? sanitize_text_field( $data['content']['title'] ) : __( 'Untitled Article', 'ai-story-maker' );
			$content = isset( $data['content']['content'] ) ? wp_kses_post( $data['content']['content'] ) : __( 'Content not available.', 'ai-story-maker' );
			$excerpt = isset( $data['content']['excerpt'] ) ? sanitize_textarea_field( $data['content']['excerpt'] ) : wp_trim_words( wp_strip_all_tags( $content ), 55, '...' );
			$references = isset( $data['content']['references'] ) && is_array( $data['content']['references'] ) ? $data['content']['references'] : array();
			$tags = isset( $data['content']['tags'] ) && is_array( $data['content']['tags'] ) ? $data['content']['tags'] : array();
		} else {
			$error = __( 'Invalid content structure from Master API', 'ai-story-maker' );
			$this->aistma_log_manager->log( 'error', $error );
			throw new \RuntimeException( esc_html( $error ) );
		}

		// Note: Image placeholders are already processed by the Master API, so we don't need to process them again
		$category_name = isset( $prompt['category'] ) ? sanitize_text_field( $prompt['category'] ) : __( 'News', 'ai-story-maker' );

		// Get or create category ID
		$category_id = get_cat_ID( $category_name );
		if ( 0 === $category_id ) {
			// Category doesn't exist, create it
			$category_id = wp_create_category( $category_name );
		}

		if ( 1 === (int) get_option( 'aistma_show_ai_attribution', 1 ) ) {
			$content .= '<div class="ai-story-model">' . __( 'generated by:', 'ai-story-maker' ) . ' ' . esc_html( get_option( 'aistma_master_model', 'gpt-4o-mini' )) . '</div>';
		}

		// Determine the post author.
		$post_author = 0;
		if ( isset( $prompt['author'] ) && ! empty( $prompt['author'] ) ) {
			$user = get_user_by( 'login', $prompt['author'] );
			if ( $user ) {
				$post_author = $user->ID;
			}
		}
		if ( ! $post_author ) {
			$post_author = get_current_user_id();
		}
		if ( ! $post_author ) {
			$post_author = 1; // Default to admin user ID 1 if no user is logged in.
		}

		// Determine post status based on auto_publish setting
		$auto_publish_value = isset( $prompt['auto_publish'] ) ? $prompt['auto_publish'] : false;
		$post_status = ( 1 === $auto_publish_value || true === $auto_publish_value ) ? 'publish' : 'draft';

		// Debug logging for auto_publish
		$this->aistma_log_manager->log( 'debug', 'Master API - Auto publish value: ' . ( $auto_publish_value ? 'true' : 'false' ) . ' (type: ' . gettype( $auto_publish_value ) . '), Post status: ' . $post_status );

		// Create the post.
		$post_data = array(
			'post_title'   => $title,
			'post_content' => $content,
			'post_excerpt' => $excerpt,
			'post_status'  => $post_status,
			'post_author'  => $post_author,
			'post_category' => array( $category_id ),
		);

		$post_id = wp_insert_post( $post_data );

		if ( is_wp_error( $post_id ) ) {
			$error = __( 'Error creating post: ', 'ai-story-maker' ) . $post_id->get_error_message();
			$this->aistma_log_manager->log( 'error', $error );
			throw new \RuntimeException( esc_html( $error ) );
		}

		// Store the prompt ID for later lookup in generate_ai_story_for_user()
		// Note: prompt_id can be numeric (post ID) or string (wizard prompt ID)
		if ( $post_id && $prompt_id ) {
			// Store as-is: numeric IDs stay numeric, string IDs stay string
			$stored_prompt_id = is_numeric( $prompt_id ) ? absint( $prompt_id ) : sanitize_key( $prompt_id );
			add_post_meta( $post_id, '_aistma_prompt_id', $stored_prompt_id, true );
		}

		// Credits are deducted by the gateway server-side. Just log the event.
		if ( $post_id ) {
			$current_user_id = get_current_user_id();
			if ( $current_user_id > 0 && class_exists( __NAMESPACE__ . '\\AISTMA_Gateway_Logger' ) ) {
				AISTMA_Gateway_Logger::log_story_generated( $current_user_id, $post_id, $prompt_id, 1 );
			}
		}

		// Set featured image from first image in content (Master API already processes images)
		if ( $post_id ) {
			$this->set_featured_image_from_content( $post_id, $content );
		}

		// Add tags to the post if provided
		if ( $post_id && ! empty( $tags ) ) {
			$this->aistma_log_manager->log( 'debug', 'Raw tags received from Master API: ' . wp_json_encode( $tags ) );
			
			$sanitized_tags = array();
			foreach ( $tags as $tag ) {
				$original_tag = $tag;
				$sanitized_tag = sanitize_text_field( trim( $tag ) );
				if ( ! empty( $sanitized_tag ) ) {
					$sanitized_tags[] = $sanitized_tag;
					$this->aistma_log_manager->log( 'debug', 'Tag processed: "' . $original_tag . '" -> "' . $sanitized_tag . '"' );
				} else {
					$this->aistma_log_manager->log( 'debug', 'Tag skipped (empty after sanitization): "' . $original_tag . '"' );
				}
			}
			
			if ( ! empty( $sanitized_tags ) ) {
				$result = wp_set_post_tags( $post_id, $sanitized_tags, true );
				if ( is_wp_error( $result ) ) {
					$this->aistma_log_manager->log( 'error', 'Failed to set tags for post ' . $post_id . ': ' . $result->get_error_message() );
				} else {
					$this->aistma_log_manager->log( 'info', 'Tags successfully added to post ' . $post_id . ': ' . implode( ', ', $sanitized_tags ) );
					$this->aistma_log_manager->log( 'debug', 'WordPress tag setting result: ' . wp_json_encode( $result ) );
				}
			} else {
				$this->aistma_log_manager->log( 'debug', 'No valid tags to add after sanitization' );
			}
		} else {
			$this->aistma_log_manager->log( 'debug', 'No tags provided or post ID not available. Tags: ' . wp_json_encode( $tags ?? array() ) . ', Post ID: ' . $post_id );
		}

		// Save post meta data
		if ( $post_id ) {
			$total_tokens = isset( $data['usage']['total_tokens'] ) ? (int) $data['usage']['total_tokens'] : 0;
			$request_id = isset( $data['usage']['request_id'] ) ? sanitize_text_field( $data['usage']['request_id'] ) : uniqid( 'ai_news_' );
			
			update_post_meta( $post_id, 'ai_story_maker_sources', wp_json_encode( $references ) );
			update_post_meta( $post_id, 'ai_story_maker_total_tokens', $total_tokens ?? 'N/A' );
			update_post_meta( $post_id, 'ai_story_maker_request_id', $request_id ?? 'N/A' );
			update_post_meta( $post_id, 'ai_story_maker_generated_via', 'master_api' );
			
			// Add enhancement tracking meta data
			$package_name      = isset( $data['package_name'] ) ? sanitize_text_field( $data['package_name'] ) : '';
			$enhancement_limit = 1; // default
			$package_id        = false;
			if ( ! empty( $package_name ) ) {
				$package_id = $this->get_package_id_by_name( $package_name );
				if ( $package_id !== false ) {
					$enhancement_limit = $this->get_package_enhancement_limit( $package_id );
					update_post_meta( $post_id, 'ai_story_maker_package_id', $package_id );
					$this->aistma_log_manager->log( 'info', 'Enhancement meta added to post ' . $post_id . ': package_name=' . $package_name . ', package_id=' . $package_id . ', limit=' . $enhancement_limit );
				} else {
					$this->aistma_log_manager->log( 'warning', 'Could not find package ID for package name: ' . $package_name . '. Defaulting enhancement limit to 1.' );
				}
			} else {
				$this->aistma_log_manager->log( 'warning', 'No package_name found in Master API response for post ' . $post_id . '. Defaulting enhancement limit to 1.' );
			}
			update_post_meta( $post_id, 'ai_story_maker_enhancements_limit', $enhancement_limit );
			update_post_meta( $post_id, 'ai_story_maker_enhancements_history', wp_json_encode( [] ) );
			
			// $this->aistma_log_manager->log( 'success', 'AI-generated news article created via Master API: ' . get_permalink( $post_id ), $request_id );
		}

		// Log usage from Master API response
		if ( isset( $data['usage']['total_tokens'] ) ) {
			$this->aistma_log_manager->log( 'info', 'Story generated via Master API. Tokens used: ' . $data['usage']['total_tokens'] );
		}

		$this->aistma_log_manager->log( 'info', 'Story generated successfully via Master API. Post ID: ' . $post_id );
		return $post_id;
	}

	/**
	 * Process response from OpenAI API.
	 *
	 * @param  array  $response_body   Response body from OpenAI API.
	 * @param  array  $parsed_content  Parsed content from OpenAI.
	 * @param  string $prompt_id       The prompt ID.
	 * @param  array  $prompt          The prompt data.
	 * @param  array  $merged_settings Merged settings.
	 * @return void
	 */
	private function process_openai_response( $response_body, $parsed_content, $prompt_id, $prompt, $merged_settings, $user_id = 0 ) {
		$total_tokens = isset( $response_body['usage']['total_tokens'] ) ? (int) $response_body['usage']['total_tokens'] : 0;
		$request_id   = isset( $response_body['id'] ) ? sanitize_text_field( $response_body['id'] ) : uniqid( 'ai_news_' );
		$title        = isset( $parsed_content['title'] ) ? sanitize_text_field( $parsed_content['title'] ) : __( 'Untitled Article', 'ai-story-maker' );
		$content      = isset( $parsed_content['content'] ) ? wp_kses_post( $parsed_content['content'] ) : __( 'Content not available.', 'ai-story-maker' );
		$tags         = isset( $parsed_content['tags'] ) && is_array( $parsed_content['tags'] ) ? $parsed_content['tags'] : array();
		$category_name = isset( $prompt['category'] ) ? sanitize_text_field( $prompt['category'] ) : __( 'News', 'ai-story-maker' );

		// Get or create category ID
		$category_id = get_cat_ID( $category_name );
		if ( 0 === $category_id ) {
			// Category doesn't exist, create it
			$category_id = wp_create_category( $category_name );
		}

		// Generate excerpt from content
		$excerpt = wp_trim_words( wp_strip_all_tags( $content ), 55, '...' );

		if ( 1 === (int) get_option( 'aistma_show_ai_attribution', 1 ) ) {
			$content .= '<div class="ai-story-model">' . __( 'generated by:', 'ai-story-maker' ) . ' ' . esc_html( get_option( 'aistma_master_model', 'gpt-4o-mini' )) . '</div>';
		}

		// Determine the post author.
		$post_author = 0;
		if ( isset( $prompt['author'] ) && ! empty( $prompt['author'] ) ) {
			$user = get_user_by( 'login', $prompt['author'] );
			if ( $user ) {
				$post_author = $user->ID;
			}
		}
		if ( ! $post_author ) {
			$post_author = get_current_user_id();
		}
		if ( ! $post_author ) {
			$post_author = 1; // Default to admin user ID 1 if no user is logged in.
		}

		// Determine post status based on auto_publish setting
		$auto_publish_value = isset( $prompt['auto_publish'] ) ? $prompt['auto_publish'] : false;
		$post_status = ( 1 === $auto_publish_value || true === $auto_publish_value ) ? 'publish' : 'draft';
		
		// Debug logging for auto_publish
		$this->aistma_log_manager->log( 'debug', 'OpenAI API - Auto publish value: ' . ( $auto_publish_value ? 'true' : 'false' ) . ' (type: ' . gettype( $auto_publish_value ) . '), Post status: ' . $post_status );

		// Create the post.
		$post_data = array(
			'post_title'   => $title,
			'post_content' => $content,
			'post_excerpt' => $excerpt,
			'post_status'  => $post_status,
			'post_author'  => $post_author,
			'post_category' => array( $category_id ),
		);

		$post_id = wp_insert_post( $post_data );

		if ( is_wp_error( $post_id ) ) {
			$error = __( 'Error creating post: ', 'ai-story-maker' ) . $post_id->get_error_message();
			$this->aistma_log_manager->log( 'error', $error );
			throw new \RuntimeException( esc_html( $error ) );
		}

		// Store the prompt ID for later lookup in generate_ai_story_for_user()
		// Note: prompt_id can be numeric (post ID) or string (wizard prompt ID)
		if ( $post_id && $prompt_id ) {
			// Store as-is: numeric IDs stay numeric, string IDs stay string
			$stored_prompt_id = is_numeric( $prompt_id ) ? absint( $prompt_id ) : sanitize_key( $prompt_id );
			add_post_meta( $post_id, '_aistma_prompt_id', $stored_prompt_id, true );
		}

		// Credits are deducted by the gateway server-side. Just log the event.
		if ( $post_id && $user_id > 0 && class_exists( __NAMESPACE__ . '\\AISTMA_Gateway_Logger' ) ) {
			AISTMA_Gateway_Logger::log_story_generated( $user_id, $post_id, $prompt_id, 1 );
		}

		// Add tags to the post if provided
		if ( $post_id && ! empty( $tags ) ) {
			$this->aistma_log_manager->log( 'debug', 'Raw tags from OpenAI API: ' . wp_json_encode( $tags ) );

			$sanitized_tags = array();
			foreach ( $tags as $tag ) {
				if ( is_string( $tag ) ) {
					$sanitized_tag = sanitize_text_field( $tag );
					if ( ! empty( $sanitized_tag ) ) {
						$sanitized_tags[] = $sanitized_tag;
					}
				}
			}

			if ( ! empty( $sanitized_tags ) ) {
				$result = wp_set_post_tags( $post_id, $sanitized_tags, true );
				if ( is_wp_error( $result ) ) {
					$this->aistma_log_manager->log( 'error', 'Failed to set tags for post ' . $post_id . ': ' . $result->get_error_message() );
				} else {
					$this->aistma_log_manager->log( 'info', 'Tags successfully added to post ' . $post_id . ': ' . implode( ', ', $sanitized_tags ) );
				}
			} else {
				$this->aistma_log_manager->log( 'debug', 'No valid tags to add after sanitization' );
			}
		} else {
			$this->aistma_log_manager->log( 'debug', 'No tags provided or post ID not available. Tags: ' . wp_json_encode( $tags ?? array() ) . ', Post ID: ' . $post_id );
		}

		// Process image placeholders and set featured image
		if ( $post_id ) {
			$content = $this->replace_image_placeholders( $content, $post_id );

			// Update the post with processed content
		wp_update_post( array(
			'ID' => $post_id,
			'post_content' => $content
		) );
		}

		// Save post meta data
		if ( $post_id ) {
			update_post_meta( $post_id, 'ai_story_maker_sources', isset( $parsed_content['references'] ) && is_array( $parsed_content['references'] ) ? wp_json_encode( $parsed_content['references'] ) : wp_json_encode( array() ) );
			update_post_meta( $post_id, 'ai_story_maker_total_tokens', $total_tokens ?? 'N/A' );
			update_post_meta( $post_id, 'ai_story_maker_request_id', $request_id ?? 'N/A' );
			update_post_meta( $post_id, 'ai_story_maker_generated_via', 'openai_api' );
			
			// Add enhancement tracking meta data for OpenAI API generated posts
			$subscription_info = $this->get_subscription_info();
			$package_name      = $subscription_info['package_name'] ?? '';
			$enhancement_limit = 1; // default
			if ( ! empty( $package_name ) ) {
				$package_id = $this->get_package_id_by_name( $package_name );
				if ( $package_id !== false ) {
					$enhancement_limit = $this->get_package_enhancement_limit( $package_id );
					update_post_meta( $post_id, 'ai_story_maker_package_id', $package_id );
				}
			}
			update_post_meta( $post_id, 'ai_story_maker_enhancements_limit', $enhancement_limit );
			update_post_meta( $post_id, 'ai_story_maker_enhancements_history', wp_json_encode( [] ) );
			
			$this->aistma_log_manager->log( 'success', 'AI-generated news article created via OpenAI API: ' . get_permalink( $post_id ), $request_id );
		}

		$this->aistma_log_manager->log( 'info', 'Story generated successfully via OpenAI API. Post ID: ' . $post_id . ', Tokens used: ' . $total_tokens );
		return $post_id;
	}

	/**
	 * Get master instructions for AI story generation.
	 *
	 * @param array $recent_posts Array of recent posts to exclude from generation.
	 * @return string Master instructions for AI story generation.
	 */
	public function aistma_get_master_instructions() {
		// Fetch dynamic system content from Exedotcom API Gateway.
		$aistma_master_instructions = get_transient( 'aistma_exaig_cached_master_instructions' );
		if ( false === $aistma_master_instructions ) {
			// No cache, fetch from the API.
			try {
				// Get plugin version
				$plugin_data = get_plugin_data( AISTMA_PATH . 'ai-story-maker.php' );
				$plugin_version = $plugin_data['Version'] ?? 'unknown';
				
				// Get subscription information
				$subscription_info = $this->get_subscription_info();
				$subscription_plan = $subscription_info['package_name'] ?? 'Using own API key';
				
				$api_response = wp_remote_post(
					aistma_get_instructions_url(),
					array(
						'timeout' => 10,
						'headers' => array(
							'X-Caller-Url' => home_url(),
							'X-Caller-IP'  => isset( $_SERVER['SERVER_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['SERVER_ADDR'] ) ) : '',
							'Content-Type' => 'application/json',
						),
						'body' => json_encode( array(
							'plugin_version' => $plugin_version,
							'subscription_plan' => $subscription_plan,
							'caller-domain' => home_url(),
							'caller-ip' => isset( $_SERVER['SERVER_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['SERVER_ADDR'] ) ) : '',

						) ),
					)
				);

				if ( ! is_wp_error( $api_response ) ) {
						$body = wp_remote_retrieve_body( $api_response );
						$json = json_decode( $body, true );
					if ( isset( $json['instructions'] ) ) {
						$aistma_master_instructions = sanitize_textarea_field( $json['instructions'] );
						set_transient( 'aistma_exaig_cached_master_instructions', $aistma_master_instructions, 5 * MINUTE_IN_SECONDS );
					}
				} else {
					// Silent fail; fallback will be handled below.
					$this->aistma_log_manager->log( 'error', 'Error fetching dynamic instructions: ' . $api_response->get_error_message() );
					$aistma_master_instructions = '';
				}
			} catch ( \Exception $e ) {
				// Silent fail; fallback will be handled below.
				$this->aistma_log_manager->log( 'error', 'Error fetching master instructions: ' . $e->getMessage() );
				$aistma_master_instructions = '';
			}
		}

		// Fallback if API call failed or returned empty.
		if ( empty( $aistma_master_instructions ) ) {
			$aistma_master_instructions = 'Write a fact-based, original article based on real-world information. Organize the article clearly with a proper beginning, middle, and conclusion.';
		}
		


		return $aistma_master_instructions;
	}

	/**
	 * Retrieve recent post excerpts from a specific category.
	 *
	 * @param  int    $number_of_posts Number of posts to retrieve.
	 * @param  string $category        Category name.
	 * @return array
	 */
	public function aistma_get_recent_posts( $number_of_posts, $category ) {
		$category_id     = get_cat_ID( $category ) ? get_cat_ID( $category ) : 0;
		$number_of_posts = absint( $number_of_posts );

		$posts = get_posts(
			array(
				'numberposts' => $number_of_posts,
				'post_status' => 'publish',
				'category'    => $category_id,
			)
		);

		$results = array();

		foreach ( $posts as $post ) {
			$content = $post->post_content;
			$excerpt = '';
			if ( ! empty( $content ) ) {
				$excerpt = wp_trim_words( strip_shortcodes( wp_strip_all_tags( $content ) ), 55 );
			}
			$results[] = array(
				'title'   => get_the_title( $post->ID ),
				'excerpt' => $excerpt,
			);
		}
		wp_reset_postdata();
		return $results;
	}

	/**
	 * Replace image placeholders in the article content with images from multiple sources.
	 *
	 * Supports multiple image resources via placeholders like {img_unsplash:...}, {img_pexels:...}, {img_pixabay:...}
	 *
	 * @param  string $article_content The article content with image placeholders.
	 * @param  int    $post_id         The post ID to set featured image for.
	 * @return string The article content with image placeholders replaced by images from the configured sources.
	 */
	public function replace_image_placeholders( $article_content, $post_id = 0 ) {
		$self = $this; // Assign $this to $self.
		$image_urls = array();
		$image_count = 0;

		$processed_content = preg_replace_callback(
			'/\{img_([a-z]+):([a-zA-Z0-9,_ ]+)\}/',
			function ( $matches ) use ( $self, &$image_urls, &$image_count ) {
				$resource_type = $matches[1];
				$keywords = explode( ',', $matches[2] );
				// Trim whitespace from keywords
				$keywords = array_map( 'trim', $keywords );

				$image_data = null;

				// Call the appropriate fetch method based on resource type
				if ( 'unsplash' === $resource_type ) {
					$image_data = $self->fetch_unsplash_image_data( $keywords );
				} elseif ( 'pexels' === $resource_type ) {
					$image_data = $self->fetch_pexels_image_data( $keywords );
				} elseif ( 'pixabay' === $resource_type ) {
					$image_data = $self->fetch_pixabay_image_data( $keywords );
				}

				if ( $image_data ) {
					$image_count++;

					// Store all image URLs for featured image selection
					$image_urls[] = $image_data['url'];

					return $image_data['html'];
				}

				return '';
			},
			$article_content
		);

		// Try to set a featured image from the collected URLs if we have a post ID
		if ( $post_id && ! empty( $image_urls ) ) {
			// Try each image until we find one that's not already used
			foreach ( $image_urls as $image_url ) {
				$result = $this->set_featured_image_from_url( $post_id, $image_url );
				if ( $result ) {
					// Successfully set featured image, stop trying
					break;
				}
			}
		}

		return $processed_content;
	}

	/**
	 * Fetch an image from Unsplash based on the provided keywords.
	 *
	 * @param  array $keywords The keywords to search for.
	 * @return string The HTML markup for the image or an empty string if no image is found.
	 */
	public function fetch_unsplash_image( $keywords ) {
		$image_data = $this->fetch_unsplash_image_data( $keywords );
		return $image_data ? $image_data['html'] : '';
	}

	/**
	 * Fetch image data from Unsplash based on the provided keywords.
	 *
	 * @param  array $keywords The keywords to search for.
	 * @return array|false Array with 'url' and 'html' keys, or false if no image found.
	 */
	public function fetch_unsplash_image_data( $keywords ) {
		$api_key = get_option( 'aistma_unsplash_api_key' );

		if ( ! $api_key ) {
			$this->aistma_log_manager->log( 'error', 'Unsplash API key not configured' );
			return false;
		}

		$query    = implode( ',', $keywords );
		$url      = 'https://api.unsplash.com/search/photos?query=' . rawurlencode( $query ) . '&client_id=' . $api_key . '&per_page=30&orientation=landscape&quantity=100';
		$response = wp_remote_get( $url );

		if ( is_wp_error( $response ) ) {
			$this->aistma_log_manager->log( 'error', 'Error fetching Unsplash image: ' . $response->get_error_message() );
			return false;
		}
		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );
		if ( empty( $data['results'] ) ) {
			$this->aistma_log_manager->log( 'error', 'No Unsplash images found for keywords: ' . $query );
			return false;
		}
		$image_index = array_rand( $data['results'] );
		if ( ! empty( $data['results'][ $image_index ]['urls']['small'] ) ) {
			$url     = $data['results'][ $image_index ]['urls']['small'];
			$credits = $data['results'][ $image_index ]['user']['name'] . ' by unsplash.com';
			// As required by unsplash.
         // phpcs:ignore PluginCheck.CodeAnalysis.ImageFunctions.NonEnqueuedImage
			$html = '<figure><img src="' . esc_url( $url ) . '" alt="' . esc_attr( implode( ' ', $keywords ) ) . '" /><figcaption>' . esc_html( $credits ) . '</figcaption></figure>';

			return array(
				'url' => $url,
				'html' => $html,
				'credits' => $credits
			);
		}

		return false; // Return false if no images found.
	}

	/**
	 * Fetch image data from Pexels based on the provided keywords.
	 *
	 * @param  array $keywords The keywords to search for.
	 * @return array|false Array with 'url', 'html', and 'credits' keys on success, false on failure.
	 */
	public function fetch_pexels_image_data( $keywords ) {
		$api_key = get_option( 'aistma_pexels_api_key' );

		if ( ! $api_key ) {
			$this->aistma_log_manager->log( 'error', 'Pexels API key not configured' );
			return false;
		}

		$query = implode( ' ', $keywords );
		$url = 'https://api.pexels.com/v1/search?query=' . rawurlencode( $query ) . '&per_page=30&orientation=landscape';
		$response = wp_remote_get( $url, array(
			'headers' => array(
				'Authorization' => $api_key
			)
		) );

		if ( is_wp_error( $response ) ) {
			$this->aistma_log_manager->log( 'error', 'Error fetching Pexels image: ' . $response->get_error_message() );
			return false;
		}

		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );

		if ( empty( $data['photos'] ) ) {
			$this->aistma_log_manager->log( 'error', 'No Pexels images found for keywords: ' . $query );
			return false;
		}

		$image_index = array_rand( $data['photos'] );
		$photo = $data['photos'][ $image_index ];

		if ( ! empty( $photo['src']['small'] ) ) {
			$image_url = $photo['src']['small'];
			$credits = 'Photo by ' . $photo['photographer'] . ' on pexels.com';
			// phpcs:ignore PluginCheck.CodeAnalysis.ImageFunctions.NonEnqueuedImage
			$html = '<figure><img src="' . esc_url( $image_url ) . '" alt="' . esc_attr( implode( ' ', $keywords ) ) . '" /><figcaption>' . esc_html( $credits ) . '</figcaption></figure>';

			return array(
				'url' => $image_url,
				'html' => $html,
				'credits' => $credits
			);
		}

		return false;
	}

	/**
	 * Fetch image data from Pixabay based on the provided keywords.
	 *
	 * @param  array $keywords The keywords to search for.
	 * @return array|false Array with 'url', 'html', and 'credits' keys on success, false on failure.
	 */
	public function fetch_pixabay_image_data( $keywords ) {
		$api_key = get_option( 'aistma_pixabay_api_key' );

		if ( ! $api_key ) {
			$this->aistma_log_manager->log( 'error', 'Pixabay API key not configured' );
			return false;
		}

		$query = implode( ' ', $keywords );
		$url = 'https://pixabay.com/api/?key=' . $api_key . '&q=' . rawurlencode( $query ) . '&per_page=30&image_type=photo&orientation=horizontal';
		$response = wp_remote_get( $url );

		if ( is_wp_error( $response ) ) {
			$this->aistma_log_manager->log( 'error', 'Error fetching Pixabay image: ' . $response->get_error_message() );
			return false;
		}

		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );

		if ( empty( $data['hits'] ) ) {
			$this->aistma_log_manager->log( 'error', 'No Pixabay images found for keywords: ' . $query );
			return false;
		}

		$image_index = array_rand( $data['hits'] );
		$image = $data['hits'][ $image_index ];

		if ( ! empty( $image['webformatURL'] ) ) {
			$image_url = $image['webformatURL'];
			$credits = 'Image from pixabay.com';
			// phpcs:ignore PluginCheck.CodeAnalysis.ImageFunctions.NonEnqueuedImage
			$html = '<figure><img src="' . esc_url( $image_url ) . '" alt="' . esc_attr( implode( ' ', $keywords ) ) . '" /><figcaption>' . esc_html( $credits ) . '</figcaption></figure>';

			return array(
				'url' => $image_url,
				'html' => $html,
				'credits' => $credits
			);
		}

		return false;
	}

	/**
	 * Download and set featured image from URL.
	 *
	 * @param  int    $post_id The post ID to set featured image for.
	 * @param  string $image_url The URL of the image to download.
	 * @return int|false The attachment ID on success, false on failure.
	 */
	private function set_featured_image_from_url( $post_id, $image_url ) {
		// Check if post exists
		if ( ! get_post( $post_id ) ) {
			$this->aistma_log_manager->log( 'error', 'Post not found for featured image: ' . $post_id );
			return false;
		}

		// Download the image
		$upload = media_sideload_image( $image_url, $post_id, '', 'id' );
		
		if ( is_wp_error( $upload ) ) {
			$this->aistma_log_manager->log( 'error', 'Failed to download featured image: ' . $upload->get_error_message() );
			return false;
		}

		// Check if this attachment is already used as a featured image by another post
		if ( $this->is_attachment_used_as_featured_image( $upload, $post_id ) ) {
			$this->aistma_log_manager->log( 'warning', 'Attachment ' . $upload . ' is already used as featured image by another post. Skipping.' );
			// Delete the uploaded attachment since we won't use it
			wp_delete_attachment( $upload, true );
			return false;
		}

		// Set as featured image
		$result = set_post_thumbnail( $post_id, $upload );
		
		if ( $result ) {
			$this->aistma_log_manager->log( 'info', 'Featured image set successfully for post ' . $post_id );
		} else {
			$this->aistma_log_manager->log( 'error', 'Failed to set featured image for post ' . $post_id );
		}

		return $upload;
	}

	/**
	 * Extract first image from content and set as featured image.
	 *
	 * @param  int    $post_id The post ID to set featured image for.
	 * @param  string $content The post content to extract image from.
	 * @return int|false The attachment ID on success, false on failure.
	 */
	private function set_featured_image_from_content( $post_id, $content ) {
		// Check if post exists
		if ( ! get_post( $post_id ) ) {
			$this->aistma_log_manager->log( 'error', 'Post not found for featured image: ' . $post_id );
			return false;
		}

		// Extract all image URLs from content
		$image_urls = $this->extract_all_image_urls( $content );
		
		if ( empty( $image_urls ) ) {
			$this->aistma_log_manager->log( 'info', 'No image found in content for featured image on post ' . $post_id );
			return false;
		}

		// Try each image URL until we find one that's not already used
		foreach ( $image_urls as $image_url ) {
			$result = $this->set_featured_image_from_url( $post_id, $image_url );
			if ( $result ) {
				// Successfully set featured image
				return $result;
			}
			// If failed (e.g., already used), try the next image
		}

		// All images were already used or failed to download
		$this->aistma_log_manager->log( 'warning', 'All images in content are already used as featured images by other posts for post ' . $post_id );
		return false;
	}

	/**
	 * Extract the first image URL from HTML content.
	 *
	 * @param  string $content The HTML content to search.
	 * @return string|false The image URL or false if not found.
	 */
	private function extract_first_image_url( $content ) {
		// Look for img tags
		if ( preg_match( '/<img[^>]+src=["\']([^"\']+)["\'][^>]*>/i', $content, $matches ) ) {
			return $matches[1];
		}

		// Look for figure tags with img inside
		if ( preg_match( '/<figure[^>]*>.*?<img[^>]+src=["\']([^"\']+)["\'][^>]*>/is', $content, $matches ) ) {
			return $matches[1];
		}

		return false;
	}

	/**
	 * Extract all image URLs from HTML content.
	 *
	 * @param  string $content The HTML content to search.
	 * @return array Array of image URLs found in the content.
	 */
	private function extract_all_image_urls( $content ) {
		$image_urls = array();

		// Look for all img tags
		if ( preg_match_all( '/<img[^>]+src=["\']([^"\']+)["\'][^>]*>/i', $content, $matches ) ) {
			$image_urls = array_merge( $image_urls, $matches[1] );
		}

		// Look for figure tags with img inside
		if ( preg_match_all( '/<figure[^>]*>.*?<img[^>]+src=["\']([^"\']+)["\'][^>]*>/is', $content, $matches ) ) {
			$image_urls = array_merge( $image_urls, $matches[1] );
		}

		// Remove duplicates
		$image_urls = array_unique( $image_urls );

		return array_values( $image_urls );
	}

	/**
	 * Check if an attachment is already used as a featured image by another post.
	 *
	 * @param  int $attachment_id The attachment ID to check.
	 * @param  int $current_post_id The current post ID (to exclude from check).
	 * @return bool True if the attachment is used by another post, false otherwise.
	 */
	private function is_attachment_used_as_featured_image( $attachment_id, $current_post_id ) {
		global $wpdb;

		// Query to find posts that use this attachment as their featured image
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$posts_using_image = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(post_id) FROM {$wpdb->postmeta} 
				WHERE meta_key = '_thumbnail_id' 
				AND meta_value = %d 
				AND post_id != %d",
				$attachment_id,
				$current_post_id
			)
		);

		return $posts_using_image > 0;
	}

	/**
	 * Check if the schedule is set for the next event.
	 *
	 * @return void
	 */
	public function check_schedule() {
		$next_event = wp_next_scheduled( 'aistma_generate_story_event' );

		if ( ! $next_event ) {
			$n = absint( get_option( 'aistma_generate_story_cron', 2 ) );
			if ( 0 !== $n ) {
				$run_at = time() + $n * DAY_IN_SECONDS;
				wp_schedule_single_event( $run_at, 'aistma_generate_story_event' );
				$this->aistma_log_manager->log( 'info', 'Scheduled next AI story generation at: ' . $this->format_date_for_display( $run_at ) );
			}
		}
	}

	/**
	 * Reschedule the cron event for AI story generation.
	 *
	 * @return void
	 */
	public function reschedule_cron_event() {
		$timestamp = wp_next_scheduled( 'aistma_generate_story_event' );
		if ( $timestamp ) {
			wp_unschedule_event( $timestamp, 'aistma_generate_story_event' );
		}

		$n = absint( get_option( 'aistma_generate_story_cron', 2 ) );
		if ( 0 !== $n ) {
			$run_at = time() + $n * DAY_IN_SECONDS;
			wp_schedule_single_event( $run_at, 'aistma_generate_story_event' );
			$this->aistma_log_manager->log( 'info', 'Rescheduled cron event: ' . $this->format_date_for_display( $run_at ) );
		}
	}

	/**
	 * Check subscription status for the current domain.
	 *
	 * Makes an API call to the gateway verify-subscription endpoint to check if the domain
	 * has an active subscription. This determines whether to use the master API or require
	 * the user's own OpenAI/Unsplash keys.
	 *
	 * @param string $domain Optional domain to check. If not provided, uses current site domain.
	 *
	 * @return array Subscription status array with the following structure:
	 *              SUCCESS: [
	 *                'valid' => bool,                  // true if subscription is active
	 *                'status' => string,               // 'active', 'active_no_credits', 'expired', etc.
	 *                'domain' => string,               // verified domain
	 *                'package_id' => string,           // subscription plan ID
	 *                'package_name' => string,         // plan name (e.g., 'Starter', 'Professional')
	 *                'price' => float,                 // plan price
	 *                'created_at' => string,           // subscription start date
	 *                'next_billing_date' => string,    // next renewal date
	 *                'user_email' => string,           // associated email
	 *                'credits_remaining' => int,       // remaining credits (0 if no credits)
	 *              ]
	 *              ERROR: [
	 *                'valid' => false,
	 *                'error' => string,                // error message
	 *                'domain' => string,
	 *              ]
	 *
	 * IMPORTANT: valid=true means subscription is active, NOT that credits are available.
	 *            Always check credits_remaining separately. The UI shows "No credits remaining"
	 *            when credits_remaining=0 even if valid=true (active subscription).
	 */
	public function aistma_get_subscription_status( $domain = '' ) {
		$master_url = aistma_get_api_url();
		// Get current domain with port if it exists
		if ( empty( $domain ) ) {
			$domain = sanitize_text_field( wp_unslash( $_SERVER['HTTP_HOST'] ?? '' ) );

		}

		// Get master URL from WordPress constant
		
		if ( empty( $master_url ) ) {
			$this->aistma_log_manager->log( 'error', 'API Gateway URL not configured' );
			$this->subscription_status = array(
				'valid' => false,
				'domain' => $domain,
				'error' => 'API Gateway URL not configured',
			);
			return $this->subscription_status;
		}

		// Make API call to master server to check subscription status
		$api_url = trailingslashit( $master_url ) . 'wp-json/exaig/v1/verify-subscription?domain=' . urlencode( $domain );
		
		$this->aistma_log_manager->log( 'info', 'Checking subscription status for domain: ' . $domain . ' at URL: ' . $api_url );
		
		$response = wp_remote_get( $api_url, array(
			'timeout' => 30,
			'headers' => $this->get_gateway_request_headers(),
		) );

		if ( is_wp_error( $response ) ) {
			$error_message = $response->get_error_message();
			$this->aistma_log_manager->log( 'error', 'Error checking subscription status: ' . $error_message );
			$this->subscription_status = array(
				'valid' => false,
				'error' => 'Network error: ' . $error_message,
				'domain' => $domain,
			);
			return $this->subscription_status;
		}

		$response_code = wp_remote_retrieve_response_code( $response );
		$response_body = wp_remote_retrieve_body( $response );
		
		$this->aistma_log_manager->log( 'info', 'Subscription API response code: ' . $response_code . ', body: ' . substr( $response_body, 0, 500 ) );
		
		$data = json_decode( $response_body, true );

		if ( $response_code !== 200 ) {
			$this->aistma_log_manager->log( 'error', 'API error checking subscription status. Response code: ' . $response_code . ', Body: ' . $response_body );
			$this->subscription_status = array(
				'valid' => false,
				'error' => 'API error: HTTP ' . $response_code,
				'domain' => $domain,
				'credits_remaining' => 0,
			);
			return $this->subscription_status;
		}

		if ( json_last_error() !== JSON_ERROR_NONE ) {
			$this->aistma_log_manager->log( 'error', 'Invalid JSON response from subscription API. Body: ' . $response_body );
			$this->subscription_status = array(
				'valid' => false,
				'error' => 'Invalid JSON response',
				'domain' => $domain,
				'credits_remaining' => 0,
			);
			return $this->subscription_status;
		}
		// Handle active subscription with no credits remaining.
		// $data comes from the gateway API response (verify-subscription endpoint)
		// and contains: valid, status, domain, package_id, package_name, price, created_at, next_billing_date, user_email, credits_remaining
		// IMPORTANT: valid=true no longer means "has credits" — it means "has active subscription"
		// Callers must check credits_remaining separately. The template displays "No credits remaining" via the status line logic.
		if ( isset( $data['valid'] ) && $data['valid'] && isset( $data['status'] ) && $data['status'] === 'active_no_credits' ) {
			$this->aistma_log_manager->log( 'info', 'Subscription valid but no credits remaining for domain: ' . $domain );
			$this->subscription_status = array(
				'valid' => true,
				'domain' => $data['domain'] ?? $domain,
				'credits_remaining' => 0,
				'package_id' => $data['package_id'] ?? '',
				'package_name' => $data['package_name'] ?? '',
				'price' => floatval( $data['price'] ?? 0 ),
				'created_at' => $data['created_at'] ?? '',
				'next_billing_date' => $data['next_billing_date'] ?? '',
				'user_email' => $data['user_email'] ?? '',
			);
			if ( ! empty( $data['client_api_key'] ) && empty( get_option( 'aistma_gateway_api_key' ) ) ) {
				update_option( 'aistma_gateway_api_key', sanitize_text_field( $data['client_api_key'] ) );
			}
			return $this->subscription_status;
		}
		if ( isset( $data['valid'] ) && $data['valid'] ) {
			$this->aistma_log_manager->log( 'info', 'Subscription found for domain: ' . $domain . ' - Credits remaining: ' . ( $data['credits_remaining'] ?? 'n/a' ) );
			$this->subscription_status = array(
				'valid' => true,
				'domain' => $data['domain'] ?? $domain,
				'credits_remaining' => isset( $data['credits_remaining'] ) ? intval( $data['credits_remaining'] ) : null,
				'package_id' => $data['package_id'] ?? '',
				'package_name' => $data['package_name'] ?? '',
				'price' => floatval( $data['price'] ?? 0 ),
				'created_at' => $data['created_at'] ?? '',
				'next_billing_date' => $data['next_billing_date'] ?? '',
				'authenticated' => ! empty( $data['authenticated'] ),
			);

			// Store the gateway API key so subsequent authenticated calls (story generation) succeed.
			if ( ! empty( $data['client_api_key'] ) && empty( get_option( 'aistma_gateway_api_key' ) ) ) {
				update_option( 'aistma_gateway_api_key', sanitize_text_field( $data['client_api_key'] ) );
				$this->aistma_log_manager->log( 'info', 'Gateway API key stored from subscription verification for domain: ' . $domain );
			}
		} else {
			$this->aistma_log_manager->log( 'info', 'No active subscription found for domain: ' . $domain . ', message: ' . ( $data['message'] ?? 'No message' ) );
			$this->subscription_status = array(
				'valid' => false,
				'message' => $data['message'] ?? 'No subscription found',
				'domain' => $domain,
				'credits_remaining' => 0,
			);
		}

		return $this->subscription_status;
	}

	/**
	 * Get the cached subscription status.
	 *
	 * @return array|null The cached subscription status or null if not set.
	 */
	public function get_cached_subscription_status() {
		return $this->subscription_status;
	}

	/**
	 * Check if subscription status is cached.
	 *
	 * @return bool True if subscription status is cached, false otherwise.
	 */
	public function has_cached_subscription_status() {
		return ! empty( $this->subscription_status );
	}

	/**
	 * Clear the cached subscription status.
	 *
	 * @return void
	 */
	public function clear_cached_subscription_status() {
		$this->subscription_status = null;
	}

	/**
	 * Get subscription information for use during story generation.
	 *
	 * @return array Subscription information including domain, package name, etc.
	 */
	public function get_subscription_info() {
		if ( ! $this->has_cached_subscription_status() ) {
			// If not cached, fetch it
			$this->aistma_get_subscription_status();
		}
		
		return $this->subscription_status ?? array(
			'valid' => false,
			'domain' => '',
			'package_name' => '',
			'package_id' => '',
			'credits_remaining' => 0,
			'price' => 0.0,
			'created_at' => '',
		);
	}

	/**
	 * Whether the gateway authorizes story generation for this domain.
	 *
	 * The gateway is the single source of truth for credits: a domain may
	 * generate when it has a valid subscription that is not out of credits.
	 * A null credits_remaining means the gateway did not report a number
	 * (e.g. unmetered plan) — treat as allowed and let the gateway enforce.
	 *
	 * @return bool
	 */
	public function gateway_can_generate() {
		$info = $this->get_subscription_info();
		if ( empty( $info['valid'] ) ) {
			return false;
		}
		$remaining = array_key_exists( 'credits_remaining', $info ) ? $info['credits_remaining'] : null;
		return ( null === $remaining || intval( $remaining ) > 0 );
	}

	/**
	 * Remaining gateway credits for this domain.
	 *
	 * @return int|null Remaining credit count, or null if unknown/unmetered.
	 */
	public function gateway_credits_remaining() {
		$info = $this->get_subscription_info();
		if ( empty( $info['valid'] ) || ! array_key_exists( 'credits_remaining', $info ) ) {
			return 0;
		}
		return null === $info['credits_remaining'] ? null : intval( $info['credits_remaining'] );
	}

	/**
	 * Check if the current subscription is a free subscription.
	 *
	 * @return bool True if it's a free subscription, false otherwise.
	 */
	public function is_free_subscription() {
		$subscription_info = $this->get_subscription_info();
		return isset( $subscription_info['package_name'] ) && $subscription_info['package_name'] === 'Free subscription';
	}

	/**
	 * Get the subscription domain.
	 *
	 * @return string The subscription domain.
	 */
	public function get_subscription_domain() {
		$subscription_info = $this->get_subscription_info();
		return $subscription_info['domain'] ?? '';
	}

	/**
	 * Get the subscription package name.
	 *
	 * @return string The subscription package name.
	 */
	public function get_subscription_package_name() {
		$subscription_info = $this->get_subscription_info();
		return $subscription_info['package_name'] ?? '';
	}

	/**
	 * Convert GMT timestamp to WordPress timezone for display.
	 *
	 * @param int $gmt_timestamp The GMT timestamp to convert.
	 * @return string The formatted date/time in WordPress timezone.
	 */
	private function format_date_for_display( $gmt_timestamp ) {
		// Convert GMT timestamp to WordPress timezone
		$wp_timestamp = get_date_from_gmt( gmdate( 'Y-m-d H:i:s', $gmt_timestamp ), 'Y-m-d H:i:s' );
		return $wp_timestamp;
	}



	/**
	 * Get package ID by package name from API Gateway.
	 *
	 * @param string $package_name The package name to search for.
	 * @return int|false Package ID if found, false otherwise.
	 */
	private function get_package_id_by_name( $package_name ) {
		$gateway_url = aistma_get_api_url();
		if ( empty( $gateway_url ) ) {
			$this->aistma_log_manager->log( 'error', 'API Gateway URL not configured for package lookup' );
			return false;
		}

		$api_url = trailingslashit( $gateway_url ) . 'wp-json/exaig/v1/packages-summary';
		
		$this->aistma_log_manager->log( 'info', 'Looking up package ID for: ' . $package_name . ' at URL: ' . $api_url );
		
		$response = wp_remote_get( $api_url, [
			'timeout' => 10,
			'headers' => [
				'User-Agent' => 'AI-Story-Maker/1.0'
			]
		] );

		if ( is_wp_error( $response ) ) {
			$this->aistma_log_manager->log( 'error', 'Error fetching packages from API Gateway: ' . $response->get_error_message() );
			return false;
		}

		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );

		if ( is_array( $data ) ) {
			$this->aistma_log_manager->log( 'info', 'Found ' . count( $data ) . ' packages from API Gateway' );
			$this->aistma_log_manager->log( 'info', 'Available packages: ' . implode( ', ', array_column( $data, 'name' ) ) );
			foreach ( $data as $index => $package ) {
				if ( isset( $package['name'] ) && $package['name'] === $package_name ) {
					$this->aistma_log_manager->log( 'info', 'Found matching package: ' . $package_name . ' with ID: ' . $index );
					return $index; // Return the index as package ID
				}
			}
			$this->aistma_log_manager->log( 'warning', 'Package not found: ' . $package_name . ' in available packages: ' . implode( ', ', array_column( $data, 'name' ) ) );
		} else {
			$this->aistma_log_manager->log( 'error', 'Invalid packages data from API Gateway: ' . $body );
		}

		return false;
	}

	/**
	 * Get package enhancement limit by package ID from API Gateway.
	 *
	 * @param int $package_id The package ID.
	 * @return int Enhancement limit (0 = unlimited).
	 */
	private function get_package_enhancement_limit( $package_id ) {
		$gateway_url = aistma_get_api_url();
		if ( empty( $gateway_url ) ) {
			$this->aistma_log_manager->log( 'error', 'API Gateway URL not configured for enhancement limit lookup' );
			return 0;
		}

		$api_url = trailingslashit( $gateway_url ) . 'wp-json/exaig/v1/packages-summary';
		
		$this->aistma_log_manager->log( 'info', 'Looking up enhancement limit for package ID: ' . $package_id );
		
		$response = wp_remote_get( $api_url, [
			'timeout' => 10,
			'headers' => [
				'User-Agent' => 'AI-Story-Maker/1.0'
			]
		] );

		if ( is_wp_error( $response ) ) {
			$this->aistma_log_manager->log( 'error', 'Error fetching packages for enhancement limit: ' . $response->get_error_message() );
			return 0;
		}

		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );

		if ( isset( $data[ $package_id ]['enhancements_per_post'] ) ) {
			$limit = (int) $data[ $package_id ]['enhancements_per_post'];
			$this->aistma_log_manager->log( 'info', 'Found enhancement limit for package ID ' . $package_id . ': ' . $limit );
			return $limit;
		}

		$this->aistma_log_manager->log( 'warning', 'No enhancement limit found for package ID: ' . $package_id . ', defaulting to 1' );
		return 1;
	}

	/**
	 * Generate a story for a specific user with a given prompt.
	 *
	 * @param int $user_id   The user ID to generate the story for.
	 * @param int $prompt_id The prompt ID to use for generation.
	 * @return int|false The post ID if successful, false otherwise.
	 */
	public static function generate_ai_story_for_user( $user_id, $prompt_id ) {
		try {
			$instance = new self();
			$user_id = absint( $user_id );

			if ( ! $user_id || ! $prompt_id ) {
				return false;
			}

			// Support both numeric post IDs and string wizard prompt IDs
			$is_numeric_id = is_numeric( $prompt_id );
			$prompt_data = null;

			if ( $is_numeric_id ) {
				// Load from WordPress post
				$prompt_id = absint( $prompt_id );
				if ( ! $prompt_id ) {
					return false;
				}

				$prompt_post = get_post( $prompt_id );
				if ( ! $prompt_post || 'aistma_prompt' !== $prompt_post->post_type ) {
					return false;
				}

				// Get prompt meta
				$prompt_text = get_post_meta( $prompt_id, '_aistma_prompt_text', true );
				if ( ! $prompt_text ) {
					return false;
				}

				$prompt_data = array(
					'text'     => $prompt_text,
					'category' => get_post_meta( $prompt_id, '_aistma_category', true ),
				);
			} else {
				// Load from wizard prompts in settings
				$raw_json = get_option( 'aistma_prompts', '{}' );
				$settings = json_decode( $raw_json, true );
				$prompts = isset( $settings['prompts'] ) ? $settings['prompts'] : array();

				foreach ( $prompts as $p ) {
					if ( isset( $p['prompt_id'] ) && $p['prompt_id'] === $prompt_id ) {
						$prompt_data = array(
							'text'     => isset( $p['text'] ) ? $p['text'] : '',
							'category' => isset( $p['category'] ) ? $p['category'] : '',
						);
						break;
					}
				}

				if ( ! $prompt_data || empty( $prompt_data['text'] ) ) {
					return false;
				}

				// Sanitize the string ID
				$prompt_id = sanitize_key( $prompt_id );
			}

			// Generate the story
			// Temporarily set current user to the target user for the generation
			$original_user = get_current_user_id();
			wp_set_current_user( $user_id );

			try {
				$instance->generate_ai_story(
					$prompt_id,
					array(
						'text'       => $prompt_data['text'],
						'category'   => $prompt_data['category'],
						'post_type'  => 'post',
					),
					$instance->default_settings,
					$instance->api_key,
					''
				);

				// Get the last created post (most recent by the target user)
				$posts = get_posts( array(
					'author' => $user_id,
					'post_type' => 'post',
					'numberposts' => 1,
					'meta_query' => array(
						array(
							'key' => '_aistma_prompt_id',
							'value' => $prompt_id,
						),
					),
				) );

				$post_id = $posts ? $posts[0]->ID : false;

				return $post_id;
			} finally {
				// Restore original user
				wp_set_current_user( $original_user );
			}
		} catch ( \Throwable $e ) {
			return false;
		}
	}


}
