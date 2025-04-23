<?php
/**
 * AI Story Maker Admin Class
 *
 * @package AI_Story_Maker
 * @license GPL-2.0-or-later
 * @link https://www.gnu.org/licenses/gpl-2.0.html
 */
/*
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 */
namespace exedotcom\aistorymaker;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
/**
 * Class Admin
 * 
 * Handles the admin area and settings.
 */
class AISTMA_Admin {
    protected $aistma_prompt_editor;
    protected $aistma_api_keys;
    protected $aistma_settings_page;
    protected $aistma_log_manager;

    /**
     * Constructor registers the admin menu.
     */
    public function __construct() {
        // call the public function aistma_load_dependencies($files = []) in AISTMA_Plugin
        $files = [
                    'admin/class-ai-story-maker-prompt-editor.php',
                    'admin/class-ai-story-maker-api-keys.php',
                    'admin/class-ai-story-maker-settings-page.php',
                    'includes/class-ai-story-maker-log-management.php',
                ];
        AISTMA_Plugin::aistma_load_dependencies($files);
        add_action( 'admin_enqueue_scripts', array( $this, 'aistma_admin_enqueue_scripts' ) );
        $this->aistma_log_manager = new AISTMA_Log_Manager();
        $this->aistma_prompt_editor = new AISTMA_prompt_editor();
        $this->aistma_api_keys = new AISTMA_API_Keys();
        $this->aistma_settings_page = new AISTMA_Settings_Page();
        // Register the admin menu.
        add_action( 'admin_menu', array( $this, 'aistma_add_admin_menu' ) );
    }


    /**
     * enqueue scripts and styles
     * 
     */
    public function aistma_admin_enqueue_scripts() {
        wp_enqueue_script(
            'aistma-admin-js',
            AI_STORY_MAKER_URL . 'admin/js/admin.js',
            array( 'jquery' ),
            filemtime( AI_STORY_MAKER_PATH . 'admin/js/admin.js' ),
            true
        );
        wp_enqueue_style(
            'aistma-admin-css',
            AI_STORY_MAKER_URL . 'admin/css/admin.css',
            array(),
            filemtime( AI_STORY_MAKER_PATH . 'admin/css/admin.css' )
        );
    }
    /**
     * Registers the main and submenu pages in the admin area.
     */
    public function aistma_add_admin_menu() {
        // Main plugin settings page.
        add_menu_page(
            __( 'AI Story Maker Settings', 'ai-story-maker' ), // Page title.
            __( 'AI Story Maker', 'ai-story-maker' ),             // Menu title.
            'manage_options',                                  // Capability.
            'aistma-settings',                            // Menu slug.
            array( $this, 'aistma_render_main_page' ),                // Callback.
            'dashicons-welcome-widgets-menus', // Icon
            9                                  // Position
        );
    }

    /**
     * Renders the main admin settings page.
     */
    public function aistma_render_main_page() {
        $allowed_tabs = [ 'welcome', 'general', 'prompts', 'log' ];
        // Safe: `tab` is used only for read-only navigation, not for processing user-submitted data.
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $active_tab = isset( $_GET['tab'] ) && in_array( $_GET['tab'], $allowed_tabs, true ) ? sanitize_text_field( wp_unslash( $_GET['tab'] ) ) : 'welcome';
            
        ?>
        <div id="aistma-notice" class="notice notice-info hidden"></div>
		<h2 class="nav-tab-wrapper">
            <a href="?page=aistma-settings&tab=welcome" class="nav-tab <?php echo ( $active_tab === 'welcome' ) ? 'nav-tab-active' : ''; ?>">
				<?php esc_html_e( 'AI Story Maker', 'ai-story-maker' ); ?>
			</a>           
			<a href="?page=aistma-settings&tab=general" class="nav-tab <?php echo ( $active_tab === 'general' ) ? 'nav-tab-active' : ''; ?>">
				<?php esc_html_e( 'General Settings', 'ai-story-maker' ); ?>
			</a>
			<a href="?page=aistma-settings&tab=prompts" class="nav-tab <?php echo ( $active_tab === 'prompts' ) ? 'nav-tab-active' : ''; ?>">
				<?php esc_html_e( 'Prompts', 'ai-story-maker' ); ?>
			</a>
            <a href="?page=aistma-settings&tab=log" class="nav-tab <?php echo ( $active_tab === 'log' ) ? 'nav-tab-active' : ''; ?>">
				<?php esc_html_e( 'log', 'ai-story-maker' ); ?>
			</a>
		</h2>
		<?php

		// Process form submission for saving settings.

        if ( 'welcome' === $active_tab ) {
            include_once AI_STORY_MAKER_PATH . 'admin/templates/welcome-tab-template.php';
        } elseif ( 'general' === $active_tab ) {
            $this->aistma_settings_page->aistma_setting_page_render();
        } elseif ( 'prompts' === $active_tab ) {
            $this->aistma_prompt_editor->aistma_prompt_editor_render();
        } elseif ( 'log' === $active_tab ) {
            $this->aistma_log_manager->aistma_log_table_render();
        }
    
	}


}

// Instantiate the Admin class.
new AISTMA_Admin();