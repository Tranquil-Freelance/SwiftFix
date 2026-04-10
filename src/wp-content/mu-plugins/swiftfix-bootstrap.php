<?php
/**
 * Plugin Name: CAE Fix — automated setup
 * Description: Optional one-click setup (Elementor import, pages, CAE Fix theme mods). Not required if you only install Rhye + child from zip and build pages yourself.
 *
 * Theme-only workflow: delete this file from mu-plugins, or in wp-config.php (before wp-settings.php) add:
 *   define( 'SWIFTFIX_AUTO_SETUP', false );
 * Also supported: env SWIFTFIX_AUTO_SETUP=0 (e.g. Render). Re-run automation: delete options swiftfix_full_bootstrap_done and swiftfix_bootstrap_extra_pages_seeded (and swiftfix_portfolio_seed_done if needed).
 * Legal pages (Privacy, Terms, Contact): delete option swiftfix_legal_pages_seeded_v1 to run the one-time seed again.
 * Service detail pages + inner template: delete option swiftfix_branded_inner_pages_v1 to re-run (after legal pages exist).
 * Demo images: rhye-child/assets/bundled/ (rewritten into Elementor data; no CDN on Render). Re-run rewrite: delete option swiftfix_remote_images_localized_v3.
 * Homepage template: default is CAE Fix PHP landing (page-services-landing.php). Use Elementor home instead: env SWIFTFIX_HOME_TEMPLATE=elementor
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( defined( 'SWIFTFIX_AUTO_SETUP' ) && ! SWIFTFIX_AUTO_SETUP ) {
	return;
}

$swiftfix_auto_off = getenv( 'SWIFTFIX_AUTO_SETUP' );
if ( false !== $swiftfix_auto_off && in_array( strtolower( (string) $swiftfix_auto_off ), array( '0', 'false', 'no', 'off' ), true ) ) {
	return;
}

// After plugins (rhye-core) register CPTs such as arts_portfolio_item.
add_action( 'init', 'swiftfix_bootstrap_run', 25 );
// One-time: Privacy, Terms, and fallback Contact page (runs before service pages + extra import).
add_action( 'init', 'swiftfix_bootstrap_seed_legal_pages', 39 );
// One-time: CAE Fix service detail pages + assign CAE Fix inner template to legal/contact pages.
add_action( 'init', 'swiftfix_bootstrap_seed_branded_inner_pages', 40 );
// One-time import for sites that finished bootstrap before bundled pages existed.
add_action( 'init', 'swiftfix_bootstrap_extra_pages_run', 41 );
// One-time: download remote Elementor images + portfolio thumbs (sites that imported before sideload existed).
add_action( 'init', 'swiftfix_bootstrap_migrate_remote_images', 43 );
// One-time: switch front page to CAE Fix PHP landing (existing installs).
add_action( 'init', 'swiftfix_bootstrap_one_time_php_home', 46 );

/**
 * Fix Home page meta if bootstrap stored _elementor_page_settings as JSON text (Elementor 3.x fatals).
 */
add_action( 'admin_init', 'swiftfix_repair_elementor_page_settings_meta', 1 );

/**
 * @return void
 */
function swiftfix_repair_elementor_page_settings_meta() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	if ( get_option( 'swiftfix_elementor_page_settings_repaired' ) ) {
		return;
	}
	$home_id = (int) get_option( 'page_on_front' );
	if ( ! $home_id ) {
		return;
	}
	$raw = get_post_meta( $home_id, '_elementor_page_settings', true );
	if ( ! is_string( $raw ) || $raw === '' ) {
		update_option( 'swiftfix_elementor_page_settings_repaired', true );

		return;
	}
	$decoded = json_decode( $raw, true );
	if ( ! is_array( $decoded ) ) {
		return;
	}
	update_post_meta( $home_id, '_elementor_page_settings', $decoded );
	update_option( 'swiftfix_elementor_page_settings_repaired', true );
}

/**
 * @return void
 */
