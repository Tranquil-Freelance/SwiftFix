<?php
/**
 * Plugin Name: SwiftFix — Render / setup guidance
 * Description: Admin notice for Elementor, homepage, and demo import. Optional reading bootstrap via env.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * One-time: create Home + Blog pages and set Settings → Reading, when SWIFTFIX_BOOTSTRAP_READING=true.
 * Runs on first wp-admin load by a user who can manage_options.
 */
add_action(
	'admin_init',
	static function () {
		if ( get_option( 'swiftfix_reading_bootstrapped' ) ) {
			return;
		}
		if ( getenv( 'SWIFTFIX_BOOTSTRAP_READING' ) !== 'true' ) {
			return;
		}
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$home_id = (int) get_option( 'swiftfix_bootstrap_home_id' );
		if ( ! $home_id || get_post_status( $home_id ) === false ) {
			$home_id = wp_insert_post(
				array(
					'post_title'   => __( 'Home', 'swiftfix' ),
					'post_name'    => 'home',
					'post_status'  => 'publish',
					'post_type'    => 'page',
					'post_content' => '<!-- wp:paragraph --><p>' . esc_html__( 'Edit this page and build your layout with Elementor (Rhye is built for Elementor).', 'swiftfix' ) . '</p><!-- /wp:paragraph -->',
				),
				true
			);
			if ( is_wp_error( $home_id ) ) {
				return;
			}
			update_option( 'swiftfix_bootstrap_home_id', $home_id );
		}

		$blog_id = (int) get_option( 'swiftfix_bootstrap_blog_id' );
		if ( ! $blog_id || get_post_status( $blog_id ) === false ) {
			$blog_id = wp_insert_post(
				array(
					'post_title'  => __( 'Blog', 'swiftfix' ),
					'post_name'   => 'blog',
					'post_status' => 'publish',
					'post_type'   => 'page',
				),
				true
			);
			if ( is_wp_error( $blog_id ) ) {
				return;
			}
			update_option( 'swiftfix_bootstrap_blog_id', $blog_id );
		}

		update_option( 'show_on_front', 'page' );
		update_option( 'page_on_front', $home_id );
		update_option( 'page_for_posts', $blog_id );
		update_option( 'swiftfix_reading_bootstrapped', true );
	}
);

add_action(
	'admin_notices',
	static function () {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		if ( get_user_meta( get_current_user_id(), 'swiftfix_dismiss_deploy_notice', true ) ) {
			return;
		}

		$stylesheet = (string) get_option( 'stylesheet' );
		$elementor  = defined( 'ELEMENTOR_VERSION' );

		echo '<div class="notice notice-info is-dismissible" data-swiftfix-notice="1"><p><strong>SwiftFix on Render</strong> — ';
		echo esc_html(
			sprintf(
				/* translators: %s: active theme stylesheet slug */
				__( 'Active theme: %s. The ThemeForest “full site” look uses Elementor layouts and (usually) imported demo content — not the default blog list.', 'swiftfix' ),
				$stylesheet ?: '—'
			)
		);
		echo '</p><ul style="list-style:disc;margin-left:1.25em;">';
		echo '<li>' . esc_html__( 'Install Elementor (Plugins → Add New) if it is not installed yet.', 'swiftfix' ) . '</li>';
		echo '<li>' . esc_html__( 'Import demo / run theme setup from your Rhye package (Merlin wizard or XML + Elementor kit), or build the Home page in Elementor.', 'swiftfix' ) . '</li>';
		echo '<li>' . esc_html__( 'Settings → Reading: set “Your homepage displays” to a static page (Home) and a Posts page (Blog) if you still see only the blog index.', 'swiftfix' ) . '</li>';
		echo '<li>' . esc_html__( 'Appearance → Customize → SwiftFix Settings for business name, phone, and colors.', 'swiftfix' ) . '</li>';
		if ( ! $elementor ) {
			echo '<li><strong>' . esc_html__( 'Elementor is not active — Rhye’s designed pages will look plain until you install it.', 'swiftfix' ) . '</strong></li>';
		}
		echo '</ul>';
		echo '<p><button type="button" class="button" id="swiftfix-dismiss-deploy-notice">' . esc_html__( 'Dismiss for my user', 'swiftfix' ) . '</button></p></div>';
	}
);

add_action(
	'admin_footer',
	static function () {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		?>
		<script>
		(function () {
			var btn = document.getElementById('swiftfix-dismiss-deploy-notice');
			if (!btn) return;
			btn.addEventListener('click', function () {
				var fd = new FormData();
				fd.append('action', 'swiftfix_dismiss_deploy_notice');
				fd.append('_ajax_nonce', '<?php echo esc_js( wp_create_nonce( 'swiftfix_dismiss' ) ); ?>');
				fetch(ajaxurl, { method: 'POST', body: fd, credentials: 'same-origin' }).then(function () {
					var n = btn.closest('.notice');
					if (n) n.remove();
				});
			});
		})();
		</script>
		<?php
	}
);

add_action(
	'wp_ajax_swiftfix_dismiss_deploy_notice',
	static function () {
		check_ajax_referer( 'swiftfix_dismiss' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( null, 403 );
		}
		update_user_meta( get_current_user_id(), 'swiftfix_dismiss_deploy_notice', true );
		wp_send_json_success();
	}
);
