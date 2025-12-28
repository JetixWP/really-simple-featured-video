<?php
/**
 * Upgrade Tab Settings
 *
 * @package RSFV
 */

namespace RSFV\Settings;

defined( 'ABSPATH' ) || exit;

if ( class_exists( '\RSFV_Pro\Plugin' ) ) {
	return;
}

/**
 * Upgrade.
 */
class Upgrade extends Settings_Page {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id    = 'upgrade';
		$this->label = __( 'Upgrade', 'rsfv' );

		parent::__construct();

		add_action( 'rsfv_settings_' . $this->id, array( $this, 'get_upgrade_template' ) );
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
			'rsfv_upgrade_settings',
			array()
		);

		return apply_filters( 'rsfv_get_settings_' . $this->id, $settings );
	}

	/**
	 * Get Upgrade Tab Data.
	 */
	public function get_upgrade_template() {
		include RSFV_PLUGIN_DIR . 'includes/Settings/Views/html-admin-settings-upgrade.php';
	}

	/**
	 * Save settings.
	 */
	public function save() {
	}
}

return new Upgrade();
