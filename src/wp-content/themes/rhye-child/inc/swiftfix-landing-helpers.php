<?php
/**
 * SwiftFix landing: default content + filters for child themes / plugins.
 *
 * @package Rhye_Child
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @return array<int, array{icon:string,icon_class:string,title:string,body:string,link:string,link_label:string}>
 */
function swiftfix_default_landing_services() {
	return array(
		array(
			'icon'        => '&#9889;',
			'icon_class'  => 'sf-service-icon--blue',
			'title'       => __( 'Electrical', 'rhye-child' ),
			'body'        => __( 'Fuse boards, rewiring, fault finding, EV chargers, outdoor lighting and full new-build wiring.', 'rhye-child' ),
			'link'        => '#contact',
			'link_label'  => __( 'Get a quote', 'rhye-child' ),
		),
		array(
			'icon'        => '&#128167;',
			'icon_class'  => 'sf-service-icon--cyan',
			'title'       => __( 'Plumbing', 'rhye-child' ),
			'body'        => __( 'Burst pipes, leaks, blocked drains, bathroom fitting, tap replacement and full renovations.', 'rhye-child' ),
			'link'        => '#contact',
			'link_label'  => __( 'Learn more', 'rhye-child' ),
		),
		array(
			'icon'        => '&#128293;',
			'icon_class'  => 'sf-service-icon--orange',
			'title'       => __( 'Heating & Gas', 'rhye-child' ),
			'body'        => __( 'Boiler servicing, breakdowns, radiator installs, central heating systems and smart thermostats.', 'rhye-child' ),
			'link'        => '#contact',
			'link_label'  => __( 'Learn more', 'rhye-child' ),
		),
		array(
			'icon'        => '&#127968;',
			'icon_class'  => 'sf-service-icon--green',
			'title'       => __( 'Building & Renovation', 'rhye-child' ),
			'body'        => __( 'Extensions, loft conversions, kitchen fitting, plastering, tiling and property maintenance.', 'rhye-child' ),
			'link'        => '#contact',
			'link_label'  => __( 'Learn more', 'rhye-child' ),
		),
	);
}

/**
 * @return array<int, array{name:string,src:string,text:string}>
 */
function swiftfix_default_landing_reviews() {
	return array(
		array(
			'name' => 'Jonah B.',
			'src'  => 'Google',
			'text' => __( 'The electrician arrived within 2 hours and sorted our power issue quickly. Polite, clean, and professional. Will use again.', 'rhye-child' ),
		),
		array(
			'name' => 'Sarah C.',
			'src'  => 'Trustpilot',
			'text' => __( 'Fixed our boiler the same day. Left no mess, explained everything clearly. Best tradesman experience I\'ve had.', 'rhye-child' ),
		),
		array(
			'name' => 'Mohammed R.',
			'src'  => 'Google',
			'text' => __( 'Brilliant builders — the kitchen extension is exactly what we wanted. On time, on budget. Highly recommend.', 'rhye-child' ),
		),
		array(
			'name' => 'Emily T.',
			'src'  => 'Trustpilot',
			'text' => __( 'Had a burst pipe at midnight. They answered immediately and had someone here within 45 minutes. Lifesavers!', 'rhye-child' ),
		),
	);
}

/**
 * @return array<int, array{icon:string,icon_class:string,title:string,body:string,link:string}>
 */
function swiftfix_get_landing_services() {
	return apply_filters( 'swiftfix_landing_services', swiftfix_default_landing_services() );
}

/**
 * @return array<int, array{name:string,src:string,text:string}>
 */
function swiftfix_get_landing_reviews() {
	return apply_filters( 'swiftfix_landing_reviews', swiftfix_default_landing_reviews() );
}

/**
 * @return array<int, array{title:string,text:string}>
 */
function swiftfix_default_landing_steps() {
	return array(
		array(
			'title' => __( 'Tell us what you need', 'rhye-child' ),
			'text'  => __( 'Describe your job online or call us directly. No jargon needed — just plain English.', 'rhye-child' ),
		),
		array(
			'title' => __( 'We send the right expert', 'rhye-child' ),
			'text'  => __( 'A certified, DBS-checked engineer arrives at a time that works for you.', 'rhye-child' ),
		),
		array(
			'title' => __( 'Job done, guaranteed', 'rhye-child' ),
			'text'  => __( 'Fixed-price work, no surprises. Every job is backed by our 12-month guarantee.', 'rhye-child' ),
		),
	);
}

/**
 * @return array<int, array{title:string,text:string}>
 */
function swiftfix_get_landing_steps() {
	return apply_filters( 'swiftfix_landing_steps', swiftfix_default_landing_steps() );
}
