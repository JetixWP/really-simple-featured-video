<?php
/**
 * Hover Autoplay feature utilities.
 *
 * @package RSFV
 */

namespace RSFV\Featuresets\Hover_Autoplay;

defined( 'ABSPATH' ) || exit;

use RSFV\Featuresets\Hover_Autoplay\Init as Hover_Autoplay;

/**
 * Class Utils
 */
class Utils {

	/**
	 * Add hover autoplay attributes to video HTML
	 *
	 * @param string $html Video HTML output.
	 * @param array  $video_data Video data array.
	 * @return string Modified HTML with hover attributes.
	 */
	public static function add_hover_attributes( $html, $video_data = array() ) {
		$settings = Hover_Autoplay::get_settings();

		if ( ! $settings['enable_hover_autoplay'] ) {
			return $html;
		}

		$hover_attrs = array(
			'data-rsfv-video="true"',
			'data-rsfv-hover-enabled="true"',
		);

		if ( isset( $video_data['type'] ) ) {
			$hover_attrs[] = 'data-rsfv-type="' . esc_attr( $video_data['type'] ) . '"';
		}

		if ( isset( $video_data['source'] ) ) {
			$hover_attrs[] = 'data-rsfv-source="' . esc_attr( $video_data['source'] ) . '"';
		}

		$hover_attrs_string = ' ' . implode( ' ', $hover_attrs );

		return preg_replace_callback(
			'/<div([^>]*class="[^"]*rsfv-[^"]*"[^>]*)>/i',
			function ( $matches ) use ( $hover_attrs_string ) {
				return '<div' . $matches[1] . $hover_attrs_string . '>';
			},
			$html
		);
	}

	/**
	 * Add play overlay to video
	 *
	 * @param string $html Video HTML output.
	 * @return string Modified HTML with play overlay.
	 */
	public static function add_play_overlay( $html ) {
		$settings = Hover_Autoplay::get_settings();

		if ( ! $settings['enable_hover_autoplay'] ) {
			return $html;
		}

		$overlay = '<div class="rsfv-play-overlay" aria-hidden="true"></div>';

		// Insert overlay before the last closing div tag.
		$last_div_pos = strrpos( $html, '</div>' );
		if ( false !== $last_div_pos ) {
			$html = substr_replace( $html, $overlay . '</div>', $last_div_pos, 6 );
		}

		return $html;
	}

	/**
	 * Get device-specific autoplay attributes for HTML5 video
	 *
	 * @param array $existing_attributes Existing attributes.
	 * @return array Modified attributes.
	 */
	public static function get_html5_attributes( $existing_attributes = array() ) {
		$settings = Hover_Autoplay::get_settings();

		if ( ! $settings['enable_hover_autoplay'] ) {
			return $existing_attributes;
		}

		$existing_attributes['preload'] = 'metadata';

		if ( ! isset( $existing_attributes['muted'] ) ) {
			$existing_attributes['muted'] = true;
		}

		$existing_attributes['playsinline'] = true;

		return $existing_attributes;
	}

	/**
	 * Modify iframe src for API support
	 *
	 * @param string $src Original iframe src URL.
	 * @param string $video_type Type of video (youtube, vimeo, dailymotion).
	 * @return string Modified iframe src URL.
	 */
	public static function enhance_iframe_src_old( $src, $video_type ) {
		$settings = Hover_Autoplay::get_settings();

		if ( ! $settings['enable_hover_autoplay'] ) {
			return $src;
		}

		$api_params = array();

		switch ( $video_type ) {
			case 'youtube':
				$api_params = array(
					'enablejsapi' => '1',
					'mute' => '1',
					'modestbranding' => '1',
				);
				break;

			case 'vimeo':
				$api_params = array(
					'api' => '1',
					'muted' => '1',
					'background' => '1',
				);
				break;

			case 'dailymotion':
				$api_params = array(
					'api' => 'postMessage',
					'mute' => '1',
				);
				break;
		}

		if ( ! empty( $api_params ) ) {
			foreach ( $api_params as $param => $value ) {
				$src = add_query_arg( $param, $value, $src );
			}
		}

		return $src;
	}

