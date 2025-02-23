<?php
// Admin Page to Set OpenAI API Key
add_action('admin_menu', function() {
    add_menu_page('AI News Settings', 'AI News', 'manage_options', 'ai-news-settings', 'ai_news_settings_page', 'dashicons-welcome-widgets-menus', 100);
});
// Add custom columns to the admin post list
add_filter('manage_post_posts_columns', function ($columns) {
    $columns['ai_news_total_tokens'] = 'Total Tokens';
    $columns['ai_news_request_id'] = 'Request ID';
    return $columns;
});

// Populate custom columns with AI metadata
add_action('manage_post_posts_custom_column', function ($column, $post_id) {
    if ($column === 'ai_news_total_tokens') {
        echo esc_html(get_post_meta($post_id, 'ai_news_total_tokens', true) ?: 'N/A');
    } elseif ($column === 'ai_news_request_id') {
        echo esc_html(get_post_meta($post_id, 'ai_news_request_id', true) ?: 'N/A');
    }
}, 10, 2);



function ai_news_settings_page() {
    if (isset($_POST['save_api_key'])) {
        update_option('openai_api_key', sanitize_text_field($_POST['openai_api_key']));
        echo '<div class="updated"><p>✅ OpenAI API Key saved!</p></div>';
        update_option('news_api_key', sanitize_text_field($_POST['news_api_key']));
        echo '<div class="updated"><p>✅ News API Key saved!</p></div>';
        update_option('unsplash_api_key', sanitize_text_field($_POST['unsplash_api_key']));
        update_option('unsplash_api_secret', sanitize_text_field($_POST['unsplash_api_secret']));
        echo '<div class="updated"><p>✅ Unsplash API Key and secret were saved!</p></div>';
        update_option('pexels_api_key', sanitize_text_field($_POST['pexels_api_key']));
        echo '<div class="updated"><p>✅ Pexels API Key and secret were saved!</p></div>';
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

    ?>
    <div class="wrap">
        <h1>AI News Generator Settings</h1>
        <form method="POST">
            <label for="openai_api_key">OpenAI API Key:</label>
            <input type="text" name="openai_api_key" value="<?php echo esc_attr(get_option('openai_api_key')); ?>" style="width: 100%;">
            <br><br>
            <label for="news_api_key"><a href="https://newsapi.org" target="_blank">News API</a>: </label>
            <input type="text" name="news_api_key" value="<?php echo esc_attr(get_option('news_api_key')); ?>" style="width: 100%;">
            <br><br>
            Secret and key for <a href="https://unsplash.com/" target="_blank">Unsplash Developers</a>, free source for photos :
            <br>
            <label for="unsplash_api_key">Key</label>
            <input type="text" name="unsplash_api_key" value="<?php echo esc_attr(get_option('unsplash_api_key')); ?>" style="width: 40%;">
            <label for="unsplash_api_secret">Secret</label>
            <input type="text" name="unsplash_api_secret" value="<?php echo esc_attr(get_option('unsplash_api_secret')); ?>" style="width: 40%;">
            
            <br><br>
            <label for="pexels_api_key">Key</label>
            <input type="text" name="pexels_api_key" value="<?php echo esc_attr(get_option('pexels_api_key')); ?>" style="width: 100%;">
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
<?php
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
    <h2>Manually Generate AI News</h2>
    <form method="POST">
        <input type="submit" name="generate_ai_news" value="Generate AI News Now" class="button button-secondary">
    </form>
    </div>

    <?php
}
