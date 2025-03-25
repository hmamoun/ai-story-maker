<?php
namespace AI_Story_Maker;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Story_Generator {

    private $api_key;
    private $default_settings;

    public function __construct() {
        // Hook into an action to trigger AI story generation.
        add_action( 'ai_story_generate', [ $this, 'generate_ai_stories' ] );


    }

    /**
     * Retrieve recent post excerpts.
     *
     * @param int $number_of_posts
     * @return array
     */
    public function get_recent_post_excerpts( $number_of_posts ) {
        $query = new \WP_Query( array(
            'posts_per_page' => $number_of_posts,
            'post_status'    => 'publish',
        ) );

        $excerpts = array();
        if ( $query->have_posts() ) {
            while ( $query->have_posts() ) {
                $query->the_post();
                $excerpts[] = get_the_excerpt();
            }
            wp_reset_postdata();
        }
        return $excerpts;
    }

    /**
     * Generate AI Story using OpenAI API.
     */
    public function generate_ai_stories() {

        $this->api_key = get_option( 'openai_api_key' );
        if ( ! $this->api_key ) {
            Log_Manager::log(  'error', __( 'OpenAI API Key is missing.', 'ai-story-generator' ) );
            return;
        }

        $raw_settings = get_option( 'ai_story_prompts', '' );
        $settings     = json_decode( $raw_settings, true );

        if ( json_last_error() !== JSON_ERROR_NONE || empty( $settings['prompts'] ) ) {
            Log_Manager::log(  'error', __( 'Invalid JSON format or no prompts found.', 'ai-story-generator' ) );
            return;
        }

        $this->default_settings = isset( $settings['default_settings'] ) ? $settings['default_settings'] : array();
        $recent_posts           = $this->get_recent_post_excerpts( 20 );
        $admin_prompt_settings  = __( 'The response must strictly follow this json structure: { "title": "Article Title", "content": "Full article content...", "excerpt": "A short summary of the article...", "references": [ {"title": "Source 1", "link": "https://yourdomain.com/source1"}, {"title": "Source 2", "link": "https://yourdomain.com/source2"} ] } return the real https tested domain for your references, not example.com', 'ai-story-generator' );

        // Loop through each prompt.
        foreach ( $settings['prompts'] as &$prompt ) {
            if ( isset( $prompt['active'] ) && "0" === $prompt['active'] ) {
                continue;
            }
            if ( empty( $prompt['text'] ) ) {
                continue;
            }
            // Ensure each prompt has a unique ID.
            if ( ! isset( $prompt['prompt_id'] ) || empty( $prompt['prompt_id'] ) ) {
                $prompt['prompt_id'] = uniqid( 'ai_prompt_' );
            }
            // self::generate_ai_story(
            //     $prompt['prompt_id'],
            //     $prompt,
            //     $this->default_settings,
            //     $recent_posts,
            //     $admin_prompt_settings,
            //     $this->api_key
            // );
        }

        // Read the repeat interval in days.
        $n = isset( $settings['opt_ai_story_repeat_interval_days'] ) ? absint( $settings['opt_ai_story_repeat_interval_days'] ) : 0;
        update_option( 'ai_story_repeat_interval_days', $n );

        // If n is not zero, schedule the recurring job.
        if ( 0 !== $n ) {
            if ( ! wp_next_scheduled( 'ai_story_generator_repeating_event' ) ) {
                wp_schedule_event( time() + ( $n * DAY_IN_SECONDS ), 'custom_interval', 'ai_story_generator_repeating_event' );
            }
        }else {
            // if n is zero, unschedule the recurring job if exists.
            wp_clear_scheduled_hook( 'ai_story_generator_repeating_event' );

        }

        // Log the event.
        Log_Manager::log(  'info', __( 'AI story generation completed.', 'ai-story-generator' ) );

        // return ajax response
        return array(
            'successes' => __( 'AI story generation completed.', 'ai-story-generator' ),
        );
        
        
    }

    /**
     * Replace image placeholders in the content.
     *
     * @param string $content
     * @return string
     */
    private function replace_image_placeholders( $content ) {
        // Add actual logic to replace image placeholders as needed.
        return $content;
    }

    /**
     * Generate AI Story using OpenAI API.
     * @param string $prompt_id
     * @param array $prompt
     * @param array $default_settings
     * @param array $recent_posts
     * @param string $admin_prompt_settings
     * @param string $api_key
     * @return void
     */
    public static function generate_ai_story( $prompt_id, $prompt, $default_settings, $recent_posts, $admin_prompt_settings, $api_key ) {
        $merged_settings = array_merge( $default_settings, $prompt );
        $default_system_content = isset( $merged_settings['system_content'] )
            ? $merged_settings['system_content']
            : __( 'Ensure all sources are reputable and properly cited, and include at least 2 references.', 'ai-story-generator' );
        $merged_settings['system_content'] = $default_system_content . "\n" . $admin_prompt_settings;

        $body = [
            'model'    => isset( $merged_settings['model'] ) ? $merged_settings['model'] : 'gpt-4-turbo',
            'messages' => [
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
                    'content' => __( "Here are summaries of recent articles to avoid repetition. Reference them when needed:", 'ai-story-generator' )
                        . "\n" . json_encode( $recent_posts, JSON_PRETTY_PRINT ),
                ],
            ],
            'max_completion_tokens' => (int)( $merged_settings['max_tokens'] ?? 1500 ),
            'response_format'       => [ 'type' => 'json_object' ],
        ];

