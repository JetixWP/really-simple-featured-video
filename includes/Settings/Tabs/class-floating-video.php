<?php
/**
 * Floating Video Settings
 *
 * @package RSFV
 */

namespace RSFV\Settings;

defined( 'ABSPATH' ) || exit;

use RSFV\Plugin;

/**
 * Floating_Video_Settings.
 */
class Floating_Video_Settings extends Settings_Page {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id    = 'floating-video';
		$this->label = __( 'Floating Video', 'rsfv' );

		parent::__construct();
	}

	/**
	 * Get settings array.
	 *
	 * @param string $current_section Current section ID.
	 * @return array
	 */
	public function get_settings( $current_section = '' ) {

		$settings = array(
			array(
				'title' => esc_html_x( 'Floating Video Layout', 'settings title', 'rsfv' ),
				'desc'  => __( 'Manage the layout and presentation style for the floating video player.', 'rsfv' ),
				'type'  => 'content',
				'class' => 'rsfv-floating-video-layout',
				'id'    => 'rsfv-floating-video-layout',
			),
		);

		if ( ! Plugin::get_instance()->has_pro_active() ) {
			$settings = array_merge(
				$settings,
				array(
					array(
						'type' => 'title',
						'id'   => 'rsfv_promo_floating_video_layout_title',
					),
					array(
						'title'   => __( 'Layout Style', 'rsfv' ),
						'desc'    => __( '"Story/Status" shows Instagram-style story tab bars at the top of the popup for quick switching between multiple videos. Available in Pro.', 'rsfv' ),
						'id'      => 'promo_floating_video_layout',
						'default' => 'standard',
						'type'    => 'promo-select',
						'options' => array(
							'standard' => __( 'Standard', 'rsfv' ),
							'story'    => __( 'Story/Status', 'rsfv' ),
						),
					),
					array(
						'type' => 'sectionend',
						'id'   => 'rsfv_promo_floating_video_layout_title',
					),
				)
			);
		}

		return apply_filters( 'rsfv_get_settings_' . $this->id, $settings );
	}
}

return new Floating_Video_Settings();
