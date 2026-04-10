<?php
/**
 * Theme functions and definitions.
 * This is a child theme of Rhye.
 *
 * @link https://developer.wordpress.org/themes/basics/theme-functions/
 */

require_once get_stylesheet_directory() . '/inc/swiftfix-inner-data.php';
require_once get_stylesheet_directory() . '/inc/swiftfix-contact-cf7.php';

/*
 * If your child theme has more than one .css file (eg. ie.css, style.css, main.css) then
 * you will have to make sure to maintain all of the parent theme dependencies.
 *
 * Make sure you're using the correct handle for loading the parent theme's styles.
 * Failure to use the proper tag will result in a CSS file needlessly being loaded twice.
 * This will usually not affect the site appearance, but it's inefficient and extends your page's loading time.
 *
 * @link https://codex.wordpress.org/Child_Themes
 */
add_action( 'wp_enqueue_scripts', 'rhye_child_enqueue_styles', 99 );
function rhye_child_enqueue_styles() {
	wp_enqueue_style( 'rhye-style', get_template_directory_uri() . '/style.css' );
	wp_enqueue_style(
		'rhye-child-style',
		get_stylesheet_directory_uri() . '/style.css',
		array( 'rhye-style' ),
		wp_get_theme()->get( 'Version' )
	);
}

/**
 * Hero image for the Tradesman Services Landing template.
 *
 * Order: Customizer media → theme bundle → Unsplash fallback.
 * We do not auto-load `images/hero-tradesman.jpg`; that path often picked up the wrong file.
 *
 * @return string Image URL.
 */
function swiftfix_get_services_landing_hero_url() {
	$attachment_id = absint( get_theme_mod( 'sf_hero_image', 0 ) );
	if ( $attachment_id ) {
		$url = wp_get_attachment_image_url( $attachment_id, 'full' );
		if ( $url ) {
			return $url;
		}
	}

	$bundled = get_stylesheet_directory() . '/assets/images/swiftfix-hero-default.jpg';
	if ( file_exists( $bundled ) ) {
		return get_stylesheet_directory_uri() . '/assets/images/swiftfix-hero-default.jpg';
	}

	return 'https://images.unsplash.com/photo-1621905251189-08b45d6a269e?w=840&h=880&fit=crop&crop=center&auto=format&q=80';
}

/**
 * First published page matching any slug; otherwise fallback URL.
 *
 * @param string[] $slugs    Candidate page slugs (order matters).
 * @param string   $fallback URL if none found.
 * @return string
 */
function swiftfix_find_page_url( $slugs, $fallback ) {
	foreach ( (array) $slugs as $slug ) {
		$posts = get_posts(
			array(
				'name'           => $slug,
				'post_type'      => 'page',
				'post_status'    => 'publish',
				'posts_per_page' => 1,
				'fields'         => 'ids',
			)
		);
		if ( ! empty( $posts ) ) {
			return get_permalink( $posts[0] );
		}
	}

	return $fallback;
}

add_action( 'wp_enqueue_scripts', 'swiftfix_enqueue_inner_template_assets', 102 );
function swiftfix_enqueue_inner_template_assets() {
	if ( ! is_page_template( 'page-swiftfix-inner.php' ) ) {
		return;
	}
	wp_enqueue_style(
		'swiftfix-inner',
		get_stylesheet_directory_uri() . '/assets/css/swiftfix-inner.css',
		array( 'rhye-child-style' ),
		wp_get_theme()->get( 'Version' )
	);
}

add_filter( 'body_class', 'swiftfix_body_class_inner_template' );
function swiftfix_body_class_inner_template( $classes ) {
	if ( is_page_template( 'page-swiftfix-inner.php' ) ) {
		$classes[] = 'swiftfix-inner-page';
	}

	return $classes;
}

/* =====================================================
   CUSTOMIZER: SwiftFix Settings
   ===================================================== */
add_action( 'customize_register', 'swiftfix_customize_register' );
function swiftfix_customize_register( $wp_customize ) {

	// --- Section ---
	$wp_customize->add_section( 'swiftfix_settings', array(
		'title'    => __( 'SwiftFix Settings', 'rhye-child' ),
		'priority' => 30,
	) );

	// --- Business Name ---
	$wp_customize->add_setting( 'sf_business_name', array(
		'default'           => 'SwiftFix',
		'sanitize_callback' => 'sanitize_text_field',
	) );
	$wp_customize->add_control( 'sf_business_name', array(
		'label'   => __( 'Business Name', 'rhye-child' ),
		'section' => 'swiftfix_settings',
		'type'    => 'text',
	) );

	// --- Phone Number ---
	$wp_customize->add_setting( 'sf_phone', array(
		'default'           => '0800 123 4567',
		'sanitize_callback' => 'sanitize_text_field',
	) );
	$wp_customize->add_control( 'sf_phone', array(
		'label'   => __( 'Phone Number', 'rhye-child' ),
		'section' => 'swiftfix_settings',
		'type'    => 'text',
	) );

	// --- Email ---
	$wp_customize->add_setting( 'sf_email', array(
		'default'           => 'hello@swiftfix.co.uk',
		'sanitize_callback' => 'sanitize_email',
	) );
	$wp_customize->add_control( 'sf_email', array(
		'label'   => __( 'Email Address', 'rhye-child' ),
		'section' => 'swiftfix_settings',
		'type'    => 'email',
	) );

	// --- Emergency Tagline ---
	$wp_customize->add_setting( 'sf_emergency_tagline', array(
		'default'           => 'Burst pipe? Power cut? No heating?',
		'sanitize_callback' => 'sanitize_text_field',
	) );
	$wp_customize->add_control( 'sf_emergency_tagline', array(
		'label'   => __( 'Emergency Tagline', 'rhye-child' ),
		'section' => 'swiftfix_settings',
		'type'    => 'text',
	) );

	// --- Google Review Rating ---
	$wp_customize->add_setting( 'sf_rating', array(
		'default'           => '4.9',
		'sanitize_callback' => 'sanitize_text_field',
	) );
	$wp_customize->add_control( 'sf_rating', array(
		'label'   => __( 'Google Review Rating', 'rhye-child' ),
		'section' => 'swiftfix_settings',
		'type'    => 'text',
	) );

	// --- Review Count ---
	$wp_customize->add_setting( 'sf_review_count', array(
		'default'           => '620',
		'sanitize_callback' => 'sanitize_text_field',
	) );
	$wp_customize->add_control( 'sf_review_count', array(
		'label'   => __( 'Total Review Count', 'rhye-child' ),
		'section' => 'swiftfix_settings',
		'type'    => 'text',
	) );

	// --- Company Reg Info ---
	$wp_customize->add_setting( 'sf_reg_info', array(
		'default'           => 'Registered in England & Wales. Gas Safe Reg. No. 123456.',
		'sanitize_callback' => 'sanitize_text_field',
	) );
	$wp_customize->add_control( 'sf_reg_info', array(
		'label'   => __( 'Registration Info (footer)', 'rhye-child' ),
		'section' => 'swiftfix_settings',
		'type'    => 'text',
	) );

	$wp_customize->add_setting( 'sf_hero_image', array(
		'default'           => 0,
		'sanitize_callback' => 'absint',
	) );
	$wp_customize->add_control(
		new WP_Customize_Media_Control(
			$wp_customize,
			'sf_hero_image',
			array(
				'label'       => __( 'Services landing hero image', 'rhye-child' ),
				'description' => __( 'Optional. If empty, the theme uses a built-in tradesman photo.', 'rhye-child' ),
				'section'     => 'swiftfix_settings',
				'mime_type'   => 'image',
			)
		)
	);
}
