<?php
/**
 * Controls Settings
 *
 * @package RSFV
 */

namespace RSFV\Settings;

defined( 'ABSPATH' ) || exit;

use RSFV\Plugin;

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
	 * Get sections.
	 *
	 * @return array
	 */
	public function get_sections() {
		$sections = array(
			''               => __( 'Standard', 'rsfv' ),
			'hover-autoplay' => __( 'Autoplay on Hover', 'rsfv' ),
		);
		return apply_filters( 'rsfv_get_sections_' . $this->id, $sections );
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
			$autoplay_note = __( 'Note: Autoplay will only work if mute sound is enabled as per browser policy.', 'rsfv' );

			$control_options = array(
				'controls' => __( 'Controls', 'rsfv' ),
				'autoplay' => __( 'Autoplay', 'rsfv' ),
				'loop'     => __( 'Loop', 'rsfv' ),
				'pip'      => __( 'Picture in Picture', 'rsfv' ),
				'mute'     => __( 'Mute sound', 'rsfv' ),
			);

			$self_control_options             = $control_options;
			$self_control_options['download'] = __( 'Download', 'rsfv' );

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
						'options' => $self_control_options,
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
		} elseif ( 'hover-autoplay' === $current_section ) {

			$settings = array(
				array(
					'title' => __( 'Autoplay on Hover Controls', 'rsfv' ),
					'desc'  => sprintf(
						'%1$s',
						__( 'Below you can manage the autoplay on hover controls. Any existing values in a disabled Style Kit panel will lose its values.', 'rsfv' ),
					),
					'type'  => 'content',
					'id'    => 'rsfv-pro-hover-autoplay-controls',
				),
				array(
					'type' => 'title',
					'id'   => 'rsfv_pro_hover_autoplay',
				),
				array(
					'title'   => __( 'Enable Feature', 'rsfv' ),
					'id'      => 'enable_hover_autoplay',
					'default' => false,
					'type'    => 'checkbox',
				),
				array(
					'type' => 'sectionend',
					'id'   => 'rsfv_pro_hover_autoplay',
				),
				array(
					'title' => esc_html_x( 'Video Types', 'settings title', 'rsfv' ),
					'desc'  => __( 'Please toggle the video types you wish to enable/disable autoplay on hover support at.', 'rsfv' ),
					'type'  => 'content',
					'class' => 'rsfv-multi-checkbox-card',
					'id'    => 'rsfv-pro-hover-autoplay-video-types',
				),
				array(
					'type' => 'title',
					'id'   => 'rsfv_pro_hover_autoplay_video_types_title',
				),
				array(
					'title'   => '',
					'id'      => 'hover_autoplay_video_types',
					'default' => array(
						'html5'       => true,
						'youtube'     => true,
						'vimeo'       => true,
						'dailymotion' => true,
					),
					'type'    => 'multi-checkbox',
					'options' => array(
						'html5'       => __( 'Self Hosted', 'rsfv' ),
						'youtube'     => __( 'YouTube', 'rsfv' ),
						'vimeo'       => __( 'Vimeo', 'rsfv' ),
						'dailymotion' => __( 'Dailymotion', 'rsfv' ),
					),
				),
				array(
					'type' => 'sectionend',
					'id'   => 'rsfv_pro_hover_autoplay_video_types_title',
				),
			);

			if ( ! Plugin::get_instance()->has_pro_active() ) {
				$settings = array_merge(
					$settings,
					array(
						array(
							'title' => esc_html_x( 'Screen Sizes', 'settings title', 'rsfv' ),
							'desc'  => __( 'Toggle the screen sizes you wish to enable/disable autoplay on hover support. Available in Pro.', 'rsfv' ),
							'type'  => 'promo-content',
							'class' => 'rsfv-promo-multi-checkbox-card',
							'id'    => 'promo-rsfv-pro-hover-autoplay-screens',
						),
						array(
							'type' => 'title',
							'id'   => 'promo_hover_autoplay_screens_title',
						),
						array(
							'title'   => '',
							'id'      => 'promo-hover-autoplay-screens',
							'default' => array(
								'desktop' => true,
								'mobile'  => true,
							),
							'type'    => 'promo-multi-checkbox',
							'options' => array(
								'desktop' => __( 'Desktop', 'rsfv' ),
								'mobile'  => __( 'Mobile', 'rsfv' ),
							),
						),
						array(
							'type' => 'sectionend',
							'id'   => 'promo_hover_autoplay_screens_title',
						),
						array(
							'type' => 'title',
							'id'   => 'promo_hover_autoplay_extras',
						),
						array(
							'title'   => __( 'Set Mobile Breakpoint (px)', 'rsfv' ),
							'desc'    => __( 'Screen width below which device is considered mobile. Default: 768px', 'rsfv' ),
							'id'      => 'promo-hover-autoplay-mobile-breakpoint',
							'default' => 768,
							'type'    => 'promo-number',
						),
						array(
							'title'   => __( 'Set Hover Delay (ms)', 'rsfv' ),
							'desc'    => __( 'Delay before video starts playing on hover. Default: 100ms', 'rsfv' ),
							'id'      => 'promo-hover-autoplay-delay',
							'default' => 100,
							'type'    => 'promo-number',
						),
						array(
							'type' => 'sectionend',
							'id'   => 'promo_hover_autoplay_extras',
						),
						array(
							'title' => __( 'Accessibility', 'rsfv' ),
							'desc'  => __( 'Toggle the accessibility features at autoplay on hover. Available in Pro.', 'rsfv' ),
							'type'  => 'promo-content',
							'id'    => 'promo-hover-autoplay-accessibility',
							'class' => 'promo-hover-autoplay-accessibility',
						),
						array(
							'type' => 'title',
							'id'   => 'promo_hover_autoplay_extras',
						),
						array(
							'title'   => __( 'User Preferences', 'rsfv' ),
							'desc'    => __( 'Respect "reduced motion" preference.', 'rsfv' ),
							'id'      => 'promo-hover-autoplay-respect-user-prefs',
							'default' => true,
							'type'    => 'promo-checkbox',
						),
						array(
							'title'   => __( 'Focus Events', 'rsfv' ),
							'desc'    => __( 'Enable focus events for keyboard navigation.', 'rsfv' ),
							'id'      => 'promo-hover-autoplay-focus-events',
							'default' => true,
							'type'    => 'promo-checkbox',
						),
						array(
							'type' => 'sectionend',
							'id'   => 'promo_hover_autoplay_accessibility',
						),
					)
				);
			}

			$settings = apply_filters(
				'rsfv_controls_hover_autoplay_settings',
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

return new Controls();
