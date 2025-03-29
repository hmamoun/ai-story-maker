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
        $number_of_posts = absint( $number_of_posts );

        $posts = get_posts( array(
            'numberposts' => $number_of_posts,
            'post_status' => 'publish',
        ) );
        foreach ( $posts as $post ) {
            setup_postdata( $post );
            $excerpts[] = get_the_excerpt( $post->ID );
        }
        wp_reset_postdata();
        return $excerpts;
    }

    /**
     * Generate AI Story using OpenAI API.
     */
    public function generate_ai_stories() {
        global $ai_story_maker_log_manager;

        $results = array(
            'errors'    => array(),
            'successes' => array(),
        );

        $this->api_key = get_option( 'openai_api_key' );
        if ( ! $this->api_key ) {
            $error = __( 'OpenAI API Key is missing.', 'ai-story-generator' );
            $ai_story_maker_log_manager::log( 'error', $error );
            $results['errors'][] = $error;
            wp_send_json_error( $results );
        }

        $raw_settings = get_option( 'ai_story_prompts', '' );
        $settings     = json_decode( $raw_settings, true );

        if ( json_last_error() !== JSON_ERROR_NONE || empty( $settings['prompts'] ) ) {
            $error = __( 'Invalid JSON format or no prompts found.', 'ai-story-generator' );
            $ai_story_maker_log_manager::log( 'error', $error );
            $results['errors'][] = $error;
            wp_send_json_error( $results );
        }

        $this->default_settings = isset( $settings['default_settings'] ) ? $settings['default_settings'] : array();

        $recent_posts           = $this->get_recent_post_excerpts( 20 );

        $admin_prompt_settings  = __( 'The response must strictly follow this json structure: { "title": "Article Title", "content": "Full article content...", "excerpt": "A short summary of the article...", "references": [ {"title": "Source 1", "link": "https://yourdomain.com/source1"}, {"title": "Source 2", "link": "https://yourdomain.com/source2"} ] } return the real https tested domain for your references, not example.com', 'ai-story-generator' );

        foreach ( $settings['prompts'] as &$prompt ) {
            if ( isset( $prompt['active'] ) && "0" === $prompt['active'] ) {
                continue;
            }
            if ( empty( $prompt['text'] ) ) {
                continue;
            }
            if ( ! isset( $prompt['prompt_id'] ) || empty( $prompt['prompt_id'] ) ) {
                continue;
            }
            // Generate the AI story immediately if needed (uncomment to run).
            // self::generate_ai_story( $prompt['prompt_id'], $prompt, $this->default_settings, $recent_posts, $admin_prompt_settings, $this->api_key );
        }
        // bmark Schedule after generate
        $n = absint(get_option( 'opt_ai_story_repeat_interval_days' ));
        if ( 0 !== $n ) {
                // cancel the current schedule
                wp_clear_scheduled_hook( 'ai_story_generator_repeating_event' );
                // schedule the next event  
                $next_schedule = date( 'Y-m-d H:i:s', time() +  $n * DAY_IN_SECONDS );
                wp_schedule_single_event( time() + $n * DAY_IN_SECONDS , 'ai_story_generator_repeating_event' );
                $ai_story_maker_log_manager::log( 'info', __( 'Set next schedule to ' . $next_schedule, 'ai-story-generator' ) );
        } else {
            $ai_story_maker_log_manager::log( 'info', __( 'Schedule for next story is unset', 'ai-story-generator' ) );
            wp_clear_scheduled_hook( 'ai_story_generator_repeating_event' );
        }
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
            ],
        ];

        $response = wp_remote_post(
            'https://api.openai.com/v1/chat/completions',
            [
                'headers' => [
                    'Authorization' => 'Bearer ' . $api_key,
                    'Content-Type'  => 'application/json',
                ],
                'body'    => wp_json_encode( $body ),
            ]
        );

        if ( is_wp_error( $response ) ) {
            $error = $response->get_error_message();
            $ai_story_maker_log_manager::log( 'error', $error );
            return;
        }

        $response_body = json_decode( wp_remote_retrieve_body( $response ), true );
        if ( ! isset( $response_body['choices'][0]['message']['content'] ) ) {
            $error = __( 'Invalid response from OpenAI API.', 'ai-story-generator' );
            $ai_story_maker_log_manager::log( 'error', $error );
            return;
        }

        $parsed_content = json_decode( $response_body['choices'][0]['message']['content'], true );
        if ( ! isset( $parsed_content['title'], $parsed_content['content'] ) ) {
            $error = __( 'Invalid content structure.', 'ai-story-generator' );
            $ai_story_maker_log_manager::log( 'error', $error );
            return;
        }

        $total_tokens = isset( $response_body['usage']['total_tokens'] ) ? (int)$response_body['usage']['total_tokens'] : 0;
        $request_id   = isset( $response_body['id'] ) ? sanitize_text_field( $response_body['id'] ) : uniqid( 'ai_news_' );
        $title        = isset( $parsed_content['title'] ) ? sanitize_text_field( $parsed_content['title'] ) : __( 'Untitled Article', 'ai-story-generator' );
        $content      = isset( $parsed_content['content'] ) ? wp_kses_post( $parsed_content['content'] ) : __( 'Content not available.', 'ai-story-generator' );
        $category     = isset( $prompt['category'] ) ? sanitize_text_field( $prompt['category'] ) : __( 'News', 'ai-story-generator' );

        if ( ! term_exists( $category, 'category' ) ) {
            wp_insert_term( $category, 'category' );
        }

        // Instantiate the class to use instance methods.
        $instance = new self();
        $content = $instance->replace_image_placeholders( $content );
        $content .= '<div class="ai-story-model">' . __( 'generated by:', 'ai-story-generator' ) . ' ' . esc_html( $merged_settings['model'] ) . '</div>';

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

        $post_arr = [
            'post_title'    => $title,
            'post_content'  => $content,
            'post_status'   => 'publish',
            'post_author'   => $post_author,
            'post_category' => [ get_cat_ID( $category ) ],
            'meta_input'    => [
                '_ai_story_maker_request_id' => $request_id,
                '_ai_story_maker_tokens'     => $total_tokens,
                '_ai_story_maker_sources'    => isset( $parsed_content['references'] ) ? wp_json_encode( $parsed_content['references'] ) : '',
            ],
        ];

        $post_id = wp_insert_post( $post_arr );
        if ( is_wp_error( $post_id ) ) {
            $error = $post_id->get_error_message();
            $ai_story_maker_log_manager::log( 'error', $error );
            return;
        }

    }
}