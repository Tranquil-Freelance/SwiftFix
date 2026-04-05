<?php
/**
 * PG4WP v3 driver fixes for WordPress on PostgreSQL:
 * - Quote libpq user/password; append sslmode when missing (Render).
 * - Implement wpsqli_fetch_field (upstream throws; WordPress load_col_info() needs it).
 * - Implement wpsqli_connect_errno (upstream throws; wpdb::bail() when show_errors).
 * - Add wpsqli_character_set_name (missing; wpdb strip_invalid_text_for_column()).
 */
declare(strict_types=1);

$path = $argv[1] ?? '/usr/src/wordpress/wp-content/pg4wp/driver_pgsql.php';
$c    = file_get_contents($path);
if ($c === false) {
	fwrite(STDERR, "Cannot read $path\n");
	exit(1);
}

$patches = array(
	array(
		'label' => 'libpq user/password + sslmode',
		'old'   => <<<'OLD'
    if (!empty($username)) {
        $GLOBALS['pg4wp_connstr'] .= ' user=' . $username;
    }

    if (!empty($password)) {
        $GLOBALS['pg4wp_connstr'] .= ' password=' . $password;
    }

    // SSL parameters
OLD,
		'new'   => <<<'NEW'
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
            $GLOBALS['pg4wp_connstr'] .= ' sslmode=prefer';
        }
    }

    // SSL parameters
NEW,
	),
	array(
		'label' => 'wpsqli_connect_errno',
		'old'   => <<<'OLD'
function wpsqli_connect_errno()
{
    throw new \Exception("PG4WP: Not Yet Implemented");
}
OLD,
		'new'   => <<<'NEW'
function wpsqli_connect_errno()
{
    $conn = $GLOBALS['pg4wp_conn'] ?? false;
    if ($conn instanceof \PgSql\Connection) {
        return 0;
    }
    if (function_exists('wpsqli_connect_error')) {
        $err = wpsqli_connect_error();
        if ($err !== '' && $err !== null) {
            return 1;
        }
    }
    return 0;
}
NEW,
	),
	array(
		'label' => 'wpsqli_fetch_field',
		'old'   => <<<'OLD'
function wpsqli_fetch_field($result)
{
    throw new \Exception("PG4WP: Not Yet Implemented");
    // mysqli_fetch_field => pg_field_table (resource $result, int $field_number, bool $oid_only = false): mixed
    // Returns the name or oid of the table of the field. There's no direct function to mimic mysqli_fetch_field completely.
    //pg_field_table($result, $field_number);
}
OLD,
		'new'   => <<<'NEW'
function wpsqli_fetch_field($result)
{
    if (!($result instanceof \PgSql\Result)) {
        return false;
    }
    static $wpsqli_last_result_id = null;
    static $wpsqli_field_index    = 0;
    $rid = spl_object_id($result);
    if ($wpsqli_last_result_id !== $rid) {
        $wpsqli_last_result_id = $rid;
        $wpsqli_field_index    = 0;
    }
    $n = pg_num_fields($result);
    if ($wpsqli_field_index >= $n) {
        return false;
    }
    $i = $wpsqli_field_index++;
    $o = new \stdClass();

    $name = pg_field_name($result, $i);
    if ($name === false) {
        return false;
    }
    $o->name    = $name;
    $o->orgname = $name;
    $tbl        = pg_field_table($result, $i);
    $o->table   = $tbl !== false ? $tbl : '';
    $o->orgtable = $o->table;
    $o->def     = '';

    $size = pg_field_size($result, $i);
    $o->max_length = ($size !== false && $size >= 0) ? $size : 0;
    $o->length     = $o->max_length;
    $o->charsetnr  = 0;
    $o->flags      = 0;
    $o->type       = pg_field_type($result, $i);
    if ($o->type === false) {
        $o->type = '';
    }
    $o->decimals = 0;

    $ptype = is_string($o->type) ? strtolower($o->type) : '';
    $o->numeric = in_array(
        $ptype,
        array('int2', 'int4', 'int8', 'float4', 'float8', 'numeric', 'decimal', 'oid', 'serial', 'bigserial', 'smallserial'),
        true
    );
    $o->blob         = ($ptype === 'bytea');
    $o->not_null     = false;
    $o->primary_key  = false;
    $o->multiple_key = false;
    $o->unique_key   = false;
    $o->unsigned     = false;
    $o->zerofill     = false;

    return $o;
}
NEW,
	),
	array(
		'label' => 'wpsqli_character_set_name',
		'old'   => <<<'OLD'
function wpsqli_set_charset(&$connection, $charset)
{
    // mysqli_set_charset => pg_set_client_encoding (resource $connection, string $encoding): int
    // Sets the client encoding.
    return pg_set_client_encoding($connection, "UTF8");
}

/**
 * Escapes special characters in a string for use in an SQL statement.
OLD,
		'new'   => <<<'NEW'
function wpsqli_set_charset(&$connection, $charset)
{
    // mysqli_set_charset => pg_set_client_encoding (resource $connection, string $encoding): int
    // Sets the client encoding.
    return pg_set_client_encoding($connection, "UTF8");
}

/**
 * Returns the connection character set (WordPress calls mysqli_character_set_name).
 *
 * @param PgSql\Connection $connection The pg connection resource.
 * @return string
 */
function wpsqli_character_set_name(&$connection)
{
    $enc = pg_client_encoding($connection);
    if ($enc === false || $enc === '') {
        return 'utf8';
    }
    if (strcasecmp($enc, 'UTF8') === 0 || strcasecmp($enc, 'UNICODE') === 0) {
        return 'utf8';
    }

    return strtolower((string) $enc);
}

/**
 * Escapes special characters in a string for use in an SQL statement.
NEW,
	),
);

$out = $c;
foreach ($patches as $p) {
	if (!str_contains($out, $p['old'])) {
		fwrite(STDERR, "patch-pg4wp-driver: [{$p['label']}] expected snippet not found (upstream changed?)\n");
		exit(1);
	}
	$count = 0;
	$out   = str_replace($p['old'], $p['new'], $out, $count);
	if ($count !== 1) {
		fwrite(STDERR, "patch-pg4wp-driver: [{$p['label']}] expected 1 replacement, got {$count}\n");
		exit(1);
	}
	fwrite(STDERR, "patch-pg4wp-driver: applied {$p['label']}\n");
}

if (file_put_contents($path, $out) === false) {
	fwrite(STDERR, "patch-pg4wp-driver: cannot write {$path}\n");
	exit(1);
}
