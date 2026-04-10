<?php
/**
 * Contact routing: prefer SwiftFix /contact/, redirect demo contacts-02, embed working CF7 form.
 *
 * @package Rhye_Child
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Visitors hitting the Rhye demo "contacts-02" page (broken/missing CF7) go to /contact/.
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
	if ( current_user_can( 'edit_page', $page_id ) ) {
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
	if ( empty( $contact_posts ) ) {
		return;
	}
	$url = get_permalink( $contact_posts[0] );
	if ( ! $url || (int) $page_id === (int) $contact_posts[0] ) {
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

	$title = 'SwiftFix quote request';

	$existing = get_posts(
		array(
			'post_type'      => 'wpcf7_contact_form',
			'post_status'    => 'publish',
			'posts_per_page' => 100,
		)
	);
	foreach ( $existing as $f ) {
		if ( $f->post_title === $title ) {
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
 * Ensure the /contact/ page has a valid Contact Form 7 shortcode (creates form if needed).
 *
 * @return void
 */
function swiftfix_ensure_contact_page_cf7_shortcode() {
	if ( ! class_exists( 'WPCF7_ContactForm' ) ) {
		return;
	}
	if ( get_option( 'swiftfix_cf7_contact_page_ready' ) ) {
		return;
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
