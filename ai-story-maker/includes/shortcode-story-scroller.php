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
 * Shortcode for AdSense integration.
 *
 * @param array $atts Shortcode attributes.
 * @return string AdSense HTML code.
 */
function aistma_adsense_shortcode( $atts ) {
	// Default AdSense settings (hardcoded as requested)
	$adsense_client = 'ca-pub-6861474761481747';
	$adsense_slot   = '8915797913';
	
	// Parse shortcode attributes
	$atts = shortcode_atts( array(
		'client' => $adsense_client,
		'slot'   => $adsense_slot,
		'format' => 'in-article', // Default format
		'style'  => 'display:block; text-align:center;', // Default style
	), $atts );
	
	// Build AdSense code
	$adsense_code = sprintf(
		'<script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=%s" crossorigin="anonymous"></script>
		<ins class="adsbygoogle" style="%s" data-ad-layout="%s" data-ad-format="fluid" data-ad-client="%s" data-ad-slot="%s"></ins>
		<script>(adsbygoogle = window.adsbygoogle || []).push({});</script>',
		esc_attr( $atts['client'] ),
		esc_attr( $atts['style'] ),
		esc_attr( $atts['format'] ),
		esc_attr( $atts['client'] ),
		esc_attr( $atts['slot'] )
	);
	
	return $adsense_code;
}
add_shortcode( 'aistma_adsense', 'aistma_adsense_shortcode' );

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
