<?php

namespace Arts\LicenseManager\Managers;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Class Frontend
 *
 * Handles the frontend interface for license management in the WordPress admin.
 *
 * @package Arts\LicenseManager\Managers
 * @since 1.0.0
 */
class Frontend extends BaseManager {
	/**
	 * The theme slug.
	 *
	 * @var string
	 */
	private $theme_slug;

	/**
	 * Date format for displaying dates.
	 *
	 * @var string
	 */
	private $date_format;

	/**
	 * URL to the directory containing assets.
	 *
	 * @var string
	 */
	private $dir_url;

	/**
	 * Version number for assets.
	 *
	 * @var string
	 */
	private $version;

	/**
	 * Initialize the Frontend manager.
	 *
	 * @param \stdClass $managers Other managers used by this manager.
	 * @return void
	 */
	public function init( $managers ) {
		$this->theme_slug  = $this->args['theme_slug'];
		$this->date_format = $this->args['date_format'];
		$this->dir_url     = $this->args['dir_url'];
		$this->version     = $this->args['version'];

		$this->add_managers( $managers );
	}

	/**
	 * Enqueues JavaScript files for the license manager.
	 *
	 * @return void
	 */
	public function enqueue_scripts() {
		wp_enqueue_script(
			'arts-license-manager',
			esc_url( untrailingslashit( $this->dir_url ) . '/libraries/arts-license-manager/index.umd.js' ),
			array(),
			$this->version,
			true
		);
	}

	/**
	 * Enqueues CSS files for the license manager.
	 *
	 * @return void
	 */
	public function enqueue_styles() {
		wp_enqueue_style(
			'arts-license-manager',
			esc_url( untrailingslashit( $this->dir_url ) . '/libraries/arts-license-manager/index.css' ),
			array(),
			$this->version
		);
	}

	/**
	 * Adds the theme license page to the WordPress admin menu.
	 *
	 * @return void
	 */
	public function add_theme_license_menu() {
		add_theme_page(
			$this->strings['theme-license'],
			$this->strings['theme-license'],
			'manage_options',
			$this->theme_slug . '-license',
			array( $this, 'add_theme_license_page' )
		);
	}

	/**
	 * Renders the theme license page content.
	 *
	 * @return void
	 */
	public function add_theme_license_page() {
		$strings = $this->strings;

		$license          = trim( $this->managers->options->get( 'license_key' ) );
		$status           = $this->managers->options->get( 'license_key_status', false );
		$is_valid_license = $license && $status === 'valid';
		?>
<div class="wrap">
	<h1 class="wp-heading-inline"><?php echo esc_html( $strings['theme-license'] ); ?></h1>
	<hr class="wp-header-end">
	<form method="post" action="options.php" data-action-ajax="<?php echo admin_url( 'admin-ajax.php' ); ?>"
		id="arts-license-form">
		<?php settings_fields( $this->theme_slug . '-license' ); ?>
		<table class="form-table">
			<tbody>
				<?php $this->render_license_row( $license, $is_valid_license ); ?>
				<?php if ( $is_valid_license ) : ?>
					<?php $this->render_expiration_row(); ?>
					<?php $this->render_activation_row(); ?>
					<?php $this->render_purchase_date_row(); ?>
					<?php $this->render_updates_row(); ?>
					<?php $this->render_support_row(); ?>
				<?php endif; ?>
			</tbody>
		</table>
		<?php if ( ! $license ) : ?>
			<?php $this->render_license_cta(); ?>
		<?php endif; ?>
	</form>
</div>
		<?php
	}

	/**
	 * Registers the license key setting.
	 *
	 * @return void
	 */
	public function register_setting() {
		register_setting(
			$this->theme_slug . '-license',
			$this->theme_slug . '_license_key',
			array( $this, 'sanitize_license' )
		);
	}

	/**
	 * Sanitizes the license key and handles status changes.
	 *
	 * @param string $new The new license key value.
	 * @return string The sanitized license key.
	 */
	public function sanitize_license( $new ) {
		$old = $this->managers->options->get( 'license_key' );

		if ( $old && $old !== $new ) {
			// New license has been entered, so must reactivate
			$this->managers->options->delete( 'license_key_status' );
		}

		return $new;
	}

