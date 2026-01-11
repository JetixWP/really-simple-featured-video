<?php
/**
 * Bulk Actions handler.
 *
 * @package RSFV
 */

namespace RSFV\BulkActions;

use RSFV\Options;

/**
 * Register Bulk Actions.
 */
class Register {
	/**
	 * Class instance
	 *
	 * @var $instance
	 */
	protected static $instance;

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

		add_action( 'rsfv_register_admin_menus', array( $this, 'register_menu_page' ) );
	}

	/**
	 * Include required files.
	 */
	protected function includes() {
		require_once RSFV_PLUGIN_DIR . 'includes/BulkActions/class-admin-bulk-actions.php';
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
			__( 'RSFV Bulk Actions', 'rsfv' ),
			__( '&nbsp;↳ Bulk Actions', 'rsfv' ),
			'manage_options',
			'rsfv-bulk-actions',
			array( $this, 'render_bulk_actions_page' ),
			RSFV_PLUGIN_DEFAULT_PRIORITY
		);
	}

	/**
	 * Render Bulk Actions page.
	 */
	public function render_bulk_actions_page() {
		Admin_Bulk_Actions::output();
	}
}
