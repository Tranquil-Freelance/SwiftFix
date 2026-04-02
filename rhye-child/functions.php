<?php
/**
 * Theme functions and definitions.
 * This is a child theme of Rhye.
 *
 * @link https://developer.wordpress.org/themes/basics/theme-functions/
 */

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
}
