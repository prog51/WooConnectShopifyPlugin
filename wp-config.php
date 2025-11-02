<?php
/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the installation.
 * You don't have to use the website, you can copy this file to "wp-config.php"
 * and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * Database settings
 * * Secret keys
 * * Database table prefix
 * * ABSPATH
 *
 * @link https://developer.wordpress.org/advanced-administration/wordpress/wp-config/
 *
 * @package WordPress
 */

// ** Database settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define( 'DB_NAME', 'wooshopDB' );

/** Database username */
define( 'DB_USER', 'root' );

/** Database password */
define( 'DB_PASSWORD', '' );

/** Database hostname */
define( 'DB_HOST', 'localhost' );

/** Database charset to use in creating database tables. */
define( 'DB_CHARSET', 'utf8mb4' );

/** The database collate type. Don't change this if in doubt. */
define( 'DB_COLLATE', '' );

/**#@+
 * Authentication unique keys and salts.
 *
 * Change these to different unique phrases! You can generate these using
 * the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}.
 *
 * You can change these at any point in time to invalidate all existing cookies.
 * This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define( 'AUTH_KEY',         '~wuDf]~Y?]wvyQ<z_=o*|Sbg>@`AwZFs+rv%UWr#6|t*GRE4>>b!!mWBcnEPy511' );
define( 'SECURE_AUTH_KEY',  ';QqwNdV^H$SG%VDNgm1g&SJ_(g/<Y8GSHO}=@;uc]go-#~<39]U@OZ[`X-#K(YGU' );
define( 'LOGGED_IN_KEY',    'cFx9p.i8oa;G1DCas*e{}sJ`Zd(L9utMng&_L0EXk{OtVn{C-_w(lsrrfK?5pt*h' );
define( 'NONCE_KEY',        'X(x(V8]*obubKTgnaeE!o49NuqWu`6%/CAwr(@[H7/+0)$c;Qi<IzVkgh s$L E_' );
define( 'AUTH_SALT',        '`^V>Q==zW9AhHm^&*E/R/eT#JdtgF}T%lN,0{*13`V/tC;iC,k0/q!2I z5Hehd#' );
define( 'SECURE_AUTH_SALT', 'D.+XV([)>^zUa.0i,-7.k9DxFrGyPwIKkV0f[g.8A[}!^VnIEll~havm R]dt!pn' );
define( 'LOGGED_IN_SALT',   '@P|5 -nSgk|0b/hAXVjpr]5pIY7O7u[k70_~b=9o[gw}!D|w*;NkOM}>laSe[_$+' );
define( 'NONCE_SALT',       '|$5S6@U;Bm1~7 M.7oT17@&du^W0y&2<:vM^Ek8uS@ebAFf=qoCp;C7Y]F](,qSL' );

/**#@-*/

/**
 * WordPress database table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 *
 * At the installation time, database tables are created with the specified prefix.
 * Changing this value after WordPress is installed will make your site think
 * it has not been installed.
 *
 * @link https://developer.wordpress.org/advanced-administration/wordpress/wp-config/#table-prefix
 */
$table_prefix = 'wp_';

/**
 * For developers: WordPress debugging mode.
 *
 * Change this to true to enable the display of notices during development.
 * It is strongly recommended that plugin and theme developers use WP_DEBUG
 * in their development environments.
 *
 * For information on other constants that can be used for debugging,
 * visit the documentation.
 *
 * @link https://developer.wordpress.org/advanced-administration/debug/debug-wordpress/
 */
define( 'WP_DEBUG', false );

/* Add any custom values between this line and the "stop editing" line. */



/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';
