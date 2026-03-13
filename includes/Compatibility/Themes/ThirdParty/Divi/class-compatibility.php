<?php
/**
 * Divi theme compatibility handler.
 *
 * @package RSFV
 */

namespace RSFV\Compatibility\Themes\ThirdParty\Divi;

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

		$this->id = 'divi';

		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
	}

	/**
	 * Enqueue scripts.
	 *
	 * @return void
	 */
	public function enqueue_scripts() {
		// Register styles.
		wp_register_style( 'rsfv-divi', $this->get_current_dir_url() . 'ThirdParty/Divi/styles.css', array(), filemtime( $this->get_current_dir() . 'ThirdParty/Divi/styles.css' ) );

		// Enqueue styles.
		wp_enqueue_style( 'rsfv-divi' );

		// Add generated CSS.
		wp_add_inline_style( 'rsfv-divi', Plugin::get_instance()->frontend_provider->generate_dynamic_css() );
	}

	/**
	 * Overrides theme Woo templates.
	 *
	 * @return void
	 */
	public function override_woo_templates() {
	}
}
