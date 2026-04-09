<?php
/**
 * Theme functions and definitions.
 * This is a child theme of Rhye.
 *
 * @link https://developer.wordpress.org/themes/basics/theme-functions/
 */

add_action( 'after_setup_theme', 'swiftfix_child_setup', 15 );
function swiftfix_child_setup() {
	register_nav_menus(
		array(
			'swiftfix_primary' => __( 'SwiftFix primary (services landing)', 'rhye-child' ),
		)
	);
}

/**
 * Load parent CSS in correct order: main.css must run before child overrides.
 * Do not re-enqueue parent style.css under a duplicate handle.
 */
add_action( 'wp_enqueue_scripts', 'rhye_child_enqueue_styles', 99 );
function rhye_child_enqueue_styles() {
	$deps = array( 'rhye-main-style', 'rhye-theme-style' );
	wp_enqueue_style(
		'rhye-child-style',
		get_stylesheet_directory_uri() . '/style.css',
		$deps,
		wp_get_theme()->get( 'Version' )
	);
}

add_action( 'wp_enqueue_scripts', 'swiftfix_enqueue_elementor_compat', 100 );
function swiftfix_enqueue_elementor_compat() {
	if ( ! is_singular() ) {
		return;
	}
	$post_id = get_queried_object_id();
	if ( ! $post_id || get_post_meta( $post_id, '_elementor_edit_mode', true ) !== 'builder' ) {
		return;
	}
	wp_enqueue_style(
		'swiftfix-elementor-compat',
		get_stylesheet_directory_uri() . '/assets/css/elementor-compat.css',
		array( 'rhye-main-style' ),
		wp_get_theme()->get( 'Version' )
	);
}

add_action( 'wp_enqueue_scripts', 'swiftfix_enqueue_landing_assets', 101 );
function swiftfix_enqueue_landing_assets() {
	if ( ! is_page_template( 'page-services-landing.php' ) ) {
		return;
	}
	wp_enqueue_script(
		'swiftfix-landing',
		get_stylesheet_directory_uri() . '/assets/js/swiftfix-landing.js',
		array(),
		wp_get_theme()->get( 'Version' ),
		true
	);
}

/**
 * Fallback nav when no menu is assigned yet.
 *
 * @return void
 */
function swiftfix_fallback_primary_nav() {
	$items = array(
		array( 'label' => __( 'Home', 'rhye-child' ), 'href' => home_url( '/' ) ),
		array( 'label' => __( 'Services', 'rhye-child' ), 'href' => '#services' ),
		array( 'label' => __( 'How It Works', 'rhye-child' ), 'href' => '#how' ),
		array( 'label' => __( 'Reviews', 'rhye-child' ), 'href' => '#reviews' ),
		array( 'label' => __( 'Contact', 'rhye-child' ), 'href' => '#contact' ),
	);
	$services = get_posts(
		array(
			'name'           => 'services-02',
			'post_type'      => 'page',
			'post_status'    => 'publish',
			'posts_per_page' => 1,
			'fields'         => 'ids',
		)
	);
	if ( ! empty( $services ) ) {
		$items[1]['href'] = get_permalink( $services[0] );
	}
	$contact = get_posts(
		array(
			'name'           => 'contacts-02',
			'post_type'      => 'page',
			'post_status'    => 'publish',
			'posts_per_page' => 1,
			'fields'         => 'ids',
		)
	);
	if ( ! empty( $contact ) ) {
		$items[4]['href'] = get_permalink( $contact[0] );
	}
	echo '<ul id="sf-nav-links" class="sf-nav__links" role="list">';
	foreach ( $items as $item ) {
		$class = ( isset( $item['href'][0] ) && '#' === $item['href'][0] ) ? 'sf-scroll' : '';
		printf(
			'<li><a href="%1$s" class="%3$s">%2$s</a></li>',
			esc_url( $item['href'] ),
			esc_html( $item['label'] ),
			esc_attr( $class )
		);
	}
	echo '</ul>';
}

add_action( 'wp_footer', 'swiftfix_elementor_mobile_cta', 99 );
function swiftfix_elementor_mobile_cta() {
	if ( ! is_singular() ) {
		return;
	}
	if ( is_page_template( 'page-services-landing.php' ) ) {
		return;
	}
	if ( get_theme_mod( 'swiftfix_hide_mobile_cta', false ) ) {
		return;
	}
	$post_id = get_queried_object_id();
	if ( ! $post_id || get_post_meta( $post_id, '_elementor_edit_mode', true ) !== 'builder' ) {
		return;
	}
	$phone = get_theme_mod( 'sf_phone', '0800 123 4567' );
	$href  = 'tel:' . preg_replace( '/\s+/', '', $phone );
	$name  = get_theme_mod( 'sf_business_name', 'SwiftFix' );
	echo '<div class="sf-elementor-cta" role="region" aria-label="' . esc_attr__( 'Quick contact', 'rhye-child' ) . '">';
	echo '<span style="color:rgba(255,255,255,.85)">' . esc_html( $name ) . '</span>';
	echo '<a class="sf-elementor-cta__call" href="' . esc_attr( $href ) . '">' . esc_html__( 'Call now', 'rhye-child' ) . '</a>';
	echo '</div>';
}

