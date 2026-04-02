<?php

namespace Arts\LicenseManager\Managers;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Class Options
 *
 * Manages options storage and retrieval for the license manager.
 *
 * @package Arts\LicenseManager\Managers
 * @since 1.0.0
 */
class Options extends BaseManager {
	/**
	 * The theme slug used for option prefixing.
	 *
	 * @var string
	 */
	private $theme_slug;

	/**
	 * Initialize the Options manager.
	 *
	 * @param \stdClass $managers Other managers used by this manager.
	 * @return void
	 */
	public function init( $managers ) {
		$this->theme_slug = $this->args['theme_slug'];

		$this->add_managers( $managers );
	}

	/**
	 * Gets an option value.
	 *
	 * @param string $option        The option name without prefix.
	 * @param mixed  $default_value Optional default value.
	 * @return mixed The option value or default if not found.
	 */
	public function get( $option, $default_value = '' ) {
		return get_option( $this->get_prefixed_string( $option ), $default_value );
	}

	/**
	 * Updates an option value.
	 *
	 * @param string $option The option name without prefix.
	 * @param mixed  $value  The new value for the option.
	 * @return bool True if the option was updated, false otherwise.
	 */
	public function update( $option, $value ) {
		return update_option( $this->get_prefixed_string( $option ), $value );
	}

	/**
	 * Deletes an option.
	 *
	 * @param string $option The option name without prefix.
	 * @return bool True if the option was deleted, false otherwise.
	 */
	public function delete( $option ) {
		return delete_option( $this->get_prefixed_string( $option ) );
	}

	/**
	 * Adds the theme slug prefix to a string.
	 *
	 * @param string $string The string to prefix.
	 * @return string The prefixed string.
	 */
	public function get_prefixed_string( $string ) {
		return $this->theme_slug . '_' . $string;
	}
}
