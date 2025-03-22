<?php
/*
 * This plugin is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 */
if (!defined('ABSPATH')) exit;
// Add the main menu item (Story Maker Settings)
add_action('admin_menu', function () {
    // Main menu item
    add_menu_page(
        'Story Maker Settings',            // Page title
        'Story Maker',                     // Menu title
        'manage_options',                  // Capability
        'story-maker-settings',            // Menu slug
        'fn_story_maker_settings_page',    // Callback function
        'dashicons-welcome-widgets-menus', // Icon
        9                                  // Position
    );

    // Add the submenu item (AI Story Logs) under the main menu
    add_submenu_page(
        'story-maker-settings',       // Parent slug
        'AI Story Logs',              // Page title
        'Story Maker Logs',           // Menu title
        'manage_options',             // Capability
        'ai-storymaker-logs',         // Menu slug
        'fn_ai_storymaker_logs_page'  // Callback function
    );
});
require_once plugin_dir_path(__FILE__) . 'admin-page-validate-keys.php';


// Add custom columns to the admin post list
add_filter('manage_post_posts_columns', function ($columns) {
    $columns['ai_story_total_tokens'] = 'Total Tokens';
    $columns['ai_story_request_id']   = 'Request ID';
    return $columns;
});

// Populate custom columns with AI metadata
add_action('manage_post_posts_custom_column', function ($column, $post_id) {
    if ($column === 'ai_story_total_tokens') {
        echo esc_html(get_post_meta($post_id, 'ai_story_total_tokens', true) ?: 'N/A');
    } elseif ($column === 'ai_story_request_id') {
        echo esc_html(get_post_meta($post_id, 'ai_story_request_id', true) ?: 'N/A');
    }
}, 10, 2);

