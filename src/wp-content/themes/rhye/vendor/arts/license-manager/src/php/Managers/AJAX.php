<?php

namespace Arts\LicenseManager\Managers;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Class AJAX
 *
 * Handles AJAX requests for license management operations.
 *
 * @package Arts\LicenseManager\Managers
 * @since 1.0.0
 */
class AJAX extends BaseManager {
	/**
	 * The nonce ID used for AJAX security.
	 *
	 * @var string
	 */
	private $nonce_id;

	/**
	 * Initialize the AJAX manager.
	 *
	 * @param \stdClass $managers Other managers used by this manager.
	 * @return void
	 */
	public function init( $managers ) {
		$this->nonce_id = $this->args['theme_slug'] . '-license-options';

		$this->add_managers( $managers );
	}

	/**
	 * Handles AJAX request to refresh license information.
	 *
	 * Refreshes license data and returns updated information or error message.
	 *
	 * @return void
	 */
	public function refresh_license() {
		check_admin_referer( $this->nonce_id );

		$result = $this->managers->license->do_refresh_license();

		if ( $result['success'] ) {
			wp_cache_flush();

			$data = array(
				'message'                     => esc_html( $result['message'] ),
				'status'                      => esc_html( $this->managers->license->get_status() ),
				'is_local'                    => esc_html( $this->managers->license->is_local() ),
				'is_support_provided'         => esc_html( $this->managers->license->is_support_provided() ),
				'expires'                     => esc_html( $this->managers->strings->get_expiration_date() ),
				'date_purchased'              => esc_html( $this->managers->strings->get_purchase_date() ),
				'date_supported_until'        => esc_html( $this->managers->strings->get_date_supported_full() ),
				'date_updates_provided_until' => esc_html( $this->managers->strings->get_date_updates_provided_until() ),
				'site_count'                  => esc_html( $this->managers->strings->get_license_site_count() ),
				'license_limit'               => esc_html( $this->managers->strings->get_license_limit() ),
				'activations_left'            => esc_html( $this->managers->strings->get_license_activations_left() ),
			);

			wp_send_json_success( $data );
		} else {
			wp_send_json_error(
				array(
					'message'  => $result['message'],
					'location' => $this->managers->license->get_page_location( $result ),
				)
			);
		}
	}

	/**
	 * Handles AJAX request to clear the license.
	 *
	 * Removes the license key and returns success or error message.
	 *
	 * @return void
	 */
	public function clear_license() {
		check_admin_referer( $this->nonce_id );

		$result = $this->managers->license->do_clear_license();

		if ( $result['success'] ) {
			wp_cache_flush();

			$data = array(
				'message' => esc_html( $result['message'] ),
				'key'     => esc_html( $this->managers->license->get_key() ),
				'status'  => esc_html( $this->managers->license->get_status() ),
			);

			wp_send_json_success( $data );
		} else {
			wp_send_json_error(
				array(
					'message'  => $result['message'],
					'location' => $this->managers->license->get_page_location( $result ),
				)
			);
		}

		die();
	}
}
