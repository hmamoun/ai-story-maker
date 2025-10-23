<?php
/**
 * AI Story Maker Standalone Content Editor
 *
 * Provides a standalone AI-powered content editor that works independently
 * of the WordPress post editor, compatible with all page builders.
 *
 * @package AI_Story_Maker
 */

namespace exedotcom\aistorymaker;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class AISTMA_Standalone_Editor
 */
class AISTMA_Standalone_Editor {

    /**
     * Constructor
     */
    public function __construct() {
        // Register the page but don't show it in the menu
        add_action( 'admin_menu', [ $this, 'add_admin_menu' ] );
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_assets' ] );
        
        // AJAX handlers for standalone editor functionality
        // Note: wp_ajax_aistma_standalone_save_post is handled by AISTMA_Content_Editor_Handler
        add_action( 'wp_ajax_aistma_standalone_improve_content', [ $this, 'handle_improve_content' ] );
        add_action( 'wp_ajax_aistma_standalone_get_post_data', [ $this, 'handle_get_post_data' ] );
        add_action( 'wp_ajax_aistma_check_enhancement_eligibility', [ $this, 'handle_check_enhancement_eligibility' ] );
        add_action( 'wp_ajax_aistma_get_enhancement_data', [ $this, 'handle_get_enhancement_data' ] );
        add_action( 'admin_footer-edit.php', [ $this, 'add_inline_edit_links' ] );
    }

    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        // Register the page but hide it from the menu
        add_submenu_page(
            'edit.php',
            'AI Story Enhancer',
            null, // Hide from menu by setting menu title to null
            'edit_posts',
            'aistma-content-editor',
            [ $this, 'render_editor_page' ]
        );
    }

    /**
     * Enqueue assets
     */
    public function enqueue_assets( $hook ) {
        // Prevent duplicate loading
        static $assets_loaded = false;
        if ( $assets_loaded ) {
            return;
        }
        
        // Debug: Log the hook to see what it actually is
        
        // Check if we're on our editor page by looking at the current screen
        $current_screen = get_current_screen();
        if ( ! $current_screen || $current_screen->id !== 'edit_page_aistma-content-editor' ) {
            // Also try to detect by URL parameters
            // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Page parameter check, nonce not required
            if ( ! isset( $_GET['page'] ) || sanitize_text_field( wp_unslash( $_GET['page'] ) ) !== 'aistma-content-editor' ) {
                return;
            }
        }
        
        $assets_loaded = true;

        wp_enqueue_style(
            'aistma-standalone-editor-css',
            AISTMA_URL . 'admin/css/standalone-editor.css',
            [],
            filemtime( AISTMA_PATH . 'admin/css/standalone-editor.css' )
        );

        wp_enqueue_script(
            'aistma-standalone-editor-js',
            AISTMA_URL . 'admin/js/standalone-editor.js',
            [ 'jquery', 'wp-util' ],
            filemtime( AISTMA_PATH . 'admin/js/standalone-editor.js' ),
            true
        );

        // Get enhancement data for JavaScript
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Post ID from URL for asset loading, nonce not required
        $post_id = isset( $_GET['post_id'] ) ? absint( sanitize_text_field( wp_unslash( $_GET['post_id'] ) ) ) : 0;
        $enhancements_limit = 0;
        $enhancements_used = 0;
        $enhancements_remaining = 0;
        $enhancements_history = [];
        
        if ( $post_id > 0 ) {
            $enhancements_limit = (int) get_post_meta( $post_id, 'ai_story_maker_enhancements_limit', true );
            $enhancements_history_json = get_post_meta( $post_id, 'ai_story_maker_enhancements_history', true );
            $enhancements_history = ! empty( $enhancements_history_json ) ? json_decode( $enhancements_history_json, true ) : [];
            $enhancements_used = count( $enhancements_history );
            $enhancements_remaining = max( 0, $enhancements_limit - $enhancements_used );
        }

        wp_localize_script( 'aistma-standalone-editor-js', 'aistmaStandaloneEditor', [
            'ajaxurl' => admin_url( 'admin-ajax.php' ),
            'nonce' => wp_create_nonce( 'aistma_standalone_editor_nonce' ),
            'post_id' => $post_id,
            'enhancements_used' => $enhancements_used,
            'enhancements_limit' => $enhancements_limit,
            'enhancements_remaining' => $enhancements_remaining,
            'enhancements_history' => $enhancements_history,
            'strings' => [
                'loading' => __( 'Loading...', 'ai-story-maker' ),
                'improving' => __( 'Improving content...', 'ai-story-maker' ),
                'saving' => __( 'Saving...', 'ai-story-maker' ),
                'error' => __( 'An error occurred. Please try again.', 'ai-story-maker' ),
                'success' => __( 'Content improved! Enhancement usage tracked.', 'ai-story-maker' ),
            ]
        ] );
    }

    /**
     * Render the editor page
     */
    public function render_editor_page() {
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Post ID from URL for page rendering, nonce not required
        $post_id = isset( $_GET['post_id'] ) ? absint( sanitize_text_field( wp_unslash( $_GET['post_id'] ) ) ) : 0;
        
        if ( ! $post_id ) {
            echo '<div class="wrap"><h1>' . esc_html__( 'AI Content Editor', 'ai-story-maker' ) . '</h1>';
            echo '<p>' . esc_html__( 'Please select a post to edit.', 'ai-story-maker' ) . '</p></div>';
            return;
        }

        $post = get_post( $post_id );
        if ( ! $post ) {
            echo '<div class="wrap"><h1>' . esc_html__( 'AI Content Editor', 'ai-story-maker' ) . '</h1>';
            echo '<p>' . esc_html__( 'Post not found.', 'ai-story-maker' ) . '</p></div>';
            return;
        }

        $post_meta = get_post_meta( $post_id );
        $tags = get_the_tags( $post_id );
        $categories = get_the_category( $post_id );
        
        // Get enhancement data
        $enhancements_limit = get_post_meta( $post_id, 'ai_story_maker_enhancements_limit', true );
        $enhancements_history_json = get_post_meta( $post_id, 'ai_story_maker_enhancements_history', true );
        $enhancements_history = ! empty( $enhancements_history_json ) ? json_decode( $enhancements_history_json, true ) : [];
        $enhancements_used = count( $enhancements_history );
        $enhancements_remaining = max( 0, (int) $enhancements_limit - $enhancements_used );
        ?>
        <div class="wrap">
            <h1><?php echo esc_html( $post->post_title ); ?> - <?php esc_html_e( 'AI Story Enhancer', 'ai-story-maker' ); ?></h1>
            
            <div class="aistma-editor-container">
                <div class="aistma-editor-main">
                    <div class="aistma-editor-section">
                        <h3><?php esc_html_e( 'Post Content', 'ai-story-maker' ); ?> <span class="aistma-instruction-text"><?php esc_html_e( 'Select the text you want to improve:', 'ai-story-maker' ); ?></span></h3>
                        <div class="aistma-content-preview-container">
                            <div id="content-preview" class="aistma-content-preview" data-post-id="<?php echo esc_attr( $post_id ); ?>">
                                <?php echo wp_kses_post( $post->post_content ); ?>
                            </div>
                        </div>
                    </div>

                    <div class="aistma-editor-section">
                        <h3><?php esc_html_e( 'Post Title', 'ai-story-maker' ); ?></h3>
                        <input type="text" id="post-title" value="<?php echo esc_attr( $post->post_title ); ?>" style="width: 100%; font-size: 18px; padding: 8px;">
                    </div>

                    <div class="aistma-editor-actions">
                        <div class="enhancement-notice" style="margin-bottom: 10px; padding: 8px; background: #e7f7e7; border: 1px solid #46b450; border-radius: 3px; color: #155724;">
                            <strong><?php esc_html_e( 'Enhancement Tracking:', 'ai-story-maker' ); ?></strong> <?php esc_html_e( 'Enhancement usage is automatically tracked and saved.', 'ai-story-maker' ); ?>
                        </div>
                        <button type="button" id="save-post-btn" class="button button-primary button-large">
                            <?php esc_html_e( 'Save Changes', 'ai-story-maker' ); ?>
                        </button>
                        <a href="<?php echo esc_url( get_edit_post_link( $post_id ) ); ?>" class="button button-large">
                            <?php esc_html_e( 'Back to Post Editor', 'ai-story-maker' ); ?>
                        </a>
                    </div>
                </div>

                <div class="aistma-editor-right-sidebar">
                    
                    <!-- Enhancement Status Widget -->
                    <div class="aistma-editor-section aistma-enhancement-status">
                        <h3><?php esc_html_e( 'Enhancement Status', 'ai-story-maker' ); ?></h3>
                        <div class="enhancement-summary">
                            <div class="enhancement-counter">
                                <strong><?php
                                // translators: %1$d is the number of enhancements used, %2$s is the total limit (or ∞ for unlimited)
                                printf( esc_html__( 'Enhancements: %1$d of %2$s used', 'ai-story-maker' ), esc_html( $enhancements_used ), $enhancements_limit > 0 ? esc_html( $enhancements_limit ) : '∞' ); ?></strong>
                            </div>
                            <?php if ( $enhancements_remaining > 0 || $enhancements_limit == 0 ) : ?>
                                <div class="enhancement-remaining">
                                    <?php
                                    // translators: %s is the number of enhancements remaining (or ∞ for unlimited)
                                    printf( esc_html__( '%s remaining', 'ai-story-maker' ), $enhancements_limit > 0 ? esc_html( $enhancements_remaining ) : '∞' ); ?>
                                </div>
                            <?php else : ?>
                                <div class="enhancement-limit-reached">
                                    <?php esc_html_e( 'Limit reached', 'ai-story-maker' ); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <?php if ( ! empty( $enhancements_history ) ) : ?>
                            <div class="enhancement-history">
                                <button type="button" class="button button-secondary enhancement-history-toggle">
                                    <?php esc_html_e( 'Show Enhancements', 'ai-story-maker' ); ?>
                                </button>
                                <div class="enhancement-history-details" style="display: none;">
                                    <table class="widefat">
                                        <thead>
                                            <tr>
                                                <th><?php esc_html_e( 'Type', 'ai-story-maker' ); ?></th>
                                                <th><?php esc_html_e( 'Date', 'ai-story-maker' ); ?></th>
                                                <th><?php esc_html_e( 'Prompt', 'ai-story-maker' ); ?></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ( $enhancements_history as $enhancement ) : ?>
                                                <tr>
                                                    <td>
                                                        <span class="enhancement-type-badge enhancement-type-<?php echo esc_attr( $enhancement['type'] ); ?>">
                                                            <?php echo esc_html( ucfirst( str_replace( '_', ' ', $enhancement['type'] ) ) ); ?>
                                                        </span>
                                                    </td>
                                                    <td><?php echo esc_html( gmdate( 'M j, Y H:i', strtotime( $enhancement['date'] ) ) ); ?></td>
                                                    <td><?php echo esc_html( $enhancement['prompt_snippet'] ); ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="aistma-editor-section">
                        <h3><?php esc_html_e( 'Tags & Keywords', 'ai-story-maker' ); ?></h3>
                        <div class="aistma-tags-panel">
                            <label for="post-tags"><?php esc_html_e( 'Tags:', 'ai-story-maker' ); ?></label>
                            <textarea id="post-tags" rows="3" placeholder="<?php esc_attr_e( 'Enter tags separated by commas', 'ai-story-maker' ); ?>"><?php echo esc_textarea( implode( ', ', wp_list_pluck( $tags ?: [], 'name' ) ) ); ?></textarea>
                            
                            <button type="button" id="improve-tags-btn" class="button">
                                <?php esc_html_e( 'Improve with AI', 'ai-story-maker' ); ?>
                            </button>
                        </div>
                    </div>

                    <div class="aistma-editor-section">
                        <h3><?php esc_html_e( 'SEO & Meta', 'ai-story-maker' ); ?></h3>
                        <div class="aistma-seo-panel">
                            <label for="meta-description"><?php esc_html_e( 'Meta Description:', 'ai-story-maker' ); ?></label>
                            <textarea id="meta-description" rows="4" placeholder="<?php esc_attr_e( 'Enter meta description...', 'ai-story-maker' ); ?>"><?php echo esc_textarea( $post_meta['_yoast_wpseo_metadesc'][0] ?? '' ); ?></textarea>
                            
                            <button type="button" id="improve-seo-btn" class="button">
                                <?php esc_html_e( 'Generate with AI', 'ai-story-maker' ); ?>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- AI Improvement Popup -->
            <div id="aistma-improvement-popup" class="aistma-popup-overlay" style="display: none;">
                <div class="aistma-popup-content">
                    <div class="aistma-popup-header">
                        <h3><?php esc_html_e( 'AI Story Enhancer', 'ai-story-maker' ); ?></h3>
                        <button type="button" class="aistma-popup-close">&times;</button>
                    </div>
                    <div class="aistma-popup-body">
                        <div class="selected-text-display">
                            <h4><?php esc_html_e( 'Selected Text:', 'ai-story-maker' ); ?></h4>
                            <div id="popup-selected-text"></div>
                        </div>
                        <div class="improvement-prompt">
                            <label for="improvement-prompt"><?php esc_html_e( 'How would you like to improve this text?', 'ai-story-maker' ); ?></label>
                            <textarea id="improvement-prompt" rows="3" placeholder="<?php esc_attr_e( 'e.g., Make it more engaging, add more details, improve the tone...', 'ai-story-maker' ); ?>"></textarea>
                        </div>
                    </div>
                    <div class="aistma-popup-footer">
                        <button type="button" id="improve-selected-btn" class="button button-primary">
                            <?php esc_html_e( 'Improve', 'ai-story-maker' ); ?>
                        </button>
                        <button type="button" class="aistma-popup-cancel button">
                            <?php esc_html_e( 'Cancel', 'ai-story-maker' ); ?>
                        </button>
                    </div>
                </div>
            </div>
        </div>
        
        <script type="text/javascript">
        
        // Fallback: If assets weren't loaded, load them manually
        if (typeof aistmaStandaloneEditor === 'undefined') {
            
            // Load CSS
            var css = document.createElement('link');
            css.rel = 'stylesheet';
            css.href = '<?php echo esc_url( AISTMA_URL ); ?>admin/css/standalone-editor.css';
            document.head.appendChild(css);
            
            // Load JS
            var script = document.createElement('script');
            script.src = '<?php echo esc_url( AISTMA_URL ); ?>admin/js/standalone-editor.js';
            script.onload = function() {
                // Create localized data manually
                window.aistmaStandaloneEditor = {
                    ajaxurl: '<?php echo esc_url( admin_url('admin-ajax.php') ); ?>',
                    nonce: '<?php echo esc_js( wp_create_nonce('aistma_standalone_editor_nonce') ); ?>',
                    post_id: <?php echo esc_js( $post_id ); ?>,
                    enhancements_used: <?php echo esc_js( $enhancements_used ); ?>,
                    enhancements_limit: <?php echo esc_js( $enhancements_limit ); ?>,
                    enhancements_remaining: <?php echo esc_js( $enhancements_remaining ); ?>,
                    enhancements_history: <?php echo wp_json_encode( $enhancements_history ); ?>,
                    strings: {
                        loading: 'Loading...',
                        improving: 'Improving content...',
                        saving: 'Saving...',
                        error: 'An error occurred. Please try again.',
                        success: 'Content saved successfully!',
                        limitReached: 'Enhancement limit reached for this post'
                    }
                };
            };
            document.head.appendChild(script);
        }
        </script>
        <?php
    }

    /**
     * Add inline edit links to posts list
     */
    public function add_inline_edit_links() {
        ?>
        <script type="text/javascript">
        jQuery(document).ready(function($) {
            // Add AI Edit link to each post row
            $('.row-actions').each(function() {
                var $this = $(this);
                var postId = $this.closest('tr').attr('id').replace('post-', '');
                var postTitle = $this.closest('tr').find('.title a').text();
                
                // Check if post has enhancement meta by making AJAX call
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'aistma_check_enhancement_eligibility',
                        post_id: postId,
                        nonce: '<?php echo esc_js( wp_create_nonce( 'aistma_check_enhancement_eligibility' ) ); ?>'
                    },
                    success: function(response) {
                        if (response.success && response.data.eligible) {
                            var aiEditLink = '<span class="aistma-edit"> | <a href="<?php echo esc_url( admin_url( 'edit.php?page=aistma-content-editor&post_id=' ) ); ?>' + postId + '" title="Enhance with AI Story Enhancer">AI Story Enhancer</a></span>';
                            $this.append(aiEditLink);
                        }
                    },
                    error: function(xhr, status, error) {
                        // AJAX error checking enhancement eligibility
                    }
                });
            });
        });
        </script>
        <?php
    }

    /**
     * Handle AJAX request to improve content
     */
    public function handle_improve_content() {
        // Verify nonce
        if ( ! check_ajax_referer( 'aistma_standalone_editor_nonce', 'nonce', false ) ) {
            wp_send_json_error( 'Security check failed.' );
        }

        // Check user permissions
        if ( ! current_user_can( 'edit_posts' ) ) {
            wp_send_json_error( 'You do not have permission to perform this action.' );
        }

        // Get and sanitize request parameters
        $content = sanitize_textarea_field( wp_unslash( $_POST['content'] ?? '' ) );
        $prompt = sanitize_textarea_field( wp_unslash( $_POST['prompt'] ?? '' ) );
        $operation_type = sanitize_text_field( wp_unslash( $_POST['operation_type'] ?? 'text_improve' ) );
        $post_id = isset( $_POST['post_id'] ) ? absint( sanitize_text_field( wp_unslash( $_POST['post_id'] ) ) ) : 0;

        // Validate required parameters
        if ( empty( $content ) || empty( $prompt ) ) {
            wp_send_json_error( 'Missing required parameters: content and prompt are required.' );
        }

        try {
            // Use the existing content editor handler with post_id
            $handler = new AISTMA_Content_Editor_Handler();
            $result = $handler->handle_improve_content_standalone( $content, $prompt, $operation_type, $post_id );

            if ( $result['success'] ) {
                wp_send_json_success( $result['data'] );
            } else {
                wp_send_json_error( $result['data'] );
            }

        } catch ( \Exception $e ) {
            wp_send_json_error( 'An unexpected error occurred. Please try again.' );
        }
    }


    /**
     * Handle AJAX request to get post data
     */
    public function handle_get_post_data() {
        // Verify nonce
        if ( ! check_ajax_referer( 'aistma_standalone_editor_nonce', 'nonce', false ) ) {
            wp_send_json_error( 'Security check failed.' );
        }

        // Check user permissions
        if ( ! current_user_can( 'edit_posts' ) ) {
            wp_send_json_error( 'You do not have permission to perform this action.' );
        }

        $post_id = intval( $_POST['post_id'] ?? 0 );

        if ( ! $post_id ) {
            wp_send_json_error( 'Invalid post ID.' );
        }

        $post = get_post( $post_id );
        if ( ! $post ) {
            wp_send_json_error( 'Post not found.' );
        }

        $tags = get_the_tags( $post_id );
        $meta_description = get_post_meta( $post_id, '_yoast_wpseo_metadesc', true );

        wp_send_json_success( [
            'title' => $post->post_title,
            'content' => $post->post_content,
            'tags' => implode( ', ', wp_list_pluck( $tags ?: [], 'name' ) ),
            'meta_description' => $meta_description,
        ] );
    }

    /**
     * Handle get enhancement data AJAX request
     */
    public function handle_get_enhancement_data() {
        // Verify nonce
        $nonce = sanitize_text_field( wp_unslash( $_POST['nonce'] ?? '' ) );
        if ( ! wp_verify_nonce( $nonce, 'aistma_standalone_editor_nonce' ) ) {
            wp_send_json_error( 'Invalid nonce.' );
        }

        // Check user permissions
        if ( ! current_user_can( 'edit_posts' ) ) {
            wp_send_json_error( 'Insufficient permissions.' );
        }

        // Get post ID
        $post_id = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0;
        if ( ! $post_id ) {
            wp_send_json_error( 'Post ID is required.' );
        }

        // Get enhancement data
        $enhancements_limit = (int) get_post_meta( $post_id, 'ai_story_maker_enhancements_limit', true );
        $enhancements_history_json = get_post_meta( $post_id, 'ai_story_maker_enhancements_history', true );
        $enhancements_history = ! empty( $enhancements_history_json ) ? json_decode( $enhancements_history_json, true ) : [];
        $enhancements_used = count( $enhancements_history );
        $enhancements_remaining = max( 0, $enhancements_limit - $enhancements_used );

        wp_send_json_success( [
            'enhancements_used' => $enhancements_used,
            'enhancements_limit' => $enhancements_limit,
            'enhancements_remaining' => $enhancements_remaining,
            'enhancements_history' => $enhancements_history
        ] );
    }

    /**
     * Handle AJAX request to check enhancement eligibility
     */
    public function handle_check_enhancement_eligibility() {
        // Verify nonce
        $nonce = sanitize_text_field( wp_unslash( $_POST['nonce'] ?? '' ) );
        if ( ! wp_verify_nonce( $nonce, 'aistma_check_enhancement_eligibility' ) ) {
            wp_send_json_error( 'Invalid nonce.' );
        }

        // Check user permissions
        if ( ! current_user_can( 'edit_posts' ) ) {
            wp_send_json_error( 'Insufficient permissions.' );
        }

        // Get post ID
        $post_id = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0;
        if ( ! $post_id ) {
            wp_send_json_error( 'Post ID is required.' );
        }

        // Check if post has enhancement meta
        $enhancements_limit = get_post_meta( $post_id, 'ai_story_maker_enhancements_limit', true );
        $package_id = get_post_meta( $post_id, 'ai_story_maker_package_id', true );
        
        // Check if we have valid enhancement data
        // package_id can be 0 (first package) or any positive number
        // enhancements_limit should be > 0
        $eligible = ! empty( $enhancements_limit ) && $enhancements_limit > 0 && ( $package_id !== '' && $package_id !== null );
        
        // Debug logging
        
        wp_send_json_success( [
            'eligible' => $eligible,
            'enhancements_limit' => $enhancements_limit,
            'package_id' => $package_id
        ] );
    }

}


