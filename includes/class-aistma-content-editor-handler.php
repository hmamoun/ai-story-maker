<?php
/**
 * AI Content Editor Handler
 *
 * Handles AJAX requests for content improvement and proxies them to the API Gateway.
 * Validates permissions and manages the communication with the exedotcom-api-gateway.
 *
 * @package AI_Story_Maker
 */

namespace exedotcom\aistorymaker;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class AISTMA_Content_Editor_Handler
 */
class AISTMA_Content_Editor_Handler {

    /**
     * Constructor
     */
    public function __construct() {
        // AJAX handlers for content improvement and standalone editor functionality
        add_action( 'wp_ajax_aistma_improve_content', [ $this, 'handle_improve_content' ] );
        add_action( 'wp_ajax_aistma_standalone_improve_content', [ $this, 'handle_standalone_improve_content' ] );
        
        // Centralized handler for standalone editor save post functionality
        // This replaces the duplicate handler that was previously in AISTMA_Standalone_Editor
        add_action( 'wp_ajax_aistma_standalone_save_post', [ $this, 'handle_standalone_save_post' ] );
    }

    /**
     * Handle content improvement AJAX request
     */
    public function handle_improve_content() {
        // Verify nonce
        if ( ! check_ajax_referer( 'aistma_content_editor_nonce', 'nonce', false ) ) {
            wp_send_json_error( 'Security check failed.' );
        }

        // Check user permissions
        if ( ! current_user_can( 'edit_posts' ) ) {
            wp_send_json_error( 'You do not have permission to perform this action.' );
        }

        // Get and sanitize request parameters
        $selected_text = sanitize_textarea_field( $_POST['selected_text'] ?? '' );
        $user_prompt = sanitize_textarea_field( $_POST['user_prompt'] ?? '' );
        $operation_type = sanitize_text_field( $_POST['operation_type'] ?? 'text_improve' );
        $editor_type = sanitize_text_field( $_POST['editor_type'] ?? 'classic' );
        $post_id = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0;

        // Validate required parameters
        if ( empty( $selected_text ) || empty( $user_prompt ) ) {
            wp_send_json_error( 'Missing required parameters: selected_text and user_prompt are required.' );
        }

        // Validate operation type
        $allowed_operations = [ 'text_improve', 'image_insert', 'image_replace' ];
        if ( ! in_array( $operation_type, $allowed_operations, true ) ) {
            wp_send_json_error( 'Invalid operation_type. Must be one of: ' . implode( ', ', $allowed_operations ) );
        }

        try {
            // Get current domain
            $domain = $this->get_current_domain();
            if ( empty( $domain ) ) {
                wp_send_json_error( 'Unable to determine current domain.' );
            }

            // For post improvements, we allow free usage but still track it
            // Only check subscription for non-improvement operations
            if ( $operation_type !== 'text_improve' ) {
                $subscription_status = $this->get_subscription_status( $domain );
                if ( ! $subscription_status['valid'] ) {
                    wp_send_json_error( 'No valid subscription found for this domain.' );
                }
            }

            // Get package_id from post meta if post_id is provided
            $package_id = 0;
            if ( $post_id > 0 ) {
                $package_id = get_post_meta( $post_id, 'ai_story_maker_package_id', true );
            }

            // Make request to API Gateway
            $response = $this->make_gateway_request( $domain, $selected_text, $user_prompt, $operation_type, $post_id, $package_id );

            if ( is_wp_error( $response ) ) {
                wp_send_json_error( 'Gateway request failed: ' . $response->get_error_message() );
            }

            $response_code = wp_remote_retrieve_response_code( $response );
            $response_body = wp_remote_retrieve_body( $response );
            $data = json_decode( $response_body, true );

            if ( $response_code !== 200 ) {
                $error_message = isset( $data['error'] ) ? $data['error'] : 'Gateway returned HTTP ' . $response_code;
                wp_send_json_error( $error_message );
            }

            if ( ! isset( $data['success'] ) || ! $data['success'] ) {
                $error_message = isset( $data['error'] ) ? $data['error'] : 'Unknown error from gateway';
                wp_send_json_error( $error_message );
            }

            // Update enhancement meta after successful enhancement
            if ( $post_id > 0 ) {
                $this->update_enhancement_meta( $post_id, $operation_type, $user_prompt );
            }

            // Return successful response
            $response_data = [
                'content' => $data['content'] ?? '',
                'operation_type' => $operation_type,
                'usage_info' => $data['usage_info'] ?? [],
            ];

            // Add enhancement status if available
            if ( isset( $data['enhancement_status'] ) ) {
                $response_data['enhancement_status'] = $data['enhancement_status'];
            }

            wp_send_json_success( $response_data );

        } catch ( \Exception $e ) {
            wp_send_json_error( 'An unexpected error occurred. Please try again.' );
        }
    }

