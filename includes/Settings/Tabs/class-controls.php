<?php
/**
 * Controls Settings
 *
 * @package RSFV
 */

namespace RSFV\Settings;

defined( 'ABSPATH' ) || exit;

/**
 * Video frame controls.
 */
class Controls extends Settings_Page {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id    = 'controls';
		$this->label = __( 'Controls', 'rsfv' );

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
		$autoplay_note = __( 'Note: Autoplay will only work if mute sound is enabled as per browser policy.', 'rsfv' );

		$control_options = array(
			'controls' => __( 'Controls', 'rsfv' ),
			'autoplay' => __( 'Autoplay', 'rsfv' ),
			'loop'     => __( 'Loop', 'rsfv' ),
			'pip'      => __( 'Picture in Picture', 'rsfv' ),
			'mute'     => __( 'Mute sound', 'rsfv' ),
		);

		$default_controls = get_default_video_controls();

		$settings = apply_filters(
			'rsfv_controls_settings',
			array(
				array(
					'title' => esc_html_x( 'Self-hosted videos', 'settings title', 'rsfv' ),
					'desc'  => __( 'Please select the controls you wish to enable for your self hosted videos.', 'rsfv' ),
					'class' => 'rsfv-self-video-controls',
					'type'  => 'content',
					'id'    => 'rsfv-self-video-controls',
				),
				array(
					'type' => 'title',
					'id'   => 'rsfv_self_video_controls_title',
				),
				array(
					'title'   => '',
					'desc'    => $autoplay_note,
					'id'      => 'self_video_controls',
					'default' => $default_controls,
					'type'    => 'multi-checkbox',
					'options' => $control_options,
				),
				array(
					'type' => 'sectionend',
					'id'   => 'rsfv_self_video_controls_title',
				),
				array(
					'title' => esc_html_x( 'Embed videos', 'settings title', 'rsfv' ),
					'desc'  => __( 'Please select the controls you wish to enable for your embedded videos.', 'rsfv' ),
					'class' => 'rsfv-embed-video-controls',
					'type'  => 'content',
					'id'    => 'rsfv-embed-video-controls',
				),
				array(
					'type' => 'title',
					'id'   => 'rsfv_self_embed_controls_title',
				),
				array(
					'title'   => '',
					'desc'    => $autoplay_note,
					'id'      => 'embed_video_controls',
					'default' => $default_controls,
					'type'    => 'multi-checkbox',
					'options' => $control_options,
				),
				array(
					'type' => 'sectionend',
					'id'   => 'rsfv_embed_video_controls_title',
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

return new Controls();
