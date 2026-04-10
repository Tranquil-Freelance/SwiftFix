<?php
/**
 * Template Name: Tradesman Services Landing
 * Description: Clean modern landing page for Electrician, Plumber, Heating & Builder.
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

get_header();
?>

<div class="sf">

  <!-- ====== TOP BAR ====== -->
  <div class="sf-topbar">
    <strong>24/7 Emergency Call-Outs</strong> &mdash; Burst pipe? Power cut? No heating?
    <a href="tel:08001234567">Call 0800 123 4567 &rarr;</a>
  </div>

  <!-- ====== NAVBAR ====== -->
  <nav class="sf-nav">
    <a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="sf-logo">
      Swift<span>Fix</span>
    </a>
    <ul class="sf-nav__links">
      <li><a href="#services">Services</a></li>
      <li><a href="#how">How It Works</a></li>
      <li><a href="#reviews">Reviews</a></li>
      <li><a href="#contact">Contact</a></li>
    </ul>
    <a href="#contact" class="sf-btn sf-btn-amber">Book Now</a>
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
        <span class="sf-trust-chip"><span class="sf-star">&#9733;</span> 4.9 Google</span>
        <span class="sf-trust-chip"><span class="sf-check">&#10003;</span> Gas Safe</span>
        <span class="sf-trust-chip"><span class="sf-check">&#10003;</span> NICEIC Approved</span>
        <span class="sf-trust-chip"><span class="sf-check">&#10003;</span> Fully Insured</span>
      </div>
    </div>
    <div class="sf-hero__media">
      <img
        src="https://images.unsplash.com/photo-1621905251189-08b45d6a269e?w=840&h=880&fit=crop&crop=faces&auto=format&q=80"
        alt="Friendly tradesperson working in a modern home"
        class="sf-hero__img"
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
          <div class="sf-rating-big__score">4.9</div>
          <div class="sf-rating-big__label">out of 5</div>
          <span class="sf-stars">&#9733;&#9733;&#9733;&#9733;&#9733;</span>
          <div class="sf-rating-big__count">from 620 verified reviews</div>
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
      <a href="mailto:hello@caefix.co.uk" class="sf-btn sf-btn-amber sf-btn-lg">Request a Free Quote</a>
      <a href="#services" class="sf-btn sf-btn-white sf-btn-lg">View Services</a>
    </div>
    <div class="sf-cta__phone">
      Or call us directly: <a href="tel:08001234567">0800 123 4567</a>
    </div>
  </section>

  <!-- ====== FOOTER ====== -->
  <footer class="sf-footer">
    <div class="sf-footer__logo">Swift<span>Fix</span></div>
    <p class="sf-footer__copy">&copy; <?php echo date( 'Y' ); ?> CAE Fix Ltd. Registered in England &amp; Wales. Gas Safe Reg. No. 123456.</p>
    <ul class="sf-footer__links">
      <li><a href="#">Privacy</a></li>
      <li><a href="#">Terms</a></li>
      <li><a href="#contact">Contact</a></li>
    </ul>
  </footer>

</div><!-- /.sf -->

<?php get_footer(); ?>
