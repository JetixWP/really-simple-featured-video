<?php
/**
 * TP Product Image Flipper compatibility handler.
 *
 * @package RSFV
 */

namespace RSFV\Compatibility\Plugins\TPProductImageFlipper;

defined( 'ABSPATH' ) || exit;

use RSFV\FrontEnd;
use RSFV\Compatibility\Plugins\Base_Compatibility;
use RSFV\Compatibility\Plugins\WooCommerce\Compatibility as WooBaseCompatibility;
use RSFV\Options;

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

		$this->id = 'tp-product-image-flipper';

		$this->setup();
	}

	/**
	 * Sets up hooks and filters.
	 *
	 * @return void
	 */
	public function setup() {
		if ( has_action( 'woocommerce_before_shop_loop_item_title', 'tp_create_flipper_images' ) ) {
			$options                     = Options::get_instance();
			$product_archives_visibility = $options->get( 'product_archives_visibility' );

			if ( ( ! $options->has( 'product_archives_visibility' ) && ! $product_archives_visibility ) || $product_archives_visibility ) {
				$woo_base_compatibility = WooBaseCompatibility::get_instance();

				remove_action( 'woocommerce_before_shop_loop_item_title', 'tp_create_flipper_images' );
				remove_action( 'woocommerce_before_shop_loop_item_title', array( $woo_base_compatibility, 'get_woo_archives_video' ) );
				add_action( 'woocommerce_before_shop_loop_item_title', array( $this, 'update_flipper_images' ) );
			}
		}
	}

	/**
	 * Make the product flipper images work with Featured video.
	 *
	 * @return void
	 */
	public function update_flipper_images() {
		global $product;

		$product_id = $product->get_id();

		$has_featured_video = FrontEnd::has_featured_video( $product_id );

		if ( function_exists( 'tp_create_flipper_images' ) && ! $has_featured_video ) {
			tp_create_flipper_images();
		} else {
			WooBaseCompatibility::get_instance()->get_woo_archives_video( $product_id );
		}
	}
}
