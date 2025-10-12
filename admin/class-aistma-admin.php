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
	const TAB_SOCIAL_MEDIA = 'social_media';
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

		// Initialize log manager
		$this->aistma_log_manager = new AISTMA_Log_Manager();

		add_action( 'admin_enqueue_scripts', array( $this, 'aistma_admin_enqueue_scripts' ) );
		add_action( 'admin_menu', array( $this, 'aistma_add_admin_menu' ) );
		add_action( 'admin_head-edit.php', array( $this, 'aistma_add_posts_page_button' ) );

		// Initialize social media bulk actions
		$this->init_social_media_bulk_actions();
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

		// Localize script with nonce for AJAX requests
		wp_localize_script( 'aistma-admin-js', 'aistmaSocialMedia', array(
			'nonce' => wp_create_nonce( 'aistma_social_media_nonce' ),
			'ajaxurl' => admin_url( 'admin-ajax.php' )
		) );

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
			self::TAB_SOCIAL_MEDIA,
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
			<a href="?page=aistma-settings&tab=<?php echo esc_attr( self::TAB_SOCIAL_MEDIA ); ?>" class="nav-tab <?php echo ( self::TAB_SOCIAL_MEDIA === $active_tab ) ? 'nav-tab-active' : ''; ?>">
		<?php esc_html_e( 'Social Media Integration', 'ai-story-maker' ); ?>
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
			$this->aistma_settings_page = AISTMA_Settings_Page::get_instance();
			$this->aistma_settings_page->aistma_subscriptions_page_render();
		} elseif ( self::TAB_SOCIAL_MEDIA === $active_tab ) {
			include_once AISTMA_PATH . 'admin/templates/social-media-template.php';
		} elseif ( self::TAB_SETTINGS === $active_tab ) {
			$this->aistma_settings_page = AISTMA_Settings_Page::get_instance();
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

	/**
	 * Initialize social media bulk actions.
	 */
	private function init_social_media_bulk_actions() {
		// Add bulk actions to posts admin page
		add_filter( 'bulk_actions-edit-post', array( $this, 'add_social_media_bulk_actions' ) );
		add_filter( 'handle_bulk_actions-edit-post', array( $this, 'handle_social_media_bulk_actions' ), 10, 3 );
		add_action( 'admin_notices', array( $this, 'show_bulk_action_notices' ) );
		
		// Add individual row actions
		add_filter( 'post_row_actions', array( $this, 'add_social_media_row_actions' ), 10, 2 );
		
		// Register AJAX handlers
		add_action( 'wp_ajax_aistma_publish_to_social_media', array( $this, 'ajax_publish_to_social_media' ) );
		
		// Register hooks for auto-publishing new posts
		add_action( 'transition_post_status', array( $this, 'auto_publish_to_social_media' ), 10, 3 );
		add_action( 'wp_insert_post', array( $this, 'handle_new_published_post' ), 10, 3 );
	}

	/**
	 * Add social media bulk actions to posts admin page.
	 */
	public function add_social_media_bulk_actions( $actions ) {
		// Get saved social media accounts
		$social_media_accounts = get_option( 'aistma_social_media_accounts', array( 'accounts' => array() ) );
		
		if ( empty( $social_media_accounts['accounts'] ) ) {
			return $actions;
		}

		// Add separator
		$actions['aistma_separator'] = '--- ' . __( 'Publish to Social Media', 'ai-story-maker' ) . ' ---';

		// Add action for each enabled account
		foreach ( $social_media_accounts['accounts'] as $account ) {
			if ( $account['enabled'] ) {
				$action_key = 'aistma_publish_to_' . $account['id'];
				$platform_name = ucfirst( $account['platform'] );
				$actions[ $action_key ] = sprintf( 
					/* translators: %1$s: Platform name (e.g., Facebook), %2$s: Account name */
					__( 'Publish to %1$s (%2$s)', 'ai-story-maker' ), 
					$platform_name, 
					$account['name'] 
				);
			}
		}

		return $actions;
	}

	/**
	 * Handle social media bulk actions.
	 */
	public function handle_social_media_bulk_actions( $redirect_to, $doaction, $post_ids ) {
		// Check if this is a social media bulk action
		if ( strpos( $doaction, 'aistma_publish_to_' ) !== 0 ) {
			return $redirect_to;
		}

		// Extract account ID from action
		$account_id = str_replace( 'aistma_publish_to_', '', $doaction );
		
		// Get the account details
		$account = $this->get_social_media_account( $account_id );
		if ( ! $account ) {
			$this->aistma_log_manager->log( 'error', 'Social media bulk action failed: Account not found (ID: ' . $account_id . ')' );
			$redirect_to = add_query_arg( 'aistma_bulk_error', 'account_not_found', $redirect_to );
			return $redirect_to;
		}

		// Validate user permissions
		if ( ! current_user_can( 'edit_posts' ) ) {
			$this->aistma_log_manager->log( 'error', 'Social media bulk action failed: Insufficient permissions for user ' . get_current_user_id() );
			$redirect_to = add_query_arg( 'aistma_bulk_error', 'insufficient_permissions', $redirect_to );
			return $redirect_to;
		}

		$published_count = 0;
		$failed_count = 0;
		$errors = array();

		// Process each selected post
		foreach ( $post_ids as $post_id ) {
			$post = get_post( $post_id );
			if ( ! $post || $post->post_status !== 'publish' ) {
				$failed_count++;
				/* translators: %d: Post ID number */
				$error_msg = sprintf( __( 'Post ID %d is not published', 'ai-story-maker' ), $post_id );
				$errors[] = $error_msg;
				$this->aistma_log_manager->log( 'error', 'Social media publish failed: ' . $error_msg . ' (Account: ' . $account['name'] . ')' );
				continue;
			}

			// Publish to social media
			$result = $this->publish_post_to_social_media( $post, $account );
			
			if ( $result['success'] ) {
				$published_count++;
				$this->aistma_log_manager->log( 
					'info', 
					sprintf( 
						'Post "%s" (ID: %d) successfully published to %s account "%s"', 
						$post->post_title, 
						$post_id, 
						$account['platform'], 
						$account['name'] 
					) 
				);
			} else {
				$failed_count++;
				$error_msg = sprintf( 
					/* translators: %1$s: Post title, %2$s: Error message */
					__( 'Failed to publish post "%1$s": %2$s', 'ai-story-maker' ), 
					$post->post_title, 
					$result['message'] 
				);
				$errors[] = $error_msg;
				$this->aistma_log_manager->log( 
					'error', 
					sprintf( 
						'Failed to publish post "%s" (ID: %d) to %s account "%s": %s', 
						$post->post_title, 
						$post_id, 
						$account['platform'], 
						$account['name'], 
						$result['message'] 
					) 
				);
			}
		}

		// Add results to redirect URL
		$redirect_to = add_query_arg( array(
			'aistma_bulk_published' => $published_count,
			'aistma_bulk_failed' => $failed_count,
			'aistma_account_name' => urlencode( $account['name'] ),
			'aistma_platform' => $account['platform']
		), $redirect_to );

		return $redirect_to;
	}

	/**
	 * Show bulk action result notices.
	 */
	public function show_bulk_action_notices() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Displaying admin notices only, no actions taken
		if ( isset( $_GET['aistma_bulk_published'] ) ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Displaying admin notices only, no actions taken
			$published = intval( $_GET['aistma_bulk_published'] );
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Displaying admin notices only, no actions taken
			$failed = isset( $_GET['aistma_bulk_failed'] ) ? intval( $_GET['aistma_bulk_failed'] ) : 0;
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Displaying admin notices only, no actions taken
			$account_name = isset( $_GET['aistma_account_name'] ) ? urldecode( sanitize_text_field( wp_unslash( $_GET['aistma_account_name'] ) ) ) : '';
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Displaying admin notices only, no actions taken
			$platform = isset( $_GET['aistma_platform'] ) ? sanitize_text_field( wp_unslash( $_GET['aistma_platform'] ) ) : '';

			if ( $published > 0 ) {
			$message = sprintf(
				/* translators: %1$d: Number of posts, %2$s: Platform name (e.g., Facebook), %3$s: Account name */
				_n(
					'Successfully published %1$d post to %2$s (%3$s).',
					'Successfully published %1$d posts to %2$s (%3$s).',
					$published,
					'ai-story-maker'
				),
				$published,
				 ucfirst( $platform ),
				$account_name
			);
				echo '<div class="notice notice-success is-dismissible"><p>' . esc_html( $message ) . '</p></div>';
			}

			if ( $failed > 0 ) {
			$message = sprintf(
				/* translators: %d: Number of posts that failed to publish */
				_n(
					'Failed to publish %d post to social media.',
					'Failed to publish %d posts to social media.',
					$failed,
					'ai-story-maker'
				),
				$failed
			);
				echo '<div class="notice notice-error is-dismissible"><p>' . esc_html( $message ) . '</p></div>';
			}
		}
	}

	/**
	 * Get social media account by ID.
	 */
	private function get_social_media_account( $account_id ) {
		$social_media_accounts = get_option( 'aistma_social_media_accounts', array( 'accounts' => array() ) );
		
		foreach ( $social_media_accounts['accounts'] as $account ) {
			if ( $account['id'] === $account_id && $account['enabled'] ) {
				return $account;
			}
		}
		
		return null;
	}

	/**
	 * Publish a post to a social media account.
	 */
	private function publish_post_to_social_media( $post, $account ) {
		if ( $account['platform'] === 'facebook' ) {
			return $this->publish_to_facebook( $post, $account );
		}
		
		/* translators: %s: Social media platform name (e.g., Twitter, Instagram) */
		$error_msg = sprintf( __( 'Platform %s not yet implemented', 'ai-story-maker' ), $account['platform'] );
		$this->aistma_log_manager->log( 
			'error', 
			sprintf( 
				'Unsupported platform for post "%s" (ID: %d): %s (Account: %s)', 
				$post->post_title, 
				$post->ID, 
				$account['platform'], 
				$account['name'] 
			) 
		);
		
		return array(
			'success' => false,
			'message' => $error_msg
		);
	}

	/**
	 * Publish post to Facebook.
	 */
	private function publish_to_facebook( $post, $account ) {
		if ( empty( $account['credentials']['access_token'] ) || empty( $account['credentials']['page_id'] ) ) {
			$error_msg = __( 'Missing Facebook credentials.', 'ai-story-maker' );
			$this->aistma_log_manager->log( 
				'error', 
				sprintf( 
					'Facebook publish failed for post "%s" (ID: %d): %s (Account: %s)', 
					$post->post_title, 
					$post->ID, 
					$error_msg, 
					$account['name'] 
				) 
			);
			return array(
				'success' => false,
				'message' => $error_msg
			);
		}

		// Prepare post content
		$message = $post->post_title;
		if ( ! empty( $post->post_excerpt ) ) {
			$message .= "\n\n" . $post->post_excerpt;
		}
		
		$post_url = get_permalink( $post );

		// Facebook Graph API endpoint
		$api_url = 'https://graph.facebook.com/v18.0/' . $account['credentials']['page_id'] . '/feed';
		
		$post_data = array(
			'message' => $message,
			'link' => $post_url,
			'access_token' => $account['credentials']['access_token']
		);

		$response = wp_remote_post( $api_url, array(
			'body' => $post_data,
			'timeout' => 30,
			'headers' => array(
				'User-Agent' => 'AI Story Maker WordPress Plugin'
			)
		) );

		if ( is_wp_error( $response ) ) {
			$error_msg = __( 'Network error: ', 'ai-story-maker' ) . $response->get_error_message();
			$this->aistma_log_manager->log( 
				'error', 
				sprintf( 
					'Facebook API network error for post "%s" (ID: %d): %s (Account: %s)', 
					$post->post_title, 
					$post->ID, 
					$response->get_error_message(), 
					$account['name'] 
				) 
			);
			return array(
				'success' => false,
				'message' => $error_msg
			);
		}

		$response_code = wp_remote_retrieve_response_code( $response );
		$response_body = wp_remote_retrieve_body( $response );
		$data = json_decode( $response_body, true );

		if ( $response_code === 200 && isset( $data['id'] ) ) {
			// Store the social media post ID for future reference
			add_post_meta( $post->ID, '_aistma_facebook_post_id', $data['id'], true );
			
			$this->aistma_log_manager->log( 
				'info', 
				sprintf( 
					'Post "%s" (ID: %d) successfully published to Facebook account "%s" (Facebook Post ID: %s)', 
					$post->post_title, 
					$post->ID, 
					$account['name'], 
					$data['id'] 
				) 
			);
			
			return array(
				'success' => true,
				/* translators: %s: Facebook account name */
				'message' => sprintf( __( 'Successfully published to Facebook: %s', 'ai-story-maker' ), $account['name'] )
			);
		} else {
			$error_message = isset( $data['error']['message'] ) ? $data['error']['message'] : __( 'Unknown Facebook API error', 'ai-story-maker' );
			$full_error = __( 'Facebook API error: ', 'ai-story-maker' ) . $error_message;
			
			$this->aistma_log_manager->log( 
				'error', 
				sprintf( 
					'Facebook API error for post "%s" (ID: %d): %s (HTTP %d) (Account: %s) (Response: %s)', 
					$post->post_title, 
					$post->ID, 
					$error_message, 
					$response_code, 
					$account['name'], 
					$response_body 
				) 
			);
			
			return array(
				'success' => false,
				'message' => $full_error
			);
		}
	}

	/**
	 * Add social media actions to individual post rows.
	 *
	 * @param array   $actions Post row actions.
	 * @param WP_Post $post    Post object.
	 * @return array Modified post row actions.
	 */
	public function add_social_media_row_actions( $actions, $post ) {
		// Only add actions to published posts
		if ( $post->post_status !== 'publish' ) {
			return $actions;
		}

		// Get saved social media accounts
		$social_media_accounts = get_option( 'aistma_social_media_accounts', array( 'accounts' => array() ) );
		
		if ( empty( $social_media_accounts['accounts'] ) ) {
			return $actions;
		}

		// Count enabled accounts
		$enabled_accounts = array_filter( $social_media_accounts['accounts'], function( $account ) {
			return $account['enabled'];
		});

		if ( empty( $enabled_accounts ) ) {
			return $actions;
		}

		// Add social media publish action
		if ( count( $enabled_accounts ) === 1 ) {
			// Single account - direct action
			$account = reset( $enabled_accounts );
			$actions['aistma_publish'] = sprintf(
				'<a href="#" class="aistma-publish-single" data-post-id="%d" data-account-id="%s" data-account-name="%s" data-platform="%s" title="%s">%s</a>',
				$post->ID,
				esc_attr( $account['id'] ),
				esc_attr( $account['name'] ),
				esc_attr( $account['platform'] ),
				/* translators: %1$s: Post title, %2$s: Platform name (e.g., Facebook) */
				esc_attr( sprintf( __( 'Publish "%1$s" to %2$s', 'ai-story-maker' ), $post->post_title, ucfirst( $account['platform'] ) ) ),
				/* translators: %s: Platform name (e.g., Facebook) */
				sprintf( __( 'Publish to %s', 'ai-story-maker' ), ucfirst( $account['platform'] ) )
			);
		} else {
			// Multiple accounts - show submenu
			$actions['aistma_publish'] = sprintf(
				'<a href="#" class="aistma-publish-menu" data-post-id="%d" title="%s">%s</a>',
				$post->ID,
				/* translators: %s: Post title */
				esc_attr( sprintf( __( 'Publish "%s" to social media', 'ai-story-maker' ), $post->post_title ) ),
				__( 'Publish to Social Media', 'ai-story-maker' )
			);
		}

		return $actions;
	}

	/**
	 * Handle AJAX request to publish post to social media.
	 */
	public function ajax_publish_to_social_media() {
		// Verify nonce for security
		if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ?? '' ) ), 'aistma_social_media_nonce' ) ) {
			wp_send_json_error( array( 'message' => 'Security check failed' ) );
		}

		// Check user capabilities
		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( array( 'message' => 'Insufficient permissions' ) );
		}

		$post_id = intval( $_POST['post_id'] ?? 0 );
		$account_id = sanitize_text_field( wp_unslash( $_POST['account_id'] ?? '' ) );

		if ( ! $post_id || ! $account_id ) {
			wp_send_json_error( array( 'message' => 'Missing required parameters' ) );
		}

		// Get the post
		$post = get_post( $post_id );
		if ( ! $post || $post->post_status !== 'publish' ) {
			wp_send_json_error( array( 'message' => 'Post not found or not published' ) );
		}

		// Get the social media account
		$account = $this->get_social_media_account( $account_id );
		if ( ! $account || ! $account['enabled'] ) {
			wp_send_json_error( array( 'message' => 'Social media account not found or disabled' ) );
		}

		// Attempt to publish
		$result = $this->publish_post_to_social_media( $post, $account );

		if ( $result['success'] ) {
			wp_send_json_success( array(
				'message' => $result['message'],
				'platform' => $account['platform'],
				'account_name' => $account['name']
			) );
		} else {
			wp_send_json_error( array(
				'message' => $result['message']
			) );
		}
	}

	/**
	 * Auto-publish posts to social media when they transition to 'publish' status.
	 *
	 * @param string  $new_status New post status.
	 * @param string  $old_status Old post status.
	 * @param WP_Post $post       Post object.
	 */
	public function auto_publish_to_social_media( $new_status, $old_status, $post ) {
		// Only process when post transitions to 'publish' status
		if ( $new_status !== 'publish' || $old_status === 'publish' ) {
			return;
		}

		// Only process standard posts (not pages, attachments, etc.)
		if ( $post->post_type !== 'post' ) {
			return;
		}

		// Check if auto-publish is enabled globally
		$social_media_accounts = get_option( 'aistma_social_media_accounts', array( 'accounts' => array() ) );
		$auto_publish_enabled = isset( $social_media_accounts['global_settings']['auto_publish'] ) && 
								$social_media_accounts['global_settings']['auto_publish'];

		if ( ! $auto_publish_enabled ) {
			return;
		}

		// Get enabled social media accounts
		$enabled_accounts = array();
		if ( ! empty( $social_media_accounts['accounts'] ) ) {
			foreach ( $social_media_accounts['accounts'] as $account ) {
				if ( $account['enabled'] ) {
					$enabled_accounts[] = $account;
				}
			}
		}

		if ( empty( $enabled_accounts ) ) {
			return;
		}

		// Log the auto-publish attempt
		$this->aistma_log_manager->log( 
			'info', 
			sprintf( 
				'Auto-publishing post "%s" (ID: %d) to %d social media accounts', 
				$post->post_title, 
				$post->ID, 
				count( $enabled_accounts ) 
			) 
		);

		// Publish to each enabled account
		foreach ( $enabled_accounts as $account ) {
			$result = $this->publish_post_to_social_media( $post, $account );
			
			if ( $result['success'] ) {
				$this->aistma_log_manager->log( 
					'info', 
					sprintf( 
						'Auto-published post "%s" (ID: %d) to %s account "%s"', 
						$post->post_title, 
						$post->ID, 
						$account['platform'], 
						$account['name'] 
					) 
				);
			} else {
				$this->aistma_log_manager->log( 
					'error', 
					sprintf( 
						'Auto-publish failed for post "%s" (ID: %d) to %s account "%s": %s', 
						$post->post_title, 
						$post->ID, 
						$account['platform'], 
						$account['name'],
						$result['message']
					) 
				);
			}
		}
	}

	/**
	 * Handle posts that are created directly with 'publish' status.
	 *
	 * @param int     $post_id Post ID.
	 * @param WP_Post $post    Post object.
	 * @param bool    $update  Whether this is an existing post being updated.
	 */
	public function handle_new_published_post( $post_id, $post, $update ) {
		// Only process new posts (not updates)
		if ( $update ) {
			return;
		}
	
		// Only process posts that are published
		if ( $post->post_status !== 'publish' ) {
			return;
		}
	
		// Only process standard posts (not pages, attachments, etc.)
		if ( $post->post_type !== 'post' ) {
			return;
		}
	
		// Check if this post was already shared (prevent duplicates)
		$already_shared = get_post_meta( $post_id, '_aistma_social_shared', true );
		if ( $already_shared ) {
			return;
		}
	
		// Mark as shared before processing
		update_post_meta( $post_id, '_aistma_social_shared', true );
	
		// Call the same auto-publish logic
		$this->auto_publish_to_social_media( 'publish', 'new', $post );
	}
}

// Instantiate the Admin class.
new AISTMA_Admin();