add_filter( 'body_class', 'swiftfix_body_class_elementor_cta' );
function swiftfix_body_class_elementor_cta( $classes ) {
	if ( ! is_singular() ) {
		return $classes;
	}
	if ( is_page_template( 'page-services-landing.php' ) ) {
		return $classes;
	}
	if ( get_theme_mod( 'swiftfix_hide_mobile_cta', false ) ) {
		return $classes;
	}
	$post_id = get_queried_object_id();
	if ( $post_id && get_post_meta( $post_id, '_elementor_edit_mode', true ) === 'builder' ) {
		$classes[] = 'swiftfix-elementor-cta-on';
	}
	return $classes;
}

add_action( 'admin_notices', 'swiftfix_admin_notice_rhye_core' );
function swiftfix_admin_notice_rhye_core() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	if ( ! file_exists( WP_PLUGIN_DIR . '/rhye-core/rhye-core.php' ) ) {
		return;
	}
	require_once ABSPATH . 'wp-admin/includes/plugin.php';
	if ( is_plugin_active( 'rhye-core/rhye-core.php' ) ) {
		return;
	}
	echo '<div class="notice notice-warning is-dismissible"><p><strong>SwiftFix / Rhye:</strong> ';
	echo esc_html__( 'Activate the Rhye Core plugin so Elementor widgets (sliders, team, maps) render fully.', 'rhye-child' );
	echo '</p></div>';
}

/* =====================================================
   CUSTOMIZER: SwiftFix Settings
   ===================================================== */
add_action( 'customize_register', 'swiftfix_customize_register' );
function swiftfix_customize_register( $wp_customize ) {

	$wp_customize->add_section( 'swiftfix_settings', array(
		'title'    => __( 'SwiftFix Settings', 'rhye-child' ),
		'priority' => 30,
	) );

	$wp_customize->add_setting( 'sf_business_name', array(
		'default'           => 'SwiftFix',
		'sanitize_callback' => 'sanitize_text_field',
	) );
	$wp_customize->add_control( 'sf_business_name', array(
		'label'   => __( 'Business Name', 'rhye-child' ),
		'section' => 'swiftfix_settings',
		'type'    => 'text',
	) );

	$wp_customize->add_setting( 'sf_phone', array(
		'default'           => '0800 123 4567',
		'sanitize_callback' => 'sanitize_text_field',
	) );
	$wp_customize->add_control( 'sf_phone', array(
		'label'   => __( 'Phone Number', 'rhye-child' ),
		'section' => 'swiftfix_settings',
		'type'    => 'text',
	) );

	$wp_customize->add_setting( 'sf_email', array(
		'default'           => 'hello@swiftfix.co.uk',
		'sanitize_callback' => 'sanitize_email',
	) );
	$wp_customize->add_control( 'sf_email', array(
		'label'   => __( 'Email Address', 'rhye-child' ),
		'section' => 'swiftfix_settings',
		'type'    => 'email',
	) );

	$wp_customize->add_setting( 'sf_emergency_tagline', array(
		'default'           => 'Burst pipe? Power cut? No heating?',
		'sanitize_callback' => 'sanitize_text_field',
	) );
	$wp_customize->add_control( 'sf_emergency_tagline', array(
		'label'   => __( 'Emergency Tagline', 'rhye-child' ),
		'section' => 'swiftfix_settings',
		'type'    => 'text',
	) );

	$wp_customize->add_setting( 'sf_rating', array(
		'default'           => '4.9',
		'sanitize_callback' => 'sanitize_text_field',
	) );
	$wp_customize->add_control( 'sf_rating', array(
		'label'   => __( 'Google Review Rating', 'rhye-child' ),
		'section' => 'swiftfix_settings',
		'type'    => 'text',
	) );

	$wp_customize->add_setting( 'sf_review_count', array(
		'default'           => '620',
		'sanitize_callback' => 'sanitize_text_field',
	) );
	$wp_customize->add_control( 'sf_review_count', array(
		'label'   => __( 'Total Review Count', 'rhye-child' ),
		'section' => 'swiftfix_settings',
		'type'    => 'text',
	) );

	$wp_customize->add_setting( 'sf_reg_info', array(
		'default'           => 'Registered in England & Wales. Gas Safe Reg. No. 123456.',
		'sanitize_callback' => 'sanitize_text_field',
	) );
	$wp_customize->add_control( 'sf_reg_info', array(
		'label'   => __( 'Registration Info (footer)', 'rhye-child' ),
		'section' => 'swiftfix_settings',
		'type'    => 'text',
	) );

	$wp_customize->add_setting( 'swiftfix_hide_mobile_cta', array(
		'default'           => false,
		'sanitize_callback' => function ( $v ) {
			return (bool) $v;
		},
	) );
	$wp_customize->add_control( 'swiftfix_hide_mobile_cta', array(
		'label'   => __( 'Hide mobile “Call now” bar on Elementor pages', 'rhye-child' ),
		'section' => 'swiftfix_settings',
		'type'    => 'checkbox',
	) );
}
