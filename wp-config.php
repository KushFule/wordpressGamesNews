<?php
/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the installation.
 * You don't have to use the web site, you can copy this file to "wp-config.php"
 * and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * Database settings
 * * Secret keys
 * * Database table prefix
 * * ABSPATH
 *
 * @link https://wordpress.org/documentation/article/editing-wp-config-php/
 *
 * @package WordPress
 */

// ** Database settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define( 'DB_NAME', 'wordpress' );

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
define( 'AUTH_KEY',         ';#lO7Tn@nRV<B$y`-w%`icp{|a+AhO-%jJng$]4:5zu#mohNb;Is @Do*!2joJN>' );
define( 'SECURE_AUTH_KEY',  'tJaM%3l_*>KT=AwTsoZ{@8)=eG Bb%4/nq7wF#zB-rF&#k+l9%tm!ORY-aY/%*/4' );
define( 'LOGGED_IN_KEY',    'C%pAN._B<u=Ya6%3-A+|3j+8:Rt_TXDbH14:dTIc[t>n]lSq^xTS 5Xw$DBqrCo8' );
define( 'NONCE_KEY',        'b&3?vPg%XXoV?B_ 5LFc{r^@hN@ Pc@@pXf4~/t8NdLTfiH!<yNTFC.2>}Cc_wv3' );
define( 'AUTH_SALT',        '=(4nQ+p0H=DdTPON+AcwifCTPt w+9uLB1v_D%!5H b;o;nmR?;{@uID1,2IN[|T' );
define( 'SECURE_AUTH_SALT', 'dDN>`:AcZm<^ol&XsuuNDt}a`Wn3/}4k/7yc-D|.0(Puy-.`orV*()0W,E$o,%S!' );
define( 'LOGGED_IN_SALT',   '4=[=|Bv1*d+6rz?olBNz~Bm|x0Pc>a2~3psrZ?tUHve(MQ<&Bi&q*s~c1;gtd@h]' );
define( 'NONCE_SALT',       ')9lQg1,<H]b!B{|}sF]Dxv/E;WaUXT_!0j-{*X8FiVwK$k(AaFYEaw?89/kK4Jv>' );

/**#@-*/

/**
 * WordPress database table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
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
 * @link https://wordpress.org/documentation/article/debugging-in-wordpress/
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
