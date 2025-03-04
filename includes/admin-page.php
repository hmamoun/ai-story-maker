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
// Add the main menu item (Story Maker Settings)
add_action('admin_menu', function() {
    // Main menu item
    add_menu_page(
        'Story Maker Settings', 
        'Story Maker',          
        'manage_options',       
        'story-maker-settings', 
        'fn_story_maker_settings_page', 
        'dashicons-welcome-widgets-menus', 
        9                    
    );

    // Add the submenu item (AI Story Logs) under the main menu
    add_submenu_page(
        'story-maker-settings', // Parent slug (same as the main menu's slug)
        'AI Story Logs',        
        'Story Maker Logs',     
        'manage_options',       
        'ai-storymaker-logs',   
        'fn_ai_storymaker_logs_page' 
    );
});


// Add custom columns to the admin post list

add_filter('manage_post_posts_columns', function ($columns) {
    $columns['story_maker_total_tokens'] = 'Total Tokens';
    $columns['story_maker_request_id'] = 'Request ID';
    return $columns;
});

// Populate custom columns with AI metadata
add_action('manage_post_posts_custom_column', function ($column, $post_id) {
    if ($column === 'story_maker_total_tokens') {
        echo esc_html(get_post_meta($post_id, 'story_maker_total_tokens', true) ?: 'N/A');
    } elseif ($column === 'story_maker_request_id') {
        echo esc_html(get_post_meta($post_id, 'story_maker_request_id', true) ?: 'N/A');
    }
}, 10, 2);


function fn_story_maker_settings_page() {
    if (isset($_POST['save_settings'])) {
        // if there are updates in schedules, clear current schedules
        // check if opt_ai_storymaker_clear_log is different
        if (get_option('opt_ai_storymaker_clear_log') != sanitize_text_field($_POST['opt_ai_storymaker_clear_log'])) {
            wp_clear_scheduled_hook('sc_ai_storymaker_clear_log');
        }
        if (get_option('opt_ai_story_scheduled_generate') != sanitize_text_field($_POST['opt_ai_story_scheduled_generate'])) {
            wp_clear_scheduled_hook('sc_ai_story_scheduled_generate');
        }

        update_option('openai_api_key', sanitize_text_field($_POST['openai_api_key']));
        update_option('unsplash_api_key', sanitize_text_field($_POST['unsplash_api_key']));
        update_option('unsplash_api_secret', sanitize_text_field($_POST['unsplash_api_secret']));
        update_option('opt_ai_storymaker_clear_log', sanitize_text_field($_POST['opt_ai_storymaker_clear_log']));
        update_option('opt_ai_story_scheduled_generate', sanitize_text_field($_POST['opt_ai_story_scheduled_generate']));
        update_option('opt_ai_story_auther', intval($_POST['opt_ai_story_auther']));
        echo '<div class="updated"><p>✅ Settings saved!</p></div>';
        ai_storymaker_log('success', 'Settings saved ');
    }

    if (isset($_POST['save_prompts'])) {
        $raw_json = stripslashes($_POST['ai_story_prompts']);
        $decoded_json = json_decode($raw_json, true);
        
        // Ensure JSON decoding was successful
        if (!is_array($decoded_json)) {
            echo '<div class="error"><p>❌ Invalid JSON format. Please check and correct it.</p></div>';
            ai_storymaker_log('success', '❌ Invalid JSON format. Please check and correct it. ');
        } else {
            // Sanitize the JSON content before saving
            array_walk_recursive($decoded_json, function (&$value) {
                $value = sanitize_text_field($value);
            });
        
            update_option('ai_story_prompts', json_encode($decoded_json, JSON_PRETTY_PRINT));
            echo '<div class="updated"><p>✅ Prompts saved successfully!</p></div>';
            ai_storymaker_log('success', '✅ Prompts saved successfully! ');
        }
    }

    ?>
    <div class="wrap">
        <h1>WP AI Story Maker Settings</h1>
        <form method="POST">
            <label for="openai_api_key">OpenAI <a href=https://platform.openai.com/>API</a> Key:</label>
            <input type="text" name="openai_api_key" value="<?php echo esc_attr(get_option('openai_api_key')); ?>" style="width: 100%;">
            <!-- <br><br>
            <label for="news_api_key"><a href="https://newsapi.org" target="_blank">News API</a>: </label>
            <input type="text" name="news_api_key" value="<?php echo esc_attr(get_option('news_api_key')); ?>" style="width: 100%;"> -->
            <br><br>
            Secret and key for <a href="https://unsplash.com/" target="_blank">Unsplash Developers</a>, free source for photos :
            <br>
            <label for="unsplash_api_key">Key</label>
            <input type="text" name="unsplash_api_key" value="<?php echo esc_attr(get_option('unsplash_api_key')); ?>" style="width: 40%;">
            <label for="unsplash_api_secret">Secret</label>
            <input type="text" name="unsplash_api_secret" value="<?php echo esc_attr(get_option('unsplash_api_secret')); ?>" style="width: 40%;">
            
            <br><br>
            <!-- <label for="pexels_api_key">Pexels Key</label>
            <input type="text" name="pexels_api_key" value="<?php echo esc_attr(get_option('pexels_api_key')); ?>" style="width: 100%;">
            <br><br>        -->

            <label for="opt_ai_storymaker_clear_log">Log Retention: </label>
            <input type="text" name="opt_ai_storymaker_clear_log" value="<?php echo esc_attr(get_option('opt_ai_storymaker_clear_log')); ?>" style="position: absolute;width: 100px;text-align: center;left: 300px;"> days.
            <br><br>   
            
            <label for="opt_ai_story_scheduled_generate">Create stories every </label>
            <input type="text" name="opt_ai_story_scheduled_generate" value="<?php echo esc_attr(get_option('opt_ai_story_scheduled_generate')); ?>"style="position: absolute;width: 100px;text-align: center;left: 300px;"> days. 

            <br><br>  
            <label for="opt_ai_story_auther">Story auther: </label>
            <?php
            $users = get_users(array('role__in' => array('author', 'administrator')));
            ?>
            <select name="opt_ai_story_auther"style="position: absolute;width: 100px;text-align: center;left: 300px;">
                <?php foreach ($users as $user) : ?>
                    <option value="<?php echo esc_attr($user->ID); ?>" <?php selected(get_option('opt_ai_story_auther'), $user->ID); ?>>
                        <?php echo esc_html($user->display_name); ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <br><br>  

            <input type="submit" name="save_settings" value="Save settings" class="button button-primary">
        </form>
        
        <form method="POST">
            <h2>Prompts</h2>
            <?php
                $raw_json = get_option('ai_story_prompts', '{}');
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
            <textarea name="ai_story_prompts" rows="10" style="width: 100%;"><?php echo esc_textarea($formatted_json); ?></textarea>
            <br><br>
            <input type="submit" name="save_prompts" value="Save Prompts" class="button button-primary">


        </form>
    </div>


    <hr>
<?php
    if (isset($_POST['generate_ai_story'])) {
        $results = generate_ai_story();
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
    <h2>Manually Generate Storeis</h2>
    <form method="POST">
        <input type="submit" name="generate_ai_story" value="Generate Stories now Now" class="button button-secondary">
    </form>
    </div>

    <?php

}

