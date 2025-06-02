<?php
/**
 * Shortcode for story scroller.
 *
 * @package AI_Story_Maker
 * @author  Hayan Mamoun
 * @license GPLv2 or later
 * @link    https://github.com/hmamoun/ai-story-maker
 * @since   0.1.0
 * @version 0.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Shortcode for story scroller.
 *
 * @return string
 */
function aistma_scrolling_bar() {
	$query = new WP_Query(
		array(
			'post_type'      => 'post',
			'posts_per_page' => 5, // Change as needed.
			'order'          => 'DESC',
			'orderby'        => 'date',
		)
	);
	if ( $query->have_posts() ) {
		ob_start();
		echo '<div class="aistma-story-scroller">';
		echo '<div class="aistma-story-items">';
		while ( $query->have_posts() ) {
			$query->the_post();
			echo '<div class="story-item"><a href="' . esc_url( get_permalink() ) . '">' . esc_html( get_the_title() ) . '</a></div>';

		}
		echo '</div>';
		echo '</div>';
		wp_reset_postdata();
		return ob_get_clean();
	}

	return '<p>No news available.</p>';
}
add_shortcode( 'aistma_scroller', 'aistma_scrolling_bar' );

/**
 * Enqueue style for story scroller.
 *
 * @return void
 */
function aistma_enqueue_style() {
	$css_url = AISTMA_URL . 'public/css/aistma-style.css';
	wp_enqueue_style(
		'aistma-story-scroller',
		$css_url,
		array(),
		filemtime( AISTMA_PATH . 'public/css/aistma-style.css' ) // Versioning.
	);
}
	add_action( 'wp_enqueue_scripts', 'aistma_enqueue_style', 99 );
