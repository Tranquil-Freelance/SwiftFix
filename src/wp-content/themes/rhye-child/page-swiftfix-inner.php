<?php
/**
 * Template Name: CAE Fix — Branded inner page
 * Description: Services, Privacy, Terms, and Contact with the CAE Fix shell and hero imagery.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

if ( ! have_posts() ) {
	get_footer();
	return;
}

the_post();

$slug = get_post_field( 'post_name', get_the_ID() );
$kind = swiftfix_detect_inner_page_kind( $slug );

$hero_bg     = isset( $kind['hero_bg'] ) ? $kind['hero_bg'] : '';
$hero_kicker = isset( $kind['hero_kicker'] ) ? $kind['hero_kicker'] : '';
$hero_title  = get_the_title();
$hero_intro  = '';
$preset      = null;

if ( 'service' === $kind['type'] && ! empty( $kind['key'] ) ) {
	$presets = swiftfix_get_service_presets();
	if ( isset( $presets[ $kind['key'] ] ) ) {
		$preset      = $presets[ $kind['key'] ];
		$hero_bg     = $preset['hero_bg'];
		$hero_title  = $preset['title'];
		$hero_intro  = $preset['intro'];
		$hero_kicker = __( 'Services', 'rhye-child' );
	}
} elseif ( 'contact' === $kind['type'] ) {
	$hero_intro = __( 'Call, email, or send a message — we respond quickly and book visits at times that suit you.', 'rhye-child' );
} elseif ( 'legal' === $kind['type'] ) {
	$hero_intro = __( 'Clear information about how we work with you and handle your data.', 'rhye-child' );
} elseif ( 'generic' === $kind['type'] ) {
	$hero_intro = get_the_excerpt();
	if ( $hero_intro === '' ) {
		$hero_intro = __( 'CAE Fix multi-trade home services across your area.', 'rhye-child' );
	}
}

$g             = swiftfix_get_shell_globals();
$sf_email      = $g['sf_email'];
$sf_phone      = $g['sf_phone'];
$sf_phone_href = $g['sf_phone_href'];
$url_home      = $g['url_home'];
$url_contact   = $g['url_contact'];
$mailto        = 'mailto:' . $sf_email;
$contact_cls   = $g['contact_scroll_class'];
?>

<div class="sf sf--inner" data-barba-prevent="all">

	<?php get_template_part( 'template-parts/swiftfix/shell', 'header' ); ?>

	<section class="sf-inner-hero" style="<?php echo $hero_bg ? 'background-image: url(' . esc_url( $hero_bg ) . ');' : ''; ?>">
		<div class="sf-inner-hero__inner">
			<?php if ( 'service' === $kind['type'] && $preset ) : ?>
				<div class="sf-inner-hero__icon" aria-hidden="true"><?php echo wp_kses_post( $preset['icon_char'] ); ?></div>
			<?php endif; ?>
			<?php if ( $hero_kicker ) : ?>
				<p class="sf-inner-hero__kicker"><?php echo esc_html( $hero_kicker ); ?></p>
			<?php endif; ?>
			<h1><?php echo esc_html( $hero_title ); ?></h1>
			<?php if ( $hero_intro ) : ?>
				<p class="sf-inner-hero__intro"><?php echo esc_html( $hero_intro ); ?></p>
			<?php endif; ?>
		</div>
	</section>

	<div class="sf-inner-body">
		<div class="sf-inner-wrap">

			<?php if ( 'service' === $kind['type'] && $preset ) : ?>
				<div class="sf-inner-features" role="list">
					<?php foreach ( $preset['features'] as $line ) : ?>
						<div class="sf-inner-feature" role="listitem">
							<p><?php echo esc_html( $line ); ?></p>
						</div>
					<?php endforeach; ?>
				</div>
				<div class="sf-inner-note"><?php echo esc_html( $preset['note'] ); ?></div>
				<div class="sf-inner-actions">
					<a href="<?php echo esc_url( $url_contact ); ?>" class="sf-btn sf-btn-amber sf-btn-lg<?php echo $contact_cls !== '' ? ' ' . esc_attr( $contact_cls ) : ''; ?>"><?php esc_html_e( 'Request a quote', 'rhye-child' ); ?></a>
					<a href="<?php echo esc_url( $url_home ); ?>#services" class="sf-btn sf-btn-outline sf-btn-lg"><?php esc_html_e( 'All services', 'rhye-child' ); ?></a>
				</div>
				<?php
				$raw_extra = get_post()->post_content;
				if ( trim( wp_strip_all_tags( (string) $raw_extra ) ) !== '' ) :
					?>
					<div class="sf-prose" style="margin-top: 48px;">
						<?php the_content(); ?>
					</div>
				<?php endif; ?>

			<?php elseif ( 'contact' === $kind['type'] ) : ?>
				<div class="sf-contact-cards">
					<div class="sf-contact-card">
						<div class="sf-contact-card__label"><?php esc_html_e( 'Phone', 'rhye-child' ); ?></div>
						<a href="<?php echo esc_url( $sf_phone_href ); ?>"><?php echo esc_html( $sf_phone ); ?></a>
						<p><?php esc_html_e( 'Emergencies and same-day call-outs where available.', 'rhye-child' ); ?></p>
					</div>
					<div class="sf-contact-card">
						<div class="sf-contact-card__label"><?php esc_html_e( 'Email', 'rhye-child' ); ?></div>
						<a href="<?php echo esc_url( $mailto ); ?>"><?php echo esc_html( $sf_email ); ?></a>
						<p><?php esc_html_e( 'Send photos and a short description for faster quotes.', 'rhye-child' ); ?></p>
					</div>
					<div class="sf-contact-card">
						<div class="sf-contact-card__label"><?php esc_html_e( 'Hours', 'rhye-child' ); ?></div>
						<strong><?php esc_html_e( 'Mon–Sat', 'rhye-child' ); ?></strong>
						<p><?php esc_html_e( '8:00–18:00 · 24/7 emergency line for urgent issues', 'rhye-child' ); ?></p>
					</div>
				</div>
				<div class="sf-contact-form-wrap">
					<?php
					$form_id = function_exists( 'swiftfix_get_cf7_quote_form_id' ) ? swiftfix_get_cf7_quote_form_id() : 0;
					if ( $form_id ) {
						echo do_shortcode( sprintf( '[contact-form-7 id="%d" html_class="swiftfix-cf7-form"]', absint( $form_id ) ) );
					} else {
						echo '<p class="sf-contact-cf7-missing">';
						echo esc_html__( 'Install and activate Contact Form 7 to use the form below. You can still reach us by phone or email.', 'rhye-child' );
						echo '</p>';
					}
					?>
				</div>
				<?php
				$is_elementor = get_post_meta( get_the_ID(), '_elementor_edit_mode', true ) === 'builder';
				$raw_contact  = (string) get_post()->post_content;
				$without_cf7  = preg_replace( '/\s*\[contact-form-7[^\]]*\]\s*/i', '', $raw_contact );
				if ( ! $is_elementor && trim( wp_strip_all_tags( $without_cf7 ) ) !== '' ) :
					?>
					<div class="sf-prose sf-contact-extra" style="margin-top: 28px;">
						<?php echo apply_filters( 'the_content', $without_cf7 ); ?>
					</div>
				<?php endif; ?>
				<div class="sf-inner-actions" style="margin-top: 32px; justify-content: center;">
					<a href="<?php echo esc_url( $mailto ); ?>?subject=<?php echo rawurlencode( __( 'Quote request', 'rhye-child' ) ); ?>" class="sf-btn sf-btn-amber sf-btn-lg"><?php esc_html_e( 'Email us', 'rhye-child' ); ?></a>
					<a href="<?php echo esc_url( $url_home ); ?>#services" class="sf-btn sf-btn-outline sf-btn-lg"><?php esc_html_e( 'Back to services', 'rhye-child' ); ?></a>
				</div>

			<?php else : ?>
				<div class="sf-prose">
					<?php the_content(); ?>
				</div>
				<div class="sf-inner-actions" style="margin-top: 40px; justify-content: center;">
					<a href="<?php echo esc_url( $url_home ); ?>" class="sf-btn sf-btn-outline sf-btn-lg"><?php esc_html_e( 'Back to home', 'rhye-child' ); ?></a>
					<a href="<?php echo esc_url( $url_contact ); ?>" class="sf-btn sf-btn-amber sf-btn-lg<?php echo $contact_cls !== '' ? ' ' . esc_attr( $contact_cls ) : ''; ?>"><?php esc_html_e( 'Contact us', 'rhye-child' ); ?></a>
				</div>
			<?php endif; ?>

		</div>
	</div>

	<section class="sf-cta">
		<h2><?php esc_html_e( 'Ready when you are', 'rhye-child' ); ?></h2>
		<p><?php esc_html_e( 'Free, no-obligation quotes. Tell us what you need and we will match you with the right trade.', 'rhye-child' ); ?></p>
		<div class="sf-cta__actions">
			<a href="<?php echo esc_url( $mailto ); ?>" class="sf-btn sf-btn-amber sf-btn-lg"><?php esc_html_e( 'Request a Free Quote', 'rhye-child' ); ?></a>
			<a href="<?php echo esc_url( $url_home ); ?>#services" class="sf-btn sf-btn-white sf-btn-lg"><?php esc_html_e( 'View Services', 'rhye-child' ); ?></a>
		</div>
		<div class="sf-cta__phone">
			<?php esc_html_e( 'Or call:', 'rhye-child' ); ?> <a href="<?php echo esc_url( $sf_phone_href ); ?>"><?php echo esc_html( $sf_phone ); ?></a>
		</div>
	</section>

	<?php get_template_part( 'template-parts/swiftfix/shell', 'footer' ); ?>

</div>

<script>
(function() {
  var toggle = document.querySelector('.sf-menu-toggle');
  var nav    = document.getElementById('sf-nav-links');
  if (toggle && nav) {
    toggle.addEventListener('click', function() {
      var expanded = this.getAttribute('aria-expanded') === 'true';
      this.setAttribute('aria-expanded', String(!expanded));
      this.classList.toggle('sf-menu-toggle--active');
      nav.classList.toggle('sf-nav__links--open');
    });
    nav.querySelectorAll('a').forEach(function(link) {
      link.addEventListener('click', function() {
        toggle.setAttribute('aria-expanded', 'false');
        toggle.classList.remove('sf-menu-toggle--active');
        nav.classList.remove('sf-nav__links--open');
      });
    });
  }
  document.querySelectorAll('.sf a').forEach(function(link) {
    link.addEventListener('click', function(e) {
      var href = this.getAttribute('href') || '';
      if (href.startsWith('#') || href.startsWith('tel:') || href.startsWith('mailto:')) {
        e.stopPropagation();
      }
    });
  });
})();
</script>

<?php
get_footer();
