<?php
/**
 * Help Settings
 *
 * @package RSFV
 */

namespace RSFV\Settings;

defined( 'ABSPATH' ) || exit;

if ( class_exists( '\RSFV_Pro\Plugin' ) ) {
	return;
}

/**
 * Help.
 */
class Help extends Settings_Page {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id    = 'help';
		$this->label = __( 'Help', 'rsfv' );

		parent::__construct();

		add_action( 'rsfv_settings_' . $this->id, array( $this, 'get_help' ) );
	}

	/**
	 * Get settings array.
	 *
	 * @param string $current_section Current section ID.
	 *
	 * @return array
	 */
	public function get_settings( $current_section = '' ) {

		$settings = apply_filters(
			'rsfv_help_settings',
			array()
		);

		return apply_filters( 'rsfv_get_settings_' . $this->id, $settings );
	}

	/**
	 * Help Tab Data.
	 */
	public function get_help() {
		include RSFV_PLUGIN_DIR . 'includes/Settings/Views/html-admin-settings-help.php';
	}

	/**
	 * Save settings.
	 */
	public function save() {
	}
}

return new Help();
