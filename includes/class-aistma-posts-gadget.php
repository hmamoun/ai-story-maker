<?php
/**
 * Posts Gadget Class for AI Story Maker
 *
 * @package AI_Story_Maker
 * @author  Hayan Mamoun
 * @license GPLv2 or later
 * @link    https://github.com/hmamoun/ai-story-maker/wiki
 * @since   2.0.1
 */

namespace exedotcom\aistorymaker;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class AISTMA_Posts_Gadget
 *
 * Handles the posts display gadget functionality with advanced filtering and display options.
 */
class AISTMA_Posts_Gadget {

    /**
     * Plugin instance
     *
     * @var AISTMA_Plugin
     */
    private $plugin;

    /**
     * Default configuration
     *
     * @var array
     */
    private $default_config = array(
        'posts_per_page' => 6,
        'show_featured_image' => true,
        'image_size' => 'medium',
        'show_excerpt' => true,
        'excerpt_length' => 20,
        'show_read_more' => true,
        'read_more_text' => 'Read More',
        'sort_by' => 'date',
        'sort_order' => 'DESC',
        'categories' => '',
        'tags' => '',
        'authors' => '',
        'date_range' => '',
        'highlight_new' => false,
        'new_post_days' => 7,
        'show_search' => true,
        'show_filters' => true,
        'layout' => 'grid', // grid or list
        'ajax_pagination' => true,
        'load_more_text' => 'Load More Posts'
    );

    /**
     * Constructor
     *
     * @param AISTMA_Plugin $plugin Plugin instance.
     */
    public function __construct( $plugin ) {
        $this->plugin = $plugin;
        $this->init_hooks();
    }

    /**
     * Initialize hooks
     */
    private function init_hooks() {
        add_action( 'init', array( $this, 'register_shortcode' ) );
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
        add_action( 'wp_ajax_aistma_posts_gadget_load', array( $this, 'ajax_load_posts' ) );
        add_action( 'wp_ajax_nopriv_aistma_posts_gadget_load', array( $this, 'ajax_load_posts' ) );
        add_action( 'wp_ajax_aistma_posts_gadget_search', array( $this, 'ajax_search_posts' ) );
        add_action( 'wp_ajax_nopriv_aistma_posts_gadget_search', array( $this, 'ajax_search_posts' ) );
    }

    /**
     * Register the posts gadget shortcode
     */
    public function register_shortcode() {
        add_shortcode( 'aistma_posts_gadget', array( $this, 'render_shortcode' ) );
    }

    /**
     * Enqueue frontend assets
     */
    public function enqueue_assets() {
        // Only enqueue on pages that use the shortcode
        global $post;
        if ( is_a( $post, 'WP_Post' ) && has_shortcode( $post->post_content, 'aistma_posts_gadget' ) ) {
            wp_enqueue_style(
                'aistma-posts-gadget',
                AISTMA_URL . 'public/css/posts-gadget.css',
                array(),
                '2.0.1'
            );

            wp_enqueue_script(
                'aistma-posts-gadget',
                AISTMA_URL . 'public/js/posts-gadget.js',
                array( 'jquery' ),
                '2.0.1',
                true
            );

            wp_localize_script( 'aistma-posts-gadget', 'aistmaPostsGadget', array(
                'ajax_url' => admin_url( 'admin-ajax.php' ),
                'nonce' => wp_create_nonce( 'aistma_posts_gadget_nonce' ),
                'strings' => array(
                    'loading' => __( 'Loading...', 'ai-story-maker' ),
                    'error' => __( 'An error occurred. Please try again.', 'ai-story-maker' ),
                    'no_posts' => __( 'No posts found.', 'ai-story-maker' ),
                    'search_placeholder' => __( 'Search posts...', 'ai-story-maker' ),
                    'filter_all' => __( 'All Posts', 'ai-story-maker' ),
                    'filter_new' => __( 'New Posts', 'ai-story-maker' ),
                    'filter_popular' => __( 'Popular', 'ai-story-maker' ),
                    'filter_recent' => __( 'Recent', 'ai-story-maker' ),
                )
            ) );
        }
    }

