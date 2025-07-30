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

?>
<div class="wrap">
<?php
	// Add a nonce for AJAX security
	$ajax_nonce = wp_create_nonce( 'aistma_save_setting' );
	$aistma_subscription_url = defined('AISTMA_SUBSCRIBTION_URL') ? AISTMA_SUBSCRIBTION_URL : 'https://www.exedotcom.ca/';

	?>
	<script type="text/javascript">
		window.aistmaSettings = {
			ajaxUrl: '<?php echo admin_url( 'admin-ajax.php' ); ?>',
			nonce: '<?php echo esc_js( $ajax_nonce ); ?>',
			masterUrl: '<?php echo $aistma_subscription_url; ?>'
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
		$parsed_url = parse_url($aistma_subscription_url);
		$domain = $parsed_url['host']; 
		$port = isset($parsed_url['port']) ? $parsed_url['port'] : null; // null or port number
		$scheme = $parsed_url['scheme']; // 'https'
		
		// Build the base URL
		$slug = 'ai-story-maker-plans';
		$base_url = $aistma_subscription_url . $slug . '/';
		
		// For the action URL, you might want to include the current site's domain
		$current_site_url = get_site_url();
		$current_parsed = parse_url($current_site_url);
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
    <?php foreach ( $packages as $package ) : ?>
        <div class="aistma-package-box">
            <div class="aistma-package-title"><?php echo esc_html( $package['name'] ); ?></div>
            <div class="aistma-package-description"><?php echo nl2br( esc_html( $package['description'] ) ); ?></div>
            <div class="aistma-package-meta">
                <span><strong>Price:</strong> $<?php echo esc_html( $package['price'] ); ?></span>
                <span><strong>Status:</strong> <?php echo esc_html( ucfirst( $package['status'] ) ); ?></span>
                <span><strong>Monthly Credits:</strong> <?php echo esc_html( $package['credits'] ); ?></span>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<?php
// Build the subscription URL with the same GET values
$subscription_url = add_query_arg(
    array(
        'domain' => rawurlencode($current_domain),
        'port' => $current_port ? rawurlencode($current_port) : '',
    ),
    $base_url
);
?>

<div class="aistma-subscribe-button-container">
    <a href="<?php echo esc_url( $subscription_url ); ?>" target="_blank" class="button button-primary button-hero">
        <?php esc_html_e( 'Subscribe', 'ai-story-maker' ); ?>
    </a>
    <p class="aistma-subscribe-description">
        <?php esc_html_e( 'Click the Subscribe button to view all available plans and choose the one that best fits your needs.', 'ai-story-maker' ); ?>
    </p>
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
</div> 