<?php
/**
 * Contact routing: prefer CAE Fix /contact/, redirect demo contacts-02, embed working CF7 form.
 *
 * @package Rhye_Child
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Ensure a published page with slug "contact" exists so /contact/ and redirects work even when
 * only the Rhye demo "contacts-02" page was imported.
 *
 * @return void
 */
function swiftfix_ensure_published_contact_slug_page() {
	if ( ! function_exists( 'is_blog_installed' ) || ! is_blog_installed() ) {
		return;
	}
	if ( defined( 'WP_INSTALLING' ) && WP_INSTALLING ) {
		return;
	}

	$published = get_posts(
		array(
			'name'           => 'contact',
			'post_type'      => 'page',
			'post_status'    => 'publish',
			'posts_per_page' => 1,
			'fields'         => 'ids',
		)
	);
	if ( ! empty( $published ) ) {
		swiftfix_maybe_assign_inner_template_to_page( (int) $published[0] );

		return;
	}

	$unpublished = get_posts(
		array(
			'name'           => 'contact',
			'post_type'      => 'page',
			'post_status'    => array( 'draft', 'pending', 'future', 'private' ),
			'posts_per_page' => 1,
			'fields'         => 'ids',
		)
	);
	if ( ! empty( $unpublished ) ) {
		$pid = (int) $unpublished[0];
		wp_update_post(
			array(
				'ID'          => $pid,
				'post_status' => 'publish',
			)
		);
		swiftfix_maybe_assign_inner_template_to_page( $pid );

		return;
	}

	if ( get_option( 'swiftfix_autocreated_contact_slug_v1' ) ) {
		return;
	}

	$pid = wp_insert_post(
		array(
			'post_title'   => __( 'Contact', 'rhye-child' ),
			'post_name'    => 'contact',
			'post_status'  => 'publish',
			'post_type'    => 'page',
			'post_content' => '<p>' . esc_html__( 'Reach us by phone, email, or the form below.', 'rhye-child' ) . '</p>',
		),
		true
	);
	if ( is_wp_error( $pid ) || ! $pid ) {
		return;
	}
	swiftfix_maybe_assign_inner_template_to_page( (int) $pid );
	update_option( 'swiftfix_autocreated_contact_slug_v1', '1', false );
}

/**
 * Assign CAE Fix inner template when the page is not an active Elementor builder document.
 *
 * @param int $page_id Post ID.
 * @return void
 */
function swiftfix_maybe_assign_inner_template_to_page( $page_id ) {
	$page_id = (int) $page_id;
	if ( $page_id < 1 ) {
		return;
	}
	if ( get_post_meta( $page_id, '_elementor_edit_mode', true ) === 'builder' ) {
		return;
	}
	if ( 'page-swiftfix-inner.php' === (string) get_post_meta( $page_id, '_wp_page_template', true ) ) {
		return;
	}
	update_post_meta( $page_id, '_wp_page_template', 'page-swiftfix-inner.php' );
}

add_action( 'init', 'swiftfix_ensure_published_contact_slug_page', 9 );

/**
 * Visitors hitting the Rhye demo "contacts-02" page (broken/missing CF7) go to /contact/.
 * Logged-in editors were skipping redirect and still saw the broken demo; Elementor preview stays on the demo URL.
 *
 * @return void
 */
