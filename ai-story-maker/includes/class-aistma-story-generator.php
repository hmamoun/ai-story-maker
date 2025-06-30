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
		$lock_key = 'aistma_generating_lock';
		delete_transient( $lock_key );
		if ( ! $force && get_transient( $lock_key ) ) {
			$instance = new self();
			$instance->aistma_log_manager->log( 'info', 'Story generation skipped due to active lock.' );
			return;
		}

		set_transient( $lock_key, true, 10 * MINUTE_IN_SECONDS );
		$instance = new self();
		try {
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
			$instance->aistma_log_manager->log( 'info', 'Rescheduled story generation at: ' . gmdate( 'Y-m-d H:i:s', $next_schedule ) );
		}
	}

	/**
	 * Generate AI Story using OpenAI API.
	 *
	 * @return void
	 */
	public function generate_ai_stories() {
		$results = array(
			'errors'    => array(),
			'successes' => array(),
		);
		// Check if the OpenAI API key is set and is valid.
		$this->api_key = get_option( 'aistma_openai_api_key' );
		if ( ! $this->api_key ) {
			$error = __( 'OpenAI API Key is missing.', 'ai-story-maker' );
			$this->aistma_log_manager::log( 'error', $error );
			$results['errors'][] = $error;
			throw new \RuntimeException( $error );

		}

		$raw_settings = get_option( 'aistma_prompts', '' );
		$settings     = json_decode( $raw_settings, true );

		// Check if the settings are valid.
		if ( JSON_ERROR_NONE !== json_last_error() || empty( $settings['prompts'] ) ) {
			$error = __( 'Invalid JSON format or no prompts found.', 'ai-story-maker' );
			$this->aistma_log_manager::log( 'error', $error );
			$results['errors'][] = $error;
			throw new \RuntimeException( $error );
		}

		$this->default_settings = isset( $settings['default_settings'] ) ? $settings['default_settings'] : array();

		// Set default values for the settings.
		$admin_prompt_settings = __( 'The response must strictly follow this json structure: { "title": "Article Title", "content": "Full article content...", "excerpt": "A short summary of the article...", "references": [ {"title": "Source 1", "link": "https://yourdomain.com/source1"}, {"title": "Source 2", "link": "https://yourdomain.com/source2"} ] } return the real https tested domain for your references, not example.com', 'ai-story-maker' );

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

			// Generate the AI story immediately if needed.
			try {
				$this->generate_ai_story( $prompt['prompt_id'], $prompt, $this->default_settings, $recent_posts, $admin_prompt_settings, $this->api_key );
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
			$next_schedule = gmdate( 'Y-m-d H:i:s', time() + $n * DAY_IN_SECONDS );
			wp_schedule_single_event( time() + $n * DAY_IN_SECONDS, 'aistma_generate_story_event' );

			/* translators: %s: The next scheduled date and time in Y-m-d H:i:s format */
			$error_msg = sprintf( __( 'Set next schedule to %s', 'ai-story-maker' ), $next_schedule );
			$this->aistma_log_manager::log( 'info', $error_msg );
		} else {
			$this->aistma_log_manager::log( 'info', __( 'Schedule for next story is unset', 'ai-story-maker' ) );
			wp_clear_scheduled_hook( 'aistma_generate_story_event' );
		}
	}

