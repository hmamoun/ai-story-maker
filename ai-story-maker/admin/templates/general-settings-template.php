<?php
/**
 * Template for the general settings page.
 *
 * @package AI_Story_Maker
 * @since   0.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

// Set default active tab if not already set
if ( ! isset( $active_tab ) ) {
	$active_tab = 'subscribe';
}
?>
<div class="wrap">
<?php

	// Add a nonce for AJAX security
	$ajax_nonce = wp_create_nonce( 'aistma_save_setting' );
	$aistma_master_url = defined('AISTMA_MASTER_URL') ? AISTMA_MASTER_URL : 'https://www.exedotcom.ca/';
	error_log( 'aistma_master_url' . $aistma_master_url );

	?>
	<script type="text/javascript">
		window.aistmaSettings = {
			ajaxUrl: '<?php echo admin_url( 'admin-ajax.php' ); ?>',
			nonce: '<?php echo esc_js( $ajax_nonce ); ?>'
		};
	</script>

<div id="aistma-settings-message"></div>

<h2 class="nav-tab-wrapper" id="aistma-subscribe-or-api-keys-wrapper">
    <a href="javascript:void(0);" data-tab="subscribe" class="nav-tab <?php echo ( $active_tab === 'subscribe' ) ? 'nav-tab-active' : ''; ?>">
        <?php esc_html_e( 'Subscribe [free tier available]', 'ai-story-maker' ); ?>
    </a>
    <a href="javascript:void(0);" data-tab="api_keys" class="nav-tab <?php echo ( $active_tab === 'api_keys' ) ? 'nav-tab-active' : ''; ?>">
        <?php esc_html_e( 'Use your own API keys', 'ai-story-maker' ); ?>
    </a>



</h2>
<div class="aistma-subscribe-or-api-keys-content-wrapper">
<div id="tab-subscribe" class="aistma-tab-content" style="display: <?php echo ( $active_tab === 'subscribe' ) ? 'block' : 'none'; ?>;">
    <h2><?php esc_html_e( 'Subscription Settings', 'ai-story-maker' ); ?></h2>
    <p>
        <?php esc_html_e( 'AI Story Maker offers a subscription service to access premium features. Please enter your subscription key below.', 'ai-story-maker' ); ?>
    </p>
    <?php
// Debug: Check local packages
$local_packages = get_option( 'exaig_aistma_packages', [ 'packages' => [] ] );
echo '<div class="notice notice-info"><p>Local packages count: ' . count($local_packages['packages'] ?? []) . '</p></div>';

// Test local REST API endpoint
$local_api_url = get_rest_url(null, 'exaig/v1/packages-summary');
echo '<div class="notice notice-info"><p>Local API URL: ' . esc_html( $local_api_url ) . '</p></div>';

$local_response = wp_remote_get( $local_api_url );
if ( !is_wp_error( $local_response ) ) {
    $local_code = wp_remote_retrieve_response_code( $local_response );
    echo '<div class="notice notice-info"><p>Local API Response Code: ' . esc_html( $local_code ) . '</p></div>';
}

// make a call to 'exaig/v1', '/packages-summary' endpoint on $aistma_master_url and print the response
$api_url = $aistma_master_url . 'exaig/v1/packages-summary';
echo '<div class="notice notice-info"><p>Calling API URL: ' . esc_html( $api_url ) . '</p></div>';

$response = wp_remote_get( $api_url );

// Debug the response
if ( is_wp_error( $response ) ) {
    echo '<div class="notice notice-error"><p>API Error: ' . esc_html( $response->get_error_message() ) . '</p></div>';
} else {
    $response_code = wp_remote_retrieve_response_code( $response );
    $response_body = wp_remote_retrieve_body( $response );
    
    echo '<div class="notice notice-info"><p>Response Code: ' . esc_html( $response_code ) . '</p></div>';
    
    if ( $response_code === 200 ) {
        $packages = json_decode( $response_body, true );
        if ( json_last_error() === JSON_ERROR_NONE ) {
            echo '<pre>';
            print_r( $packages );
            echo '</pre>';
        } else {
            echo '<div class="notice notice-error"><p>JSON Decode Error: ' . esc_html( json_last_error_msg() ) . '</p></div>';
            echo '<pre>Raw Response: ' . esc_html( $response_body ) . '</pre>';
        }
    } else {
        echo '<div class="notice notice-error"><p>HTTP Error: ' . esc_html( $response_code ) . '</p></div>';
        echo '<pre>Response Body: ' . esc_html( $response_body ) . '</pre>';
    }
}

		// Parse the URL to get domain and port
		$parsed_url = parse_url($aistma_master_url);
		$domain = $parsed_url['host']; // 'www.exedotcom.ca'
		$port = isset($parsed_url['port']) ? $parsed_url['port'] : null; // null or port number
		$scheme = $parsed_url['scheme']; // 'https'
		
		// Build the base URL
		$slug = 'ai-story-maker-plans';
		$base_url = $aistma_master_url . $slug . '/';
		
		// For the action URL, you might want to include the current site's domain
		$current_site_url = get_site_url();
		$current_parsed = parse_url($current_site_url);
		$current_domain = $current_parsed['host'];
		$current_port = isset($current_parsed['port']) ? $current_parsed['port'] : null;
		
		$action_url = add_query_arg(
			array(
				'domain' => rawurlencode($current_domain),
				'port' => $current_port ? rawurlencode($current_port) : '',
			),
			$base_url
		);
    ?>
    <a href="<?php echo esc_url( $action_url ); ?>" target="_blank" class="button button-primary">
        <?php esc_html_e( 'Buy Credits', 'ai-story-maker' ); ?>
    </a>
</div>

<div id="tab-api_keys" class="aistma-tab-content" style="display: <?php echo ( $active_tab === 'api_keys' ) ? 'block' : 'none'; ?>;">

<h2><?php esc_html_e( 'API Keys', 'ai-story-maker' ); ?></h2>
	<p>
		<?php esc_html_e( 'AI Story Maker integrates with OpenAI and Unsplash APIs to generate content and images. Please enter your API keys below. Registration may be required to obtain them.', 'ai-story-maker' ); ?>
	</p>
	<label for="aistma_openai_api_key">
		<?php esc_html_e( 'OpenAI', 'ai-story-maker' ); ?> <a href="https://platform.openai.com/" target="_blank"><?php esc_html_e( 'API', 'ai-story-maker' ); ?></a> <?php esc_html_e( 'Key:', 'ai-story-maker' ); ?>
	</label>
	<input type="text" id="aistma_openai_api_key" data-setting="aistma_openai_api_key" value="<?php echo esc_attr( get_option( 'aistma_openai_api_key' ) ); ?>">

	<label for="aistma_unsplash_api_key">
		<?php esc_html_e( 'Unsplash', 'ai-story-maker' ); ?> <a href="https://unsplash.com/developers" target="_blank"><?php esc_html_e( 'API Key and Secret', 'ai-story-maker' ); ?></a>:
	</label>
	<div class="inline-fields">
		<label for="aistma_unsplash_api_key"><?php esc_html_e( 'Key:', 'ai-story-maker' ); ?></label>
		<input type="text" id="aistma_unsplash_api_key" data-setting="aistma_unsplash_api_key" value="<?php echo esc_attr( get_option( 'aistma_unsplash_api_key' ) ); ?>">
		<label for="aistma_unsplash_api_secret"><?php esc_html_e( 'Secret:', 'ai-story-maker' ); ?></label>
		<input type="text" id="aistma_unsplash_api_secret" data-setting="aistma_unsplash_api_secret" value="<?php echo esc_attr( get_option( 'aistma_unsplash_api_secret' ) ); ?>">
	</div>
</div>
</div>






	<h2><?php esc_html_e( 'Settings', 'ai-story-maker' ); ?></h2>
	<div class="aistma-settings-grid">
		<div class="aistma-setting-item">
			<b><label for="aistma_clear_log_cron"><?php esc_html_e( 'Log Retention (Days):', 'ai-story-maker' ); ?></label></b>
			<p>
				<?php
				printf(
				/* translators: %s: link to log page */
					wp_kses_post( __( 'AI Story Maker maintains a detailed log of its activities. Choose how many days to retain the logs, or set to 0 to keep them indefinitely. You can view the log <a href="%s">here</a>.', 'ai-story-maker' ) ),
					esc_url( admin_url( 'admin.php?page=aistma-settings&tab=log' ) )
				);
				?>
			</p>
			<select id="aistma_clear_log_cron" data-setting="aistma_clear_log_cron">
				<?php for ( $i = 0; $i <= 30; $i++ ) : ?>
					<option value="<?php echo esc_attr( $i ); ?>" <?php selected( get_option( 'aistma_clear_log_cron' ), $i ); ?>>
						<?php echo esc_attr( $i ); ?> <?php esc_html_e( 'Day(s)', 'ai-story-maker' ); ?>
					</option>
				<?php endfor; ?>
			</select>
		</div>
		
		<div class="aistma-setting-item">
			<b><label for="aistma_generate_story_cron"><?php esc_html_e( 'Generate New Stories Every (Days):', 'ai-story-maker' ); ?></label></b>
			<p>
				<?php esc_html_e( 'AI Story Maker can automatically generate new stories at regular intervals. Set to 0 to disable scheduled generation.', 'ai-story-maker' ); ?>
			</p>
			<select id="aistma_generate_story_cron" data-setting="aistma_generate_story_cron">
				<?php for ( $i = 0; $i <= 30; $i++ ) : ?>
					<option value="<?php echo esc_attr( $i ); ?>" <?php selected( get_option( 'aistma_generate_story_cron' ), $i ); ?>>
						<?php echo esc_attr( $i ); ?> <?php esc_html_e( 'Day(s)', 'ai-story-maker' ); ?>
					</option>
				<?php endfor; ?>
			</select>
		</div>
		
		<div class="aistma-setting-item">
			<b><label for="aistma_opt_auther"><?php esc_html_e( 'Select Story Author:', 'ai-story-maker' ); ?></label></b>
			<p>
				<?php esc_html_e( 'Select the author for AI-generated stories. If you need to create a new author, you can do so', 'ai-story-maker' ); ?>
				<a href="<?php echo esc_url( admin_url( 'user-new.php?role=author' ) ); ?>" target="_blank"><?php esc_html_e( 'here', 'ai-story-maker' ); ?></a>.
				<?php esc_html_e( 'Ensure the role is set to "Author".', 'ai-story-maker' ); ?>
			</p>
			<select id="aistma_opt_auther" data-setting="aistma_opt_auther">
				<?php
				$users = get_users( array( 'role__in' => array( 'author', 'administrator' ) ) );
				foreach ( $users as $user ) :
					?>
					<option value="<?php echo esc_attr( $user->ID ); ?>" <?php selected( get_option( 'aistma_opt_auther' ), $user->ID ); ?>>
						<?php echo esc_html( $user->display_name ); ?>
					</option>
				<?php endforeach; ?>
			</select>
		</div>
		
		<div class="aistma-setting-item">
			<p>
				<b><label for="aistma_show_ai_attribution">
					<input type="checkbox" id="aistma_show_ai_attribution" data-setting="aistma_show_ai_attribution" value="1" <?php checked( get_option( 'aistma_show_ai_attribution', 1 ), 1 ); ?> />
					<?php esc_html_e( 'Show "Generated by AI" attribution at the end of each story', 'ai-story-maker' ); ?>
				</label></b>
			</p>
			<p style="margin-top: 10px; font-size: 12px; color: #666;">
				<?php esc_html_e( 'Recommended: Keeping this enabled promotes transparency and trust with your readers.', 'ai-story-maker' ); ?>
				<?php esc_html_e( 'Note: Future regulations may require disclosure of AI-generated content.', 'ai-story-maker' ); ?>
			</p>
		</div>
		
		<div class="aistma-setting-item">
			<p>
				<b><label for="aistma_show_exedotcom_attribution">
					<input type="checkbox" id="aistma_show_exedotcom_attribution" data-setting="aistma_show_exedotcom_attribution" value="1" <?php checked( get_option( 'aistma_show_exedotcom_attribution', 0 ), 1 ); ?> />
					<?php esc_html_e( 'Show "Created by AI Story Maker" attribution', 'ai-story-maker' ); ?>
				</label></b>
			</p>
			<p style="margin-top:10px; font-size: 12px; color: #666;">
				<?php esc_html_e( 'Support our work by showing a small attribution link to Exedotcom.ca. This helps us continue developing and improving AI Story Maker.', 'ai-story-maker' ); ?>
				<?php esc_html_e( 'Your support means a lot to us!', 'ai-story-maker' ); ?>
			</p>
		</div>
	</div>
</div>


<?php