    /**
     * Render the posts gadget shortcode
     *
     * @param array $atts Shortcode attributes.
     * @return string HTML output.
     */
    public function render_shortcode( $atts ) {
        $config = shortcode_atts( $this->default_config, $atts );
        
        // Sanitize and validate configuration
        $config = $this->sanitize_config( $config );

        // Generate unique ID for this gadget instance
        $gadget_id = 'aistma-posts-gadget-' . uniqid();

        // Get initial posts
        $posts_data = $this->get_posts( $config );

        // Start output buffering
        ob_start();
        ?>
        <div id="<?php echo esc_attr( $gadget_id ); ?>" class="aistma-posts-gadget" data-config="<?php echo esc_attr( wp_json_encode( $config ) ); ?>">
            
            <?php if ( $config['show_search'] || $config['show_filters'] ) : ?>
            <div class="aistma-posts-controls">
                
                <?php if ( $config['show_search'] ) : ?>
                <div class="aistma-search-box">
                    <input type="text" 
                           class="aistma-posts-search" 
                           placeholder="<?php echo esc_attr( $this->get_localized_string( 'search_placeholder' ) ); ?>"
                           data-gadget-id="<?php echo esc_attr( $gadget_id ); ?>">
                    <button type="button" class="aistma-search-btn">
                        <span class="dashicons dashicons-search"></span>
                    </button>
                </div>
                <?php endif; ?>

                <?php if ( $config['show_filters'] ) : ?>
                <div class="aistma-posts-filters">
                    <button type="button" class="aistma-filter-btn active" data-filter="all">
                        <?php echo esc_html( $this->get_localized_string( 'filter_all' ) ); ?>
                    </button>
                    <button type="button" class="aistma-filter-btn" data-filter="new">
                        <?php echo esc_html( $this->get_localized_string( 'filter_new' ) ); ?>
                    </button>
                    <button type="button" class="aistma-filter-btn" data-filter="popular">
                        <?php echo esc_html( $this->get_localized_string( 'filter_popular' ) ); ?>
                    </button>
                    <button type="button" class="aistma-filter-btn" data-filter="recent">
                        <?php echo esc_html( $this->get_localized_string( 'filter_recent' ) ); ?>
                    </button>
                </div>
                <?php endif; ?>

            </div>
            <?php endif; ?>

            <div class="aistma-posts-container <?php echo esc_attr( 'layout-' . $config['layout'] ); ?>">
                <div class="aistma-posts-grid">
                    <?php echo wp_kses_post( $this->render_posts( $posts_data['posts'], $config ) ); ?>
                </div>
                
                <?php if ( $posts_data['has_more'] && $config['ajax_pagination'] ) : ?>
                <div class="aistma-posts-pagination">
                    <button type="button" class="aistma-load-more-btn" data-page="2" data-gadget-id="<?php echo esc_attr( $gadget_id ); ?>">
                        <?php echo esc_html( $config['load_more_text'] ); ?>
                    </button>
                </div>
                <?php endif; ?>

                <div class="aistma-posts-loading" style="display: none;">
                    <div class="aistma-spinner"></div>
                    <span><?php echo esc_html( $this->get_localized_string( 'loading' ) ); ?></span>
                </div>

                <div class="aistma-posts-error" style="display: none;">
                    <p><?php echo esc_html( $this->get_localized_string( 'error' ) ); ?></p>
                </div>

                <div class="aistma-posts-empty" style="display: none;">
                    <p><?php echo esc_html( $this->get_localized_string( 'no_posts' ) ); ?></p>
                </div>
            </div>
        </div>
        <?php

        return ob_get_clean();
    }

    /**
     * Get posts based on configuration
     *
     * @param array $config Configuration array.
     * @return array Posts data with pagination info.
     */
    private function get_posts( $config ) {
        $args = array(
            'post_type' => 'post',
            'post_status' => 'publish',
            'posts_per_page' => intval( $config['posts_per_page'] ),
            'paged' => 1,
            // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Required for posts gadget filtering functionality
            'meta_query' => array(),
            // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query -- Required for posts gadget taxonomy filtering
            'tax_query' => array(),
        );

        // Apply sorting
        $this->apply_sorting( $args, $config );

        // Apply filters
        $this->apply_filters( $args, $config );

        // Apply date range
        $this->apply_date_range( $args, $config );

        $query = new \WP_Query( $args );
        $posts = $query->posts;

        return array(
            'posts' => $posts,
            'has_more' => $query->max_num_pages > 1,
            'total_pages' => $query->max_num_pages,
            'found_posts' => $query->found_posts
        );
    }

