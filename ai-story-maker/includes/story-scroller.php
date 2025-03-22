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
function story_scrolling_bar() {
    $query = new WP_Query([
        'post_type'      => 'post',
        'posts_per_page' => 5, // Change as needed
        'order'          => 'DESC',
        'orderby'        => 'date'
    ]);
    if ($query->have_posts()) {
        ob_start();
        echo '<div class="story-scroller">';
        echo '<div class="story-items">';
        while ($query->have_posts()) {
            $query->the_post();
            echo '<div class="story-item"><a href="' . esc_url(get_permalink()) . '">' . esc_html(get_the_title()) . '</a></div>';

        }
        echo '</div>';
        echo '</div>';
        wp_reset_postdata();
        return ob_get_clean();
    }

    return '<p>No news available.</p>';
    
}
add_shortcode('story_scroller', 'story_scrolling_bar');
    /*
    The above code will create a shortcode  [story_scroller]  that will display the latest 5 posts in a scrolling bar. 
    You can add the shortcode in your theme files or in the post content. 
    Usage: 
    [story_scroller] 
    */


    // add_action('wp_head', function() {
    //     echo '<link rel="stylesheet" href="' . esc_html(plugin_dir_url(__FILE__)) . '../assets/story-style.css">';
    // });
    
    function story_scroller_styles() {
        $css_url = plugin_dir_url(__FILE__) . '../assets/story-style.css';

    
        wp_enqueue_style('story-scroller', $css_url,
        [],
        filemtime(plugin_dir_path(__FILE__) . 'css/story-scroller.css') // Versioning
    );
    }
    add_action('wp_enqueue_scripts', 'story_scroller_styles',99);
    