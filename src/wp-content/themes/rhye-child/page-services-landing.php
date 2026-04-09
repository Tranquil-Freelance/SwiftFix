<?php
/**
 * Template Name: Tradesman Services Landing
 * Description: Clean modern landing page for Electrician, Plumber, Heating & Builder.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$sf_name          = get_theme_mod( 'sf_business_name', 'SwiftFix' );
$sf_phone         = get_theme_mod( 'sf_phone', '0800 123 4567' );
$sf_email         = get_theme_mod( 'sf_email', 'hello@swiftfix.co.uk' );
$sf_tagline       = get_theme_mod( 'sf_emergency_tagline', 'Burst pipe? Power cut? No heating?' );
$sf_rating        = get_theme_mod( 'sf_rating', '4.9' );
$sf_review_total  = get_theme_mod( 'sf_review_count', '620' );
$sf_reg           = get_theme_mod( 'sf_reg_info', 'Registered in England & Wales. Gas Safe Reg. No. 123456.' );
$sf_topbar_title  = get_theme_mod( 'sf_topbar_title', '24/7 Emergency Call-Outs' );
$sf_nav_cta       = get_theme_mod( 'sf_nav_cta_label', 'Book Now' );
$sf_hero_badge    = get_theme_mod( 'sf_hero_badge', 'Trusted by 1,400+ UK homeowners' );
$sf_hero_heading  = get_theme_mod( 'sf_hero_heading', 'Your home deserves the best tradespeople.' );
$sf_hero_sub      = get_theme_mod( 'sf_hero_sub', 'Certified electricians, plumbers, heating engineers & builders — all under one roof. No call-out fee, no hidden costs.' );
$sf_hero_alt      = get_theme_mod( 'sf_hero_img_alt', 'Friendly tradesperson working in a modern home' );
$sf_btn_primary   = get_theme_mod( 'sf_hero_btn_primary', 'Get Free Quote' );
$sf_btn_secondary = get_theme_mod( 'sf_hero_btn_secondary', 'Our Services' );
$sf_trust_google  = get_theme_mod( 'sf_trust_google_suffix', 'Google' );
$sf_trust_2       = get_theme_mod( 'sf_trust_chip_2', 'Gas Safe' );
$sf_trust_3       = get_theme_mod( 'sf_trust_chip_3', 'NICEIC Approved' );
$sf_trust_4       = get_theme_mod( 'sf_trust_chip_4', 'Fully Insured' );
$sf_svc_label     = get_theme_mod( 'sf_services_label', 'Services' );
$sf_svc_title     = get_theme_mod( 'sf_services_title', 'What we can help with' );
$sf_svc_sub       = get_theme_mod( 'sf_services_sub', 'From quick fixes to full installations — one team that does it all, properly.' );
$sf_how_label     = get_theme_mod( 'sf_how_label', 'Simple Process' );
$sf_how_title     = get_theme_mod( 'sf_how_title', 'How it works' );
$sf_rating_label  = get_theme_mod( 'sf_rating_label', 'out of 5' );
$sf_reviews_suffix = get_theme_mod( 'sf_reviews_count_label', 'verified reviews' );
$sf_cta_heading   = get_theme_mod( 'sf_cta_heading', 'Ready to get started?' );
$sf_cta_sub       = get_theme_mod( 'sf_cta_sub', 'Free, no-obligation quotes. We cover all of Greater London & surrounding areas.' );
$sf_cta_quote_btn = get_theme_mod( 'sf_cta_quote_btn', 'Request a Free Quote' );
$sf_cta_svc_btn   = get_theme_mod( 'sf_cta_services_btn', 'View Services' );
$sf_privacy       = get_theme_mod( 'sf_privacy_url', '' );
$sf_terms         = get_theme_mod( 'sf_terms_url', '' );
$sf_phone_href    = 'tel:' . preg_replace( '/\s+/', '', $sf_phone );
$parts            = explode( ' ', $sf_name, 2 );
$privacy_url      = $sf_privacy ? $sf_privacy : home_url( '/privacy-policy/' );
$terms_url        = $sf_terms ? $sf_terms : home_url( '/terms/' );

$landing_services = swiftfix_get_landing_services();
$landing_steps    = swiftfix_get_landing_steps();
$landing_reviews  = swiftfix_get_landing_reviews();

$hero_local = get_stylesheet_directory() . '/images/hero-tradesman.jpg';
if ( file_exists( $hero_local ) ) {
	$hero_url = get_stylesheet_directory_uri() . '/images/hero-tradesman.jpg';
} else {
	$hero_url = 'https://images.unsplash.com/photo-1621905251189-08b45d6a269e?w=840&h=880&fit=crop&crop=faces&auto=format&q=80';
}

get_header();
?>

<div class="sf" data-barba-prevent="all">

  <div class="sf-topbar">
    <strong><?php echo esc_html( $sf_topbar_title ); ?></strong> &mdash; <?php echo esc_html( $sf_tagline ); ?>
    <a href="<?php echo esc_url( $sf_phone_href ); ?>"><?php esc_html_e( 'Call', 'rhye-child' ); ?> <?php echo esc_html( $sf_phone ); ?> &rarr;</a>
  </div>

  <nav class="sf-nav" aria-label="<?php esc_attr_e( 'Primary', 'rhye-child' ); ?>">
    <a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="sf-logo">
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

    <?php
	if ( has_nav_menu( 'swiftfix_primary' ) ) {
		wp_nav_menu(
			array(
				'theme_location' => 'swiftfix_primary',
				'container'      => false,
				'fallback_cb'    => false,
				'depth'          => 1,
				'items_wrap'     => '<ul id="sf-nav-links" class="sf-nav__links" role="list">%3$s</ul>',
			)
		);
	} else {
		swiftfix_fallback_primary_nav();
	}
	?>
    <a href="#contact" class="sf-btn sf-btn-amber sf-nav__cta sf-scroll"><?php echo esc_html( $sf_nav_cta ); ?></a>
  </nav>

  <section class="sf-hero" aria-labelledby="sf-hero-heading">
    <div class="sf-hero__text">
      <div class="sf-badge"><?php echo esc_html( $sf_hero_badge ); ?></div>
      <h1 id="sf-hero-heading"><?php echo esc_html( $sf_hero_heading ); ?></h1>
      <p class="sf-hero__sub"><?php echo esc_html( $sf_hero_sub ); ?></p>
      <div class="sf-hero__actions">
        <a href="#contact" class="sf-btn sf-btn-amber sf-btn-lg sf-scroll"><?php echo esc_html( $sf_btn_primary ); ?></a>
        <a href="#services" class="sf-btn sf-btn-outline sf-btn-lg sf-scroll"><?php echo esc_html( $sf_btn_secondary ); ?></a>
      </div>
      <div class="sf-hero__trust">
        <span class="sf-trust-chip"><span class="sf-star" aria-hidden="true">&#9733;</span> <?php echo esc_html( $sf_rating . ' ' . $sf_trust_google ); ?></span>
        <span class="sf-trust-chip"><span class="sf-check" aria-hidden="true">&#10003;</span> <?php echo esc_html( $sf_trust_2 ); ?></span>
        <span class="sf-trust-chip"><span class="sf-check" aria-hidden="true">&#10003;</span> <?php echo esc_html( $sf_trust_3 ); ?></span>
        <span class="sf-trust-chip"><span class="sf-check" aria-hidden="true">&#10003;</span> <?php echo esc_html( $sf_trust_4 ); ?></span>
      </div>
    </div>
    <div class="sf-hero__media">
      <img
        src="<?php echo esc_url( $hero_url ); ?>"
        alt="<?php echo esc_attr( $sf_hero_alt ); ?>"
        class="sf-hero__img"
        loading="lazy"
        width="840"
        height="880"
      />
    </div>
  </section>

  <section class="sf-services" id="services" aria-labelledby="sf-services-title">
    <div class="sf-container">
      <div class="sf-section-header">
        <span class="sf-section-label"><?php echo esc_html( $sf_svc_label ); ?></span>
        <h2 id="sf-services-title" class="sf-section-title"><?php echo esc_html( $sf_svc_title ); ?></h2>
        <p class="sf-section-sub"><?php echo esc_html( $sf_svc_sub ); ?></p>
      </div>

      <div class="sf-services-grid">
        <?php foreach ( $landing_services as $svc ) : ?>
        <div class="sf-service-card">
          <div class="sf-service-icon <?php echo esc_attr( $svc['icon_class'] ); ?>"><?php echo wp_kses_post( $svc['icon'] ); ?></div>
          <h3><?php echo esc_html( $svc['title'] ); ?></h3>
          <p><?php echo esc_html( $svc['body'] ); ?></p>
          <?php
			$link      = isset( $svc['link'] ) ? $svc['link'] : '#contact';
			$link_lbl  = isset( $svc['link_label'] ) ? $svc['link_label'] : __( 'Learn more', 'rhye-child' );
			$link_cls  = 'sf-link';
			if ( isset( $link[0] ) && '#' === $link[0] ) {
				$link_cls .= ' sf-scroll';
			}
			?>
          <a href="<?php echo esc_url( $link ); ?>" class="<?php echo esc_attr( $link_cls ); ?>"><?php echo esc_html( $link_lbl ); ?> &rarr;</a>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </section>

  <section class="sf-how" id="how" aria-labelledby="sf-how-title">
    <div class="sf-container">
      <div class="sf-section-header">
        <span class="sf-section-label"><?php echo esc_html( $sf_how_label ); ?></span>
        <h2 id="sf-how-title" class="sf-section-title"><?php echo esc_html( $sf_how_title ); ?></h2>
      </div>

      <div class="sf-steps">
        <?php
		$step_n = 0;
		foreach ( $landing_steps as $step ) :
			++$step_n;
			?>
        <div class="sf-step">
          <div class="sf-step__number"><?php echo (int) $step_n; ?></div>
          <h4><?php echo esc_html( $step['title'] ); ?></h4>
          <p><?php echo esc_html( $step['text'] ); ?></p>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </section>

  <section class="sf-reviews" id="reviews" aria-label="<?php esc_attr_e( 'Customer reviews', 'rhye-child' ); ?>">
    <div class="sf-container">
      <div class="sf-reviews-layout">
        <div class="sf-rating-big">
          <div class="sf-rating-big__score"><?php echo esc_html( $sf_rating ); ?></div>
          <div class="sf-rating-big__label"><?php echo esc_html( $sf_rating_label ); ?></div>
          <span class="sf-stars" aria-hidden="true">&#9733;&#9733;&#9733;&#9733;&#9733;</span>
          <div class="sf-rating-big__count"><?php
			/* translators: 1: review count, 2: suffix e.g. "verified reviews" */
			printf( esc_html__( 'from %1$s %2$s', 'rhye-child' ), esc_html( $sf_review_total ), esc_html( $sf_reviews_suffix ) );
			?></div>
        </div>

        <div class="sf-reviews-grid">
          <?php foreach ( $landing_reviews as $r ) : ?>
          <div class="sf-review-card">
            <span class="sf-stars" aria-hidden="true">&#9733;&#9733;&#9733;&#9733;&#9733;</span>
            <p>&ldquo;<?php echo esc_html( $r['text'] ); ?>&rdquo;</p>
            <div class="sf-review-card__author"><?php echo esc_html( $r['name'] ); ?></div>
            <div class="sf-review-card__source"><?php
				/* translators: %s: review source name */
				printf( esc_html__( 'via %s', 'rhye-child' ), esc_html( $r['src'] ) );
				?></div>
          </div>
          <?php endforeach; ?>
        </div>

      </div>
    </div>
  </section>

  <section class="sf-cta" id="contact" aria-labelledby="sf-cta-heading">
    <h2 id="sf-cta-heading"><?php echo esc_html( $sf_cta_heading ); ?></h2>
    <p><?php echo esc_html( $sf_cta_sub ); ?></p>
    <div class="sf-cta__actions">
      <a href="mailto:<?php echo esc_attr( $sf_email ); ?>" class="sf-btn sf-btn-amber sf-btn-lg"><?php echo esc_html( $sf_cta_quote_btn ); ?></a>
      <a href="#services" class="sf-btn sf-btn-white sf-btn-lg sf-scroll"><?php echo esc_html( $sf_cta_svc_btn ); ?></a>
    </div>
    <div class="sf-cta__phone">
      <?php esc_html_e( 'Or call us directly:', 'rhye-child' ); ?> <a href="<?php echo esc_url( $sf_phone_href ); ?>"><?php echo esc_html( $sf_phone ); ?></a>
    </div>
  </section>

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
      <li><a href="<?php echo esc_url( $privacy_url ); ?>"><?php esc_html_e( 'Privacy', 'rhye-child' ); ?></a></li>
      <li><a href="<?php echo esc_url( $terms_url ); ?>"><?php esc_html_e( 'Terms', 'rhye-child' ); ?></a></li>
      <li><a href="#contact" class="sf-scroll"><?php esc_html_e( 'Contact', 'rhye-child' ); ?></a></li>
    </ul>
  </footer>

</div>

<?php
get_footer();
