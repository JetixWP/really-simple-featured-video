<?php
/**
 * Hover Autoplay feature handler.
 *
 * @package RSFV
 */

namespace RSFV\Featuresets\Hover_Autoplay;

use RSFV\Options;

defined( 'ABSPATH' ) || exit;

/**
 * Class Init
 *
 * @package RSFV
 */
class Init {
	/**
	 * Class instance.
	 *
	 * @var $instance
	 */
	protected static $instance;

	/**
	 * Get a class instance.
	 *
	 * @return Init
	 */
	public static function get_instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
	}

	/**
	 * Get current settings with defaults
	 */
	public function get_settings() {

		$default_settings = array(
			'enable_hover_autoplay' => false,
			'enable_on_desktop' => true,
			'enable_on_mobile' => true,
			'mobile_breakpoint' => 768,
			'hover_delay' => 200,
			'respect_user_preferences' => true,
			'enable_focus_events' => true,
			'debug_mode' => false,
			'video_types' => array(
				'html5' => true,
				'youtube' => true,
				'vimeo' => false,
				'dailymotion' => false,
			),
		);

		$options = Options::get_instance();

		// Get screen sizes settings.
		$hover_autoplay_screens = $options->get(
			'hover_autoplay_screens',
			array(
				'desktop' => true,
				'mobile' => true,
			)
		);

		// Get video types settings.
		$hover_autoplay_video_types = $options->get(
			'hover_autoplay_video_types',
			array(
				'html5' => true,
				'youtube' => true,
				'vimeo' => false,
				'dailymotion' => false,
			)
		);

		// Match with controls option keys.
		$settings = array(
			'enable_hover_autoplay' => $options->get( 'enable_hover_autoplay', false ), // works.
			'enable_on_desktop' => $hover_autoplay_screens['desktop'] || false, // works.
			'enable_on_mobile' => $hover_autoplay_screens['mobile'] || false, // works.
			'mobile_breakpoint' => $options->get( 'hover_autoplay_mobile_breakpoint', 768 ), // works.
			'hover_delay' => $options->get( 'hover_autoplay_delay', 200 ), // works.
			'respect_user_preferences' => $options->get( 'hover_autoplay_respect_user_prefs', true ), // works.
			'enable_focus_events' => $options->get( 'hover_autoplay_focus_events', true ), // works.
			'debug_mode' => false, // Not implemented yet.
			'video_types' => array(
				'html5' => $hover_autoplay_video_types['html5'] ?? false,
				'youtube' => $hover_autoplay_video_types['youtube'] ?? false,
				'vimeo' => $hover_autoplay_video_types['vimeo'] ?? false,
				'dailymotion' => $hover_autoplay_video_types['dailymotion'] ?? false,
			), // works.
		);

		return wp_parse_args( $settings, $default_settings );
	}

	/**
	 * Enqueue scripts.
	 *
	 * @return void
	 */
	public function enqueue_scripts() {
		$settings = $this->get_settings();

		ray( $settings );

		// Only continue if hover autoplay is enabled.
		if ( ! $settings['enable_hover_autoplay'] ) {
				return;
		}

		// Register style.
		wp_register_style(
			'rsfv-hover-autoplay-css',
			RSFV_PLUGIN_URL . 'assets/css/hover-autoplay.css',
			array(),
			filemtime( RSFV_PLUGIN_DIR . 'assets/css/hover-autoplay.css' )
		);

		// Enqueue style.
		wp_enqueue_style( 'rsfv-hover-autoplay-css' );

		// Register script.
		wp_register_script( 'rsfv-hover-autoplay', RSFV_PLUGIN_URL . 'assets/js/hover-autoplay.js', array( 'jquery' ), filemtime( RSFV_PLUGIN_DIR . 'assets/js/hover-autoplay.js' ), true );

		// Localize script with settings.
		$script_data = array(
			'enableOnDesktop' => $settings['enable_on_desktop'],
			'enableOnMobile' => $settings['enable_on_mobile'],
			'mobileBreakpoint' => $settings['mobile_breakpoint'],
			'hoverDelay' => $settings['hover_delay'],
			'respectUserPreferences' => $settings['respect_user_preferences'],
			'enableFocusEvents' => $settings['enable_focus_events'],
			'debugMode' => $settings['debug_mode'],
			'videoTypes' => $settings['video_types'],
		);

		wp_localize_script( 'rsfv-hover-autoplay', 'RSFVHoverAutoplaySettings', $script_data );

		// Enqueue script.
		wp_enqueue_script( 'rsfv-hover-autoplay' );
	}
}
