<?php
/**
 * Ollie theme compatibility handler.
 *
 * @package RSFV
 */

namespace RSFV\Compatibility\Themes\ThirdParty\Ollie;

use RSFV\Compatibility\Themes\Base_Compatibility;
use RSFV\Options;
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

		$this->id = 'ollie';

		$this->setup();

		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

		if ( has_action( 'rsfv_woo_archives_product_thumbnails', 'woocommerce_template_loop_product_thumbnail' ) ) {
			remove_action( 'rsfv_woo_archives_product_thumbnails', 'woocommerce_template_loop_product_thumbnail', 10 );
		}
	}

	/**
	 * Enqueue scripts.
	 *
	 * @return void
	 */
	public function enqueue_scripts() {
		// Register styles.
		wp_register_style( 'rsfv-ollie', $this->get_current_dir_url() . 'ThirdParty/Ollie/styles.css', array(), filemtime( $this->get_current_dir() . 'ThirdParty/Ollie/styles.css' ) );

		// Enqueue styles.
		wp_enqueue_style( 'rsfv-ollie' );

		// Add generated CSS.
		wp_add_inline_style( 'rsfv-ollie', Plugin::get_instance()->frontend_provider->generate_dynamic_css() );
	}

	/**
	 * Setup compat.
	 *
	 * @return void
	 */
	public function setup() {
		// Removes old support for Woo FSE archives.
		$this->remove_woo_fse_archives_support();
	}

	/**
	 * Removes Woo Archives support for fse themes.
	 *
	 * @return void
	 */
	public function remove_woo_fse_archives_support() {
		if ( ! class_exists( '\WooCommerce' ) ) {
			return;
		}

		$options = Options::get_instance();

		$woo_archives_supported = $options->get( 'woo_archives_supported', true );

		if ( $woo_archives_supported ) {
			$options->set( 'woo_archives_supported', false );
		}
	}
}
