<?php
namespace RSFV\Settings;

/**
 * Register Settings.
 */
class Register {
	/**
	 * Class instance
	 *
	 * @var $instance
	 */
	protected static $instance;

	/**
	 * Class constructor.
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'register_menu' ) );
	}

	/**
	 * Get a class instance.
	 *
	 * @return Register
	 */
	public static function get_instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Register plugin menu.
	 *
	 * @return void
	 */
	public function register_menu() {

		add_submenu_page(
			'options-general.php',
			__( 'Really Simple Featured Video Settings', 'rsfv' ),
			__( 'Really Simple Featured Video', 'rsfv' ),
			'manage_options',
			'rsfv-settings',
			array( $this, 'settings_page' ),
		);

	}

	/**
	 * Add settings page.
	 *
	 * @return void
	 */
	public function settings_page() {
		// TODO: Add a settigs page.
	}

}