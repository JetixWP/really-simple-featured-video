<?php
/**
 * Get Pro Settings
 *
 * @package RSFV
 */

namespace RSFV\Settings;

defined( 'ABSPATH' ) || exit;

if ( class_exists( '\RSFV_Pro\Plugin' ) ) {
	return;
}

/**
 * GetPro.
 */
class GetPro extends Settings_Page {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id    = 'getpro';
		$this->label = __( 'Get PRO', 'rsfv' );

		parent::__construct();

		add_action( 'rsfv_settings_' . $this->id, array( $this, 'get_pro' ) );
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
			'rsfv_getpro_settings',
			array()
		);

		return apply_filters( 'rsfv_get_settings_' . $this->id, $settings );
	}

	/**
	 * Get Pro Tab Data.
	 */
	public function get_pro() {
		include RSFV_PLUGIN_DIR . 'includes/Settings/Views/html-admin-settings-getpro.php';
	}

	/**
	 * Save settings.
	 */
	public function save() {
	}
}

return new GetPro();
