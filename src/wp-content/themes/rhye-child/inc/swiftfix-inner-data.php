<?php
/**
 * SwiftFix branded inner pages: service presets + page kind detection.
 *
 * @package Rhye_Child
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Shared shell variables for landing + inner templates.
 *
 * @return array<string, mixed>
 */
function swiftfix_get_shell_globals() {
	static $cached = null;
	if ( null !== $cached ) {
		return $cached;
	}

	$sf_name       = get_theme_mod( 'sf_business_name', 'SwiftFix' );
	$sf_phone      = get_theme_mod( 'sf_phone', '0800 123 4567' );
	$sf_email      = get_theme_mod( 'sf_email', 'hello@swiftfix.co.uk' );
	$sf_tagline    = get_theme_mod( 'sf_emergency_tagline', 'Burst pipe? Power cut? No heating?' );
	$sf_reg        = get_theme_mod( 'sf_reg_info', 'Registered in England & Wales. Gas Safe Reg. No. 123456.' );
	$sf_phone_href = 'tel:' . preg_replace( '/\s+/', '', $sf_phone );
	$parts         = explode( ' ', $sf_name, 2 );

	$url_home     = home_url( '/' );
	$url_privacy  = swiftfix_find_page_url( array( 'privacy-policy', 'privacy' ), home_url( '/privacy-policy/' ) );
	$url_terms    = swiftfix_find_page_url( array( 'terms', 'terms-of-service', 'terms-and-conditions' ), home_url( '/terms/' ) );
	$url_contact  = swiftfix_find_page_url( array( 'contact', 'contacts-02', 'contact-us' ), home_url( '/#contact' ) );
	$contact_scroll_class = ( false !== strpos( $url_contact, '#contact' ) ) ? 'sf-scroll' : '';

	$cached = array(
		'sf_name'               => $sf_name,
		'sf_phone'              => $sf_phone,
		'sf_email'              => $sf_email,
		'sf_tagline'            => $sf_tagline,
		'sf_reg'                => $sf_reg,
		'sf_phone_href'         => $sf_phone_href,
		'parts'                 => $parts,
		'url_home'              => $url_home,
		'url_privacy'           => $url_privacy,
		'url_terms'             => $url_terms,
		'url_contact'           => $url_contact,
		'contact_scroll_class'  => $contact_scroll_class,
	);

	return $cached;
}

/**
 * Service detail presets (hero imagery + copy). Images: Unsplash (hotlink OK per their license).
 *
 * @return array<string, array<string, mixed>>
 */
function swiftfix_get_service_presets() {
	return array(
		'electrical' => array(
			'title'       => __( 'Electrical services', 'rhye-child' ),
			'label'       => __( 'Electrical', 'rhye-child' ),
			'icon_class'  => 'sf-service-icon--blue',
			'icon_char'   => '&#9889;',
			'hero_bg'     => 'https://images.unsplash.com/photo-1621905251189-08b45d6a269e?w=1920&h=900&fit=crop&crop=center&auto=format&q=82',
			'intro'       => __( 'From minor repairs to full rewires, our qualified electricians keep your home safe, compliant, and ready for modern living — including EV chargers and smart lighting.', 'rhye-child' ),
			'features'    => array(
				__( 'Consumer unit upgrades & fuse board replacements', 'rhye-child' ),
				__( 'Full & partial rewiring; fault finding & certification', 'rhye-child' ),
				__( 'EV charger installation & outdoor power', 'rhye-child' ),
				__( 'Lighting design, downlights, and security lighting', 'rhye-child' ),
				__( 'New-build & extension electrics', 'rhye-child' ),
				__( 'Landlord EICR and safety checks', 'rhye-child' ),
			),
			'note'        => __( 'Work is carried out by competent electricians and documented to BS 7671 where applicable. Tell us about your property and we will recommend the right visit.', 'rhye-child' ),
		),
		'plumbing'   => array(
			'title'       => __( 'Plumbing services', 'rhye-child' ),
			'label'       => __( 'Plumbing', 'rhye-child' ),
			'icon_class'  => 'sf-service-icon--cyan',
			'icon_char'   => '&#128167;',
			'hero_bg'     => 'https://images.unsplash.com/photo-1584622650111-993a426fbf0a?w=1920&h=900&fit=crop&crop=center&auto=format&q=82',
			'intro'       => __( 'Leaks, blockages, installs, and full bathroom projects — one team that turns up on time, protects your home, and leaves the job tidy.', 'rhye-child' ),
			'features'    => array(
				__( 'Emergency leaks, burst pipes & isolation', 'rhye-child' ),
				__( 'Taps, toilets, cylinders & pumps', 'rhye-child' ),
				__( 'Drain unblocking & repair', 'rhye-child' ),
				__( 'Bathroom & kitchen plumbing for renovations', 'rhye-child' ),
				__( 'Radiator changes & system alterations', 'rhye-child' ),
				__( 'Water pressure issues & pipework upgrades', 'rhye-child' ),
			),
			'note'        => __( 'We explain what we find before any extra work, and we can coordinate with heating or building trades when your job spans more than one trade.', 'rhye-child' ),
		),
		'heating'    => array(
			'title'       => __( 'Heating & gas services', 'rhye-child' ),
			'label'       => __( 'Heating & gas', 'rhye-child' ),
			'icon_class'  => 'sf-service-icon--orange',
			'icon_char'   => '&#128293;',
			'hero_bg'     => 'https://images.unsplash.com/photo-1558618666-fcd25c85cd64?w=1920&h=900&fit=crop&crop=center&auto=format&q=82',
			'intro'       => __( 'Boilers, radiators, and controls — serviced and repaired by engineers who understand UK heating systems and safety requirements.', 'rhye-child' ),
			'features'    => array(
				__( 'Boiler service, repair & replacement', 'rhye-child' ),
				__( 'Central heating diagnostics & balancing', 'rhye-child' ),
				__( 'Radiator installs, TRVs & power flushing (where appropriate)', 'rhye-child' ),
				__( 'Smart thermostats & zoning', 'rhye-child' ),
				__( 'Landlord gas safety records', 'rhye-child' ),
				__( 'Underfloor heating & manifold checks', 'rhye-child' ),
			),
			'note'        => __( 'Gas work is performed by Gas Safe registered engineers. Share your boiler make/model and any error codes for a faster first visit.', 'rhye-child' ),
		),
		'building'   => array(
			'title'       => __( 'Building & renovation', 'rhye-child' ),
			'label'       => __( 'Building & renovation', 'rhye-child' ),
			'icon_class'  => 'sf-service-icon--green',
			'icon_char'   => '&#127968;',
			'hero_bg'     => 'https://images.unsplash.com/photo-1503387762-592deb58ef4e?w=1920&h=900&fit=crop&crop=center&auto=format&q=82',
			'intro'       => __( 'Structural alterations, kitchens, lofts, and finishing trades — planned properly so electrics, plumbing, and heating stay coordinated.', 'rhye-child' ),
			'features'    => array(
				__( 'Extensions, loft conversions & structural openings', 'rhye-child' ),
				__( 'Kitchen fitting & first-fix coordination', 'rhye-child' ),
				__( 'Plastering, dry lining & making good', 'rhye-child' ),
				__( 'Tiling, flooring prep & finishing', 'rhye-child' ),
				__( 'External repairs, fencing & minor groundworks', 'rhye-child' ),
				__( 'Property maintenance programmes', 'rhye-child' ),
			),
			'note'        => __( 'Larger projects include clear staged quotes and a single point of contact. We work to agreed schedules and keep you updated as work progresses.', 'rhye-child' ),
		),
	);
}

