<?php
/**
 * Plugin Name: SwiftFix — automated setup
 * Description: Optional one-click setup (Elementor import, pages, SwiftFix theme mods). Not required if you only install Rhye + child from zip and build pages yourself.
 *
 * Theme-only workflow: delete this file from mu-plugins, or in wp-config.php (before wp-settings.php) add:
 *   define( 'SWIFTFIX_AUTO_SETUP', false );
 * Also supported: env SWIFTFIX_AUTO_SETUP=0 (e.g. Render). Re-run automation: delete options swiftfix_full_bootstrap_done and swiftfix_bootstrap_extra_pages_seeded (and swiftfix_portfolio_seed_done if needed).
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
// One-time import for sites that finished bootstrap before bundled pages existed.
add_action( 'init', 'swiftfix_bootstrap_extra_pages_run', 40 );
// One-time: download remote Elementor images + portfolio thumbs (sites that imported before sideload existed).
add_action( 'init', 'swiftfix_bootstrap_migrate_remote_images', 43 );

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
		error_log( 'SwiftFix bootstrap: ' . $e->getMessage() ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
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
		$name = 'SwiftFix';
	}
	update_option( 'blogname', $name );

	swiftfix_bootstrap_polish_home_title( (int) $home_id );

	swiftfix_bootstrap_seed_theme_mods( $name );

	swiftfix_bootstrap_import_bundled_elementor_pages();
	swiftfix_bootstrap_seed_portfolio_if_needed();
	swiftfix_bootstrap_attach_portfolio_featured_images();

	flush_rewrite_rules( false );

	update_option( 'swiftfix_bootstrap_extra_pages_seeded', true );
	update_option( 'swiftfix_full_bootstrap_done', true );
}

/**
 * @param int $home_id Front page ID.
 * @return void
 */
function swiftfix_bootstrap_polish_home_title( $home_id ) {
	$title = getenv( 'SWIFTFIX_HOME_TITLE' );
	if ( ! is_string( $title ) || $title === '' ) {
		$bn = get_option( 'blogname' );
		$title = is_string( $bn ) && $bn !== '' ? $bn : 'SwiftFix';
	}
	wp_update_post(
		array(
			'ID'         => (int) $home_id,
			'post_title' => $title,
		)
	);
}

/**
 * Fill SwiftFix Customizer keys only when not already stored (does not overwrite user choices).
 *
 * @param string $business_name Default business name (matches blogname after bootstrap).
 * @return void
 */
function swiftfix_bootstrap_seed_theme_mods( $business_name = '' ) {
	if ( ! is_string( $business_name ) || $business_name === '' ) {
		$business_name = 'SwiftFix';
	}
	$mods = get_theme_mods();
	if ( ! is_array( $mods ) ) {
		$mods = array();
	}
	$defaults = array(
		'sf_business_name'      => $business_name,
		'sf_phone'              => '0800 123 4567',
		'sf_email'              => 'hello@swiftfix.co.uk',
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
			$aid = swiftfix_bootstrap_ensure_local_attachment( $urls[ $i ] );
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
			$name = 'SwiftFix';
		}
		swiftfix_bootstrap_seed_theme_mods( $name );
		swiftfix_bootstrap_import_bundled_elementor_pages();
		swiftfix_bootstrap_seed_portfolio_if_needed();
		swiftfix_bootstrap_attach_portfolio_featured_images();
		$home_id = (int) get_option( 'page_on_front' );
		if ( $home_id ) {
			swiftfix_bootstrap_polish_home_title( $home_id );
		}
		flush_rewrite_rules( false );
		update_option( 'swiftfix_bootstrap_extra_pages_seeded', true );
	} catch ( Throwable $e ) {
		update_option( 'swiftfix_bootstrap_last_error', $e->getMessage() );
		error_log( 'SwiftFix bootstrap (extra pages): ' . $e->getMessage() ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
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
		error_log( 'SwiftFix sideload: ' . $url . ' — ' . $tmp->get_error_message() ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log

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
		error_log( 'SwiftFix sideload media_handle_sideload: ' . $attachment_id->get_error_message() ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
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
		$url = $urls[ $i % $n ];
		$aid = swiftfix_bootstrap_ensure_local_attachment( $url );
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
	// v1 could be set after a broken regex pass (no images fixed). v2 re-runs localization once.
	if ( get_option( 'swiftfix_remote_images_localized_v2' ) ) {
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
		update_option( 'swiftfix_remote_images_localized_v2', true );
		delete_option( 'swiftfix_remote_images_localized_v1' );
	} catch ( Throwable $e ) {
		update_option( 'swiftfix_bootstrap_last_error', $e->getMessage() );
		error_log( 'SwiftFix migrate images: ' . $e->getMessage() ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
	}
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
		echo '<div class="notice notice-error"><p><strong>SwiftFix setup</strong> — ' . esc_html( $err ) . '</p></div>';
	}
);
