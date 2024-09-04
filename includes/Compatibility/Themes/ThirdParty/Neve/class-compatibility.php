<?php
/**
 * Neve theme compatibility handler.
 *
 * @package RSFV
 */

namespace RSFV\Compatibility\Themes\ThirdParty\Neve;

use RSFV\Compatibility\Themes\Base_Compatibility;
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

		$this->id = 'neve';

		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
	}

	/**
	 * Enqueue scripts.
	 *
	 * @return void
	 */
	public function enqueue_scripts() {
		// Register styles.
		wp_register_style( 'rsfv-neve', $this->get_current_dir_url() . 'ThirdParty/Neve/styles.css', array(), filemtime( $this->get_current_dir() . 'ThirdParty/Neve/styles.css' ) );

		// Enqueue styles.
		wp_enqueue_style( 'rsfv-neve' );

		// Add generated CSS.
		wp_add_inline_style( 'rsfv-neve', Plugin::get_instance()->frontend_provider->generate_dynamic_css() );
	}
}
