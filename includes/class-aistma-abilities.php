<?php
/**
 * AI Story Maker — WordPress 7.0 Abilities API
 *
 * Registers generate_story, enhance_content, and schedule_stories as named
 * Abilities so AI agents and other WP 7.0 AI workflows can orchestrate this
 * plugin's content-generation capabilities.
 *
 * @package AI_Story_Maker
 * @since   2.4.0
 */

namespace exedotcom\aistorymaker;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers and handles the plugin's WP 7.0 Abilities.
 */
class AISTMA_Abilities {

	/**
	 * Ability namespace prefix.
	 */
	const NAMESPACE = 'ai-story-maker';

	/**
	 * Hook into WordPress to register abilities after plugins_loaded.
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'register' ), 20 );
	}

	/**
	 * Register all plugin abilities with the WP 7.0 Abilities registry.
	 *
	 * Bails silently on WP < 7.0 so the plugin keeps working unchanged on
	 * older installs.
	 */
	public function register() {
		if ( ! function_exists( 'wp_register_ability' ) ) {
			return;
		}

		wp_register_ability(
			self::NAMESPACE . '/generate-story',
			array(
				'label'               => __( 'Generate Story', 'ai-story-maker' ),
				'description'         => __( 'Generate a new AI-powered story using the configured prompt settings. Requires an active plan with available credits.', 'ai-story-maker' ),
				'input_schema'        => array(
					'type'       => 'object',
					'properties' => array(
						'prompt_id' => array(
							'type'        => 'string',
							'description' => __( 'Prompt ID to use. Omit to run all active prompts.', 'ai-story-maker' ),
						),
						'force'     => array(
							'type'        => 'boolean',
							'description' => __( 'Override the concurrent-generation lock.', 'ai-story-maker' ),
							'default'     => false,
						),
					),
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'success'  => array( 'type' => 'boolean' ),
						'post_id'  => array( 'type' => array( 'integer', 'null' ) ),
						'post_url' => array( 'type' => array( 'string', 'null' ) ),
						'message'  => array( 'type' => 'string' ),
					),
				),
				'callback'            => array( $this, 'ability_generate_story' ),
				'permission_callback' => array( $this, 'require_edit_posts' ),
			)
		);

