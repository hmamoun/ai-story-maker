<?php
/**
 * Template for the AI Story Maker post.
 *
 * @package AI_Story_Maker
 * @since   0.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

wp_head();

/**
 * Enqueue the story style.
 *
 * @return void
 */
function aistma_enqueue_story_style() {
	wp_enqueue_style(
		'story-style',
		AISTMA_URL . 'css/aistma-style.css',
		array(), // No dependencies.
		filemtime(AISTMA_PATH . 'public/css/aistma-style.css'),
		'all' // Media type.
	);
}
//aistma_enqueue_story_style();
?>
<?php
// Log the view early in the template lifecycle.
if ( class_exists( '\\exedotcom\\aistorymaker\\AISTMA_Traffic_Logger' ) ) {
    exedotcom\aistorymaker\AISTMA_Traffic_Logger::log_post_view( get_the_ID() );
}
?>
<main class="ai-story-container">
	<article class="ai-story-article">
		<header class="ai-story-header">
			<h1><?php the_title(); ?></h1>
			<p class="ai-story-meta">Published on <?php echo get_the_date(); ?></p>
		</header>
		<section class="ai-story-content">
			<?php the_content(); ?>
		</section>
		<?php
		// Retrieve references from post meta.
		$references = get_post_meta( get_the_ID(), '_ai_story_maker_sources', true );
		$references = json_decode( $references, true ); // Decode JSON if stored as JSON.
		?>
		<?php if ( ! empty( $references ) && is_array( $references ) ) : ?>
			<section class="ai-news-references">
				<h2>References</h2>
				<ul>
			<?php foreach ( $references as $ref ) : ?>
						<li><a href="<?php echo esc_url( $ref['link'] ); ?>" target="_blank">
				<?php echo esc_html( $ref['title'] ); ?>
						</a></li>
			<?php endforeach; ?>
				</ul>
			</section>
		<?php endif; ?>
	</article>

	<!-- Sidebar for Other News and Search -->
	<aside class="ai-story-sidebar">
	<p><a href="<?php echo esc_url( home_url() ); ?>">
<?php
// Show the post's featured image if it exists, otherwise show the site logo/name
if ( has_post_thumbnail() ) {
	the_post_thumbnail( 'medium', array( 'class' => 'post-featured-image' ) );
} else {
	// Fallback to site logo or name
	$custom_logo_id = get_theme_mod( 'custom_logo' );
	$logo           = wp_get_attachment_image_src( $custom_logo_id, 'full' );
	if ( has_custom_logo() ) {
		echo wp_get_attachment_image( $custom_logo_id, 'full' );
	} else {
		echo esc_html( get_bloginfo( 'name' ) );
	}
}
?>

	</a></p> 
		<form role="search" method="get" class="search-form" onsubmit="return false;">
			<input type="search" class="search-field" placeholder="Search stories..." value="<?php echo get_search_query(); ?>" name="s" id="ai-story-search">
			<input type="hidden" id="search_nonce" value="<?php echo esc_html( wp_create_nonce( 'search_nonce' ) ); ?>">
			<button type="button" id="ai-story-search-btn">Search</button>
		</form>

		<section class="ai-story-related">
	<h2>Other News and Articles</h2>
	<ul class="ai-news-list" id="ai-story-results">
		<?php
		$search_query    = isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '';
		$current_post_id = get_the_ID();
		$args            = array(
			'post_type'           => 'post',
			'posts_per_page'      => 6, // Fetch one extra post to account for potential exclusion.
			'orderby'             => 'date',
			'order'               => 'DESC',
			'ignore_sticky_posts' => true,
			'no_found_rows'       => true,
		);

		if ( ! empty( $search_query ) && isset( $_GET['search_nonce'] )
			&& wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['search_nonce'] ) ), 'search_nonce' )
		) {
			$args['s'] = $search_query;
		}

		$recent_posts    = new WP_Query( $args );
		$displayed_posts = 0;

		if ( $recent_posts->have_posts() ) :
			while ( $recent_posts->have_posts() && $displayed_posts < 5 ) :
				$recent_posts->the_post();
				if ( get_the_ID() !== $current_post_id ) {
						++$displayed_posts;
					?>
					<li><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></li>
					<?php
				}
			endwhile;
			wp_reset_postdata();
		endif;

		if ( 0 === $displayed_posts ) :
			?>
			<li>No other stories found.</li>
		<?php endif; ?>
	</ul>
</section>

	</aside>
</main>

<?php
// Enqueue the search script.
wp_enqueue_script(
	'aistma-search-script',
	AISTMA_URL . 'public/js/search.js',
	array(),
	filemtime( AISTMA_PATH . 'public/js/search.js' ),
	true
);
?>
<footer class="ai-story-maker-footer">
	<?php if ( get_option( 'aistma_show_exedotcom_attribution', 0 ) ) : ?>
	<p>
		This story is created by AI Story Maker, a plugin by <a href="https://exedotcom.ca" title="Exedotcom" rel="nofollow" style="color: inherit;">Exedotcom.ca</a>
	</p>
	<?php endif; ?>
</footer>
<?php
wp_footer();
