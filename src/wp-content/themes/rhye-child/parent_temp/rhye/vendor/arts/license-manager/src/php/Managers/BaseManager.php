<?php

namespace Arts\LicenseManager\Managers;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Abstract class BaseManager
 *
 * This class serves as a base for managers of the plugin.
 *
 * @package Arts\LicenseManager\Managers
 * @since 1.0.0
 */
abstract class BaseManager {
	/**
	 * Arguments for the manager.
	 *
	 * @var array
	 */
	protected $args;

	/**
	 * Array of text strings used by the manager.
	 *
	 * @var array
	 */
	protected $strings;

	/**
	 * Other managers used by the current manager.
	 *
	 * @var \stdClass
	 */
	protected $managers;

	/**
	 * Constructor for the BaseManager class.
	 *
	 * @param array $args    Arguments for the manager.
	 * @param array $strings Array of text strings used by the manager.
	 */
	public function __construct( $args = array(), $strings = array() ) {
		$this->args    = $args;
		$this->strings = $strings;
	}

	/**
	 * Initialize the manager with other managers.
	 *
	 * @param \stdClass $managers Other managers used by the current manager.
	 */
	public function init( $managers ) {
		$this->add_managers( $managers );
	}

	/**
	 * Add other managers to the current manager.
	 *
	 * @param \stdClass $managers Other managers used by the current manager.
	 * @return BaseManager Returns the current instance for method chaining.
	 */
	protected function add_managers( $managers ) {
		if ( ! isset( $this->managers ) ) {
			$this->managers = new \stdClass();
		}

		foreach ( $managers as $key => $manager ) {
			// Prevent adding self to the managers property to avoid infinite loop.
			if ( $manager !== $this ) {
				$this->managers->$key = $manager;
			}
		}

		return $this;
	}

	/**
	 * Initialize a property from the args array.
	 *
	 * This method checks if the specified property exists in the args array and initializes
	 * the property with the corresponding value from the args array if it exists.
	 *
	 * @param string $property The name of the property to initialize.
	 *
	 * @return BaseManager Returns the current instance for method chaining.
	 */
	protected function init_property( $property ) {
		if ( isset( $this->args[ $property ] ) ) {
			$this->$property = $this->args[ $property ];
		}

		return $this;
	}

	/**
	 * Initialize an array property from the args array.
	 *
	 * This method checks if the specified property exists in the args array, is an array,
	 * and is not empty. If all conditions are met, it initializes the property with the
	 * corresponding value from the args array.
	 *
	 * @param string $property The name of the property to initialize.
	 *
	 * @return BaseManager Returns the current instance for method chaining.
	 */
	protected function init_array_property( $property ) {
		if ( isset( $this->args[ $property ] ) && is_array( $this->args[ $property ] ) && ! empty( $this->args[ $property ] ) ) {
			$this->$property = $this->args[ $property ];
		}

		return $this;
	}
}
