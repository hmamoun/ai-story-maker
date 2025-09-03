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

// Get current user emailf
$current_user = wp_get_current_user();
$current_user_email = $current_user->user_email;

?>
<div class="wrap">
<?php
	// Add a nonce for AJAX security
	$ajax_nonce = wp_create_nonce( 'aistma_save_setting' );
    $aistma_api_url = getenv('AISTMA_MASTER_API');

    $aistma_master_url = getenv('AISTMA_MASTER_URL');
	$aistma_api_url = $aistma_api_url  ? $aistma_api_url  : 'https://www.exedotcom.ca/';

	?>
 <script type="text/javascript">
		window.aistmaSettings = {
			ajaxUrl: '<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>',
			nonce: '<?php echo esc_js( $ajax_nonce ); ?>',
			masterUrl: '<?php echo esc_url( $aistma_master_url ); ?>'
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
    <h2><?php esc_html_e( 'Subscription', 'ai-story-maker' ); ?></h2>
    <?php

		// Parse the URL to get domain and port
		$parsed_url = wp_parse_url($aistma_master_url);
		$domain = $parsed_url['host']; 
		$port = isset($parsed_url['port']) ? $parsed_url['port'] : null; // null or port number
		$scheme = $parsed_url['scheme']; // 'https'
		
		// Build the base URL
		$slug = 'ai-story-maker-plans';
		$base_url = $aistma_master_url . $slug . '/';
		
		// For the action URL, you might want to include the current site's domain
		$current_site_url = get_site_url();
		$current_parsed = wp_parse_url($current_site_url);
		$current_domain = $current_parsed['host'];
		$current_port = isset($current_parsed['port']) ? $current_parsed['port'] : null;
        // Standardized wrapper support: decode top-level wrapper and then body
        $decoded_wrapper = json_decode( $response_body, true );
        if ( json_last_error() === JSON_ERROR_NONE && is_array( $decoded_wrapper ) && isset( $decoded_wrapper['body'] ) ) {
            $packages_json = is_string( $decoded_wrapper['body'] ) ? $decoded_wrapper['body'] : json_encode( $decoded_wrapper['body'] );
        } else {
            // Backward compatibility: treat response as direct packages JSON
            $packages_json = $response_body;
        }

        $packages = json_decode( $packages_json, true );
        if ( json_last_error() !== JSON_ERROR_NONE ) {
            $packages = [];
        }

        // Determine whether to hide the packages container
        $hide_container = false;
        $message = '';

        // Legacy error format support
        if ( is_array( $packages ) && isset( $packages['status'] ) && $packages['status'] === 'error' ) {
            $hide_container = true;
            $message = isset( $packages['message'] ) ? (string) $packages['message'] : 'Subscription server not available.';
        } else {
            // Expected: list of packages
            if ( ! is_array( $packages ) ) {
                $hide_container = true;
                $message = 'Subscription server not available.';
            } else {
                $fallback_detected = ( count( $packages ) === 1 && isset( $packages[0]['name'] ) && strtolower( (string) $packages[0]['name'] ) === 'subscription server not available' );
                $no_packages = empty( $packages ) || $fallback_detected;
                if ( $no_packages ) {
                    $hide_container = true;
                    $message = $fallback_detected ? 'Subscription server not available.' : 'No packages are available at the moment.';
                }
            }
        }

if ( $hide_container ) {
    echo "<div class='aistma-error-message'>" . esc_html( $message ) . "</div>";
} else {
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
    <p>
        <?php esc_html_e( 'Choose one of the available subscription tiers', 'ai-story-maker' ); ?>
    </p>

<div class="aistma-packages-container">
    <?php
        $matched = false;
        foreach ($packages as $package):
        // Build the subscription URL with package ID, domain, port, and email
        $package_subscription_url = add_query_arg(
            array(
                'domain' => rawurlencode($current_domain),
                'port' => $current_port ? rawurlencode($current_port) : '',
                'email' => rawurlencode(string: $current_user_email),
                // Send package name as plain value; add_query_arg will urlencode
                'package_name' => isset($package['name']) ? $package['name'] : '',
            ),
            $base_url
        );
        $is_current = $current_package_name && isset($package['name']) && strcasecmp($package['name'], $current_package_name) === 0;
        if ( $is_current ) { $matched = true; }
        ?>
        <a 
            href="<?php echo esc_url($package_master_url); ?>"
            target="_blank"
            class="aistma-package-box aistma-package-clickable<?php echo $is_current ? ' aistma-current-package' : ''; ?>"
            <?php if ( $is_current ) : ?>aria-current="true"<?php endif; ?>
            data-package-name="<?php echo esc_attr(isset($package['name']) ? $package['name'] : ''); ?>"
          >
            <div class="aistma-package-title"><?php echo esc_html($package['name']); ?></div>
            
            <div class="aistma-package-meta">
                <span><strong>Price:</strong> $<?php echo esc_html($package['price']); ?></span>
				<span>
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
                <div class="aistma-current-plan-line" style="<?php echo $is_current && $status_line ? 'display:block;margin-top:8px;font-weight:600;color:#0073aa;' : 'display:none;'; ?>"><?php echo $is_current ? esc_html( $status_line ) : ''; ?></div>
            </div>
        </a>
    <?php endforeach; 
    // If we have a valid status but no matching card, show a fallback status above
    if ( $status_line && ! $matched ) {
        echo '<div id="aistma-subscription-status" class="notice notice-success" style="margin:10px 0;">' . esc_html( $status_line ) . '</div>';
    }
    ?>
    
</div>

<p class="aistma-subscribe-description">
	<?php esc_html_e( 'Click on any package above to go directly to the subscription page for that specific plan. This will take you to the plugin\'s secure official page on Exedotcom.ca with more details.', 'ai-story-maker' ); ?>
</p>
<?php } ?>
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
