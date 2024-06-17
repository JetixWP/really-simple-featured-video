<?php
/**
 * Settings handler.
 *
 * @package RSFV
 */

namespace RSFV\Settings;

use RSFV\Options;

/**
 * Register Settings.
 */
class Register {
	/**
	 * Class instance
	 *
	 * @var $instance
	 */
	protected static $instance;

	/**
	 * Class constructor.
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'register_menu' ) );

		// Handle saving settings earlier than load-{page} hook to avoid race conditions in conditional menus.
		add_action( 'wp_loaded', array( $this, 'save_settings' ) );

		add_action( 'init', array( $this, 'create_options' ) );

		add_action( 'load-settings_page_rsfv-settings', array( $this, 'cleanup_plugin_settings_page' ) );
	}

	/**
	 * Get a class instance.
	 *
	 * @return Register
	 */
	public static function get_instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Register plugin menu.
	 *
	 * @return void
	 */
	public function register_menu() {
		add_submenu_page(
			'options-general.php',
			__( 'Really Simple Featured Video Settings', 'rsfv' ),
			__( 'Really Simple Featured Video', 'rsfv' ),
			'manage_options',
			'rsfv-settings',
			array( $this, 'settings_page' )
		);
	}

	/**
	 * Add settings page.
	 *
	 * @return void
	 */
	public function settings_page() {
		Admin_Settings::output();
	}

	/**
	 * Default options.
	 *
	 * Sets up the default options used on the settings page.
	 */
	public function create_options() {
		if ( ! is_admin() ) {
			return false;
		}

		// Include settings so that we can run through defaults.
		include RSFV_PLUGIN_DIR . 'includes/Settings/class-admin-settings.php';

		$settings = Admin_Settings::get_settings_pages();

		foreach ( $settings as $section ) {
			if ( 'object' !== gettype( $section ) || ! method_exists( $section, 'get_settings' ) ) {
				continue;
			}
			$subsections = array_unique( array_merge( array( '' ), array_keys( $section->get_sections() ) ) );

			foreach ( $subsections as $subsection ) {
				foreach ( $section->get_settings( $subsection ) as $value ) {
					if ( isset( $value['default'], $value['id'] ) ) {
						$autoload = isset( $value['autoload'] ) ? (bool) $value['autoload'] : true;
						add_option( $value['id'], $value['default'], '', ( $autoload ? 'yes' : 'no' ) );
					}
				}
			}
		}
	}

	/**
	 * Handle saving of settings.
	 *
	 * @return void
	 */
	public function save_settings() {
		global $current_tab, $current_section;

		// We should only save on the settings page.
		if ( ! is_admin() || ! isset( $_GET['page'] ) || 'rsfv-settings' !== $_GET['page'] ) {
			return;
		}

		// Include settings pages.
		Admin_Settings::get_settings_pages();

		// Get current tab/section.
		$current_tab     = empty( $_GET['tab'] ) ? 'general' : sanitize_title( wp_unslash( $_GET['tab'] ) );
		$current_section = empty( $_REQUEST['section'] ) ? '' : sanitize_title( wp_unslash( $_REQUEST['section'] ) );
		$nonce           = isset( $_POST['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) ) : '';

		// Save settings if data has been posted.
		if ( wp_verify_nonce( $nonce, 'rsfv-settings' ) ) {
			if ( '' !== $current_section && apply_filters( "rsfv_save_settings_{$current_tab}_{$current_section}", ! empty( $_POST['save'] ) ) ) {
				Admin_Settings::save();
			} elseif ( '' === $current_section && apply_filters( "rsfv_save_settings_{$current_tab}", ! empty( $_POST['save'] ) || isset( $_POST['rsfv-license_activate'] ) ) ) {
				Admin_Settings::save();
			}
		}
	}

	/**
	 * Remove all notices from settings page for a clean and minimal look.
	 *
	 * @return void
	 */
	public function cleanup_plugin_settings_page() {
		remove_all_actions( 'admin_notices' );
	}
}

/**
 * Clean variables using sanitize_text_field. Arrays are cleaned recursively.
 * Non-scalar values are ignored.
 *
 * @param string|array $var Data to sanitize.
 * @return string|array
 */
function rsfv_clean( $var ) {
	if ( is_array( $var ) ) {
		return array_map( __NAMESPACE__ . '\rsfv_clean', $var );
	}

	return is_scalar( $var ) ? sanitize_text_field( $var ) : $var;
}

/**
 * Output admin fields.
 *
 * Loops though the RSFV options array and outputs each field.
 *
 * @param array $options Opens array to output.
 */
function rsfv_admin_fields( $options ) {

	if ( ! class_exists( 'Admin_Settings', false ) ) {
		include __DIR__ . '/class-admin-settings.php';
	}

	Admin_Settings::output_fields( $options );
}

/**
 * Update all settings which are passed.
 *
 * @param array $options Option fields to save.
 * @param array $data Passed data.
 */
function rsfv_update_options( $options, $data = null ) {

	if ( ! class_exists( 'Admin_Settings', false ) ) {
		include __DIR__ . '/class-admin-settings.php';
	}

	Admin_Settings::save_fields( $options, $data );
}

/**
 * Get a setting from the settings API.
 *
 * @param mixed $option_name Option name to save.
 * @param mixed $default Default value to save.
 * @return string
 */
function rsfv_settings_get_option( $option_name, $default = '' ) {

	if ( ! class_exists( 'Admin_Settings', false ) ) {
		include __DIR__ . '/class-admin-settings.php';
	}

	return Admin_Settings::get_option( $option_name, $default );
}

/**
 * Get enabled post types.
 *
 * @return array
 */
function get_post_types() {
	$post_types = Options::get_instance()->get( 'post_types' );
	$post_types = is_array( $post_types ) ? array_keys( $post_types ) : '';

	if ( ! is_array( $post_types ) && empty( $post_types ) ) {
		$post_types = array( 'post' );
	}

	return apply_filters( 'rsfv_get_enabled_post_types', $post_types );
}

/**
 * Get default video controls.
 *
 * @return array
 */
function get_default_video_controls() {
	return array(
		'controls' => true,
	);
}

/**
 * Get enabled video controls.
 *
 * @param string $type Type of video.
 *
 * @return array
 */
function get_video_controls( $type = 'self' ) {
	$defaults = get_default_video_controls();

	if ( 'self' === $type ) {
		$controls = Options::get_instance()->get( 'self_video_controls' );
	} else {
		$controls = Options::get_instance()->get( 'embed_video_controls' );
	}

	return is_array( $controls ) && ! empty( $controls ) ? $controls : $defaults;
}
