<?php
/**
 * AI Content Editor Integration
 *
 * Integrates AI-powered content editing capabilities into both Block Editor and Classic Editor.
 * Provides a sidebar panel for text improvement and image insertion.
 *
 * @package AI_Story_Maker
 */

namespace exedotcom\aistorymaker;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class AISTMA_Content_Editor
 */
class AISTMA_Content_Editor {

    /**
     * Constructor
     */
    public function __construct() {
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_editor_assets' ] );
        add_action( 'enqueue_block_editor_assets', [ $this, 'enqueue_block_editor_assets' ] );
        add_action( 'add_meta_boxes', [ $this, 'add_classic_editor_meta_box' ] );
    }

    /**
     * Enqueue assets for both editors
     *
     * @param string $hook Current admin page hook
     */
    public function enqueue_editor_assets( $hook ) {
        // Only load on post editor pages
        if ( ! in_array( $hook, [ 'post.php', 'post-new.php' ], true ) ) {
            return;
        }

        // Enqueue CSS
        wp_enqueue_style(
            'aistma-content-editor-css',
            AISTMA_URL . 'admin/css/content-editor.css',
            [],
            filemtime( AISTMA_PATH . 'admin/css/content-editor.css' )
        );

        // Enqueue JavaScript for Classic Editor
        wp_enqueue_script(
            'aistma-content-editor-js',
            AISTMA_URL . 'admin/js/content-editor.js',
            [ 'jquery' ],
            filemtime( AISTMA_PATH . 'admin/js/content-editor.js' ),
            true
        );

        // Localize script
        wp_localize_script( 'aistma-content-editor-js', 'aistmaContentEditor', [
            'ajaxurl' => admin_url( 'admin-ajax.php' ),
            'nonce' => wp_create_nonce( 'aistma_content_editor_nonce' ),
            'editorType' => $this->detect_editor_type(),
        ] );
    }

    /**
     * Enqueue assets specifically for Block Editor
     */
    public function enqueue_block_editor_assets() {
        // Check if the JavaScript file exists
        $js_file = AISTMA_PATH . 'admin/js/block-editor-integration.js';
        if ( ! file_exists( $js_file ) ) {
            error_log( 'AI Content Editor: JavaScript file not found: ' . $js_file );
            return;
        }

        wp_enqueue_script(
            'aistma-block-editor-js',
            AISTMA_URL . 'admin/js/block-editor-integration.js',
            [ 'wp-plugins', 'wp-edit-post', 'wp-element', 'wp-components', 'wp-data', 'wp-compose' ],
            filemtime( AISTMA_PATH . 'admin/js/block-editor-integration.js' ),
            true
        );

        wp_localize_script( 'aistma-block-editor-js', 'aistmaBlockEditor', [
            'ajaxurl' => admin_url( 'admin-ajax.php' ),
            'nonce' => wp_create_nonce( 'aistma_content_editor_nonce' ),
        ] );
    }

    /**
     * Add meta box for Classic Editor
     *
     * @param string $post_type Current post type
     */
    public function add_classic_editor_meta_box( $post_type ) {
        if ( ! in_array( $post_type, [ 'post', 'page' ], true ) ) {
            return;
        }

        add_meta_box(
            'aistma-content-editor',
            'AI Content Editor',
            [ $this, 'render_classic_editor_panel' ],
            $post_type,
            'side',
            'high'
        );
    }

    /**
     * Render Classic Editor panel
     *
     * @param \WP_Post $post Current post object
     */
    public function render_classic_editor_panel( $post ) {
        ?>
        <div id="aistma-content-editor-panel">
            <div class="aistma-editor-status">
                <p class="status-message">
                    <span class="dashicons dashicons-edit"></span>
                    Select text in the editor to start improving with AI
                </p>
            </div>

            <div class="aistma-editor-selection" style="display: none;">
                <h4>Selected Text:</h4>
                <div class="selected-text-preview"></div>
                
                <h4>Improvement Instructions:</h4>
                <textarea id="aistma-improvement-prompt" 
                          placeholder="Describe how you want to improve this text..." 
                          rows="3"></textarea>
                
                <div class="operation-type-selection">
                    <label>
                        <input type="radio" name="operation_type" value="text_improve" checked>
                        Improve Text
                    </label>
                    <label>
                        <input type="radio" name="operation_type" value="image_insert">
                        Add Image
                    </label>
                    <label>
                        <input type="radio" name="operation_type" value="image_replace">
                        Replace with Image
                    </label>
                </div>
                
                <div class="aistma-editor-actions">
                    <button type="button" id="aistma-improve-content" class="button button-primary">
                        <span class="button-text">Improve Content</span>
                        <span class="spinner" style="display: none;"></span>
                    </button>
                    <button type="button" id="aistma-clear-selection" class="button">
                        Clear Selection
                    </button>
                </div>
            </div>

            <div class="aistma-editor-result" style="display: none;">
                <h4>Improved Content:</h4>
                <div class="result-preview"></div>
                <div class="result-actions">
                    <button type="button" id="aistma-apply-result" class="button button-primary">
                        Apply Changes
                    </button>
                    <button type="button" id="aistma-discard-result" class="button">
                        Discard
                    </button>
                </div>
            </div>

            <div class="aistma-editor-error" style="display: none;">
                <p class="error-message"></p>
                <button type="button" id="aistma-dismiss-error" class="button">
                    Dismiss
                </button>
            </div>
        </div>
        <?php
    }

    /**
     * Detect editor type
     *
     * @return string Editor type (block or classic)
     */
    private function detect_editor_type() {
        global $current_screen;
        
        if ( ! $current_screen ) {
            return 'classic';
        }

        // Check if Block Editor is enabled for this post type
        if ( method_exists( $current_screen, 'is_block_editor' ) && $current_screen->is_block_editor() ) {
            return 'block';
        }

        // Fallback: check if classic editor is forced
        if ( function_exists( 'classic_editor_replace' ) ) {
            return 'classic';
        }

        // Default to block editor for WordPress 5.0+
        return 'block';
    }
}
