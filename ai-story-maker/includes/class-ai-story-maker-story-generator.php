<?php
namespace AI_Story_Maker;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Story_Generator {

    private $api_key;
    private $default_settings;
    protected $log_manager;


    public function __construct() {
        // Load the Log_Manager class.
        
        $this->log_manager = new Log_Manager();
        // Hook into an action to trigger AI story generation.
        add_action( 'ai_story_generate', [ $this, 'generate_ai_stories' ] );
    }

    /**
     * Retrieve recent post excerpts.
     *
     * @param int $number_of_posts
     * @return array
     */
    public function get_recent_posts( $number_of_posts, $category ) {
        $category_id = get_cat_ID( $category ) ?: 0;
        $number_of_posts = absint( $number_of_posts );
        $posts = get_posts( [
            'numberposts' => $number_of_posts,
            'post_status' => 'publish',
            'category'    => $category_id,
        ] );
        $results = [];
        foreach ( $posts as $post ) {
            $results[] = [
                'title'   => get_the_title( $post->ID ),
                'excerpt' => get_the_excerpt( $post->ID ),
            ];
        }
        wp_reset_postdata();
        return $results;
    }


    
    /**
     * Generate AI Story using OpenAI API.
     */
    public function generate_ai_stories() {

        $results = array(
            'errors'    => array(),
            'successes' => array(),
        );

        $this->api_key = get_option( 'openai_api_key' );
        if ( ! $this->api_key ) {
            $error = __( 'OpenAI API Key is missing.', 'ai-story-generator' );
            $this->log_manager::log( 'error', $error );
            $results['errors'][] = $error;
            wp_send_json_error( $results );
        }

        $raw_settings = get_option( 'ai_story_prompts', '' );
        $settings     = json_decode( $raw_settings, true );

        if ( json_last_error() !== JSON_ERROR_NONE || empty( $settings['prompts'] ) ) {
            $error = __( 'Invalid JSON format or no prompts found.', 'ai-story-generator' );
            $this->log_manager::log( 'error', $error );
            $results['errors'][] = $error;
            wp_send_json_error( $results );
        }

        $this->default_settings = isset( $settings['default_settings'] ) ? $settings['default_settings'] : array();

        $admin_prompt_settings  = __( 'The response must strictly follow this json structure: { "title": "Article Title", "content": "Full article content...", "excerpt": "A short summary of the article...", "references": [ {"title": "Source 1", "link": "https://yourdomain.com/source1"}, {"title": "Source 2", "link": "https://yourdomain.com/source2"} ] } return the real https tested domain for your references, not example.com', 'ai-story-generator' );

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
            $recent_posts = $this->get_recent_posts( 20 , $prompt['category'] );

            // Generate the AI story immediately if needed (uncomment to run).
            self::generate_ai_story( $prompt['prompt_id'], $prompt, $this->default_settings, $recent_posts, $admin_prompt_settings, $this->api_key );
        }

