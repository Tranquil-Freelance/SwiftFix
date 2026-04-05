<?php
/**
 * Plugin Name: SwiftFix — activate Rhye child theme once
 * Description: Fresh installs default to Twenty *; switch to the bundled child theme a single time.
 *
 * Override target: env SWIFTFIX_THEME (default rhye-child). Disable: env SWIFTFIX_THEME=0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action(
	'init',
	static function () {
		if ( get_option( 'swiftfix_rhye_auto_done' ) ) {
			return;
		}
		if ( defined( 'WP_INSTALLING' ) && WP_INSTALLING ) {
			return;
		}

		$target = getenv( 'SWIFTFIX_THEME' );
		if ( $target === false || $target === '' ) {
			$target = 'rhye-child';
		}
		if ( $target === '0' || $target === 'false' ) {
			return;
		}

		$theme = wp_get_theme( $target );
		if ( ! $theme->exists() ) {
			return;
		}

		$stylesheet = (string) get_option( 'stylesheet' );
		if ( $stylesheet === $target ) {
			update_option( 'swiftfix_rhye_auto_done', true );
			return;
		}

		$defaults = array(
			'twentytwentyfive',
			'twentytwentyfour',
			'twentytwentythree',
			'twentytwentytwo',
			'twentytwentyone',
			'twentytwenty',
		);

		if ( in_array( $stylesheet, $defaults, true ) ) {
			switch_theme( $target );
			update_option( 'swiftfix_rhye_auto_done', true );
		}
	},
	0
);
