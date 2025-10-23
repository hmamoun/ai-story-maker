<?php
/**
 * Open Graph Meta Tags Handler for AI Story Maker
 *
 * @package AI_Story_Maker
 * @since   2.1.3
 */

namespace exedotcom\aistorymaker;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Class AISTMA_Open_Graph
 *
 * Handles Open Graph meta tags for social media sharing, particularly Facebook.
 */
class AISTMA_Open_Graph {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'wp_head', array( $this, 'add_open_graph_meta_tags' ), 1 );
	}

	/**
	 * Add Open Graph meta tags to the head section.
	 */
	public function add_open_graph_meta_tags() {
		// Only add meta tags on single posts
		if ( ! is_single() ) {
			return;
		}

		global $post;
		if ( ! $post ) {
			return;
		}

		// Get post data
		$post_id = $post->ID;
		$post_title = get_the_title( $post_id );
		$post_url = get_permalink( $post_id );
		$post_excerpt = $this->get_post_excerpt( $post );
		$site_name = get_bloginfo( 'name' );
		$site_url = home_url();

		// Get the image URL (featured image or first image in content)
		$image_url = $this->get_post_image_url( $post_id );

		// Basic Open Graph tags
		echo '<meta property="og:type" content="article" />' . "\n";
		echo '<meta property="og:title" content="' . esc_attr( $post_title ) . '" />' . "\n";
		echo '<meta property="og:url" content="' . esc_url( $post_url ) . '" />' . "\n";
		echo '<meta property="og:site_name" content="' . esc_attr( $site_name ) . '" />' . "\n";

		// Description
		if ( $post_excerpt ) {
			echo '<meta property="og:description" content="' . esc_attr( $post_excerpt ) . '" />' . "\n";
		}

		// Image
		if ( $image_url ) {
			echo '<meta property="og:image" content="' . esc_url( $image_url ) . '" />' . "\n";
			echo '<meta property="og:image:width" content="1200" />' . "\n";
			echo '<meta property="og:image:height" content="630" />' . "\n";
			echo '<meta property="og:image:type" content="image/jpeg" />' . "\n";
		}

		// Article specific tags
		echo '<meta property="article:published_time" content="' . esc_attr( get_the_date( 'c', $post_id ) ) . '" />' . "\n";
		echo '<meta property="article:modified_time" content="' . esc_attr( get_the_modified_date( 'c', $post_id ) ) . '" />' . "\n";
		echo '<meta property="article:author" content="' . esc_attr( get_the_author_meta( 'display_name', $post->post_author ) ) . '" />' . "\n";

		// Add post tags as article:tag
		$tags = get_the_tags( $post_id );
		if ( $tags && ! is_wp_error( $tags ) ) {
			foreach ( $tags as $tag ) {
				echo '<meta property="article:tag" content="' . esc_attr( $tag->name ) . '" />' . "\n";
			}
		}

		// Twitter Card tags for better Twitter sharing
		echo '<meta name="twitter:card" content="summary_large_image" />' . "\n";
		echo '<meta name="twitter:title" content="' . esc_attr( $post_title ) . '" />' . "\n";
		if ( $post_excerpt ) {
			echo '<meta name="twitter:description" content="' . esc_attr( $post_excerpt ) . '" />' . "\n";
		}
		if ( $image_url ) {
			echo '<meta name="twitter:image" content="' . esc_url( $image_url ) . '" />' . "\n";
		}

		// Additional meta tags for better SEO
		echo '<meta name="description" content="' . esc_attr( $post_excerpt ) . '" />' . "\n";
	}

	/**
	 * Get post excerpt or generate one from content.
	 *
	 * @param WP_Post $post The post object.
	 * @return string The excerpt.
	 */
	private function get_post_excerpt( $post ) {
		// Use post excerpt if available
		if ( ! empty( $post->post_excerpt ) ) {
			return wp_trim_words( $post->post_excerpt, 30, '...' );
		}

		// Generate excerpt from content
		$content = strip_shortcodes( $post->post_content );
		$content = wp_strip_all_tags( $content );
		$content = wp_trim_words( $content, 30, '...' );

		return $content;
	}

	/**
	 * Get the best image URL for the post.
	 *
	 * @param int $post_id The post ID.
	 * @return string|false The image URL or false if no image found.
	 */
	private function get_post_image_url( $post_id ) {
		// First, try to get the featured image
		if ( has_post_thumbnail( $post_id ) ) {
			$thumbnail_id = get_post_thumbnail_id( $post_id );
			$image_url = wp_get_attachment_image_url( $thumbnail_id, 'large' );
			
			if ( $image_url ) {
				return $image_url;
			}
		}

		// If no featured image, look for the first image in the post content
		$post = get_post( $post_id );
		if ( ! $post ) {
			return false;
		}

		$content = $post->post_content;
		
		// Look for images in the content
		preg_match_all( '/<img[^>]+src="([^"]+)"[^>]*>/i', $content, $matches );
		
		if ( ! empty( $matches[1] ) ) {
			$first_image_url = $matches[1][0];
			
			// Convert relative URLs to absolute URLs
			if ( strpos( $first_image_url, 'http' ) !== 0 ) {
				$first_image_url = home_url( $first_image_url );
			}
			
			return $first_image_url;
		}

		// Look for images in shortcodes (like galleries)
		preg_match_all( '/\[gallery[^\]]*ids="([^"]+)"[^\]]*\]/i', $content, $gallery_matches );
		
		if ( ! empty( $gallery_matches[1] ) ) {
			$image_ids = explode( ',', $gallery_matches[1][0] );
			$first_image_id = trim( $image_ids[0] );
			
			if ( $first_image_id ) {
				$image_url = wp_get_attachment_image_url( $first_image_id, 'large' );
				if ( $image_url ) {
					return $image_url;
				}
			}
		}

		// Fallback: try to get the site logo or a default image
		$custom_logo_id = get_theme_mod( 'custom_logo' );
		if ( $custom_logo_id ) {
			$logo_url = wp_get_attachment_image_url( $custom_logo_id, 'full' );
			if ( $logo_url ) {
				return $logo_url;
			}
		}

		// Last resort: return false (no image found)
		return false;
	}
}
