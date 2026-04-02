<?php

namespace Arts\WizardSetup\Managers;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

use \Arts\Utilities\Utilities;

/**
 * Class Elementor
 *
 * Manages Elementor settings and configurations for the theme setup wizard.
 *
 * @since      1.0.0
 * @package    Arts\WizardSetup\Managers
 * @author     Artem Semkin
 * @property   array  $options Configuration options.
 * @property   array  $editable_post_types Post types that can be edited.
 * @property   bool   $set_active_kit Flag to set the active kit.
 * @property   bool   $link_uploaded_fonts Flag to link uploaded fonts.
 * @property   string $replace_urls_from URL to replace from.
 * @property   string $replace_urls_to URL to replace to.
 */
class Elementor extends BaseManager {
	/**
	 * Elementor configuration options.
	 *
	 * @since 1.0.0
	 * @access private
	 * @var array
	 */
	private $options;

	/**
	 * Post types that can be edited with Elementor.
	 *
	 * @since 1.0.0
	 * @access private
	 * @var array
	 */
	private $editable_post_types;

	/**
	 * Whether to set active Elementor kit.
	 *
	 * @since 1.0.0
	 * @access private
	 * @var bool
	 */
	private $set_active_kit;

	/**
	 * Whether to link uploaded fonts.
	 *
	 * @since 1.0.0
	 * @access private
	 * @var bool
	 */
	private $link_uploaded_fonts;

	/**
	 * URL to replace from in Elementor content.
	 *
	 * @since 1.0.0
	 * @access private
	 * @var string
	 */
	private $replace_urls_from;

	/**
	 * URL to replace to in Elementor content.
	 *
	 * @since 1.0.0
	 * @access private
	 * @var string
	 */
	private $replace_urls_to;

	/**
	 * Performs various setup operations for Elementor.
	 *
	 * @since  1.0.0
	 * @access public
	 * @return array The results of all setup operations.
	 */
	public function setup() {
		$result = array(
			'link_uploaded_fonts'        => self::link_uploaded_fonts( $this->link_uploaded_fonts ),
			'update_editable_post_types' => self::update_editable_post_types( $this->editable_post_types ),
			'update_options'             => self::update_options( $this->options ),
			'set_active_kit'             => self::set_active_kit( $this->set_active_kit ),
			'clear_theme_builder_cache'  => self::clear_theme_builder_cache(),
			'replace_urls'               => self::replace_urls( $this->replace_urls_from, $this->replace_urls_to ),
			'regenerate_css_and_data'    => self::regenerate_css_and_data(),
		);

		return $result;
	}

	/**
	 * Remove Elementor welcome splash screen on the initial plugin activation.
	 *
	 * This method deletes the transient responsible for the Elementor activation redirect.
	 * It is used to prevent issues when the Merlin wizard is installing and activating the required plugins.
	 *
	 * @since  1.0.0
	 * @access public
	 * @return bool Returns true after the transient is deleted.
	 */
	public function remove_welcome_screen() {
		delete_transient( 'elementor_activation_redirect' );

		return true;
	}

	/**
	 * Initializes the properties from configuration arguments.
	 *
	 * @since  1.0.0
	 * @access protected
	 * @return $this For method chaining.
	 */
	protected function init_properties() {
		if ( isset( $this->args['setup_elementor']['editable_post_types'] ) && is_array( $this->args['setup_elementor']['editable_post_types'] ) && ! empty( $this->args['setup_elementor']['editable_post_types'] ) ) {
			$this->editable_post_types = $this->args['setup_elementor']['editable_post_types'];
		}

		if ( isset( $this->args['setup_elementor']['options'] ) && is_array( $this->args['setup_elementor']['options'] ) && ! empty( $this->args['setup_elementor']['options'] ) ) {
			$this->options = $this->args['setup_elementor']['options'];
		}

		if ( isset( $this->args['setup_elementor']['set_active_kit'] ) ) {
			$this->set_active_kit = $this->args['setup_elementor']['set_active_kit'];
		}

		if ( isset( $this->args['setup_elementor']['link_uploaded_fonts'] ) ) {
			$this->link_uploaded_fonts = $this->args['setup_elementor']['link_uploaded_fonts'];
		}

		if ( isset( $this->args['setup_elementor']['replace_urls_from'] ) && ! empty( $this->args['setup_elementor']['replace_urls_from'] ) ) {
			$this->replace_urls_from = $this->args['setup_elementor']['replace_urls_from'];
		}

		if ( isset( $this->args['setup_elementor']['replace_urls_to'] ) && ! empty( $this->args['setup_elementor']['replace_urls_to'] ) ) {
			$this->replace_urls_to = $this->args['setup_elementor']['replace_urls_to'];
		} else {
			$this->replace_urls_to = trailingslashit( get_site_url() );
		}

		return $this;
	}

