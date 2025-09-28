<?php
/**
 * Admin setup for AI Story Maker.
 *
 * @package AI_Story_Maker
 * @author  Hayan Mamoun
 * @license GPLv2 or later
 * @link    https://github.com/hmamoun/ai-story-maker/wiki
 * @since   0.1.0
 */

// phpcs:disable WordPress.Files.FileName.NotClassName
// phpcs:disable WordPress.Files.FileName.NotClass

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
	 * Instance of the prompt editor.
	 *
	 * @var AISTMA_Prompt_Editor
	 */
	protected $aistma_prompt_editor;

	/**
	 * Instance of the API keys manager.
	 *
	 * @var AISTMA_API_Keys
	 */
	protected $aistma_api_keys;

	/**
	 * Instance of the settings page manager.
	 *
	 * @var AISTMA_Settings_Page
	 */
	protected $aistma_settings_page;

	/**
	 * Instance of the log manager.
	 *
	 * @var AISTMA_Log_Manager
	 */
	protected $aistma_log_manager;

	// These constants are used internally as tab identifiers.
	// Translation and HTML escaping are applied when outputting user-facing labels.
	const TAB_WELCOME = 'welcome';
	const TAB_AI_WRITER = 'ai_writer';
	const TAB_SETTINGS = 'settings';
	const TAB_GENERAL = 'general';
	const TAB_PROMPTS = 'prompts';
	const TAB_ANALYTICS = 'analytics';
	const TAB_LOG     = 'log';


	/**
	 * AISTMA_Admin constructor.
	 *
	 * Loads dependencies and initializes admin menu and tabs.
	 */
	public function __construct() {
		$files = array(
			'admin/class-aistma-prompt-editor.php',
			'admin/class-aistma-api-keys.php',
			'admin/class-aistma-settings-page.php',
			'includes/class-aistma-log-manager.php',
			'admin/widgets/widgets-manager.php',
		);
		AISTMA_Plugin::aistma_load_dependencies( $files );

		add_action( 'admin_enqueue_scripts', array( $this, 'aistma_admin_enqueue_scripts' ) );
		add_action( 'admin_menu', array( $this, 'aistma_add_admin_menu' ) );
		add_action( 'admin_head-edit.php', array( $this, 'aistma_add_posts_page_button' ) );
	}



	/**
	 * Enqueue admin scripts and styles.
	 *
	 * @return void
	 */
	public function aistma_admin_enqueue_scripts() {
		wp_enqueue_script(
			'aistma-admin-js',
			AISTMA_URL . 'admin/js/admin.js',
			array( 'jquery' ),
			filemtime( AISTMA_PATH . 'admin/js/admin.js' ),
			true
		);

		wp_enqueue_style(
			'aistma-admin-css',
			AISTMA_URL . 'admin/css/admin.css',
			array(),
			filemtime( AISTMA_PATH . 'admin/css/admin.css' )
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

		$allowed_tabs = array(
			self::TAB_WELCOME,
			self::TAB_AI_WRITER,
			self::TAB_SETTINGS,
			self::TAB_GENERAL,
			self::TAB_PROMPTS,
			self::TAB_ANALYTICS,
			self::TAB_LOG,
		);
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Tab selection only affects UI; no action taken
		$active_tab = isset( $_GET['tab'] ) && in_array( sanitize_key( $_GET['tab'] ), $allowed_tabs, true ) ? sanitize_key( $_GET['tab'] ) : self::TAB_WELCOME;

		?>

		<h2 class="nav-tab-wrapper">
			<a href="?page=aistma-settings&tab=<?php echo esc_attr( self::TAB_WELCOME ); ?>" class="nav-tab <?php echo ( self::TAB_WELCOME === $active_tab ) ? 'nav-tab-active' : ''; ?>">
		<?php esc_html_e( 'AI Story Maker', 'ai-story-maker' ); ?>
			</a>
			<a href="?page=aistma-settings&tab=<?php echo esc_attr( self::TAB_AI_WRITER ); ?>" class="nav-tab <?php echo ( self::TAB_AI_WRITER === $active_tab ) ? 'nav-tab-active' : ''; ?>">
		<?php esc_html_e( 'Accounts', 'ai-story-maker' ); ?>
			</a>
			<a href="?page=aistma-settings&tab=<?php echo esc_attr( self::TAB_SETTINGS ); ?>" class="nav-tab <?php echo ( self::TAB_SETTINGS === $active_tab ) ? 'nav-tab-active' : ''; ?>">
		<?php esc_html_e( 'Settings', 'ai-story-maker' ); ?>
			</a>
			<a href="?page=aistma-settings&tab=<?php echo esc_attr( self::TAB_PROMPTS ); ?>" class="nav-tab <?php echo ( self::TAB_PROMPTS === $active_tab ) ? 'nav-tab-active' : ''; ?>">
		<?php esc_html_e( 'Prompts', 'ai-story-maker' ); ?>
			</a>
			<a href="?page=aistma-settings&tab=<?php echo esc_attr( self::TAB_ANALYTICS ); ?>" class="nav-tab <?php echo ( self::TAB_ANALYTICS === $active_tab ) ? 'nav-tab-active' : ''; ?>">
		<?php esc_html_e( 'Analytics', 'ai-story-maker' ); ?>
			</a>
			<a href="?page=aistma-settings&tab=<?php echo esc_attr( self::TAB_LOG ); ?>" class="nav-tab <?php echo ( self::TAB_LOG === $active_tab ) ? 'nav-tab-active' : ''; ?>">
		<?php esc_html_e( 'Log', 'ai-story-maker' ); ?>
			</a>
		</h2>
		<?php

		if ( self::TAB_WELCOME === $active_tab ) {
			include_once AISTMA_PATH . 'admin/templates/welcome-tab-template.php';
		} elseif ( self::TAB_AI_WRITER === $active_tab ) {
			$this->aistma_settings_page = new AISTMA_Settings_Page();
			$this->aistma_settings_page->aistma_subscriptions_page_render();
		} elseif ( self::TAB_SETTINGS === $active_tab ) {
			$this->aistma_settings_page = new AISTMA_Settings_Page();
			$this->aistma_settings_page->aistma_settings_page_render();
		} elseif ( self::TAB_PROMPTS === $active_tab ) {
			$this->aistma_prompt_editor = new AISTMA_Prompt_Editor();
			$this->aistma_prompt_editor->aistma_prompt_editor_render();
		} elseif ( self::TAB_ANALYTICS === $active_tab ) {
			include_once AISTMA_PATH . 'admin/templates/analytics-template.php';
		} elseif ( self::TAB_LOG === $active_tab ) {
			$this->aistma_log_manager = new AISTMA_Log_Manager();
			$this->aistma_log_manager->aistma_log_table_render();
		}

		// Include generation controls on all tabs
		include_once AISTMA_PATH . 'admin/templates/generation-controls-template.php';
	}

	/**
	 * Add Generate Stories button to the WordPress Posts page.
	 *
	 * @return void
	 */
	public function aistma_add_posts_page_button() {
		global $typenow;
		
		// Only add button on the posts list page
		if ( $typenow !== 'post' ) {
			return;
		}
		
		// Only show to users who can edit posts
		if ( ! current_user_can( 'edit_posts' ) ) {
			return;
		}
		
		?>
		<style>
			.aistma-posts-page-button {
				margin-left: 10px;
				vertical-align: top;
			}
			#aistma-posts-notice {
				margin-top: 10px;
				padding: 12px;
				border-left: 4px solid #0073aa;
				background: #fff;
				box-shadow: 0 1px 1px rgba(0,0,0,.04);
			}
			#aistma-posts-notice.notice-success {
				border-left-color: #46b450;
			}
			#aistma-posts-notice.notice-error {
				border-left-color: #dc3232;
			}
		</style>
		<script type="text/javascript">
		document.addEventListener('DOMContentLoaded', function() {
			// Add the Generate Stories button next to "Add New" button
			const addNewButton = document.querySelector('.page-title-action');
			if (addNewButton) {
				<?php
				$is_generating   = get_transient( 'aistma_generating_lock' );
				$button_disabled = $is_generating ? 'disabled' : '';
				$button_text     = $is_generating
					? __( 'Story generation in progress [recheck in 10 minutes]', 'ai-story-maker' )
					: __( 'Generate AI Stories', 'ai-story-maker' );
				?>
				
				// Create button HTML
				const buttonHtml = `
					<input type="hidden" id="aistma-posts-generate-story-nonce" value="<?php echo esc_attr( wp_create_nonce( 'generate_story_nonce' ) ); ?>">
					<button id="aistma-posts-generate-stories-button" class="button button-primary aistma-posts-page-button" <?php echo esc_attr( $button_disabled ); ?>>
						<?php echo esc_html( $button_text ); ?>
					</button>
					<div id="aistma-posts-notice" style="display:none;"></div>
				`;
				
				// Insert button after the "Add New" button
				addNewButton.insertAdjacentHTML('afterend', buttonHtml);
				
				// Add event listener for the button
				const generateButton = document.getElementById('aistma-posts-generate-stories-button');
				if (generateButton) {
					generateButton.addEventListener('click', function(e) {
						e.preventDefault();
						const originalCaption = this.innerHTML;
						this.disabled = true;
						this.innerHTML = '<span class="spinner" style="visibility: visible; float: none; margin: 0 5px 0 0;"></span>Generating... do not leave or close the page';

						const nonce = document.getElementById('aistma-posts-generate-story-nonce').value;
						const showNotice = (message, type) => {
							let messageDiv = document.getElementById('aistma-posts-notice');
							if (messageDiv) {
								messageDiv.className = `notice notice-${type} is-dismissible`;
								messageDiv.style.display = 'block';
								// Normalize and simplify common fatal error wording and strip HTML tags
								const normalized = String(message || '')
									.replace(/<[^>]*>/g, '')
									.replace(/fatal\s+error:?/ig, 'Error')
									.trim();
								messageDiv.textContent = normalized || (type === 'success' ? 'Done.' : 'Error. Please check the logs.');
							}
						};
						
						fetch(ajaxurl, {
							method: "POST",
							headers: {
								"Content-Type": "application/x-www-form-urlencoded"
							},
							body: new URLSearchParams({
								action: "generate_ai_stories",
								nonce: nonce
							})
						})
						.then(response => {
							if (!response.ok) {
								return response.text().then(text => {
									throw new Error(text)
								});
							}
							return response.json();
						})
						.then(data => {
							if (data.success) {
								showNotice("Story generated successfully!", 'success');
								// Refresh the page to show new posts
								setTimeout(() => {
									window.location.reload();
								}, 2000);
							} else {
								const serverMsg = (data && data.data && (data.data.message || data.data.error)) || data.message || "Error generating stories. Please check the logs!";
								showNotice(serverMsg, 'error');
							}
						})
						.catch(error => {
							console.error("Fetch error:", error);
							const errMsg = (error && error.message) ? `Network error: ${error.message}` : 'Network error. Please try again.';
							showNotice(errMsg, 'error');
						})
						.finally(() => {
							this.disabled = false;
							this.innerHTML = originalCaption;
						});
					});
				}
			}
		});
		</script>
		<?php
	}
}

// Instantiate the Admin class.
new AISTMA_Admin();