	/**
	 * Enhanced iframe src for better mobile support
	 *
	 * @param string $src Original iframe src.
	 * @param string $video_type Video type (youtube, vimeo, dailymotion).
	 * @return string Enhanced iframe src.
	 */
	public static function enhance_iframe_src( $src, $video_type ) {
		$settings = Hover_Autoplay::get_settings();

		if ( ! $settings['enable_hover_autoplay'] ) {
			return $src;
		}

		$is_mobile = wp_is_mobile();
		$api_params = array();

		switch ( $video_type ) {
			case 'youtube':
				$api_params = array(
					'enablejsapi' => '1',
					'origin' => urlencode( home_url() ),
					'modestbranding' => '1',
					'rel' => '0',
					'showinfo' => '0',
					'iv_load_policy' => '3', // Hide annotations.
					'playsinline' => '1', // Important for mobile.
				);

				// Mobile-specific parameters.
				if ( $is_mobile ) {
					$api_params['fs'] = '1'; // Allow fullscreen.
					$api_params['autoplay'] = '0'; // Disable autoplay on mobile initially.
					$api_params['mute'] = '1'; // Ensure muted for mobile autoplay.
				}
				break;

			case 'vimeo':
				$api_params = array(
					'api' => '1',
					'player_id' => 'rsfv_vimeo_' . wp_rand( 1000, 9999 ),
					'autopause' => '0',
					'background' => '0', // Don't use background mode on mobile.
				);

				if ( $is_mobile ) {
					$api_params['muted'] = '1';
					$api_params['playsinline'] = '1';
					$api_params['responsive'] = '1';
				}
				break;

			case 'dailymotion':
				$api_params = array(
					'api' => 'postMessage',
					'id' => 'rsfv_dm_' . wp_rand( 1000, 9999 ),
					'ui-highlight' => 'ffffff',
					'ui-logo' => '0',
					'sharing-enable' => '0',
				);

				if ( $is_mobile ) {
					$api_params['mute'] = '1';
					$api_params['webkit-playsinline'] = '1';
				}
				break;
		}

		// Add parameters to URL.
		if ( ! empty( $api_params ) ) {
			foreach ( $api_params as $param => $value ) {
				$src = add_query_arg( $param, $value, $src );
			}
		}

		return $src;
	}

	/**
	 * Generate responsive iframe wrapper
	 *
	 * @param string $iframe_html The iframe HTML code.
	 * @param string $aspect_ratio The aspect ratio (e.g., '16:9', '4:3').
	 * @return string The wrapped iframe HTML.
	 */
	public static function wrap_iframe( $iframe_html, $aspect_ratio = '16:9' ) {
		return sprintf(
			'<div class="rsfv-iframe-wrapper" data-aspect-ratio="%s">%s</div>',
			esc_attr( $aspect_ratio ),
			$iframe_html
		);
	}

	/**
	 * Add accessibility attributes
	 *
	 * @param string $html The HTML content.
	 * @return string The modified HTML with accessibility attributes.
	 */
	public static function add_accessibility_attributes( $html ) {
		$settings = Hover_Autoplay::get_settings();

		if ( ! $settings['enable_hover_autoplay'] || ! $settings['enable_focus_events'] ) {
			return $html;
		}

		// Add accessibility attributes to video tags.
		$html = preg_replace_callback(
			'/<video([^>]*)>/i',
			function ( $matches ) {
				$attrs = $matches[1] ?? false;
				$accessibility_attrs = '';

				if ( strpos( $attrs, 'role=' ) === false ) {
					$accessibility_attrs .= ' role="presentation"';
				}

				if ( strpos( $attrs, 'aria-label=' ) === false ) {
					$accessibility_attrs .= ' aria-label="' . esc_attr( __( 'Featured video - hover to play', 'really-simple-featured-video' ) ) . '"';
				}

				if ( strpos( $attrs, 'tabindex=' ) === false ) {
					$accessibility_attrs .= ' tabindex="0"';
				}

				return '<video' . $attrs . $accessibility_attrs . '>';
			},
			$html
		);

		// Add accessibility attributes to iframe tags.
		$html = preg_replace_callback(
			'/<iframe([^>]*)>/i',
			function ( $matches ) {
				$attrs = $matches[1] ?? false;
				$accessibility_attrs = '';

				if ( strpos( $attrs, 'role=' ) === false ) {
					$accessibility_attrs .= ' role="presentation"';
				}

				if ( strpos( $attrs, 'aria-label=' ) === false ) {
					$accessibility_attrs .= ' aria-label="' . esc_attr( __( 'Featured video - hover to play', 'really-simple-featured-video' ) ) . '"';
				}

				return '<iframe' . $attrs . $accessibility_attrs . '>';
			},
			$html
		);

		return $html;
	}

	/**
	 * Check if current device supports autoplay
	 *
	 * @return bool True if device supports autoplay, false otherwise.
	 */
	public static function device_supports_autoplay() {
		$user_agent = '';
		if ( isset( $_SERVER['HTTP_USER_AGENT'] ) ) {
			$user_agent = sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) );
		}

		if ( preg_match( '/iPhone|iPad/i', $user_agent ) ) {
			return false;
		}

		if ( preg_match( '/Android (\d+\.\d+)/i', $user_agent, $matches ) ) {
			if ( isset( $matches[1] ) && floatval( $matches[1] ) < 5.0 ) {
				return false;
			}
		}

		return true;
	}
}
