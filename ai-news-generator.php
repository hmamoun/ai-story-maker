<?php
/**
 * Plugin Name: AI News Generator
 * Description: Automatically generates AI-powered news articles and publishes them as WordPress posts.
 * Version: 1.0
 * Author: Your Name
 */


 /*
 TODO: add a property to the prmopt [active / inactive] and exclude inactive prmpts
 TODO: return another entity for the article and save it with the data of the post [like excerpt] 
 TODO: let the function read the previous posts and make the new post exclude all topics mentioned in the posts publishd the last week
 TODO: create a git repo for this plugin
 TODO: Add an proprty to the prmpt [category] and let the generated post be in the same category
 TODO: create the category if not exists
 TODO: parametarize the schedule information
 TODO: check for the liability of the plugin, and is it ok to make it public
 */
if (!defined('ABSPATH')) exit; // Prevent direct access

// Schedule WP Cron event on plugin activation
register_activation_hook(__FILE__, function() {
    if (!wp_next_scheduled('generate_ai_news_cron')) {
        wp_schedule_event(time(), 'daily', 'generate_ai_news_cron');
    }
});

// Unschedule WP Cron event on plugin deactivation
register_deactivation_hook(__FILE__, function() {
    wp_clear_scheduled_hook('generate_ai_news_cron');
});

// Hook into WP Cron to generate AI content
add_action('generate_ai_news_cron', 'generate_ai_news');


function generate_ai_newsx() {
    $api_key = get_option('openai_api_key'); // Store API key in WP options
    if (!$api_key) {
        return ['errors' => ['❌ ERROR: OpenAI API Key is missing.']];
    }

    $raw_settings = get_option('ai_news_prompts', '');
    $settings = json_decode($raw_settings, true);

    if (json_last_error() !== JSON_ERROR_NONE || empty($settings['prompts'])) {
        return ['errors' => ['❌ ERROR: Invalid JSON format or no prompts found.']];
    }

    // Default settings
    $default_settings = $settings['default_settings'] ?? [];
    $errors = [];
    $successes = [];

    foreach ($settings['prompts'] as $prompt) {
        if (empty($prompt['text'])) continue;

        // Merge individual prompt settings with default settings
        $merged_settings = array_merge($default_settings, $prompt);
        
        $response = wp_remote_post("https://api.openai.com/v1/chat/completions", [
            'headers' => [
                'Authorization' => 'Bearer ' . $api_key,
                'Content-Type' => 'application/json',
            ],
            'body' => json_encode([
                'model' => $merged_settings['model'] ?? 'gpt-4-turbo',
                'messages' => [
                    ['role' => 'system', 'content' => $merged_settings['system_content'] ?? 'You are an expert writer specializing in immigration topics.'],
                    ['role' => 'user', 'content' => $prompt['text']],
                ],
                'max_tokens' => (int) ($merged_settings['max_tokens'] ?? 1500),
            ]),
            'timeout' => $merged_settings['timeout'] ?? 30,
        ]);

        if (is_wp_error($response)) {
            $errors[] = '❌ ERROR: OpenAI API Request failed: ' . $response->get_error_message();
            continue;
        }

        // $body = json_decode(wp_remote_retrieve_body($response), true);
        // $content = $body['choices'][0]['message']['content'] ?? '';
        $raw_body = wp_remote_retrieve_body($response);
        $body = json_decode($raw_body, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log('❌ ERROR: Invalid JSON from OpenAI: ' . $raw_body);
            continue;
        }
        
        // Log the entire API response for debugging
        error_log('🔹 DEBUG: OpenAI Raw Response: ' . print_r($body, true));
        
        $content = $body['choices'][0]['message']['content'] ?? '';
        
        if (!$content) {
            error_log('❌ ERROR: OpenAI returned an empty content. Full response: ' . print_r($body, true));
            continue;
        }


        // Extract title and content
        preg_match('/Title:\s*(.*?)\s*Content:/s', $content, $matches);
        $title = $matches[1] ?? 'AI Immigration Update - ' . date('F j, Y');
        $content = str_replace("Title: $title\nContent:", '', $content);

        // Insert new post into WordPress
        $post_id = wp_insert_post([
            'post_title'   => $title,
            'post_content' => $content,
            'post_status'  => 'publish',
            'post_author'  => 1,
            'post_category' => [get_cat_ID('Immigration News')],
        ]);

        if ($post_id) {
            $successes[] = '✅ AI-generated news article published: ' . get_permalink($post_id);
        } else {
            $errors[] = '❌ ERROR: Failed to insert post into WordPress.';
        }
    }

    return ['errors' => $errors, 'successes' => $successes];
}
function generate_ai_news() {
    $api_key = get_option('openai_api_key'); // Store API key in WP options
    if (!$api_key) {
        error_log('❌ ERROR: OpenAI API Key is missing.');
        return;
    }

    $raw_settings = get_option('ai_news_prompts', '');
    $settings = json_decode($raw_settings, true);

    if (json_last_error() !== JSON_ERROR_NONE || empty($settings['prompts'])) {
        error_log('❌ ERROR: Invalid JSON format or no prompts found.');
        return;
    }

    // Default settings
    $default_settings = $settings['default_settings'] ?? [];
    
    foreach ($settings['prompts'] as $prompt) {
        if (empty($prompt['text'])) continue;

        // Merge individual prompt settings with default settings
        $merged_settings = array_merge($default_settings, $prompt);
        
        $response = wp_remote_post("https://api.openai.com/v1/chat/completions", [
            'headers' => [
                'Authorization' => 'Bearer ' . $api_key,
                'Content-Type' => 'application/json',
            ],
            'body' => json_encode([
                'model' => $merged_settings['model'] ?? 'gpt-4-turbo',
                'messages' => [
                    ['role' => 'system', 'content' => $merged_settings['system_content'] ?? 'You are an expert writer specializing in immigration topics. Ensure the response is formatted as a JSON object with keys: title and content.'],
                    ['role' => 'user', 'content' => $prompt['text']],
                ],
                'max_tokens' => (int)$merged_settings['max_tokens'] ?? 1500,
                'response_format' => ['type' => 'json_object']
            ], JSON_PRETTY_PRINT),
            'timeout' => $merged_settings['timeout'] ?? 30,
        ]);

        if (is_wp_error($response)) {
            error_log('❌ ERROR: OpenAI API Request failed: ' . $response->get_error_message());
            continue;
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);

        if (!isset($body['choices'][0]['message']['content'])) {
            error_log('❌ ERROR: OpenAI returned an empty response.');
            error_log(print_r($body, true));
            continue;
        }

        // Parse JSON response from OpenAI
        $parsed_content = json_decode($body['choices'][0]['message']['content'], true);
        if (!isset($parsed_content['title'], $parsed_content['content'])) {
            error_log('❌ ERROR: OpenAI response does not contain valid JSON with title and content.');
            continue;
        }

        $title = sanitize_text_field($parsed_content['title']);
        $content = wp_kses_post($parsed_content['content']);

        // Insert new post into WordPress
        $post_id = wp_insert_post([
            'post_title'   => $title,
            'post_content' => $content,
            'post_status'  => 'publish',
            'post_author'  => 1,
            'post_category' => [get_cat_ID('Immigration News')],
        ]);

        if ($post_id) {
            error_log('✅ AI-generated news article published: ' . get_permalink($post_id));
        }
    }
}
// Admin Page to Set OpenAI API Key
add_action('admin_menu', function() {
    add_menu_page('AI News Settings', 'AI News', 'manage_options', 'ai-news-settings', 'ai_news_settings_page');
});