	/**
	 * Renders the license call-to-action section.
	 *
	 * @return void
	 */
	private function render_license_cta() {
		$strings = $this->strings;

		?>
<div class="card">
	<h2><?php echo esc_html( $strings['license-help-no-purchase-code-heading'] ); ?></h2>
	<p>
		<?php printf( $strings['license-help-no-purchase-code-text'], wp_kses_post( '<a href="' . esc_url( $strings['item-checkout-url'] ) . '" target="_blank" rel="nofollow">' . esc_html( $strings['license-help-no-purchase-code-link'] ) . '</a>' ) ); ?>
		<?php echo esc_html( $strings['license-help-no-purchase-code-benefits-before'] ); ?></p>
		<?php if ( is_array( $strings['license-help-no-purchase-code-benefits'] ) && ! empty( $strings['license-help-no-purchase-code-benefits'] ) ) : ?>
	<ul class="ul-disc">
			<?php foreach ( $strings['license-help-no-purchase-code-benefits'] as $benefit ) : ?>
		<li><?php echo esc_html( $benefit ); ?></li>
		<?php endforeach; ?>
	</ul>
	<?php endif; ?>
	<p>
		<a class="button button-primary button-hero" href="<?php echo esc_url( $strings['item-checkout-url'] ); ?>"
			target="_blank"><?php echo esc_html( $strings['item-checkout-link'] ); ?></a>
		<a class="button button-secondary button-hero" href="<?php echo esc_url( $strings['item-page-url'] ); ?>"
			target="_blank"><?php echo esc_html( $strings['item-page-link'] ); ?></a>
	</p>
</div>
		<?php
	}

	/**
	 * Renders the license key input row.
	 *
	 * @param string $license The current license key.
	 * @param bool   $is_valid_license Whether the license is valid.
	 * @return void
	 */
	private function render_license_row( $license, $is_valid_license ) {
		$strings = $this->strings;

		// Checks license status to display under license key
		if ( ! $license ) {
			$message = $strings['enter-key'];
		} else {
			$message = $strings['license-key-activated'];
		}

		$clear_button_class = $license ? 'button arts-license-ajax-button license-input-wrapper__button' : 'button arts-license-ajax-button license-input-wrapper__button license-hidden';

		?>
<tr valign="top">
	<th class="row-title"><?php echo esc_html( $strings['license-key'] ); ?></th>
	<td>
		<?php if ( $is_valid_license ) : ?>
		<div class="card" style="margin-top: 0;">
			<?php endif; ?>
			<?php if ( $is_valid_license ) : ?>
			<h2 class="title"><?php echo esc_html( $license ); ?></h2>
			<p class="description license-color-active"><?php echo esc_html( $message ); ?></p>
			<br>
			<?php else : ?>
			<div class="license-input-wrapper">
				<input id="<?php echo esc_attr( $this->theme_slug . '_license_key' ); ?>"
					name="<?php echo esc_attr( $this->theme_slug . '_license_key' ); ?>" type="text"
					class="regular-text license-input" value="<?php echo esc_attr( $license ); ?>" autocomplete="off"
					autocorrect="off" autocapitalize="off" spellcheck="false" maxlength="36"
					placeholder="xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx"
					pattern="[0-9a-zA-Z]{8}-[0-9a-zA-Z]{4}-[0-9a-zA-F]{4}-[0-9a-zA-Z]{4}-[0-9a-zA-Z]{12}" required
					title="Purchase code format: xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx" />
				<button id="clear-license-button" class="<?php echo esc_attr( $clear_button_class ); ?>" type="button"
					name="<?php echo esc_attr( $this->theme_slug . '_license_clear' ); ?>" data-ajax-action="clear_license"
					aria-label="<?php echo esc_attr( $strings['clear-license'] ); ?>">
					<span class="arts-license-ajax-button__icon dashicons dashicons-trash"></span>
				</button>
			</div>
			<p class="description">
				<a href="<?php echo esc_html( $strings['license-help-purchase-code-url'] ); ?>"
					target="_blank"><?php echo esc_html( $strings['license-help-purchase-code'] ); ?></a>
			</p>
			<br>
			<?php endif; ?>
			<?php if ( $license ) : ?>
				<?php if ( $is_valid_license ) : ?>
			<input type="submit" class="button button-primary button-large"
				name="<?php echo esc_attr( $this->theme_slug . '_license_deactivate' ); ?>"
				value="<?php echo esc_attr( $strings['deactivate-license'] ); ?>" />
			<button id="refresh-license-button" class="button button-secondary arts-license-ajax-button right" type="submit"
				name="<?php echo esc_attr( $this->theme_slug . '_license_refresh' ); ?>"
				value="<?php echo esc_attr( $strings['refresh-license'] ); ?>" data-ajax-action="refresh_license">
				<span class="arts-license-ajax-button__icon-animated dashicons dashicons-update"></span>
				<span class="arts-license-ajax-button__label"><?php echo esc_html( $strings['refresh-license'] ); ?></span>
			</button>
			<?php else : ?>
			<input type="submit" class="button button-primary button-large"
				name="<?php echo esc_attr( $this->theme_slug . '_license_activate' ); ?>"
				value="<?php echo esc_attr( $strings['activate-license'] ); ?>" />
			<?php endif; ?>
			<?php else : ?>
			<input type="submit" class="button button-primary button-large"
				name="<?php echo esc_attr( $this->theme_slug . '_license_activate' ); ?>"
				value="<?php echo esc_attr( $strings['activate-license'] ); ?>" />
			<?php endif; ?>
			<?php if ( $is_valid_license ) : ?>
		</div>
		<?php endif; ?>
	</td>
</tr>
		<?php
	}

