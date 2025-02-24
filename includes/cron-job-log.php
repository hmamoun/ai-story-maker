<?php
function maybe_generate_posts() {
    $lock = get_transient('generating_ai_news_lock');
    if ($lock) {
        if (is_array($lock) && isset($lock['time']) && (time() - $lock['time']) > DAY_IN_SECONDS) {
            delete_transient('generating_ai_news_lock');
        } else {
            return;
        }
    }
    
    $latest_posts = get_posts([
        'posts_per_page' => 1,
        'post_status'    => 'publish',
        'orderby'        => 'date',
        'order'          => 'DESC',
    ]);
    
    if (empty($latest_posts) || (time() - strtotime($latest_posts[0]->post_date)) > DAY_IN_SECONDS) {
        set_transient('generating_ai_news_lock', ['time' => time()], 300);
        try {
            generate_ai_news();
        } catch (Exception $e) {
            error_log('Error generating AI news: ' . $e->getMessage());
        } finally {
            delete_transient('generating_ai_news_lock');
        }
    }
}
add_action('init', 'maybe_generate_posts');
