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

        // Instantiate the page-specific classes.
        $this->prompt_editor = new Prompt_Editor();
        $this->validate_keys = new API_Keys();
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
		</h2>
		<?php

		// Process form submission for saving settings.


		if ( 'general' === $active_tab ) {
                $settings_page->render();
        } elseif ( 'welcome' === $active_tab ) {
            // Include the welcome page.
            //include 'admin-page-welcome.php';


		} elseif ( 'prompts' === $active_tab ) {
			// Include the prompt editor page.
			//include 'admin-page-prompt-editor.php';

			if ( isset( $_POST['generate_ai_story'] ) ) {
				$results = generate_ai_story();

				if ( ! empty( $results['errors'] ) ) {
					foreach ( $results['errors'] as $error ) {
						echo '<div class="error"><p>' . esc_html( $error ) . '</p></div>';
					}
				}
				if ( ! empty( $results['successes'] ) ) {
					foreach ( $results['successes'] as $success ) {
						echo '<div class="updated"><p>' . esc_html( $success ) . '</p></div>';
					}
				}
			}

			$nextRun           = date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), wp_next_scheduled( 'sc_ai_story_scheduled_generate' ) );
			$currentServerDate = date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), current_time( 'timestamp' ) );
			$RemainingTime     = human_time_diff( current_time( 'timestamp' ), wp_next_scheduled( 'sc_ai_story_scheduled_generate' ) );
			?>
			<h2><?php esc_html_e( 'Generate Stories', 'ai-story-maker' ); ?></h2>
			<p>
				<?php
				printf(
					/* translators: %s: next scheduled run, %s: remaining time */
					esc_html__( 'Stories will be generated when anyone visits the site after <strong>%s</strong> (%s remaining).', 'ai-story-maker' ),
					esc_html( $nextRun ),
					esc_html( $RemainingTime )
				);
				?>
			</p>
			<?php
		}
	}

    /**
     * Renders the prompt editor page.
     */
    public function render_prompt_editor() {
        $this->prompt_editor->render();
    }

}

// Instantiate the Admin class.
new Admin();