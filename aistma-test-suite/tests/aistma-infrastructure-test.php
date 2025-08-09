<?php
/**
 * Infrastructure Test
 *
 * @package AISTMA_Test_Suite
 * @since 1.0.0
 */

class Infrastructure_Test extends AISTMA_Test_Base {

    /**
     * Test name
     */
    protected $test_name = 'Comprehensive Infrastructure Check';

    /**
     * Test description
     */
    protected $test_description = 'Validates plugin activation, installation, update status, and compatibility with WordPress, PHP, and MySQL.';

    /**
     * Test category
     */
    protected $test_category = 'Health';

    /**
     * Run the test
     */
    public function run_test() {
        $this->check_wordpress_loaded();

        // 1) Active
        $this->log_info('Checking if AI Story Maker plugin is active...');
        $this->check_aistma_active();
        $this->log_info('✅ Plugin is active.');

        // 2) Installed (files/directories exist)
        $plugin_slug = 'ai-story-maker';
        $plugin_main = $plugin_slug . '/ai-story-maker.php';
        $plugin_dir  = WP_PLUGIN_DIR . '/' . $plugin_slug;
        $plugin_file = WP_PLUGIN_DIR . '/' . $plugin_main;

        $this->log_info('Checking if plugin files are present...');
        $this->check_directory_exists($plugin_dir);
        $this->check_file_exists($plugin_file);
        $this->log_info('✅ Plugin files found at: ' . $plugin_file);

        // Read plugin headers (version/requirements)
        if (!function_exists('get_plugin_data')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }
        $plugin_data = get_plugin_data($plugin_file, false, false);
        $headers_extra = get_file_data($plugin_file, array(
            'Tested'      => 'Tested up to',
            'RequiresWP'  => 'Requires at least', // fallback if not populated in get_plugin_data
        ));

        $version           = isset($plugin_data['Version']) ? $plugin_data['Version'] : '0.0.0';
        $requires_php      = isset($plugin_data['RequiresPHP']) ? $plugin_data['RequiresPHP'] : '';
        $requires_wp       = !empty($plugin_data['RequiresWP']) ? $plugin_data['RequiresWP'] : (isset($headers_extra['RequiresWP']) ? $headers_extra['RequiresWP'] : '');
        $tested_up_to_wp   = isset($headers_extra['Tested']) ? $headers_extra['Tested'] : '';

        $this->log_info('Detected plugin version: ' . $version);
        if ($requires_php) {
            $this->log_info('Requires PHP: ' . $requires_php);
        }
        if ($requires_wp) {
            $this->log_info('Requires WordPress: ' . $requires_wp);
        }
        if ($tested_up_to_wp) {
            $this->log_info('Tested up to WordPress: ' . $tested_up_to_wp);
        }

        // 3) Updated (no available update)
        $this->log_info('Checking if plugin has pending updates...');
        $update_info = get_site_transient('update_plugins');
        $plugin_basename = $plugin_main; // same as plugin file relative path
        $has_update = is_object($update_info) && isset($update_info->response[$plugin_basename]);
        if ($has_update) {
            $new_version = $update_info->response[$plugin_basename]->new_version ?? 'unknown';
            throw new Exception('Plugin has an available update. Current: ' . $version . ' → Available: ' . $new_version);
        }
        $this->log_info('✅ Plugin is up to date (no updates available).');

        // 4) WordPress compatibility
        $wp_version = get_bloginfo('version');
        $this->log_info('Current WordPress version: ' . $wp_version);
        if (!empty($requires_wp) && version_compare($wp_version, $requires_wp, '<')) {
            throw new Exception('WordPress version is below plugin minimum requirement. Requires at least: ' . $requires_wp . ', current: ' . $wp_version);
        }
        if (!empty($tested_up_to_wp) && version_compare($wp_version, $tested_up_to_wp, '>')) {
            $this->log_warning('Current WordPress (' . $wp_version . ") is newer than plugin's tested version (" . $tested_up_to_wp . '). Proceed with caution.');
        }
        $this->log_info('✅ WordPress compatibility check passed.');

        // 5) PHP compatibility
        $php_version = PHP_VERSION;
        $this->log_info('Current PHP version: ' . $php_version);
        if (!empty($requires_php) && version_compare($php_version, $requires_php, '<')) {
            throw new Exception('PHP version is below plugin minimum requirement. Requires PHP: ' . $requires_php . ', current: ' . $php_version);
        }
        $this->log_info('✅ PHP compatibility check passed.');

        // 6) MySQL/MariaDB compatibility (basic guardrails)
        global $wpdb;
        $db_version_raw = method_exists($wpdb, 'db_version') ? $wpdb->db_version() : '';
        $server_info = property_exists($wpdb, 'db_server_info') ? (string) $wpdb->db_server_info : '';
        $this->log_info('Database server info: ' . ($server_info ?: 'n/a'));
        $this->log_info('Database version: ' . ($db_version_raw ?: 'n/a'));

        $is_mariadb = stripos($server_info . ' ' . $db_version_raw, 'mariadb') !== false;
        $db_version_for_compare = $this->extract_version_number($db_version_raw);

        if ($is_mariadb) {
            // WordPress recommends MariaDB >= 10.4
            if ($db_version_for_compare && version_compare($db_version_for_compare, '10.4', '<')) {
                throw new Exception('MariaDB version too low. Requires >= 10.4, current: ' . $db_version_raw);
            }
        } else {
            // WordPress recommends MySQL >= 5.7
            if ($db_version_for_compare && version_compare($db_version_for_compare, '5.7', '<')) {
                throw new Exception('MySQL version too low. Requires >= 5.7, current: ' . $db_version_raw);
            }
        }
        $this->log_info('✅ Database compatibility check passed.');

        // 7) Registration / Subscription status (informational)
        $registration_summary = 'unknown';
        if (class_exists('exedotcom\\aistorymaker\\AISTMA_Story_Generator')) {
            $generator = new \exedotcom\aistorymaker\AISTMA_Story_Generator();
            $status = $generator->aistma_get_subscription_status();

            $valid   = isset($status['valid']) ? (bool) $status['valid'] : false;
            $domain  = $status['domain'] ?? '';
            $package = $status['package_name'] ?? '';
            $credits = isset($status['credits_remaining']) ? intval($status['credits_remaining']) : null;
            $msg     = $status['message'] ?? ($status['error'] ?? '');

            if ($valid) {
                $registration_summary = 'Registered (active) - Domain: ' . $domain . ', Plan: ' . $package . ', Credits: ' . ($credits !== null ? $credits : 'n/a');
                $this->log_info('✅ Registration: ' . $registration_summary);
            } else {
                $state = ($msg === 'No credits remaining') ? 'Registered (no credits)' : 'Not registered';
                $registration_summary = $state . ' - Domain: ' . $domain
                    . ($package ? ', Plan: ' . $package : '')
                    . ($credits !== null ? ', Credits: ' . $credits : '')
                    . ($msg ? ', Message: ' . $msg : '');
                $this->log_warning('ℹ️ Registration: ' . $registration_summary);
            }
        } else {
            $this->log_warning('AISTMA_Story_Generator class not found; cannot determine registration status.');
            $registration_summary = 'unavailable';
        }

        return 'All health checks passed for AI Story Maker v' . $version
            . ' on WordPress ' . $wp_version
            . ' / PHP ' . $php_version
            . ' / DB ' . ($db_version_raw ?: 'unknown')
            . ' | Registration: ' . $registration_summary;
    }

    /**
     * Extract X.Y[.Z] version from a server string
     */
    private function extract_version_number($input) {
        if (!is_string($input) || $input === '') {
            return '';
        }
        if (preg_match('/(\d+\.\d+(?:\.\d+)?)/', $input, $m)) {
            return $m[1];
        }
        return '';
    }
}


