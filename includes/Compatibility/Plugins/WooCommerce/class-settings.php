<?php
/**
 * WooCommerce Settings
 *
 * @package RSFV
 */

namespace RSFV\Compatibility\Plugins\WooCommerce;

use RSFV\Plugin;
use RSFV\Settings\Settings_Page;
use RSFV\Settings\Admin_Settings;

if ( ! class_exists( '\RSFV\Settings\Admin_Settings' ) ) {
	return;
}

defined( 'ABSPATH' ) || exit;

/**
 * Integrations controls.
 */
class Settings extends Settings_Page {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id    = 'woocommerce';
		$this->label = __( 'WooCommerce', 'rsfv' );

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
			);

			if ( ! Plugin::get_instance()->has_pro_active() ) {
				$settings = array_merge(
					$settings,
					array(
						array(
							'type' => 'title',
							'id'   => 'rsfv_pro_woocommerce',
						),
						array(
							'title'   => __( 'Featured Video Order', 'rsfv' ),
							'desc'    => __( 'Set video order on single product page/gallery, available in the Pro version.', 'rsfv' ),
							'id'      => 'promo-global-woo-video-order',
							'default' => 'first',
							'type'    => 'promo-select',
							'options' => array(
								'first'  => __( 'First (Default)', 'rsfv' ),
								'second' => __( 'Second', 'rsfv' ),
								'last'   => __( 'Last', 'rsfv' ),
							),
						),
						array(
							'type' => 'sectionend',
							'id'   => 'rsfv_pro_woocommerce',
						),
						array(
							'type' => 'title',
							'id'   => 'rsfv_pro_change_default_gallery_thumb',
						),
						array(
							'title'   => __( 'Default Gallery Thumb', 'rsfv' ),
							'desc'    => __( 'Set default product gallery thumb on single product page.', 'rsfv' ),
							'id'      => 'promo-default-woo-gallery-video-thumb',
							'default' => RSFV_PLUGIN_URL . 'assets/images/video_frame.png',
							'type'    => 'promo-media-image',
						),
						array(
							'type' => 'sectionend',
							'id'   => 'rsfv_pro_change_default_gallery_thumb',
						),
					)
				);
			}

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
