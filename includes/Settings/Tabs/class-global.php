<?php
/**
 * Global Settings
 *
 * @package RSFV
 */

namespace RSFV\Settings;

use RSFV\Plugin;

defined( 'ABSPATH' ) || exit;

/**
 * Global_Settings.
 */
class Global_Settings extends Settings_Page {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id    = 'global';
		$this->label = __( 'Global', 'rsfv' );

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

		$settings = array(
			array(
				'title' => esc_html_x( 'Blogs & Archives', 'settings title', 'rsfv' ),
				'desc'  => '',
				'class' => 'rsfv-blog-archives-title',
				'type'  => 'content',
				'id'    => 'rsfv-blog-archives-title',
			),
			array(
				'type' => 'title',
				'id'   => 'rsfv_archives_visibilitiy',
			),
			array(
				'title'   => __( 'Show videos at Blog, Category and Tag archives', 'rsfv' ),
				'desc'    => __( 'When toggled on, it shows set videos at blog home and archives such as category, tag archives etc.', 'rsfv' ),
				'id'      => 'blog_archives_visibility',
				'default' => true,
				'type'    => 'checkbox',
			),
			array(
				'title'   => __( 'Show videos at Blog single posts', 'rsfv' ),
				'desc'    => __( 'When toggled on, it shows set videos at blog single posts.', 'rsfv' ),
				'id'      => 'blog_single_visibility',
				'default' => true,
				'type'    => 'checkbox',
			),
			array(
				'type' => 'sectionend',
				'id'   => 'rsfv_archives_visibilitiy',
			),
		);

		if ( ! Plugin::get_instance()->has_pro_active() ) {
			$settings = array_merge(
				$settings,
				array(
					array(
						'title' => esc_html_x( 'Global Aspect Ratio', 'settings title', 'rsfv' ),
						'desc'  => __( 'Set aspect ratio for featured videos shown sitewide.', 'rsfv' ),
						'class' => 'promo-aspect-ratios',
						'type'  => 'promo-content',
						'id'    => 'promo-aspect-ratios',
					),
					array(
						'type' => 'title',
						'id'   => 'rsfv_pro_aspect_ratio_title',
					),
					array(
						'title'   => __( 'Video Aspect Ratio', 'rsfv' ),
						'desc'    => __( 'Available in the Pro version.', 'rsfv' ),
						'id'      => 'promo-global-aspect-ratio',
						'default' => 'sixteen-nine',
						'type'    => 'promo-select',
						'options' => array(
							'custom'       => __( 'Custom', 'rsfv' ),
							'sixteen-nine' => '16/9 (Default)',
							'one-one'      => '1/1',
							'three-two'    => '3/2',
							'four-three'   => '4/3',
						),
					),
					array(
						'type' => 'sectionend',
						'id'   => 'rsfv_pro_aspect_ratio_title',
					),
				)
			);
		}

		$settings = apply_filters(
			'rsfv_global_settings',
			$settings
		);

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

return new Global_Settings();
