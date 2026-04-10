<?php
/**
 * SwiftFix top bar + nav (landing + inner pages).
 *
 * @package Rhye_Child
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$g = swiftfix_get_shell_globals();
extract( $g, EXTR_SKIP ); // phpcs:ignore WordPress.PHP.DontExtract.extract_extract

?>
<div class="sf-topbar">
	<strong><?php esc_html_e( '24/7 Emergency Call-Outs', 'rhye-child' ); ?></strong> &mdash; <?php echo esc_html( $sf_tagline ); ?>
	<a href="<?php echo esc_url( $sf_phone_href ); ?>"><?php esc_html_e( 'Call', 'rhye-child' ); ?> <?php echo esc_html( $sf_phone ); ?> &rarr;</a>
</div>

<nav class="sf-nav" aria-label="<?php esc_attr_e( 'Primary', 'rhye-child' ); ?>">
	<a href="<?php echo esc_url( $url_home ); ?>" class="sf-logo">
		<?php
		if ( isset( $parts[1] ) ) {
			echo esc_html( $parts[0] ) . '<span>' . esc_html( $parts[1] ) . '</span>';
		} else {
			$len = strlen( $sf_name );
			if ( $len > 3 ) {
				echo esc_html( substr( $sf_name, 0, $len - 3 ) ) . '<span>' . esc_html( substr( $sf_name, -3 ) ) . '</span>';
			} else {
				echo esc_html( $sf_name );
			}
		}
		?>
	</a>

	<button class="sf-menu-toggle" type="button" aria-label="<?php esc_attr_e( 'Toggle navigation', 'rhye-child' ); ?>" aria-expanded="false" aria-controls="sf-nav-links">
		<span></span><span></span><span></span>
	</button>

	<ul class="sf-nav__links" id="sf-nav-links">
		<li><a href="<?php echo esc_url( $url_home ); ?>#services"><?php esc_html_e( 'Services', 'rhye-child' ); ?></a></li>
		<li><a href="<?php echo esc_url( $url_home ); ?>#how"><?php esc_html_e( 'How It Works', 'rhye-child' ); ?></a></li>
		<li><a href="<?php echo esc_url( $url_home ); ?>#reviews"><?php esc_html_e( 'Reviews', 'rhye-child' ); ?></a></li>
		<li><a href="<?php echo esc_url( $url_contact ); ?>"<?php echo $contact_scroll_class !== '' ? ' class="' . esc_attr( $contact_scroll_class ) . '"' : ''; ?>><?php esc_html_e( 'Contact', 'rhye-child' ); ?></a></li>
	</ul>
	<a href="<?php echo esc_url( $url_contact ); ?>" class="sf-btn sf-btn-amber sf-nav__cta<?php echo $contact_scroll_class !== '' ? ' ' . esc_attr( $contact_scroll_class ) : ''; ?>"><?php esc_html_e( 'Book Now', 'rhye-child' ); ?></a>
</nav>