	/**
	 * Clears the Elementor Pro Theme Builder cache if the Conditions_Cache class exists.
	 *
	 * @since  1.0.0
	 * @access public static
	 * @return bool True if the cache was cleared, false otherwise.
	 */
	public static function clear_theme_builder_cache() {
		if ( class_exists( '\ElementorPro\Modules\ThemeBuilder\Classes\Conditions_Cache' ) ) {
			$cache = new \ElementorPro\Modules\ThemeBuilder\Classes\Conditions_Cache();
			$cache->regenerate();

			return true;
		}

		return false;
	}

	/**
	 * Replaces URLs using Elementor's Utils class.
	 *
	 * @since  1.0.0
	 * @access public static
	 * @param  string $from The URL to be replaced.
	 * @param  string $to The new URL to replace with.
	 * @return bool True on success, false on failure.
	 */
	public static function replace_urls( $from, $to ) {
		if ( ! class_exists( '\Elementor\Utils' ) || ! $from || ! $to ) {
			return false;
		}

		try {
			\Elementor\Utils::replace_urls( $from, $to );
		} catch ( \Exception $e ) {
			return false;
		}

		return true;
	}

	/**
	 * Regenerates CSS and data for Elementor by clearing the cache.
	 *
	 * @since  1.0.0
	 * @access public static
	 * @return bool True if the cache was cleared, false otherwise.
	 */
	public static function regenerate_css_and_data() {
		if ( class_exists( '\Elementor\Plugin' ) && \Elementor\Plugin::$instance && \Elementor\Plugin::$instance->files_manager ) {
			\Elementor\Plugin::$instance->files_manager->clear_cache();

			return true;
		}

		return false;
	}

	/**
	 * Sets the active Elementor kit.
	 *
	 * @since  1.0.0
	 * @access public static
	 * @param  string|int $active_kit The kit name or ID to set as active.
	 * @return int|false The kit post ID if successful, false otherwise.
	 */
	public static function set_active_kit( $active_kit ) {
		if ( ! $active_kit || ! class_exists( '\Elementor\TemplateLibrary\Source_Local' ) ) {
			return false;
		}

		$kit_post_id   = null;
		$kit_post_name = '';

		if ( is_string( $active_kit ) ) {
			$kit_post_name = $active_kit;
		} elseif ( is_int( $active_kit ) ) {
			$kit_post_id = $active_kit;
		}

		if ( ! $kit_post_id ) {
			$kit_post_type   = \Elementor\TemplateLibrary\Source_Local::CPT;
			$kit_post_object = Utilities::get_page_by_title( $kit_post_name, OBJECT, $kit_post_type );

			if ( $kit_post_object && $kit_post_object->ID ) {
				$kit_post_id = $kit_post_object->ID;
			}
		}

		update_option( 'elementor_active_kit', $kit_post_id );

		return $kit_post_id;
	}

	/**
	 * Updates Elementor options.
	 *
	 * @since  1.0.0
	 * @access public static
	 * @param  array $options Array of options to update.
	 * @return bool True on success, false on failure.
	 */
	public static function update_options( $options ) {
		if ( ! is_array( $options ) || empty( $options ) ) {
			return false;
		}

		$prefix = 'elementor_';

		foreach ( $options as $option => $value ) {
			update_option( $prefix . $option, $value );
		}

		return true;
	}

