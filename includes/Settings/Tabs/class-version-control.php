<?php
/**
 * Version Control Settings
 *
 * @package RSFV
 */

namespace RSFV\Settings;

use RSFV\Featuresets\Rollback\Init as Rollback;

defined( 'ABSPATH' ) || exit;

/**
 * Version_Control_Settings.
 */
class Version_Control_Settings extends Settings_Page {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id    = 'version_control';
		$this->label = __( 'Version Control', 'rsfv' );

		parent::__construct();
	}

	/**
	 * Get settings array.
	 *
	 * @param string $current_section Current section ID.
	 *
	 * @return array
	 */
	public function get_settings( $current_section = '' ) {

		$rollback_controls = array();

		if ( current_user_can( 'update_plugins' ) ) {
			array_push(
				$rollback_controls,
				array(
					'title' => __( 'Rollback Versions', 'rsfv' ),
					'desc'  => __( 'If you are having issues with current version of Really Simple Featured Video, you can rollback to a previous stable version.', 'rsfv' ),
					'type'  => 'title',
					'id'    => 'rsfv_plugin_rollback_version',
				),
				array(
					'title'     => __( 'Rollback RSFV', 'rsfv' ),
					'id'        => 'rsfv_rollback_version_select_option',
					'type'      => 'select',
					'class'     => 'rsfv-enhanced-select',
					'desc_tip'  => true,
					'options'   => $this->get_rollback_versions(),
					'is_option' => false,
				),
				array(
					'id'    => 'rsfv_rollback_version_button',
					'type'  => 'button',
					'class' => 'rsfv-rollback-version-button rsfv-button button-secondary',
					'value' => __( 'Reinstall this version', 'rsfv' ),
				),
				array(
					'type' => 'sectionend',
					'id'   => 'rsfv_plugin_rollback',
				)
			);
		}

		$settings = apply_filters(
			'rsfv_version_control_settings',
			$rollback_controls
		);

		return apply_filters( 'rsfv_get_settings_' . $this->id, $settings );
	}

	/**
	 * Get recent rollback versions in key/value pair.
	 *
	 * @return array
	 */
	public function get_rollback_versions() {
		$keys = Rollback::get_rollback_versions();
		$data = array();
		foreach ( $keys as $key => $value ) {
			$data[ $value ] = $value;
		}

		return $data;
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

return new Version_Control_Settings();
