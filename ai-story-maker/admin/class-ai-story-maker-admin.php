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
class Admin {
    protected $prompt_editor;
    protected $api_keys;
    protected $settings_page;
    protected $log_manager;

    /**
     * Constructor registers the admin menu.
     */
    public function __construct() {

        // Load the additional class files.


        $this->load_dependencies();
        
        // Hook the enqueue_scripts method to the admin_enqueue_scripts action.
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

        $this->log_manager = new AISTMA_Log_Manager();
        $this->prompt_editor = new Prompt_Editor();
        $this->api_keys = new API_Keys();
        $this->settings_page = new Settings_Page();
        // Register the admin menu.
        add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
    }
    /**
     * Loads required dependency files for admin pages.
     */
    private function load_dependencies() {
        $files = [
            'class-ai-story-maker-prompt-editor.php',
            'class-ai-story-maker-api-keys.php',
            'class-ai-story-maker-settings-page.php',
            '../includes/class-ai-story-maker-log-management.php',
        ];
    
        foreach ( $files as $file ) {
            $path = plugin_dir_path( __FILE__ ) . $file;
            if ( file_exists( $path ) ) {
                include_once $path;
            } else {
                $this->error_log->log( "Missing dependency file: $path" );
            }
        }
    }

    /**
     * enqueue scripts and styles
     * 
     */
    public function enqueue_scripts() {
        wp_enqueue_script(
            'ai-story-maker-admin',
            plugin_dir_url( __FILE__ ) . 'js/admin.js',
            array( 'jquery' ),
            filemtime( plugin_dir_path( __FILE__ ) . 'js/admin.js' ),
            true
        );
        wp_enqueue_style(
            'ai-story-maker-admin',
            plugin_dir_url( __FILE__ ) . 'css/admin.css',
            array(),
            filemtime( plugin_dir_path( __FILE__ ) . 'css/admin.css' )
        );
    }
    /**
     * Registers the main and submenu pages in the admin area.
     */
    public function add_admin_menu() {
        // Main plugin settings page.
        add_menu_page(
            __( 'AI Story Maker Settings', 'ai-story-maker' ), // Page title.
            __( 'AI Story Maker', 'ai-story-maker' ),             // Menu title.
            'manage_options',                                  // Capability.
            'story-maker-settings',                            // Menu slug.
            array( $this, 'render_main_page' ),                // Callback.
            'dashicons-welcome-widgets-menus', // Icon
            9                                  // Position
        );
    }

    /**
     * Renders the main admin settings page.
     */
    public function render_main_page() {
        $allowed_tabs = [ 'welcome', 'general', 'prompts', 'log' ];
        // Safe: `tab` is used only for read-only navigation, not for processing user-submitted data.
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $active_tab = isset( $_GET['tab'] ) && in_array( $_GET['tab'], $allowed_tabs, true ) ? sanitize_text_field( wp_unslash( $_GET['tab'] ) ) : 'welcome';
            
        ?>
        <div id="ai-story-maker-messages" class="notice notice-info hidden"></div>
		<h2 class="nav-tab-wrapper">
            <a href="?page=story-maker-settings&tab=welcome" class="nav-tab <?php echo ( $active_tab === 'welcome' ) ? 'nav-tab-active' : ''; ?>">
				<?php esc_html_e( 'AI Story Maker', 'ai-story-maker' ); ?>
			</a>           
			<a href="?page=story-maker-settings&tab=general" class="nav-tab <?php echo ( $active_tab === 'general' ) ? 'nav-tab-active' : ''; ?>">
				<?php esc_html_e( 'General Settings', 'ai-story-maker' ); ?>
			</a>
			<a href="?page=story-maker-settings&tab=prompts" class="nav-tab <?php echo ( $active_tab === 'prompts' ) ? 'nav-tab-active' : ''; ?>">
				<?php esc_html_e( 'Prompts', 'ai-story-maker' ); ?>
			</a>
            <a href="?page=story-maker-settings&tab=log" class="nav-tab <?php echo ( $active_tab === 'log' ) ? 'nav-tab-active' : ''; ?>">
				<?php esc_html_e( 'log', 'ai-story-maker' ); ?>
			</a>
		</h2>
		<?php

		// Process form submission for saving settings.

        if ( 'welcome' === $active_tab ) {
            include_once plugin_dir_path( __FILE__ ) . 'templates/welcome-tab-template.php';
        } elseif ( 'general' === $active_tab ) {
            $this->settings_page->render();
        } elseif ( 'prompts' === $active_tab ) {
            $this->prompt_editor->render();
        } elseif ( 'log' === $active_tab ) {
            $this->log_manager->render_log_table();
        }
    
	}


}

// Instantiate the Admin class.
new Admin();