function fn_story_maker_settings_page() {
    $active_tab = isset($_GET['tab']) ? sanitize_text_field(wp_unslash($_GET['tab'])) : 'general';
    ?>
        <h2 class="nav-tab-wrapper">
        <a href="?page=story-maker-settings&tab=general" class="nav-tab <?php echo $active_tab == 'general' ? 'nav-tab-active' : ''; ?>">General Settings</a>
        <a href="?page=story-maker-settings&tab=prompts" class="nav-tab <?php echo $active_tab == 'prompts' ? 'nav-tab-active' : ''; ?>">Prompts</a>
    </h2>
    <?php
    // Save settings
    if (isset($_POST['save_settings'])) {
        $story_maker_nonce = isset($_POST['story_maker_nonce'])
            ? sanitize_text_field(wp_unslash($_POST['story_maker_nonce']))
            : '';

        if (!$story_maker_nonce || !wp_verify_nonce($story_maker_nonce, 'save_story_maker_settings')) {
            echo '<div class="error"><p>❌ Security check failed. Please try again.</p></div>';
            ai_storymaker_log('error', '❌ Security check failed. Please try again.');
            return;
        }
        if (validate_openai_api_key(sanitize_text_field(wp_unslash($_POST['openai_api_key']))) === false) {
            echo '<div class="error"><p>❌ Invalid OpenAI API key.</p></div>';
            ai_storymaker_log('error', '❌ Invalid OpenAI API key.');
            return;
        }
        if (isset($_POST['opt_ai_storymaker_clear_log']) &&
            get_option('opt_ai_storymaker_clear_log') != sanitize_text_field(wp_unslash($_POST['opt_ai_storymaker_clear_log']))
        ) {
            wp_clear_scheduled_hook('sc_ai_storymaker_clear_log');
        }
        if (isset($_POST['opt_ai_story_scheduled_generate']) &&
            get_option('opt_ai_storymaker_clear_log') != sanitize_text_field(wp_unslash($_POST['opt_ai_story_scheduled_generate']))
        ) {
            wp_clear_scheduled_hook('sc_ai_story_scheduled_generate');
        }
              


        // Update API keys and options
        if (isset($_POST['openai_api_key'])) {
            update_option('openai_api_key', sanitize_text_field(wp_unslash($_POST['openai_api_key'])));
        }
        if (isset($_POST['unsplash_api_key'])) {
            update_option('unsplash_api_key', sanitize_text_field(wp_unslash($_POST['unsplash_api_key'])));
        }
        if (isset($_POST['unsplash_api_secret'])) {
            update_option('unsplash_api_secret', sanitize_text_field(wp_unslash($_POST['unsplash_api_secret'])));
        }
        if (isset($_POST['opt_ai_storymaker_clear_log'])) {
            update_option('opt_ai_storymaker_clear_log', sanitize_text_field(wp_unslash($_POST['opt_ai_storymaker_clear_log'])));
        }
        if (isset($_POST['opt_ai_story_scheduled_generate'])) {
            update_option('opt_ai_story_scheduled_generate', sanitize_text_field(wp_unslash($_POST['opt_ai_story_scheduled_generate'])));
        }
        if (isset($_POST['opt_ai_story_auther'])) {
            update_option('opt_ai_story_auther', intval($_POST['opt_ai_story_auther']));
        }

        echo '<div class="updated"><p>✅ Settings saved!</p></div>';
        ai_storymaker_log('success', 'Settings saved ');
    }
    if ($active_tab == 'general') {
    ?>
        <div class="wrap">
            <form method="POST" class="ai-storymaker-settings">
                <?php wp_nonce_field('save_story_maker_settings', 'story_maker_nonce'); ?>
                <h2>API Keys</h2>
                <p>
                    AI Story Maker integrates with OpenAI and Unsplash APIs to generate content and images.
                    Please enter your API keys below. Registration may be required to obtain them.
                </p>
                <label for="openai_api_key">
                    OpenAI <a href="https://platform.openai.com/" target="_blank">API</a> Key:
                </label>
                <input type="text" name="openai_api_key" placeholder="OpenAI API Key"  value="<?php echo esc_attr(get_option('openai_api_key')); ?>">
                <label for="unsplash_api_key">
                    Unsplash <a href="https://unsplash.com/developers" target="_blank">API Key and Secret</a>:
                </label>
                <div class="inline-fields">
                    <label for="unsplash_api_key">Key:</label>
                    <input type="text" name="unsplash_api_key" placeholder="Key" value="<?php echo esc_attr(get_option('unsplash_api_key')); ?>">
                    <label for="unsplash_api_secret">Secret:</label>
                    <input type="text" name="unsplash_api_secret" placeholder="Secret" value="<?php echo esc_attr(get_option('unsplash_api_secret')); ?>">
                </div>

                <h2>Story Generation Settings</h2>
                <label for="opt_ai_storymaker_clear_log">Log Retention (Days):</label>
                <p>
                    AI Story Maker maintains a
                    <a href="<?php echo admin_url('admin.php?page=ai-storymaker-logs'); ?>">detailed log</a>
                    of its activities.
                    Choose how many days to retain the logs, or set to 0 to keep them indefinitely.
                </p>
                <select name="opt_ai_storymaker_clear_log">
                    <?php for ($i = 0; $i <= 30; $i++) : ?>
                        <option value="<?php echo $i; ?>" <?php selected(get_option('opt_ai_storymaker_clear_log'), $i); ?>>
                            <?php echo $i; ?> Day(s)
                        </option>
                    <?php endfor; ?>
                </select>
                <hr>
                <label for="opt_ai_story_scheduled_generate">Generate New Stories Every (Days):</label>
                <p>
                    AI Story Maker can automatically generate new stories at regular intervals.
                    Set to 0 to disable scheduled generation.
                </p>
                <select name="opt_ai_story_scheduled_generate">
                    <?php for ($i = 0; $i <= 30; $i++) : ?>
                        <option value="<?php echo $i; ?>" <?php selected(get_option('opt_ai_story_scheduled_generate'), $i); ?>>
                            <?php echo $i; ?> Day(s)
                        </option>
                    <?php endfor; ?>
                </select>
                <hr>
                <label for="opt_ai_story_auther">Select Story Author:</label>
                <p>
                    Select the author for AI-generated stories.
                    If you need to create a new author, you can do so
                    <a href="<?php echo admin_url('user-new.php?role=author'); ?>">here</a>.
                    Ensure the role is set to "Author".
                </p>
                <select name="opt_ai_story_auther">
                    <?php
                    $users = get_users(array('role__in' => array('author', 'administrator')));
                    foreach ($users as $user) :
                    ?>
                        <option value="<?php echo esc_attr($user->ID); ?>" <?php selected(get_option('opt_ai_story_auther'), $user->ID); ?>>
                            <?php echo esc_html($user->display_name); ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <input type="submit" name="save_settings" value="Save Settings" class="button button-primary submit-button">
            </form>
        </div>
    <?php
    }elseif ($active_tab == 'prompts') {
        include 'admin-page-prompt-editor.php';

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

        $nextRun           = date_i18n(get_option('date_format') . ' ' . get_option('time_format'), wp_next_scheduled('sc_ai_story_scheduled_generate'));
        $currentServerDate = date_i18n(get_option('date_format') . ' ' . get_option('time_format'), current_time('timestamp'));
        $RemainingTime     = human_time_diff(current_time('timestamp'), wp_next_scheduled('sc_ai_story_scheduled_generate'));
        ?>

        <h2>Generate Stories</h2>
        <p>
            Stories will be generated when anyone visits the site after
            <strong><?php echo esc_html($nextRun); ?></strong>
            (<?
    }
}