function swiftfix_redirect_contacts_02_to_contact_page() {
	if ( ! is_page() ) {
		return;
	}
	$page_id = get_queried_object_id();
	if ( ! $page_id ) {
		return;
	}
	if ( 'contacts-02' !== get_post_field( 'post_name', $page_id ) ) {
		return;
	}
	if ( is_preview() ) {
		return;
	}
	if ( isset( $_GET['elementor-preview'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		return;
	}
	$contact_posts = get_posts(
		array(
			'name'           => 'contact',
			'post_type'      => 'page',
			'post_status'    => 'publish',
			'posts_per_page' => 1,
			'fields'         => 'ids',
		)
	);
	$url = '';
	if ( ! empty( $contact_posts ) ) {
		$url = get_permalink( $contact_posts[0] );
	}
	if ( ! $url && function_exists( 'swiftfix_find_page_url' ) ) {
		$url = swiftfix_find_page_url( array( 'contact-us' ), '' );
	}
	if ( ! $url ) {
		return;
	}
	if ( (int) $page_id === url_to_postid( $url ) ) {
		return;
	}
	wp_safe_redirect( $url, 301 );
	exit;
}

add_action( 'template_redirect', 'swiftfix_redirect_contacts_02_to_contact_page', 5 );

/**
 * @return int CF7 post ID or 0.
 */
function swiftfix_create_or_get_cf7_quote_form() {
	if ( ! class_exists( 'WPCF7_ContactForm' ) ) {
		return 0;
	}

	$title         = 'CAE Fix quote request';
	$legacy_titles = array( $title, 'SwiftFix quote request' );

	$existing = get_posts(
		array(
			'post_type'      => 'wpcf7_contact_form',
			'post_status'    => 'publish',
			'posts_per_page' => 100,
		)
	);
	foreach ( $existing as $f ) {
		if ( in_array( $f->post_title, $legacy_titles, true ) ) {
			return (int) $f->ID;
		}
	}

	$cf = WPCF7_ContactForm::get_template( array( 'title' => $title ) );
	$props = $cf->get_properties();

	$props['form'] = '<label> ' . __( 'Your name', 'rhye-child' ) . "\n    [text* your-name autocomplete:name] </label>\n\n"
		. '<label> ' . __( 'Your email', 'rhye-child' ) . "\n    [email* your-email autocomplete:email] </label>\n\n"
		. '<label> ' . __( 'Phone (optional)', 'rhye-child' ) . "\n    [tel your-phone] </label>\n\n"
		. '<label> ' . __( 'What do you need help with?', 'rhye-child' ) . "\n    [textarea* your-message] </label>\n\n"
		. '[submit "' . __( 'Request a free quote', 'rhye-child' ) . '"]';

	$blog = get_bloginfo( 'name', 'display' );
	$props['mail']['subject']             = '[' . $blog . '] ' . __( 'Quote request from', 'rhye-child' ) . ' [your-name]';
	$props['mail']['sender']              = $blog . ' <' . get_option( 'admin_email' ) . '>';
	$props['mail']['body']                = __( 'From:', 'rhye-child' ) . " [your-name] <[your-email]>\n"
		. __( 'Phone:', 'rhye-child' ) . " [your-phone]\n\n[your-message]";
	$props['mail']['recipient']           = get_option( 'admin_email' );
	$props['mail']['additional_headers']  = 'Reply-To: [your-email]';

	$cf->set_properties( $props );
	$cf->save();

	return $cf->id() ? (int) $cf->id() : 0;
}

/**
 * Cached CF7 form ID for the CAE Fix quote form (creates the form when CF7 is available).
 *
 * @return int
 */
function swiftfix_get_cf7_quote_form_id() {
	static $cached = null;
	if ( null !== $cached ) {
		return $cached;
	}
	$cached = 0;
	if ( class_exists( 'WPCF7_ContactForm' ) ) {
		$cached = swiftfix_create_or_get_cf7_quote_form();
	}

	return (int) $cached;
}

add_action( 'wpcf7_init', 'swiftfix_get_cf7_quote_form_id', 5 );

/**
 * Load CF7 scripts/styles on CAE Fix inner contact pages even when the shortcode is only output from the template
 * (not stored in post_content), so submission and validation work.
 *
 * @param bool $load Whether to load the default script.
 * @return bool
 */
function swiftfix_cf7_load_js_on_inner_contact( $load ) {
	if ( $load ) {
		return $load;
	}
	if ( ! is_page_template( 'page-swiftfix-inner.php' ) ) {
		return $load;
	}
	$slug = get_post_field( 'post_name', get_queried_object_id() );
	if ( ! in_array( (string) $slug, array( 'contact', 'contacts-02', 'contact-us' ), true ) ) {
		return $load;
	}

	return true;
}

add_filter( 'wpcf7_load_js', 'swiftfix_cf7_load_js_on_inner_contact', 20 );

/**
 * @return void
 */
function swiftfix_cf7_enqueue_assets_inner_contact() {
	if ( ! is_page_template( 'page-swiftfix-inner.php' ) ) {
		return;
	}
	$slug = get_post_field( 'post_name', get_queried_object_id() );
	if ( ! in_array( (string) $slug, array( 'contact', 'contacts-02', 'contact-us' ), true ) ) {
		return;
	}
	if ( function_exists( 'wpcf7_enqueue_scripts' ) ) {
		wpcf7_enqueue_scripts();
	}
	if ( function_exists( 'wpcf7_enqueue_styles' ) ) {
		wpcf7_enqueue_styles();
	}
}

add_action( 'wp_enqueue_scripts', 'swiftfix_cf7_enqueue_assets_inner_contact', 999 );

/**
 * Ensure the /contact/ page has a valid Contact Form 7 shortcode (creates form if needed).
 *
 * @return void
 */
function swiftfix_ensure_contact_page_cf7_shortcode() {
	if ( ! class_exists( 'WPCF7_ContactForm' ) ) {
		return;
	}
	if ( get_option( 'swiftfix_cf7_contact_page_ready' ) ) {
		$contact_posts = get_posts(
			array(
				'name'           => 'contact',
				'post_type'      => 'page',
				'post_status'    => 'publish',
				'posts_per_page' => 1,
			)
		);
		if ( empty( $contact_posts ) ) {
			delete_option( 'swiftfix_cf7_contact_page_ready' );

			return;
		}
		$content_chk = (string) $contact_posts[0]->post_content;
		if ( preg_match( '/\[contact-form-7[^\]]*\bid\s*=\s*["\']?(\d+)/i', $content_chk, $m ) ) {
			$fid = (int) $m[1];
			if ( $fid && get_post( $fid ) && 'wpcf7_contact_form' === get_post_type( $fid ) ) {
				return;
			}
		}
		delete_option( 'swiftfix_cf7_contact_page_ready' );
	}

	$contact_posts = get_posts(
		array(
			'name'           => 'contact',
			'post_type'      => 'page',
			'post_status'    => 'publish',
			'posts_per_page' => 1,
		)
	);

	if ( empty( $contact_posts ) ) {
		return;
	}

	$post    = $contact_posts[0];
	$content = (string) $post->post_content;

	if ( preg_match( '/\[contact-form-7[^\]]*\bid\s*=\s*["\']?(\d+)/i', $content, $m ) ) {
		$fid = (int) $m[1];
		if ( $fid && get_post( $fid ) && 'wpcf7_contact_form' === get_post_type( $fid ) ) {
			update_option( 'swiftfix_cf7_contact_page_ready', '1' );

			return;
		}
	}

	$content = preg_replace( '/\s*\[contact-form-7[^\]]*\]\s*/i', "\n", $content );
	$content = trim( $content );

	$form_id = swiftfix_create_or_get_cf7_quote_form();
	if ( ! $form_id ) {
		return;
	}

	$shortcode = sprintf( '[contact-form-7 id="%d" html_class="swiftfix-cf7-form"]', $form_id );
	$new       = $shortcode . "\n\n" . $content;

	wp_update_post(
		array(
			'ID'           => (int) $post->ID,
			'post_content' => $new,
		)
	);

	update_option( 'swiftfix_cf7_contact_page_ready', '1' );
}

add_action( 'init', 'swiftfix_ensure_contact_page_cf7_shortcode', 120 );
