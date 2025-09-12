<?php
/**
 * Register and initialize all Featuresets for RSFV.
 *
 * @package RSFV
 */

namespace RSFV\Featuresets;


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
		do_action( 'rsfv_featuresets_initialize' );
	}
}
