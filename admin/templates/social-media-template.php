<?php
/**
 * Template for the Social Media Integration page.
 * Called from AISTMA_Admin::aistma_render_main_page()
 * to display social media account configuration and publishing options
 * 
 * @package AI_Story_Maker
 * @since   2.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

// Get current social media accounts configuration
$social_media_accounts = get_option( 'aistma_social_media_accounts', array() );
$default_accounts = array(
    'accounts' => array(),
    'global_settings' => array(
        'auto_publish' => false,
        'include_hashtags' => true,
        'default_hashtags' => '#AIStoryMaker #AutomatedContent'
    )
);

// Merge with defaults
$social_media_accounts = wp_parse_args( $social_media_accounts, $default_accounts );

// Add nonce for AJAX security
$ajax_nonce = wp_create_nonce( 'aistma_social_media_settings' );

?>
<div class="wrap">
    <div class="aistma-style-settings">
        
        <script type="text/javascript">
            window.aistmaSocialMediaSettings = {
                ajaxUrl: '<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>',
                nonce: '<?php echo esc_js( $ajax_nonce ); ?>',
                accounts: <?php echo wp_json_encode( $social_media_accounts['accounts'] ); ?>
            };
        </script>

        <div class="aistma-social-media-integration">
            <h2><?php esc_html_e( 'Social Media Integration', 'ai-story-maker' ); ?></h2>
            
            <div class="aistma-section-description">
                <p><?php esc_html_e( 'Connect your social media accounts to automatically publish your AI-generated stories across multiple platforms. Configure individual account settings and global publishing preferences below.', 'ai-story-maker' ); ?></p>
            </div>

            <?php
            // Handle Facebook OAuth callback messages
            if ( isset( $_GET['facebook_oauth'] ) && isset( $_GET['_wpnonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'aistma_facebook_oauth_result' ) ) {
                if ( $_GET['facebook_oauth'] === 'success' && isset( $_GET['account_name'] ) ) {
                    $account_name = sanitize_text_field( wp_unslash( $_GET['account_name'] ) );
                    echo '<div class="notice notice-success is-dismissible"><p>';
                    /* translators: %s: Facebook page name */
                    printf( esc_html__( 'Successfully connected Facebook page: %s', 'ai-story-maker' ), esc_html( $account_name ) );
                    echo '</p></div>';
                } elseif ( $_GET['facebook_oauth'] === 'error' && isset( $_GET['error_message'] ) ) {
                    $error_message = sanitize_text_field( wp_unslash( $_GET['error_message'] ) );
                    echo '<div class="notice notice-error is-dismissible"><p>';
                    /* translators: %s: Error message */
                    printf( esc_html__( 'Facebook connection failed: %s', 'ai-story-maker' ), esc_html( $error_message ) );
                    echo '</p></div>';
                }
            }
            ?>

            <!-- Global Settings Section -->
            <div class="aistma-settings-section">
                <h3><?php esc_html_e( 'Global Publishing Settings', 'ai-story-maker' ); ?></h3>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="aistma_auto_publish"><?php esc_html_e( 'Auto-Publish New Stories', 'ai-story-maker' ); ?></label>
                        </th>
                        <td>
                            <input type="checkbox" id="aistma_auto_publish" name="aistma_auto_publish" value="1" 
                                   <?php checked( $social_media_accounts['global_settings']['auto_publish'], true ); ?> />
                            <label for="aistma_auto_publish"><?php esc_html_e( 'Automatically publish new AI-generated stories to connected social media accounts', 'ai-story-maker' ); ?></label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="aistma_include_hashtags"><?php esc_html_e( 'Include Post Tags as Hashtags', 'ai-story-maker' ); ?></label>
                        </th>
                        <td>
                            <input type="checkbox" id="aistma_include_hashtags" name="aistma_include_hashtags" value="1" 
                                   <?php checked( $social_media_accounts['global_settings']['include_hashtags'], true ); ?> />
                            <label for="aistma_include_hashtags"><?php esc_html_e( 'Add post tags as hashtags to social media posts', 'ai-story-maker' ); ?></label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="aistma_default_hashtags"><?php esc_html_e( 'Additional Hashtags', 'ai-story-maker' ); ?></label>
                        </th>
                        <td>
                            <input type="text" id="aistma_default_hashtags" name="aistma_default_hashtags" 
                                   value="<?php echo esc_attr( $social_media_accounts['global_settings']['default_hashtags'] ); ?>" 
                                   class="regular-text" />
                            <p class="description"><?php esc_html_e( 'Additional hashtags to include with posts (space-separated)', 'ai-story-maker' ); ?></p>
                        </td>
                    </tr>
                </table>

                <p class="submit">
                    <button type="button" id="aistma-save-global-settings" class="button button-primary">
                        <?php esc_html_e( 'Save Global Settings', 'ai-story-maker' ); ?>
                    </button>
                </p>
            </div>

            <!-- Connected Accounts Section -->
            <div class="aistma-settings-section">
                <h3><?php esc_html_e( 'Connected Accounts', 'ai-story-maker' ); ?></h3>
                
                <?php if ( empty( $social_media_accounts['accounts'] ) ) : ?>
                    <div class="aistma-no-accounts">
                        <p><?php esc_html_e( 'No social media accounts connected yet.', 'ai-story-maker' ); ?></p>
                    </div>
                <?php else : ?>
                    <div class="aistma-accounts-list">
                        <?php foreach ( $social_media_accounts['accounts'] as $account ) : ?>
                            <div class="aistma-account-card" data-account-id="<?php echo esc_attr( $account['id'] ); ?>">
                                <div class="aistma-account-header">
                                    <h4><?php echo esc_html( $account['name'] ); ?></h4>
                                    <span class="aistma-platform-badge aistma-platform-<?php echo esc_attr( $account['platform'] ); ?>">
                                        <?php echo esc_html( ucfirst( $account['platform'] ) ); ?>
                                    </span>
                                    <div class="aistma-account-status">
                                        <?php if ( $account['enabled'] ) : ?>
                                            <span class="aistma-status-enabled"><?php esc_html_e( 'Enabled', 'ai-story-maker' ); ?></span>
                                        <?php else : ?>
                                            <span class="aistma-status-disabled"><?php esc_html_e( 'Disabled', 'ai-story-maker' ); ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="aistma-account-actions">
                                    <button type="button" class="button aistma-test-account" data-account-id="<?php echo esc_attr( $account['id'] ); ?>">
                                        <?php esc_html_e( 'Test Connection', 'ai-story-maker' ); ?>
                                    </button>
                                    <button type="button" class="button button-link-delete aistma-delete-account" data-account-id="<?php echo esc_attr( $account['id'] ); ?>">
                                        <?php esc_html_e( 'Delete', 'ai-story-maker' ); ?>
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <div class="aistma-add-account-section">
                    <h4><?php esc_html_e( 'Add New Account', 'ai-story-maker' ); ?></h4>
                    <p><?php esc_html_e( 'Select a platform to connect a new social media account (some will be enabled soon):', 'ai-story-maker' ); ?></p>
                    
                    <div class="aistma-platform-buttons">
                        <button type="button" class="button aistma-add-platform" data-platform="twitter" disabled>
                            <span class="dashicons dashicons-twitter"></span>
                            <?php esc_html_e( 'Add Twitter/X Account', 'ai-story-maker' ); ?>
                        </button>
                        <button type="button" class="button aistma-add-platform" data-platform="facebook">
                            <span class="dashicons dashicons-facebook"></span>
                            <?php esc_html_e( 'Add Facebook Page', 'ai-story-maker' ); ?>
                        </button>
                        <button type="button" class="button aistma-add-platform" data-platform="linkedin" disabled>
                            <span class="dashicons dashicons-linkedin"></span>
                            <?php esc_html_e( 'Add LinkedIn Page', 'ai-story-maker' ); ?>
                        </button>
                        <button type="button" class="button aistma-add-platform" data-platform="instagram" disabled>
                            <span class="dashicons dashicons-instagram"></span>
                            <?php esc_html_e( 'Add Instagram Account', 'ai-story-maker' ); ?>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Publishing History Section -->
            <div class="aistma-settings-section">
                <h3><?php esc_html_e( 'Recent Publishing Activity', 'ai-story-maker' ); ?></h3>
                <div id="aistma-publishing-history">
                    <p><?php esc_html_e( 'Publishing history will be displayed here once you start using social media integration.', 'ai-story-maker' ); ?></p>
                </div>
            </div>
        </div>

        <!-- Account Configuration Modal -->
        <div id="aistma-account-modal" class="aistma-modal" style="display: none;">
            <div class="aistma-modal-content">
                <div class="aistma-modal-header">
                    <h3 id="aistma-modal-title"><?php esc_html_e( 'Configure Social Media Account', 'ai-story-maker' ); ?></h3>
                    <button type="button" class="aistma-modal-close">&times;</button>
                </div>
                <div class="aistma-modal-body">
                    <form id="aistma-account-form">
                        <input type="hidden" id="aistma-account-id" name="account_id" />
                        <input type="hidden" id="aistma-account-platform" name="platform" />
                        
                        <table class="form-table">
                            <tr>
                                <th scope="row">
                                    <label for="aistma-account-name"><?php esc_html_e( 'Account Name', 'ai-story-maker' ); ?></label>
                                </th>
                                <td>
                                    <input type="text" id="aistma-account-name" name="account_name" class="regular-text" required />
                                    <p class="description"><?php esc_html_e( 'A friendly name to identify this account', 'ai-story-maker' ); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="aistma-account-enabled"><?php esc_html_e( 'Enable Publishing', 'ai-story-maker' ); ?></label>
                                </th>
                                <td>
                                    <input type="checkbox" id="aistma-account-enabled" name="enabled" value="1" />
                                    <label for="aistma-account-enabled"><?php esc_html_e( 'Publish stories to this account', 'ai-story-maker' ); ?></label>
                                </td>
                            </tr>
                        </table>

                        <!-- Platform-specific credentials will be inserted here via JavaScript -->
                        <div id="aistma-platform-credentials"></div>

                        <div class="aistma-modal-actions">
                            <button type="button" id="aistma-save-account" class="button button-primary">
                                <?php esc_html_e( 'Save Account', 'ai-story-maker' ); ?>
                            </button>
                            <button type="button" class="button aistma-modal-close">
                                <?php esc_html_e( 'Cancel', 'ai-story-maker' ); ?>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <style>
            .aistma-social-media-integration {
                max-width: 1000px;
            }
            
            .aistma-section-description {
                background: #f9f9f9;
                border-left: 4px solid #0073aa;
                padding: 15px;
                margin-bottom: 20px;
            }
            
            .aistma-settings-section {
                background: #fff;
                border: 1px solid #ccd0d4;
                box-shadow: 0 1px 1px rgba(0,0,0,.04);
                margin-bottom: 20px;
                padding: 20px;
            }
            
            .aistma-settings-section h3 {
                margin-top: 0;
                border-bottom: 1px solid #eee;
                padding-bottom: 10px;
            }
            
            .aistma-no-accounts {
                text-align: center;
                padding: 40px 20px;
                color: #666;
            }
            
            .aistma-account-card {
                border: 1px solid #ddd;
                border-radius: 4px;
                padding: 15px;
                margin-bottom: 15px;
                background: #fafafa;
            }
            
            .aistma-account-header {
                display: flex;
                align-items: center;
                gap: 15px;
                margin-bottom: 10px;
            }
            
            .aistma-account-header h4 {
                margin: 0;
                flex-grow: 1;
            }
            
            .aistma-platform-badge {
                background: #0073aa;
                color: white;
                padding: 4px 8px;
                border-radius: 3px;
                font-size: 12px;
                text-transform: uppercase;
            }
            
            .aistma-platform-twitter { background: #1da1f2; }
            .aistma-platform-facebook { background: #4267b2; }
            .aistma-platform-linkedin { background: #0077b5; }
            .aistma-platform-instagram { background: #e4405f; }
            
            .aistma-status-enabled {
                color: #46b450;
                font-weight: bold;
            }
            
            .aistma-status-disabled {
                color: #dc3232;
                font-weight: bold;
            }
            
            .aistma-account-actions {
                display: flex;
                gap: 10px;
            }
            
            .aistma-platform-buttons {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
                gap: 15px;
                margin-top: 15px;
            }
            
            .aistma-add-platform {
                display: flex;
                align-items: center;
                gap: 8px;
                padding: 12px 16px;
                height: auto;
            }
            
            .aistma-modal {
                position: fixed;
                z-index: 100000;
                left: 0;
                top: 0;
                width: 100%;
                height: 100%;
                background-color: rgba(0,0,0,0.5);
            }
            
            .aistma-modal-content {
                background-color: #fefefe;
                margin: 5% auto;
                padding: 0;
                border: 1px solid #888;
                width: 80%;
                max-width: 600px;
                border-radius: 4px;
            }
            
            .aistma-modal-header {
                padding: 20px;
                border-bottom: 1px solid #eee;
                display: flex;
                justify-content: space-between;
                align-items: center;
            }
            
            .aistma-modal-header h3 {
                margin: 0;
            }
            
            .aistma-modal-close {
                background: none;
                border: none;
                font-size: 24px;
                cursor: pointer;
                padding: 0;
                width: 30px;
                height: 30px;
                display: flex;
                align-items: center;
                justify-content: center;
            }
            
            .aistma-modal-body {
                padding: 20px;
            }
            
            .aistma-modal-actions {
                margin-top: 20px;
                padding-top: 20px;
                border-top: 1px solid #eee;
                display: flex;
                gap: 10px;
                justify-content: flex-end;
            }
        </style>

        <script>
            jQuery(document).ready(function($) {
                // Modal functionality
                $('.aistma-add-platform').on('click', function() {
                    const platform = $(this).data('platform');
                    openAccountModal('add', platform);
                });
                
                $('.aistma-delete-account').on('click', function() {
                    const accountId = $(this).data('account-id');
                    const accountName = $(this).closest('.aistma-account-card').find('h4').text();
                    
                    if (confirm('Are you sure you want to delete the account "' + accountName + '"? This action cannot be undone.')) {
                        deleteAccount(accountId);
                    }
                });
                
                $('.aistma-test-account').on('click', function() {
                    const accountId = $(this).data('account-id');
                    testAccount(accountId);
                });
                
                $('.aistma-modal-close').on('click', function() {
                    $('#aistma-account-modal').hide();
                });
                
                // Save global settings
                $('#aistma-save-global-settings').on('click', function() {
                    const settings = {
                        auto_publish: $('#aistma_auto_publish').is(':checked'),
                        include_hashtags: $('#aistma_include_hashtags').is(':checked'),
                        default_hashtags: $('#aistma_default_hashtags').val()
                    };
                    
                    saveGlobalSettings(settings);
                });
                
                // Save account
                $('#aistma-save-account').on('click', function() {
                    const formData = $('#aistma-account-form').serializeArray();
                    debugger;
                    const accountData = {};
                    
                    // Convert form data to object
                    $.each(formData, function(i, field) {
                        accountData[field.name] = field.value;
                    });
                    
                    // Add enabled checkbox value
                    accountData.enabled = $('#aistma-account-enabled').is(':checked');
                    
                    // Check if this is Facebook platform - OAuth only
                    if (accountData.platform === 'facebook') {
                        alert('Facebook accounts can only be connected using the "Connect Facebook Page" button above. Please use the OAuth connection method.');
                        return;
                    }
                    
                    // Debug logging
                    console.log('Account data to save:', accountData);
                    console.log('AJAX settings:', window.aistmaSocialMediaSettings);
                    
                    saveAccount(accountData);
                });
                
                // Handle Facebook OAuth button click
                $(document).on('click', '#aistma-connect-facebook-oauth', function() {
 
                    const button = $(this);
                    const statusDiv = $('#aistma-facebook-oauth-status');
                    
                    // Get Facebook App credentials from the modal form
                    const facebookAppId = $('input[name="facebook_app_id"]').val();
                    const facebookAppSecret = $('input[name="facebook_app_secret"]').val();
                    
                    if (!facebookAppId || !facebookAppSecret) {
                        statusDiv.html('<div class="notice notice-error inline"><p>Please enter your Facebook App ID and App Secret above before connecting.</p></div>');
                        return;
                    }
                    
                    // Disable button and show loading state
                    button.prop('disabled', true).text('Connecting...');
                    statusDiv.html('<div class="notice notice-info inline"><p>Generating OAuth URL...</p></div>');
                    
                    // Get OAuth URL from server
                    $.ajax({
                        url: window.aistmaSocialMediaSettings.ajaxUrl,
                        type: 'POST',
                        data: {
                            action: 'aistma_facebook_oauth_callback',
                            nonce: window.aistmaSocialMediaSettings.nonce,
                            facebook_app_id: facebookAppId,
                            facebook_app_secret: facebookAppSecret
                        },
                        success: function(response) {
                            console.log('OAuth URL response:', response);
                            if (response.success && response.data.oauth_url) {
                                statusDiv.html('<div class="notice notice-info inline"><p>Redirecting to Facebook...</p></div>');
                                // Small delay to show the message, then redirect
                                setTimeout(function() {
                                    window.location.href = response.data.oauth_url;
                                }, 500);
                            } else {
                                const errorMsg = (response.data && response.data.message) || response.data || 'Failed to get OAuth URL';
                                statusDiv.html('<div class="notice notice-error inline"><p>' + errorMsg + '</p></div>');
                                button.prop('disabled', false).html('<span class="dashicons dashicons-facebook" style="margin-right: 5px;"></span>Connect Facebook Page');
                            }
                        },
                        error: function(xhr, status, error) {
                            console.error('OAuth URL request failed:', {
                                xhr: xhr,
                                status: status,
                                error: error,
                                responseText: xhr.responseText
                            });
                            let errorMessage = 'Network error occurred. Please try again.';
                            if (xhr.responseText) {
                                try {
                                    const errorData = JSON.parse(xhr.responseText);
                                    if (errorData.data && errorData.data.message) {
                                        errorMessage = errorData.data.message;
                                    }
                                } catch (e) {
                                    // Use default message if JSON parsing fails
                                }
                            }
                            statusDiv.html('<div class="notice notice-error inline"><p>' + errorMessage + '</p></div>');
                            button.prop('disabled', false).html('<span class="dashicons dashicons-facebook" style="margin-right: 5px;"></span>Connect Facebook Page');
                        }
                    });
                });
                
                function openAccountModal(action, platform, accountId) {
                    $('#aistma-account-modal').show();
                    $('#aistma-account-platform').val(platform || '');
                    $('#aistma-account-id').val(accountId || '');
                    
                    if (action === 'add') {
                        $('#aistma-modal-title').text('Add ' + platform.charAt(0).toUpperCase() + platform.slice(1) + ' Account');
                        // Clear form fields for new account
                        $('#aistma-account-name').val('');
                        $('#aistma-account-enabled').prop('checked', true);
                        generatePlatformCredentialsForm(platform);
                    }
                }
                
                function generatePlatformCredentialsForm(platform) {
                    let credentialsHtml = '<h4>API Credentials</h4>';
                    
                    switch(platform) {
                        case 'twitter':
                            credentialsHtml += `
                                <table class="form-table">
                                    <tr>
                                        <th><label>API Key</label></th>
                                        <td><input type="password" name="api_key" class="regular-text" required /></td>
                                    </tr>
                                    <tr>
                                        <th><label>API Secret</label></th>
                                        <td><input type="password" name="api_secret" class="regular-text" required /></td>
                                    </tr>
                                    <tr>
                                        <th><label>Access Token</label></th>
                                        <td><input type="password" name="access_token" class="regular-text" required /></td>
                                    </tr>
                                    <tr>
                                        <th><label>Access Token Secret</label></th>
                                        <td><input type="password" name="access_token_secret" class="regular-text" required /></td>
                                    </tr>
                                </table>
                            `;
                            break;
                        case 'facebook':
                            credentialsHtml += `
                                <div class="aistma-facebook-oauth-section">
                                    <h4>Facebook App Configuration</h4>
                                    <p>Enter your Facebook App credentials to connect your Facebook page automatically using OAuth.</p>
                                    
                                    <table class="form-table" style="margin-bottom: 20px;">
                                        <tr>
                                            <th><label>Facebook App ID</label></th>
                                            <td><input type="text" name="facebook_app_id" class="regular-text" required placeholder="Your Facebook App ID" /></td>
                                        </tr>
                                        <tr>
                                            <th><label>Facebook App Secret</label></th>
                                            <td><input type="password" name="facebook_app_secret" class="regular-text" required placeholder="Your Facebook App Secret" /></td>
                                        </tr>
                                    </table>
                                    
                                    <div class="aistma-oauth-option" style="padding: 15px; border: 1px solid #ddd; border-radius: 5px; text-align: center;">
                                        <button type="button" id="aistma-connect-facebook-oauth" class="button button-primary" style="font-size: 14px; padding: 8px 16px;">
                                            <span class="dashicons dashicons-facebook" style="margin-right: 5px;"></span>
                                            Connect Facebook Page
                                        </button>
                                        <div id="aistma-facebook-oauth-status" style="margin-top: 15px;"></div>
                                        <p class="description" style="margin-top: 15px;">
                                            <strong>Setup Requirements:</strong><br>
                                            1. Create Facebook App with permissions: <code>pages_manage_posts</code>, <code>pages_read_engagement</code>, <code>pages_show_list</code><br>
                                            2. Go to Facebook Login for Business → "Valid OAuth Redirect URLs" → Add:<br>
                                            <code style="background: #f1f1f1; padding: 2px 4px;"><?php echo esc_url( admin_url( 'admin.php?aistma_facebook_oauth=1' ) ); ?></code><br>
                                            3. From App Settings → Basic: Get the App ID and App Secret<br>
                                            <br>
                                            <strong>⚠️ Domain Error Fix:</strong><br>
                                            If you see "domain not included" error:<br>
                                            • Settings → Basic → Add your domain to "App Domains"<br>
                                            • Facebook Login → Settings → Add redirect URI above
                                        </p>
                                    </div>
                                </div>
                            `;
                            break;
                        case 'linkedin':
                            credentialsHtml += `
                                <table class="form-table">
                                    <tr>
                                        <th><label>Access Token</label></th>
                                        <td><input type="password" name="access_token" class="regular-text" required /></td>
                                    </tr>
                                    <tr>
                                        <th><label>Company ID</label></th>
                                        <td><input type="text" name="company_id" class="regular-text" required /></td>
                                    </tr>
                                </table>
                            `;
                            break;
                        case 'instagram':
                            credentialsHtml += `
                                <table class="form-table">
                                    <tr>
                                        <th><label>Access Token</label></th>
                                        <td><input type="password" name="access_token" class="regular-text" required /></td>
                                    </tr>
                                    <tr>
                                        <th><label>Instagram Business Account ID</label></th>
                                        <td><input type="text" name="account_id" class="regular-text" required /></td>
                                    </tr>
                                </table>
                            `;
                            break;
                    }
                    
                    $('#aistma-platform-credentials').html(credentialsHtml);
                }
                
                function saveGlobalSettings(settings) {
                    $.ajax({
                        url: window.aistmaSocialMediaSettings.ajaxUrl,
                        type: 'POST',
                        data: {
                            action: 'aistma_save_social_media_global_settings',
                            nonce: window.aistmaSocialMediaSettings.nonce,
                            settings: settings
                        },
                        success: function(response) {
                            if (response.success) {
                                // Show success message
                                $('<div class="notice notice-success is-dismissible"><p>Global settings saved successfully!</p></div>')
                                    .insertAfter('.aistma-social-media-integration h2')
                                    .delay(3000)
                                    .fadeOut();
                            } else {
                                alert('Error saving settings: ' + (response.data || 'Unknown error'));
                            }
                        },
                        error: function() {
                            alert('Network error occurred while saving settings.');
                        }
                    });
                }
                
                function saveAccount(accountData) {
                    // Show loading state
                    const saveButton = $('#aistma-save-account');
                    const originalText = saveButton.text();
                    saveButton.prop('disabled', true).text('Saving...');
                    
                    // Validate required data
                    if (!accountData.platform || !accountData.account_name) {
                        alert('Platform and account name are required.');
                        saveButton.prop('disabled', false).text(originalText);
                        return;
                    }
                    
                    console.log('Making AJAX request to:', window.aistmaSocialMediaSettings.ajaxUrl);
                    console.log('With data:', {
                        action: 'aistma_save_social_media_account',
                        nonce: window.aistmaSocialMediaSettings.nonce,
                        account_data: accountData
                    });
                    
                    $.ajax({
                        url: window.aistmaSocialMediaSettings.ajaxUrl,
                        type: 'POST',
                        data: {
                            action: 'aistma_save_social_media_account',
                            nonce: window.aistmaSocialMediaSettings.nonce,
                            account_data: accountData
                        },
                        success: function(response) {
                            console.log('AJAX response:', response);
                            if (response.success) {
                                // Show success message
                                $('<div class="notice notice-success is-dismissible"><p>Account saved successfully!</p></div>')
                                    .insertAfter('.aistma-social-media-integration h2')
                                    .delay(3000)
                                    .fadeOut();
                                
                                // Close modal and refresh page to show new account
                                $('#aistma-account-modal').hide();
                                setTimeout(() => {
                                    window.location.reload();
                                }, 1000);
                            } else {
                                const errorMsg = (response.data && response.data.message) || response.data || 'Unknown error';
                                alert('Error saving account: ' + errorMsg);
                                console.error('Server error:', response);
                            }
                        },
                        error: function(xhr, status, error) {
                            console.error('AJAX error details:', {
                                xhr: xhr,
                                status: status,
                                error: error,
                                responseText: xhr.responseText
                            });
                            
                            let errorMessage = 'Network error occurred while saving account.';
                            if (xhr.responseText) {
                                try {
                                    const errorResponse = JSON.parse(xhr.responseText);
                                    errorMessage += ' Server response: ' + (errorResponse.data || errorResponse.message || xhr.responseText);
                                } catch (e) {
                                    errorMessage += ' Server response: ' + xhr.responseText.substring(0, 200);
                                }
                            }
                            errorMessage += ' (Status: ' + status + ')';
                            
                            alert(errorMessage);
                        },
                        complete: function() {
                            // Restore button state
                            saveButton.prop('disabled', false).text(originalText);
                        }
                    });
                }
                
                function deleteAccount(accountId) {
                    if (!accountId) {
                        alert('Account ID is required for deletion.');
                        return;
                    }
                    
                    console.log('Deleting account:', accountId);
                    
                    $.ajax({
                        url: window.aistmaSocialMediaSettings.ajaxUrl,
                        type: 'POST',
                        data: {
                            action: 'aistma_delete_social_media_account',
                            nonce: window.aistmaSocialMediaSettings.nonce,
                            account_id: accountId
                        },
                        success: function(response) {
                            console.log('Delete response:', response);
                            if (response.success) {
                                // Show success message
                                $('<div class="notice notice-success is-dismissible"><p>Account deleted successfully!</p></div>')
                                    .insertAfter('.aistma-social-media-integration h2')
                                    .delay(3000)
                                    .fadeOut();
                                
                                // Refresh page to show updated accounts list
                                setTimeout(() => {
                                    window.location.reload();
                                }, 1000);
                            } else {
                                const errorMsg = (response.data && response.data.message) || response.data || 'Unknown error';
                                alert('Error deleting account: ' + errorMsg);
                                console.error('Server error:', response);
                            }
                        },
                        error: function(xhr, status, error) {
                            console.error('Delete AJAX error:', {
                                xhr: xhr,
                                status: status,
                                error: error,
                                responseText: xhr.responseText
                            });
                            
                            let errorMessage = 'Network error occurred while deleting account.';
                            if (xhr.responseText) {
                                try {
                                    const errorResponse = JSON.parse(xhr.responseText);
                                    errorMessage += ' Server response: ' + (errorResponse.data || errorResponse.message || xhr.responseText);
                                } catch (e) {
                                    errorMessage += ' Server response: ' + xhr.responseText.substring(0, 200);
                                }
                            }
                            errorMessage += ' (Status: ' + status + ')';
                            
                            alert(errorMessage);
                        }
                    });
                }
                
                function testAccount(accountId) {
                    if (!accountId) {
                        alert('Account ID is required for testing.');
                        return;
                    }
                    
                    const testButton = $('[data-account-id="' + accountId + '"].aistma-test-account');
                    const originalText = testButton.text();
                    testButton.prop('disabled', true).text('Testing...');
                    
                    console.log('Testing account:', accountId);
                    
                    $.ajax({
                        url: window.aistmaSocialMediaSettings.ajaxUrl,
                        type: 'POST',
                        data: {
                            action: 'aistma_test_social_media_account',
                            nonce: window.aistmaSocialMediaSettings.nonce,
                            account_id: accountId
                        },
                        success: function(response) {
                            console.log('Test response:', response);
                            if (response.success) {
                                alert('Account connection test successful!');
                            } else {
                                const errorMsg = (response.data && response.data.message) || response.data || 'Connection test failed';
                                alert('Connection test failed: ' + errorMsg);
                            }
                        },
                        error: function(xhr, status, error) {
                            console.error('Test AJAX error:', error);
                            alert('Network error occurred while testing account connection.');
                        },
                        complete: function() {
                            testButton.prop('disabled', false).text(originalText);
                        }
                    });
                }
            });
        </script>
    </div>
</div>
