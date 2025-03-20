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
 * * Localized language
 * * ABSPATH
 *
 * @link https://wordpress.org/support/article/editing-wp-config-php/
 *
 * @package WordPress
 */

// ** Database settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define( 'DB_NAME', 'local' );

/** Database username */
define( 'DB_USER', 'root' );

/** Database password */
define( 'DB_PASSWORD', 'root' );

/** Database hostname */
define( 'DB_HOST', 'localhost' );

/** Database charset to use in creating database tables. */
define( 'DB_CHARSET', 'utf8' );

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
define( 'AUTH_KEY',          'T<qdlRf9A14ySZ:&ezC>@[2F{a#KTI%g`=5By5Ui7LWuLdc;V(0Y1_|jeJY%pmRa' );
define( 'SECURE_AUTH_KEY',   '^^7]OMEUl+PLh[T/O%Il=D4{Cy9o@I6`Tp9!mGXZuh7Ro=t G)~R(n6mR}?F;3|j' );
define( 'LOGGED_IN_KEY',     '$bLF;|; nUPs]/]o40gZsu@PV}1c?DnGX{/Gglcq >NLEq?I5kt0?=%yYY3Ww.w!' );
define( 'NONCE_KEY',         'Rr}U{H^X)4y6]nq0SY_PB,} {sJ`}3><P;=Os[>-P]H{(GSuM|_LaV.D3j*hnmo=' );
define( 'AUTH_SALT',         '%<Z1ls2;}Zr4u4pN_sXE[~|7.tc/P>gfbCdG2N hD4==9;ST4Lq9,+M<;OaPrj;,' );
define( 'SECURE_AUTH_SALT',  '[O#NTl0ei}@k;go$b,,]y}-Wx:fT(![H9hp`4y<uWwA^qD&ymipv`>&OWV3QR@jy' );
define( 'LOGGED_IN_SALT',    '5xGO.T()~sea:3Jz$dd?Ia o-5u94]-:L Dy@sh}W`#1GQTde);kX7aqVE7`+[/|' );
define( 'NONCE_SALT',        'j%Z6,cwE~#I#n_m(lNiFbbMd2X^INtXXihy^nm}-I6Yw<)7&R!Na}%ag|A#Mh4pb' );
define( 'WP_CACHE_KEY_SALT', '5_Ko L/p_eS8X<VZX-(b92wP=c^y?D5Wsm7:b%lvltp;%]eTDRX4(V5igOw)W0gq' );


/**#@-*/

/**
 * WordPress database table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 */
$table_prefix = 'wp_';


/* Add any custom values between this line and the "stop editing" line. */



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
 * @link https://wordpress.org/support/article/debugging-in-wordpress/
 */
if ( ! defined( 'WP_DEBUG' ) ) {
	define( 'WP_DEBUG', false );
}

define( 'WP_ENVIRONMENT_TYPE', 'local' );
/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';
