<?php
/**
 * Twenty Twenty One theme compatibility handler.
 *
 * @package RSFV
 */

namespace RSFV\Compatibility\Themes\Core\Twentytwenty_One;

use RSFV\Compatibility\Themes\Base_Compatibility;

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
	 *
	 * @param string $id Compat ID.
	 * @param string $title Compat title.
	 */
	public function __construct( $id, $title ) {
		parent::__construct( $id, $title );

		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
	}

	/**
	 * Enqueue scripts.
	 *
	 * @return void
	 */
	public function enqueue_scripts() {
		// Register styles.
		wp_register_style( 'rsfv-twentytwentyone', $this->get_current_dir_url() . 'Core/Twentytwenty_One/styles.css', array(), filemtime( $this->get_current_dir() . 'Core/Twentytwenty_One/styles.css' ) );

		// Enqueue styles.
		wp_enqueue_style( 'rsfv-twentytwentyone' );
	}
}
