<?php
/**
 * Register and initialize all Featuresets for RSFV.
 *
 * @package RSFV
 */

namespace RSFV\Featuresets;

use RSFV\Featuresets\Hover_Autoplay\Init as Hover_Autoplay_Init;
use RSFV\Featuresets\Floating_Video;

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
		// Hover Autoplay.
		require_once __DIR__ . '/hover-autoplay/class-utils.php';
		require_once __DIR__ . '/hover-autoplay/class-init.php';

		// Rollback.
		require_once __DIR__ . '/rollback/class-rollbacker.php';
		require_once __DIR__ . '/rollback/class-init.php';

		// Floating Video.
		require_once __DIR__ . '/floating-video/class-rest-api.php';
		require_once __DIR__ . '/floating-video/class-init.php';
		Floating_Video\Init::get_instance();

		do_action( 'rsfv_after_featuresets_initialize' );
	}
}
