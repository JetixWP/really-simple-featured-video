<?php
/**
 * Bricks's compatibility handler.
 *
 * @package RSFV
 */

namespace RSFV\Compatibility\Plugins\Bricks;

defined( 'ABSPATH' ) || exit;

use RSFV\Compatibility\Plugins\Base_Compatibility;
use RSFV\FrontEnd;
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

		$this->id = 'bricks';

		$this->setup();
	}

	/**
	 * Sets up hooks and filters.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function setup() {
		add_action(
			'after_setup_theme',
			function() {
				add_action(
					'init',
					function() {
						if ( ! class_exists( 'Bricks\Element' ) ) {
							return;
						}

						$element_files = array(
							__DIR__ . '/elements/really-simple-featured-video.php',
						);

						foreach ( $element_files as $file ) {
							\Bricks\Elements::register_element( $file );
						}
					}
				);
			}
		);
	}
}
