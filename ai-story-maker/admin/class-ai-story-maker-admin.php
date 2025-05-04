<?php
/**
 * Admin setup for AI Story Maker.
 *
 * @package AI_Story_Maker
 * @author Hayan Mamoun
 * @license GPLv2 or later
 * @link https://github.com/hmamoun/ai-story-maker/wiki
 * @since 0.1.0
 */

namespace exedotcom\aistorymaker;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class AISTMA_Admin
 *
 * Handles logging for the AI Story Maker plugin.
 *
 * Creates a custom database table to store log entries and provides
 * methods to insert, display, and clear logs. This helps with debugging
 * and monitoring plugin activity.
 */
class AISTMA_Admin {

    /**
     * @var AISTMA_Prompt_Editor
     */
    protected $aistma_prompt_editor;

    /**
     * @var AISTMA_API_Keys
     */
    protected $aistma_api_keys;

    /**
     * @var AISTMA_Settings_Page
     */
    protected $aistma_settings_page;

    /**
     * @var AISTMA_Log_Manager
     */
    protected $aistma_log_manager;

    // These constants are used internally as tab identifiers.
    // Translation and HTML escaping are applied when outputting user-facing labels.
    const TAB_WELCOME = 'welcome';
    const TAB_GENERAL = 'general';
    const TAB_PROMPTS = 'prompts';
    const TAB_LOG     = 'log';


    /**
     * AISTMA_Admin constructor.
     *
     * Loads dependencies and initializes admin menu and tabs.
     */
    public function __construct() {
        $files = [
            'admin/class-ai-story-maker-prompt-editor.php',
            'admin/class-ai-story-maker-api-keys.php',
            'admin/class-ai-story-maker-settings-page.php',
            'includes/class-ai-story-maker-log-management.php',
        ];
        AISTMA_Plugin::aistma_load_dependencies( $files );

        add_action( 'admin_enqueue_scripts', array( $this, 'aistma_admin_enqueue_scripts' ) );
        add_action( 'admin_menu', array( $this, 'aistma_add_admin_menu' ) );
    }



    /**
     * Enqueue admin scripts and styles.
     *
     * @return void
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
     * Add the top-level admin menu and main tabbed interface.
     *
     * @return void
     */
    public function aistma_add_admin_menu() {
        add_menu_page(
            __( 'AI Story Maker Settings', 'ai-story-maker' ),
            __( 'AI Story Maker', 'ai-story-maker' ),
            'manage_options',
            'aistma-settings',
            array( $this, 'aistma_render_main_page' ),
            'dashicons-welcome-widgets-menus',
            9
        );
    }

    /**
     * Render the plugin's main settings page with tabs.
     *
     * @return void
     */
    public function aistma_render_main_page() {


        $allowed_tabs = [
            self::TAB_WELCOME,
            self::TAB_GENERAL,
            self::TAB_PROMPTS,
            self::TAB_LOG,
        ];
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Tab selection only affects UI; no action taken
        $active_tab = isset( $_GET['tab'] ) && in_array( sanitize_key( $_GET['tab'] ), $allowed_tabs, true )? sanitize_key( $_GET['tab']) : self::TAB_WELCOME;



        ?>
        <div id="aistma-notice" class="notice notice-info hidden"></div>
        <h2 class="nav-tab-wrapper">
            <a href="?page=aistma-settings&tab=<?php echo esc_attr( self::TAB_WELCOME ); ?>" class="nav-tab <?php echo ( $active_tab === self::TAB_WELCOME ) ? 'nav-tab-active' : ''; ?>">
                <?php esc_html_e( 'AI Story Maker', 'ai-story-maker' ); ?>
            </a>
            <a href="?page=aistma-settings&tab=<?php echo esc_attr( self::TAB_GENERAL ); ?>" class="nav-tab <?php echo ( $active_tab === self::TAB_GENERAL ) ? 'nav-tab-active' : ''; ?>">
                <?php esc_html_e( 'General Settings', 'ai-story-maker' ); ?>
            </a>
            <a href="?page=aistma-settings&tab=<?php echo esc_attr( self::TAB_PROMPTS ); ?>" class="nav-tab <?php echo ( $active_tab === self::TAB_PROMPTS ) ? 'nav-tab-active' : ''; ?>">
                <?php esc_html_e( 'Prompts', 'ai-story-maker' ); ?>
            </a>
            <a href="?page=aistma-settings&tab=<?php echo esc_attr( self::TAB_LOG ); ?>" class="nav-tab <?php echo ( $active_tab === self::TAB_LOG ) ? 'nav-tab-active' : ''; ?>">
                <?php esc_html_e( 'Log', 'ai-story-maker' ); ?>
            </a>
        </h2>
        <?php

        if ( 'welcome' === $active_tab ) {
            include_once AI_STORY_MAKER_PATH . 'admin/templates/welcome-tab-template.php';
        } elseif ( 'general' === $active_tab ) {
            $this->aistma_settings_page   = new AISTMA_Settings_Page();
            $this->aistma_settings_page->aistma_setting_page_render();
        } elseif ( 'prompts' === $active_tab ) {
            $this->aistma_prompt_editor   = new AISTMA_Prompt_Editor();
            $this->aistma_prompt_editor->aistma_prompt_editor_render();
        } elseif ( 'log' === $active_tab ) {
            // $this->aistma_api_keys        = new AISTMA_API_Keys();
            $this->aistma_log_manager     = new AISTMA_Log_Manager();
            $this->aistma_log_manager->aistma_log_table_render();
        }
    }
}

// Instantiate the Admin class.
new AISTMA_Admin();