        // bmark Schedule after generate
        $n = absint(get_option( 'opt_ai_story_repeat_interval_days' ));
        if ( 0 !== $n ) {
                // cancel the current schedule
                wp_clear_scheduled_hook( 'ai_story_generator_repeating_event' );
                // schedule the next event  
                $next_schedule = date( 'Y-m-d H:i:s', time() +  $n * DAY_IN_SECONDS );
                wp_schedule_single_event( time() + $n * DAY_IN_SECONDS , 'ai_story_generator_repeating_event' );

                $this->log_manager::log( 'info', __( 'Set next schedule to ' . $next_schedule, 'ai-story-generator' ) );
        } else {
            $this->log_manager::log( 'info', __( 'Schedule for next story is unset', 'ai-story-generator' ) );
            wp_clear_scheduled_hook( 'ai_story_generator_repeating_event' );
        }
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
    public function generate_ai_story( $prompt_id, $prompt, $default_settings, $recent_posts, $admin_prompt_settings, $api_key ) {
        $merged_settings = array_merge( $default_settings, $prompt );
        $default_system_content = isset( $merged_settings['system_content'] )
            ? $merged_settings['system_content']
            : '';
            $default_system_content .= "\n" . __( 'You are an article generator. Your task is to search the web for the topic and create an article based on the given prompt.', 'ai-story-generator' );
            $default_system_content .= "\n" . __( 'The article should be informative, engaging, and well-structured.', 'ai-story-generator' );

            $default_system_content .= "\n" . __( '- at least 1000 words long.', 'ai-story-generator' );
            $default_system_content .= "\n" . __( '- written in a professional tone.', 'ai-story-generator' );
            $default_system_content .= "\n" . __( '- free of grammatical errors and typos.', 'ai-story-generator' );
            $default_system_content .= "\n" . __( '- relevant to the given prompt.', 'ai-story-generator' );
            $default_system_content .= "\n" . __( '- unique and not plagiarized.', 'ai-story-generator' );
            $default_system_content .= "\n" . __( '- written in English.', 'ai-story-generator' );
            $default_system_content .= "\n" . __( 'The article should not include any of the following recent posts or titles:', 'ai-story-generator' );
            foreach ( $recent_posts as $post ) {
                $default_system_content .= "\n" . __( 'Title: ', 'ai-story-generator' ) . $post['title'];
                $default_system_content .= "\n" . __( 'Excerpt: ', 'ai-story-generator' ) . $post['excerpt'];
            }
            $default_system_content .= "\n" . __( 'The article should be divided into sections with appropriate headings and subheadings.', 'ai-story-generator' );
            $default_system_content .= "\n" . __( 'The article should include at least 2 valid reference links.', 'ai-story-generator' );



            $merged_settings['system_content'] = $default_system_content . "\n" . $admin_prompt_settings;

            $thePrompt = $prompt['text'];
            if ($prompt['photos'] > 0) {
                $thePrompt .= "\n" . __( 'Include at least ', 'ai-story-generator' ) . $prompt['photos'] . __( ' placeholders for images in the article. insert a placeholder in the following format {img_unsplash:keyword1,keyword2,keyword3} using the most relevant keywords for fetching related images from Unsplash', 'ai-story-generator' );
            }
            $response = wp_remote_post("https://api.openai.com/v1/chat/completions", [
                'headers' => [
                    'Authorization' => 'Bearer ' . $api_key,
                    'Content-Type' => 'application/json',
                ],
                'body' => json_encode([
                    'model' => $merged_settings['model'] ?? 'gpt-4-turbo',
                    'messages' => [
                        ['role' => 'system', 'content' => $merged_settings['system_content'] ?? ''],
                        ['role' => 'user', 'content' => $thePrompt],
                    ],
                    'max_tokens' =>  1500,
                    'response_format' => ['type' => 'json_object']
                ], JSON_PRETTY_PRINT),
                'timeout' => $merged_settings['timeout'] ?? 30,
            ]);

        if ( is_wp_error( $response ) ) {
            $error = $response->get_error_message();
            $this->log_manager->log( 'error', $error );
            wp_send_json_error( array( 'errors' => array( $error ) ) );
        }

        $response_body = json_decode( wp_remote_retrieve_body( $response ), true );
        if ( ! isset( $response_body['choices'][0]['message']['content'] ) ) {
            $error = __( 'Invalid response from OpenAI API.', 'ai-story-generator' );
            $this->log_manager->log( 'error', $error );
            wp_send_json_error( array( 'errors' => array( $error ) ) );
        }

        $parsed_content = json_decode( $response_body['choices'][0]['message']['content'], true );
        if ( ! isset( $parsed_content['title'], $parsed_content['content'] ) ) {
            $error = __( 'Invalid content structure.', 'ai-story-generator' );
            $this->log_manager->log( 'error', $error );
            wp_send_json_error( array( 'errors' => array( $error ) ) );
        }


        $total_tokens = isset( $response_body['usage']['total_tokens'] ) ? (int)$response_body['usage']['total_tokens'] : 0;
        $request_id   = isset( $response_body['id'] ) ? sanitize_text_field( $response_body['id'] ) : uniqid( 'ai_news_' );
        $title        = isset( $parsed_content['title'] ) ? sanitize_text_field( $parsed_content['title'] ) : __( 'Untitled Article', 'ai-story-generator' );
        $content      = isset( $parsed_content['content'] ) ? wp_kses_post( $parsed_content['content'] ) : __( 'Content not available.', 'ai-story-generator' );
        $content      = $this->replace_image_placeholders( $content );
        $category     = isset( $prompt['category'] ) ? sanitize_text_field( $prompt['category'] ) : __( 'News', 'ai-story-generator' );

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


        // Determine auto publish post variable
        $auto_publish = isset( $prompt['auto_publish'] ) ? (bool) $prompt['auto_publish'] : false;
        if ( $auto_publish ) {
            $post_status = 'publish';
        } else {
            $post_status = 'draft';
        }
        

        $post_id = wp_insert_post([
            'post_title'   => sanitize_text_field($parsed_content['title'] ?? 'Untitled AI Post'),
            'post_content' => $content,
            // 'post_status'  => 'publish',
            'post_author'  => 1,
            'post_category' => [get_cat_ID($category)],
            //'page_template' => '../public/single-ai-story.php',
            'post_excerpt' => $parsed_content['excerpt'] ?? 'No excerpt available.',
            'post_status' => $post_status,
         
        ]);
        
        // check for errors
        if ( is_wp_error( $post_id ) ) {
            $error = $post_id->get_error_message();
            $this->log_manager::log( 'error', $error );
            wp_send_json_error( array( 'errors' => array( $error ) ) );
        }

        if ($post_id) {
            update_post_meta($post_id, 'ai_story_maker_sources', isset($parsed_content['references']) && is_array($parsed_content['references']) ? json_encode($parsed_content['references']) : json_encode([]));
            update_post_meta($post_id, 'ai_story_maker_total_tokens', $total_tokens ?? 'N/A');
            update_post_meta($post_id, 'ai_story_maker_request_id', $request_id ?? 'N/A');
            $this->log_manager::log('success', 'AI-generated news article created: ' . get_permalink($post_id), $request_id);

        }

       // check for errors
        if ( is_wp_error( $post_id ) ) {
            $error = $post_id->get_error_message();
            $this->log_manager::log( 'error', $error );
            wp_send_json_error( array( 'errors' => array( $error ) ) );
        }

    }

