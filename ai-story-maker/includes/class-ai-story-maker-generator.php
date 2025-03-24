<?php
/*
This plugin is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.
 */
namespace AI_Story_Maker;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Story_Generator {

    private $api_key;
    private $default_settings;

    public function __construct() {
        // Hook into your preferred action to trigger AI story generation.
        add_action( 'ai_story_generate', [ $this, 'generate_ai_story' ] );

    }

    /**
     * Retrieve recent post excerpts by numbser of days.
     * Returns an array of post excerpts to add to AI prompt, and avoid content repetition.
     *
     * @param int    $days
     * @param string $category
     *
     * @return array
     */
    public function get_recent_post_excerpts($number_of_posts) {
        $query = new \WP_Query(array(
            'posts_per_page' => $number_of_posts,
            'post_status' => 'publish',
        ));

        $excerpts = array();
        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $excerpts[] = get_the_excerpt();
            }
            wp_reset_postdata();
        }

        return $excerpts;
    }


    /**
     * Generate AI Story using OpenAI API
     */
    public function generate_ai_story() {
        $this->api_key = get_option( 'openai_api_key' );
        if ( ! $this->api_key ) {
            $this->log( 'error', 'OpenAI API Key is missing.' );
            return;
        }

        $raw_settings = get_option( 'ai_story_prompts', '' );
        $settings     = json_decode( $raw_settings, true );

        if ( json_last_error() !== JSON_ERROR_NONE || empty( $settings['prompts'] ) ) {
            $this->log( 'error', 'Invalid JSON format or no prompts found.' );
            return;
        }

        $this->default_settings = isset( $settings['default_settings'] ) ? $settings['default_settings'] : [];

        // Get recent post excerpts (avoid content repetition)
        $recent_posts = $this->get_recent_post_excerpts( 20 );

        // Admin preset to guarantee required structure
        $admin_prompt_settings = 'The response must strictly follow this json structure: { "title": "Article Title", "content": "Full article content...", "excerpt": "A short summary of the article...", "references": [ {"title": "Source 1", "link": "https://yourdomain.com/source1"}, {"title": "Source 2", "link": "https://yourdomain.com/source2"} ] } return the real https tested domain for your references, not example.com';

        foreach ( $settings['prompts'] as $prompt ) {

            if ( isset( $prompt['active'] ) && "0" === $prompt['active'] ) {
                continue;
            }
            if ( empty( $prompt['text'] ) ) {
                continue;
            }

            // Merge individual prompt settings with default settings
            $merged_settings = array_merge( $this->default_settings, $prompt );
            $merged_settings['system_content'] = ( isset( $merged_settings['system_content'] ) ? $merged_settings['system_content'] : 'Ensure all sources are reputable and properly cited, and include at least 2 references.' ) . $admin_prompt_settings;

            $body = [
                'model'       => isset( $merged_settings['model'] ) ? $merged_settings['model'] : 'gpt-4-turbo',
                'messages'    => [
                    [
                        'role'    => 'system',
                        'content' => $merged_settings['system_content'],
                    ],
                    [
                        'role'    => 'user',
                        'content' => $prompt['text'],
                    ],
                    [
                        'role'    => 'user',
                        'content' => "Here are summaries of recent articles to avoid repetition. Reference them when needed:\n" . json_encode( $recent_posts, JSON_PRETTY_PRINT ),
                    ],
                ],
                'max_completion_tokens' => (int) ( $merged_settings['max_tokens'] ?? 1500 ),
                'response_format'       => [ 'type' => 'json_object' ],
            ];

            $response = wp_remote_post(
                "https://api.openai.com/v1/chat/completions",
                [
                    'headers' => [
                        'Authorization' => 'Bearer ' . $this->api_key,
                        'Content-Type'  => 'application/json',
                    ],
                    'body'    => json_encode( $body, JSON_PRETTY_PRINT ),
                    'timeout' => $merged_settings['timeout'] ?? 30,
                ]
            );

            if ( is_wp_error( $response ) ) {
                $this->log( 'error', 'OpenAI API Request failed: ' . $response->get_error_message() );
                continue;
            }

            $response_body = json_decode( wp_remote_retrieve_body( $response ), true );

            if ( ! isset( $response_body['choices'][0]['message']['content'] ) ) {
                $error_message = isset( $response_body['error']['message'] ) ? $response_body['error']['message'] : 'Unknown error';
                $this->log( 'error', $error_message );
                continue;
            }

            // Parse JSON response from OpenAI
            $parsed_content = json_decode( $response_body['choices'][0]['message']['content'], true );

            if ( ! isset( $parsed_content['title'], $parsed_content['content'] ) ) {
                $this->log( 'error', 'OpenAI response does not contain valid JSON with title and content.' );
                continue;
            }

            $total_tokens = isset( $response_body['usage']['total_tokens'] ) ? (int) $response_body['usage']['total_tokens'] : 0;
            $request_id   = isset( $response_body['id'] ) ? sanitize_text_field( $response_body['id'] ) : uniqid( 'ai_news_' );
            $title        = isset( $parsed_content['title'] ) ? sanitize_text_field( $parsed_content['title'] ) : 'Untitled Article';
            $content      = isset( $parsed_content['content'] ) ? wp_kses_post( $parsed_content['content'] ) : 'Content not available.';
            $category     = isset( $prompt['category'] ) ? sanitize_text_field( $prompt['category'] ) : 'News';

            if ( ! term_exists( $category, 'category' ) ) {
                wp_insert_term( $category, 'category' );
            }
            $content = $this->replace_image_placeholders( $content );
            $content .= '<div class="ai-story-model">generated by: ' . esc_html( $merged_settings['model'] ) . '</div>';

            $post_arr = [
                'post_title'    => $title,
                'post_content'  => $content,
                'post_status'   => 'publish',
                'post_author'   => 1,
                'post_category' => [ get_cat_ID( $category ) ],
                'page_template' => 'single-ai-story.php',
                'post_excerpt'  => isset( $parsed_content['excerpt'] ) ? $parsed_content['excerpt'] : 'No excerpt available.',
                'meta_input'    => [
                    '_ai_story_maker_sources'     => isset( $parsed_content['references'] ) && is_array( $parsed_content['references'] )
                        ? json_encode( $parsed_content['references'] )
                        : json_encode( [] ),
                    '_ai_story_maker_total_tokens' => $total_tokens,
                    '_ai_story_maker_request_id'   => $request_id,
                ],
            ];

            $post_id = wp_insert_post( $post_arr );

            if ( $post_id ) {
                update_post_meta( $post_id, '_ai_story_maker_sources', isset( $parsed_content['references'] ) && is_array( $parsed_content['references'] ) ? json_encode( $parsed_content['references'] ) : json_encode( [] ) );
                update_post_meta( $post_id, '_ai_story_maker_total_tokens', $total_tokens );
                update_post_meta( $post_id, '_ai_story_maker_request_id', $request_id );
           
                $this->log( 'success', 'AI-generated news article published: ' . get_permalink( $post_id ), $request_id );
            }
        }
    }

    /**
     * Replace image placeholders in the content.
     *
     * @param string $content
     *
     * @return string
     */
    private function replace_image_placeholders( $content ) {
        // Add actual logic to replace image placeholders as needed.
        return $content;
    }

    /**
     * Logging helper function.
     *
     * @param string $level
     * @param string $message
     * @param string $context
     */
    private function log( $level, $message, $context = '' ) {
        // Implement your logging logic, e.g., error_log or custom logging.
        error_log( strtoupper( $level ) . ': ' . $message . ( $context ? ' | Context: ' . $context : '' ) );
    }
}

// // Initialize the AI Story Maker
// new Story_Generator();