/**
 * Get master instructions from master website
 */
	private function aistma_get_master_instructions(): string{
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
					$this->aistma_log_manager->log( 'error', 'Error fetching dynamic instructions: ' . $api_response->get_error_message() );
					$aistma_master_instructions = '';
				}
			} catch ( Exception $e ) {
				// Silent fail; fallback will be handled below.
				$this->aistma_log_manager->log( 'error', 'Error fetching master instructions: ' . $e->getMessage() );
				$aistma_master_instructions = '';
			}
		}

		// Fallback if API call failed or returned empty.
		if ( empty( $aistma_master_instructions ) ) {
			$aistma_master_instructions = 'Write a fact-based, original article based on real-world information. Organize the article clearly with a proper beginning, middle, and conclusion.';
		}
		// Append recent posts titles.
		$aistma_master_instructions .= "\n" . __( 'Exclude references to the following recent posts:', 'ai-story-maker' );
		foreach ( $recent_posts as $post ) {
			$aistma_master_instructions .= "\n" . __( 'Title: ', 'ai-story-maker' ) . $post['title'];
		}

	}

	/**
	 * Generate AI Story using OpenAI API.
	 *
	 * @param  string $prompt_id             The prompt ID.
	 * @param  array  $prompt                The prompt data.
	 * @param  array  $default_settings      Default settings for generation.
	 * @param  array  $recent_posts          Recent posts to avoid duplication.
	 * @param  string $admin_prompt_settings Admin prompt settings.
	 * @param  string $api_key               OpenAI API key.
	 * @return void
	 */
	public function generate_ai_story( $prompt_id, $prompt, $default_settings, $recent_posts, $admin_prompt_settings, $api_key ) {
		$merged_settings        = array_merge( $default_settings, $prompt );
		$default_system_content = isset( $merged_settings['system_content'] )
		? $merged_settings['system_content'] : '';


		// Assign final system content.
		$merged_settings['system_content'] = $aistma_master_instructions . "\n" . $admin_prompt_settings;

		$the_prompt = $prompt['text'];
		if ( $prompt['photos'] > 0 ) {
			$the_prompt .= "\n" . __( 'Include at least ', 'ai-story-maker' ) . $prompt['photos'] . __( ' placeholders for images in the article. insert a placeholder in the following format {img_unsplash:keyword1,keyword2,keyword3} using the most relevant keywords for fetching related images from Unsplash', 'ai-story-maker' );
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
								'content' => $merged_settings['system_content'] ?? '',
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
			delete_transient( 'aistma_generating_lock' );
			wp_send_json_error( array( 'errors' => array( $error_msg ) ) );
		}

		// Check if response is valid.
		if ( is_wp_error( $response ) ) {
			$error = $response->get_error_message();
			$this->aistma_log_manager->log( 'error', $error );
			delete_transient( 'aistma_generating_lock' );
			wp_send_json_error( array( 'errors' => array( $error ) ) );
		}

		// Check if response is empty.
		$response_body = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( ! isset( $response_body['choices'][0]['message']['content'] ) ) {
			$error = __( 'Invalid response from OpenAI API.', 'ai-story-maker' );
			$this->aistma_log_manager->log( 'error', $error );
			delete_transient( 'aistma_generating_lock' );
			wp_send_json_error( array( 'errors' => array( $error ) ) );
		}

		$parsed_content = json_decode( $response_body['choices'][0]['message']['content'], true );
		if ( ! isset( $parsed_content['title'], $parsed_content['content'] ) ) {
			$error = __( 'Invalid content structure, try to simplify your prompts', 'ai-story-maker' );
			$this->aistma_log_manager->log( 'error', $error );
			delete_transient( 'aistma_generating_lock' );
			wp_send_json_error( array( 'errors' => array( $error ) ) );
		}

		$total_tokens = isset( $response_body['usage']['total_tokens'] ) ? (int) $response_body['usage']['total_tokens'] : 0;
		$request_id   = isset( $response_body['id'] ) ? sanitize_text_field( $response_body['id'] ) : uniqid( 'ai_news_' );
		$title        = isset( $parsed_content['title'] ) ? sanitize_text_field( $parsed_content['title'] ) : __( 'Untitled Article', 'ai-story-maker' );
		$content      = isset( $parsed_content['content'] ) ? wp_kses_post( $parsed_content['content'] ) : __( 'Content not available.', 'ai-story-maker' );
		$content      = $this->replace_image_placeholders( $content );
		$category     = isset( $prompt['category'] ) ? sanitize_text_field( $prompt['category'] ) : __( 'News', 'ai-story-maker' );

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

		// Determine auto publish post variable.
		$auto_publish = isset( $prompt['auto_publish'] ) ? (bool) $prompt['auto_publish'] : false;
		$post_status  = $auto_publish ? 'publish' : 'draft';

		$post_id = wp_insert_post(
			array(
				'post_title'    => sanitize_text_field( $parsed_content['title'] ?? 'Untitled AI Post' ),
				'post_content'  => $content,
				'post_author'   => 1,
				'post_category' => array( get_cat_ID( $category ) ),
				'post_excerpt'  => $parsed_content['excerpt'] ?? 'No excerpt available.',
				'post_status'   => $post_status,
			)
		);

		// Check for errors.
		if ( is_wp_error( $post_id ) ) {
			$error = $post_id->get_error_message();
			$this->aistma_log_manager::log( 'error', $error );
			wp_send_json_error( array( 'errors' => array( $error ) ) );
		}

		if ( $post_id ) {
			update_post_meta( $post_id, 'ai_story_maker_sources', isset( $parsed_content['references'] ) && is_array( $parsed_content['references'] ) ? wp_json_encode( $parsed_content['references'] ) : wp_json_encode( array() ) );
			update_post_meta( $post_id, 'ai_story_maker_total_tokens', $total_tokens ?? 'N/A' );
			update_post_meta( $post_id, 'ai_story_maker_request_id', $request_id ?? 'N/A' );
			$this->aistma_log_manager->log( 'success', 'AI-generated news article created: ' . get_permalink( $post_id ), $request_id );
		}
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
	 * @return string The article content with image placeholders replaced by Unsplash images.
	 */
	public function replace_image_placeholders( $article_content ) {
		$self = $this; // Assign $this to $self.
		return preg_replace_callback(
			'/\{img_unsplash:([a-zA-Z0-9,_ ]+)\}/',
			function ( $matches ) use ( $self ) {
				$keywords = explode( ',', $matches[1] );
				$image    = $self->fetch_unsplash_image( $keywords );
				return $image ? $image : '';
			},
			$article_content
		);
	}

	/**
	 * Fetch an image from Unsplash based on the provided keywords.
	 *
	 * @param  array $keywords The keywords to search for.
	 * @return string The HTML markup for the image or an empty string if no image is found.
	 */
	public function fetch_unsplash_image( $keywords ) {
		$api_key = get_option( 'aistma_unsplash_api_key' );

		$query    = implode( ',', $keywords );
		$url      = 'https://api.unsplash.com/search/photos?query=' . rawurlencode( $query ) . '&client_id=' . $api_key . '&per_page=30&orientation=landscape&quantity=100';
		$response = wp_remote_get( $url );

		if ( is_wp_error( $response ) ) {
			$this->aistma_log_manager::log( 'error', 'Error fetching Unsplash image: ' . $response->get_error_message() );
			return '';
		}
		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );
		if ( empty( $data['results'] ) ) {
			$this->aistma_log_manager::log( 'error', $data['errors'][0] );
			return '';
		}
		$image_index = array_rand( $data['results'] );
		if ( ! empty( $data['results'][ $image_index ]['urls']['small'] ) ) {
			$url     = $data['results'][ $image_index ]['urls']['small'];
			$credits = $data['results'][ $image_index ]['user']['name'] . ' by unsplash.com';
			// As required by unsplash.
         // phpcs:ignore PluginCheck.CodeAnalysis.ImageFunctions.NonEnqueuedImage
			$ret = '<figure><img src="' . esc_url( $url ) . '" alt="' . esc_attr( implode( ' ', $keywords ) ) . '" /><figcaption>' . esc_html( $credits ) . '</figcaption></figure>';

			return $ret;
		}

		return ''; // Return empty if no images found.
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
				$this->aistma_log_manager->log( 'info', 'Scheduled next AI story generation at: ' . gmdate( 'Y-m-d H:i:s', $run_at ) );
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
			$this->aistma_log_manager->log( 'info', 'Rescheduled cron event: ' . gmdate( 'Y-m-d H:i:s', $run_at ) );
		}
	}
}
