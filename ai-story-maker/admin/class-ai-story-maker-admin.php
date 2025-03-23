<?php
/*
 * This plugin is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 */
namespace AI_Story_Maker;

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

    /**
     * Constructor registers the admin menu.
     */
    public function __construct() {
        // Load the additional class files.
        $this->load_dependencies();
        $this->enqueue_scripts();

        // Instantiate the page-specific classes.
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
        // Assuming these files are in the same folder as this file.
        include_once plugin_dir_path( __FILE__ ) . 'class-ai-story-maker-prompt-editor.php';
        include_once plugin_dir_path( __FILE__ ) . 'class-ai-story-maker-api-keys.php';
        include_once plugin_dir_path( __FILE__ ) . 'class-ai-story-maker-settings-page.php';
    }

    /**
     * enqueue scripts and styles
     * 
     */
    public function enqueue_scripts() {
        wp_enqueue_script( 'ai-story-maker-admin', plugin_dir_url( __FILE__ ) . 'js/admin.js', array( 'jquery' ), '1.0', true );
        wp_enqueue_style( 'ai-story-maker-admin', plugin_dir_url( __FILE__ ) . 'css/admin.css', array(), '1.0' );
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
		$active_tab = isset( $_GET['tab'] ) ? sanitize_text_field( wp_unslash( $_GET['tab'] ) ) : 'welcome';
		?>
		<h2 class="nav-tab-wrapper">
            <a href="?page=story-maker-settings&tab=welcome" class="nav-tab <?php echo ( $active_tab === 'welcome' ) ? 'nav-tab-active' : ''; ?>">
				<?php esc_html_e( 'Welcome to AI Story Maker', 'ai-story-maker' ); ?>
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


		if ( 'general' === $active_tab ) {
                $this->settings_page->render();
        } elseif ( 'welcome' === $active_tab ) {
            ?>
            <h2><?php esc_html_e( 'Welcome to AI Story Maker', 'ai-story-maker' ); ?></h2>
            <p><?php esc_html_e( 'AI Story Maker is a plugin that generates stories using OpenAI\'s GPT-3 model.', 'ai-story-maker' ); ?></p>
            <p><?php esc_html_e( 'To get started, you need to enter your OpenAI API key in the settings page.', 'ai-story-maker' ); ?></p>
            <p><?php esc_html_e( 'You can also generate stories using the prompts page.', 'ai-story-maker' ); ?></p>
            <p>
                <?php printf(
                    /* translators: %s: GitHub Wiki URL */
                    esc_html__( 'For more info go to %s', 'ai-story-maker' ),
                    '<a href="' . esc_url( 'https://github.com/hmamoun/ai-story-maker/wiki' )  . '" target="_blank">' . esc_html__( 'GitHub Wiki', 'ai-story-maker' ) . '</a>'
                ); ?>
            </p>
            <?php
		} elseif ( 'prompts' === $active_tab ) {
			// Include the prompt editor page.
			//include 'admin-page-prompt-editor.php';
            $this->prompt_editor->render();
			$nextRun           = date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), wp_next_scheduled( 'sc_ai_story_scheduled_generate' ) );
			$currentServerDate = date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), current_time( 'timestamp' ) );
			$RemainingTime     = human_time_diff( current_time( 'timestamp' ), wp_next_scheduled( 'sc_ai_story_scheduled_generate' ) );

		} elseif ( 'log' === $active_tab ) {
        }
	}

    // /**
    //  * Renders the prompt editor page.
    //  */
    // public function render_prompt_editor() {
    //     $this->prompt_editor->render();
    // }

}

// Instantiate the Admin class.
new Admin();