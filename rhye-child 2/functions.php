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
