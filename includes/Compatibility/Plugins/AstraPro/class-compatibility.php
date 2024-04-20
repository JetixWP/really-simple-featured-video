<?php
/**
 * Astra Pro's compatibility handler.
 *
 * @package RSFV
 */

namespace RSFV\Compatibility\Plugins\AstraPro;

defined( 'ABSPATH' ) || exit;

use ASTRA_Ext_WooCommerce_Markup;
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

		$this->id = 'astra-addon';

		$this->setup();
	}

	/**
	 * Sets up hooks and filters.
	 *
	 * @return void
	 */
	public function setup() {
		add_action( 'wp', array( $this, 'woo_single_product_layouts' ), 100 );
	}

	/**
	 * Override Astra Pro's Woocommerce single product layouts.
	 *
	 * @return void
	 * @since n.e.x.t
	 */
	public function woo_single_product_layouts() {
		// Exit early if WooCommerce addon isn't active.
		if ( ! class_exists( 'ASTRA_Ext_WooCommerce_Markup' ) ) {
			return;
		}

		$astra_ext_instance = ASTRA_Ext_WooCommerce_Markup::get_instance();

		if ( ( class_exists( 'Woocommerce' ) && is_product() ) && ( false === $astra_ext_instance::$wc_layout_built_with_themer ) && apply_filters( 'rsfv_astra_addon_override_single_product_layout', true ) ) {
			// Vertical product gallery slider.
			if ( 'vertical-slider' === astra_get_option( 'single-product-gallery-layout' ) || 'horizontal-slider' === astra_get_option( 'single-product-gallery-layout' ) ) {
				remove_action( 'woocommerce_before_single_product_summary', 'woocommerce_show_product_images', 20 );
				remove_action( 'woocommerce_before_single_product_summary', array( $astra_ext_instance, 'woo_single_product_gallery_output' ), 20 );
				add_action( 'woocommerce_before_single_product_summary', array( $this, 'woo_single_product_gallery_output' ), 20 );

				add_filter(
					'woocommerce_gallery_thumbnail_size',
					function ( $size ) {
						return 'thumbnail';
					}
				);

				add_filter( 'woocommerce_single_product_carousel_options', array( $astra_ext_instance, 'filter_single_product_carousel_options' ) );
			}

			// First image large gallery.
			if ( 'first-image-large' === astra_get_option( 'single-product-gallery-layout' ) ) {

				remove_theme_support( 'wc-product-gallery-slider' );

				add_filter(
					'woocommerce_gallery_thumbnail_size',
					function ( $size ) {
						return 'medium';
					}
				);
			}
		}
	}

	/**
	 * Astra Pro's Woocommerce single product gallery template override.
	 *
	 * @return void
	 * @since n.e.x.t
	 */
	public function woo_single_product_gallery_output() {
		include_once $this->get_current_dir() . 'AstraPro/templates/woocommerce/single-product-gallery.php';
	}
}
