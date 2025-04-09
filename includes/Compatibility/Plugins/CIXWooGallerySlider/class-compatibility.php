<?php
/**
 * Codeixer Product Gallery Slider compatibility handler.
 *
 * @package RSFV
 */

namespace RSFV\Compatibility\Plugins\CIXWooGallerySlider;

defined( 'ABSPATH' ) || exit;

use RSFV\Compatibility\Plugins\Base_Compatibility;

/**
 * Class Compatibility
 *
 * @package RSFV
 */
class Compatibility extends Base_Compatibility {

	/**
	 * Class instance.
	 *
	 * @var $instance
	 */
	protected static $instance;

	/**
	 * Constructor.
	 */
	public function __construct() {
		parent::__construct();

		$this->id = 'cix-woo-gallery-slider';

		$this->setup();
	}

	/**
	 * Sets up hooks and filters.
	 *
	 * @return void
	 */
	public function setup() {
		if ( function_exists( 'wpgs_get_template' ) && has_filter( 'wc_get_template', 'wpgs_get_template' ) ) {
			remove_filter( 'wc_get_template', 'wpgs_get_template' );
			add_filter( 'wc_get_template', array( $this, 'filter_product_image_template' ), 10, 5 );
		}
	}

	/**
	 * Updates plugin's template for Featured video.
	 *
	 * @param string $located Located at absolute file path.
	 * @param string $template_name Widget name/id.
	 * @param array  $args Arguments from widget.
	 * @param string $template_path Widget path.
	 * @param string $default_path Default location.
	 */
	public function filter_product_image_template( $located, $template_name, $args, $template_path, $default_path ) {
		if ( 'single-product/product-image.php' === $template_name ) {
			$template_directory = untrailingslashit( plugin_dir_path( __FILE__ ) );
			$located            = $template_directory . '/templates/product-image.php';
			$located            = apply_filters( 'rsfv_cix_woo_gallery_slider_template', $located );
		}

		return $located;
	}
}
