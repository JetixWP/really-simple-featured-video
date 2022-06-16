<?php
/**
 * Main plugin class.
 *
 * @package RSFV
 */

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
	 * Plugin Updater.
	 *
	 * @var $plugin_updater
	 */
	public $plugin_updater;

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
		define( 'RSFV_SOURCE_META_KEY', 'rsfv_source' );
		define( 'RSFV_META_KEY', 'rsfv_featured_video' );
		define( 'RSFV_EMBED_META_KEY', 'rsfv_featured_embed_video' );
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

		// Updates.
		$this->plugin_updater = new Updater();
		add_action( 'admin_init', array( $this->plugin_updater, 'init' ) );

		// Register action links.
		add_filter( 'network_admin_plugin_action_links_really-simple-featured-video/really-simple-featured-video.php', array( $this, 'filter_plugin_action_links' ) );
		add_filter( 'plugin_action_links_really-simple-featured-video/really-simple-featured-video.php', array( $this, 'filter_plugin_action_links' ) );

	}

	/**
	 *
	 * Load translation domain & files.
	 *
	 * @return void
	 */
	public function load_plugin_textdomain() {
		load_plugin_textdomain( 'rsfv', false, dirname( RSFV_PLUGIN_BASE ) . '/languages/' );
	}

	/**
	 * Include plugin files.
	 *
	 * @return void
	 */
	public function includes() {
		require_once RSFV_PLUGIN_DIR . 'includes/class-options.php';
		require_once RSFV_PLUGIN_DIR . 'includes/Settings/class-register.php';
		require_once RSFV_PLUGIN_DIR . 'includes/class-metabox.php';
		require_once RSFV_PLUGIN_DIR . 'includes/class-shortcode.php';
		require_once RSFV_PLUGIN_DIR . 'includes/class-frontend.php';
		require_once RSFV_PLUGIN_DIR . 'includes/class-updater.php';
	}

	/**
	 * Add settings link at plugins page action links.
	 *
	 * @param array $actions Action links.
	 *
	 * @return array
	 */
	public function filter_plugin_action_links( array $actions ) {
		$settings_url = admin_url( 'options-general.php?page=rsfv-settings' );

		return array_merge(
			array(
				'settings' => "<a href='{$settings_url}'>" . esc_html__( 'Settings', 'rsfv' ) . '</a>',
			),
			$actions
		);
	}

	/**
	 * Checks if WooCommerce is activated.
	 *
	 * @return bool
	 */
	public static function is_woo_activated() {
		return class_exists( 'WooCommerce' );
	}
}
