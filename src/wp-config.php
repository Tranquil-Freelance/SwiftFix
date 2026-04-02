<?php
/**
 * Custom wp-config.php for Render (using environment variables)
 */

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/** The name of the database for WordPress */
define( 'DB_NAME', getenv('WORDPRESS_DB_NAME') ?: 'database' );

/** MySQL database username */
define( 'DB_USER', getenv('WORDPRESS_DB_USER') ?: 'user' );

/** MySQL database password */
define( 'DB_PASSWORD', getenv('WORDPRESS_DB_PASSWORD') ?: 'password' );

/** MySQL hostname */
define( 'DB_HOST', getenv('WORDPRESS_DB_HOST') ?: 'localhost' );

/** Database Charset */
define( 'DB_CHARSET', 'utf8' );

/** The Database Collate type. Don't change this if in doubt. */
define( 'DB_COLLATE', '' );

/** PG4WP support (Postgres adapter) */
define( 'DB_TYPE', 'pgsql' ); // This tells PG4WP to use Postgres

/**#@+
 * Authentication Unique Keys and Salts.
 * Get these from https://api.wordpress.org/secret-key/1.1/salt/
 */
define( 'AUTH_KEY',         getenv('AUTH_KEY')         ?: 'put your unique phrase here' );
define( 'SECURE_AUTH_KEY',  getenv('SECURE_AUTH_KEY')  ?: 'put your unique phrase here' );
define( 'LOGGED_IN_KEY',    getenv('LOGGED_IN_KEY')    ?: 'put your unique phrase here' );
define( 'NONCE_KEY',        getenv('NONCE_KEY')        ?: 'put your unique phrase here' );
define( 'AUTH_SALT',        getenv('AUTH_SALT')        ?: 'put your unique phrase here' );
define( 'SECURE_AUTH_SALT', getenv('SECURE_AUTH_SALT') ?: 'put your unique phrase here' );
define( 'LOGGED_IN_SALT',   getenv('LOGGED_IN_SALT')   ?: 'put your unique phrase here' );
define( 'NONCE_SALT',       getenv('NONCE_SALT')       ?: 'put your unique phrase here' );
/**#@-*/

/**
 * WordPress Database Table prefix.
 */
$table_prefix = 'wp_';

/**
 * For developers: WordPress debugging mode.
 */
define( 'WP_DEBUG', getenv('WP_DEBUG') === 'true' );

/* That's all, stop editing! Happy publishing. */

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';