	/**
	 * Renders the license expiration row.
	 *
	 * @return void
	 */
	private function render_expiration_row() {
		$license_expiration_date = $this->get_expiration_date_string();
		?>
<tr valign="top">
	<th class="row-title"><?php echo esc_html( $this->strings['license-expiration-date'] ); ?></th>
	<td><span id="license-expires" class="license-info"><?php echo esc_html( $license_expiration_date ); ?></span></td>
</tr>
		<?php
	}

	/**
	 * Renders the license activation information row.
	 *
	 * @return void
	 */
	private function render_activation_row() {
		$activated_sites   = $this->managers->options->get( 'license_site_count' );
		$activation_limit  = $this->managers->options->get( 'license_limit' );
		$is_local          = $this->managers->options->get( 'license_is_local' );
		$description_class = $is_local ? 'description license-info' : 'description license-hidden license-info';
		?>
<tr valign="top">
	<th class="row-title"><?php echo esc_html( $this->strings['license-activations'] ); ?></th>
	<td>
		<strong><span id="license-site-count"
				class="license-info"><?php echo esc_html( $activated_sites ); ?></span>&nbsp;<span
				id="license-site-count-limit-delimiter" class="license-info">/</span>&nbsp;<span id="license-limit"
				class="license-info"><?php echo esc_html( $activation_limit ); ?></span></strong>
		<p id="license-site-count-description" class="<?php echo esc_attr( $description_class ); ?>">
			<em><?php echo esc_html( $this->strings['license-local-info'] ); ?></em>
		</p>
	</td>
</tr>
		<?php
	}

	/**
	 * Renders the purchase date row.
	 *
	 * @return void
	 */
	private function render_purchase_date_row() {
		$purchase_date = $this->get_purchase_date_string();
		?>
<tr valign="top">
	<th class="row-title"><?php echo esc_html( $this->strings['license-purchase-date'] ); ?></th>
	<td><span id="license-date-purchased" class="license-info"><?php echo esc_html( $purchase_date ); ?></span></td>
</tr>
		<?php
	}

	/**
	 * Renders the updates provided until row.
	 *
	 * @return void
	 */
	private function render_updates_row() {
		$updates_provided_until_date = $this->get_date_updates_provided_until_string();
		?>
<tr valign="top">
	<th class="row-title"><?php echo esc_html( $this->strings['license-updates-provided-until'] ); ?></th>
	<td><span id="license-date-updates-provided-until"
			class="license-info"><?php echo esc_html( $updates_provided_until_date ); ?></span></td>
</tr>
		<?php
	}