    /**
     * Apply sorting to query arguments
     *
     * @param array $args Query arguments.
     * @param array $config Configuration.
     */
    private function apply_sorting( &$args, $config ) {
        switch ( $config['sort_by'] ) {
            case 'popular':
                $args['orderby'] = 'comment_count';
                break;
            case 'random':
                $args['orderby'] = 'rand';
                break;
            case 'title':
                $args['orderby'] = 'title';
                break;
            default: // date
                $args['orderby'] = 'date';
                break;
        }

        $args['order'] = strtoupper( $config['sort_order'] );
    }

    /**
     * Apply filters to query arguments
     *
     * @param array $args Query arguments.
     * @param array $config Configuration.
     */
    private function apply_filters( &$args, $config ) {
		// Ensure tax_query is initialized to an array to avoid undefined index warnings
		if ( ! isset( $args['tax_query'] ) || ! is_array( $args['tax_query'] ) ) {
			// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query -- Required for posts gadget category/tag filtering
			$args['tax_query'] = array();
		}
        // Categories
        if ( ! empty( $config['categories'] ) ) {
            $categories = array_map( 'intval', explode( ',', $config['categories'] ) );
            $args['tax_query'][] = array(
                'taxonomy' => 'category',
                'field' => 'term_id',
                'terms' => $categories,
            );
        }

        // Tags
        if ( ! empty( $config['tags'] ) ) {
            $tags = array_map( 'intval', explode( ',', $config['tags'] ) );
            $args['tax_query'][] = array(
                'taxonomy' => 'post_tag',
                'field' => 'term_id',
                'terms' => $tags,
            );
        }

        // Authors
        if ( ! empty( $config['authors'] ) ) {
            $authors = array_map( 'intval', explode( ',', $config['authors'] ) );
            $args['author__in'] = $authors;
        }

        // Handle multiple tax queries
        if ( count( $args['tax_query'] ) > 1 ) {
            $args['tax_query']['relation'] = 'AND';
        }
    }

    /**
     * Apply date range filter
     *
     * @param array $args Query arguments.
     * @param array $config Configuration.
     */
    private function apply_date_range( &$args, $config ) {
        if ( empty( $config['date_range'] ) ) {
            return;
        }

        $date_query = array();

        switch ( $config['date_range'] ) {
            case 'today':
                $date_query = array(
                    'after' => 'today',
                );
                break;
            case 'week':
                $date_query = array(
                    'after' => '1 week ago',
                );
                break;
            case 'month':
                $date_query = array(
                    'after' => '1 month ago',
                );
                break;
            case 'year':
                $date_query = array(
                    'after' => '1 year ago',
                );
                break;
        }

        if ( ! empty( $date_query ) ) {
            $args['date_query'] = array( $date_query );
        }
    }

    /**
     * Render posts HTML
     *
     * @param array $posts Array of WP_Post objects.
     * @param array $config Configuration.
     * @return string HTML output.
     */
    private function render_posts( $posts, $config ) {
        if ( empty( $posts ) ) {
            return '<div class="aistma-no-posts">' . esc_html( $this->get_localized_string( 'no_posts' ) ) . '</div>';
        }

        $output = '';
        $new_post_days = intval( $config['new_post_days'] );

        foreach ( $posts as $post ) {
            $is_new = $config['highlight_new'] && $this->is_new_post( $post, $new_post_days );
            $output .= $this->render_single_post( $post, $config, $is_new );
        }

        return $output;
    }

