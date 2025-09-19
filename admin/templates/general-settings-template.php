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
	$aistma_subscription_url = defined('AISTMA_SUBSCRIBTION_URL') ? AISTMA_SUBSCRIBTION_URL : 'https://www.exedotcom.ca/';

	?>
	<script type="text/javascript">
		window.aistmaSettings = {
			ajaxUrl: '<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>',
			nonce: '<?php echo esc_js( $ajax_nonce ); ?>',
			masterUrl: '<?php echo esc_url( $aistma_subscription_url ); ?>'
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
        <?php esc_html_e( 'Choose one of the available subscription tiers', 'ai-story-maker' ); ?>
    </p>
    <?php
	    $aistma_subscription_url = aistma_get_master_url();
		// Parse the URL to get domain and port
		$parsed_url = wp_parse_url($aistma_subscription_url);
		$domain = $parsed_url['host']; 
		$port = isset($parsed_url['port']) ? $parsed_url['port'] : null; // null or port number
		$scheme = $parsed_url['scheme']; // 'https'
		
		// Build the base URL
		$slug = 'ai-story-maker-plans';
		$base_url = $aistma_subscription_url . $slug . '/';
		
		// For the action URL, you might want to include the current site's domain
		$current_site_url = get_site_url();
		$current_parsed = wp_parse_url($current_site_url);
		$current_domain = $current_parsed['host'];
		$current_port = isset($current_parsed['port']) ? $current_parsed['port'] : null;
$packages = json_decode( $response_body, true );

// Server-side subscription status fetch (no frontend AJAX)
$subscription_status = null;
try {
    $generator = new \exedotcom\aistorymaker\AISTMA_Story_Generator();
    $subscription_status = $generator->aistma_get_subscription_status();
} catch ( \Throwable $e ) {
    $subscription_status = null;
}

$current_package_name = ( is_array( $subscription_status ) && ! empty( $subscription_status['valid'] ) ) ? ( $subscription_status['package_name'] ?? '' ) : '';

// Build concise status line
$status_line = '';
if ( is_array( $subscription_status ) && ! empty( $subscription_status['valid'] ) ) {
    $credits_remaining = isset( $subscription_status['credits_remaining'] ) ? intval( $subscription_status['credits_remaining'] ) : null;
    $next_billing_raw = $subscription_status['next_billing_date'] ?? '';
    $next_billing = 'N/A';
    if ( is_array( $next_billing_raw ) ) {
        $next_billing = $next_billing_raw['formatted_date'] ?? $next_billing_raw['date'] ?? 'N/A';
    } elseif ( is_string( $next_billing_raw ) && $next_billing_raw !== '' ) {
        $ts = strtotime( $next_billing_raw );
        $next_billing = $ts ? gmdate( 'Y-M-d', $ts ) : $next_billing_raw;
    }
    $parts = [];
    if ( null !== $credits_remaining ) {
        $parts[] = sprintf( '%d stories remaining', $credits_remaining );
    }
    if ( $next_billing && 'N/A' !== $next_billing ) {
        $parts[] = 'Next billing: ' . $next_billing;
    }
    if ( ! empty( $current_package_name ) ) {
        array_unshift( $parts, 'Current plan: ' . $current_package_name );
    }
    $status_line = implode( '. ', $parts ) . '.';
}
?>
<?php if ( $status_line ) : ?>
    <div id="aistma-subscription-status" class="notice notice-success" style="margin:10px 0;">
        <?php echo esc_html( $status_line ); ?>
    </div>
<?php endif; ?>
<div class="aistma-packages-container">
    <?php foreach ( $packages as $package ) : 
		$action_url = add_query_arg(
			array(
				'domain' => rawurlencode($current_domain),
				'port' => $current_port ? rawurlencode($current_port) : '',
				'package' => $package['name']
			),
			$base_url
		);
		
		?>
        <div class="aistma-package-box">
            <div class="aistma-package-title"><?php echo esc_html( $package['name'] ); ?></div>
            <div class="aistma-package-description"><?php echo nl2br( esc_html( $package['description'] ) ); ?></div>
            <div class="aistma-package-meta">
                <span><strong>Price:</strong> $<?php echo esc_html( $package['price'] ); ?></span>
                <span><strong>Status:</strong> <?php echo esc_html( ucfirst( $package['status'] ) ); ?></span>
                <span><strong>Monthly Credits:</strong> <?php echo esc_html( $package['credits'] ); ?></span>
				<a href="<?php echo esc_url( $action_url ); ?>" target="_blank" class="button button-primary">
					<?php esc_html_e( 'Buy Credits', 'ai-story-maker' ); ?>
				</a>
            </div>
        </div>
    <?php endforeach; ?>
</div>


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
	<div class="aistma-settings-vertical">
		<div class="aistma-setting-item-compact">
			<div class="setting-label">
				<h4><?php esc_html_e( 'Generate New Stories Every (Days)', 'ai-story-maker' ); ?></h4>
				<p><?php esc_html_e( 'Automatically generate stories at regular intervals. Set to 0 to disable.', 'ai-story-maker' ); ?></p>
			</div>
			<div class="setting-control">
				<select id="aistma_generate_story_cron" data-setting="aistma_generate_story_cron">
					<?php for ( $i = 0; $i <= 30; $i++ ) : ?>
						<option value="<?php echo esc_attr( $i ); ?>" <?php selected( get_option( 'aistma_generate_story_cron' ), $i ); ?>>
							<?php echo esc_attr( $i ); ?> <?php esc_html_e( 'Day(s)', 'ai-story-maker' ); ?>
						</option>
					<?php endfor; ?>
				</select>
			</div>
		</div>
		
		<div class="aistma-setting-item-compact">
			<div class="setting-label">
				<h4><?php esc_html_e( 'Story Author', 'ai-story-maker' ); ?></h4>
				<p>
					<?php esc_html_e( 'Select author for AI-generated stories.', 'ai-story-maker' ); ?>
					<a href="<?php echo esc_url( admin_url( 'user-new.php?role=author' ) ); ?>" target="_blank"><?php esc_html_e( 'Create new author', 'ai-story-maker' ); ?></a>.
				</p>
			</div>
			<div class="setting-control">
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
		</div>
		
		<div class="aistma-setting-item-full">
			<label for="aistma_show_ai_attribution">
				<input type="checkbox" id="aistma_show_ai_attribution" data-setting="aistma_show_ai_attribution" value="1" <?php checked( get_option( 'aistma_show_ai_attribution', 1 ), 1 ); ?> />
				<div class="checkbox-content">
					<strong><?php esc_html_e( 'Show "Generated by AI" attribution at the end of each story', 'ai-story-maker' ); ?></strong>
					<div class="checkbox-description">
						<?php esc_html_e( 'Recommended: Promotes transparency and trust with readers. Future regulations may require AI content disclosure.', 'ai-story-maker' ); ?>
					</div>
				</div>
			</label>
		</div>
		
		<div class="aistma-setting-item-full">
			<label for="aistma_show_exedotcom_attribution">
				<input type="checkbox" id="aistma_show_exedotcom_attribution" data-setting="aistma_show_exedotcom_attribution" value="1" <?php checked( get_option( 'aistma_show_exedotcom_attribution', 0 ), 1 ); ?> />
				<div class="checkbox-content">
					<strong><?php esc_html_e( 'Show "Created by AI Story Maker" attribution', 'ai-story-maker' ); ?></strong>
					<div class="checkbox-description">
						<?php esc_html_e( 'Support our work by showing a small attribution link to Exedotcom.ca. Helps us continue developing AI Story Maker.', 'ai-story-maker' ); ?>
					</div>
				</div>
			</label>
		</div>
		
		<div class="aistma-setting-item-compact">
			<div class="setting-label">
				<h4><?php esc_html_e( 'Log Retention (Days)', 'ai-story-maker' ); ?></h4>
				<p>
					<?php
					printf(
					/* translators: %s: link to log page */
						wp_kses_post( __( 'Choose how many days to retain logs, or 0 for indefinitely. <a href="%s">View logs</a>.', 'ai-story-maker' ) ),
						esc_url( admin_url( 'admin.php?page=aistma-settings&tab=log' ) )
					);
					?>
				</p>
			</div>
			<div class="setting-control">
				<select id="aistma_clear_log_cron" data-setting="aistma_clear_log_cron">
					<?php for ( $i = 0; $i <= 30; $i++ ) : ?>
						<option value="<?php echo esc_attr( $i ); ?>" <?php selected( get_option( 'aistma_clear_log_cron' ), $i ); ?>>
							<?php echo esc_attr( $i ); ?> <?php esc_html_e( 'Day(s)', 'ai-story-maker' ); ?>
						</option>
					<?php endfor; ?>
				</select>
			</div>
		</div>
	</div>
</div>


<?php
