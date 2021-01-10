<?php
/**
 * RSFV General Settings
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
				array(
					'type' => 'title',
					'id'   => 'rsfv_enable_controls',
				),
				array(
					'title' => __( 'Enable Video Controls', 'rsfv' ),
					'desc'  => __( 'If you want to enable video controls, please check this option.', 'rsfv' ),
					'id'    => 'video_controls',
					'type'  => 'checkbox',
				),
				array(
					'type' => 'sectionend',
					'id'   => 'rsfv_enable_controls',
				),
				array(
					'type' => 'title',
					'id'   => 'rsfv_enable_autoplay',
				),
				array(
					'title' => __( 'Enable Video Autoplay', 'rsfv' ),
					'desc'  => __( 'If you want to autoplay video on pageload please check this option.', 'rsfv' ),
					'id'    => 'video_autoplay',
					'type'  => 'checkbox',
				),
				array(
					'type' => 'sectionend',
					'id'   => 'rsfv_enable_autoplay',
				),
				array(
					'type' => 'title',
					'id'   => 'rsfv_enable_loop',
				),
				array(
					'title' => __( 'Enable Video Loop', 'rsfv' ),
					'desc'  => __( 'If you want to keep the video on loop please check this option.', 'rsfv' ),
					'id'    => 'video_loop',
					'type'  => 'checkbox',
				),
				array(
					'type' => 'sectionend',
					'id'   => 'rsfv_enable_loop',
				),
				array(
					'type' => 'title',
					'id'   => 'rsfv_picture_in_picture',
				),
				array(
					'title' => __( 'Enable Picture in Picture', 'rsfv' ),
					'desc'  => __( 'If you want to enable picture in picture feature, please check this option.', 'rsfv' ),
					'id'    => 'picture_in_picture',
					'type'  => 'checkbox',
				),
				array(
					'type' => 'sectionend',
					'id'   => 'rsfv_picture_in_picture',
				),
				array(
					'type' => 'title',
					'id'   => 'rsfv_disable_audio',
				),
				array(
					'title' => __( 'Mute video sound', 'rsfv' ),
					'desc'  => __( 'If you want to mute the audio in the video, please check this option.', 'rsfv' ),
					'id'    => 'mute_video',
					'type'  => 'checkbox',
				),
				array(
					'type' => 'sectionend',
					'id'   => 'rsfv_disable_audio',
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