<?php
/**
 * Template for the subscriptions page.
 * called from AISTMA_Settings_Page::aistma_subscriptions_page_render()
 * which is called from AISTMA_Admin::aistma_admin_render_main_page()
 * to display the subscription options and api keys
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

// Handle posted subscription email and persist it
$email_update_message = '';
if ( isset( $_SERVER['REQUEST_METHOD'] ) && 'POST' === $_SERVER['REQUEST_METHOD'] && isset( $_POST['aistma_subscription_email_nonce'] ) ) {
    // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Verified via nonce and sanitized below
    if ( wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['aistma_subscription_email_nonce'] ) ), 'aistma_subscription_email' ) ) {
        $posted_email_raw = isset( $_POST['aistma_subscription_email'] ) ? sanitize_text_field( wp_unslash( $_POST['aistma_subscription_email'] ) ) : '';
        $posted_email = sanitize_email( $posted_email_raw );
        if ( ! empty( $posted_email ) && is_email( $posted_email ) ) {
            update_option( 'aistma_subscription_email', $posted_email );
            $current_user_email = $posted_email; // reflect immediately
            $email_update_message = __( 'Subscription email updated.', 'ai-story-maker' );
        } else {
            $email_update_message = __( 'Please enter a valid email address.', 'ai-story-maker' );
        }
    } else {
        $email_update_message = __( 'Security check failed. Please try again.', 'ai-story-maker' );
    }
}

// Use saved subscription email if available
$saved_subscription_email = get_option( 'aistma_subscription_email' );
if ( ! empty( $saved_subscription_email ) ) {
    $current_user_email = $saved_subscription_email;
}

?>
<div class="wrap">
    <div class="aistma-style-settings">
       
<?php
	// Add a nonce for AJAX security
	$ajax_nonce = wp_create_nonce( 'aistma_save_setting' );
    $aistma_api_url = aistma_get_api_url();
    $aistma_master_url = aistma_get_master_url();
	$aistma_api_url = $aistma_api_url  ? $aistma_api_url  : 'https://www.exedotcom.ca/';
    $aistma_master_url = $aistma_master_url  ? $aistma_master_url  : 'https://www.exedotcom.ca/';
?>



<script type="text/javascript">
		window.aistmaSettings = {
			ajaxUrl: '<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>',
			nonce: '<?php echo esc_js( $ajax_nonce ); ?>',
			masterUrl: '<?php echo esc_url( $aistma_master_url ); ?>'
		};
</script> 
<!--  tabs for subscribe and api keys -->
<h2 class="nav-tab-wrapper" id="aistma-subscribe-or-api-keys-wrapper">
    <a href="javascript:void(0);" data-tab="subscribe" class="nav-tab <?php echo ( $active_tab === 'subscribe' ) ? 'nav-tab-active' : ''; ?>">
        <?php esc_html_e( 'Subscribe', 'ai-story-maker' ); ?>
    </a>
    <a href="javascript:void(0);" data-tab="api_keys" class="nav-tab <?php echo ( $active_tab === 'api_keys' ) ? 'nav-tab-active' : ''; ?>">
        <?php esc_html_e( 'Use your own API keys', 'ai-story-maker' ); ?>
    </a>
</h2>


<div id="tab-subscribe" class="aistma-tab-content" style="display: <?php echo ( $active_tab === 'subscribe' ) ? 'block' : 'none'; ?>;">
    <h2><?php esc_html_e( 'Subscription', 'ai-story-maker' ); ?></h2>

    <?php
		// Build the base URL
		$package_registration_url = $aistma_master_url . 'ai-story-maker-plans' . '/';
		
        // For the action URL, you might want to include the current site's domain
		$current_site_url = get_site_url();
		$current_parsed = wp_parse_url($current_site_url);
		$current_domain = $current_parsed['host'];
		$current_port = isset($current_parsed['port']) ? $current_parsed['port'] : null;
        
        // Standardized wrapper support: decode top-level wrapper and then body
        // $response_body is passed from the settings page
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
            // Skip if package is not an array
            if (!is_array($package)) {
                continue;
            }
        // Build the subscription URL with package ID, domain, port, and email
        $package_registration_url = add_query_arg(
            array(
                'domain' => rawurlencode($current_domain),
                'port' => $current_port ? rawurlencode($current_port) : '',
                'email' => rawurlencode(string: $current_user_email),
                'package_name' => isset($package['name']) ? $package['name'] : '',
            ),
            $package_registration_url
        );
        $is_current = $current_package_name && isset($package['name']) && strcasecmp($package['name'], $current_package_name) === 0;
        if ( $is_current ) { $matched = true; }
        
        // Check if this package has subscription info from the API gateway
        $has_subscription = isset($package['subscription_status']) && isset($package['subscription_info']);
        $subscription_status = $has_subscription ? $package['subscription_status'] : '';
        $subscription_info = $has_subscription ? $package['subscription_info'] : null;
        ?>
        <a 
            href="<?php echo esc_url($package_registration_url); ?>"
            target="_blank"
            class="aistma-package-box aistma-package-clickable<?php echo $is_current ? ' aistma-current-package' : ''; ?><?php echo $has_subscription ? ' aistma-subscribed-package' : ''; ?>"
            <?php if ( $is_current ) : ?>aria-current="true"<?php endif; ?>
            data-package-name="<?php echo esc_attr(isset($package['name']) ? $package['name'] : ''); ?>"
          >
            <div class="aistma-package-title">
                <?php echo esc_html(isset($package['name']) ? $package['name'] : 'Unknown Package'); ?>
                <?php if ( $has_subscription ) : ?>
                    <span class="aistma-subscription-badge" style="background-color: #0073aa; color: white; font-size: 0.75em; padding: 2px 6px; border-radius: 3px; margin-left: 8px;">
                        <?php echo esc_html( ucfirst( $subscription_status ) ); ?>
                    </span>
                <?php endif; ?>
            </div>
            <div class="aistma-package-meta">
            <?php if (isset($package['price']) and $package['price'] > 0) { ?>
    
                <span><strong>Price:</strong> $<?php echo esc_html(isset($package['price']) ? $package['price'] : '0'); ?></span>
				<span>
            <?php
            }
                    $stories_count = intval(isset($package['stories']) ? $package['stories'] : 0);
                    $interval_count = intval(isset($package['interval_count']) ? $package['interval_count'] : 1);
                    $interval = isset($package['interval']) ? $package['interval'] : 'month';

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
                
                <?php if ( $has_subscription && $subscription_info ) : ?>
                    <div class="aistma-subscription-details" style="margin-top: 12px; padding: 8px; background-color: #f0f6fc; border: 1px solid #c3c4c7; border-radius: 4px; font-size: 0.9em;">
                        <div style="font-weight: 600; color: #0073aa; margin-bottom: 4px;">Your Subscription Details</div>
                        <?php if ( isset( $subscription_info['user_email'] ) ) : ?>
                            <div><strong>Email:</strong> <?php echo esc_html( $subscription_info['user_email'] ); ?></div>
                        <?php endif; ?>
                        <?php if ( isset( $subscription_info['credits_total'] ) && isset( $subscription_info['credits_used'] ) ) : ?>
                            <div><strong>Credits:</strong> <?php echo esc_html( $subscription_info['credits_used'] ); ?> / <?php echo esc_html( $subscription_info['credits_total'] ); ?> used</div>
                        <?php endif; ?>
                        <?php if ( isset( $subscription_info['created_at'] ) ) : ?>
                            <div><strong>Since:</strong> <?php echo esc_html( gmdate( 'M j, Y', strtotime( $subscription_info['created_at'] ) ) ); ?></div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </a>
    <?php endforeach; 
    // If we have a valid status but no matching card, show a fallback status above
    if ( $status_line && ! $matched ) {
        echo '<div id="aistma-subscription-status" class="notice notice-success" style="margin:10px 0;">' . esc_html( $status_line ) . '</div>';
    }
    ?>
    
</div>

<!-- Email field for subscription email -->
<form method="post" class="aistma-subscription-email-field" style="margin: 10px 0 16px;">
    <?php wp_nonce_field( 'aistma_subscription_email', 'aistma_subscription_email_nonce' ); ?>
    <label for="aistma_subscription_email" style="display:block;margin-bottom:6px;">
        <?php esc_html_e( 'Use this email for subscription', 'ai-story-maker' ); ?>
    </label>
    <div style="display:flex; gap:10px; align-items:center; flex-wrap:wrap;">
        <input
            type="email"
            id="aistma_subscription_email"
            name="aistma_subscription_email"
            value="<?php echo esc_attr( $current_user_email ); ?>"
            placeholder="<?php esc_attr_e( 'you@example.com', 'ai-story-maker' ); ?>"
            style="max-width: 360px; width: 100%;"
            aria-label="<?php esc_attr_e( 'Subscription email', 'ai-story-maker' ); ?>"
            required
        />
        <button type="submit" class="button button-primary"><?php esc_html_e( 'use email', 'ai-story-maker' ); ?></button>

    </div>
</form>

<script>
// Keep package links' email param in sync with the input
document.addEventListener('DOMContentLoaded', function() {
    const emailInput = document.getElementById('aistma_subscription_email');
    if (!emailInput) return;

    function aistma_update_package_links_email() {
        const email = emailInput.value || '';
        document.querySelectorAll('.aistma-package-clickable').forEach(function(a) {
            try {
                const u = new URL(a.href);
                if (email) {
                    u.searchParams.set('email', email);
                } else {
                    u.searchParams.delete('email');
                }
                a.href = u.toString();
            } catch (e) { /* ignore invalid URLs */ }
        });
    }

    emailInput.addEventListener('input', aistma_update_package_links_email);
    emailInput.addEventListener('change', aistma_update_package_links_email);
});
</script>

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
