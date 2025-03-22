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
if (!defined('ABSPATH')) exit;
function get_recent_post_excerpts($days = 10, $category = '') {
    $args = [
        'post_type'      => 'post',
        'posts_per_page' => -1,
        'date_query'     => [
            [
                'after' => gmdate('Y-m-d', strtotime('-' . $days . ' days')),
                'inclusive' => true,
            ]
        ]
    ];

    $query = new WP_Query($args);
    $excerpts = [];
    
    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();
            $excerpts[] = [
                'title' => get_the_title(),
                'excerpt' => get_the_excerpt(),
                'link' => get_permalink()
            ];
        }
    }
    wp_reset_postdata();

    return $excerpts;
}


function generate_ai_story() {
    $api_key = get_option('openai_api_key'); // Store API key in WP options
    
   
    if (!$api_key) {
        ai_storymaker_log('error', 'OpenAI API Key is missing.');
        return;
    }

    $raw_settings = get_option('ai_story_prompts', '');
    $settings = json_decode($raw_settings, true);

    if (json_last_error() !== JSON_ERROR_NONE || empty($settings['prompts'])) {
        ai_storymaker_log('error', 'Invalid JSON format or no prompts found.');
        return;
    }

    // Default settings
    $default_settings = $settings['default_settings'] ?? [];

    // Get recent post excerpts
    $recent_posts = get_recent_post_excerpts(20);


     //This will guarantee that the response will have the required structure
     $admin_prompt_settings = 'The response must strictly follow this structure: { "title": "Article Title", "content": "Full article content...", "excerpt": "A short summary of the article...", "references": [ {"title": "Source 1", "link": "https://example.com/source1"}, {"title": "Source 2", "link": "https://example.com/source2"} ] } return the real https tested domain for your references, not example.com';

    foreach ($settings['prompts'] as $prompt) {
        if ($prompt['active'] === "0") {
            continue;
        }
        if (empty($prompt['text'])) continue;

        // Merge individual prompt settings with default settings
        $merged_settings = array_merge($default_settings, $prompt);
        // add the admin prompt settings to the system content
        $merged_settings['system_content'] .= $admin_prompt_settings;

        $response = wp_remote_post("https://api.openai.com/v1/chat/completions", [
            'headers' => [
                'Authorization' => 'Bearer ' . $api_key,
                'Content-Type' => 'application/json',
            ],
            'body' => json_encode([
                'model' => $merged_settings['model'] ?? 'gpt-4-turbo',
                'messages' => [
                    ['role' => 'system', 'content' => $merged_settings['system_content'] ?? 
                    'Ensure all sources are reputable and properly cited, and include at least 2 references.'],
                    ['role' => 'user', 'content' => $prompt['text']],
                    ['role' => 'user', 'content' => "Here are summaries of recent articles to avoid repetition. Reference them when needed:\n" . json_encode($recent_posts, JSON_PRETTY_PRINT)]
                ],
                'max_completion_tokens' => (int)$merged_settings['max_tokens'] ?? 1500,
                'response_format' => ['type' => 'json_object']
            ], JSON_PRETTY_PRINT),
            'timeout' => $merged_settings['timeout'] ?? 30,
        ]);

        if (is_wp_error($response)) {
            ai_storymaker_log('error', 'OpenAI API Request failed: ' . $response->get_error_message());
            continue;
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);

        if (!isset($body['choices'][0]['message']['content'])) {
            $error_message = $body['error']['message']?? 'Unknown error';
            ai_storymaker_log('error',  $error_message );
            continue;
        }

        // Parse JSON response from OpenAI
        $parsed_content = json_decode($body['choices'][0]['message']['content'], true);

        if (!isset($parsed_content['title'], $parsed_content['content'])) {
            ai_storymaker_log('error', 'OpenAI response does not contain valid JSON with title and content.');
            continue;
        }

        $total_tokens = isset($body['usage']['total_tokens']) ? (int) $body['usage']['total_tokens'] : 0; 
        $request_id = isset($body['id']) ? sanitize_text_field($body['id']) : uniqid('ai_news_'); 
        $title = isset($parsed_content['title']) ? sanitize_text_field($parsed_content['title']) : 'Untitled Article'; 
        $content = isset($parsed_content['content']) ? wp_kses_post($parsed_content['content']) : 'Content not available.'; 
        $category = isset($prompt['category']) ? sanitize_text_field($prompt['category']) : 'News'; 
        if (!term_exists($category, 'category')) {
            wp_insert_term($category, 'category');
        }
        $content = replace_image_placeholders($content); 
        $content .= '<div class="ai-story-model">generated by: '.$default_settings['model'].'</div>';
        $post_id = wp_insert_post([
            'post_title'   => sanitize_text_field($parsed_content['title'] ?? 'Untitled AI Post'),
            'post_content' => $content,
            'post_status'  => 'publish',
            'post_author'  => 1,
            'post_category' => [get_cat_ID($category)],
            'page_template' => 'single-ai-story.php',
            'post_excerpt' => $parsed_content['excerpt'] ?? 'No excerpt available.',
            'meta_input'   => [
                'ai_story_sources' => isset($parsed_content['references']) && is_array($parsed_content['references'])
                    ? json_encode($parsed_content['references'])
                    : json_encode([]),
                'ai_story_total_tokens' => $total_tokens ?? 'N/A',
                'ai_story_request_id' => $request_id ?? 'N/A',
            ],
        ]);


        if ($post_id) {
            update_post_meta($post_id, 'ai_story_sources', isset($parsed_content['references']) && is_array($parsed_content['references']) ? json_encode($parsed_content['references']) : json_encode([]));
            update_post_meta($post_id, 'ai_story_total_tokens', $total_tokens ?? 'N/A');
            update_post_meta($post_id, 'ai_story_request_id', $request_id ?? 'N/A');
            ai_storymaker_log('success', 'AI-generated news article published: ' . get_permalink($post_id), $request_id);

        }
    }
    //fn_ai_story_scheduled_generate(true);
}


// function fn_ai_story_scheduled_generate($scheduleOnly = false) {
//     $current_time = time();
//     $interval = get_option('opt_ai_story_scheduled_generate', 0);
//     $next_scheduled = wp_next_scheduled('sc_ai_story_scheduled_generate');
//     if ($interval < 1) {
//         // just remove schedule
//         wp_clear_scheduled_hook('sc_ai_story_scheduled_generate');
//         ai_storymaker_log('info', 'Scheduled update: The AI story generation schedule has been removed.');
//         return;
//     }
//     $interval_seconds = $interval *  DAY_IN_SECONDS;
//     if ($scheduleOnly) {
//         wp_schedule_single_event(time() + $interval_seconds, 'sc_ai_story_scheduled_generate');
//         ai_storymaker_log('info', 'Scheduled update: The AI story generation schedule has been modified. A new AI-generated story is queued, and the next run is scheduled to occur at: ' . gmdate('Y-m-d H:i:s', time() + $interval_seconds));
//         return;
//     }

//     if ($next_scheduled < $current_time || !$next_scheduled) {
//         wp_clear_scheduled_hook('sc_ai_story_scheduled_generate');
//         wp_schedule_single_event(time() + $interval_seconds, 'sc_ai_story_scheduled_generate');
//         generate_ai_story();
//         ai_storymaker_log('info', 'Schedule just ran, a new story(s) generated on schedule, next run after : ' . gmdate('Y-m-d H:i:s', time() + $interval_seconds));
//     }
// }
// fn_ai_story_scheduled_generate();