function swiftfix_bootstrap_run() {
	if ( get_option( 'swiftfix_full_bootstrap_done' ) ) {
		return;
	}
	if ( ! function_exists( 'is_blog_installed' ) || ! is_blog_installed() ) {
		return;
	}
	if ( defined( 'WP_INSTALLING' ) && WP_INSTALLING ) {
		return;
	}
	if ( wp_doing_ajax() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
		return;
	}
	if ( defined( 'WP_CLI' ) && WP_CLI ) {
		return;
	}

	delete_option( 'swiftfix_bootstrap_last_error' );

	try {
		swiftfix_bootstrap_execute();
	} catch ( Throwable $e ) {
		update_option( 'swiftfix_bootstrap_last_error', $e->getMessage() );
		error_log( 'CAE Fix bootstrap: ' . $e->getMessage() ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
	}
}

/**
 * @return void
 */
function swiftfix_bootstrap_execute() {
	require_once ABSPATH . 'wp-admin/includes/plugin.php';

	$child = 'rhye-child';
	if ( wp_get_theme( $child )->exists() && get_option( 'stylesheet' ) !== $child ) {
		switch_theme( $child );
	}

	if ( ! wp_get_theme( $child )->exists() ) {
		update_option( 'swiftfix_bootstrap_last_error', 'Child theme rhye-child is missing from wp-content/themes.' );
		return;
	}

	$elementor_main = WP_PLUGIN_DIR . '/elementor/elementor.php';
	if ( ! file_exists( $elementor_main ) ) {
		if ( ! swiftfix_bootstrap_install_elementor() ) {
			update_option( 'swiftfix_bootstrap_last_error', 'Could not install Elementor from wordpress.org (network or permissions).' );
			return;
		}
	}

	$act = activate_plugin( 'elementor/elementor.php', '', false, true );
	if ( is_wp_error( $act ) ) {
		update_option( 'swiftfix_bootstrap_last_error', 'Elementor activate: ' . $act->get_error_message() );
		return;
	}

	if ( ! defined( 'ELEMENTOR_VERSION' ) ) {
		// activate_plugin should have loaded the main file; fail softly on next request.
		return;
	}

	$json_path = trailingslashit( get_stylesheet_directory() ) . 'electrician-template.json';
	if ( ! is_readable( $json_path ) ) {
		update_option( 'swiftfix_bootstrap_last_error', 'Missing electrician-template.json in active child theme.' );
		update_option( 'swiftfix_full_bootstrap_done', true );
		return;
	}

	$raw  = file_get_contents( $json_path );
	$data = json_decode( $raw, true );
	if ( empty( $data['content'] ) || ! is_array( $data['content'] ) ) {
		update_option( 'swiftfix_bootstrap_last_error', 'Invalid Elementor JSON in electrician-template.json.' );
		update_option( 'swiftfix_full_bootstrap_done', true );
		return;
	}

	swiftfix_bootstrap_localize_remote_images_in_data( $data );

	$home_id = swiftfix_bootstrap_get_or_create_page( 'home', 'Home' );
	$blog_id = swiftfix_bootstrap_get_or_create_page( 'blog', 'Blog' );
	if ( ! $home_id || ! $blog_id || is_wp_error( $home_id ) || is_wp_error( $blog_id ) ) {
		update_option( 'swiftfix_bootstrap_last_error', 'Could not create Home or Blog pages.' );
		return;
	}

	swiftfix_bootstrap_apply_elementor_to_page( (int) $home_id, $data );

	update_option( 'show_on_front', 'page' );
	update_option( 'page_on_front', (int) $home_id );
	update_option( 'page_for_posts', (int) $blog_id );

	$name = getenv( 'SWIFTFIX_SITE_NAME' );
	if ( ! is_string( $name ) || $name === '' ) {
		$name = 'CAE Fix';
	}
	update_option( 'blogname', $name );

	swiftfix_bootstrap_polish_home_title( (int) $home_id );

	swiftfix_bootstrap_seed_theme_mods( $name );

	swiftfix_bootstrap_import_bundled_elementor_pages();
	swiftfix_bootstrap_seed_portfolio_if_needed();
	swiftfix_bootstrap_attach_portfolio_featured_images();
	swiftfix_bootstrap_assign_home_template( (int) $home_id );
	swiftfix_bootstrap_ensure_legal_pages_once();
	swiftfix_bootstrap_ensure_branded_inner_pages_once();
	swiftfix_bootstrap_create_primary_menu();

	flush_rewrite_rules( false );

	update_option( 'swiftfix_bootstrap_extra_pages_seeded', true );
	update_option( 'swiftfix_full_bootstrap_done', true );
	update_option( 'swiftfix_legal_pages_seeded_v1', true );
	update_option( 'swiftfix_branded_inner_pages_v1', true );
}

/**
 * @param int $home_id Front page ID.
 * @return void
 */
function swiftfix_bootstrap_polish_home_title( $home_id ) {
	$title = getenv( 'SWIFTFIX_HOME_TITLE' );
	if ( ! is_string( $title ) || $title === '' ) {
		$bn = get_option( 'blogname' );
		$title = is_string( $bn ) && $bn !== '' ? $bn : 'CAE Fix';
	}
	wp_update_post(
		array(
			'ID'         => (int) $home_id,
			'post_title' => $title,
		)
	);
}

/**
 * Fill CAE Fix Customizer keys only when not already stored (does not overwrite user choices).
 *
 * @param string $business_name Default business name (matches blogname after bootstrap).
 * @return void
 */
function swiftfix_bootstrap_seed_theme_mods( $business_name = '' ) {
	if ( ! is_string( $business_name ) || $business_name === '' ) {
		$business_name = 'CAE Fix';
	}
	$mods = get_theme_mods();
	if ( ! is_array( $mods ) ) {
		$mods = array();
	}
	$defaults = array(
		'sf_business_name'      => $business_name,
		'sf_phone'              => '0800 123 4567',
		'sf_email'              => 'hello@caefix.co.uk',
		'sf_emergency_tagline'  => 'Burst pipe? Power cut? No heating?',
		'sf_rating'             => '4.9',
		'sf_review_count'       => '620',
		'sf_reg_info'           => 'Registered in England & Wales. Gas Safe Reg. No. 123456.',
	);
	foreach ( $defaults as $key => $value ) {
		if ( ! array_key_exists( $key, $mods ) ) {
			set_theme_mod( $key, $value );
		}
	}
}

/**
 * Import JSON from child theme bundled-elementor/{pages,services}/ as published pages (skips demo index).
 *
 * @return void
 */
function swiftfix_bootstrap_import_bundled_elementor_pages() {
	if ( ! defined( 'ELEMENTOR_VERSION' ) ) {
		return;
	}
	$base = trailingslashit( get_stylesheet_directory() ) . 'bundled-elementor/';
	foreach ( array( 'pages', 'services' ) as $sub ) {
		$dir = $base . $sub;
		if ( ! is_dir( $dir ) ) {
			continue;
		}
		foreach ( glob( $dir . '/*.json' ) ?: array() as $file ) {
			if ( stripos( basename( $file ), 'Demo Index' ) !== false ) {
				continue;
			}
			$raw = file_get_contents( $file );
			if ( ! is_string( $raw ) || $raw === '' ) {
				continue;
			}
			$data = json_decode( $raw, true );
			if ( empty( $data['content'] ) || ! is_array( $data['content'] ) ) {
				continue;
			}
			swiftfix_bootstrap_localize_remote_images_in_data( $data );
			$slug  = sanitize_title( pathinfo( $file, PATHINFO_FILENAME ) );
			$title = isset( $data['title'] ) && is_string( $data['title'] ) && $data['title'] !== ''
				? $data['title']
				: ucwords( preg_replace( '/[\s_-]+/', ' ', $slug ) );
			$page_id = swiftfix_bootstrap_get_or_create_page( $slug, $title );
			if ( ! $page_id || is_wp_error( $page_id ) ) {
				continue;
			}
			swiftfix_bootstrap_apply_elementor_to_page( (int) $page_id, $data );
		}
	}
	if ( class_exists( '\Elementor\Plugin' ) ) {
		\Elementor\Plugin::$instance->files_manager->clear_cache();
	}
}

/**
 * Placeholder portfolio posts so Rhye sliders querying arts_portfolio_item are not empty.
 *
 * @return void
 */
function swiftfix_bootstrap_seed_portfolio_if_needed() {
	if ( get_option( 'swiftfix_portfolio_seed_done' ) ) {
		return;
	}
	if ( ! post_type_exists( 'arts_portfolio_item' ) ) {
		return;
	}
	$existing = get_posts(
		array(
			'post_type'              => 'arts_portfolio_item',
			'post_status'            => 'any',
			'posts_per_page'         => 1,
			'fields'                 => 'ids',
			'no_found_rows'          => true,
			'update_post_meta_cache' => false,
			'update_post_term_cache' => false,
		)
	);
	if ( ! empty( $existing ) ) {
		update_option( 'swiftfix_portfolio_seed_done', true );

		return;
	}
	$titles = array(
		'Emergency plumbing repair',
		'Consumer unit upgrade',
		'Boiler service and safety check',
		'Kitchen rewiring',
		'Bathroom electrical',
		'Landlord safety certificate',
	);
	$urls   = swiftfix_bootstrap_demo_team_image_urls();
	foreach ( $titles as $i => $post_title ) {
		$pid = wp_insert_post(
			array(
				'post_title'  => $post_title,
				'post_status' => 'publish',
				'post_type'   => 'arts_portfolio_item',
				'menu_order'  => $i,
			),
			true
		);
		if ( is_wp_error( $pid ) || ! $pid ) {
			continue;
		}
		if ( isset( $urls[ $i ] ) ) {
			$base = basename( wp_parse_url( $urls[ $i ], PHP_URL_PATH ) );
			$aid  = $base ? swiftfix_bootstrap_attachment_from_theme_bundled( $base ) : 0;
			if ( ! $aid ) {
				$aid = swiftfix_bootstrap_ensure_local_attachment( $urls[ $i ] );
			}
			if ( $aid > 0 ) {
				set_post_thumbnail( (int) $pid, $aid );
			}
		}
	}
	update_option( 'swiftfix_portfolio_seed_done', true );
}

/**
 * @return void
 */
function swiftfix_bootstrap_extra_pages_run() {
	if ( get_option( 'swiftfix_bootstrap_extra_pages_seeded' ) ) {
		return;
	}
	if ( ! get_option( 'swiftfix_full_bootstrap_done' ) ) {
		return;
	}
	if ( ! function_exists( 'is_blog_installed' ) || ! is_blog_installed() ) {
		return;
	}
	if ( defined( 'WP_INSTALLING' ) && WP_INSTALLING ) {
		return;
	}
	if ( wp_doing_ajax() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
		return;
	}
	if ( defined( 'WP_CLI' ) && WP_CLI ) {
		return;
	}

	delete_option( 'swiftfix_bootstrap_last_error' );

	try {
		$name = get_option( 'blogname' );
		if ( ! is_string( $name ) || $name === '' ) {
			$name = 'CAE Fix';
		}
		swiftfix_bootstrap_seed_theme_mods( $name );
		swiftfix_bootstrap_import_bundled_elementor_pages();
		swiftfix_bootstrap_seed_portfolio_if_needed();
		swiftfix_bootstrap_attach_portfolio_featured_images();
		swiftfix_bootstrap_create_primary_menu();
		$home_id = (int) get_option( 'page_on_front' );
		if ( $home_id ) {
			swiftfix_bootstrap_assign_home_template( $home_id );
			swiftfix_bootstrap_polish_home_title( $home_id );
		}
		flush_rewrite_rules( false );
		update_option( 'swiftfix_bootstrap_extra_pages_seeded', true );
	} catch ( Throwable $e ) {
		update_option( 'swiftfix_bootstrap_last_error', $e->getMessage() );
		error_log( 'CAE Fix bootstrap (extra pages): ' . $e->getMessage() ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
	}
}

/**
 * Demo image URLs used in electrician-template.json team avatars (and portfolio featured images).
 *
 * @return string[]
 */
function swiftfix_bootstrap_demo_team_image_urls() {
	return array(
		'https://artemsemkin.com/rhye/wp/wp-content/uploads/sites/9/2020/07/member-1-1.jpg',
		'https://artemsemkin.com/rhye/wp/wp-content/uploads/sites/9/2020/07/member-2-1.jpg',
		'https://artemsemkin.com/rhye/wp/wp-content/uploads/sites/9/2020/07/member-3-1.jpg',
		'https://artemsemkin.com/rhye/wp/wp-content/uploads/sites/9/2020/07/member-4-1.jpg',
		'https://artemsemkin.com/rhye/wp/wp-content/uploads/sites/9/2020/07/member-5-1.jpg',
		'https://artemsemkin.com/rhye/wp/wp-content/uploads/sites/9/2020/07/member-6-1.jpg',
	);
}

/**
 * @return int
 */
function swiftfix_bootstrap_http_timeout_60() {
	return 60;
}

/**
 * media_handle_sideload() needs a user with upload_files; front-end init often has no user.
 *
 * @return void
 */
function swiftfix_bootstrap_elevate_user_for_media_upload() {
	if ( current_user_can( 'upload_files' ) ) {
		return;
	}
	static $done = false;
	if ( $done ) {
		return;
	}
	$done = true;
	$user_ids = get_users(
		array(
			'role'    => 'administrator',
			'number'  => 1,
			'fields'  => 'ID',
			'orderby' => 'ID',
			'order'   => 'ASC',
		)
	);
	if ( ! empty( $user_ids ) ) {
		wp_set_current_user( (int) $user_ids[0] );
	}
}

/**
 * Theme-shipped demo images (no outbound HTTP needed on Render).
 *
 * @return string Absolute filesystem path to bundled folder (no trailing slash).
 */
function swiftfix_bootstrap_theme_bundled_dir() {
	return trailingslashit( get_stylesheet_directory() ) . 'assets/bundled';
}

/**
 * @param string $basename File name in assets/bundled/.
 * @return bool
 */
function swiftfix_bootstrap_theme_bundled_file_exists( $basename ) {
	if ( ! is_string( $basename ) || $basename === '' || false !== strpos( $basename, '..' ) ) {
		return false;
	}
	$path = trailingslashit( swiftfix_bootstrap_theme_bundled_dir() ) . $basename;

	return is_readable( $path );
}

/**
 * @param string $url URL or path fragment.
 * @return bool
 */
function swiftfix_bootstrap_is_demo_cdn_asset_url( $url ) {
	return is_string( $url ) && false !== strpos( $url, 'artemsemkin.com/rhye/wp/wp-content/uploads/sites/9/' );
}

/**
 * Point Elementor image URLs at child theme files; drop stale attachment ids from the export.
 *
 * @param array<string,mixed> $arr Decoded Elementor JSON fragment.
 * @return void
 */
function swiftfix_bootstrap_rewrite_demo_urls_to_theme_assets_walk( &$arr ) {
	if ( ! is_array( $arr ) ) {
		return;
	}
	foreach ( $arr as $k => &$v ) {
		if ( is_array( $v ) ) {
			swiftfix_bootstrap_rewrite_demo_urls_to_theme_assets_walk( $v );
		}
	}
	unset( $v );

	if ( isset( $arr['url'] ) && is_string( $arr['url'] ) && swiftfix_bootstrap_is_demo_cdn_asset_url( $arr['url'] ) ) {
		$path = wp_parse_url( $arr['url'], PHP_URL_PATH );
		$base = is_string( $path ) ? basename( $path ) : '';
		if ( $base !== '' && swiftfix_bootstrap_theme_bundled_file_exists( $base ) ) {
			$arr['url'] = trailingslashit( get_stylesheet_directory_uri() ) . 'assets/bundled/' . $base;
			unset( $arr['id'] );
		}
	}
}

/**
 * @param array<string,mixed> $data Decoded Elementor template.
 * @return void
 */
function swiftfix_bootstrap_rewrite_demo_urls_to_theme_assets_in_data( array &$data ) {
	if ( ! empty( $data['content'] ) && is_array( $data['content'] ) ) {
		swiftfix_bootstrap_rewrite_demo_urls_to_theme_assets_walk( $data['content'] );
	}
	$page_settings = isset( $data['page_settings'] ) ? $data['page_settings'] : null;
	if ( is_string( $page_settings ) && $page_settings !== '' ) {
		$page_settings = json_decode( $page_settings, true );
	}
	if ( is_array( $page_settings ) ) {
		swiftfix_bootstrap_rewrite_demo_urls_to_theme_assets_walk( $page_settings );
		$data['page_settings'] = $page_settings;
	}
}

/**
 * @param string $abs Absolute path inside uploads.
 * @return int Attachment ID or 0.
 */
function swiftfix_bootstrap_find_attachment_by_abspath( $abs ) {
	$upload  = wp_upload_dir();
	$basedir = wp_normalize_path( trailingslashit( $upload['basedir'] ) );
	$absnorm = wp_normalize_path( $abs );
	if ( strpos( $absnorm, $basedir ) !== 0 ) {
		return 0;
	}
	$relative = ltrim( substr( $absnorm, strlen( $basedir ) ), '/' );
	global $wpdb;
	$id = $wpdb->get_var(
		$wpdb->prepare(
			"SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_wp_attached_file' AND meta_value = %s LIMIT 1",
			$relative
		)
	);

	return $id ? (int) $id : 0;
}

/**
 * Copy a file from the child theme bundle into uploads and create an attachment (for featured images).
 *
 * @param string $basename File in assets/bundled/.
 * @return int Attachment ID or 0.
 */
function swiftfix_bootstrap_attachment_from_theme_bundled( $basename ) {
	if ( ! is_string( $basename ) || $basename === '' || false !== strpos( $basename, '..' ) ) {
		return 0;
	}
	static $cache = array();
	if ( isset( $cache[ $basename ] ) ) {
		return $cache[ $basename ];
	}
	$src = trailingslashit( swiftfix_bootstrap_theme_bundled_dir() ) . $basename;
	if ( ! is_readable( $src ) ) {
		$cache[ $basename ] = 0;

		return 0;
	}

	swiftfix_bootstrap_elevate_user_for_media_upload();

	require_once ABSPATH . 'wp-admin/includes/file.php';
	require_once ABSPATH . 'wp-admin/includes/image.php';

	$upload = wp_upload_dir();
	if ( ! empty( $upload['error'] ) ) {
		$cache[ $basename ] = 0;

		return 0;
	}

	$dest = trailingslashit( $upload['path'] ) . sanitize_file_name( $basename );
	if ( is_file( $dest ) ) {
		$existing = swiftfix_bootstrap_find_attachment_by_abspath( $dest );
		if ( $existing > 0 ) {
			$cache[ $basename ] = $existing;

			return $existing;
		}
	}

	if ( ! is_file( $dest ) && ! copy( $src, $dest ) ) {
		$cache[ $basename ] = 0;

		return 0;
	}

	$ftype = wp_check_filetype( $dest, null );
	$mime  = is_array( $ftype ) && ! empty( $ftype['type'] ) ? $ftype['type'] : 'image/jpeg';

	$attachment = array(
		'post_mime_type' => $mime,
		'post_title'     => sanitize_file_name( pathinfo( $basename, PATHINFO_FILENAME ) ),
		'post_status'    => 'inherit',
	);

	$attach_id = wp_insert_attachment( $attachment, $dest );
	if ( is_wp_error( $attach_id ) || ! $attach_id ) {
		$cache[ $basename ] = 0;

		return 0;
	}

	$meta = wp_generate_attachment_metadata( $attach_id, $dest );
	wp_update_attachment_metadata( $attach_id, $meta );

	$cache[ $basename ] = (int) $attach_id;

	return (int) $attach_id;
}

/**
 * Download a remote image into the Media Library (cached per request by URL).
 *
 * @param string $url Remote image URL.
 * @return int Attachment ID or 0 on failure.
 */
function swiftfix_bootstrap_ensure_local_attachment( $url ) {
	if ( ! is_string( $url ) || ! preg_match( '#^https?://#i', $url ) ) {
		return 0;
	}
	static $cache = array();
	if ( isset( $cache[ $url ] ) ) {
		return $cache[ $url ];
	}

	swiftfix_bootstrap_elevate_user_for_media_upload();

	require_once ABSPATH . 'wp-admin/includes/file.php';
	require_once ABSPATH . 'wp-admin/includes/media.php';
	require_once ABSPATH . 'wp-admin/includes/image.php';

	add_filter( 'http_request_timeout', 'swiftfix_bootstrap_http_timeout_60' );
	$tmp = download_url( $url );
	remove_filter( 'http_request_timeout', 'swiftfix_bootstrap_http_timeout_60' );

	if ( is_wp_error( $tmp ) ) {
		$cache[ $url ] = 0;
		error_log( 'CAE Fix sideload: ' . $url . ' — ' . $tmp->get_error_message() ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log

		return 0;
	}

	$path = wp_parse_url( $url, PHP_URL_PATH );
	$file = is_string( $path ) && $path !== '' ? basename( $path ) : 'image.jpg';
	if ( '' === $file || false === strpos( $file, '.' ) ) {
		$file = 'image.jpg';
	}

	$file_array = array(
		'name'     => sanitize_file_name( $file ),
		'tmp_name' => $tmp,
	);

	$attachment_id = media_handle_sideload( $file_array, 0 );
	if ( is_wp_error( $attachment_id ) ) {
		error_log( 'CAE Fix sideload media_handle_sideload: ' . $attachment_id->get_error_message() ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
		if ( is_string( $tmp ) && is_file( $tmp ) ) {
			unlink( $tmp );
		}
		$cache[ $url ] = 0;

		return 0;
	}

	$cache[ $url ] = (int) $attachment_id;

	return (int) $attachment_id;
}

/**
 * Replace remote Elementor image controls with local attachments (mutates by reference).
 *
 * @param array<string,mixed> $arr Elementor node (recursive).
 * @return void
 */
function swiftfix_bootstrap_localize_remote_images_in_tree( &$arr ) {
	if ( ! is_array( $arr ) ) {
		return;
	}

	if ( isset( $arr['url'] ) && is_string( $arr['url'] ) && preg_match( '#^https?://#i', $arr['url'] ) ) {
		// Use ~ delimiter so '#' in URL fragments does not end the pattern early.
		$looks_like_image = (bool) preg_match( '~\.(jpe?g|png|gif|webp|svg)(\?|#|$)~i', $arr['url'] );
		if ( $looks_like_image ) {
			if ( ! empty( $arr['id'] ) && wp_attachment_is_image( (int) $arr['id'] ) ) {
				$u = wp_get_attachment_url( (int) $arr['id'] );
				if ( is_string( $u ) && $u !== '' ) {
					$arr['url'] = $u;
				}

				return;
			}
			$site_host = wp_parse_url( home_url(), PHP_URL_HOST );
			$img_host  = wp_parse_url( $arr['url'], PHP_URL_HOST );
			if ( $site_host && $img_host && strcasecmp( (string) $site_host, (string) $img_host ) === 0 ) {
				return;
			}
			$id = swiftfix_bootstrap_ensure_local_attachment( $arr['url'] );
			if ( $id > 0 ) {
				$arr['id'] = $id;
				$local_u   = wp_get_attachment_url( $id );
				if ( is_string( $local_u ) && $local_u !== '' ) {
					$arr['url'] = $local_u;
				}
			}

			return;
		}
	}

	foreach ( $arr as $k => &$v ) {
		if ( is_array( $v ) ) {
			swiftfix_bootstrap_localize_remote_images_in_tree( $v );
		}
	}
	unset( $v );
}

/**
 * @param array<string,mixed> $data Decoded Elementor template (content + optional page_settings).
 * @return void
 */
function swiftfix_bootstrap_localize_remote_images_in_data( array &$data ) {
	swiftfix_bootstrap_rewrite_demo_urls_to_theme_assets_in_data( $data );
	if ( ! empty( $data['content'] ) && is_array( $data['content'] ) ) {
		swiftfix_bootstrap_localize_remote_images_in_tree( $data['content'] );
	}
	$page_settings = isset( $data['page_settings'] ) ? $data['page_settings'] : null;
	if ( is_string( $page_settings ) && $page_settings !== '' ) {
		$page_settings = json_decode( $page_settings, true );
	}
	if ( is_array( $page_settings ) ) {
		swiftfix_bootstrap_localize_remote_images_in_tree( $page_settings );
		$data['page_settings'] = $page_settings;
	}
}

/**
 * @param int $post_id Page ID.
 * @return void
 */
function swiftfix_bootstrap_localize_post_elementor_images( $post_id ) {
	$raw = get_post_meta( $post_id, '_elementor_data', true );
	if ( ! is_string( $raw ) || $raw === '' ) {
		return;
	}
	$decoded = json_decode( wp_unslash( $raw ), true );
	if ( ! is_array( $decoded ) ) {
		return;
	}
	swiftfix_bootstrap_rewrite_demo_urls_to_theme_assets_walk( $decoded );
	swiftfix_bootstrap_localize_remote_images_in_tree( $decoded );
	$encoded = wp_json_encode( $decoded, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
	update_post_meta( $post_id, '_elementor_data', wp_slash( $encoded ) );
}

/**
 * Assign featured images to portfolio posts that do not have one (slider content).
 *
 * @return void
 */
function swiftfix_bootstrap_attach_portfolio_featured_images() {
	if ( ! post_type_exists( 'arts_portfolio_item' ) ) {
		return;
	}
	$posts = get_posts(
		array(
			'post_type'              => 'arts_portfolio_item',
			'post_status'            => 'publish',
			'posts_per_page'         => 50,
			'orderby'                => array( 'menu_order' => 'ASC', 'ID' => 'ASC' ),
			'no_found_rows'          => true,
			'update_post_meta_cache' => false,
			'update_post_term_cache' => false,
		)
	);
	if ( empty( $posts ) ) {
		return;
	}
	$urls = swiftfix_bootstrap_demo_team_image_urls();
	$n    = count( $urls );
	if ( $n < 1 ) {
		return;
	}
	$i = 0;
	foreach ( $posts as $p ) {
		if ( has_post_thumbnail( $p->ID ) ) {
			continue;
		}
		$url  = $urls[ $i % $n ];
		$base = basename( wp_parse_url( $url, PHP_URL_PATH ) );
		$aid  = $base ? swiftfix_bootstrap_attachment_from_theme_bundled( $base ) : 0;
		if ( ! $aid ) {
			$aid = swiftfix_bootstrap_ensure_local_attachment( $url );
		}
		if ( $aid > 0 ) {
			set_post_thumbnail( (int) $p->ID, $aid );
		}
		++$i;
	}
}

/**
 * @return void
 */
function swiftfix_bootstrap_migrate_remote_images() {
	// v3: rewrite demo CDN URLs to child theme assets/bundled (works on Render without outbound image HTTP).
	if ( get_option( 'swiftfix_remote_images_localized_v3' ) ) {
		return;
	}
	if ( ! get_option( 'swiftfix_full_bootstrap_done' ) ) {
		return;
	}
	if ( ! function_exists( 'is_blog_installed' ) || ! is_blog_installed() ) {
		return;
	}
	if ( defined( 'WP_INSTALLING' ) && WP_INSTALLING ) {
		return;
	}
	if ( wp_doing_ajax() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
		return;
	}
	if ( defined( 'WP_CLI' ) && WP_CLI ) {
		return;
	}
	if ( ! defined( 'ELEMENTOR_VERSION' ) ) {
		return;
	}

	delete_option( 'swiftfix_bootstrap_last_error' );

	try {
		$page_ids = get_posts(
			array(
				'post_type'              => 'page',
				'post_status'            => 'any',
				'posts_per_page'         => -1,
				'fields'                 => 'ids',
				'meta_query'             => array(
					array(
						'key'   => '_elementor_edit_mode',
						'value' => 'builder',
					),
				),
				'no_found_rows'          => true,
				'update_post_meta_cache' => false,
				'update_post_term_cache' => false,
			)
		);
		foreach ( $page_ids as $pid ) {
			swiftfix_bootstrap_localize_post_elementor_images( (int) $pid );
		}
		swiftfix_bootstrap_attach_portfolio_featured_images();
		if ( class_exists( '\Elementor\Plugin' ) ) {
			\Elementor\Plugin::$instance->files_manager->clear_cache();
		}
		update_option( 'swiftfix_remote_images_localized_v3', true );
		delete_option( 'swiftfix_remote_images_localized_v2' );
		delete_option( 'swiftfix_remote_images_localized_v1' );
	} catch ( Throwable $e ) {
		update_option( 'swiftfix_bootstrap_last_error', $e->getMessage() );
		error_log( 'CAE Fix migrate images: ' . $e->getMessage() ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
	}
}

/**
 * @param string $slug Page slug.
 * @return int
 */
function swiftfix_bootstrap_get_page_id_by_slug( $slug ) {
	$posts = get_posts(
		array(
			'name'           => $slug,
			'post_type'      => 'page',
			'post_status'    => 'any',
			'posts_per_page' => 1,
			'fields'         => 'ids',
		)
	);

	return ! empty( $posts ) ? (int) $posts[0] : 0;
}

/**
 * Prefer demo contact page, then generic slugs.
 *
 * @return int Page ID or 0.
 */
function swiftfix_bootstrap_contact_page_id() {
	foreach ( array( 'contact', 'contact-us', 'contacts-02' ) as $slug ) {
		$id = swiftfix_bootstrap_get_page_id_by_slug( $slug );
		if ( $id ) {
			return $id;
		}
	}

	return 0;
}

/**
 * Create or publish a page and add starter content if the body is empty.
 *
 * @param string $slug    Post name.
 * @param string $title   Title.
 * @param string $content HTML content.
 * @return int Post ID or 0 on failure.
 */
function swiftfix_bootstrap_ensure_page_with_content( $slug, $title, $content ) {
	$existing = get_posts(
		array(
			'post_type'      => 'page',
			'post_status'    => 'any',
			'name'           => $slug,
			'posts_per_page' => 1,
		)
	);
	if ( ! empty( $existing ) ) {
		$post   = $existing[0];
		$id     = (int) $post->ID;
		$update = array(
			'ID'          => $id,
			'post_status' => 'publish',
		);
		$text = trim( wp_strip_all_tags( (string) $post->post_content ) );
		if ( $text === '' && $content !== '' ) {
			$update['post_content'] = $content;
		}
		wp_update_post( $update );

		return $id;
	}

	$new = wp_insert_post(
		array(
			'post_title'   => $title,
			'post_name'    => $slug,
			'post_status'  => 'publish',
			'post_type'    => 'page',
			'post_content' => $content,
		),
		true
	);

	if ( is_wp_error( $new ) || ! $new ) {
		return 0;
	}

	return (int) $new;
}

/**
 * @return string
 */
function swiftfix_bootstrap_placeholder_privacy_html() {
	$site = esc_html( get_option( 'blogname', 'CAE Fix' ) );

	return '<h2>Who we are</h2><p>Our website address is <a href="' . esc_url( home_url( '/' ) ) . '">' . esc_html( home_url( '/' ) ) . '</a>. This policy explains how <strong>' . $site . '</strong> (referred to as "we" or "us") collects, uses, and protects personal information when you browse this site, call or email us, or book trade services.</p>'
		. '<h2>What we collect</h2><p>Depending on how you contact us, we may process your name, phone number, email address, property address, payment-related details (handled by our bank or card provider where applicable), and information about the job you describe (including photos you choose to send). Calls may be logged for training and quality only if we tell you at the time.</p>'
		. '<h2>Why we use it</h2><p>We use this information to provide quotes, schedule visits, deliver and invoice work, meet legal and regulatory duties (including health, safety, and tax records), and improve how we respond to customers. We do not sell your personal data. Marketing emails, if any, are sent only with clear consent and you can opt out at any time.</p>'
		. '<h2>Legal basis</h2><p>Where UK GDPR applies, we rely on performance of a contract (providing the services you ask for), legitimate interests (running a safe, efficient business), or legal obligation, as appropriate to each activity.</p>'
		. '<h2>Cookies</h2><p>We use essential cookies and similar technologies needed for security and basic site operation. If we add analytics or non-essential cookies, we will update this policy and obtain consent where the law requires.</p>'
		. '<h2>Retention</h2><p>We keep information only as long as necessary for the purposes above and to meet statutory retention periods (for example accounting, warranties, or dispute resolution).</p>'
		. '<h2>Sharing</h2><p>We may share data with insurers, accountants, or IT providers who process it on our instructions, or with authorities when the law requires. We use reasonable technical and organisational measures to protect personal data.</p>'
		. '<h2>Your rights</h2><p>You may have the right to access, correct, erase, restrict, or object to certain processing, and to complain to the ICO (UK). To exercise your rights, contact us using the details on this website.</p>'
		. '<h2>Updates</h2><p>We may revise this policy; the date below will reflect the latest version.</p>'
		. '<p><em>Last updated: starter template — replace with your own wording or advice from a qualified professional.</em></p>';
}

/**
 * @return string
 */
function swiftfix_bootstrap_placeholder_terms_html() {
	$site = esc_html( get_option( 'blogname', 'CAE Fix' ) );

	return '<h2>Agreement</h2><p>By using this website or instructing <strong>' . $site . '</strong> to carry out work, you agree to these terms. If you disagree, please do not use the site or book our services.</p>'
		. '<h2>Our services</h2><p>We provide domestic and light commercial trade services as described on our pages. Written or email quotes are valid for the period stated. A quote is not a contract until you accept it and we confirm the booking in writing (including email).</p>'
		. '<h2>Access & your property</h2><p>You agree to provide safe access, working utilities, and any permissions (for example landlord consent or parking). Concealed issues (e.g. hidden pipework or asbestos) may require extra work and cost; we will explain options before proceeding where reasonably possible.</p>'
		. '<h2>Payments</h2><p>Payment terms (deposit, interim, or payment on completion) are confirmed in your quote or invoice. Late payment may attract statutory interest and reasonable recovery costs where the law allows.</p>'
		. '<h2>Cancellations & delays</h2><p>Please give as much notice as you can if you need to cancel or move an appointment. Repeated short-notice cancellations or lock-outs may incur a reasonable fee to cover lost time, as set out in your booking communication.</p>'
		. '<h2>Warranties & liability</h2><p>We stand behind our workmanship for the period stated in your paperwork. We maintain appropriate insurance. We are not liable for indirect or consequential loss. Our total liability for any claim relating to a specific job is limited to the amount you paid us for that job except where liability cannot be limited by law.</p>'
		. '<h2>Website</h2><p>Site content is for general information. We are not responsible for temporary unavailability or for third-party websites we may link to.</p>'
		. '<h2>Law</h2><p>These terms are governed by the laws of England and Wales. The courts of England and Wales have exclusive jurisdiction.</p>'
		. '<p><em>Last updated: starter template — have a solicitor review before relying on these terms in disputes.</em></p>';
}

/**
 * Create/publish Privacy, Terms, and a fallback Contact page when none exists.
 * Runs during full bootstrap before the nav menu is built, and from the one-time init hook.
 *
 * @return void
 */
function swiftfix_bootstrap_ensure_legal_pages_once() {
	$privacy_id = swiftfix_bootstrap_ensure_page_with_content(
		'privacy-policy',
		'Privacy Policy',
		swiftfix_bootstrap_placeholder_privacy_html()
	);
	swiftfix_bootstrap_ensure_page_with_content(
		'terms',
		'Terms of Use',
		swiftfix_bootstrap_placeholder_terms_html()
	);

	if ( $privacy_id > 0 && (int) get_option( 'wp_page_for_privacy_policy' ) < 1 ) {
		update_option( 'wp_page_for_privacy_policy', $privacy_id );
	}

	if ( ! swiftfix_bootstrap_contact_page_id() ) {
		swiftfix_bootstrap_ensure_page_with_content(
			'contact',
			'Contact',
			'<p>We are happy to help with quotes, scheduling, and emergencies. Use the phone number or email in the site header for the fastest reply, or describe your job below.</p><p>If you use Contact Form 7 or WPForms, you can paste your form shortcode here in the editor.</p>'
		);
	}
}

/**
 * One-time: ensure Privacy Policy, Terms, and a simple Contact page exist.
 *
 * @return void
 */
function swiftfix_bootstrap_seed_legal_pages() {
	if ( get_option( 'swiftfix_legal_pages_seeded_v1' ) ) {
		return;
	}
	if ( ! function_exists( 'is_blog_installed' ) || ! is_blog_installed() ) {
		return;
	}
	if ( defined( 'WP_INSTALLING' ) && WP_INSTALLING ) {
		return;
	}
	if ( wp_doing_ajax() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
		return;
	}
	if ( defined( 'WP_CLI' ) && WP_CLI ) {
		return;
	}

	delete_option( 'swiftfix_bootstrap_last_error' );

	try {
		swiftfix_bootstrap_ensure_legal_pages_once();
		flush_rewrite_rules( false );
		update_option( 'swiftfix_legal_pages_seeded_v1', true );
	} catch ( Throwable $e ) {
		update_option( 'swiftfix_bootstrap_last_error', $e->getMessage() );
		error_log( 'CAE Fix legal pages: ' . $e->getMessage() ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
	}
}

/**
 * Assign CAE Fix inner template when the page is not built with Elementor.
 *
 * @param int $page_id Post ID.
 * @return void
 */
function swiftfix_bootstrap_safe_assign_inner_template( $page_id ) {
	$page_id = (int) $page_id;
	if ( $page_id < 1 ) {
		return;
	}
	if ( get_post_meta( $page_id, '_elementor_edit_mode', true ) === 'builder' ) {
		return;
	}
	update_post_meta( $page_id, '_wp_page_template', 'page-swiftfix-inner.php' );
}

/**
 * Create four service detail pages and assign the branded inner template.
 *
 * @return void
 */
function swiftfix_bootstrap_ensure_service_pages_once() {
	$list = array(
		array( 'swiftfix-electrical', 'Electrical services' ),
		array( 'swiftfix-plumbing', 'Plumbing services' ),
		array( 'swiftfix-heating-gas', 'Heating & gas services' ),
		array( 'swiftfix-building-renovation', 'Building & renovation' ),
	);
	foreach ( $list as $row ) {
		list( $slug, $title ) = $row;
		$id = swiftfix_bootstrap_get_page_id_by_slug( $slug );
		if ( ! $id ) {
			$new = wp_insert_post(
				array(
					'post_title'   => $title,
					'post_name'    => $slug,
					'post_status'  => 'publish',
					'post_type'    => 'page',
					'post_content' => '',
				),
				true
			);
			if ( is_wp_error( $new ) || ! $new ) {
				continue;
			}
			$id = (int) $new;
		}
		swiftfix_bootstrap_safe_assign_inner_template( $id );
	}
}

/**
 * Apply CAE Fix inner template to Privacy, Terms, and simple Contact (not Elementor demo pages).
 *
 * @return void
 */
function swiftfix_bootstrap_assign_inner_template_legal_contact() {
	foreach ( array( 'privacy-policy', 'terms', 'contact' ) as $slug ) {
		$id = swiftfix_bootstrap_get_page_id_by_slug( $slug );
		if ( $id ) {
			swiftfix_bootstrap_safe_assign_inner_template( $id );
		}
	}
}

/**
 * Service pages + legal/contact inner templates.
 *
 * @return void
 */
function swiftfix_bootstrap_ensure_branded_inner_pages_once() {
	swiftfix_bootstrap_ensure_service_pages_once();
	swiftfix_bootstrap_assign_inner_template_legal_contact();
}

/**
 * One-time for existing installs.
 *
 * @return void
 */
function swiftfix_bootstrap_seed_branded_inner_pages() {
	if ( get_option( 'swiftfix_branded_inner_pages_v1' ) ) {
		return;
	}
	if ( ! function_exists( 'is_blog_installed' ) || ! is_blog_installed() ) {
		return;
	}
	if ( defined( 'WP_INSTALLING' ) && WP_INSTALLING ) {
		return;
	}
	if ( wp_doing_ajax() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
		return;
	}
	if ( defined( 'WP_CLI' ) && WP_CLI ) {
		return;
	}

	delete_option( 'swiftfix_bootstrap_last_error' );

	try {
		swiftfix_bootstrap_ensure_branded_inner_pages_once();
		flush_rewrite_rules( false );
		update_option( 'swiftfix_branded_inner_pages_v1', true );
	} catch ( Throwable $e ) {
		update_option( 'swiftfix_bootstrap_last_error', $e->getMessage() );
		error_log( 'CAE Fix branded inner pages: ' . $e->getMessage() ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
	}
}

/**
 * Use full CAE Fix PHP landing for front page (nav, hero, services, reviews). Elementor: set SWIFTFIX_HOME_TEMPLATE=elementor.
 *
 * @param int $home_id Front page ID.
 * @return void
 */
function swiftfix_bootstrap_assign_home_template( $home_id ) {
	$mode = getenv( 'SWIFTFIX_HOME_TEMPLATE' );
	$s    = is_string( $mode ) ? strtolower( trim( $mode ) ) : '';
	if ( 'elementor' === $s || 'builder' === $s ) {
		update_post_meta( (int) $home_id, '_wp_page_template', 'default' );

		return;
	}
	update_post_meta( (int) $home_id, '_wp_page_template', 'page-services-landing.php' );
}

/**
 * @return void
 */
function swiftfix_bootstrap_one_time_php_home() {
	if ( get_option( 'swiftfix_php_home_assigned_v1' ) ) {
		return;
	}
	if ( ! get_option( 'swiftfix_full_bootstrap_done' ) ) {
		return;
	}
	if ( ! function_exists( 'is_blog_installed' ) || ! is_blog_installed() ) {
		return;
	}
	if ( defined( 'WP_INSTALLING' ) && WP_INSTALLING ) {
		return;
	}
	if ( wp_doing_ajax() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
		return;
	}
	if ( defined( 'WP_CLI' ) && WP_CLI ) {
		return;
	}
	$home_id = (int) get_option( 'page_on_front' );
	if ( ! $home_id ) {
		return;
	}
	swiftfix_bootstrap_assign_home_template( $home_id );
	update_option( 'swiftfix_php_home_assigned_v1', true );
}

/**
 * @param int $menu_id Nav menu term ID.
 * @return void
 */
function swiftfix_bootstrap_set_swiftfix_menu_location( $menu_id ) {
	$locations = get_theme_mod( 'nav_menu_locations', array() );
	if ( ! is_array( $locations ) ) {
		$locations = array();
	}
	$locations['swiftfix_primary'] = (int) $menu_id;
	set_theme_mod( 'nav_menu_locations', $locations );
}

/**
 * @return void
 */
function swiftfix_bootstrap_create_primary_menu() {
	if ( get_option( 'swiftfix_nav_menu_created' ) ) {
		return;
	}
	swiftfix_bootstrap_elevate_user_for_media_upload();
	if ( ! function_exists( 'wp_create_nav_menu' ) ) {
		require_once ABSPATH . 'wp-admin/includes/nav-menu.php';
	}
	$menu_name = 'CAE Fix Primary';
	$menu_obj  = wp_get_nav_menu_object( $menu_name );
	if ( $menu_obj ) {
		$menu_id = (int) $menu_obj->term_id;
	} else {
		$created = wp_create_nav_menu( $menu_name );
		if ( is_wp_error( $created ) ) {
			return;
		}
		$menu_id = (int) $created;
	}
	$existing_items = wp_get_nav_menu_items( $menu_id );
	if ( ! empty( $existing_items ) ) {
		swiftfix_bootstrap_set_swiftfix_menu_location( $menu_id );
		update_option( 'swiftfix_nav_menu_created', true );

		return;
	}

	wp_update_nav_menu_item(
		$menu_id,
		0,
		array(
			'menu-item-title'  => 'Home',
			'menu-item-url'    => home_url( '/' ),
			'menu-item-status' => 'publish',
			'menu-item-type'   => 'custom',
		)
	);
	$blog = (int) get_option( 'page_for_posts' );
	if ( $blog ) {
		wp_update_nav_menu_item(
			$menu_id,
			0,
			array(
				'menu-item-title'     => 'Blog',
				'menu-item-object-id' => $blog,
				'menu-item-object'    => 'page',
				'menu-item-type'      => 'post_type',
				'menu-item-status'    => 'publish',
			)
		);
	}
	$services_id = swiftfix_bootstrap_get_page_id_by_slug( 'services-02' );
	if ( $services_id ) {
		wp_update_nav_menu_item(
			$menu_id,
			0,
			array(
				'menu-item-title'     => 'Services',
				'menu-item-object-id' => $services_id,
				'menu-item-object'    => 'page',
				'menu-item-type'      => 'post_type',
				'menu-item-status'    => 'publish',
			)
		);
	}
	$contact_id = swiftfix_bootstrap_contact_page_id();
	if ( $contact_id ) {
		wp_update_nav_menu_item(
			$menu_id,
			0,
			array(
				'menu-item-title'     => 'Contact',
				'menu-item-object-id' => $contact_id,
				'menu-item-object'    => 'page',
				'menu-item-type'      => 'post_type',
				'menu-item-status'    => 'publish',
			)
		);
	}

	swiftfix_bootstrap_set_swiftfix_menu_location( $menu_id );
	update_option( 'swiftfix_nav_menu_created', true );
}

/**
 * @param string $slug  Post slug.
 * @param string $title Post title.
 * @return int|\WP_Error
 */
function swiftfix_bootstrap_get_or_create_page( $slug, $title ) {
	$existing = get_posts(
		array(
			'post_type'      => 'page',
			'post_status'    => 'any',
			'name'           => $slug,
			'posts_per_page' => 1,
			'fields'         => 'ids',
		)
	);
	if ( ! empty( $existing ) ) {
		return (int) $existing[0];
	}
	return wp_insert_post(
		array(
			'post_title'  => $title,
			'post_name'   => $slug,
			'post_status' => 'publish',
			'post_type'   => 'page',
		),
		true
	);
}

/**
 * @param int   $post_id Post ID.
 * @param array $data    Decoded electrician-template.json.
 * @return void
 */
function swiftfix_bootstrap_apply_elementor_to_page( $post_id, array $data ) {
	$encoded = wp_json_encode( $data['content'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );

	update_post_meta( $post_id, '_elementor_edit_mode', 'builder' );
	update_post_meta( $post_id, '_elementor_template_type', 'wp-page' );
	update_post_meta( $post_id, '_elementor_version', ELEMENTOR_VERSION );
	update_post_meta( $post_id, '_elementor_data', wp_slash( $encoded ) );

	// Must be a PHP array — WordPress serializes it. A JSON string breaks Elementor 3.x
	// (sanitize_settings() expects array, not string).
	$page_settings = isset( $data['page_settings'] ) ? $data['page_settings'] : null;
	if ( is_string( $page_settings ) && $page_settings !== '' ) {
		$page_settings = json_decode( $page_settings, true );
	}
	if ( ! empty( $page_settings ) && is_array( $page_settings ) ) {
		update_post_meta( $post_id, '_elementor_page_settings', $page_settings );
	}

	if ( class_exists( '\Elementor\Plugin' ) ) {
		\Elementor\Plugin::$instance->files_manager->clear_cache();
	}
}

/**
 * @return bool
 */
function swiftfix_bootstrap_install_elementor() {
	require_once ABSPATH . 'wp-admin/includes/file.php';
	require_once ABSPATH . 'wp-admin/includes/plugin-install.php';
	require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
	require_once ABSPATH . 'wp-admin/includes/class-automatic-upgrader-skin.php';

	$api = plugins_api(
		'plugin_information',
		array(
			'slug'   => 'elementor',
			'fields' => array(
				'sections' => false,
			),
		)
	);

	if ( is_wp_error( $api ) || empty( $api->download_link ) ) {
		return false;
	}

	$skin     = new Automatic_Upgrader_Skin();
	$upgrader = new Plugin_Upgrader( $skin );
	$result   = $upgrader->install( $api->download_link );

	if ( is_wp_error( $result ) ) {
		return false;
	}

	return file_exists( WP_PLUGIN_DIR . '/elementor/elementor.php' );
}

add_action(
	'admin_notices',
	static function () {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$err = get_option( 'swiftfix_bootstrap_last_error' );
		if ( ! is_string( $err ) || $err === '' ) {
			return;
		}
		echo '<div class="notice notice-error"><p><strong>CAE Fix setup</strong> — ' . esc_html( $err ) . '</p></div>';
	}
);
