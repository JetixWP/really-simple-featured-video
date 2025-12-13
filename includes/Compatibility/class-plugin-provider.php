<?php
/**
 * Plugin compatibility handler.
 *
 * @package RSFV
 */

namespace RSFV\Compatibility;

/**
 * Class Plugin_Provider
 *
 * @package RSFV
 */
class Plugin_Provider {
	const COMPAT_DIR = RSFV_PLUGIN_DIR . 'includes/Compatibility/Plugins/';
	const COMPAT_URL = RSFV_PLUGIN_URL . 'includes/Compatibility/Plugins/';

	/**
	 * Class instance.
	 *
	 * @var $instance
	 */
	protected static $instance;

	/**
	 * Plugin engines.
	 *
	 * @var array $plugin_engines
	 */
	private $plugin_engines;

	/**
	 * Constructor.
	 */
	public function __construct() {

		// Register plugin engines.
		// @note - This variable is being called early and hence translation functions will trigger a warning if used here.
		$this->plugin_engines = apply_filters(
			'rsfv_plugin_compatibility_engines',
			array(
				'woocommerce'              => array(
					'title'            => 'WooCommerce',
					'file_source'      => self::COMPAT_DIR . 'WooCommerce/class-compatibility.php',
					'class'            => 'RSFV\Compatibility\Plugins\WooCommerce\Compatibility',
					'has_class_loaded' => 'WooCommerce',
				),
				'astra-addon'              => array(
					'title'            => 'Astra Pro',
					'file_source'      => self::COMPAT_DIR . 'AstraPro/class-compatibility.php',
					'class'            => 'RSFV\Compatibility\Plugins\AstraPro\Compatibility',
					'has_class_loaded' => 'Astra_Addon_Update',
				),
				'salient-core'             => array(
					'title'            => 'Salient Core',
					'file_source'      => RSFV_PLUGIN_DIR . 'includes/Compatibility/Plugins/SalientCore/class-compatibility.php',
					'class'            => 'RSFV\Compatibility\Plugins\SalientCore\Compatibility',
					'has_class_loaded' => 'Salient_Core',
				),
				'elementor'                => array(
					'title'            => 'Elementor',
					'file_source'      => RSFV_PLUGIN_DIR . 'includes/Compatibility/Plugins/Elementor/class-compatibility.php',
					'class'            => 'RSFV\Compatibility\Plugins\Elementor\Compatibility',
					'has_class_loaded' => 'Elementor\Plugin',
				),
				'divi'                     => array(
					'title'       => 'Divi',
					'file_source' => RSFV_PLUGIN_DIR . 'includes/Compatibility/Plugins/Divi/class-compatibility.php',
					'class'       => 'RSFV\Compatibility\Plugins\Divi\Compatibility',
				),
				'tp-product-image-flipper' => array(
					'title'        => 'TP Product Image Flipper',
					'file_source'  => RSFV_PLUGIN_DIR . 'includes/Compatibility/Plugins/TPProductImageFlipper/class-compatibility.php',
					'class'        => 'RSFV\Compatibility\Plugins\TPProductImageFlipper\Compatibility',
					'has_function' => 'tp_remove_action',
				),
				'cix-woo-gallery-slider'   => array(
					'title'            => 'Codeixer Product Gallery Slider',
					'file_source'      => RSFV_PLUGIN_DIR . 'includes/Compatibility/Plugins/CIXWooGallerySlider/class-compatibility.php',
					'class'            => 'RSFV\Compatibility\Plugins\CIXWooGallerySlider\Compatibility',
					'has_class_loaded' => 'Product_Gallery_Sldier\Product',
				),
				'bricks'                   => array(
					'title'       => 'Bricks',
					'file_source' => RSFV_PLUGIN_DIR . 'includes/Compatibility/Plugins/Bricks/class-compatibility.php',
					'class'       => 'RSFV\Compatibility\Plugins\Bricks\Compatibility',
				),
			)
		);

		$this->load_plugin_compat();
	}

	/**
	 * Get a class instance.
	 *
	 * @return Object
	 */
	public static function get_instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}


	/**
	 * Load plugin compatibility.
	 *
	 * @return void
	 */
	public function load_plugin_compat() {

		foreach ( $this->plugin_engines as $plugin_engine => $plugin_data ) {

			// For classes.
			if ( isset( $plugin_data['has_class_loaded'] ) && ! class_exists( $plugin_data['has_class_loaded'] ) ) {
				continue;
			}

			// For functions.
			if ( isset( $plugin_data['has_function'] ) && ! function_exists( $plugin_data['has_function'] ) ) {
				continue;
			}

			// For constants.
			if ( isset( $plugin_data['has_defined_constant'] ) && ! defined( $plugin_data['has_defined_constant'] ) ) {
				continue;
			}

			require_once $plugin_data['file_source'];
			$plugin_data['class']::get_instance();
		}
	}

	/**
	 * Get registered engines id and title.
	 *
	 * @return array
	 */
	public function get_available_engines() {
		$registered_engines = array();

		foreach ( $this->plugin_engines as $engine_id => $engine_data ) {
			$registered_engines[ $engine_id ] = $engine_data['title'];
		}

		return $registered_engines;
	}

	/**
	 * Get selectable engines for user settings.
	 *
	 * @return array
	 */
	public function get_selectable_engine_options() {
		$selectable_engines = array();

		foreach ( $this->plugin_engines as $engine_id => $engine_data ) {
			$selectable_engines[ $engine_id ] = $engine_data['title'];
		}

		return $selectable_engines;
	}
}