function ai_news_settings_page() {
    if (isset($_POST['save_api_key'])) {
        update_option('openai_api_key', sanitize_text_field($_POST['openai_api_key']));
        echo '<div class="updated"><p>✅ OpenAI API Key saved!</p></div>';
    }
    if (isset($_POST['news_api_key'])) {
        update_option('news_api_key', sanitize_text_field($_POST['news_api_key']));
        echo '<div class="updated"><p>✅ News API Key saved!</p></div>';
    }
    if (isset($_POST['save_prompts'])) {
        $raw_json = stripslashes($_POST['ai_news_prompts']);
        $decoded_json = json_decode($raw_json, true);
        
        // Ensure JSON decoding was successful
        if (!is_array($decoded_json)) {
            echo '<div class="error"><p>❌ Invalid JSON format. Please check and correct it.</p></div>';
        } else {
            // Sanitize the JSON content before saving
            array_walk_recursive($decoded_json, function (&$value) {
                $value = sanitize_text_field($value);
            });
        
            update_option('ai_news_prompts', json_encode($decoded_json, JSON_PRETTY_PRINT));
            echo '<div class="updated"><p>✅ Prompts saved successfully!</p></div>';
        }
        

    }

    if (isset($_POST['generate_ai_news'])) {
        $results = generate_ai_news();
        if (!empty($results['errors'])) {
            foreach ($results['errors'] as $error) {
            echo '<div class="error"><p>' . esc_html($error) . '</p></div>';
            }
        }
        if (!empty($results['successes'])) {
            foreach ($results['successes'] as $success) {
            echo '<div class="updated"><p>' . esc_html($success) . '</p></div>';
            }
        }
    }


    ?>
    <div class="wrap">
        <h1>AI News Generator Settings</h1>
        <form method="POST">
            <label for="openai_api_key">OpenAI API Key:</label>
            <input type="text" name="openai_api_key" value="<?php echo esc_attr(get_option('openai_api_key')); ?>" style="width: 100%;">
            <br><br>
            <label for="openai_api_key">News API https://newsapi.org/:</label>
            <input type="text" name="news_api_key" value="<?php echo esc_attr(get_option('news_api_key')); ?>" style="width: 100%;">
            <br><br>
            <input type="submit" name="save_api_key" value="Save API Key" class="button button-primary">
        </form>
        
        <form method="POST">
            <h2>Prompts</h2>
            <?php
                $raw_json = get_option('ai_news_prompts', '{}');
                if (is_array($raw_json)) {
                    $raw_json = json_encode($raw_json, JSON_PRETTY_PRINT);
                }
                $decoded_json = json_decode($raw_json, true);

                if (json_last_error() !== JSON_ERROR_NONE) {
                    $formatted_json = "NOT VALID JSON\n" . $raw_json;
                } else {
                    $formatted_json = json_encode($decoded_json, JSON_PRETTY_PRINT);
                }
                ?>
            <textarea name="ai_news_prompts" rows="10" style="width: 100%;"><?php echo esc_textarea($formatted_json); ?></textarea>
            <br><br>
            <input type="submit" name="save_prompts" value="Save Prompts" class="button button-primary">


        </form>
    </div>


    <hr>

    <h2>Manually Generate AI News</h2>
    <form method="POST">
        <input type="submit" name="generate_ai_news" value="Generate AI News Now" class="button button-secondary">
    </form>
    </div>

    <?php
}
?>
