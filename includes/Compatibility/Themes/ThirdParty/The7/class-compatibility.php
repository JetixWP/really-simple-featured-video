<?php
/**
 * The7 theme compatibility handler.
 *
 * @package RSFV
 */

namespace RSFV\Compatibility\Themes\ThirdParty\The7;

use RSFV\Compatibility\Themes\Base_Compatibility;
use RSFV\Options;
use RSFV\Compatibility\Plugins\WooCommerce\Compatibility as BaseWooCompatibility;
use RSFV\Plugin;

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

		$this->id = 'dt-the7';

		$this->override_woo_templates();

		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
	}

	/**
	 * Enqueue scripts.
	 *
	 * @return void
	 */
	public function enqueue_scripts() {
		// Register dummy styles.
		wp_register_style( 'rsfv-dt-the7', $this->get_current_dir_url() . 'ThirdParty/The7/styles.css', array(), filemtime( $this->get_current_dir() . 'ThirdParty/The7/styles.css' ) );

		// Enqueue styles.
		wp_enqueue_style( 'rsfv-dt-the7' );

		// Add generated CSS.
		wp_add_inline_style( 'rsfv-dt-the7', Plugin::get_instance()->frontend_provider->generate_dynamic_css() );
	}

	/**
	 * Overrides theme Woo templates.
	 *
	 * @return void
	 */
	public function override_woo_templates() {
		if ( ! class_exists( 'WooCommerce' ) ) {
			return;
		}

		$options                     = Options::get_instance();
		$product_archives_visibility = $options->get( 'product_archives_visibility' );

		$base_woo_compat_instance = BaseWooCompatibility::get_instance();

		if ( ( ! $options->has( 'product_archives_visibility' ) && ! $product_archives_visibility ) || $product_archives_visibility ) {
			remove_action( 'woocommerce_before_shop_loop_item_title', array( $base_woo_compat_instance, 'get_woo_archives_video' ), 10 );

			add_action( 'dt_woocommerce_shop_loop_images', array( $base_woo_compat_instance, 'get_woo_archives_video' ), 10 );

			remove_action( 'dt_woocommerce_shop_loop_images', 'dt_woocommerce_get_alt_product_thumbnail', 11 );

			add_action( 'rsfv_woo_archives_product_thumbnails', 'woocommerce_template_loop_product_thumbnail' );

			add_action(
				'init', // Since it doesn't work outside a parent hook.
				function () {
					remove_action( 'dt_woocommerce_shop_loop_images', 'woocommerce_template_loop_product_thumbnail', 10 );
				}
			);
		}
	}
}
