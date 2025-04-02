<?php
/**
 * Short code to display AI-generated stories as scrolling bar at the buttom of the page.
 * 
 * Plugin Name: AI Story Maker
 * Plugin URI: https://github.com/hmamoun/ai-story-maker/wiki
 * Description: AI-powered WordPress plugin that generates engaging stories, articles, and images using Large Language Models.
 * Version: 1.0
 * Author: Hayan Mamoun
 * Author URI: https://exedotcom.ca
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: ai-story-maker
 * Domain Path: /languages
 * Requires PHP: 7.4
 * Requires at least: 5.0
 * Tested up to: 6.7

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

    
    function story_scroller_styles() {
        $css_url = plugin_dir_url(__FILE__) . '../public/css/story-style.css';
        wp_enqueue_style('story-scroller', $css_url,
        [],
        filemtime(plugin_dir_path(__FILE__) . '../public/css/story-style.css') // Versioning
    );
    }
    add_action('wp_enqueue_scripts', 'story_scroller_styles',99);
    