<?php
/*

Plugin URI: https://github.com/hmamoun/ai-story-maker/wiki
Description: AI-powered content generator for WordPress — create engaging stories with a single click.
Version: 0.1.0
Author: Hayan Mamoun
Author URI: https://exedotcom.ca
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Text Domain: ai-story-maker
Domain Path: /languages
Requires PHP: 7.4
Requires at least: 5.8
Tested up to: 6.7
*/
namespace exedotcom\aistorymaker;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
use exedotcom\aistorymaker\AISTMA_Log_Manager;
class AISTMA_Story_Generator {

    private $api_key;
    private $default_settings;
    protected $aistma_log_manager;


    public function __construct() {
        // Load the Log_Manager class.
        
        $this->aistma_log_manager = new AISTMA_Log_Manager();
        // Hook into an action to trigger AI story generation.
        add_action( 'ai_story_generate', [ $this, 'generate_ai_stories' ] );
    }

    /**
     * Generate AI stories with a lock to prevent concurrent executions.
     * 
     */
    public static function generate_ai_stories_with_lock( $force = false ) {

        $lock_key = 'aistma_generating_lock';
    
        if ( ! $force && get_transient( $lock_key ) ) {
            $instance = new self();
            $instance->aistma_log_manager->log('info', 'Story generation skipped due to active lock.');
            return;
        }
    
        set_transient( $lock_key, true, 10 * MINUTE_IN_SECONDS );
    
        $instance = new self();
        try {
            $instance->generate_ai_stories();
            $instance->aistma_log_manager->log('info', 'Stories successfully generated.');
        } catch ( \Throwable $e ) {
            $instance->aistma_log_manager->log('error', 'Error generating stories: ' . $e->getMessage());
        } finally {
            // Always delete the lock, even if an error occurs
            delete_transient( $lock_key );
        }
    
        // Always schedule the next run after execution
        $n = absint( get_option( 'aistma_generate_story_cron' ) );
        if ( $n !== 0 ) {
            $next_schedule = time() + $n * DAY_IN_SECONDS;
            wp_schedule_single_event( $next_schedule, 'aistma_generate_story_event' );
            $instance->aistma_log_manager->log('info', 'Rescheduled story generation at: ' . gmdate('Y-m-d H:i:s', $next_schedule));
        }
    }
    /**
     * Generate AI Story using OpenAI API.
     * Will get the saved parameters for generating stories, loops through the prompts, and calls the generate_ai_story function.
     * @return void
     */
    public function generate_ai_stories() {
        $results = array(
            'errors'    => array(),
            'successes' => array(),
        );
        // Check if the OpenAI API key is set and is valid
        $this->api_key = get_option( 'aistma_openai_api_key' );
        if ( ! $this->api_key ) {
            $error = __( 'OpenAI API Key is missing.', 'ai-story-maker' );
            $this->aistma_log_manager::log( 'error', $error );
            $results['errors'][] = $error;
            return;
        }

        $raw_settings = get_option( 'aistma_prompts', '' );
        $settings     = json_decode( $raw_settings, true );

        // Check if the settings are valid
        if ( json_last_error() !== JSON_ERROR_NONE || empty( $settings['prompts'] ) ) {
            $error = __( 'Invalid JSON format or no prompts found.', 'ai-story-maker' );
            $this->aistma_log_manager::log( 'error', $error );
            $results['errors'][] = $error;
            wp_send_json_error( $results );
        }

        $this->default_settings = isset( $settings['default_settings'] ) ? $settings['default_settings'] : array();

        // Set default values for the settings. to force the structure of the response and insure the response is in the correct format
        $admin_prompt_settings  = __( 'The response must strictly follow this json structure: { "title": "Article Title", "content": "Full article content...", "excerpt": "A short summary of the article...", "references": [ {"title": "Source 1", "link": "https://yourdomain.com/source1"}, {"title": "Source 2", "link": "https://yourdomain.com/source2"} ] } return the real https tested domain for your references, not example.com', 'ai-story-maker' );

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
            $recent_posts = $this->aistma_get_recent_posts( 20 , $prompt['category'] );

            // Generate the AI story immediately if needed (uncomment to run).
            try {
                $this->generate_ai_story( $prompt['prompt_id'], $prompt, $this->default_settings, $recent_posts, $admin_prompt_settings, $this->api_key );
                $results['successes'][] = __( 'AI story generated successfully.', 'ai-story-maker' );
            } catch ( \Exception $e ) {
                $error = __( 'Error generating AI story: ', 'ai-story-maker' ) . $e->getMessage();
                $this->aistma_log_manager::log( 'error', $error );
                $results['errors'][] = $error;
                
            }
            //self::generate_ai_story( $prompt['prompt_id'], $prompt, $this->default_settings, $recent_posts, $admin_prompt_settings, $this->api_key );
        }

