<?php
/**
 * SwiftFix footer (landing + inner pages).
 *
 * @package Rhye_Child
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$g = swiftfix_get_shell_globals();
extract( $g, EXTR_SKIP ); // phpcs:ignore WordPress.PHP.DontExtract.extract_extract

?>
<footer class="sf-footer">
	<div class="sf-footer__logo">
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
	</div>
	<p class="sf-footer__copy">&copy; <?php echo esc_html( gmdate( 'Y' ) ); ?> <?php echo esc_html( $sf_name ); ?> Ltd. <?php echo esc_html( $sf_reg ); ?></p>
	<ul class="sf-footer__links">
		<li><a href="<?php echo esc_url( $url_privacy ); ?>"><?php esc_html_e( 'Privacy', 'rhye-child' ); ?></a></li>
		<li><a href="<?php echo esc_url( $url_terms ); ?>"><?php esc_html_e( 'Terms', 'rhye-child' ); ?></a></li>
		<li><a href="<?php echo esc_url( $url_contact ); ?>"<?php echo $contact_scroll_class !== '' ? ' class="' . esc_attr( $contact_scroll_class ) . '"' : ''; ?>><?php esc_html_e( 'Contact', 'rhye-child' ); ?></a></li>
	</ul>
</footer>
