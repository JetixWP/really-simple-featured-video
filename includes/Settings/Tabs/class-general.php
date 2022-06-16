<?php
/**
 * General Settings
 *
 * @package RSFV
 */

namespace RSFV\Settings;

use RSFV\Plugin;

defined( 'ABSPATH' ) || exit;

/**
 * General.
 */
class General extends Settings_Page {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id    = 'general';
		$this->label = __( 'General', 'rsfv' );

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

		$post_types = array(
			'post' => __( 'Posts' ),
			'page' => __( 'Pages' ),
		);

		if ( Plugin::is_woo_activated() ) {
			$post_types['product'] = __( 'Products' );
		}

		$settings = apply_filters(
			'rsfv_general_settings',
			array(
				array(
					'title' => esc_html_x( 'Enable Post Types Support', 'settings title', 'rsfv' ),
					'desc'  => __( 'Please select the post types you wish to enable featured video support at.', 'rsfv' ),
					'class' => 'rsfv-enable-post-types',
					'type'  => 'content',
					'id'    => 'rsfv-enable-post-types',
				),
				array(
					'type' => 'title',
					'id'   => 'rsfv_post_types_title',
				),
				array(
					'title'   => '',
					'id'      => 'post_types',
					'default' => false,
					'type'    => 'multi-checkbox',
					'options' => $post_types,
				),
				array(
					'type' => 'sectionend',
					'id'   => 'rsfv_post_types_title',
				),
			)
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

return new General();