        // bmark Schedule after generate
        $n = absint(get_option( 'aistma_generate_story_cron' ));
        if ( 0 !== $n ) {
                // cancel the current schedule
                wp_clear_scheduled_hook( 'aistma_generate_story_event' );
                // schedule the next event  
                $next_schedule = gmdate( 'Y-m-d H:i:s', time() +  $n * DAY_IN_SECONDS );
                wp_schedule_single_event( time() + $n * DAY_IN_SECONDS , 'aistma_generate_story_event' );
               
                $this->aistma_log_manager::log(
					'info',
                    // translators: %s: Date and time 
					sprintf( __( 'Set next schedule to %s', 'ai-story-maker' ), $next_schedule )
				);

        } else {
            $this->aistma_log_manager::log( 'info', __( 'Schedule for next story is unset', 'ai-story-maker' ) );
            wp_clear_scheduled_hook( 'aistma_generate_story_event' );
        }
    }
    /**
     * Generate AI Story using OpenAI API.
     * will generate the story based on the prompt and settings and create a post.
     * 
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
            ? $merged_settings['system_content'] : '';
            $default_system_content .= "\n" . __( 'You are an article generator tasked with researching a topic online and creating an article based on the provided prompt.', 'ai-story-maker' );
            // $default_system_content .= "\n" . __( 'The article should be:', 'ai-story-maker' );
            // $default_system_content .= "\n" . __( '- At least 1000 words.', 'ai-story-maker' );
            // $default_system_content .= "\n" . __( '- Written in a professional, engaging, and informative tone.', 'ai-story-maker' );
            // $default_system_content .= "\n" . __( '- Free of grammatical errors and typos.', 'ai-story-maker' );
            $default_system_content .= "\n" . __( '- Relevant, original, and not plagiarized.', 'ai-story-maker' );
            $default_system_content .= "\n" . __( '- Composed in clear, semantic HTML format with:', 'ai-story-maker' );
            $default_system_content .= "\n" . __( '  - Main headings using <h2> tags.', 'ai-story-maker' );
            $default_system_content .= "\n" . __( '  - Subheadings using <h3> tags.', 'ai-story-maker' );
            $default_system_content .= "\n" . __( '  - Lists using standard HTML <ul> or <ol> tags.', 'ai-story-maker' );
            $default_system_content .= "\n" . __( '  - Bold text where emphasis is required using <strong> tags.', 'ai-story-maker' );
            $default_system_content .= "\n" . __( 'The article should NOT include Markdown syntax (such as #, *, -, etc.).', 'ai-story-maker' );
            $default_system_content .= "\n" . __( 'Exclude references to the following recent posts:', 'ai-story-maker' );
            
            foreach ( $recent_posts as $post ) {
                $default_system_content .= "\n" . __( 'Title: ', 'ai-story-maker' ) . $post['title'];
                $default_system_content .= "\n" . __( 'Excerpt: ', 'ai-story-maker' ) . $post['excerpt'];
            }
            
            $default_system_content .= "\n" . __( 'Organize the article clearly into sections with meaningful headings and subheadings.', 'ai-story-maker' );
            $default_system_content .= "\n" . __( 'Include at least two valid external reference links formatted as clickable HTML <a> tags.', 'ai-story-maker' );
            

            $merged_settings['system_content'] = $default_system_content . "\n" . $admin_prompt_settings;

            $thePrompt = $prompt['text'];
            if ($prompt['photos'] > 0) {
                $thePrompt .= "\n" . __( 'Include at least ', 'ai-story-maker' ) . $prompt['photos'] . __( ' placeholders for images in the article. insert a placeholder in the following format {img_unsplash:keyword1,keyword2,keyword3} using the most relevant keywords for fetching related images from Unsplash', 'ai-story-maker' );
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
        $status_code = wp_remote_retrieve_response_code( $response );
        // check if response is success
        if ( $status_code !== 200 ) {
            // translators: %d: HTTP status code
            $error_msg = sprintf( __( 'OpenAI API returned HTTP %d', 'ai-story-maker' ), $status_code );
            $this->aistma_log_manager->log( 'error', $error_msg );

            wp_send_json_error( array( 'errors' => array( $error_msg ) ) );
        }
        // check if response is valid
        if ( is_wp_error( $response ) ) {
            $error = $response->get_error_message();
            $this->aistma_log_manager->log( 'error', $error );

            wp_send_json_error( array( 'errors' => array( $error ) ) );
        }
        // check if response is empty
        $response_body = json_decode( wp_remote_retrieve_body( $response ), true );
        if ( ! isset( $response_body['choices'][0]['message']['content'] ) ) {
            $error = __( 'Invalid response from OpenAI API.', 'ai-story-maker' );
            $this->aistma_log_manager->log( 'error', $error );
            wp_send_json_error( array( 'errors' => array( $error ) ) );
        }

        $parsed_content = json_decode( $response_body['choices'][0]['message']['content'], true );
        if ( ! isset( $parsed_content['title'], $parsed_content['content'] ) ) {
            $error = __( 'Invalid content structure.', 'ai-story-maker' );
            $this->aistma_log_manager->log( 'error', $error );
            wp_send_json_error( array( 'errors' => array( $error ) ) );
        }


        $total_tokens = isset( $response_body['usage']['total_tokens'] ) ? (int)$response_body['usage']['total_tokens'] : 0;
        $request_id   = isset( $response_body['id'] ) ? sanitize_text_field( $response_body['id'] ) : uniqid( 'ai_news_' );
        $title        = isset( $parsed_content['title'] ) ? sanitize_text_field( $parsed_content['title'] ) : __( 'Untitled Article', 'ai-story-maker' );
        $content      = isset( $parsed_content['content'] ) ? wp_kses_post( $parsed_content['content'] ) : __( 'Content not available.', 'ai-story-maker' );
        $content      = $this->replace_image_placeholders( $content );
        $category     = isset( $prompt['category'] ) ? sanitize_text_field( $prompt['category'] ) : __( 'News', 'ai-story-maker' );

        $content .= '<div class="ai-story-model">' . __( 'generated by:', 'ai-story-maker' ) . ' ' . esc_html( $merged_settings['model'] ) . '</div>';

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
            //'page_template' => '../public/templates/aistma-post-template.php',
            'post_excerpt' => $parsed_content['excerpt'] ?? 'No excerpt available.',
            'post_status' => $post_status,
         
        ]);
        
        // check for errors
        if ( is_wp_error( $post_id ) ) {
            $error = $post_id->get_error_message();
            $this->aistma_log_manager::log( 'error', $error );
            wp_send_json_error( array( 'errors' => array( $error ) ) );
        }

        if ($post_id) {
            update_post_meta($post_id, 'ai_story_maker_sources', isset($parsed_content['references']) && is_array($parsed_content['references']) ? json_encode($parsed_content['references']) : json_encode([]));
            update_post_meta($post_id, 'ai_story_maker_total_tokens', $total_tokens ?? 'N/A');
            update_post_meta($post_id, 'ai_story_maker_request_id', $request_id ?? 'N/A');
            $this->aistma_log_manager::log('success', 'AI-generated news article created: ' . get_permalink($post_id), $request_id);

        }

       // check for errors
        if ( is_wp_error( $post_id ) ) {
            $error = $post_id->get_error_message();
            $this->aistma_log_manager::log( 'error', $error );
            wp_send_json_error( array( 'errors' => array( $error ) ) );
        }

    }

    /**
     * Retrieve recent post excerpts from a specific category.
     *
     * @param int $number_of_posts
     * @param string $category
     * @return array
     */
    public function aistma_get_recent_posts( $number_of_posts, $category ) {
        $category_id = get_cat_ID( $category ) ?: 0;
        $number_of_posts = absint( $number_of_posts );
    
        $posts = get_posts( [
            'numberposts' => $number_of_posts,
            'post_status' => 'publish',
            'category'    => $category_id,
        ] );
    
        $results = [];
    
        foreach ( $posts as $post ) {
            $content = $post->post_content;
            $excerpt = '';
            if (!empty($content)) {
                $excerpt = wp_trim_words( strip_shortcodes( wp_strip_all_tags( $content ) ), 55 );
            }
            $results[] = [
                'title'   => get_the_title( $post->ID ),
                'excerpt' => $excerpt,
            ];
        }
        wp_reset_postdata();
        return $results;
    }

    /**
     * Replace image placeholders in the article content with Unsplash images.
     * 
     * @param string $article_content The article content with image placeholders.
     * @return string The article content with image placeholders replaced by Unsplash images.
     */
    function replace_image_placeholders($article_content) {
        $self = $this; // assign $this to $self
        return preg_replace_callback('/\{img_unsplash:([a-zA-Z0-9,_ ]+)\}/', function ($matches) use ($self) {
            $keywords = explode(',', $matches[1]);
            $image = $self->fetch_unsplash_image($keywords);
            return $image ? $image : '';
        }, $article_content);
    }
    
    /**
     * Fetch an image from Unsplash based on the provided keywords.
     * 
     * @param array $keywords The keywords to search for.
     * @return string The HTML markup for the image or an empty string if no image is found.
     */
    function fetch_unsplash_image($keywords) {
        $api_key = get_option('aistma_unsplash_api_key');
    
        $query = implode(',', $keywords);
        $url = "https://api.unsplash.com/search/photos?query=" . urlencode($query) . "&client_id=" . $api_key . "&per_page=30&orientation=landscape&quantity=100";
        $response = wp_remote_get($url);
    
        if (is_wp_error($response)) {
            $this->aistma_log_manager::log( 'error', 'Error fetching Unsplash image: ' . $response->get_error_message());
            return ''; 
        }
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        if (empty($data['results'])) {
            $this->aistma_log_manager::log( 'error', $data['errors'][0]);
            return ''; 
        }
        $image_index = array_rand($data['results']); 
        if (!empty($data['results'][$image_index]['urls']['small'])) {
            $url = $data['results'][$image_index]['urls']['small'];
            $credits = $data['results'][$image_index]['user']['name'] . ' by unsplash.com';
            // as required by unsplash
            // phpcs:ignore PluginCheck.CodeAnalysis.ImageFunctions.NonEnqueuedImage
            $ret = '<figure><img src="' . esc_url($url) . '" alt="' . esc_attr(implode(' ', $keywords)) . '" /><figcaption>' . esc_html($credits) . '</figcaption></figure>';
    
          
            return $ret;
    
        }
    
        return ''; // Return empty if no images found
    }

    /**
     * Check if the schedule is set for the next event.
     * 
     * @return void
     */
    public function check_schedule() {
        $next_event = wp_next_scheduled('aistma_generate_story_event');
    
        if ( ! $next_event ) {
            $n = absint(get_option('aistma_generate_story_cron'));
            if ($n !== 0) {
                $run_at = time() + $n * DAY_IN_SECONDS;
                wp_schedule_single_event($run_at, 'aistma_generate_story_event');
                $this->aistma_log_manager->log('info', 'Scheduled next AI story generation at: ' . gmdate('Y-m-d H:i:s', $run_at));
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
    
        $n = absint(get_option('aistma_generate_story_cron'));
        if ($n !== 0) {
            $run_at = time() + $n * DAY_IN_SECONDS;
            wp_schedule_single_event( $run_at, 'aistma_generate_story_event' );
            $this->aistma_log_manager->log('info', 'Rescheduled cron event: ' . gmdate('Y-m-d H:i:s', $run_at));
        }
    }
    

}