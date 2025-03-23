<?php
/*
Template Name: Story Template
Template Post Type: post
 
This plugin is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.
 */
// Get the post data
if (!defined('ABSPATH')) exit;
get_header();
?>
<main class="ai-story-container">
    <article class="ai-story-article">
        <header class="ai-story-header">
            <h1><?php the_title(); ?></h1>
            <p class="ai-story-meta">Published on <?php echo get_the_date();?></p>
        </header>
        <section class="ai-story-content">
            <?php the_content(); ?>
        </section>
        <?php
        // Retrieve references from post meta
        $references = get_post_meta(get_the_ID(), 'ai_story_sources', true);
        $references = json_decode($references, true); // Decode JSON if stored as JSON
        ?>
        <?php if (!empty($references) && is_array($references)) : ?>
            <section class="ai-news-references">
                <h2>References</h2>
                <ul>
                    <?php foreach ($references as $ref) : ?>
                        <li><a href="<?php echo esc_url($ref['link']); ?>" target="_blank">
                            <?php echo esc_html($ref['title']); ?>
                        </a></li>
                    <?php endforeach; ?>
                </ul>
            </section>
        <?php endif; ?>
    </article>

    <!-- Sidebar for Other News and Search -->
    <aside class="ai-story-sidebar">
    <p><a href="<?php echo esc_url(home_url()); ?>">
<?php
// show the icon and the title of the website, add an anchro tag to the home page
$custom_logo_id = get_theme_mod('custom_logo');
$logo = wp_get_attachment_image_src($custom_logo_id, 'full');
if (has_custom_logo()) {
    echo '<img src="' . esc_url($logo[0]) . '" alt="' . get_bloginfo('name') . '">';
} else {
    echo get_bloginfo('name');
}

?>

    </a></p> 
        <form role="search" method="get" class="search-form" onsubmit="return false;">
            <input type="search" class="search-field" placeholder="Search stories..." value="<?php echo get_search_query(); ?>" name="s" id="ai-story-search">
            <input type="hidden" id="search_nonce" value="<?php echo esc_html(wp_create_nonce('search_nonce')); ?>">
            <button type="button" id="ai-story-search-btn">Search</button>
        </form>

        <section class="ai-story-related">
    <h2>Other News and Articles</h2>
    <ul class="ai-news-list" id="ai-story-results">
        <?php
        $search_query = isset($_GET['s']) ? sanitize_text_field(wp_unslash($_GET['s'])) : '';
        $current_post_id = get_the_ID();
        $args = array(
            'post_type'           => 'post',
            'posts_per_page'      => 6, // Fetch one extra post to account for potential exclusion
            'orderby'             => 'date',
            'order'               => 'DESC',
            'ignore_sticky_posts' => true,
            'no_found_rows'       => true,
        );

        if (!empty($search_query) && isset($_GET['search_nonce']) &&
            wp_verify_nonce(sanitize_text_field(wp_unslash($_GET['search_nonce'])), 'search_nonce')
        ) {
            $args['s'] = $search_query;
        }

        $recent_posts = new WP_Query($args);
        $displayed_posts = 0;

        if ($recent_posts->have_posts()) :
            while ($recent_posts->have_posts() && $displayed_posts < 5) : $recent_posts->the_post();
                if (get_the_ID() !== $current_post_id) {
                    $displayed_posts++;
                    ?>
                    <li><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></li>
                    <?php
                }
            endwhile;
            wp_reset_postdata();
        else :
            ?>
            <li>No results found. Please try a different search.</li>
        <?php endif; ?>
    </ul>
</section>

    </aside>
</main>

<script>
    document.getElementById('ai-story-search-btn').addEventListener('click', function() {
        let searchQuery = document.getElementById('ai-story-search').value;
        let url = new URL(window.location.href);
        url.searchParams.set('s', searchQuery);
        history.pushState(null, '', url.toString());
        location.reload();
    });
</script>

<?php 

function enqueue_story_style() {
    wp_enqueue_style(
        'story-style',
        plugin_dir_url(__FILE__) . '../public/css/story-style.css',
        array(), // No dependencies
        filemtime(plugin_dir_path(__FILE__) . '../public/css/story-style.css'), // Cache busting
        'all' // Media type
    );
}
enqueue_story_style();
//add_action('wp_enqueue_scripts', 'enqueue_story_style');
get_footer(); 
?>