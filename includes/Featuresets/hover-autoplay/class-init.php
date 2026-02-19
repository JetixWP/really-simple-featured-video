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
	 *
	 * @return array
	 */
	public static function get_settings() {
		$default_settings = array(
			'enable_hover_autoplay'    => false,
			'video_types'              => array(
				'html5'       => true,
				'youtube'     => true,
				'vimeo'       => false,
				'dailymotion' => false,
			),
			'enable_on_desktop'        => true,
			'enable_on_mobile'         => true,
			'mobile_breakpoint'        => 768,
			'hover_delay'              => 100,
			'respect_user_preferences' => true,
			'enable_focus_events'      => true,
			'debug_mode'               => false,
		);

		$options = Options::get_instance();

		// Get video types settings.
		$has_hover_autoplay_video_types = $options->has( 'hover_autoplay_video_types' );
		$hover_autoplay_video_types     = $options->get(
			'hover_autoplay_video_types',
			array(
				'html5'       => true,
				'youtube'     => true,
				'vimeo'       => false,
				'dailymotion' => false,
			)
		);

		// Match with controls option keys.
		$settings = apply_filters(
			'rsfv_hover_autoplay_options',
			array(
				'enable_hover_autoplay' => $options->get( 'enable_hover_autoplay', false ),
				'video_types'           => array(
					'html5'       => $has_hover_autoplay_video_types ? $hover_autoplay_video_types['html5'] ?? false : true,
					'youtube'     => $has_hover_autoplay_video_types ? $hover_autoplay_video_types['youtube'] ?? false : true,
					'vimeo'       => $has_hover_autoplay_video_types ? $hover_autoplay_video_types['vimeo'] ?? false : false,
					'dailymotion' => $has_hover_autoplay_video_types ? $hover_autoplay_video_types['dailymotion'] ?? false : false,
				),
				'debug_mode'            => false, // Not implemented yet.
			)
		);

		return wp_parse_args( $settings, $default_settings );
	}

	/**
	 * Enqueue scripts.
	 *
	 * @return void
	 */
	public function enqueue_scripts() {
		$settings = self::get_settings();

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
			'enableOnDesktop'        => $settings['enable_on_desktop'],
			'enableOnMobile'         => $settings['enable_on_mobile'],
			'mobileBreakpoint'       => $settings['mobile_breakpoint'],
			'hoverDelay'             => $settings['hover_delay'],
			'respectUserPreferences' => $settings['respect_user_preferences'],
			'enableFocusEvents'      => $settings['enable_focus_events'],
			'debugMode'              => $settings['debug_mode'],
			'videoTypes'             => $settings['video_types'],
			'extraSelectors'         => ! empty( $settings['extra_selectors'] ) ? $settings['extra_selectors'] : '',
		);

		wp_localize_script( 'rsfv-hover-autoplay', 'RSFVHoverAutoplaySettings', $script_data );

		// Enqueue script.
		wp_enqueue_script( 'rsfv-hover-autoplay' );

		// Add inline CSS for mobile-specific styles.
		$mobile_css = $this->get_mobile_css( $settings );
		if ( ! empty( $mobile_css ) ) {
			wp_add_inline_style( 'rsfv-hover-autoplay', $mobile_css );
		}
	}

	/**
	 * Get mobile-specific CSS
	 *
	 * @param array $settings Current settings.
	 * @return string CSS styles
	 */
	private function get_mobile_css( $settings ) {
		if ( ! $settings['enable_hover_autoplay'] ) {
			return '';
		}

		$mobile_breakpoint = absint( $settings['mobile_breakpoint'] ?? 768 );

		return "
    /* Mobile Iframe Video Enhancements */
    @media (max-width: {$mobile_breakpoint}px) {
        .rsfv-iframe-wrapper {
            position: relative;
            width: 100%;
            height: 0;
            padding-bottom: 56.25%; /* 16:9 aspect ratio */
            overflow: hidden;
            border-radius: 8px;
        }
        
        .rsfv-iframe-wrapper iframe {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            border: none;
            border-radius: 8px;
        }
        
        /* Enhanced mobile iframe containers */
        [data-rsfv-video] .rsfv-iframe-wrapper {
            -webkit-tap-highlight-color: transparent;
            touch-action: manipulation;
            cursor: pointer;
        }
        
        /* Mobile overlay for iframe interaction */
        [data-rsfv-video] .rsfv-mobile-overlay {
            position: absolute !important;
            top: 0 !important;
            left: 0 !important;
            width: 100% !important;
            height: 100% !important;
            z-index: 10 !important;
            background: transparent !important;
            cursor: pointer !important;
            border-radius: 8px;
        }
        
        /* Hide overlay when video is playing */
        [data-state='playing'] .rsfv-mobile-overlay {
            display: none !important;
        }
        
        /* Mobile iframe touch feedback */
        [data-rsfv-video]:active .rsfv-iframe-wrapper {
            transform: scale(0.98);
            transition: transform 0.1s ease;
        }
    }
    
    /* Tablet specific iframe styles */
    @media (min-width: 481px) and (max-width: 768px) {
        [data-rsfv-video]:hover .rsfv-iframe-wrapper {
            transform: scale(1.02);
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
            transition: all 0.2s ease;
        }
    }
    ";
	}
}

// Initialize Hover Autoplay Featureset.
Init::get_instance();
