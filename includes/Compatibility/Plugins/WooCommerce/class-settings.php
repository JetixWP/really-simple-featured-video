<?php
/**
 * WooCommerce integration settings.
 *
 * @package RSFV
 */

namespace RSFV\Compatibility\Plugins\WooCommerce;

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( '\RSFV\Settings\Admin_Settings' ) && ! class_exists( '\RSFV\Settings\Integrations' ) ) {
	return;
}

/**
 * WooCommerce Subtab Settings.
 */
class Settings {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_filter( 'rsfv_get_sections_integrations', array( $this, 'update_sections' ) );
		add_filter( 'rsfv_get_settings_integrations', array( $this, 'get_settings' ) );
	}

	/**
	 * Update sections array.
	 *
	 * @param array $sections Sections array data.
	 * @return array
	 */
	public function update_sections( $sections ) {
		$sections['woocommerce'] = __( 'WooCommerce', 'rsfv' );
		return $sections;
	}

	/**
	 * Get settings array.
	 *
	 * @param array $settings Settings.
	 * @return array
	 */
	public function get_settings( $settings = array() ) {
		global $current_section;

		if ( 'woocommerce' === $current_section ) {
			$settings = apply_filters(
				'rsfv_get_integration_woocommerce_settings',
				array(
					array(
						'type' => 'title',
						'id'   => 'rsfv_woocommerce_title',
					),
					array(
						'title'   => __( 'Show videos at Product archives', 'rsfv' ),
						'desc'    => __( 'When toggled on, it shows set videos at product archives such as Shop and Product category etc.', 'rsfv' ),
						'id'      => 'product_archives_visibility',
						'default' => true,
						'type'    => 'checkbox',
					),
					array(
						'type' => 'sectionend',
						'id'   => 'rsfv_woocommerce_title',
					),
				)
			);
		}
		return $settings;
	}
}
