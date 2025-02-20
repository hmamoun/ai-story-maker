<?php
/* Template Name: AI News Page */
get_header(); ?>

<h1>Latest News</h1>
<?php
$query = new WP_Query(['category_name' => 'Immigration News', 'posts_per_page' => 10]);
if ($query->have_posts()) {
    while ($query->have_posts()) {
        $query->the_post();
        echo '<h2><a href="' . get_permalink() . '">' . get_the_title() . '</a></h2>';
        echo '<p>' . get_the_excerpt() . '</p>';
    }
}
wp_reset_postdata();
?>
<?php get_footer(); ?>
