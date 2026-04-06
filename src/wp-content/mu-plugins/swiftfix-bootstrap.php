<?php
/**
 * Plugin Name: SwiftFix — automated setup
 * Description: Activates Rhye Child, Elementor, imports electrician landing JSON, sets static Home/Blog, SwiftFix defaults. Runs once per site.
 *
 * Disable: putenv SWIFTFIX_AUTO_SETUP=0 or define before wp-settings (not practical on Local) — use wp option delete swiftfix_full_bootstrap_done to re-run after fixing issues.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$swiftfix_auto_off = getenv( 'SWIFTFIX_AUTO_SETUP' );
if ( false !== $swiftfix_auto_off && in_array( strtolower( (string) $swiftfix_auto_off ), array( '0', 'false', 'no', 'off' ), true ) ) {
	return;
}

add_action( 'init', 'swiftfix_bootstrap_run', 2 );

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
	set_theme_mod( 'sf_business_name', $name );
	set_theme_mod( 'sf_phone', '0800 123 4567' );
	set_theme_mod( 'sf_email', 'hello@swiftfix.co.uk' );

	flush_rewrite_rules( false );

	update_option( 'swiftfix_full_bootstrap_done', true );
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

	if ( ! empty( $data['page_settings'] ) && is_array( $data['page_settings'] ) ) {
		update_post_meta(
			$post_id,
			'_elementor_page_settings',
			wp_slash( wp_json_encode( $data['page_settings'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) )
		);
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