	/**
	 * Updates the editable post types for Elementor.
	 *
	 * @since  1.0.0
	 * @access public static
	 * @param  array $editable_post_types Array of post types to be made editable.
	 * @return bool True on success, false on failure.
	 */
	public static function update_editable_post_types( $editable_post_types ) {
		if ( ! array( $editable_post_types ) || empty( $editable_post_types ) ) {
			return false;
		}

		$option = 'elementor_cpt_support';
		$value  = get_option( $option );

		if ( ! $value || empty( $value ) ) {
			$default_editable_post_types = array( 'page', 'post' );
			$value                       = array_merge( $default_editable_post_types, $editable_post_types );
		} else {
			foreach ( $editable_post_types as $post_type ) {
				if ( ! in_array( $post_type, $value ) ) {
					$value[] = $post_type;
				}
			}
		}

		update_option( $option, $value );

		return true;
	}

	/**
	 * Updates custom fonts URLs in Elementor.
	 *
	 * @since  1.0.0
	 * @access public static
	 * @param  array $font_files Array of font files with 'ID' and 'permalink'.
	 * @return void
	 */
	public static function update_custom_fonts( $font_files ) {
		if ( ! array( $font_files ) || empty( $font_files ) || ! class_exists( '\ElementorPro\Modules\AssetsManager\AssetTypes\Fonts\Custom_Fonts' ) ) {
			return;
		}

		$meta_key        = \ElementorPro\Modules\AssetsManager\AssetTypes\Fonts\Custom_Fonts::FONT_META_KEY;
		$font_extensions = array(
			'woff',
			'woff2',
			'ttf',
			'svg',
			'eot',
		);
		$query_args      = array(
			'posts_per_page' => -1,
			'post_type'      => 'elementor_font',
		);
		$loop            = new \WP_Query( $query_args );

		if ( $loop->have_posts() ) {
			while ( $loop->have_posts() ) {
				$loop->the_post();
				$post_id = get_the_ID();

				$elementor_font_files = get_post_meta( $post_id, $meta_key, true );

				if ( ! empty( $elementor_font_files ) ) {
					foreach ( $elementor_font_files as $elementor_font_file_index => $elementor_font_file ) {
						foreach ( $elementor_font_file as $elementor_font_variant_index => $elementor_font_variant ) {
							foreach ( $font_extensions as $font_ext ) {
								if ( $elementor_font_variant_index === $font_ext &&
										is_array( $elementor_font_variant ) &&
										array_key_exists( 'id', $elementor_font_variant ) &&
										! empty( $elementor_font_variant['id'] )
								) {
									$font_id = $elementor_font_variant['id'];

									foreach ( $font_files as $file ) {
										if ( $font_id == $file['ID'] ) {
											// Update font URL
											$elementor_font_files[ $elementor_font_file_index ][ $elementor_font_variant_index ]['url'] = $file['permalink'];
										}
									}
								}
							}
						}
					}

					update_post_meta( $post_id, $meta_key, $elementor_font_files );
				}
			}

			wp_reset_postdata();

			self::regenerate_css_and_data();
		}
	}

	/**
	 * Links uploaded custom fonts.
	 *
	 * @since  1.0.0
	 * @access public static
	 * @param  bool $link_uploaded_fonts Flag to link uploaded fonts.
	 * @return bool True if fonts are linked, false otherwise.
	 */
	public static function link_uploaded_fonts( $link_uploaded_fonts ) {
		if ( ! $link_uploaded_fonts ) {
			return false;
		}

		$uploaded_fonts = Utilities::get_uploaded_fonts();

		if ( ! array( $uploaded_fonts ) || empty( $uploaded_fonts ) ) {
			return false;
		}

		self::update_custom_fonts( $uploaded_fonts );

		return true;
	}
}
