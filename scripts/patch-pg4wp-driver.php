<?php
/**
 * PG4WP builds a libpq keyword string. Unquoted passwords with spaces/symbols break pg_connect.
 * Render Postgres also needs sslmode=require in the string (env alone is not always enough).
 */
declare(strict_types=1);

$path = $argv[1] ?? '/usr/src/wordpress/wp-content/pg4wp/driver_pgsql.php';
$c    = file_get_contents($path);
if ($c === false) {
	fwrite(STDERR, "Cannot read $path\n");
	exit(1);
}

$old = <<<'OLD'
    if (!empty($username)) {
        $GLOBALS['pg4wp_connstr'] .= ' user=' . $username;
    }

    if (!empty($password)) {
        $GLOBALS['pg4wp_connstr'] .= ' password=' . $password;
    }

    // SSL parameters
OLD;

$new = <<<'NEW'
    if (!empty($username)) {
        $pq_u = str_replace(array('\\', "'"), array('\\\\', "\\'"), $username);
        $GLOBALS['pg4wp_connstr'] .= " user='" . $pq_u . "'";
    }

    if (!empty($password)) {
        $pq_p = str_replace(array('\\', "'"), array('\\\\', "\\'"), $password);
        $GLOBALS['pg4wp_connstr'] .= " password='" . $pq_p . "'";
    }

    if ( stripos( $GLOBALS['pg4wp_connstr'], 'sslmode=' ) === false ) {
        $sm = getenv( 'PGSSLMODE' );
        if ( $sm && $sm !== 'disable' ) {
            $GLOBALS['pg4wp_connstr'] .= ' sslmode=' . $sm;
        } elseif ( getenv( 'RENDER' ) ) {
            $GLOBALS['pg4wp_connstr'] .= ' sslmode=require';
        }
    }

    // SSL parameters
NEW;

if ( ! str_contains( $c, $old ) ) {
	fwrite(STDERR, "patch-pg4wp-driver: expected snippet not found in driver (upstream changed?)\n");
	exit(1);
}

$count = 0;
$out   = str_replace( $old, $new, $c, $count );
file_put_contents( $path, $out );
fwrite(STDERR, "patch-pg4wp-driver: applied ($count replacement(s))\n");
