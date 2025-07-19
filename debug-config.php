<?php
// Add this to your wp-config.php file to enable detailed error reporting
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
define('WP_DEBUG_DISPLAY_ERRORS', false);
define('SCRIPT_DEBUG', true);

// Force error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', '/path/to/your/debug.log');
?> 