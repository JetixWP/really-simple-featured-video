<?php
namespace RSFV;

use RSFV\Settings\Register;

/**
 * Class RSFV_featured_video
 */
final class Plugin {
	/**
	 * Class instance.
	 *
	 * @var $instance
	 */
	protected static $instance;

	/**
	 * Plugin constructor.
	 */
	public function __construct() {
		$this->define_constants();
		$this->includes();
		$this->register();
	}

	/**
	 * Get a class instance.
	 *
	 * @return Plugin
	 */
	public static function get_instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		do_action( 'rsfv_loaded' );
		return self::$instance;
	}

	/**
	 * Defines constants.
	 *
	 * @retun void
	 */
	public function define_constants() {
		define( 'RSFV_META_KEY', 'rsfv_featured_video' );
	}

	/**
	 * Registers plugin classes & translation.
	 *
	 * @return void
	 */
	public function register() {
		// Load translation.
		add_action( 'plugins_loaded', array( $this, 'load_plugin_textdomain' ) );

		// Load classes.
		Register::get_instance();
		Metabox::get_instance();
		Shortcode::get_instance();
		FrontEnd::get_instance();
	}

	/**
	 *
	 * Load translation domain & files.
	 *
	 * @return void
	 */
	public function load_plugin_textdomain() {
		load_plugin_textdomain( 'rsfv', '', dirname( RSFV_PLUGIN_BASE ) . '/languages/' );
	}

	/**
	 * Include plugin files.
	 *
	 * @return void
	 */
	public function includes() {
		require_once RSFV_PLUGIN_DIR . 'includes/Options.php';
		require_once RSFV_PLUGIN_DIR . 'includes/Settings/Register.php';
		require_once RSFV_PLUGIN_DIR . 'includes/Metabox.php';
		require_once RSFV_PLUGIN_DIR . 'includes/Shortcode.php';
		require_once RSFV_PLUGIN_DIR . 'includes/FrontEnd.php';
	}

	/**
	 * Checks if WooCommerce is activated.
	 *
	 * @return bool
	 */
	public static function is_woo_activated() {
		return class_exists( 'WooCommerce' ) ? true : false;
	}
}