		wp_register_ability(
			self::NAMESPACE . '/enhance-content',
			array(
				'label'               => __( 'Enhance Content', 'ai-story-maker' ),
				'description'         => __( 'Improve or rewrite a block of existing post content using AI. Returns the enhanced HTML.', 'ai-story-maker' ),
				'input_schema'        => array(
					'type'       => 'object',
					'required'   => array( 'post_id', 'selected_text', 'instruction' ),
					'properties' => array(
						'post_id'       => array(
							'type'        => 'integer',
							'description' => __( 'ID of the post being edited.', 'ai-story-maker' ),
						),
						'selected_text' => array(
							'type'        => 'string',
							'description' => __( 'The text to enhance.', 'ai-story-maker' ),
						),
						'instruction'   => array(
							'type'        => 'string',
							'description' => __( 'What to do with the text (e.g. "make it more concise").', 'ai-story-maker' ),
						),
						'operation'     => array(
							'type'        => 'string',
							'enum'        => array( 'text_improve', 'image_insert', 'image_replace' ),
							'default'     => 'text_improve',
							'description' => __( 'Enhancement operation type.', 'ai-story-maker' ),
						),
					),
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'success'          => array( 'type' => 'boolean' ),
						'improved_content' => array( 'type' => array( 'string', 'null' ) ),
						'message'          => array( 'type' => 'string' ),
					),
				),
				'callback'            => array( $this, 'ability_enhance_content' ),
				'permission_callback' => array( $this, 'require_edit_posts' ),
			)
		);

		wp_register_ability(
			self::NAMESPACE . '/schedule-stories',
			array(
				'label'               => __( 'Schedule Stories', 'ai-story-maker' ),
				'description'         => __( 'Enable or disable the weekly automatic story generation for a user, and set which prompt to use.', 'ai-story-maker' ),
				'input_schema'        => array(
					'type'       => 'object',
					'required'   => array( 'enabled' ),
					'properties' => array(
						'enabled'   => array(
							'type'        => 'boolean',
							'description' => __( 'Whether to enable weekly auto-generation.', 'ai-story-maker' ),
						),
						'prompt_id' => array(
							'type'        => array( 'string', 'integer' ),
							'description' => __( 'Prompt ID to use for scheduled generation.', 'ai-story-maker' ),
						),
						'user_id'   => array(
							'type'        => 'integer',
							'description' => __( 'User to configure. Defaults to the current user. Requires manage_options to set another user.', 'ai-story-maker' ),
						),
					),
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'success'          => array( 'type' => 'boolean' ),
						'enabled'          => array( 'type' => 'boolean' ),
						'prompt_id'        => array( 'type' => array( 'string', 'integer', 'null' ) ),
						'next_generation'  => array( 'type' => array( 'string', 'null' ) ),
						'message'          => array( 'type' => 'string' ),
					),
				),
				'callback'            => array( $this, 'ability_schedule_stories' ),
				'permission_callback' => array( $this, 'require_edit_posts' ),
			)
		);
	}

	// -------------------------------------------------------------------------
	// Ability callbacks
	// -------------------------------------------------------------------------

	/**
	 * Ability: generate-story
	 *
	 * @param  array $params Validated input parameters.
	 * @return array
	 */
	public function ability_generate_story( array $params ) {
		$force     = ! empty( $params['force'] );
		$prompt_id = isset( $params['prompt_id'] ) ? sanitize_key( $params['prompt_id'] ) : '';

		if ( ! empty( $prompt_id ) ) {
			// Single-prompt generation for a specific ID.
			$post_id = AISTMA_Story_Generator::generate_ai_story_for_user(
				get_current_user_id(),
				$prompt_id
			);

			if ( $post_id ) {
				return array(
					'success'  => true,
					'post_id'  => $post_id,
					'post_url' => get_permalink( $post_id ) ?: null,
					'message'  => __( 'Story generated successfully.', 'ai-story-maker' ),
				);
			}

			return array(
				'success'  => false,
				'post_id'  => null,
				'post_url' => null,
				'message'  => __( 'Story generation failed. Check that the prompt ID is valid and your plan has available credits.', 'ai-story-maker' ),
			);
		}

		// Full batch: run all active prompts.
		$result = AISTMA_Story_Generator::generate_ai_stories_with_lock( $force );

		return array(
			'success'  => $result['success'],
			'post_id'  => null,
			'post_url' => null,
			'message'  => $result['message'],
		);
	}

	/**
	 * Ability: enhance-content
	 *
	 * Proxies to the same gateway endpoint used by the content-editor AJAX handler.
	 *
	 * @param  array $params Validated input parameters.
	 * @return array
	 */
	public function ability_enhance_content( array $params ) {
		$post_id       = absint( $params['post_id'] ?? 0 );
		$selected_text = sanitize_textarea_field( $params['selected_text'] ?? '' );
		$instruction   = sanitize_textarea_field( $params['instruction'] ?? '' );
		$operation     = sanitize_key( $params['operation'] ?? 'text_improve' );

		if ( ! $post_id || empty( $selected_text ) || empty( $instruction ) ) {
			return array(
				'success'          => false,
				'improved_content' => null,
				'message'          => __( 'post_id, selected_text, and instruction are required.', 'ai-story-maker' ),
			);
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return array(
				'success'          => false,
				'improved_content' => null,
				'message'          => __( 'You do not have permission to edit this post.', 'ai-story-maker' ),
			);
		}

		$allowed_ops = array( 'text_improve', 'image_insert', 'image_replace' );
		if ( ! in_array( $operation, $allowed_ops, true ) ) {
			$operation = 'text_improve';
		}

		$gateway_url = aistma_get_api_url( 'wp-json/exaig/v1/improve-content' );
		$domain      = sanitize_text_field( wp_unslash( $_SERVER['HTTP_HOST'] ?? '' ) );

		$payload = array(
			'domain'        => $domain,
			'post_id'       => $post_id,
			'selected_text' => $selected_text,
			'user_prompt'   => $instruction,
			'operation_type' => $operation,
		);

		$generator = new AISTMA_Story_Generator();
		$headers   = $this->get_gateway_headers( $generator );
		$headers['Content-Type'] = 'application/json';

		$response = wp_remote_post(
			$gateway_url,
			array(
				'timeout' => 30,
				'headers' => $headers,
				'body'    => wp_json_encode( $payload ),
			)
		);

		if ( is_wp_error( $response ) ) {
			return array(
				'success'          => false,
				'improved_content' => null,
				'message'          => $response->get_error_message(),
			);
		}

		$code = wp_remote_retrieve_response_code( $response );
		$data = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( 200 !== $code || empty( $data['success'] ) ) {
			$msg = $data['message'] ?? $data['error'] ?? sprintf( __( 'Gateway returned HTTP %d.', 'ai-story-maker' ), $code );
			return array(
				'success'          => false,
				'improved_content' => null,
				'message'          => $msg,
			);
		}

		return array(
			'success'          => true,
			'improved_content' => wp_kses_post( $data['content'] ?? '' ),
			'message'          => __( 'Content enhanced successfully.', 'ai-story-maker' ),
		);
	}

	/**
	 * Ability: schedule-stories
	 *
	 * @param  array $params Validated input parameters.
	 * @return array
	 */
	public function ability_schedule_stories( array $params ) {
		$enabled   = (bool) ( $params['enabled'] ?? false );
		$prompt_id = $params['prompt_id'] ?? null;

		// Resolve target user.
		$user_id = isset( $params['user_id'] ) ? absint( $params['user_id'] ) : get_current_user_id();
		if ( $user_id !== get_current_user_id() && ! current_user_can( 'manage_options' ) ) {
			return array(
				'success'         => false,
				'enabled'         => false,
				'prompt_id'       => null,
				'next_generation' => null,
				'message'         => __( 'manage_options capability required to configure another user.', 'ai-story-maker' ),
			);
		}

		if ( ! get_userdata( $user_id ) ) {
			return array(
				'success'         => false,
				'enabled'         => false,
				'prompt_id'       => null,
				'next_generation' => null,
				'message'         => __( 'User not found.', 'ai-story-maker' ),
			);
		}

		update_user_meta( $user_id, AISTMA_Weekly_Scheduler::META_KEY_WEEKLY_ENABLED, $enabled ? 1 : 0 );

		if ( $enabled && ! empty( $prompt_id ) ) {
			$safe_prompt_id = is_numeric( $prompt_id ) ? absint( $prompt_id ) : sanitize_key( $prompt_id );
			update_user_meta( $user_id, AISTMA_Weekly_Scheduler::META_KEY_WEEKLY_PROMPT_ID, $safe_prompt_id );
			$prompt_id = $safe_prompt_id;
		} else {
			$prompt_id = AISTMA_Weekly_Scheduler::get_weekly_prompt( $user_id ) ?: null;
		}

		$next = null;
		if ( $enabled ) {
			$last = (int) get_user_meta( $user_id, AISTMA_Weekly_Scheduler::META_KEY_WEEKLY_LAST_GENERATED, true );
			$next_ts = $last ? $last + WEEK_IN_SECONDS : time() + WEEK_IN_SECONDS;
			$next = get_date_from_gmt( gmdate( 'Y-m-d H:i:s', $next_ts ), 'Y-m-d H:i:s' );
		}

		return array(
			'success'         => true,
			'enabled'         => $enabled,
			'prompt_id'       => $prompt_id,
			'next_generation' => $next,
			'message'         => $enabled
				? __( 'Weekly story generation enabled.', 'ai-story-maker' )
				: __( 'Weekly story generation disabled.', 'ai-story-maker' ),
		);
	}

	// -------------------------------------------------------------------------
	// Helpers
	// -------------------------------------------------------------------------

	/**
	 * Permission callback: caller must have edit_posts.
	 *
	 * @return bool
	 */
	public function require_edit_posts() {
		return current_user_can( 'edit_posts' );
	}

	/**
	 * Build standard gateway request headers by reusing the generator's method
	 * via reflection (it's private, so we expose what we need here directly).
	 *
	 * @param  AISTMA_Story_Generator $generator Generator instance.
	 * @return array
	 */
	private function get_gateway_headers( AISTMA_Story_Generator $generator ) {
		$headers = array(
			'User-Agent'   => 'AI-Story-Maker/1.0',
			'X-Caller-Url' => home_url(),
			'X-Caller-IP'  => sanitize_text_field( wp_unslash( $_SERVER['SERVER_ADDR'] ?? '' ) ),
		);

		$api_key = defined( 'AISTMA_GATEWAY_API_KEY' ) && AISTMA_GATEWAY_API_KEY
			? sanitize_text_field( AISTMA_GATEWAY_API_KEY )
			: sanitize_text_field( get_option( 'aistma_gateway_api_key', '' ) );

		if ( ! empty( $api_key ) ) {
			$headers['Authorization'] = 'Bearer ' . $api_key;
		}

		return $headers;
	}
}
