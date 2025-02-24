<?php
function maybe_generate_posts() {
    // Query the latest published post
    $args = array(
        'posts_per_page' => 1,
        'post_status'    => 'publish',
        'orderby'        => 'date',
        'order'          => 'DESC',
    );
    $latest_posts = get_posts( $args );

    // If no posts are found, run generate_posts()
    if ( empty( $latest_posts ) ) {
        generate_posts();
        return;
    }
    
    // Convert the post date to a timestamp
    $latest_post_date = strtotime( $latest_posts[0]->post_date );
    
    // Check if the latest post is more than one day old (using DAY_IN_SECONDS constant)
    if ( ( time() - $latest_post_date ) > DAY_IN_SECONDS ) {
        generate_ai_news();
    }
}
add_action( 'init', 'maybe_generate_posts' );