<?php
/**
 * Template for the subscriptions page.
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

// Get current user email
$current_user = wp_get_current_user();
$current_user_email = $current_user->user_email;

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
?>
<script>
	// Check subscription status when page loads
	document.addEventListener('DOMContentLoaded', function() {
		if (typeof aistma_get_subscription_status === 'function') {
			aistma_get_subscription_status();
		}
	});
</script>
<div class="aistma-packages-container">
    <?php foreach ( $packages as $package ) : 
        // Build the subscription URL with package ID, domain, port, and email
        $package_subscription_url = add_query_arg(
            array(
                'domain' => rawurlencode($current_domain),
                'port' => $current_port ? rawurlencode($current_port) : '',
                'email' => rawurlencode($current_user_email),
                'package_name' => rawurlencode($package['name']),
            ),
            $base_url
        );
    ?>
        <a 
            href="<?php echo esc_url( $package_subscription_url ); ?>"
            target="_blank"
            class="aistma-package-box aistma-package-clickable"
            data-package-name="<?php echo esc_attr( isset( $package['name'] ) ? $package['name'] : '' ); ?>"
          >
            <div class="aistma-package-title"><?php echo esc_html( $package['name'] ); ?></div>
            
            <div class="aistma-package-meta">
                <span><strong>Price:</strong> $<?php echo esc_html( $package['price'] ); ?></span>
				<span><strong>
    <?php 
    $stories_count = intval($package['stories']);
    $interval_count = intval($package['interval_count']);
    $interval = $package['interval'];
    
    // Handle stories display - hide if only 1 story, use singular if 1, plural if more
    if ($stories_count > 1) {
        echo esc_html($stories_count . ' stories');
    } else {
        echo esc_html('1 story');
    }
    
    echo ' every ';
    
    // Handle interval display - singular if count is 1, plural if more
    if ($interval_count > 1) {
        echo esc_html($interval_count . ' ' . $interval . 's');
    } else {
        echo esc_html($interval);
    }
    ?>
</span>
                <div class="aistma-current-plan-line" style="display:none;"></div>
            </div>
        </a>
    <?php endforeach; ?>
</div>

<p class="aistma-subscribe-description">
	<?php esc_html_e( 'Click on any package above to go directly to the subscription page for that specific plan. This will take you to the plugin\'s secure official page on Exedotcom.ca with more details.', 'ai-story-maker' ); ?>
</p>
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
</div> 