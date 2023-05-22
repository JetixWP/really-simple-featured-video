<?php
/**
 * Main plugin class.
 *
 * @package RSFV
 */

namespace RSFV;

use RSFV\Compatibility\Plugin_Provider;
use RSFV\Settings\Register;
use RSFV\Compatibility\Theme_Provider;

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
	 * Self Updater.
	 *
	 * @var $plugin_updater
	 */
	public $self_updater;

	/**
	 * Register instance.
	 *
	 * @var $registration_provider
	 */
	public $registration_provider;

	/**
	 * Metabox instance.
	 *
	 * @var $metabox_provider
	 */
	public $metabox_provider;

	/**
	 * Shortcode instance.
	 *
	 * @var $shortcode_provider
	 */
	public $shortcode_provider;

	/**
	 * Frontend instance.
	 *
	 * @var $frontend_provider
	 */
	public $frontend_provider;

	/**
	 * Plugin Compat Provide
	 *
	 * @var $plugin_provider
	 */
	public $plugin_provider;

	/**
	 * Theme Compat Provide
	 *
	 * @var $theme_provider
	 */
	public $theme_provider;

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
			/**
			 * RSFV loaded.
			 *
			 * Fires when RSFV is fully loaded and instantiated.
			 *
			 * @since 0.6.0
			 */
			do_action( 'rsfv_loaded' );
		}

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
		// Let's call these providers.
		$this->registration_provider = Register::get_instance();
		$this->metabox_provider      = Metabox::get_instance();
		$this->shortcode_provider    = Shortcode::get_instance();
		$this->frontend_provider     = FrontEnd::get_instance();

		// Load compatibility.
		$this->plugin_provider = Plugin_Provider::get_instance();
		$this->theme_provider  = Theme_Provider::get_instance();

		// Updates.
		$this->self_updater = new Updater();

		add_action( 'admin_init', array( $this->self_updater, 'init' ) );

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

		// Frontend loaders.
		require_once RSFV_PLUGIN_DIR . 'includes/class-shortcode.php';
		require_once RSFV_PLUGIN_DIR . 'includes/class-frontend.php';

		// Plugin compatibility.
		require_once RSFV_PLUGIN_DIR . 'includes/Compatibility/Plugins/class-base-compatibility.php';
		require_once RSFV_PLUGIN_DIR . 'includes/Compatibility/class-plugin-provider.php';

		// Theme compatibility.
		require_once RSFV_PLUGIN_DIR . 'includes/Compatibility/Themes/class-base-compatibility.php';
		require_once RSFV_PLUGIN_DIR . 'includes/Compatibility/class-theme-provider.php';

		// Database upgraders.
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
}