    /**
     * Render single post HTML
     *
     * @param WP_Post $post Post object.
     * @param array   $config Configuration.
     * @param bool    $is_new Whether this is a new post.
     * @return string HTML output.
     */
    private function render_single_post( $post, $config, $is_new = false ) {
        $post_classes = array( 'aistma-post-item' );
        if ( $is_new ) {
            $post_classes[] = 'aistma-post-new';
        }

        $output = '<article class="' . implode( ' ', $post_classes ) . '">';

        // Featured image
        if ( $config['show_featured_image'] ) {
            $thumbnail = get_the_post_thumbnail( $post->ID, $config['image_size'], array(
                'class' => 'aistma-post-thumbnail',
                'loading' => 'lazy'
            ) );
            
            if ( $thumbnail ) {
                $output .= '<div class="aistma-post-image">';
                $output .= '<a href="' . esc_url( get_permalink( $post->ID ) ) . '">';
                $output .= $thumbnail;
                $output .= '</a>';
                if ( $is_new ) {
                    $output .= '<span class="aistma-new-badge">' . __( 'New', 'ai-story-maker' ) . '</span>';
                }
                $output .= '</div>';
            }
        }

        // Post content
        $output .= '<div class="aistma-post-content">';
        
        // Title
        $output .= '<h3 class="aistma-post-title">';
        $output .= '<a href="' . esc_url( get_permalink( $post->ID ) ) . '">';
        $output .= esc_html( get_the_title( $post->ID ) );
        $output .= '</a>';
        $output .= '</h3>';

        // Excerpt
        if ( $config['show_excerpt'] ) {
            $excerpt = $this->get_post_excerpt( $post, $config['excerpt_length'] );
            if ( $excerpt ) {
                $output .= '<div class="aistma-post-excerpt">';
                $output .= wp_kses_post( $excerpt );
                $output .= '</div>';
            }
        }

        // Read more link
        if ( $config['show_read_more'] ) {
            $output .= '<div class="aistma-post-meta">';
            $output .= '<a href="' . esc_url( get_permalink( $post->ID ) ) . '" class="aistma-read-more">';
            $output .= esc_html( $config['read_more_text'] );
            $output .= '</a>';
            $output .= '</div>';
        }

        $output .= '</div>';
        $output .= '</article>';

        return $output;
    }

    /**
     * Get post excerpt
     *
     * @param WP_Post $post Post object.
     * @param int     $length Excerpt length.
     * @return string Post excerpt.
     */
    private function get_post_excerpt( $post, $length ) {
        if ( ! empty( $post->post_excerpt ) ) {
            return wp_trim_words( $post->post_excerpt, $length, '...' );
        }

        $content = strip_shortcodes( $post->post_content );
        $content = wp_strip_all_tags( $content );
        
        return wp_trim_words( $content, $length, '...' );
    }

    /**
     * Check if post is new
     *
     * @param WP_Post $post Post object.
     * @param int     $days Number of days to consider as "new".
     * @return bool Whether post is new.
     */
    private function is_new_post( $post, $days ) {
        $post_date = strtotime( $post->post_date );
        $cutoff_date = strtotime( "-{$days} days" );
        
        return $post_date > $cutoff_date;
    }

    /**
     * AJAX handler for loading more posts
     */
    public function ajax_load_posts() {
        check_ajax_referer( 'aistma_posts_gadget_nonce', 'nonce' );

        $page = intval( $_POST['page'] ?? 1 );
        $config_raw = isset( $_POST['config'] ) ? sanitize_text_field( wp_unslash( $_POST['config'] ) ) : '{}';
        $config = json_decode( stripslashes( $config_raw ), true );
        $config = array_merge( $this->default_config, $config );

        $config['posts_per_page'] = intval( $config['posts_per_page'] );
        $page = max( 1, $page );

        $args = array(
            'post_type' => 'post',
            'post_status' => 'publish',
            'posts_per_page' => $config['posts_per_page'],
            'paged' => $page,
        );

        $this->apply_sorting( $args, $config );
        $this->apply_filters( $args, $config );
        $this->apply_date_range( $args, $config );

        $query = new \WP_Query( $args );
        $posts = $query->posts;

        $response = array(
            'success' => true,
            'posts' => $this->render_posts( $posts, $config ),
            'has_more' => $query->max_num_pages > $page,
            'next_page' => $page + 1,
        );

        wp_send_json( $response );
    }

