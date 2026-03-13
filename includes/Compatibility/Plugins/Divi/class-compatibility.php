<?php
/**
 * Divi compatibility handler.
 *
 * @package RSFV
 */

namespace RSFV\Compatibility\Plugins\Divi;

defined( 'ABSPATH' ) || exit;

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
	 * A counter variable.
	 *
	 * @var int $counter
	 */
	protected $counter;

	/**
	 * Constructor.
	 */
	public function __construct() {
		parent::__construct();

		$this->id = 'divi';

		$this->setup();
	}

	/**
	 * Sets up hooks and filters.
	 *
	 * @return void
	 */
	public function setup() {
		add_action( 'after_setup_theme', array( $this, 'run_extension' ), 9999999 );
	}

	/**
	 * Runs extension hooks.
	 *
	 * @return void
	 */
	public function run_extension() {
		if ( defined( 'ET_CORE' ) && class_exists( 'WooCommerce' ) ) {
			// Registers related settings tab.
			add_filter( 'rsfv_get_settings_pages', array( $this, 'register_settings' ) );
		}
	}

	/**
	 * Register Settings.
	 *
	 * @param array $settings Active settings file array.
	 *
	 * @return array
	 */
	public function register_settings( $settings ) {
		// Settings.
		$settings[] = include 'class-settings.php';

		return $settings;
	}
}
