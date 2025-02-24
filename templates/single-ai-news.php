<?php
/*
Template Name: News Template
Template Post Type: post
*/

// Get the post data
get_header();
?>

<main class="ai-news-container">
    <article class="ai-news-article">
        <header class="ai-news-header">
            <h1><?php the_title(); ?></h1>
            <p class="ai-news-meta">Published on <?php echo get_the_date();?></p>

        </header>
        <section class="ai-news-content">
            <?php the_content(); ?>
        </section>
        <?php
        // Retrieve references from post meta
        $references = get_post_meta(get_the_ID(), 'ai_news_sources', true);
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
    <aside class="ai-news-sidebar">
    <p><a href="<?php echo home_url(); ?>">Back to Home</a></p> 
        <form role="search" method="get" class="search-form" onsubmit="return false;">
            <input type="search" class="search-field" placeholder="Search news..." value="<?php echo get_search_query(); ?>" name="s" id="ai-news-search">
            <button type="button" id="ai-news-search-btn">Search</button>
        </form>

        <section class="ai-news-related">
            <h2>Other News and Articles</h2>
            <ul class="ai-news-list" id="ai-news-results">
                <?php
                $search_query = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';
                $args = array(
                    'post_type' => 'post',
                    'posts_per_page' => 5,
                    'post__not_in' => array(get_the_ID()),
                    'orderby' => 'date',
                    'order' => 'DESC'
                );
                if (!empty($search_query)) {
                    $args['s'] = $search_query;
                }
                $recent_posts = new WP_Query($args);
                if ($recent_posts->have_posts()) :
                    while ($recent_posts->have_posts()) : $recent_posts->the_post(); ?>
                        <li><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></li>
                    <?php endwhile;
                    wp_reset_postdata();
                else : ?>
                    <li>No results found. Please try a different search.</li>
                <?php endif; ?>
            </ul>
        </section>
    </aside>
</main>

<script>
    document.getElementById('ai-news-search-btn').addEventListener('click', function() {
        let searchQuery = document.getElementById('ai-news-search').value;
        let url = new URL(window.location.href);
        url.searchParams.set('s', searchQuery);
        history.pushState(null, '', url.toString());
        location.reload();
    });
</script>

<style>
    .ai-news-container {
        display: flex;
        max-width: 1000px;
        margin: auto;
        padding: 20px;
        background: white; /* Added background color */
    }
    .ai-news-article {
        flex: 2;
        padding-right: 20px;
    }
    .ai-news-header h1 {
        font-size: 2rem;
        color: #333;
    }
    .ai-news-meta {
        color: #777;
        font-size: 0.9rem;
    }
    .ai-news-content {
        font-size: 1.1rem;
        line-height: 1.6;
    }
    .ai-news-references {
        margin-top: 30px;
        padding: 15px;
        background: #f9f9f9;
        border-left: 5px solid #0073aa;
    }
    .ai-news-sidebar {
        flex: 1;
        background: #f4f4f4;
        padding: 15px;
        border-left: 1px solid #ddd;
    }
    .search-form {
        margin-bottom: 20px;
    }
    .search-field {
        width: 100%;
        padding: 8px;
    }
    .ai-news-related h2 {
        font-size: 1.5rem;
    }
    .ai-news-list {
        list-style: none;
        padding: 0;
    }
    .ai-news-list li {
        margin-bottom: 5px;
    }
    .ai-news-list li a {
        text-decoration: none;
        color: #0073aa;
    }
</style>

<?php get_footer(); ?>