        $response = wp_remote_post(
            "https://api.openai.com/v1/chat/completions",
            [
                'headers' => [
                    'Authorization' => 'Bearer ' . $api_key,
                    'Content-Type'  => 'application/json',
                ],
                'body'    => json_encode( $body, JSON_PRETTY_PRINT ),
                'timeout' => $merged_settings['timeout'] ?? 30,
            ]
        );

        if ( is_wp_error( $response ) ) {
            error_log( __( 'OpenAI API Request failed: ', 'ai-story-generator' ) . $response->get_error_message() );
            return;
        }

        $response_body = json_decode( wp_remote_retrieve_body( $response ), true );
        if ( ! isset( $response_body['choices'][0]['message']['content'] ) ) {
            $error_message = isset( $response_body['error']['message'] )
                ? $response_body['error']['message']
                : __( 'Unknown error', 'ai-story-generator' );
            error_log( $error_message );
            return;
        }

        $parsed_content = json_decode( $response_body['choices'][0]['message']['content'], true );
        if ( ! isset( $parsed_content['title'], $parsed_content['content'] ) ) {
            error_log( __( 'OpenAI response does not contain valid JSON with title and content.', 'ai-story-generator' ) );
            return;
        }

        $total_tokens = isset( $response_body['usage']['total_tokens'] ) ? (int)$response_body['usage']['total_tokens'] : 0;
        $request_id   = isset( $response_body['id'] ) ? sanitize_text_field( $response_body['id'] ) : uniqid( 'ai_news_' );
        $title        = isset( $parsed_content['title'] ) ? sanitize_text_field( $parsed_content['title'] ) : __( 'Untitled Article', 'ai-story-generator' );
        $content      = isset( $parsed_content['content'] ) ? wp_kses_post( $parsed_content['content'] ) : __( 'Content not available.', 'ai-story-generator' );
        $category     = isset( $prompt['category'] ) ? sanitize_text_field( $prompt['category'] ) : __( 'News', 'ai-story-generator' );

        if ( ! term_exists( $category, 'category' ) ) {
            $term = wp_insert_term( $category, 'category' );
            if ( is_wp_error( $term ) ) {
                error_log( __( 'Failed to insert category term: ', 'ai-story-generator' ) . $term->get_error_message() );
                return;
            }
        }

        // Instantiate the class to use instance methods.
        $instance = new self();
        $content = $instance->replace_image_placeholders( $content );
        $content .= '<div class="ai-story-model">' . __( 'generated by:', 'ai-story-generator' ) . ' ' . esc_html( $merged_settings['model'] ) . '</div>';

        // Determine the post author.
        $post_author = 0;
        if ( isset( $prompt['author'] ) && ! empty( $prompt['author'] ) ) {
            $post_author = absint( $prompt['author'] );
            if ( ! get_userdata( $post_author ) ) {
                $post_author = 0;
            }
        }
        if ( ! $post_author ) {
            $post_author = get_current_user_id();
        }
        if ( ! $post_author ) {
            $post_author = get_option( 'ai_story_default_author', 1 );
        }

        $post_arr = [
            'post_title'    => $title,
            'post_content'  => $content,
            'post_status'   => 'publish',
            'post_author'   => $post_author,
            'post_category' => [ get_cat_ID( $category ) ],
            'page_template' => 'single-ai-story.php',
            'post_excerpt'  => isset( $parsed_content['excerpt'] ) ? $parsed_content['excerpt'] : __( 'No excerpt available.', 'ai-story-generator' ),
            'meta_input'    => [
                '_ai_story_maker_sources'     => isset( $parsed_content['references'] ) && is_array( $parsed_content['references'] )
                    ? json_encode( $parsed_content['references'] )
                    : json_encode( [] ),
                '_ai_story_maker_total_tokens' => $total_tokens,
                '_ai_story_maker_request_id'   => $request_id,
            ],
        ];

        $post_id = wp_insert_post( $post_arr );
        if ( is_wp_error( $post_id ) ) {
            error_log( __( 'Failed to insert post: ', 'ai-story-generator' ) . $post_id->get_error_message() );
            return;
        }
        error_log( __( 'AI-generated news article published: ', 'ai-story-generator' ) . get_permalink( $post_id ) );
    }


}
