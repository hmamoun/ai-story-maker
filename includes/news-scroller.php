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
function news_scrolling_bar() {



    $query = new WP_Query([
        'post_type'      => 'post',
        'posts_per_page' => 5, // Change as needed
        'order'          => 'DESC',
        'orderby'        => 'date'
    ]);
    if ($query->have_posts()) {
        ob_start();
        echo '<div class="news-scroller">';
        echo '<div class="news-items">';
        while ($query->have_posts()) {
            $query->the_post();
            echo '<div class="news-item"><a href="' . get_permalink() . '">' . get_the_title() . '</a></div>';
        }
        echo '</div>';
        echo '</div>';
        wp_reset_postdata();
        return ob_get_clean();
    }

    return '<p>No news available.</p>';
}
add_shortcode('news_scroller', 'news_scrolling_bar');
add_action('wp_head', function() {
    echo '<link rel="stylesheet" href="' . plugin_dir_url(__FILE__) . '../assets/news-style.css">';
});
