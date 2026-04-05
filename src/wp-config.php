<?php
/**
 * Custom wp-config.php for Render (using environment variables)
 */

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

// Surface PHP errors in Render "Logs" (Apache stderr) without exposing them in HTML.
if ( getenv( 'RENDER' ) ) {
	@ini_set( 'log_errors', '1' );
	@ini_set( 'error_log', 'php://stderr' );
	register_shutdown_function(
		static function () {
			$e = error_get_last();
			if ( ! is_array( $e ) ) {
				return;
			}
			$fatal = array( E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR );
			if ( in_array( (int) $e['type'], $fatal, true ) ) {
				error_log( 'swiftfix fatal: ' . $e['message'] . ' in ' . $e['file'] . ':' . $e['line'] );
			}
		}
	);
}

// Render Postgres: DATABASE_URL / INTERNAL_DATABASE_URL is the private connection string
// (Blueprint property: connectionString). It is authoritative when set and overrides stale
// WORDPRESS_DB_* from invalid fromDatabase bindings (e.g. property "host" is not for Postgres).
$db_url = getenv( 'DATABASE_URL' );
if ( ! is_string( $db_url ) || ! preg_match( '#^postgres(ql)?://#i', $db_url ) ) {
	$db_url = getenv( 'INTERNAL_DATABASE_URL' );
}
if ( is_string( $db_url ) && preg_match( '#^postgres(ql)?://#i', $db_url ) ) {
	$p = parse_url( $db_url );
	if ( ! empty( $p['host'] ) ) {
		$host = $p['host'];
		if ( ! empty( $p['port'] ) ) {
			$host .= ':' . (int) $p['port'];
		}
		putenv( 'WORDPRESS_DB_HOST=' . $host );
		putenv( 'WORDPRESS_DB_USER=' . rawurldecode( (string) ( $p['user'] ?? '' ) ) );
		putenv( 'WORDPRESS_DB_PASSWORD=' . rawurldecode( (string) ( $p['pass'] ?? '' ) ) );
		$dbpath = isset( $p['path'] ) ? rawurldecode( ltrim( (string) $p['path'], '/' ) ) : '';
		if ( $dbpath !== '' ) {
			putenv( 'WORDPRESS_DB_NAME=' . $dbpath );
		}
		parse_str( (string) ( $p['query'] ?? '' ), $qs );
		if ( ! empty( $qs['sslmode'] ) ) {
			putenv( 'PGSSLMODE=' . $qs['sslmode'] );
			putenv( 'WORDPRESS_DB_SSLMODE=' . $qs['sslmode'] );
		}
	}
}

// Libpq / PG4WP: use "prefer" by default on Render (internal mesh + SSL can fail hard with "require").
$db_host_env = getenv( 'WORDPRESS_DB_HOST' ) ?: '';
$sslmode      = getenv( 'WORDPRESS_DB_SSLMODE' );
if ( $sslmode ) {
	putenv( 'PGSSLMODE=' . $sslmode );
} elseif ( strpos( $db_host_env, 'render.com' ) !== false || getenv( 'RENDER' ) ) {
	putenv( 'PGSSLMODE=prefer' );
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

/** PG4WP v3 (postgresql-for-wordpress) */
if ( ! defined( 'DB_DRIVER' ) ) {
	define( 'DB_DRIVER', 'pgsql' );
}
define( 'DB_TYPE', 'pgsql' );

// Public site URL: overrides stale/wrong siteurl + home in the DB (fixes unstyled wp-admin / broken asset URLs on Render).
// Optional env: WP_HOME, WP_SITEURL. Default on Render: RENDER_EXTERNAL_URL (e.g. https://swiftfixwp.onrender.com).
$swiftfix_home = getenv( 'WP_HOME' );
if ( ! is_string( $swiftfix_home ) || $swiftfix_home === '' ) {
	$swiftfix_home = getenv( 'WP_SITEURL' );
}
if ( ! is_string( $swiftfix_home ) || $swiftfix_home === '' ) {
	$swiftfix_home = getenv( 'RENDER_EXTERNAL_URL' );
}
if ( is_string( $swiftfix_home ) && $swiftfix_home !== '' ) {
	$swiftfix_home = rtrim( $swiftfix_home, '/' );
	$swiftfix_siteurl = getenv( 'WP_SITEURL' );
	if ( ! is_string( $swiftfix_siteurl ) || $swiftfix_siteurl === '' ) {
		$swiftfix_siteurl = $swiftfix_home;
	} else {
		$swiftfix_siteurl = rtrim( $swiftfix_siteurl, '/' );
	}
	if ( ! defined( 'WP_HOME' ) ) {
		define( 'WP_HOME', $swiftfix_home );
	}
	if ( ! defined( 'WP_SITEURL' ) ) {
		define( 'WP_SITEURL', $swiftfix_siteurl );
	}
}

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
