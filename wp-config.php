<?php
/**
 * The base configurations of the WordPress.
 *
 * This file has the following configurations: MySQL settings, Table Prefix,
 * Secret Keys, WordPress Language, and ABSPATH. You can find more information
 * by visiting {@link http://codex.wordpress.org/Editing_wp-config.php Editing
 * wp-config.php} Codex page. You can get the MySQL settings from your web host.
 *
 * This file is used by the wp-config.php creation script during the
 * installation. You don't have to use the web site, you can just copy this file
 * to "wp-config.php" and fill in the values.
 *
 * @package WordPress
 */

// ** MySQL settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define('DB_NAME', 'edmonton_urbanslices_com');

/** MySQL database username */
define('DB_USER', 'edmontonurbansli');

/** MySQL database password */
define('DB_PASSWORD', '-m76zafa');

/** MySQL hostname */
define('DB_HOST', 'mysql.edmonton.urbanslices.com');

/** Database Charset to use in creating database tables. */
define('DB_CHARSET', 'utf8');

/** The Database Collate type. Don't change this if in doubt. */
define('DB_COLLATE', '');

/**#@+
 * Authentication Unique Keys and Salts.
 *
 * Change these to different unique phrases!
 * You can generate these using the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}
 * You can change these at any point in time to invalidate all existing cookies. This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define('AUTH_KEY',         'GcF:`/Igo~/Vw!b~bOKLT&Fjk#I8y1eAuX1Y1/D(ZA6qhK"EUuIUsw0!XZ!J?zQ?');
define('SECURE_AUTH_KEY',  '0t"Pu:lYulbqYI8:6yqiL*(oa79C+FGpkvzWcaX*6hCF2TFzq3"Ay4!8uBJhP`5A');
define('LOGGED_IN_KEY',    'ut~~C!/~_ARZqWPkZDr^f^Pu0#K(T+cZtjm3qlex+AI#N56Re$YFf7|uEpSV+o9a');
define('NONCE_KEY',        'xRz(/`fioVSM#_DA^yS6hlm"AG_7whAPVO/$Z|"644mf"S7EixTK;obkaeqK+1)R');
define('AUTH_SALT',        'ozUFiHbPz~arzoU5CF$Vu`Z``bou3ci"lU6XwL?H0Y~HK/|y*y5wB!2%?wRe:(Pg');
define('SECURE_AUTH_SALT', 'u`R+kiY|l`GsCjU@?JI##ZWJc~)_w|I!%#jBlowlvkCG&oM;!taGq?*&#)iIC#ws');
define('LOGGED_IN_SALT',   '0q%Y;7KPy"p_)&nS?FT`TqP;V*NP:M;ps|_pXDf)$$A%6nQTez@_;oQbCOr5C$w?');
define('NONCE_SALT',       'H0z)&R3eg6j|f37)TLO%&_q9waA2z4V7*/_c:O+stgEriJav$e_2Y?zurz@LlrK*');

/**#@-*/

/**
 * WordPress Database Table prefix.
 *
 * You can have multiple installations in one database if you give each a unique
 * prefix. Only numbers, letters, and underscores please!
 */
$table_prefix  = 'wp_4a5km4_';

/**
 * Limits total Post Revisions saved per Post/Page.
 * Change or comment this line out if you would like to increase or remove the limit.
 */
define('WP_POST_REVISIONS',  10);

/**
 * WordPress Localized Language, defaults to English.
 *
 * Change this to localize WordPress. A corresponding MO file for the chosen
 * language must be installed to wp-content/languages. For example, install
 * de_DE.mo to wp-content/languages and set WPLANG to 'de_DE' to enable German
 * language support.
 */
define('WPLANG', '');

/**
 * For developers: WordPress debugging mode.
 *
 * Change this to true to enable the display of notices during development.
 * It is strongly recommended that plugin and theme developers use WP_DEBUG
 * in their development environments.
 */
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
/**
 * Removing this could cause issues with your experience in the DreamHost panel
 */

if (isset($_SERVER['HTTP_HOST']) && preg_match("/^(.*)\.dream\.website$/", $_SERVER['HTTP_HOST'])) {
        $proto = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https" : "http";
        define('WP_SITEURL', $proto . '://' . $_SERVER['HTTP_HOST']);
        define('WP_HOME',    $proto . '://' . $_SERVER['HTTP_HOST']);
        define('JETPACK_STAGING_MODE', true);
}


/**
 * Set memory limit on shared
 */
define('WP_MEMORY_LIMIT', '128M');


/* That's all, stop editing! Happy blogging. */

/** Absolute path to the WordPress directory. */
if ( !defined('ABSPATH') )
	define('ABSPATH', dirname(__FILE__) . '/');

/** Sets up WordPress vars and included files. */
require_once(ABSPATH . 'wp-settings.php');
