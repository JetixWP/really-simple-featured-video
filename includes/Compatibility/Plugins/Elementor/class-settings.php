<?php
/**
 * Elementor Settings
 *
 * @package RSFV
 */

namespace RSFV\Compatibility\Plugins\Elementor;

use RSFV\Settings\Settings_Page;
use RSFV\Settings\Admin_Settings;

defined( 'ABSPATH' ) || exit;

/**
 * Integrations controls.
 */
class Settings extends Settings_Page {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id    = 'elementor';
		$this->label = __( 'Elementor', 'rsfv' );

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
			$settings = array(
				array(
					'type' => 'title',
					'id'   => 'rsfv_elementor_title',
				),
				array(
					'title'   => __( 'Disable Elementor Support', 'rsfv' ),
					'desc'    => __( 'Toggle this on if in Elementor you see the site logo, footer logo or any other part of the site images getting replaced with featured video.', 'rsfv' ),
					'id'      => 'disable_elementor_support',
					'default' => false,
					'type'    => 'checkbox',
				),
				array(
					'type' => 'sectionend',
					'id'   => 'rsfv_elementor_title',
				),
			);

			$settings = apply_filters(
				'rsfv_' . $this->id . '_settings',
				$settings
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

return new Settings();