    /**
     * Get current domain
     *
     * @return string Current domain
     */
    private function get_current_domain() {
        // Try to get domain from various sources
        $domain = '';

        // First, try to get from HTTP_HOST
        if ( isset( $_SERVER['HTTP_HOST'] ) ) {
            $domain = sanitize_text_field( wp_unslash( $_SERVER['HTTP_HOST'] ) );
        }

        // If that fails, try to get from WordPress
        if ( empty( $domain ) ) {
            $domain = wp_parse_url( home_url(), PHP_URL_HOST );
        }

        // Remove port if present
        if ( strpos( $domain, ':' ) !== false ) {
            $domain = substr( $domain, 0, strpos( $domain, ':' ) );
        }

        return $domain;
    }

    /**
     * Get subscription status for domain
     *
     * @param string $domain Domain to check
     * @return array Subscription status
     */
    private function get_subscription_status( $domain ) {
        // Use the existing story generator to get subscription status
        $story_generator = new AISTMA_Story_Generator();
        return $story_generator->aistma_get_subscription_status( $domain );
    }

    /**
     * Make request to API Gateway
     *
     * @param string $domain Domain
     * @param string $selected_text Selected text
     * @param string $user_prompt User prompt
     * @param string $operation_type Operation type
     * @param int $post_id Post ID
     * @param int $package_id Package ID
     * @return array|\WP_Error Response
     */
    private function make_gateway_request( $domain, $selected_text, $user_prompt, $operation_type, $post_id = 0, $package_id = 0 ) {
        $gateway_url = aistma_get_api_url();
        
        if ( empty( $gateway_url ) ) {
            return new \WP_Error( 'no_gateway_url', 'API Gateway URL not configured' );
        }

        $api_url = trailingslashit( $gateway_url ) . 'wp-json/exaig/v1/improve-content';

        // Get current enhancement count if post_id is provided
        $current_enhancement_count = 0;
        if ( $post_id > 0 ) {
            $enhancements_history_json = get_post_meta( $post_id, 'ai_story_maker_enhancements_history', true );
            $enhancements_history = ! empty( $enhancements_history_json ) ? json_decode( $enhancements_history_json, true ) : [];
            $current_enhancement_count = count( $enhancements_history );
        }

        $request_data = [
            'domain' => $domain,
            'selected_text' => $selected_text,
            'user_prompt' => $user_prompt,
            'operation_type' => $operation_type,
            'post_id' => $post_id,
            'package_id' => $package_id,
            'current_enhancement_count' => $current_enhancement_count,
        ];
        

        $response = wp_remote_post( $api_url, [
            'timeout' => 60,
            'headers' => [
                'Content-Type' => 'application/json',
                'User-Agent' => 'AI-Story-Maker-Content-Editor/1.0',
            ],
            'body' => wp_json_encode( $request_data ),
        ] );

        return $response;
    }

