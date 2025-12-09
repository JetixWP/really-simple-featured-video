<?php
/**
 * General Settings
 *
 * @package RSFV
 */

namespace RSFV\Settings;

use RSFV\Options;
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

		add_action( 'wp_ajax_rsfv_current_theme_compat', array( $this, 'get_current_theme_compat_ajax' ) );
	}

	/**
	 * Gets available post types.
	 *
	 * @return mixed|array
	 */
	public function get_available_post_types() {
		$post_types                        = array();
		$all_post_types                    = \get_post_types();
		$post_types_with_thumbnail_support = \get_post_types_by_support( 'thumbnail' );

		// Just in case.
		if ( ! is_array( $post_types_with_thumbnail_support ) ) {
			$post_types_with_thumbnail_support = array();
		}

		foreach ( $all_post_types as $post_type ) {
			if ( ! isset( $post_types[ $post_type ] ) && in_array( $post_type, $post_types_with_thumbnail_support, true ) ) {
				$post_types[ $post_type ] = get_post_type_object( $post_type )->labels->name;
			}
		}

		return apply_filters(
			'rsfv_post_types_support',
			$post_types
		);
	}

	/**
	 * Get current compatibility engine.
	 *
	 * @return array
	 */
	public function get_current_compatibility_engine() {
		$plugin  = Plugin::get_instance();
		$options = Options::get_instance();

		$compatibility_engines = $plugin->theme_provider->get_selectable_engine_options();
		$current_engine        = $options->get( 'active-theme-engine' );

		return array(
			'engine' => $compatibility_engines[ $current_engine ] ?? $current_engine,
			'status' => 'disabled' !== $current_engine ? 'engine-active' : 'engine-inactive',
		);
	}

	/**
	 * Get settings array.
	 *
	 * @param string $current_section Current section ID.
	 *
	 * @return array
	 */
	public function get_settings( $current_section = '' ) {

		$post_types = $this->get_available_post_types();
		$plugin     = Plugin::get_instance();
		$options    = Options::get_instance();

		$compatibility_engines = $plugin->theme_provider->get_selectable_engine_options();
		$current_engine        = $this->get_current_compatibility_engine();

		$engine_description = '';

		if ( ! class_exists( '\RSFV_Pro\Plugin' ) ) {
			$pro_compatibility_engines = $plugin->theme_provider->get_selectable_pro_engine_options_promo();
			$compatibility_engine      = $options->get( 'theme-compatibility-engine' );
			$engine_description        = in_array( $compatibility_engine, array_keys( $pro_compatibility_engines ), true ) ? __( 'If you set a PRO compatibility engine in the Free version of the plugin, the compatibility will fall back to the Default engine.', 'rsfv' ) : '';
		}

		$default_enabled_post_types = apply_filters(
			'rsfv_default_enabled_post_types',
			array(
				'post' => true,
			)
		);

		$settings = array(
			array(
				'title' => esc_html_x( 'Theme Compatibility Engine', 'settings title', 'rsfv' ),
				'desc'  => __( 'If featured videos aren\'t working as expected in your theme, you may need to set this from the list of supported theme engines. (Default engine follows standard WordPress rules, and may not work for all themes)', 'rsfv' ),
				'class' => 'rsfv-theme-compatibility-engine',
				'type'  => 'content',
				'id'    => 'rsfv-theme-compatibility',
			),
			array(
				'type' => 'title',
				'id'   => 'rsfv_theme_support_title',
			),
			array(
				'title'   => __( 'Status', 'rsfv' ),
				'desc'    => '',
				'id'      => 'theme-engine-status',
				'default' => __( 'Auto', 'rsfv' ),
				'class'   => $current_engine['status'],
				'type'    => 'status',
				'current' => $current_engine['engine'],
			),
			array(
				'title'   => __( 'Set engine', 'rsfv' ),
				'desc'    => $engine_description,
				'id'      => 'theme-compatibility-engine',
				'class'   => 'rsfv-theme-compatibility-select',
				'default' => 'auto',
				'type'    => 'select',
				'options' => $compatibility_engines,
			),
			array(
				'type' => 'sectionend',
				'id'   => 'rsfv_theme_support_title',
			),
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
				'default' => $default_enabled_post_types,
				'type'    => 'multi-checkbox',
				'options' => $post_types,
			),
			array(
				'type' => 'sectionend',
				'id'   => 'rsfv_post_types_title',
			),
		);

		$settings = apply_filters(
			'rsfv_general_settings',
			$settings
		);

		return apply_filters( 'rsfv_get_settings_' . $this->id, $settings );
	}

	/**
	 * Sync theme compatibility via ajax.
	 */
	public function get_current_theme_compat_ajax() {
		check_ajax_referer( 'rsfv_admin_nonce', '_wpnonce' );

		$current_engine = $this->get_current_compatibility_engine();

		if ( ! is_array( $current_engine ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'Could not get current compatibility engine.', 'rsfv' ),
				)
			);
		}

		wp_send_json_success(
			$current_engine
		);
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
