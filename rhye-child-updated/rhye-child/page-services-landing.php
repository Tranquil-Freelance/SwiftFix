<?php
/**
 * Template Name: Tradesman Services Landing
 * Description: Clean modern landing page for Electrician, Plumber, Heating & Builder.
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

// --- Get Customizer values ---
$sf_name     = get_theme_mod( 'sf_business_name', 'SwiftFix' );
$sf_phone    = get_theme_mod( 'sf_phone', '0800 123 4567' );
$sf_email    = get_theme_mod( 'sf_email', 'hello@swiftfix.co.uk' );
$sf_tagline  = get_theme_mod( 'sf_emergency_tagline', 'Burst pipe? Power cut? No heating?' );
$sf_rating   = get_theme_mod( 'sf_rating', '4.9' );
$sf_reviews  = get_theme_mod( 'sf_review_count', '620' );
$sf_reg      = get_theme_mod( 'sf_reg_info', 'Registered in England &amp; Wales. Gas Safe Reg. No. 123456.' );
$sf_phone_href = 'tel:' . preg_replace( '/\s+/', '', $sf_phone );

// --- Hero image: use local file if it exists, fallback to Unsplash ---
$hero_local = get_stylesheet_directory() . '/images/hero-tradesman.jpg';
if ( file_exists( $hero_local ) ) {
	$hero_url = get_stylesheet_directory_uri() . '/images/hero-tradesman.jpg';
} else {
	$hero_url = 'https://images.unsplash.com/photo-1621905251189-08b45d6a269e?w=840&h=880&fit=crop&crop=faces&auto=format&q=80';
}

get_header();
?>

<div class="sf">

  <!-- ====== TOP BAR ====== -->
  <div class="sf-topbar">
    <strong>24/7 Emergency Call-Outs</strong> &mdash; <?php echo esc_html( $sf_tagline ); ?>
    <a href="<?php echo esc_attr( $sf_phone_href ); ?>">Call <?php echo esc_html( $sf_phone ); ?> &rarr;</a>
  </div>

  <!-- ====== NAVBAR ====== -->
  <nav class="sf-nav">
    <a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="sf-logo">
      <?php
        // Split business name: first word normal, second word highlighted
        $parts = explode( ' ', $sf_name, 2 );
        if ( isset( $parts[1] ) ) {
          echo esc_html( $parts[0] ) . '<span>' . esc_html( $parts[1] ) . '</span>';
        } else {
          // Single word — highlight last 3 chars
          $len = strlen( $sf_name );
          if ( $len > 3 ) {
            echo esc_html( substr( $sf_name, 0, $len - 3 ) ) . '<span>' . esc_html( substr( $sf_name, -3 ) ) . '</span>';
          } else {
            echo esc_html( $sf_name );
          }
        }
      ?>
    </a>

    <!-- Mobile hamburger toggle -->
    <button class="sf-menu-toggle" aria-label="Toggle navigation" aria-expanded="false">
      <span></span><span></span><span></span>
    </button>

    <ul class="sf-nav__links" id="sf-nav-links">
      <li><a href="#services">Services</a></li>
      <li><a href="#how">How It Works</a></li>
      <li><a href="#reviews">Reviews</a></li>
      <li><a href="#contact">Contact</a></li>
    </ul>
    <a href="#contact" class="sf-btn sf-btn-amber sf-nav__cta">Book Now</a>
  </nav>

  <!-- ====== HERO ====== -->
  <section class="sf-hero">
    <div class="sf-hero__text">
      <div class="sf-badge">Trusted by 1,400+ UK homeowners</div>
      <h1>Your home deserves the best tradespeople.</h1>
      <p class="sf-hero__sub">
        Certified electricians, plumbers, heating engineers &amp; builders
        — all under one roof. No call-out fee, no hidden costs.
      </p>
      <div class="sf-hero__actions">
        <a href="#contact" class="sf-btn sf-btn-amber sf-btn-lg">Get Free Quote</a>
        <a href="#services" class="sf-btn sf-btn-outline sf-btn-lg">Our Services</a>
      </div>
      <div class="sf-hero__trust">
        <span class="sf-trust-chip"><span class="sf-star">&#9733;</span> <?php echo esc_html( $sf_rating ); ?> Google</span>
        <span class="sf-trust-chip"><span class="sf-check">&#10003;</span> Gas Safe</span>
        <span class="sf-trust-chip"><span class="sf-check">&#10003;</span> NICEIC Approved</span>
        <span class="sf-trust-chip"><span class="sf-check">&#10003;</span> Fully Insured</span>
      </div>
    </div>
    <div class="sf-hero__media">
      <img
        src="<?php echo esc_url( $hero_url ); ?>"
        alt="Friendly tradesperson working in a modern home"
        class="sf-hero__img"
        loading="lazy"
      />
    </div>
  </section>

  <!-- ====== SERVICES ====== -->
  <section class="sf-services" id="services">
    <div class="sf-container">
      <div class="sf-section-header">
        <span class="sf-section-label">Services</span>
        <h2 class="sf-section-title">What we can help with</h2>
        <p class="sf-section-sub">
          From quick fixes to full installations &mdash; one team that does it all, properly.
        </p>
      </div>

      <div class="sf-services-grid">

        <div class="sf-service-card">
          <div class="sf-service-icon sf-service-icon--blue">&#9889;</div>
          <h3>Electrical</h3>
          <p>Fuse boards, rewiring, fault finding, EV chargers, outdoor lighting and full new-build wiring.</p>
          <a href="#" class="sf-link">Learn more &rarr;</a>
        </div>

        <div class="sf-service-card">
          <div class="sf-service-icon sf-service-icon--cyan">&#128167;</div>
          <h3>Plumbing</h3>
          <p>Burst pipes, leaks, blocked drains, bathroom fitting, tap replacement and full renovations.</p>
          <a href="#" class="sf-link">Learn more &rarr;</a>
        </div>

        <div class="sf-service-card">
          <div class="sf-service-icon sf-service-icon--orange">&#128293;</div>
          <h3>Heating &amp; Gas</h3>
          <p>Boiler servicing, breakdowns, radiator installs, central heating systems and smart thermostats.</p>
          <a href="#" class="sf-link">Learn more &rarr;</a>
        </div>

        <div class="sf-service-card">
          <div class="sf-service-icon sf-service-icon--green">&#127968;</div>
          <h3>Building &amp; Renovation</h3>
          <p>Extensions, loft conversions, kitchen fitting, plastering, tiling and property maintenance.</p>
          <a href="#" class="sf-link">Learn more &rarr;</a>
        </div>

      </div>
    </div>
  </section>

  <!-- ====== HOW IT WORKS ====== -->
  <section class="sf-how" id="how">
    <div class="sf-container">
      <div class="sf-section-header">
        <span class="sf-section-label">Simple Process</span>
        <h2 class="sf-section-title">How it works</h2>
      </div>

      <div class="sf-steps">
        <div class="sf-step">
          <div class="sf-step__number">1</div>
          <h4>Tell us what you need</h4>
          <p>Describe your job online or call us directly. No jargon needed &mdash; just plain English.</p>
        </div>
        <div class="sf-step">
          <div class="sf-step__number">2</div>
          <h4>We send the right expert</h4>
          <p>A certified, DBS-checked engineer arrives at a time that works for you.</p>
        </div>
        <div class="sf-step">
          <div class="sf-step__number">3</div>
          <h4>Job done, guaranteed</h4>
          <p>Fixed-price work, no surprises. Every job is backed by our 12-month guarantee.</p>
        </div>
      </div>
    </div>
  </section>

  <!-- ====== REVIEWS ====== -->
  <section class="sf-reviews" id="reviews">
    <div class="sf-container">
      <div class="sf-reviews-layout">

        <div class="sf-rating-big">
          <div class="sf-rating-big__score"><?php echo esc_html( $sf_rating ); ?></div>
          <div class="sf-rating-big__label">out of 5</div>
          <span class="sf-stars">&#9733;&#9733;&#9733;&#9733;&#9733;</span>
          <div class="sf-rating-big__count">from <?php echo esc_html( $sf_reviews ); ?> verified reviews</div>
        </div>

        <div class="sf-reviews-grid">
          <?php
          $reviews = [
            [ 'name' => 'Jonah B.',     'src' => 'Google',     'text' => 'The electrician arrived within 2 hours and sorted our power issue quickly. Polite, clean, and professional. Will use again.' ],
            [ 'name' => 'Sarah C.',     'src' => 'Trustpilot', 'text' => 'Fixed our boiler the same day. Left no mess, explained everything clearly. Best tradesman experience I\'ve had.' ],
            [ 'name' => 'Mohammed R.',  'src' => 'Google',     'text' => 'Brilliant builders — the kitchen extension is exactly what we wanted. On time, on budget. Highly recommend.' ],
            [ 'name' => 'Emily T.',     'src' => 'Trustpilot', 'text' => 'Had a burst pipe at midnight. They answered immediately and had someone here within 45 minutes. Lifesavers!' ],
          ];
          foreach ( $reviews as $r ) : ?>
          <div class="sf-review-card">
            <span class="sf-stars">&#9733;&#9733;&#9733;&#9733;&#9733;</span>
            <p>&ldquo;<?php echo esc_html( $r['text'] ); ?>&rdquo;</p>
            <div class="sf-review-card__author"><?php echo esc_html( $r['name'] ); ?></div>
            <div class="sf-review-card__source">via <?php echo esc_html( $r['src'] ); ?></div>
          </div>
          <?php endforeach; ?>
        </div>

      </div>
    </div>
  </section>

  <!-- ====== CTA ====== -->
  <section class="sf-cta" id="contact">
    <h2>Ready to get started?</h2>
    <p>Free, no-obligation quotes. We cover all of Greater London &amp; surrounding areas.</p>
    <div class="sf-cta__actions">
      <a href="mailto:<?php echo esc_attr( $sf_email ); ?>" class="sf-btn sf-btn-amber sf-btn-lg">Request a Free Quote</a>
      <a href="#services" class="sf-btn sf-btn-white sf-btn-lg">View Services</a>
    </div>
    <div class="sf-cta__phone">
      Or call us directly: <a href="<?php echo esc_attr( $sf_phone_href ); ?>"><?php echo esc_html( $sf_phone ); ?></a>
    </div>
  </section>

  <!-- ====== FOOTER ====== -->
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
    <p class="sf-footer__copy">&copy; <?php echo date( 'Y' ); ?> <?php echo esc_html( $sf_name ); ?> Ltd. <?php echo esc_html( $sf_reg ); ?></p>
    <ul class="sf-footer__links">
      <li><a href="#">Privacy</a></li>
      <li><a href="#">Terms</a></li>
      <li><a href="#contact">Contact</a></li>
    </ul>
  </footer>

</div><!-- /.sf -->

<!-- Mobile menu toggle script -->
<script>
(function() {
  var toggle = document.querySelector('.sf-menu-toggle');
  var nav    = document.getElementById('sf-nav-links');
  if ( ! toggle || ! nav ) return;
  toggle.addEventListener('click', function() {
    var expanded = this.getAttribute('aria-expanded') === 'true';
    this.setAttribute('aria-expanded', String(!expanded));
    this.classList.toggle('sf-menu-toggle--active');
    nav.classList.toggle('sf-nav__links--open');
  });
  // Close menu when a link is clicked
  nav.querySelectorAll('a').forEach(function(link) {
    link.addEventListener('click', function() {
      toggle.setAttribute('aria-expanded', 'false');
      toggle.classList.remove('sf-menu-toggle--active');
      nav.classList.remove('sf-nav__links--open');
    });
  });
})();
</script>

<?php get_footer(); ?>