    /**
     * Handle content improvement for standalone editor
     */
    public function handle_standalone_improve_content() {
        // Verify nonce
        if ( ! wp_verify_nonce( $_POST['nonce'] ?? '', 'aistma_standalone_editor_nonce' ) ) {
            wp_send_json_error( 'Invalid nonce.' );
        }

        // Check user permissions
        if ( ! current_user_can( 'edit_posts' ) ) {
            wp_send_json_error( 'Insufficient permissions.' );
        }

        // Sanitize and validate input
        $content = sanitize_textarea_field( $_POST['content'] ?? '' );
        $prompt = sanitize_textarea_field( $_POST['prompt'] ?? '' );
        $operation_type = sanitize_text_field( $_POST['operation_type'] ?? 'text_improve' );

        if ( empty( $content ) ) {
            wp_send_json_error( 'Content is required.' );
        }

        if ( empty( $prompt ) ) {
            wp_send_json_error( 'Improvement prompt is required.' );
        }

        // Validate operation type
        $allowed_operations = [ 'text_improve', 'image_insert', 'image_replace' ];
        if ( ! in_array( $operation_type, $allowed_operations, true ) ) {
            wp_send_json_error( 'Invalid operation type.' );
        }

        try {
            // Get current domain
            $domain = $this->get_current_domain();
            if ( empty( $domain ) ) {
                wp_send_json_error( 'Unable to determine current domain.' );
            }

            // For post improvements, we allow free usage but still track it
            // Only check subscription for non-improvement operations
            if ( $operation_type !== 'text_improve' ) {
                $subscription_status = $this->get_subscription_status( $domain );
                if ( ! $subscription_status['valid'] ) {
                    wp_send_json_error( 'No valid subscription found for this domain.' );
                }
            }

            // Make request to API Gateway
            $response = $this->make_gateway_request( $domain, $content, $prompt, $operation_type );

            if ( is_wp_error( $response ) ) {
                wp_send_json_error( 'Gateway request failed: ' . $response->get_error_message() );
            }

            $response_code = wp_remote_retrieve_response_code( $response );
            $response_body = wp_remote_retrieve_body( $response );
            $data = json_decode( $response_body, true );

            if ( $response_code !== 200 ) {
                $error_message = isset( $data['error'] ) ? $data['error'] : 'Gateway returned HTTP ' . $response_code;
                wp_send_json_error( $error_message );
            }

            if ( ! isset( $data['success'] ) || ! $data['success'] ) {
                $error_message = isset( $data['error'] ) ? $data['error'] : 'Unknown error from gateway';
                wp_send_json_error( $error_message );
            }

            // Return successful response
            wp_send_json_success( [
                'content' => $data['content'] ?? '',
                'operation_type' => $operation_type,
                'usage_info' => $data['usage_info'] ?? [],
            ] );

        } catch ( \Exception $e ) {
            wp_send_json_error( 'An unexpected error occurred. Please try again.' );
        }
    }

    /**
     * Handle save post for standalone editor
     */
    public function handle_standalone_save_post() {
        // Verify nonce
        if ( ! wp_verify_nonce( $_POST['nonce'] ?? '', 'aistma_standalone_editor_nonce' ) ) {
            wp_send_json_error( 'Invalid nonce.' );
        }

        // Check user permissions
        if ( ! current_user_can( 'edit_posts' ) ) {
            wp_send_json_error( 'Insufficient permissions.' );
        }

        // Sanitize and validate input
        $post_id = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0;
        $title = sanitize_text_field( $_POST['title'] ?? '' );
        $content = wp_kses_post( $_POST['content'] ?? '' );
        $tags = sanitize_text_field( $_POST['tags'] ?? '' );
        $meta_description = sanitize_textarea_field( $_POST['meta_description'] ?? '' );

        if ( ! $post_id ) {
            wp_send_json_error( 'Post ID is required.' );
        }

        if ( empty( $title ) ) {
            wp_send_json_error( 'Post title is required.' );
        }

        try {
            // Update the post
            $post_data = [
                'ID' => $post_id,
                'post_title' => $title,
                'post_content' => $content,
            ];

            $result = wp_update_post( $post_data );

            if ( is_wp_error( $result ) ) {
                wp_send_json_error( 'Failed to update post: ' . $result->get_error_message() );
            }

            // Update tags
            if ( ! empty( $tags ) ) {
                wp_set_post_tags( $post_id, $tags );
            }

            // Update meta description
            if ( ! empty( $meta_description ) ) {
                update_post_meta( $post_id, '_yoast_wpseo_metadesc', $meta_description );
                update_post_meta( $post_id, '_aioseo_description', $meta_description );
            }

            wp_send_json_success( [ 'message' => 'Post updated successfully.' ] );

        } catch ( \Exception $e ) {
            wp_send_json_error( 'An unexpected error occurred while saving the post.' );
        }
    }

