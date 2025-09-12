<?php
/**
 * Register and initialize all Featuresets for RSFV.
 *
 * @package RSFV
 */

namespace RSFV\Featuresets;

use RSFV\Featuresets\Hover_Autoplay\Init as Hover_Autoplay_Init;

defined( 'ABSPATH' ) || exit;

/**
 * Class Register_Featuresets
 */
class Register_Featuresets {
	/**
	 * Class instance.
	 *
	 * @var $instance
	 */
	protected static $instance;

	/**
	 * Get a class instance.
	 *
	 * @return Init
	 */
	public static function get_instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->init_featuresets();
	}

	/**
	 * Initialize all Featuresets.
	 */
	public function init_featuresets() {
		// Hover Autoplay Featuresets.
		require_once __DIR__ . '/hover-autoplay/class-init.php';
		require_once __DIR__ . '/hover-autoplay/class-utils.php';

		if ( class_exists( '\RSFV\Featuresets\Hover_Autoplay\Init' ) ) {
			// Initialize Hover Autoplay Featureset.
			Hover_Autoplay_Init::get_instance();
		}

		do_action( 'rsfv_featuresets_initialize' );
	}
}