	/**
	 * Renders the support row.
	 *
	 * @return void
	 */
	private function render_support_row() {
		$is_support_provided             = $this->managers->license->is_support_provided();
		$date_supported_full_string      = $this->get_date_supported_full_string();
		$date_supported_until_class      = $is_support_provided ? 'license-info license-color-active' : 'license-info license-color-expired';
		$description_support_forum_class = $is_support_provided ? 'description license-info' : 'description license-hidden license-info';
		$description_renew_support_class = $is_support_provided ? 'description license-hidden license-info' : 'description license-info';

		?>
<tr valign="top">
	<th class="row-title"><?php echo esc_html( $this->strings['license-supported-until'] ); ?></th>
	<td>
		<span id="license-date-supported-until"
			class="<?php echo esc_attr( $date_supported_until_class ); ?>"><?php echo esc_html( $date_supported_full_string ); ?></span>
		<p id="license-support-forum" class="<?php echo esc_attr( $description_support_forum_class ); ?>">
			<a class="button button-secondary button-large"
				href="<?php echo esc_url( $this->strings['support-forum-url'] ); ?>"
				target="_blank"><?php echo esc_html( $this->strings['support-forum-link'] ); ?></a>
		</p>
		<p id="license-renew-support" class="<?php echo esc_attr( $description_renew_support_class ); ?>">
			<a class="button button-secondary button-large" href="<?php echo esc_url( $this->strings['item-page-url'] ); ?>"
				target="_blank"><?php echo esc_html( $this->strings['support-renew'] ); ?></a>
		</p>
	</td>
</tr>
		<?php
	}

	/**
	 * Gets the formatted expiration date string.
	 *
	 * @return string The formatted expiration date.
	 */
	private function get_expiration_date_string() {
		$license_expires = $this->managers->options->get( 'license_expires' );

		if ( strtolower( $license_expires ) === 'lifetime' ) {
			return $this->strings['expires-never'];
		}

		return $license_expires ? date( $this->date_format, strtotime( $license_expires ) ) : '';
	}

	/**
	 * Gets the formatted date supported until string.
	 *
	 * @return string The formatted support until date.
	 */
	private function get_date_supported_until_string() {
		$support_provided_until      = $this->managers->options->get( 'license_date_supported_until' );
		$support_provided_until_date = $support_provided_until ? date( $this->date_format, strtotime( $support_provided_until ) ) : $this->strings['date-unknown'];

		return $support_provided_until_date;
	}

	/**
	 * Gets the formatted purchase date string.
	 *
	 * @return string The formatted purchase date.
	 */
	private function get_purchase_date_string() {
		$date_purchased = $this->managers->options->get( 'license_date_purchased' );
		$purchase_date  = $date_purchased ? date( $this->date_format, strtotime( $date_purchased ) ) : $this->strings['date-unknown'];

		return $purchase_date;
	}

	/**
	 * Gets the formatted support date with status message.
	 *
	 * @return string The formatted support date with status.
	 */
	private function get_date_supported_full_string() {
		$support_provided_until_date = $this->get_date_supported_until_string();
		$is_support_provided         = $this->managers->license->is_support_provided();

		if ( $is_support_provided ) {
			return $this->strings['support-supported-until'] . ' ' . $support_provided_until_date;
		}

		return $this->strings['support-expired'] . ' ' . $support_provided_until_date;
	}

	/**
	 * Gets the formatted updates provided until date string.
	 *
	 * @return string The formatted updates until date.
	 */
	private function get_date_updates_provided_until_string() {
		$updates_provided_until = $this->managers->options->get( 'license_date_updates_provided_until' );

		if ( strtolower( $updates_provided_until ) === 'lifetime' ) {
			$updates_provided_until_date = $this->strings['license-lifetime-updates'];
		} else {
			$updates_provided_until_date = $updates_provided_until ? date( $this->date_format, strtotime( $updates_provided_until ) ) : $this->strings['date-unknown'];
		}

		return $updates_provided_until_date;
	}
}