/**
 * Map page slug → service preset key.
 *
 * @return array<string, string>
 */
function swiftfix_service_slug_to_preset_key() {
	return array(
		'swiftfix-electrical'          => 'electrical',
		'swiftfix-plumbing'            => 'plumbing',
		'swiftfix-heating-gas'         => 'heating',
		'swiftfix-building-renovation' => 'building',
	);
}

/**
 * @param string $slug Post slug.
 * @return array{type:string,key?:string,hero_bg?:string,hero_title?:string,hero_kicker?:string}
 */
function swiftfix_detect_inner_page_kind( $slug ) {
	$map = swiftfix_service_slug_to_preset_key();
	if ( isset( $map[ $slug ] ) ) {
		return array(
			'type' => 'service',
			'key'  => $map[ $slug ],
		);
	}

	if ( 'privacy-policy' === $slug || 'privacy' === $slug ) {
		return array(
			'type'        => 'legal',
			'hero_bg'     => 'https://images.unsplash.com/photo-1450101499163-c8848c66ca85?w=1920&h=900&fit=crop&crop=center&auto=format&q=82',
			'hero_kicker' => __( 'Legal', 'rhye-child' ),
		);
	}

	if ( in_array( $slug, array( 'terms', 'terms-of-service', 'terms-and-conditions' ), true ) ) {
		return array(
			'type'        => 'legal',
			'hero_bg'     => 'https://images.unsplash.com/photo-1454165804606-c3d57bc86b40?w=1920&h=900&fit=crop&crop=center&auto=format&q=82',
			'hero_kicker' => __( 'Legal', 'rhye-child' ),
		);
	}

	if ( in_array( $slug, array( 'contact', 'contacts-02', 'contact-us' ), true ) ) {
		return array(
			'type'        => 'contact',
			'hero_bg'     => 'https://images.unsplash.com/photo-1423666639041-f56000c27a9a?w=1920&h=900&fit=crop&crop=center&auto=format&q=82',
			'hero_kicker' => __( 'Get in touch', 'rhye-child' ),
		);
	}

	return array(
		'type'        => 'generic',
		'hero_bg'     => 'https://images.unsplash.com/photo-1497366216548-37526070297c?w=1920&h=900&fit=crop&crop=center&auto=format&q=82',
		'hero_kicker' => '',
	);
}

/**
 * URLs for service cards on the landing page.
 *
 * @return array<string, string> keys electrical, plumbing, heating, building
 */
function swiftfix_get_landing_service_urls() {
	$slugs = array(
		'electrical' => array( 'swiftfix-electrical' ),
		'plumbing'   => array( 'swiftfix-plumbing' ),
		'heating'    => array( 'swiftfix-heating-gas' ),
		'building'   => array( 'swiftfix-building-renovation' ),
	);
	$fallback = home_url( '/#services' );
	$out        = array();
	foreach ( $slugs as $key => $candidates ) {
		$out[ $key ] = swiftfix_find_page_url( $candidates, $fallback );
	}

	return $out;
}
