<?php
/**
 * Tools handler.
 *
 * @package RSFV
 */

namespace RSFV\Tools;

/**
 * Register Tools.
 */
class Register {
	/**
	 * Class instance
	 *
	 * @var $instance
	 */
	protected static $instance;

	/**
	 * REST API instance.
	 *
	 * @var REST_API
	 */
	public $rest_api;

	/**
	 * Bulk import instance.
	 *
	 * @var Bulk_Import
	 */
	public $bulk_import;

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
	 * Class constructor.
	 */
	public function __construct() {
		// Include required files.
		$this->includes();

		// Initialize REST API.
		$this->rest_api    = REST_API::get_instance();
		$this->bulk_import = Bulk_Import::get_instance();

		add_action( 'rsfv_register_admin_menus', array( $this, 'register_menu_page' ) );
	}

	/**
	 * Include required files.
	 */
	protected function includes() {
		require_once RSFV_PLUGIN_DIR . 'includes/Tools/class-admin-tools.php';
		require_once RSFV_PLUGIN_DIR . 'includes/Tools/class-rest-api.php';
		require_once RSFV_PLUGIN_DIR . 'includes/Tools/class-bulk-import.php';
	}

	/**
	 * Register bulk actions page.
	 *
	 * @param string $primary_slug Primary menu slug.
	 *
	 * @return void
	 */
	public function register_menu_page( $primary_slug ) {
		add_submenu_page(
			$primary_slug,
			__( 'RSFV Tools', 'rsfv' ),
			__( '&nbsp;↳ Video Tools', 'rsfv' ),
			'manage_options',
			'rsfv-tools',
			array( $this, 'render_tools_page' ),
			RSFV_PLUGIN_DEFAULT_PRIORITY
		);
	}

	/**
	 * Render Bulk Actions page.
	 */
	public function render_tools_page() {
		Admin_Tools::output();
	}
}