    /**
     * Handle content improvement for standalone editor (legacy method)
     *
     * @param string $content Content to improve
     * @param string $prompt Improvement prompt
     * @param string $operation_type Operation type
     * @param int $post_id Post ID for enhancement tracking
     * @return array Result array
     */
    public function handle_improve_content_standalone( $content, $prompt, $operation_type, $post_id = 0 ) {
        try {
            // Get current domain
            $domain = $this->get_current_domain();
            if ( empty( $domain ) ) {
                return [ 'success' => false, 'data' => 'Unable to determine current domain.' ];
            }

            // For post improvements, we allow free usage but still track it
            // Only check subscription for non-improvement operations
            if ( $operation_type !== 'text_improve' ) {
                $subscription_status = $this->get_subscription_status( $domain );
                if ( ! $subscription_status['valid'] ) {
                    return [ 'success' => false, 'data' => 'No valid subscription found for this domain.' ];
                }
            }

            // Get package_id from post meta if post_id is provided
            $package_id = 0;
            if ( $post_id > 0 ) {
                $package_id = get_post_meta( $post_id, 'ai_story_maker_package_id', true );
            }

            // Make request to API Gateway
            $response = $this->make_gateway_request( $domain, $content, $prompt, $operation_type, $post_id, $package_id );

            if ( is_wp_error( $response ) ) {
                return [ 'success' => false, 'data' => 'Gateway request failed: ' . $response->get_error_message() ];
            }

            $response_code = wp_remote_retrieve_response_code( $response );
            $response_body = wp_remote_retrieve_body( $response );
            $data = json_decode( $response_body, true );

            if ( $response_code !== 200 ) {
                $error_message = isset( $data['error'] ) ? $data['error'] : 'Gateway returned HTTP ' . $response_code;
                return [ 'success' => false, 'data' => $error_message ];
            }

            if ( ! isset( $data['success'] ) || ! $data['success'] ) {
                $error_message = isset( $data['error'] ) ? $data['error'] : 'Unknown error from gateway';
                return [ 'success' => false, 'data' => $error_message ];
            }

            // Update enhancement meta after successful enhancement
            if ( $post_id > 0 ) {
                $this->update_enhancement_meta( $post_id, $operation_type, $prompt );
            }

            // Return successful response
            $response_data = [
                'content' => $data['content'] ?? '',
                'operation_type' => $operation_type,
                'usage_info' => $data['usage_info'] ?? [],
            ];

            // Add enhancement status if available
            if ( isset( $data['enhancement_status'] ) ) {
                $response_data['enhancement_status'] = $data['enhancement_status'];
            }

            return [ 'success' => true, 'data' => $response_data ];

        } catch ( \Exception $e ) {
            return [ 'success' => false, 'data' => 'An unexpected error occurred. Please try again.' ];
        }
    }


    /**
     * Update enhancement meta data for a post
     *
     * @param int $post_id Post ID
     * @param string $operation_type Type of enhancement
     * @param string $user_prompt User's prompt
     * @return void
     */
    private function update_enhancement_meta( $post_id, $operation_type, $user_prompt ) {
        // Get current enhancement history
        $history_json = get_post_meta( $post_id, 'ai_story_maker_enhancements_history', true );
        $history = ! empty( $history_json ) ? json_decode( $history_json, true ) : [];
        
        // Determine the specific enhancement type based on prompt content
        $enhancement_type = $operation_type;
        if ( $operation_type === 'text_improve' ) {
            $prompt_lower = strtolower( $user_prompt );
            if ( strpos( $prompt_lower, 'tag' ) !== false || strpos( $prompt_lower, 'generate relevant tags' ) !== false ) {
                $enhancement_type = 'tags_enhancement';
            } elseif ( strpos( $prompt_lower, 'seo' ) !== false || strpos( $prompt_lower, 'meta description' ) !== false ) {
                $enhancement_type = 'seo_enhancement';
            } else {
                $enhancement_type = 'content_enhancement';
            }
        }
        
        // Add new enhancement to history
        $history[] = [
            'type' => $enhancement_type,
            'date' => current_time( 'mysql' ),
            'prompt_snippet' => substr( $user_prompt, 0, 100 )
        ];
        
        // Update the history meta
        update_post_meta( $post_id, 'ai_story_maker_enhancements_history', wp_json_encode( $history ) );
    }
}
