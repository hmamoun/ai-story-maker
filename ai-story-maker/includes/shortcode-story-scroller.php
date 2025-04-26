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
if (!defined('ABSPATH')) exit;
function aistma_scrolling_bar() {
    $query = new WP_Query([
        'post_type'      => 'post',
        'posts_per_page' => 5, // Change as needed
        'order'          => 'DESC',
        'orderby'        => 'date'
    ]);
    if ($query->have_posts()) {
        ob_start();
        echo '<div class="aistma-story-scroller">';
        echo '<div class="aistma-story-items">';
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
add_shortcode('aistma_scroller', 'aistma_scrolling_bar');
    
    function aistma_enqueue_style() {
        $css_url = AI_STORY_MAKER_URL. 'public/css/aistma-style.css';
        wp_enqueue_style('aistma-story-scroller', $css_url,
        [],
        filemtime(AI_STORY_MAKER_PATH . 'public/css/aistma-style.css') // Versioning
    );
    }
    add_action('wp_enqueue_scripts', 'aistma_enqueue_style',99);
    