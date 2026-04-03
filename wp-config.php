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
define( 'DB_NAME', 'mail' );

/** Database username */
define( 'DB_USER', 'Reo' );

/** Database password */
define( 'DB_PASSWORD', 'Reoikolol12345' );

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
define( 'AUTH_KEY',         'b-l,egSi8.>(&CD>SjgZr&xPUbz[`f[f-=zaYghN)^`EvQs>Hi1!`uo~8RylDXH;' );
define( 'SECURE_AUTH_KEY',  '&#kA%r/yTRIlN_.1(Qj)nb[;o1LC~#Xh!#}T}tFl=W|f|{>Du&udGp&7M<RngSiZ' );
define( 'LOGGED_IN_KEY',    'qaNe;`|m.lU=GQF d8OY(ynu2tXs6n7`0<<E2%*J>&_sP&g:.lN%W`)EiAF~HlvD' );
define( 'NONCE_KEY',        ':8J$!Q2F{fF1?j;6nci?l58jGJq(a|jAUO!O_u,qzJacAu?sk:8t,hYjGKq.roSI' );
define( 'AUTH_SALT',        '7T?4BI6#ygol&!fo? 9GDHQCDiJFGkV3aUD]Ry)>PU7;^GO`U!rj7LNnQPCXOSIw' );
define( 'SECURE_AUTH_SALT', 'w1lm<9^, i6=dE>Xp*9Qj:g}x-oE)CPPyUW:6jk KwEAjZ5NR8TY{CQ9})];g&BA' );
define( 'LOGGED_IN_SALT',   '#8t~As4m&u?@u@5W(#;YECF^0w29I~#5AW#lVENAA!c}p:en>;&|jS;6Jkds`TI(' );
define( 'NONCE_SALT',       ']5:zdR02765_:dC9G>|DkGRd@8+>WnOZs.6H2sj&n{trY,ovS?6|@/5Tyg5dDVNK' );

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