    function replace_image_placeholders($article_content) {
        $self = $this; // assign $this to $self
        return preg_replace_callback('/\{img_unsplash:([a-zA-Z0-9,_ ]+)\}/', function ($matches) use ($self) {
            $keywords = explode(',', $matches[1]);
            $image = $self->fetch_unsplash_image($keywords);
            return $image ? $image : '';
        }, $article_content);
    }
    
    function fetch_unsplash_image($keywords) {
        $api_key = get_option('unsplash_api_key');
    
        $query = implode(',', $keywords);
        $url = "https://api.unsplash.com/search/photos?query=" . urlencode($query) . "&client_id=" . $api_key . "&per_page=30&orientation=landscape&quantity=100";
        $response = wp_remote_get($url);
    
        if (is_wp_error($response)) {
            $this->log_manager::log( 'error', 'Error fetching Unsplash image: ' . $response->get_error_message());
            return ''; 
        }
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        if (empty($data['results'])) {
            $this->log_manager::log( 'error', $data['errors'][0]);
            return ''; 
        }
        $image_index = array_rand($data['results']); 
        if (!empty($data['results'][$image_index]['urls']['small'])) {
            $url = $data['results'][$image_index]['urls']['small'];
            $credits = $data['results'][$image_index]['user']['name'] . ' by unsplash.com';
            // required by unsplash
            // phpcs:ignore PluginCheck.CodeAnalysis.ImageFunctions.NonEnqueuedImage
            $ret = '<figure><img src="' . esc_url($url) . '" alt="' . esc_attr(implode(' ', $keywords)) . '" /><figcaption>' . esc_html($credits) . '</figcaption></figure>';
    
          
            return $ret;
    
        }
    
        return ''; // Return empty if no images found
    }



}