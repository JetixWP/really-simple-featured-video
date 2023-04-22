<?php
/**
 * Integrations Settings
 *
 * @package RSFV
 */

namespace RSFV\Settings;

defined( 'ABSPATH' ) || exit;

/**
 * Integrations controls.
 */
class Integrations extends Settings_Page {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id    = 'integrations';
		$this->label = __( 'Integrations', 'rsfv' );

		parent::__construct();
	}

	/**
	 * Get sections.
	 *
	 * @return array
	 */
	public function get_sections() {
		return apply_filters( 'rsfv_get_sections_' . $this->id, array() );
	}

	/**
	 * Get settings array.
	 *
	 * @param string $current_section Current section ID.
	 *
	 * @return array
	 */
	public function get_settings( $current_section = '' ) {
		global $current_section;

		$settings = array();

		if ( '' === $current_section ) {

			$settings = apply_filters(
				'rsfv_' . $this->id . '_settings',
				array()
			);
		}

		return apply_filters( 'rsfv_get_settings_' . $this->id, $settings );
	}

	/**
	 * Save settings.
	 */
	public function save() {
		global $current_section;

		$settings = $this->get_settings( $current_section );

		Admin_Settings::save_fields( $settings );
		if ( $current_section ) {
			do_action( 'rsfv_update_options_' . $this->id . '_' . $current_section );
		}
	}
}

return new Integrations();