    /**
     * AJAX handler for searching posts
     */
    public function ajax_search_posts() {
        check_ajax_referer( 'aistma_posts_gadget_nonce', 'nonce' );

        $search_term = sanitize_text_field( wp_unslash( $_POST['search'] ?? '' ) );
        $config_raw = isset( $_POST['config'] ) ? sanitize_text_field( wp_unslash( $_POST['config'] ) ) : '{}';
        $config = json_decode( stripslashes( $config_raw ), true );
        $config = array_merge( $this->default_config, $config );

        if ( empty( $search_term ) ) {
            $posts_data = $this->get_posts( $config );
        } else {
            $args = array(
                'post_type' => 'post',
                'post_status' => 'publish',
                'posts_per_page' => intval( $config['posts_per_page'] ),
                's' => $search_term,
            );

            $this->apply_sorting( $args, $config );
            $this->apply_filters( $args, $config );
            $this->apply_date_range( $args, $config );

            $query = new \WP_Query( $args );
            $posts_data = array(
                'posts' => $query->posts,
                'has_more' => false, // No pagination for search results
                'total_pages' => 1,
                'found_posts' => $query->found_posts
            );
        }

        $response = array(
            'success' => true,
            'posts' => $this->render_posts( $posts_data['posts'], $config ),
            'has_more' => $posts_data['has_more'],
        );

        wp_send_json( $response );
    }

    /**
     * Sanitize configuration
     *
     * @param array $config Configuration array.
     * @return array Sanitized configuration.
     */
    private function sanitize_config( $config ) {
        $sanitized = array();

        foreach ( $this->default_config as $key => $default_value ) {
            if ( ! isset( $config[ $key ] ) ) {
                $sanitized[ $key ] = $default_value;
                continue;
            }

            switch ( $key ) {
                case 'posts_per_page':
                case 'excerpt_length':
                case 'new_post_days':
                    $sanitized[ $key ] = max( 1, intval( $config[ $key ] ) );
                    break;
                case 'image_size':
                    $allowed_sizes = array( 'thumbnail', 'medium', 'large', 'full' );
                    $sanitized[ $key ] = in_array( $config[ $key ], $allowed_sizes ) ? $config[ $key ] : $default_value;
                    break;
                case 'sort_by':
                    $allowed_sort = array( 'date', 'title', 'popular', 'random' );
                    $sanitized[ $key ] = in_array( $config[ $key ], $allowed_sort ) ? $config[ $key ] : $default_value;
                    break;
                case 'sort_order':
                    $sanitized[ $key ] = strtoupper( $config[ $key ] ) === 'ASC' ? 'ASC' : 'DESC';
                    break;
                case 'layout':
                    $allowed_layouts = array( 'grid', 'list' );
                    $sanitized[ $key ] = in_array( $config[ $key ], $allowed_layouts ) ? $config[ $key ] : $default_value;
                    break;
                case 'date_range':
                    $allowed_ranges = array( '', 'today', 'week', 'month', 'year' );
                    $sanitized[ $key ] = in_array( $config[ $key ], $allowed_ranges ) ? $config[ $key ] : $default_value;
                    break;
                case 'categories':
                case 'tags':
                case 'authors':
                    $sanitized[ $key ] = sanitize_text_field( $config[ $key ] );
                    break;
                case 'read_more_text':
                case 'load_more_text':
                    $sanitized[ $key ] = sanitize_text_field( $config[ $key ] );
                    break;
                default:
                    $sanitized[ $key ] = (bool) $config[ $key ];
                    break;
            }
        }

        return $sanitized;
    }

    /**
     * Get localized string
     *
     * @param string $key String key.
     * @return string Localized string.
     */
    private function get_localized_string( $key ) {
        $strings = array(
            'loading' => __( 'Loading...', 'ai-story-maker' ),
            'error' => __( 'An error occurred. Please try again.', 'ai-story-maker' ),
            'no_posts' => __( 'No posts found.', 'ai-story-maker' ),
            'search_placeholder' => __( 'Search posts...', 'ai-story-maker' ),
            'filter_all' => __( 'All Posts', 'ai-story-maker' ),
            'filter_new' => __( 'New Posts', 'ai-story-maker' ),
            'filter_popular' => __( 'Popular', 'ai-story-maker' ),
            'filter_recent' => __( 'Recent', 'ai-story-maker' ),
        );

        return $strings[ $key ] ?? $key;
    }
}
