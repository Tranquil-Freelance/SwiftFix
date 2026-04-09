<?php
/**
 * Theme functions and definitions.
 * This is a child theme of Rhye.
 *
 * @link https://developer.wordpress.org/themes/basics/theme-functions/
 */

require_once get_stylesheet_directory() . '/inc/swiftfix-landing-helpers.php';

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
	wp_enqueue_style(
		'swiftfix-landing-shell',
		get_stylesheet_directory_uri() . '/assets/css/swiftfix-landing-shell.css',
		array( 'rhye-child-style' ),
		wp_get_theme()->get( 'Version' )
	);
	wp_enqueue_script(
		'swiftfix-landing',
		get_stylesheet_directory_uri() . '/assets/js/swiftfix-landing.js',
		array(),
		wp_get_theme()->get( 'Version' ),
		true
	);
}

add_filter( 'body_class', 'swiftfix_body_class_landing' );
function swiftfix_body_class_landing( $classes ) {
	if ( is_page_template( 'page-services-landing.php' ) ) {
		$classes[] = 'swiftfix-landing-page';
	}
	return $classes;
}

add_action( 'wp_head', 'swiftfix_print_local_business_schema', 4 );
function swiftfix_print_local_business_schema() {
	if ( ! is_page_template( 'page-services-landing.php' ) ) {
		return;
	}
	$name   = get_theme_mod( 'sf_business_name', 'SwiftFix' );
	$phone  = preg_replace( '/\s+/', '', get_theme_mod( 'sf_phone', '0800 123 4567' ) );
	$email  = get_theme_mod( 'sf_email', 'hello@swiftfix.co.uk' );
	$region = get_theme_mod( 'sf_area_served', 'Greater London' );
	$schema = array(
		'@context'    => 'https://schema.org',
		'@type'       => 'HomeAndConstructionBusiness',
		'name'        => $name,
		'url'         => home_url( '/' ),
		'telephone'   => $phone,
		'email'       => $email,
		'areaServed'  => $region,
		'description' => wp_strip_all_tags( get_theme_mod( 'sf_hero_sub', '' ) ),
	);
	if ( '' === $schema['description'] ) {
		unset( $schema['description'] );
	}
	echo '<script type="application/ld+json">' . wp_json_encode( $schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . '</script>' . "\n";
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

	$wp_customize->add_setting( 'sf_topbar_title', array(
		'default'           => '24/7 Emergency Call-Outs',
		'sanitize_callback' => 'sanitize_text_field',
	) );
	$wp_customize->add_control( 'sf_topbar_title', array(
		'label'   => __( 'Landing: top bar title', 'rhye-child' ),
		'section' => 'swiftfix_settings',
		'type'    => 'text',
	) );

	$wp_customize->add_setting( 'sf_nav_cta_label', array(
		'default'           => 'Book Now',
		'sanitize_callback' => 'sanitize_text_field',
	) );
	$wp_customize->add_control( 'sf_nav_cta_label', array(
		'label'   => __( 'Landing: nav CTA button', 'rhye-child' ),
		'section' => 'swiftfix_settings',
		'type'    => 'text',
	) );

	$wp_customize->add_setting( 'sf_hero_badge', array(
		'default'           => 'Trusted by 1,400+ UK homeowners',
		'sanitize_callback' => 'sanitize_text_field',
	) );
	$wp_customize->add_control( 'sf_hero_badge', array(
		'label'   => __( 'Landing: hero badge', 'rhye-child' ),
		'section' => 'swiftfix_settings',
		'type'    => 'text',
	) );

	$wp_customize->add_setting( 'sf_hero_heading', array(
		'default'           => 'Your home deserves the best tradespeople.',
		'sanitize_callback' => 'sanitize_text_field',
	) );
	$wp_customize->add_control( 'sf_hero_heading', array(
		'label'   => __( 'Landing: hero headline', 'rhye-child' ),
		'section' => 'swiftfix_settings',
		'type'    => 'text',
	) );

	$wp_customize->add_setting( 'sf_hero_sub', array(
		'default'           => 'Certified electricians, plumbers, heating engineers & builders — all under one roof. No call-out fee, no hidden costs.',
		'sanitize_callback' => 'sanitize_textarea_field',
	) );
	$wp_customize->add_control( 'sf_hero_sub', array(
		'label'   => __( 'Landing: hero subtext', 'rhye-child' ),
		'section' => 'swiftfix_settings',
		'type'    => 'textarea',
	) );

	$wp_customize->add_setting( 'sf_hero_img_alt', array(
		'default'           => 'Friendly tradesperson working in a modern home',
		'sanitize_callback' => 'sanitize_text_field',
	) );
	$wp_customize->add_control( 'sf_hero_img_alt', array(
		'label'   => __( 'Landing: hero image alt text', 'rhye-child' ),
		'section' => 'swiftfix_settings',
		'type'    => 'text',
	) );

	$wp_customize->add_setting( 'sf_hero_btn_primary', array(
		'default'           => 'Get Free Quote',
		'sanitize_callback' => 'sanitize_text_field',
	) );
	$wp_customize->add_control( 'sf_hero_btn_primary', array(
		'label'   => __( 'Landing: hero primary button', 'rhye-child' ),
		'section' => 'swiftfix_settings',
		'type'    => 'text',
	) );

	$wp_customize->add_setting( 'sf_hero_btn_secondary', array(
		'default'           => 'Our Services',
		'sanitize_callback' => 'sanitize_text_field',
	) );
	$wp_customize->add_control( 'sf_hero_btn_secondary', array(
		'label'   => __( 'Landing: hero secondary button', 'rhye-child' ),
		'section' => 'swiftfix_settings',
		'type'    => 'text',
	) );

	$wp_customize->add_setting( 'sf_trust_google_suffix', array(
		'default'           => 'Google',
		'sanitize_callback' => 'sanitize_text_field',
	) );
	$wp_customize->add_control( 'sf_trust_google_suffix', array(
		'label'   => __( 'Landing: rating chip suffix (e.g. Google)', 'rhye-child' ),
		'section' => 'swiftfix_settings',
		'type'    => 'text',
	) );

	$wp_customize->add_setting( 'sf_trust_chip_2', array(
		'default'           => 'Gas Safe',
		'sanitize_callback' => 'sanitize_text_field',
	) );
	$wp_customize->add_control( 'sf_trust_chip_2', array(
		'label'   => __( 'Landing: trust chip 2', 'rhye-child' ),
		'section' => 'swiftfix_settings',
		'type'    => 'text',
	) );

	$wp_customize->add_setting( 'sf_trust_chip_3', array(
		'default'           => 'NICEIC Approved',
		'sanitize_callback' => 'sanitize_text_field',
	) );
	$wp_customize->add_control( 'sf_trust_chip_3', array(
		'label'   => __( 'Landing: trust chip 3', 'rhye-child' ),
		'section' => 'swiftfix_settings',
		'type'    => 'text',
	) );

	$wp_customize->add_setting( 'sf_trust_chip_4', array(
		'default'           => 'Fully Insured',
		'sanitize_callback' => 'sanitize_text_field',
	) );
	$wp_customize->add_control( 'sf_trust_chip_4', array(
		'label'   => __( 'Landing: trust chip 4', 'rhye-child' ),
		'section' => 'swiftfix_settings',
		'type'    => 'text',
	) );

	$wp_customize->add_setting( 'sf_services_label', array(
		'default'           => 'Services',
		'sanitize_callback' => 'sanitize_text_field',
	) );
	$wp_customize->add_control( 'sf_services_label', array(
		'label'   => __( 'Landing: services section label', 'rhye-child' ),
		'section' => 'swiftfix_settings',
		'type'    => 'text',
	) );

	$wp_customize->add_setting( 'sf_services_title', array(
		'default'           => 'What we can help with',
		'sanitize_callback' => 'sanitize_text_field',
	) );
	$wp_customize->add_control( 'sf_services_title', array(
		'label'   => __( 'Landing: services title', 'rhye-child' ),
		'section' => 'swiftfix_settings',
		'type'    => 'text',
	) );

	$wp_customize->add_setting( 'sf_services_sub', array(
		'default'           => 'From quick fixes to full installations — one team that does it all, properly.',
		'sanitize_callback' => 'sanitize_textarea_field',
	) );
	$wp_customize->add_control( 'sf_services_sub', array(
		'label'   => __( 'Landing: services intro', 'rhye-child' ),
		'section' => 'swiftfix_settings',
		'type'    => 'textarea',
	) );

	$wp_customize->add_setting( 'sf_how_label', array(
		'default'           => 'Simple Process',
		'sanitize_callback' => 'sanitize_text_field',
	) );
	$wp_customize->add_control( 'sf_how_label', array(
		'label'   => __( 'Landing: how-it-works label', 'rhye-child' ),
		'section' => 'swiftfix_settings',
		'type'    => 'text',
	) );

	$wp_customize->add_setting( 'sf_how_title', array(
		'default'           => 'How it works',
		'sanitize_callback' => 'sanitize_text_field',
	) );
	$wp_customize->add_control( 'sf_how_title', array(
		'label'   => __( 'Landing: how-it-works title', 'rhye-child' ),
		'section' => 'swiftfix_settings',
		'type'    => 'text',
	) );

	$wp_customize->add_setting( 'sf_reviews_label', array(
		'default'           => 'Reviews',
		'sanitize_callback' => 'sanitize_text_field',
	) );
	$wp_customize->add_control( 'sf_reviews_label', array(
		'label'   => __( 'Landing: reviews sidebar label (hidden on layout)', 'rhye-child' ),
		'section' => 'swiftfix_settings',
		'type'    => 'text',
	) );

	$wp_customize->add_setting( 'sf_rating_label', array(
		'default'           => 'out of 5',
		'sanitize_callback' => 'sanitize_text_field',
	) );
	$wp_customize->add_control( 'sf_rating_label', array(
		'label'   => __( 'Landing: rating subtitle', 'rhye-child' ),
		'section' => 'swiftfix_settings',
		'type'    => 'text',
	) );

	$wp_customize->add_setting( 'sf_reviews_count_label', array(
		'default'           => 'verified reviews',
		'sanitize_callback' => 'sanitize_text_field',
	) );
	$wp_customize->add_control( 'sf_reviews_count_label', array(
		'label'   => __( 'Landing: review count suffix', 'rhye-child' ),
		'section' => 'swiftfix_settings',
		'type'    => 'text',
	) );

	$wp_customize->add_setting( 'sf_cta_heading', array(
		'default'           => 'Ready to get started?',
		'sanitize_callback' => 'sanitize_text_field',
	) );
	$wp_customize->add_control( 'sf_cta_heading', array(
		'label'   => __( 'Landing: CTA heading', 'rhye-child' ),
		'section' => 'swiftfix_settings',
		'type'    => 'text',
	) );

	$wp_customize->add_setting( 'sf_cta_sub', array(
		'default'           => 'Free, no-obligation quotes. We cover all of Greater London & surrounding areas.',
		'sanitize_callback' => 'sanitize_textarea_field',
	) );
	$wp_customize->add_control( 'sf_cta_sub', array(
		'label'   => __( 'Landing: CTA intro', 'rhye-child' ),
		'section' => 'swiftfix_settings',
		'type'    => 'textarea',
	) );

	$wp_customize->add_setting( 'sf_cta_quote_btn', array(
		'default'           => 'Request a Free Quote',
		'sanitize_callback' => 'sanitize_text_field',
	) );
	$wp_customize->add_control( 'sf_cta_quote_btn', array(
		'label'   => __( 'Landing: CTA email button', 'rhye-child' ),
		'section' => 'swiftfix_settings',
		'type'    => 'text',
	) );

	$wp_customize->add_setting( 'sf_cta_services_btn', array(
		'default'           => 'View Services',
		'sanitize_callback' => 'sanitize_text_field',
	) );
	$wp_customize->add_control( 'sf_cta_services_btn', array(
		'label'   => __( 'Landing: CTA secondary button', 'rhye-child' ),
		'section' => 'swiftfix_settings',
		'type'    => 'text',
	) );

	$wp_customize->add_setting( 'sf_area_served', array(
		'default'           => 'Greater London',
		'sanitize_callback' => 'sanitize_text_field',
	) );
	$wp_customize->add_control( 'sf_area_served', array(
		'label'       => __( 'Service area (SEO / schema)', 'rhye-child' ),
		'description' => __( 'Used in structured data for local search.', 'rhye-child' ),
		'section'     => 'swiftfix_settings',
		'type'        => 'text',
	) );

	$wp_customize->add_setting( 'sf_privacy_url', array(
		'default'           => '',
		'sanitize_callback' => 'esc_url_raw',
	) );
	$wp_customize->add_control( 'sf_privacy_url', array(
		'label'       => __( 'Landing: Privacy link URL', 'rhye-child' ),
		'description' => __( 'Leave empty to use /privacy-policy/', 'rhye-child' ),
		'section'     => 'swiftfix_settings',
		'type'        => 'url',
	) );

	$wp_customize->add_setting( 'sf_terms_url', array(
		'default'           => '',
		'sanitize_callback' => 'esc_url_raw',
	) );
	$wp_customize->add_control( 'sf_terms_url', array(
		'label'       => __( 'Landing: Terms link URL', 'rhye-child' ),
		'description' => __( 'Leave empty to use /terms/', 'rhye-child' ),
		'section'     => 'swiftfix_settings',
		'type'        => 'url',
	) );
}
