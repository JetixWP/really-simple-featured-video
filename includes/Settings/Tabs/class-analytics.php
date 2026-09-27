<?php
/**
 * Analytics settings.
 *
 * @package RSFV
 * @since   0.90.0
 */

namespace RSFV\Settings;

use RSFV\Plugin;

defined( 'ABSPATH' ) || exit;

/**
 * Analytics tab on the Featured Video screen.
 */
class Analytics extends Settings_Page {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id    = 'analytics';
		$this->label = __( 'Analytics', 'rsfv' );

		parent::__construct();
	}

	/**
	 * Settings fields.
	 *
	 * @param string $current_section Current section ID.
	 * @return array
	 */
	public function get_settings( $current_section = '' ) {
		unset( $current_section );

		$report_url = admin_url( 'admin.php?page=rsfv-tools#analytics' );

		$settings = array(
			array(
				'title' => esc_html_x( 'Video analytics', 'settings title', 'rsfv' ),
				'desc'  => sprintf(
					/* translators: %s: URL of the analytics report. */
					__( 'Counts are anonymous and stay on this site. No cookie is set. The report is in <a href="%s">Video Tools</a>.', 'rsfv' ),
					esc_url( $report_url )
				),
				'type'  => 'content',
				'id'    => 'rsfv-analytics-intro',
			),
			array(
				'type' => 'title',
				'id'   => 'rsfv_analytics_options',
			),
			array(
				'title'   => __( 'Enable analytics', 'rsfv' ),
				'desc'    => __( 'Record views and plays for videos added with this plugin.', 'rsfv' ),
				'id'      => 'analytics_enabled',
				'default' => true,
				'type'    => 'checkbox',
			),
			array(
				'title'   => __( 'Skip logged-in editors', 'rsfv' ),
				'desc'    => __( 'Visits from people who can edit posts are left out, so your own previews do not fill the report.', 'rsfv' ),
				'id'      => 'analytics_ignore_editors',
				'default' => true,
				'type'    => 'checkbox',
			),
			array(
				'type' => 'sectionend',
				'id'   => 'rsfv_analytics_options',
			),
		);

		if ( ! Plugin::get_instance()->has_pro_active() ) {
			$settings = array_merge(
				$settings,
				array(
					array(
						'type' => 'title',
						'id'   => 'rsfv_promo_analytics_title',
					),
					array(
						'title'     => __( 'Keep counts for', 'rsfv' ),
						'desc'      => __( 'Free keeps the last 14 days. A longer history recording is a Pro setting.', 'rsfv' ),
						'id'        => 'promo-analytics-retention',
						'default'   => '14',
						'type'      => 'promo-select',
						'disabled'  => true,
						'is_option' => false,
						'options'   => array(
							'14'  => __( '14 days', 'rsfv' ),
							'30'  => __( '30 days', 'rsfv' ),
							'90'  => __( '90 days', 'rsfv' ),
							'365' => __( '365 days', 'rsfv' ),
							'all' => __( 'All time', 'rsfv' ),
						),
					),
					array(
						'title'     => __( 'Country, device, and referrer', 'rsfv' ),
						'desc'      => __( 'Pro can record a country when your host sends one, a general device type, and the referring site name.', 'rsfv' ),
						'id'        => 'promo-analytics-audience',
						'type'      => 'promo-checkbox',
						'disabled'  => true,
						'is_option' => false,
					),
					array(
						'type' => 'sectionend',
						'id'   => 'rsfv_promo_analytics_title',
					),
				)
			);
		}

		return apply_filters( 'rsfv_get_settings_' . $this->id, $settings );
	}
}

return new Analytics();
