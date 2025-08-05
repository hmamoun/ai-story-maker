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
	 * @return void
	 */
	public static function generate_ai_stories_with_lock( $force = false ) {
		$instance = new self();

		$lock_key = 'aistma_generating_lock';
		if ( ! $force && get_transient( $lock_key ) ) {
			$instance->aistma_log_manager->log( 'info', 'Story generation skipped due to active lock.' );
			return;
		}

		// Check subscription status and API key availability before generating stories
		try {
			$subscription_status = $instance->aistma_get_subscription_status();
			$has_valid_subscription = $subscription_status['valid'];
			
			// Check if we have a valid subscription
			if ( $has_valid_subscription ) {
				$instance->aistma_log_manager::log( 'info', 'Subscription validated for domain: ' . ( $subscription_status['domain'] ?? 'unknown' ) . ' - Package: ' . ( $subscription_status['package_name'] ?? 'unknown' ) );
			} else {
				// No valid subscription, check if we have a valid OpenAI API key as fallback
				$openai_api_key = get_option( 'aistma_openai_api_key' );
				if ( empty( $openai_api_key ) ) {
					$error_message = isset( $subscription_status['error'] ) 
						? 'Subscription check failed: ' . $subscription_status['error'] . '. Also, no OpenAI API key found.'
						: 'No valid subscription found and no OpenAI API key configured. Please either purchase a subscription or configure an OpenAI API key to generate stories.';
					
					$instance->aistma_log_manager::log( 'error', $error_message );
					throw new \RuntimeException( $error_message );
				} else {
					$instance->aistma_log_manager::log( 'info', 'No valid subscription found, but OpenAI API key is available. Will use direct OpenAI API calls.' );
				}
			}
		} catch ( \RuntimeException $e ) {
			$error = $e->getMessage();
			$instance->aistma_log_manager::log( 'error', $error );
			throw new \RuntimeException( esc_html( $error ) );
		}

		set_transient( $lock_key, true, 10 * MINUTE_IN_SECONDS );
		try {
			// Pass the instance with cached subscription status to generate_ai_stories
			$instance->generate_ai_stories();
			//$instance->aistma_log_manager->log( 'info', 'Stories successfully generated.' );
		} catch ( \Throwable $e ) {
			$instance->aistma_log_manager->log( 'error', 'Error generating stories: ' . $e->getMessage() );
			delete_transient( $lock_key );
			wp_send_json_error( array( 'message' => 'Error generating stories: ' . $e->getMessage() ) );
		} finally {
			// Always delete the lock, even if an error occurs.
			delete_transient( $lock_key );
		}
		// Always schedule the next run after execution.
		$n = absint( get_option( 'aistma_generate_story_cron' ) );
		if ( 0 !== $n ) {
			$next_schedule = time() + $n * DAY_IN_SECONDS;
			wp_schedule_single_event( $next_schedule, 'aistma_generate_story_event' );
			//$instance->aistma_log_manager->log( 'info', 'Rescheduled story generation at: ' . $instance->format_date_for_display( $next_schedule ) );
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
	public function generate_ai_story( $prompt_id, $prompt, $default_settings, $recent_posts,  $api_key ) {
		$merged_settings        = array_merge( $default_settings, $prompt );
		$aistma_master_instructions = $this->aistma_get_master_instructions( $recent_posts );	

		// Assign final system content.
		$merged_settings['system_content'] .= $aistma_master_instructions ;

		$the_prompt = $prompt['text'];


		// Check if we have a valid subscription
		$subscription_info = $this->get_subscription_info();
		
		if ( $subscription_info['valid'] ) {
			// Use Master Server API
			
			$this->generate_story_via_master_api( $prompt_id, $prompt, $merged_settings, $recent_posts, $the_prompt, $subscription_info );
		} else {
			// Fallback to direct OpenAI API call
			if ( $prompt['photos'] > 0 ) {
				$the_prompt .= "\n" . __( 'Include at least ', 'ai-story-maker' ) . $prompt['photos'] . __( ' placeholders for images in the article. insert a placeholder in the following format {img_unsplash:keyword1,keyword2,keyword3} using the most relevant keywords for fetching related images from Unsplash', 'ai-story-maker' );
			}
			$this->generate_story_via_openai_api( $prompt_id, $prompt, $merged_settings, $recent_posts,  $api_key, $the_prompt );
		}
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
		
		// Check subscription status first
		$subscription_info = $this->get_subscription_info();
		$has_valid_subscription = $subscription_info['valid'];
		
		// Only check OpenAI API key if no valid subscription 
		if ( ! $has_valid_subscription ) {
			$this->api_key = get_option( 'aistma_openai_api_key' );
					if ( ! $this->api_key ) {
			$error = __( 'OpenAI API Key is missing. Required for direct OpenAI calls when no subscription is active.', 'ai-story-maker' );
			$this->aistma_log_manager::log( 'error', $error );
			$results['errors'][] = $error;
			throw new \RuntimeException( esc_html( $error ) );
			}
		} else {
			// For subscription users, we'll use master API, so OpenAI key is not required
			$this->api_key = null;
			$this->aistma_log_manager::log( 'info', 'Valid subscription detected, will use Master API for story generation' );
		}

		$raw_settings = get_option( 'aistma_prompts', '' );
		$settings     = json_decode( $raw_settings, true );

		// Check if the settings are valid.
		if ( JSON_ERROR_NONE !== json_last_error() || empty( $settings['prompts'] ) ) {
			$error = __( 'Invalid JSON format or no prompts found.', 'ai-story-maker' );
			$this->aistma_log_manager::log( 'error', $error );
			$results['errors'][] = $error;
			throw new \RuntimeException( esc_html( $error ) );
		}
		$this->default_settings = isset( $settings['default_settings'] ) ? $settings['default_settings'] : array();
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
				$recent_posts = $this->aistma_get_recent_posts( 20, $prompt['category'] );

		// Log recent posts for debugging
		$this->aistma_log_manager::log( 'info', sprintf(
			'Recent posts for category "%s": %s',
			$prompt['category'],
			json_encode( array_column( $recent_posts, 'title' ) )
		) );

		// Generate the AI story immediately if needed.
		try {
				// Generate the story
				$this->generate_ai_story( $prompt['prompt_id'], $prompt, $this->default_settings, $recent_posts,  $this->api_key );
				
				$results['successes'][] = __( 'AI story generated successfully.', 'ai-story-maker' );
			} catch ( \Exception $e ) {
				$error = __( 'Error generating AI story: ', 'ai-story-maker' ) . $e->getMessage();
				$this->aistma_log_manager::log( 'error', $error );
				$results['errors'][] = $error;
			}
		}

		// Schedule after generate.
		$n = absint( get_option( 'aistma_generate_story_cron' ) );
		if ( 0 !== $n ) {
			// Cancel the current schedule.
			wp_clear_scheduled_hook( 'aistma_generate_story_event' );
			// Schedule the next event.
			$next_schedule_timestamp = time() + $n * DAY_IN_SECONDS;
			$next_schedule_display = $this->format_date_for_display( $next_schedule_timestamp );
			wp_schedule_single_event( $next_schedule_timestamp, 'aistma_generate_story_event' );

			/* translators: %s: The next scheduled date and time in Y-m-d H:i:s format */
			$error_msg = sprintf( __( 'Set next schedule to %s', 'ai-story-maker' ), $next_schedule_display );
			$this->aistma_log_manager::log( 'info', $error_msg );
		} else {
			$this->aistma_log_manager::log( 'info', __( 'Schedule for next story is unset', 'ai-story-maker' ) );
			wp_clear_scheduled_hook( 'aistma_generate_story_event' );
		}
	}

	/**
	 * Generate story via Master Server API.
	 *
	 * @param  string $prompt_id        The prompt ID.
	 * @param  array  $prompt           The prompt data.
	 * @param  array  $merged_settings  Merged settings.
	 * @param  array  $recent_posts     Recent posts to avoid duplication.
	 * @param  string $the_prompt       The prompt text.
	 * @param  array  $subscription_info Subscription information.
	 * @return void
	 */
	private function generate_story_via_master_api( $prompt_id, $prompt, $merged_settings, $recent_posts, $the_prompt, $subscription_info ) {
		$master_url = defined( 'AISTMA_MASTER_URL' ) ? AISTMA_MASTER_URL : '';
		
		if ( empty( $master_url ) ) {
			$this->aistma_log_manager::log( 'error', message: 'AISTMA_MASTER_URL not defined, falling back to direct OpenAI call' );
			// Fallback to direct OpenAI call
			$this->generate_story_via_openai_api( $prompt_id, $prompt, $merged_settings, $recent_posts, $this->api_key, $the_prompt );
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

		$response = wp_remote_post( $api_url, array(
			'timeout' => 60,
			'headers' => array(
				'Content-Type' => 'application/json',
				'User-Agent' => 'AI-Story-Maker/1.0',
			),
			'body' => wp_json_encode( $request_data ),
		) );

		if ( is_wp_error( $response ) ) {
			$error_message = $response->get_error_message();
			$this->aistma_log_manager::log( 'error', 'Master API error: ' . $error_message . ', falling back to direct OpenAI call' );
			// Fallback to direct OpenAI call
			$this->generate_story_via_openai_api( $prompt_id, $prompt, $merged_settings, $recent_posts, $this->api_key, $the_prompt );
			return;
		}

		$response_code = wp_remote_retrieve_response_code( $response );
		$response_body = wp_remote_retrieve_body( $response );
		$data = json_decode( $response_body, true );

		if ( $response_code !== 200 ) {
			$this->aistma_log_manager::log( 'error', 'Master API returned HTTP ' . $response_code . ', falling back to direct OpenAI call' );
			// Fallback to direct OpenAI call
			$this->generate_story_via_openai_api( $prompt_id, $prompt, $merged_settings, $recent_posts, $this->api_key, $the_prompt );
			return;
		}

		if ( json_last_error() !== JSON_ERROR_NONE ) {
			$this->aistma_log_manager::log( 'error', 'Invalid JSON response from Master API, falling back to direct OpenAI call' );
			// Fallback to direct OpenAI call
			$this->generate_story_via_openai_api( $prompt_id, $prompt, $merged_settings, $recent_posts, $this->api_key, $the_prompt );
			return;
		}

		if ( ! isset( $data['success'] ) || ! $data['success'] ) {
			$error_msg = isset( $data['error'] ) ? $data['error'] : 'Unknown error from Master API';
			$this->aistma_log_manager::log( 'error', 'Master API error: ' . $error_msg . ', falling back to direct OpenAI call' );
			// Fallback to direct OpenAI call
			$this->generate_story_via_openai_api( $prompt_id, $prompt, $merged_settings, $recent_posts, $this->api_key, $the_prompt );
			return;
		}

		// Success! Process the response from Master API
		$this->process_master_api_response( $data, $prompt_id, $prompt, $merged_settings );
	}

	/**
	 * Generate story via direct OpenAI API (fallback method).
	 *
	 * @param  string $prompt_id             The prompt ID.
	 * @param  array  $prompt                The prompt data.
	 * @param  array  $merged_settings       Merged settings.
	 * @param  array  $recent_posts          Recent posts to avoid duplication.

	 * @param  string $api_key               OpenAI API key.
	 * @param  string $the_prompt            The prompt text.
	 * @return void
	 */
	private function generate_story_via_openai_api( $prompt_id, $prompt, $merged_settings, $recent_posts,  $api_key, $the_prompt ) {
		// Check if the OpenAI API key is set and is valid.
		if ( empty( $api_key ) ) {
			$api_key = get_option( 'aistma_openai_api_key' );
		}
		
		if ( ! $api_key ) {
			$error = __( 'OpenAI API Key is missing. Required for direct OpenAI calls without subscription', 'ai-story-maker' );
			$this->aistma_log_manager::log( 'error', $error );
			throw new \RuntimeException( esc_html( $error ) );
		}

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
								'content' => $merged_settings['system_content'] ?? '' ,
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
			$this->aistma_log_manager::log( 'error', $error_msg );
			delete_transient( 'aistma_generating_lock' );
			wp_send_json_error( array( 'errors' => array( $error_msg ) ) );
		}

		// Check if response is valid.
		if ( is_wp_error( $response ) ) {
			$error = $response->get_error_message();
			$this->aistma_log_manager::log( 'error', $error );
			delete_transient( 'aistma_generating_lock' );
			wp_send_json_error( array( 'errors' => array( $error ) ) );
		}

		// Check if response is empty.
		$response_body = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( ! isset( $response_body['choices'][0]['message']['content'] ) ) {
			$error = __( 'Invalid response from OpenAI API.', 'ai-story-maker' );
			$this->aistma_log_manager::log( 'error', $error );
			delete_transient( 'aistma_generating_lock' );
			wp_send_json_error( array( 'errors' => array( $error ) ) );
		}

		$parsed_content = json_decode( $response_body['choices'][0]['message']['content'], true );

		if ( ! isset( $parsed_content['title'], $parsed_content['content'] ) ) {
			$error = __( 'Invalid content structure, try to simplify your prompts', 'ai-story-maker' );
			$this->aistma_log_manager::log( 'error', $error );
			delete_transient( 'aistma_generating_lock' );
			wp_send_json_error( array( 'errors' => array( $error ) ) );
		}

		// Process the OpenAI response
		$this->process_openai_response( $response_body, $parsed_content, $prompt_id, $prompt, $merged_settings );
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
			$this->aistma_log_manager::log( 'error', $error );
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
			$content .= '<div class="ai-story-model">' . __( 'generated by:', 'ai-story-maker' ) . ' ' . esc_html( $merged_settings['model'] ?? 'gpt-4-turbo' ) . '</div>';
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

		// Create the post.
		$post_data = array(
			'post_title'   => $title,
			'post_content' => $content,
			'post_excerpt' => $excerpt,
			'post_status'  => isset( $prompt['auto_publish'] ) && 1 === $prompt['auto_publish'] ? 'publish' : 'draft',
			'post_author'  => $post_author,
			'post_category' => array( $category_id ),
		);

		$post_id = wp_insert_post( $post_data );

		if ( is_wp_error( $post_id ) ) {
			$error = __( 'Error creating post: ', 'ai-story-maker' ) . $post_id->get_error_message();
			$this->aistma_log_manager::log( 'error', $error );
			throw new \RuntimeException( esc_html( $error ) );
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
			$this->aistma_log_manager->log( 'success', 'AI-generated news article created via Master API: ' . get_permalink( $post_id ), $request_id );
		}

		// Log usage from Master API response
		if ( isset( $data['usage']['total_tokens'] ) ) {
			$this->aistma_log_manager->log( 'info', 'Story generated via Master API. Tokens used: ' . $data['usage']['total_tokens'] );
		}

		$this->aistma_log_manager->log( 'info', 'Story generated successfully via Master API. Post ID: ' . $post_id );
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
	private function process_openai_response( $response_body, $parsed_content, $prompt_id, $prompt, $merged_settings ) {
		$total_tokens = isset( $response_body['usage']['total_tokens'] ) ? (int) $response_body['usage']['total_tokens'] : 0;
		$request_id   = isset( $response_body['id'] ) ? sanitize_text_field( $response_body['id'] ) : uniqid( 'ai_news_' );
		$title        = isset( $parsed_content['title'] ) ? sanitize_text_field( $parsed_content['title'] ) : __( 'Untitled Article', 'ai-story-maker' );
		$content      = isset( $parsed_content['content'] ) ? wp_kses_post( $parsed_content['content'] ) : __( 'Content not available.', 'ai-story-maker' );
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
			$content .= '<div class="ai-story-model">' . __( 'generated by:', 'ai-story-maker' ) . ' ' . esc_html( $merged_settings['model'] ) . '</div>';
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

		// Create the post.
		$post_data = array(
			'post_title'   => $title,
			'post_content' => $content,
			'post_excerpt' => $excerpt,
			'post_status'  => isset( $prompt['auto_publish'] ) && 1 === $prompt['auto_publish'] ? 'publish' : 'draft',
			'post_author'  => $post_author,
			'post_category' => array( $category_id ),
		);

		$post_id = wp_insert_post( $post_data );

		if ( is_wp_error( $post_id ) ) {
			$error = __( 'Error creating post: ', 'ai-story-maker' ) . $post_id->get_error_message();
			$this->aistma_log_manager::log( 'error', $error );
			throw new \RuntimeException( esc_html( $error ) );
		}

		// Process image placeholders and set featured image
		if ( $post_id ) {
			$content = $this->replace_image_placeholders( $content, $post_id );
			
			// Update the post with processed content
			wp_update_post( postarr: array(
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
			$this->aistma_log_manager::log( 'success', 'AI-generated news article created via OpenAI API: ' . get_permalink( $post_id ), $request_id );
		}

		$this->aistma_log_manager::log( 'info', 'Story generated successfully via OpenAI API. Post ID: ' . $post_id . ', Tokens used: ' . $total_tokens );
	}

	/**
	 * Get master instructions for AI story generation.
	 *
	 * @param array $recent_posts Array of recent posts to exclude from generation.
	 * @return string Master instructions for AI story generation.
	 */
	private function aistma_get_master_instructions( $recent_posts = array() )	{
		// Fetch dynamic system content from Exedotcom API Gateway.
		$aistma_master_instructions = get_transient( 'aistma_exaig_cached_master_instructions' );
		if ( false === $aistma_master_instructions ) {
			// No cache, fetch from the API.
			try {
				$api_response = wp_remote_get(
					aistma_get_instructions_url(),
					array(
						'timeout' => 10,
						'headers' => array(
							'X-Caller-Url' => home_url(),
							'X-Caller-IP'  => isset( $_SERVER['SERVER_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['SERVER_ADDR'] ) ) : '',
						),
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
					$this->aistma_log_manager::log( 'error', 'Error fetching dynamic instructions: ' . $api_response->get_error_message() );
					$aistma_master_instructions = '';
				}
			} catch ( Exception $e ) {
				// Silent fail; fallback will be handled below.
				$this->aistma_log_manager::log( 'error', 'Error fetching master instructions: ' . $e->getMessage() );
				$aistma_master_instructions = '';
			}
		}

		// Fallback if API call failed or returned empty.
		if ( empty( $aistma_master_instructions ) ) {
			$aistma_master_instructions = 'Write a fact-based, original article based on real-world information. Organize the article clearly with a proper beginning, middle, and conclusion.';
		}
		
		// Append recent posts titles if provided and not empty.
		if ( ! empty( $recent_posts ) && is_array( $recent_posts ) ) {
			$aistma_master_instructions .= "\n" . __( 'Exclude references to the following recent posts:', 'ai-story-maker' );
			foreach ( $recent_posts as $post ) {
				if ( isset( $post['title'] ) && ! empty( $post['title'] ) ) {
					$aistma_master_instructions .= "\n" . __( 'Title: ', 'ai-story-maker' ) . $post['title'];
				}
			}
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
	 * Replace image placeholders in the article content with Unsplash images.
	 *
	 * @param  string $article_content The article content with image placeholders.
	 * @param  int    $post_id         The post ID to set featured image for.
	 * @return string The article content with image placeholders replaced by Unsplash images.
	 */
	public function replace_image_placeholders( $article_content, $post_id = 0 ) {
		$self = $this; // Assign $this to $self.
		$first_image_url = null;
		$image_count = 0;
		
		$processed_content = preg_replace_callback(
			'/\{img_unsplash:([a-zA-Z0-9,_ ]+)\}/',
			function ( $matches ) use ( $self, &$first_image_url, &$image_count ) {
				$keywords = explode( ',', $matches[1] );
				$image_data = $self->fetch_unsplash_image_data( $keywords );
				
				if ( $image_data ) {
					$image_count++;
					
					// Store the first image URL for featured image
					if ( $image_count === 1 && ! $first_image_url ) {
						$first_image_url = $image_data['url'];
					}
					
					return $image_data['html'];
				}
				
				return '';
			},
			$article_content
		);
		
		// Set the first image as featured image if we have a post ID
		if ( $post_id && $first_image_url ) {
			$this->set_featured_image_from_url( $post_id, $first_image_url );
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
			$this->aistma_log_manager::log( 'error', 'Unsplash API key not configured' );
			return false;
		}

		$query    = implode( ',', $keywords );
		$url      = 'https://api.unsplash.com/search/photos?query=' . rawurlencode( $query ) . '&client_id=' . $api_key . '&per_page=30&orientation=landscape&quantity=100';
		$response = wp_remote_get( $url );

		if ( is_wp_error( $response ) ) {
			$this->aistma_log_manager::log( 'error', 'Error fetching Unsplash image: ' . $response->get_error_message() );
			return false;
		}
		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );
		if ( empty( $data['results'] ) ) {
			$this->aistma_log_manager::log( 'error', 'No Unsplash images found for keywords: ' . $query );
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
	 * Download and set featured image from URL.
	 *
	 * @param  int    $post_id The post ID to set featured image for.
	 * @param  string $image_url The URL of the image to download.
	 * @return int|false The attachment ID on success, false on failure.
	 */
	private function set_featured_image_from_url( $post_id, $image_url ) {
		// Check if post exists
		if ( ! get_post( $post_id ) ) {
			$this->aistma_log_manager::log( 'error', 'Post not found for featured image: ' . $post_id );
			return false;
		}

		// Download the image
		$upload = media_sideload_image( $image_url, $post_id, '', 'id' );
		
		if ( is_wp_error( $upload ) ) {
			$this->aistma_log_manager::log( 'error', 'Failed to download featured image: ' . $upload->get_error_message() );
			return false;
		}

		// Set as featured image
		$result = set_post_thumbnail( $post_id, $upload );
		
		if ( $result ) {
			$this->aistma_log_manager::log( 'info', 'Featured image set successfully for post ' . $post_id );
		} else {
			$this->aistma_log_manager::log( 'error', 'Failed to set featured image for post ' . $post_id );
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
			$this->aistma_log_manager::log( 'error', 'Post not found for featured image: ' . $post_id );
			return false;
		}

		// Extract first image URL from content
		$image_url = $this->extract_first_image_url( $content );
		
		if ( ! $image_url ) {
			$this->aistma_log_manager::log( 'info', 'No image found in content for featured image on post ' . $post_id );
			return false;
		}

		// Set featured image from URL
		return $this->set_featured_image_from_url( $post_id, $image_url );
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
	 * Check if the schedule is set for the next event.
	 *
	 * @return void
	 */
	public function check_schedule() {
		$next_event = wp_next_scheduled( 'aistma_generate_story_event' );

		if ( ! $next_event ) {
			$n = absint( get_option( 'aistma_generate_story_cron' ) );
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

		$n = absint( get_option( 'aistma_generate_story_cron' ) );
		if ( 0 !== $n ) {
			$run_at = time() + $n * DAY_IN_SECONDS;
			wp_schedule_single_event( $run_at, 'aistma_generate_story_event' );
			$this->aistma_log_manager->log( 'info', 'Rescheduled cron event: ' . $this->format_date_for_display( $run_at ) );
		}
	}

	/**
	 * Check subscription status for the current domain.
	 *
	 * Similar to the JavaScript aistma_get_subscription_status() function.
	 * Makes an API call to the master server to verify subscription status.
	 *
	 * @param string $domain Optional domain to check. If not provided, uses current site domain.
	 * @return array Subscription status data or error information.
	 */
	public function aistma_get_subscription_status( $domain = '' ) {
		// Get current domain with port if it exists
		if ( empty( $domain ) ) {
			$domain = sanitize_text_field( wp_unslash( $_SERVER['HTTP_HOST'] ?? '' ) );

		}

		// Get master URL from WordPress constant
		$master_url = defined( 'AISTMA_MASTER_URL' ) ? AISTMA_MASTER_URL : '';
		
		if ( empty( $master_url ) ) {
			$this->aistma_log_manager::log( 'error', 'AISTMA_MASTER_URL not defined' );
			$this->subscription_status = array(
				'valid' => false,
				'error' => 'AISTMA_MASTER_URL not defined',
				'domain' => $domain,
			);
			return $this->subscription_status;
		}

		// Make API call to master server to check subscription status
		$api_url = trailingslashit( $master_url ) . 'wp-json/exaig/v1/verify-subscription?domain=' . urlencode( $domain );
		
		$response = wp_remote_get( $api_url, array(
			'timeout' => 30,
			'headers' => array(
				'User-Agent' => 'AI-Story-Maker/1.0',
			),
		) );

		if ( is_wp_error( $response ) ) {
			$error_message = $response->get_error_message();
			$this->aistma_log_manager::log( 'error', 'Error checking subscription status: ' . $error_message );
			$this->subscription_status = array(
				'valid' => false,
				'error' => 'Network error: ' . $error_message,
				'domain' => $domain,
			);
			return $this->subscription_status;
		}

		$response_code = wp_remote_retrieve_response_code( $response );
		$response_body = wp_remote_retrieve_body( $response );
		$data = json_decode( $response_body, true );

		if ( $response_code !== 200 ) {
			$this->aistma_log_manager::log( 'error', 'API error checking subscription status. Response code: ' . $response_code );
			$this->subscription_status = array(
				'valid' => false,
				'error' => 'API error: HTTP ' . $response_code,
				'domain' => $domain,
			);
			return $this->subscription_status;
		}

		if ( json_last_error() !== JSON_ERROR_NONE ) {
			$this->aistma_log_manager::log( 'error', 'Invalid JSON response from subscription API' );
			$this->subscription_status = array(
				'valid' => false,
				'error' => 'Invalid JSON response',
				'domain' => $domain,
			);
			return $this->subscription_status;
		}

		if ( isset( $data['valid'] ) && $data['valid'] ) {
			$this->aistma_log_manager::log( 'info', 'Subscription found for domain: ' . $domain . ' - Credits remaining: ' . ( $data['credits_remaining'] ?? 0 ) );
			$this->subscription_status = array(
				'valid' => true,
				'domain' => $data['domain'] ?? $domain,
				'credits_remaining' => intval( $data['credits_remaining'] ?? 0 ),
				'package_id' => $data['package_id'] ?? '',
				'package_name' => $data['package_name'] ?? '',
				'price' => floatval( $data['price'] ?? 0 ),
				'created_at' => $data['created_at'] ?? '',
			);
		} else {
			//$this->aistma_log_manager::log( 'info', 'No active subscription found for domain: ' . $domain );
			$this->subscription_status = array(
				'valid' => false,
				'message' => $data['message'] ?? 'No subscription found',
				'domain' => $domain,
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

}
